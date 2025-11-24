<?php
/**
 * Entrance QR Code Management API
 * 
 * CRUD operations for entrance QR codes:
 * - generate: Create QR codes from floor graph entrance definitions
 * - get_all: Retrieve all entrance QR codes
 * - get_by_floor: Get entrances filtered by floor
 * - delete: Remove entrance QR code
 * - toggle_status: Activate/deactivate entrance QR
 * - regenerate: Regenerate single entrance QR code
 * 
 * Entrances are independent of offices and excluded from statistics.
 */

// Start output buffering to catch any stray output
ob_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'connect_db.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

// Set content type header
header('Content-Type: application/json');

// Clean any buffered output that might have occurred during includes
ob_end_clean();

// Start session for CSRF token validation (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token validation for state-changing operations
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

/**
 * Get base URL for QR code links
 * @return string Base URL with protocol and domain
 */
function getEntranceQRBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $protocol . '://' . $host . $scriptDir . '/mobileScreen/';
}

/**
 * Sanitize entrance ID for filename
 * @param string $string Entrance ID or label
 * @return string Sanitized filename
 */
function sanitizeEntranceFilename($string) {
    // Remove special characters, keep letters, numbers, spaces, hyphens, underscores
    $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
    // Replace multiple spaces/underscores with single underscore
    $string = preg_replace('/[\s_]+/', '_', $string);
    return trim($string, '_') ?: 'entrance';
}

/**
 * Read entrances from floor graph JSON files
 * @return array Array of entrance definitions from all floors
 */
function getEntrancesFromFloorGraphs() {
    $entrances = [];
    $floorFiles = [
        1 => __DIR__ . '/floor_graph.json',
        2 => __DIR__ . '/floor_graph_2.json',
        3 => __DIR__ . '/floor_graph_3.json'
    ];
    
    foreach ($floorFiles as $floor => $filePath) {
        if (!file_exists($filePath)) {
            continue;
        }
        
        $jsonContent = file_get_contents($filePath);
        $graphData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to parse floor graph for floor $floor: " . json_last_error_msg());
            continue;
        }
        
        // Check if entrances array exists in floor graph
        if (isset($graphData['entrances']) && is_array($graphData['entrances'])) {
            foreach ($graphData['entrances'] as $entrance) {
                // Ensure entrance has required fields
                if (isset($entrance['id'], $entrance['label'], $entrance['x'], $entrance['y'])) {
                    $entrance['floor'] = $floor; // Add floor number
                    $entrances[] = $entrance;
                }
            }
        }
    }
    
    return $entrances;
}

/**
 * Generate QR codes for all entrances defined in floor graphs
 */
function generateEntranceQRCodes($connect) {
    global $connect;
    
    try {
        // Read entrances from floor graph JSON files
        $entrances = getEntrancesFromFloorGraphs();
        
        if (empty($entrances)) {
            echo json_encode([
                'success' => false, 
                'error' => 'No entrances found in floor graph JSON files. Please add entrances array to floor_graph.json, floor_graph_2.json, floor_graph_3.json'
            ]);
            return;
        }
        
        $baseUrl = getEntranceQRBaseUrl();
        $qrDir = __DIR__ . '/entrance_qrcodes/';
        
        // Create directory if it doesn't exist
        if (!is_dir($qrDir)) {
            if (!mkdir($qrDir, 0755, true)) {
                throw new Exception('Failed to create entrance_qrcodes directory');
            }
        }
        
        $generatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        foreach ($entrances as $entrance) {
            $entranceId = $entrance['id'];
            $floor = $entrance['floor'];
            $label = $entrance['label'];
            $x = $entrance['x'];
            $y = $entrance['y'];
            $nearestPathId = $entrance['nearestPathId'] ?? null;
            
            // Check if QR already exists
            $checkStmt = $connect->prepare("SELECT id FROM entrance_qrcodes WHERE entrance_id = ?");
            $checkStmt->execute([$entranceId]);
            
            if ($checkStmt->fetch()) {
                $skippedCount++;
                continue; // Skip if already exists
            }
            
            // Generate QR code URL
            $qrData = $baseUrl . "explore.php?entrance_qr=1&entrance_id=" . urlencode($entranceId) . "&floor=" . $floor;
            
            // Generate filename: entrance_main_1_floor_1.png
            $sanitizedId = sanitizeEntranceFilename($entranceId);
            $filename = $sanitizedId . "_floor_" . $floor . ".png";
            $filePath = $qrDir . $filename;
            
            // Generate QR code image
            try {
                QRcode::png($qrData, $filePath, QR_ECLEVEL_L, 8, 2);
            } catch (Exception $e) {
                $errors[] = "Failed to generate QR for $entranceId: " . $e->getMessage();
                continue;
            }
            
            // Insert into database
            $insertStmt = $connect->prepare("
                INSERT INTO entrance_qrcodes 
                (entrance_id, floor, label, x, y, nearest_path_id, qr_code_data, qr_code_image, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $insertStmt->execute([
                $entranceId,
                $floor,
                $label,
                $x,
                $y,
                $nearestPathId,
                $qrData,
                $filename
            ]);
            
            $generatedCount++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Generated $generatedCount QR codes, skipped $skippedCount existing",
            'generated' => $generatedCount,
            'skipped' => $skippedCount,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        error_log("Entrance QR generation error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate entrance QR codes: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get all entrance QR codes
 */
function getAllEntranceQRs($connect) {
    try {
        $stmt = $connect->prepare("
            SELECT 
                id, entrance_id, floor, label, x, y, nearest_path_id,
                qr_code_data, qr_code_image, is_active, created_at, updated_at
            FROM entrance_qrcodes 
            ORDER BY floor ASC, label ASC
        ");
        $stmt->execute();
        $entrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add full file path for downloads
        foreach ($entrances as &$entrance) {
            $entrance['qr_code_path'] = 'entrance_qrcodes/' . $entrance['qr_code_image'];
        }
        
        echo json_encode([
            'success' => true,
            'entrances' => $entrances
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to retrieve entrance QR codes: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get entrances filtered by floor
 */
function getEntrancesByFloor($connect, $floor) {
    try {
        $stmt = $connect->prepare("
            SELECT 
                id, entrance_id, floor, label, x, y, nearest_path_id,
                qr_code_data, qr_code_image, is_active, created_at, updated_at
            FROM entrance_qrcodes 
            WHERE floor = ?
            ORDER BY label ASC
        ");
        $stmt->execute([$floor]);
        $entrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add full file path for downloads
        foreach ($entrances as &$entrance) {
            $entrance['qr_code_path'] = 'entrance_qrcodes/' . $entrance['qr_code_image'];
        }
        
        echo json_encode([
            'success' => true,
            'entrances' => $entrances
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to retrieve entrances: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete entrance QR code
 */
function deleteEntranceQR($connect, $entranceId) {
    try {
        // Get QR image filename before deletion
        $stmt = $connect->prepare("SELECT qr_code_image FROM entrance_qrcodes WHERE entrance_id = ?");
        $stmt->execute([$entranceId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo json_encode(['success' => false, 'error' => 'Entrance QR not found']);
            return;
        }
        
        $filename = $result['qr_code_image'];
        $filePath = __DIR__ . '/entrance_qrcodes/' . $filename;
        
        // Delete from database (cascade will delete scan logs)
        $deleteStmt = $connect->prepare("DELETE FROM entrance_qrcodes WHERE entrance_id = ?");
        $deleteStmt->execute([$entranceId]);
        
        // Delete QR image file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Entrance QR code deleted successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete entrance QR: ' . $e->getMessage()
        ]);
    }
}

/**
 * Toggle entrance QR active status
 */
function toggleEntranceStatus($connect, $entranceId, $isActive) {
    try {
        $stmt = $connect->prepare("UPDATE entrance_qrcodes SET is_active = ? WHERE entrance_id = ?");
        $stmt->execute([$isActive, $entranceId]);
        
        $status = $isActive ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true,
            'message' => "Entrance QR $status successfully"
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to toggle entrance status: ' . $e->getMessage()
        ]);
    }
}

/**
 * Regenerate single entrance QR code
 */
function regenerateEntranceQR($connect, $entranceId) {
    try {
        // Get entrance data from database
        $stmt = $connect->prepare("SELECT * FROM entrance_qrcodes WHERE entrance_id = ?");
        $stmt->execute([$entranceId]);
        $entrance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entrance) {
            echo json_encode(['success' => false, 'error' => 'Entrance not found']);
            return;
        }
        
        $qrDir = __DIR__ . '/entrance_qrcodes/';
        $oldFilePath = $qrDir . $entrance['qr_code_image'];
        
        // Delete old QR image
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
        
        // Generate new QR code with same data
        $newFilePath = $qrDir . $entrance['qr_code_image'];
        QRcode::png($entrance['qr_code_data'], $newFilePath, QR_ECLEVEL_L, 8, 2);
        
        // Update timestamp
        $updateStmt = $connect->prepare("UPDATE entrance_qrcodes SET updated_at = NOW() WHERE entrance_id = ?");
        $updateStmt->execute([$entranceId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Entrance QR code regenerated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Update entrance position in database
 */
function updateEntrancePosition($connect, $entranceId, $x, $y, $nearestPathId = null) {
    try {
        // Build update query based on whether nearestPathId is provided
        if ($nearestPathId !== null) {
            $stmt = $connect->prepare("UPDATE entrance_qrcodes SET x = ?, y = ?, nearest_path_id = ?, updated_at = NOW() WHERE entrance_id = ?");
            $stmt->execute([$x, $y, $nearestPathId, $entranceId]);
        } else {
            $stmt = $connect->prepare("UPDATE entrance_qrcodes SET x = ?, y = ?, updated_at = NOW() WHERE entrance_id = ?");
            $stmt->execute([$x, $y, $entranceId]);
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Entrance position updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Entrance not found or position unchanged'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to regenerate entrance QR: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// MAIN API ROUTING
// ============================================

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'generate':
            // POST only - requires CSRF validation
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            validateCSRFToken($_POST['csrf_token'] ?? '');
            generateEntranceQRCodes($connect);
            break;
            
        case 'get_all':
            // GET - no CSRF needed for read operations
            getAllEntranceQRs($connect);
            break;
            
        case 'get_by_floor':
            // GET - requires floor parameter
            $floor = $_GET['floor'] ?? null;
            if ($floor === null) {
                throw new Exception('Floor parameter required');
            }
            getEntrancesByFloor($connect, $floor);
            break;
            
        case 'delete':
            // POST only - requires CSRF validation
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            validateCSRFToken($_POST['csrf_token'] ?? '');
            $entranceId = $_POST['entrance_id'] ?? null;
            if (!$entranceId) {
                throw new Exception('Entrance ID required');
            }
            deleteEntranceQR($connect, $entranceId);
            break;
            
        case 'toggle_status':
            // POST only - requires CSRF validation
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            validateCSRFToken($_POST['csrf_token'] ?? '');
            $entranceId = $_POST['entrance_id'] ?? null;
            $isActive = $_POST['is_active'] ?? null;
            if ($entranceId === null || $isActive === null) {
                throw new Exception('Entrance ID and status required');
            }
            toggleEntranceStatus($connect, $entranceId, $isActive);
            break;
            
        case 'regenerate':
            // POST only - requires CSRF validation
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            validateCSRFToken($_POST['csrf_token'] ?? '');
            $entranceId = $_POST['entrance_id'] ?? null;
            if (!$entranceId) {
                throw new Exception('Entrance ID required');
            }
            regenerateEntranceQR($connect, $entranceId);
            break;
            
        case 'update_position':
            // POST only - requires CSRF validation
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            validateCSRFToken($_POST['csrf_token'] ?? '');
            $entranceId = $_POST['entrance_id'] ?? null;
            $x = $_POST['x'] ?? null;
            $y = $_POST['y'] ?? null;
            $nearestPathId = $_POST['nearest_path_id'] ?? null;
            
            if ($entranceId === null || $x === null || $y === null) {
                throw new Exception('Entrance ID, x, and y coordinates required');
            }
            updateEntrancePosition($connect, $entranceId, $x, $y, $nearestPathId);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
