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
          GROUP_CONCAT(oi.template_name SEPARATOR ', ') as item_names, 
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
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 10% auto;
            padding: 30px;
            width: 60%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close-modal:hover {
            color: #333;
        }

        .order-details {
            margin-top: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            width: 150px;
            font-weight: bold;
            color: #555;
        }

        .detail-value {
            flex: 1;
            color: #333;
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 90%;
                margin: 20% auto;
            }
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
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <h2><?php echo $pageTitle; ?></h2>
            <div class="orders-container">
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
                            <tr class="order-row" data-user-id="<?php echo htmlspecialchars($order['user_id']); ?>"
                                data-user-name="<?php echo htmlspecialchars($order['user_name'] ?? 'Unknown User'); ?>">
                                <td class="order-id">#<?= $order['order_id'] ?></td>
                                <td class="order-date">
                                    <?= htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))) ?>
                                </td>
                                <td><?= htmlspecialchars($order['item_names']) ?></td>
                                <td>
                                    <?php
                                    $statuses = array_unique(explode(',', $order['item_statuses']));
                                    $current_status = count($statuses) === 1 ? trim($statuses[0]) : 'mixed';
                                    ?>
                                    <form method="post" action="update_order_status.php" style="margin:0;">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <select name="status" class="status-dropdown" onchange="this.form.submit()">
                                            <option value="pending" <?= $current_status === 'pending' ? 'selected' : ($current_status === 'mixed' ? '' : '') ?>>Pending</option>
                                            <option value="ready" <?= $current_status === 'ready' ? 'selected' : ($current_status === 'mixed' ? '' : '') ?>>Ready</option>
                                            <option value="completed" <?= $current_status === 'completed' ? 'selected' : ($current_status === 'mixed' ? '' : '') ?>>Completed</option>
                                            <option value="cancelled" <?= $current_status === 'cancelled' ? 'selected' : ($current_status === 'mixed' ? '' : '') ?>>Cancelled</option>
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

    <!-- Add this modal HTML before the closing body tag -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Order Details</h2>
            <div class="order-details">
                <div class="detail-row">
                    <div class="detail-label">User ID:</div>
                    <div class="detail-value" id="modalUserId"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">User Name:</div>
                    <div class="detail-value" id="modalUserName"></div>
                </div>
                <!-- Add more details as needed -->
            </div>
        </div>
    </div>

    <script>
        // Add this to your existing JavaScript
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('orderModal');
            const closeBtn = document.querySelector('.close-modal');
            const orderRows = document.querySelectorAll('.order-row');

            // Function to show modal with order details
            function showOrderDetails(userId, userName) {
                document.getElementById('modalUserId').textContent = userId;
                document.getElementById('modalUserName').textContent = userName;
                modal.style.display = 'block';
            }

            // Add click event to each order row
            orderRows.forEach(row => {
                row.addEventListener('click', function () {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    showOrderDetails(userId, userName);
                });
            });

            // Close modal when clicking the close button
            closeBtn.addEventListener('click', function () {
                modal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>