<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Get notification ID from POST data
    $notification_id = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : null;

    if ($notification_id) {
        // Mark specific notification as read
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
    } else {
        // Mark all notifications as read
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    // Get updated unread count
    $count_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = (int) $result->fetch_assoc()['count'];

    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count
    ]);

} catch (Exception $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to mark notification as read',
        'debug' => $e->getMessage()
    ]);
}

$conn->close();