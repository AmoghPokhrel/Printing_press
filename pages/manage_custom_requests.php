<?php
session_start();
require_once('../includes/dbcon.php');
require_once('../includes/create_notification.php');
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

            // Get staff user_id and request details for notification
            $staff_details = $pdo->prepare("
                SELECT u.id as user_id, ctr.category_id, c.c_Name as category_name
                FROM staff s 
                JOIN users u ON s.user_id = u.id
                JOIN custom_template_requests ctr ON ctr.id = ?
                JOIN category c ON c.c_id = ctr.category_id
                WHERE s.id = ?
            ");
            $staff_details->execute([$request_id, $staff_id]);
            $staff_info = $staff_details->fetch(PDO::FETCH_ASSOC);

            // Create notification for the assigned staff
            $title = "New Custom Request Assignment";
            $message = "You have been assigned a new custom request for {$staff_info['category_name']}";

            // Create notification using PDO
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id, reference_type) VALUES (?, ?, ?, ?, ?, ?)");
            $notification_stmt->execute([
                $staff_info['user_id'],
                $title,
                $message,
                'custom_request',
                $request_id,
                'custom_template'
            ]);

            $success_message = "Request successfully assigned!";
        }

        if (isset($_POST['update_status'])) {
            $request_id = $_POST['request_id'];
            $new_status = $_POST['new_status'];
            $design_price = isset($_POST['design_price']) ? floatval($_POST['design_price']) : 0;
            $price_range = $_POST['price_range'] ?? '';

            // Validate price range
            $price_limits = [
                'Under Rs 50' => ['min' => 0, 'max' => 49],
                'Rs 50 - Rs 100' => ['min' => 50, 'max' => 100],
                'Rs 100 - Rs 200' => ['min' => 100, 'max' => 200],
                'Rs 200 - Rs 500' => ['min' => 200, 'max' => 500],
                'Above Rs 500' => ['min' => 500, 'max' => 999999]
            ];

            // Check if this is a revision by counting existing revisions
            $revision_check = $pdo->prepare("SELECT COUNT(*) as rev_count FROM design_revisions WHERE request_id = ?");
            $revision_check->execute([$request_id]);
            $revision_count = $revision_check->fetch(PDO::FETCH_ASSOC)['rev_count'];
            $is_revision = $revision_count >= 1;

            // Get customer details and request info for notification
            $customer_details = $pdo->prepare("
                SELECT u.id as user_id, ctr.category_id, c.c_Name as category_name, ctr.price_range
                FROM custom_template_requests ctr 
                JOIN users u ON ctr.user_id = u.id
                JOIN category c ON c.c_id = ctr.category_id
                WHERE ctr.id = ?
            ");
            $customer_details->execute([$request_id]);
            $customer_info = $customer_details->fetch(PDO::FETCH_ASSOC);

            // Validate price against range if status is Completed and not a revision
            if ($new_status === 'Completed' && !$is_revision) {
                $price_range = $customer_info['price_range'];
                if (!isset($price_limits[$price_range])) {
                    throw new Exception("Invalid price range specified");
                }

                $min_price = $price_limits[$price_range]['min'];
                $max_price = $price_limits[$price_range]['max'];

                if ($design_price < $min_price || $design_price > $max_price) {
                    throw new Exception("Price must be between Rs {$min_price} and Rs {$max_price} for {$price_range}");
                }
            }

            // Handle final design upload
            if ($new_status === 'Completed' && isset($_FILES['final_design']) && $_FILES['final_design']['error'] === UPLOAD_ERR_OK) {
                $file_name = uniqid() . '_' . basename($_FILES['final_design']['name']);
                $upload_path = '../uploads/custom_templates/final/' . $file_name;

                if (move_uploaded_file($_FILES['final_design']['tmp_name'], $upload_path)) {
                    // Get the original price from the first revision if this is a revision
                    if ($is_revision) {
                        $original_price_stmt = $pdo->prepare("
                            SELECT price 
                            FROM design_revisions 
                            WHERE request_id = ? 
                            ORDER BY revision_number ASC 
                            LIMIT 1
                        ");
                        $original_price_stmt->execute([$request_id]);
                        $original_price = $original_price_stmt->fetchColumn();
                        $design_price = $original_price ?: $design_price; // Use original price if available
                    }

                    // Create a new revision entry
                    $revision_stmt = $pdo->prepare("
                        INSERT INTO design_revisions (request_id, revision_number, final_design, price, created_at)
                        SELECT 
                            ?, 
                            (SELECT COALESCE(MAX(revision_number), 0) + 1 FROM design_revisions dr WHERE dr.request_id = ?) as next_revision,
                            ?, 
                            ?, 
                            NOW()
                    ");
                    $revision_stmt->execute([$request_id, $request_id, $file_name, $design_price]);

                    $stmt = $pdo->prepare("UPDATE custom_template_requests 
                        SET status = ?, final_design = ? 
                        WHERE id = ?");
                    $stmt->execute([$new_status, 'final/' . $file_name, $request_id]);

                    // Create notification for completed status with final design
                    $title = "Custom Request Completed";
                    $message = "Your custom request for {$customer_info['category_name']} has been completed. The final design is ready for review.";
                } else {
                    throw new Exception("Failed to upload final design.");
                }
            } else {
                // Update status without final design
                $stmt = $pdo->prepare("UPDATE custom_template_requests SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $request_id]);

                // Create notification for other status changes
                $title = "Custom Request Status Update";
                $message = "Your custom request for {$customer_info['category_name']} has been updated to: $new_status";
            }

            // Create notification using PDO
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id, reference_type) VALUES (?, ?, ?, ?, ?, ?)");
            $notification_stmt->execute([
                $customer_info['user_id'],
                $title,
                $message,
                'custom_request',
                $request_id,
                'custom_template'
            ]);

            $success_message = "Request status successfully updated!";

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

// Initialize additional info array at the start of the file
$additional_info = [];

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
$per_page = 4; // Show 4 cards per page
$total_requests = count($requests);
$total_pages = ceil($total_requests / $per_page);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $per_page;
$paginated_requests = array_slice($requests, $start, $per_page);

// Process each request to include additional info
foreach ($requests as &$request) {
    $request['additional_info'] = []; // Initialize empty array

    try {
        // Check if additional info table exists for this category
        $check_table = $pdo->query("SHOW TABLES LIKE 'additional_info_{$request['category_id']}'");
        if ($check_table->rowCount() > 0) {
            // Get the additional info fields for this category
            $fields_stmt = $pdo->prepare("SELECT field_name, field_label FROM additional_info_fields WHERE category_id = ?");
            $fields_stmt->execute([$request['category_id']]);
            $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($fields)) {
                // Get the actual additional info data
                $info_stmt = $pdo->prepare("SELECT * FROM additional_info_{$request['category_id']} WHERE request_id = ?");
                $info_stmt->execute([$request['request_id']]);
                $info_data = $info_stmt->fetch(PDO::FETCH_ASSOC);

                if ($info_data) {
                    foreach ($fields as $field) {
                        if (isset($info_data[$field['field_name']])) {
                            $request['additional_info'][$field['field_label']] = $info_data[$field['field_name']];
                        }
                    }
                }
            }
        }
    } catch (PDOException $e) {
        // Log error and continue
        error_log("Error fetching additional info for request {$request['request_id']}: " . $e->getMessage());
    }
}
unset($request); // Break the reference

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            width: 4cm;
            height: 4cm;
            object-fit: contain;
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
            width: 4cm;
            height: 4cm;
            border-radius: 4px;
            overflow: hidden;
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.2s ease;
        }

        .no-image-text {
            color: #94a3b8;
            font-size: 8px;
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
            padding: 4px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
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
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 1050;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 2% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-content img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-content h3 {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            color: #4a5568;
        }

        .modal-content .btn-assign {
            width: 100%;
            padding: 12px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .modal-content .btn-assign:hover {
            background-color: #2563eb;
        }

        .close-modal {
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
            z-index: 1060;
            text-decoration: none;
            border: none;
            outline: none;
        }

        .close-modal:hover {
            background: #ffffff;
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            color: #dc3545;
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
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
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
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .info-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .info-label {
            font-weight: 500;
            color: #4a5568;
        }

        .info-value {
            color: #2d3748;
            font-weight: 400;
        }

        /* Improved Request Details Modal Styles */
        .request-details-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            animation: fadeIn 0.3s ease;
        }

        .request-details-content {
            position: relative;
            background-color: #fff;
            margin: 3% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
        }

        .request-details-content h2 {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .details-section {
            background-color: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .details-section h3 {
            color: #4a5568;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .details-section h3 i {
            color: #3b82f6;
            font-size: 1rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .details-item {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .details-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
        }

        .details-label {
            color: #64748b;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 120px;
        }

        .details-label i {
            color: #3b82f6;
            font-size: 1rem;
            width: 16px;
            text-align: center;
        }

        .details-value {
            color: #1e293b;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .color-preview {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .additional-notes {
            background-color: #fff8dc;
            border-left: 4px solid #ffd700;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .additional-notes p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Close button styling */
        .request-details-content .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            color: #64748b;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .request-details-content .close:hover {
            background: #e2e8f0;
            color: #1e293b;
            transform: rotate(90deg);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .request-details-content {
                margin: 0;
                width: 100%;
                height: 100%;
                max-height: 100%;
                border-radius: 0;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .details-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .details-label {
                margin-bottom: 4px;
            }
        }

        /* Restore original page layout styles */
        .request-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            /* 2 cards per row */
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            /* Increased to accommodate 2 cards */
            margin: 0 auto;
        }

        .request-card {
            position: relative;
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .staff-labels {
            position: absolute;
            top: -15px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 0 15px;
            pointer-events: none;
            z-index: 15;
        }

        .staff-label {
            background: white;
            padding: 5px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 0.85rem;
            max-width: 45%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            pointer-events: auto;
            transition: all 0.3s ease;
        }

        .staff-label:hover {
            max-width: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            z-index: 20;
        }

        .staff-label.assigned {
            background: white;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }

        .staff-label.preferred {
            background: #f0f9ff;
            color: #3b82f6;
            border: 1px solid #bfdbfe;
        }

        .card-content {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 60px 1fr;
            gap: 20px;
            align-items: start;
        }

        /* Status-specific border colors */
        .request-card.status-pending {
            border-left-color: #fbbf24;
            /* Amber/Yellow for Pending */
        }

        .request-card.status-in-progress {
            border-left-color: #3b82f6;
            /* Blue for In Progress */
        }

        .request-card.status-approved {
            border-left-color: #10b981;
            /* Green for Approved */
        }

        .request-card.status-rejected {
            border-left-color: #ef4444;
            /* Red for Rejected */
        }

        .request-card.status-completed {
            border-left-color: #22c55e;
            /* Emerald Green for Completed */
        }

        /* Add hover effect */
        .request-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Add status indicator dot */
        .status-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #e2e8f0;
        }

        .status-indicator.status-pending {
            background-color: #fbbf24;
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.4);
        }

        .status-indicator.status-in-progress {
            background-color: #3b82f6;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
        }

        .status-indicator.status-approved {
            background-color: #10b981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
        }

        .status-indicator.status-rejected {
            background-color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
        }

        .status-indicator.status-completed {
            background-color: #22c55e;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.4);
        }

        .reference-section,
        .final-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            width: 100%;
        }

        .image-container {
            width: 3cm;
            height: 3cm;
            border-radius: 4px;
            overflow: hidden;
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.2s ease;
        }

        .no-image-text {
            color: #94a3b8;
            font-size: 14px;
            text-align: center;
        }

        .section-title {
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .icons-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            justify-content: center;
            padding-top: 20px;
        }

        .icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid #e2e8f0;
            position: relative;
        }

        .icon-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .icon-circle.info i {
            color: #3b82f6;
        }

        .icon-circle.details i {
            color: #10b981;
        }

        .icon-circle.designer i {
            color: #8b5cf6;
        }

        .designer-name {
            display: none;
            position: absolute;
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-size: 12px;
            color: #4a5568;
            z-index: 10;
            white-space: nowrap;
            left: 50%;
            transform: translateX(-50%);
            top: -40px;
        }

        .icon-circle:hover .designer-name {
            display: block;
        }

        .final-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            width: 100%;
        }

        .final-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .final-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 20px;
        }

        .final-design-container {
            border-left: 5px solid #94a3b8;
            /* Default gray border for not rated */
            padding-left: 15px;
            transition: all 0.3s ease;
            width: 100%;
        }

        /* Satisfaction status borders */
        .final-design-container.satisfaction-yes {
            border-left: 5px solid #22c55e !important;
            /* Green for satisfied */
        }

        .final-design-container.satisfaction-no {
            border-left: 5px solid #ef4444 !important;
            /* Red for not satisfied */
        }

        /* Responsive adjustments for cards */
        @media (max-width: 992px) {
            .request-cards {
                grid-template-columns: 1fr;
                /* Single column on smaller screens */
            }
        }

        /* Update pagination controls style */
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 30px 0;
            padding: 10px;
        }

        .pagination-controls .btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #3b82f6;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .pagination-controls .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .pagination-controls .page-info {
            font-weight: 500;
            color: #4b5563;
            font-size: 14px;
            padding: 5px 10px;
            background: #f3f4f6;
            border-radius: 6px;
        }

        /* Disabled button state */
        .pagination-controls .btn.disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Update satisfaction status styles */
        .final-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .final-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            min-height: 4cm;
            justify-content: center;
        }

        /* Add styles for the color legend info icon and tooltip */
        .color-legend-info {
            position: fixed;
            top: 80px;
            right: 25px;
            width: 40px;
            height: 40px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 997;
            transition: all 0.3s ease;
        }

        .color-legend-info i {
            color: #3b82f6;
            font-size: 20px;
        }

        .color-legend-info:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .color-legend-tooltip {
            display: none;
            position: fixed;
            top: 130px;
            right: 25px;
            width: 280px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 997;
        }

        .color-legend-tooltip.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .color-legend-tooltip h4 {
            margin: 0 0 15px 0;
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .color-legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .color-label {
            color: #4b5563;
            font-size: 14px;
        }

        .assigned-staff {
            position: absolute;
            top: -10px;
            left: 10px;
            background: white;
            padding: 5px 10px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 0.85rem;
            color: #4a5568;
            z-index: 10;
            border: 1px solid #e2e8f0;
            max-width: calc(100% - 20px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .assigned-staff:hover {
            max-width: none;
            z-index: 20;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: #f8fafc;
            transform: translateY(-2px);
        }

        .request-card {
            position: relative;
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .card-content {
            position: relative;
            z-index: 5;
        }

        /* Add styles for preferred staff name */
        .preferred-staff {
            position: absolute;
            top: -10px;
            right: 10px;
            background: #f0f9ff;
            padding: 5px 10px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 0.85rem;
            color: #3b82f6;
            z-index: 10;
            border: 1px solid #bfdbfe;
            max-width: calc(50% - 20px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .preferred-staff:hover {
            max-width: none;
            z-index: 20;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: #e0f2fe;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <!-- Add color legend info icon and tooltip -->
        <div class="color-legend-info" onclick="toggleColorLegend()">
            <i class="fas fa-info-circle"></i>
        </div>
        <div class="color-legend-tooltip" id="colorLegend">
            <h4><i class="fas fa-palette"></i> Status Colors</h4>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #fbbf24;"></div>
                <span class="color-label">Pending</span>
            </div>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #3b82f6;"></div>
                <span class="color-label">In Progress</span>
            </div>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #22c55e;"></div>
                <span class="color-label">Completed</span>
            </div>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #ef4444;"></div>
                <span class="color-label">Rejected</span>
            </div>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #22c55e;"></div>
                <span class="color-label">Approved</span>
            </div>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #94a3b8;"></div>
                <span class="color-label">Not Rated</span>
            </div>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #22c55e;"></div>
                <span class="color-label">Satisfied</span>
            </div>
            <div class="color-legend-item">
                <div class="color-dot" style="background: #ef4444;"></div>
                <span class="color-label">Not Satisfied</span>
            </div>
        </div>

        <div class="container">
            <!-- <h2>Manage Custom Template Requests</h2> -->

            <!-- <div class="request-count">
                <div class="total-requests">
                    Total Requests: <span class="count-badge"><?php echo count($requests); ?></span>
                </div>
            </div> -->

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" id="successAlert"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" id="errorAlert"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <script>
                // Function to hide alerts after 3 seconds
                function hideAlerts() {
                    const successAlert = document.getElementById('successAlert');
                    const errorAlert = document.getElementById('errorAlert');

                    if (successAlert) {
                        setTimeout(() => {
                            successAlert.style.transition = 'opacity 0.5s ease';
                            successAlert.style.opacity = '0';
                            setTimeout(() => {
                                successAlert.style.display = 'none';
                            }, 500);
                        }, 3000);
                    }

                    if (errorAlert) {
                        setTimeout(() => {
                            errorAlert.style.transition = 'opacity 0.5s ease';
                            errorAlert.style.opacity = '0';
                            setTimeout(() => {
                                errorAlert.style.display = 'none';
                            }, 500);
                        }, 3000);
                    }
                }

                // Call the function when the page loads
                document.addEventListener('DOMContentLoaded', hideAlerts);
            </script>

            <div class="request-cards">
                <?php foreach ($paginated_requests as $request): ?>
                    <div class="request-card status-<?php echo str_replace(' ', '-', strtolower($request['status'])); ?>">
                        <div
                            class="status-indicator status-<?php echo str_replace(' ', '-', strtolower($request['status'])); ?>">
                        </div>

                        <!-- Add staff labels container -->
                        <?php if ($user_role === 'Admin'): ?>
                            <div class="staff-labels">
                                <?php if (!empty($request['assigned_to'])): ?>
                                    <div class="staff-label assigned"
                                        title="<?php echo htmlspecialchars($request['assigned_to']); ?>">
                                        <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($request['assigned_to']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($request['preferred_staff_name'])): ?>
                                    <div class="staff-label preferred"
                                        title="<?php echo htmlspecialchars($request['preferred_staff_name']); ?>">
                                        <i class="fas fa-star"></i>
                                        <?php echo htmlspecialchars($request['preferred_staff_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="card-content">
                            <div class="reference-section">
                                <h4 class="section-title">Reference Image</h4>
                                <div class="image-container">
                                    <?php if (!empty($request['reference_image'])): ?>
                                        <img src="../uploads/custom_templates/<?php echo htmlspecialchars($request['reference_image']); ?>"
                                            alt="Reference Image" onclick="showImage(this.src)">
                                    <?php else: ?>
                                        <div class="no-image-text">No reference image</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="icons-section">
                                <div class="icon-circle info" onclick="showAdditionalInfo(<?php
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
                                echo htmlspecialchars(json_encode($additional_info));
                                ?>, <?php echo $request['request_id']; ?>)">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="icon-circle details"
                                    onclick="showRequestDetails(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <?php if (!empty($request['preferred_staff_name'])): ?>
                                    <div class="icon-circle designer">
                                        <i class="fas fa-cube"></i>
                                        <div class="designer-name">Preferred:
                                            <?php echo htmlspecialchars($request['preferred_staff_name']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="final-section">
                                <h4 class="section-title" style="padding-right:70px;">Final Design</h4>
                                <div class="final-wrapper">
                                    <div class="final-content">
                                        <?php
                                        // Get the latest revision's satisfaction status
                                        $satisfaction_check = $pdo->prepare("
                                            SELECT is_satisfied 
                                            FROM design_revisions 
                                            WHERE request_id = ? 
                                            ORDER BY revision_number DESC 
                                            LIMIT 1
                                        ");
                                        $satisfaction_check->execute([$request['request_id']]);
                                        $satisfaction_result = $satisfaction_check->fetch(PDO::FETCH_ASSOC);

                                        $satisfaction_class = '';
                                        if ($satisfaction_result) {
                                            // Cast to integer for proper comparison
                                            $is_satisfied = (int) $satisfaction_result['is_satisfied'];
                                            if ($is_satisfied === 1) {
                                                $satisfaction_class = 'satisfaction-yes';
                                            } elseif ($is_satisfied === 0) {
                                                $satisfaction_class = 'satisfaction-no';
                                            }
                                        }
                                        ?>
                                        <div class="final-design-container <?php echo $satisfaction_class; ?>">
                                            <div class="image-container">
                                                <?php if (isset($request['final_design']) && $request['final_design']): ?>
                                                    <img src="../uploads/custom_templates/<?php echo htmlspecialchars($request['final_design']); ?>"
                                                        alt="Final Design" onclick="showImage(this.src)">
                                                <?php else: ?>
                                                    <div class="no-image-text">No final design yet</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="final-actions">
                                        <?php if (!empty($request['assigned_to'])): ?>
                                            <div class="icon-circle designer">
                                                <i class="fas fa-cube"></i>
                                                <div class="designer-name">Assigned:
                                                    <?php echo htmlspecialchars($request['assigned_to']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($user_role === 'Admin' && $request['status'] === 'Pending'): ?>
                                            <button class="btn btn-assign"
                                                onclick="showAssignModal(<?php echo $request['request_id']; ?>)">
                                                Assign
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($user_role === 'Staff' && $request['status'] !== 'Completed'): ?>
                                            <button class="btn btn-update"
                                                onclick="showStatusModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['price_range']); ?>')">
                                                Update
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="btn disabled">
                            <i class="fas fa-chevron-left"></i> Previous
                        </span>
                    <?php endif; ?>

                    <span class="page-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="btn disabled">
                            Next <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
                        <div class="form-group" style="margin-top: 15px;">
                            <label for="staff_comment">Comment (optional):</label>
                            <textarea name="staff_comment" id="staff_comment" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-update"
                        id="updateStatusBtn">Update</button>
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
            function showImage(src) {
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.style.display = 'flex';
                modal.innerHTML = `
                <div class="modal-content">
                    <span class="close-modal" onclick="this.parentElement.parentElement.remove()">&times;</span>
                    <img src="${src}" style="max-width: 100%; height: auto;">
                </div>
            `;
                document.body.appendChild(modal);
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

                // Create modal content with improved structure and icons
                const content = `
                <div class="request-details-content">
                    <span class="close" onclick="closeRequestDetails()">&times;</span>
                    <h2><i class="fas fa-clipboard-list"></i> Request Details</h2>
                    
                    <div class="details-section">
                        <h3><i class="fas fa-film"></i> Media Specifications</h3>
                        <div class="details-grid">
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-photo-video"></i>
                                    Media Type
                                </span>
                                <span class="details-value">${request.media_type_name || 'Not specified'}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-ruler-combined"></i>
                                    Size
                                </span>
                                <span class="details-value">${request.size || 'Not specified'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="details-section">
                        <h3><i class="fas fa-palette"></i> Design Specifications</h3>
                        <div class="details-grid">
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-compass"></i>
                                    Orientation
                                </span>
                                <span class="details-value">${request.orientation || 'Not specified'}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-paint-brush"></i>
                                    Color Scheme
                                </span>
                                <span class="details-value">${request.color_scheme || 'Not specified'}</span>
                            </div>
                            ${request.preferred_color ? `
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-fill-drip"></i>
                                    Primary Color
                                </span>
                                <span class="details-value">
                                    <span class="color-preview" style="background: ${request.preferred_color}"></span>
                                    ${request.preferred_color}
                                </span>
                            </div>
                            ` : ''}
                            ${request.secondary_color ? `
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-fill"></i>
                                    Secondary Color
                                </span>
                                <span class="details-value">
                                    <span class="color-preview" style="background: ${request.secondary_color}"></span>
                                    ${request.secondary_color}
                                </span>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="details-section">
                        <h3><i class="fas fa-info-circle"></i> Order Details</h3>
                        <div class="details-grid">
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-boxes"></i>
                                    Quantity
                                </span>
                                <span class="details-value">${request.quantity || 'Not specified'}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-tag"></i>
                                    Price Range
                                </span>
                                <span class="details-value">${request.price_range || 'Not specified'}</span>
                            </div>
                            <div class="details-item">
                                <span class="details-label">
                                    <i class="fas fa-user"></i>
                                    Customer
                                </span>
                                <span class="details-value">${request.customer_name || 'Not specified'}</span>
                            </div>
                        </div>
                    </div>

                    ${request.additional_notes ? `
                    <div class="details-section">
                        <h3><i class="fas fa-sticky-note"></i> Additional Notes</h3>
                        <div class="additional-notes">
                            <p>${request.additional_notes}</p>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;

                modal.innerHTML = content;
                modal.style.display = 'block';
            }

            function showAdditionalInfo(additionalInfo, requestId) {
                if (!additionalInfo || Object.keys(additionalInfo).length === 0) {
                    alert('No additional information available for this request');
                    return;
                }

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
                    if (value !== null && value !== undefined) {
                        infoContent += `
                        <div class="details-item">
                            <span class="details-label"><i class="fas fa-info-circle"></i> ${label}:</span>
                            <span class="details-value">${value}</span>
                </div>
            `;
                    }
                }

                // Create modal content
                const content = `
                <div class="request-details-content">
                    <span class="close" onclick="closeAdditionalInfo()">&times;</span>
                    <h2>Additional Information</h2>
                    
                    <div class="details-section">
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

            function showAssignModal(requestId) {
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.style.display = 'flex';
                modal.innerHTML = `
                <div class="modal-content">
                    <span class="close-modal" onclick="this.parentElement.parentElement.remove()">&times;</span>
                    <h3>Assign Request</h3>
                    <form method="POST">
                        <input type="hidden" name="request_id" value="${requestId}">
                        <input type="hidden" name="assign_request" value="1">
                        <select name="staff_id" required>
                            <option value="">Select Staff Member</option>
                            <?php foreach ($staff_members as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>">
                                    <?php echo htmlspecialchars($staff['name']); ?> 
                                    (<?php echo $staff['active_count']; ?> active requests)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-assign">Assign</button>
                    </form>
                </div>
            `;
                document.body.appendChild(modal);
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

                // Check if this is a revision
                fetch(`check_revision.php?request_id=${requestId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('is_revision').value = data.is_revision;
                    })
                    .catch(error => {
                        console.error('Error checking revision:', error);
                    });
            }

            function toggleFinalDesignUpload(selectElement) {
                const uploadField = document.getElementById('finalDesignUpload');
                const priceInputGroup = document.getElementById('priceInputGroup');
                const isRevision = document.getElementById('is_revision').value === 'true';

                uploadField.style.display = selectElement.value === 'Completed' ? 'block' : 'none';

                // Show price input only if it's not a revision
                if (selectElement.value === 'Completed' && !isRevision) {
                    priceInputGroup.style.display = 'block';
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
                } else {
                    priceInputGroup.style.display = 'none';
                }
            }

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

                    const finalDesign = document.getElementById('final_design');
                    if (!finalDesign.files || finalDesign.files.length === 0) {
                        e.preventDefault();
                        alert('Please upload a final design file.');
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

            function closeRequestDetails() {
                const modal = document.getElementById('requestDetailsModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                    // Reset form if it's the status modal
                    if (modalId === 'statusModal') {
                        document.getElementById('statusForm').reset();
                        document.getElementById('priceError').style.display = 'none';
                        document.getElementById('finalDesignUpload').style.display = 'none';
                    }
                }
            }

            // Close modal when clicking outside
            window.onclick = function (event) {
                const modals = ['imageModal', 'assignModal', 'statusModal', 'feedbackModal', 'requestDetailsModal', 'additionalInfoModal'];

                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (event.target === modal) {
                        closeModal(modalId);
                    }
                });
            };

            // Add escape key listener for closing modals
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    const modals = ['imageModal', 'assignModal', 'statusModal', 'feedbackModal', 'requestDetailsModal', 'additionalInfoModal'];

                    modals.forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (modal && modal.style.display === 'block') {
                            closeModal(modalId);
                        }
                    });
                }
            });

            // Add this new function for toggling the color legend
            function toggleColorLegend() {
                const legend = document.getElementById('colorLegend');
                legend.classList.toggle('show');
            }

            // Close color legend when clicking outside
            document.addEventListener('click', function (event) {
                const legend = document.getElementById('colorLegend');
                const infoIcon = document.querySelector('.color-legend-info');

                if (!legend.contains(event.target) && !infoIcon.contains(event.target)) {
                    legend.classList.remove('show');
                }
            });
        </script>

        <?php include('../includes/footer.php'); ?>
</body>

</html>