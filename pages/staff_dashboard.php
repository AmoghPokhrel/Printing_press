<?php
session_start();
require_once('../includes/db.php');
$pageTitle = isset($_GET['page']) ? $_GET['page'] : 'Dashboard';

// Check if user is logged in and is Staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    echo '<script>alert("You need to log in"); window.location.href = "login.php";</script>';
    exit();
}

// Get staff ID from staff table using user_id
$user_id = $_SESSION['user_id'];
$staffQuery = "SELECT id FROM staff WHERE user_id = $user_id";
$staffResult = $conn->query($staffQuery);

if (!$staffResult || $staffResult->num_rows === 0) {
    echo '<script>alert("Staff profile not found"); window.location.href = "login.php";</script>';
    exit();
}

$staff = $staffResult->fetch_assoc();
$staff_id = $staff['id'];

// Get customer count
$customerCount = 0;
$customerQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'Customer'";
$customerResult = $conn->query($customerQuery);
if ($customerResult) {
    $customerCount = $customerResult->fetch_assoc()['count'];
}

// Get template count
$templateCount = 0;
$templateQuery = "SELECT COUNT(*) as count FROM templates";
$templateResult = $conn->query($templateQuery);
if ($templateResult) {
    $templateCount = $templateResult->fetch_assoc()['count'];
}

// Get active tasks count for this staff member
$activeTasksCount = 0;
$tasksQuery = "SELECT COUNT(*) as count 
               FROM custom_template_requests 
               WHERE assigned_staff_id = ? 
               AND status != 'Completed'";
$stmt = $conn->prepare($tasksQuery);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$tasksResult = $stmt->get_result();
if ($tasksResult) {
    $activeTasksCount = $tasksResult->fetch_assoc()['count'];
}
$stmt->close();

// Get recently added templates by this staff member
$recentTemplates = [];
$recentQuery = "SELECT t.*, c.c_Name as category_name 
                FROM templates t
                JOIN category c ON t.c_id = c.c_id
                WHERE t.staff_id = $staff_id
                ORDER BY t.created_at DESC
                LIMIT 3";
$recentResult = $conn->query($recentQuery);
if ($recentResult) {
    $recentTemplates = $recentResult->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
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
            min-height: 200px;
        }

        .card h3 {
            margin-top: 0;
            color: #333;
            font-weight: 400;
            font-family: 'Poppins', sans-serif;
        }

        .card .count {
            font-size: 2.5rem;
            font-weight: 400;
            margin: 10px 0;
        }

        /* Card specific colors */
        .card:nth-child(1) .count {
            color: #3498db;
            /* Blue for customers */
        }

        .card:nth-child(2) .count {
            color: #9b59b6;
            /* Purple for active tasks */
        }

        .card:nth-child(3) .count {
            color: #f39c12;
            /* Orange for templates */
        }

        .card:nth-child(4) .count {
            color: #2ecc71;
            /* Green for coming soon */
        }

        .card a {
            display: inline-block;
            padding: 8px 15px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            transition: background 0.3s;
        }

        /* Card specific button colors */
        .card:nth-child(1) a {
            background: #3498db;
        }

        .card:nth-child(2) a {
            background: #9b59b6;
        }

        .card:nth-child(3) a {
            background: #f39c12;
        }

        .card:nth-child(4) a {
            background: #2ecc71;
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .template-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .template-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }

        .template-info {
            flex: 1;
        }

        .template-name {
            font-weight: 400;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .template-category {
            font-size: 0.8em;
            color: #666;
            font-weight: 400;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="content">
            <div class="card">
                <h3>Total Customers</h3>
                <div class="count"><?php echo $customerCount; ?></div>
                <a>View All Customers</a>
            </div>
            <div class="card">
                <h3>Active Tasks</h3>
                <div class="count"><?php echo $activeTasksCount; ?></div>
                <a href="manage_custom_requests.php">View Requests</a>
            </div>
            <div class="card">
                <h3>Total Templates</h3>
                <div class="count"><?php echo $templateCount; ?></div>
                <a href="intemplates.php">View Templates</a>
            </div>
            <div class="card">
                <h3>Coming Soon</h3>
                <div class="count">-</div>
                <a href="#">Explore</a>
            </div>
            <div class="card extra large">
                <h3>Statistics Overview</h3>
                <p>Additional statistics will be displayed here.</p>
            </div>
            <div class="card large">
                <h3>Recent Templates</h3>
                <?php if (!empty($recentTemplates)): ?>
                    <?php foreach ($recentTemplates as $template): ?>
                        <div class="template-item">
                            <?php if (!empty($template['thumbnail_path'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($template['thumbnail_path']); ?>"
                                    class="template-thumbnail">
                            <?php else: ?>
                                <div class="template-thumbnail" style="background: #eee;"></div>
                            <?php endif; ?>
                            <div class="template-info">
                                <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                                <div class="template-category"><?php echo htmlspecialchars($template['category_name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="your_template.php" class="btn">View All Your Templates</a>
                <?php else: ?>
                    <p>No recent templates</p>
                    <a href="template_setup.php" class="btn">Create New Template</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
</body>

</html>