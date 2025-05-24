-- Add foreign key constraint to link with additional_info tables
ALTER TABLE template_modification
ADD CONSTRAINT fk_template_modification_additional_info
FOREIGN KEY (id) REFERENCES additional_info_28(request_id)
ON DELETE CASCADE; 