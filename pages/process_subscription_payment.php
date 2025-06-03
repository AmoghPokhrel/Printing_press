<?php
session_start();
require_once '../includes/dbcon.php';
require_once '../includes/payments/EsewaGateway.php';
require_once '../includes/payments/SubscriptionPaymentHandler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Initialize payment handlers
    $esewaGateway = new EsewaGateway();
    $subscriptionPaymentHandler = new SubscriptionPaymentHandler($esewaGateway, $pdo);

    // Process payment response
    $result = $subscriptionPaymentHandler->processPaymentResponse($_GET, $_SESSION['user_id']);

    if ($result['success']) {
        $_SESSION['success_message'] = "Subscription payment successful! You can now start creating custom templates.";
        header('Location: subscription.php');
    } else {
        $_SESSION['error_message'] = "Subscription payment failed: " . $result['message'];
        header('Location: subscription.php');
    }
} catch (Exception $e) {
    error_log("[Subscription] Payment processing error: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while processing your payment. Please try again later.";
    header('Location: subscription.php');
}
exit();