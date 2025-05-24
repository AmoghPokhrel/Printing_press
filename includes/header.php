<?php


// Ensure user role is set
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Determine the correct dashboard link
$dashboard_link = '../index.php'; // Fallback in case the role isn't set properly
if ($user_role === 'Admin' || $user_role === 'Super Admin') {
    $dashboard_link = '../pages/admin_dashboard.php';
} elseif ($user_role === 'Staff') {
    $dashboard_link = '../pages/staff_dashboard.php';
} elseif ($user_role === 'Customer') {
    $dashboard_link = '../pages/customer_dashboard.php';
}

// Database connection (adjust these parameters as needed)
$host = 'localhost';
$dbname = 'printing_press';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch categories from database including their IDs
    $stmt = $pdo->query("SELECT c_id, c_Name FROM category ORDER BY c_Name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get pending requests count for admin and staff
    $pendingRequestsCount = 0;
    if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff') {
        $pendingQuery = "SELECT COUNT(*) as count FROM custom_template_requests WHERE status = 'Pending'";
        $pendingResult = $pdo->prepare($pendingQuery);
        $pendingResult->execute();
        $pendingResult = $pendingResult->fetch(PDO::FETCH_ASSOC);
        if ($pendingResult) {
            $pendingRequestsCount = $pendingResult['count'];
        }
    }

    // Get pending template modifications count
    $pendingModificationsCount = 0;
    if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff') {
        $modQuery = "SELECT COUNT(*) as count FROM template_modifications WHERE status = 'Pending'";
        $modResult = $pdo->prepare($modQuery);
        $modResult->execute();
        $modResult = $modResult->fetch(PDO::FETCH_ASSOC);
        if ($modResult) {
            $pendingModificationsCount = $modResult['count'];
        }
    }
} catch (PDOException $e) {
    $categories = [];
    error_log("Database error: " . $e->getMessage());
}

// Only output HTML after all header operations are complete
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Base sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 93vh;
            width: 280px;
            background-color: #ffffff;
            overflow-x: hidden;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
            border-right: 1px solid #e5e7eb;
            padding: 1rem 0;
        }

        /* Menu styles */
        .menu {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .menu li {
            padding: 0;
            margin: 0;
        }

        .menu li a {
            display: flex;
            align-items: center;
            padding: 0.85rem 1rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 3px solid transparent;
            margin: 2px 0;
        }

        .menu li a i {
            width: 24px;
            margin-right: 12px;
            font-size: 1.1rem;
            color: #6b7280;
            text-align: center;
        }

        /* Minimized sidebar styles */
        .sidebar.minimized {
            width: 70px;
            padding: 1rem 0;
        }

        .sidebar.minimized .menu li {
            display: flex;
            justify-content: center;
            padding: 0;
            margin: 0;
        }

        .sidebar.minimized .menu li a {
            padding: 0;
            margin: 5px auto;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border-left: none;
        }

        .sidebar.minimized .menu li a i {
            margin: 0;
            padding: 0;
            width: auto;
            font-size: 1.25rem;
            line-height: 1;
        }

        /* Active and hover states */
        .menu li a:hover {
            background-color: #f0f9ff;
            color: #1d4ed8;
            border-left-color: #1d4ed8;
        }

        .menu li a.active-option {
            background-color: #eff6ff;
            color: #1d4ed8;
            border-left-color: #1d4ed8;
        }

        .menu li a.active-option i {
            color: #1d4ed8;
        }

        /* Main content adjustment */
        .main-content.sidebar-minimized {
            margin-left: 70px;
        }

        header.sidebar-minimized {
            left: 70px;
        }

        /* Request count badge adjustment */
        .sidebar.minimized .request-count {
            position: absolute;
            top: 5px;
            right: 5px;
            margin: 0;
            font-size: 0.7rem;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
        }

        .menu {
            position: relative;
            padding: 1rem 0;
        }

        .menu li a {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0.85rem 1.25rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-right: none;
            border-left: 3px solid transparent;
            margin: 0;
            text-align: left;
        }

        .menu li a i {
            width: 20px;
            margin-right: 12px;
            margin-left: 0;
            font-size: 1.1rem;
            color: #6b7280;
            transition: all 0.2s ease;
            order: 1;
        }

        .menu li a span {
            order: 2;
        }

        .menu li a:hover {
            background-color: #f9fafb;
            color: #1d4ed8;
            border-left: 3px solid #1d4ed8;
            border-right: none;
        }

        .active-option {
            background-color: #eff6ff !important;
            border-left: 3px solid #1d4ed8 !important;
            border-right: none !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
        }

        /* Update dropdown toggle styles */
        .dropdown-toggle {
            padding: 0.85rem 1.25rem !important;
            margin: 0 !important;
            justify-content: flex-start !important;
        }

        .dropdown-toggle::after {
            content: '›';
            font-size: 1.1rem;
            margin-left: auto;
            margin-right: 0;
            opacity: 0.7;
            transition: all 0.25s ease;
            transform: rotate(90deg);
            order: 2;
        }

        .dropdown.active .dropdown-toggle::after {
            transform: rotate(270deg);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            left: calc(100% + 5px);
            right: auto;
            top: 0;
            background: #ffffff;
            min-width: 220px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(229, 231, 235, 0.5);
            z-index: 9999;
            padding: 0.75rem 0;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.98);
            text-align: left;
        }

        .dropdown-menu li {
            padding: 0;
            margin: 0;
            animation: none;
        }

        .dropdown-menu li a {
            justify-content: flex-start;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            color: #4b5563;
            display: flex;
            align-items: center;
            margin: 0 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .dropdown-menu li a:hover {
            background-color: #f3f4f6;
            color: #2563eb;
            transform: translateX(5px);
        }

        .dropdown-menu li a.active-option {
            background-color: #eff6ff;
            color: #2563eb;
            font-weight: 600;
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
        }

        /* Scrollbar styling for dropdown */
        .dropdown-menu::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .dropdown-menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .dropdown-menu::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 20px;
        }

        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #d1d5db;
        }

        /* Add subtle animation for dropdown items */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .dropdown-menu li {
            animation: slideIn 0.2s ease forwards;
            opacity: 0;
        }

        .dropdown-menu li:nth-child(1) { animation-delay: 0.05s; }
        .dropdown-menu li:nth-child(2) { animation-delay: 0.1s; }
        .dropdown-menu li:nth-child(3) { animation-delay: 0.15s; }
        .dropdown-menu li:nth-child(4) { animation-delay: 0.2s; }
        .dropdown-menu li:nth-child(5) { animation-delay: 0.25s; }

        /* Add hover effect for the dropdown toggle */
        .dropdown:hover .dropdown-toggle {
            background-color: #f9fafb;
            color: #2563eb;
        }

        /* Style for the request count in dropdown */
        .dropdown-menu .request-count {
            background-color: #ef4444;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
            order: 2;
        }

        /* Add a subtle separator between groups of items */
        .dropdown-menu li:not(:last-child) {
            position: relative;
        }

        .dropdown-menu li:not(:last-child)::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 1rem;
            right: 1rem;
            height: 1px;
            background: linear-gradient(to right, transparent, #e5e7eb, transparent);
            opacity: 0.5;
        }

        /* Search bar styling */
        .search {
            position: sticky;
            top: 0;
            background-color: #ffffff;
            padding: 1.25rem 1.5rem;
            z-index: 1001;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }

        .search input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            font-size: 0.9rem;
            color: #4b5563;
            transition: all 0.25s ease;
            background-color: #f8fafc;
            text-align: left;
            padding-left: 1rem;
        }

        .search input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background-color: #ffffff;
        }

        /* Request count badge */
        .request-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #ef4444;
            color: white;
            border-radius: 9999px;
            padding: 0.15rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: auto;
            margin-right: 0;
            min-width: 1.25rem;
            height: 1.25rem;
            order: 2;
        }

        /* Style specifically for dropdown menu icons */
        .dropdown-menu li a i {
            width: 20px;
            margin-right: 12px;
            font-size: 1.1rem;
            color: #6b7280;
            transition: all 0.2s ease;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .dropdown-menu li a span {
            margin-left: 8px;
        }

        /* Ensure consistent spacing in dropdown items */
        .dropdown-menu li a {
            gap: 12px;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            color: #4b5563;
            display: flex;
            align-items: center;
            margin: 0 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        /* Hide elements in minimized state */
        .sidebar.minimized .menu li a span,
        .sidebar.minimized .dropdown-toggle span,
        .sidebar.minimized .dropdown-toggle::after,
        .sidebar.minimized .search {
            display: none;
        }

        /* Dropdown menu adjustments */
        .sidebar.minimized .dropdown-menu {
            position: fixed;
            left: 70px;
            min-width: 200px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
        }

        /* Enhanced tooltip */
        .sidebar.minimized .menu li a::before {
            content: attr(data-title);
            position: absolute;
            left: 50px;
            top: 50%;
            transform: translateY(-50%);
            background: #1e293b;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            z-index: 1000;
        }

        .sidebar.minimized .menu li a::after {
            content: '';
            position: absolute;
            left: 45px;
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: #1e293b;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .sidebar.minimized .menu li a:hover::before,
        .sidebar.minimized .menu li a:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Hover effects for minimized state */
        .sidebar.minimized .menu li a:hover {
            transform: translateY(-1px);
        }

        .sidebar.minimized .menu li a:hover i {
            transform: scale(1.1);
            color: #1d4ed8;
        }

        /* Request count badge */
        .sidebar.minimized .request-count {
            position: absolute;
            top: -2px;
            right: -2px;
            margin: 0;
            font-size: 0.65rem;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <script>
        function searchMenu() {
            var input, filter, ul, li, a, i, txtValue;
            input = document.getElementById('searchBar');
            filter = input.value.toUpperCase();
            ul = document.getElementById('menuList');
            li = ul.getElementsByTagName('li');

            for (i = 0; i < li.length; i++) {
                a = li[i].getElementsByTagName('a')[0];
                txtValue = a.textContent || a.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    li[i].style.display = "";
                } else {
                    li[i].style.display = "none";
                }
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdowns = document.querySelectorAll('.dropdown');
            
            // Create a container for dropdowns if it doesn't exist
            let dropdownContainer = document.querySelector('.dropdown-container');
            if (!dropdownContainer) {
                dropdownContainer = document.createElement('div');
                dropdownContainer.className = 'dropdown-container';
                document.body.appendChild(dropdownContainer);
            }

            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                let isClickOpen = false;

                // Move dropdown menu to the container
                dropdownContainer.appendChild(menu);

                function updateDropdownPosition() {
                    const rect = toggle.getBoundingClientRect();
                    menu.style.top = `${rect.top}px`;
                    menu.style.left = `${rect.right}px`;
                }

                // Update position on scroll and resize
                window.addEventListener('scroll', updateDropdownPosition);
                window.addEventListener('resize', updateDropdownPosition);

                // Handle click events
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Close all other dropdowns
                    dropdowns.forEach(d => {
                        if (d !== dropdown) {
                            d.classList.remove('active');
                            const otherMenu = d.querySelector('.dropdown-menu');
                            if (otherMenu) {
                                otherMenu.classList.remove('show', 'clicked');
                            }
                        }
                    });

                    // Toggle current dropdown
                    isClickOpen = !isClickOpen;
                    dropdown.classList.toggle('active');
                    if (isClickOpen) {
                        menu.classList.add('show', 'clicked');
                        updateDropdownPosition();
                    } else {
                        menu.classList.remove('show', 'clicked');
                    }
                });

                // Close when clicking outside
                document.addEventListener('click', function (e) {
                    if (!dropdown.contains(e.target) && !menu.contains(e.target)) {
                        dropdown.classList.remove('active');
                        menu.classList.remove('show', 'clicked');
                        isClickOpen = false;
                    }
                });

                // Handle hover events - only if not clicked open
                dropdown.addEventListener('mouseenter', function () {
                    if (!isClickOpen) {
                        updateDropdownPosition();
                        menu.classList.add('show');
                    }
                });

                dropdown.addEventListener('mouseleave', function (e) {
                    if (!isClickOpen && !menu.contains(e.relatedTarget)) {
                        menu.classList.remove('show');
                    }
                });

                menu.addEventListener('mouseleave', function (e) {
                    if (!isClickOpen && !dropdown.contains(e.relatedTarget)) {
                        menu.classList.remove('show');
                    }
                });

                // Initial position update
                updateDropdownPosition();
            });
        });
    </script>
</head>

<body>
    <div class="sidebar">

        <div class="search">
            <input type="text" id="searchBar" onkeyup="searchMenu()" placeholder="Search" />
        </div>
        <ul class="menu" id="menuList">
            <li>
                <a href="<?= htmlspecialchars($dashboard_link) ?>"
                    <?= basename($_SERVER['PHP_SELF']) == basename($dashboard_link) ? 'class="active-option"' : '' ?>
                    data-title="Dashboard">
                    <i class="fas fa-home"></i>
                    <span style="color:black;font-weight:bold">Dashboard</span>
                </a>
            </li>

            <!-- Reports Section -->
            <?php if ($user_role === 'Super Admin'): ?>
                <li>
                    <a href="../pages/reports.php" 
                        <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active-option"' : '' ?>
                        data-title="Reports">
                        <i class="fas fa-chart-bar"></i>
                        <span style="color:black;font-weight:bold">Reports</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Setup Section -->
            <?php if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff'): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-title="Setup">
                        <i class="fas fa-cog"></i>
                        <span style="color:black;font-weight:bold">Setup</span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if ($user_role === 'Admin' || $user_role === 'Super Admin'): ?>
                            <li>
                                <a href="../pages/admin_setup.php" 
                                    <?= basename($_SERVER['PHP_SELF']) == 'admin_setup.php' ? 'class="active-option"' : '' ?>
                                    data-title="Admin Setup">
                                    <i class="fas fa-user-shield"></i>
                                    <span style="color:black;font-weight:bold">
                                        <?php echo ($user_role === 'Super Admin') ? 'Admin Setup' : 'Admins'; ?>
                                    </span>
                                </a>
                            </li>
                            <li>
                                <a href="../pages/staff_setup.php" 
                                    <?= basename($_SERVER['PHP_SELF']) == 'staff_setup.php' ? 'class="active-option"' : '' ?>
                                    data-title="Staff Setup">
                                    <i class="fas fa-users"></i>
                                    <span style="color:black;font-weight:bold">Staff Setup</span>
                                </a>
                            </li>
                            <li>
                                <a href="../pages/cat_setup.php" 
                                    <?= basename($_SERVER['PHP_SELF']) == 'cat_setup.php' ? 'class="active-option"' : '' ?>
                                    data-title="Category Setup">
                                    <i class="fas fa-folder-open"></i>
                                    <span style="color:black;font-weight:bold">Category Setup</span>
                                </a>
                            </li>
                            <li>
                                <a href="../pages/media_type_setup.php"
                                    <?= basename($_SERVER['PHP_SELF']) == 'media_type_setup.php' ? 'class="active-option"' : '' ?>
                                    data-title="Media Type Setup">
                                    <i class="fas fa-photo-video"></i>
                                    <span style="color:black;font-weight:bold">Media Type Setup</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="../pages/template_setup.php" 
                                <?= basename($_SERVER['PHP_SELF']) == 'template_setup.php' ? 'class="active-option"' : '' ?>
                                data-title="Template Setup">
                                <i class="fas fa-file-alt"></i>
                                <span style="color:black;font-weight:bold">Template Setup</span>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($user_role === 'Admin'): ?>
                <li>
                    <a href="../pages/orders.php" <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'class="active-option"' : '' ?>>
                        <i class="fas fa-shopping-cart"></i>
                        <span style="color:black;font-weight:bold">Orders</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Requests Section -->
            <?php if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff'): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-inbox"></i>
                        <span style="color:black;font-weight:bold">Requests</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="../pages/manage_custom_requests.php"
                                <?= basename($_SERVER['PHP_SELF']) == 'manage_custom_requests.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-tasks"></i>
                                <span style="color:black;font-weight:bold">Custom Requests</span>
                                <?php if ($pendingRequestsCount > 0): ?>
                                    <span class="request-count"><?php echo $pendingRequestsCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php if ($user_role === 'Staff'): ?>
                            <li>
                                <a href="../pages/template_finishing.php"
                                    <?= basename($_SERVER['PHP_SELF']) == 'template_finishing.php' ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-pencil-alt"></i>
                                    <span style="color:black;font-weight:bold">Template Request</span>
                                    <?php if ($pendingModificationsCount > 0): ?>
                                        <span class="request-count"><?php echo $pendingModificationsCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Templates Section (for Admin, Staff, Super Admin) -->
            <?php if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff'): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-images"></i>
                        <span style="color:black;font-weight:bold">Templates</span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if ($user_role === 'Staff'): ?>
                            <li>
                                <a href="../pages/your_template.php" <?= basename($_SERVER['PHP_SELF']) == 'your_template.php' ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-user-edit"></i>
                                    <span style="color:black;font-weight:bold">Your Templates</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="../pages/intemplates.php?category=all" <?= (isset($_GET['category']) && $_GET['category'] == 'all') ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-th-large"></i>
                                <span style="color:black;font-weight:bold">All Templates</span>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="../pages/intemplates.php?category=<?= $category['c_id'] ?>"
                                    <?= (isset($_GET['category']) && $_GET['category'] == $category['c_id']) ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-folder"></i>
                                    <span
                                        style="color:black;font-weight:bold"><?= htmlspecialchars($category['c_Name']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Customer Services Section -->
            <?php if ($user_role === 'Customer'): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-concierge-bell"></i>
                        <span style="color:black;font-weight:bold">Our Services</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="../pages/custom_template.php" <?= basename($_SERVER['PHP_SELF']) == 'custom_template.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-magic"></i>
                                <span style="color:black;font-weight:bold">Custom Template</span>
                            </a>
                        </li>
                        <li>
                            <a href="../pages/template_finishing.php"
                                <?= basename($_SERVER['PHP_SELF']) == 'template_finishing.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-paint-brush"></i>
                                <span style="color:black;font-weight:bold">Template Finishing</span>
                            </a>
                        </li>
                        <li>
                            <a href="../pages/your_orders.php" <?= basename($_SERVER['PHP_SELF']) == 'your_orders.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-shopping-bag"></i>
                                <span style="color:black;font-weight:bold">Your Orders</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Our Products Dropdown for Customers -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-store"></i>
                        <span style="color:black;font-weight:bold">Our Products</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="../pages/intemplates.php?category=all" <?= (isset($_GET['category']) && $_GET['category'] == 'all') ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-th-large"></i>
                                <span style="color:black;font-weight:bold">All Templates</span>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="../pages/intemplates.php?category=<?= $category['c_id'] ?>"
                                    <?= (isset($_GET['category']) && $_GET['category'] == $category['c_id']) ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-folder"></i>
                                    <span
                                        style="color:black;font-weight:bold"><?= htmlspecialchars($category['c_Name']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>

        </ul>
    </div>
</body>

</html>