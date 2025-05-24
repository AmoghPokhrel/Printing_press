<?php
session_start();
require_once('../includes/dbcon.php');

// Add debug logging function
function debug_log($message) {
    error_log("[Additional Info Debug] " . $message);
}

// Debug marker
echo '<!-- additional_info_form.php loaded -->';

// Get category ID and request ID from URL
$category_id = $_GET['category_id'] ?? null;
$request_id = $_GET['request_id'] ?? null;
$request_type = $_GET['type'] ?? 'custom';

if (!$category_id || !$request_id) {
    die('Invalid category or request.');
}

// Fetch category name
$stmt = $pdo->prepare("SELECT c_Name FROM category WHERE c_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    die('Category not found.');
}

// Fetch additional fields for this category
$stmt = $pdo->prepare("
    SELECT aif.*, aft.name as type_name, aft.type as field_type
    FROM additional_info_fields aif
    JOIN additional_field_types aft ON aif.field_type_id = aft.id
    WHERE aif.category_id = ?
    ORDER BY aif.display_order ASC
");
$stmt->execute([$category_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("Form submitted via POST method");
    try {
        $table_name = 'additional_info_' . $category_id;
        $request_type = $_GET['type'] ?? 'custom';

        debug_log("Processing form for table: " . $table_name . ", request_type: " . $request_type);

        // Check if table exists
        $check_table = $pdo->query("SHOW TABLES LIKE '$table_name'");
        if ($check_table->rowCount() === 0) {
            throw new Exception("Additional information table for this category does not exist.");
        }

        // Prepare columns and values
        $columns = ['user_id', 'request_type'];
        $values = [$_SESSION['user_id'], $request_type];
        $placeholders = ['?', '?'];

        // Add the appropriate ID column based on request type
        if ($request_type === 'modification') {
            $columns[] = 'template_modification_id';
            $values[] = $request_id;
            $id_column = 'template_modification_id';
        } else {
            $columns[] = 'request_id';
            $values[] = $request_id;
            $id_column = 'request_id';
        }
        $placeholders[] = '?';

        debug_log("Processing " . count($fields) . " dynamic fields");

        // Add dynamic fields
        foreach ($fields as $field) {
            $columns[] = $field['field_name'];
            $placeholders[] = '?';

            if ($field['field_type'] === 'image') {
                if (isset($_FILES[$field['field_name']]) && $_FILES[$field['field_name']]['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/custom_templates/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_name = uniqid('custom_', true) . '_' . basename($_FILES[$field['field_name']]['name']);
                    $upload_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES[$field['field_name']]['tmp_name'], $upload_path)) {
                        $values[] = $file_name;
                        debug_log("Successfully uploaded image for field: " . $field['field_name']);
                    } else {
                        throw new Exception("Failed to upload image for field: " . $field['field_name']);
                    }
                } else {
                    $values[] = null;
                }
            } else {
                $values[] = $_POST[$field['field_name']] ?? null;
                debug_log("Field " . $field['field_name'] . " value: " . ($_POST[$field['field_name']] ?? 'null'));
            }
        }

        $columns_str = implode(', ', $columns);
        $placeholders_str = implode(', ', $placeholders);

        // Check if record exists
        $check_sql = "SELECT id FROM $table_name WHERE user_id = ? AND $id_column = ?";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$_SESSION['user_id'], $request_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            debug_log("Updating existing record");
            // Update existing record
            $set_clause = implode(' = ?, ', $columns) . ' = ?';
            $update_sql = "UPDATE $table_name SET $set_clause WHERE user_id = ? AND $id_column = ?";
            
            // Add the WHERE clause values to the values array
            $update_values = array_merge($values, [$_SESSION['user_id'], $request_id]);
            
            debug_log("Update SQL: " . $update_sql);
            debug_log("Update values: " . print_r($update_values, true));
            
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute($update_values);
        } else {
            debug_log("Inserting new record");
            // Insert new record
            $insert_sql = "INSERT INTO $table_name ($columns_str) VALUES ($placeholders_str)";
            debug_log("SQL Query: " . $insert_sql);
            debug_log("Values: " . print_r($values, true));
            
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute($values);
        }

        debug_log("Form processed successfully, redirecting...");

        // After successful submission, redirect based on request type
        if ($request_type === 'modification') {
            header("Location: template_finishing.php");
        } else {
            header("Location: custom_template_success.php?request_id=" . $request_id);
        }
        exit();
    } catch (Exception $e) {
        debug_log("Error processing form: " . $e->getMessage());
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch existing data if any
$table_name = 'additional_info_' . $category_id;
$existing_data = [];
try {
    if ($request_type === 'modification') {
        $stmt = $pdo->prepare("SELECT * FROM $table_name WHERE user_id = ? AND template_modification_id = ?");
        $stmt->execute([$_SESSION['user_id'], $request_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM $table_name WHERE user_id = ? AND request_id = ?");
        $stmt->execute([$_SESSION['user_id'], $request_id]);
    }
    $existing_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet, which is fine
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Additional Information - <?php echo htmlspecialchars($category['c_Name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-section { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-primary { background-color: #007bff; color: white; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .required::after { content: " *"; color: red; }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <div class="container">
            <h1>Additional Information </h1>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <div class="form-section">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="request_type" value="<?php echo isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'custom'; ?>">
                    <?php foreach ($fields as $field): ?>
                        <div class="form-group">
                            <label class="<?php echo $field['is_required'] ? 'required' : ''; ?>">
                                <?php echo htmlspecialchars($field['field_label']); ?>
                            </label>
                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" name="<?php echo $field['field_name']; ?>" class="form-control"
                                    value="<?php echo htmlspecialchars($existing_data[$field['field_name']] ?? ''); ?>"
                                    <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea name="<?php echo $field['field_name']; ?>" class="form-control"
                                    <?php echo $field['is_required'] ? 'required' : ''; ?> rows="4"><?php echo htmlspecialchars($existing_data[$field['field_name']] ?? ''); ?></textarea>
                            <?php elseif ($field['field_type'] === 'number'): ?>
                                <input type="number" name="<?php echo $field['field_name']; ?>" class="form-control"
                                    value="<?php echo htmlspecialchars($existing_data[$field['field_name']] ?? ''); ?>"
                                    <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'email'): ?>
                                <input type="email" name="<?php echo $field['field_name']; ?>" class="form-control"
                                    value="<?php echo htmlspecialchars($existing_data[$field['field_name']] ?? ''); ?>"
                                    <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'phone'): ?>
                                <input type="tel" name="<?php echo $field['field_name']; ?>" class="form-control"
                                    value="<?php echo htmlspecialchars($existing_data[$field['field_name']] ?? ''); ?>"
                                    <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'date'): ?>
                                <input type="date" name="<?php echo $field['field_name']; ?>" class="form-control"
                                    value="<?php echo htmlspecialchars($existing_data[$field['field_name']] ?? ''); ?>"
                                    <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php elseif ($field['field_type'] === 'image'): ?>
                                <input type="file" name="<?php echo $field['field_name']; ?>" class="form-control"
                                    accept="image/*" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Save Information</button>
                </form>
            </div>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
</body>
</html>