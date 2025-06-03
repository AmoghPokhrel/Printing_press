-- Check and create Subscriptions table
DROP PROCEDURE IF EXISTS create_subscriptions_table;
DELIMITER //
CREATE PROCEDURE create_subscriptions_table()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'subscriptions') THEN
        CREATE TABLE `subscriptions` (
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
        
        SELECT 'Subscriptions table created successfully.' AS message;
    ELSE
        SELECT 'Subscriptions table already exists.' AS message;
    END IF;
END //
DELIMITER ;

-- Check and create Subscription payments table
DROP PROCEDURE IF EXISTS create_subscription_payments_table;
DELIMITER //
CREATE PROCEDURE create_subscription_payments_table()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'subscription_payments') THEN
        CREATE TABLE `subscription_payments` (
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
        
        SELECT 'Subscription payments table created successfully.' AS message;
    ELSE
        SELECT 'Subscription payments table already exists.' AS message;
    END IF;
END //
DELIMITER ;

-- Check and create Subscription limits table
DROP PROCEDURE IF EXISTS create_subscription_limits_table;
DELIMITER //
CREATE PROCEDURE create_subscription_limits_table()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'subscription_limits') THEN
        CREATE TABLE `subscription_limits` (
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
        
        SELECT 'Subscription limits table created successfully.' AS message;
    ELSE
        SELECT 'Subscription limits table already exists.' AS message;
    END IF;
END //
DELIMITER ;

-- Execute the procedures
CALL create_subscriptions_table();
CALL create_subscription_payments_table();
CALL create_subscription_limits_table();

-- Clean up procedures
DROP PROCEDURE IF EXISTS create_subscriptions_table;
DROP PROCEDURE IF EXISTS create_subscription_payments_table;
DROP PROCEDURE IF EXISTS create_subscription_limits_table; 