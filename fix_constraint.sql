USE admin;

-- Drop the existing foreign key constraint
ALTER TABLE activities DROP FOREIGN KEY activities_ibfk_1;

-- Modify the office_id column to allow NULL values
ALTER TABLE activities MODIFY COLUMN office_id INT NULL;

-- Add the new foreign key constraint with ON DELETE SET NULL
ALTER TABLE activities 
ADD CONSTRAINT activities_ibfk_1 
FOREIGN KEY (office_id) 
REFERENCES offices(id) 
ON DELETE SET NULL; 