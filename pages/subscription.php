<?php
session_start();
require_once '../includes/dbcon.php';
require_once '../includes/header.php';
require_once '../includes/SubscriptionManager.php';

$pageTitle = 'Subscription Plans';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$subscriptionManager = new SubscriptionManager($pdo, $user_id);

// Get current subscription status
$stmt = $pdo->prepare("
    SELECT status, subscription_type, end_date 
    FROM subscriptions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Get usage statistics
$stmt = $pdo->prepare("
    SELECT custom_design_count, template_modification_count 
    FROM subscription_limits 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$usage = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        .subscription-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .current-status {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .status-title {
            font-size: 1.5em;
            color: #2c3e50;
            margin: 0;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9em;
        }

        .status-active {
            background: #2ecc71;
            color: white;
        }

        .status-inactive {
            background: #e74c3c;
            color: white;
        }

        .usage-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: 600;
            color: #2c3e50;
            margin: 10px 0;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .plan-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-5px);
        }

        .plan-header {
            margin-bottom: 20px;
        }

        .plan-name {
            font-size: 1.5em;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }

        .plan-price {
            font-size: 2em;
            color: #2ecc71;
            margin: 0;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .plan-features li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #7f8c8d;
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .subscribe-btn {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .subscribe-btn:hover {
            background: #27ae60;
        }

        .subscribe-btn.disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .current-plan {
            border: 2px solid #2ecc71;
        }

        .current-plan .subscribe-btn {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .expiry-date {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="subscription-container">
            <div class="current-status">
                <div class="status-header">
                    <h2 class="status-title">Current Subscription Status</h2>
                    <span
                        class="status-badge <?php echo ($subscription && $subscription['status'] === 'active') ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo ($subscription && $subscription['status'] === 'active') ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>

                <?php if ($subscription && $subscription['status'] === 'active'): ?>
                    <p>Your premium subscription is active until:
                        <?php echo date('F j, Y', strtotime($subscription['end_date'])); ?>
                    </p>
                <?php else: ?>
                    <p>You are currently on the free plan.</p>
                <?php endif; ?>

                <div class="usage-stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $usage ? $usage['custom_design_count'] : 0; ?>/2</div>
                        <div class="stat-label">Custom Designs Used</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $usage ? $usage['template_modification_count'] : 0; ?>/2
                        </div>
                        <div class="stat-label">Template Modifications Used</div>
                    </div>
                </div>
            </div>

            <div class="plans-container">
                <div
                    class="plan-card <?php echo (!$subscription || $subscription['subscription_type'] === 'free') ? 'current-plan' : ''; ?>">
                    <div class="plan-header">
                        <h3 class="plan-name">Free Plan</h3>
                        <p class="plan-price">Rs 0</p>
                    </div>
                    <ul class="plan-features">
                        <li>2 Custom Design Requests</li>
                        <li>2 Template Modifications</li>
                        <li>Basic Support</li>
                        <li>Standard Processing Time</li>
                    </ul>
                    <button class="subscribe-btn disabled" disabled>Current Plan</button>
                </div>

                <div
                    class="plan-card <?php echo ($subscription && $subscription['subscription_type'] === 'premium') ? 'current-plan' : ''; ?>">
                    <div class="plan-header">
                        <h3 class="plan-name">Premium Plan</h3>
                        <p class="plan-price">Rs 199/month</p>
                    </div>
                    <ul class="plan-features">
                        <li>Unlimited Custom Design Requests</li>
                        <li>Unlimited Template Modifications</li>
                        <li>Priority Support</li>
                        <li>Faster Processing Time</li>
                        <li>Advanced Design Features</li>
                    </ul>
                    <?php if ($subscription && $subscription['subscription_type'] === 'premium'): ?>
                        <button class="subscribe-btn disabled" disabled>Current Plan</button>
                        <p class="expiry-date">Renews on:
                            <?php echo date('F j, Y', strtotime($subscription['end_date'])); ?>
                        </p>
                    <?php else: ?>
                        <button class="subscribe-btn" onclick="initiateSubscription()">Subscribe Now</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function initiateSubscription() {
            // Here you would integrate with your payment gateway
            // For now, we'll just show a message
            alert('Payment integration coming soon! This is where you would integrate with a payment gateway like Stripe or PayPal.');
        }
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>