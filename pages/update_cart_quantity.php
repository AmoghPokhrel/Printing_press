<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$item_id = intval($_POST['item_id']);
$quantity = max(1, intval($_POST['quantity'])); // Ensure quantity is at least 1
$user_id = $_SESSION['user_id'];

// First verify that this cart item belongs to the user
$verify_query = "SELECT c.id FROM cart c 
                JOIN cart_item_line cil ON c.id = cil.cart_id 
                WHERE cil.id = ? AND c.uid = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit();
}

// Update the quantity
$update_query = "UPDATE cart_item_line SET quantity = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $quantity, $item_id);
$success = $stmt->execute();

if ($success) {
    // Get updated cart count for the badge
    $count_query = "SELECT COALESCE(SUM(cil.quantity), 0) as count 
                    FROM cart c 
                    JOIN cart_item_line cil ON c.id = cil.cart_id 
                    WHERE c.uid = ? AND (cil.status = 'active' OR cil.status IS NULL)";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['count'];

    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated successfully',
        'cart_count' => $cart_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
}