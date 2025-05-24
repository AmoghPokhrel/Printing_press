<?php
session_start();
require '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $redirect = $_POST['redirect'] ?? null;
    $category_id = $_POST['category_id'] ?? null;

    $stmt = $conn->prepare("SELECT id, name, email, password_hash, role, staff_role FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {
            // Check if user is an admin and verify their availability
            if ($user['role'] === 'Admin') {
                $admin_query = "SELECT availability FROM admin WHERE user_id = ?";
                $admin_stmt = $conn->prepare($admin_query);
                $admin_stmt->bind_param("i", $user['id']);
                $admin_stmt->execute();
                $admin_result = $admin_stmt->get_result();

                if ($admin_result->num_rows === 1) {
                    $admin_data = $admin_result->fetch_assoc();
                    // If admin is inactive, change their role to Customer
                    if ($admin_data['availability'] === 'inactive') {
                        $update_role = "UPDATE users SET role = 'Customer' WHERE id = ?";
                        $update_stmt = $conn->prepare($update_role);
                        $update_stmt->bind_param("i", $user['id']);
                        $update_stmt->execute();
                        $user['role'] = 'Customer';
                    }
                }
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['staff_role'] = $user['staff_role'] ?? null;

            // Handle redirect after login
            if ($redirect && $category_id) {
                // If coming from a category "See More" link
                header("Location: ../pages/$redirect?category_id=$category_id");
            } else {
                // Default role-based redirection
                switch ($user['role']) {
                    case 'Admin':
                    case 'Super Admin':
                        header("Location: ../pages/admin_dashboard.php");
                        break;
                    case 'Staff':
                        header("Location: ../pages/staff_dashboard.php");
                        break;
                    case 'Customer':
                        header("Location: ../pages/customer_dashboard.php");
                        break;
                    default:
                        header("Location: ../pages/login.php");
                }
            }
            exit();
        } else {
            echo "<script>alert('Incorrect password!'); window.location.href='../pages/login.php';</script>";
        }
    } else {
        echo "<script>alert('No account found with this email!'); window.location.href='../pages/login.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request.'); window.location.href='../pages/login.php';</script>";
}
?>