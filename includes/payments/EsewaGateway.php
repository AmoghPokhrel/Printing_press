<?php

require_once 'PaymentInterface.php';

class EsewaGateway implements PaymentInterface
{
    private string $merchantCode;
    private string $secretKey;
    private bool $testMode;

    public function __construct(string $merchantCode = "EPAYTEST", string $secretKey = "8gBm/:&EnhH.1/q", bool $testMode = true)
    {
        $this->merchantCode = $merchantCode;
        $this->secretKey = $secretKey;
        $this->testMode = $testMode;
    }

    public function getGatewayUrl(): string
    {
        return $this->testMode
            ? 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'
            : 'https://epay.esewa.com.np/api/epay/main/v2/form';
    }

    private function generateUUID(): string
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

    private function generateSignature(float $totalAmount, string $transactionUUID): string
    {
        $message = "total_amount={$totalAmount},transaction_uuid={$transactionUUID},product_code={$this->merchantCode}";
        error_log("Generating signature with message: " . $message);
        return base64_encode(hash_hmac('sha256', $message, $this->secretKey, true));
    }

    public function generatePaymentForm(float $amount, string $successUrl, string $failureUrl, $referenceId = null): array
    {
        $tax = 0.00;
        $totalAmount = $amount + $tax;
        $transactionUUID = $this->generateUUID();

        // Create a state parameter that includes the order ID and payment type
        $state = [
            'oid' => $referenceId,
            'amount' => $amount,
            'payment_type' => 'order'
        ];

        $encodedState = base64_encode(json_encode($state));

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
            'state' => $encodedState
        ];

        // Add reference ID in multiple formats to ensure compatibility
        if ($referenceId !== null) {
            $fields['oid'] = $referenceId;
            $fields['reference_id'] = $referenceId;

            // Include order details in transaction data
            $transactionData = [
                'order_id' => $referenceId,
                'reference_id' => $referenceId,
                'payment_type' => 'order',
                'amount' => $amount
            ];
            $fields['transaction_data'] = base64_encode(json_encode($transactionData));
        }

        // Generate signature after all fields are set
        $fields['signature'] = $this->generateSignature($totalAmount, $transactionUUID);

        error_log("Generated eSewa payment form with fields: " . print_r($fields, true));
        return [
            'action_url' => $this->getGatewayUrl(),
            'fields' => $fields
        ];
    }

    private function decodeEsewaResponse(array $responseData): array
    {
        if (!isset($responseData['data'])) {
            error_log("No 'data' parameter found in eSewa response");
            return $responseData;
        }

        try {
            $decodedData = base64_decode($responseData['data']);
            if ($decodedData === false) {
                error_log("Failed to base64 decode eSewa response data");
                return $responseData;
            }

            $jsonData = json_decode($decodedData, true);
            if ($jsonData === null) {
                error_log("Failed to JSON decode eSewa response data: " . json_last_error_msg());
                return $responseData;
            }

            error_log("Decoded eSewa response: " . print_r($jsonData, true));
            return $jsonData;
        } catch (Exception $e) {
            error_log("Error decoding eSewa response: " . $e->getMessage());
            return $responseData;
        }
    }

    public function verifyPayment(array $responseData): bool
    {
        error_log("Starting payment verification with response data: " . print_r($responseData, true));

        // Decode the response data
        $decodedResponse = $this->decodeEsewaResponse($responseData);
        error_log("Decoded response data: " . print_r($decodedResponse, true));

        if ($this->testMode) {
            return $this->verifyTestPayment($decodedResponse);
        }
        return $this->verifyProductionPayment($decodedResponse);
    }

    private function verifyTestPayment(array $responseData): bool
    {
        error_log("Verifying test payment...");

        // Check for required fields in the new response format
        if (!isset($responseData['status']) || !isset($responseData['total_amount'])) {
            error_log("Missing required fields in decoded response");
            return false;
        }

        // Verify status is COMPLETE
        if ($responseData['status'] !== 'COMPLETE') {
            error_log("Payment status is not COMPLETE: " . $responseData['status']);
            return false;
        }

        // Verify product code matches
        if ($responseData['product_code'] !== $this->merchantCode) {
            error_log("Product code mismatch. Expected: {$this->merchantCode}, Got: {$responseData['product_code']}");
            return false;
        }

        error_log("Test payment verification successful");
        return true;
    }

    private function verifyProductionPayment(array $responseData): bool
    {
        error_log("Verifying production payment...");

        if (!isset($responseData['transaction_code']) || !isset($responseData['total_amount'])) {
            error_log("Missing required fields for production verification");
            return false;
        }

        $verifyData = [
            'merchantCode' => $this->merchantCode,
            'transactionId' => $responseData['transaction_code'],
            'amount' => $responseData['total_amount']
        ];

        error_log("Verification request data: " . print_r($verifyData, true));

        $signature = base64_encode(hash_hmac('sha256', json_encode($verifyData), $this->secretKey, true));
        $url = 'https://epay.esewa.com.np/api/epay/transaction/status';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
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

        error_log("eSewa API Response - HTTP Code: " . $httpCode);
        error_log("eSewa API Response Body: " . $response);

        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $verified = isset($result['status']) && $result['status'] === 'COMPLETE';
            error_log("Payment verification result: " . ($verified ? 'SUCCESS' : 'FAILED'));
            return $verified;
        }

        error_log("Payment verification failed with HTTP code: " . $httpCode);
        return false;
    }
}