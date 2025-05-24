<?php
session_start();
require_once '../includes/dbcon.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// This would typically come from your payment gateway
$payment_successful = true; // Replace with actual payment verification
$subscription_type = 'premium';
$duration_months = 1;

if ($payment_successful) {
    try {
        $pdo->beginTransaction();

        // Calculate end date
        $end_date = date('Y-m-d H:i:s', strtotime("+$duration_months months"));

        // Insert new subscription record
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (user_id, status, subscription_type, start_date, end_date)
            VALUES (?, 'active', ?, CURRENT_TIMESTAMP, ?)
        ");
        $stmt->execute([$user_id, $subscription_type, $end_date]);

        // Reset usage limits
        $stmt = $pdo->prepare("
            INSERT INTO subscription_limits (user_id, custom_design_count, template_modification_count)
            VALUES (?, 0, 0)
            ON DUPLICATE KEY UPDATE 
            custom_design_count = 0,
            template_modification_count = 0
        ");
        $stmt->execute([$user_id]);

        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Subscription updated successfully',
            'end_date' => $end_date
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error updating subscription: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Payment verification failed'
    ]);
}
?>