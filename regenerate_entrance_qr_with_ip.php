<?php
/**
 * Regenerate Entrance QR Codes with Network IP Address
 * 
 * This script regenerates all entrance QR codes using your computer's
 * local network IP address instead of localhost, making them scannable
 * from mobile devices on the same network.
 * 
 * Usage:
 * 1. Run: php regenerate_entrance_qr_with_ip.php
 * 2. Or access via browser: http://localhost/gabay/regenerate_entrance_qr_with_ip.php
 */

require_once 'connect_db.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

echo "<h2>Entrance QR Code Regeneration Tool</h2>\n";
echo "<p>This script will regenerate entrance QR codes with your network IP address.</p>\n";

// Function to get local IP address
function getLocalIP() {
    // Get all network interfaces
    $localIP = gethostbyname(gethostname());
    
    // If that doesn't work, try to get it from socket
    if ($localIP === gethostname() || $localIP === '127.0.0.1') {
        // Try to connect to an external server to determine local IP
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_connect($sock, "8.8.8.8", 53);
        socket_getsockname($sock, $localIP);
        socket_close($sock);
    }
    
    return $localIP;
}

// Get the local IP
$localIP = getLocalIP();

echo "<h3>📡 Detected Network Configuration:</h3>\n";
echo "<pre>\n";
echo "Local IP Address: $localIP\n";
echo "Current localhost URL: http://localhost/gabay/\n";
echo "Network URL: http://$localIP/gabay/\n";
echo "</pre>\n";

echo "<h4>⚠️ IMPORTANT INSTRUCTIONS:</h4>\n";
echo "<ol>\n";
echo "<li><strong>Verify your IP:</strong> The detected IP is <code>$localIP</code></li>\n";
echo "<li><strong>If incorrect:</strong> You can manually set it below</li>\n";
echo "<li><strong>Mobile must be on same WiFi:</strong> Your phone must be connected to the same network as this computer</li>\n";
echo "<li><strong>Test in browser first:</strong> Try accessing <code>http://$localIP/gabay/mobileScreen/explore.php</code> on your phone</li>\n";
echo "</ol>\n";

// Allow manual IP override via form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate'])) {
    $customIP = $_POST['custom_ip'] ?? $localIP;
    $baseUrl = "http://$customIP/gabay/mobileScreen/";
    
    echo "<hr>\n";
    echo "<h3>🔄 Regenerating QR Codes...</h3>\n";
    echo "<pre>\n";
    
    try {
        // Get all entrances from database
        $stmt = $connect->query("SELECT * FROM entrance_qrcodes ORDER BY floor, entrance_id");
        $entrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($entrances)) {
            echo "❌ No entrances found in database!\n";
            echo "   Run: php create_entrance_tables.php first\n";
            exit;
        }
        
        $qrDir = __DIR__ . '/entrance_qrcodes/';
        $successCount = 0;
        $errorCount = 0;
        
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
                
                echo "✓ Regenerated: $label (Floor $floor)\n";
                echo "  URL: $qrData\n";
                echo "  File: $filename\n\n";
                
                $successCount++;
            } catch (Exception $e) {
                echo "❌ Failed: $label - " . $e->getMessage() . "\n\n";
                $errorCount++;
            }
        }
        
        echo "\n========================================\n";
        echo "✅ Regeneration Complete!\n";
        echo "========================================\n";
        echo "Success: $successCount QR codes\n";
        echo "Errors: $errorCount QR codes\n\n";
        
        echo "Next steps:\n";
        echo "1. Download QR codes from entrance_qrcodes/ folder\n";
        echo "2. Print and place at physical entrance locations\n";
        echo "3. Test by scanning with mobile device\n";
        echo "4. Ensure mobile is on same WiFi network ($customIP)\n";
        
    } catch (PDOException $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
    
    echo "</pre>\n";
    
} else {
    // Show form
    echo "<hr>\n";
    echo "<h3>🚀 Ready to Regenerate?</h3>\n";
    echo "<form method='POST'>\n";
    echo "<p>\n";
    echo "<label for='custom_ip'><strong>Network IP Address:</strong></label><br>\n";
    echo "<input type='text' id='custom_ip' name='custom_ip' value='$localIP' size='20' style='font-size: 16px; padding: 5px;'>\n";
    echo "</p>\n";
    echo "<p>\n";
    echo "<button type='submit' name='regenerate' value='1' style='font-size: 16px; padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 5px;'>🔄 Regenerate All Entrance QR Codes</button>\n";
    echo "</p>\n";
    echo "</form>\n";
    
    echo "<hr>\n";
    echo "<h3>📋 Current Entrance QR Codes:</h3>\n";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background-color: #f0f0f0;'>\n";
    echo "<th>Label</th>\n";
    echo "<th>Floor</th>\n";
    echo "<th>Current URL</th>\n";
    echo "<th>Status</th>\n";
    echo "<th>Preview</th>\n";
    echo "</tr>\n";
    
    try {
        $stmt = $connect->query("SELECT * FROM entrance_qrcodes ORDER BY floor, entrance_id");
        $entrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($entrances as $entrance) {
            $statusIcon = $entrance['is_active'] ? '✅' : '🚫';
            $urlStyle = (strpos($entrance['qr_code_data'], 'localhost') !== false) ? 'color: red; font-weight: bold;' : 'color: green;';
            
            echo "<tr>\n";
            echo "<td>{$entrance['label']}</td>\n";
            echo "<td style='text-align: center;'>{$entrance['floor']}</td>\n";
            echo "<td style='$urlStyle font-size: 12px;'>{$entrance['qr_code_data']}</td>\n";
            echo "<td style='text-align: center;'>$statusIcon</td>\n";
            echo "<td style='text-align: center;'><img src='entrance_qrcodes/{$entrance['qr_code_image']}' width='80' height='80'></td>\n";
            echo "</tr>\n";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='5'>Error loading entrances: " . $e->getMessage() . "</td></tr>\n";
    }
    
    echo "</table>\n";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h2, h3, h4 {
    color: #333;
}
pre {
    background-color: #fff;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    border: 1px solid #ddd;
}
table {
    background-color: #fff;
    margin-top: 10px;
}
code {
    background-color: #f0f0f0;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}
ol {
    line-height: 1.8;
}
</style>
