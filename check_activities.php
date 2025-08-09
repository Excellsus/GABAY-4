<?php
include 'connect_db.php';

try {
    // First, let's check for any activities with office_ids that don't exist in offices table
    $query = "SELECT a.id, a.office_id, a.activity_type, a.activity_text 
              FROM activities a 
              LEFT JOIN offices o ON a.office_id = o.id 
              WHERE a.office_id IS NOT NULL 
              AND o.id IS NULL";
    
    $stmt = $connect->query($query);
    $invalid_refs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($invalid_refs) > 0) {
        echo "Found invalid office references in activities table:\n";
        foreach ($invalid_refs as $ref) {
            echo "Activity ID: " . $ref['id'] . ", Invalid Office ID: " . $ref['office_id'] . 
                 ", Type: " . $ref['activity_type'] . ", Text: " . $ref['activity_text'] . "\n";
            
            // Set these invalid references to NULL
            $update = $connect->prepare("UPDATE activities SET office_id = NULL WHERE id = ?");
            $update->execute([$ref['id']]);
        }
        echo "\nFixed invalid references by setting them to NULL\n";
    } else {
        echo "No invalid office references found in activities table.\n";
    }
    
    // Now show the current structure of both tables
    echo "\nActivities table structure:\n";
    foreach($connect->query("SHOW CREATE TABLE activities") as $row) {
        print_r($row[1] . "\n");
    }
    
    echo "\nOffices table structure:\n";
    foreach($connect->query("SHOW CREATE TABLE offices") as $row) {
        print_r($row[1] . "\n");
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 