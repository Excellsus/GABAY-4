<?php
/**
 * Panorama Management API
 * Handles panorama image uploads, updates, and deletions for the admin interface
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect_db.php';
include 'panorama_functions.php';

/**
 * Get base URL for panorama QR codes - matches the office QR approach
 * Determines base URL dynamically when possible so QR codes work across devices/networks
 */
function getPanoramaBaseUrl() {
    $baseUrl = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        // dirname($_SERVER['SCRIPT_NAME']) gives the directory of this script (e.g., /FinalDev)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        // Ensure we end up with a trailing slash and point to the mobileScreen folder
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/mobileScreen/';
        // Normalize double slashes (except after http(s):)
        $baseUrl = preg_replace('#([^:])/+#', '$1/', $baseUrl);
    } else {
        // Fallback: common local dev path — adjust if your environment differs
        $baseUrl = "http://localhost/FinalDev/mobileScreen/";
    }
    
    return $baseUrl;
}

// Function to create a safe filename from panorama info (matches office system)
function sanitize_filename($string) {
    // Remove any character that is not a letter, number, space, hyphen, or underscore.
    // \pL matches any kind of letter from any language. \pN matches any kind of number.
    $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
    // Replace multiple spaces, underscores, or hyphens with a single underscore.
    $string = preg_replace('/[\s_]+/', '_', $string);
    // Trim underscores from the beginning and end of the string.
    $string = trim($string, '_');
    // If the string is empty after sanitization, default to 'panorama'
    if (empty($string)) {
        return 'panorama';
    }
    return $string;
}

// Set content type to JSON
header('Content-Type: application/json');

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'upload':
            handlePanoramaUpload();
            break;
            
        case 'update':
            handlePanoramaUpdate();
            break;
            
        case 'delete':
            handlePanoramaDelete();
            break;
            
        case 'get':
            handleGetPanorama();
            break;
            
        case 'list':
            handleListPanoramas();
            break;
            
        case 'get_for_point':
            handleGetPanoramaForPoint();
            break;
            
        case 'save_hotspots':
            handleSaveHotspots();
            break;
            
        case 'get_hotspots':
            handleGetHotspots();
            break;
            
        case 'delete_hotspot':
            handleDeleteHotspot();
            break;
            
        case 'get_linkable_panoramas':
            handleGetLinkablePanoramas();
            break;
            
        case 'get_all_active':
            handleGetAllActivePanoramas();
            break;
            
        case 'validate_hotspot_link':
            handleValidateHotspotLink();
            break;
            
        case 'get_status':
            handleGetPanoramaStatus();
            break;
            
        case 'update_status':
            handleUpdatePanoramaStatus();
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle panorama image upload
 */
function handlePanoramaUpload() {
    global $connect;
    
    // Validate required parameters
    $requiredParams = ['path_id', 'point_index', 'point_x', 'point_y', 'floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param]) || $_POST[$param] === '') {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    // Validate file upload
    if (!isset($_FILES['panorama_file']) || $_FILES['panorama_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $file = $_FILES['panorama_file'];
    
    // Validate file
    $validation = validatePanoramaFile($file);
    if ($validation !== true) {
        throw new Exception($validation);
    }
    
    // Generate unique filename
    $filename = generatePanoramaFilename(
        $file['name'], 
        $_POST['path_id'], 
        $_POST['point_index']
    );
    
    // Create Pano directory if it doesn't exist
    $uploadDir = 'Pano/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Save to database
    $panoramaData = [
        'path_id' => $_POST['path_id'],
        'point_index' => (int)$_POST['point_index'],
        'point_x' => (float)$_POST['point_x'],
        'point_y' => (float)$_POST['point_y'],
        'floor_number' => (int)$_POST['floor_number'],
        'image_filename' => $filename,
        'original_filename' => $file['name'],
        'title' => $_POST['title'] ?? null,
        'description' => $_POST['description'] ?? null,
        'file_size' => $file['size'],
        'mime_type' => $file['type']
    ];
    
    $panoramaId = savePanoramaImage($connect, $panoramaData);
    
    if (!$panoramaId) {
        // Remove uploaded file if database save failed
        unlink($uploadPath);
        throw new Exception('Failed to save panorama to database');
    }
    
    // Update floor graph JSON
    updateFloorGraphWithPanoramas($connect, (int)$_POST['floor_number']);
    
    // Generate QR code automatically (like office system)
    generatePanoramaQRCode($_POST['path_id'], (int)$_POST['point_index'], (int)$_POST['floor_number']);
    
    echo json_encode([
        'success' => true,
        'panorama_id' => $panoramaId,
        'filename' => $filename,
        'message' => 'Panorama uploaded successfully'
    ]);
}

/**
 * Handle panorama information update (without file upload)
 */
function handlePanoramaUpdate() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    // Get existing panorama
    $existing = getPanoramaByPoint(
        $connect, 
        $_POST['path_id'], 
        (int)$_POST['point_index'], 
        (int)$_POST['floor_number']
    );
    
    if (!$existing) {
        throw new Exception('Panorama not found');
    }
    
    // Update only title and description
    $updateData = [
        'path_id' => $_POST['path_id'],
        'point_index' => (int)$_POST['point_index'],
        'floor_number' => (int)$_POST['floor_number'],
        'image_filename' => $existing['image_filename'], // Keep existing filename
        'title' => $_POST['title'] ?? $existing['title'],
        'description' => $_POST['description'] ?? $existing['description']
    ];
    
    $result = savePanoramaImage($connect, $updateData);
    
    if (!$result) {
        throw new Exception('Failed to update panorama information');
    }
    
    // Update floor graph JSON
    updateFloorGraphWithPanoramas($connect, (int)$_POST['floor_number']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Panorama information updated successfully'
    ]);
}

/**
 * Handle panorama deletion
 */
function handlePanoramaDelete() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    $filename = deletePanoramaImage(
        $connect, 
        $_POST['path_id'], 
        (int)$_POST['point_index'], 
        (int)$_POST['floor_number']
    );
    
    if (!$filename) {
        throw new Exception('Failed to delete panorama from database');
    }
    
    // Remove file from filesystem
    $filePath = 'Pano/' . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Update floor graph JSON
    updateFloorGraphWithPanoramas($connect, (int)$_POST['floor_number']);
    
    // Delete QR code automatically (like office system)
    deletePanoramaQRCode($_POST['path_id'], (int)$_POST['point_index'], (int)$_POST['floor_number']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Panorama deleted successfully'
    ]);
}

/**
 * Get panorama information for a specific point
 */
function handleGetPanorama() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_GET[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    $panorama = getPanoramaByPoint(
        $connect, 
        $_GET['path_id'], 
        (int)$_GET['point_index'], 
        (int)$_GET['floor_number']
    );
    
    echo json_encode([
        'success' => true,
        'panorama' => $panorama
    ]);
}

/**
 * List all panoramas for a floor
 */
function handleListPanoramas() {
    global $connect;
    
    $floorNumber = isset($_GET['floor_number']) ? (int)$_GET['floor_number'] : null;
    
    try {
        // Try to get panoramas with status information
        $sql = "SELECT *, 
                COALESCE(status, 'active') as status,
                COALESCE(is_active, 1) as is_active
                FROM panorama_image";
        $params = [];
        
        if ($floorNumber !== null) {
            $sql .= " WHERE floor_number = ?";
            $params[] = $floorNumber;
        }
        
        $sql .= " ORDER BY uploaded_at DESC";
        
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        $panoramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normalize status and is_active fields for each panorama
        foreach ($panoramas as &$panorama) {
            // Ensure we have consistent is_active and status fields
            if (isset($panorama['is_active']) && $panorama['is_active'] !== null) {
                // Use is_active as the primary field
                $panorama['is_active'] = (int)$panorama['is_active'];
                $panorama['status'] = $panorama['is_active'] ? 'active' : 'inactive';
            } elseif (isset($panorama['status']) && $panorama['status'] !== null) {
                // Fallback to status field
                $panorama['is_active'] = ($panorama['status'] === 'active') ? 1 : 0;
            } else {
                // Default to active
                $panorama['is_active'] = 1;
                $panorama['status'] = 'active';
            }
        }
        
        echo json_encode([
            'success' => true,
            'panoramas' => $panoramas,
            'count' => count($panoramas)
        ]);
        
    } catch (PDOException $e) {
        // Fallback to basic query if status columns don't exist
        $panoramas = getAllPanoramas($connect, $floorNumber);
        
        // Set default is_active and status for all panoramas
        foreach ($panoramas as &$panorama) {
            $panorama['is_active'] = 1; // Default to active
            $panorama['status'] = 'active';
        }
        
        echo json_encode([
            'success' => true,
            'panoramas' => $panoramas,
            'count' => count($panoramas),
            'note' => 'Status columns not found, assuming all active'
        ]);
    }
}

/**
 * Get panorama data for a specific floor graph point (for mobile explore.php)
 */
function handleGetPanoramaForPoint() {
    global $connect;
    
    $pathId = $_GET['path_id'] ?? '';
    $pointIndex = $_GET['point_index'] ?? '';
    $floorNumber = $_GET['floor_number'] ?? 1;
    
    if (empty($pathId) || $pointIndex === '') {
        echo json_encode([
            'success' => false,
            'error' => 'path_id and point_index are required'
        ]);
        return;
    }
    
    $panorama = getPanoramaByPoint($connect, $pathId, (int)$pointIndex, (int)$floorNumber);
    
    if ($panorama) {
        echo json_encode([
            'success' => true,
            'panorama' => $panorama,
            'image_url' => 'Pano/' . $panorama['image_filename']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No panorama found for the specified point'
        ]);
    }
}

/**
 * Handle saving hotspots for a panorama point
 */
function handleSaveHotspots() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number', 'hotspots'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    $pathId = $_POST['path_id'];
    $pointIndex = (int)$_POST['point_index'];
    $floorNumber = (int)$_POST['floor_number'];
    $hotspots = json_decode($_POST['hotspots'], true);
    
    if ($hotspots === null) {
        throw new Exception('Invalid hotspots data');
    }
    
    // First, delete existing hotspots for this point
    $deleteStmt = $connect->prepare("DELETE FROM panorama_hotspots WHERE path_id = ? AND point_index = ? AND floor_number = ?");
    $deleteStmt->execute([$pathId, $pointIndex, $floorNumber]);
    
    // First check if transform columns exist, if not add them
    try {
        // Check if rotation and scale columns exist
        $checkColumns = $connect->query("SHOW COLUMNS FROM panorama_hotspots LIKE 'rotation_%'");
        $rotationExists = $checkColumns->rowCount() > 0;
        
        if (!$rotationExists) {
            // Add transform columns to the database
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN rotation_x DECIMAL(10,6) DEFAULT 0.0");
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN rotation_y DECIMAL(10,6) DEFAULT 0.0"); 
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN rotation_z DECIMAL(10,6) DEFAULT 0.0");
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN scale_x DECIMAL(10,6) DEFAULT 1.0");
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN scale_y DECIMAL(10,6) DEFAULT 1.0");
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN scale_z DECIMAL(10,6) DEFAULT 1.0");
        }
    } catch (Exception $e) {
        // Columns might already exist, continue
    }

    // Check and add missing columns individually
    try {
        // Check for video_hotspot_id column
        $checkVideoId = $connect->query("SHOW COLUMNS FROM panorama_hotspots LIKE 'video_hotspot_id'");
        if ($checkVideoId->rowCount() == 0) {
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN video_hotspot_id INT NULL");
        }
        
        // Check for video_hotspot_path column
        $checkVideoPath = $connect->query("SHOW COLUMNS FROM panorama_hotspots LIKE 'video_hotspot_path'");
        if ($checkVideoPath->rowCount() == 0) {
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN video_hotspot_path VARCHAR(500) NULL");
        }
        
        // Check for video_hotspot_name column
        $checkVideoName = $connect->query("SHOW COLUMNS FROM panorama_hotspots LIKE 'video_hotspot_name'");
        if ($checkVideoName->rowCount() == 0) {
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN video_hotspot_name VARCHAR(255) NULL");
        }
        
        // Check for animated_icon_path column
        $checkAnimatedPath = $connect->query("SHOW COLUMNS FROM panorama_hotspots LIKE 'animated_icon_path'");
        if ($checkAnimatedPath->rowCount() == 0) {
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN animated_icon_path VARCHAR(500) NULL");
        }
        
        // Check for animated_icon_name column
        $checkAnimatedName = $connect->query("SHOW COLUMNS FROM panorama_hotspots LIKE 'animated_icon_name'");
        if ($checkAnimatedName->rowCount() == 0) {
            $connect->exec("ALTER TABLE panorama_hotspots ADD COLUMN animated_icon_name VARCHAR(255) NULL");
        }
        
    } catch (Exception $e) {
        error_log("Column creation error: " . $e->getMessage());
        // Continue anyway, might be permission issue
    }

    // Insert new hotspots with navigation, transform, and asset support
    $insertStmt = $connect->prepare("
        INSERT INTO panorama_hotspots 
        (path_id, point_index, floor_number, hotspot_id, position_x, position_y, position_z, 
         title, description, hotspot_type, target_url, icon_class, animated_icon_id,
         link_type, link_path_id, link_point_index, link_floor_number, 
         navigation_angle, is_navigation, 
         rotation_x, rotation_y, rotation_z, scale_x, scale_y, scale_z,
         video_hotspot_id, video_hotspot_path, video_hotspot_name,
         animated_icon_path, animated_icon_name, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $savedCount = 0;
    foreach ($hotspots as $hotspot) {
        // Extract transform values
        $rotationX = isset($hotspot['rotation']) ? ($hotspot['rotation']['x'] ?? 0) : 0;
        $rotationY = isset($hotspot['rotation']) ? ($hotspot['rotation']['y'] ?? 0) : 0;
        $rotationZ = isset($hotspot['rotation']) ? ($hotspot['rotation']['z'] ?? 0) : 0;
        $scaleX = isset($hotspot['scale']) ? ($hotspot['scale']['x'] ?? 1) : 1;
        $scaleY = isset($hotspot['scale']) ? ($hotspot['scale']['y'] ?? 1) : 1;
        $scaleZ = isset($hotspot['scale']) ? ($hotspot['scale']['z'] ?? 1) : 1;
        
        // Log transform data for debugging
        error_log("Saving hotspot {$hotspot['id']}: rotation=($rotationX,$rotationY,$rotationZ), scale=($scaleX,$scaleY,$scaleZ)");
        
        // Debug navigation data
        if (isset($hotspot['is_navigation']) && $hotspot['is_navigation']) {
            error_log("🎯 Navigation hotspot {$hotspot['id']} data: link_path_id=" . ($hotspot['link_path_id'] ?? 'null') . 
                     ", link_point_index=" . ($hotspot['link_point_index'] ?? 'null') . 
                     ", link_floor_number=" . ($hotspot['link_floor_number'] ?? 'null'));
        }
        
        // Auto-fix navigation hotspots ONLY when target data is truly missing
        if (($hotspot['type'] ?? '') === 'navigation' || (isset($hotspot['is_navigation']) && $hotspot['is_navigation'])) {
            $linkPathId = $hotspot['linkPathId'] ?? $hotspot['link_path_id'] ?? null;
            $linkPointIndex = $hotspot['linkPointIndex'] ?? $hotspot['link_point_index'] ?? null;
            $linkFloorNumber = $hotspot['linkFloorNumber'] ?? $hotspot['link_floor_number'] ?? $floorNumber;
            
            // Only auto-fix if BOTH path_id AND point_index are missing/empty
            // This prevents overwriting valid user selections
            if (empty($linkPathId) && empty($linkPointIndex)) {
                // Smart target assignment based on current location (emergency fallback only)
                if ($pathId == 'path1' && $pointIndex == 5) {
                    $linkPathId = 'path1';
                    $linkPointIndex = 8;
                } elseif ($pathId == 'path1' && $pointIndex == 8) {
                    $linkPathId = 'path1';
                    $linkPointIndex = 5;
                } elseif ($pathId == 'path1' && $pointIndex == 7) {
                    $linkPathId = 'path2';
                    $linkPointIndex = 0;
                } elseif ($pathId == 'path2' && $pointIndex == 0) {
                    $linkPathId = 'path1';
                    $linkPointIndex = 7;
                } else {
                    // Default fallback
                    $linkPathId = $pathId;
                    $linkPointIndex = ($pointIndex == 5) ? 8 : 5;
                }
                
                error_log("🔧 Auto-fixed missing navigation target for hotspot {$hotspot['id']}: {$pathId} point {$pointIndex} → {$linkPathId} point {$linkPointIndex}");
            } else {
                error_log("✅ Preserving user-selected navigation target for hotspot {$hotspot['id']}: → {$linkPathId} point {$linkPointIndex}");
            }
            
            // Ensure navigation fields are properly set for database
            $hotspot['linkPathId'] = $hotspot['link_path_id'] = $linkPathId;
            $hotspot['linkPointIndex'] = $hotspot['link_point_index'] = $linkPointIndex;
            $hotspot['linkFloorNumber'] = $hotspot['link_floor_number'] = $linkFloorNumber;
            $hotspot['linkType'] = $hotspot['link_type'] = 'panorama';
            $hotspot['isNavigation'] = $hotspot['is_navigation'] = true;
        }
        
        $result = $insertStmt->execute([
            $pathId,
            $pointIndex,
            $floorNumber,
            $hotspot['id'],
            $hotspot['position']['x'] ?? 0,
            $hotspot['position']['y'] ?? 0,
            $hotspot['position']['z'] ?? 0,
            $hotspot['title'] ?? '',
            $hotspot['description'] ?? '',
            $hotspot['type'] ?? 'info',
            $hotspot['target'] ?? '',
            $hotspot['icon'] ?? 'fas fa-info-circle',
            $hotspot['animated_icon_id'] ?? null,
            $hotspot['linkType'] ?? $hotspot['link_type'] ?? 'none',
            $hotspot['linkPathId'] ?? $hotspot['link_path_id'] ?? null,
            $hotspot['linkPointIndex'] ?? $hotspot['link_point_index'] ?? null,
            $hotspot['linkFloorNumber'] ?? $hotspot['link_floor_number'] ?? null,
            $hotspot['navigationAngle'] ?? $hotspot['navigation_angle'] ?? 0,
            isset($hotspot['isNavigation']) ? (bool)$hotspot['isNavigation'] : (isset($hotspot['is_navigation']) ? (bool)$hotspot['is_navigation'] : false),
            $rotationX,
            $rotationY,
            $rotationZ,
            $scaleX,
            $scaleY,
            $scaleZ,
            // Video hotspot fields
            $hotspot['video_hotspot_id'] ?? null,
            $hotspot['video_hotspot_path'] ?? null,
            $hotspot['video_hotspot_name'] ?? null,
            // Animated icon fields  
            $hotspot['animated_icon_path'] ?? null,
            $hotspot['animated_icon_name'] ?? null
        ]);
        
        if ($result) {
            $savedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Hotspots saved successfully with transform data',
        'count' => count($hotspots),
        'saved_count' => $savedCount,
        'transform_support' => true
    ]);
}

/**
 * Handle getting hotspots for a panorama point
 */
function handleGetHotspots() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_GET[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    $pathId = $_GET['path_id'];
    $pointIndex = (int)$_GET['point_index'];
    $floorNumber = (int)$_GET['floor_number'];
    
    $stmt = $connect->prepare("
        SELECT ph.hotspot_id as id, ph.position_x, ph.position_y, ph.position_z, 
               ph.title, ph.description, ph.hotspot_type as type, ph.target_url as target,
               ph.icon_class as icon, ph.created_at as created, ph.animated_icon_id,
               ph.link_type as linkType, ph.link_path_id as linkPathId, 
               ph.link_point_index as linkPointIndex, ph.link_floor_number as linkFloorNumber,
               ph.navigation_angle as navigationAngle, ph.is_navigation as isNavigation,
               ph.rotation_x, ph.rotation_y, ph.rotation_z, ph.scale_x, ph.scale_y, ph.scale_z,
               ph.video_hotspot_id, ph.video_hotspot_path, ph.video_hotspot_name,
               ph.animated_icon_path, ph.animated_icon_name,
               ai.icon_file_path as animated_icon_path_fallback, ai.icon_name as animated_icon_name_fallback
        FROM panorama_hotspots ph
        LEFT JOIN animated_hotspot_icons ai ON ph.animated_icon_id = ai.id AND ai.is_active = 1
        WHERE ph.path_id = ? AND ph.point_index = ? AND ph.floor_number = ?
        ORDER BY ph.created_at
    ");
    
    $stmt->execute([$pathId, $pointIndex, $floorNumber]);
    $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format positions and transforms as objects for JavaScript
    foreach ($hotspots as &$hotspot) {
        $hotspot['position'] = [
            'x' => (float)$hotspot['position_x'],
            'y' => (float)$hotspot['position_y'],
            'z' => (float)$hotspot['position_z']
        ];
        unset($hotspot['position_x'], $hotspot['position_y'], $hotspot['position_z']);
        
        // Add transform data if it exists and is not default
        if (isset($hotspot['rotation_x']) && 
            ($hotspot['rotation_x'] != 0 || $hotspot['rotation_y'] != 0 || $hotspot['rotation_z'] != 0)) {
            $hotspot['rotation'] = [
                'x' => (float)$hotspot['rotation_x'],
                'y' => (float)$hotspot['rotation_y'], 
                'z' => (float)$hotspot['rotation_z']
            ];
        }
        
        if (isset($hotspot['scale_x']) && 
            ($hotspot['scale_x'] != 1 || $hotspot['scale_y'] != 1 || $hotspot['scale_z'] != 1)) {
            $hotspot['scale'] = [
                'x' => (float)$hotspot['scale_x'],
                'y' => (float)$hotspot['scale_y'],
                'z' => (float)$hotspot['scale_z']
            ];
        }
        
        // Use fallback values for animated icon if main values are empty
        if (empty($hotspot['animated_icon_path']) && !empty($hotspot['animated_icon_path_fallback'])) {
            $hotspot['animated_icon_path'] = $hotspot['animated_icon_path_fallback'];
        }
        if (empty($hotspot['animated_icon_name']) && !empty($hotspot['animated_icon_name_fallback'])) {
            $hotspot['animated_icon_name'] = $hotspot['animated_icon_name_fallback'];
        }
        
        // Clean up individual transform columns and fallback columns
        unset($hotspot['rotation_x'], $hotspot['rotation_y'], $hotspot['rotation_z']);
        unset($hotspot['scale_x'], $hotspot['scale_y'], $hotspot['scale_z']);
        unset($hotspot['animated_icon_path_fallback'], $hotspot['animated_icon_name_fallback']);
    }
    
    echo json_encode([
        'success' => true,
        'hotspots' => $hotspots
    ]);
}

/**
 * Handle deleting a specific hotspot
 */
function handleDeleteHotspot() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number', 'hotspot_id'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    $stmt = $connect->prepare("
        DELETE FROM panorama_hotspots 
        WHERE path_id = ? AND point_index = ? AND floor_number = ? AND hotspot_id = ?
    ");
    
    $result = $stmt->execute([
        $_POST['path_id'],
        (int)$_POST['point_index'],
        (int)$_POST['floor_number'],
        $_POST['hotspot_id']
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Hotspot deleted successfully'
        ]);
    } else {
        throw new Exception('Hotspot not found or could not be deleted');
    }
}

/**
 * Get linkable panorama points for hotspot navigation
 */
function handleGetLinkablePanoramas() {
    global $connect;
    
    $currentPathId = $_GET['current_path_id'] ?? '';
    $currentPointIndex = $_GET['current_point_index'] ?? '';
    $currentFloor = $_GET['current_floor'] ?? 1;
    
    try {
        $stmt = $connect->prepare("
            SELECT DISTINCT 
                pi.path_id, 
                pi.point_index, 
                pi.floor_number,
                pi.title,
                pi.description,
                pi.image_filename,
                pi.created_at
            FROM panorama_image pi
            WHERE pi.is_active = 1 
            AND NOT (pi.path_id = ? AND pi.point_index = ? AND pi.floor_number = ?)
            ORDER BY pi.floor_number ASC, pi.path_id ASC, pi.point_index ASC
        ");
        
        $stmt->execute([$currentPathId, $currentPointIndex, $currentFloor]);
        $panoramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enhanced panorama data with better titles for UI display
        $enhancedPanoramas = [];
        foreach ($panoramas as $pano) {
            // Create a more descriptive display title
            $displayTitle = '';
            if (!empty($pano['title'])) {
                $displayTitle = $pano['title'];
            } else {
                $displayTitle = "Point {$pano['point_index']} - {$pano['path_id']}";
            }
            
            // Add location context
            $locationInfo = "Floor {$pano['floor_number']} | {$pano['path_id']} Point {$pano['point_index']}";
            
            $enhancedPano = [
                'path_id' => $pano['path_id'],
                'point_index' => (int)$pano['point_index'],
                'floor_number' => (int)$pano['floor_number'],
                'title' => $pano['title'],
                'description' => $pano['description'],
                'image_filename' => $pano['image_filename'],
                'display_title' => $displayTitle,
                'location_info' => $locationInfo,
                'full_display' => "{$displayTitle} ({$locationInfo})",
                'created_at' => $pano['created_at']
            ];
            
            $enhancedPanoramas[] = $enhancedPano;
        }
        
        // Group by floor for easier UI handling
        $groupedPanoramas = [];
        foreach ($enhancedPanoramas as $pano) {
            $floorKey = "Floor " . $pano['floor_number'];
            if (!isset($groupedPanoramas[$floorKey])) {
                $groupedPanoramas[$floorKey] = [];
            }
            $groupedPanoramas[$floorKey][] = $pano;
        }
        
        echo json_encode([
            'success' => true,
            'panoramas' => $enhancedPanoramas,
            'grouped' => $groupedPanoramas,
            'count' => count($enhancedPanoramas),
            'message' => 'Available panorama navigation targets loaded'
        ]);
        
    } catch (PDOException $e) {
        throw new Exception('Error fetching linkable panoramas: ' . $e->getMessage());
    }
}

/**
 * Get all active panoramas for navigation selection
 */
function handleGetAllActivePanoramas() {
    global $connect;
    
    try {
        // Debug: Check what we're working with
        $debugInfo = [];
        
        // Check if table exists
        $tableExists = $connect->query("SHOW TABLES LIKE 'panorama_image'")->rowCount() > 0;
        if (!$tableExists) {
            throw new Exception('Table panorama_image does not exist');
        }
        
        // Get column information
        $columns = $connect->query("SHOW COLUMNS FROM panorama_image")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Check if is_active column exists
        $hasIsActive = in_array('is_active', $columnNames);
        
        // Build dynamic query based on available columns
        $selectColumns = [
            'id', 'path_id', 'point_index', 'floor_number', 
            'title', 'description', 'image_filename', 'created_at'
        ];
        
        // Filter to only existing columns
        $existingColumns = array_intersect($selectColumns, $columnNames);
        $selectClause = implode(', ', array_map(function($col) { return "pi.$col"; }, $existingColumns));
        
        // Build WHERE clause
        $whereClause = $hasIsActive ? "WHERE pi.is_active = 1" : "";
        
        // If no is_active column, maybe check for other status indicators
        if (!$hasIsActive) {
            if (in_array('status', $columnNames)) {
                $whereClause = "WHERE (pi.status IS NULL OR pi.status != 'deleted')";
            } else {
                $whereClause = ""; // Get all records
            }
        }
        
        $query = "
            SELECT DISTINCT $selectClause
            FROM panorama_image pi
            $whereClause 
            ORDER BY pi.floor_number ASC, pi.path_id ASC, pi.point_index ASC
        ";
        
        $stmt = $connect->prepare($query);
        $stmt->execute();
        $panoramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debugInfo = [
            'table_exists' => $tableExists,
            'available_columns' => $columnNames,
            'has_is_active' => $hasIsActive,
            'query_used' => $query,
            'raw_count' => count($panoramas)
        ];
        
        // Enhanced panorama data for the navigation dialog
        $enhancedPanoramas = [];
        foreach ($panoramas as $pano) {
            // Create a descriptive display title
            $displayTitle = '';
            if (!empty($pano['title'])) {
                $displayTitle = $pano['title'];
            } else {
                $displayTitle = "Point " . ($pano['point_index'] ?? 'Unknown');
            }
            
            $enhancedPano = [
                'id' => isset($pano['id']) ? (int)$pano['id'] : null,
                'path_id' => $pano['path_id'] ?? 'unknown',
                'point_index' => isset($pano['point_index']) ? (int)$pano['point_index'] : 0,
                'floor_number' => isset($pano['floor_number']) ? (int)$pano['floor_number'] : 1,
                'title' => $pano['title'] ?? null,
                'description' => $pano['description'] ?? null,
                'image_filename' => $pano['image_filename'] ?? null,
                'status' => 'Active',
                'display_title' => $displayTitle,
                'created_at' => $pano['created_at'] ?? null
            ];
            
            $enhancedPanoramas[] = $enhancedPano;
        }
        
        echo json_encode([
            'success' => true,
            'panoramas' => $enhancedPanoramas,
            'count' => count($enhancedPanoramas),
            'debug' => $debugInfo,
            'message' => count($enhancedPanoramas) > 0 
                ? 'All active panoramas loaded for navigation' 
                : 'No panoramas found - check if any are uploaded'
        ]);
        
    } catch (PDOException $e) {
        throw new Exception('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        throw new Exception('Error fetching active panoramas: ' . $e->getMessage());
    }
}

/**
 * Validate if a hotspot link target exists
 */
function handleValidateHotspotLink() {
    global $connect;
    
    $requiredParams = ['link_path_id', 'link_point_index', 'link_floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_GET[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    try {
        $panorama = getPanoramaByPoint(
            $connect, 
            $_GET['link_path_id'], 
            (int)$_GET['link_point_index'], 
            (int)$_GET['link_floor_number']
        );
        
        if ($panorama) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'panorama' => [
                    'title' => $panorama['title'],
                    'description' => $panorama['description'],
                    'image' => $panorama['image_filename']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'valid' => false,
                'message' => 'No panorama found at specified location'
            ]);
        }
        
    } catch (Exception $e) {
        throw new Exception('Error validating hotspot link: ' . $e->getMessage());
    }
}

/**
 * Generate QR Code for Panorama (like office system)
 */
function generatePanoramaQRCode($pathId, $pointIndex, $floorNumber) {
    global $connect;
    
    try {
        require_once __DIR__ . '/phpqrcode/qrlib.php';
        
        // Get base URL for network accessibility (matches office QR approach)
        $baseUrl = getPanoramaBaseUrl();
        $qrDir = __DIR__ . '/qrcodes/';
        
        // Create qrcodes directory if it doesn't exist
        if (!file_exists($qrDir)) {
            mkdir($qrDir, 0777, true);
        }
        
        // Generate QR data URL - redirect to explore page with panorama highlight (matches office system with from_qr flag)
        $qrData = $baseUrl . "explore.php?scanned_panorama=path_id:" . $pathId . "_point:" . $pointIndex . "_floor:" . $floorNumber . "&from_qr=1";
        
        // Create sanitized filename
        $sanitizedName = "panorama_floor{$floorNumber}_{$pathId}_point{$pointIndex}";
        $filename = $qrDir . $sanitizedName . ".png";
        
        // Generate QR code PNG file
        \QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);
        $qrImage = basename($filename);
        
        // Insert/Update QR code info in database
        $check = $connect->prepare("SELECT id FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
        $check->execute([$pathId, $pointIndex, $floorNumber]);
        $existingQR = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($existingQR) {
            // Update existing QR record
            $updateStmt = $connect->prepare("UPDATE panorama_qrcodes SET qr_filename = ?, mobile_url = ?, updated_at = CURRENT_TIMESTAMP WHERE path_id = ? AND point_index = ? AND floor_number = ?");
            $updateStmt->execute([$qrImage, $qrData, $pathId, $pointIndex, $floorNumber]);
        } else {
            // Insert new QR record
            $insertStmt = $connect->prepare("INSERT INTO panorama_qrcodes (path_id, point_index, floor_number, qr_filename, mobile_url) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$pathId, $pointIndex, $floorNumber, $qrImage, $qrData]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("QR generation failed for panorama {$pathId}_{$pointIndex}_{$floorNumber}: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete QR Code for Panorama (like office system)
 */
function deletePanoramaQRCode($pathId, $pointIndex, $floorNumber) {
    global $connect;
    
    try {
        // Get existing QR record
        $stmt = $connect->prepare("SELECT qr_filename FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
        $stmt->execute([$pathId, $pointIndex, $floorNumber]);
        $qrRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($qrRecord) {
            // Delete QR image file
            $qrPath = __DIR__ . '/qrcodes/' . $qrRecord['qr_filename'];
            if (file_exists($qrPath)) {
                unlink($qrPath);
            }
            
            // Delete QR record from database
            $deleteStmt = $connect->prepare("DELETE FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
            $deleteStmt->execute([$pathId, $pointIndex, $floorNumber]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("QR deletion failed for panorama {$pathId}_{$pointIndex}_{$floorNumber}: " . $e->getMessage());
        return false;
    }
}

/**
 * Get panorama status for a specific point
 */
function handleGetPanoramaStatus() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_GET[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    $pathId = $_GET['path_id'];
    $pointIndex = (int)$_GET['point_index'];
    $floorNumber = (int)$_GET['floor_number'];
    
    // Check if status column exists in panorama_image table
    try {
        $stmt = $connect->prepare("
            SELECT status, is_active 
            FROM panorama_image 
            WHERE path_id = ? AND point_index = ? AND floor_number = ?
        ");
        $stmt->execute([$pathId, $pointIndex, $floorNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Return both status and is_active fields, prioritizing is_active
            $isActive = 1; // default to active
            $status = 'active';
            
            if (isset($result['is_active']) && $result['is_active'] !== null) {
                $isActive = (int)$result['is_active'];
                $status = $isActive ? 'active' : 'inactive';
            } elseif (isset($result['status']) && $result['status'] !== null) {
                // Fallback to status field
                $status = $result['status'];
                $isActive = ($status === 'active') ? 1 : 0;
            }
            
            echo json_encode([
                'success' => true,
                'is_active' => $isActive,
                'status' => $status,
                'exists' => true
            ]);
        } else {
            // No panorama found, return default active status
            echo json_encode([
                'success' => true,
                'is_active' => 1,
                'status' => 'active',
                'exists' => false
            ]);
        }
        
    } catch (PDOException $e) {
        // If columns don't exist, add them
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            try {
                // Add status columns if they don't exist
                $connect->exec("ALTER TABLE panorama_image ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
                $connect->exec("ALTER TABLE panorama_image ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                
                // Return default active status
                echo json_encode([
                    'success' => true,
                    'status' => 'active',
                    'exists' => false,
                    'columns_added' => true
                ]);
            } catch (Exception $e2) {
                throw new Exception('Error adding status columns: ' . $e2->getMessage());
            }
        } else {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
}

/**
 * Update panorama status for a specific point
 */
function handleUpdatePanoramaStatus() {
    global $connect;
    
    $requiredParams = ['path_id', 'point_index', 'floor_number'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param])) {
            throw new Exception("Missing required parameter: $param");
        }
    }
    
    $pathId = $_POST['path_id'];
    $pointIndex = (int)$_POST['point_index'];
    $floorNumber = (int)$_POST['floor_number'];
    
    // Prioritize is_active field if provided
    if (isset($_POST['is_active'])) {
        $isActive = (int)$_POST['is_active']; // Should be 1 or 0
        $status = $isActive ? 'active' : 'inactive';
    } elseif (isset($_POST['status'])) {
        $status = $_POST['status']; // 'active' or 'inactive'
        $isActive = ($status === 'active') ? 1 : 0;
    } else {
        throw new Exception("Either 'is_active' or 'status' parameter is required");
    }
    
    try {
        // First check if the panorama exists
        $checkStmt = $connect->prepare("
            SELECT id FROM panorama_image 
            WHERE path_id = ? AND point_index = ? AND floor_number = ?
        ");
        $checkStmt->execute([$pathId, $pointIndex, $floorNumber]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            // Update existing panorama, prioritizing is_active field
            $updateStmt = $connect->prepare("
                UPDATE panorama_image 
                SET is_active = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE path_id = ? AND point_index = ? AND floor_number = ?
            ");
            $result = $updateStmt->execute([$isActive, $status, $pathId, $pointIndex, $floorNumber]);
            
            if ($result) {
                // Update floor graph JSON to reflect status change
                updateFloorGraphWithPanoramas($connect, $floorNumber);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Panorama status updated successfully',
                    'status' => $status
                ]);
            } else {
                throw new Exception('Failed to update panorama status');
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Panorama not found - cannot update status'
            ]);
        }
        
    } catch (PDOException $e) {
        // If status columns don't exist, add them first
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            try {
                $connect->exec("ALTER TABLE panorama_image ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
                $connect->exec("ALTER TABLE panorama_image ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                
                // Try the update again
                handleUpdatePanoramaStatus();
                return;
            } catch (Exception $e2) {
                throw new Exception('Error adding status columns: ' . $e2->getMessage());
            }
        } else {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
}

?>