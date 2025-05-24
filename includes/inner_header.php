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
            padding: 0 1.5rem;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            z-index: 999;
            transition: left 0.3s ease;
        }

        header.sidebar-minimized {
            left: 80px;
        }

        .menu-icon {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.25rem;
            color: #4b5563;
            transition: color 0.2s ease;
            cursor: pointer;
        }

        .menu-icon:hover {
            color: #2563eb;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            letter-spacing: -0.025em;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        /* Profile styles */
        .profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            background: #f8fafc;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .profile:hover {
            background: #f1f5f9;
        }

        .profile-icon {
            width: 2.25rem;
            height: 2.25rem;
            background: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            font-weight: 500;
        }

        .profile-info {
            text-align: left;
        }

        .profile-info strong {
            display: block;
            font-size: 0.875rem;
            color: #1e293b;
            font-weight: 600;
            line-height: 1.25;
            letter-spacing: -0.025em;
        }

        .profile-info small {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
            letter-spacing: -0.025em;
        }

        /* Cart button styles */
        .cart-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.375rem;
            color: #4b5563;
            transition: all 0.2s ease;
        }

        .cart-btn:hover {
            background-color: #f8fafc;
            color: #2563eb;
        }

        .cart-icon {
            font-size: 1.5rem;
        }

        .cart-count {
            position: absolute;
            top: -0.25rem;
            right: -0.25rem;
            background-color: #ef4444;
            color: white;
            border-radius: 9999px;
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
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
            z-index: 1000;
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
        }

        /* Remove these duplicate styles that were causing the extra spacing */
        body {
            padding-top: 0;
            /* Remove this */
        }

        .main-content {
            margin-top: 0;
            /* Remove this */
            position: relative;
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
            z-index: 1000;
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
            z-index: 1001;
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
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuIcon = document.querySelector('.menu-icon');
            const sidebar = document.querySelector('.sidebar');
            const header = document.querySelector('header');
            const mainContent = document.querySelector('.main-content');

            // Check for saved state
            const isSidebarMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            if (isSidebarMinimized) {
                sidebar.classList.add('minimized');
                header.classList.add('sidebar-minimized');
                mainContent.classList.add('sidebar-minimized');
            }

            menuIcon.addEventListener('click', function () {
                sidebar.classList.toggle('minimized');
                header.classList.toggle('sidebar-minimized');
                mainContent.classList.toggle('sidebar-minimized');

                // Save state
                localStorage.setItem('sidebarMinimized', sidebar.classList.contains('minimized'));
            });
        });
    </script>
</head>

<body>
    <header>
        <div class="menu-icon">
            <i class="fas fa-bars"></i>
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
        </div>
        <div class="header-right">
            <div class="profile" onclick="toggleProfileDropdown()">
                <div class="profile-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($userName); ?></strong>
                    <small><?php echo ucfirst($userRole); ?></small>
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
                        <span class="cart-count"><?php echo $cartCount; ?></span>
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
        <style>
            body {
                overflow: hidden !important;
            }

            #preferenceModal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(30, 41, 59, 0.25);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(7px);
            }

            #preferenceModal .modal-content {
                background: #fff;
                padding: 2.5rem 2.5rem 2rem 2.5rem;
                border-radius: 18px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
                min-width: 350px;
                max-width: 95vw;
                width: 100%;
                max-width: 420px;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                animation: modalPopIn 0.3s cubic-bezier(.4, 2, .6, 1);
            }

            @keyframes modalPopIn {
                from {
                    transform: scale(0.95) translateY(30px);
                    opacity: 0;
                }

                to {
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }
            }

            #preferenceModal h2 {
                margin-bottom: 1.2rem;
                font-size: 1.5rem;
                color: #2563eb;
                text-align: center;
                font-weight: 700;
                letter-spacing: 0.5px;
            }

            #preferenceModal label {
                font-weight: 600;
                margin-top: 1.1rem;
                margin-bottom: 0.4rem;
                color: #334155;
                font-size: 1rem;
            }

            #preferenceModal select {
                width: 100%;
                padding: 10px 12px;
                border: 1.5px solid #cbd5e1;
                border-radius: 7px;
                font-size: 1rem;
                background: #f8fafc;
                transition: border-color 0.2s;
                margin-bottom: 0.2rem;
            }

            #preferenceModal select:focus {
                border-color: #2563eb;
                outline: none;
                background: #fff;
            }

            #preferenceModal button[type='submit'] {
                margin-top: 2rem;
                width: 100%;
                background: linear-gradient(90deg, #2563eb 0%, #60a5fa 100%);
                color: #fff;
                border: none;
                border-radius: 8px;
                padding: 13px 0;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
                transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
                letter-spacing: 0.5px;
            }

            #preferenceModal button[type='submit']:hover {
                background: linear-gradient(90deg, #1d4ed8 0%, #3b82f6 100%);
                box-shadow: 0 4px 16px rgba(37, 99, 235, 0.13);
                transform: translateY(-2px) scale(1.01);
            }
        </style>
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
                        <option value="<?= htmlspecialchars($cat['c_Name']) ?>"><?= htmlspecialchars($cat['c_Name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="preferred_media_type">Preferred Media Type</label>
                <select name="preferred_media_type" id="preferred_media_type" required>
                    <option value="">Select</option>
                    <?php foreach (isset($media_types) ? $media_types : [] as $mt): ?>
                        <option value="<?= htmlspecialchars($mt['name']) ?>"><?= htmlspecialchars($mt['name']) ?></option>
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
                                <input type="checkbox" class="cart-select-checkbox" value="${item.id}" checked>
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
                    document.querySelector('.cart-footer').innerHTML = `
                        <div class="cart-total">
                            <span>Total:</span>
                            <span>Rs ${total.toFixed(2)}</span>
                        </div>
                        <button type="button" class="checkout-btn" onclick="checkoutSelectedItems()">
                            <i class="fas fa-shopping-cart"></i>
                            Checkout Selected Items
                        </button>`;
                })
                .catch(error => {
                    console.error('Error loading cart:', error);
                    document.getElementById('cartItemsContainer').innerHTML =
                        '<div class="cart-empty-message">Error loading cart items</div>';
                    document.querySelector('.cart-footer').style.display = 'none';
                });
        }

        // Change Cart Quantity
        function changeCartQty(cartId, delta) {
            const item = document.querySelector(`[data-cart-id='${cartId}'] .cart-qty-value`);
            if (!item) return;

            let qty = parseInt(item.textContent) + delta;
            if (qty < 1) qty = 1;

            fetch('/printing_press/pages/update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${cartId}&action=update&quantity=${qty}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showCartToast(data.message);
                        loadCartItems();
                        updateCartBadge();
                    } else {
                        showCartToast(data.message || 'Error updating quantity', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showCartToast('Error updating quantity', 'error');
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
    </script>
</body>

</html>