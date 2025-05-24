<?php
session_start();
$pageTitle = 'Size Setup';

include '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script> window.location.href = "login.php";</script>';
    exit();
}

// Get category ID from URL
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Fetch category details
$category_query = "SELECT * FROM category WHERE c_id = ?";
$stmt = $conn->prepare($category_query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category_result = $stmt->get_result();
$category = $category_result->fetch_assoc();

if (!$category) {
    header('Location: cat_setup.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $size_name = $_POST['size_name'];

            $insert_query = "INSERT INTO sizes (category_id, size_name) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("is", $category_id, $size_name);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Size added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding size. Please try again.";
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['size_id'])) {
            $size_id = $_POST['size_id'];

            $delete_query = "DELETE FROM sizes WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $size_id);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Size deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting size. Please try again.";
            }
        }
    }
    header("Location: size_setup.php?category_id=" . $category_id);
    exit();
}

// Fetch sizes for this category
$sizes_query = "SELECT * FROM sizes WHERE category_id = ? ORDER BY size_name";
$stmt = $conn->prepare($sizes_query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$sizes_result = $stmt->get_result();
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-section,
        .table-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
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

        .btn-submit {
            background-color: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #27ae60;
        }

        .button,
        .btn-secondary,
        .btn-danger {
            padding: 10px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            margin-right: 6px;
            transition: background 0.2s;
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

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 16px;
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

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: left;
        }

        th {
            background: #f4f8fb;
            font-weight: bold;
            color: #222;
            border-bottom: 2px solid #e0e0e0;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:nth-child(even) {
            background: #f9fbfc;
        }

        tbody tr:hover {
            background: #eafaf1;
        }

        tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 8px;
            }

            th,
            td {
                padding: 10px 6px;
                font-size: 15px;
            }
        }

        .button.btn-secondary {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .button.btn-secondary:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .button.btn-secondary i {
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div style="margin: 30px;">
            <a href="cat_setup.php" class="button btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>
        <div class="container">

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="form-section">
                <h2>Add New Size</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label for="size_name">Size Name</label>
                        <input type="text" name="size_name" id="size_name" class="form-control" required>
                    </div>

                    <button type="submit" class="btn-submit">Add Size</button>
                </form>
            </div>

            <div class="table-container">
                <h2>Existing Sizes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Size Name</th>
                            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                                <th>User</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sizes_result->num_rows > 0): ?>
                            <?php while ($size = $sizes_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($size['size_name']); ?></td>
                                    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                                        <td>
                                            <?php
                                            $user_name = 'Unknown';
                                            if (!empty($size['user_id'])) {
                                                $uid = (int) $size['user_id'];
                                                $user_q = $conn->query("SELECT name FROM users WHERE id = $uid LIMIT 1");
                                                if ($user_q && $user_row = $user_q->fetch_assoc()) {
                                                    $user_name = htmlspecialchars($user_row['name']);
                                                }
                                            }
                                            echo $user_name;
                                            ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="size_id" value="<?php echo $size['id']; ?>">
                                            <button type="submit" class="btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this size?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">No sizes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
</body>

</html>