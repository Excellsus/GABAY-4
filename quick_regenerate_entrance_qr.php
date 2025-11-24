<?php
/**
 * Quick Entrance QR Regeneration Script
 * Pre-configured with your network IP: 192.168.254.164
 */

require_once 'connect_db.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

$networkIP = '192.168.254.164'; // Your computer's local IP
$baseUrl = "http://$networkIP/gabay/mobileScreen/";

echo "========================================\n";
echo "ENTRANCE QR CODE REGENERATION\n";
echo "========================================\n\n";
echo "Network IP: $networkIP\n";
echo "Base URL: $baseUrl\n\n";

try {
    // Get all entrances from database
    $stmt = $connect->query("SELECT * FROM entrance_qrcodes ORDER BY floor, entrance_id");
    $entrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($entrances)) {
        echo "❌ No entrances found in database!\n";
        exit;
    }
    
    $qrDir = __DIR__ . '/entrance_qrcodes/';
    $successCount = 0;
    $errorCount = 0;
    
    echo "Regenerating " . count($entrances) . " QR codes...\n\n";
    
    foreach ($entrances as $entrance) {
        $entranceId = $entrance['entrance_id'];
        $floor = $entrance['floor'];
        $label = $entrance['label'];
        
        // Generate new QR URL with network IP
        $qrData = $baseUrl . "explore.php?entrance_qr=1&entrance_id=" . urlencode($entranceId) . "&floor=" . $floor;
        
        // Generate filename
        $filename = $entrance['qr_code_image'];
        $filePath = $qrDir . $filename;
        
        // Generate QR code image
        try {
            QRcode::png($qrData, $filePath, QR_ECLEVEL_L, 8, 2);
            
            // Update database with new URL
            $updateStmt = $connect->prepare("UPDATE entrance_qrcodes SET qr_code_data = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$qrData, $entrance['id']]);
            
            echo "✓ $label (Floor $floor)\n";
            echo "  URL: $qrData\n";
            echo "  File: $filename\n\n";
            
            $successCount++;
        } catch (Exception $e) {
            echo "❌ Failed: $label - " . $e->getMessage() . "\n\n";
            $errorCount++;
        }
    }
    
    echo "\n========================================\n";
    echo "✅ REGENERATION COMPLETE!\n";
    echo "========================================\n";
    echo "Success: $successCount QR codes\n";
    echo "Errors: $errorCount QR codes\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Connect your phone to the same WiFi network\n";
    echo "2. Test this URL in mobile browser:\n";
    echo "   http://$networkIP/gabay/mobileScreen/explore.php\n";
    echo "3. If page loads, scan any entrance QR code\n";
    echo "4. QR codes are in: entrance_qrcodes/ folder\n\n";
    
    echo "FIRST TEST:\n";
    echo "Open this URL on your phone's browser:\n";
    echo $baseUrl . "explore.php?entrance_qr=1&entrance_id=entrance_main_1&floor=1\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
