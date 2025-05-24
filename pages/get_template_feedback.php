<?php
session_start();
header('Content-Type: application/json');

include '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing request ID']);
    exit();
}

$request_id = $_GET['request_id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check if user has access to this request
$query = "SELECT tm.id, tm.feedback 
          FROM template_modifications tm
          WHERE tm.id = ? AND (
              tm.user_id = ? OR 
              tm.staff_id = (SELECT id FROM staff WHERE user_id = ?)
          )";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $request_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$row = $result->fetch_assoc();
echo json_encode([
    'success' => true,
    'feedback' => $row['feedback'] ?? 'No feedback available yet'
]);

$stmt->close();
$conn->close();
?>