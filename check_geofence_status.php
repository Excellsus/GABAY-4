<?php
/**
 * GABAY Geofence Status Check API
 * Returns whether geofencing is currently enabled
 */

header('Content-Type: application/json');

include 'connect_db.php';

try {
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection not available");
    }
    
    // Query geofence enabled status
    $stmt = $connect->query("SELECT enabled FROM geofences WHERE name = 'default' LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Default to enabled if no record found (for safety)
    $enabled = true;
    
    if ($result && isset($result['enabled'])) {
        $enabled = (bool)$result['enabled'];
    }
    
    echo json_encode([
        'success' => true,
        'enabled' => $enabled,
        'message' => $enabled ? 'Geofencing is enabled' : 'Geofencing is disabled'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'enabled' => true, // Default to enabled on error for safety
        'message' => 'Error checking geofence status: ' . $e->getMessage()
    ]);
}
