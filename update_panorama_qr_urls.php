<?php
/**
 * Update all existing panorama QR URLs to use IP addresses instead of localhost
 */

// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

include 'connect_db.php';

// Function to get the current base URL with IP detection
function getCurrentBaseUrl() {
    $baseUrl = '';
    
    // Try to detect the server's IP address
    $serverIp = '';
    if (!empty($_SERVER['SERVER_ADDR'])) {
        $serverIp = $_SERVER['SERVER_ADDR'];
    } elseif (!empty($_SERVER['LOCAL_ADDR'])) {
        $serverIp = $_SERVER['LOCAL_ADDR'];
    } else {
        // Try to get IP from command line
        $ipOutput = shell_exec('ipconfig | findstr /i "IPv4"');
        if ($ipOutput) {
            preg_match('/\d+\.\d+\.\d+\.\d+/', $ipOutput, $matches);
            if (!empty($matches[0])) {
                $serverIp = $matches[0];
            }
        }
    }
    
    if (!empty($serverIp)) {
        $baseUrl = "http://" . $serverIp . "/FinalDev/mobileScreen/";
    } else {
        // Fallback to localhost if IP detection fails
        $baseUrl = "https://localhost/gabay/mobileScreen/";
    }
    
    return $baseUrl;
}

echo "Updating Panorama QR URLs from localhost to IP addresses\n";
echo "========================================================\n\n";

try {
    // Get the new base URL
    $newBaseUrl = getCurrentBaseUrl();
    echo "New Base URL: $newBaseUrl\n\n";
    
    // Get all panorama QR records with localhost URLs
    $stmt = $connect->prepare("SELECT id, path_id, point_index, floor_number, mobile_url FROM panorama_qrcodes WHERE mobile_url LIKE '%localhost%'");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($records) . " records with localhost URLs\n\n";
    
    if (count($records) > 0) {
        $updateCount = 0;
        
        foreach ($records as $record) {
            // Extract the panorama parameters from the old URL
            $oldUrl = $record['mobile_url'];
            
            // Build new URL with IP address
            $newUrl = $newBaseUrl . "explore.php?scanned_panorama=path_id:" . $record['path_id'] . "_point:" . $record['point_index'] . "_floor:" . $record['floor_number'];
            
            // Update the record
            $updateStmt = $connect->prepare("UPDATE panorama_qrcodes SET mobile_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$newUrl, $record['id']]);
            
            echo "Updated ID {$record['id']}: {$record['path_id']}_point{$record['point_index']}_floor{$record['floor_number']}\n";
            echo "  Old: $oldUrl\n";
            echo "  New: $newUrl\n\n";
            
            $updateCount++;
        }
        
        echo "Successfully updated $updateCount panorama QR URLs!\n";
        
    } else {
        echo "No records found with localhost URLs. All panorama QR URLs are already using IP addresses.\n";
    }
    
} catch (Exception $e) {
    echo "Error updating panorama QR URLs: " . $e->getMessage() . "\n";
}

echo "\nUpdate process completed!\n";
?>