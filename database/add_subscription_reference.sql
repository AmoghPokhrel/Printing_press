-- Check if the column exists first to avoid errors
SET @dbname = DATABASE();
SET @tablename = "subscriptions";
SET @columnname = "subscription_reference";
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @dbname
        AND TABLE_NAME = @tablename
        AND COLUMN_NAME = @columnname
    ) > 0,
    "SELECT 'Column already exists'",
    "ALTER TABLE subscriptions ADD COLUMN subscription_reference varchar(255) DEFAULT NULL AFTER payment_reference"
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists; 