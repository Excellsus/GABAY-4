<?php
// Generate QR codes for all existing panoramas
include 'connect_db.php';
include 'panorama_api.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Generate Panorama QR Codes</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#f5f5f5;}</style>";
echo "</head><body>";
echo "<h2>🔗 Generate QR Codes for Existing Panoramas</h2>";

try {
    // Get all existing panoramas
    $stmt = $connect->prepare("SELECT DISTINCT path_id, point_index, floor_number, title FROM panorama_image ORDER BY floor_number, path_id, point_index");
    $stmt->execute();
    $panoramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($panoramas) . " panoramas. Generating QR codes...</p>";
    echo "<div style='background:white;padding:20px;border-radius:8px;'>";
    
    $generated = 0;
    $errors = 0;
    
    foreach ($panoramas as $pano) {
        $pathId = $pano['path_id'];
        $pointIndex = $pano['point_index'];
        $floorNumber = $pano['floor_number'];
        $title = $pano['title'] ?: 'Untitled';
        
        echo "<div style='margin:10px 0;padding:10px;border-left:3px solid #4CAF50;background:#f9f9f9;'>";
        echo "<strong>Floor {$floorNumber} • {$pathId} • Point {$pointIndex}</strong><br>";
        echo "<small>{$title}</small><br>";
        
        $result = generatePanoramaQRCode($pathId, $pointIndex, $floorNumber);
        
        if ($result) {
            $generated++;
            $filename = "panorama_floor{$floorNumber}_path{$pathId}_point{$pointIndex}.png";
            echo "<span style='color:green;'>✅ QR Generated: {$filename}</span>";
        } else {
            $errors++;
            echo "<span style='color:red;'>❌ Failed to generate QR</span>";
        }
        
        echo "</div>";
    }
    
    echo "</div>";
    echo "<div style='margin:20px 0;padding:15px;background:white;border-radius:8px;'>";
    echo "<h3>📊 Summary</h3>";
    echo "<p><strong>Total Panoramas:</strong> " . count($panoramas) . "</p>";
    echo "<p><strong>QR Codes Generated:</strong> <span style='color:green;'>{$generated}</span></p>";
    echo "<p><strong>Errors:</strong> <span style='color:red;'>{$errors}</span></p>";
    echo "</div>";
    
    if ($generated > 0) {
        echo "<div style='margin:20px 0;padding:15px;background:#e3f2fd;border-radius:8px;'>";
        echo "<h3>🎉 Success!</h3>";
        echo "<p>QR codes have been generated and saved to the <code>qrcodes/</code> directory.</p>";
        echo "<p>Now when you upload new panoramas in the floor plan, QR codes will be generated automatically.</p>";
        echo "<p>When you click a panorama marker → QR download button will appear for existing panoramas.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;padding:15px;background:white;border-radius:8px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div style='margin:30px 0;text-align:center;'>";
echo "<a href='floorPlan.php' style='display:inline-block;padding:12px 24px;background:#2196F3;color:white;text-decoration:none;border-radius:6px;'>← Back to Floor Plan</a>";
echo "</div>";

echo "</body></html>";
?>