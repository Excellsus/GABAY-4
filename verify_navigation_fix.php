<?php
include 'connect_db.php';

echo "<h2>🧪 Navigation Hotspot Fix Verification</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px; }
.error { background: #ffe8e8; padding: 10px; margin: 10px 0; border-radius: 5px; }
.info { background: #e8f4ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
.fixed { background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background: #f5f5f5; }
</style>";

echo "<h3>📋 Current Status</h3>";

try {
    // Get all navigation hotspots
    $stmt = $connect->prepare("
        SELECT id, path_id, point_index, floor_number, title, hotspot_type,
               link_path_id, link_point_index, link_floor_number, 
               link_type, is_navigation, created_at, updated_at
        FROM panorama_hotspots 
        WHERE hotspot_type = 'navigation' OR is_navigation = 1
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($hotspots)) {
        echo "<div class='info'>ℹ️ No navigation hotspots found. Create one in the admin interface to test the auto-fix.</div>";
    } else {
        echo "<div class='success'>✅ Found " . count($hotspots) . " navigation hotspot(s)</div>";
        
        $workingCount = 0;
        $brokenCount = 0;
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Source Location</th><th>Target Location</th><th>Status</th><th>Last Updated</th></tr>";
        
        foreach ($hotspots as $hotspot) {
            $source = "path{$hotspot['path_id']}, point {$hotspot['point_index']}, floor {$hotspot['floor_number']}";
            
            if (empty($hotspot['link_path_id']) || empty($hotspot['link_point_index']) || $hotspot['link_point_index'] == 1) {
                $target = "❌ BROKEN: " . ($hotspot['link_path_id'] ?: 'NULL') . ", point " . ($hotspot['link_point_index'] ?: 'NULL');
                $status = "❌ NEEDS FIX";
                $rowColor = "#ffe8e8";
                $brokenCount++;
            } else {
                $target = "path{$hotspot['link_path_id']}, point {$hotspot['link_point_index']}, floor {$hotspot['link_floor_number']}";
                $status = $hotspot['is_navigation'] ? "✅ WORKING" : "⚠️ Not active";
                $rowColor = $hotspot['is_navigation'] ? "#e8f5e8" : "#fff3cd";
                if ($hotspot['is_navigation']) $workingCount++;
            }
            
            $lastUpdated = $hotspot['updated_at'] ?: $hotspot['created_at'];
            
            echo "<tr style='background:{$rowColor}'>";
            echo "<td>{$hotspot['id']}</td>";
            echo "<td>{$source}</td>";
            echo "<td>{$target}</td>";
            echo "<td>{$status}</td>";
            echo "<td>" . date('M j, H:i', strtotime($lastUpdated)) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='info'>";
        echo "<strong>📊 Summary:</strong><br>";
        echo "✅ Working: {$workingCount}<br>";
        echo "❌ Broken: {$brokenCount}<br>";
        echo "📝 Total: " . count($hotspots);
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<h3>🔧 Applied Fixes</h3>";
echo "<div class='fixed'>";
echo "<strong>✅ Fixed Issues:</strong><br>";
echo "1. Updated floorPlan.php to use panorama_viewer_photosphere.html (the fixed editor)<br>";
echo "2. Added navigation field mapping in saveHotspots() function<br>";
echo "3. Enhanced API to handle both camelCase and snake_case field formats<br>";
echo "4. Added intelligent auto-fix for navigation targets during save operations<br>";
echo "5. Added comprehensive debug logging for navigation data flow<br>";
echo "</div>";

echo "<h3>🧪 How to Test</h3>";
echo "<ol>";
echo "<li><a href='floorPlan.php' target='_blank'>Open Floor Plan</a></li>";
echo "<li>Click on any panorama marker (camera icon)</li>";
echo "<li>Click 'Edit Hotspots' to open the editor</li>";
echo "<li>Add a navigation hotspot (click 'Add Navigation Hotspot')</li>";
echo "<li>Select a target panorama and place the hotspot</li>";
echo "<li>Save and return here to verify the navigation data was properly saved</li>";
echo "</ol>";

if (!empty($hotspots) && $brokenCount > 0) {
    echo "<p><a href='emergency_navigation_fix.php' style='background: #ff4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Manual Fix Remaining Issues</a></p>";
}

echo "<p><a href='test_navigation_fix.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Detailed Analysis</a></p>";
?>