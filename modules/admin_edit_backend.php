<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

// Redirect if not logged in or not a Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    echo '<script>alert("You need Super Admin privileges to perform this action"); window.location.href = "../pages/login.php";</script>';
    exit();
}

// Check if form data is received
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $dob = sanitize_input($_POST['dob']);
    $gender = sanitize_input($_POST['gender']);

    // Get current user data
    $current_user = get_user_details($id);
    if (!$current_user) {
        echo "<script>alert('Admin not found.'); window.location.href='../pages/admin_setup.php';</script>";
        exit();
    }

    // Prevent email change
    if ($email !== $current_user['email']) {
        echo "<script>alert('Email cannot be changed as it is used as a unique identifier.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
        exit();
    }

    // Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
        exit();
    }

    // Validate phone number format (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo "<script>alert('Phone number must be 10 digits.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
        exit();
    }

    // Validate name (only letters, spaces, and basic punctuation)
    if (!preg_match('/^[a-zA-Z\s\.\'-]+$/', $name)) {
        echo "<script>alert('Name can only contain letters, spaces, and basic punctuation.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
        exit();
    }

    // Validate gender
    if (!in_array($gender, ['male', 'female', 'other'])) {
        echo "<script>alert('Invalid gender selection.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
        exit();
    }

    // Check if dob is empty, and set it to null if necessary
    $dob = empty($dob) ? NULL : $dob;

    // Ensure required fields are filled
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($gender)) {
        echo "<script>alert('Please fill all the required fields.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
        exit();
    }

    // Use a prepared statement to prevent SQL injection
    $update_query = "UPDATE users SET name=?, phone=?, address=?, dob=?, gender=?, updated_at=NOW() WHERE id=? AND role='Admin'";

    if ($stmt = mysqli_prepare($conn, $update_query)) {
        // Bind the parameters
        mysqli_stmt_bind_param($stmt, 'sssssi', $name, $phone, $address, $dob, $gender, $id);

        // Execute the query
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Admin details updated successfully.'); window.location.href='../pages/admin_setup.php';</script>";
        } else {
            error_log("Error executing query: " . mysqli_error($conn), 3, "error_log.txt");
            echo "<script>alert('Error updating admin details.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Failed to prepare query.'); window.location.href='../pages/admin_edit.php?id=$id';</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.location.href='../pages/admin_setup.php';</script>";
}
?>