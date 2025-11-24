<?php
/**
 * Regenerate Door QR Codes (No Auth - CLI Only)
 * 
 * This script regenerates all existing door QR codes with the correct network IP.
 * Use this after changing the IP address in getDoorQRBaseUrl().
 * 
 * Usage: php regenerate_door_qrs_no_auth.php
 */

require_once 'connect_db.php';
require 'phpqrcode/qrlib.php';

echo "🔄 REGENERATING DOOR QR CODES WITH CORRECT IP\n";
echo str_repeat("=", 60) . "\n\n";

// Network IP for QR codes - CHANGE THIS TO YOUR COMPUTER'S IP
$baseUrl = "https://localhost/gabay/mobileScreen/";

// Sanitize filename
function sanitizeDoorFilename($string) {
    $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
    $string = preg_replace('/[\s_]+/', '_', $string);
    return trim($string, '_') ?: 'door';
}

try {
    // Get all existing door QR codes
    $stmt = $connect->query("
        SELECT dqr.*, o.name as office_name 
        FROM door_qrcodes dqr
        JOIN offices o ON dqr.office_id = o.id
        ORDER BY dqr.office_id, dqr.door_index
    ");
    $doorQrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($doorQrs)) {
        echo "⚠️  No door QR codes found in database.\n";
        echo "Run generate_all_door_qrs.php first to create door QR codes.\n";
        exit(0);
    }
    
    echo "Found " . count($doorQrs) . " door QR codes to regenerate.\n\n";
    
    $qrDir = 'qrcodes/doors/';
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0777, true);
    }
    
    $totalRegenerated = 0;
    $totalErrors = 0;
    
    foreach ($doorQrs as $qr) {
        $officeId = $qr['office_id'];
        $doorIndex = $qr['door_index'];
        $officeName = $qr['office_name'];
        $roomId = $qr['room_id'];
        
        echo "Regenerating: {$officeName} - Door {$doorIndex}\n";
        
        try {
            // Create new QR data URL with correct IP
            $qrData = $baseUrl . "explore.php?door_qr=1&office_id=" . $officeId . "&door_index=" . $doorIndex . "&from_qr=1";
            
            // Generate new filename
            $sanitizedName = sanitizeDoorFilename($officeName);
            $filename = $qrDir . $sanitizedName . "_door_" . $doorIndex . "_office_" . $officeId . ".png";
            
            // Delete old file if exists
            if (file_exists($filename)) {
                unlink($filename);
            }
            
            // Generate new QR code image
            QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);
            
            $qrImage = basename($filename);
            
            // Update database with new URL
            $updateStmt = $connect->prepare("
                UPDATE door_qrcodes 
                SET qr_code_data = ?, qr_code_image = ?, updated_at = NOW() 
                WHERE office_id = ? AND door_index = ?
            ");
            $updateStmt->execute([$qrData, $qrImage, $officeId, $doorIndex]);
            
            echo "  ✅ Regenerated: $qrImage\n";
            echo "  📱 New URL: $qrData\n\n";
            
            $totalRegenerated++;
            
        } catch (Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n\n";
            $totalErrors++;
        }
    }
    
    // Summary
    echo str_repeat("=", 60) . "\n";
    echo "SUMMARY:\n";
    echo "  ✅ Successfully regenerated: $totalRegenerated\n";
    echo "  ❌ Errors: $totalErrors\n";
    echo str_repeat("=", 60) . "\n";
    
    if ($totalRegenerated > 0) {
        echo "\n✅ SUCCESS! All door QR codes now use the correct network IP.\n";
        echo "\nNext steps:\n";
        echo "1. Print the updated QR codes from qrcodes/doors/\n";
        echo "2. Replace the old QR codes at door locations\n";
        echo "3. Test by scanning with your mobile device\n";
        echo "4. The QR codes should now work on your network!\n";
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
