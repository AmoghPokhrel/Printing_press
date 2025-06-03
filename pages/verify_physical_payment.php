<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['orderId']) || !isset($data['amount']) || !isset($data['method']) || !isset($data['reference'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$order_id = intval($data['orderId']);
$amount = floatval($data['amount']);
$method = $conn->real_escape_string($data['method']);
$reference = $conn->real_escape_string($data['reference']);
$notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : '';
$admin_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get order items to calculate total amount and verify status
    $order_items_query = "SELECT id, status, total_price FROM order_item_line WHERE oid = ? FOR UPDATE";
    $stmt = $conn->prepare($order_items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($items)) {
        throw new Exception("No order items found for Order ID: $order_id");
    }

    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += $item['total_price'];
    }

    // Log the amounts for debugging
    error_log("Physical Payment - Order ID: $order_id, Paid Amount: $amount, Total Amount: $total_amount");
    error_log("Current items status: " . json_encode($items));

    // Verify if paid amount matches order amount
    if ($amount < $total_amount) {
        throw new Exception("Paid amount ($amount) is less than order total ($total_amount)");
    }

    // Insert payment records for each order item
    foreach ($items as $item) {
        $payment_query = "INSERT INTO payments (order_item_id, amount, payment_method, transaction_id, payment_date, status) 
                         VALUES (?, ?, ?, ?, NOW(), 'completed')";
        $stmt = $conn->prepare($payment_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare payment query: " . $conn->error);
        }

        $item_amount = $amount / count($items); // Divide total amount among items
        $stmt->bind_param("idss", $item['id'], $item_amount, $method, $reference);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute payment query: " . $stmt->error);
        }
    }

    // Store receipt number
    $receipt_query = "INSERT INTO receipt_numbers (order_id, receipt_number) VALUES (?, ?)";
    $stmt = $conn->prepare($receipt_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare receipt query: " . $conn->error);
    }

    error_log("Storing receipt number - Order ID: $order_id, Receipt Number: $reference");
    $stmt->bind_param("is", $order_id, $reference);
    if (!$stmt->execute()) {
        throw new Exception("Failed to store receipt number: " . $stmt->error);
    }

    // Update status for all order items - using direct update with FOR UPDATE lock
    $update_status_query = "UPDATE order_item_line SET status = 'completed' WHERE oid = ? AND status = 'ready'";
    error_log("Updating order items status - Query: $update_status_query - Order ID: $order_id");

    $stmt = $conn->prepare($update_status_query);
    if (!$stmt) {
        error_log("Failed to prepare status update query. MySQL Error: " . $conn->error);
        throw new Exception("Failed to prepare status update query");
    }

    $stmt->bind_param("i", $order_id);
    $success = $stmt->execute();
    $rows_affected = $stmt->affected_rows;

    error_log("Status update execution result - Success: " . ($success ? 'true' : 'false') .
        ", Rows affected: $rows_affected, Error: " . $stmt->error);

    if (!$success) {
        throw new Exception("Failed to update order items status: " . $stmt->error);
    }

    if ($rows_affected === 0) {
        throw new Exception("No items were updated to completed status. Please verify the order status.");
    }

    // Double-check the status update
    $verify_query = "SELECT id, status FROM order_item_line WHERE oid = ? FOR UPDATE";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $verify_result = $stmt->get_result();
    $updated_items = $verify_result->fetch_all(MYSQLI_ASSOC);
    error_log("Status after update - Order ID: $order_id - " . json_encode($updated_items));

    // Verify all items are completed
    foreach ($updated_items as $item) {
        if ($item['status'] !== 'completed') {
            throw new Exception("Status update verification failed - Some items are not marked as completed");
        }
    }

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully - Order ID: $order_id");

    echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);

} catch (Exception $e) {
    error_log("Physical Payment Error - " . $e->getMessage());
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>