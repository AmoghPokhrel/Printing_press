<?php
// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'printing_press';

// Create connection
try {
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
            die(json_encode(['success' => false, 'message' => 'Database connection error']));
        } else {
            die("Database connection failed.");
        }
    }

    // Set charset to utf8
    $conn->set_charset("utf8");

    // Set wait_timeout and interactive_timeout
    $conn->query("SET SESSION wait_timeout=600");
    $conn->query("SET SESSION interactive_timeout=600");

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Database connection error']));
    } else {
        die("Database connection failed.");
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Time zone setting
date_default_timezone_set('Asia/Kolkata'); // Change this to your timezone
?>