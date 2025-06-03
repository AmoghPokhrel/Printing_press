<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

// Increase max execution time and memory limit
ini_set('max_execution_time', '300'); // 5 minutes
ini_set('memory_limit', '256M');

// Start output buffering to catch any unwanted output
ob_start();

session_start();
header('Content-Type: application/json');

require_once '../includes/dbcon.php';  // Using dbcon.php for PDO connection
require_once '../includes/create_notification.php';

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $data = null)
{
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit();
}

// Function to validate file
function validateFile($file)
{
    $errors = [];

    // Check file size (max 5MB)
    $maxFileSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxFileSize) {
        $errors[] = 'File size too large. Maximum size is 5MB';
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Invalid file type. Only JPG, PNG and GIF files are allowed';
    }

    return $errors;
}

// Check if user is logged in and is staff/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    sendJsonResponse(false, 'Unauthorized access');
}

if (!isset($_POST['request_id']) || !isset($_FILES['final_design'])) {
    sendJsonResponse(false, 'Missing required parameters');
}

try {
    $request_id = $_POST['request_id'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];

    // For staff, verify they are assigned to this request
    if ($user_role === 'Staff') {
        $check_query = "SELECT COUNT(*) FROM template_modifications tm 
                       JOIN staff s ON tm.staff_id = s.id 
                       WHERE tm.id = ? AND s.user_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$request_id, $user_id]);
        if ($check_stmt->fetchColumn() === 0) {
            sendJsonResponse(false, 'Not authorized to upload for this request');
        }
    }

    // Get request details
    $query = "SELECT tm.user_id, t.name as template_name 
              FROM template_modifications tm 
              JOIN templates t ON tm.template_id = t.id 
              WHERE tm.id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        sendJsonResponse(false, 'Request not found');
    }

    // Validate file
    $file = $_FILES['final_design'];
    $errors = validateFile($file);
    if (!empty($errors)) {
        sendJsonResponse(false, implode('. ', $errors));
    }

    // Handle file upload
    $targetDir = '../uploads/template_designs/';

    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
        chmod($targetDir, 0777); // Ensure directory is writable
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . uniqid() . '.' . $extension;
    $targetFile = $targetDir . $fileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        error_log("Upload failed. File: " . $file['tmp_name'] . " Target: " . $targetFile);
        error_log("Upload error: " . error_get_last()['message']);
        throw new Exception('Failed to move uploaded file');
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Update the template_modifications table
        $update_query = "UPDATE template_modifications 
                        SET final_design = ?, status = 'Completed', status_updated_at = NOW() 
                        WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$fileName, $request_id]);

        // Create notification for the customer
        $title = "Final Design Uploaded";
        $message = "The final design for your template modification request '{$request['template_name']}' has been uploaded";
        if (
            !createNotification(
                $pdo,
                $request['user_id'],
                $title,
                $message,
                'template_status',
                $request_id,
                'template_finishing'
            )
        ) {
            throw new Exception('Failed to create notification');
        }

        // Commit transaction
        $pdo->commit();
        sendJsonResponse(true, 'Design uploaded successfully', ['fileName' => $fileName]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error uploading template design: " . $e->getMessage());
    sendJsonResponse(false, 'Error uploading design: ' . $e->getMessage());
}

// Clean output buffer before exit
if (ob_get_length())
    ob_end_clean();
?>