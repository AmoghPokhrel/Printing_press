<?php
session_start();
require_once('../includes/dbcon.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

if (!isset($_GET['request_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Request ID is required']);
    exit();
}

$request_id = $_GET['request_id'];

// Check if this is a revision by counting existing revisions
$revision_check = $pdo->prepare("SELECT COUNT(*) as rev_count FROM design_revisions WHERE request_id = ?");
$revision_check->execute([$request_id]);
$revision_count = $revision_check->fetch(PDO::FETCH_ASSOC)['rev_count'];
$is_revision = $revision_count >= 1;

header('Content-Type: application/json');
echo json_encode(['is_revision' => $is_revision]);