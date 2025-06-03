<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get order ID from request
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // Calculate total from order_item_line table
    $query = "SELECT SUM(total_price) as total_amount 
              FROM order_item_line 
              WHERE oid = ? AND status = 'ready'";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && isset($result['total_amount'])) {
        echo json_encode([
            'success' => true,
            'total' => floatval($result['total_amount'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No ready items found for this order'
        ]);
    }
} catch (Exception $e) {
    error_log("Error calculating order total: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error calculating order total'
    ]);
}