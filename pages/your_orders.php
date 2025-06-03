<?php
session_start();
$pageTitle = 'Your Orders';

include '../includes/db.php';
require_once '../includes/EsewaPayment.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$esewa = new EsewaPayment();

// Fetch all orders for the user
$query = "SELECT * FROM `order` WHERE uid = ? ORDER BY order_date DESC, id DESC";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all items for these orders
$order_items = [];
if (!empty($orders)) {
    // Debug table structure
    $tables = ['order_item_line', 'cart_item_line', 'templates', 'template_modifications', 'custom_template_requests'];
    foreach ($tables as $table) {
        $structure_query = "DESCRIBE $table";
        $result = $conn->query($structure_query);
        if ($result === false) {
            error_log("Error checking structure for table $table: " . $conn->error);
        } else {
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            error_log("Columns in $table: " . implode(', ', $columns));
        }
    }

    $order_ids = array_column($orders, 'id');
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    $types = str_repeat('i', count($order_ids));

    // Debug the query construction
    error_log("Order IDs: " . implode(',', $order_ids));
    error_log("IN clause: " . $in);
    error_log("Types string: " . $types);

    // First, let's try a simpler query to ensure basic functionality works
    $test_query = "SELECT oil.* FROM order_item_line oil WHERE oil.oid IN ($in)";
    error_log("Testing basic query: " . $test_query);
    $test_stmt = $conn->prepare($test_query);
    if ($test_stmt === false) {
        error_log("Basic query prepare failed: " . $conn->error);
    } else {
        $test_stmt->bind_param($types, ...$order_ids);
        if (!$test_stmt->execute()) {
            error_log("Basic query execute failed: " . $test_stmt->error);
        } else {
            error_log("Basic query succeeded");
        }
        $test_stmt->close();
    }

    // Now try the full query
    $item_query = "SELECT 
        oil.*, 
        p.status as payment_status, 
        p.id as payment_id,
        cil.req_type, 
        cil.final_design,
        t.name as template_name, 
        t.image_path as template_image,
        tm.final_design as modification_final_design,
        ctr.final_design as custom_final_design
                   FROM order_item_line oil 
                   LEFT JOIN payments p ON oil.id = p.order_item_id 
    LEFT JOIN cart_item_line cil ON oil.ca_it_id = cil.id
    LEFT JOIN templates t ON cil.template_id = t.id
    LEFT JOIN template_modifications tm ON cil.request_id = tm.id
    LEFT JOIN custom_template_requests ctr ON cil.custom_request_id = ctr.id
                   WHERE oil.oid IN ($in) 
                   ORDER BY oil.oid DESC, oil.id ASC";

    // Debug the final query
    error_log("Final SQL Query: " . $item_query);

    $item_stmt = $conn->prepare($item_query);
    if ($item_stmt === false) {
        error_log("SQL Prepare Error: " . $conn->error);
        die("Error preparing statement: " . $conn->error);
    }

    $item_stmt->bind_param($types, ...$order_ids);
    if (!$item_stmt->execute()) {
        error_log("SQL Execute Error: " . $item_stmt->error);
        die("Error executing statement: " . $item_stmt->error);
    }

    $items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $item) {
        $order_items[$item['oid']][] = $item;
    }
}

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 3;
$total_orders = count($orders);
$total_pages = ceil($total_orders / $per_page);
$start = ($page - 1) * $per_page;
$paginated_orders = array_slice($orders, $start, $per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .orders-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 18px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.10);
        }

        .order-header {
            padding: 10px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-id {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-date {
            color: #7f8c8d;
            font-size: 0.95em;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .order-table th,
        .order-table td {
            padding: 5px 6px;
            text-align: left;
        }

        .order-table th {
            background: #f1f3f6;
            color: #333;
            font-size: 0.98em;
        }

        .order-table tr:nth-child(even) {
            background: #fafbfc;
        }

        .order-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .order-status {
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 0.95em;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 1.2em;
        }

        .btn-info {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-info:hover {
            background-color: #217dbb;
        }

        .payment-btn {
            background-color: #10b981;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .payment-btn:hover {
            background-color: #059669;
        }

        .payment-btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .payment-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .payment-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .payment-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .payment-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Add styles for messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Add highlight animation for orders */
        @keyframes highlightOrder {
            0% {
                background-color: #fff7ed;
            }

            50% {
                background-color: #ffedd5;
            }

            100% {
                background-color: #fff7ed;
            }
        }

        .order-highlight {
            animation: highlightOrder 2s ease-in-out;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <!-- <h2><?php echo $pageTitle; ?></h2> -->

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="orders-container">
                <?php if (empty($paginated_orders)): ?>
                    <div class="no-orders">
                        <p>You haven't placed any orders yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($paginated_orders as $order): ?>
                        <div
                            class="order-card <?php echo isset($_GET['highlight']) && $_GET['highlight'] == $order['id'] ? 'order-highlight' : ''; ?>">
                            <div class="order-header">
                                <div>
                                    <span class="order-id">Order #<?= $order['id'] ?></span>
                                </div>
                                <span class="order-date" style="cursor: pointer;" data-customer-id="<?= $order['uid'] ?>"
                                    data-customer-name="<?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>"
                                    onclick="showCustomerDetails(this)">
                                    Order Date: <?= htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))) ?>
                                </span>
                            </div>
                            <div style="padding: 18px;">
                                <table class="order-table">
                                    <thead>
                                        <tr>
                                            <th>Template</th>
                                            <th>Image</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $orderTotal = 0;
                                        $allItemsReady = true;
                                        $anyItemPending = false;
                                        $allItemsCompleted = true;

                                        foreach ($order_items[$order['id']] ?? [] as $item):
                                            if ($item['status'] === 'ready') {
                                                $orderTotal += $item['total_price'];
                                                if (!isset($item['payment_status']) || $item['payment_status'] === 'pending') {
                                                    $anyItemPending = true;
                                                }
                                            }
                                            if ($item['status'] !== 'ready') {
                                                $allItemsReady = false;
                                            }
                                            if (!isset($item['payment_status']) || $item['payment_status'] !== 'completed') {
                                                $allItemsCompleted = false;
                                            }
                                            ?>
                                            <?php
                                            // Set image path based on req_type and final_design from cart_item_line
                                            $image = '';
                                            if (!empty($item['req_type'])) {
                                                if ($item['req_type'] === 'modify') {
                                                    $image = '../uploads/template_designs/' . $item['modification_final_design'];
                                                    error_log("Modified template image path: " . $image);
                                                } elseif ($item['req_type'] === 'custom') {
                                                    $image = '../uploads/custom_templates/' . $item['custom_final_design'];
                                                    error_log("Custom template image path: " . $image);
                                                }
                                            } else {
                                                if (!empty($item['template_image'])) {
                                                    $image = '../uploads/template_images/' . $item['template_image'];
                                                    error_log("Regular template image path: " . $image);
                                                }
                                            }
                                            error_log("Final image path: " . $image);
                                            error_log("Item data: " . print_r($item, true));
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['template_name'] ?? 'Custom Template') ?></td>
                                                <td>
                                                    <?php if (!empty($image)): ?>
                                                        <img src="<?= htmlspecialchars($image) ?>" alt="Template Image"
                                                            style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #eee;cursor:pointer;"
                                                            onclick="showOrderImageModal(this.src, '<?= htmlspecialchars($item['template_name'] ?? 'Custom Template') ?>')"
                                                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGFsaWdubWVudC1iYXNlbGluZT0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWYiIGZpbGw9IiNhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                                                    <?php else: ?>
                                                        <div class="no-image-placeholder">
                                                            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGFsaWdubWVudC1iYXNlbGluZT0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWYiIGZpbGw9IiNhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=="
                                                                alt="No Image Available"
                                                                style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #eee;">
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td>Rs <?= number_format($item['unit_price'], 2) ?></td>
                                                <td>Rs <?= number_format($item['total_price'], 2) ?></td>
                                                <td>
                                                    <span class="order-status status-<?= htmlspecialchars($item['status']) ?>">
                                                        <?= htmlspecialchars(ucfirst($item['status'])) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <!-- Payment Row -->
                                        <tr class="payment-row">
                                            <td colspan="4" style="text-align: right;"><strong>Order Total:</strong></td>
                                            <td><strong>Rs <?= number_format($orderTotal, 2) ?></strong></td>
                                            <td colspan="1">
                                                <?php if ($allItemsReady && $anyItemPending): ?>
                                                    <?php
                                                    $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/printing_press/pages";
                                                    $successUrl = $baseUrl . "/payment_success.php";
                                                    $failureUrl = $baseUrl . "/payment_fail.php?oid=" . $order['id'] . "&type=order";

                                                    // Enhanced debugging
                                                    error_log("============ Payment Debug Info ============");
                                                    error_log("Order ID: " . $order['id']);
                                                    error_log("Order Total: " . $orderTotal);
                                                    error_log("Success URL: " . $successUrl);
                                                    error_log("Failure URL: " . $failureUrl);
                                                    error_log("User ID: " . $_SESSION['user_id']);
                                                    error_log("========================================");

                                                    require_once '../includes/payment_functions.php';
                                                    $paymentData = $esewa->getPaymentForm($orderTotal, $successUrl, $failureUrl, $order['id']);

                                                    // Log payment form data
                                                    error_log("Payment Form Data: " . print_r($paymentData, true));

                                                    if (isset($paymentData['fields']['transaction_uuid'])) {
                                                        trackPaymentRequest($order['id'], $paymentData['fields']['transaction_uuid'], $orderTotal);
                                                        // Log tracking info
                                                        error_log("Payment tracking recorded for UUID: " . $paymentData['fields']['transaction_uuid']);
                                                    }

                                                    // Use absolute paths for success and failure URLs
                                                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                                                    $domain = $_SERVER['HTTP_HOST'];
                                                    $baseUrl = $protocol . $domain . dirname($_SERVER['PHP_SELF']);

                                                    $paymentData['fields']['success_url'] = $baseUrl . "/payment_success.php";
                                                    $paymentData['fields']['failure_url'] = $baseUrl . "/payment_fail.php?oid=" . $order['id'] . "&type=order";
                                                    $paymentData['fields']['oid'] = $order['id'];
                                                    $paymentData['fields']['payment_type'] = 'order';

                                                    error_log("[Order] Success URL: " . $paymentData['fields']['success_url']);
                                                    error_log("[Order] Failure URL: " . $paymentData['fields']['failure_url']);
                                                    ?>
                                                    <form action="<?php echo $paymentData['action_url']; ?>" method="POST"
                                                        style="margin:0;">
                                                        <?php foreach ($paymentData['fields'] as $name => $value): ?>
                                                            <input type="hidden" name="<?php echo $name; ?>"
                                                                value="<?php echo htmlspecialchars($value); ?>">
                                                        <?php endforeach; ?>
                                                        <button type="submit" class="payment-btn">
                                                            Pay Order with eSewa
                                                        </button>
                                                    </form>
                                                <?php elseif ($allItemsCompleted): ?>
                                                    <a href="generate_bill.php?order_id=<?php echo $order['id']; ?>"
                                                        class="btn-info" style="text-decoration: none;">
                                                        Download Bill
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div
                        style="text-align:center; margin: 20px 0; display: flex; justify-content: center; align-items: center; gap: 10px;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-info">Previous</a>
                        <?php endif; ?>
                        <span style="font-weight: 500;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-info">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
    <!-- Add this before </body> -->
    <div id="orderImageModal"
        style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);backdrop-filter:blur(3px);align-items:center;justify-content:center;">
        <span id="orderImageModalClose"
            style="position:absolute;top:20px;right:40px;font-size:40px;color:#fff;cursor:pointer;font-weight:bold;z-index:10000;">&times;</span>
        <div
            style="text-align: center; max-width: 90vw; max-height: 90vh; display: flex; flex-direction: column; align-items: center; gap: 15px;">
            <img id="orderImageModalImg" src="" alt=""
                style="max-width:90vw;max-height:70vh;display:block;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.4);position:relative;z-index:10000;">
            <div id="orderImageModalCaption"
                style="color: #fff; text-align: center; font-size: 18px; font-weight: 500; margin-top: 10px; max-width: 80vw; word-wrap: break-word;">
            </div>
        </div>
        <script>
            function showOrderImageModal(src, templateName) {
                document.body.style.overflow = 'hidden'; // Prevent scrolling
                var modal = document.getElementById('orderImageModal');
                var modalImg = document.getElementById('orderImageModalImg');
                var caption = document.getElementById('orderImageModalCaption');
                modal.style.display = 'flex';
                modalImg.src = src;
                modalImg.alt = 'Template Image';
                caption.innerHTML = '<strong>Template Name:</strong> ' + templateName;
            }

            function closeOrderImageModal() {
                document.body.style.overflow = ''; // Restore scrolling
                document.getElementById('orderImageModal').style.display = 'none';
            }

            document.getElementById('orderImageModalClose').onclick = closeOrderImageModal;

            document.getElementById('orderImageModal').onclick = function (e) {
                if (e.target === this) {
                    closeOrderImageModal();
                }
            };

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeOrderImageModal();
                }
            });
        </script>

        <!-- Customer Details Modal -->
        <div id="customerModal" class="modal"
            style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div class="modal-content"
                style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; position: relative; z-index: 10000;">
                <span class="close"
                    style="position: absolute; right: 20px; top: 10px; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                <h3 style="margin-bottom: 20px;">Customer Details</h3>
                <div class="customer-info">
                    <p><strong>Customer ID:</strong> <span id="customerId"></span></p>
                    <p><strong>Customer Name:</strong> <span id="customerName"></span></p>
                </div>
            </div>
        </div>

        <script>
            // Customer Details Modal functionality
            const customerModal = document.getElementById('customerModal');
            const closeCustomerModal = document.querySelector('#customerModal .close');

            function showCustomerDetails(element) {
                document.body.style.overflow = 'hidden'; // Prevent scrolling
                const customerId = element.getAttribute('data-customer-id');
                const customerName = element.getAttribute('data-customer-name');

                document.getElementById('customerId').textContent = customerId;
                document.getElementById('customerName').textContent = customerName;

                customerModal.style.display = 'block';
            }

            closeCustomerModal.onclick = function () {
                document.body.style.overflow = ''; // Restore scrolling
                customerModal.style.display = 'none';
            }

            window.onclick = function (event) {
                if (event.target == customerModal) {
                    document.body.style.overflow = ''; // Restore scrolling
                    customerModal.style.display = 'none';
                }
            }
        </script>

        <script>
            // Scroll to highlighted order if exists
            document.addEventListener('DOMContentLoaded', function () {
                const highlightedOrder = document.querySelector('.order-highlight');
                if (highlightedOrder) {
                    highlightedOrder.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        </script>
</body>

</html>