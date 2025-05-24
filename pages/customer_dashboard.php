<?php
session_start();
include '../includes/db.php';

if (
    isset($_SESSION['role']) && $_SESSION['role'] === 'Customer' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])
) {
    $user_id = $_SESSION['user_id'];
    $preferred_color_scheme = $_POST['preferred_color_scheme'];
    $preferred_category = $_POST['preferred_category'];
    $preferred_media_type = $_POST['preferred_media_type'];
    $insert = $conn->prepare("INSERT INTO preferences (user_id, preferred_category, preferred_color_scheme, preferred_media_type) VALUES (?, ?, ?, ?)");
    $insert->bind_param("isss", $user_id, $preferred_category, $preferred_color_scheme, $preferred_media_type);
    $insert->execute();
    $insert->close();
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$pageTitle = isset($_GET['page']) ? $_GET['page'] : 'Dashboard';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Customer') {
    $user_id = $_SESSION['user_id'];

    // Fetch user's name from the database
    $name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $name_stmt->bind_param("i", $user_id);
    $name_stmt->execute();
    $name_stmt->bind_result($user_name);
    $name_stmt->fetch();
    $name_stmt->close();

    // Get the preferred category and color scheme for the logged-in customer
    $pref_stmt = $conn->prepare("SELECT preferred_category, preferred_color_scheme FROM preferences WHERE user_id = ?");
    $pref_stmt->bind_param("i", $user_id);
    $pref_stmt->execute();
    $pref_stmt->bind_result($preferred_category, $preferred_color_scheme);
    $pref_stmt->fetch();
    $pref_stmt->close();

    // Get up to 4 templates from the preferred category
    $templates = [];
    if (!empty($preferred_category)) {
        $cat_stmt = $conn->prepare("SELECT c_id FROM category WHERE c_Name = ?");
        $cat_stmt->bind_param("s", $preferred_category);
        $cat_stmt->execute();
        $cat_stmt->bind_result($preferred_cid);
        $cat_stmt->fetch();
        $cat_stmt->close();

        if (!empty($preferred_cid)) {
            $tpl_stmt = $conn->prepare("SELECT * FROM templates WHERE c_id = ? LIMIT 4");
            $tpl_stmt->bind_param("i", $preferred_cid);
            $tpl_stmt->execute();
            $result = $tpl_stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $templates[] = $row;
            }
            $tpl_stmt->close();
        }
    }

    // Get up to 4 templates from ANY category that match the preferred color scheme
    $color_templates = [];
    if (!empty($preferred_color_scheme)) {
        $color_tpl_stmt = $conn->prepare("SELECT * FROM templates WHERE color_scheme = ? LIMIT 4");
        $color_tpl_stmt->bind_param("s", $preferred_color_scheme);
        $color_tpl_stmt->execute();
        $color_result = $color_tpl_stmt->get_result();
        while ($row = $color_result->fetch_assoc()) {
            $color_templates[] = $row;
        }
        $color_tpl_stmt->close();
    }

    // Get the preferred media type for the logged-in customer
    $pref_stmt2 = $conn->prepare("SELECT preferred_media_type FROM preferences WHERE user_id = ?");
    $pref_stmt2->bind_param("i", $user_id);
    $pref_stmt2->execute();
    $pref_stmt2->bind_result($preferred_media_type);
    $pref_stmt2->fetch();
    $pref_stmt2->close();

    // Get the media_type_id for the preferred media type
    $preferred_media_type_id = null;
    if (!empty($preferred_media_type)) {
        $media_type_stmt = $conn->prepare("SELECT id FROM media_type WHERE name = ?");
        $media_type_stmt->bind_param("s", $preferred_media_type);
        $media_type_stmt->execute();
        $media_type_stmt->bind_result($preferred_media_type_id);
        $media_type_stmt->fetch();
        $media_type_stmt->close();
    }

    // Get up to 4 templates from ANY category that match the preferred media type
    $media_type_templates = [];
    if (!empty($preferred_media_type_id)) {
        $media_tpl_stmt = $conn->prepare("SELECT * FROM templates WHERE media_type_id = ? LIMIT 4");
        $media_tpl_stmt->bind_param("i", $preferred_media_type_id);
        $media_tpl_stmt->execute();
        $media_result = $media_tpl_stmt->get_result();
        while ($row = $media_result->fetch_assoc()) {
            $media_type_templates[] = $row;
        }
        $media_tpl_stmt->close();
    }

    // Get all categories ordered by user's order history
    $popular_templates_query = "
        WITH UserCategoryOrders AS (
            SELECT 
                c.c_id,
                c.c_Name as category_name,
                COUNT(DISTINCT CASE WHEN o.uid = ? THEN o.id ELSE NULL END) as user_order_count
            FROM category c
            LEFT JOIN templates t ON c.c_id = t.c_id
            LEFT JOIN cart_item_line cil ON t.id = cil.template_id
            LEFT JOIN order_item_line oil ON cil.id = oil.ca_it_id
            LEFT JOIN `order` o ON oil.oid = o.id
            GROUP BY c.c_id, c.c_Name
        )
        SELECT 
            t.*,
            c.c_Name as category_name,
            COALESCE(uco.user_order_count, 0) as user_order_count
        FROM category c
        JOIN templates t ON c.c_id = t.c_id
        LEFT JOIN UserCategoryOrders uco ON c.c_id = uco.c_id
        ORDER BY user_order_count DESC, c.c_Name";

    $stmt = $conn->prepare($popular_templates_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $popular_templates_result = $stmt->get_result();
    $popular_templates_by_category = [];

    if ($popular_templates_result) {
        while ($row = $popular_templates_result->fetch_assoc()) {
            $category = $row['category_name'];
            if (!isset($popular_templates_by_category[$category])) {
                $popular_templates_by_category[$category] = [];
            }
            $popular_templates_by_category[$category][] = $row;
        }
    }
    $stmt->close();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            :root {
                --primary-color: #2563eb;
                --primary-dark: #1d4ed8;
                --secondary-color: #3b82f6;
                --text-primary: #1e293b;
                --text-secondary: #4b5563;
                --text-tertiary: #64748b;
                --bg-light: #f8fafc;
                --bg-white: #ffffff;
                --bg-card: #ffffff;
                --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
                --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
                --shadow-hover: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
                --radius-sm: 0.375rem;
                --radius-md: 0.5rem;
                --radius-lg: 0.75rem;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background-color: var(--bg-light);
                color: var(--text-primary);
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                line-height: 1.5;
                font-weight: 400;
            }

            .main-content {
                background: var(--bg-light);
                min-height: calc(100vh - 64px);
                margin-left: 250px;
                padding-top: 64px;
            }

            .dashboard-container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 1.5rem;
                position: relative;
                padding-top: calc(64px + 1.5rem);
                /* Added padding to account for fixed nav */
            }

            .dashboard-header {
                margin-bottom: 2rem;
                padding: 1.5rem;
                background: var(--bg-white);
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-md);
                position: relative;
                overflow: hidden;
            }

            .dashboard-title {
                font-size: 1.5rem;
                font-weight: 500;
                color: var(--primary-color);
                margin-bottom: 0.5rem;
                line-height: 1.2;
                letter-spacing: -0.025em;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .dashboard-title i {
                font-size: 1.25rem;
                color: var(--primary-color);
            }

            .dashboard-subtitle {
                color: var(--text-secondary);
                font-size: 0.875rem;
                line-height: 1.5;
                font-weight: 400;
                max-width: 65ch;
            }

            .section-title {
                font-size: 1.125rem;
                font-weight: 500;
                color: var(--text-primary);
                margin: 1.75rem 0 1.25rem;
                padding-left: 1rem;
                border-left: 3px solid var(--primary-color);
                letter-spacing: -0.025em;
                line-height: 1.3;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .section-title i {
                font-size: 1rem;
                color: var(--primary-color);
            }

            .content {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
                margin-bottom: 2rem;
            }

            .category-card {
                background: var(--bg-card);
                border-radius: var(--radius-md);
                overflow: hidden;
                box-shadow: var(--shadow-sm);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                height: auto;
                display: flex;
                flex-direction: column;
                position: relative;
                border: 1px solid rgba(0, 0, 0, 0.05);
                max-height: 320px;
            }

            .category-card:hover {
                transform: translateY(-3px);
                box-shadow: var(--shadow-md);
            }

            .category-header {
                padding: 0.75rem 1rem;
                background: var(--bg-white);
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-height: 48px;
            }

            .category-name {
                font-size: 0.938rem;
                font-weight: 500;
                color: var(--text-primary);
                margin: 0;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                line-height: 1.3;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: calc(100% - 24px);
            }

            .category-name i {
                color: var(--primary-color);
                font-size: 0.875rem;
                flex-shrink: 0;
            }

            .template-card {
                background: var(--bg-white);
                border-radius: var(--radius-md);
                overflow: hidden;
                transition: all 0.3s ease;
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .template-card:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-lg);
            }

            .template-image {
                width: 100%;
                height: 200px;
                object-fit: cover;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .template-preview {
                padding: 0.75rem;
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                max-height: 220px;
            }

            .template-details {
                display: flex;
                flex-direction: column;
                gap: 0.375rem;
                padding: 0.5rem 0;
            }

            .template-title {
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--text-primary);
                margin: 0;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .template-description {
                font-size: 0.813rem;
                color: var(--text-secondary);
                line-height: 1.4;
                margin: 0;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .template-info {
                font-size: 0.813rem;
                color: var(--text-secondary);
                display: flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.25rem 0;
                font-weight: 400;
            }

            .template-info i {
                color: var(--primary-color);
                font-size: 0.75rem;
            }

            .see-more-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.375rem;
                padding: 0.625rem 1rem;
                background: var(--primary-color);
                color: white;
                text-decoration: none;
                font-weight: 500;
                border-radius: var(--radius-md);
                transition: all 0.2s ease;
                border: none;
                cursor: pointer;
                width: 100%;
                font-size: 0.813rem;
                margin-top: auto;
            }

            .see-more-btn i {
                font-size: 0.75rem;
            }

            .see-more-btn:hover {
                background: var(--primary-dark);
                transform: translateY(-1px);
            }

            .empty-state {
                text-align: center;
                padding: 3rem 2rem;
                color: var(--text-tertiary);
            }

            .empty-state i {
                font-size: 3rem;
                margin-bottom: 1rem;
                color: var(--text-secondary);
            }

            .empty-state-text {
                font-size: 1.125rem;
                font-weight: 500;
                margin-bottom: 1rem;
            }

            .preferences-section {
                background: var(--bg-white);
                border-radius: var(--radius-lg);
                padding: 1.5rem;
                margin-bottom: 2rem;
                box-shadow: var(--shadow-md);
                position: relative;
                overflow: hidden;
            }

            .preferences-title {
                font-size: 1.125rem;
                font-weight: 500;
                color: var(--text-primary);
                margin-bottom: 1.25rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .preferences-title i {
                color: var(--primary-color);
                font-size: 1rem;
            }

            .preferences-form {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.25rem;
                background: var(--bg-light);
                padding: 1.25rem;
                border-radius: var(--radius-md);
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
                color: var(--text-primary);
                font-size: 0.875rem;
            }

            .form-group select {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #e2e8f0;
                border-radius: var(--radius-md);
                background-color: var(--bg-white);
                color: var(--text-primary);
                font-size: 0.875rem;
                transition: all 0.2s ease;
                cursor: pointer;
                font-weight: 400;
            }

            .save-preferences-btn {
                padding: 0.75rem 1.5rem;
                font-size: 0.875rem;
                margin-top: 0.5rem;
            }

            .template-preview {
                padding: 0.75rem;
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                max-height: 220px;
            }

            .template-image-container {
                position: relative;
                padding-top: 56%;
                border-radius: var(--radius-sm);
                overflow: hidden;
                background: var(--bg-light);
                box-shadow: var(--shadow-sm);
            }

            .template-image {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .template-info {
                font-size: 0.813rem;
                color: var(--text-secondary);
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.375rem 0;
                font-weight: 400;
            }

            .see-more-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.625rem 1rem;
                background: var(--primary-color);
                color: white;
                text-decoration: none;
                font-weight: 500;
                border-radius: var(--radius-md);
                transition: all 0.2s ease;
                border: none;
                cursor: pointer;
                width: 100%;
                font-size: 0.813rem;
            }

            @media (max-width: 768px) {
                .dashboard-container {
                    padding: 1rem;
                    padding-top: calc(64px + 1rem);
                }

                .dashboard-header {
                    padding: 1.25rem;
                    margin-bottom: 1.5rem;
                }

                .dashboard-title {
                    font-size: 1.25rem;
                }

                .dashboard-subtitle {
                    font-size: 0.875rem;
                }

                .section-title {
                    font-size: 1rem;
                    margin: 1.5rem 0 1rem;
                }

                .content {
                    gap: 1rem;
                }

                .category-card {
                    max-height: 300px;
                }

                .template-preview {
                    max-height: 200px;
                }

                .template-image-container {
                    padding-top: 52%;
                }

                .category-header {
                    padding: 0.625rem 0.875rem;
                }

                .template-preview {
                    padding: 0.625rem;
                }

                .see-more-btn {
                    padding: 0.5rem 0.875rem;
                }

                .preferences-title {
                    font-size: 1rem;
                }

                .category-name {
                    font-size: 0.875rem;
                }

                .template-title {
                    font-size: 0.813rem;
                }

                .template-description {
                    font-size: 0.75rem;
                }

                .template-info {
                    font-size: 0.75rem;
                }

                .see-more-btn {
                    font-size: 0.75rem;
                    padding: 0.5rem 0.75rem;
                }
            }

            /* Navigation Bar Styles */
            .nav-container {
                background: var(--bg-white);
                padding: 1rem 0;
                border-bottom: 1px solid #e5e7eb;
                position: fixed;
                top: 64px;
                /* Adjusted to account for the header height */
                left: 250px;
                /* Adjusted to account for the sidebar */
                right: 0;
                z-index: 1000;
                box-shadow: var(--shadow-sm);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                background-color: rgba(255, 255, 255, 0.95);
            }

            .nav-content {
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 1.5rem;
                display: flex;
                justify-content: center;
                gap: 2rem;
                flex-wrap: wrap;
            }

            /* Add padding to the first section to prevent content from hiding under the fixed nav */
            #welcome-section {
                padding-top: 80px;
            }

            .nav-item {
                padding: 0.5rem 1rem;
                color: var(--text-secondary);
                text-decoration: none;
                font-weight: 500;
                font-size: 0.95rem;
                border-bottom: 2px solid transparent;
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .nav-item:hover,
            .nav-item.active {
                color: var(--primary-color);
                border-bottom-color: var(--primary-color);
            }

            @media (max-width: 768px) {
                .nav-container {
                    left: 0;
                    padding: 0.75rem 0;
                }

                .nav-content {
                    gap: 1rem;
                    padding: 0 1rem;
                }

                .nav-item {
                    font-size: 0.875rem;
                    padding: 0.5rem;
                }
            }

            #category-section,
            #color-section,
            #media-section,
            #popular-section,
            #preferences-section {
                scroll-margin-top: 150px;
                /* This ensures sections scroll to the right position */
            }
        </style>
    </head>

    <body>
        <?php include('../includes/header.php'); ?>
        <div class="main-content">
            <?php include('../includes/inner_header.php'); ?>
            <div class="dashboard-container">


                <div class="nav-container">
                    <div class="nav-content">
                        <a class="nav-item" onclick="scrollToSection('category-section')">Preferred Category</a>
                        <a class="nav-item" onclick="scrollToSection('color-section')">Color Scheme</a>
                        <a class="nav-item" onclick="scrollToSection('media-section')">Media Type</a>
                        <a class="nav-item" onclick="scrollToSection('popular-section')">Popular Templates</a>
                    </div>
                </div>

                <?php if (empty($preferred_category) || empty($preferred_color_scheme) || empty($preferred_media_type)): ?>
                    <div class="preferences-section" id="preferences-section">
                        <h2 class="preferences-title"><i class="fas fa-cog"></i> Set Your Preferences</h2>
                        <form method="POST" class="preferences-form">
                            <div class="form-group">
                                <label for="preferred_category"><i class="fas fa-folder"></i> Preferred Category</label>
                                <select name="preferred_category" id="preferred_category" required>
                                    <option value="">Select a category</option>
                                    <?php
                                    $cat_query = "SELECT DISTINCT c_Name FROM category ORDER BY c_Name";
                                    $cat_result = $conn->query($cat_query);
                                    while ($cat_row = $cat_result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($cat_row['c_Name']) . "'>" .
                                            htmlspecialchars($cat_row['c_Name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="preferred_color_scheme"><i class="fas fa-palette"></i> Preferred Color
                                    Scheme</label>
                                <select name="preferred_color_scheme" id="preferred_color_scheme" required>
                                    <option value="">Select a color scheme</option>
                                    <?php
                                    $color_query = "SELECT DISTINCT color_scheme FROM templates ORDER BY color_scheme";
                                    $color_result = $conn->query($color_query);
                                    while ($color_row = $color_result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($color_row['color_scheme']) . "'>" .
                                            htmlspecialchars($color_row['color_scheme']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="preferred_media_type"><i class="fas fa-photo-video"></i> Preferred Media
                                    Type</label>
                                <select name="preferred_media_type" id="preferred_media_type" required>
                                    <option value="">Select a media type</option>
                                    <?php
                                    $media_query = "SELECT name FROM media_type ORDER BY name";
                                    $media_result = $conn->query($media_query);
                                    while ($media_row = $media_result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($media_row['name']) . "'>" .
                                            htmlspecialchars($media_row['name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="save_preferences" class="save-preferences-btn">
                                    <i class="fas fa-save"></i> Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (!empty($templates)): ?>
                    <div id="category-section">
                        <h2 class="section-title"><i class="fas fa-folder"></i> Templates from Your Preferred Category</h2>
                        <div class="content">
                            <?php foreach ($templates as $template): ?>
                                <div class="category-card">
                                    <div class="category-header">
                                        <h3 class="category-name">
                                            <i class="fas fa-file-alt"></i>
                                            <?php echo htmlspecialchars($template['name']); ?>
                                        </h3>
                                    </div>
                                    <div class="template-preview">
                                        <div class="template-image-container">
                                            <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                                                alt="<?php echo htmlspecialchars($template['name']); ?>" class="template-image">
                                        </div>
                                        <div class="template-details">
                                            <div class="template-info">
                                                <i class="fas fa-info-circle"></i>
                                                Click to view details and customize
                                            </div>
                                        </div>
                                    </div>
                                    <a href="intemplates.php?category=<?php echo $template['c_id']; ?>" class="see-more-btn">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($color_templates)): ?>
                    <div id="color-section">
                        <h2 class="section-title"><i class="fas fa-palette"></i> Templates with Your Preferred Color Scheme</h2>
                        <div class="content">
                            <?php foreach ($color_templates as $template): ?>
                                <div class="category-card">
                                    <div class="category-header">
                                        <h3 class="category-name">
                                            <i class="fas fa-palette"></i>
                                            <?php echo htmlspecialchars($template['name']); ?>
                                        </h3>
                                    </div>
                                    <div class="template-preview">
                                        <div class="template-image-container">
                                            <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                                                alt="<?php echo htmlspecialchars($template['name']); ?>" class="template-image">
                                        </div>
                                        <div class="template-details">
                                            <div class="template-info">
                                                <i class="fas fa-info-circle"></i>
                                                Click to view details and customize
                                            </div>
                                        </div>
                                    </div>
                                    <a href="intemplates.php?category=<?php echo $template['c_id']; ?>" class="see-more-btn">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($media_type_templates)): ?>
                    <div id="media-section">
                        <h2 class="section-title"><i class="fas fa-photo-video"></i> Templates with Your Preferred Media Type
                        </h2>
                        <div class="content">
                            <?php foreach ($media_type_templates as $template): ?>
                                <div class="category-card">
                                    <div class="category-header">
                                        <h3 class="category-name">
                                            <i class="fas fa-photo-video"></i>
                                            <?php echo htmlspecialchars($template['name']); ?>
                                        </h3>
                                    </div>
                                    <div class="template-preview">
                                        <div class="template-image-container">
                                            <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                                                alt="<?php echo htmlspecialchars($template['name']); ?>" class="template-image">
                                        </div>
                                        <div class="template-details">
                                            <div class="template-info">
                                                <i class="fas fa-info-circle"></i>
                                                Click to view details and customize
                                            </div>
                                        </div>
                                    </div>
                                    <a href="intemplates.php?category=<?php echo $template['c_id']; ?>" class="see-more-btn">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($popular_templates_by_category)): ?>
                    <div id="popular-section">
                        <h2 class="section-title"><i class="fas fa-star"></i> Popular Templates</h2>
                        <div class="content">
                            <?php foreach ($popular_templates_by_category as $category => $templates): ?>
                                <div class="category-card">
                                    <div class="category-header">
                                        <h3 class="category-name">
                                            <i class="fas fa-folder"></i>
                                            <?php echo htmlspecialchars($category); ?>
                                        </h3>
                                    </div>
                                    <?php
                                    if (!empty($templates)) {
                                        $template = $templates[0];
                                        ?>
                                        <div class="template-preview">
                                            <div class="template-image-container">
                                                <img src="../uploads/templates/<?php echo htmlspecialchars($template['image_path']); ?>"
                                                    alt="<?php echo htmlspecialchars($template['name']); ?>" class="template-image">
                                            </div>
                                            <div class="template-details">
                                                <h4 class="template-title"><?php echo htmlspecialchars($template['name']); ?></h4>
                                                <div class="template-info">
                                                    <?php if ($template['user_order_count'] > 0): ?>
                                                        <i class="fas fa-shopping-cart"></i>
                                                        You've ordered <?php echo $template['user_order_count']; ?> time(s)
                                                    <?php else: ?>
                                                        <i class="fas fa-info-circle"></i>
                                                        Explore this category
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="intemplates.php?category=<?php echo $template['c_id']; ?>" class="see-more-btn">
                                            <i class="fas fa-arrow-right"></i>
                                            View Details
                                        </a>
                                    <?php } ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            <?php include('../includes/footer.php'); ?>
        </div>

        <script>
            function scrollToSection(sectionId) {
                const section = document.getElementById(sectionId);
                const navHeight = document.querySelector('.nav-container').offsetHeight;
                const offset = 20; // Additional offset for better visibility

                if (section) {
                    const targetPosition = section.offsetTop - navHeight - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Update active state
                    document.querySelectorAll('.nav-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    event.target.classList.add('active');
                }
            }

            // Highlight active section on scroll
            window.addEventListener('scroll', function () {
                const sections = document.querySelectorAll('[id$="-section"]');
                const navItems = document.querySelectorAll('.nav-item');
                const navHeight = document.querySelector('.nav-container').offsetHeight;

                let current = '';

                sections.forEach(section => {
                    const sectionTop = section.offsetTop - navHeight - 100;
                    if (window.pageYOffset >= sectionTop) {
                        current = section.getAttribute('id');
                    }
                });

                navItems.forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('onclick').includes(current)) {
                        item.classList.add('active');
                    }
                });
            });
        </script>
    </body>

    </html>
<?php } else {
    echo '<script>alert("You need to log in"); window.location.href = "login.php";</script>';
} ?>