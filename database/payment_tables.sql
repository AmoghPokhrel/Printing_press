-- Create the payments table for order payments
CREATE TABLE IF NOT EXISTS `payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_item_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `payment_method` varchar(50) NOT NULL DEFAULT 'esewa',
    `transaction_id` varchar(255) DEFAULT NULL,
    `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
    `payment_date` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_item_line` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the subscriptions table
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

-- Create the subscription_payments table
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

-- Create the subscription_limits table
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

-- Create the users table if it doesn't exist (required for foreign key constraints)
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the order table if it doesn't exist (required for order payments)
CREATE TABLE IF NOT EXISTS `order` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `uid` int(11) NOT NULL,
    `order_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    PRIMARY KEY (`id`),
    KEY `uid` (`uid`),
    CONSTRAINT `order_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the order_item_line table if it doesn't exist (required for order payments)
CREATE TABLE IF NOT EXISTS `order_item_line` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `oid` int(11) NOT NULL,
    `total_price` decimal(10,2) NOT NULL,
    `status` enum('pending','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `oid` (`oid`),
    CONSTRAINT `order_item_line_ibfk_1` FOREIGN KEY (`oid`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 