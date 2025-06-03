<?php
session_start();
$pageTitle = 'Orders Handling';

include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Get search and sort parameters
$search_order_id = isset($_GET['search_order_id']) ? trim($_GET['search_order_id']) : '';
$search_item = isset($_GET['search_item']) ? trim($_GET['search_item']) : '';
$sort_status = isset($_GET['sort_status']) ? trim($_GET['sort_status']) : '';

// Build the query with search and sort conditions
$query = "SELECT o.id as order_id, o.order_date, o.uid as user_id, u.name as user_name, 
          u.phone as user_phone, GROUP_CONCAT(oi.template_name SEPARATOR ', ') as item_names, 
          GROUP_CONCAT(oi.status) as item_statuses
          FROM `order` o
          JOIN order_item_line oi ON o.id = oi.oid
          LEFT JOIN users u ON o.uid = u.id";

// Add search conditions
$where_conditions = [];
if (!empty($search_order_id)) {
    $where_conditions[] = "o.id = " . intval($search_order_id);
}
if (!empty($search_item)) {
    $where_conditions[] = "oi.template_name LIKE '%" . $conn->real_escape_string($search_item) . "%'";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " GROUP BY o.id, o.order_date, o.uid, u.name";

// Add sorting condition
if (!empty($sort_status)) {
    $query .= " HAVING MAX(oi.status) = '" . $conn->real_escape_string($sort_status) . "'";
}

$query .= " ORDER BY o.order_date DESC, o.id DESC";

$result = $conn->query($query);
$orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get unique statuses for dropdown
$status_query = "SELECT DISTINCT status FROM order_item_line ORDER BY status";
$status_result = $conn->query($status_query);
$statuses = $status_result ? $status_result->fetch_all(MYSQLI_ASSOC) : [];

// Pagination
$items_per_page = 6;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$total_orders = count($orders);
$total_pages = ceil($total_orders / $items_per_page);
$start_index = ($current_page - 1) * $items_per_page;
$paginated_orders = array_slice($orders, $start_index, $items_per_page);
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

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .orders-table th,
        .orders-table td {
            padding: 14px 10px;
            text-align: left;
        }

        .orders-table th {
            background: #f1f3f6;
            color: #333;
            font-size: 1em;
        }

        .orders-table tr:nth-child(even) {
            background: #fafbfc;
        }

        .orders-table tr:hover {
            background: #f0f8ff;
        }

        .status-dropdown {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1em;
        }

        .pagination {
            text-align: center;
            margin: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .pagination a {
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            background-color: #3498db;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .order-id {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-date {
            color: #7f8c8d;
            font-size: 0.95em;
        }

        .order-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .order-row:hover {
            background-color: #f5f5f5;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: none;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalFadeIn 0.3s ease-out;
            margin: 0;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close-modal {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
            transition: color 0.2s ease;
            line-height: 1;
        }

        .close-modal:hover {
            color: #000;
        }

        .customer-details {
            margin-top: 20px;
        }

        .detail-row {
            margin-bottom: 10px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: none;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 3px;
            font-size: 0.9rem;
        }

        .detail-value {
            width: 100%;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 2px;
            font-size: 14px;
        }

        textarea.detail-value {
            min-height: 60px;
            resize: vertical;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .modal h2 {
            margin: 0 0 20px 0;
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .search-sort-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .search-filters {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .search-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .search-group label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #4a5568;
        }

        .search-form input[type="text"],
        .status-dropdown {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .search-form input[type="text"]:focus,
        .status-dropdown:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .search-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            align-items: center;
        }

        .search-btn {
            padding: 8px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .search-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .clear-filters {
            padding: 8px 20px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .clear-filters:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .search-filters {
                grid-template-columns: 1fr;
            }

            .search-actions {
                flex-direction: column;
                width: 100%;
            }

            .search-btn,
            .clear-filters {
                width: 100%;
                justify-content: center;
            }
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .transaction-verification input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 14px;
        }

        #verificationMessage {
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            background-color: #f8f9fa;
        }

        .payment-type-selector {
            margin-bottom: 15px !important;
        }

        .payment-type-selector label {
            margin-bottom: 5px !important;
        }

        .transaction-verification p {
            margin: 0 0 10px 0;
            font-size: 0.9rem;
            color: #4a5568;
        }

        #physicalPaymentFields .detail-row:last-child {
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <!-- <h2><?php echo $pageTitle; ?></h2> -->
            <div class="orders-container">
                <!-- Customer Details Modal -->
                <div id="customerModal" class="modal">
                    <div class="modal-content">
                        <span class="close-modal" onclick="closeModal('customerModal')">&times;</span>
                        <h2>Customer Details</h2>
                        <div class="customer-details">
                            <div class="detail-row">
                                <div class="detail-label">Customer Name:</div>
                                <div class="detail-value" id="modalCustomerName"></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phone Number:</div>
                                <div class="detail-value" id="modalCustomerPhone"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Verification Modal -->
                <div id="transactionModal" class="modal">
                    <div class="modal-content">
                        <span class="close-modal" onclick="closeModal('transactionModal')">&times;</span>
                        <h2>Payment Verification</h2>
                        <div class="transaction-verification">
                            <div id="verificationMessage" style="display: none; margin-bottom: 15px;"></div>

                            <div class="payment-type-selector" style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 10px;">Select Payment Type:</label>
                                <select id="paymentTypeSelect" class="form-control" onchange="togglePaymentFields()"
                                    style="width: 100%; padding: 8px; margin-bottom: 15px;">
                                    <option value="online">Online Payment (eSewa)</option>
                                    <option value="physical">Physical Payment (Cash/Card)</option>
                                </select>
                            </div>

                            <div id="onlinePaymentFields">
                                <p>Please enter the transaction ID to verify the online payment.</p>
                                <div class="detail-row">
                                    <div class="detail-label">Transaction ID:</div>
                                    <input type="text" id="transactionIdInput" class="detail-value"
                                        placeholder="Enter transaction ID">
                                </div>
                            </div>

                            <div id="physicalPaymentFields" style="display: none;">
                                <p>Please enter the physical payment details.</p>
                                <div class="detail-row">
                                    <div class="detail-label">Amount Paid:</div>
                                    <input type="number" id="physicalAmountInput" class="detail-value" step="0.01"
                                        placeholder="Enter amount">
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Payment Method:</div>
                                    <select id="physicalMethodInput" class="detail-value">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Receipt/Reference Number:</div>
                                    <input type="text" id="physicalReferenceInput" class="detail-value"
                                        placeholder="Enter receipt/reference number">
                                </div>
                            </div>

                            <div style="margin-top: 20px; text-align: right;">
                                <button onclick="closeModal('transactionModal')" class="btn-secondary"
                                    style="margin-right: 10px;">Cancel</button>
                                <button onclick="handlePaymentVerification()" class="btn-primary">Verify &
                                    Complete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="search-sort-container">
                    <form action="" method="GET" class="search-form">
                        <div class="search-filters">
                            <div class="search-group">
                                <label for="search_order_id">Order ID</label>
                                <input type="text" id="search_order_id" name="search_order_id"
                                    placeholder="Search by Order ID"
                                    value="<?php echo htmlspecialchars($search_order_id); ?>">
                            </div>
                            <div class="search-group">
                                <label for="search_item">Item Name</label>
                                <input type="text" id="search_item" name="search_item" placeholder="Search by Item"
                                    value="<?php echo htmlspecialchars($search_item); ?>">
                            </div>
                            <div class="search-group">
                                <label for="sort_status">Status</label>
                                <select id="sort_status" name="sort_status" class="status-dropdown">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status['status']); ?>" <?php echo $sort_status === $status['status'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['status']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="search-actions">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if (!empty($search_order_id) || !empty($search_item) || !empty($sort_status)): ?>
                                <a href="orders.php" class="clear-filters">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Order Items</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated_orders as $order): ?>
                            <tr class="order-row" data-user-id="<?= htmlspecialchars($order['user_id']); ?>"
                                data-user-name="<?= htmlspecialchars($order['user_name'] ?? 'Unknown User'); ?>"
                                data-user-phone="<?= htmlspecialchars($order['user_phone'] ?? 'Not provided'); ?>">
                                <td class="order-id">#<?= $order['order_id'] ?></td>
                                <td class="order-date">
                                    <?= htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))) ?>
                                </td>
                                <td><?= htmlspecialchars($order['item_names']) ?></td>
                                <td onclick="event.stopPropagation();">
                                    <?php
                                    $statuses = array_unique(explode(',', $order['item_statuses']));
                                    $current_status = count($statuses) === 1 ? trim($statuses[0]) : 'mixed';
                                    ?>
                                    <form method="post" action="update_order_status.php" style="margin:0;"
                                        id="statusForm_<?= $order['order_id'] ?>" onclick="event.stopPropagation();">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="status" value="">
                                        <select name="status_select" class="status-dropdown"
                                            onchange="handleStatusChange(this, <?= $order['order_id'] ?>)"
                                            onclick="event.stopPropagation();">
                                            <option value="pending" <?= $current_status === 'pending' ? 'selected' : '' ?>>
                                                Pending</option>
                                            <option value="ready" <?= $current_status === 'ready' ? 'selected' : '' ?>>Ready
                                            </option>
                                            <option value="completed" <?= $current_status === 'completed' ? 'selected' : '' ?>>
                                                Completed</option>
                                            <option value="cancelled" <?= $current_status === 'cancelled' ? 'selected' : '' ?>>
                                                Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php if ($total_pages > 1): ?>
                        <?php
                        // Build query string for pagination links
                        $query_params = [];
                        if (!empty($search_order_id))
                            $query_params['search_order_id'] = $search_order_id;
                        if (!empty($search_item))
                            $query_params['search_item'] = $search_item;
                        if (!empty($sort_status))
                            $query_params['sort_status'] = $sort_status;

                        // Create base query string
                        $query_string = http_build_query($query_params);
                        $page_link_base = '?' . ($query_string ? $query_string . '&' : '');
                        ?>

                        <?php if ($current_page > 1): ?>
                            <a href="<?php echo $page_link_base; ?>page=<?php echo $current_page - 1; ?>"
                                class="btn btn-info">Previous</a>
                        <?php endif; ?>

                        <span style="font-weight: 500;">Page <?php echo $current_page; ?> of
                            <?php echo $total_pages; ?></span>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="<?php echo $page_link_base; ?>page=<?php echo $current_page + 1; ?>"
                                class="btn btn-info">Next</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>

    <script>
        let currentForm = null;
        let currentSelectedStatus = null;

        function handleStatusChange(selectElement, orderId) {
            // Store the previous value before any change
            const previousValue = selectElement.getAttribute('data-previous-value') || selectElement.value;
            selectElement.setAttribute('data-previous-value', previousValue);

            if (selectElement.value === 'completed') {
                // Prevent the change until verification
                selectElement.value = previousValue;

                // Store the current form and selected status
                currentForm = document.getElementById('statusForm_' + orderId);
                currentSelectedStatus = selectElement;

                // Reset the verification message and input
                const messageDiv = document.getElementById('verificationMessage');
                if (messageDiv) {
                    messageDiv.style.display = 'none';
                    messageDiv.innerHTML = '';
                }

                document.getElementById('transactionIdInput').value = '';

                // Reset physical payment fields
                if (document.getElementById('physicalAmountInput')) {
                    document.getElementById('physicalAmountInput').value = '';
                    document.getElementById('physicalReferenceInput').value = '';
                    document.getElementById('physicalMethodInput').value = 'cash';
                }

                // Show the transaction verification modal
                document.getElementById('transactionModal').classList.add('show');
                document.getElementById('transactionModal').style.display = 'flex';

                // Set default payment type to online
                if (document.getElementById('paymentTypeSelect')) {
                    document.getElementById('paymentTypeSelect').value = 'online';
                    togglePaymentFields();
                }

                // Prevent the form from submitting
                return false;
            } else {
                // For other statuses, update hidden input and submit the form
                const form = document.getElementById('statusForm_' + orderId);
                form.querySelector('input[name="status"]').value = selectElement.value;
                form.submit();
            }
        }

        function togglePaymentFields() {
            const paymentType = document.getElementById('paymentTypeSelect').value;
            const onlineFields = document.getElementById('onlinePaymentFields');
            const physicalFields = document.getElementById('physicalPaymentFields');

            if (paymentType === 'online') {
                onlineFields.style.display = 'block';
                physicalFields.style.display = 'none';
            } else {
                onlineFields.style.display = 'none';
                physicalFields.style.display = 'block';
            }
        }

        function handlePaymentVerification() {
            const paymentType = document.getElementById('paymentTypeSelect').value;

            if (paymentType === 'online') {
                verifyTransaction();
            } else {
                verifyPhysicalPayment();
            }
        }

        function verifyTransaction() {
            const transactionId = document.getElementById('transactionIdInput').value.trim();
            const orderId = currentForm.querySelector('input[name="order_id"]').value;
            const messageDiv = document.getElementById('verificationMessage');

            if (!transactionId) {
                messageDiv.innerHTML = 'Please enter a transaction ID';
                messageDiv.style.color = '#dc3545';
                messageDiv.style.display = 'block';
                return;
            }

            messageDiv.innerHTML = 'Verifying transaction...';
            messageDiv.style.color = '#0d6efd';
            messageDiv.style.display = 'block';

            // Send verification request to the server
            fetch('verify_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    orderId: orderId,
                    transactionId: transactionId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        messageDiv.innerHTML = 'Transaction verified successfully!';
                        messageDiv.style.color = '#198754';
                        messageDiv.style.display = 'block';

                        // Update the status dropdown and hidden input
                        if (currentSelectedStatus) {
                            currentSelectedStatus.value = 'completed';
                        }
                        currentForm.querySelector('input[name="status"]').value = 'completed';

                        // Wait a moment to show the success message before submitting
                        setTimeout(() => {
                            currentForm.submit();
                            closeModal('transactionModal');
                        }, 1000);
                    } else {
                        // Show error message
                        messageDiv.innerHTML = data.message || 'Invalid transaction ID';
                        messageDiv.style.color = '#dc3545';
                        messageDiv.style.display = 'block';
                        // Reset the status dropdown
                        if (currentSelectedStatus) {
                            currentSelectedStatus.value = currentSelectedStatus.getAttribute('data-previous-value') || 'ready';
                        }
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = 'An error occurred during verification';
                    messageDiv.style.color = '#dc3545';
                    messageDiv.style.display = 'block';
                    console.error('Error:', error);
                    // Reset the status dropdown
                    if (currentSelectedStatus) {
                        currentSelectedStatus.value = currentSelectedStatus.getAttribute('data-previous-value') || 'ready';
                    }
                });
        }

        function verifyPhysicalPayment() {
            const amount = parseFloat(document.getElementById('physicalAmountInput').value);
            const method = document.getElementById('physicalMethodInput').value;
            const reference = document.getElementById('physicalReferenceInput').value;
            const orderId = currentForm.querySelector('input[name="order_id"]').value;
            const messageDiv = document.getElementById('verificationMessage');

            if (!amount || amount <= 0) {
                messageDiv.innerHTML = 'Please enter a valid payment amount';
                messageDiv.style.color = '#dc3545';
                messageDiv.style.display = 'block';
                return;
            }

            if (!reference) {
                messageDiv.innerHTML = 'Please enter a receipt/reference number';
                messageDiv.style.color = '#dc3545';
                messageDiv.style.display = 'block';
                return;
            }

            messageDiv.innerHTML = 'Verifying payment...';
            messageDiv.style.color = '#0d6efd';
            messageDiv.style.display = 'block';

            // Send verification request to the server
            fetch('verify_physical_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    orderId: orderId,
                    amount: amount,
                    method: method,
                    reference: reference
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        messageDiv.innerHTML = 'Payment verified successfully!';
                        messageDiv.style.color = '#198754';
                        messageDiv.style.display = 'block';

                        // Update the status dropdown and hidden input
                        if (currentSelectedStatus) {
                            currentSelectedStatus.value = 'completed';
                        }
                        currentForm.querySelector('input[name="status"]').value = 'completed';

                        // Wait a moment to show the success message before submitting
                        setTimeout(() => {
                            currentForm.submit();
                            closeModal('transactionModal');
                        }, 1000);
                    } else {
                        messageDiv.innerHTML = data.message || 'Error processing physical payment';
                        messageDiv.style.color = '#dc3545';
                        messageDiv.style.display = 'block';
                        // Reset the status dropdown
                        if (currentSelectedStatus) {
                            currentSelectedStatus.value = currentSelectedStatus.getAttribute('data-previous-value') || 'ready';
                        }
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = 'An error occurred during verification';
                    messageDiv.style.color = '#dc3545';
                    messageDiv.style.display = 'block';
                    console.error('Error:', error);
                    // Reset the status dropdown
                    if (currentSelectedStatus) {
                        currentSelectedStatus.value = currentSelectedStatus.getAttribute('data-previous-value') || 'ready';
                    }
                });
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        }

        // Store the previous value when the dropdown is clicked
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', function () {
                this.setAttribute('data-previous-value', this.value);
            });
        });

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }

        // Close modal on escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        // Add click handlers to table rows after DOM is loaded
        document.addEventListener('DOMContentLoaded', function () {
            const rows = document.querySelectorAll('.order-row');
            rows.forEach(row => {
                row.addEventListener('click', function () {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    const userPhone = this.getAttribute('data-user-phone');
                    if (userId && userName) {
                        showCustomerDetails(userId, userName, userPhone);
                    }
                });
            });
        });

        function showCustomerDetails(userId, userName, userPhone) {
            document.getElementById('modalCustomerName').textContent = userName || 'Unknown User';
            document.getElementById('modalCustomerPhone').textContent = userPhone || 'Not provided';

            const modal = document.getElementById('customerModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }
    </script>
</body>

</html>