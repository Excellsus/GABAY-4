<?php
// Fix entrance records in database to match floor_graph.json files

include 'connect_db.php';

echo "<h2>Fixing Entrance Records in Database</h2>\n";
echo "<pre>\n";

try {
    // 1. Delete Floor 3 entrances (floor_graph_3.json has no entrances)
    echo "Step 1: Deleting Floor 3 entrances...\n";
    $stmt = $connect->prepare("DELETE FROM entrance_qrcodes WHERE floor = 3");
    $stmt->execute();
    echo "  ✅ Deleted " . $stmt->rowCount() . " Floor 3 entrance records\n\n";
    
    // 2. Delete entrance_west_2 (not in floor_graph_2.json)
    echo "Step 2: Deleting entrance_west_2 (not in floor graph)...\n";
    $stmt = $connect->prepare("DELETE FROM entrance_qrcodes WHERE entrance_id = 'entrance_west_2'");
    $stmt->execute();
    echo "  ✅ Deleted " . $stmt->rowCount() . " records\n\n";
    
    // 3. Rename entrance_main_2 to entrance_main_1
    echo "Step 3: Renaming entrance_main_2 to entrance_main_1...\n";
    $stmt = $connect->prepare("UPDATE entrance_qrcodes SET entrance_id = 'entrance_main_1', label = 'Main Entrance' WHERE entrance_id = 'entrance_main_2'");
    $stmt->execute();
    echo "  ✅ Updated " . $stmt->rowCount() . " records\n\n";
    
    // 4. Display final state
    echo "Step 4: Final entrance records:\n";
    $stmt = $connect->query("SELECT entrance_id, label, floor, is_active FROM entrance_qrcodes ORDER BY floor ASC, entrance_id ASC");
    $entrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total records: " . count($entrances) . "\n\n";
    echo str_pad("Entrance ID", 25) . str_pad("Label", 35) . str_pad("Floor", 8) . str_pad("Active", 8) . "\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($entrances as $entrance) {
        echo str_pad($entrance['entrance_id'], 25) . 
             str_pad($entrance['label'], 35) . 
             str_pad($entrance['floor'], 8) . 
             str_pad($entrance['is_active'] ? 'Yes' : 'No', 8) . "\n";
    }
    
    echo "\n✅ Database now matches floor_graph.json files!\n";
    echo "   - Floor 1: 3 entrances (entrance_main_1, entrance_west_1, entrance_east_1)\n";
    echo "   - Floor 2: 1 entrance (entrance_main_1)\n";
    echo "   - Floor 3: 0 entrances\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
