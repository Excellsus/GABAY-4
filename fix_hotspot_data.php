<?php
include 'connect_db.php';

try {
    // Update hotspot point_index to match existing panorama data
    $stmt = $connect->prepare('UPDATE panorama_hotspots SET point_index = 0 WHERE path_id = "path1" AND point_index = 1');
    $result = $stmt->execute();
    
    if ($result) {
        echo "Hotspots updated to match panorama data (path1, point 0)\n";
        
        // Verify the update
        $stmt = $connect->query('SELECT * FROM panorama_hotspots WHERE path_id = "path1"');
        $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Updated hotspots:\n";
        foreach ($hotspots as $hotspot) {
            echo "ID: {$hotspot['id']}, Path: {$hotspot['path_id']}, Point: {$hotspot['point_index']}, Title: {$hotspot['title']}\n";
        }
    } else {
        echo "Failed to update hotspots\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>