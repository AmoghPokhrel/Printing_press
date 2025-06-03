<?php
session_start();
$pageTitle = 'Template Finishing';

include '../includes/db.php';

// Check if user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Customer', 'Staff'])) {
    echo '<script>alert("You need to be logged in to access this page"); window.location.href = "login.php";</script>';
    exit();
}

require_once '../includes/header.php';
require_once '../includes/SubscriptionManager.php';
require_once '../includes/subscription_popup.php';

// Initialize SubscriptionManager
$subscriptionManager = new SubscriptionManager($pdo, $_SESSION['user_id']);

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check if user can modify template (only for customers)
if ($user_role === 'Customer' && !$subscriptionManager->canModifyTemplate()) {
    show_subscription_popup("You have reached your free template modification limit. Upgrade to Premium to make unlimited modifications!");
    exit;
}

// Fetch requests based on user role
if ($user_role === 'Customer') {
    $query = "SELECT tm.*, t.name as template_name, t.image_path as template_image, c.c_Name as category_name, 
              m.name as media_type_name, u.name as staff_name, tm.final_design, tm.satisfaction_status
              FROM template_modifications tm
              JOIN templates t ON tm.template_id = t.id
              JOIN category c ON tm.category_id = c.c_id
              JOIN media_type m ON tm.media_type_id = m.id
              JOIN staff s ON tm.staff_id = s.id
              JOIN users u ON s.user_id = u.id
              WHERE tm.user_id = ?
              ORDER BY tm.status_updated_at DESC, tm.created_at DESC";
} else {
    // Get staff ID first
    $staff_query = "SELECT id FROM staff WHERE user_id = ?";
    $staff_stmt = $pdo->prepare($staff_query);
    $staff_stmt->execute([$user_id]);
    $staff_id = $staff_stmt->fetchColumn();

    if (!$staff_id) {
        die("Error: Staff ID not found");
    }

    $query = "SELECT tm.*, t.name as template_name, t.image_path as template_image, c.c_Name as category_name, 
              m.name as media_type_name, u.name as customer_name, tm.final_design, tm.satisfaction_status
              FROM template_modifications tm
              JOIN templates t ON tm.template_id = t.id
              JOIN category c ON tm.category_id = c.c_id
              JOIN media_type m ON tm.media_type_id = m.id
              JOIN users u ON tm.user_id = u.id
              WHERE tm.staff_id = ?
              ORDER BY tm.status_updated_at DESC, tm.created_at DESC";
}

// Use PDO instead of mysqli
$stmt = $pdo->prepare($query);
if ($user_role === 'Customer') {
    $stmt->execute([$user_id]);
} else {
    $stmt->execute([$staff_id]);
}
$requests = $stmt->fetchAll();

// Debug output to check satisfaction status
error_log("Requests data: " . print_r($requests, true));

// Fetch modification_reason for each request (for non-customer roles)
foreach ($requests as &$request) {
    $category_id = $request['category_id'];
    $mod_id = $request['id']; // template_modifications.id
    $table_name = "additional_info_" . intval($category_id);

    // Check if the table exists
    $check = $pdo->query("SHOW TABLES LIKE '$table_name'");
    if ($check && $check->rowCount() > 0) {
        // Check if modification_reason column exists
        $column_check = $pdo->query("SHOW COLUMNS FROM `$table_name` LIKE 'modification_reason'");
        if ($column_check && $column_check->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT modification_reason FROM $table_name WHERE template_modification_id = ?");
            if ($stmt) {
                $stmt->execute([$mod_id]);
                $result = $stmt->fetch();
                $request['modification_reason'] = $result ? $result['modification_reason'] : '';
            } else {
                error_log("Prepare failed for $table_name: " . $pdo->errorInfo()[2]);
                $request['modification_reason'] = '';
            }
        } else {
            // Column doesn't exist, set empty value
            $request['modification_reason'] = '';
        }
    } else {
        $request['modification_reason'] = '';
    }
}
unset($request);

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 2;
$total_requests = count($requests);
$total_pages = ceil($total_requests / $per_page);
$start = ($page - 1) * $per_page;
$paginated_requests = array_slice($requests, $start, $per_page);

// When form is submitted, increment the counter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Your existing form processing code...

    // If the modification request is successful, increment the counter
    if ($success) {
        $subscriptionManager->incrementTemplateModificationCount();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Essential sidebar collapse styles */
        body.sidebar-collapsed .main-content {
            margin-left: 0;
        }

        .main-content {
            margin-left: 250px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            background: var(--bg-light);
            position: relative;
        }

        .requests-container {
            padding: 10px;
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(600px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .request-card {
            display: flex;
            gap: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
            height: fit-content;
        }

        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .image-container {
            display: flex;
            flex-direction: row;
            gap: 10px;
            width: 250px;
            flex-shrink: 0;
        }

        .template-image-container,
        .final-design-container {
            width: 120px;
            height: 95px;
            overflow: hidden;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .template-image,
        .final-design-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-label {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            padding-left: 6px;
            border-left: 3px solid #3498db;
        }

        .request-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .request-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        .request-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white !important;
        }

        .status-pending {
            background-color: #f39c12;
        }

        .status-in-progress {
            background-color: #3498db;
        }

        .status-completed {
            background-color: #2ecc71;
        }

        .status-rejected {
            background-color: #e74c3c;
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 12px;
        }

        .detail-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .detail-label {
            font-size: 11px;
            color: #7f8c8d;
            font-weight: 500;
        }

        .detail-value {
            font-size: 12px;
            color: #2c3e50;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-update {
            background-color: #3498db;
            color: white;
        }

        .btn-upload {
            position: relative;
            transition: all 0.3s ease;
        }

        .btn-upload:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn-upload .fa-spinner {
            margin-right: 5px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .fa-spin {
            animation: spin 1s linear infinite;
        }

        .btn-feedback {
            background-color: #f39c12;
            color: white;
        }

        .btn-info {
            background-color: #3498db;
            color: white;
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            background-color: white;
            font-size: 12px;
            font-weight: 500;
            color: #2c3e50;
            cursor: pointer;
        }

        .satisfaction-status {
            margin-top: 8px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .satisfaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .satisfaction-title {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
        }

        .satisfaction-buttons {
            display: flex;
            gap: 6px;
        }

        .btn-satisfaction {
            padding: 4px 8px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            font-size: 11px;
            transition: all 0.2s ease;
        }

        .btn-satisfied {
            background-color: #2ecc71;
            color: white;
        }

        .btn-not-satisfied {
            background-color: #e74c3c;
            color: white;
        }

        .satisfaction-status-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 6px;
            padding: 6px;
            border-radius: 4px;
            background: white;
        }

        .status-satisfied {
            color: #2ecc71;
            font-weight: 500;
        }

        .status-not-satisfied {
            color: #e74c3c;
            font-weight: 500;
        }

        .status-pending {
            color: #f39c12;
            font-weight: 500;
        }

        .image-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(5px);
        }

        .image-modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            animation: zoom 0.3s ease;
        }

        .image-modal-caption {
            margin: 8px auto;
            text-align: center;
            color: white;
            font-size: 14px;
            font-weight: 500;
        }

        .image-modal-close {
            position: fixed;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1002;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .image-modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        @keyframes zoom {
            from {
                transform: scale(0.1)
            }

            to {
                transform: scale(1)
            }
        }

        .clickable-image {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .clickable-image:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .requests-container {
                grid-template-columns: 1fr;
            }

            .request-card {
                flex-direction: column;
            }

            .image-container {
                width: 100%;
                justify-content: center;
            }

            .template-image-container,
            .final-design-container {
                width: 120px;
                height: 120px;
            }
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 10000;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #7f8c8d;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10001;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f9fa;
        }

        .close:hover {
            color: #e74c3c;
            background-color: #fee2e2;
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-group input[type="file"]:hover {
            border-color: #3498db;
            background-color: #f0f9ff;
        }

        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-modal-submit {
            background-color: #2ecc71;
            color: white;
        }

        .btn-modal-submit:hover {
            background-color: #27ae60;
            transform: translateY(-1px);
        }

        .btn-modal-cancel {
            background-color: #e74c3c;
            color: white;
        }

        .btn-modal-cancel:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }

        .feedback-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .feedback-modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1051;
        }

        .feedback-content {
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }

        .feedback-text {
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.5;
            color: #2c3e50;
        }

        .feedback-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .feedback-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
        }

        .feedback-form textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        /* Modal styles for additional info */
        .info-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .info-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .info-modal .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
        }

        .info-modal .close:hover {
            color: #333;
        }

        .info-section {
            margin-top: 10px;
        }

        .info-item {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            margin-right: 8px;
        }

        .info-value {
            color: #333;
        }

        .no-info {
            color: #888;
            font-style: italic;
            margin-top: 10px;
        }

        .view-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background-color 0.2s;
            margin-left: 8px;
        }

        .view-btn:hover {
            background-color: #0056b3;
        }

        /* Upload modal specific styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-title {
            margin-bottom: 20px;
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }

        .btn-modal {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-left: 10px;
        }

        .btn-modal-submit {
            background-color: #4CAF50;
            color: white;
        }

        .btn-modal-submit:hover {
            background-color: #45a049;
        }

        .btn-modal-cancel {
            background-color: #f44336;
            color: white;
        }

        .btn-modal-cancel:hover {
            background-color: #da190b;
        }

        .btn-modal:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            text-align: center;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .fade-out {
            opacity: 0;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status-updating {
            color: #666;
            font-style: italic;
        }

        .fade-in {
            animation: fadeIn 0.5s;
        }

        .fade-out {
            opacity: 0;
            transition: opacity 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
            transition: opacity 0.5s;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hamburger = document.querySelector('.hamburger-icon');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const body = document.body;

            // Get the stored sidebar state
            let isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            // Initialize sidebar state
            if (isSidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                hamburger.classList.add('active');
                body.classList.add('sidebar-collapsed');
            }

            hamburger.addEventListener('click', function () {
                isSidebarCollapsed = !isSidebarCollapsed;
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                hamburger.classList.toggle('active');
                body.classList.toggle('sidebar-collapsed');

                // Store the sidebar state
                localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
            });
        });
    </script>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">


            <div class="requests-container">
                <?php if (empty($paginated_requests)): ?>
                    <p>No requests found.</p>
                <?php else: ?>
                    <?php foreach ($paginated_requests as $request): ?>
                        <div class="request-card">
                            <div class="image-container">
                                <div>
                                    <div class="image-label">Original Template</div>
                                    <div class="template-image-container">
                                        <?php if (!empty($request['template_image'])): ?>
                                            <img src="../uploads/templates/<?php echo htmlspecialchars($request['template_image']); ?>"
                                                alt="<?php echo htmlspecialchars($request['template_name']); ?>"
                                                class="template-image clickable-image"
                                                onclick="openImageModal('../uploads/templates/<?php echo htmlspecialchars($request['template_image']); ?>', '<?php echo htmlspecialchars($request['template_name']); ?>')">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/200x200?text=No+Image" alt="No template image"
                                                class="template-image">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($request['final_design'])): ?>
                                    <div>
                                        <div class="image-label">Final Design</div>
                                        <div class="final-design-container">
                                            <img src="../uploads/template_designs/<?php echo htmlspecialchars($request['final_design']); ?>"
                                                alt="Final design for <?php echo htmlspecialchars($request['template_name']); ?>"
                                                class="final-design-image clickable-image"
                                                onclick="openImageModal('../uploads/template_designs/<?php echo htmlspecialchars($request['final_design']); ?>', 'Final design for <?php echo htmlspecialchars($request['template_name']); ?>')">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="request-content">
                                <div class="request-header">
                                    <div class="request-title">
                                        <?php echo htmlspecialchars($request['template_name']); ?>
                                    </div>
                                    <div
                                        class="request-status status-<?php echo str_replace(' ', '-', strtolower(trim($request['status']))); ?>">
                                        <?php echo htmlspecialchars($request['status']); ?>
                                    </div>
                                </div>

                                <div class="request-details">
                                    <div class="detail-row">
                                        <div class="detail-label">Category:</div>
                                        <div><?php echo htmlspecialchars($request['category_name']); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Media Type:</div>
                                        <div><?php echo htmlspecialchars($request['media_type_name']); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Size:</div>
                                        <div><?php echo htmlspecialchars($request['size']); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Orientation:</div>
                                        <div><?php echo htmlspecialchars($request['orientation']); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Color Scheme:</div>
                                        <div><?php echo htmlspecialchars($request['color_scheme']); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Quantity:</div>
                                        <div><?php echo htmlspecialchars($request['quantity']); ?></div>
                                    </div>
                                    <?php if ($user_role !== 'Customer'): ?>
                                        <div class="detail-row">
                                            <div class="detail-label">Customer:</div>
                                            <div><?php echo htmlspecialchars($request['customer_name']); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="detail-row">
                                            <div class="detail-label">Designer:</div>
                                            <div><?php echo htmlspecialchars($request['staff_name']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['additional_notes'])): ?>
                                        <div class="detail-row">
                                            <div class="detail-label">Additional Notes:</div>
                                            <div><?php echo htmlspecialchars($request['additional_notes']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($user_role !== 'Customer' && !empty($request['modification_reason'])): ?>
                                        <div class="detail-row">
                                            <div class="detail-label">Modification Reason:</div>
                                            <div><?php echo htmlspecialchars($request['modification_reason']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="detail-row">
                                        <div class="detail-label">Preferred Color:</div>
                                        <div>
                                            <?php if (!empty($request['preferred_color'])): ?>
                                                <span
                                                    style="display:inline-block;width:22px;height:22px;border-radius:4px;border:1px solid #ccc;vertical-align:middle;background:<?php echo htmlspecialchars($request['preferred_color']); ?>;margin-right:6px;"></span>
                                                <?php echo htmlspecialchars($request['preferred_color']); ?>
                                            <?php else: ?>
                                                <span style="color:#aaa;">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Secondary Color:</div>
                                        <div>
                                            <?php if (!empty($request['secondary_color'])): ?>
                                                <span
                                                    style="display:inline-block;width:22px;height:22px;border-radius:4px;border:1px solid #ccc;vertical-align:middle;background:<?php echo htmlspecialchars($request['secondary_color']); ?>;margin-right:6px;"></span>
                                                <?php echo htmlspecialchars($request['secondary_color']); ?>
                                            <?php else: ?>
                                                <span style="color:#aaa;">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <?php if ($user_role !== 'Customer'): ?>
                                        <select class="status-select" data-request-id="<?php echo $request['id']; ?>">
                                            <option value="Pending" <?php echo $request['status'] === 'Pending' ? 'selected' : ''; ?>>
                                                Pending</option>
                                            <option value="In Progress" <?php echo $request['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Completed" <?php echo $request['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Rejected" <?php echo $request['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <?php if ($request['status'] === 'Completed'): ?>
                                            <button class="btn btn-upload"
                                                onclick="initializeDirectUpload(<?php echo $request['id']; ?>)">
                                                Upload Final Design
                                            </button>
                                        <?php endif; ?>
                                        <button class="view-btn"
                                            onclick="showInfoModal(<?php echo $request['id']; ?>, <?php echo $request['category_id']; ?>)"><i
                                                class="fas fa-eye"></i> View Details</button>
                                    <?php endif; ?>
                                    <button class="btn btn-feedback" onclick="showFeedbackModal(<?php echo $request['id']; ?>)">
                                        View Feedback
                                    </button>
                                    <?php if ($user_role === 'Customer' && $request['satisfaction_status'] === 'Satisfied'): ?>
                                        <form action="/printing_press/pages/add_to_cart.php" method="POST" style="display: inline;"
                                            class="add-to-cart-form">
                                            <input type="hidden" name="template_id" value="<?php echo $request['template_id']; ?>">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="unique_id" value="<?php echo uniqid('template_', true); ?>">
                                            <input type="hidden" name="quantity" value="<?php echo $request['quantity']; ?>">
                                            <input type="hidden" name="redirect_to" value="template_finishing.php">
                                            <button type="submit" name="add_to_cart" class="btn btn-success btn-sm">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <?php if ($request['status'] === 'Completed'): ?>
                                    <div class="satisfaction-status">
                                        <div class="satisfaction-header">
                                            <div class="satisfaction-title">Satisfaction Status</div>
                                            <?php if ($user_role === 'Customer' && $request['satisfaction_status'] !== 'Satisfied'): ?>
                                                <div class="satisfaction-buttons">
                                                    <button class="btn-satisfaction btn-satisfied"
                                                        onclick="updateSatisfactionAndStatus(<?php echo $request['id']; ?>, 'Satisfied')"
                                                        data-request-id="<?php echo $request['id']; ?>"
                                                        <?php echo $request['satisfaction_status'] === 'Satisfied' ? 'disabled' : ''; ?>>
                                                        Satisfied
                                                    </button>
                                                    <button class="btn-satisfaction btn-not-satisfied"
                                                        onclick="updateSatisfactionAndStatus(<?php echo $request['id']; ?>, 'Not Satisfied')"
                                                        data-request-id="<?php echo $request['id']; ?>"
                                                        <?php echo $request['satisfaction_status'] === 'Not Satisfied' ? 'disabled' : ''; ?>>
                                                        Not Satisfied
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="satisfaction-status-text status-<?php echo strtolower($request['satisfaction_status']); ?>">
                                            Current Status: <?php echo htmlspecialchars($request['satisfaction_status']); ?>
                                            <?php if ($user_role !== 'Customer'): ?>
                                                <br>
                                                <small>Customer: <?php echo htmlspecialchars($request['customer_name']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($total_pages > 1): ?>
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

            <!-- Add remaining attempts display only for customers -->
            <?php if ($user_role === 'Customer'): ?>
                <div class="alert alert-info" role="alert" id="remainingModifications">
                Remaining free template modifications: <?php echo $subscriptionManager->getRemainingModifications(); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add hidden file input for direct upload -->
    <form id="directUploadForm" style="display: none;">
        <input type="file" id="directFileInput" name="final_design" accept="image/*">
        <input type="hidden" id="directRequestId" name="request_id">
    </form>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="feedback-modal" style="display: none;">
        <div class="feedback-modal-content">
            <span class="close" onclick="closeFeedbackModal()">&times;</span>
            <div class="modal-title">Feedback</div>
            <div class="feedback-content">
                <div id="feedbackContent" class="feedback-text">Loading feedback...</div>
            </div>
            <?php if ($user_role === 'Customer'): ?>
                <form id="feedbackForm" class="feedback-form">
                    <input type="hidden" id="feedbackRequestId" name="request_id">
                    <div class="form-group">
                        <label for="feedback">Your Feedback</label>
                        <textarea id="feedback" name="feedback" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-modal-cancel"
                            onclick="closeFeedbackModal()">Cancel</button>
                        <button type="submit" class="btn-modal btn-modal-submit">Submit Feedback</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="closeFeedbackModal()">Close</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Additional Info Modal -->
    <div id="infoModal" class="info-modal">
        <div class="info-modal-content">
            <span class="close" onclick="closeInfoModal()">&times;</span>
            <div class="modal-title">Additional Information</div>
            <div id="infoSection"></div>
        </div>
    </div>

    <!-- Add this before the closing body tag -->
    <div id="imageModal" class="image-modal">
        <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
        <img class="image-modal-content" id="modalImage">
        <div id="modalCaption" class="image-modal-caption"></div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Auto-hide remaining modifications message after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const remainingMods = document.getElementById('remainingModifications');
            if (remainingMods) {
                setTimeout(() => {
                    remainingMods.classList.add('fade-out');
                    setTimeout(() => {
                        remainingMods.style.display = 'none';
                    }, 500); // Wait for fade animation to complete
                }, 3000);
            }
        });

        // Status update
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function () {
                const requestId = this.dataset.requestId;
                const newStatus = this.value;
                const requestCard = this.closest('.request-card');
                const actionButtons = this.parentElement;
                const statusDisplay = requestCard.querySelector('.request-status');
                const uploadButton = actionButtons.querySelector('.btn-upload');

                // Store the current value as previous value
                const previousStatus = this.getAttribute('data-previous-value') || this.value;
                this.setAttribute('data-previous-value', previousStatus);

                // Disable the select while updating
                this.disabled = true;

                // Show loading state
                statusDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                statusDisplay.className = 'request-status status-updating';

                // Add loading overlay
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'loading-overlay';
                loadingOverlay.innerHTML = '<div class="spinner"></div>';
                requestCard.appendChild(loadingOverlay);

                // Set timeout for the request
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

                fetch('update_template_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=${newStatus}`,
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) {
                        return response.text().then(text => {
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || 'Failed to update status');
                            } catch (e) {
                                throw new Error('Server error: ' + text);
                            }
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update status display immediately
                        statusDisplay.className = `request-status status-${newStatus.toLowerCase().replace(' ', '-')}`;
                        statusDisplay.textContent = newStatus;

                        // Handle upload button visibility
                        if (newStatus === 'Completed') {
                            if (!uploadButton) {
                                const newUploadButton = document.createElement('button');
                                newUploadButton.className = 'btn btn-upload';
                                newUploadButton.onclick = () => initializeDirectUpload(requestId);
                                newUploadButton.textContent = 'Upload Final Design';
                                this.insertAdjacentElement('afterend', newUploadButton);
                            }

                            // Add satisfaction status section if it doesn't exist
                            if (!requestCard.querySelector('.satisfaction-status')) {
                                const satisfactionSection = document.createElement('div');
                                satisfactionSection.className = 'satisfaction-status';
                                satisfactionSection.innerHTML = `
                                    <div class="satisfaction-header">
                                        <div class="satisfaction-title">Satisfaction Status</div>
                                    </div>
                                    <div class="satisfaction-status-text status-pending">
                                        Current Status: Not Rated
                                    </div>
                                `;
                                requestCard.querySelector('.request-content').appendChild(satisfactionSection);
                            }
                        } else {
                            // Remove upload button if status is not Completed
                            if (uploadButton) {
                                uploadButton.remove();
                            }
                            // Remove satisfaction status section if status is not Completed
                            const satisfactionSection = requestCard.querySelector('.satisfaction-status');
                            if (satisfactionSection) {
                                satisfactionSection.remove();
                            }
                        }

                        // Show success message
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success fade-in';
                        successAlert.textContent = `Status updated to ${newStatus}`;
                        requestCard.insertBefore(successAlert, requestCard.firstChild);

                        // Remove success message after 3 seconds
                        setTimeout(() => {
                            successAlert.classList.add('fade-out');
                            setTimeout(() => successAlert.remove(), 500);
                        }, 3000);
                    } else {
                        throw new Error(data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert select value
                    this.value = previousStatus;
                    statusDisplay.className = `request-status status-${previousStatus.toLowerCase().replace(' ', '-')}`;
                    statusDisplay.textContent = previousStatus;

                    // Show error message
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger fade-in';
                    errorAlert.textContent = error.name === 'AbortError' 
                        ? 'Update timed out. Please try again.' 
                        : (error.message || 'Error updating status. Please try again.');
                    requestCard.insertBefore(errorAlert, requestCard.firstChild);

                    // Remove error message after 3 seconds
                    setTimeout(() => {
                        errorAlert.classList.add('fade-out');
                        setTimeout(() => errorAlert.remove(), 500);
                    }, 3000);
                })
                .finally(() => {
                    // Re-enable the select
                    this.disabled = false;
                    // Remove loading overlay
                    const overlay = requestCard.querySelector('.loading-overlay');
                    if (overlay) {
                        overlay.remove();
                    }
                });
            });
        });

        // Direct upload functionality
        function initializeDirectUpload(requestId) {
            const fileInput = document.getElementById('directFileInput');
            document.getElementById('directRequestId').value = requestId;
            fileInput.click();
        }

        // Handle file selection and automatic upload
        document.getElementById('directFileInput').addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const formData = new FormData(document.getElementById('directUploadForm'));

                // Show loading state on the button that triggered the upload
                const buttons = document.querySelectorAll('.btn-upload');
                buttons.forEach(button => {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                });

                // Create alert div for messages
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert';
                alertDiv.style.display = 'none';
                document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.requests-container'));

                // Set timeout for the fetch request
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

                fetch('upload_template_design.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                    .then(response => {
                        clearTimeout(timeoutId);
                        if (!response.ok) {
                            return response.text().then(text => {
                                try {
                                    // Try to parse as JSON first
                                    const json = JSON.parse(text);
                                    throw new Error(json.message || 'Upload failed');
                                } catch (e) {
                                    // If not JSON, throw the text content
                                    throw new Error('Server error: ' + text);
                                }
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            alertDiv.className = 'alert alert-success';
                            alertDiv.textContent = 'Design uploaded successfully!';
                            alertDiv.style.display = 'block';
                            
                            // Hide the upload button after successful upload
                            buttons.forEach(button => {
                                button.style.display = 'none';
                            });

                            // Reload the page after a short delay
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            throw new Error(data.message || 'Upload failed');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Show error message
                        alertDiv.className = 'alert alert-danger';
                        if (error.name === 'AbortError') {
                            alertDiv.textContent = 'Upload timed out. Please try again.';
                        } else {
                            alertDiv.textContent = error.message || 'Error uploading design. Please try again.';
                        }
                        alertDiv.style.display = 'block';
                        
                        // Reset buttons
                        buttons.forEach(button => {
                            button.disabled = false;
                            button.innerHTML = 'Upload Final Design';
                        });

                        // Hide error message after 3 seconds
                        setTimeout(() => {
                            alertDiv.style.opacity = '0';
                            setTimeout(() => alertDiv.remove(), 500);
                        }, 3000);
                    });
            }
        });

        // Feedback modal
        function showFeedbackModal(requestId) {
            const modal = document.getElementById('feedbackModal');
            const feedbackContent = document.getElementById('feedbackContent');
            const feedbackRequestId = document.getElementById('feedbackRequestId');

            // Set the request ID
            if (feedbackRequestId) {
                feedbackRequestId.value = requestId;
            }

            // Show loading state
            feedbackContent.textContent = 'Loading feedback...';
            modal.style.display = 'block';

            // Load existing feedback
            fetch(`get_template_feedback.php?request_id=${requestId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        feedbackContent.textContent = data.feedback || 'No feedback available yet';
                        // Also set the textarea value if it exists
                        const feedbackTextarea = document.getElementById('feedback');
                        if (feedbackTextarea && data.feedback) {
                            feedbackTextarea.value = data.feedback;
                        }
                    } else {
                        feedbackContent.textContent = 'Error: ' + (data.message || 'Failed to load feedback');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    feedbackContent.textContent = 'Error loading feedback. Please try again.';
                });
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').style.display = 'none';
            const form = document.getElementById('feedbackForm');
            if (form) {
                form.reset();
            }
        }

        // Get the modal
        var modal = document.getElementById("imageModal");
        var modalImg = document.getElementById("modalImage");
        var captionText = document.getElementById("modalCaption");
        var closeBtn = document.getElementsByClassName("image-modal-close")[0];

        // Add click event to all images
        document.querySelectorAll('.template-image, .final-design-image').forEach(img => {
            img.classList.add('clickable-image');
            img.onclick = function () {
                openImageModal(this.src, this.alt);
            }
        });

        // Close modal when clicking the close button
        closeBtn.onclick = function () {
            modal.style.display = "none";
        }

        // Close modal when clicking outside the image
        window.onclick = function (event) {
            if (event.target == modal) {
                closeImageModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeImageModal();
                closeFeedbackModal();
            }
        });

        function updateSatisfactionAndStatus(requestId, status) {
            if (!confirm('Are you sure you want to mark this design as ' + status + '?')) {
                return;
            }

            // Find the request card using the button that was clicked
            const clickedButton = event.target;
            const requestCard = clickedButton.closest('.request-card');
            const satisfactionButtons = requestCard.querySelectorAll('.btn-satisfaction');
            const satisfactionStatus = requestCard.querySelector('.satisfaction-status-text');

            // Debug logs
            console.log('Request ID:', requestId);
            console.log('Status:', status);
            console.log('Request Card:', requestCard);

            // Disable buttons and show loading state
            satisfactionButtons.forEach(button => {
                button.disabled = true;
            });
            satisfactionStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            // Add loading overlay
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = '<div class="spinner"></div>';
            requestCard.appendChild(loadingOverlay);

            // Set timeout for the request
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

            // Debug log the request body
            const requestBody = `request_id=${requestId}&satisfaction_status=${status}`;
            console.log('Request Body:', requestBody);

            fetch('update_satisfaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: requestBody,
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                console.log('Response status:', response.status);
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Parsed response:', data);
                if (data.success) {
                    // Show success message
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success fade-in';
                    successAlert.textContent = `Satisfaction status updated to ${status}`;
                    requestCard.insertBefore(successAlert, requestCard.firstChild);

                    // Update the satisfaction status text immediately
                    satisfactionStatus.className = `satisfaction-status-text status-${status.toLowerCase().replace(' ', '-')}`;
                    satisfactionStatus.textContent = `Current Status: ${status}`;

                    // Remove success message after 3 seconds
                    setTimeout(() => {
                        successAlert.classList.add('fade-out');
                        setTimeout(() => successAlert.remove(), 500);
                    }, 3000);

                    // If marked as Not Satisfied, update the status to In Progress
                    if (status === 'Not Satisfied') {
                        // Update the status display
                        const statusDisplay = requestCard.querySelector('.request-status');
                        if (statusDisplay) {
                            statusDisplay.className = 'request-status status-in-progress';
                            statusDisplay.textContent = 'In Progress';
                        }

                        // Update the status select if it exists
                        const statusSelect = requestCard.querySelector('.status-select');
                        if (statusSelect) {
                            statusSelect.value = 'In Progress';
                        }
                    }

                    // Reload the page after a short delay to show updated state
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Failed to update satisfaction status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Show error message
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger fade-in';
                errorAlert.textContent = error.name === 'AbortError' 
                    ? 'Update timed out. Please try again.' 
                    : (error.message || 'Error updating satisfaction status. Please try again.');
                requestCard.insertBefore(errorAlert, requestCard.firstChild);

                // Remove error message after 3 seconds
                setTimeout(() => {
                    errorAlert.classList.add('fade-out');
                    setTimeout(() => errorAlert.remove(), 500);
                }, 3000);

                // Re-enable buttons
                satisfactionButtons.forEach(button => {
                    button.disabled = false;
                });

                // Reset satisfaction status text
                satisfactionStatus.textContent = 'Current Status: ' + (status === 'Satisfied' ? 'Not Satisfied' : 'Not Rated');
            })
            .finally(() => {
                // Remove loading overlay
                const overlay = requestCard.querySelector('.loading-overlay');
                if (overlay) {
                    overlay.remove();
                }
            });
        }

        function showInfoModal(requestId, categoryId) {
            const modal = document.getElementById('infoModal');
            const infoSection = document.getElementById('infoSection');
            infoSection.innerHTML = 'Loading...';
            modal.style.display = 'block';
            fetch(`get_additional_info.php?request_id=${requestId}&category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.fields && data.fields.length > 0) {
                        let html = '<div class="info-section">';
                        for (const field of data.fields) {
                            html += `<div class="info-item"><span class="info-label">${field.label}</span>: <span class="info-value">${field.value}</span></div>`;
                        }
                        html += '</div>';
                        infoSection.innerHTML = html;
                    } else {
                        infoSection.innerHTML = '<div class="no-info">No additional information available</div>';
                    }
                })
                .catch(() => {
                    infoSection.innerHTML = '<div class="no-info">Error loading additional information.</div>';
                });
        }
        function closeInfoModal() {
            document.getElementById('infoModal').style.display = 'none';
        }
        window.onclick = function (event) {
            const modal = document.getElementById('infoModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Update the image modal functions
        function openImageModal(src, alt) {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImage");
            const captionText = document.getElementById("modalCaption");

            modal.style.display = "block";
            modalImg.src = src;
            captionText.innerHTML = alt;
        }

        function closeImageModal() {
            document.getElementById("imageModal").style.display = "none";
        }

        // Add click event to all images
        document.querySelectorAll('.template-image, .final-design-image').forEach(img => {
            img.classList.add('clickable-image');
            img.onclick = function () {
                openImageModal(this.src, this.alt);
            }
        });

        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeImageModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>

</html>