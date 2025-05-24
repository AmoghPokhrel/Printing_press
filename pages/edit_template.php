<?php
session_start();
$pageTitle = 'Edit Templates';

// Redirect if not logged in or not admin/staff
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

// Get template ID from URL
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch template details
$template = [];
$query = "SELECT t.*, c.c_Name as category_name, m.name as media_type_name 
          FROM templates t
          JOIN category c ON t.c_id = c.c_id
          JOIN media_type m ON t.media_type_id = m.id
          WHERE t.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $template_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Template not found
    $_SESSION['error_message'] = "Template not found";
    header("Location: intemplates.php?category=" . urlencode($current_category));
    exit();
}

$template = $result->fetch_assoc();

// Fetch all categories
$categories = [];
$category_query = "SELECT c_id, c_Name FROM category ORDER BY c_Name ASC";
$category_result = $conn->query($category_query);
if ($category_result) {
    $categories = $category_result->fetch_all(MYSQLI_ASSOC);
}

// Fetch all media types
$media_types = [];
$media_query = "SELECT id, name FROM media_type ORDER BY name ASC";
$media_result = $conn->query($media_query);
if ($media_result) {
    $media_types = $media_result->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_template'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $cost = floatval($_POST['cost']);
    $category_id = intval($_POST['category_id']);
    $media_type_id = intval($_POST['media_type_id']);
    // $description = $conn->real_escape_string($_POST['description']);

    // Handle file upload
    $image_path = $template['image_path']; // Keep existing image by default

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/templates/';
        $file_name = basename($_FILES['image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            // Generate unique filename
            $new_filename = uniqid('template_', true) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if it exists
                if (!empty($template['image_path']) && file_exists($upload_dir . $template['image_path'])) {
                    unlink($upload_dir . $template['image_path']);
                }
                $image_path = $new_filename;
            }
        }
    }

    // Update template in database
    $update_query = "UPDATE templates SET 
                    name = ?, 
                    cost = ?, 
                    c_id = ?, 
                    media_type_id = ?, 
                    -- description = ?, 
                    image_path = ? 
                    WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdiisi", $name, $cost, $category_id, $media_type_id, $image_path, $template_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Template updated successfully!";
        header("Location: intemplates.php?category=" . urlencode($current_category));
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating template: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Template</title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .edit-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group textarea {
            height: 100px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: block;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <h2>Edit Template: <?php echo htmlspecialchars($template['name']); ?></h2>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <form class="edit-form" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Template Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($template['name']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="cost">Cost ($)</label>
                    <input type="number" id="cost" name="cost" step="0.01" min="0"
                        value="<?php echo htmlspecialchars($template['cost']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['c_id']; ?>" <?php echo $category['c_id'] == $template['c_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['c_Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="media_type_id">Media Type</label>
                    <select id="media_type_id" name="media_type_id" required>
                        <?php foreach ($media_types as $media): ?>
                            <option value="<?php echo $media['id']; ?>" <?php echo $media['id'] == $template['media_type_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($media['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description"
                        name="description">
                        <?php echo htmlspecialchars($template['description'] ?? ''); ?>
                    </textarea>
                </div> -->

                <div class="form-group">
                    <label for="image">Template Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if (!empty($template['image_path'])): ?>
                        <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                            class="preview-image" id="imagePreview">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/200x150?text=No+Image" class="preview-image"
                            id="imagePreview">
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <button type="submit" name="update_template" class="btn btn-primary">Update Template</button>
                    <a href="intemplates.php?category=<?php echo urlencode($current_category); ?>"
                        class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Preview image when a new one is selected
        document.getElementById('image').addEventListener('change', function (e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.src = e.target.result;
                }

                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>