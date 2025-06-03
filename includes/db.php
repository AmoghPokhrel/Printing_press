<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

$servername = "localhost";
$username = "root";  // Change if needed
$password = "";      // Change if needed
$database = "printing_press";

try {
    $conn = new mysqli($servername, $username, $password, $database);

    // Set the connection to use UTF-8
    $conn->set_charset("utf8mb4");

    // Enable error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed");
    }

    // Set wait_timeout and interactive_timeout
    $conn->query("SET SESSION wait_timeout=600");
    $conn->query("SET SESSION interactive_timeout=600");
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'message' => 'Database connection error'
        ]));
    } else {
        die("Database connection failed.");
    }
}
?>