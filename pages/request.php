<?php
session_start();

$pageTitle = 'Request';
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo '<script>alert("You need to log in first"); window.location.href = "login.php";</script>';
    exit();
}

$staff_id = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['status'];
    $fileUploaded = false;
    $fileName = null;

    if ($new_status === 'Completed') {
        if (!isset($_FILES['design_image']) || $_FILES['design_image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = "Please upload a design image when marking as Completed";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        $uploadDir = '../uploads/design_catalog/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['design_image']['name']);
        $targetPath = $uploadDir . $fileName;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['design_image']['type'], $allowedTypes)) {
            $_SESSION['error_message'] = "Only JPG, PNG, and GIF files are allowed";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        if (move_uploaded_file($_FILES['design_image']['tmp_name'], $targetPath)) {
            $fileUploaded = true;

            $check_query = "SELECT id FROM design_catalog WHERE staff_id = ? AND request_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $staff_id, $request_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc();

            if ($exists) {
                $update_query = "UPDATE design_catalog SET image_path = ? WHERE staff_id = ? AND request_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sii", $fileName, $staff_id, $request_id);
                $update_stmt->execute();
            } else {
                $insert_query = "INSERT INTO design_catalog (staff_id, request_id, image_path) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iis", $staff_id, $request_id, $fileName);
                $insert_stmt->execute();
            }
        } else {
            $_SESSION['error_message'] = "Error uploading file";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    $update_query = "UPDATE design_skeleton SET status = ? WHERE id = ? AND staff_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $new_status, $request_id, $staff_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Status updated successfully!" . ($fileUploaded ? " Design uploaded." : "");
    } else {
        $_SESSION['error_message'] = "Error updating status: " . $conn->error;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reupload'])) {
    $request_id = intval($_POST['request_id']);
    $description = $_POST['description'] ?? '';

    if (isset($_FILES['reupload_image']) && $_FILES['reupload_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/design_catalog/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['reupload_image']['name']);
        $targetPath = $uploadDir . $fileName;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['reupload_image']['type'], $allowedTypes)) {
            $_SESSION['error_message'] = "Only JPG, PNG, and GIF files are allowed.";
        } elseif (move_uploaded_file($_FILES['reupload_image']['tmp_name'], $targetPath)) {
            $get_query = "SELECT * FROM design_catalog WHERE staff_id = ? AND request_id = ?";
            $get_stmt = $conn->prepare($get_query);
            $get_stmt->bind_param("ii", $staff_id, $request_id);
            $get_stmt->execute();
            $catalog_entry = $get_stmt->get_result()->fetch_assoc();

            if ($catalog_entry) {
                if (!empty($catalog_entry['image_path']) && file_exists($uploadDir . $catalog_entry['image_path'])) {
                    unlink($uploadDir . $catalog_entry['image_path']);
                }

                $update_query = "UPDATE design_catalog 
                               SET image_path = ?, 
                                   description = ?,
                                   upload_count = upload_count + 1,
                                   satisfied = NULL
                               WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssi", $fileName, $description, $catalog_entry['id']);

                if ($update_stmt->execute()) {
                    $_SESSION['success_message'] = "Design reuploaded successfully!";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $_SESSION['error_message'] = "Error updating design: " . $conn->error;
                }
            } else {
                $_SESSION['error_message'] = "No design catalog entry found to update";
            }
        } else {
            $_SESSION['error_message'] = "Error uploading file";
        }
    } else {
        $_SESSION['error_message'] = "Please select a valid file to reupload";
    }
}

$query = "SELECT ds.*, 
                 c.c_Name AS category_name,
                 mt.name AS media_type_name,
                 dc.satisfied AS customer_satisfied,
                 dc.description AS customer_feedback,
                 dc.image_path AS final_design_path,
                 dc.upload_count
          FROM design_skeleton ds
          LEFT JOIN category c ON ds.catid = c.c_id
          LEFT JOIN media_type mt ON ds.mtid = mt.id
          LEFT JOIN design_catalog dc ON dc.staff_id = ds.staff_id AND dc.request_id = ds.id
          WHERE ds.staff_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .request-container {
            margin: 20px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .request-item {
            margin-bottom: 30px;
            padding: 20px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .request-image-container {
            margin-bottom: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .request-image {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #eee;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .request-image:hover {
            transform: scale(1.03);
        }

        .request-details {
            text-align: center;
            font-size: 1.1em;
        }

        .request-detail {
            margin: 10px 0;
            padding: 8px 15px;
            background-color: #f0f0f0;
            border-radius: 4px;
            display: inline-block;
        }

        .no-requests {
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 1.2em;
        }

        h3 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            margin: auto;
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 80vw;
            max-height: 80vh;
            transition: all 0.3s ease;
            animation: zoomIn 0.3s;
        }

        @keyframes zoomIn {
            from {
                transform: translate(-50%, -50%) scale(0.7);
                opacity: 0;
            }

            to {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
        }

        .modal-content.zoomed {
            max-width: 95vw;
            max-height: 95vh;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            z-index: 1001;
        }

        .close:hover {
            color: #bbb;
        }

        .modal-controls {
            position: fixed;
            bottom: 20px;
            left: 0;
            width: 100%;
            text-align: center;
            z-index: 1001;
        }

        .modal-btn {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .modal-btn:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .status-form {
            margin-top: 15px;
        }

        .status-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 1em;
            margin-right: 10px;
        }

        .status-btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .status-btn:hover {
            background-color: #45a049;
        }

        .status-pending {
            color: #e67e22;
        }

        .status-inprogress {
            color: #3498db;
        }

        .status-completed {
            color: #2ecc71;
        }

        .satisfaction-status {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            background-color: #f8f9fa;
            text-align: center;
            font-size: 1em;
        }

        .satisfaction-yes {
            color: #28a745;
            font-weight: bold;
        }

        .satisfaction-no {
            color: #dc3545;
            font-weight: bold;
        }

        .satisfaction-pending {
            color: #6c757d;
            font-style: italic;
        }

        .customer-feedback {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff3cd;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
        }

        .upload-count {
            margin-top: 10px;
            font-size: 0.9em;
            color: #6c757d;
        }

        .reupload-form {
            margin-top: 15px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            width: 100%;
            max-width: 500px;
        }

        .reupload-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .reupload-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            min-height: 80px;
        }

        .reupload-btn {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .reupload-btn:hover {
            background-color: #138496;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <h2><?php echo $pageTitle; ?></h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div id="success-message"
                    style="padding: 10px; background-color: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div id="error-message"
                    style="padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="request-container">
                <?php if ($result->num_rows > 0): ?>
                    <h3>Your Design Requests</h3>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="request-item" id="request-item-<?php echo $row['id']; ?>">
                            <?php if (!empty($row['image_path'])): ?>
                                <div class="request-image-container"
                                    onclick="openModal('<?php echo htmlspecialchars($row['image_path']); ?>')">
                                    <img src="../uploads/custom_templates/<?php echo htmlspecialchars($row['image_path']); ?>"
                                        alt="Design reference image" class="request-image">
                                </div>
                            <?php endif; ?>

                            <div class="request-details">
                                <div class="request-detail">
                                    <strong>Category:</strong>
                                    <?php echo htmlspecialchars($row['category_name'] ?? 'Not specified'); ?>
                                </div>
                                <div class="request-detail">
                                    <strong>Media Type:</strong>
                                    <?php echo htmlspecialchars($row['media_type_name'] ?? 'Not specified'); ?>
                                </div>
                                <div class="request-detail">
                                    <strong>Current Status:</strong>
                                    <span class="status-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <form class="status-form" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                <select name="status" class="status-select" required
                                    onchange="toggleUploadField(this, '<?php echo $row['id']; ?>')">
                                    <option value="Pending" <?php echo $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending
                                    </option>
                                    <option value="In Progress" <?php echo $row['status'] === 'In Progress' ? 'selected' : ''; ?>>
                                        In Progress</option>
                                    <option value="Completed" <?php echo $row['status'] === 'Completed' ? 'selected' : ''; ?>>
                                        Completed</option>
                                </select>
                                <div id="upload-field-<?php echo $row['id']; ?>" style="display: none; margin-top: 10px;">
                                    <input type="file" name="design_image" accept="image/*" required>
                                </div>
                                <button type="submit" name="update_status" class="status-btn">Update Status</button>
                            </form>

                            <div class="satisfaction-status">
                                <strong>Customer Satisfied Status:</strong>
                                <?php if ($row['customer_satisfied'] === 'yes'): ?>
                                    <span class="satisfaction-yes">Yes</span>
                                <?php elseif ($row['customer_satisfied'] === 'no'): ?>
                                    <span class="satisfaction-no">No</span>
                                <?php else: ?>
                                    <span class="satisfaction-pending">Not yet rated</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($row['customer_satisfied'] === 'no'): ?>
                                <div class="customer-feedback">
                                    <strong>Customer Feedback:</strong>
                                    <p><?php echo !empty($row['customer_feedback']) ? htmlspecialchars($row['customer_feedback']) : 'No additional feedback provided'; ?>
                                    </p>
                                </div>

                                <div class="upload-count">
                                    <strong>Reupload Count:</strong> <?php echo $row['upload_count'] ?? 0; ?>
                                </div>

                                <form class="reupload-form" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <label for="description">Update Description:</label>
                                    <textarea name="description"
                                        placeholder="Enter any additional information about the changes"><?php echo !empty($row['customer_feedback']) ? htmlspecialchars($row['customer_feedback']) : ''; ?></textarea>
                                    <label for="reupload_image">Reupload Design:</label>
                                    <input type="file" name="reupload_image" accept="image/*" required>
                                    <button type="submit" name="reupload" class="reupload-btn">Reupload Design</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-requests">You currently have no design requests.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
        <div class="modal-controls">
            <button class="modal-btn" onclick="zoomImage()">Zoom In</button>
            <button class="modal-btn" onclick="resetZoom()">Reset</button>
        </div>
    </div>

    <script>
        let currentZoom = 1;
        const maxZoom = 2;
        const zoomStep = 0.25;

        function openModal(imagePath) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = "block";
            modalImg.src = "../uploads/custom_templates/" + imagePath;
            currentZoom = 1;
            resetZoom();
            modal.onclick = function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            }
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
            currentZoom = 1;
        }

        function zoomImage() {
            const modalImg = document.getElementById('modalImage');
            if (currentZoom < maxZoom) {
                currentZoom += zoomStep;
                modalImg.style.transform = `translate(-50%, -50%) scale(${currentZoom})`;
            }
        }

        function resetZoom() {
            const modalImg = document.getElementById('modalImage');
            currentZoom = 1;
            modalImg.style.transform = 'translate(-50%, -50%) scale(1)';
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        setTimeout(function () {
            const success = document.getElementById('success-message');
            if (success) success.style.display = 'none';
            const error = document.getElementById('error-message');
            if (error) error.style.display = 'none';
        }, 3000);

        function toggleUploadField(selectElement, requestId) {
            const uploadField = document.getElementById('upload-field-' + requestId);
            uploadField.style.display = selectElement.value === 'Completed' ? 'block' : 'none';
        }
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>