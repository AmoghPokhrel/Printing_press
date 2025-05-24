<?php
session_start();
$pageTitle = 'Template Finishing';

include '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/SubscriptionManager.php';
require_once '../includes/subscription_popup.php';

// Initialize SubscriptionManager
$subscriptionManager = new SubscriptionManager($pdo, $_SESSION['user_id']);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check if user can modify template
if (!$subscriptionManager->canModifyTemplate()) {
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
    $query = "SELECT tm.*, t.name as template_name, t.image_path as template_image, c.c_Name as category_name, 
              m.name as media_type_name, u.name as customer_name, tm.final_design, tm.satisfaction_status
              FROM template_modifications tm
              JOIN templates t ON tm.template_id = t.id
              JOIN category c ON tm.category_id = c.c_id
              JOIN media_type m ON tm.media_type_id = m.id
              JOIN users u ON tm.user_id = u.id
              WHERE tm.staff_id = (SELECT id FROM staff WHERE user_id = ?)
              ORDER BY tm.status_updated_at DESC, tm.created_at DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);

// Debug output to check satisfaction status
error_log("Requests data: " . print_r($requests, true));

// Fetch modification_reason for each request (for non-customer roles)
foreach ($requests as &$request) {
    $category_id = $request['category_id'];
    $mod_id = $request['id']; // template_modifications.id
    $table_name = "additional_info_" . intval($category_id);

    // Check if the table exists
    $check = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("SELECT modification_reason FROM $table_name WHERE template_modification_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $mod_id);
            $stmt->execute();
            $stmt->bind_result($modification_reason);
            if ($stmt->fetch()) {
                $request['modification_reason'] = $modification_reason;
            } else {
                $request['modification_reason'] = '';
            }
            $stmt->close();
        } else {
            error_log("Prepare failed for $table_name: " . $conn->error);
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
    <style>
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
            background-color: #2ecc71;
            color: white;
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
            z-index: 1000;
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
            padding: 20px;
            width: 50%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #e74c3c;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            background: #f8f9fa;
            cursor: pointer;
        }

        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
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
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-modal-submit {
            background-color: #2ecc71;
            color: white;
        }

        .btn-modal-submit:hover {
            background-color: #27ae60;
        }

        .btn-modal-cancel {
            background-color: #e74c3c;
            color: white;
            margin-right: 10px;
        }

        .btn-modal-cancel:hover {
            background-color: #c0392b;
        }

        .feedback-modal {
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

        .feedback-modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
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
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <h2><?php echo $pageTitle; ?></h2>

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
                                                                        class="template-image clickable-image">
                                                        <?php else: ?>
                                                                    <img src="https://via.placeholder.com/200x200?text=No+Image" alt="No template image"
                                                                        class="template-image clickable-image">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if (!empty($request['final_design'])): ?>
                                                            <div>
                                                                <div class="image-label">Final Design</div>
                                                                <div class="final-design-container">
                                                                    <img src="../uploads/template_designs/<?php echo htmlspecialchars($request['final_design']); ?>"
                                                                        alt="Final design for <?php echo htmlspecialchars($request['template_name']); ?>"
                                                                        class="final-design-image clickable-image">
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
                                                                    <option value="Completed" <?php echo $request['status'] === 'Completed' ? 'selected' : ''; ?>>
                                                                        Completed</option>
                                                                    <option value="Rejected" <?php echo $request['status'] === 'Rejected' ? 'selected' : ''; ?>>
                                                                        Rejected</option>
                                                                </select>
                                                                <?php if ($request['status'] === 'Completed'): ?>
                                                                            <button class="btn btn-upload" onclick="showUploadModal(<?php echo $request['id']; ?>)">
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
                                                                    <?php if ($user_role === 'Customer'): ?>
                                                                                <div class="satisfaction-buttons">
                                                                                    <button class="btn-satisfaction btn-satisfied"
                                                                                        onclick="updateSatisfaction(<?php echo $request['id']; ?>, 'Satisfied')" <?php echo $request['satisfaction_status'] === 'Satisfied' ? 'disabled' : ''; ?>>
                                                                                        Satisfied
                                                                                    </button>
                                                                                    <button class="btn-satisfaction btn-not-satisfied"
                                                                                        onclick="updateSatisfaction(<?php echo $request['id']; ?>, 'Not Satisfied')"
                                                                                        <?php echo $request['satisfaction_status'] === 'Not Satisfied' ? 'disabled' : ''; ?>>
                                                                                        Not Satisfied
                                                                                    </button>
                                                                                </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div
                                                                    class="satisfaction-status-text status-<?php echo strtolower($request['satisfaction_status']); ?>">
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

            <!-- Add remaining attempts display -->
            <div class="alert alert-info" role="alert">
                Remaining free template modifications: <?php echo $subscriptionManager->getRemainingModifications(); ?>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUploadModal()">&times;</span>
            <div class="modal-title">Upload Final Design</div>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" id="requestId" name="request_id">
                <div class="form-group">
                    <label for="final_design">Select Design File</label>
                    <input type="file" id="final_design" name="final_design" required accept="image/*">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-cancel"
                        onclick="closeUploadModal()">Cancel</button>
                    <button type="submit" class="btn-modal btn-modal-submit">Upload</button>
                </div>
            </form>
        </div>
    </div>

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
        // Status update
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function () {
                const requestId = this.dataset.requestId;
                const newStatus = this.value;

                fetch('update_template_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=${newStatus}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error updating status');
                        }
                    });
            });
        });

        // Upload modal
        function showUploadModal(requestId) {
            document.getElementById('requestId').value = requestId;
            document.getElementById('uploadModal').style.display = 'block';
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        // Feedback modal
        function showFeedbackModal(requestId) {
            console.log('Opening feedback modal for request:', requestId);
            const modal = document.getElementById('feedbackModal');
            const feedbackContent = document.getElementById('feedbackContent');
            const feedbackRequestId = document.getElementById('feedbackRequestId');

            // Set the request ID
            feedbackRequestId.value = requestId;

            // Show loading state
            feedbackContent.textContent = 'Loading feedback...';
            modal.style.display = 'block';

            // Load existing feedback
            fetch(`get_template_feedback.php?request_id=${requestId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Feedback data:', data);
                    if (data.success && data.feedback) {
                        feedbackContent.textContent = data.feedback;
                        // Also set the textarea value if it exists
                        const feedbackTextarea = document.getElementById('feedback');
                        if (feedbackTextarea) {
                            feedbackTextarea.value = data.feedback;
                        }
                    } else {
                        feedbackContent.textContent = 'No feedback available yet';
                    }
                })
                .catch(error => {
                    console.error('Error loading feedback:', error);
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

        // Handle form submissions
        document.getElementById('uploadForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('upload_template_design.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error uploading design');
                    }
                });
        });

        document.getElementById('feedbackForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('submit_template_feedback.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Feedback submitted successfully');
                        closeFeedbackModal();
                        location.reload();
                    } else {
                        alert('Error submitting feedback: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting feedback');
                });
        });

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
                modal.style.display = "none";
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                modal.style.display = "none";
            }
        });

        function updateSatisfaction(requestId, status) {
            if (!confirm('Are you sure you want to mark this design as ' + status + '?')) {
                return;
            }

            fetch('update_satisfaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `request_id=${requestId}&satisfaction_status=${status}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating satisfaction status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating satisfaction status');
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