<?php
require_once "connect_db.php";

echo "=== ALL COLUMNS IN FEEDBACK TABLE ===\n\n";

$stmt = $connect->query("DESCRIBE feedback");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "Column: " . $col['Field'] . "\n";
    echo "  Type: " . $col['Type'] . "\n";
    echo "  Null: " . $col['Null'] . "\n";
    echo "  Key: " . $col['Key'] . "\n";
    echo "  Default: " . ($col['Default'] ?? 'NULL') . "\n";
    echo "  Extra: " . $col['Extra'] . "\n\n";
}
?>
