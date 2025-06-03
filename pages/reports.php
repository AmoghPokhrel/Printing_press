<?php
session_start();
$pageTitle = 'Reports';

// Only allow Super Admin
if ($_SESSION['role'] !== 'Super Admin') {
    header('Location: ../index.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'printing_press';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query: Monthly Orders Count
    $monthlyOrdersQuery = "
        SELECT 
            DATE_FORMAT(order_date, '%Y-%m') as month,
            COUNT(*) as order_count
        FROM `order`
        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ";
    $monthlyOrders = $pdo->query($monthlyOrdersQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Query: Most Ordered Staff Templates (using correct join path)
    $staffTemplatesQuery = "
        SELECT u.name AS staff_name, COUNT(oi.id) AS order_count
        FROM order_item_line oi
        JOIN cart_item_line cil ON oi.ca_it_id = cil.id
        JOIN template_modifications tm ON cil.request_id = tm.id
        JOIN templates t ON tm.template_id = t.id
        JOIN staff s ON t.staff_id = s.id
        JOIN users u ON s.user_id = u.id
        GROUP BY s.id, u.name
        ORDER BY order_count DESC
    ";
    $staffTemplates = $pdo->query($staffTemplatesQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Query: Staff Custom Design Reuploads (sum max revision_number per request, subtract number of requests, grouped by staff name)
    $staffReuploadsQuery = "
        SELECT 
            u.name AS staff_name, 
            SUM(sub.max_rev) - COUNT(*) AS reupload_count
        FROM (
            SELECT 
                ctr.assigned_staff_id AS staff_id, 
                dr.request_id, 
                MAX(dr.revision_number) AS max_rev
            FROM design_revisions dr
            JOIN custom_template_requests ctr ON dr.request_id = ctr.id
            GROUP BY dr.request_id, ctr.assigned_staff_id
            HAVING max_rev > 1
        ) AS sub
        JOIN staff s ON sub.staff_id = s.id
        JOIN users u ON s.user_id = u.id
        GROUP BY u.name
        HAVING reupload_count > 0
        ORDER BY reupload_count DESC
    ";
    $staffReuploads = $pdo->query($staffReuploadsQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Query: Most Preferred Staff (based on preferred_staff_id in custom_template_requests)
    $preferredStaffQuery = "
        SELECT u.name AS staff_name, COUNT(ctr.preferred_staff_id) AS preference_count
        FROM custom_template_requests ctr
        JOIN staff s ON ctr.preferred_staff_id = s.id
        JOIN users u ON s.user_id = u.id
        GROUP BY s.id, u.name
        ORDER BY preference_count DESC
    ";
    $preferredStaff = $pdo->query($preferredStaffQuery)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $monthlyOrders = [];
    $staffTemplates = [];
    $staffReuploads = [];
    $preferredStaff = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        if (typeof Chart === 'undefined') {
            document.write('<script src="../assets/js/chart.umd.min.js"><\/script>');
        }
    </script>
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin: 40px auto;
            max-width: 1200px;
            padding: 0 20px;
        }

        .report-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 24px 20px;
            min-width: 0;
            min-height: 0;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 16px;
            color: #374151;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.01em;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Add responsive styles */
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }

            .chart-title {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <!-- <h2><?php echo $pageTitle; ?></h2> -->
            <div class="reports-grid">
                <div class="report-container">
                    <div class="chart-title">Monthly Orders</div>
                    <canvas id="monthlyOrdersChart" height="90"></canvas>
                </div>
                <div class="report-container">
                    <div class="chart-title">Most Ordered Staff Templates</div>
                    <canvas id="staffTemplatesChart" height="90"></canvas>
                </div>
                <div class="report-container">
                    <div class="chart-title">Most Reuploaded Custom Designs (Staff)</div>
                    <canvas id="staffReuploadsChart" height="90"></canvas>
                </div>
                <div class="report-container">
                    <div class="chart-title">Most Preferred Staff by Customers</div>
                    <canvas id="preferredStaffChart" height="90"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
    <script>
        // Data from PHP
        const months = <?= json_encode(array_column(array_reverse($monthlyOrders), 'month')) ?>;
        const orderCounts = <?= json_encode(array_column(array_reverse($monthlyOrders), 'order_count')) ?>;
        const staffNames = <?= json_encode(array_column($staffTemplates, 'staff_name')) ?>;
        const staffOrderCounts = <?= json_encode(array_column($staffTemplates, 'order_count')) ?>;
        const staffReuploadNames = <?= json_encode(array_column($staffReuploads, 'staff_name')) ?>;
        const staffReuploadCounts = <?= json_encode(array_column($staffReuploads, 'reupload_count')) ?>;
        const preferredStaffNames = <?= json_encode(array_column($preferredStaff, 'staff_name')) ?>;
        const preferredStaffCounts = <?= json_encode(array_column($preferredStaff, 'preference_count')) ?>;

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

        // Chart.js Bar Graph for Staff Custom Design Reuploads
        new Chart(document.getElementById('staffReuploadsChart'), {
            type: 'bar',
            data: {
                labels: staffReuploadNames,
                datasets: [{
                    label: 'Number of Reuploads',
                    data: staffReuploadCounts,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
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

        // Chart.js Bar Graph for Most Preferred Staff
        new Chart(document.getElementById('preferredStaffChart'), {
            type: 'bar',
            data: {
                labels: preferredStaffNames,
                datasets: [{
                    label: 'Number of Preferences',
                    data: preferredStaffCounts,
                    backgroundColor: 'rgba(153, 102, 255, 0.5)',
                    borderColor: 'rgba(153, 102, 255, 1)',
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
</body>

</html>