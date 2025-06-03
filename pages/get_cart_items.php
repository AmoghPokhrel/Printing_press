<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'items' => []]);
    exit();
}

$user_id = $_SESSION['user_id'];

// First get the user's cart
$cart_query = "SELECT id FROM cart WHERE uid = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows === 0) {
    echo json_encode(['success' => true, 'items' => [], 'total' => 0]);
    exit();
}

$cart_id = $cart_result->fetch_assoc()['id'];

// Fetch cart items
$sql = "SELECT cil.*, 
       t.name AS template_name, 
       t.image_path AS template_image, 
       t.cost AS template_cost,
       ctr.additional_notes as custom_notes, 
       ctr.final_design as custom_final_design,
       tm.final_design as modification_final_design
FROM cart_item_line cil
LEFT JOIN templates t ON cil.template_id = t.id
LEFT JOIN custom_template_requests ctr ON cil.custom_request_id = ctr.id
LEFT JOIN template_modifications tm ON cil.request_id = tm.id
WHERE cil.cart_id = ? AND (cil.status = 'active' OR cil.status IS NULL)
ORDER BY cil.id DESC";

// Debug: log the SQL query
error_log("Cart SQL: " . $sql);

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("SQL error in get_cart_items.php: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'SQL error: ' . $conn->error, 'sql' => $sql]);
    exit();
}

$stmt->bind_param('i', $cart_id);
$stmt->execute();
$result = $stmt->get_result();

// Debug logging
error_log("Cart ID: " . $cart_id);
error_log("Number of items found: " . $result->num_rows);

$items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    error_log("Processing item: " . json_encode($row));

    // Set image path based on req_type
    $image = '';
    if (!empty($row['req_type'])) {
        if ($row['req_type'] === 'modify') {
            $image = '../uploads/template_designs/' . $row['modification_final_design'];
        } elseif ($row['req_type'] === 'custom') {
            $image = '../uploads/custom_templates/' . $row['custom_final_design'];
        }
    } else {
        if (!empty($row['template_image'])) {
            $image = '../uploads/template_images/' . $row['template_image'];
        }
    }

    // Calculate price
    $price = 0;
    if (!empty($row['custom_request_id'])) {
        $rev_stmt = $conn->prepare("SELECT price FROM design_revisions WHERE request_id = ? ORDER BY revision_number DESC LIMIT 1");
        $rev_stmt->bind_param('i', $row['custom_request_id']);
        $rev_stmt->execute();
        $rev_result = $rev_stmt->get_result();
        if ($rev_row = $rev_result->fetch_assoc()) {
            $price = is_numeric($rev_row['price']) ? (float) $rev_row['price'] : 0;
        }
        $rev_stmt->close();
    } elseif (is_numeric($row['template_cost'])) {
        $price = (float) $row['template_cost'];
    }

    $item = [
        'id' => $row['id'],
        'quantity' => $row['quantity'],
        'type' => $row['template_id'] ? 'template' : 'custom',
        'name' => $row['template_id'] ? $row['template_name'] : 'Custom Design',
        'image' => $image,
        'notes' => $row['custom_notes'] ?? '',
        'price' => $price,
        'status' => $row['status']
    ];

    $total += $price * $row['quantity'];
    $items[] = $item;
}

error_log("Final items array: " . json_encode($items));
echo json_encode(['success' => true, 'items' => $items, 'total' => $total]);