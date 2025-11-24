<?php
/**
 * Diagnostic Script: Check Entrance QR Code Status
 * 
 * This script verifies:
 * 1. If entrance_qrcodes table exists
 * 2. How many entrance QR codes are in the database
 * 3. Which entrances are active vs inactive
 * 4. The actual QR code URLs being generated
 * 5. If QR code image files exist on disk
 */

require_once 'connect_db.php';

echo "========================================\n";
echo "ENTRANCE QR CODE STATUS REPORT\n";
echo "========================================\n\n";

try {
    // Check if table exists
    $checkTable = $connect->query("SHOW TABLES LIKE 'entrance_qrcodes'");
    if ($checkTable->rowCount() === 0) {
        echo "❌ ERROR: entrance_qrcodes table does not exist!\n";
        echo "   Run: php create_entrance_tables.php\n\n";
        exit;
    }
    
    echo "✓ entrance_qrcodes table exists\n\n";
    
    // Get all entrance QR codes
    $stmt = $connect->query("SELECT * FROM entrance_qrcodes ORDER BY floor, entrance_id");
    $entrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($entrances)) {
        echo "⚠️  WARNING: No entrance QR codes found in database!\n";
        echo "   Solution: Generate QR codes in floorPlan.php by clicking an entrance icon\n\n";
        exit;
    }
    
    echo "📊 Total Entrance QR Codes: " . count($entrances) . "\n\n";
    
    // Count active vs inactive
    $activeCount = 0;
    $inactiveCount = 0;
    
    foreach ($entrances as $entrance) {
        if ($entrance['is_active']) {
            $activeCount++;
        } else {
            $inactiveCount++;
        }
    }
    
    echo "✅ Active: $activeCount\n";
    echo "🚫 Inactive: $inactiveCount\n\n";
    
    echo "========================================\n";
    echo "ENTRANCE DETAILS\n";
    echo "========================================\n\n";
    
    // Show details for each entrance
    foreach ($entrances as $entrance) {
        $status = $entrance['is_active'] ? '✅ ACTIVE' : '🚫 INACTIVE';
        
        echo "Entrance: {$entrance['label']}\n";
        echo "  ID: {$entrance['entrance_id']}\n";
        echo "  Floor: {$entrance['floor']}\n";
        echo "  Status: $status\n";
        echo "  QR URL: {$entrance['qr_code_data']}\n";
        echo "  QR Image: {$entrance['qr_code_image']}\n";
        
        // Check if QR image file exists
        $qrFilePath = __DIR__ . '/entrance_qrcodes/' . $entrance['qr_code_image'];
        if (file_exists($qrFilePath)) {
            $fileSize = filesize($qrFilePath);
            echo "  QR File: ✓ EXISTS (" . round($fileSize / 1024, 2) . " KB)\n";
        } else {
            echo "  QR File: ❌ MISSING at $qrFilePath\n";
        }
        
        echo "\n";
    }
    
    echo "========================================\n";
    echo "QR CODE DIRECTORY STATUS\n";
    echo "========================================\n\n";
    
    $qrDir = __DIR__ . '/entrance_qrcodes/';
    if (is_dir($qrDir)) {
        echo "✓ Directory exists: $qrDir\n";
        
        $files = glob($qrDir . '*.png');
        echo "  PNG files found: " . count($files) . "\n\n";
        
        if (!empty($files)) {
            echo "Files in directory:\n";
            foreach ($files as $file) {
                $filename = basename($file);
                $fileSize = filesize($file);
                echo "  - $filename (" . round($fileSize / 1024, 2) . " KB)\n";
            }
        }
    } else {
        echo "❌ Directory does not exist: $qrDir\n";
        echo "   This directory should be created automatically when generating QR codes.\n";
    }
    
    echo "\n========================================\n";
    echo "MOBILE URL TEST\n";
    echo "========================================\n\n";
    
    if (!empty($entrances)) {
        $testEntrance = $entrances[0];
        echo "Test URL for first entrance ({$testEntrance['label']}):\n";
        echo $testEntrance['qr_code_data'] . "\n\n";
        echo "To test on mobile:\n";
        echo "1. Open this URL in mobile browser\n";
        echo "2. Or scan QR code: entrance_qrcodes/{$testEntrance['qr_code_image']}\n";
        echo "3. Should load explore.php with entrance highlighted\n\n";
        
        if (!$testEntrance['is_active']) {
            echo "⚠️  WARNING: This entrance is INACTIVE!\n";
            echo "   Scanning will redirect to 404_inactive_door.php\n";
            echo "   Activate it first in floorPlan.php entrance management\n\n";
        }
    }
    
    echo "========================================\n";
    echo "SCAN LOG COUNT\n";
    echo "========================================\n\n";
    
    // Check scan logs
    $scanStmt = $connect->query("SELECT COUNT(*) as count FROM entrance_scan_logs");
    $scanCount = $scanStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Total entrance scans recorded: $scanCount\n\n";
    
    if ($scanCount > 0) {
        echo "Recent scans (last 5):\n";
        $recentScans = $connect->query("
            SELECT esl.*, eq.label 
            FROM entrance_scan_logs esl 
            JOIN entrance_qrcodes eq ON esl.entrance_qr_id = eq.id 
            ORDER BY esl.check_in_time DESC 
            LIMIT 5
        ");
        
        foreach ($recentScans as $scan) {
            echo "  - {$scan['label']} at {$scan['check_in_time']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "END OF REPORT\n";
echo "========================================\n";
?>
