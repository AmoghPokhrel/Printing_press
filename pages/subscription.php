<?php
session_start();
require_once '../includes/dbcon.php';
require_once '../includes/header.php';
require_once '../includes/SubscriptionManager.php';
require_once '../includes/EsewaPayment.php';

$pageTitle = 'Subscription Plans';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$subscriptionManager = new SubscriptionManager($pdo, $user_id);
$esewa = new EsewaPayment();

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

// Get payment form data
$amount = 199.00; // Premium plan price
// Use absolute paths for success and failure URLs
$baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/printing_press/pages";
$successUrl = $baseUrl . "/subscription_payment_success.php";
$failureUrl = $baseUrl . "/payment_fail.php?type=subscription";

// Debug log the URLs and amount
error_log("Subscription Payment Form Generation:");
error_log("Amount: " . $amount);
error_log("Success URL: " . $successUrl);
error_log("Failure URL: " . $failureUrl);

$paymentData = $esewa->getPaymentForm($amount, $successUrl, $failureUrl);

// Debug log the payment form data
error_log("eSewa Payment Form Data: " . print_r($paymentData, true));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>
        .subscription-container {
            max-width: 800px;
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
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 40px;
        }

        .plan-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            display: grid;
            grid-template-columns: 2fr 3fr 2fr;
            align-items: center;
            gap: 30px;
        }

        .plan-card:hover {
            transform: translateY(-5px);
        }

        .plan-header {
            text-align: center;
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
            margin: 0;
            border-left: 2px solid #eee;
            padding-left: 30px;
        }

        .plan-features li {
            padding: 10px 0;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-features li:before {
            content: "✓";
            color: #2ecc71;
            font-weight: bold;
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
            max-width: 200px;
            justify-self: center;
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
            text-align: center;
        }

        @media (max-width: 768px) {
            .plan-card {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 20px;
            }

            .plan-features {
                border-left: none;
                border-top: 2px solid #eee;
                padding-left: 0;
                padding-top: 20px;
            }

            .plan-features li {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="subscription-container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

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
                        <div class="stat-value">
                            <?php
                            $custom_query = $pdo->prepare("SELECT COUNT(*) as count FROM custom_template_requests WHERE user_id = ? AND status != 'cancelled'");
                            $custom_query->execute([$user_id]);
                            echo $custom_query->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>/3
                        </div>
                        <div class="stat-label">Custom Designs Used</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            $mod_query = $pdo->prepare("SELECT COUNT(*) as count FROM template_modifications WHERE user_id = ? AND status != 'cancelled'");
                            $mod_query->execute([$user_id]);
                            echo $mod_query->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>/3
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
                        <div>
                            <button class="subscribe-btn disabled" disabled>Current Plan</button>
                            <p class="expiry-date">Renews on:
                                <?php echo date('F j, Y', strtotime($subscription['end_date'])); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <form id="esewaForm" action="<?php echo $paymentData['action_url']; ?>" method="POST">
                            <?php
                            // Add payment type to the form data
                            $paymentData['fields']['payment_type'] = 'subscription';
                            foreach ($paymentData['fields'] as $name => $value): ?>
                                <input type="hidden" name="<?php echo $name; ?>"
                                    value="<?php echo htmlspecialchars($value); ?>">
                            <?php endforeach; ?>
                            <button type="submit" class="subscribe-btn">
                                Subscribe Now with eSewa
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php include('../includes/footer.php'); ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const esewaForm = document.getElementById('esewaForm');
            if (esewaForm) {
                esewaForm.addEventListener('submit', function (e) {
                    // You could add a loading spinner or disable the button here
                    console.log('Submitting payment to eSewa...');
                });
            }
        });
    </script>
</body>

</html>