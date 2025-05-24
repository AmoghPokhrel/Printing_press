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
            padding: 20px;
            justify-content: flex-start;
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

        .filter-indicator {
            font-size: 14px;
            color: #666;
            margin-left: 10px;
            font-weight: normal;
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

        .container {
            padding-bottom: 80px !important;
            /* Ensures pagination is visible above the fixed footer */
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
            <h2><?php echo $pageTitle; ?>
                <?php if ($category_filter !== 'all'): ?>
                    <span class="filter-indicator">
                        (Filtered by: <?php
                        $current_category = array_filter($categories, function ($cat) use ($category_filter) {
                            return $cat['c_id'] == $category_filter;
                        });
                        $current_category_name = current($current_category)['c_Name'] ?? 'Unknown Category';
                        echo htmlspecialchars($current_category_name);
                        ?>)
                    </span>
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
                                <div class="template-price">Rs<?php echo number_format($template['cost'], 2); ?></div>
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
                                    <button class="btn btn-details" onclick="showTemplateDetails(
                                                '<?php echo htmlspecialchars($template['name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['cost']); ?>',
                                                '<?php echo htmlspecialchars($template['category_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['media_type_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['staff_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($template['created_at']); ?>',
                                                '<?php echo !empty($template['image_path']) ? '../uploads/templates/' . htmlspecialchars($template['image_path']) : 'https://via.placeholder.com/600x400?text=No+Image'; ?>',
                                                '<?php echo htmlspecialchars($template['color_scheme'] ?? 'N/A', ENT_QUOTES); ?>'
                                            )">
                                        Details
                                    </button>
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
            <?php if ($total_pages > 1): ?>
                <div
                    style="text-align:center; margin: 20px 0; padding-bottom: 40px; background: #f9f9f9; border: 1px solid #eee;">
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

            <div class="detail-row">
                <div class="detail-label">Color Scheme:</div>
                <div class="detail-value" id="modalTemplateColorScheme"></div>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Get the modal element
        const modal = document.getElementById('templateModal');
        const span = document.getElementsByClassName('close')[0];

        // Function to show template details in modal
        function showTemplateDetails(name, cost, category, mediaType, staff, createdAt, imagePath, colorScheme) {
            document.getElementById('modalTemplateName').textContent = name;
            document.getElementById('modalTemplateCategory').textContent = category;
            document.getElementById('modalTemplatePrice').textContent = 'Rs' + parseFloat(cost).toFixed(2);
            document.getElementById('modalTemplateMediaType').textContent = mediaType;
            document.getElementById('modalTemplateStaff').textContent = staff;
            document.getElementById('modalTemplateCreatedAt').textContent = createdAt;
            document.getElementById('modalTemplateColorScheme').textContent = colorScheme;
            const imgElement = document.getElementById('modalTemplateImage');
            imgElement.src = imagePath;
            imgElement.alt = name;
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