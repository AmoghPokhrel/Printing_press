CREATE TABLE IF NOT EXISTS `payment_tracking` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `transaction_uuid` varchar(255) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `status` enum('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `transaction_uuid` (`transaction_uuid`),
    KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 