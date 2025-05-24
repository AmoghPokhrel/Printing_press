<?php
session_start();
include '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo '<script>alert("You need to log in as an Admin"); window.location.href = "login.php";</script>';
    exit();
}

// Check if the id parameter is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $admin_id = $_GET['id'];

    // Prepare SQL query to delete the admin
    $query = "DELETE FROM users WHERE id = $admin_id AND role = 'Admin'";

    // Execute the query
    if (mysqli_query($conn, $query)) {
        // Success: Redirect to admin setup page
        echo '<script>alert("Admin deleted successfully."); window.location.href = "../pages/admin_setup.php";</script>';
    } else {
        // Error: Display an error message
        echo '<script>alert("Error deleting admin. Please try again later."); window.location.href = "../pages/admin_setup.php";</script>';
    }
} else {
    // If the id parameter is not provided, redirect to admin setup
    echo '<script>window.location.href = "../pages/admin_setup.php";</script>';
}
?>