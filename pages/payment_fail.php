<?php
session_start();
require_once '../includes/dbcon.php';
require_once '../includes/payments/EsewaGateway.php';
require_once '../includes/payments/OrderPaymentHandler.php';
require_once '../includes/payments/SubscriptionPaymentHandler.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Log all available data for debugging
error_log("Payment Failure Handler - GET Data: " . print_r($_GET, true));
error_log("Payment Failure Handler - POST Data: " . print_r($_POST, true));

$esewaGateway = new EsewaGateway();
$orderPaymentHandler = new OrderPaymentHandler($esewaGateway, $pdo);
$subscriptionPaymentHandler = new SubscriptionPaymentHandler($esewaGateway, $pdo);

// Get payment type and order ID
$paymentType = $_GET['type'] ?? ($_POST['payment_type'] ?? null);
$orderId = $_GET['oid'] ?? ($_POST['oid'] ?? null);
$amount = $_GET['amt'] ?? ($_POST['amount'] ?? 0);

error_log("Payment Failure - Type: " . $paymentType . ", Order ID: " . $orderId . ", Amount: " . $amount);

if ($paymentType === 'order' && $orderId) {
    // Handle order payment failure
    error_log("Recording failed order payment for order ID: " . $orderId);
    $orderPaymentHandler->recordFailedPayment($orderId);
    $_SESSION['message'] = "Order payment failed. Please try again or contact support if the problem persists.";
    $_SESSION['message_type'] = "error";
    header('Location: your_orders.php');
} elseif ($paymentType === 'subscription') {
    // Handle subscription payment failure
    error_log("Recording failed subscription payment for user ID: " . $_SESSION['user_id']);
    $subscriptionPaymentHandler->recordFailedPayment($_SESSION['user_id'], $amount);
    $_SESSION['message'] = "Subscription payment failed. Please try again or contact support if the problem persists.";
    $_SESSION['message_type'] = "error";
    header('Location: subscription.php');
} else {
    // Default case - unknown payment type
    error_log("Unknown payment type or missing order ID");
    $_SESSION['message'] = "Payment failed. Please try again or contact support if the problem persists.";
    $_SESSION['message_type'] = "error";
    header('Location: your_orders.php');
}

exit();
?>