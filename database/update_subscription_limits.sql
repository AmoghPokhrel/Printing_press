-- Add missing columns for subscription limits
ALTER TABLE subscription_limits
ADD COLUMN IF NOT EXISTS custom_design_count int(11) NOT NULL DEFAULT 0 AFTER user_id,
ADD COLUMN IF NOT EXISTS template_modification_count int(11) NOT NULL DEFAULT 0 AFTER custom_design_count,
ADD COLUMN IF NOT EXISTS download_count int(11) NOT NULL DEFAULT 0 AFTER template_modification_count,
ADD COLUMN IF NOT EXISTS monthly_limit int(11) NOT NULL DEFAULT 10 AFTER download_count;

-- Update last_reset_date to handle monthly resets
ALTER TABLE subscription_limits
MODIFY COLUMN last_reset_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Make sure timestamps have correct defaults
ALTER TABLE subscription_limits
MODIFY COLUMN created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add indexes for better performance
ALTER TABLE subscription_limits
ADD INDEX idx_last_reset_date (last_reset_date),
ADD INDEX idx_user_limits (user_id, custom_design_count, template_modification_count, download_count); 