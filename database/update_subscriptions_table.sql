-- First, modify the status enum to include all required states
ALTER TABLE subscriptions 
MODIFY COLUMN status enum('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active';

-- Update subscription_type enum to include all required types
ALTER TABLE subscriptions 
MODIFY COLUMN subscription_type enum('free', 'premium') NOT NULL DEFAULT 'free';

-- Add payment_reference column if it doesn't exist
ALTER TABLE subscriptions 
ADD COLUMN IF NOT EXISTS payment_reference varchar(255) DEFAULT NULL AFTER end_date;

-- Add subscription_reference column if it doesn't exist
ALTER TABLE subscriptions 
ADD COLUMN IF NOT EXISTS subscription_reference varchar(255) DEFAULT NULL AFTER payment_reference;

-- Make sure timestamps have correct defaults
ALTER TABLE subscriptions 
MODIFY COLUMN start_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN end_date timestamp NULL DEFAULT NULL,
MODIFY COLUMN created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP; 