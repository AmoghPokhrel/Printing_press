<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['orderId']) || !isset($data['transactionId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$orderId = intval($data['orderId']);
$transactionId = trim($data['transactionId']);

// Query to verify transaction ID
$query = "SELECT p.transaction_id 
          FROM order_item_line oil
          JOIN payments p ON oil.id = p.order_item_id
          WHERE oil.oid = ? AND p.status = 'completed'
          AND p.transaction_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('is', $orderId, $transactionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Transaction verified successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID for this order']);
}