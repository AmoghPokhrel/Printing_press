<?php
session_start();
include '../includes/db.php';

// Default page title
$pageTitle = 'Additional Info Setup';

// Redirect if not logged in or not staff/admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need staff or admin privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Initialize messages
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

// Get category ID from URL
$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;

if ($category_id === 0) {
    echo '<script>alert("Invalid category ID"); window.location.href = "cat_setup.php";</script>';
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_field'])) {
        handleAddField($conn, $category_id);
    } elseif (isset($_POST['update_field'])) {
        handleUpdateField($conn);
    }

    header("Location: additional_info_setup.php?category_id=" . $category_id);
    exit();
}

// Handle delete request
if (isset($_GET['delete'])) {
    handleDeleteField($conn);
    header("Location: additional_info_setup.php?category_id=" . $category_id);
    exit();
}

// Load existing fields
$fields = loadFields($conn, $category_id);

// Edit mode check
$edit_mode = false;
$edit_field = null;
if (isset($_GET['edit'])) {
    $edit_field = getFieldById($conn, (int) $_GET['edit']);
    $edit_mode = ($edit_field !== null);
}

// Helper Functions
function handleAddField($conn, $category_id)
{
    $field_name = $conn->real_escape_string(trim($_POST['field_name'] ?? ''));
    $field_label = $conn->real_escape_string(trim($_POST['field_label'] ?? ''));
    $field_type_id = (int) ($_POST['field_type_id'] ?? 0);
    $display_order = (int) ($_POST['display_order'] ?? 0);
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];

    if (empty($field_name) || empty($field_label) || $field_type_id === 0) {
        $_SESSION['error'] = "Field name, label, and type are required!";
        return;
    }

    // Check for duplicate field name in the same category
    $stmt = $conn->prepare("SELECT id FROM additional_info_fields WHERE category_id = ? AND field_name = ?");
    $stmt->bind_param("is", $category_id, $field_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Field name already exists in this category!";
        $stmt->close();
        return;
    }
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Insert new field into additional_info_fields
        $stmt = $conn->prepare("INSERT INTO additional_info_fields (category_id, field_name, field_label, field_type_id, is_required, display_order, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiiii", $category_id, $field_name, $field_label, $field_type_id, $is_required, $display_order, $user_id);
        $stmt->execute();
        $stmt->close();

        // 2. Get field type from additional_field_types
        $stmt = $conn->prepare("SELECT type FROM additional_field_types WHERE id = ?");
        $stmt->bind_param("i", $field_type_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $field_type = $result->fetch_assoc()['type'];
        $stmt->close();

        // 3. Determine SQL column type based on field type
        $column_type = 'VARCHAR(255)'; // default
        switch ($field_type) {
            case 'text':
                $column_type = 'VARCHAR(255)';
                break;
            case 'textarea':
                $column_type = 'TEXT';
                break;
            case 'image':
                $column_type = 'VARCHAR(255)';
                break;
            case 'date':
                $column_type = 'DATE';
                break;
            case 'time':
                $column_type = 'TIME';
                break;
            case 'email':
                $column_type = 'VARCHAR(255)';
                break;
            case 'url':
                $column_type = 'VARCHAR(255)';
                break;
            case 'tel':
                $column_type = 'VARCHAR(20)';
                break;
        }

        // 4. Create or update the dynamic table
        $table_name = 'additional_info_' . $category_id;

        // First, create the table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            request_id INT NULL,
            template_modification_id INT NULL,
            request_type ENUM('custom', 'modification') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES custom_template_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (template_modification_id) REFERENCES template_modifications(id) ON DELETE CASCADE,
            CHECK (
                (request_type = 'custom' AND request_id IS NOT NULL AND template_modification_id IS NULL) OR
                (request_type = 'modification' AND template_modification_id IS NOT NULL AND request_id IS NULL)
            )
        )";
        $conn->query($create_table_sql);

        // Then, add the new column if it doesn't exist
        $alter_table_sql = "ALTER TABLE $table_name ADD COLUMN IF NOT EXISTS $field_name $column_type";
        $conn->query($alter_table_sql);

        $conn->commit();
        $_SESSION['message'] = "Field added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error adding field: " . $e->getMessage();
    }
}

function handleUpdateField($conn)
{
    $id = (int) ($_POST['field_id'] ?? 0);
    $field_name = $conn->real_escape_string(trim($_POST['field_name'] ?? ''));
    $field_label = $conn->real_escape_string(trim($_POST['field_label'] ?? ''));
    $field_type_id = (int) ($_POST['field_type_id'] ?? 0);
    $display_order = (int) ($_POST['display_order'] ?? 0);
    $is_required = isset($_POST['is_required']) ? 1 : 0;

    if (empty($field_name) || empty($field_label) || $field_type_id === 0 || $id === 0) {
        $_SESSION['error'] = "Field name, label, type, and ID are required!";
        return;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Get the old field name and category_id
        $stmt = $conn->prepare("SELECT field_name, category_id FROM additional_info_fields WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_field = $result->fetch_assoc();
        $stmt->close();

        if (!$old_field) {
            throw new Exception("Field not found!");
        }

        $category_id = $old_field['category_id'];
        $old_field_name = $old_field['field_name'];
        $table_name = 'additional_info_' . $category_id;

        // 2. Get field type from additional_field_types
        $stmt = $conn->prepare("SELECT type FROM additional_field_types WHERE id = ?");
        $stmt->bind_param("i", $field_type_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $field_type = $result->fetch_assoc()['type'];
        $stmt->close();

        // 3. Determine SQL column type based on field type
        $column_type = 'VARCHAR(255)'; // default
        switch ($field_type) {
            case 'text':
                $column_type = 'VARCHAR(255)';
                break;
            case 'textarea':
                $column_type = 'TEXT';
                break;
            case 'image':
                $column_type = 'VARCHAR(255)';
                break;
            case 'date':
                $column_type = 'DATE';
                break;
            case 'time':
                $column_type = 'TIME';
                break;
            case 'email':
                $column_type = 'VARCHAR(255)';
                break;
            case 'url':
                $column_type = 'VARCHAR(255)';
                break;
            case 'tel':
                $column_type = 'VARCHAR(20)';
                break;
        }

        // 4. Update the field in additional_info_fields
        $stmt = $conn->prepare("UPDATE additional_info_fields SET field_name = ?, field_label = ?, field_type_id = ?, is_required = ?, display_order = ? WHERE id = ?");
        $stmt->bind_param("ssiiii", $field_name, $field_label, $field_type_id, $is_required, $display_order, $id);
        $stmt->execute();
        $stmt->close();

        // 5. If field name changed, rename the column in the dynamic table
        if ($old_field_name !== $field_name) {
            $rename_column_sql = "ALTER TABLE $table_name CHANGE COLUMN $old_field_name $field_name $column_type";
            $conn->query($rename_column_sql);
        }

        $conn->commit();
        $_SESSION['message'] = "Field updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error updating field: " . $e->getMessage();
    }
}

function handleDeleteField($conn)
{
    $id = (int) ($_GET['delete'] ?? 0);

    if ($id === 0) {
        $_SESSION['error'] = "Invalid field ID!";
        return;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Get the field name and category_id before deleting
        $stmt = $conn->prepare("SELECT field_name, category_id FROM additional_info_fields WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $field = $result->fetch_assoc();
        $stmt->close();

        if (!$field) {
            throw new Exception("Field not found!");
        }

        $category_id = $field['category_id'];
        $field_name = $field['field_name'];
        $table_name = 'additional_info_' . $category_id;

        // 2. Delete the field from additional_info_fields
        $stmt = $conn->prepare("DELETE FROM additional_info_fields WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 3. Remove the column from the dynamic table
        $drop_column_sql = "ALTER TABLE $table_name DROP COLUMN IF EXISTS $field_name";
        $conn->query($drop_column_sql);

        $conn->commit();
        $_SESSION['message'] = "Field deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting field: " . $e->getMessage();
    }
}

function loadFields($conn, $category_id)
{
    $fields = [];
    try {
        $sql = "SELECT a.id, a.field_name, a.field_label, a.is_required, a.display_order, t.name as field_type 
                FROM additional_info_fields a 
                INNER JOIN additional_field_types t ON a.field_type_id = t.id 
                WHERE a.category_id = ? 
                ORDER BY a.display_order, a.id";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("Failed to prepare statement: " . $conn->error);
            return $fields;
        }

        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $fields[] = $row;
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error in loadFields: " . $e->getMessage());
    }
    return $fields;
}

function getFieldById($conn, $id)
{
    $stmt = $conn->prepare("SELECT * FROM additional_info_fields WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $field = $result->fetch_assoc();
    $stmt->close();
    return $field;
}

// Helper function to get field type ID
function getFieldTypeId($conn, $type)
{
    $type_map = [
        'text' => 1,
        'number' => 2,
        'date' => 3,
        'checkbox' => 4,
        'dropdown' => 5
    ];
    return $type_map[$type] ?? 1;
}

// Helper function to determine field type from SQL type
function getFieldType($sql_type)
{
    $sql_type = strtolower($sql_type);
    if (strpos($sql_type, 'varchar') !== false) {
        return 'text';
    } elseif (strpos($sql_type, 'decimal') !== false || strpos($sql_type, 'int') !== false) {
        return 'number';
    } elseif (strpos($sql_type, 'date') !== false) {
        return 'date';
    } elseif (strpos($sql_type, 'tinyint(1)') !== false) {
        return 'checkbox';
    }
    return 'text';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .container {
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            margin-top: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 100%;
            overflow-x: hidden;
        }

        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .error {
            background-color: #f2dede;
            color: #a94442;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .button:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background-color: #f44336;
        }

        .table-responsive {
            width: 80%;
            margin: 0 auto;
            max-width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 25px;
            table-layout: fixed;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            color: black;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Set specific column widths */
        th:nth-child(1),
        td:nth-child(1) {
            width: 15%;
        }

        /* Field Name */
        th:nth-child(2),
        td:nth-child(2) {
            width: 15%;
        }

        /* Label */
        th:nth-child(3),
        td:nth-child(3) {
            width: 15%;
        }

        /* Type */
        th:nth-child(4),
        td:nth-child(4) {
            width: 10%;
        }

        /* Required */
        th:nth-child(5),
        td:nth-child(5) {
            width: 10%;
        }

        /* Display Order */
        th:nth-child(6),
        td:nth-child(6) {
            width: 35%;
        }

        /* Actions */

        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .action-buttons {
            display: inline-flex;
            gap: 4px;
            width: 100%;
        }

        .action-buttons .button {
            padding: 4px 6px;
            font-size: 12px;
            white-space: nowrap;
            min-width: 0;
            flex: 1;
            text-align: center;
        }

        /* Add tooltip for truncated content */
        td {
            position: relative;
        }

        td:hover::after {
            content: attr(title);
            position: absolute;
            left: 0;
            top: 100%;
            background: #333;
            color: white;
            padding: 5px;
            border-radius: 3px;
            z-index: 1;
            display: none;
        }

        td:hover::after {
            display: block;
        }

        /* Ensure the table container doesn't cause horizontal scroll */
        @media screen and (max-width: 1200px) {
            .table-responsive {
                width: 95%;
            }
        }

        @media screen and (max-width: 768px) {
            .table-responsive {
                width: 100%;
            }

            .action-buttons .button {
                padding: 3px 4px;
                font-size: 11px;
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

            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php
            // Fetch field types from database
            $field_types_query = "SELECT id, name FROM additional_field_types ORDER BY name";
            $field_types = $conn->query($field_types_query)->fetch_all(MYSQLI_ASSOC);
            ?>

            <?php if ($_SESSION['role'] === 'Staff' || $_SESSION['role'] === 'Admin'): ?>
                <div class="field-form" id="fieldForm">
                    <h2><?php echo $edit_mode ? 'Edit Field' : 'Add New Field'; ?></h2>
                    <form method="POST" action="additional_info_setup.php?category_id=<?php echo $category_id; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="field_id" value="<?php echo htmlspecialchars($edit_field['id']); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="field_name">Field Name:</label>
                            <input type="text" id="field_name" name="field_name" class="form-control"
                                value="<?php echo $edit_mode ? htmlspecialchars($edit_field['field_name']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="field_label">Field Label:</label>
                            <input type="text" id="field_label" name="field_label" class="form-control"
                                value="<?php echo $edit_mode ? htmlspecialchars($edit_field['field_label']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="field_type_id">Field Type:</label>
                            <select id="field_type_id" name="field_type_id" class="form-control" required>
                                <?php
                                $field_types_query = "SELECT id, name FROM additional_field_types ORDER BY name";
                                $field_types = $conn->query($field_types_query);
                                while ($type = $field_types->fetch_assoc()) {
                                    $selected = ($edit_mode && $edit_field['field_type_id'] == $type['id']) ? 'selected' : '';
                                    echo "<option value='{$type['id']}' {$selected}>{$type['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="display_order">Display Order:</label>
                            <input type="number" id="display_order" name="display_order" class="form-control"
                                value="<?php echo $edit_mode ? htmlspecialchars($edit_field['display_order']) : '0'; ?>"
                                min="0">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_required" <?php echo ($edit_mode && $edit_field['is_required']) ? 'checked' : ''; ?>>
                                Required Field
                            </label>
                        </div>
                        <div class="button-group">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="update_field" class="button btn-primary">Update Field</button>
                                <a href="additional_info_setup.php?category_id=<?php echo $category_id; ?>"
                                    class="button btn-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_field" class="button btn-primary">Add Field</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <h2>Existing Fields</h2>
            <div class="table-responsive">
                <table class="field-table">
                    <thead>
                        <tr>
                            <th>Field Name</th>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Display Order</th>
                            <?php if ($_SESSION['role'] === 'Staff' || $_SESSION['role'] === 'Admin'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fields)): ?>
                            <tr>
                                <td colspan="6">No fields found. Please add some fields.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td title="<?php echo htmlspecialchars($field['field_name']); ?>">
                                        <?php echo htmlspecialchars($field['field_name']); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($field['field_label']); ?>">
                                        <?php echo htmlspecialchars($field['field_label']); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($field['field_type']); ?>">
                                        <?php echo htmlspecialchars($field['field_type']); ?>
                                    </td>
                                    <td><?php echo $field['is_required'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo htmlspecialchars($field['display_order']); ?></td>
                                    <?php if ($_SESSION['role'] === 'Staff' || $_SESSION['role'] === 'Admin'): ?>
                                        <td class="action-buttons">
                                            <a href="additional_info_setup.php?category_id=<?php echo $category_id; ?>&edit=<?php echo $field['id']; ?>"
                                                class="button btn-primary">Edit</a>
                                            <a href="additional_info_setup.php?category_id=<?php echo $category_id; ?>&delete=<?php echo $field['id']; ?>"
                                                class="button btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this field?')">Delete</a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleFieldForm() {
            const form = document.getElementById('fieldForm');
            form.classList.toggle('show');
        }

        // Show form if in edit mode
        <?php if ($edit_mode): ?>
            document.getElementById('fieldForm').classList.add('show');
        <?php endif; ?>
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html><?php $conn->close(); ?>