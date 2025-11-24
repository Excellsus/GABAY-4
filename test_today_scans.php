<?php
/**
 * Test Script: Verify Today's Scans Detection
 * 
 * This script checks if the "Today's Scans" filter is working correctly
 * by testing the CURDATE() SQL function and verifying recent scan data.
 */

require_once 'connect_db.php';

echo "=== Today's Scans Detection Test ===\n\n";

try {
    // 1. Check MySQL date/time
    $dateStmt = $connect->query("SELECT CURDATE() as today, NOW() as now, DATE(NOW()) as date_now");
    $dateInfo = $dateStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "1. MySQL Date/Time Info:\n";
    echo "   CURDATE(): {$dateInfo['today']}\n";
    echo "   NOW(): {$dateInfo['now']}\n";
    echo "   DATE(NOW()): {$dateInfo['date_now']}\n\n";
    
    // 2. Check most recent door scan
    $recentScanStmt = $connect->query("
        SELECT 
            qsl.check_in_time,
            DATE(qsl.check_in_time) as scan_date,
            CURDATE() as today_date,
            CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 'TODAY' ELSE 'NOT TODAY' END as is_today,
            o.name as office_name,
            qsl.door_index
        FROM qr_scan_logs qsl
        INNER JOIN offices o ON qsl.office_id = o.id
        WHERE qsl.door_index IS NOT NULL
        ORDER BY qsl.check_in_time DESC
        LIMIT 5
    ");
    
    echo "2. Most Recent 5 Door Scans:\n";
    $recentScans = $recentScanStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($recentScans)) {
        echo "   ⚠️  No door scans found!\n\n";
    } else {
        foreach ($recentScans as $scan) {
            $doorNum = $scan['door_index'] + 1;
            echo "   - {$scan['office_name']} Door {$doorNum}\n";
            echo "     Scanned: {$scan['check_in_time']}\n";
            echo "     Date: {$scan['scan_date']}\n";
            echo "     Status: {$scan['is_today']}\n";
            echo "\n";
        }
    }
    
    // 3. Test the actual query from home.php
    echo "3. Testing home.php Query for Today's Scans:\n";
    $testQueryStmt = $connect->prepare("
        SELECT 
            o.id as office_id,
            o.name as office_name,
            dqr.door_index,
            COUNT(DISTINCT qsl.id) as total_scans,
            SUM(CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 1 ELSE 0 END) as today_scans,
            MAX(qsl.check_in_time) as last_scanned_at
        FROM offices o
        INNER JOIN door_qrcodes dqr ON o.id = dqr.office_id
        LEFT JOIN qr_scan_logs qsl ON (
            o.id = qsl.office_id 
            AND dqr.door_index = qsl.door_index 
            AND qsl.door_index IS NOT NULL
        )
        GROUP BY o.id, o.name, dqr.door_index
        HAVING today_scans > 0
        ORDER BY today_scans DESC, last_scanned_at DESC
        LIMIT 10
    ");
    $testQueryStmt->execute();
    $todaysScans = $testQueryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todaysScans)) {
        echo "   ⚠️  No doors scanned today according to the query!\n";
        echo "   This means either:\n";
        echo "   - No door QR codes have been scanned today\n";
        echo "   - Or the CURDATE() comparison is failing\n\n";
        
        // Check if there are ANY recent scans in the last 24 hours
        $last24hStmt = $connect->query("
            SELECT COUNT(*) as count 
            FROM qr_scan_logs 
            WHERE door_index IS NOT NULL 
            AND check_in_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $last24h = $last24hStmt->fetch(PDO::FETCH_ASSOC);
        echo "   Scans in last 24 hours: {$last24h['count']}\n\n";
    } else {
        echo "   Doors scanned today: " . count($todaysScans) . "\n";
        foreach ($todaysScans as $scan) {
            $doorNum = $scan['door_index'] + 1;
            echo "   - {$scan['office_name']} Door {$doorNum}: {$scan['today_scans']} scan(s) today\n";
            echo "     Total scans: {$scan['total_scans']}\n";
            echo "     Last scan: {$scan['last_scanned_at']}\n";
        }
        echo "\n";
    }
    
    // 4. Check filter logic expectations
    echo "4. Filter Logic Analysis:\n";
    echo "   For 'Today's Scans Only' filter to work:\n";
    echo "   - data-today-scans attribute must be > 0\n";
    echo "   - This value comes from: SUM(CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 1 ELSE 0 END)\n";
    echo "   - JavaScript checks: parseInt(item.dataset.todayScans || '0') > 0\n\n";
    
    // 5. Recommendations
    echo "5. Recommendations:\n";
    if (empty($todaysScans)) {
        echo "   → Scan a door QR code right now to test\n";
        echo "   → After scanning, refresh home.php\n";
        echo "   → Then test 'Today's Scans Only' filter\n";
        echo "   → Re-run this script to verify the scan was recorded\n\n";
    } else {
        echo "   ✅ Today's scans are being detected!\n";
        echo "   → Refresh home.php to see updated statistics\n";
        echo "   → Test the 'Today's Scans Only' filter\n";
        echo "   → It should show " . count($todaysScans) . " door(s)\n\n";
    }
    
    echo "=== Test Complete ===\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
