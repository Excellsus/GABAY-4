<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect_db.php';

try {
    // Drop the existing foreign key constraint
    $connect->exec("ALTER TABLE activities DROP FOREIGN KEY activities_ibfk_1");
    
    // Add the new foreign key constraint with proper NULL handling
    $connect->exec("ALTER TABLE activities 
                   MODIFY COLUMN office_id INT NULL,
                   ADD CONSTRAINT activities_ibfk_1 
                   FOREIGN KEY (office_id) 
                   REFERENCES offices(id) 
                   ON DELETE SET NULL");
    
    echo "Successfully updated the foreign key constraint for activities table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 