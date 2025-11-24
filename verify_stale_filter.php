<?php
/**
 * Verify Stale Filter Results
 * This script shows which doors match the "Stale (7+ days)" filter criteria
 */

include 'connect_db.php';

echo "=== VERIFYING STALE FILTER (7+ DAYS) ===\n\n";

try {
    // Query matching the home.php dashboard logic
    $stmt = $connect->query("
        SELECT 
            o.id as office_id,
            o.name as office_name,
            o.location as room_location,
            dqr.id as door_qr_id,
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
        GROUP BY o.id, o.name, o.location, dqr.id, dqr.door_index, dqr.is_active
        ORDER BY days_since_last_scan DESC, total_scans DESC
    ");
    
    $allDoors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter for stale doors (active + 7+ days + has been scanned)
    $staleDoors = array_filter($allDoors, function($door) {
        return $door['is_active'] == 1 
            && $door['days_since_last_scan'] >= 7 
            && $door['last_scanned_at'] !== null;
    });
    
    // Filter for all active doors
    $activeDoors = array_filter($allDoors, function($door) {
        return $door['is_active'] == 1;
    });
    
    // Filter for never scanned
    $neverScanned = array_filter($allDoors, function($door) {
        return $door['total_scans'] == 0;
    });
    
    // Filter for today's scans
    $todayScanned = array_filter($allDoors, function($door) {
        return $door['today_scans'] > 0;
    });
    
    echo "SUMMARY:\n";
    echo str_repeat("-", 80) . "\n";
    echo sprintf("Total doors: %d\n", count($allDoors));
    echo sprintf("Active doors: %d\n", count($activeDoors));
    echo sprintf("Stale doors (7+ days): %d\n", count($staleDoors));
    echo sprintf("Never scanned: %d\n", count($neverScanned));
    echo sprintf("Scanned today: %d\n", count($todayScanned));
    echo "\n" . str_repeat("=", 80) . "\n\n";
    
    echo "STALE DOORS (7+ days since last scan):\n";
    echo str_repeat("-", 80) . "\n";
    
    if (empty($staleDoors)) {
        echo "❌ No stale doors found!\n";
    } else {
        foreach ($staleDoors as $door) {
            $displayDoor = $door['door_index'] + 1;
            $status = $door['days_since_last_scan'] >= 7 ? "🔴 STALE" : "🟢 OK";
            
            echo sprintf(
                "%-25s Door %d | %3d scans | Last: %s (%d days ago) %s\n",
                $door['office_name'],
                $displayDoor,
                $door['total_scans'],
                $door['last_scanned_at'] ? date('Y-m-d H:i', strtotime($door['last_scanned_at'])) : 'Never',
                $door['days_since_last_scan'],
                $status
            );
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
    
    echo "TODAY'S SCANNED DOORS:\n";
    echo str_repeat("-", 80) . "\n";
    
    if (empty($todayScanned)) {
        echo "❌ No doors scanned today!\n";
    } else {
        foreach ($todayScanned as $door) {
            $displayDoor = $door['door_index'] + 1;
            
            echo sprintf(
                "%-25s Door %d | +%d today | Last: %s\n",
                $door['office_name'],
                $displayDoor,
                $door['today_scans'],
                $door['last_scanned_at'] ? date('Y-m-d H:i', strtotime($door['last_scanned_at'])) : 'Never'
            );
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
    
    echo "NEVER SCANNED DOORS:\n";
    echo str_repeat("-", 80) . "\n";
    
    if (empty($neverScanned)) {
        echo "✅ All doors have been scanned at least once!\n";
    } else {
        foreach ($neverScanned as $door) {
            $displayDoor = $door['door_index'] + 1;
            
            echo sprintf(
                "%-25s Door %d | %s\n",
                $door['office_name'],
                $displayDoor,
                $door['is_active'] ? '🔴 ACTIVE (never scanned)' : '⚫ INACTIVE'
            );
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
    
    echo "TESTING CHECKLIST:\n";
    echo str_repeat("-", 80) . "\n";
    echo "1. Open: http://localhost/FinalDev/home.php\n";
    echo "2. Click: 'Refresh Data' button\n";
    echo "3. Select filter: 'Stale (7+ days)'\n";
    echo "4. Expected doors shown: " . count($staleDoors) . " door(s)\n";
    
    if (!empty($staleDoors)) {
        echo "\n   Should display:\n";
        foreach ($staleDoors as $door) {
            $displayDoor = $door['door_index'] + 1;
            echo sprintf("   - %s Door %d (%d days ago)\n", 
                $door['office_name'], 
                $displayDoor, 
                $door['days_since_last_scan']
            );
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
