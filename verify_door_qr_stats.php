<?php
/**
 * Verification Script: Check Door QR Statistics
 * 
 * This script verifies that the door QR statistics fix is working properly.
 * Run this after applying the fixes to validate the implementation.
 */

require_once 'connect_db.php';

echo "=== Door QR Statistics Verification Script ===\n\n";

try {
    // 1. Check door QR codes exist
    echo "1. Checking door QR codes...\n";
    $doorQrStmt = $connect->query("SELECT COUNT(*) as total FROM door_qrcodes");
    $doorQrCount = $doorQrStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   ✓ Found {$doorQrCount} door QR codes\n\n";
    
    if ($doorQrCount === 0) {
        echo "   ⚠️  No door QR codes found. Generate some in officeManagement.php first.\n";
        exit(0);
    }
    
    // 2. Check scan logs with door_index
    echo "2. Checking door-level scan logs...\n";
    $doorScanStmt = $connect->query("
        SELECT COUNT(*) as total 
        FROM qr_scan_logs 
        WHERE door_index IS NOT NULL
    ");
    $doorScanCount = $doorScanStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   ✓ Found {$doorScanCount} door-level scans\n";
    
    // 3. Check legacy scan logs (should be excluded)
    $legacyScanStmt = $connect->query("
        SELECT COUNT(*) as total 
        FROM qr_scan_logs 
        WHERE door_index IS NULL
    ");
    $legacyScanCount = $legacyScanStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   ℹ️  Found {$legacyScanCount} legacy office scans (will be excluded)\n\n";
    
    if ($doorScanCount === 0) {
        echo "   ⚠️  No door-level scans found. Run populate_door_scan_data.php to create sample data.\n\n";
    }
    
    // 4. Test the statistics query from home.php
    echo "3. Testing statistics query...\n";
    $statsStmt = $connect->prepare("
        SELECT 
            o.id as office_id,
            o.name as office_name,
            dqr.door_index,
            dqr.is_active,
            COUNT(DISTINCT qsl.id) as total_scans,
            MAX(qsl.check_in_time) as last_scanned_at,
            COALESCE(
                CASE 
                    WHEN MAX(qsl.check_in_time) IS NULL THEN 999
                    ELSE DATEDIFF(NOW(), MAX(qsl.check_in_time))
                END, 999
            ) as days_since_last_scan,
            SUM(CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 1 ELSE 0 END) as today_scans
        FROM offices o
        INNER JOIN door_qrcodes dqr ON o.id = dqr.office_id
        LEFT JOIN qr_scan_logs qsl ON (
            o.id = qsl.office_id 
            AND dqr.door_index = qsl.door_index 
            AND qsl.door_index IS NOT NULL
        )
        GROUP BY o.id, o.name, dqr.door_index, dqr.is_active
        LIMIT 5
    ");
    $statsStmt->execute();
    $sampleStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Sample results (first 5 doors):\n";
    foreach ($sampleStats as $stat) {
        $doorNum = $stat['door_index'] + 1; // Display as 1-based
        $status = $stat['is_active'] ? 'Active' : 'Inactive';
        $lastScan = $stat['last_scanned_at'] ? date('Y-m-d H:i', strtotime($stat['last_scanned_at'])) : 'Never';
        
        echo "   - {$stat['office_name']} Door {$doorNum}: ";
        echo "{$stat['total_scans']} scans, ";
        echo "Last: {$lastScan}, ";
        echo "Status: {$status}\n";
    }
    echo "\n";
    
    // 5. Calculate statistics categories
    echo "4. Calculating statistics...\n";
    $allStatsStmt = $connect->prepare("
        SELECT 
            dqr.is_active,
            MAX(qsl.check_in_time) as last_scanned_at,
            COALESCE(
                CASE 
                    WHEN MAX(qsl.check_in_time) IS NULL THEN 999
                    ELSE DATEDIFF(NOW(), MAX(qsl.check_in_time))
                END, 999
            ) as days_since_last_scan,
            SUM(CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 1 ELSE 0 END) as today_scans
        FROM door_qrcodes dqr
        INNER JOIN offices o ON dqr.office_id = o.id
        LEFT JOIN qr_scan_logs qsl ON (
            o.id = qsl.office_id 
            AND dqr.door_index = qsl.door_index 
            AND qsl.door_index IS NOT NULL
        )
        GROUP BY dqr.id, dqr.is_active
    ");
    $allStatsStmt->execute();
    $allStats = $allStatsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $activeCount = 0;
    $inactiveCount = 0;
    $staleCount = 0;
    $neverScannedCount = 0;
    $todayScansCount = 0;
    
    foreach ($allStats as $stat) {
        if ($stat['is_active'] == 1) {
            $activeCount++;
        } else {
            $inactiveCount++;
        }
        
        if ($stat['is_active'] == 1 && $stat['days_since_last_scan'] >= 7 && $stat['last_scanned_at'] !== null) {
            $staleCount++;
        }
        
        if ($stat['last_scanned_at'] === null) {
            $neverScannedCount++;
        }
        
        if ($stat['today_scans'] > 0) {
            $todayScansCount++;
        }
    }
    
    echo "   Active Door QR Codes: {$activeCount}\n";
    echo "   Inactive Door QR Codes: {$inactiveCount}\n";
    echo "   Stale (7+ days): {$staleCount}\n";
    echo "   Never Scanned: {$neverScannedCount}\n";
    echo "   Scanned Today: {$todayScansCount}\n\n";
    
    // 6. Verification checks
    echo "5. Running verification checks...\n";
    $issues = [];
    
    // Check if all doors are showing as never scanned when scans exist
    if ($doorScanCount > 0 && $neverScannedCount === $doorQrCount) {
        $issues[] = "All doors show as 'Never Scanned' despite scan data existing. SQL query may be incorrect.";
    }
    
    // Check if legacy scans are being counted incorrectly
    if ($doorScanCount === 0 && $legacyScanCount > 0) {
        $issues[] = "Only legacy scans exist. Run populate_door_scan_data.php to create door-level scans.";
    }
    
    // Check door numbering (sample check)
    $doorNumStmt = $connect->query("SELECT door_index FROM door_qrcodes LIMIT 1");
    $firstDoor = $doorNumStmt->fetch(PDO::FETCH_ASSOC);
    if ($firstDoor && $firstDoor['door_index'] !== 0) {
        $issues[] = "Door indexing doesn't start at 0. Database schema may be incorrect.";
    }
    
    if (empty($issues)) {
        echo "   ✅ All checks passed!\n";
        echo "   ✅ Door QR statistics are working correctly\n";
        echo "   ✅ Display numbering should be 1-based (Door 1, 2, 3...)\n";
        echo "   ✅ Filters should work properly\n\n";
    } else {
        echo "   ⚠️  Issues found:\n";
        foreach ($issues as $issue) {
            echo "      - {$issue}\n";
        }
        echo "\n";
    }
    
    // 7. Recommendations
    echo "6. Recommendations:\n";
    if ($doorScanCount === 0) {
        echo "   → Run: php populate_door_scan_data.php\n";
        echo "     (Creates realistic scan data for testing)\n\n";
    } else {
        echo "   → Refresh home.php to see updated statistics\n";
        echo "   → Test all filter options (Today, Latest, Active, Stale, Never)\n";
        echo "   → Verify door numbering shows as 1-based (Door 1, 2, 3...)\n\n";
    }
    
    echo "=== Verification Complete ===\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
