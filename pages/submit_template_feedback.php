<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Template feedback submission started");

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

require_once '../includes/dbcon.php';

try {
    // Validate required parameters
    if (!isset($_POST['request_id']) || !isset($_POST['feedback'])) {
        error_log("Missing required parameters");
        throw new Exception("Missing required parameters");
    }

    $requestId = (int) $_POST['request_id'];
    $feedback = trim($_POST['feedback']);
    $userId = $_SESSION['user_id'];

    // Validate feedback
    if (empty($feedback)) {
        error_log("Empty feedback provided");
        throw new Exception("Feedback cannot be empty");
    }

    // Verify the request belongs to the user
    $stmt = $pdo->prepare("SELECT user_id FROM template_modifications WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        error_log("Request not found: $requestId");
        throw new Exception("Request not found");
    }

    if ($request['user_id'] != $userId) {
        error_log("User $userId attempted to submit feedback for request belonging to user {$request['user_id']}");
        throw new Exception("Unauthorized to submit feedback for this request");
    }

    // Update the feedback
    $stmt = $pdo->prepare("UPDATE template_modifications SET feedback = ? WHERE id = ?");
    $stmt->execute([$feedback, $requestId]);

    if ($stmt->rowCount() === 0) {
        error_log("No rows updated for request ID: $requestId");
        throw new Exception("Failed to update feedback");
    }

    error_log("Feedback updated successfully for request ID: $requestId");
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}