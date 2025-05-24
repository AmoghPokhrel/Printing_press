<?php
session_start();
require_once('../includes/dbcon.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get category ID from request
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_id <= 0) {
    echo json_encode(['error' => 'Invalid category ID']);
    exit();
}

// Fetch sizes for the category
$sizes_query = "SELECT * FROM sizes WHERE category_id = ? ORDER BY size_name";
$stmt = $conn->prepare($sizes_query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

$sizes = [];
while ($row = $result->fetch_assoc()) {
    $sizes[] = [
        'size_name' => $row['size_name']
    ];
}

echo json_encode($sizes);