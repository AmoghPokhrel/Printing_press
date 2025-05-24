ALTER TABLE template_modifications
ADD COLUMN satisfaction_status ENUM('Pending', 'Satisfied', 'Not Satisfied') DEFAULT 'Pending' AFTER status; 