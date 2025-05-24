<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die('User not logged in');
}

$userId = $_SESSION['user_id'];

// Fetch cart items with template details
$cartQuery = "SELECT c.id as cart_id, c.quantity, t.id as template_id, t.name, t.cost, t.image_path 
              FROM cart c 
              JOIN templates t ON c.tid = t.id 
              WHERE c.user_id = ?";
$stmt = $conn->prepare($cartQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartResult = $stmt->get_result();
$cartItems = $cartResult->fetch_all(MYSQLI_ASSOC);

// Calculate cart total
$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['cost'] * $item['quantity'];
}

if (empty($cartItems)): ?>
    <div style="text-align: center; padding: 20px;">
        Your cart is empty
    </div>
<?php else: ?>
    <?php foreach ($cartItems as $item): ?>
        <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
            <img src="../uploads/templates/<?php echo htmlspecialchars($item['image_path'] ?? 'default.jpg'); ?>"
                alt="<?php echo htmlspecialchars($item['name']); ?>">
            <div class="cart-item-details">
                <div class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="cart-item-price">$<?php echo number_format($item['cost'], 2); ?></div>
                <div class="quantity-control">
                    <button type="button" class="quantity-btn minus-btn">-</button>
                    <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" readonly>
                    <button type="button" class="quantity-btn plus-btn">+</button>
                </div>
            </div>
            <button class="delete-cart-item-btn" title="Remove from cart"
                style="background: none; border: none; color: #e74c3c; font-size: 20px; cursor: pointer; margin-left: 10px;">&times;</button>
        </div>
    <?php endforeach; ?>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Total:</span>
            <span>$<?php echo number_format($cartTotal, 2); ?></span>
        </div>
        <button class="checkout-btn">Proceed to Checkout</button>
    </div>
<?php endif; ?>