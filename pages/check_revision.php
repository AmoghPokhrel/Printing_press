<?php
session_start();
require_once('../includes/dbcon.php');
header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['request_id'])) {
    echo json_encode(['error' => 'Missing request ID']);
    exit();
}

$request_id = intval($_GET['request_id']);

try {
    // Check if this is a revision by counting existing revisions
    $revision_check = $pdo->prepare("SELECT COUNT(*) as rev_count FROM design_revisions WHERE request_id = ?");
    $revision_check->execute([$request_id]);
    $revision_count = $revision_check->fetch(PDO::FETCH_ASSOC)['rev_count'];

    echo json_encode([
        'success' => true,
        'is_revision' => $revision_count >= 1
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>