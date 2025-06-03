<?php
session_start();
$pageTitle = 'Template Modification Request';

include '../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header('Location: login.php');
    exit();
}

// Get template ID from URL
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

// Fetch template details
$template_query = "SELECT t.*, c.c_Name as category_name, m.name as media_type_name, s.id as staff_id 
    FROM templates t
    JOIN category c ON t.c_id = c.c_id
    JOIN media_type m ON t.media_type_id = m.id
    JOIN staff s ON t.staff_id = s.id
    WHERE t.id = ?";
$stmt = $conn->prepare($template_query);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $template_id);
$stmt->execute();
$template_result = $stmt->get_result();
$template = $template_result->fetch_assoc();

if (!$template) {
    header('Location: intemplates.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $staff_id = $template['staff_id'];
    $category_id = $template['c_id'];
    $media_type_id = $template['media_type_id'];
    $size = $_POST['size'];
    $orientation = $_POST['orientation'];
    $color_scheme = $_POST['color_scheme'];
    $preferred_color = $_POST['preferred_color'] ?? '#000000';
    $secondary_color = $_POST['secondary_color'] ?? '#ffffff';
    $quantity = $_POST['quantity'];
    $additional_notes = $_POST['additional_notes'];
    $reference_image = '';

    // Handle file upload
    if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/reference_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['reference_image']['name'], PATHINFO_EXTENSION);
        $reference_image = uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['reference_image']['tmp_name'], $upload_dir . $reference_image);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into template_modifications table
        $insert_query = "INSERT INTO template_modifications (
            user_id, staff_id, template_id, category_id, media_type_id, 
            size, orientation, color_scheme, preferred_color, secondary_color, quantity, additional_notes, 
            reference_image, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

        $stmt = $conn->prepare($insert_query);

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param(
            "iiiiisssssiss",
            $user_id,
            $staff_id,
            $template_id,
            $category_id,
            $media_type_id,
            $size,
            $orientation,
            $color_scheme,
            $preferred_color,
            $secondary_color,
            $quantity,
            $additional_notes,
            $reference_image
        );

        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $template_modification_id = $conn->insert_id;

        // Get staff user details for notification
        $staff_query = "SELECT u.id as user_id, u.name as staff_name, t.name as template_name 
                       FROM staff s 
                       JOIN users u ON s.user_id = u.id 
                       JOIN templates t ON t.id = ?
                       WHERE s.id = ?";
        $staff_stmt = $conn->prepare($staff_query);
        $staff_stmt->bind_param("ii", $template_id, $staff_id);
        $staff_stmt->execute();
        $staff_result = $staff_stmt->get_result();
        $staff_info = $staff_result->fetch_assoc();

        // Create notification for the staff member
        $title = "New Template Modification Request";
        $message = "You have received a new template modification request for template '{$staff_info['template_name']}'";

        // Include create_notification.php if not already included
        require_once '../includes/create_notification.php';

        // Create notification
        createNotification(
            $conn,
            $staff_info['user_id'],
            $title,
            $message,
            'template_modification',
            $template_modification_id,
            'template_modification'
        );

        $conn->commit();

        // Redirect to additional info form
        header("Location: additional_info_form.php?category_id=" . $category_id . "&request_id=" . $template_modification_id . "&type=modification");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
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
            max-width: 1200px;
            margin: 15px auto;
            padding: 20px 30px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(44, 62, 80, 0.10), 0 1.5px 4px rgba(44, 62, 80, 0.08);
            transition: box-shadow 0.3s;
            display: flex;
            gap: 30px;
        }

        .template-preview {
            flex: 0 0 320px;
            height: fit-content;
            text-align: center;
            background: #f4f8fb;
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 1px 4px rgba(44, 62, 80, 0.07);
            position: sticky;
            top: 85px;
        }

        .container h2 {
            margin: 15px 0;
        }

        .form-content {
            flex: 1;
        }

        .template-preview img {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 8px;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.10);
        }

        .template-preview h3 {
            margin: 12px 0 8px 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }

        .template-preview p {
            margin: 3px 0;
            color: #34495e;
            font-size: 0.9rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #34495e;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #d1d8e0;
            border-radius: 6px;
            font-size: 15px;
            background: #f9fafb;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #2ecc71;
            outline: none;
            box-shadow: 0 0 0 2px rgba(46, 204, 113, 0.15);
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .color-pickers {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .color-pickers>div {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .color-pickers label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
        }

        .color-pickers input[type="color"] {
            width: 100%;
            height: 40px;
            padding: 5px;
            border: 2px solid #e0e6ed;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .color-pickers input[type="color"]:hover {
            border-color: #2ecc71;
        }

        .color-pickers input[type="color"]:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }

        .btn-submit {
            background: linear-gradient(90deg, #2ecc71 60%, #27ae60 100%);
            color: #fff;
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(46, 204, 113, 0.10);
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(90deg, #27ae60 60%, #2ecc71 100%);
            box-shadow: 0 4px 16px rgba(46, 204, 113, 0.15);
            transform: translateY(-2px);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 15px;
            font-weight: 500;
        }

        .alert-danger {
            background: #fdecea;
            color: #c0392b;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 1200px) {
            .form-container {
                flex-direction: column;
                padding: 20px;
            }

            .template-preview {
                position: relative;
                top: 0;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
            }

            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .color-pickers {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <h2>Request Template Modification</h2>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <div class="template-preview">
                    <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                        alt="<?php echo htmlspecialchars($template['name']); ?>">
                    <h3><?php echo htmlspecialchars($template['name']); ?></h3>
                    <p>Category: <?php echo htmlspecialchars($template['category_name']); ?></p>
                    <p>Media Type: <?php echo htmlspecialchars($template['media_type_name']); ?></p>
                </div>

                <div class="form-content">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="size">Size</label>
                                <select name="size" id="size" class="form-control" required>
                                    <option value="">Select Size</option>
                                    <?php
                                    // Fetch sizes for the template's category
                                    $sizes_query = "SELECT * FROM sizes WHERE category_id = ? ORDER BY size_name";
                                    $stmt = $conn->prepare($sizes_query);

                                    if ($stmt) {
                                        $stmt->bind_param("i", $template['c_id']);
                                        $stmt->execute();
                                        $sizes_result = $stmt->get_result();
                                        while ($size = $sizes_result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($size['size_name']) . '">' . htmlspecialchars($size['size_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="orientation">Orientation</label>
                                <select name="orientation" id="orientation" class="form-control" required>
                                    <option value="Portrait">Portrait</option>
                                    <option value="Landscape">Landscape</option>
                                </select>
                            </div>

                            <div class="form-group" id="color_scheme_group">
                                <label for="color_scheme">Color Scheme</label>
                                <select name="color_scheme" id="color_scheme" class="form-control" required
                                    onchange="toggleColorPicker()">
                                    <option value="">Select Color Scheme</option>
                                    <option value="Black and White">Black and White</option>
                                    <option value="Custom Color">Custom Color</option>
                                    <option value="Grayscale">Grayscale</option>
                                </select>
                            </div>
                            <div class="form-group" id="preferred_color_group" style="display: none;">
                                <label for="preferred_color">Primary Color</label>
                                <input type="color" id="preferred_color" name="preferred_color" value="#000000"
                                    class="form-control" style="width: 60px; height: 40px; padding: 0; border: none;">
                            </div>

                            <div class="form-group" id="secondary_color_group" style="display: none;">
                                <label for="secondary_color">Secondary Color (Optional)</label>
                                <input type="color" id="secondary_color" name="secondary_color" value="#ffffff"
                                    class="form-control" style="width: 60px; height: 40px; padding: 0; border: none;">
                            </div>

                            <div class="form-group">
                                <label for="quantity">Quantity</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" min="1"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="reference_image">Reference Image (Optional)</label>
                                <input type="file" name="reference_image" id="reference_image" class="form-control"
                                    accept="image/*">
                            </div>

                            <div class="form-group">
                                <label for="additional_notes">Additional Notes</label>
                                <textarea name="additional_notes" id="additional_notes" class="form-control"
                                    rows="4"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
    <script>
        function enforceGrayscale(colorInput) {
            const hex = colorInput.value;
            if (/^#([0-9A-Fa-f]{6})$/.test(hex)) {
                // Get RGB values
                const r = parseInt(hex.substr(1, 2), 16);
                const g = parseInt(hex.substr(3, 2), 16);
                const b = parseInt(hex.substr(5, 2), 16);
                // Use luminance formula for better grayscale conversion
                const gray = Math.round(0.299 * r + 0.587 * g + 0.114 * b);
                const grayHex = gray.toString(16).padStart(2, '0');
                colorInput.value = `#${grayHex}${grayHex}${grayHex}`;
            }
        }

        function toggleColorPicker() {
            const scheme = document.getElementById('color_scheme').value;
            const primaryColorGroup = document.getElementById('preferred_color_group');
            const secondaryColorGroup = document.getElementById('secondary_color_group');
            const primaryColorInput = document.getElementById('preferred_color');
            const secondaryColorInput = document.getElementById('secondary_color');

            if (scheme === 'Black and White') {
                primaryColorGroup.style.display = 'none';
                secondaryColorGroup.style.display = 'none';
            } else {
                primaryColorGroup.style.display = 'block';
                secondaryColorGroup.style.display = 'block';

                if (scheme === 'Grayscale') {
                    // Set default grayscale values
                    primaryColorInput.value = '#808080';  // 50% gray
                    secondaryColorInput.value = '#C0C0C0';  // 75% gray

                    // Force the color inputs to update to grayscale
                    enforceGrayscale(primaryColorInput);
                    enforceGrayscale(secondaryColorInput);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const colorSchemeSelect = document.getElementById('color_scheme');
            const primaryColorInput = document.getElementById('preferred_color');
            const secondaryColorInput = document.getElementById('secondary_color');

            function handleColorInput(e) {
                if (colorSchemeSelect.value === 'Grayscale') {
                    enforceGrayscale(e.target);
                }
            }

            // Add input event listeners to color inputs
            primaryColorInput.addEventListener('input', handleColorInput);
            secondaryColorInput.addEventListener('input', handleColorInput);

            // Also enforce grayscale when the color scheme changes
            colorSchemeSelect.addEventListener('change', function () {
                if (this.value === 'Grayscale') {
                    enforceGrayscale(primaryColorInput);
                    enforceGrayscale(secondaryColorInput);
                }
            });

            // Initial setup
            toggleColorPicker();
        });
    </script>
</body>

</html>