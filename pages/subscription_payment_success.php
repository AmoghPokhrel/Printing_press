<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/payments/EsewaGateway.php';
require_once '../includes/payments/SubscriptionPaymentHandler.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize payment handlers
$esewaGateway = new EsewaGateway();
$subscriptionPaymentHandler = new SubscriptionPaymentHandler($esewaGateway, $conn);

// Process payment response
$result = $subscriptionPaymentHandler->processPaymentResponse($_GET, $_SESSION['user_id']);

if ($result['success']) {
    $_SESSION['success_message'] = "Subscription payment successful! You can now start creating custom templates.";
    header('Location: custom_template.php');
} else {
    $_SESSION['error_message'] = "Subscription payment failed: " . $result['message'];
    header('Location: subscription.php');
}
exit();