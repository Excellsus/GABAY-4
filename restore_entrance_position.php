<?php
require 'connect_db.php';

echo "Updating entrance_west_1 to your custom position...\n\n";

try {
    // Update entrance_west_1 to the original position you want
    $stmt = $connect->prepare("UPDATE entrance_qrcodes SET x = 70, y = 340 WHERE entrance_id = 'entrance_west_1'");
    $stmt->execute();
    
    echo "✅ Updated entrance_west_1 to X: 70, Y: 340\n\n";
    
    // Verify the update
    $stmt = $connect->query('SELECT entrance_id, floor, x, y, nearest_path_id FROM entrance_qrcodes WHERE is_active = 1 ORDER BY floor, entrance_id');
    
    echo "Current positions in database:\n";
    echo str_repeat("=", 80) . "\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s Floor: %d  X: %-8s Y: %-8s Path: %s\n", 
            $row['entrance_id'], 
            $row['floor'], 
            $row['x'], 
            $row['y'], 
            $row['nearest_path_id']
        );
    }
    
    echo str_repeat("=", 80) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
