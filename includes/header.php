<?php
// No whitespace or output before PHP opening tag
if (!isset($pageTitle)) {
    $pageTitle = 'Printing Press';
}

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
        /* Hamburger menu icon styling */
        .hamburger-icon {
            position: fixed;
            top: 20px;
            left: 225px;
            z-index: 1020;
            cursor: pointer;
            padding: 12px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 40px;
            height: 40px;
            transition: all 0.3s ease;
        }

        .hamburger-icon span {
            display: block;
            width: 100%;
            height: 3px;
            /* Reduced from 5px */
            background: #6B7280;
            /* Changed from #333 to a more faded gray */
            border-radius: 3px;
            /* Adjusted for thinner lines */
            transition: all 0.3s ease;
        }

        .hamburger-icon:hover span {
            background: #4B5563;
            /* Slightly darker on hover */
        }

        .hamburger-icon.active span {
            background: #4B5563;
            /* Keep the active state slightly darker */
        }

        /* Adjust hamburger position when sidebar is collapsed */
        .sidebar.collapsed+.hamburger-icon {
            left: 20px;
        }

        .hamburger-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .hamburger-icon.active span:nth-child(1) {
            transform: rotate(45deg) translate(9px, 9px);
            /* Adjusted for new size */
        }

        .hamburger-icon.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-icon.active span:nth-child(3) {
            transform: rotate(-45deg) translate(9px, -9px);
            /* Adjusted for new size */
        }

        /* Update sidebar for collapse functionality */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 93vh;
            width: 250px;
            background-color: #ffffff;
            z-index: 1013;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            padding-bottom: 20px;
        }

        .sidebar.collapsed {
            transform: translateX(-250px);
        }

        /* Adjust main content when sidebar is collapsed */
        .main-content {
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Modern sidebar styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 93vh;
            width: 250px;
            background-color: #ffffff;
            overflow-x: visible;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
            border-right: 1px solid #e5e7eb;
        }

        .menu {
            position: relative;
            padding: 0.5rem 0;
        }

        .menu li a {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0.5rem 1rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 400;
            border-radius: 0;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-right: none;
            border-left: 3px solid transparent;
            margin: 0;
            text-align: left;
        }

        .menu li a i {
            width: 16px;
            margin-right: 8px;
            margin-left: 0;
            font-size: 1rem;
            color: #6b7280;
            transition: all 0.2s ease;
            order: 1;
        }

        .menu li a span {
            order: 2;
            color: #4b5563;
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
            font-weight: 500 !important;
        }

        /* Update dropdown toggle styles */
        .dropdown-toggle {
            padding: 0.5rem 1rem !important;
            margin: 0 !important;
            justify-content: flex-start !important;
            align-items: center !important;
            width: 100%;
            gap: 8px;
        }

        .dropdown-toggle .toggle-icon {
            margin-left: auto;
            font-size: 0.8rem;
            opacity: 0.7;
            transition: all 0.25s ease;
            order: 3;
            /* Place after the text */
        }

        .dropdown-toggle i:first-child {
            order: 1;
            /* Main icon */
        }

        .dropdown-toggle span {
            order: 2;
            /* Text */
            flex: 1;
            /* Take up remaining space */
        }

        .dropdown.active .dropdown-toggle .toggle-icon {
            transform: rotate(90deg);
        }

        /* Remove the old pseudo-element arrow */
        .dropdown-toggle::after {
            content: none;
        }

        .dropdown-menu {
            display: none;
            position: fixed;
            left: 250px;
            background: #ffffff;
            min-width: 220px;
            max-height: 300px;
            overflow-y: auto !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(229, 231, 235, 0.5);
            z-index: 1014;
            padding: 0.5rem 0;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.98);
            text-align: left;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
            overflow-y: auto !important;
        }

        /* Enhance scrollbar styling for dropdown */
        .dropdown-menu::-webkit-scrollbar {
            width: 6px;
            display: block !important;
        }

        .dropdown-menu::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
            display: block !important;
        }

        .dropdown-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
            transition: background 0.2s ease;
            display: block !important;
        }

        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Update dropdown items container */
        .dropdown-menu ul {
            margin: 0;
            padding: 0;
            list-style: none;
            max-height: 100%;
        }

        /* Update dropdown items styling */
        .dropdown-menu li {
            padding: 0;
            margin: 0;
            list-style: none;
            min-height: 40px;
            /* Ensure minimum height for items */
        }

        .dropdown-menu li a {
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            white-space: nowrap;
            min-height: 40px;
            /* Match parent min-height */
        }

        .dropdown-menu li a:hover {
            background-color: #f8fafc;
            color: #2563eb;
        }

        .dropdown-menu li a i {
            font-size: 1rem;
            color: #6b7280;
            transition: color 0.2s ease;
        }

        .dropdown-menu li a:hover i {
            color: #2563eb;
        }

        /* Add hover effect for the dropdown toggle */
        .dropdown:hover .dropdown-toggle {
            background-color: #f9fafb;
            color: #2563eb;
        }

        /* Style for the request count in dropdown */
        .dropdown-menu .request-count {
            background-color: #ef4444;
            color: white;
            padding: 0.1rem 0.4rem;
            border-radius: 12px;
            font-size: 0.65rem;
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
            padding: 0.75rem 1rem;
            z-index: 1001;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }

        .search input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            font-size: 0.85rem;
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
            padding: 0.1rem 0.4rem;
            font-size: 0.65rem;
            font-weight: 600;
            margin-left: auto;
            margin-right: 0;
            min-width: 1rem;
            height: 1rem;
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

        /* Update all spans in the sidebar to remove bold */
        .dropdown-toggle span {
            color: #4b5563;
        }

        .dropdown-menu li a span {
            color: #4b5563;
        }

        /* Keep active items slightly bolder but not too bold */
        .active-option {
            background-color: #eff6ff !important;
            border-left: 3px solid #1d4ed8 !important;
            border-right: none !important;
            color: #1d4ed8 !important;
            font-weight: 500 !important;
        }

        /* Update the menu items to have a consistent medium weight */
        .menu li a {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0.5rem 1rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 400;
            border-radius: 0;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-right: none;
            border-left: 3px solid transparent;
            margin: 0;
            text-align: left;
        }

        /* Add these new styles for dropdown active states */
        .dropdown-toggle.active-option {
            background-color: #eff6ff !important;
            border-left: 3px solid #1d4ed8 !important;
            color: #1d4ed8 !important;
        }

        .dropdown-toggle.active-option i,
        .dropdown-toggle.active-option span {
            color: #1d4ed8 !important;
        }

        .dropdown.active .dropdown-toggle.active-option::after {
            color: #1d4ed8 !important;
        }

        /* Enhance the active state visibility in dropdown menus */
        .dropdown-menu li a.active-option {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        .dropdown-menu li a.active-option i {
            color: #1d4ed8 !important;
        }
    </style>
    <script>
        function searchMenu() {
            const input = document.getElementById('searchBar');
            const filter = input.value.toUpperCase();
            const ul = document.getElementById('menuList');
            const dropdowns = ul.getElementsByClassName('dropdown');
            const allItems = ul.getElementsByTagName('li');
            let hasResults = false;

            // First hide all items
            for (let item of allItems) {
                item.style.display = "none";
            }

            // Function to check text content
            function matchesSearch(text) {
                return text.toUpperCase().indexOf(filter) > -1;
            }

            // Search through all items including dropdowns
            for (let item of allItems) {
                let shouldShow = false;
                const itemLink = item.querySelector('a');

                if (item.classList.contains('dropdown')) {
                    // For dropdown headers
                    const dropdownText = itemLink.textContent || itemLink.innerText;
                    const dropdownMenu = item.querySelector('.dropdown-menu');
                    const dropdownItems = dropdownMenu ? dropdownMenu.getElementsByTagName('li') : [];

                    // Check dropdown items
                    let hasMatchingChild = false;
                    for (let dropdownItem of dropdownItems) {
                        const dropdownItemLink = dropdownItem.querySelector('a');
                        const dropdownItemText = dropdownItemLink.textContent || dropdownItemLink.innerText;

                        if (matchesSearch(dropdownItemText)) {
                            dropdownItem.style.display = "";
                            hasMatchingChild = true;
                            hasResults = true;
                        }
                    }

                    // Show dropdown if header matches or has matching children
                    if (matchesSearch(dropdownText) || hasMatchingChild) {
                        item.style.display = "";
                        if (hasMatchingChild) {
                            // Show the dropdown menu
                            item.classList.add('active');
                            const dropdownMenu = item.querySelector('.dropdown-menu');
                            if (dropdownMenu) {
                                dropdownMenu.classList.add('show');
                            }
                        }
                        hasResults = true;
                    }
                } else {
                    // For regular menu items
                    const text = itemLink.textContent || itemLink.innerText;
                    if (matchesSearch(text)) {
                        item.style.display = "";
                        hasResults = true;
                    }
                }
            }

            // If search is empty, reset everything
            if (!filter) {
                for (let item of allItems) {
                    item.style.display = "";
                    if (item.classList.contains('dropdown')) {
                        item.classList.remove('active');
                        const dropdownMenu = item.querySelector('.dropdown-menu');
                        if (dropdownMenu) {
                            dropdownMenu.classList.remove('show');
                        }
                    }
                }
            }

            // Show "No results found" if necessary
            const existingNoResults = document.getElementById('noResults');
            if (!hasResults && filter) {
                if (!existingNoResults) {
                    const noResults = document.createElement('li');
                    noResults.id = 'noResults';
                    noResults.style.padding = '10px';
                    noResults.style.textAlign = 'center';
                    noResults.style.color = '#666';
                    noResults.innerHTML = 'No results found';
                    ul.appendChild(noResults);
                }
            } else if (existingNoResults) {
                existingNoResults.remove();
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdowns = document.querySelectorAll('.dropdown');

            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                let isClickOpen = false;

                function adjustDropdownPosition() {
                    const rect = toggle.getBoundingClientRect();
                    const viewportHeight = window.innerHeight;
                    const isSidebarCollapsed = document.body.classList.contains('sidebar-collapsed');

                    // Set the top position to match the toggle button
                    let topPosition = rect.top;

                    // Calculate available space below the toggle
                    const spaceBelow = viewportHeight - rect.bottom;
                    const spaceAbove = rect.top;

                    // If there's not enough space below, position above if there's more space there
                    if (spaceBelow < 300 && spaceAbove > spaceBelow) {
                        topPosition = Math.max(10, rect.top - 300);
                    } else {
                        // Position below, but ensure it doesn't go off screen
                        topPosition = Math.min(rect.top, viewportHeight - 310);
                    }

                    menu.style.top = `${topPosition}px`;

                    // Adjust left position based on sidebar state
                    if (isSidebarCollapsed) {
                        menu.style.left = `${rect.right + 5}px`;
                    } else {
                        menu.style.left = '250px';
                    }
                }

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
                        adjustDropdownPosition();
                        // Force recalculation of scrollbar
                        menu.style.overflow = 'hidden';
                        setTimeout(() => {
                            menu.style.overflow = 'auto';
                        }, 0);
                    } else {
                        menu.classList.remove('show', 'clicked');
                    }
                });

                // Close when clicking outside
                document.addEventListener('click', function (e) {
                    if (dropdown && menu && (!dropdown.contains(e.target) && !menu.contains(e.target))) {
                        dropdown.classList.remove('active');
                        menu.classList.remove('show', 'clicked');
                        isClickOpen = false;
                    }
                });

                // Handle hover events - only if not clicked open
                dropdown.addEventListener('mouseenter', function () {
                    if (!isClickOpen) {
                        menu.classList.add('show');
                        adjustDropdownPosition();
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

                // Adjust position on scroll and resize
                window.addEventListener('scroll', () => {
                    if (menu.classList.contains('show')) {
                        adjustDropdownPosition();
                    }
                });

                window.addEventListener('resize', () => {
                    if (menu.classList.contains('show')) {
                        adjustDropdownPosition();
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hamburger = document.querySelector('.hamburger-icon');
            const sidebar = document.querySelector('.sidebar');
            const body = document.body;
            let isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            // Initialize sidebar state
            if (isSidebarCollapsed) {
                sidebar.classList.add('collapsed');
                hamburger.classList.add('active');
                body.classList.add('sidebar-collapsed');
            }

            hamburger.addEventListener('click', function () {
                isSidebarCollapsed = !isSidebarCollapsed;
                hamburger.classList.toggle('active');
                sidebar.classList.toggle('collapsed');
                body.classList.toggle('sidebar-collapsed');

                // Store the sidebar state
                localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);

                // Trigger window resize to adjust any responsive elements
                window.dispatchEvent(new Event('resize'));
            });

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    // Add any specific resize handling here if needed
                }, 250);
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Get current page URL and parameters
            const currentPath = window.location.pathname;
            const currentParams = new URLSearchParams(window.location.search);
            const currentPage = currentPath.split('/').pop();

            // Function to check if a menu item should be active
            function shouldBeActive(linkElement) {
                const href = linkElement.getAttribute('href');
                if (!href) return false;

                // Extract path and parameters from the link
                const linkUrl = new URL(href, window.location.origin);
                const linkPath = linkUrl.pathname;
                const linkParams = new URLSearchParams(linkUrl.search);

                // For intemplates.php, check both the page and category parameter
                if (currentPage === 'intemplates.php') {
                    const currentCategory = currentParams.get('category');
                    const linkCategory = linkParams.get('category');
                    return linkPath.endsWith(currentPage) && currentCategory === linkCategory;
                }

                // For other pages, just check the page name
                return linkPath.endsWith(currentPage);
            }

            // Find and mark active menu items
            const menuItems = document.querySelectorAll('.menu a');
            menuItems.forEach(item => {
                if (shouldBeActive(item)) {
                    item.classList.add('active-option');

                    // If this is inside a dropdown, show the dropdown as active
                    const parentDropdown = item.closest('.dropdown');
                    if (parentDropdown) {
                        const dropdownToggle = parentDropdown.querySelector('.dropdown-toggle');
                        if (dropdownToggle) {
                            dropdownToggle.classList.add('active-option');
                        }
                    }
                }
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
                    <?= basename($_SERVER['PHP_SELF']) == basename($dashboard_link) ? 'class="active-option"' : '' ?>>
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Reports Section -->
            <?php if ($user_role === 'Super Admin'): ?>
                <li>
                    <a href="../pages/reports.php" <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active-option"' : '' ?>>
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li>
                    <a href="../pages/admin_order_history.php" <?= basename($_SERVER['PHP_SELF']) == 'admin_order_history.php' ? 'class="active-option"' : '' ?>>
                        <i class="fas fa-history"></i>
                        <span>Order History</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Setup Section -->
            <?php if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff'): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-cog"></i>
                        <span>Setup</span>
                        <i class="fas fa-chevron-right toggle-icon"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if ($user_role === 'Admin' || $user_role === 'Super Admin'): ?>
                            <li>
                                <a href="../pages/admin_setup.php" <?= basename($_SERVER['PHP_SELF']) == 'admin_setup.php' ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-user-shield"></i>
                                    <span>
                                        <?php echo ($user_role === 'Super Admin') ? 'Admin Setup' : 'Admins'; ?>
                                    </span>
                                </a>
                            </li>
                            <li>
                                <a href="../pages/staff_setup.php" <?= basename($_SERVER['PHP_SELF']) == 'staff_setup.php' ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-users"></i>
                                    <span>Staff Setup</span>
                                </a>
                            </li>
                            <li>
                                <a href="../pages/cat_setup.php" <?= basename($_SERVER['PHP_SELF']) == 'cat_setup.php' ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-folder-open"></i>
                                    <span>Category Setup</span>
                                </a>
                            </li>
                            <li>
                                <a href="../pages/media_type_setup.php"
                                    <?= basename($_SERVER['PHP_SELF']) == 'media_type_setup.php' ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-photo-video"></i>
                                    <span>Media Type Setup</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="../pages/template_setup.php" <?= basename($_SERVER['PHP_SELF']) == 'template_setup.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-file-alt"></i>
                                <span>Template Setup</span>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($user_role === 'Admin'): ?>
                <li>
                    <a href="../pages/orders.php" <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'class="active-option"' : '' ?>>
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Requests Section -->
            <?php if ($user_role === 'Admin' || $user_role === 'Super Admin' || $user_role === 'Staff'): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-inbox"></i>
                        <span>Requests</span>
                        <i class="fas fa-chevron-right toggle-icon"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="../pages/manage_custom_requests.php"
                                <?= basename($_SERVER['PHP_SELF']) == 'manage_custom_requests.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-tasks"></i>
                                <span>Custom Requests</span>
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
                                    <span>Template Request</span>
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
                        <span>Templates</span>
                        <i class="fas fa-chevron-right toggle-icon"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if ($user_role === 'Staff'): ?>
                            <li>
                                <a href="../pages/your_template.php" <?= basename($_SERVER['PHP_SELF']) == 'your_template.php' ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-user-edit"></i>
                                    <span>Your Templates</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="../pages/intemplates.php?category=all" <?= (isset($_GET['category']) && $_GET['category'] == 'all') ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-th-large"></i>
                                <span>All Templates</span>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="../pages/intemplates.php?category=<?= $category['c_id'] ?>"
                                    <?= (isset($_GET['category']) && $_GET['category'] == $category['c_id']) ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-folder"></i>
                                    <span><?= htmlspecialchars($category['c_Name']) ?></span>
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
                        <span>Our Services</span>
                        <i class="fas fa-chevron-right toggle-icon"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="../pages/staff_setup.php" <?= basename($_SERVER['PHP_SELF']) == 'staff_setup.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-users"></i>
                                <span>Designer</span>
                            </a>
                        </li>
                        <li>
                            <a href="../pages/custom_template.php" <?= basename($_SERVER['PHP_SELF']) == 'custom_template.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-magic"></i>
                                <span>Custom Template</span>
                            </a>
                        </li>
                        <li>
                            <a href="../pages/template_finishing.php"
                                <?= basename($_SERVER['PHP_SELF']) == 'template_finishing.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-paint-brush"></i>
                                <span>Template Finishing</span>
                            </a>
                        </li>
                        <li>
                            <a href="../pages/your_orders.php" <?= basename($_SERVER['PHP_SELF']) == 'your_orders.php' ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-shopping-bag"></i>
                                <span>Your Orders</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Our Products Dropdown for Customers -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-store"></i>
                        <span>Our Products</span>
                        <i class="fas fa-chevron-right toggle-icon"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="../pages/intemplates.php?category=all" <?= (isset($_GET['category']) && $_GET['category'] == 'all') ? 'class="active-option"' : '' ?>>
                                <i class="fas fa-th-large"></i>
                                <span>All Templates</span>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="../pages/intemplates.php?category=<?= $category['c_id'] ?>"
                                    <?= (isset($_GET['category']) && $_GET['category'] == $category['c_id']) ? 'class="active-option"' : '' ?>>
                                    <i class="fas fa-folder"></i>
                                    <span><?= htmlspecialchars($category['c_Name']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>

        </ul>
    </div>

    <!-- Move hamburger icon after sidebar for proper positioning -->
    <div class="hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
    </div>
</body>

</html>