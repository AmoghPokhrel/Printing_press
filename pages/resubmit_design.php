<?php
session_start();
require_once('../includes/dbcon.php');

// Check if user is logged in and is Staff/Admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Staff', 'Admin'])) {
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];

    // Get request details and latest revision
    $stmt = $pdo->prepare("
        SELECT ctr.*, c.c_Name as category_name, mt.name as media_type_name,
        dr.revision_number, dr.feedback
        FROM custom_template_requests ctr
        JOIN category c ON ctr.category_id = c.c_id
        JOIN media_type mt ON ctr.media_type_id = mt.id
        LEFT JOIN design_revisions dr ON ctr.id = dr.request_id
        WHERE ctr.id = ?
        ORDER BY dr.revision_number DESC
        LIMIT 1
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        header('Location: manage_custom_requests.php');
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();

            // Validate file upload
            if (!isset($_FILES['new_design']) || $_FILES['new_design']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please upload a new design file.");
            }

            $upload_dir = '../uploads/custom_templates/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate new filename
            $file_extension = pathinfo($_FILES['new_design']['name'], PATHINFO_EXTENSION);
            $new_filename = 'design_' . $request_id . '_rev_' . ($request['revision_number'] + 1) . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            // Upload file
            if (!move_uploaded_file($_FILES['new_design']['tmp_name'], $upload_path)) {
                throw new Exception("Failed to upload the new design.");
            }

            // Insert new revision
            $stmt = $pdo->prepare("
                INSERT INTO design_revisions (
                    request_id, 
                    revision_number, 
                    design_file,
                    staff_comments,
                    created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $request_id,
                $request['revision_number'] + 1,
                $new_filename,
                $_POST['staff_comments']
            ]);

            // Update request status and final design
            $stmt = $pdo->prepare("
                UPDATE custom_template_requests 
                SET status = 'Completed', 
                    final_design = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$new_filename, $request_id]);

            $pdo->commit();
            $success_message = "Design has been resubmitted successfully.";

            // Redirect after successful submission
            header("Location: manage_custom_requests.php?success_message=" . urlencode($success_message));
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resubmit Design</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .resubmit-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .request-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }

        .request-details h3 {
            margin-top: 0;
            color: #333;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }

        .previous-feedback {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
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
            font-size: 16px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .submit-btn {
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #218838;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <div class="resubmit-container">
            <h2>Resubmit Design</h2>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <div class="request-details">
                <h3>Request Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Request ID:</span>
                    <span>#<?php echo htmlspecialchars($request['id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Category:</span>
                    <span><?php echo htmlspecialchars($request['category_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Media Type:</span>
                    <span><?php echo htmlspecialchars($request['media_type_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Current Revision:</span>
                    <span><?php echo htmlspecialchars($request['revision_number']); ?></span>
                </div>

                <?php if ($request['feedback']): ?>
                    <div class="previous-feedback">
                        <h4>Previous Feedback:</h4>
                        <p><?php echo htmlspecialchars($request['feedback']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="new_design">Upload New Design</label>
                    <input type="file" name="new_design" id="new_design" class="form-control" required accept="image/*">
                </div>

                <div class="form-group">
                    <label for="staff_comments">Staff Comments</label>
                    <textarea name="staff_comments" id="staff_comments" class="form-control" rows="4"
                        placeholder="Describe the changes made in this revision..."></textarea>
                </div>

                <button type="submit" class="submit-btn">Submit New Design</button>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>