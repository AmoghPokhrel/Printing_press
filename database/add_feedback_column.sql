-- Add feedback column to template_modifications table
ALTER TABLE template_modifications
ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL; 