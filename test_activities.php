<?php
include("connect_db.php");

try {
    // Test 1: Check if activities table exists
    $stmt = $connect->query("SHOW TABLES LIKE 'activities'");
    $tableExists = $stmt->rowCount() > 0;
    echo "Activities table exists: " . ($tableExists ? "Yes" : "No") . "<br>";

    if ($tableExists) {
        // Test 2: Check activities data
        $stmt = $connect->query("SELECT * FROM activities ORDER BY created_at DESC");
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Activities:</h3>";
        if (count($activities) > 0) {
            foreach ($activities as $activity) {
                echo "Type: " . $activity['activity_type'] . 
                     ", Text: " . $activity['activity_text'] . 
                     ", Time: " . $activity['created_at'] . "<br>";
            }
        } else {
            echo "No activities found in the table.<br>";
        }
    }

    // Test 3: Try to insert a test activity
    $stmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text) VALUES ('test', 'Test activity from diagnostic script')");
    $stmt->execute();
    echo "<br>Test activity inserted successfully!";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 