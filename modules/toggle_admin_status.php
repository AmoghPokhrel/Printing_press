<?php
session_start();
include '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['admin_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$admin_id = intval($_POST['admin_id']);
$new_status = $_POST['status'] === 'active' ? 'active' : 'inactive';

// Update the admin's availability status
$update_query = "UPDATE admin SET availability = ? WHERE user_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $new_status, $admin_id);

if ($stmt->execute()) {
    // Update user role based on availability
    $role = ($new_status === 'active') ? 'Admin' : 'Customer';
    $update_role = "UPDATE users SET role = ? WHERE id = ?";
    $role_stmt = $conn->prepare($update_role);
    $role_stmt->bind_param("si", $role, $admin_id);
    $role_stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>