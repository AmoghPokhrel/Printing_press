<?php
// dbcon.php - Database Connection File

// Database configuration
$host = 'localhost';
$dbname = 'printing_press'; // Verify this matches your actual DB name
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (empty)

// Create PDO connection (global scope)
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Security: disables emulated prepares
        ]
    );
} catch (PDOException $e) {
    // Log error securely (don't expose details to users)
    error_log("Database connection failed: " . $e->getMessage());

    // Return JSON error if used in an API context
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

// Ensure upload directories exist
function ensureUploadDirectories()
{
    $directories = [
        '../uploads/template_designs',
        '../uploads/custom_templates',
        '../uploads/template_images',
        '../uploads/templates'
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Call the function (optional, can be moved elsewhere)
ensureUploadDirectories();
?>