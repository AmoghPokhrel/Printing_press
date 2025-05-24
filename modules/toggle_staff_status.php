<?php
session_start();
include '../includes/db.php';

// Check if user is logged in and is Admin or Super Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['staff_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$staff_id = intval($_POST['staff_id']);
$status = $_POST['status'] === 'active' ? 'active' : 'inactive';

// Update the staff availability status
$query = "UPDATE staff SET availability = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "si", $status, $staff_id);

if (mysqli_stmt_execute($stmt)) {
    // Also update the user's role in the users table
    $get_user_id_query = "SELECT user_id FROM staff WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $get_user_id_query);
    mysqli_stmt_bind_param($user_stmt, "i", $staff_id);
    mysqli_stmt_execute($user_stmt);
    mysqli_stmt_bind_result($user_stmt, $user_id);
    mysqli_stmt_fetch($user_stmt);
    mysqli_stmt_close($user_stmt);

    if ($user_id) {
        $new_role = ($status === 'active') ? 'Staff' : 'Customer';
        $update_role_query = "UPDATE users SET role = ? WHERE id = ?";
        $role_stmt = mysqli_prepare($conn, $update_role_query);
        mysqli_stmt_bind_param($role_stmt, "si", $new_role, $user_id);
        mysqli_stmt_execute($role_stmt);
        mysqli_stmt_close($role_stmt);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating status']);
}

mysqli_stmt_close($stmt);
?>