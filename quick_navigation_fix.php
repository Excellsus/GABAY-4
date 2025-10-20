<?php
include 'connect_db.php';

echo "<h2>🚀 Quick Navigation Fix</h2>";
echo "<style>body{font-family:Arial;margin:20px;} .success{background:#e6ffe6;padding:10px;margin:5px;} .error{background:#ffe6e6;padding:10px;margin:5px;}</style>";

try {
    // Update hotspot #339 (at path1, point 5) to navigate to path1, point 8
    $stmt = $connect->prepare("
        UPDATE panorama_hotspots 
        SET link_path_id = 'path1',
            link_point_index = 8,
            link_floor_number = 1,
            link_type = 'panorama',
            is_navigation = 1,
            updated_at = NOW()
        WHERE id = 339
    ");
    $result1 = $stmt->execute();
    
    // Update hotspot #338 (at path1, point 8) to navigate to path1, point 5  
    $stmt = $connect->prepare("
        UPDATE panorama_hotspots 
        SET link_path_id = 'path1',
            link_point_index = 5,
            link_floor_number = 1,
            link_type = 'panorama',
            is_navigation = 1,
            updated_at = NOW()
        WHERE id = 338
    ");
    $result2 = $stmt->execute();
    
    if ($result1 && $result2) {
        echo "<div class='success'>✅ Navigation hotspots fixed successfully!</div>";
        echo "<ul>";
        echo "<li>Hotspot #339 (path1, point 5) → now navigates to → path1, point 8</li>";
        echo "<li>Hotspot #338 (path1, point 8) → now navigates to → path1, point 5</li>";
        echo "</ul>";
        
        echo "<h3>🧪 Test Navigation:</h3>";
        echo "<ol>";
        echo "<li>Go to <a href='mobileScreen/explore.php' target='_blank'>Mobile Interface</a></li>";
        echo "<li>Click camera circle to open split screen</li>";
        echo "<li>Look for navigation hotspots (🧭 icons)</li>";
        echo "<li>Click hotspot to test navigation</li>";
        echo "<li>URL should update and camera highlight should move</li>";
        echo "</ol>";
        
    } else {
        echo "<div class='error'>❌ Failed to update navigation hotspots</div>";
    }
    
    // Verify the changes
    echo "<h3>📋 Verification:</h3>";
    $stmt = $connect->prepare("
        SELECT id, path_id, point_index, floor_number, 
               link_path_id, link_point_index, link_floor_number, is_navigation
        FROM panorama_hotspots 
        WHERE id IN (338, 339)
    ");
    $stmt->execute();
    $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>ID</th><th>Source Location</th><th>Target Location</th><th>Navigation Status</th></tr>";
    
    foreach ($hotspots as $hotspot) {
        $source = "Floor {$hotspot['floor_number']}, {$hotspot['path_id']}, Point {$hotspot['point_index']}";
        $target = "Floor {$hotspot['link_floor_number']}, {$hotspot['link_path_id']}, Point {$hotspot['link_point_index']}";
        $status = $hotspot['is_navigation'] ? "✅ Ready" : "❌ Not active";
        
        echo "<tr>";
        echo "<td>{$hotspot['id']}</td>";
        echo "<td>{$source}</td>";
        echo "<td>{$target}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<p><a href="debug_navigation_mapping.php">🔍 Re-check Navigation Status</a></p>