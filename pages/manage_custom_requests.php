<?php
session_start();
require_once('../includes/dbcon.php');
$pageTitle = 'Manage Custom Template Request';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get category ID from the request if provided, otherwise default to 1
$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 1;

// Handle request assignment/status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['assign_request'])) {
            $request_id = $_POST['request_id'];
            $staff_id = $_POST['staff_id'];

            // Validate inputs
            if (empty($request_id) || empty($staff_id)) {
                throw new Exception("Both request ID and staff member are required.");
            }

            // Check if request exists and is pending
            $check_stmt = $pdo->prepare("SELECT id, status FROM custom_template_requests WHERE id = ?");
            $check_stmt->execute([$request_id]);
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception("Request #" . $request_id . " not found. Please verify the request ID and try again.");
            }

            $request_status = $result['status'];
            if ($request_status !== 'Pending') {
                throw new Exception("Request #" . $request_id . " cannot be assigned because its status is '" . $request_status . "'. Only pending requests can be assigned.");
            }

            // Check if staff exists
            $check_staff_stmt = $pdo->prepare("SELECT id FROM staff WHERE id = ?");
            $check_staff_stmt->execute([$staff_id]);
            if (!$check_staff_stmt->fetch()) {
                throw new Exception("Selected staff member not found.");
            }

            // Check number of in-progress tasks for the staff member
            $in_progress_check = $pdo->prepare("
                SELECT COUNT(*) as in_progress_count 
                FROM custom_template_requests 
                WHERE assigned_staff_id = ? AND status = 'In Progress'
            ");
            $in_progress_check->execute([$staff_id]);
            $in_progress_count = $in_progress_check->fetch(PDO::FETCH_ASSOC)['in_progress_count'];

            if ($in_progress_count >= 2) {
                throw new Exception("Cannot assign request. Selected staff member already has 2 or more requests in progress.");
            }

            // Update request
            $stmt = $pdo->prepare("UPDATE custom_template_requests 
                SET assigned_staff_id = ?, status = 'In Progress' 
                WHERE id = ?");
            $stmt->execute([$staff_id, $request_id]);

            $success_message = "Request successfully assigned!";
        }

        if (isset($_POST['update_status'])) {
            $request_id = $_POST['request_id'];
            $new_status = $_POST['new_status'];

            // Check if this is a revision by counting existing revisions
            $revision_check = $pdo->prepare("SELECT COUNT(*) as rev_count FROM design_revisions WHERE request_id = ?");
            $revision_check->execute([$request_id]);
            $revision_count = $revision_check->fetch(PDO::FETCH_ASSOC)['rev_count'];
            $is_revision = $revision_count >= 1;

            // Handle final design upload
            if ($new_status === 'Completed' && isset($_FILES['final_design']) && $_FILES['final_design']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/custom_templates/final/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Get current revision number
                $revision_number = $revision_count + 1;

                $file_name = uniqid('final_', true) . '_' . basename($_FILES['final_design']['name']);
                $upload_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['final_design']['tmp_name'], $upload_path)) {
                    // Get the design price only if it's not a revision
                    $design_price = null;
                    if (!$is_revision && isset($_POST['design_price'])) {
                        $design_price = floatval($_POST['design_price']);
                    } else {
                        // For revisions, get the price from the previous revision
                        $price_stmt = $pdo->prepare("SELECT price FROM design_revisions WHERE request_id = ? ORDER BY revision_number DESC LIMIT 1");
                        $price_stmt->execute([$request_id]);
                        $previous_price = $price_stmt->fetch(PDO::FETCH_ASSOC);
                        $design_price = $previous_price ? $previous_price['price'] : null;
                    }

                    // Insert into design_revisions
                    $revision_insert = $pdo->prepare("INSERT INTO design_revisions (request_id, revision_number, final_design, staff_comment, price) VALUES (?, ?, ?, ?, ?)");
                    $staff_comment = isset($_POST['staff_comment']) ? $_POST['staff_comment'] : ($revision_number > 1 ? 'Reuploaded' : 'Initial design');
                    $revision_insert->execute([$request_id, $revision_number, 'final/' . $file_name, $staff_comment, $design_price]);

                    $stmt = $pdo->prepare("UPDATE custom_template_requests 
                        SET status = ?, final_design = ? 
                        WHERE id = ?");
                    $stmt->execute([$new_status, 'final/' . $file_name, $request_id]);
                } else {
                    throw new Exception("Failed to upload final design.");
                }
            }

            // Handle customer satisfaction feedback
            if (isset($_POST['submit_feedback'])) {
                $request_id = $_POST['request_id'];
                $is_satisfied = $_POST['is_satisfied'];
                $feedback = $_POST['feedback'];

                // Update the latest revision with customer feedback
                $update_revision = $pdo->prepare("
                    UPDATE design_revisions 
                    SET is_satisfied = ?, feedback = ?
                    WHERE request_id = ? AND revision_number = (
                        SELECT max_revision FROM (
                            SELECT MAX(revision_number) as max_revision 
                            FROM design_revisions 
                            WHERE request_id = ?
                        ) as subquery
                    )
                ");
                $update_revision->execute([$is_satisfied, $feedback, $request_id, $request_id]);

                // If not satisfied, set status back to In Progress
                if (!$is_satisfied) {
                    $stmt = $pdo->prepare("UPDATE custom_template_requests SET status = 'In Progress' WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $success_message = "Feedback submitted. The design will be revised.";
                } else {
                    $success_message = "Thank you for your feedback!";
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch all staff members for assignment
$staff_members = [];
if ($user_role === 'Admin') {
    // Only include staff with 3 or fewer combined pending and in-progress requests, and show the count
    $stmt = $pdo->query('
        SELECT s.id, u.name,
            (
                SELECT COUNT(*) FROM custom_template_requests ctr
                WHERE ctr.assigned_staff_id = s.id AND ctr.status IN ("Pending", "In Progress")
            ) AS active_count
        FROM staff s
        JOIN users u ON s.user_id = u.id
        WHERE u.role = "Staff"
        AND (
            SELECT COUNT(*) FROM custom_template_requests ctr
            WHERE ctr.assigned_staff_id = s.id AND ctr.status IN ("Pending", "In Progress")
        ) <= 3
        ORDER BY u.name ASC
    ');
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Build the base query
$requests_query = "
    SELECT 
        ctr.id as request_id,
        ctr.user_id,
        ctr.category_id,
        ctr.media_type_id,
        ctr.size,
        ctr.orientation,
        ctr.color_scheme,
        ctr.quantity,
        ctr.additional_notes,
        ctr.reference_image,
        ctr.status,
        ctr.assigned_staff_id,
        ctr.preferred_staff_id,
        ctr.created_at,
        ctr.final_design,
        c.c_Name as category_name,
        mt.name as media_type_name,
        u.name as customer_name,
        CONCAT(assigned_staff.name, ' (', assigned_staff.role, ')') as assigned_to,
        CONCAT(preferred_staff.name, ' (Preferred)') as preferred_staff_name,
        ctr.preferred_color,
        ctr.secondary_color,
        ctr.price_range
    FROM custom_template_requests ctr
    JOIN category c ON ctr.category_id = c.c_id
    JOIN media_type mt ON ctr.media_type_id = mt.id
    JOIN users u ON ctr.user_id = u.id
    LEFT JOIN users assigned_staff ON assigned_staff.id = (
        SELECT user_id FROM staff WHERE id = ctr.assigned_staff_id
    )
    LEFT JOIN users preferred_staff ON preferred_staff.id = (
        SELECT user_id FROM staff WHERE id = ctr.preferred_staff_id
    )
";

// Get all unique category IDs from the requests
$category_ids_query = "SELECT DISTINCT category_id FROM custom_template_requests";
$category_ids_result = $pdo->query($category_ids_query);
$category_ids = $category_ids_result->fetchAll(PDO::FETCH_COLUMN);

// For each category, check if it has an additional info table and add fields
foreach ($category_ids as $cat_id) {
    try {
        // Check if table exists
        $check_table = $pdo->query("SHOW TABLES LIKE 'additional_info_{$cat_id}'");
        if ($check_table->rowCount() > 0) {
            // Get fields for this category
            $fields_query = "SELECT field_name, field_label FROM additional_info_fields WHERE category_id = ?";
            $stmt = $pdo->prepare($fields_query);
            $stmt->execute([$cat_id]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($fields)) {
                // Add LEFT JOIN for this category's additional info table
                $requests_query .= "\nLEFT JOIN additional_info_{$cat_id} ai_{$cat_id} ON (ctr.id = ai_{$cat_id}.request_id AND ctr.category_id = {$cat_id})";

                // Add fields to SELECT clause
                foreach ($fields as $field) {
                    $field_name = $field['field_name'];
                    $requests_query = str_replace(
                        "assigned_to",
                        "assigned_to,\n        ai_{$cat_id}.`{$field_name}` as `{$cat_id}_{$field_name}`",
                        $requests_query
                    );
                }
            }
        }
    } catch (PDOException $e) {
        // Skip if there's an error with this category
        continue;
    }
}

// Add conditions based on role
if ($user_role === 'Staff') {
    $staff_query = "SELECT id FROM staff WHERE user_id = ?";
    $stmt = $pdo->prepare($staff_query);
    $stmt->execute([$user_id]);
    $staff_id = $stmt->fetchColumn();

    $requests_query .= " WHERE ctr.assigned_staff_id = ?";
    $requests_query .= " ORDER BY FIELD(ctr.status, 'Pending', 'In Progress', 'Approved', 'Rejected', 'Completed'), ctr.created_at DESC";
    $stmt = $pdo->prepare($requests_query);
    $stmt->execute([$staff_id]);
} elseif ($user_role === 'Customer') {
    $requests_query .= " WHERE ctr.user_id = ?";
    $requests_query .= " ORDER BY ctr.created_at DESC";
    $stmt = $pdo->prepare($requests_query);
    $stmt->execute([$user_id]);
} else {
    // Admin sees all requests
    $requests_query .= " ORDER BY FIELD(ctr.status, 'Pending', 'In Progress', 'Approved', 'Rejected', 'Completed'), ctr.created_at DESC";
    $stmt = $pdo->prepare($requests_query);
    $stmt->execute();
}

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 5;
$total_requests = count($requests);
$total_pages = ceil($total_requests / $per_page);
$start = ($page - 1) * $per_page;
$paginated_requests = array_slice($requests, $start, $per_page);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Custom Template Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .requests-table {
            width: 95%;
            /* Increased from 80% */
            border-collapse: collapse;
            margin: 20px auto;
            /* Changed margin to auto for centering */
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .requests-table th,
        .requests-table td {
            padding: 6px 8px;
            /* Reduced padding for compact rows */
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .requests-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .preview-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease;
        }

        .preview-image:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .image-container {
            width: 100px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 4px;
            overflow: hidden;
        }

        .no-image-text {
            color: #6c757d;
            font-size: 0.85rem;
            text-align: center;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-in-progress {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: white;
        }

        .btn-assign {
            background-color: #007bff;
        }

        .btn-update {
            background-color: #28a745;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .modal-image {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            margin: 0 auto;
            display: block;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #333;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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

        .request-count {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .count-badge {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        .total-requests {
            font-size: 1.1em;
            color: #666;
        }

        .info-section {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .info-section strong {
            display: block;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 4px 0;
            font-size: 13px;
            line-height: 1.4;
            color: #495057;
        }

        .info-item:not(:last-child) {
            border-bottom: 1px dashed #e9ecef;
        }

        .info-label {
            font-weight: 500;
            color: #6c757d;
            flex: 0 0 40%;
            padding-right: 10px;
        }

        .info-value {
            flex: 0 0 60%;
            text-align: right;
        }

        .no-info {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px dashed #dee2e6;
        }

        .requests-table td {
            max-width: 300px;
            vertical-align: top;
        }

        /* Modal styles */
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

        .view-btn,
        .btn-info {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .view-btn:hover,
        .btn-info:hover {
            background-color: #0056b3;
        }

        .view-btn i,
        .btn-info i {
            font-size: 12px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        /* Compact Details Column Styles */
        .details-content {
            background: #fff;
            border-radius: 4px;
            padding: 6px;
            margin: 0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            width: 160px;
        }

        .details-content strong {
            display: block;
            color: #2c3e50;
            font-size: 12px;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #e9ecef;
        }

        .details-item {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 2px 0;
            font-size: 11px;
            line-height: 1.2;
            color: #495057;
            gap: 4px;
        }

        .details-item:not(:last-child) {
            border-bottom: 1px dashed #e9ecef;
        }

        .details-label {
            font-weight: 500;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 2px;
            min-width: 60px;
        }

        .details-label i {
            font-size: 10px;
            width: 10px;
            color: #6c757d;
        }

        .details-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .details-notes {
            margin-top: 6px;
            padding: 6px;
            background-color: #fff8e1;
            border-radius: 4px;
            border-left: 2px solid #ffc107;
            font-style: italic;
            color: #856404;
            font-size: 11px;
            line-height: 1.3;
        }

        .details-notes:before {
            content: '💡';
            margin-right: 4px;
            font-size: 11px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .details-content {
                padding: 12px;
            }

            .details-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .details-label,
            .details-value {
                flex: 1;
                width: 100%;
                text-align: left;
            }

            .details-value {
                padding: 4px 0;
                background: none;
                border: none;
            }
        }

        .feedback-form {
            margin-top: 20px;
        }

        .satisfaction-options {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }

        .satisfaction-options label {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .revision-info {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
        }

        .feedback-text {
            margin: 5px 0 0;
            font-style: italic;
            color: #856404;
        }

        /* Update the feedback status styles to be more compact */
        .feedback-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin: 8px 0;
        }

        .feedback-description {
            margin: 5px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #007bff;
            font-size: 12px;
            line-height: 1.3;
        }

        .feedback-container {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .design-feedback {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
            font-size: 12px;
        }

        .design-feedback i {
            font-size: 14px;
        }

        .feedback-text {
            margin: 0;
            color: #666;
            font-size: 12px;
            line-height: 1.3;
            background-color: #fff3cd;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ffeeba;
        }

        /* Add styles for the feedback icons and popup */
        .status-icons {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .status-icon {
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            position: relative;
        }

        .status-icon.revision {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .status-icon.satisfied {
            color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }

        .status-icon.pending {
            color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }

        .status-icon:hover {
            transform: scale(1.1);
        }

        .feedback-popup {
            display: none;
            position: absolute;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            padding: 12px;
            z-index: 1000;
            width: 250px;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 8px;
        }

        .feedback-popup::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 50%;
            transform: translateX(-50%) rotate(45deg);
            width: 12px;
            height: 12px;
            background: white;
            box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.06);
        }

        .feedback-popup-title {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }

        .feedback-popup-content {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }

        /* Add overlay styles */
        .feedback-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 999;
        }

        /* Update styles for the final design container */
        .final-design-container {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .image-container {
            flex: 0 0 auto;
        }

        .status-icons {
            display: flex;
            align-items: center;
            margin-top: 0;
        }

        .status-icon {
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            position: relative;
        }

        .preview-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease;
        }

        .preview-image:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .image-container {
            width: 100px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 4px;
            overflow: hidden;
        }

        .no-image-text {
            color: #6c757d;
            font-size: 0.85rem;
            text-align: center;
        }

        /* Restore and update modal styles */
        .request-details-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        .request-details-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow: auto;
        }

        .details-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .details-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .details-label {
            font-weight: 500;
            color: #666;
        }

        .details-value {
            color: #333;
        }

        .icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #007bff;
        }

        .icon-button:hover {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .icon-button i {
            font-size: 1.2rem;
        }

        /* Add tooltip styles */
        .icon-button {
            position: relative;
        }

        .icon-button:hover::after {
            content: attr(title);
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <h2>Manage Custom Template Requests</h2>

            <div class="request-count">
                <div class="total-requests">
                    Total Requests: <span class="count-badge"><?php echo count($requests); ?></span>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Details</th>
                        <th>Additional Info</th>
                        <th>Reference Image</th>
                        <th>Final Design</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paginated_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['category_name']); ?></td>
                            <td>
                                <div class="icon-button"
                                    onclick="showRequestDetails(<?php echo htmlspecialchars(json_encode($request)); ?>)"
                                    title="View Details">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </td>
                            <td class="details-column">
                                <?php
                                // Get the category ID for this request
                                $req_category_id = $request['category_id'];

                                // Get the field definitions for this category
                                $fields_query = "SELECT field_name, field_label FROM additional_info_fields WHERE category_id = ?";
                                $stmt = $pdo->prepare($fields_query);
                                $stmt->execute([$req_category_id]);
                                $category_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                $additional_info = [];
                                foreach ($category_fields as $field) {
                                    $column_key = $req_category_id . '_' . $field['field_name'];
                                    if (isset($request[$column_key])) {
                                        $additional_info[$field['field_label']] = $request[$column_key];
                                    }
                                }

                                if (!empty($additional_info)): ?>
                                    <div class="icon-button"
                                        onclick="showAdditionalInfo(<?php echo htmlspecialchars(json_encode($additional_info)); ?>, <?php echo $request['request_id']; ?>)"
                                        title="Additional Info">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="no-info">No additional information available</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($request['reference_image'])): ?>
                                    <div class="image-container">
                                        <img src="../uploads/custom_templates/<?php echo htmlspecialchars($request['reference_image']); ?>"
                                            class="preview-image" alt="Reference Image" onclick="showImage(this.src)">
                                    </div>
                                <?php else: ?>
                                    <div class="no-image-text">No image</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($request['final_design']) && $request['final_design']): ?>
                                    <div class="final-design-container">
                                        <div class="image-container">
                                            <img src="../uploads/custom_templates/<?php echo htmlspecialchars($request['final_design']); ?>"
                                                class="preview-image" alt="Final Design" onclick="showImage(this.src)">
                                        </div>
                                        <?php
                                        // Check latest revision feedback
                                        $feedback_check = $pdo->prepare("
                                            SELECT is_satisfied, feedback, created_at 
                                            FROM design_revisions 
                                            WHERE request_id = ? 
                                            ORDER BY revision_number DESC 
                                            LIMIT 1
                                        ");
                                        $feedback_check->execute([$request['request_id']]);
                                        $latest_feedback = $feedback_check->fetch(PDO::FETCH_ASSOC);

                                        if ($latest_feedback):
                                            $icon_class = $latest_feedback['is_satisfied'] ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
                                            $status_class = $latest_feedback['is_satisfied'] ? 'satisfied' : 'revision';
                                            $status_text = $latest_feedback['is_satisfied'] ? 'Customer Satisfied' : 'Needs Revision';
                                            ?>
                                            <div class="status-icons">
                                                <div class="status-icon <?php echo $status_class; ?>"
                                                    onclick="toggleFeedback('feedback_<?php echo $request['request_id']; ?>')">
                                                    <i class="<?php echo $icon_class; ?>"></i>
                                                    <?php if (!empty($latest_feedback['feedback'])): ?>
                                                        <div id="feedback_<?php echo $request['request_id']; ?>" class="feedback-popup">
                                                            <div class="feedback-popup-title"><?php echo $status_text; ?></div>
                                                            <div class="feedback-popup-content">
                                                                <?php echo htmlspecialchars($latest_feedback['feedback']); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="status-icons">
                                                <div class="status-icon pending">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    Not available
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                    <?php echo htmlspecialchars($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $request['assigned_to'] ? htmlspecialchars($request['assigned_to']) : 'Not assigned'; ?>
                            </td>
                            <td class="action-buttons">
                                <?php if ($user_role === 'Admin' && $request['status'] === 'Pending'): ?>
                                    <?php
                                    // Debug the request ID
                                    echo "<!-- Debug: Request ID before button = " . $request['request_id'] . " -->";
                                    ?>
                                    <button class="btn btn-assign"
                                        onclick="showAssignModal(<?php echo $request['request_id']; ?>)">
                                        Assign (ID: <?php echo $request['request_id']; ?>)
                                    </button>
                                <?php endif; ?>
                                <?php if ($user_role === 'Staff' && $request['status'] !== 'Completed'): ?>
                                    <button class="btn btn-update"
                                        onclick="showStatusModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['price_range']); ?>')">
                                        Update Status
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="12">
                                <div class="staff-info">
                                    <?php if (!empty($request['preferred_staff_name'])): ?>
                                        <p class="preferred-staff">Customer's Preferred Staff:
                                            <?php echo htmlspecialchars($request['preferred_staff_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
                <div class="pagination-controls"
                    style="display: flex; justify-content: center; align-items: center; gap: 10px; margin: 20px 0;">
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

    <!-- Assign Staff Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('assignModal')">&times;</span>
            <h3>Assign Request</h3>
            <form method="POST" action="">
                <input type="hidden" name="request_id" id="assign_request_id">
                <div class="form-group">
                    <label for="staff_id">Select Staff Member:</label>
                    <select name="staff_id" id="staff_id" class="form-control" required>
                        <option value="">Select Staff</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>">
                                <?php echo htmlspecialchars($staff['name']); ?> (<?php echo $staff['active_count']; ?>
                                active)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign_request" class="btn btn-assign">Assign</button>
            </form>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('statusModal')">&times;</span>
            <h3>Update Request Status</h3>
            <form method="POST" enctype="multipart/form-data" id="statusForm">
                <input type="hidden" name="request_id" id="status_request_id">
                <input type="hidden" name="price_range" id="price_range">
                <input type="hidden" name="is_revision" id="is_revision">
                <div class="form-group">
                    <label for="new_status">Select New Status:</label>
                    <select name="new_status" id="new_status" class="form-control" required
                        onchange="toggleFinalDesignUpload(this)">
                        <option value="">Select Status</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div id="finalDesignUpload" class="form-group" style="display: none;">
                    <label for="final_design">Upload Final Design:</label>
                    <input type="file" name="final_design" id="final_design" class="form-control" accept="image/*">
                    <div class="form-group" id="priceInputGroup" style="margin-top: 15px;">
                        <label for="design_price">Design Price (Rs):</label>
                        <input type="number" name="design_price" id="design_price" class="form-control" step="0.01"
                            min="0">
                        <small id="priceRangeInfo" class="text-muted"></small>
                        <div id="priceError" class="text-danger" style="display: none;"></div>
                    </div>
                </div>
                <button type="submit" name="update_status" class="btn btn-update" id="updateStatusBtn">Update</button>
            </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('imageModal')">&times;</span>
            <img id="modalImage" class="modal-image" alt="Preview">
        </div>
    </div>

    <!-- Add Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
            <h3>Design Feedback</h3>
            <form method="POST" class="feedback-form">
                <input type="hidden" name="request_id" id="feedback_request_id">
                <div class="form-group">
                    <label>Are you satisfied with the design?</label>
                    <div class="satisfaction-options">
                        <label>
                            <input type="radio" name="is_satisfied" value="1" required> Yes
                        </label>
                        <label>
                            <input type="radio" name="is_satisfied" value="0" required> No
                        </label>
                    </div>
                </div>
                <div class="form-group" id="feedbackNoteGroup" style="display: none;">
                    <label for="feedback">Please explain why you're not satisfied:</label>
                    <textarea name="feedback" id="feedback" class="form-control" rows="4"></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
            </form>
        </div>
    </div>

    <script>
        function showAssignModal(requestId) {
            console.log('showAssignModal called with ID:', requestId); // Debug log
            document.getElementById('assign_request_id').value = requestId;
            document.getElementById('assignModal').style.display = 'block';
        }

        function showStatusModal(requestId, priceRange) {
            requestId = requestId.toString().replace('#', '');
            document.getElementById('status_request_id').value = requestId;
            document.getElementById('price_range').value = priceRange;
            document.getElementById('statusModal').style.display = 'block';

            // Reset form
            document.getElementById('statusForm').reset();
            document.getElementById('priceError').style.display = 'none';
            document.getElementById('finalDesignUpload').style.display = 'none';

            // Check if this is a revision by making an AJAX call
            fetch('check_revision.php?request_id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('is_revision').value = data.is_revision;
                });
        }

        function showImage(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = src;

            // Center the modal content
            modalImg.onload = function () {
                const modalContent = modal.querySelector('.modal-content');
                modalContent.style.marginTop = '5%';
            };
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function toggleFinalDesignUpload(selectElement) {
            const uploadField = document.getElementById('finalDesignUpload');
            const priceInputGroup = document.getElementById('priceInputGroup');
            const isRevision = document.getElementById('is_revision').value === 'true';

            uploadField.style.display = selectElement.value === 'Completed' ? 'block' : 'none';

            // Show price input only if it's not a revision
            priceInputGroup.style.display = (selectElement.value === 'Completed' && !isRevision) ? 'block' : 'none';

            if (selectElement.value === 'Completed' && !isRevision) {
                const priceRange = document.getElementById('price_range').value;
                // Extract numbers from the price range string (e.g., "50-100" or "50 - 100")
                const prices = priceRange.match(/\d+/g);
                if (prices && prices.length >= 2) {
                    const minPrice = parseFloat(prices[0]);
                    const maxPrice = parseFloat(prices[1]);

                    const priceInput = document.getElementById('design_price');
                    priceInput.min = minPrice;
                    priceInput.max = maxPrice;

                    document.getElementById('priceRangeInfo').textContent =
                        `Price must be between Rs ${minPrice} and Rs ${maxPrice}`;
                }
            }
        }

        function showRequestDetails(request) {
            // Create modal if it doesn't exist
            let modal = document.getElementById('requestDetailsModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'requestDetailsModal';
                modal.className = 'request-details-modal';
                document.body.appendChild(modal);
            }

            // Create modal content
            const content = `
                <div class="request-details-content">
                    <span class="close" onclick="closeRequestDetails()">&times;</span>
                    <h2>Request Details</h2>
                    
                    <div class="details-section">
                        <h3>Media Specifications</h3>
                        <div class="details-grid">
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-film"></i> Type:</span>
                                <span class="details-value">${request.media_type_name}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-ruler"></i> Size:</span>
                                <span class="details-value">${request.size}</span>
                            </div>
                        </div>
                    </div>

                    <div class="details-section">
                        <h3>Design Details</h3>
                        <div class="details-grid">
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-compass"></i> Orientation:</span>
                                <span class="details-value">${request.orientation}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-palette"></i> Color Scheme:</span>
                                <span class="details-value">${request.color_scheme}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-boxes"></i> Quantity:</span>
                                <span class="details-value">${request.quantity}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-dollar-sign"></i> Price Range:</span>
                                <span class="details-value">${request.price_range}</span>
                            </div>
                            ${request.preferred_color ? `
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-tint"></i> Primary Color:</span>
                                <span class="details-value">
                                    <span class="color-preview" style="background: ${request.preferred_color}"></span>
                                    ${request.preferred_color}
                                </span>
                            </div>
                            ` : ''}
                            ${request.secondary_color ? `
                            <div class="details-item">
                                <span class="details-label"><i class="fas fa-tint"></i> Secondary Color:</span>
                                <span class="details-value">
                                    <span class="color-preview" style="background: ${request.secondary_color}"></span>
                                    ${request.secondary_color}
                                </span>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    ${request.additional_notes ? `
                    <div class="details-section">
                        <h3>Additional Notes</h3>
                        <p>${request.additional_notes}</p>
                    </div>
                    ` : ''}
                </div>
            `;

            modal.innerHTML = content;
            modal.style.display = 'block';
        }

        function closeRequestDetails() {
            const modal = document.getElementById('requestDetailsModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function showAdditionalInfo(additionalInfo, requestId) {
            // Create modal if it doesn't exist
            let modal = document.getElementById('additionalInfoModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'additionalInfoModal';
                modal.className = 'request-details-modal';
                document.body.appendChild(modal);
            }

            // Create content sections from additional info
            let infoContent = '';
            for (const [label, value] of Object.entries(additionalInfo)) {
                infoContent += `
                    <div class="details-item">
                        <span class="details-label"><i class="fas fa-info-circle"></i> ${label}:</span>
                        <span class="details-value">${value}</span>
                    </div>
                `;
            }

            // Create modal content
            const content = `
                <div class="request-details-content">
                    <span class="close" onclick="closeAdditionalInfo()">&times;</span>
                    <h2>Additional Information</h2>
                    
                    <div class="details-section">
                        <h3>Custom Fields</h3>
                        <div class="details-grid">
                            ${infoContent}
                        </div>
                    </div>
                </div>
            `;

            modal.innerHTML = content;
            modal.style.display = 'block';
        }

        function closeAdditionalInfo() {
            const modal = document.getElementById('additionalInfoModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function showFeedbackModal(requestId) {
            document.getElementById('feedback_request_id').value = requestId;
            document.getElementById('feedbackModal').style.display = 'block';
        }

        // Show/hide feedback note based on satisfaction selection
        document.querySelectorAll('input[name="is_satisfied"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const feedbackGroup = document.getElementById('feedbackNoteGroup');
                feedbackGroup.style.display = this.value === '0' ? 'block' : 'none';

                if (this.value === '1') {
                    document.getElementById('feedback').value = 'Customer is satisfied with the design.';
                } else {
                    document.getElementById('feedback').value = '';
                }
            });
        });

        // Add form submission validation
        document.getElementById('statusForm').addEventListener('submit', function (e) {
            const status = document.getElementById('new_status').value;

            if (status === 'Completed') {
                const priceRange = document.getElementById('price_range').value;
                const prices = priceRange.match(/\d+/g);
                if (prices && prices.length >= 2) {
                    const minPrice = parseFloat(prices[0]);
                    const maxPrice = parseFloat(prices[1]);
                    const enteredPrice = parseFloat(document.getElementById('design_price').value);

                    if (enteredPrice < minPrice || enteredPrice > maxPrice) {
                        e.preventDefault();
                        const priceError = document.getElementById('priceError');
                        priceError.textContent = `Price must be between Rs ${minPrice} and Rs ${maxPrice}`;
                        priceError.style.display = 'block';
                    }
                }
            }
        });

        // Add input validation for price
        document.getElementById('design_price').addEventListener('input', function (e) {
            const priceRange = document.getElementById('price_range').value;
            const prices = priceRange.match(/\d+/g);
            if (prices && prices.length >= 2) {
                const minPrice = parseFloat(prices[0]);
                const maxPrice = parseFloat(prices[1]);
                const enteredPrice = parseFloat(this.value);

                const priceError = document.getElementById('priceError');
                if (enteredPrice < minPrice || enteredPrice > maxPrice) {
                    priceError.textContent = `Price must be between Rs ${minPrice} and Rs ${maxPrice}`;
                    priceError.style.display = 'block';
                    document.getElementById('updateStatusBtn').disabled = true;
                } else {
                    priceError.style.display = 'none';
                    document.getElementById('updateStatusBtn').disabled = false;
                }
            }
        });

        // Close modal when clicking outside
        window.onclick = function (event) {
            const requestModal = document.getElementById('requestDetailsModal');
            const additionalInfoModal = document.getElementById('additionalInfoModal');
            const imageModal = document.getElementById('imageModal');
            const assignModal = document.getElementById('assignModal');
            const statusModal = document.getElementById('statusModal');
            const feedbackModal = document.getElementById('feedbackModal');

            if (event.target === requestModal) {
                closeRequestDetails();
            }
            if (event.target === additionalInfoModal) {
                closeAdditionalInfo();
            }
            if (event.target === imageModal ||
                event.target === assignModal ||
                event.target === statusModal ||
                event.target === feedbackModal) {
                closeModal(event.target.id);
            }
        }

        // Add these new functions for feedback popup handling
        let activePopup = null;
        let overlay = null;

        function createOverlay() {
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'feedback-overlay';
                document.body.appendChild(overlay);

                overlay.addEventListener('click', function () {
                    if (activePopup) {
                        activePopup.style.display = 'none';
                        overlay.style.display = 'none';
                        activePopup = null;
                    }
                });
            }
        }

        function toggleFeedback(popupId) {
            createOverlay();
            const popup = document.getElementById(popupId);

            if (activePopup && activePopup !== popup) {
                activePopup.style.display = 'none';
            }

            if (popup.style.display === 'block') {
                popup.style.display = 'none';
                overlay.style.display = 'none';
                activePopup = null;
            } else {
                popup.style.display = 'block';
                overlay.style.display = 'block';
                activePopup = popup;
            }
        }

        // Close popup when clicking outside
        document.addEventListener('click', function (event) {
            if (activePopup && !event.target.closest('.status-icon')) {
                activePopup.style.display = 'none';
                if (overlay) {
                    overlay.style.display = 'none';
                }
                activePopup = null;
            }
        });
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>