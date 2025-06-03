<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/dbcon.php';  // Using PDO connection
require_once '../includes/create_notification.php';

// Check if user is logged in and is staff/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['request_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

try {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];

    // Validate status
    $valid_statuses = ['Pending', 'In Progress', 'Completed', 'Rejected'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    // Start transaction
    $pdo->beginTransaction();

    // For staff, verify they are assigned to this request
    if ($user_role === 'Staff') {
        $check_query = "SELECT COUNT(*) FROM template_modifications tm 
                       JOIN staff s ON tm.staff_id = s.id 
                       WHERE tm.id = ? AND s.user_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$request_id, $user_id]);
        if ($check_stmt->fetchColumn() === 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Not authorized to update this request']);
            exit();
        }
    }

    // Get the customer's user_id and template name from the template_modifications table
    $query = "SELECT tm.user_id, t.name as template_name 
              FROM template_modifications tm 
              JOIN templates t ON tm.template_id = t.id 
              WHERE tm.id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$request_id]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Template modification not found']);
        exit();
    }

    $customer_id = $row['user_id'];
    $template_name = $row['template_name'];

    // Update status
    $update_query = "UPDATE template_modifications SET status = ?, status_updated_at = NOW() WHERE id = ?";
    $update_stmt = $pdo->prepare($update_query);

    if (!$update_stmt) {
        error_log("Failed to prepare update query");
        echo json_encode(['success' => false, 'message' => 'Error updating status']);
        exit();
    }

    if (!$update_stmt->execute([$status, $request_id])) {
        error_log("Failed to execute update query");
        echo json_encode(['success' => false, 'message' => 'Error updating status']);
        exit();
    }

    // Create notification for the customer
    $title = "Template Status Updated";
    $message = "Your template modification request for '$template_name' has been updated to $status";

    if (
        !createNotification(
            $pdo,
            $customer_id,
            $title,
            $message,
            'template_status',
            $request_id,
            'template_finishing'
        )
    ) {
        error_log("Failed to create notification");
        echo json_encode(['success' => false, 'message' => 'Error creating notification']);
        exit();
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'status' => $status,
            'template_name' => $template_name
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error updating template status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating status. Please try again.']);
}