<?php
class EsewaPayment
{
    private string $merchantCode;
    private string $secretKey;
    private bool $testMode;

    public function __construct()
    {
        // Always use test mode for now
        $this->testMode = true;

        try {
            // Initialize credentials
            $this->initializeCredentials();

            // Log successful initialization
            error_log("[EsewaPayment] Successfully initialized with merchant code: {$this->merchantCode}");
        } catch (Exception $e) {
            error_log("[EsewaPayment] Initialization error: " . $e->getMessage());
            throw new Exception("Payment system initialization failed. Please contact support.");
        }
    }

    private function initializeCredentials(): void
    {
        // Set test credentials
        $this->merchantCode = "EPAYTEST";
        $this->secretKey = "8gBm/:&EnhH.1/q";

        // Validate credentials
        if (empty($this->merchantCode) || trim($this->merchantCode) === '') {
            error_log("[EsewaPayment] Empty merchant code");
            throw new Exception("Invalid merchant configuration");
        }

        if (empty($this->secretKey) || trim($this->secretKey) === '') {
            error_log("[EsewaPayment] Empty secret key");
            throw new Exception("Invalid merchant configuration");
        }
    }

    public function getPaymentUrl(): string
    {
        return $this->testMode
            ? 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'
            : 'https://epay.esewa.com.np/api/epay/main/v2/form';
    }

    public function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public function generateSignature(float $totalAmount, string $transactionUUID): string
    {
        try {
            // Validate inputs
            if ($totalAmount <= 0) {
                throw new Exception("Invalid amount");
            }

            if (empty($transactionUUID)) {
                throw new Exception("Invalid transaction UUID");
            }

            // Generate signature
            $message = "total_amount={$totalAmount},transaction_uuid={$transactionUUID},product_code={$this->merchantCode}";
            error_log("[EsewaPayment] Generating signature with message: " . $message);

            $signature = base64_encode(hash_hmac('sha256', $message, $this->secretKey, true));
            error_log("[EsewaPayment] Generated signature: " . $signature);

            return $signature;
        } catch (Exception $e) {
            error_log("[EsewaPayment] Signature generation error: " . $e->getMessage());
            throw new Exception("Payment processing error. Please try again.");
        }
    }

    public function getPaymentForm(float $amount, string $successUrl, string $failureUrl, string $order_item_id = null): array
    {
        try {
            // Validate inputs
            if ($amount <= 0) {
                throw new Exception("Invalid payment amount");
            }

            if (empty($successUrl) || empty($failureUrl)) {
                throw new Exception("Invalid return URLs");
            }

            $tax = 0.00;
            $totalAmount = $amount + $tax;
            $transactionUUID = $this->generateUUID();

            // Generate signature
            $signature = $this->generateSignature($totalAmount, $transactionUUID);

            // Prepare payment fields
            $fields = [
                'amount' => $amount,
                'tax_amount' => $tax,
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUUID,
                'product_code' => $this->merchantCode,
                'product_service_charge' => 0,
                'product_delivery_charge' => 0,
                'success_url' => $successUrl,
                'failure_url' => $failureUrl,
                'signed_field_names' => 'total_amount,transaction_uuid,product_code',
                'signature' => $signature,
                'payment_type' => 'subscription'
            ];

            if ($order_item_id !== null) {
                $fields['oid'] = $order_item_id;
            }

            error_log("[EsewaPayment] Payment form generated: " . print_r($fields, true));

            return [
                'action_url' => $this->getPaymentUrl(),
                'fields' => $fields
            ];
        } catch (Exception $e) {
            error_log("[EsewaPayment] Payment form generation error: " . $e->getMessage());
            throw new Exception("Unable to process payment request. Please try again.");
        }
    }

    public function verifyPaymentResponse(array $data): bool
    {
        error_log("[EsewaPayment] Verifying payment response: " . print_r($data, true));

        try {
            if ($this->testMode) {
                return $this->verifyTestPayment($data);
            }
            return $this->verifyProductionPayment($data);
        } catch (Exception $e) {
            error_log("[EsewaPayment] Payment verification error: " . $e->getMessage());
            return false;
        }
    }

    private function verifyTestPayment(array $data): bool
    {
        if (!isset($data['status']) || !isset($data['total_amount'])) {
            error_log("[EsewaPayment] Missing required fields in test response");
            return false;
        }

        error_log("[EsewaPayment] Test payment status: " . $data['status']);
        return $data['status'] === 'COMPLETE';
    }

    private function verifyProductionPayment(array $data): bool
    {
        if (!isset($data['refId']) || !isset($data['total_amount'])) {
            error_log("[EsewaPayment] Missing required fields in production response");
            return false;
        }

        $verifyData = [
            'merchantCode' => $this->merchantCode,
            'transactionId' => $data['refId'],
            'amount' => $data['total_amount']
        ];

        $signature = base64_encode(hash_hmac('sha256', json_encode($verifyData), $this->secretKey, true));

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://epay.esewa.com.np/api/epay/transaction/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($verifyData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'merchantCode: ' . $this->merchantCode,
                'signature: ' . $signature
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("[EsewaPayment] API Response - HTTP Code: " . $httpCode);
        error_log("[EsewaPayment] API Response Body: " . $response);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return isset($result['status']) && $result['status'] === 'COMPLETE';
        }

        return false;
    }
}