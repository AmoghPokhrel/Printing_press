<?php

require_once 'PaymentInterface.php';

class SubscriptionPaymentHandler
{
    private PaymentInterface $paymentGateway;
    private $db;
    private $transactionStarted = false;

    public function __construct(PaymentInterface $paymentGateway, $db)
    {
        $this->paymentGateway = $paymentGateway;
        $this->db = $db;
    }

    public function generatePaymentForm(float $amount, string $successUrl, string $failureUrl): array
    {
        return $this->paymentGateway->generatePaymentForm($amount, $successUrl, $failureUrl);
    }

    public function processPaymentResponse(array $responseData, int $userId): array
    {
        try {
            if (!$this->paymentGateway->verifyPayment($responseData)) {
                throw new Exception("Subscription payment verification failed");
            }

            // Get the decoded amount from the response
            $amount = 0;
            $transactionId = '';
            if (isset($responseData['data'])) {
                $decodedData = base64_decode($responseData['data']);
                if ($decodedData !== false) {
                    $jsonData = json_decode($decodedData, true);
                    if (isset($jsonData['total_amount'])) {
                        $amount = floatval($jsonData['total_amount']);
                    }
                    if (isset($jsonData['transaction_code'])) {
                        $transactionId = $jsonData['transaction_code'];
                    }
                }
            }

            error_log("Processing subscription payment - Amount: $amount, Transaction ID: $transactionId, User ID: $userId");

            // Start transaction
            $this->db->autocommit(FALSE);
            $this->transactionStarted = true;

            // Calculate subscription end date (1 month from now)
            $endDate = date('Y-m-d H:i:s', strtotime("+1 month"));

            // First check if tables exist
            $this->ensureTablesExist();

            // Insert new subscription record
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions (
                    user_id,
                    status,
                    subscription_type,
                    start_date,
                    end_date,
                    payment_reference
                ) VALUES (?, 'active', 'premium', NOW(), ?, ?)
            ");

            if ($stmt === false) {
                throw new Exception("Failed to prepare subscription insert statement: " . $this->db->error);
            }

            $stmt->bind_param("iss", $userId, $endDate, $transactionId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create subscription record: " . $stmt->error);
            }
            $stmt->close();

            // Reset usage limits
            $stmt = $this->db->prepare("
                INSERT INTO subscription_limits (
                    user_id, 
                    custom_design_count, 
                    template_modification_count
                ) VALUES (?, 0, 0)
                ON DUPLICATE KEY UPDATE 
                custom_design_count = 0,
                template_modification_count = 0
            ");

            if ($stmt === false) {
                throw new Exception("Failed to prepare limits update statement: " . $this->db->error);
            }

            $stmt->bind_param("i", $userId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to reset usage limits: " . $stmt->error);
            }
            $stmt->close();

            // Record subscription payment
            $stmt = $this->db->prepare("
                INSERT INTO subscription_payments (
                    user_id,
                    amount,
                    payment_method,
                    transaction_id,
                    status,
                    payment_date
                ) VALUES (?, ?, 'esewa', ?, 'completed', NOW())
            ");

            if ($stmt === false) {
                throw new Exception("Failed to prepare payment record statement: " . $this->db->error);
            }

            $stmt->bind_param("ids", $userId, $amount, $transactionId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to record subscription payment: " . $stmt->error);
            }
            $stmt->close();

            // Commit transaction
            if (!$this->db->commit()) {
                throw new Exception("Failed to commit transaction: " . $this->db->error);
            }
            $this->db->autocommit(TRUE);
            $this->transactionStarted = false;

            return [
                'success' => true,
                'message' => 'Subscription payment processed successfully',
                'end_date' => $endDate
            ];

        } catch (Exception $e) {
            error_log("Subscription payment error: " . $e->getMessage());
            if ($this->transactionStarted) {
                $this->db->rollback();
                $this->db->autocommit(TRUE);
                $this->transactionStarted = false;
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function ensureTablesExist(): void
    {
        // Check if subscriptions table exists
        $result = $this->db->query("SHOW TABLES LIKE 'subscriptions'");
        if ($result->num_rows === 0) {
            throw new Exception("Subscriptions table does not exist. Please run the database setup script.");
        }

        // Check if subscription_limits table exists
        $result = $this->db->query("SHOW TABLES LIKE 'subscription_limits'");
        if ($result->num_rows === 0) {
            throw new Exception("Subscription limits table does not exist. Please run the database setup script.");
        }

        // Check if subscription_payments table exists
        $result = $this->db->query("SHOW TABLES LIKE 'subscription_payments'");
        if ($result->num_rows === 0) {
            throw new Exception("Subscription payments table does not exist. Please run the database setup script.");
        }
    }

    public function recordFailedPayment(int $userId, float $amount): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscription_payments (
                    user_id,
                    amount,
                    payment_method,
                    status,
                    payment_date
                ) VALUES (?, ?, 'esewa', 'failed', NOW())
            ");

            if ($stmt === false) {
                throw new Exception("Failed to prepare failed payment statement: " . $this->db->error);
            }

            $stmt->bind_param("id", $userId, $amount);
            if (!$stmt->execute()) {
                throw new Exception("Failed to record failed payment: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Failed to record failed subscription payment: " . $e->getMessage());
        }
    }

    public function getCurrentSubscription(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT status, subscription_type, end_date 
                FROM subscriptions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");

            if ($stmt === false) {
                throw new Exception("Failed to prepare get subscription statement: " . $this->db->error);
            }

            $stmt->bind_param("i", $userId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to get current subscription: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $subscription = $result->fetch_assoc();
            $stmt->close();
            return $subscription;
        } catch (Exception $e) {
            error_log("Error getting current subscription: " . $e->getMessage());
            return null;
        }
    }
}