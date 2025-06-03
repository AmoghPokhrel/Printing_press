<?php
session_start();
require_once('../includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'];

// Verify order belongs to user
$order_query = "SELECT o.*, u.name as customer_name 
                FROM `order` o 
                JOIN users u ON o.uid = u.id 
                WHERE o.id = ? AND o.uid = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Get order items from order_item_line with all necessary joins
$items_query = "SELECT 
    oil.*,
    oil.unit_price as price,
    cil.req_type,
    t.name AS template_name,
    t.image_path AS template_image,
    tm.final_design as modification_final_design,
    ctr.final_design as custom_final_design
    FROM order_item_line oil
    LEFT JOIN cart_item_line cil ON oil.ca_it_id = cil.id
    LEFT JOIN templates t ON cil.template_id = t.id
    LEFT JOIN template_modifications tm ON cil.request_id = tm.id
    LEFT JOIN custom_template_requests ctr ON cil.custom_request_id = ctr.id
    WHERE oil.oid = ?";

// Debug the query
error_log("Order confirmation query: " . $items_query);

$stmt = $conn->prepare($items_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Debug the results
error_log("Order items data: " . print_r($items, true));

$total = 0;
foreach ($items as $item) {
    // Calculate total using unit_price instead of price
    $total += $item['unit_price'] * $item['quantity'];
}

$pageTitle = 'Order Confirmation';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            min-height: calc(100vh - 64px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 1.5rem;
            margin-left: 250px;
            width: calc(100% - 250px);
            background: #f1f5f9;
        }

        .confirmation-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin: 0 auto;
            width: 95%;
            max-width: 1000px;
        }

        .success-message {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }

        .success-message h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .success-message p {
            margin: 0;
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .order-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }

        .order-header h3 {
            color: #2d3748;
            margin: 0 0 0.75rem 0;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .order-header p {
            margin: 0.375rem 0;
            color: #4a5568;
            font-size: 0.875rem;
        }

        .order-header strong {
            color: #2d3748;
            font-weight: 600;
        }

        .order-items {
            margin-bottom: 1.5rem;
        }

        .order-items h3 {
            color: #2d3748;
            margin: 0 0 1rem 0;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }

        .order-item:hover {
            background-color: #f8f9fa;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-right: 1rem;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-details h4 {
            color: #2d3748;
            margin: 0 0 0.375rem 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .order-item-details p {
            margin: 0.25rem 0;
            color: #4a5568;
            font-size: 0.875rem;
        }

        .order-total {
            text-align: right;
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
            margin: 1.5rem 0;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .continue-shopping {
            text-align: center;
            margin-top: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }

            .confirmation-container {
                padding: 1rem;
                width: 100%;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
                align-items: center;
            }

            .order-item img {
                margin: 0 0 0.75rem 0;
            }

            .order-total {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="confirmation-container">
            <div class="success-message">
                <h2><i class="fas fa-check-circle"></i> Order Placed Successfully!</h2>
                <p>Thank you for your order. Your order number is #<?php echo $order_id; ?></p>
            </div>
            <div class="order-header">
                <h3><i class="fas fa-info-circle"></i> Order Details</h3>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?>
                </p>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            </div>
            <div class="order-items">
                <h3><i class="fas fa-shopping-bag"></i> Order Items</h3>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <?php
                            // Set image path based on req_type
                            $image = '';
                            if (!empty($item['req_type'])) {
                                if ($item['req_type'] === 'modify') {
                                    $image = '../uploads/template_designs/' . $item['modification_final_design'];
                                    error_log("Order confirmation - Modified template image path: " . $image);
                                } elseif ($item['req_type'] === 'custom') {
                                    $image = '../uploads/custom_templates/' . $item['custom_final_design'];
                                    error_log("Order confirmation - Custom template image path: " . $image);
                                }
                            } else {
                                if (!empty($item['template_image'])) {
                                    $image = '../uploads/template_images/' . $item['template_image'];
                                    error_log("Order confirmation - Regular template image path: " . $image);
                                }
                            }
                            error_log("Order confirmation - Final image path: " . $image);
                            error_log("Order confirmation - Item data: " . print_r($item, true));

                            if (!empty($image)) {
                                echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($item['template_name']) . '" 
                                    onerror="this.src=\'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGFsaWdubWVudC1iYXNlbGluZT0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWYiIGZpbGw9IiNhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==\'">';
                            } else {
                                echo '<div class="no-image-placeholder">
                                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGFsaWdubWVudC1iYXNlbGluZT0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWYiIGZpbGw9IiNhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==" alt="No Image Available" style="width:80px;height:80px;object-fit:cover;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.1);margin-right:1rem;">
                                </div>';
                            }
                            ?>
                            <div class="order-item-details">
                                <h4><?php echo htmlspecialchars($item['template_name'] ?? 'Custom Design'); ?></h4>
                                <p><strong>Quantity:</strong> <?php echo $item['quantity']; ?></p>
                                <p><strong>Unit Price:</strong> Rs <?php echo number_format($item['unit_price'], 2); ?></p>
                                <p><strong>Total Price:</strong> Rs
                                    <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No items found for this order.
                        Please contact support if this is unexpected.
                    </div>
                <?php endif; ?>
            </div>
            <div class="order-total">
                <strong>Total Amount:</strong> Rs <?php echo number_format($total, 2); ?>
            </div>

            <div class="continue-shopping">
                <a href="customer_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
</body>

</html>