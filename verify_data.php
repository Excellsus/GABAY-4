<?php
include 'connect_db.php';

try {
    echo "=== Data Verification Report ===\n\n";
    
    // 1. Check for any remaining invalid office_id references
    $query1 = "SELECT COUNT(*) as invalid_count 
               FROM activities a 
               LEFT JOIN offices o ON a.office_id = o.id 
               WHERE a.office_id IS NOT NULL AND o.id IS NULL";
    $result1 = $connect->query($query1)->fetch(PDO::FETCH_ASSOC);
    echo "Invalid office references: " . $result1['invalid_count'] . "\n";

    // 2. Show sample of recent activities with their office references
    $query2 = "SELECT a.id, a.activity_type, a.activity_text, a.office_id, o.name as office_name, a.created_at
               FROM activities a
               LEFT JOIN offices o ON a.office_id = o.id
               ORDER BY a.created_at DESC LIMIT 5";
    $result2 = $connect->query($query2)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nRecent Activities:\n";
    foreach ($result2 as $activity) {
        echo "ID: " . $activity['id'] . 
             "\nType: " . $activity['activity_type'] .
             "\nText: " . $activity['activity_text'] .
             "\nOffice ID: " . ($activity['office_id'] ?? 'NULL') .
             "\nOffice Name: " . ($activity['office_name'] ?? 'NULL') .
             "\nCreated: " . $activity['created_at'] . "\n\n";
    }

    // 3. Show office count and activity count
    $query3 = "SELECT COUNT(*) as office_count FROM offices";
    $query4 = "SELECT COUNT(*) as activity_count FROM activities";
    
    $office_count = $connect->query($query3)->fetch(PDO::FETCH_ASSOC)['office_count'];
    $activity_count = $connect->query($query4)->fetch(PDO::FETCH_ASSOC)['activity_count'];
    
    echo "\nSummary:\n";
    echo "Total Offices: " . $office_count . "\n";
    echo "Total Activities: " . $activity_count . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 