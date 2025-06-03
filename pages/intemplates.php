<?php
session_start();

// Default page title
$pageTitle = 'Templates';

include '../includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    // Store the requested URL before redirecting to login
    if (isset($_GET['category_id'])) {
        $_SESSION['redirect_after_login'] = 'intemplates.php?category_id=' . $_GET['category_id'];
    }
    echo '<script>alert("You need to log in first"); window.location.href = "login.php";</script>';
    exit();
}

// First, fetch all categories for header and filtering
$categories = [];
$category_query = "SELECT c_id, c_Name FROM category ORDER BY c_Name ASC";
$category_result = $conn->query($category_query);
if ($category_result) {
    $categories = $category_result->fetch_all(MYSQLI_ASSOC);
}

// Check user role
$user_id = $_SESSION['user_id'];
$role_query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($role_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$role_result = $stmt->get_result();
$user_role = $role_result->fetch_assoc()['role'] ?? 'Customer';

// Handle add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $template_id = intval($_POST['template_id']);
    $user_id = $_SESSION['user_id'];
    $current_category = isset($_POST['current_category']) ? $_POST['current_category'] : 'all';

    // Check if item already exists in cart
    $check_query = "SELECT id FROM cart WHERE tid = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $template_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Item exists, update quantity
        $update_query = "UPDATE cart SET quantity = quantity + 1 WHERE tid = ? AND user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $template_id, $user_id);
        $stmt->execute();
    } else {
        // Item doesn't exist, insert new record
        $insert_query = "INSERT INTO cart (tid, user_id, quantity) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $template_id, $user_id);
        $stmt->execute();
    }

    // Set success message
    $_SESSION['success_message'] = "Item successfully added to cart!";

    // Redirect back to the same filtered view
    header("Location: intemplates.php?category=" . urlencode($current_category));
    exit();
}

// Handle edit template action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_template'])) {
    $template_id = intval($_POST['template_id']);
    $current_category = isset($_POST['current_category']) ? $_POST['current_category'] : 'all';

    // Redirect to edit page
    header("Location: edit_template.php?id=" . $template_id . "&category=" . urlencode($current_category));
    exit();
}

// Handle select template action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_template'])) {
    $template_id = intval($_POST['template_id']);
    header("Location: template_modification.php?template_id=" . $template_id);
    exit();
}

// Get category filter from URL (accepts both 'category' and 'category_id')
$category_filter = isset($_GET['category']) ? $_GET['category'] : (isset($_GET['category_id']) ? $_GET['category_id'] : 'all');

// Fetch templates from database with filtering
$templates = [];
$query = "SELECT t.*, c.c_Name as category_name, m.name as media_type_name, u.name as staff_name 
          FROM templates t
          JOIN category c ON t.c_id = c.c_id
          JOIN media_type m ON t.media_type_id = m.id
          JOIN staff s ON t.staff_id = s.id
          JOIN users u ON s.user_id = u.id";

// Add category filter if not 'all'
if ($category_filter !== 'all') {
    $category_id = intval($category_filter);
    $query .= " WHERE t.c_id = $category_id";

    // Update page title to show filtered category
    $current_category_name = 'Unknown Category';
    foreach ($categories as $category) {
        if ($category['c_id'] == $category_id) {
            $current_category_name = $category['c_Name'];
            break;
        }
    }
    $pageTitle = 'Templates - ' . htmlspecialchars($current_category_name);
}

// Only show active templates to customers
if ($user_role === 'Customer') {
    $query .= ($category_filter !== 'all' ? " AND" : " WHERE") . " t.status = 'active'";
}

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $templates = $result->fetch_all(MYSQLI_ASSOC);
}

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 8;
$total_templates = count($templates); // assuming $templates is the array of cards
$total_pages = ceil($total_templates / $per_page);
$start = ($page - 1) * $per_page;
$paginated_templates = array_slice($templates, $start, $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Success message styling */
        .alert-success {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: fadeIn 0.5s, fadeOut 0.5s 2.5s forwards;
            display: flex;
            align-items: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        /* Your existing styles */
        .templates-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 0 20px;
            justify-content: flex-start;
            margin-bottom: 40px;
        }

        .template-card {
            width: 280px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .template-image-container {
            height: 180px;
            overflow: hidden;
        }

        .template-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .template-info {
            padding: 15px;
        }

        .template-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .template-price {
            color: #e67e22;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .template-color-scheme {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .template-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-add-to-cart {
            background-color: #2ecc71;
            color: white;
        }

        .btn-select {
            background-color: #2ecc71;
            color: white;
        }

        .btn-details {
            background-color: #3498db;
            color: white;
        }

        .edit-template {
            background-color: #f39c12;
            color: white;
        }

        /* Modal centering and styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }

        .modal.fade.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .modal-dialog {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            top: 50%;
            transform: translateY(-50%) !important;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: none;
            position: relative;
            margin: 1rem;
            width: 100%;
        }

        .modal-header {
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: calc(100vh - 210px);
            overflow-y: auto;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        #modalTemplateImage {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .row.mb-2 {
            margin-bottom: 1rem !important;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .row.mb-2:last-child {
            border-bottom: none;
        }

        .col-4.fw-bold {
            color: #495057;
        }

        .col-8 {
            color: #2c3e50;
        }

        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
            }
        }

        /* Add these new styles */
        .status-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-active {
            background-color: #10b981;
        }

        .status-inactive {
            background-color: #ef4444;
        }

        .toggle-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .toggle-btn.active {
            background-color: #ef4444;
            color: white;
            border: none;
        }

        .toggle-btn.inactive {
            background-color: #10b981;
            color: white;
            border: none;
        }

        .status-text {
            font-weight: 500;
            color: #374151;
        }

        /* Add this new style for main content spacing */
        .main-content {
            min-height: calc(100vh - 200px);
            /* Adjust based on your footer height */
            padding-bottom: 60px;
            /* Add padding at the bottom */
        }

        .container h2 {
            margin: 20px 0 0 0;
            padding: 0 20px;
        }

        .filter-indicator {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            color: #4a5568;
            background: #f7fafc;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 15px 0;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .filter-indicator i {
            color: #3182ce;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .filter-indicator {
                font-size: 0.95rem;
                padding: 6px 12px;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <!-- Success Message Display -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success" id="successAlert">
            <?php echo $_SESSION['success_message']; ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
        <script>
            setTimeout(function () {
                document.getElementById('successAlert').remove();
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <h2>
                <?php if ($category_filter !== 'all'): ?>
                    <div class="filter-indicator">
                        <i class="fas fa-filter"></i>
                        Filtered by: <?php
                        $current_category = array_filter($categories, function ($cat) use ($category_filter) {
                            return $cat['c_id'] == $category_filter;
                        });
                        $current_category_name = current($current_category)['c_Name'] ?? 'Unknown Category';
                        echo htmlspecialchars($current_category_name);
                        ?>
                    </div>
                <?php endif; ?>
            </h2>

            <div class="templates-container">
                <?php if (!empty($paginated_templates)): ?>
                    <?php foreach ($paginated_templates as $template): ?>
                        <div class="template-card">
                            <div class="template-image-container">
                                <?php if (!empty($template['image_path'])): ?>
                                    <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                                        alt="<?php echo htmlspecialchars($template['name']); ?>" class="template-image">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/300x200?text=No+Image" alt="No image available"
                                        class="template-image">
                                <?php endif; ?>
                            </div>

                            <div class="template-info">
                                <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                                <div class="template-price">Rs <?php echo number_format($template['cost'], 2); ?></div>
                                <div class="template-color-scheme">Color Scheme:
                                    <?php echo htmlspecialchars($template['color_scheme'] ?? 'N/A'); ?>
                                </div>
                                <div class="template-actions">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                        <input type="hidden" name="current_category" value="<?php echo $category_filter; ?>">
                                        <?php if ($user_role === 'Customer'): ?>
                                            <button type="submit" name="select_template" class="btn btn-select">
                                                Select
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff'): ?>
                                            <button type="submit" name="edit_template" class="btn edit-template">
                                                Edit Template
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <button type="button" class="btn btn-details" onclick="showTemplateDetails(
                                                '<?php echo htmlspecialchars($template['name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['cost']); ?>',
                                                '<?php echo htmlspecialchars($template['category_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['media_type_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['staff_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['created_at']); ?>',
                                                '<?php echo !empty($template['image_path']) ? '../uploads/templates/' . htmlspecialchars($template['image_path']) : 'https://via.placeholder.com/600x400?text=No+Image'; ?>',
                                        '<?php echo htmlspecialchars($template['color_scheme'] ?? 'N/A', ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($template['status'], ENT_QUOTES); ?>',
                                        '<?php echo $template['id']; ?>'
                                    )">Details</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center;">
                        No templates found<?php echo $category_filter !== 'all' ? ' in this category' : ''; ?>.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bootstrap Modal -->
            <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="templateModalLabel">Template Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img id="modalTemplateImage" src="" alt="Template Image" class="img-fluid">
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Template Name:</div>
                                <div class="col-8" id="modalTemplateName"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Category:</div>
                                <div class="col-8" id="modalTemplateCategory"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Cost:</div>
                                <div class="col-8" id="modalTemplatePrice"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Media Type:</div>
                                <div class="col-8" id="modalTemplateMediaType"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Created By:</div>
                                <div class="col-8" id="modalTemplateStaff"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Created Date:</div>
                                <div class="col-8" id="modalTemplateCreatedAt"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Color Scheme:</div>
                                <div class="col-8" id="modalTemplateColorScheme"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Status:</div>
                                <div class="col-8" id="modalTemplateStatus">
                                    <?php if ($user_role === 'Admin' || $user_role === 'Super Admin'): ?>
                                        <div class="status-toggle">
                                            <span>
                                                <span class="status-indicator" id="statusIndicator"></span>
                                                <span class="status-text" id="statusText"></span>
                                            </span>
                                            <button type="button" class="toggle-btn" id="toggleStatus"
                                                onclick="toggleTemplateStatus(this)">
                                                Toggle Status
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
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

    <?php include('../includes/footer.php'); ?>

    <!-- Add Bootstrap JS and its dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>

    <script>
        let templateModal;
        let currentTemplateId;

        document.addEventListener('DOMContentLoaded', function () {
            templateModal = new bootstrap.Modal(document.getElementById('templateModal'), {
                backdrop: 'static',
                keyboard: true
            });
        });

        function updateStatusUI(status) {
            const indicator = document.getElementById('statusIndicator');
            const text = document.getElementById('statusText');
            const toggleBtn = document.getElementById('toggleStatus');

            if (status === 'active') {
                indicator.className = 'status-indicator status-active';
                text.textContent = 'Active';
                toggleBtn.textContent = 'Deactivate';
                toggleBtn.className = 'toggle-btn active';
            } else {
                indicator.className = 'status-indicator status-inactive';
                text.textContent = 'Inactive';
                toggleBtn.textContent = 'Activate';
                toggleBtn.className = 'toggle-btn inactive';
            }
        }

        function toggleTemplateStatus(button) {
            if (!currentTemplateId) return;

            // Send AJAX request to update status
            fetch('ajax/update_template_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'template_id=' + currentTemplateId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStatusUI(data.new_status);
                    } else {
                        alert('Failed to update status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update status. Please try again.');
                });
        }

        function showTemplateDetails(name, cost, category, mediaType, staff, createdAt, imagePath, colorScheme, status, templateId) {
            currentTemplateId = templateId;

            // Set the content
            document.getElementById('modalTemplateName').textContent = name;
            document.getElementById('modalTemplateCategory').textContent = category;
            document.getElementById('modalTemplatePrice').textContent = 'Rs' + parseFloat(cost).toFixed(2);
            document.getElementById('modalTemplateMediaType').textContent = mediaType;
            document.getElementById('modalTemplateStaff').textContent = staff;
            document.getElementById('modalTemplateCreatedAt').textContent = createdAt;
            document.getElementById('modalTemplateColorScheme').textContent = colorScheme;
            document.getElementById('modalTemplateImage').src = imagePath;

            // Update status UI if admin/super admin
            <?php if ($user_role === 'Admin' || $user_role === 'Super Admin'): ?>
                updateStatusUI(status);
            <?php endif; ?>

            // Show the modal
            templateModal.show();
        }
    </script>
</body>

</html>