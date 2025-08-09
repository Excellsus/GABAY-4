<?php
include 'connect_db.php';

try {
    // Step 1: Remove the foreign key constraint if it exists
    $connect->exec("SET FOREIGN_KEY_CHECKS=0");
    
    try {
        $connect->exec("ALTER TABLE activities DROP FOREIGN KEY activities_ibfk_1");
        echo "Existing foreign key constraint removed.\n";
    } catch (PDOException $e) {
        echo "No existing constraint found or already removed.\n";
    }
    
    // Step 2: Clean up invalid references
    $connect->exec("UPDATE activities SET office_id = NULL 
                   WHERE office_id NOT IN (SELECT id FROM offices)");
    echo "Cleaned up invalid office references.\n";
    
    // Step 3: Make sure office_id allows NULL
    $connect->exec("ALTER TABLE activities MODIFY office_id INT NULL");
    echo "Modified office_id to allow NULL values.\n";
    
    // Step 4: Add the foreign key constraint back
    $connect->exec("ALTER TABLE activities 
                   ADD CONSTRAINT activities_ibfk_1 
                   FOREIGN KEY (office_id) 
                   REFERENCES offices(id) 
                   ON DELETE SET NULL");
    echo "Added new foreign key constraint.\n";
    
    // Step 5: Re-enable foreign key checks
    $connect->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "Successfully completed all steps.\n";
    
    // Verify the changes
    echo "\nCurrent activities table structure:\n";
    $stmt = $connect->query("SHOW CREATE TABLE activities");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'] . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 