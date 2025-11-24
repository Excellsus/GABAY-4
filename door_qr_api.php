<?php
/**
 * Door QR Code API
 * 
 * Handles CRUD operations for door entry point QR codes
 * Actions: generate, regenerate, delete, get_all, get_by_office
 */

require_once 'auth_guard.php';
require_once 'connect_db.php';
require 'phpqrcode/qrlib.php';

header('Content-Type: application/json');

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

/**
 * Generate base URL for mobile QR scanning
 */
function getDoorQRBaseUrl() {
    // Dynamic URL detection for production and development
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/mobileScreen/';
        return preg_replace('#([^:])/+#', '$1/', $baseUrl);
    }
    // Fallback for production environment
    return "https://localhost/gabay/mobileScreen/";
}

/**
 * Sanitize filename
 */
function sanitizeDoorFilename($string) {
    $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
    $string = preg_replace('/[\s_]+/', '_', $string);
    return trim($string, '_') ?: 'door';
}

try {
    switch ($action) {
        
        // ============================================================
        // GENERATE: Create QR codes for all doors of an office
        // ============================================================
        case 'generate':
            $officeId = $_POST['office_id'] ?? null;
            
            if (!$officeId) {
                throw new Exception('Office ID is required');
            }
            
            // Get office details
            $stmt = $connect->prepare("SELECT id, name, location FROM offices WHERE id = ?");
            $stmt->execute([$officeId]);
            $office = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$office) {
                throw new Exception('Office not found');
            }
            
            if (!$office['location']) {
                throw new Exception('Office has no location assigned');
            }
            
            // Load floor graph to get entry points
            $roomId = $office['location'];
            $floorNumber = 1; // Default
            if (preg_match('/room-\d+-(\d+)/', $roomId, $matches)) {
                $floorNumber = (int)$matches[1];
            }
            
            // Build the correct floor graph filename
            $graphFile = __DIR__ . '/floor_graph' . ($floorNumber > 1 ? "_$floorNumber" : '') . '.json';
            
            // Enhanced debugging for floor 3
            if ($floorNumber == 3) {
                error_log("Floor 3 QR Generation Debug:");
                error_log("  Office ID: $officeId");
                error_log("  Office Name: " . $office['name']);
                error_log("  Room ID: $roomId");
                error_log("  Graph File: $graphFile");
                error_log("  File Exists: " . (file_exists($graphFile) ? 'YES' : 'NO'));
            }
            
            if (!file_exists($graphFile)) {
                throw new Exception("Floor graph file not found: $graphFile (Floor $floorNumber)");
            }
            
            $graphData = json_decode(file_get_contents($graphFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to parse floor graph JSON: " . json_last_error_msg());
            }
            
            if (!isset($graphData['rooms'])) {
                throw new Exception("Floor graph file is missing 'rooms' property");
            }
            
            if (!isset($graphData['rooms'][$roomId])) {
                throw new Exception("Room '$roomId' not found in floor $floorNumber graph. Available rooms: " . implode(', ', array_keys($graphData['rooms'])));
            }
            
            if (!isset($graphData['rooms'][$roomId]['doorPoints'])) {
                throw new Exception("No doorPoints found for room '$roomId'. Room has properties: " . implode(', ', array_keys($graphData['rooms'][$roomId])));
            }
            
            $entryPoints = $graphData['rooms'][$roomId]['doorPoints'];
            $qrDir = 'qrcodes/doors/';
            
            if (!file_exists($qrDir)) {
                mkdir($qrDir, 0777, true);
            }
            
            $baseUrl = getDoorQRBaseUrl();
            $generated = [];
            
            foreach ($entryPoints as $doorIndex => $door) {
                // Create QR data URL - includes office_id and door_index
                $qrData = $baseUrl . "explore.php?door_qr=1&office_id=" . $officeId . "&door_index=" . $doorIndex . "&from_qr=1";
                
                // Generate filename
                $sanitizedName = sanitizeDoorFilename($office['name']);
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
                } else {
                    // Insert new
                    $insertStmt = $connect->prepare("INSERT INTO door_qrcodes (office_id, door_index, room_id, qr_code_data, qr_code_image) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$officeId, $doorIndex, $roomId, $qrData, $qrImage]);
                }
                
                $generated[] = [
                    'door_index' => $doorIndex,
                    'filename' => $qrImage,
                    'url' => $qrData
                ];
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Generated ' . count($generated) . ' door QR codes',
                'doors' => $generated
            ]);
            break;
            
        // ============================================================
        // GET_ALL: Retrieve all door QR codes for an office
        // ============================================================
        case 'get_all':
            $officeId = $_GET['office_id'] ?? null;
            
            if (!$officeId) {
                throw new Exception('Office ID is required');
            }
            
            $stmt = $connect->prepare("SELECT * FROM door_qrcodes WHERE office_id = ? ORDER BY door_index ASC");
            $stmt->execute([$officeId]);
            $doorQrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'doors' => $doorQrs
            ]);
            break;
            
        // ============================================================
        // DELETE: Remove a specific door QR code
        // ============================================================
        case 'delete':
            $officeId = $_POST['office_id'] ?? null;
            $doorIndex = $_POST['door_index'] ?? null;
            
            if (!$officeId || $doorIndex === null) {
                throw new Exception('Office ID and door index are required');
            }
            
            // Get QR image filename before deleting
            $stmt = $connect->prepare("SELECT qr_code_image FROM door_qrcodes WHERE office_id = ? AND door_index = ?");
            $stmt->execute([$officeId, $doorIndex]);
            $qr = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($qr) {
                // Delete file
                $filePath = 'qrcodes/doors/' . $qr['qr_code_image'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete from database
                $deleteStmt = $connect->prepare("DELETE FROM door_qrcodes WHERE office_id = ? AND door_index = ?");
                $deleteStmt->execute([$officeId, $doorIndex]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Door QR code deleted'
                ]);
            } else {
                throw new Exception('Door QR code not found');
            }
            break;
            
        // ============================================================
        // DELETE_ALL: Remove all door QR codes for an office
        // ============================================================
        case 'delete_all':
            $officeId = $_POST['office_id'] ?? null;
            
            if (!$officeId) {
                throw new Exception('Office ID is required');
            }
            
            // Get all QR images
            $stmt = $connect->prepare("SELECT qr_code_image FROM door_qrcodes WHERE office_id = ?");
            $stmt->execute([$officeId]);
            $qrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete files
            foreach ($qrs as $qr) {
                $filePath = 'qrcodes/doors/' . $qr['qr_code_image'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Delete from database
            $deleteStmt = $connect->prepare("DELETE FROM door_qrcodes WHERE office_id = ?");
            $deleteStmt->execute([$officeId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All door QR codes deleted for office'
            ]);
            break;
            
        // ============================================================
        // TOGGLE_STATUS: Enable/disable a door QR code
        // ============================================================
        case 'toggle_status':
            $officeId = $_POST['office_id'] ?? null;
            $doorIndex = $_POST['door_index'] ?? null;
            $isActive = $_POST['is_active'] ?? null;
            
            if (!$officeId || $doorIndex === null || $isActive === null) {
                throw new Exception('Office ID, door index, and status are required');
            }
            
            $stmt = $connect->prepare("UPDATE door_qrcodes SET is_active = ? WHERE office_id = ? AND door_index = ?");
            $stmt->execute([$isActive ? 1 : 0, $officeId, $doorIndex]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Door QR status updated'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
