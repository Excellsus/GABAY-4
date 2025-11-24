<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

header('Content-Type: application/json');

include 'connect_db.php';

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            handleGetDoorStatuses();
            break;
            
        case 'update':
            handleUpdateDoorStatus();
            break;
            
        case 'get_all':
            handleGetAllDoorStatuses();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGetDoorStatuses() {
    global $connect;
    
    $officeId = $_GET['office_id'] ?? null;
    
    if (!$officeId) {
        throw new Exception('Office ID is required');
    }
    
    // Get door statuses for this office
    $stmt = $connect->prepare("
        SELECT door_id, is_active 
        FROM door_status 
        WHERE office_id = ?
    ");
    $stmt->execute([$officeId]);
    
    $doors = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $doors[$row['door_id']] = (bool)$row['is_active'];
    }
    
    echo json_encode([
        'success' => true,
        'doors' => $doors
    ]);
}

function handleUpdateDoorStatus() {
    global $connect;
    
    $officeId = $_POST['office_id'] ?? null;
    $doorId = $_POST['door_id'] ?? null;
    $isActive = $_POST['is_active'] ?? '1';
    
    if (!$officeId || !$doorId) {
        throw new Exception('Office ID and Door ID are required');
    }
    
    // Convert to boolean
    $isActive = ($isActive === '1' || $isActive === 'true' || $isActive === true);
    
    // Check if record exists
    $stmt = $connect->prepare("
        SELECT id FROM door_status 
        WHERE office_id = ? AND door_id = ?
    ");
    $stmt->execute([$officeId, $doorId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing record
        $stmt = $connect->prepare("
            UPDATE door_status 
            SET is_active = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE office_id = ? AND door_id = ?
        ");
        $stmt->execute([$isActive, $officeId, $doorId]);
    } else {
        // Insert new record
        $stmt = $connect->prepare("
            INSERT INTO door_status (office_id, door_id, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$officeId, $doorId, $isActive]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Door status updated successfully',
        'door_id' => $doorId,
        'is_active' => $isActive
    ]);
}

function handleGetAllDoorStatuses() {
    global $connect;
    
    // Get all door statuses
    $stmt = $connect->query("
        SELECT office_id, door_id, is_active 
        FROM door_status
    ");
    
    $doors = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $officeId = $row['office_id'];
        if (!isset($doors[$officeId])) {
            $doors[$officeId] = [];
        }
        $doors[$officeId][$row['door_id']] = (bool)$row['is_active'];
    }
    
    echo json_encode([
        'success' => true,
        'doors' => $doors
    ]);
}
?>
