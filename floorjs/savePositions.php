<?php
    
require_once __DIR__ . '/../auth_guard.php';

include '../connect_db.php'; // Use correct relative path

header('Content-Type: application/json'); // Ensure JSON response

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['assignments'])) {
    echo json_encode(['success' => false, 'message' => 'No assignments provided']);
    exit;
}

try {
    foreach ($data['assignments'] as $assignment) {
        $officeId = intval($assignment['officeId']);
        $roomId = $assignment['roomId']; // Expected format: room-<number>-<floor>
        
        // Validate room ID format (allow any floor suffix)
        if (!preg_match('/^room-\d+(?:-\d+)?$/', $roomId)) {
            continue; // Skip invalid room IDs
        }
        
        if (!$officeId) {
            continue; // Skip invalid office IDs
        }
        
        // Get office name and verify office exists before updating
        $nameStmt = $connect->prepare("SELECT name FROM offices WHERE id = ?");
        $nameStmt->execute([$officeId]);
        $officeName = $nameStmt->fetchColumn();
        
        if ($officeName) {
            // First check if another office is already using this location
            $checkStmt = $connect->prepare("SELECT id FROM offices WHERE location = ? AND id != ?");
            $checkStmt->execute([$roomId, $officeId]);
            $existingOffice = $checkStmt->fetch();
            
            if ($existingOffice) {
                // Another office is using this location, we need to swap
                $stmt = $connect->prepare("UPDATE offices SET location = ? WHERE id = ?");
                
                // Get the current location of the office we're updating
                $currentLocStmt = $connect->prepare("SELECT location FROM offices WHERE id = ?");
                $currentLocStmt->execute([$officeId]);
                $currentLocation = $currentLocStmt->fetchColumn();
                
                // Only proceed with swap if current location is also in correct format
                if ($currentLocation && preg_match('/^room-\d+(?:-\d+)?$/', $currentLocation)) {
                    // Update the other office to use the current office's location
                    $stmt->execute([$currentLocation, $existingOffice['id']]);
                    
                    // Log the swap for the other office
                    $otherNameStmt = $connect->prepare("SELECT name FROM offices WHERE id = ?");
                    $otherNameStmt->execute([$existingOffice['id']]);
                    $otherOfficeName = $otherNameStmt->fetchColumn();
                    
                    $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
                    $activityText = "Floor plan location swapped with " . $officeName;
                    $activityStmt->execute(['file', $activityText, $existingOffice['id']]);
                }
            }
            
            // Update the current office's location
            $stmt = $connect->prepare("UPDATE offices SET location = ? WHERE id = ?");
            $stmt->execute([$roomId, $officeId]);
            
            // Log the floor plan update activity
            $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
            $activityText = "Floor plan location updated for " . $officeName;
            $activityStmt->execute(['file', $activityText, $officeId]);
        } else {
            error_log("Warning: Office with ID {$officeId} not found when updating floor plan");
        }
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error in savePositions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}