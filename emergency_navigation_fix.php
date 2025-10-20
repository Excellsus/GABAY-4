<?php
include 'connect_db.php';

echo "<h2>🔧 Emergency Navigation Fix</h2>";
echo "<style>body{font-family:Arial;margin:20px;} .success{background:#e6ffe6;padding:10px;margin:5px;} .error{background:#ffe6e6;padding:10px;margin:5px;} .warning{background:#fff3cd;padding:10px;margin:5px;}</style>";

if ($_POST['action'] == 'fix_now') {
    try {
        echo "<h3>🚀 Fixing Navigation Hotspots</h3>";
        
        // Get all navigation hotspots that are pointing to point 1 (which doesn't exist)
        $stmt = $connect->prepare("
            SELECT id, path_id, point_index, floor_number, title,
                   link_path_id, link_point_index, link_floor_number
            FROM panorama_hotspots 
            WHERE hotspot_type = 'navigation' 
            AND (link_point_index = 1 OR link_point_index IS NULL OR link_point_index = 0)
        ");
        $stmt->execute();
        $brokenHotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($brokenHotspots)) {
            echo "<div class='warning'>⚠️ No broken navigation hotspots found pointing to point 1.</div>";
        } else {
            echo "<div class='warning'>⚠️ Found " . count($brokenHotspots) . " hotspots pointing to invalid targets</div>";
            
            foreach ($brokenHotspots as $hotspot) {
                $sourceLocation = "path{$hotspot['path_id']}, point {$hotspot['point_index']}";
                
                // Set intelligent navigation targets based on source location
                $targetPath = $hotspot['path_id']; // Same path by default
                $targetPoint = null;
                $targetFloor = $hotspot['floor_number']; // Same floor
                
                // Smart target assignment
                if ($hotspot['path_id'] == 'path1' && $hotspot['point_index'] == 5) {
                    $targetPoint = 8; // point 5 → point 8
                } elseif ($hotspot['path_id'] == 'path1' && $hotspot['point_index'] == 8) {
                    $targetPoint = 5; // point 8 → point 5
                } elseif ($hotspot['path_id'] == 'path1' && $hotspot['point_index'] == 7) {
                    $targetPath = 'path2';
                    $targetPoint = 0; // path1 point 7 → path2 point 0
                } elseif ($hotspot['path_id'] == 'path2' && $hotspot['point_index'] == 0) {
                    $targetPath = 'path1';
                    $targetPoint = 7; // path2 point 0 → path1 point 7
                } else {
                    // Default fallback
                    $targetPoint = ($hotspot['point_index'] == 5) ? 8 : 5;
                }
                
                if ($targetPoint) {
                    $updateStmt = $connect->prepare("
                        UPDATE panorama_hotspots 
                        SET link_path_id = ?,
                            link_point_index = ?,
                            link_floor_number = ?,
                            link_type = 'panorama',
                            is_navigation = 1,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $success = $updateStmt->execute([$targetPath, $targetPoint, $targetFloor, $hotspot['id']]);
                    
                    if ($success) {
                        echo "<div class='success'>✅ Fixed Hotspot #{$hotspot['id']}: {$sourceLocation} → {$targetPath}, point {$targetPoint}</div>";
                    } else {
                        echo "<div class='error'>❌ Failed to fix Hotspot #{$hotspot['id']}</div>";
                    }
                } else {
                    echo "<div class='warning'>⚠️ Could not determine target for Hotspot #{$hotspot['id']}: {$sourceLocation}</div>";
                }
            }
        }
        
        echo "<p><a href='mobileScreen/explore.php' target='_blank'>🔙 Test Mobile Interface</a></p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    // Show current broken hotspots
    try {
        $stmt = $connect->prepare("
            SELECT id, path_id, point_index, floor_number, title,
                   link_path_id, link_point_index, link_floor_number, is_navigation
            FROM panorama_hotspots 
            WHERE hotspot_type = 'navigation'
            ORDER BY id DESC
        ");
        $stmt->execute();
        $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>🔍 Current Navigation Hotspots</h3>";
        
        if (empty($hotspots)) {
            echo "<div class='error'>❌ No navigation hotspots found!</div>";
        } else {
            echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
            echo "<tr><th>ID</th><th>Source</th><th>Current Target</th><th>Status</th></tr>";
            
            $needsFix = false;
            foreach ($hotspots as $hotspot) {
                $source = "path{$hotspot['path_id']}, point {$hotspot['point_index']}";
                
                if (empty($hotspot['link_path_id']) || $hotspot['link_point_index'] == 1) {
                    $target = "❌ BROKEN: " . ($hotspot['link_path_id'] ?: 'NULL') . ", point " . ($hotspot['link_point_index'] ?: 'NULL');
                    $status = "❌ Needs Fix";
                    $needsFix = true;
                    $bgColor = "#ffe6e6";
                } else {
                    $target = "path{$hotspot['link_path_id']}, point {$hotspot['link_point_index']}";
                    $status = $hotspot['is_navigation'] ? "✅ Working" : "⚠️ Not active";
                    $bgColor = $hotspot['is_navigation'] ? "#e6ffe6" : "#fff3cd";
                }
                
                echo "<tr style='background:{$bgColor}'>";
                echo "<td>{$hotspot['id']}</td>";
                echo "<td>{$source}</td>";
                echo "<td>{$target}</td>";
                echo "<td>{$status}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if ($needsFix) {
                echo "<div class='warning'>⚠️ Some navigation hotspots are broken and need fixing!</div>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='action' value='fix_now'>";
                echo "<button type='submit' style='background:#ff4444;color:white;padding:15px 30px;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>🔧 Auto-Fix All Navigation Hotspots</button>";
                echo "</form>";
                
                echo "<h4>💡 What will be fixed:</h4>";
                echo "<ul>";
                echo "<li>path1, point 5 → path1, point 8</li>";
                echo "<li>path1, point 8 → path1, point 5</li>";
                echo "<li>path1, point 7 → path2, point 0</li>";
                echo "<li>path2, point 0 → path1, point 7</li>";
                echo "</ul>";
            } else {
                echo "<div class='success'>✅ All navigation hotspots are properly configured!</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<p><a href="debug_navigation_mapping.php">🔍 Check Navigation Status</a></p>