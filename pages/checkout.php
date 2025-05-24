<?php
session_start();
require_once('../includes/db.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get user's cart
$cart_query = "SELECT id FROM cart WHERE uid = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows === 0) {
    header('Location: cart.php');
    exit();
}

$cart_id = $cart_result->fetch_assoc()['id'];

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Create new order
        $order_query = "INSERT INTO `order` (uid) VALUES (?)";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Get selected cart items
        $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];

        if (empty($selected_items)) {
            throw new Exception("Please select at least one item to checkout.");
        }

        // Get complete cart item data
        $cart_items_query = "SELECT cil.*, t.name as template_name, t.image_path as template_image, 
                           ctr.additional_notes as custom_notes, ctr.final_design
                           FROM cart_item_line cil 
                           LEFT JOIN templates t ON cil.template_id = t.id 
                           LEFT JOIN custom_template_requests ctr ON cil.custom_request_id = ctr.id 
                           WHERE cil.cart_id = ? AND cil.id IN (" . implode(',', array_fill(0, count($selected_items), '?')) . ")";

        $stmt = $conn->prepare($cart_items_query);
        $types = str_repeat('i', count($selected_items) + 1);
        $params = array_merge([$cart_id], $selected_items);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Insert complete order items
        foreach ($cart_items as $item) {
            $total_price = $item['price'] * $item['quantity'];
            $template_image = $item['template_image'] ?? $item['final_design'] ?? '';
            $template_name = $item['template_name'] ?? 'Custom Design';
            $custom_notes = $item['custom_notes'] ?? '';

            $insert_query = "INSERT INTO order_item_line 
                            (oid, ca_it_id, quantity, unit_price, total_price, 
                             template_name, template_image, custom_notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);

            // Now bind variables instead of literal values
            $stmt->bind_param(
                'iiiddsss',
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['price'],
                $total_price,
                $template_name,
                $template_image,
                $custom_notes
            );
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        // // Remove checked out items from cart
        // if (!empty($selected_items)) {
        //     $delete_query = "DELETE FROM cart_item_line WHERE cart_id = ? AND id IN (" .
        //         implode(',', array_fill(0, count($selected_items), '?')) . ")";
        //     $stmt = $conn->prepare($delete_query);
        //     $types = str_repeat('i', count($selected_items) + 1);
        //     $params = array_merge([$cart_id], $selected_items);
        //     $stmt->bind_param($types, ...$params);
        //     $stmt->execute();
        // }
        if (!empty($selected_items)) {
            $update_query = "UPDATE cart_item_line SET status = 'completed' 
                            WHERE cart_id = ? AND id IN (" .
                implode(',', array_fill(0, count($selected_items), '?')) . ")";
            $stmt = $conn->prepare($update_query);
            $types = str_repeat('i', count($selected_items) + 1);
            $params = array_merge([$cart_id], $selected_items);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }

        // Redirect to order confirmation
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
        error_log('Checkout Error: ' . $e->getMessage());
    }
}

// Get cart items for display
$cart_items_query = "SELECT cil.*, t.name as template_name, t.image_path as template_image, 
                    ctr.additional_notes as custom_notes, ctr.final_design
                    FROM cart_item_line cil 
                    LEFT JOIN templates t ON cil.template_id = t.id 
                    LEFT JOIN custom_template_requests ctr ON cil.custom_request_id = ctr.id 
                    WHERE cil.cart_id = ? AND (cil.status = 'active' OR cil.status IS NULL)";
$stmt = $conn->prepare($cart_items_query);
$stmt->bind_param('i', $cart_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: #f1f5f9;
        }

        .main-content {
            min-height: calc(100vh - 64px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem;
            margin-left: 250px;
            /* Account for sidebar */
            width: calc(100% - 250px);
        }

        .checkout-container {
            max-width: 800px;
            /* Decreased from 1000px */
            width: 90%;
            margin: 0 auto;
            padding: 2rem;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
        }

        .checkout-container h2 {
            color: #1e293b;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .checkout-items {
            margin-bottom: 2rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            max-height: calc(100vh - 400px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .checkout-items::-webkit-scrollbar {
            width: 6px;
        }

        .checkout-items::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .checkout-items::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }

        .checkout-items:empty {
            padding: 2rem;
            text-align: center;
            color: #64748b;
        }

        .checkout-items:empty::before {
            content: "No items in cart";
            font-size: 0.875rem;
        }

        .checkout-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .checkout-item:last-child {
            border-bottom: none;
        }

        .checkout-item:hover {
            background: #f8fafc;
        }

        .checkout-item input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            border: 2px solid #cbd5e1;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-right: 0.5rem;
        }

        .checkout-item input[type="checkbox"]:checked {
            background-color: #0f766e;
            border-color: #0f766e;
        }

        .checkout-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 0.375rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .checkout-item img:hover {
            transform: scale(1.05);
        }

        .checkout-item-details {
            flex: 1;
        }

        .checkout-item-details h3 {
            color: #1e293b;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .checkout-item-details p {
            color: #64748b;
            font-size: 0.813rem;
            margin-bottom: 0.125rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .checkout-item-details p i {
            color: #94a3b8;
            width: 14px;
            font-size: 0.875rem;
        }

        .price-info {
            color: #0f766e !important;
            font-weight: 600;
            font-size: 0.938rem !important;
            margin-top: 0.125rem !important;
        }

        .notes-info {
            background: #fff8e1;
            padding: 0.375rem 0.625rem;
            border-radius: 0.25rem;
            border-left: 2px solid #fbbf24;
            margin-top: 0.375rem;
            font-size: 0.75rem;
            color: #92400e;
            line-height: 1.2;
        }

        .checkout-total {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin: 2rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
        }

        .checkout-total-label {
            font-size: 1.125rem;
            color: #1e293b;
            font-weight: 600;
        }

        .checkout-total-amount {
            font-size: 1.5rem;
            color: #0f766e;
            font-weight: 700;
        }

        .checkout-form {
            max-width: 100%;
            margin: 0 auto;
        }

        .place-order-btn {
            width: 100%;
            background: linear-gradient(to right, #0f766e, #0d9488);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 0.75rem;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }

        .place-order-btn:hover {
            background: linear-gradient(to right, #0d9488, #0f766e);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .place-order-btn i {
            font-size: 1.25rem;
        }

        .error-message,
        .success-message {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .success-message {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #dcfce7;
        }

        .error-message i,
        .success-message i {
            font-size: 1.25rem;
        }

        @media (max-width: 1200px) {
            .checkout-container {
                width: 95%;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                margin-left: 0;
                width: 100%;
            }

            .checkout-container {
                width: 100%;
                padding: 1rem;
            }

            .checkout-items {
                max-height: calc(100vh - 300px);
            }

            .checkout-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .checkout-item img {
                width: 100%;
                height: 150px;
            }

            .checkout-total {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }

            .checkout-item-details {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <?php include('../includes/inner_header.php'); ?>

    <div class="main-content">
        <div class="checkout-container">
            <h2><i class="fas fa-shopping-cart"></i> Checkout</h2>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="checkout-form">
                <div class="checkout-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="checkout-item">
                            <input type="checkbox" name="selected_items[]" value="<?php echo $item['id']; ?>" checked>
                            <img src="<?php echo $item['template_image'] ? '../uploads/templates/' . $item['template_image'] : '../uploads/custom_templates/' . $item['final_design']; ?>"
                                alt="<?php echo htmlspecialchars($item['template_name'] ?? 'Custom Design'); ?>"
                                onerror="this.src='../assets/images/placeholder.jpg'">
                            <div class="checkout-item-details">
                                <h3><?php echo htmlspecialchars($item['template_name'] ?? 'Custom Design'); ?></h3>
                                <p><i class="fas fa-box"></i> Quantity: <?php echo $item['quantity']; ?></p>
                                <p class="price-info"><i class="fas fa-tag"></i> Rs
                                    <?php echo number_format($item['price'], 2); ?>
                                </p>
                                <?php if ($item['custom_notes']): ?>
                                    <div class="notes-info">
                                        <i class="fas fa-note-sticky"></i>
                                        <?php echo htmlspecialchars($item['custom_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-total">
                    <span class="checkout-total-label">Total Amount</span>
                    <span class="checkout-total-amount">Rs <?php echo number_format($total, 2); ?></span>
                </div>

                <button type="submit" name="checkout" class="place-order-btn">
                    <i class="fas fa-shopping-bag"></i>
                    Place Order
                </button>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>