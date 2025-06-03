<?php
session_start();

// Default page title
$pageTitle = 'Template Setup';

include '../includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    echo '<script>alert("You need to log in first"); window.location.href = "login.php";</script>';
    exit();
}

// Initialize variables
$template_name = $cost = '';
$category_id = $media_type_id = $staff_id = 0;
$error_message = $success_message = '';

// Fetch categories for dropdown
$categories = [];
$category_stmt = $conn->query("SELECT c_id, c_Name FROM category ORDER BY c_Name ASC");
if ($category_stmt) {
    $categories = $category_stmt->fetch_all(MYSQLI_ASSOC);
}

// Fetch media types for dropdown
$media_types = [];
$media_type_stmt = $conn->query("SELECT id, name FROM media_type ORDER BY name ASC");
if ($media_type_stmt) {
    $media_types = $media_type_stmt->fetch_all(MYSQLI_ASSOC);
}

// Fetch staff for dropdown
$staff_members = [];
$staff_stmt = $conn->query("SELECT staff.id, users.name FROM staff JOIN users ON staff.user_id = users.id ORDER BY users.name ASC");
if ($staff_stmt) {
    $staff_members = $staff_stmt->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $template_name = mysqli_real_escape_string($conn, $_POST['template_name']);
    $cost = mysqli_real_escape_string($conn, $_POST['cost']);
    $category_id = intval($_POST['category_id']);
    $media_type_id = intval($_POST['media_type_id']);
    $staff_id = intval($_POST['staff_id']);
    $color_scheme = isset($_POST['color_scheme']) ? mysqli_real_escape_string($conn, $_POST['color_scheme']) : '';

    // Image upload handling
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp_path = $_FILES['image']['tmp_name'];
        $image_path = time() . '_' . basename($_FILES['image']['name']);
        $upload_dir = '../uploads/templates/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $dest_path = $upload_dir . $image_path;

        if (!move_uploaded_file($image_tmp_path, $dest_path)) {
            $error_message = "Image upload failed.";
            $image_path = '';
        }
    }

    if (empty($template_name) || empty($cost) || $category_id == 0 || $media_type_id == 0 || $staff_id == 0 || empty($color_scheme)) {
        $error_message = "All fields are required!";
    } else {
        $sql = "INSERT INTO templates (name, cost, c_id, media_type_id, staff_id, image_path, color_scheme) VALUES ('$template_name', '$cost', $category_id, $media_type_id, $staff_id, '$image_path', '$color_scheme')";

        if (mysqli_query($conn, $sql)) {
            $success_message = "Template added successfully!";
            $template_name = $cost = '';
            $category_id = $media_type_id = $staff_id = 0;
        } else {
            $error_message = "Error adding template: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            font-family: 'Inter', sans-serif;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.01em;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            transition: all 0.2s ease;
            background-color: #f9fafb;
        }

        .form-control:focus {
            border-color: #3b82f6;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background-color: #fff;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }

        .btn-primary {
            background: linear-gradient(to right, #2563eb, #3b82f6);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #1d4ed8, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }

        .alert {
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        input[type="file"].form-control {
            padding: 10px;
            font-size: 0.9rem;
            line-height: 1.4;
            color: #4b5563;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <!-- <h2><?php echo $pageTitle; ?></h2> -->
            <div class="form-container">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" id="successMessage"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" id="successMessage"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="template_name">Template Name:</label>
                        <input type="text" class="form-control" id="template_name" name="template_name"
                            value="<?php echo htmlspecialchars($template_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Template Category:</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="0">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['c_id']; ?>" <?php echo ($category_id == $category['c_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['c_Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="media_type_id">Media Type:</label>
                        <select class="form-control" id="media_type_id" name="media_type_id" required>
                            <option value="0">Select Media Type</option>
                            <?php foreach ($media_types as $media_type): ?>
                                <option value="<?php echo $media_type['id']; ?>" <?php echo ($media_type_id == $media_type['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($media_type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cost">Cost:</label>
                        <input type="number" step="0.01" class="form-control" id="cost" name="cost"
                            value="<?php echo htmlspecialchars($cost); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="staff_id">Staff Member:</label>
                        <select class="form-control" id="staff_id" name="staff_id" required>
                            <option value="0">Select Staff Member</option>
                            <?php foreach ($staff_members as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo ($staff_id == $staff['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="color_scheme">Color Scheme:</label>
                        <select class="form-control" id="color_scheme" name="color_scheme" required>
                            <option value="Black and White">Black and White</option>
                            <option value="Grayscale">Grayscale</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="image">Template Image:</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                    <button type="submit" class="btn-primary">Save Template</button>
                </form>
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
                    }, 500); // Match this with the CSS transition duration
                }, 3000); // 3 seconds
            }
        });
    </script>

</body>

</html>