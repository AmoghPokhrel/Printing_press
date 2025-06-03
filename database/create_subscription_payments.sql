-- Create subscription_payments table
CREATE TABLE IF NOT EXISTS `subscription_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `payment_method` varchar(50) NOT NULL DEFAULT 'esewa',
    `transaction_id` varchar(255) DEFAULT NULL,
    `status` enum('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `payment_date` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `idx_transaction` (`transaction_id`),
    KEY `idx_status_date` (`status`, `payment_date`),
    CONSTRAINT `subscription_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add subscription_id column if needed for reference
ALTER TABLE subscription_payments
ADD COLUMN IF NOT EXISTS subscription_id int(11) DEFAULT NULL AFTER user_id,
ADD CONSTRAINT `subscription_payments_ibfk_2` 
FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL;

-- Add subscription_type column to track which type of subscription was purchased
ALTER TABLE subscription_payments
ADD COLUMN IF NOT EXISTS subscription_type enum('free', 'premium') NOT NULL DEFAULT 'premium' AFTER amount;

-- Add subscription_period column to track duration
ALTER TABLE subscription_payments
ADD COLUMN IF NOT EXISTS subscription_period int(11) NOT NULL DEFAULT 30 AFTER subscription_type COMMENT 'Duration in days'; 