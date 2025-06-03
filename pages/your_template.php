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

        /* Modal wrapper to ensure proper stacking */
        .modal-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 99999;
            pointer-events: none;
        }

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: auto;
        }

        .modal.show {
            opacity: 1;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 3% auto;
            padding: 0;
            border-radius: 12px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal-header {
            background-color: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h2 {
            margin: 0;
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close {
            color: #718096;
            font-size: 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #fff;
            border: 1px solid #e2e8f0;
        }

        .close:hover {
            background-color: #f7fafc;
            color: #2d3748;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .modal-image-container {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .modal-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .modal-image:hover {
            transform: scale(1.02);
        }

        .details-container {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-row:hover {
            background-color: #fff;
            border-radius: 6px;
        }

        .detail-label {
            font-weight: 600;
            width: 180px;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .detail-value {
            flex: 1;
            color: #2d3748;
            font-size: 0.95rem;
        }

        /* Scrollbar styling */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 20px auto;
            }

            .modal-image-container {
                height: 300px;
            }

            .detail-row {
                flex-direction: column;
            }

            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="page-header">
            <!-- <h2><?php echo $pageTitle; ?></h2> -->
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

    <!-- Modal wrapper and Modal for Template Details -->
    <div class="modal-wrapper">
        <div id="templateModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTemplateName"></h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="modal-image-container">
                        <img id="modalTemplateImage" src="" alt="Template Image" class="modal-image">
                    </div>
                    <div class="details-container">
                        <div class="detail-row">
                            <div class="detail-label">Category:</div>
                            <div class="detail-value" id="modalTemplateCategory"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Cost:</div>
                            <div class="detail-value" id="modalTemplateCost"></div>
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
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Get the modal
        var modal = document.getElementById('templateModal');
        var span = document.getElementsByClassName('close')[0];

        function showTemplateDetails(name, cost, category, mediaType, staff, createdAt, imagePath) {
            // Set the content
            document.getElementById('modalTemplateName').textContent = name;
            document.getElementById('modalTemplateCategory').textContent = category;
            document.getElementById('modalTemplateCost').textContent = 'Rs' + parseFloat(cost).toFixed(2);
            document.getElementById('modalTemplateMediaType').textContent = mediaType;
            document.getElementById('modalTemplateStaff').textContent = staff;
            document.getElementById('modalTemplateCreatedAt').textContent = createdAt;
            document.getElementById('modalTemplateImage').src = imagePath;

            // Show the modal with animation
            modal.style.display = 'block';
            requestAnimationFrame(() => {
                modal.classList.add('show');
            });

            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }

        // Update the close functions
        span.onclick = function () {
            closeModal();
        }

        window.onclick = function (event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        function closeModal() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
    </script>
</body>

</html>