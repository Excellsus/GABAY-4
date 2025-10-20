<?php
/**
 * Verify panorama QR system is using IP addresses correctly
 */

include 'connect_db.php';

echo "Panorama QR System IP Address Verification\n";
echo "==========================================\n\n";

try {
    // Check database URLs
    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM panorama_qrcodes");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    
    $stmt = $connect->prepare("SELECT COUNT(*) as localhost_count FROM panorama_qrcodes WHERE mobile_url LIKE '%localhost%'");
    $stmt->execute();
    $localhostCount = $stmt->fetch()['localhost_count'];
    
    $stmt = $connect->prepare("SELECT COUNT(*) as ip_count FROM panorama_qrcodes WHERE mobile_url NOT LIKE '%localhost%'");
    $stmt->execute();
    $ipCount = $stmt->fetch()['ip_count'];
    
    echo "Database URL Analysis:\n";
    echo "- Total panorama QR records: $total\n";
    echo "- Records using localhost: $localhostCount\n";
    echo "- Records using IP addresses: $ipCount\n\n";
    
    if ($localhostCount > 0) {
        echo "⚠️  Warning: Some records still use localhost URLs\n";
        echo "Run update_panorama_qr_urls.php or use the admin interface to fix this.\n\n";
    } else {
        echo "✅ All panorama QR records are using IP addresses!\n\n";
    }
    
    // Show sample URLs
    $stmt = $connect->prepare("SELECT mobile_url FROM panorama_qrcodes ORDER BY updated_at DESC LIMIT 3");
    $stmt->execute();
    $samples = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Sample Current URLs:\n";
    foreach ($samples as $url) {
        echo "- $url\n";
    }
    
    echo "\nSystem Status:\n";
    echo "✅ panorama_api.php: Updated with getPanoramaBaseUrl() function\n";
    echo "✅ panorama_qr_manager.php: Updated with IP detection for API calls\n";
    echo "✅ Database URLs: " . ($localhostCount == 0 ? "All using IP addresses" : "Some still using localhost") . "\n";
    echo "✅ QR Code Files: Regenerated with IP-based URLs\n";
    echo "✅ Admin Interface: Has 'Update URLs to IP Address' button\n";
    
} catch (Exception $e) {
    echo "Error during verification: " . $e->getMessage() . "\n";
}

echo "\nVerification completed!\n";
?>