<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'template_finishing.php';
    $success = false;
    $message = '';


    $check_cart_query = "SELECT id FROM cart WHERE uid = ?";
    $stmt = $conn->prepare($check_cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();

    if ($cart_result->num_rows === 0) {

        $create_cart_query = "INSERT INTO cart (uid) VALUES (?)";
        $stmt = $conn->prepare($create_cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;
    } else {
        $cart_id = $cart_result->fetch_assoc()['id'];
    }

    if (isset($_POST['template_id'])) {
        $template_id = intval($_POST['template_id']);
        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;
        $unique_id = isset($_POST['unique_id']) ? $_POST['unique_id'] : uniqid('template_', true);
        $template_price = 0;
        $price_query = "SELECT cost FROM templates WHERE id = ?";
        $price_stmt = $conn->prepare($price_query);
        $price_stmt->bind_param('i', $template_id);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        if ($price_row = $price_result->fetch_assoc()) {
            $template_price = floatval($price_row['cost']);
        }
        $price_stmt->close();
        $final_design = null;
        $final_design_query = "SELECT final_design FROM template_modifications WHERE id = ?";
        $fd_stmt = $conn->prepare($final_design_query);
        $fd_stmt->bind_param('i', $request_id);
        $fd_stmt->execute();
        $fd_result = $fd_stmt->get_result();
        if ($fd_row = $fd_result->fetch_assoc()) {
            $final_design = basename($fd_row['final_design']);
        }
        $fd_stmt->close();
        $req_type = 'modify';


        if ($request_id) {
            $check_query = "SELECT id, quantity FROM cart_item_line WHERE cart_id = ? AND request_id = ? AND status = 'active'";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $cart_id, $request_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Item exists and is active, update quantity, price, and final_design                $existing_item = $result->fetch_assoc();
                $new_quantity = $existing_item['quantity'] + $quantity;

                $update_query = "UPDATE cart_item_line SET quantity = ?, price = ?, final_design = ?, req_type = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("idssi", $new_quantity, $template_price, $final_design, $req_type, $existing_item['id']);
                $success = $stmt->execute();
                $message = $success ? 'Cart quantity updated!' : 'Error updating cart quantity.';
            } else {
                $insert_query = "INSERT INTO cart_item_line (cart_id, template_id, request_id, unique_id, quantity, price, final_design, req_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iiisidss", $cart_id, $template_id, $request_id, $unique_id, $quantity, $template_price, $final_design, $req_type);
                $success = $stmt->execute();
                $message = $success ? 'Template added to cart!' : 'Error adding template to cart.';
            }
        } else {
            $insert_query = "INSERT INTO cart_item_line (cart_id, template_id, request_id, unique_id, quantity, price, req_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiisidss", $cart_id, $template_id, $request_id, $unique_id, $quantity, $template_price, $req_type);
            $success = $stmt->execute();
            $message = $success ? 'Template added to cart!' : 'Error adding template to cart.';
        }
    } elseif (isset($_POST['custom_request_id'])) {
        $custom_request_id = intval($_POST['custom_request_id']);
        $final_design = isset($_POST['final_design']) ? $_POST['final_design'] : null;
        $price = (isset($_POST['price']) && $_POST['price'] !== '') ? floatval($_POST['price']) : null;
        $unique_id = uniqid('custom_', true);
        $req_type = 'custom';

        // If price is not set, try to fetch from design_revisions table using final_design
        if ($price === null) {
            $price_query = "SELECT price FROM design_revisions WHERE final_design = ?";
            $price_stmt = $conn->prepare($price_query);
            if (!$price_stmt) {
                error_log("Prepare failed: " . $conn->error);
                $price = 0;
            } else {
                $price_stmt->bind_param('s', $final_design);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                if ($price_row = $price_result->fetch_assoc()) {
                    $price = floatval($price_row['price']);
                } else {
                    $price = 0;
                }
                $price_stmt->close();
            }
        }

        // Check if a custom request with the same ID already exists in the cart_item_line
        $check_query = "SELECT id, quantity FROM cart_item_line WHERE cart_id = ? AND custom_request_id = ? AND status = 'active'";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $cart_id, $custom_request_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Item exists and is active, update quantity and price
            $existing_item = $result->fetch_assoc();
            $new_quantity = $existing_item['quantity'] + $quantity;

            $update_query = "UPDATE cart_item_line SET quantity = ?, price = ?, req_type = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("idsi", $new_quantity, $price, $req_type, $existing_item['id']);
            $success = $stmt->execute();
            $message = $success ? 'Cart quantity updated!' : 'Error updating cart quantity.';
        } else {
            // Item doesn't exist or is completed, insert as new item
            $insert_query = "INSERT INTO cart_item_line (cart_id, custom_request_id, final_design, unique_id, quantity, price, req_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iissids", $cart_id, $custom_request_id, $final_design, $unique_id, $quantity, $price, $req_type);
            $success = $stmt->execute();
            $message = $success ? 'Custom design added to cart!' : 'Error adding custom design to cart.';
        }

        // For custom templates (custom_request_id)
        if ($final_design && isset($_POST['custom_request_id'])) {
            $final_design_filename = basename($final_design);
            $final_design = 'custom_templates/' . $final_design_filename;
        }
    } elseif (isset($_POST['add_custom_to_cart'])) {
        $custom_request_id = $_POST['custom_request_id'];
        $final_design = $_POST['final_design'];
        $quantity = $_POST['quantity'];
        $price = $_POST['price'];
        $unique_id = uniqid('custom_', true);
        $req_type = 'custom';


        $check_query = "SELECT id, quantity FROM cart_item_line WHERE cart_id = ? AND custom_request_id = ? AND status = 'active'";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $cart_id, $custom_request_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $existing_item = $result->fetch_assoc();
            $new_quantity = $existing_item['quantity'] + $quantity;

            $update_query = "UPDATE cart_item_line SET quantity = ?, price = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("idi", $new_quantity, $price, $existing_item['id']);
            $success = $stmt->execute();
            $message = $success ? 'Cart quantity updated!' : 'Error updating cart quantity.';
        } else {

            $insert_query = "INSERT INTO cart_item_line (cart_id, custom_request_id, final_design, unique_id, quantity, price, req_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iissids", $cart_id, $custom_request_id, $final_design, $unique_id, $quantity, $price, $req_type);
            $success = $stmt->execute();
            $message = $success ? 'Custom design added to cart!' : 'Error adding custom design to cart.';
        }
    } else {
        $message = 'Invalid cart request.';
    }

    if ($is_ajax) {
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    } else {
        $_SESSION['success_message'] = $message;
        header('Location: ' . $redirect_to);
        exit();
    }
} else {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit();
    }
    header('Location: template_finishing.php');
    exit();
}