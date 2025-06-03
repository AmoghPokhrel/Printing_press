<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

function createNotification($conn, $userId, $title, $message, $type, $referenceId = null, $referenceType = null)
{
    try {
        // Validate parameters
        if (!$conn || !$userId || !$title || !$message || !$type) {
            error_log("Missing required parameters for notification creation");
            return false;
        }

        $query = "INSERT INTO notifications (user_id, title, message, type, reference_id, reference_type, is_read, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)";

        // Handle both PDO and mysqli connections
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare notification query (PDO)");
                return false;
            }

            $success = $stmt->execute([
                $userId,
                $title,
                $message,
                $type,
                $referenceId,
                $referenceType
            ]);

            if (!$success) {
                error_log("Failed to create notification (PDO): " . implode(", ", $stmt->errorInfo()));
                return false;
            }
        } elseif ($conn instanceof mysqli) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare notification query (mysqli): " . $conn->error);
                return false;
            }

            $stmt->bind_param(
                "isssss",
                $userId,
                $title,
                $message,
                $type,
                $referenceId,
                $referenceType
            );

            $success = $stmt->execute();
            $stmt->close();

            if (!$success) {
                error_log("Failed to create notification (mysqli): " . $conn->error);
                return false;
            }
        } else {
            error_log("Invalid connection object provided to createNotification");
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

// Example usage:
/*
include 'db.php';

// For customer notifications
createNotification(
    $conn,
    $customerId,
    'Order Status Updated',
    'Your order #123 status has been updated to Processing',
    'order_status',
    123,
    'order'
);

// For admin notifications
createNotification(
    $conn,
    $adminId,
    'New Order Received',
    'A new order #123 has been placed',
    'new_order',
    123,
    'order'
);

// For staff notifications
createNotification(
    $conn,
    $staffId,
    'New Template Request',
    'You have been assigned a new template request #456',
    'template_request',
    456,
    'custom_template'
);
*/
?>