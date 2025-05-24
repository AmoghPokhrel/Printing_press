<?php
session_start();
include '../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request_id and satisfaction_status are provided
if (!isset($_POST['request_id']) || !isset($_POST['satisfaction_status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$request_id = $_POST['request_id'];
$satisfaction_status = $_POST['satisfaction_status'];
$user_id = $_SESSION['user_id'];

// Validate satisfaction status
$allowed_statuses = ['Satisfied', 'Not Satisfied'];
if (!in_array($satisfaction_status, $allowed_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid satisfaction status']);
    exit();
}

try {
    // Check if the request belongs to the user and is completed
    $check_query = "SELECT id FROM template_modifications 
                    WHERE id = ? AND user_id = ? AND status = 'Completed'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $request_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request or unauthorized access']);
        exit();
    }

    // Update the satisfaction status
    $update_query = "UPDATE template_modifications 
                    SET satisfaction_status = ? 
                    WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sii", $satisfaction_status, $request_id, $user_id);

    if ($update_stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Satisfaction status updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating satisfaction status']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>