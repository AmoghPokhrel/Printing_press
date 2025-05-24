<?php
// Function to sanitize input data
function sanitize_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to check if user is logged in
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Function to get user role
function get_user_role()
{
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Function to check if user is admin
function is_admin()
{
    return get_user_role() === 'admin';
}

// Function to check if user is customer
function is_customer()
{
    return get_user_role() === 'customer';
}

// Function to redirect with message
function redirect_with_message($url, $message, $type = 'success')
{
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

// Function to display message
function display_message()
{
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}

// Function to generate random string
function generate_random_string($length = 10)
{
    return bin2hex(random_bytes($length));
}

// Function to format date
function format_date($date, $format = 'd M Y H:i:s')
{
    return date($format, strtotime($date));
}

// Function to check if email exists
function email_exists($email)
{
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to get user details
function get_user_details($user_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to validate password strength
function is_password_strong($password)
{
    // At least 8 characters long
    if (strlen($password) < 8) {
        return false;
    }

    // Contains at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    // Contains at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    // Contains at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }

    // Contains at least one special character
    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        return false;
    }

    return true;
}
?>