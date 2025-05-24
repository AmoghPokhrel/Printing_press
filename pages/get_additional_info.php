<?php
header('Content-Type: application/json');
require_once('../includes/dbcon.php');

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$request_type = isset($_GET['type']) ? $_GET['type'] : null; // Optional, for clarity

if (!$category_id || !$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$table_name = 'additional_info_' . $category_id;

// Check if table exists
$check = $pdo->query("SHOW TABLES LIKE '$table_name'");
if (!$check || $check->rowCount() == 0) {
    echo json_encode(['success' => false, 'message' => 'No additional info table for this category']);
    exit;
}

// Get all additional fields for this category
$field_stmt = $pdo->prepare("SELECT field_name, field_label FROM additional_info_fields WHERE category_id = ?");
$field_stmt->execute([$category_id]);
$fields = $field_stmt->fetchAll(PDO::FETCH_ASSOC);

// Try to fetch by template_modification_id first (for modification requests)
$row_stmt = $pdo->prepare("SELECT * FROM $table_name WHERE template_modification_id = ?");
$row_stmt->execute([$request_id]);
$row = $row_stmt->fetch(PDO::FETCH_ASSOC);

// If not found, try by request_id (for custom requests)
if (!$row) {
    $row_stmt = $pdo->prepare("SELECT * FROM $table_name WHERE request_id = ?");
    $row_stmt->execute([$request_id]);
    $row = $row_stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$row) {
    echo json_encode(['success' => true, 'fields' => []]);
    exit;
}

$result_fields = [];
foreach ($fields as $field) {
    $fname = $field['field_name'];
    if (isset($row[$fname])) {
        $result_fields[] = [
            'label' => $field['field_label'],
            'value' => $row[$fname]
        ];
    }
}

echo json_encode(['success' => true, 'fields' => $result_fields]);