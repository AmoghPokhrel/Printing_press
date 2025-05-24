<?php
session_start();

// Default page title
$pageTitle = 'Category Setup';

include '../includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Initialize messages
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        handleAddCategory($conn);
    } elseif (isset($_POST['update_category'])) {
        handleUpdateCategory($conn);
    }

    header("Location: cat_setup.php");
    exit();
}

// Handle delete request
if (isset($_GET['delete'])) {
    handleDeleteCategory($conn);
    header("Location: cat_setup.php");
    exit();
}

// Load existing categories
$catSetupCategories = loadCategories($conn);

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = ($page == 1) ? 6 : 8;
$total_categories = count($catSetupCategories);
if ($total_categories <= 6) {
    $total_pages = 1;
} else {
    $total_pages = 1 + ceil(($total_categories - 6) / 8);
}
if ($page == 1) {
    $start = 0;
    $length = 6;
} else {
    $start = 6 + ($page - 2) * 8;
    $length = 8;
}
$paginated_categories = array_slice($catSetupCategories, $start, $length);

// Edit mode check
$edit_mode = false;
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_category = getCategoryById($conn, (int) $_GET['edit']);
    $edit_mode = ($edit_category !== null);
}

// Helper Functions
function handleAddCategory($conn)
{
    $name = $conn->real_escape_string(trim($_POST['c_name'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['c_discription'] ?? ''));

    if (empty($name)) {
        $_SESSION['error'] = "Category name is required!";
        return;
    }

    // Check for duplicate
    $stmt = $conn->prepare("SELECT c_id FROM category WHERE c_Name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Category already exists!";
        $stmt->close();
        return;
    }
    $stmt->close();

    // Get admin_id from admin table using user_id
    $admin_query = "SELECT id FROM admin WHERE user_id = ?";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->bind_param("i", $_SESSION['user_id']);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();

    if ($admin_row = $admin_result->fetch_assoc()) {
        $admin_id = $admin_row['id'];

        // Insert new category with admin_id
        $stmt = $conn->prepare("INSERT INTO category (c_Name, c_discription, admin_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $description, $admin_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Category added successfully!";
        } else {
            $_SESSION['error'] = "Error adding category: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error: Could not find admin record for the current user.";
    }
    $admin_stmt->close();
}

function handleUpdateCategory($conn)
{
    $id = (int) ($_POST['c_id'] ?? 0);
    $name = $conn->real_escape_string(trim($_POST['c_name'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['c_discription'] ?? ''));

    if (empty($name) || $id === 0) {
        $_SESSION['error'] = "Category name and ID are required!";
        return;
    }

    $stmt = $conn->prepare("UPDATE category SET c_Name = ?, c_discription = ? WHERE c_id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Category updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating category: " . $conn->error;
    }
    $stmt->close();
}

function handleDeleteCategory($conn)
{
    $id = (int) ($_GET['delete'] ?? 0);

    if ($id === 0) {
        $_SESSION['error'] = "Invalid category ID!";
        return;
    }

    $stmt = $conn->prepare("DELETE FROM category WHERE c_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Category deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting category: " . $conn->error;
    }
    $stmt->close();
}

function loadCategories($conn)
{
    $categories = [];
    $sql = "SELECT c_id, c_Name, c_discription, created_at, admin_id FROM category ORDER BY c_id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

function getCategoryById($conn, $id)
{
    $stmt = $conn->prepare("SELECT * FROM category WHERE c_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    return $category;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px;
        }

        .add-category-btn {
            background-color: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-category-btn:hover {
            background-color: #27ae60;
        }

        .add-category-btn i {
            font-size: 18px;
        }

        .category-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: none;
            max-width: 600px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .category-form.show {
            display: block;
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
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #2ecc71;
            outline: none;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .button {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #2ecc71;
            color: white;
        }

        .btn-primary:hover {
            background-color: #27ae60;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .category-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .category-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .category-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .category-actions {
            display: flex;
            gap: 8px;
        }

        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }
        }

        .table-responsive {
            margin-bottom: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
            padding: 20px;
        }

        .category-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 0;
            background: transparent;
            border-radius: 8px;
            box-shadow: none;
            overflow: hidden;
        }

        .category-table th,
        .category-table td {
            padding: 16px 14px;
            text-align: left;
        }

        .category-table th {
            background: #f4f8fb;
            font-weight: bold;
            color: #222;
            border-bottom: 2px solid #e0e0e0;
        }

        .category-table tbody tr {
            transition: background 0.2s;
        }

        .category-table tbody tr:nth-child(even) {
            background: #f9fbfc;
        }

        .category-table tbody tr:hover {
            background: #eafaf1;
        }

        .category-table tr:last-child td {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .action-buttons .button {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .action-buttons .button i {
            font-size: 12px;
        }

        .action-buttons .btn-secondary {
            background-color: #3498db;
            color: white;
        }

        .action-buttons .btn-secondary:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .action-buttons .btn-primary {
            background-color: #2ecc71;
            color: white;
        }

        .action-buttons .btn-primary:hover {
            background-color: #27ae60;
            transform: translateY(-1px);
        }

        .action-buttons .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .action-buttons .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }

        /* Update table styles for better button display */
        .category-table td {
            vertical-align: middle;
        }

        .category-table td:last-child {
            min-width: 280px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }

            .action-buttons .button {
                width: 100%;
                justify-content: center;
            }

            .category-table td:last-child {
                min-width: auto;
            }
        }

        .btn-info {
            background-color: #3498db !important;
            color: #fff !important;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-info:hover {
            background-color: #217dbb !important;
            color: #fff !important;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="page-header">
            <?php if ($_SESSION['role'] === 'Admin' && $page == 1): ?>
                <button class="add-category-btn" id="toggleButton" onclick="toggleForm()">
                    <i class="fas fa-plus"></i> Add New Category
                </button>
            <?php endif; ?>
        </div>

        <div class="container">

            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="category-form" id="categoryForm" style="display: none;">
                <h2><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h2>
                <form method="POST" action="cat_setup.php">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="c_id" value="<?php echo htmlspecialchars($edit_category['c_id']); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="c_name">Category Name:</label>
                        <input type="text" id="c_name" name="c_name" class="form-control"
                            value="<?php echo $edit_mode ? htmlspecialchars($edit_category['c_Name']) : ''; ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="c_discription">Description:</label>
                        <textarea id="c_discription" name="c_discription" class="form-control" rows="4"><?php
                        echo $edit_mode ? htmlspecialchars($edit_category['c_discription']) : '';
                        ?></textarea>
                    </div>
                    <div class="button-group">
                        <?php if ($edit_mode): ?>
                            <button type="submit" name="update_category" class="button btn-primary">Update Category</button>
                            <a href="cat_setup.php" class="button btn-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_category" class="button btn-primary">Add Category</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (!($edit_mode || (isset($_GET['showForm']) && $_GET['showForm'] == '1'))): ?>
                <h2>Existing Categories</h2>
                <div class="table-responsive" id="categoriesTable">
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
                                    <th>Admin Name</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paginated_categories)): ?>
                                <tr>
                                    <td colspan="3">No categories found. Please add some categories.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($paginated_categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['c_Name']); ?></td>
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
                                            <td>
                                                <?php
                                                $adminName = 'Unknown';
                                                if (!empty($category['admin_id'])) {
                                                    $adminId = (int) $category['admin_id'];
                                                    $adminQ = $conn->prepare("SELECT user_id FROM admin WHERE id = ? LIMIT 1");
                                                    $adminQ->bind_param('i', $adminId);
                                                    $adminQ->execute();
                                                    $adminResult = $adminQ->get_result();
                                                    if ($adminRow = $adminResult->fetch_assoc()) {
                                                        $userId = $adminRow['user_id'];
                                                        $userQ = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
                                                        $userQ->bind_param('i', $userId);
                                                        $userQ->execute();
                                                        $userResult = $userQ->get_result();
                                                        if ($userRow = $userResult->fetch_assoc()) {
                                                            $adminName = htmlspecialchars($userRow['name']);
                                                        }
                                                        $userQ->close();
                                                    }
                                                    $adminQ->close();
                                                }
                                                echo $adminName;
                                                ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="action-buttons">
                                            <a href="additional_info_setup.php?category_id=<?php echo $category['c_id']; ?>"
                                                class="button btn-secondary">
                                                <i class="fas fa-info-circle"></i> Additional Info
                                            </a>
                                            <a href="size_setup.php?category_id=<?php echo $category['c_id']; ?>"
                                                class="button btn-secondary">
                                                <i class="fas fa-ruler"></i> Size Setup
                                            </a>
                                            <a href="cat_setup.php?edit=<?php echo $category['c_id']; ?>"
                                                class="button btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="cat_setup.php?delete=<?php echo $category['c_id']; ?>"
                                                class="button btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this category?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('categoryForm');
            const button = document.getElementById('toggleButton');
            const table = document.getElementById('categoriesTable');

            if (form.style.display === 'none') {
                form.style.display = 'block';
                button.innerHTML = '<i class="fas fa-arrow-left"></i> Back';
                button.style.backgroundColor = '#e74c3c'; // Red color for back button
                if (table) table.style.display = 'none';
            } else {
                form.style.display = 'none';
                button.innerHTML = '<i class="fas fa-plus"></i> Add New Category';
                button.style.backgroundColor = '#2ecc71'; // Green color for add button
                if (table) table.style.display = 'block';
            }
        }

        // Show form if in edit mode
        <?php if ($edit_mode): ?>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('categoryForm');
                const button = document.getElementById('toggleButton');
                const table = document.getElementById('categoriesTable');
                if (form) {
                    form.style.display = 'block';
                    button.innerHTML = '<i class="fas fa-arrow-left"></i> Back';
                    button.style.backgroundColor = '#e74c3c';
                    if (table) table.style.display = 'none';
                }
            });
        <?php endif; ?>
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>
<?php $conn->close(); ?>