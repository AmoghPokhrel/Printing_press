<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $cart_item_id = intval($_POST['id']);
    $action = $_POST['action'];

    // First get the user's cart
    $cart_query = "SELECT c.id FROM cart c WHERE c.uid = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();

    if ($cart_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart not found']);
        exit();
    }

    $cart_id = $cart_result->fetch_assoc()['id'];

    if ($action === 'update' && isset($_POST['quantity'])) {
        $quantity = max(1, intval($_POST['quantity']));
        $stmt = $conn->prepare("UPDATE cart_item_line SET quantity = ? WHERE id = ? AND cart_id = ?");
        $stmt->bind_param('iii', $quantity, $cart_item_id, $cart_id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'message' => $success ? 'Quantity updated.' : 'Failed to update.']);
        exit();
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM cart_item_line WHERE id = ? AND cart_id = ?");
        $stmt->bind_param('ii', $cart_item_id, $cart_id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'message' => $success ? 'Item removed.' : 'Failed to remove.']);
        exit();
    }
}
echo json_encode(['success' => false, 'message' => 'Invalid request.']);