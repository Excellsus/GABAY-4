<?php
/**
 * Regenerate all panorama QR codes with IP addresses instead of localhost
 * This will create new QR code image files and update database URLs
 */

include 'connect_db.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

// Function to get current base URL with IP detection
function getCurrentBaseUrl() {
    $baseUrl = '';
    
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/mobileScreen/';
        $baseUrl = preg_replace('#([^:])/+#', '$1/', $baseUrl);
    } else {
        // Try to detect IP for command line execution
        $serverIp = '';
        $ipOutput = shell_exec('ipconfig | findstr /i "IPv4"');
        if ($ipOutput) {
            preg_match('/\d+\.\d+\.\d+\.\d+/', $ipOutput, $matches);
            if (!empty($matches[0])) {
                $serverIp = $matches[0];
                $baseUrl = "http://" . $serverIp . "/FinalDev/mobileScreen/";
            }
        }
        
        if (empty($baseUrl)) {
            $baseUrl = "http://localhost/FinalDev/mobileScreen/";
        }
    }
    
    return $baseUrl;
}

echo "Regenerating ALL Panorama QR Codes with IP Addresses\n";
echo "====================================================\n\n";

try {
    // Get the new base URL
    $newBaseUrl = getCurrentBaseUrl();
    echo "Base URL: $newBaseUrl\n\n";
    
    // Create QR codes directory if it doesn't exist
    $qrDir = __DIR__ . '/qrcodes/';
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0777, true);
    }
    
    // Get all panorama QR records
    $stmt = $connect->prepare("SELECT id, path_id, point_index, floor_number, qr_filename FROM panorama_qrcodes ORDER BY floor_number, path_id, point_index");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($records) . " panorama QR records to regenerate\n\n";
    
    $regeneratedCount = 0;
    $errorCount = 0;
    
    foreach ($records as $record) {
        try {
            // Generate new QR data URL
            $qrData = $newBaseUrl . "explore.php?scanned_panorama=path_id:" . $record['path_id'] . "_point:" . $record['point_index'] . "_floor:" . $record['floor_number'];
            
            // Create sanitized filename (same as original)
            $sanitizedName = "panorama_floor{$record['floor_number']}_{$record['path_id']}_point{$record['point_index']}";
            $filename = $qrDir . $sanitizedName . ".png";
            
            // Generate new QR code PNG file
            QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);
            
            // Update database with new URL
            $updateStmt = $connect->prepare("UPDATE panorama_qrcodes SET mobile_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$qrData, $record['id']]);
            
            echo "✓ Regenerated ID {$record['id']}: {$record['path_id']}_point{$record['point_index']}_floor{$record['floor_number']}\n";
            echo "  File: $filename\n";
            echo "  URL: $qrData\n\n";
            
            $regeneratedCount++;
            
        } catch (Exception $e) {
            echo "✗ Error regenerating ID {$record['id']}: " . $e->getMessage() . "\n\n";
            $errorCount++;
        }
    }
    
    echo "Regeneration Summary:\n";
    echo "- Successfully regenerated: $regeneratedCount QR codes\n";
    echo "- Errors encountered: $errorCount\n";
    echo "- All QR codes now use IP addresses instead of localhost!\n";
    
} catch (Exception $e) {
    echo "Fatal error during QR regeneration: " . $e->getMessage() . "\n";
}

echo "\nRegeneration process completed!\n";
?>