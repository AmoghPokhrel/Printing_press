<?php
session_start();
header('Content-Type: application/json');

include '../includes/db.php';

// Check if user is logged in and is staff/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['request_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$request_id = $_POST['request_id'];
$status = $_POST['status'];

// Validate status
$valid_statuses = ['Pending', 'In Progress', 'Completed', 'Rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Update status
$query = "UPDATE template_modifications SET status = ?, status_updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $status, $request_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>