<?php
header('Content-Type: application/json');
include 'connect_db.php';

// Handle different API operations
$operation = $_POST['operation'] ?? $_GET['operation'] ?? '';

switch ($operation) {
    case 'getQrStatus':
        handleGetQrStatus();
        break;
    case 'updateQrStatus':
        handleUpdateQrStatus();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid operation']);
        break;
}

function handleGetQrStatus() {
    global $connect;
    
    $office_id = $_POST['office_id'] ?? $_GET['office_id'] ?? '';
    
    if (empty($office_id)) {
        echo json_encode(['success' => false, 'message' => 'Office ID required']);
        return;
    }
    
    try {
        $stmt = $connect->prepare("SELECT is_active FROM qrcode_info WHERE office_id = ?");
        $stmt->execute([$office_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'is_active' => (bool)$result['is_active']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'QR code not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleUpdateQrStatus() {
    global $connect;
    
    $office_id = $_POST['office_id'] ?? '';
    $is_active = $_POST['is_active'] ?? '';
    
    if (empty($office_id) || $is_active === '') {
        echo json_encode(['success' => false, 'message' => 'Office ID and is_active status required']);
        return;
    }
    
    // Convert to boolean/integer
    $is_active = $is_active === 'true' || $is_active === '1' || $is_active === 1 ? 1 : 0;
    
    try {
        $stmt = $connect->prepare("UPDATE qrcode_info SET is_active = ? WHERE office_id = ?");
        $result = $stmt->execute([$is_active, $office_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log activity
            $officeStmt = $connect->prepare("SELECT name FROM offices WHERE id = ?");
            $officeStmt->execute([$office_id]);
            $officeName = $officeStmt->fetchColumn();
            
            $status = $is_active ? 'activated' : 'deactivated';
            $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
            $activityStmt->execute(['qr_code', "QR code for office '$officeName' was $status", $office_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'QR code status updated successfully',
                'is_active' => (bool)$is_active
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update QR code status or QR code not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>