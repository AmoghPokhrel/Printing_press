<?php
session_start();
require_once('../includes/db.php');
$pageTitle = isset($_GET['page']) ? $_GET['page'] : 'Dashboard';

// Check if user is logged in and is Admin or Super Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Get counts for admin dashboard
$userCount = $staffCount = $customerCount = $templateCount = 0;

// Total Users count
$userQuery = "SELECT COUNT(*) as count FROM users";
$userResult = $conn->query($userQuery);
if ($userResult) {
    $userCount = $userResult->fetch_assoc()['count'];
}

// Staff count
$staffQuery = "SELECT COUNT(*) as count FROM staff";
$staffResult = $conn->query($staffQuery);
if ($staffResult) {
    $staffCount = $staffResult->fetch_assoc()['count'];
}

// Customer count
$customerQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'Customer'";
$customerResult = $conn->query($customerQuery);
if ($customerResult) {
    $customerCount = $customerResult->fetch_assoc()['count'];
}

// Template count
$templateQuery = "SELECT COUNT(*) as count FROM templates";
$templateResult = $conn->query($templateQuery);
if ($templateResult) {
    $templateCount = $templateResult->fetch_assoc()['count'];
}

// Get pending design requests count
$pendingRequestsCount = 0;
$requestsQuery = "SELECT COUNT(*) as count FROM custom_template_requests WHERE status = 'pending'";
$requestsResult = $conn->query($requestsQuery);
if ($requestsResult) {
    $pendingRequestsCount = $requestsResult->fetch_assoc()['count'];
}

// Get last 3 recently registered users
$recentUsers = [];
$recentUsersQuery = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 2";
$recentUsersResult = $conn->query($recentUsersQuery);
if ($recentUsersResult) {
    $recentUsers = $recentUsersResult->fetch_all(MYSQLI_ASSOC);
}

// --- Add bar graph queries for Super Admin ---
$monthlyOrders = $staffTemplates = [];
if ($_SESSION['role'] === 'Super Admin') {
    // Monthly Orders
    $monthlyOrdersQuery = "SELECT DATE_FORMAT(order_date, '%Y-%m') as month, COUNT(*) as order_count FROM `order` GROUP BY DATE_FORMAT(order_date, '%Y-%m') ORDER BY month DESC LIMIT 12";
    $monthlyOrdersResult = $conn->query($monthlyOrdersQuery);
    if ($monthlyOrdersResult) {
        while ($row = $monthlyOrdersResult->fetch_assoc()) {
            $monthlyOrders[] = $row;
        }
    }
    // Most Ordered Staff Templates
    $staffTemplatesQuery = "SELECT u.name AS staff_name, COUNT(oi.id) AS order_count FROM order_item_line oi JOIN cart_item_line cil ON oi.ca_it_id = cil.id JOIN template_modifications tm ON cil.request_id = tm.id JOIN templates t ON tm.template_id = t.id JOIN staff s ON t.staff_id = s.id JOIN users u ON s.user_id = u.id GROUP BY s.id, u.name ORDER BY order_count DESC";
    $staffTemplatesResult = $conn->query($staffTemplatesQuery);
    if ($staffTemplatesResult) {
        while ($row = $staffTemplatesResult->fetch_assoc()) {
            $staffTemplates[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card {
            padding: 20px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card h3 {
            margin-top: 0;
            color: #333;
        }

        .card .count {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .card.users .count {
            color: #3498db;
        }

        .card.staff .count {
            color: #2ecc71;
        }

        .card.customers .count {
            color: #e74c3c;
        }

        .card.templates .count {
            color: #f39c12;
        }

        .card.requests .count {
            color: #9b59b6;
        }

        .card a {
            display: inline-block;
            padding: 8px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            transition: background 0.3s;
        }

        .card.users a {
            background: #3498db;
        }

        .card.staff a {
            background: #2ecc71;
        }

        .card.customers a {
            background: #e74c3c;
        }

        .card.templates a {
            background: #f39c12;
        }

        .card.requests a {
            background: #9b59b6;
        }

        .card a:hover {
            opacity: 0.9;
        }

        .card.large {
            grid-column: span 2;
        }

        .card.extra.large {
            grid-column: span 3;
        }

        .content {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .user-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .user-role {
            font-size: 0.8em;
            color: #666;
        }

        .user-date {
            font-size: 0.8em;
            color: #999;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }

        .btn-primary {
            background: #3498db;
        }

        .btn-success {
            background: #2ecc71;
        }

        .btn-danger {
            background: #e74c3c;
        }

        .btn-warning {
            background: #f39c12;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="content">
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                <div class="card users">
                    <h3>Total Users</h3>
                    <div class="count"><?php echo $userCount; ?></div>
                    <a href="manage_users.php">Manage Users</a>
                </div>
            <?php endif; ?>
            <div class="card staff">
                <h3>Total Staff</h3>
                <div class="count"><?php echo $staffCount; ?></div>
                <a href="staff_setup.php">Manage Staff</a>
            </div>
            <div class="card customers">
                <h3>Total Customers</h3>
                <div class="count"><?php echo $customerCount; ?></div>
                <a href="manage_customers.php">Manage Customers</a>
            </div>
            <div class="card templates">
                <h3>Total Templates</h3>
                <div class="count"><?php echo $templateCount; ?></div>
                <a href="intemplates.php">Manage Templates</a>
            </div>
            <div class="card requests">
                <h3>Pending Custom Requests</h3>
                <div class="count"><?php echo $pendingRequestsCount; ?></div>
                <a href="manage_custom_requests.php">View Requests</a>
            </div>

            <?php if ($_SESSION['role'] !== 'Super Admin'): ?>
                <div class="card large">
                    <h3>Quick Actions</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <a href="c_register.php" class="btn btn-primary">Add New Customer</a>
                        <a href="staff_setup.php" class="btn btn-success">Add Staff Member</a>
                        <a href="admin_setup.php" class="btn btn-warning">Admin Member</a>
                        <a href="cat_setup.php" class="btn btn-danger">Category Setup</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card extra large">
                <h3>System Overview</h3>

                <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 24px; justify-content: center; align-items: flex-start; margin-top: 20px;">
                        <div
                            style="flex: 1 1 320px; min-width: 300px; max-width: 480px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 16px 10px;">
                            <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Monthly Orders</div>
                            <canvas id="monthlyOrdersChart" height="90"></canvas>
                        </div>
                        <div
                            style="flex: 1 1 320px; min-width: 300px; max-width: 480px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 16px 10px;">
                            <div style="font-weight: bold; text-align: center; margin-bottom: 10px;">Most Ordered Staff
                                Templates</div>
                            <canvas id="staffTemplatesChart" height="90"></canvas>
                        </div>
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <a href="reports.php" class="btn btn-primary">See More</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
</body>

</html>

<?php if ($_SESSION['role'] === 'Super Admin'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data from PHP
        const months = <?php echo json_encode(array_column(array_reverse($monthlyOrders), 'month')); ?>;
        const orderCounts = <?php echo json_encode(array_column(array_reverse($monthlyOrders), 'order_count')); ?>;
        const staffNames = <?php echo json_encode(array_column($staffTemplates, 'staff_name')); ?>;
        const staffOrderCounts = <?php echo json_encode(array_column($staffTemplates, 'order_count')); ?>;
        // Chart.js Bar Graph for Monthly Orders
        new Chart(document.getElementById('monthlyOrdersChart'), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Number of Orders',
                    data: orderCounts,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
        // Chart.js Bar Graph for Most Ordered Staff Templates
        new Chart(document.getElementById('staffTemplatesChart'), {
            type: 'bar',
            data: {
                labels: staffNames,
                datasets: [{
                    label: 'Number of Orders',
                    data: staffOrderCounts,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
<?php endif; ?>