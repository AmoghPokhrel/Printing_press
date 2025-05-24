<?php
require_once('../includes/db.php');

// Fetch all categories from database
$categories = [];
$categoryQuery = "SELECT * FROM category ORDER BY c_name";
$categoryResult = $conn->query($categoryQuery);
if ($categoryResult) {
    $categories = $categoryResult->fetch_all(MYSQLI_ASSOC);
}

// Fetch limited templates (2 per category) for preview
$templatesByCategory = [];
foreach ($categories as $category) {
    $templateQuery = "SELECT * FROM templates WHERE c_id = " . $category['c_id'] . " ORDER BY created_at DESC LIMIT 2";
    $templateResult = $conn->query($templateQuery);
    if ($templateResult) {
        $templatesByCategory[$category['c_id']] = $templateResult->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates Gallery</title>
    <link rel="stylesheet" href="../assets/css/templets.css">
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            /* Soft blue-gray background */
            color: #333;
            padding-top: 76px;
        }

        nav {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            /* Professional blue gradient */
            padding: 18px 0;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            padding: 0;
            margin: 0;
            gap: 25px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 17px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        nav ul li.login a {
            background-color: rgba(255, 255, 255, 0.25);
            padding: 8px 20px;
            border-radius: 20px;
        }

        .content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .category-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            padding: 20px;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .category-title {
            font-size: 24px;
            color: #333;
            margin: 0;
        }

        .category-description {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .templates-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .templates-grid {
            display: flex;
            gap: 20px;
            padding-bottom: 10px;
        }

        .template-card {
            min-width: 250px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            flex-shrink: 0;
        }

        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .template-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .template-info {
            padding: 15px;
        }

        .template-name {
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #333;
        }

        .template-price {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .see-more-btn {
            display: block;
            text-align: center;
            padding: 10px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .see-more-btn:hover {
            background-color: #2980b9;
        }

        .no-templates {
            color: #666;
            font-style: italic;
            text-align: center;
            padding: 20px;
            width: 100%;
        }
    </style>
</head>

<body>
    <nav>
        <ul>
            <li class="login"><a href="../index.php">Home</a></li>
            <li class="login"><a href="templets.php">Templates</a></li>
            <li class="login"><a href="contact.php">Contact</a></li>
            <li class="login"><a href="login.php">Login</a></li>
        </ul>
    </nav>

    <div class="content">
        <?php if (empty($categories)): ?>
            <div class="category-section">
                <h2>No categories found</h2>
                <p>There are currently no template categories available.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <div class="category-section" id="category-<?php echo $category['c_id']; ?>">
                    <div class="category-header">
                        <div>
                            <h2 class="category-title"><?php echo htmlspecialchars($category['c_Name']); ?></h2>
                            <?php if (!empty($category['c_description'])): ?>
                                <p class="category-description"><?php echo htmlspecialchars($category['c_description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="templates-container">
                        <div class="templates-grid">
                            <?php if (empty($templatesByCategory[$category['c_id']])): ?>
                                <div class="no-templates">No templates available in this category</div>
                            <?php else: ?>
                                <?php foreach ($templatesByCategory[$category['c_id']] as $template): ?>
                                    <div class="template-card">
                                        <?php if (!empty($template['image_path'])): ?>
                                            <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                                                alt="<?php echo htmlspecialchars($template['name']); ?>" class="template-image">
                                        <?php else: ?>
                                            <div class="template-image"
                                                style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                <span>No Image</span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="template-info">
                                            <h3 class="template-name"><?php echo htmlspecialchars($template['name']); ?></h3>
                                            <div class="template-price">RS<?php echo number_format($template['cost'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="login.php?redirect=intemplates.php&category_id=<?php echo $category['c_id']; ?>"
                        class="see-more-btn">
                        See More in <?php echo htmlspecialchars($category['c_Name']); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>