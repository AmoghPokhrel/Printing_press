<?php
session_start();
require_once('../includes/dbcon.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = $_GET['request_id'] ?? 0;

// Fetch request details
$stmt = $pdo->prepare("
    SELECT ctr.*, c.c_Name as category_name, mt.name as media_type_name 
    FROM custom_template_requests ctr
    JOIN category c ON ctr.category_id = c.c_id
    JOIN media_type mt ON ctr.media_type_id = mt.id
    WHERE ctr.id = ? AND ctr.user_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: custom_template.php');
    exit();
}

// Handle template selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_path'])) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE custom_template_requests 
            SET selected_template = ?, status = 'Template Selected'
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$_POST['template_path'], $request_id, $user_id]);

        $pdo->commit();
        header("Location: custom_template.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error selecting template: " . $e->getMessage();
    }
}

// Decode generated templates
$generated_templates = json_decode($request['generated_templates'], true) ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Template</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .template-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px;
        }

        .template-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .template-preview {
            width: 100%;
            height: auto;
            border: 1px solid #eee;
            margin-bottom: 10px;
        }

        .template-info {
            margin-bottom: 10px;
        }

        .template-info span {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .select-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .select-btn:hover {
            background-color: #0056b3;
        }

        .template-dimensions {
            font-size: 0.9em;
            color: #666;
        }

        .template-content {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .template-content h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .template-content p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <div class="container">
            <h2>Select Template for <?php echo htmlspecialchars($request['category_name']); ?></h2>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="template-container">
                <?php foreach ($generated_templates as $template): ?>
                    <div class="template-card">
                        <img src="../uploads/custom_templates/<?php echo htmlspecialchars($template['path']); ?>"
                            alt="Template Preview" class="template-preview">

                        <div class="template-info">
                            <span><strong>Layout:</strong> <?php echo htmlspecialchars($template['layout']); ?></span>
                            <span class="template-dimensions">
                                <strong>Size:</strong> <?php echo htmlspecialchars($request['size']); ?>
                                (<?php echo $template['dimensions']['width']; ?> x
                                <?php echo $template['dimensions']['height']; ?> mm)
                            </span>
                            <span><strong>Orientation:</strong>
                                <?php echo htmlspecialchars($request['orientation']); ?></span>
                            <span><strong>Color Scheme:</strong>
                                <?php echo htmlspecialchars($request['color_scheme']); ?></span>
                        </div>

                        <div class="template-content">
                            <h4>Content Preview:</h4>
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($template['content']['title']); ?></p>
                            <p><strong>Main Text:</strong>
                                <?php echo substr(htmlspecialchars($template['content']['main_text']), 0, 50) . '...'; ?>
                            </p>
                            <?php if (!empty($template['content']['business_name'])): ?>
                                <p><strong>Business:</strong>
                                    <?php echo htmlspecialchars($template['content']['business_name']); ?></p>
                            <?php endif; ?>
                        </div>

                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="template_path"
                                value="<?php echo htmlspecialchars($template['path']); ?>">
                            <button type="submit" class="select-btn">Select This Template</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>