<?php
require_once '../includes/db.php';

function executeSQL($conn, $sql)
{
    try {
        if ($conn->query($sql) === TRUE) {
            echo "Success: " . substr($sql, 0, 50) . "...\n";
            return true;
        } else {
            echo "Error: " . $conn->error . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Create Subscriptions table if it doesn't exist
$sql_subscriptions = "
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
    `subscription_type` varchar(50) NOT NULL DEFAULT 'premium',
    `start_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_date` timestamp NULL DEFAULT NULL,
    `payment_reference` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create Subscription payments table if it doesn't exist
$sql_subscription_payments = "
CREATE TABLE IF NOT EXISTS `subscription_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `payment_method` varchar(50) NOT NULL DEFAULT 'esewa',
    `transaction_id` varchar(255) DEFAULT NULL,
    `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
    `payment_date` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `subscription_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create Subscription limits table if it doesn't exist
$sql_subscription_limits = "
CREATE TABLE IF NOT EXISTS `subscription_limits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `custom_design_count` int(11) NOT NULL DEFAULT 0,
    `template_modification_count` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    CONSTRAINT `subscription_limits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

echo "Starting database setup...\n";

// Execute the SQL statements
$success = true;
$success &= executeSQL($conn, $sql_subscriptions);
$success &= executeSQL($conn, $sql_subscription_payments);
$success &= executeSQL($conn, $sql_subscription_limits);

if ($success) {
    echo "\nDatabase setup completed successfully!\n";
} else {
    echo "\nDatabase setup completed with errors. Please check the error messages above.\n";
}