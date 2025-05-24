-- Table for additional information field types
CREATE TABLE IF NOT EXISTS additional_field_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type VARCHAR(20) NOT NULL, -- 'text', 'textarea', 'image', 'date', 'time', 'email', 'url', 'tel'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for additional information fields
CREATE TABLE IF NOT EXISTS additional_info_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    field_type_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(100) NOT NULL,
    is_required BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(c_id),
    FOREIGN KEY (field_type_id) REFERENCES additional_field_types(id)
);

-- Insert default field types
INSERT INTO additional_field_types (name, type) VALUES
('Text Input', 'text'),
('Text Area', 'textarea'),
('Image Upload', 'image'),
('Date', 'date'),
('Time', 'time'),
('Email', 'email'),
('URL', 'url'),
('Phone Number', 'tel');

-- Procedure to create or update additional info tables
DELIMITER //
CREATE PROCEDURE create_additional_info_table(IN category_id INT)
BEGIN
    SET @table_name = CONCAT('additional_info_', category_id);
    
    -- Create the table if it doesn't exist
    SET @sql = CONCAT('
        CREATE TABLE IF NOT EXISTS ', @table_name, ' (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NULL,
            modification_id INT NULL,
            request_type ENUM(\'custom\', \'modification\') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES custom_template_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (modification_id) REFERENCES template_modifications(id) ON DELETE CASCADE,
            CHECK (
                (request_type = \'custom\' AND request_id IS NOT NULL AND modification_id IS NULL) OR
                (request_type = \'modification\' AND modification_id IS NOT NULL AND request_id IS NULL)
            )
        )
    ');
    
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    -- Get fields for this category
    SET @fields_sql = CONCAT('
        SELECT field_name, field_type_id 
        FROM additional_info_fields 
        WHERE category_id = ', category_id, '
        ORDER BY display_order
    ');
    
    -- Create a temporary table to store the fields
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_fields (
        field_name VARCHAR(100),
        field_type_id INT
    );
    
    -- Insert fields into temporary table
    INSERT INTO temp_fields
    EXECUTE @fields_sql;
    
    -- Add columns for each field
    DECLARE done INT DEFAULT FALSE;
    DECLARE field_name VARCHAR(100);
    DECLARE field_type_id INT;
    DECLARE cur CURSOR FOR SELECT field_name, field_type_id FROM temp_fields;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO field_name, field_type_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Get field type
        SELECT type INTO @field_type FROM additional_field_types WHERE id = field_type_id;
        
        -- Determine column type based on field type
        CASE @field_type
            WHEN 'text' THEN SET @column_type = 'VARCHAR(255)';
            WHEN 'textarea' THEN SET @column_type = 'TEXT';
            WHEN 'image' THEN SET @column_type = 'VARCHAR(255)';
            WHEN 'date' THEN SET @column_type = 'DATE';
            WHEN 'time' THEN SET @column_type = 'TIME';
            WHEN 'email' THEN SET @column_type = 'VARCHAR(255)';
            WHEN 'url' THEN SET @column_type = 'VARCHAR(255)';
            WHEN 'tel' THEN SET @column_type = 'VARCHAR(20)';
            ELSE SET @column_type = 'VARCHAR(255)';
        END CASE;
        
        -- Add column if it doesn't exist
        SET @alter_sql = CONCAT('
            ALTER TABLE ', @table_name, '
            ADD COLUMN IF NOT EXISTS ', field_name, ' ', @column_type
        );
        
        PREPARE stmt FROM @alter_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;
    
    -- Drop temporary table
    DROP TEMPORARY TABLE IF EXISTS temp_fields;
END //
DELIMITER ;

-- Procedure to update all existing additional info tables
DELIMITER //
CREATE PROCEDURE update_all_additional_info_tables()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE cat_id INT;
    DECLARE cur CURSOR FOR SELECT c_id FROM category;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO cat_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        CALL create_additional_info_table(cat_id);
    END LOOP;
    CLOSE cur;
END //
DELIMITER ;

-- Execute the update procedure
CALL update_all_additional_info_tables();

-- Clean up
DROP PROCEDURE IF EXISTS create_additional_info_table;
DROP PROCEDURE IF EXISTS update_all_additional_info_tables; 