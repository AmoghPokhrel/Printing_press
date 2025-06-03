-- Create subscription limits table to track usage
CREATE TABLE IF NOT EXISTS `subscription_limits` (
    `limit_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `custom_design_count` INT DEFAULT 0,
    `template_modification_count` INT DEFAULT 0,
    `last_reset_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
); 