<?php
require_once '../includes/init.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store form data in session
    $_SESSION['form_data'] = $_POST;

    // Collect form data
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $dob = sanitize_input($_POST['dob']);
    $gender = sanitize_input($_POST['gender']);
    $role = "Customer";

    // Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!'); window.location.href='../pages/c_register.php';</script>";
        exit();
    }

    // Validate phone number format (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo "<script>alert('Phone number must be 10 digits!'); window.location.href='../pages/c_register.php';</script>";
        exit();
    }

    // Validate name (only letters, spaces, and basic punctuation)
    if (!preg_match('/^[a-zA-Z\s\.\'-]+$/', $name)) {
        echo "<script>alert('Name can only contain letters, spaces, and basic punctuation!'); window.location.href='../pages/c_register.php';</script>";
        exit();
    }

    // Validate gender
    if (!in_array($gender, ['male', 'female', 'other'])) {
        echo "<script>alert('Invalid gender selection!'); window.location.href='../pages/c_register.php';</script>";
        exit();
    }

    // Validate password strength
    if (!is_password_strong($password)) {
        echo "<script>alert('Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character!'); window.location.href='../pages/c_register.php';</script>";
        exit();
    }

    // Check if email already exists
    if (email_exists($email)) {
        echo "<script>alert('Email already exists! Please use a different email.'); window.location.href='../pages/c_register.php';</script>";
        exit();
    }

    // Hash password for security
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert data into users table
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, phone, address, role, dob, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $password_hash, $phone, $address, $role, $dob, $gender);

    if ($stmt->execute()) {
        // Clear form data from session on successful registration
        unset($_SESSION['form_data']);
        echo "<script>alert('Registration successful! You can now log in.'); window.location.href='../pages/login.php';</script>";
    } else {
        echo "<script>alert('Registration failed. Please try again.'); window.location.href='../pages/c_register.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request.'); window.location.href='../pages/login.php';</script>";
}
?>