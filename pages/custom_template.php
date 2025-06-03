<?php
session_start();
require_once('../includes/dbcon.php');
require_once '../includes/SubscriptionManager.php';
require_once '../includes/subscription_popup.php';

$pageTitle = 'Custom Template';

// Add this check at the beginning of the file, after the session start
if (!extension_loaded('gd')) {
    die('GD library is not installed. Please enable the GD extension in your PHP configuration.');
}

// Check if user is logged in and is a Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize SubscriptionManager
$subscriptionManager = new SubscriptionManager($pdo, $_SESSION['user_id']);

// Check if user can request custom design
if (!$subscriptionManager->canRequestCustomDesign()) {
    show_subscription_popup("You have reached your free custom design limit. Upgrade to Premium to create unlimited custom designs!");
    exit;
}

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    try {
        // Debug log all POST data
        error_log("DEBUG: Full POST data: " . print_r($_POST, true));

        // Basic validation
        if (!isset($_POST['request_id']) || !isset($_POST['is_satisfied'])) {
            throw new Exception("Missing required fields");
        }

        // Get and cast values
        $request_id = (int) $_POST['request_id'];
        $is_satisfied = (int) $_POST['is_satisfied'];
        $feedback = trim($_POST['feedback'] ?? '');

        error_log("DEBUG: Processed values - request_id: $request_id, is_satisfied: $is_satisfied, feedback: $feedback");

        // Validate values
        if ($request_id <= 0) {
            throw new Exception("Invalid request ID");
        }

        if (!in_array($is_satisfied, [0, 1], true)) {
            throw new Exception("Invalid satisfaction value");
        }

        if ($is_satisfied === 0 && empty($feedback)) {
            throw new Exception("Feedback is required when not satisfied");
        }

        // Get request details for notification
        $request_stmt = $pdo->prepare("
            SELECT ctr.*, c.c_Name as category_name, u.name as customer_name 
            FROM custom_template_requests ctr
            JOIN category c ON ctr.category_id = c.c_id
            JOIN users u ON ctr.user_id = u.id
            WHERE ctr.id = ?
        ");
        $request_stmt->execute([$request_id]);
        $request_details = $request_stmt->fetch(PDO::FETCH_ASSOC);

        error_log("DEBUG: Request details: " . print_r($request_details, true));

        // Get assigned staff details if any
        $staff_stmt = $pdo->prepare("
            SELECT u.id as user_id, u.name as staff_name 
            FROM staff s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $staff_stmt->execute([$request_details['preferred_staff_id']]);
        $staff_details = $staff_stmt->fetch(PDO::FETCH_ASSOC);

        error_log("DEBUG: Staff details: " . print_r($staff_details, true));

        $pdo->beginTransaction();

        try {
            // First, check if there's an existing revision
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM design_revisions WHERE request_id = ?");
            $check_stmt->execute([$request_id]);
            $exists = $check_stmt->fetchColumn() > 0;

            if ($exists) {
                // Update existing revision
                $stmt = $pdo->prepare("
            UPDATE design_revisions 
                    SET is_satisfied = ?,
                        feedback = ?
                    WHERE request_id = ?
        ");
                $result = $stmt->execute([$is_satisfied, $feedback, $request_id]);
                error_log("DEBUG: Update result: " . ($result ? 'Success' : 'Failed'));
            } else {
                // Insert new revision
                $stmt = $pdo->prepare("
                    INSERT INTO design_revisions 
                    (request_id, revision_number, is_satisfied, feedback, created_at) 
                    VALUES (?, 1, ?, ?, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([$request_id, $is_satisfied, $feedback]);
                error_log("DEBUG: Insert result: " . ($result ? 'Success' : 'Failed'));
            }

            // Update request status
            $status = $is_satisfied === 1 ? 'Completed' : 'In Progress';
            $stmt = $pdo->prepare("UPDATE custom_template_requests SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $request_id]);
            error_log("DEBUG: Status update result: " . ($result ? 'Success' : 'Failed'));

            // Prepare notification content
            $notification_title = $is_satisfied === 1
                ? "Design Approved ✓"
                : "Design Revision Requested";

            $notification_message = $is_satisfied === 1
                ? "Customer {$request_details['customer_name']} has approved the design for {$request_details['category_name']} (Request #$request_id)"
                : "Customer {$request_details['customer_name']} has requested revisions for {$request_details['category_name']} (Request #$request_id). Feedback: {$feedback}";

            error_log("DEBUG: Preparing to send notification - Title: $notification_title, Message: $notification_message");

            // Get all staff members regardless of assignment
            $all_staff_stmt = $pdo->prepare("
                SELECT DISTINCT u.id as user_id, u.name as staff_name
                FROM users u
                WHERE u.role = 'Staff'
            ");
            $all_staff_stmt->execute();
            $all_staff = $all_staff_stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("DEBUG: Found " . count($all_staff) . " staff members to notify");

            // Notify all staff members
            foreach ($all_staff as $staff) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications 
                        (user_id, title, message, type, reference_id, reference_type, created_at, is_read) 
                        VALUES (?, ?, ?, 'custom_design_feedback', ?, 'custom_template', CURRENT_TIMESTAMP, 0)
                    ");

                    $notification_result = $stmt->execute([
                        $staff['user_id'],
                        $notification_title,
                        $notification_message,
                        $request_id
                    ]);

                    error_log("DEBUG: Notification created for staff ID {$staff['user_id']} - Result: " . ($notification_result ? 'Success' : 'Failed'));
                } catch (Exception $e) {
                    error_log("DEBUG: Error creating notification for staff ID {$staff['user_id']}: " . $e->getMessage());
                }
            }

            $pdo->commit();
            error_log("DEBUG: Transaction committed successfully");

            $success_message = $is_satisfied === 1 ?
                "Thank you for your feedback! The staff has been notified." :
                "Feedback submitted. The design will be revised.";

            header("Location: custom_template.php?success_message=" . urlencode($success_message));
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("DEBUG: Database error: " . $e->getMessage());
            throw $e;
        }

    } catch (Exception $e) {
        error_log("DEBUG: General error: " . $e->getMessage());
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch categories
$stmt = $pdo->query("SELECT c_id, c_Name FROM category ORDER BY c_Name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch media types
$stmt = $pdo->query("SELECT id, name FROM media_type ORDER BY name ASC");
$mediaTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff members for customer selection
$stmt = $pdo->query("SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.role = 'Staff' ORDER BY u.name ASC");
$staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get staff ID from URL if present
$selected_staff_id = null;
if (isset($_GET['staff_id'])) {
    $selected_staff_id = intval($_GET['staff_id']);
} elseif (isset($_POST['staff_id'])) {
    $selected_staff_id = intval($_POST['staff_id']);
}

// Add this function at the top of the file after session_start()
function debug_log($message)
{
    error_log("[Template Debug] " . $message);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    try {
        $pdo->beginTransaction();

        $category_id = $_POST['category'] ?? '';
        $media_type_id = $_POST['media_type'] ?? '';
        $size = $_POST['size'] ?? '';
        $orientation = $_POST['orientation'] ?? '';
        $color_scheme = $_POST['color_scheme'] ?? '';
        $quantity = $_POST['quantity'] ?? 1;
        $price_range = $_POST['price_range'] ?? '';
        $additional_notes = $_POST['additional_notes'] ?? '';
        $preferred_staff = !empty($_POST['preferred_staff']) ? $_POST['preferred_staff'] : null;
        $preferred_color = $_POST['preferred_color'] ?? '#000000';
        $secondary_color = $_POST['secondary_color'] ?? null;

        // Get category name
        $stmt = $pdo->prepare("SELECT c_Name FROM category WHERE c_id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        $category_name = $category ? strtolower($category['c_Name']) : '';

        // Define required fields based on category
        $required_fields = [
            'category' => 'Category',
            'media_type' => 'Media Type',
            'size' => 'Size',
            'orientation' => 'Orientation',
            'color_scheme' => 'Color Scheme',
            'quantity' => 'Quantity'
        ];

        // Validate required fields
        $missing_fields = [];
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $label;
            }
        }

        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        // Handle file upload
        if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/custom_templates/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid('custom_', true) . '_' . basename($_FILES['reference_image']['name']);
            $upload_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['reference_image']['tmp_name'], $upload_path)) {
                debug_log("Reference image uploaded successfully to: " . $upload_path);

                // Insert into custom_template_requests table
                $stmt = $pdo->prepare("INSERT INTO custom_template_requests 
                    (user_id, category_id, media_type_id, size, orientation, color_scheme, quantity, 
                    price_range, additional_notes, reference_image, status, preferred_staff_id, preferred_color, secondary_color, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $current_time = date('Y-m-d H:i:s');

                // Debug the values being bound
                $bind_values = [
                    $user_id,
                    $category_id,
                    $media_type_id,
                    $size,
                    $orientation,
                    $color_scheme,
                    $quantity,
                    $price_range,
                    $additional_notes,
                    $file_name,
                    'Pending',
                    $preferred_staff,
                    $preferred_color,
                    $secondary_color,
                    $current_time
                ];

                debug_log("Number of placeholders in query: 15");
                debug_log("Number of values to bind: " . count($bind_values));
                debug_log("Values being bound: " . print_r($bind_values, true));

                $stmt->execute($bind_values);

                $request_id = $pdo->lastInsertId();
                debug_log("Successfully inserted request with ID: " . $request_id);
                $pdo->commit();

                // Create notification for admin users
                $admin_notification_stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, reference_id, reference_type)
                    SELECT 
                        u.id,
                        'New Custom Template Request',
                        CONCAT('A new custom template request has been submitted for ', c.c_Name),
                        'custom_request',
                        ?,
                        'custom_template'
                    FROM users u
                    CROSS JOIN category c
                    WHERE u.role = 'Admin'
                    AND c.c_id = ?
                ");
                $admin_notification_stmt->execute([$request_id, $category_id]);

                // Increment the counter before redirecting
                $subscriptionManager->incrementCustomDesignCount();

                // Redirect to additional information form with success message
                header("Location: additional_info_form.php?category_id=" . $category_id . "&request_id=" . $request_id . "&type=custom&success_message=" . urlencode("Custom template request submitted successfully!"));
                exit();
            } else {
                throw new Exception("Failed to upload image.");
            }
        } else {
            throw new Exception("Please upload a reference image.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error: " . $e->getMessage();
        debug_log("Error in form submission: " . $e->getMessage());
    }
}

// Fetch user's custom template requests
$stmt = $pdo->prepare("
    SELECT 
        ctr.id,
        ctr.user_id,
        ctr.category_id,
        ctr.media_type_id,
        ctr.size,
        ctr.orientation,
        ctr.color_scheme,
        ctr.quantity,
        ctr.additional_notes,
        ctr.reference_image,
        ctr.final_design,
        ctr.status,
        ctr.created_at,
        c.c_Name as category_name, 
        mt.name as media_type_name,
        (SELECT price FROM design_revisions WHERE request_id = ctr.id ORDER BY revision_number DESC LIMIT 1) as design_price
    FROM custom_template_requests ctr
    JOIN category c ON ctr.category_id = c.c_id
    JOIN media_type mt ON ctr.media_type_id = mt.id
    WHERE ctr.user_id = ?
    ORDER BY ctr.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = ($page === 1) ? 3 : 5;
$total_requests = count($requests);
$total_pages = ($total_requests <= 3) ? 1 : ceil(($total_requests - 3) / 5) + 1;
if ($page === 1) {
    $start = 0;
    $paginated_requests = array_slice($requests, 0, 3);
} else {
    $start = 3 + ($page - 2) * 5;
    $paginated_requests = array_slice($requests, $start, 5);
}

// Now include the header after all header operations are complete
require_once '../includes/header.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Template Request</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .requests-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            max-width: 100%;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .color-pickers {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-direction: row;
        }

        .color-pickers>div {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .color-pickers label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
        }

        .color-pickers input[type="color"],
        .color-pickers input[type="range"] {
            width: 100%;
            height: 40px;
            padding: 5px;
            border: 2px solid #e0e6ed;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .color-pickers input[type="color"]:hover,
        .color-pickers input[type="range"]:hover {
            border-color: #2ecc71;
        }

        .color-pickers input[type="color"]:focus,
        .color-pickers input[type="range"]:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }

        .grayscale-picker-container {
            position: relative;
            width: 100%;
        }

        .grayscale-picker {
            -webkit-appearance: none;
            width: 100%;
            height: 40px;
            background: linear-gradient(to right, #000000, #ffffff);
            outline: none;
            border-radius: 6px;
            border: 2px solid #e0e6ed;
        }

        .grayscale-picker::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 36px;
            background: #ffffff;
            cursor: pointer;
            border: 2px solid #2ecc71;
            border-radius: 3px;
        }

        .grayscale-picker::-moz-range-thumb {
            width: 20px;
            height: 36px;
            background: #ffffff;
            cursor: pointer;
            border: 2px solid #2ecc71;
            border-radius: 3px;
        }

        .color-preview {
            width: 100%;
            height: 20px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #e0e6ed;
        }

        .btn-submit {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .btn-submit:hover {
            background: linear-gradient(45deg, #0056b3, #003d80);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .color-pickers {
                flex-direction: column;
                gap: 10px;
            }
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            position: relative;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }

        .alert.fade-out {
            opacity: 0;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 3px 5px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .status-pending {
            color: #856404;
            background-color: #fff3cd;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .status-approved {
            color: #155724;
            background-color: #d4edda;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .status-rejected {
            color: #721c24;
            background-color: #f8d7da;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .preview-image {
            max-width: 100px;
            max-height: 100px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            object-fit: cover;
            border: 2px solid transparent;
        }

        .preview-image:hover {
            transform: scale(1.05);
            border-color: #2ecc71;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.2);
        }

        .feedback-btn {
            display: block;
            margin-top: 8px;
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            width: 100%;
            text-align: center;
        }

        .feedback-btn:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 99999;
            /* Increased z-index */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background-color: transparent;
            margin: 2% auto;
            padding: 20px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .modal-image {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .close {
            position: fixed;
            right: 25px;
            top: 25px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 100000;
            /* Even higher z-index for close button */
            text-decoration: none;
            border: none;
            outline: none;
        }

        .close:hover {
            background: #ffffff;
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            color: #dc3545;
        }

        .modal-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Details modal specific styles */
        .details-modal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
        }

        .details-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.3s ease-out;
        }

        /* Feedback modal specific styles */
        #feedbackModal .modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            margin: 5% auto;
            position: relative;
        }

        .modal-caption {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-size: 16px;
            text-align: center;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 10px 20px;
            border-radius: 20px;
            max-width: 80%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show .modal-caption {
            opacity: 1;
        }

        .feedback-form {
            margin-top: 20px;
        }

        .satisfaction-options {
            display: flex;
            gap: 20px;
            margin: 15px 0;
        }

        .satisfaction-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .satisfaction-option:hover {
            border-color: #007bff;
        }

        .satisfaction-option input[type="radio"] {
            margin-right: 8px;
        }

        .satisfaction-option.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }

        #feedbackNoteGroup {
            margin-top: 20px;
        }

        #feedback {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            resize: vertical;
            min-height: 100px;
            margin-top: 8px;
        }

        .feedback-submit-btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .feedback-submit-btn:hover {
            background-color: #0056b3;
        }

        .feedback-status {
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-block;
            margin-top: 10px;
        }

        .feedback-status.satisfied {
            background-color: #d4edda;
            color: #155724;
        }

        .feedback-status.not-satisfied {
            background-color: #f8d7da;
            color: #721c24;
        }

        .feedback-description {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }

        .resubmit-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .resubmit-btn:hover {
            background-color: #218838;
        }

        /* Add these styles to your existing CSS */
        .view-details-btn {
            background: none;
            border: none;
            color: #2ecc71;
            cursor: pointer;
            padding: 30px;
            transition: transform 0.2s;
        }

        .view-details-btn:hover {
            transform: scale(1.1);
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .details-title {
            font-size: 1.5em;
            color: #2c3e50;
            margin: 0;
        }

        .details-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #95a5a6;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }

        .details-close:hover {
            color: #2c3e50;
        }

        .details-body {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .detail-item {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .detail-label {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.1em;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Update the button styles */
        .add-template-button {
            background-color: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .add-template-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            background-color: #27ae60;
        }

        .add-template-button.back-mode {
            background-color: #e74c3c;
        }

        .add-template-button.back-mode:hover {
            background-color: #c0392b;
        }

        .add-template-button i {
            font-size: 1.1em;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px;
        }

        /* Update sort controls styling */
        .sort-controls-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 25px auto;
            padding: 18px 25px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            max-width: 400px;
            border: 1px solid #eef2f7;
            position: relative;
        }

        .sort-controls-container::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: #2ecc71;
            border-radius: 3px;
        }

        .sort-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .sort-controls label {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .sort-controls select {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            background-color: #f8fafc;
            font-size: 0.95rem;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%232c3e50' d='M6 8.825L1.175 4 2.05 3.125 6 7.075 9.95 3.125 10.825 4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 35px;
        }

        .sort-controls select:hover {
            border-color: #2ecc71;
            background-color: #ffffff;
        }

        .sort-controls select:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
            background-color: #ffffff;
        }

        /* Add a subtle animation for the container */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sort-controls-container {
            animation: fadeInUp 0.3s ease-out;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .sort-controls-container {
                margin: 20px 15px;
                padding: 15px;
            }

            .sort-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .sort-controls label {
                text-align: center;
            }

            .sort-controls select {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .modal-image {
                max-width: 95%;
                max-height: 80vh;
            }

            .close {
                top: 15px;
                right: 15px;
                font-size: 30px;
            }

            .modal-caption {
                bottom: 15px;
                font-size: 14px;
                padding: 8px 15px;
            }
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-btn {
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        .back-button {
            background-color: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .back-button:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .back-button i {
            font-size: 1.1em;
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

        /* Add these styles to your existing CSS */
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 10px 0;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radio-group label:hover {
            background-color: #f5f5f5;
        }

        .radio-group input[type="radio"] {
            margin: 0;
        }

        .radio-group input[type="radio"]:checked+span {
            font-weight: bold;
        }

        .radio-group label:has(input[type="radio"]:checked) {
            border-color: #007bff;
            background-color: #e7f1ff;
        }
    </style>
    <script>
        // Function to filter sizes based on selected category
        function filterSizes() {
            const sizeSelect = document.getElementById('size');
            if (!sizeSelect) return;

            const categorySelect = document.getElementById('category');
            if (!categorySelect) return;

            const selectedCategoryId = categorySelect.value;
            const options = sizeSelect.getElementsByTagName('option');

            for (let i = 1; i < options.length; i++) {
                const option = options[i];
                const optionCategory = option.getAttribute('data-category');
                if (!selectedCategoryId || optionCategory === selectedCategoryId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            sizeSelect.value = '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const categorySelect = document.getElementById('category');
            if (categorySelect) {
                categorySelect.addEventListener('change', function () {
                    filterSizes();
                });
                if (categorySelect.selectedIndex > 0) {
                    filterSizes();
                }
            }
        });

        function showImage(src, caption = '') {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const modalCaption = modal.querySelector('.modal-caption');
            const loading = modal.querySelector('.modal-loading');

            // Show loading indicator
            loading.style.display = 'block';
            modalImg.style.opacity = '0';

            // Show modal
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);

            // Load image
            modalImg.onload = function () {
                loading.style.display = 'none';
                modalImg.style.opacity = '1';
            };

            modalImg.src = src;
            modalCaption.textContent = caption;
        }

        function closeModal(modalId) {
            console.log('Closing modal:', modalId); // Debug log
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Update the window click handler
        window.onclick = function (event) {
            const imageModal = document.getElementById('imageModal');
            const feedbackModal = document.getElementById('feedbackModal');
            if (event.target === imageModal) {
                imageModal.style.display = 'none';
            }
            if (event.target === feedbackModal) {
                feedbackModal.style.display = 'none';
            }
        }

        // Add keyboard event listener for Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const imageModal = document.getElementById('imageModal');
                const feedbackModal = document.getElementById('feedbackModal');
                if (imageModal.style.display === 'block') {
                    imageModal.style.display = 'none';
                }
                if (feedbackModal.style.display === 'block') {
                    feedbackModal.style.display = 'none';
                }
            }
        });

        function handleSatisfactionChange(value) {
            const feedbackGroup = document.getElementById('feedbackNoteGroup');
            const feedbackInput = document.getElementById('feedback');

            if (value === 1) {
                feedbackGroup.style.display = 'none';
                feedbackInput.value = 'Customer is satisfied with the design.';
            } else {
                feedbackGroup.style.display = 'block';
                feedbackInput.value = '';
            }
        }

        function showFeedbackModal(requestId) {
            console.log('Opening feedback modal for request:', requestId);
            const modal = document.getElementById('feedbackModal');
            const form = document.getElementById('feedbackForm');

            // Reset form
            form.reset();
            document.getElementById('feedback_request_id').value = requestId;
            document.getElementById('feedbackNoteGroup').style.display = 'none';

            // Show modal
            modal.style.display = 'block';

            // Add change event listeners to radio buttons
            const radioButtons = form.querySelectorAll('input[name="is_satisfied"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function () {
                    const feedbackGroup = document.getElementById('feedbackNoteGroup');
                    feedbackGroup.style.display = this.value === '0' ? 'block' : 'none';

                    if (this.value === '1') {
                        document.getElementById('feedback').value = 'Customer is satisfied with the design.';
                    } else {
                        document.getElementById('feedback').value = '';
                    }

                    console.log('Radio button changed - Value:', this.value);
                });
            });
        }

        // Function to handle alert messages
        function handleAlerts() {
            const alerts = document.querySelectorAll('.alert');

            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500); // Wait for fade animation to complete
                }, 3000); // Hide after 3 seconds
            });
        }

        // Call handleAlerts when the page loads
        document.addEventListener('DOMContentLoaded', function () {
            handleAlerts();
            // ... any existing DOMContentLoaded handlers ...
        });

        // Modify the toggleForm function to reset the form when showing it
        function toggleForm() {
            const form = document.getElementById('customTemplateForm');
            const button = document.getElementById('formToggleBtn');
            const requestsSection = document.getElementById('requestsSection');

            if (form.style.display === 'none') {
                // Show form
                form.style.display = 'block';
                // Change button to Back
                button.innerHTML = '<i class="fas fa-arrow-left"></i> Back';
                button.classList.add('back-mode');
                // Hide requests section
                requestsSection.style.display = 'none';
            } else {
                // Hide form
                form.style.display = 'none';
                // Change button back to Add Custom Template
                button.innerHTML = '<i class="fas fa-plus"></i> Add Custom Template';
                button.classList.remove('back-mode');
                // Show requests section
                requestsSection.style.display = 'block';
            }
        }

        // Remove the automatic form display on error
        // Only show form if explicitly requested through the button
        document.addEventListener('DOMContentLoaded', function () {
            // Hide form by default
            document.getElementById('customTemplateForm').style.display = 'none';
            document.getElementById('requestsSection').style.display = 'block';

            // Handle alerts
            handleAlerts();

            // Initialize category filter
            const categorySelect = document.getElementById('category');
            if (categorySelect) {
                categorySelect.addEventListener('change', filterSizes);
                if (categorySelect.selectedIndex > 0) {
                    filterSizes();
                }
            }
        });

        function sortTable() {
            const select = document.getElementById('sortCategory');
            const table = document.getElementById('customTemplatesTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const selectedCategory = select.value;

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const category = row.getAttribute('data-category');

                if (selectedCategory === 'all' || category === selectedCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        // Initialize sorting when page loads
        document.addEventListener('DOMContentLoaded', function () {
            sortTable();
        });

        function showDetailsModal(mediaType, size, orientation, colorScheme) {
            const modal = document.getElementById('detailsModal');
            document.getElementById('modalMediaType').textContent = mediaType;
            document.getElementById('modalSize').textContent = size;
            document.getElementById('modalOrientation').textContent = orientation;
            document.getElementById('modalColorScheme').textContent = colorScheme;
            modal.style.display = 'block';
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDetailsModal();
            }
        });

        function createGrayscalePicker(container, colorInput) {
            // Create container for the grayscale picker
            const pickerContainer = document.createElement('div');
            pickerContainer.className = 'grayscale-picker-container';

            // Create range input
            const rangeInput = document.createElement('input');
            rangeInput.type = 'range';
            rangeInput.min = 0;
            rangeInput.max = 255;
            rangeInput.value = 128;
            rangeInput.className = 'grayscale-picker';

            // Create color preview
            const preview = document.createElement('div');
            preview.className = 'color-preview';

            // Add elements to container
            pickerContainer.appendChild(rangeInput);
            pickerContainer.appendChild(preview);

            // Update color on range input change
            rangeInput.addEventListener('input', function () {
                const grayHex = parseInt(this.value).toString(16).padStart(2, '0');
                const color = `#${grayHex}${grayHex}${grayHex}`;
                preview.style.backgroundColor = color;
                colorInput.value = color;
            });

            return pickerContainer;
        }

        function toggleColorPicker() {
            const scheme = document.getElementById('color_scheme').value;
            const primaryColorGroup = document.getElementById('preferred_color_group');
            const secondaryColorGroup = document.getElementById('secondary_color_group');
            const primaryColorInput = document.getElementById('preferred_color');
            const secondaryColorInput = document.getElementById('secondary_color');

            if (scheme === 'Black and White') {
                primaryColorGroup.style.display = 'none';
                secondaryColorGroup.style.display = 'none';
            } else {
                primaryColorGroup.style.display = 'block';
                secondaryColorGroup.style.display = 'block';

                if (scheme === 'Grayscale') {
                    // Set default grayscale values if not already grayscale
                    primaryColorInput.value = '#808080';  // 50% gray
                    secondaryColorInput.value = '#C0C0C0';  // 75% gray

                    // Force the color inputs to update to grayscale
                    enforceGrayscale(primaryColorInput);
                    enforceGrayscale(secondaryColorInput);
                }
            }
        }

        function enforceGrayscale(colorInput) {
            const hex = colorInput.value;
            if (/^#([0-9A-Fa-f]{6})$/.test(hex)) {
                // Get the red value and use it for all channels
                const r = parseInt(hex.substr(1, 2), 16);
                const g = parseInt(hex.substr(3, 2), 16);
                const b = parseInt(hex.substr(5, 2), 16);
                // Use luminance formula for better grayscale conversion
                const gray = Math.round(0.299 * r + 0.587 * g + 0.114 * b);
                const grayHex = gray.toString(16).padStart(2, '0');
                colorInput.value = `#${grayHex}${grayHex}${grayHex}`;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const colorSchemeSelect = document.getElementById('color_scheme');
            const primaryColorInput = document.getElementById('preferred_color');
            const secondaryColorInput = document.getElementById('secondary_color');

            function handleColorInput(e) {
                if (colorSchemeSelect.value === 'Grayscale') {
                    enforceGrayscale(e.target);
                }
            }

            // Add input event listeners to color inputs
            primaryColorInput.addEventListener('input', handleColorInput);
            secondaryColorInput.addEventListener('input', handleColorInput);

            // Also enforce grayscale when the color scheme changes
            colorSchemeSelect.addEventListener('change', function () {
                if (this.value === 'Grayscale') {
                    enforceGrayscale(primaryColorInput);
                    enforceGrayscale(secondaryColorInput);
                }
            });

            // Initial setup
            toggleColorPicker();
        });

        // Add this function to validate the form before submission
        function validateFeedbackForm() {
            const form = document.getElementById('feedbackForm');
            const selectedValue = form.querySelector('input[name="is_satisfied"]:checked');
            const feedbackText = document.getElementById('feedback').value.trim();

            console.log('Form validation - Selected value:', selectedValue ? selectedValue.value : 'none');
            console.log('Form validation - Feedback text:', feedbackText);

            if (!selectedValue) {
                alert('Please select whether you are satisfied with the design.');
                return false;
            }

            const isSatisfied = parseInt(selectedValue.value);
            if (isSatisfied === 0 && !feedbackText) {
                alert('Please provide feedback for the revision.');
                return false;
            }

            return true;
        }
    </script>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="content-header">
            <?php if ($page === 1): ?>
                <button onclick="toggleForm()" class="add-template-button" id="formToggleBtn">
                    <i class="fas fa-plus"></i> Add Custom Template
                </button>
            <?php endif; ?>
        </div>
        <div class="container">
            <?php if (isset($_GET['success_message'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($_GET['success_message']); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form id="customTemplateForm" method="POST" enctype="multipart/form-data" style="display: none;">
                    <div class="form-header">
                        <h2>Custom Template Request</h2>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" id="category" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['c_id']; ?>">
                                        <?php echo htmlspecialchars($category['c_Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="media_type">Media Type</label>
                            <select name="media_type" id="media_type" class="form-control" required>
                                <option value="">Select Media Type</option>
                                <?php foreach ($mediaTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="size">Size</label>
                            <select name="size" id="size" class="form-control" required>
                                <option value="">Select Size</option>
                                <?php
                                // Fetch all sizes from the database using PDO
                                $sizes_query = "SELECT * FROM sizes ORDER BY size_name";
                                $stmt = $pdo->query($sizes_query);
                                while ($size = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($size['size_name']) . '" data-category="' . htmlspecialchars($size['category_id']) . '">' . htmlspecialchars($size['size_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="orientation">Orientation</label>
                            <select name="orientation" id="orientation" class="form-control" required>
                                <option value="">Select Orientation</option>
                                <option value="Portrait">Portrait</option>
                                <option value="Landscape">Landscape</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="color_scheme">Color Scheme</label>
                            <select name="color_scheme" id="color_scheme" class="form-control" required
                                onchange="toggleColorPicker()">
                                <option value="">Select Color Scheme</option>
                                <option value="Black and White">Black and White</option>
                                <option value="Custom Color">Custom Color</option>
                                <option value="Grayscale">Grayscale</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" required min="1"
                                value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="price_range">Price Range (in Rs)</label>
                            <select name="price_range" id="price_range" class="form-control" required>
                                <option value="">Select Price Range</option>
                                <option value="Under Rs 50">Under Rs 50</option>
                                <option value="Rs 50 - Rs 100">Rs 50 - Rs 100</option>
                                <option value="Rs 100 - Rs 200">Rs 100 - Rs 200</option>
                                <option value="Rs 200 - Rs 500">Rs 200 - Rs 500</option>
                                <option value="Above Rs 500">Above Rs 500</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="preferred_staff">Preferred Designer</label>
                            <select name="preferred_staff" id="preferred_staff" class="form-control">
                                <option value="">Select Preferred Designer (Optional)</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?= $staff['id'] ?>" <?= ($selected_staff_id == $staff['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staff['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group color-pickers" id="preferred_color_group" style="display: none;">
                            <div>
                                <label for="preferred_color">Primary Color</label>
                                <input type="color" id="preferred_color" name="preferred_color"
                                    value="<?php echo isset($_POST['preferred_color']) ? htmlspecialchars($_POST['preferred_color']) : '#000000'; ?>"
                                    class="form-control" style="width: 60px; height: 40px; padding: 0; border: none;">
                            </div>
                            <div>
                                <label for="secondary_color">Secondary Color (Optional)</label>
                                <input type="color" id="secondary_color" name="secondary_color"
                                    value="<?php echo isset($_POST['secondary_color']) ? htmlspecialchars($_POST['secondary_color']) : '#ffffff'; ?>"
                                    class="form-control" style="width: 60px; height: 40px; padding: 0; border: none;">
                            </div>
                        </div>

                        <div class="form-group full-width" id="reference_image_group">
                            <label for="reference_image">Reference Image (if any)</label>
                            <input type="file" name="reference_image" id="reference_image" class="form-control"
                                accept="image/*">
                        </div>

                        <div class="form-group full-width" id="additional_notes_group">
                            <label for="additional_notes">Additional Notes</label>
                            <textarea name="additional_notes" id="additional_notes" class="form-control"
                                rows="4"><?php echo isset($_POST['additional_notes']) ? htmlspecialchars($_POST['additional_notes']) : ''; ?></textarea>
                        </div>
                    </div>

                    <button type="submit" name="submit_request" class="btn-submit">Submit Request</button>
                </form>
            </div>

            <div class="requests-section" id="requestsSection">
                <h2>Custom Templates</h2>
                <div class="sort-controls-container">
                    <div class="sort-controls">
                        <label for="sortCategory">Filter by Category:</label>
                        <select id="sortCategory" onchange="sortTable()">
                            <option value="all">All Categories</option>
                            <?php
                            // Get unique categories from requests
                            $categories = array_unique(array_column($requests, 'category_name'));
                            sort($categories);
                            foreach ($categories as $cat) {
                                echo '<option value="' . htmlspecialchars($cat) . '">' . htmlspecialchars($cat) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php if (!empty($requests)): ?>
                    <table id="customTemplatesTable">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Reference</th>
                                <th>Final Design</th>
                                <th>Price</th>
                                <th>Add to Cart</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginated_requests as $request): ?>
                                <?php
                                // Fetch latest revision for this request
                                $stmt = $pdo->prepare("SELECT is_satisfied, final_design FROM design_revisions WHERE request_id = ? ORDER BY revision_number DESC LIMIT 1");
                                $stmt->execute([$request['id']]);
                                $latest_revision = $stmt->fetch(PDO::FETCH_ASSOC);
                                $is_satisfied = $latest_revision ? $latest_revision['is_satisfied'] : 0;
                                $final_design = $latest_revision ? $latest_revision['final_design'] : '';
                                ?>
                                <tr data-category="<?php echo htmlspecialchars($request['category_name']); ?>">
                                    <td>
                                        <?php echo htmlspecialchars($request['category_name']); ?>
                                        <button class="view-details-btn"
                                            onclick="showDetailsModal('<?php echo htmlspecialchars($request['media_type_name']); ?>', '<?php echo htmlspecialchars($request['size']); ?>', '<?php echo htmlspecialchars($request['orientation']); ?>', '<?php echo htmlspecialchars($request['color_scheme']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo strtolower($request['status']); ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <?php if (isset($request['reference_image']) && $request['reference_image']): ?>
                                            <img src="../uploads/custom_templates/<?php echo htmlspecialchars($request['reference_image']); ?>"
                                                class="preview-image" alt="Reference Image" onclick="showImage(this.src)">
                                        <?php else: ?>
                                            No image
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($request['final_design'])): ?>
                                            <img src="../uploads/custom_templates/<?php echo htmlspecialchars($request['final_design']); ?>"
                                                class="preview-image" alt="Final Design" onclick="showImage(this.src)">
                                            <?php
                                            // Check if feedback is needed
                                            $feedback_check = $pdo->prepare("
                                                            SELECT is_satisfied, feedback, created_at 
                                                            FROM design_revisions 
                                                            WHERE request_id = ? 
                                                            ORDER BY revision_number DESC 
                                                            LIMIT 1
                                                        ");
                                            $feedback_check->execute([$request['id']]);
                                            $latest_feedback = $feedback_check->fetch(PDO::FETCH_ASSOC);

                                            if ($request['status'] === 'Completed' && !isset($latest_feedback['is_satisfied'])): ?>
                                                <button type="button" class="feedback-btn"
                                                    onclick="showFeedbackModal(<?php echo $request['id']; ?>)">
                                                    Provide Feedback
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Not available
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($request['design_price'])): ?>
                                            Rs <?php echo number_format($request['design_price'], 2); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_satisfied && !empty($final_design)): ?>
                                            <form action="/printing_press/pages/add_to_cart.php" method="POST"
                                                style="display:inline;" class="add-to-cart-form">
                                                <input type="hidden" name="custom_request_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="final_design"
                                                    value="<?php echo htmlspecialchars($final_design); ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="redirect_to" value="custom_template.php">
                                                <button type="submit" name="add_custom_to_cart" class="btn btn-success btn-sm">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                <?php else: ?>
                    <p>No custom template requests found.</p>
                <?php endif; ?>
            </div>

            <!-- Add Image Preview Modal -->
            <div id="imageModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('imageModal')">&times;</span>
                    <div class="modal-loading"></div>
                    <img id="modalImage" class="modal-image" alt="Enlarged Image">
                    <div class="modal-caption"></div>
                </div>
            </div>

            <!-- Add Feedback Modal -->
            <div id="feedbackModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
                    <h3>Design Feedback</h3>
                    <form id="feedbackForm" method="POST" class="feedback-form"
                        onsubmit="return validateFeedbackForm()">
                        <input type="hidden" name="request_id" id="feedback_request_id">
                        <div class="form-group">
                            <label>Are you satisfied with the design?</label>
                            <div class="satisfaction-options">
                                <label class="satisfaction-option">
                                    <input type="radio" name="is_satisfied" value="1" required>
                                    <span>Yes, I'm satisfied</span>
                                </label>
                                <label class="satisfaction-option">
                                    <input type="radio" name="is_satisfied" value="0" required>
                                    <span>No, needs revision</span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group" id="feedbackNoteGroup" style="display: none;">
                            <label for="feedback">Please explain what needs to be improved:</label>
                            <textarea name="feedback" id="feedback" class="form-control" rows="4"
                                placeholder="Please provide specific details about what changes you would like to see..."></textarea>
                        </div>
                        <button type="submit" name="submit_feedback" class="feedback-submit-btn">Submit
                            Feedback</button>
                    </form>
                </div>
            </div>

            <!-- Add this modal HTML before the closing body tag -->
            <div id="detailsModal" class="details-modal">
                <div class="details-modal-content">
                    <div class="details-header">
                        <h3 class="details-title">Template Details</h3>
                        <button class="details-close" onclick="closeDetailsModal()">&times;</button>
                    </div>
                    <div class="details-body">
                        <div class="detail-item">
                            <div class="detail-label">Media Type</div>
                            <div class="detail-value" id="modalMediaType"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Size</div>
                            <div class="detail-value" id="modalSize"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Orientation</div>
                            <div class="detail-value" id="modalOrientation"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Color Scheme</div>
                            <div class="detail-value" id="modalColorScheme"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add remaining attempts display -->
            <div class="alert alert-info" role="alert">
                Remaining free custom design requests: <?php echo $subscriptionManager->getRemainingCustomDesigns(); ?>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>