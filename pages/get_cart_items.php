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
$sql = "SELECT cil.id, cil.quantity, cil.template_id, cil.request_id, cil.custom_request_id, cil.final_design AS cil_final_design, cil.price, cil.status,
       cil.req_type,
       tm.final_design AS modification_final_design,
       t.name AS template_name, t.image_path AS template_image, t.cost AS template_cost
FROM cart_item_line cil
LEFT JOIN templates t ON cil.template_id = t.id
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

    // For custom templates, fetch latest design revision for image and price
    $custom_image = null;
    $custom_price = null;
    if (!empty($row['request_id'])) {
        $rev_stmt = $conn->prepare("SELECT final_design, price FROM design_revisions WHERE request_id = ? ORDER BY revision_number DESC LIMIT 1");
        $rev_stmt->bind_param('i', $row['request_id']);
        $rev_stmt->execute();
        $rev_result = $rev_stmt->get_result();
        if ($rev_row = $rev_result->fetch_assoc()) {
            error_log('Design revision for request_id ' . $row['request_id'] . ': ' . print_r($rev_row, true));
            $custom_image = $rev_row['final_design'] ? '/printing_press/uploads/custom_templates/' . $rev_row['final_design'] : null;
            $custom_price = is_numeric($rev_row['price']) ? (float) $rev_row['price'] : null;
        }
        $rev_stmt->close();
    }

    // Price logic: custom templates from design_revisions, others from templates
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

    // Set image path based on req_type
    $image = '';
    if (!empty($row['req_type'])) {
        if ($row['req_type'] === 'modify') {
            $image = '/printing_press/uploads/template_designs/' . $row['cil_final_design'];
        } elseif ($row['req_type'] === 'custom') {
            $image = '/printing_press/uploads/custom_templates/' . $row['cil_final_design'];
        }
    } else {
        // Fallback logic if req_type is missing
        if (!empty($row['template_id']) && !empty($row['cil_final_design'])) {
            $image = '/printing_press/uploads/template_designs/' . $row['cil_final_design'];
        } elseif (!empty($row['custom_request_id']) && !empty($row['cil_final_design'])) {
            $image = '/printing_press/uploads/custom_templates/' . $row['cil_final_design'];
        } elseif (!empty($row['template_image'])) {
            $image = '/printing_press/uploads/template_images/' . $row['template_image'];
        }
    }

    $item = [
        'id' => $row['id'],
        'quantity' => $row['quantity'],
        'type' => $row['template_id'] ? 'template' : 'custom',
        'name' => $row['template_id'] ? $row['template_name'] : 'Custom Design',
        'image' => $image,
        'notes' => '',
        'price' => $price,
        'status' => $row['status']
    ];
    $items[] = $item;
}

error_log("Final items array: " . json_encode($items));
echo json_encode(['success' => true, 'items' => $items, 'total' => $total]);