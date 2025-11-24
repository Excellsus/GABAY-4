<?php
require 'connect_db.php';

echo "Entrance Position Sync Verification\n";
echo str_repeat("=", 100) . "\n\n";

// Get database positions
$stmt = $connect->query('SELECT entrance_id, floor, x, y, nearest_path_id FROM entrance_qrcodes WHERE is_active = 1 ORDER BY floor, entrance_id');
$dbEntrances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load floor graph files
$floorFiles = [
    1 => __DIR__ . '/floor_graph.json',
    2 => __DIR__ . '/floor_graph_2.json',
    3 => __DIR__ . '/floor_graph_3.json'
];

$allMatch = true;

foreach ($floorFiles as $floor => $filePath) {
    if (!file_exists($filePath)) {
        echo "❌ Floor $floor graph file not found!\n";
        continue;
    }
    
    $graphData = json_decode(file_get_contents($filePath), true);
    
    if (!isset($graphData['entrances'])) {
        echo "❌ No entrances in floor $floor graph!\n";
        continue;
    }
    
    echo "Floor $floor:\n";
    echo str_repeat("-", 100) . "\n";
    
    foreach ($graphData['entrances'] as $entrance) {
        // Find matching DB entrance
        $dbMatch = null;
        foreach ($dbEntrances as $dbEntrance) {
            if ($dbEntrance['entrance_id'] === $entrance['id'] && $dbEntrance['floor'] == $floor) {
                $dbMatch = $dbEntrance;
                break;
            }
        }
        
        if (!$dbMatch) {
            echo "⚠️  {$entrance['id']}: Not found in database\n";
            continue;
        }
        
        $dbX = (float)$dbMatch['x'];
        $dbY = (float)$dbMatch['y'];
        $jsonX = (float)$entrance['x'];
        $jsonY = (float)$entrance['y'];
        
        if ($dbX == $jsonX && $dbY == $jsonY) {
            echo "✅ {$entrance['id']}: MATCHED (X: $jsonX, Y: $jsonY)\n";
        } else {
            echo "❌ {$entrance['id']}: MISMATCH!\n";
            echo "   Database: X: $dbX, Y: $dbY\n";
            echo "   JSON:     X: $jsonX, Y: $jsonY\n";
            $allMatch = false;
        }
    }
    
    echo "\n";
}

echo str_repeat("=", 100) . "\n";
if ($allMatch) {
    echo "✅ ALL ENTRANCE POSITIONS MATCH!\n";
} else {
    echo "❌ SOME POSITIONS DO NOT MATCH - PLEASE REVIEW\n";
}
echo str_repeat("=", 100) . "\n";
?>
