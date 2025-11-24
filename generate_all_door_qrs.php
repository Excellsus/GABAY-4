<?php
/**
 * Bulk Door QR Generation Script
 * 
 * Generates door QR codes for all offices that have:
 * 1. A location assigned
 * 2. Entry points defined in floor graph
 * 
 * Usage: php generate_all_door_qrs.php
 */

require_once 'auth_guard.php';
require_once 'connect_db.php';
require 'phpqrcode/qrlib.php';

echo "🚀 BULK DOOR QR CODE GENERATION\n";
echo str_repeat("=", 60) . "\n\n";

// Get base URL for QR codes
function getDoorQRBaseUrl() {
    // Always use network IP for QR codes so mobile devices can scan
    // Change this IP to match your computer's local network IP
    return "https://localhost/gabay/mobileScreen/";
}

// Sanitize filename
function sanitizeDoorFilename($string) {
    $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
    $string = preg_replace('/[\s_]+/', '_', $string);
    return trim($string, '_') ?: 'door';
}

try {
    // Get all offices with locations
    $stmt = $connect->query("SELECT id, name, location FROM offices WHERE location IS NOT NULL AND location != '' ORDER BY name");
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($offices)) {
        echo "⚠️  No offices with locations found.\n";
        exit(0);
    }
    
    echo "Found " . count($offices) . " offices with locations.\n\n";
    
    $totalGenerated = 0;
    $totalSkipped = 0;
    $totalErrors = 0;
    
    $qrDir = 'qrcodes/doors/';
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0777, true);
    }
    
    $baseUrl = getDoorQRBaseUrl();
    
    foreach ($offices as $office) {
        $officeId = $office['id'];
        $officeName = $office['name'];
        $roomId = $office['location'];
        
        echo "Processing: {$officeName} (ID: {$officeId}, Location: {$roomId})\n";
        
        // Determine floor number
        $floorNumber = 1;
        if (preg_match('/room-\d+-(\d+)/', $roomId, $matches)) {
            $floorNumber = (int)$matches[1];
        }
        
        // Load floor graph
        $graphFile = __DIR__ . '/floor_graph' . ($floorNumber > 1 ? "_$floorNumber" : '') . '.json';
        
        if (!file_exists($graphFile)) {
            echo "  ⚠️  Floor graph not found: $graphFile\n";
            $totalSkipped++;
            continue;
        }
        
        $graphData = json_decode(file_get_contents($graphFile), true);
        
        if (!isset($graphData['rooms'][$roomId]['doorPoints'])) {
            echo "  ⚠️  No door points in floor graph\n";
            $totalSkipped++;
            continue;
        }
        
        $doorPoints = $graphData['rooms'][$roomId]['doorPoints'];
        
        if (empty($doorPoints)) {
            echo "  ⚠️  Door points array is empty\n";
            $totalSkipped++;
            continue;
        }
        
        echo "  Found " . count($doorPoints) . " door point(s)\n";
        
        foreach ($doorPoints as $doorIndex => $door) {
            try {
                // Create QR data URL
                $qrData = $baseUrl . "explore.php?door_qr=1&office_id=" . $officeId . "&door_index=" . $doorIndex . "&from_qr=1";
                
                // Generate filename
                $sanitizedName = sanitizeDoorFilename($officeName);
                $filename = $qrDir . $sanitizedName . "_door_" . $doorIndex . "_office_" . $officeId . ".png";
                
                // Generate QR code image
                QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);
                
                $qrImage = basename($filename);
                
                // Save to database (upsert)
                $checkStmt = $connect->prepare("SELECT id FROM door_qrcodes WHERE office_id = ? AND door_index = ?");
                $checkStmt->execute([$officeId, $doorIndex]);
                
                if ($checkStmt->fetch()) {
                    // Update existing
                    $updateStmt = $connect->prepare("UPDATE door_qrcodes SET room_id = ?, qr_code_data = ?, qr_code_image = ?, updated_at = NOW() WHERE office_id = ? AND door_index = ?");
                    $updateStmt->execute([$roomId, $qrData, $qrImage, $officeId, $doorIndex]);
                    echo "  ✅ Updated Door $doorIndex: $qrImage\n";
                } else {
                    // Insert new
                    $insertStmt = $connect->prepare("INSERT INTO door_qrcodes (office_id, door_index, room_id, qr_code_data, qr_code_image) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$officeId, $doorIndex, $roomId, $qrData, $qrImage]);
                    echo "  ✅ Created Door $doorIndex: $qrImage\n";
                }
                
                $totalGenerated++;
                
            } catch (Exception $e) {
                echo "  ❌ Error generating Door $doorIndex: " . $e->getMessage() . "\n";
                $totalErrors++;
            }
        }
        
        echo "\n";
    }
    
    // Summary
    echo str_repeat("=", 60) . "\n";
    echo "SUMMARY:\n";
    echo "  ✅ Total QR codes generated: $totalGenerated\n";
    echo "  ⚠️  Offices skipped: $totalSkipped\n";
    echo "  ❌ Errors: $totalErrors\n";
    echo str_repeat("=", 60) . "\n";
    
    if ($totalGenerated > 0) {
        echo "\n✅ SUCCESS! Door QR codes are ready in: $qrDir\n";
        echo "\nNext steps:\n";
        echo "1. Go to $qrDir to view all generated QR codes\n";
        echo "2. Print the QR codes you need\n";
        echo "3. Place them near the physical door locations\n";
        echo "4. Test by scanning with a mobile device\n";
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
