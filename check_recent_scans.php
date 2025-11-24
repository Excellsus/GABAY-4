<?php
/**
 * Check Recent Door QR Scans
 * This script checks the most recent door QR code scans
 */

include 'connect_db.php';

echo "=== CHECKING RECENT DOOR QR SCANS ===\n\n";

// Check most recent door scans
echo "Most Recent 10 Door Scans:\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connect->query("
    SELECT 
        qsl.id,
        qsl.office_id,
        qsl.door_index,
        qsl.check_in_time,
        qsl.qr_code_id,
        o.name as office_name
    FROM qr_scan_logs qsl
    LEFT JOIN offices o ON qsl.office_id = o.id
    WHERE qsl.door_index IS NOT NULL
    ORDER BY qsl.check_in_time DESC
    LIMIT 10
");

$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $count++;
    echo sprintf(
        "Scan #%d: Office '%s' (ID: %d) | Door: %d | Time: %s | QR Code ID: %d\n",
        $count,
        $row['office_name'] ?? 'Unknown',
        $row['office_id'],
        $row['door_index'],
        $row['check_in_time'],
        $row['qr_code_id']
    );
}

if ($count === 0) {
    echo "⚠️  NO DOOR SCANS FOUND IN DATABASE!\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Check Kinder Joy office specifically
echo "Kinder Joy Office Information:\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connect->prepare("SELECT * FROM offices WHERE name LIKE ?");
$stmt->execute(['%Kinder Joy%']);
$office = $stmt->fetch(PDO::FETCH_ASSOC);

if ($office) {
    echo "Office ID: " . $office['id'] . "\n";
    echo "Office Name: " . $office['name'] . "\n";
    echo "Location: " . $office['location'] . "\n\n";
    
    // Check door QR codes for this office
    echo "Door QR Codes for Kinder Joy:\n";
    $stmt = $connect->prepare("SELECT * FROM door_qrcodes WHERE office_id = ? ORDER BY door_index");
    $stmt->execute([$office['id']]);
    
    while ($door = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "  Door %d: QR Image: %s | Active: %d\n",
            $door['door_index'],
            $door['qr_code_image'],
            $door['is_active']
        );
    }
    
    echo "\nScans for Kinder Joy:\n";
    $stmt = $connect->prepare("
        SELECT * FROM qr_scan_logs 
        WHERE office_id = ? AND door_index IS NOT NULL 
        ORDER BY check_in_time DESC
    ");
    $stmt->execute([$office['id']]);
    
    $scanCount = 0;
    while ($scan = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $scanCount++;
        echo sprintf(
            "  Scan #%d: Door %d | Time: %s\n",
            $scanCount,
            $scan['door_index'],
            $scan['check_in_time']
        );
    }
    
    if ($scanCount === 0) {
        echo "  ⚠️  NO SCANS RECORDED FOR KINDER JOY!\n";
    }
} else {
    echo "⚠️  Kinder Joy office not found!\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Check today's scans
echo "Today's Door Scans (using CURDATE()):\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connect->query("
    SELECT 
        qsl.id,
        qsl.office_id,
        qsl.door_index,
        qsl.check_in_time,
        o.name as office_name,
        DATE(qsl.check_in_time) as scan_date,
        CURDATE() as today
    FROM qr_scan_logs qsl
    LEFT JOIN offices o ON qsl.office_id = o.id
    WHERE qsl.door_index IS NOT NULL 
    AND DATE(qsl.check_in_time) = CURDATE()
    ORDER BY qsl.check_in_time DESC
");

$todayCount = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $todayCount++;
    echo sprintf(
        "Scan #%d: Office '%s' | Door: %d | Time: %s | Scan Date: %s | Today: %s\n",
        $todayCount,
        $row['office_name'] ?? 'Unknown',
        $row['door_index'],
        $row['check_in_time'],
        $row['scan_date'],
        $row['today']
    );
}

if ($todayCount === 0) {
    echo "⚠️  NO DOOR SCANS TODAY!\n";
    echo "MySQL CURDATE: " . date('Y-m-d') . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
