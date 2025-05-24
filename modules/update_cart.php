<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$cartId = intval($_POST['cart_id']);
$newQuantity = intval($_POST['quantity']);
$userId = $_SESSION['user_id'];

try {
    if ($newQuantity > 0) {
        $updateQuery = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("iii", $newQuantity, $cartId, $userId);
        $stmt->execute();
    } else {
        $deleteQuery = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $cartId, $userId);
        $stmt->execute();
    }

    // Get updated cart count
    $countQuery = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartCount = $result->fetch_assoc()['count'] ?? 0;

    echo json_encode(['success' => true, 'cartCount' => $cartCount]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}