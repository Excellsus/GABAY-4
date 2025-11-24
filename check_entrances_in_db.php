<?php
// Diagnostic script to check what entrances exist in database

include 'connect_db.php';

echo "<h2>Entrance QR Codes in Database</h2>\n";
echo "<pre>\n";

try {
    $stmt = $connect->query("SELECT entrance_id, label, floor, x, y, is_active FROM entrance_qrcodes ORDER BY floor ASC, entrance_id ASC");
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
    
    echo "\n\n";
    echo "Floor 3 entrances:\n";
    $floor3 = array_filter($entrances, fn($e) => $e['floor'] == 3);
    if (count($floor3) > 0) {
        foreach ($floor3 as $e) {
            echo "  - {$e['entrance_id']} ({$e['label']}) - Active: " . ($e['is_active'] ? 'YES' : 'NO') . "\n";
        }
    } else {
        echo "  (none)\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
