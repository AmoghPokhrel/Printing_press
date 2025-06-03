-- Create table for Wedding Card category (category_id = 28)
CREATE TABLE IF NOT EXISTS `additional_info_28` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `template_modification_id` INT NOT NULL,
    `modification_reason` TEXT,
    `bride_name` VARCHAR(255),
    `groom_name` VARCHAR(255),
    `wedding_date` DATE,
    `venue` TEXT,
    `rsvp_contact` VARCHAR(255),
    `special_instructions` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX idx_template_mod_id ON `additional_info_28` (`template_modification_id`); 