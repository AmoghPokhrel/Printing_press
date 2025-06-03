<?php

interface PaymentInterface
{
    /**
     * Generate payment form with necessary fields
     *
     * @param float $amount The amount to be paid
     * @param string $successUrl URL to redirect after successful payment
     * @param string $failureUrl URL to redirect after failed payment
     * @param mixed|null $referenceId Optional reference ID (like order ID)
     * @return array Array containing form action URL and fields
     */
    public function generatePaymentForm(float $amount, string $successUrl, string $failureUrl, $referenceId = null): array;

    /**
     * Get the gateway URL
     *
     * @return string The payment gateway URL
     */
    public function getGatewayUrl(): string;

    /**
     * Verify the payment response
     *
     * @param array $responseData The response data from payment gateway
     * @return bool True if payment is verified, false otherwise
     */
    public function verifyPayment(array $responseData): bool;
}