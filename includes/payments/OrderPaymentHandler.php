<?php

require_once 'PaymentInterface.php';

class OrderPaymentHandler
{
    private PaymentInterface $paymentGateway;
    private $db;
    private $transactionStarted = false;

    public function __construct(PaymentInterface $paymentGateway, $db)
    {
        $this->paymentGateway = $paymentGateway;
        $this->db = $db;
    }

    public function generatePaymentForm(float $amount, string $successUrl, string $failureUrl, int $orderId): array
    {
        // Store the order ID in session before redirecting to payment
        $_SESSION['pending_order_id'] = $orderId;
        error_log("Stored order ID in session: " . $orderId);

        // Append order ID to URLs
        $successUrl = $successUrl . (strpos($successUrl, '?') === false ? '?' : '&') . 'oid=' . $orderId;
        $failureUrl = $failureUrl . (strpos($failureUrl, '?') === false ? '?' : '&') . 'oid=' . $orderId;

        return $this->paymentGateway->generatePaymentForm($amount, $successUrl, $failureUrl, $orderId);
    }

    private function extractOrderId($responseData)
    {
        error_log("Attempting to extract order ID from response data: " . print_r($responseData, true));

        // Method 1: Try to get from direct parameters
        if (isset($responseData['oid'])) {
            error_log("Found order ID in direct oid parameter: " . $responseData['oid']);
            return $responseData['oid'];
        }

        // Method 2: Try to get from session
        if (isset($_SESSION['pending_order_id'])) {
            error_log("Found order ID in session: " . $_SESSION['pending_order_id']);
            return $_SESSION['pending_order_id'];
        }

        // Method 3: Try to get from the decoded base64 data
        if (isset($responseData['data'])) {
            error_log("Found 'data' parameter, attempting base64 decode");
            try {
                $decodedData = base64_decode($responseData['data']);
                if ($decodedData !== false) {
                    error_log("Successfully base64 decoded data: " . $decodedData);
                    $jsonData = json_decode($decodedData, true);

                    // If we have transaction_uuid, try to look up the order from our database
                    if (isset($jsonData['transaction_uuid'])) {
                        error_log("Looking up order by transaction UUID: " . $jsonData['transaction_uuid']);
                        $stmt = $this->db->prepare("
                            SELECT oil.id as order_item_id 
                            FROM order_item_line oil 
                            JOIN payments p ON p.order_item_id = oil.id 
                            WHERE p.transaction_id = ? OR p.transaction_id LIKE CONCAT(?, '%')
                            ORDER BY p.created_at DESC 
                            LIMIT 1
                        ");
                        $stmt->bind_param("ss", $jsonData['transaction_uuid'], $jsonData['transaction_uuid']);
                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                error_log("Found order ID from transaction UUID lookup: " . $row['order_item_id']);
                                return $row['order_item_id'];
                            }
                        }
                        error_log("No order found for transaction UUID: " . $jsonData['transaction_uuid']);
                    }
                }
            } catch (Exception $e) {
                error_log("Error processing data parameter: " . $e->getMessage());
            }
        }

        error_log("Failed to extract order ID using any method");
        return null;
    }

    public function processPaymentResponse(array $responseData): array
    {
        try {
            if (!$this->paymentGateway->verifyPayment($responseData)) {
                throw new Exception("Payment verification failed");
            }

            // Extract order ID using the new method
            $orderId = $this->extractOrderId($responseData);
            error_log("Extracted Order ID: " . ($orderId ?? 'null'));

            if (!$orderId) {
                throw new Exception("Order ID not found in payment response");
            }

            // Get the decoded amount from the response
            $amount = 0;
            if (isset($responseData['data'])) {
                $decodedData = base64_decode($responseData['data']);
                if ($decodedData !== false) {
                    $jsonData = json_decode($decodedData, true);
                    if (isset($jsonData['total_amount'])) {
                        $amount = floatval($jsonData['total_amount']);
                    }
                }
            }

            // Calculate total order amount
            $stmt = $this->db->prepare("
                SELECT SUM(total_price) as total_amount
                FROM order_item_line
                WHERE oid = ? AND status = 'ready'
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $orderAmount = $result['total_amount'];

            // Verify amount matches with a small tolerance for floating point comparison
            if (abs($orderAmount - $amount) > 0.01) {
                error_log("Amount mismatch - Order total: {$orderAmount}, Paid amount: {$amount}");
                throw new Exception("Payment amount mismatch");
            }

            // Start transaction
            $this->db->autocommit(FALSE);
            $this->transactionStarted = true;

            // Verify order exists and belongs to user
            $stmt = $this->db->prepare("
                SELECT o.*, oil.id as order_item_id 
                FROM `order` o 
                JOIN order_item_line oil ON o.id = oil.oid 
                WHERE o.id = ?
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new Exception("Order not found");
            }

            // Get transaction ID from the decoded response
            $transactionId = '';
            if (isset($responseData['data'])) {
                $decodedData = base64_decode($responseData['data']);
                if ($decodedData !== false) {
                    $jsonData = json_decode($decodedData, true);
                    if (isset($jsonData['transaction_code'])) {
                        $transactionId = $jsonData['transaction_code'];
                    }
                }
            }

            // Record payment for all order items
            $stmt = $this->db->prepare("
                INSERT INTO payments (
                    order_item_id,
                    amount,
                    payment_method,
                    transaction_id,
                    status,
                    payment_date
                ) 
                SELECT 
                    id,
                    total_price,
                    'esewa',
                    ?,
                    'completed',
                    NOW()
                FROM order_item_line
                WHERE oid = ? AND status = 'ready'
            ");
            $stmt->bind_param("si", $transactionId, $orderId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to record payment: " . $stmt->error);
            }

            // Commit transaction
            $this->db->commit();
            $this->db->autocommit(TRUE);
            $this->transactionStarted = false;

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'order_id' => $orderId
            ];

        } catch (Exception $e) {
            if ($this->transactionStarted) {
                $this->db->rollback();
                $this->db->autocommit(TRUE);
                $this->transactionStarted = false;
            }
            error_log("Payment processing error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function recordFailedPayment(int $orderId): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payments (
                    order_item_id,
                    amount,
                    payment_method,
                    status,
                    payment_date
                ) SELECT 
                    :order_id,
                    total_price,
                    'esewa',
                    'failed',
                    NOW()
                FROM order_item_line 
                WHERE id = :order_id_where
            ");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->bindParam(':order_id_where', $orderId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to record failed payment: " . $e->getMessage());
        }
    }
}