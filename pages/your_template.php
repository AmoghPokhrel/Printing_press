<?php
session_start();
require_once('../includes/db.php');

// Check authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Get staff ID from staff table
$user_id = $_SESSION['user_id'];
$staffQuery = "SELECT id FROM staff WHERE user_id = $user_id";
$staffResult = $conn->query($staffQuery);

if (!$staffResult || $staffResult->num_rows === 0) {
    header('Location: login.php');
    exit();
}

$staff = $staffResult->fetch_assoc();
$staff_id = $staff['id'];

// Get all templates for this staff member with additional details
$templates = [];
$query = "SELECT t.*, c.c_Name as category_name, m.name as media_type_name, u.name as staff_name 
          FROM templates t
          JOIN category c ON t.c_id = c.c_id
          JOIN media_type m ON t.media_type_id = m.id
          JOIN staff s ON t.staff_id = s.id
          JOIN users u ON s.user_id = u.id
          WHERE t.staff_id = $staff_id
          ORDER BY t.created_at DESC";
$result = $conn->query($query);

if ($result) {
    $templates = $result->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'Your Templates';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
        }

        .templates-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .template-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .template-card:hover {
            transform: translateY(-5px);
        }

        .template-image-container {
            height: 180px;
            overflow: hidden;
        }

        .template-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .template-info {
            padding: 15px;
        }

        .template-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .template-category {
            color: #666;
            margin-bottom: 10px;
        }

        .template-actions {
            display: flex;
            justify-content: space-between;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-view {
            background-color: #3498db;
            color: white;
        }

        .btn-edit {
            background-color: #f39c12;
            color: white;
        }

        .no-templates {
            text-align: center;
            padding: 40px;
            grid-column: 1 / -1;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 60%;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }

        .detail-value {
            flex: 1;
        }

        .modal-image {
            max-width: 100%;
            max-height: 300px;
            display: block;
            margin: 0 auto 20px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="page-header">
            <h2><?php echo $pageTitle; ?></h2>
            <a href="template_setup.php" class="btn btn-primary">+ Add New Template</a>
        </div>

        <div class="templates-container">
            <?php if (!empty($templates)): ?>
                <?php foreach ($templates as $template): ?>
                    <div class="template-card">
                        <div class="template-image-container">
                            <?php if (!empty($template['image_path'])): ?>
                                <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                                    class="template-image">
                            <?php else: ?>
                                <div
                                    style="height: 100%; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                    <span>No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="template-info">
                            <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                            <div class="template-category"><?php echo htmlspecialchars($template['category_name']); ?></div>
                            <div class="template-actions">
                                <button class="btn btn-view" onclick="showTemplateDetails(
                                    '<?php echo htmlspecialchars($template['name'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($template['cost']); ?>',
                                    '<?php echo htmlspecialchars($template['category_name'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($template['media_type_name'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($template['staff_name'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($template['created_at']); ?>',
                                    '<?php echo !empty($template['image_path']) ? '../uploads/templates/' . htmlspecialchars($template['image_path']) : 'https://via.placeholder.com/600x400?text=No+Image'; ?>'
                                )">View</button>
                                <a href="edit_template.php?id=<?php echo $template['id']; ?>" class="btn btn-edit">Edit</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-templates">
                    <p style="padding-bottom: 25px;">You haven't created any templates yet.</p>
                    <a href="template_setup.php" class="btn btn-primary">Create your first template</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Template Details -->
    <div id="templateModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img id="modalTemplateImage" src="" alt="Template Image" class="modal-image">

            <div class="detail-row">
                <div class="detail-label">Template Name:</div>
                <div class="detail-value" id="modalTemplateName"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Category:</div>
                <div class="detail-value" id="modalTemplateCategory"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Cost:</div>
                <div class="detail-value" id="modalTemplatePrice"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Media Type:</div>
                <div class="detail-value" id="modalTemplateMediaType"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Created By:</div>
                <div class="detail-value" id="modalTemplateStaff"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Created Date:</div>
                <div class="detail-value" id="modalTemplateCreatedAt"></div>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Get the modal element
        const modal = document.getElementById('templateModal');
        const span = document.getElementsByClassName('close')[0];

        // Function to show template details in modal
        function showTemplateDetails(name, cost, category, mediaType, staff, createdAt, imagePath) {
            document.getElementById('modalTemplateName').textContent = name;
            document.getElementById('modalTemplateCategory').textContent = category;
            document.getElementById('modalTemplatePrice').textContent = '$' + parseFloat(cost).toFixed(2);
            document.getElementById('modalTemplateMediaType').textContent = mediaType;
            document.getElementById('modalTemplateStaff').textContent = staff;
            document.getElementById('modalTemplateCreatedAt').textContent = createdAt;

            const imgElement = document.getElementById('modalTemplateImage');
            imgElement.src = imagePath;
            imgElement.alt = name;

            // Display the modal
            modal.style.display = 'block';
        }

        // Close the modal when clicking the X
        span.onclick = function () {
            modal.style.display = 'none';
        }

        // Close the modal when clicking outside of it
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>