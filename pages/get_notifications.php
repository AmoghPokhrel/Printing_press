<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];

    // Get unread count with explicit conditions
    $unread_query = "SELECT COUNT(*) as count 
                     FROM notifications 
                     WHERE user_id = ? 
                     AND (is_read = 0 OR is_read IS NULL)";
    $stmt = $conn->prepare($unread_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = (int) $result->fetch_assoc()['count'];

    // Get notifications with proper ordering
    $query = "SELECT id, title, message, type, 
                     COALESCE(is_read, 0) as is_read, 
                     created_at, reference_id, reference_type 
              FROM notifications 
              WHERE user_id = ? 
              ORDER BY created_at DESC, id DESC 
              LIMIT 20";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'is_read' => (bool) $row['is_read'],
            'created_at' => $row['created_at'],
            'reference_id' => $row['reference_id'] ? (int) $row['reference_id'] : null,
            'reference_type' => $row['reference_type']
        ];
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    error_log("Error in get_notifications.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications'
    ]);
}

$conn->close();
?>