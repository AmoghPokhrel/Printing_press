<?php
session_start();
header('Content-Type: application/json');

include '../includes/db.php';

// Check if user is logged in and is staff/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['request_id']) || !isset($_FILES['final_design'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$request_id = $_POST['request_id'];
$file = $_FILES['final_design'];

// Validate file
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit();
}

// Create upload directory if it doesn't exist
$upload_dir = '../uploads/template_designs/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $file_extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    exit();
}

// Update database
$query = "UPDATE template_modifications SET final_design = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $filename, $request_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    // Delete uploaded file if database update fails
    unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>