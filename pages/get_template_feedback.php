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

try {
    // For staff, first get their staff ID
    if ($user_role === 'Staff') {
        $staff_query = "SELECT id FROM staff WHERE user_id = ?";
        $staff_stmt = $conn->prepare($staff_query);
        $staff_stmt->bind_param("i", $user_id);
        $staff_stmt->execute();
        $staff_result = $staff_stmt->get_result();
        $staff_row = $staff_result->fetch_assoc();
        $staff_id = $staff_row ? $staff_row['id'] : null;

        if (!$staff_id) {
            echo json_encode(['success' => false, 'message' => 'Staff ID not found']);
            exit();
        }

        // Check if the request is assigned to this staff member
        $access_query = "SELECT tm.id, tm.feedback 
                        FROM template_modifications tm
                        WHERE tm.id = ? AND tm.staff_id = ?";
        $stmt = $conn->prepare($access_query);
        $stmt->bind_param("ii", $request_id, $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // For customers, check if they own the request
        $access_query = "SELECT tm.id, tm.feedback 
                        FROM template_modifications tm
                        WHERE tm.id = ? AND tm.user_id = ?";
        $stmt = $conn->prepare($access_query);
        $stmt->bind_param("ii", $request_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $row = $result->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Access denied or request not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'feedback' => $row['feedback'] ?? 'No feedback available yet'
    ]);

} catch (Exception $e) {
    error_log("Error retrieving feedback: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>