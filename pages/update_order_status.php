<?php
session_start();
include '../includes/db.php';
include '../includes/create_notification.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

$order_id = $_POST['order_id'] ?? null;
$status = $_POST['status'] ?? null;

if ($order_id && $status) {
    try {
        $conn->begin_transaction();

        // 1. Update all order_item_line statuses for this order
        $stmt = $conn->prepare("UPDATE order_item_line SET status = ? WHERE oid = ?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        $stmt->close();

        // 2. Get admin_id from admin table using session user_id
        $admin_id = null;
        $stmt = $conn->prepare("SELECT id FROM admin WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($admin_id);
        $stmt->fetch();
        $stmt->close();

        // 3. Insert a single record into order_handling for this order
        if ($admin_id) {
            $insert_stmt = $conn->prepare("INSERT INTO order_handling (admin_id, order_id, changed_status) VALUES (?, ?, ?)");
            if (!$insert_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $insert_stmt->bind_param("iis", $admin_id, $order_id, $status);
            $insert_stmt->execute();
            $insert_stmt->close();
        }

        // 4. Get customer ID for notification
        $customer_query = "SELECT uid FROM `order` WHERE id = ?";
        $stmt = $conn->prepare($customer_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();

        if ($customer) {
            // 5. Create notification for the customer
            $notification_title = "Order Status Updated";
            $notification_message = "Your order #$order_id has been updated to " . ucfirst($status);

            createNotification(
                $conn,
                $customer['uid'],
                $notification_title,
                $notification_message,
                'order_status',
                $order_id,
                'order'
            );
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating order status: " . $e->getMessage());
    }
}

header("Location: orders.php");
exit();