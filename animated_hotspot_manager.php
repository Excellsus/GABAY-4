<?php
/**
 * Animated Hotspot Icons Manager API
 * Handles animated GIF uploads and management for panorama hotspots
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect_db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'upload':
            handleIconUpload();
            break;
            
        case 'list':
            handleListIcons();
            break;
            
        case 'update':
            handleUpdateIcon();
            break;
            
        case 'delete':
            handleDeleteIcon();
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
 * Handle animated icon upload
 */
function handleIconUpload() {
    global $connect;
    
    // Validate required parameters
    if (!isset($_POST['icon_name']) || empty(trim($_POST['icon_name']))) {
        throw new Exception('Icon name is required');
    }
    
    if (!isset($_FILES['icon_file']) || $_FILES['icon_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $iconName = trim($_POST['icon_name']);
    $iconDescription = $_POST['icon_description'] ?? '';
    $iconCategory = $_POST['icon_category'] ?? 'general';
    $file = $_FILES['icon_file'];
    
    // Validate file
    $validation = validateIconFile($file);
    if ($validation !== true) {
        throw new Exception($validation);
    }
    
    // Check if icon name already exists
    $checkStmt = $connect->prepare("SELECT id FROM animated_hotspot_icons WHERE icon_name = ?");
    $checkStmt->execute([$iconName]);
    if ($checkStmt->rowCount() > 0) {
        throw new Exception('Icon name already exists. Please choose a different name.');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'animated_hotspot_icons/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uniqueFilename = sanitizeFileName($iconName) . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $uniqueFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Insert into database
    $insertStmt = $connect->prepare("
        INSERT INTO animated_hotspot_icons 
        (icon_name, icon_description, icon_category, icon_file_path, icon_file_name, file_size, is_active, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, 1, 'admin')
    ");
    
    $result = $insertStmt->execute([
        $iconName,
        $iconDescription,
        $iconCategory,
        $uploadPath,
        $uniqueFilename,
        $file['size']
    ]);
    
    if ($result) {
        $iconId = $connect->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Icon uploaded successfully',
            'icon_id' => $iconId,
            'file_path' => $uploadPath
        ]);
    } else {
        // Clean up file if database insert fails
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        throw new Exception('Failed to save icon to database');
    }
}

/**
 * Handle listing icons
 */
function handleListIcons() {
    global $connect;
    
    $category = $_GET['category'] ?? 'all';
    
    $sql = "SELECT id, icon_name, icon_description, icon_category, icon_file_path, 
                   file_size, upload_date, is_active 
            FROM animated_hotspot_icons 
            WHERE is_active = 1";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND icon_category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY upload_date DESC";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $icons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'icons' => $icons
    ]);
}

/**
 * Handle updating icon
 */
function handleUpdateIcon() {
    global $connect;
    
    if (!isset($_POST['icon_id']) || !isset($_POST['icon_name'])) {
        throw new Exception('Icon ID and name are required');
    }
    
    $iconId = (int)$_POST['icon_id'];
    $iconName = trim($_POST['icon_name']);
    $iconDescription = $_POST['icon_description'] ?? '';
    $iconCategory = $_POST['icon_category'] ?? 'general';
    
    // Check if icon exists
    $checkStmt = $connect->prepare("SELECT id FROM animated_hotspot_icons WHERE id = ?");
    $checkStmt->execute([$iconId]);
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Icon not found');
    }
    
    // Check if name already exists for different icon
    $nameCheckStmt = $connect->prepare("SELECT id FROM animated_hotspot_icons WHERE icon_name = ? AND id != ?");
    $nameCheckStmt->execute([$iconName, $iconId]);
    if ($nameCheckStmt->rowCount() > 0) {
        throw new Exception('Icon name already exists. Please choose a different name.');
    }
    
    // Update icon
    $updateStmt = $connect->prepare("
        UPDATE animated_hotspot_icons 
        SET icon_name = ?, icon_description = ?, icon_category = ?
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$iconName, $iconDescription, $iconCategory, $iconId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Icon updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update icon');
    }
}

/**
 * Handle deleting icon
 */
function handleDeleteIcon() {
    global $connect;
    
    if (!isset($_POST['icon_id'])) {
        throw new Exception('Icon ID is required');
    }
    
    $iconId = (int)$_POST['icon_id'];
    
    // Get icon details first
    $selectStmt = $connect->prepare("SELECT icon_file_path FROM animated_hotspot_icons WHERE id = ?");
    $selectStmt->execute([$iconId]);
    $icon = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$icon) {
        throw new Exception('Icon not found');
    }
    
    // Delete from database
    $deleteStmt = $connect->prepare("DELETE FROM animated_hotspot_icons WHERE id = ?");
    $result = $deleteStmt->execute([$iconId]);
    
    if ($result) {
        // Delete file from filesystem
        if (file_exists($icon['icon_file_path'])) {
            unlink($icon['icon_file_path']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Icon deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete icon');
    }
}

/**
 * Validate uploaded icon file
 */
function validateIconFile($file) {
    // Check file size (max 2MB)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return 'File size must be less than 2MB';
    }
    
    // Check file type
    $allowedTypes = ['image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return 'Only GIF files are allowed';
    }
    
    // Check file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'gif') {
        return 'File must have .gif extension';
    }
    
    return true;
}

/**
 * Sanitize filename
 */
function sanitizeFileName($filename) {
    // Remove special characters and spaces
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    // Remove leading/trailing underscores
    $filename = trim($filename, '_');
    
    return strtolower($filename);
}
?>