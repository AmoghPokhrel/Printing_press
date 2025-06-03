<?php
// Database connection is required
require_once 'db.php';

/**
 * Track a new payment request
 */
function trackPaymentRequest($orderId, $transactionUuid, $amount)
{
    global $conn;
    $sql = "INSERT INTO payment_tracking (order_id, transaction_uuid, amount) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isd", $orderId, $transactionUuid, $amount);
    return $stmt->execute();
}

/**
 * Get order ID using transaction UUID
 */
function getOrderIdByTransactionUuid($transactionUuid)
{
    global $conn;
    $sql = "SELECT order_id FROM payment_tracking WHERE transaction_uuid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $transactionUuid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['order_id'];
    }
    return null;
}

/**
 * Update payment status
 */
function updatePaymentStatus($transactionUuid, $status)
{
    global $conn;
    $sql = "UPDATE payment_tracking SET status = ? WHERE transaction_uuid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $status, $transactionUuid);
    return $stmt->execute();
}

/**
 * Process eSewa payment
 */
function processEsewaPayment($paymentData)
{
    // When initiating payment
    if (isset($paymentData['oid']) && isset($paymentData['transaction_uuid'])) {
        trackPaymentRequest($paymentData['oid'], $paymentData['transaction_uuid'], $paymentData['amount']);
    }

    // When receiving payment response
    if (isset($paymentData['transaction_uuid'])) {
        $orderId = getOrderIdByTransactionUuid($paymentData['transaction_uuid']);
        if ($orderId) {
            // Update payment status
            updatePaymentStatus($paymentData['transaction_uuid'], 'completed');
            return [
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Payment processed successfully'
            ];
        }
    }

    return [
        'success' => false,
        'message' => 'Order ID not found in payment tracking'
    ];
}