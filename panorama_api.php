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
    $panoramas = getAllPanoramas($connect, $floorNumber);
    
    echo json_encode([
        'success' => true,
        'panoramas' => $panoramas,
        'count' => count($panoramas)
    ]);
}

?>