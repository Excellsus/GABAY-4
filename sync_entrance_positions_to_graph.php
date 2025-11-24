<?php
/**
 * Sync Entrance Positions from Database to Floor Graph JSON Files
 * 
 * This script reads entrance x,y coordinates from entrance_qrcodes table
 * and updates the corresponding floor_graph.json files.
 * 
 * Run this after moving entrances in the admin panel to persist changes to JSON files.
 */

require_once 'connect_db.php';

echo "Starting entrance position sync...\n\n";

try {
    // Fetch all entrance positions from database
    $stmt = $connect->prepare("SELECT entrance_id, floor, x, y, nearest_path_id FROM entrance_qrcodes WHERE is_active = 1");
    $stmt->execute();
    $dbEntrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($dbEntrances) . " active entrances in database\n\n";
    
    // Group entrances by floor
    $entrancesByFloor = [];
    foreach ($dbEntrances as $entrance) {
        $floor = (int)$entrance['floor'];
        if (!isset($entrancesByFloor[$floor])) {
            $entrancesByFloor[$floor] = [];
        }
        $entrancesByFloor[$floor][] = $entrance;
    }
    
    // Update each floor's graph file
    $floorFiles = [
        1 => __DIR__ . '/floor_graph.json',
        2 => __DIR__ . '/floor_graph_2.json',
        3 => __DIR__ . '/floor_graph_3.json'
    ];
    
    foreach ($floorFiles as $floor => $filePath) {
        if (!file_exists($filePath)) {
            echo "⚠️  Floor $floor graph file not found: $filePath\n";
            continue;
        }
        
        // Load floor graph
        $jsonContent = file_get_contents($filePath);
        $graphData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "❌ Error parsing floor $floor graph: " . json_last_error_msg() . "\n";
            continue;
        }
        
        if (!isset($graphData['entrances'])) {
            echo "⚠️  No entrances array in floor $floor graph\n";
            continue;
        }
        
        // Update entrances with database positions
        $updatedCount = 0;
        foreach ($graphData['entrances'] as &$entrance) {
            if (!isset($entrancesByFloor[$floor])) {
                continue;
            }
            
            foreach ($entrancesByFloor[$floor] as $dbEntrance) {
                if ($dbEntrance['entrance_id'] === $entrance['id']) {
                    $oldX = $entrance['x'];
                    $oldY = $entrance['y'];
                    $newX = (float)$dbEntrance['x'];
                    $newY = (float)$dbEntrance['y'];
                    
                    $entrance['x'] = $newX;
                    $entrance['y'] = $newY;
                    
                    if (!empty($dbEntrance['nearest_path_id'])) {
                        $entrance['nearestPathId'] = $dbEntrance['nearest_path_id'];
                    }
                    
                    if ($oldX != $newX || $oldY != $newY) {
                        echo "✅ Updated entrance '{$entrance['label']}' on floor $floor:\n";
                        echo "   From: ($oldX, $oldY) → To: ($newX, $newY)\n";
                        $updatedCount++;
                    }
                    
                    break;
                }
            }
        }
        unset($entrance); // Break reference
        
        if ($updatedCount > 0) {
            // Save updated graph back to file
            $jsonOutput = json_encode($graphData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($filePath, $jsonOutput);
            echo "💾 Saved $updatedCount entrance position(s) to floor $floor graph\n\n";
        } else {
            echo "ℹ️  No entrance position changes for floor $floor\n\n";
        }
    }
    
    echo "✅ Entrance position sync completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
