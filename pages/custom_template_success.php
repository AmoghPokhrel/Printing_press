<?php
session_start();
require_once('../includes/dbcon.php');
$pageTitle = 'Template Request Submitted';

// Check if user is logged in and is a Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header('Location: login.php');
    exit();
}

$request_id = $_GET['request_id'] ?? 0;

// Fetch request details
$stmt = $pdo->prepare("
    SELECT 
        ctr.*,
        c.c_Name as category_name,
        mt.name as media_type_name
    FROM custom_template_requests ctr
    JOIN category c ON ctr.category_id = c.c_id
    JOIN media_type mt ON ctr.media_type_id = mt.id
    WHERE ctr.id = ? AND ctr.user_id = ?
");
$stmt->execute([$request_id, $_SESSION['user_id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: custom_template.php');
    exit();
}

// Decode JSON fields with proper checks
$template_content = !empty($request['template_content']) ? json_decode($request['template_content'], true) : [];
$contact_info = !empty($request['contact_info']) ? json_decode($request['contact_info'], true) : [];
$business_info = !empty($request['business_info']) ? json_decode($request['business_info'], true) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Request Submitted</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .success-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .request-details {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            width: 200px;
            font-weight: bold;
            color: #666;
        }

        .detail-value {
            flex: 1;
        }

        .reference-image {
            max-width: 100%;
            max-height: 300px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .action-buttons {
            margin-top: 30px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .staff-selection-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .staff-selection-info p {
            margin-bottom: 10px;
        }

        .btn-primary {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="success-container">
            <div class="success-header">
                <div class="success-icon">✓</div>
                <h2>Template Request Submitted Successfully!</h2>
                <p>Your request has been received and is being processed. We'll notify you once it's ready.</p>
            </div>

            <div class="request-details">
                <h3>Request Details</h3>

                <div class="detail-row">
                    <div class="detail-label">Request ID:</div>
                    <div class="detail-value">#<?php echo htmlspecialchars($request['id']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Category:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['category_name']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Media Type:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['media_type_name']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Size:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['size']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Orientation:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['orientation']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Color Scheme:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['color_scheme']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Quantity:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['quantity']); ?></div>
                </div>

                <?php if ($request['reference_image']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Reference Image:</div>
                        <div class="detail-value">
                            <img src="../uploads/custom_templates/<?php echo htmlspecialchars($request['reference_image']); ?>"
                                alt="Reference Image" class="reference-image">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($request['additional_notes']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Additional Notes:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($request['additional_notes']); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="staff-selection-info">
                <?php if (!empty($request['preferred_staff_id'])): ?>
                    <p>You have selected a preferred staff member for your request.</p>
                <?php else: ?>
                    <p>You can optionally select a preferred staff member for your request.</p>
                    <a href="staff_setup.php?request_id=<?php echo $request_id; ?>" class="btn btn-primary">
                        Select Preferred Staff
                    </a>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <a href="custom_template.php" class="btn btn-primary">Create Another Request</a>
                <a href="customer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>