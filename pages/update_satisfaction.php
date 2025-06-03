<?php
session_start();

// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

// Start output buffering to catch any unwanted output
ob_start();

// Set JSON content type header
header('Content-Type: application/json');

require_once '../includes/db.php';  // Using mysqli connection

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '', $data = null)
{
    // Clear any previous output
    if (ob_get_length())
        ob_clean();

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit();
}

try {
    // Debug log the request
    error_log("Satisfaction update request - POST data: " . print_r($_POST, true));
    error_log("User session data: " . print_r($_SESSION, true));

    // Check if user is logged in and is a customer
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
        error_log("Unauthorized access attempt - User ID: " . ($_SESSION['user_id'] ?? 'none') . ", Role: " . ($_SESSION['role'] ?? 'none'));
        throw new Exception('Unauthorized access');
    }

    // Validate input parameters
    if (!isset($_POST['request_id']) || !isset($_POST['satisfaction_status'])) {
        error_log("Missing parameters in request");
        throw new Exception('Missing required parameters');
    }

    $request_id = (int) $_POST['request_id'];
    $satisfaction_status = $_POST['satisfaction_status'];

    // Validate satisfaction status
    $valid_statuses = ['Satisfied', 'Not Satisfied'];
    if (!in_array($satisfaction_status, $valid_statuses)) {
        error_log("Invalid satisfaction status: " . $satisfaction_status);
        throw new Exception('Invalid satisfaction status');
    }

    // Start transaction
    $conn->begin_transaction();

    // First, verify that this request belongs to the current user
    $verify_query = "SELECT tm.*, t.name as template_name, s.user_id as staff_user_id 
                    FROM template_modifications tm
                    JOIN templates t ON tm.template_id = t.id
                    JOIN staff s ON tm.staff_id = s.id
                    WHERE tm.id = ? AND tm.user_id = ?";

    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $request_info = $result->fetch_assoc();
    $verify_stmt->close();

    if (!$request_info) {
        error_log("Request not found or unauthorized - Request ID: $request_id, User ID: {$_SESSION['user_id']}");
        throw new Exception('Request not found or unauthorized');
    }

    // Update satisfaction status
    $update_query = "UPDATE template_modifications 
                    SET satisfaction_status = ?
                    WHERE id = ? AND user_id = ?";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sii", $satisfaction_status, $request_id, $_SESSION['user_id']);
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update satisfaction status');
    }
    $update_stmt->close();

    // If marked as Not Satisfied, update the status to In Progress
    if ($satisfaction_status === 'Not Satisfied') {
        $status_update_query = "UPDATE template_modifications 
                              SET status = 'In Progress', 
                                  status_updated_at = NOW()
                              WHERE id = ? AND user_id = ?";
        $status_update_stmt = $conn->prepare($status_update_query);
        $status_update_stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
        if (!$status_update_stmt->execute()) {
            throw new Exception('Failed to update request status');
        }
        $status_update_stmt->close();
    }

    // Create notification for the staff member
    if (isset($request_info['staff_user_id'])) {
        require_once '../includes/create_notification.php';

        $notification_title = $satisfaction_status === 'Satisfied'
            ? "Design Approved ✓"
            : "Design Revision Requested";

        $notification_message = $satisfaction_status === 'Satisfied'
            ? "Customer has approved the design for template '{$request_info['template_name']}'"
            : "Customer has requested revisions for template '{$request_info['template_name']}'. The status has been updated to In Progress.";

        if (
            !createNotification(
                $conn,
                $request_info['staff_user_id'],
                $notification_title,
                $notification_message,
                'template_status',
                $request_id,
                'template_finishing'
            )
        ) {
            throw new Exception('Failed to create notification');
        }
    }

    // Commit transaction
    $conn->commit();

    $response_data = [
        'satisfaction_status' => $satisfaction_status,
        'request_status' => $satisfaction_status === 'Not Satisfied' ? 'In Progress' : $request_info['status']
    ];

    sendJsonResponse(true, 'Satisfaction status updated successfully', $response_data);

} catch (Exception $e) {
    if ($conn && $conn->ping()) {
        $conn->rollback();
    }
    error_log("Error updating satisfaction status: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, $e->getMessage());
}