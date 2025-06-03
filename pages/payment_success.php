<?php
session_start();
require_once '../includes/db.php';  // This is our main database connection file
require_once '../includes/payment_functions.php';
require_once '../includes/payments/EsewaGateway.php';
require_once '../includes/payments/OrderPaymentHandler.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Log all available data for debugging
error_log("Payment Success Handler - Session Data: " . print_r($_SESSION, true));
error_log("Payment Success Handler - GET Data: " . print_r($_GET, true));
error_log("Payment Success Handler - POST Data: " . print_r($_POST, true));

try {
    // Get the response data
    $responseData = isset($_GET['data']) ? $_GET['data'] : null;

    if (!$responseData) {
        throw new Exception("No response data received");
    }

    error_log("Starting payment verification with response data: " . print_r(['data' => $responseData], true));

    // Decode base64 data
    $decodedData = base64_decode($responseData);
    if ($decodedData === false) {
        throw new Exception("Invalid base64 data received");
    }

    error_log("Successfully base64 decoded data: " . $decodedData);

    // Parse JSON data
    $paymentResponse = json_decode($decodedData, true);
    if (!$paymentResponse) {
        throw new Exception("Invalid JSON data received");
    }

    error_log("Decoded response data: " . print_r($paymentResponse, true));

    // Initialize payment handlers
    $esewaGateway = new EsewaGateway();
    $orderPaymentHandler = new OrderPaymentHandler($esewaGateway, $conn);

    // First verify the payment with eSewa
    if (!$esewaGateway->verifyPayment(['data' => $responseData])) {
        throw new Exception("Payment verification failed");
    }

    // Extract transaction UUID and order ID
    $transactionUuid = $paymentResponse['transaction_uuid'] ?? null;
    $orderId = null;

    // Try to get order ID from state parameter first
    if (isset($paymentResponse['state'])) {
        $state = json_decode(base64_decode($paymentResponse['state']), true);
        if ($state && isset($state['oid'])) {
            $orderId = $state['oid'];
            error_log("Found order ID in state parameter: " . $orderId);
        }
    }

    // If no order ID in state, try transaction data
    if (!$orderId && isset($paymentResponse['transaction_data'])) {
        $transactionData = json_decode(base64_decode($paymentResponse['transaction_data']), true);
        if ($transactionData && isset($transactionData['order_id'])) {
            $orderId = $transactionData['order_id'];
            error_log("Found order ID in transaction data: " . $orderId);
        }
    }

    // If still no order ID, try direct oid field
    if (!$orderId && isset($paymentResponse['oid'])) {
        $orderId = $paymentResponse['oid'];
        error_log("Found order ID in oid field: " . $orderId);
    }

    // If still no order ID, try looking up by transaction UUID
    if (!$orderId && $transactionUuid) {
        $orderId = getOrderIdByTransactionUuid($transactionUuid);
        error_log("Found order ID by transaction UUID lookup: " . $orderId);
    }

    if (!$orderId) {
        throw new Exception("Could not determine order ID from payment response");
    }

    // Process the payment using OrderPaymentHandler
    $result = $orderPaymentHandler->processPaymentResponse([
        'data' => $responseData,
        'oid' => $orderId
    ]);

    if ($result['success']) {
        $_SESSION['message'] = "Payment successful! Your order has been confirmed.";
        $_SESSION['message_type'] = "success";
        header("Location: your_orders.php");
        exit();
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    error_log("Payment Processing Error: " . $e->getMessage());
    $_SESSION['message'] = "Payment processing failed: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: payment_fail.php?error=" . urlencode($e->getMessage()));
    exit();
}