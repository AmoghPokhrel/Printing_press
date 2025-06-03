<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$pageTitle = 'Order History & Payments';

include '../includes/db.php';

// Check if user is logged in and is Admin or Super Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Get search parameters
$search_order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
$search_payment_method = isset($_GET['payment_method']) ? trim($_GET['payment_method']) : '';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Build the query with search conditions
$where_conditions = array();
$params = array();

if (!empty($search_order_id)) {
    $where_conditions[] = "o.id = ?";
    $params[] = $search_order_id;
}

if (!empty($search_payment_method)) {
    if ($search_payment_method === 'esewa') {
        $where_conditions[] = "EXISTS (
            SELECT 1 FROM order_item_line oil 
            JOIN payments p ON oil.id = p.order_item_id 
            WHERE oil.oid = o.id AND p.payment_method = 'esewa'
        )";
    } elseif ($search_payment_method === 'physical') {
        $where_conditions[] = "EXISTS (
            SELECT 1 FROM order_item_line oil 
            JOIN payments p ON oil.id = p.order_item_id 
            WHERE oil.oid = o.id AND p.payment_method != 'esewa'
        )";
    }
}

// Get total number of orders with search conditions
$count_query = "SELECT COUNT(DISTINCT o.id) as total FROM `order` o";
if (!empty($where_conditions)) {
    $count_query .= " WHERE " . implode(" AND ", $where_conditions);
}

$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Main query for orders
$query = "SELECT DISTINCT o.*, u.name as customer_name 
          FROM `order` o 
          LEFT JOIN users u ON o.uid = u.id";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$orders = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Now include header after data retrieval
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .search-form {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-form .form-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-form label {
            font-weight: 500;
            color: #333;
            min-width: 100px;
        }

        .search-form input,
        .search-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
        }

        .search-form button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .search-form button:hover {
            background: #0056b3;
        }

        .search-form button[type="button"] {
            background: #6c757d;
        }

        .search-form button[type="button"]:hover {
            background: #5a6268;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .orders-table tr:hover {
            background: #f5f5f5;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background: #ffeeba;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-ready {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-cancelled {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .debug-log {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .debug-log h3 {
            margin-top: 0;
            color: #333;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            gap: 10px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #333;
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #f5f5f5;
        }

        .pagination .active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination .disabled {
            color: #999;
            pointer-events: none;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            z-index: 10001;
        }

        body.modal-open {
            overflow: hidden;
        }

        .modal-open .inner-header,
        .modal-open .hamburger {
            display: none !important;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }

        .detail-value {
            flex: 1;
        }

        .orders-table tbody tr {
            cursor: pointer;
        }

        .orders-table tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <!-- <h1 class="page-title">Order History & Payments</h1> -->

            <!-- Search Form -->
            <form class="search-form" method="GET">
                <div class="form-group">
                    <label for="order_id">Order ID:</label>
                    <input type="number" id="order_id" name="order_id" placeholder="Search by Order ID"
                        value="<?php echo htmlspecialchars($search_order_id); ?>">
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <select id="payment_method" name="payment_method">
                        <option value="">All Payment Methods</option>
                        <option value="esewa" <?php echo $search_payment_method === 'esewa' ? 'selected' : ''; ?>>eSewa
                        </option>
                        <option value="physical" <?php echo $search_payment_method === 'physical' ? 'selected' : ''; ?>>
                            Physical (Cash/Other)</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit">Search</button>
                    <button type="button" onclick="window.location.href='admin_order_history.php'">Reset</button>
                </div>
            </form>

            <!-- Orders Table -->
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Payment Method</th>
                        <th>Payment Status</th>
                        <th>Paid Amount</th>
                        <th>Order Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($orders)) {
                        echo "<tr><td colspan='5'>No orders found</td></tr>";
                    } else {
                        foreach ($orders as $order) {
                            // Get order items and payments
                            $items_query = "SELECT oil.*, p.payment_method, p.amount as paid_amount, 
                                          p.status as payment_status, p.payment_date, p.transaction_id 
                                          FROM order_item_line oil 
                                          LEFT JOIN payments p ON oil.id = p.order_item_id 
                                          WHERE oil.oid = " . intval($order['id']);

                            $items_result = $conn->query($items_query);

                            $total_amount = 0;
                            $total_paid = 0;
                            $order_statuses = array();
                            $payment_methods = array();
                            $payment_statuses = array();
                            $payment_dates = array();
                            $transactions = array(); // Array to store transaction IDs
                    
                            if ($items_result && $items_result->num_rows > 0) {
                                while ($item = $items_result->fetch_assoc()) {
                                    $total_amount += floatval($item['total_price']);
                                    $order_statuses[$item['status']] = true;

                                    if (!empty($item['payment_method'])) {
                                        if (strtolower($item['payment_method']) === 'esewa') {
                                            $payment_methods['eSewa'] = true;
                                        } else {
                                            $payment_methods['Physical'] = true;
                                        }

                                        $payment_statuses[$item['payment_status']] = true;
                                        if (!empty($item['payment_date'])) {
                                            $payment_dates[] = $item['payment_date'];
                                        }
                                        if (!empty($item['transaction_id'])) {
                                            $transactions[] = [
                                                'method' => $item['payment_method'],
                                                'id' => $item['transaction_id']
                                            ];
                                        }
                                        $total_paid += floatval($item['paid_amount']);
                                    }
                                }
                            }
                            ?>
                            <tr onclick="showOrderDetails(<?php
                            echo htmlspecialchars(json_encode([
                                'orderId' => $order['id'],
                                'customerName' => $order['customer_name'] ?? 'Unknown',
                                'orderDate' => date('Y-m-d H:i', strtotime($order['order_date'])),
                                'totalAmount' => number_format($total_amount, 2),
                                'paymentDates' => empty($payment_dates) ? ['N/A'] : array_map(function ($date) {
                                    return date('Y-m-d H:i', strtotime($date));
                                }, $payment_dates),
                                'transactions' => $transactions
                            ]));
                            ?>)">
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php
                                if (!empty($payment_methods)) {
                                    $methods = array_keys($payment_methods);
                                    sort($methods);
                                    echo implode(', ', $methods);
                                } else {
                                    echo 'Not Paid';
                                }
                                ?></td>
                                <td>
                                    <?php
                                    if (empty($payment_statuses)) {
                                        echo "<span class='status-badge status-pending'>Pending</span>";
                                    } else {
                                        foreach (array_keys($payment_statuses) as $status) {
                                            echo "<span class='status-badge status-" . strtolower($status) . "'>"
                                                . htmlspecialchars($status)
                                                . "</span> ";
                                        }
                                    }
                                    ?>
                                </td>
                                <td>Rs. <?php echo number_format($total_paid, 2); ?></td>
                                <td>
                                    <?php
                                    if (empty($order_statuses)) {
                                        echo "<span class='status-badge status-pending'>Pending</span>";
                                    } else {
                                        foreach (array_keys($order_statuses) as $status) {
                                            echo "<span class='status-badge status-" . strtolower($status) . "'>"
                                                . htmlspecialchars($status)
                                                . "</span> ";
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>

            <!-- Order Details Modal -->
            <div id="orderDetailsModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal()">&times;</span>
                    <div class="modal-header">
                        <h2>Order Details</h2>
                    </div>
                    <div class="modal-body">
                        <div class="detail-row">
                            <div class="detail-label">Order ID:</div>
                            <div class="detail-value" id="modalOrderId"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Customer Name:</div>
                            <div class="detail-value" id="modalCustomerName"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Order Date:</div>
                            <div class="detail-value" id="modalOrderDate"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Total Amount:</div>
                            <div class="detail-value" id="modalTotalAmount"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Payment Date(s):</div>
                            <div class="detail-value" id="modalPaymentDates"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Transaction ID(s):</div>
                            <div class="detail-value" id="modalTransactions"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a
                            href="?page=1<?php echo !empty($search_order_id) ? '&order_id=' . urlencode($search_order_id) : ''; ?><?php echo !empty($search_payment_method) ? '&payment_method=' . urlencode($search_payment_method) : ''; ?>">&laquo;
                            First</a>
                        <a
                            href="?page=<?php echo $page - 1; ?><?php echo !empty($search_order_id) ? '&order_id=' . urlencode($search_order_id) : ''; ?><?php echo !empty($search_payment_method) ? '&payment_method=' . urlencode($search_payment_method) : ''; ?>">&lsaquo;
                            Previous</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; First</span>
                        <span class="disabled">&lsaquo; Previous</span>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<span class='active'>$i</span>";
                        } else {
                            echo "<a href='?page=$i" .
                                (!empty($search_order_id) ? '&order_id=' . urlencode($search_order_id) : '') .
                                (!empty($search_payment_method) ? '&payment_method=' . urlencode($search_payment_method) : '') .
                                "'>$i</a>";
                        }
                    }
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <a
                            href="?page=<?php echo $page + 1; ?><?php echo !empty($search_order_id) ? '&order_id=' . urlencode($search_order_id) : ''; ?><?php echo !empty($search_payment_method) ? '&payment_method=' . urlencode($search_payment_method) : ''; ?>">Next
                            &rsaquo;</a>
                        <a
                            href="?page=<?php echo $total_pages; ?><?php echo !empty($search_order_id) ? '&order_id=' . urlencode($search_order_id) : ''; ?><?php echo !empty($search_payment_method) ? '&payment_method=' . urlencode($search_payment_method) : ''; ?>">Last
                            &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Next &rsaquo;</span>
                        <span class="disabled">Last &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>

    <script>
        function showOrderDetails(orderData) {
            document.getElementById('modalOrderId').textContent = orderData.orderId;
            document.getElementById('modalCustomerName').textContent = orderData.customerName;
            document.getElementById('modalOrderDate').textContent = orderData.orderDate;
            document.getElementById('modalTotalAmount').textContent = 'Rs. ' + orderData.totalAmount;
            document.getElementById('modalPaymentDates').textContent = orderData.paymentDates.join(', ');

            // Format transaction IDs with payment methods
            const transactionsText = orderData.transactions.length > 0
                ? orderData.transactions.map(t => `${t.method}: ${t.id}`).join(', ')
                : 'N/A';
            document.getElementById('modalTransactions').textContent = transactionsText;

            document.getElementById('orderDetailsModal').style.display = 'block';
            document.body.classList.add('modal-open');
        }

        function closeModal() {
            document.getElementById('orderDetailsModal').style.display = 'none';
            document.body.classList.remove('modal-open');
        }

        window.onclick = function (event) {
            const modal = document.getElementById('orderDetailsModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>

</html>