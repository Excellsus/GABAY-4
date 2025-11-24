<?php
include 'connect_db.php';

echo "=== qr_scan_logs Table Schema ===\n\n";

$stmt = $connect->query('DESCRIBE qr_scan_logs');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-20s %-30s %-10s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null']
    );
}

echo "\n=== door_qrcodes Table Schema ===\n\n";

$stmt2 = $connect->query('DESCRIBE door_qrcodes');
while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-20s %-30s %-10s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null']
    );
}
