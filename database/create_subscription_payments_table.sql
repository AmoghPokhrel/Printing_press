-- Create subscription_payments table with structure similar to payments table
CREATE TABLE IF NOT EXISTS `subscription_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,                                    -- Instead of order_item_id
    `amount` decimal(10,2) NOT NULL,
    `payment_method` varchar(50) NOT NULL DEFAULT 'esewa',
    `transaction_id` varchar(255) DEFAULT NULL,
    `status` enum('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `payment_date` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `subscription_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 