<?php
include 'connect_db.php';

echo "<h2>🔧 Fix Navigation Hotspots</h2>";
echo "<style>body{font-family:Arial;margin:20px;} .success{background:#e6ffe6;padding:10px;margin:5px;} .error{background:#ffe6e6;padding:10px;margin:5px;} .warning{background:#fff3cd;padding:10px;margin:5px;}</style>";

if ($_POST['action'] == 'fix_hotspots') {
    try {
        echo "<h3>🔧 Updating Navigation Hotspots</h3>";
        
        // Get all navigation hotspots that need fixing
        $stmt = $connect->prepare("
            SELECT id, path_id, point_index, floor_number, title 
            FROM panorama_hotspots 
            WHERE hotspot_type = 'navigation' 
            AND (link_path_id IS NULL OR link_path_id = '')
        ");
        $stmt->execute();
        $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($hotspots)) {
            echo "<div class='warning'>⚠️ No navigation hotspots found that need fixing.</div>";
        } else {
            echo "<div class='success'>✅ Found " . count($hotspots) . " navigation hotspots to fix</div>";
            
            // For each hotspot, set a default navigation target
            foreach ($hotspots as $hotspot) {
                $targetPath = $_POST["target_path_{$hotspot['id']}"];
                $targetPoint = $_POST["target_point_{$hotspot['id']}"];
                $targetFloor = $_POST["target_floor_{$hotspot['id']}"];
                
                if (!empty($targetPath) && !empty($targetPoint) && !empty($targetFloor)) {
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
                        echo "<div class='success'>✅ Fixed Hotspot #{$hotspot['id']}: {$hotspot['title']} → Floor {$targetFloor}, {$targetPath}, Point {$targetPoint}</div>";
                    } else {
                        echo "<div class='error'>❌ Failed to fix Hotspot #{$hotspot['id']}</div>";
                    }
                } else {
                    echo "<div class='warning'>⚠️ Skipped Hotspot #{$hotspot['id']}: No target specified</div>";
                }
            }
        }
        
        echo "<p><a href='debug_hotspot_table.php'>🔍 Check Updated Hotspots</a></p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    // Show form to fix hotspots
    try {
        $stmt = $connect->prepare("
            SELECT id, path_id, point_index, floor_number, title 
            FROM panorama_hotspots 
            WHERE hotspot_type = 'navigation' 
            AND (link_path_id IS NULL OR link_path_id = '')
        ");
        $stmt->execute();
        $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($hotspots)) {
            echo "<div class='success'>✅ All navigation hotspots are already properly configured!</div>";
            echo "<p><a href='mobileScreen/explore.php'>🔙 Test Mobile Interface</a></p>";
        } else {
            echo "<div class='warning'>⚠️ Found " . count($hotspots) . " navigation hotspots that need target data</div>";
            
            echo "<form method='POST'>";
            echo "<input type='hidden' name='action' value='fix_hotspots'>";
            
            echo "<h3>📝 Set Navigation Targets</h3>";
            echo "<p>For each hotspot, specify where it should navigate to:</p>";
            
            foreach ($hotspots as $hotspot) {
                echo "<fieldset style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
                echo "<legend><strong>Hotspot #{$hotspot['id']}: {$hotspot['title']}</strong></legend>";
                echo "<p>Current Location: Floor {$hotspot['floor_number']}, {$hotspot['path_id']}, Point {$hotspot['point_index']}</p>";
                
                echo "<label>Target Path: ";
                echo "<select name='target_path_{$hotspot['id']}'>";
                echo "<option value=''>Select Target Path</option>";
                echo "<option value='path1'>path1</option>";
                echo "<option value='path2'>path2</option>";
                echo "</select></label><br>";
                
                echo "<label>Target Point: ";
                echo "<input type='number' name='target_point_{$hotspot['id']}' placeholder='e.g., 5' min='0' max='20'></label><br>";
                
                echo "<label>Target Floor: ";
                echo "<select name='target_floor_{$hotspot['id']}'>";
                echo "<option value=''>Select Floor</option>";
                echo "<option value='1'>Floor 1</option>";
                echo "<option value='2'>Floor 2</option>";
                echo "<option value='3'>Floor 3</option>";
                echo "</select></label><br>";
                
                echo "</fieldset>";
            }
            
            echo "<button type='submit' style='background:#4CAF50;color:white;padding:15px 30px;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>🔧 Fix All Navigation Hotspots</button>";
            echo "</form>";
            
            echo "<h4>💡 Quick Setup Recommendations:</h4>";
            echo "<ul>";
            echo "<li>Hotspot at path1, point 5 → Navigate to path1, point 8</li>";
            echo "<li>Hotspot at path1, point 7 → Navigate to path2, point 0</li>";
            echo "<li>Hotspot at path1, point 8 → Navigate to path1, point 5</li>";
            echo "<li>Hotspot at path2, point 0 → Navigate to path1, point 7</li>";
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<p><a href="debug_navigation_diagnostic.php">🔙 Back to Diagnostic</a></p>