<?php
/**
 * Fix Missing Hotspot Columns
 * Run this script to add the missing animated_icon_path and related columns
 */

include 'connect_db.php';

echo "<h2>🔧 GABAY Hotspot Columns Fix</h2>";
echo "<p>Adding missing columns to panorama_hotspots table...</p>";

try {
    // Get current table structure
    $result = $connect->query("SHOW COLUMNS FROM panorama_hotspots");
    $existingColumns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>📊 Current Columns:</h3>";
    echo "<ul>";
    foreach ($existingColumns as $column) {
        echo "<li>✅ $column</li>";
    }
    echo "</ul>";
    
    // Define columns to add
    $columnsToAdd = [
        'video_hotspot_id' => 'INT NULL',
        'video_hotspot_path' => 'VARCHAR(500) NULL', 
        'video_hotspot_name' => 'VARCHAR(255) NULL',
        'animated_icon_path' => 'VARCHAR(500) NULL',
        'animated_icon_name' => 'VARCHAR(255) NULL'
    ];
    
    echo "<h3>🔄 Adding Missing Columns:</h3>";
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $sql = "ALTER TABLE panorama_hotspots ADD COLUMN $columnName $columnDef";
                $connect->exec($sql);
                echo "<div style='color: green;'>✅ Added: $columnName ($columnDef)</div>";
            } catch (Exception $e) {
                echo "<div style='color: red;'>❌ Failed to add $columnName: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div style='color: blue;'>ℹ️ Already exists: $columnName</div>";
        }
    }
    
    // Show updated table structure
    echo "<h3>📊 Updated Table Structure:</h3>";
    $result = $connect->query("SHOW COLUMNS FROM panorama_hotspots");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = in_array($col['Field'], array_keys($columnsToAdd)) ? "style='background-color: #d4edda;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎉 Column Fix Complete!</h3>";
    echo "<p>The panorama_hotspots table now has all required columns for video and animated hotspots.</p>";
    echo "<p><strong>You can now save hotspots without the 'Column not found' error!</strong></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'><h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p></div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #04aa6d; }
    h3 { color: #333; }
    ul { margin: 10px 0; }
    table { width: 100%; }
    th { background-color: #04aa6d; color: white; padding: 8px; }
    td { padding: 8px; }
    div { margin: 5px 0; padding: 5px; }
</style>