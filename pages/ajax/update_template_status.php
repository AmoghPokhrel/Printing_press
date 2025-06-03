<?php
session_start();
require_once '../../includes/db.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user role
$user_id = $_SESSION['user_id'];
$role_query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($role_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$role_result = $stmt->get_result();
$user_role = $role_result->fetch_assoc()['role'] ?? 'Customer';

// Only allow Admin and Super Admin to update status
if ($user_role !== 'Admin' && $user_role !== 'Super Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if template_id is provided
if (!isset($_POST['template_id'])) {
    echo json_encode(['success' => false, 'message' => 'Template ID not provided']);
    exit;
}

$template_id = intval($_POST['template_id']);

// Get current status
$status_query = "SELECT status FROM templates WHERE id = ?";
$stmt = $conn->prepare($status_query);
$stmt->bind_param("i", $template_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Template not found']);
    exit;
}

$current_status = $result->fetch_assoc()['status'];
$new_status = $current_status === 'active' ? 'inactive' : 'active';

// Update status
$update_query = "UPDATE templates SET status = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $new_status, $template_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'new_status' => $new_status]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}