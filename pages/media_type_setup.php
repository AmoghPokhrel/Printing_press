<?php
session_start();

// Default page title
$pageTitle = 'Media Type Setup';

include '../includes/dbcon.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    echo '<script>alert("You need to log in first"); window.location.href = "login.php";</script>';
    exit();
}

// Initialize variables
$editMode = false;
$currentId = null;
$currentName = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        $name = trim($_POST['name']);
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if (!empty($name)) {
            try {
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    // Update existing media type
                    $stmt = $pdo->prepare("UPDATE media_type SET name = :name WHERE id = :id");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    $success = "Media type updated successfully!";
                } else {
                    // Add new media type with admin_id
                    $admin_id = null;
                    if (isset($_SESSION['user_id'])) {
                        $adminQ = $pdo->prepare("SELECT id FROM admin WHERE user_id = :user_id LIMIT 1");
                        $adminQ->bindParam(':user_id', $_SESSION['user_id']);
                        $adminQ->execute();
                        if ($adminRow = $adminQ->fetch(PDO::FETCH_ASSOC)) {
                            $admin_id = $adminRow['id'];
                        }
                    }
                    if ($admin_id) {
                        $stmt = $pdo->prepare("INSERT INTO media_type (name, admin_id) VALUES (:name, :admin_id)");
                        $stmt->bindParam(':name', $name);
                        $stmt->bindParam(':admin_id', $admin_id);
                        $stmt->execute();
                        $success = "Media type added successfully!";
                    } else {
                        $error = "Could not find admin record for the current user.";
                    }
                }

                // Reset form after submission
                $editMode = false;
                $currentId = null;
                $currentName = '';

                // Refresh the media types list
                $stmt = $pdo->query("SELECT * FROM media_type ORDER BY created_at DESC");
                $mediaTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Redirect to clear POST data and prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Please enter a media type name";
        }
    } elseif (isset($_POST['delete'])) {
        // Delete media type
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM media_type WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $success = "Media type deleted successfully!";

            // Refresh the media types list
            $stmt = $pdo->query("SELECT * FROM media_type ORDER BY created_at DESC");
            $mediaTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Redirect to clear POST data and prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            $error = "Error deleting media type: " . $e->getMessage();
        }
    } elseif (isset($_POST['cancel'])) {
        // Cancel edit mode and redirect to clear edit param
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    }
}

// Handle edit request
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM media_type WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $mediaType = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mediaType) {
            $editMode = true;
            $currentId = $mediaType['id'];
            $currentName = $mediaType['name'];
        }
    } catch (PDOException $e) {
        $error = "Error fetching media type: " . $e->getMessage();
    }
}

// Fetch all media types
try {
    $stmt = $pdo->query("SELECT * FROM media_type ORDER BY created_at DESC");
    $mediaTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching media types: " . $e->getMessage();
    $mediaTypes = [];
}

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 5;
$total_media_types = count($mediaTypes);
$total_pages = ceil($total_media_types / $per_page);
$start = ($page - 1) * $per_page;
$paginated_media_types = array_slice($mediaTypes, $start, $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .media-type-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h3 {
            padding-bottom: 25px;
            font-weight: 500;
            color: #2d3748;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 400;
            color: #4a5568;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-weight: normal;
            font-family: inherit;
        }

        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 400;
        }

        .btn-cancel {
            background-color: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-weight: 400;
        }

        .btn-submit:hover {
            background-color: #45a049;
        }

        .btn-cancel:hover {
            background-color: #da190b;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            transition: opacity 0.5s ease-out;
        }

        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .alert-error {
            background-color: #f2dede;
            color: #a94442;
        }

        .media-types-list h3 {
            padding-left: 20px;
        }

        .media-types-table {
            width: 80%;
            border-collapse: collapse;
            margin-top: 30px;
            margin-left: 150px;
            margin-bottom: 60px;
        }

        .media-types-table th,
        .media-types-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            color: black;
        }

        .media-types-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .media-types-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background-color: #2196F3;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-edit:hover {
            background-color: #0b7dda;
        }

        .btn-delete:hover {
            background-color: #da190b;
        }

        .form-actions {
            display: flex;
        }
    </style>
</head>

<body>

    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <!-- <h2><?php echo $pageTitle; ?></h2> -->

            <!-- Media Type Form -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <div class="media-type-form">
                    <h3><?php echo $editMode ? 'Edit Media Type' : 'Add New Media Type'; ?></h3>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Media Type Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($currentName); ?>"
                                required>
                        </div>
                        <?php if ($editMode): ?>
                            <input type="hidden" name="id" value="<?php echo $currentId; ?>">
                        <?php endif; ?>
                        <button type="submit" name="submit"
                            class="btn-submit"><?php echo $editMode ? 'Update' : 'Add'; ?></button>
                        <?php if ($editMode): ?>
                            <button type="submit" name="cancel" class="btn-cancel">Cancel</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Media Types Table -->
            <div class="media-types-list">
                <h3 style="padding-top:20px; padding-bottom: 5px;">Existing Media Types</h3>

                <?php if (!empty($paginated_media_types)): ?>
                    <table class="media-types-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Created At</th>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
                                    <th>Admin Name</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginated_media_types as $mediaType): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mediaType['name']); ?></td>
                                    <td><?php echo htmlspecialchars($mediaType['created_at']); ?></td>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
                                        <td>
                                            <?php
                                            $adminName = 'Unknown';
                                            if (!empty($mediaType['admin_id'])) {
                                                $adminId = (int) $mediaType['admin_id'];
                                                // Step 1: Get admin.user_id
                                                $adminQ = $pdo->prepare("SELECT user_id FROM admin WHERE id = :id LIMIT 1");
                                                $adminQ->bindParam(':id', $adminId);
                                                $adminQ->execute();
                                                if ($adminRow = $adminQ->fetch(PDO::FETCH_ASSOC)) {
                                                    $userId = $adminRow['user_id'];
                                                    // Step 2: Get users.name
                                                    $userQ = $pdo->prepare("SELECT name FROM users WHERE id = :id LIMIT 1");
                                                    $userQ->bindParam(':id', $userId);
                                                    $userQ->execute();
                                                    if ($userRow = $userQ->fetch(PDO::FETCH_ASSOC)) {
                                                        $adminName = htmlspecialchars($userRow['name']);
                                                    }
                                                }
                                            }
                                            echo $adminName;
                                            ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="action-buttons">
                                        <form method="GET" action="" style="display: inline;">
                                            <input type="hidden" name="edit" value="<?php echo $mediaType['id']; ?>">
                                            <button type="submit" class="btn-edit">Edit</button>
                                        </form>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $mediaType['id']; ?>">
                                            <button type="submit" name="delete" class="btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this media type?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No media types found.</p>
                <?php endif; ?>
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
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Auto-hide success message after 3 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) {
                setTimeout(function () {
                    successMessage.style.opacity = '0';
                    setTimeout(function () {
                        successMessage.style.display = 'none';
                    }, 500);
                }, 3000);
            }
        });
    </script>
</body>

</html>