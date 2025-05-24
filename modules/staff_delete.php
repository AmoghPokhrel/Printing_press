<?php
session_start();
include '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "../pages/login.php";</script>';
    exit();
}

// Check if the id parameter is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $staff_id = $_GET['id'];

    // Begin a transaction to ensure both deletions are executed successfully
    mysqli_begin_transaction($conn);

    try {
        // Delete from users table
        $delete_user_query = "DELETE FROM users WHERE id = $staff_id AND role = 'Staff'";
        if (!mysqli_query($conn, $delete_user_query)) {
            throw new Exception("Error deleting from users table.");
        }

        // Delete from designer table where staff_id is the foreign key
        $delete_designer_query = "DELETE FROM designer WHERE staff_id = $staff_id";
        if (!mysqli_query($conn, $delete_designer_query)) {
            throw new Exception("Error deleting from designer table.");
        }

        // Commit the transaction if both deletions are successful
        mysqli_commit($conn);

        // Success: Redirect to staff setup page
        echo '<script>alert("Staff deleted successfully."); window.location.href = "../pages/staff_setup.php";</script>';
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        mysqli_rollback($conn);
        // Error: Display an error message
        echo '<script>alert("Error deleting staff. Please try again later."); window.location.href = "../pages/staff_setup.php";</script>';
    }
} else {
    // If the id parameter is not provided, redirect to staff setup
    echo '<script>window.location.href = "../pages/staff_setup.php";</script>';
}
?>