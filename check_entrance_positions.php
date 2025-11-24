<?php
require 'connect_db.php';

echo "Current Entrance Positions in Database:\n";
echo str_repeat("=", 80) . "\n\n";

$stmt = $connect->query('SELECT entrance_id, floor, x, y, nearest_path_id FROM entrance_qrcodes WHERE is_active = 1 ORDER BY floor, entrance_id');

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-25s Floor: %d  X: %-8s Y: %-8s Path: %s\n", 
        $row['entrance_id'], 
        $row['floor'], 
        $row['x'], 
        $row['y'], 
        $row['nearest_path_id']
    );
}

echo "\n" . str_repeat("=", 80) . "\n";
?>
