<?php
require_once '../includes/db.php';

// SQL to create the receipt numbers table
$sql = "CREATE TABLE IF NOT EXISTS `reciept_numbers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `receipt_number` varchar(50) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    CONSTRAINT `reciept_numbers_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    // Execute the SQL
    if ($conn->query($sql) === TRUE) {
        echo "Receipt numbers table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();