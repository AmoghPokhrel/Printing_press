<?php
// session_start();
// $pageTitle = 'Templates';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['username'];
$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];

include '../includes/db.php';

// Fetch only ACTIVE cart items count
$cartCountQuery = "SELECT COALESCE(SUM(cil.quantity), 0) as count 
                  FROM cart c 
                  JOIN cart_item_line cil ON c.id = cil.cart_id 
                  WHERE c.uid = ? AND (cil.status = 'active' OR cil.status IS NULL)";
$stmt = $conn->prepare($cartCountQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartCount = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

if (isset($_SESSION['role']) && $_SESSION['role'] === 'Customer') {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $needs_preferences = $stmt->num_rows === 0;
    $stmt->close();
    $categories = $conn->query("SELECT c_id, c_Name FROM category ORDER BY c_Name ASC")->fetch_all(MYSQLI_ASSOC);
    $media_types = $conn->query("SELECT id, name FROM media_type ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset default margins and padding */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            color: #4b5563;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        header {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 64px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            z-index: 1019;
            transition: all 0.3s ease;
        }

        body.sidebar-collapsed header {
            left: 0;
            padding-left: 5rem;
        }

        .menu-icon {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 1.25rem;
            color: #4b5563;
            transition: color 0.2s ease;
            padding: 0.5rem;
            border-radius: 0.375rem;
            margin-left: 0;
        }

        .menu-icon:hover {
            color: #2563eb;
            background-color: #f8fafc;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            margin-left: 0;
            letter-spacing: -0.025em;
            transition: all 0.2s ease;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: auto;
        }

        /* Profile styles */
        .profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 9999px;
            background: #f8fafc;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e5e7eb;
        }

        .profile:hover {
            background: #f1f5f9;
            border-color: #d1d5db;
        }

        .profile-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.125rem;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
        }

        .profile-info {
            text-align: left;
            min-width: 150px;
            margin-left: 0.5rem;
        }

        .profile-info strong {
            display: block;
            font-size: 0.95rem;
            color: #2563eb;
            font-weight: 500;
            line-height: 1.4;
            letter-spacing: 0.01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: 'Poppins', sans-serif;
        }

        .profile-info small {
            font-size: 0.8rem;
            color: #4b5563;
            font-weight: 400;
            letter-spacing: 0.02em;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: 'Inter', sans-serif;
            margin-top: 2px;
        }

        /* Cart button styles */
        .cart-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            color: #4b5563;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            margin-left: 0.25rem;
        }

        .cart-btn:hover {
            background-color: #f8fafc;
            color: #2563eb;
            border-color: #e5e7eb;
        }

        .cart-icon {
            font-size: 1.25rem;
        }

        .cart-count {
            position: absolute;
            top: -0.15rem;
            right: -0.15rem;
            background-color: #ef4444;
            color: white;
            border-radius: 9999px;
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        /* Add responsive adjustments */
        @media (max-width: 768px) {
            .profile-info {
                display: none;
            }

            .cart-btn {
                padding: 0.5rem;
            }

            .page-title {
                font-size: 1.25rem;
            }
        }

        /* Cart Dropdown Styles */
        .cart-dropdown {
            position: absolute;
            right: 1.5rem;
            top: calc(100% + 0.75rem);
            width: 360px;
            background: #ffffff;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            border: 1px solid #f1f5f9;
            padding: 0;
            z-index: 1003;
            display: none;
            max-height: 480px;
            overflow-y: auto;
            transition: all 0.2s ease;
        }

        .cart-dropdown.active {
            display: block;
            animation: fadeInCart 0.2s ease;
        }

        @keyframes fadeInCart {
            from {
                opacity: 0;
                transform: translateY(-0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .cart-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            letter-spacing: -0.025em;
        }

        .cart-header button {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #64748b;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .cart-header button:hover {
            color: #ef4444;
            background: #fee2e2;
        }

        .cart-items {
            padding: 1rem 1.5rem;
            max-height: 360px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .cart-items::-webkit-scrollbar {
            width: 6px;
        }

        .cart-items::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .cart-items::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }

        .cart-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
            position: relative;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 4.5rem;
            height: 4.5rem;
            object-fit: cover;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cart-item img:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .cart-item-details {
            flex: 1;
            min-width: 0;
        }

        .cart-item-title {
            font-weight: 600;
            font-size: 0.875rem;
            color: #1e293b;
            margin-bottom: 0.25rem;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cart-item-price {
            font-size: 0.875rem;
            color: #0f766e;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .cart-item-notes {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .cart-qty-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-qty-btn {
            background: #f1f5f9;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            color: #1e293b;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .cart-qty-btn:hover {
            background: #e2e8f0;
            color: #0f766e;
        }

        .cart-qty-value {
            font-size: 0.875rem;
            color: #1e293b;
            font-weight: 500;
            min-width: 24px;
            text-align: center;
        }

        .cart-remove-btn {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
            position: absolute;
            top: 1rem;
            right: 0;
        }

        .cart-remove-btn:hover {
            background: #fee2e2;
        }

        .cart-footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            border-radius: 0 0 0.75rem 0.75rem;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(to right, #0f766e, #0d9488);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .checkout-btn:hover {
            background: linear-gradient(to right, #0d9488, #0f766e);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .cart-empty-message {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        .cart-select-checkbox {
            margin-right: 0.5rem;
            width: 1.125rem;
            height: 1.125rem;
            border-radius: 0.25rem;
            border: 2px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cart-select-checkbox:checked {
            background-color: #0f766e;
            border-color: #0f766e;
        }

        /* Main content adjustment - Remove duplicate padding/margin */
        .main-content {
            margin-left: 250px;
            padding-top: 64px;
            min-height: 100vh;
            background-color: #f8fafc;
            position: relative;
        }

        /* Remove these duplicate styles that were causing the extra spacing */
        body {
            padding-top: 0;
        }

        .main-content {
            margin-top: 0;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 0;
        }

        /* Profile Dropdown Styles */
        .profile {
            position: relative;
            cursor: pointer;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            display: none;
            z-index: 1016;
            overflow: hidden;
            animation: dropdownFade 0.2s ease;
        }

        .profile-dropdown.active {
            display: block;
        }

        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item i {
            width: 20px;
            color: #666;
        }

        .dropdown-item.logout {
            color: #dc3545;
            border-top: 1px solid #eee;
        }

        .dropdown-item.logout i {
            color: #dc3545;
        }

        .dropdown-item.logout:hover {
            background-color: #fff5f5;
        }

        /* Add these styles for the image modal */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(5px);
        }

        .image-modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            animation: zoom 0.3s ease;
        }

        .image-modal-caption {
            margin: 8px auto;
            text-align: center;
            color: white;
            font-size: 14px;
            font-weight: 500;
        }

        .image-modal-close {
            position: absolute;
            top: 10px;
            right: 25px;
            color: #f1f1f1;
            font-size: 30px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            z-index: 1051;
        }

        @keyframes zoom {
            from {
                transform: scale(0.1)
            }

            to {
                transform: scale(1)
            }
        }

        .cart-item img {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .cart-item img:hover {
            transform: scale(1.05);
        }

        /* Preference Modal Styles */
        #preferenceModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(30, 41, 59, 0.25);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(7px);
        }

        #preferenceModal .modal-content {
            background: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }

        #preferenceModal h2 {
            color: #1e293b;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        #preferenceModal label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4b5563;
            font-weight: 500;
            font-size: 0.875rem;
        }

        #preferenceModal select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            background-color: #ffffff;
            color: #1e293b;
            font-size: 0.875rem;
        }

        #preferenceModal button[type="submit"] {
            width: 100%;
            padding: 0.75rem;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #preferenceModal button[type="submit"]:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        /* Toast Notification - should be above all */
        #cartToast {
            z-index: 1060;
        }

        header .profile div {
            text-align: right;
            font-size: 14px;
        }

        /* Notification Styles */
        .notifications-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            color: #4b5563;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            margin-right: 0.5rem;
        }

        .notifications-btn:hover {
            background-color: #f8fafc;
            color: #2563eb;
            border-color: #e5e7eb;
        }

        .notifications-btn i {
            font-size: 1.25rem;
        }

        .notification-count {
            position: absolute;
            top: -0.15rem;
            right: -0.15rem;
            background-color: #ef4444;
            color: white;
            border-radius: 9999px;
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        .notifications-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 1000;
            margin-top: 0.5rem;
        }

        .notifications-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .notifications-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }

        #closeNotifications {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        #closeNotifications:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 0.5rem 0;
        }

        .notification-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
            cursor: pointer;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: #f8fafc;
        }

        .notification-item.unread {
            background-color: #eff6ff;
        }

        .notification-item.unread:hover {
            background-color: #dbeafe;
        }

        .notification-content {
            font-size: 0.875rem;
            color: #4b5563;
            line-height: 1.25rem;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .empty-notifications {
            padding: 2rem 1rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <header>
        <div class="menu-icon">
            <!-- <i class="fas fa-bars"></i> -->
            <span>
                <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
            </span>
        </div>
        <div class="header-right">
            <!-- Notifications -->
            <?php
            // Get initial unread notification count
            $unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
            $stmt = $conn->prepare($unread_query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $initial_unread_count = (int) $result->fetch_assoc()['count'];
            $stmt->close();
            ?>
            <div class="notifications-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notification-count" id="notificationCount">
                    <?php echo $initial_unread_count; ?>
                </span>
                <div class="notifications-dropdown" id="notificationsDropdown">
                    <div class="notifications-header">
                        <h3>Notifications</h3>
                        <button id="closeNotifications"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="notifications-list" id="notificationsList">
                        <!-- Notifications will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="profile" onclick="toggleProfileDropdown()">
                <div class="profile-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <strong>
                        <?php echo htmlspecialchars($userName); ?>
                    </strong>
                    <small>
                        <?php echo ucfirst($userRole); ?>
                    </small>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        Profile
                    </a>
                    <a href="../pages/logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
            <?php if ($userRole === 'Customer'): ?>
                <button class="cart-btn" onclick="toggleCart()">
                    <i class="fas fa-shopping-cart cart-icon"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <!-- Add Cart Dropdown Structure -->
                <div class="cart-dropdown" id="cartDropdown">
                    <div class="cart-header">
                        <h3>Your Cart</h3>
                        <button id="closeCart"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="cart-items" id="cartItemsContainer">
                        <!-- Cart items will be loaded here dynamically -->
                    </div>
                    <div class="cart-footer">
                        <button type="button" class="checkout-btn" onclick="checkoutSelectedItems()">
                            Checkout Selected Items
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Toast Notification -->
    <div id="cartToast"
        style="display:none;position:fixed;top:20px;right:20px;z-index:10000;background:#2ecc71;color:#fff;padding:14px 24px;border-radius:6px;font-size:16px;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:opacity 0.3s;opacity:0;">
    </div>

    <!-- Add Image Modal -->
    <div id="cartImageModal" class="image-modal">
        <span class="image-modal-close">&times;</span>
        <img class="image-modal-content" id="cartModalImage">
        <div id="cartModalCaption" class="image-modal-caption"></div>
    </div>

    <?php if (isset($needs_preferences) && $needs_preferences): ?>
        <div id="preferenceModal">
            <form method="post" class="modal-content" autocomplete="off">
                <h2>Set Your Preferences</h2>
                <label for="preferred_color_scheme">Preferred Color Scheme</label>
                <select name="preferred_color_scheme" id="preferred_color_scheme" required>
                    <option value="">Select</option>
                    <option value="Custom">Custom</option>
                    <option value="Black and White">Black and White</option>
                    <option value="Grayscale">Grayscale</option>
                </select>
                <label for="preferred_category">Preferred Category</label>
                <select name="preferred_category" id="preferred_category" required>
                    <option value="">Select</option>
                    <?php foreach (isset($categories) ? $categories : [] as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['c_Name']) ?>">
                            <?= htmlspecialchars($cat['c_Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="preferred_media_type">Preferred Media Type</label>
                <select name="preferred_media_type" id="preferred_media_type" required>
                    <option value="">Select</option>
                    <?php foreach (isset($media_types) ? $media_types : [] as $mt): ?>
                        <option value="<?= htmlspecialchars($mt['name']) ?>">
                            <?= htmlspecialchars($mt['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="save_preferences" class="btn btn-primary">Save Preferences</button>
            </form>
        </div>
        <script>
            // Prevent navigation while modal is open
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('a').forEach(a => {
                    a.addEventListener('click', function (e) {
                        if (document.getElementById('preferenceModal')) e.preventDefault();
                    });
                });
            });
        </script>
    <?php endif; ?>

    <script>
        // Cart Dropdown Toggle
        function toggleCart() {
            const cartDropdown = document.getElementById('cartDropdown');
            cartDropdown.classList.toggle('active');
            if (cartDropdown.classList.contains('active')) {
                loadCartItems();
            }
        }

        // Close cart when clicking outside
        document.addEventListener('click', (e) => {
            const cartDropdown = document.getElementById('cartDropdown');
            const cartBtn = document.querySelector('.cart-btn');
            const cartImageModal = document.getElementById('cartImageModal');

            if (cartImageModal && cartImageModal.contains(e.target)) {
                return;
            }

            if (!cartDropdown.contains(e.target) && e.target !== cartBtn && !cartBtn.contains(e.target)) {
                cartDropdown.classList.remove('active');
            }
        });

        // Close cart button
        document.getElementById('closeCart').addEventListener('click', () => {
            document.getElementById('cartDropdown').classList.remove('active');
        });

        // Render cart items from JSON
        function loadCartItems() {
            fetch('/printing_press/pages/get_cart_items.php')
                .then(res => res.json())
                .then(data => {
                    const cartItemsContainer = document.getElementById('cartItemsContainer');
                    if (!data.success || !data.items || !data.items.length) {
                        cartItemsContainer.innerHTML = '<div class="cart-empty-message">Your cart is empty</div>';
                        document.querySelector('.cart-footer').style.display = 'none';
                        return;
                    }

                    let html = '';
                    let total = 0;
                    data.items.forEach(item => {
                        const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                        total += itemTotal;
                        html += `
                            <div class="cart-item" data-cart-id="${item.id}">
                                <input type="checkbox" class="cart-select-checkbox" value="${item.id}" checked onchange="updateCartTotal()">
                                <img src="${item.image}" 
                                     alt="${item.name}" 
                                     onclick="showCartImageModal(this.src, this.alt)"
                                     onerror="this.src='../assets/images/placeholder.jpg'" />
                                <div class="cart-item-details">
                                    <div class="cart-item-title">${item.name}</div>
                                    <div class="cart-item-price">Rs ${parseFloat(item.price).toFixed(2)}</div>
                                    ${item.notes ? `<div class="cart-item-notes">${item.notes}</div>` : ''}
                                    <div class="cart-qty-controls">
                                        <button class="cart-qty-btn" onclick="changeCartQty(${item.id}, -1)">-</button>
                                        <span class="cart-qty-value">${item.quantity}</span>
                                        <button class="cart-qty-btn" onclick="changeCartQty(${item.id}, 1)">+</button>
                                    </div>
                                </div>
                                <button class="cart-remove-btn" onclick="removeCartItem(${item.id})" title="Remove item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>`;
                    });

                    cartItemsContainer.innerHTML = html;
                    document.querySelector('.cart-footer').style.display = 'block';
                    updateCartTotal();
                })
                .catch(error => {
                    console.error('Error loading cart:', error);
                    document.getElementById('cartItemsContainer').innerHTML =
                        '<div class="cart-empty-message">Error loading cart items</div>';
                    document.querySelector('.cart-footer').style.display = 'none';
                });
        }

        // Change Cart Quantity
        function changeCartQty(itemId, change) {
            const qtyElement = document.querySelector(`.cart-item[data-cart-id="${itemId}"] .cart-qty-value`);
            let newQty = parseInt(qtyElement.textContent) + change;
            if (newQty < 1) newQty = 1;

            fetch('/printing_press/pages/update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&quantity=${newQty}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        qtyElement.textContent = newQty;
                        updateCartTotal();
                        updateCartBadge();
                        showCartToast('Cart updated successfully');
                    } else {
                        showCartToast('Error updating cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showCartToast('Error updating cart', 'error');
                });
        }

        // Remove Cart Item
        function removeCartItem(cartId) {
            if (!confirm('Are you sure you want to remove this item?')) return;

            fetch('/printing_press/pages/update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${cartId}&action=remove`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showCartToast(data.message);
                        loadCartItems();
                        updateCartBadge();
                    } else {
                        showCartToast(data.message || 'Error removing item', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showCartToast('Error removing item', 'error');
                });
        }

        // Show Cart Image Modal
        function showCartImageModal(src, alt) {
            const modal = document.getElementById('cartImageModal');
            const modalImg = document.getElementById('cartModalImage');
            const captionText = document.getElementById('cartModalCaption');

            modal.style.display = "block";
            modalImg.src = src;
            captionText.innerHTML = alt;
        }

        // Close Cart Image Modal
        document.querySelector('.image-modal-close').onclick = function () {
            document.getElementById('cartImageModal').style.display = "none";
        }

        // Toast Notification
        function showCartToast(message, type = 'success') {
            const toast = document.getElementById('cartToast');
            if (!toast) return;

            toast.style.backgroundColor = type === 'success' ? '#2ecc71' : '#e74c3c';
            toast.textContent = message;
            toast.style.display = 'block';
            toast.style.opacity = '1';

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 300);
            }, 2000);
        }

        // Update Cart Badge
        function updateCartBadge() {
            fetch('/printing_press/pages/get_cart_count.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.querySelector('.cart-count');
                    if (badge) {
                        badge.textContent = data.count || '0';
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    }
                })
                .catch(error => console.error('Error updating cart badge:', error));
        }

        // Checkout Selected Items
        function checkoutSelectedItems() {
            const selectedItems = Array.from(document.querySelectorAll('.cart-select-checkbox:checked'))
                .map(cb => cb.value);

            if (selectedItems.length === 0) {
                showCartToast('Please select items to checkout', 'error');
                return;
            }

            window.location.href = '/printing_press/pages/checkout.php?items=' + selectedItems.join(',');
        }

        // Profile Dropdown Toggle
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('profileDropdown');
            const profile = document.querySelector('.profile');

            if (!profile.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Notifications functionality
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationsDropdown');
            dropdown.classList.toggle('active');
            if (dropdown.classList.contains('active')) {
                loadNotifications(true);
            }
        }

        // Set initial display of notification count
        document.addEventListener('DOMContentLoaded', () => {
            const countElement = document.getElementById('notificationCount');
            const count = <?php echo $initial_unread_count; ?>;
            if (countElement) {
                countElement.style.display = count > 0 ? 'flex' : 'none';
            }

            // Initial load of notifications
            loadNotifications(true);

            // Set up periodic refresh
            setInterval(() => loadNotifications(false), 30000);

            // Set up click handlers
            setupNotificationClickHandlers();
        });

        // Update notification count
        function updateNotificationCount(count) {
            const countElement = document.getElementById('notificationCount');
            if (countElement) {
                count = parseInt(count) || 0;
                countElement.textContent = count;
                countElement.style.display = count > 0 ? 'flex' : 'none';
            }
        }

        // Load notifications with cache prevention
        function loadNotifications(forceUpdate = false) {
            const timestamp = new Date().getTime();
            fetch(`/printing_press/pages/get_notifications.php?t=${timestamp}`, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationCount(data.unread_count);

                        const dropdown = document.getElementById('notificationsDropdown');
                        if (dropdown.classList.contains('active') || forceUpdate) {
                            renderNotifications(data.notifications);
                        }
                    } else {
                        console.error('Error loading notifications:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                });
        }

        // Render notifications
        function renderNotifications(notifications) {
            const container = document.getElementById('notificationsList');
            if (!notifications || !notifications.length) {
                container.innerHTML = '<div class="empty-notifications">No notifications</div>';
                return;
            }

            container.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
                     onclick="handleNotificationClick(${notification.id}, '${notification.reference_type}', ${notification.reference_id})">
                    <div class="notification-content">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                    </div>
                    <div class="notification-time">${formatDate(notification.created_at)}</div>
                </div>
            `).join('');
        }

        // Handle notification click with role-based redirection
        function handleNotificationClick(notificationId, referenceType, referenceId) {
            if (!notificationId) {
                console.error('Invalid notification ID');
                return;
            }

            fetch('/printing_press/pages/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Force update notifications
                        loadNotifications(true);

                        // Get user role from PHP session
                        const userRole = '<?php echo $_SESSION["role"]; ?>';

                        // Handle navigation based on role and reference type
                        if (referenceType && referenceId) {
                            switch (referenceType) {
                                case 'custom_template':
                                    if (userRole === 'Customer') {
                                        window.location.href = `/printing_press/pages/custom_template.php?id=${referenceId}`;
                                    } else {
                                        // For Staff and Admin
                                        window.location.href = `/printing_press/pages/manage_custom_requests.php?id=${referenceId}`;
                                    }
                                    break;

                                case 'template_finishing':
                                    // Same page for both staff and customer
                                    window.location.href = `/printing_press/pages/template_finishing.php?id=${referenceId}`;
                                    break;

                                case 'order':
                                    if (userRole === 'Customer') {
                                        window.location.href = `/printing_press/pages/your_orders.php?highlight=${referenceId}`;
                                    } else if (userRole === 'Admin') {
                                        window.location.href = `/printing_press/pages/orders.php?highlight=${referenceId}`;
                                    }
                                    break;

                                default:
                                    console.log('Unknown reference type:', referenceType);
                            }
                        }
                    } else {
                        console.error('Error marking notification as read:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
        }

        // Format date for notifications
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);

            if (days > 7) {
                return date.toLocaleDateString();
            } else if (days > 0) {
                return `${days} day${days > 1 ? 's' : ''} ago`;
            } else if (hours > 0) {
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            } else if (minutes > 0) {
                return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            } else {
                return 'Just now';
            }
        }

        function setupNotificationClickHandlers() {
            // Close notifications when clicking outside
            document.addEventListener('click', (e) => {
                const dropdown = document.getElementById('notificationsDropdown');
                const btn = document.querySelector('.notifications-btn');
                const closeBtn = document.getElementById('closeNotifications');

                if (!dropdown || !btn) return;

                // If clicking the close button, close the dropdown
                if (closeBtn && (e.target === closeBtn || closeBtn.contains(e.target))) {
                    dropdown.classList.remove('active');
                    e.stopPropagation();
                    return;
                }

                // If clicking outside the dropdown and not on the notifications button
                if (!dropdown.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            // Handle close button click
            const closeBtn = document.getElementById('closeNotifications');
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    const dropdown = document.getElementById('notificationsDropdown');
                    dropdown.classList.remove('active');
                    e.stopPropagation();
                });
            }
        }

        // Add new function to update cart total based on selected items
        function updateCartTotal() {
            const cartItems = document.querySelectorAll('.cart-item');
            let total = 0;

            cartItems.forEach(item => {
                const checkbox = item.querySelector('.cart-select-checkbox');
                if (checkbox.checked) {
                    const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace('Rs ', ''));
                    const quantity = parseInt(item.querySelector('.cart-qty-value').textContent);
                    total += price * quantity;
                }
            });

            const cartFooter = document.querySelector('.cart-footer');
            cartFooter.innerHTML = `
                <div class="cart-total">
                    <span>Total:</span>
                    <span>Rs ${total.toFixed(2)}</span>
                </div>
                <button type="button" class="checkout-btn" onclick="checkoutSelectedItems()">
                    <i class="fas fa-shopping-cart"></i>
                    Checkout Selected Items
                </button>`;
        }
    </script>
</body>

</html>