<?php
/**
 * Database Schema Update for Hotspot Navigation
 * Run this script once to add navigation features to your panorama_hotspots table
 */

include 'connect_db.php';

try {
    echo "Starting database schema update for hotspot navigation...\n";
    
    // Check if columns already exist
    $stmt = $connect->query("DESCRIBE panorama_hotspots");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    $newColumns = [
        'link_type' => "VARCHAR(50) DEFAULT 'none'",
        'link_path_id' => "VARCHAR(100) DEFAULT NULL",
        'link_point_index' => "INT DEFAULT NULL", 
        'link_floor_number' => "INT DEFAULT NULL",
        'navigation_angle' => "DECIMAL(5,2) DEFAULT 0",
        'is_navigation' => "BOOLEAN DEFAULT FALSE"
    ];
    
    $columnsAdded = 0;
    
    foreach ($newColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            $sql = "ALTER TABLE panorama_hotspots ADD COLUMN $columnName $columnDef";
            echo "Adding column: $columnName\n";
            $connect->exec($sql);
            $columnsAdded++;
        } else {
            echo "Column already exists: $columnName\n";
        }
    }
    
    if ($columnsAdded > 0) {
        echo "\nSuccessfully added $columnsAdded new columns to panorama_hotspots table.\n";
    } else {
        echo "\nAll columns already exist. No updates needed.\n";
    }
    
    // Update existing hotspots to have default link_type
    $updateStmt = $connect->prepare("
        UPDATE panorama_hotspots 
        SET link_type = 'none' 
        WHERE link_type IS NULL OR link_type = ''
    ");
    $updateStmt->execute();
    $updatedRows = $updateStmt->rowCount();
    
    if ($updatedRows > 0) {
        echo "Updated $updatedRows existing hotspots with default link_type.\n";
    }
    
    echo "\nDatabase schema update completed successfully!\n";
    echo "You can now create navigation hotspots that link to other panorama points.\n";
    
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
    exit(1);
}
?>