<?php
/**
 * Public Door Status API - No Authentication Required
 * 
 * This endpoint allows mobile visitors (non-authenticated users) to fetch
 * door statuses for the floor plan. Only GET requests are allowed.
 * 
 * Usage: public_door_status_api.php?action=get_all
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests for mobile

include 'connect_db.php';

// Only allow GET requests (read-only for public access)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Only GET requests are supported.']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            handleGetAllDoorStatuses();
            break;
            
        default:
            throw new Exception('Invalid action. Only "get_all" is supported.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGetAllDoorStatuses() {
    global $connect;
    
    try {
        // Fetch all door statuses grouped by office
        $stmt = $connect->prepare("
            SELECT office_id, door_id, is_active 
            FROM door_status 
            ORDER BY office_id, door_id
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group door statuses by office_id
        $doorsByOffice = [];
        foreach ($results as $row) {
            $officeId = $row['office_id'];
            $doorId = $row['door_id'];
            $isActive = (bool)$row['is_active'];
            
            if (!isset($doorsByOffice[$officeId])) {
                $doorsByOffice[$officeId] = [];
            }
            
            $doorsByOffice[$officeId][$doorId] = $isActive;
        }
        
        echo json_encode([
            'success' => true,
            'doors' => $doorsByOffice,
            'total_offices' => count($doorsByOffice),
            'total_doors' => count($results),
            'public_access' => true // Flag to indicate this is public API
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in public_door_status_api.php: " . $e->getMessage());
        throw new Exception('Database error: ' . $e->getMessage());
    }
}
