-- Add useful indexes for better query performance
ALTER TABLE payments
ADD INDEX IF NOT EXISTS idx_transaction_id (transaction_id),
ADD INDEX IF NOT EXISTS idx_status_date (status, payment_date);

-- Add payment reference column for external reference numbers
ALTER TABLE payments
ADD COLUMN IF NOT EXISTS payment_reference varchar(255) DEFAULT NULL AFTER transaction_id;

-- Add payment description column
ALTER TABLE payments
ADD COLUMN IF NOT EXISTS payment_description text DEFAULT NULL AFTER amount;

-- Add payment type column to distinguish different types of payments
ALTER TABLE payments
ADD COLUMN IF NOT EXISTS payment_type enum('order', 'subscription', 'other') NOT NULL DEFAULT 'order' AFTER payment_method;

-- Add metadata column for additional JSON data
ALTER TABLE payments
ADD COLUMN IF NOT EXISTS metadata JSON DEFAULT NULL AFTER payment_description;

-- Make sure timestamps have correct defaults
ALTER TABLE payments
MODIFY COLUMN payment_date timestamp NULL DEFAULT NULL,
MODIFY COLUMN created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP; 