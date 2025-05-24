<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'printing_press';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone setting
date_default_timezone_set('Asia/Kolkata'); // Change this to your timezone
?>