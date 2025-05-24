<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Feedback update started");

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized access']));
}

require_once __DIR__ . '/../includes/dbcon.php';

try {
    // Log incoming data
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));

    // Validate required parameters
    if (!isset($_POST['modification_id']) || !isset($_POST['feedback'])) {
        error_log("Missing required parameters");
        throw new Exception("Missing required parameters: modification_id and feedback are required");
    }

    $modificationId = (int) $_POST['modification_id'];
    $feedback = trim($_POST['feedback']);

    // Validate feedback
    if (empty($feedback)) {
        error_log("Empty feedback provided");
        throw new Exception("Feedback cannot be empty");
    }

    if (strlen($feedback) > 1000) { // Assuming max length of 1000 characters
        error_log("Feedback too long: " . strlen($feedback) . " characters");
        throw new Exception("Feedback too long (max 1000 characters)");
    }

    // Update the feedback in template_modifications table
    $sql = "UPDATE template_modifications SET feedback = :feedback WHERE id = :id";
    error_log("Executing SQL: $sql with ID: $modificationId");

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':feedback' => $feedback,
        ':id' => $modificationId
    ]);

    if ($stmt->rowCount() === 0) {
        error_log("No rows updated for modification ID: $modificationId");
        throw new Exception("No record updated - modification ID $modificationId not found");
    }

    error_log("Update successful");
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}