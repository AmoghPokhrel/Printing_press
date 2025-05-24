<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form data
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $dob = sanitize_input($_POST['dob']);
    $gender = sanitize_input($_POST['gender']);
    $password = sanitize_input($_POST['password']);
    $staff_role = sanitize_input($_POST['staff_role']);

    // Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<script>alert("Invalid email format!"); window.history.back();</script>';
        exit();
    }

    // Validate phone number format (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo '<script>alert("Phone number must be 10 digits!"); window.history.back();</script>';
        exit();
    }

    // Validate name (only letters, spaces, and basic punctuation)
    if (!preg_match('/^[a-zA-Z\s\.\'-]+$/', $name)) {
        echo '<script>alert("Name can only contain letters, spaces, and basic punctuation!"); window.history.back();</script>';
        exit();
    }

    // Validate gender
    if (!in_array($gender, ['male', 'female', 'other'])) {
        echo '<script>alert("Invalid gender selection!"); window.history.back();</script>';
        exit();
    }

    // Validate staff role
    if (!in_array($staff_role, ['Designer'])) {
        echo '<script>alert("Invalid staff role selection!"); window.history.back();</script>';
        exit();
    }

    // Validate password strength
    if (!is_password_strong($password)) {
        echo '<script>alert("Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character!"); window.history.back();</script>';
        exit();
    }

    // Check if email already exists
    if (email_exists($email)) {
        echo '<script>alert("Email already exists!"); window.history.back();</script>';
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert new staff into the users table
    $insert_query = "INSERT INTO users (name, email, phone, address, dob, gender, password_hash, staff_role, role, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Staff', NOW(), NOW())";

    if ($stmt = mysqli_prepare($conn, $insert_query)) {
        mysqli_stmt_bind_param($stmt, "ssssssss", $name, $email, $phone, $address, $dob, $gender, $hashed_password, $staff_role);

        if (mysqli_stmt_execute($stmt)) {
            // Get the last inserted user_id
            $user_id = mysqli_insert_id($conn);

            // Debug: Check session data
            error_log("Session user ID: " . $_SESSION['user_id']);
            error_log("Session role: " . $_SESSION['role']);

            // First check if the user exists in users table with Admin role
            $check_admin_query = "SELECT id FROM users WHERE id = ? AND role = 'Admin'";
            if ($check_stmt = mysqli_prepare($conn, $check_admin_query)) {
                mysqli_stmt_bind_param($check_stmt, "i", $_SESSION['user_id']);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);

                if ($check_row = mysqli_fetch_assoc($check_result)) {
                    // Now get the admin_id from the admin table
                    $admin_query = "SELECT id FROM admin WHERE user_id = ?";
                    if ($admin_stmt = mysqli_prepare($conn, $admin_query)) {
                        mysqli_stmt_bind_param($admin_stmt, "i", $_SESSION['user_id']);
                        mysqli_stmt_execute($admin_stmt);
                        $admin_result = mysqli_stmt_get_result($admin_stmt);

                        if ($admin_row = mysqli_fetch_assoc($admin_result)) {
                            $admin_id = $admin_row['id'];

                            // Now insert the user_id and admin_id into the Staff table
                            $staff_insert_query = "INSERT INTO Staff (user_id, admin_id) VALUES (?, ?)";
                            if ($staff_stmt = mysqli_prepare($conn, $staff_insert_query)) {
                                mysqli_stmt_bind_param($staff_stmt, "ii", $user_id, $admin_id);

                                if (mysqli_stmt_execute($staff_stmt)) {
                                    echo '<script>
                                            alert("Staff registered successfully!");
                                            window.location.href = "../pages/staff_setup.php";
                                          </script>';
                                } else {
                                    echo '<script>alert("Error adding user_id to Staff table: ' . mysqli_error($conn) . '");</script>';
                                }
                                mysqli_stmt_close($staff_stmt);
                            }
                        } else {
                            echo '<script>alert("Error: Admin record not found in admin table. Please contact system administrator."); window.history.back();</script>';
                        }
                        mysqli_stmt_close($admin_stmt);
                    } else {
                        echo '<script>alert("Error preparing admin query: ' . mysqli_error($conn) . '"); window.history.back();</script>';
                    }
                } else {
                    echo '<script>alert("Error: You must be logged in as an Admin to add staff members."); window.history.back();</script>';
                }
                mysqli_stmt_close($check_stmt);
            } else {
                echo '<script>alert("Error preparing check query: ' . mysqli_error($conn) . '"); window.history.back();</script>';
            }
        } else {
            echo '<script>alert("Error: ' . mysqli_error($conn) . '");</script>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>