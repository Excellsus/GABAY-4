<?php
/**
 * Panorama QR Code Management API
 * Handles QR code generation, management and download for panorama points
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect_db.php';
require_once 'phpqrcode/qrlib.php'; // Make sure you have phpqrcode library

header('Content-Type: application/json');

// Function to sanitize filename
function sanitizeFilename($string) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $string);
}

// Function to get dynamic base URL with enhanced IP detection
function getPanoramaQRBaseUrl() {
    $baseUrl = '';
    
    // Try multiple methods to get the server IP/host
    $serverHost = '';
    
    // Method 1: HTTP_HOST (most reliable for web requests)
    if (!empty($_SERVER['HTTP_HOST'])) {
        $serverHost = $_SERVER['HTTP_HOST'];
    }
    // Method 2: SERVER_NAME as fallback
    elseif (!empty($_SERVER['SERVER_NAME'])) {
        $serverHost = $_SERVER['SERVER_NAME'];
    }
    // Method 3: Try to detect IP address from server variables
    elseif (!empty($_SERVER['SERVER_ADDR'])) {
        $serverHost = $_SERVER['SERVER_ADDR'];
    }
    // Method 4: Try LOCAL_ADDR
    elseif (!empty($_SERVER['LOCAL_ADDR'])) {
        $serverHost = $_SERVER['LOCAL_ADDR'];
    }
    
    if (!empty($serverHost)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        // dirname($_SERVER['SCRIPT_NAME']) gives the directory of this script (e.g., /FinalDev)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        // Ensure we end up with a trailing slash and point to the mobileScreen folder
        $baseUrl = $protocol . '://' . $serverHost . $scriptDir . '/mobileScreen/';
        // Normalize double slashes (except after http(s):)
        $baseUrl = preg_replace('#([^:])/+#', '$1/', $baseUrl);
    } else {
        // Enhanced fallback: Try to detect IP using system commands
        $detectedIP = '';
        
        // Try ipconfig on Windows
        if (stripos(PHP_OS, 'WIN') === 0) {
            $ipOutput = shell_exec('ipconfig | findstr /i "IPv4"');
            if ($ipOutput) {
                preg_match('/\d+\.\d+\.\d+\.\d+/', $ipOutput, $matches);
                if (!empty($matches[0]) && $matches[0] !== '127.0.0.1') {
                    $detectedIP = $matches[0];
                }
            }
        } else {
            // Try hostname -I on Linux
            $ipOutput = shell_exec('hostname -I');
            if ($ipOutput) {
                $ips = explode(' ', trim($ipOutput));
                foreach ($ips as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ip !== '127.0.0.1') {
                        $detectedIP = $ip;
                        break;
                    }
                }
            }
        }
        
        if (!empty($detectedIP)) {
            $baseUrl = "http://" . $detectedIP . "/gabay/mobileScreen/";
        } else {
            // Final fallback: production domain
            $baseUrl = "https://localhost/gabay/mobileScreen/";
        }
    }
    
    return $baseUrl;
}

// Function to create a safe filename from panorama info (matches office system)
function sanitizePanoramaFilename($string) {
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

// Function to generate panorama QR code (EXACTLY matches office QR system)
function generatePanoramaQR($pathId, $pointIndex, $floor, $title = '') {
    global $connect;
    
    try {
        // Construct the URL for the QR code. Add a `from_qr=1` flag so the server can
        // distinguish true QR scans from normal navigation (clicks or manual refreshes).
        $baseUrl = getPanoramaQRBaseUrl();
        $qrData = $baseUrl . "explore.php?scanned_panorama=path_id:" . urlencode($pathId) . "_point:" . urlencode($pointIndex) . "_floor:" . urlencode($floor) . "&from_qr=1";
        
        // Sanitize the panorama identifier for the filename and create unique identifier
        $panoramaIdentifier = $pathId . "_point" . $pointIndex . "_floor" . $floor;
        $sanitizedName = sanitizePanoramaFilename($panoramaIdentifier);
        $qrDir = 'qrcodes/';
        if (!file_exists($qrDir)) {
            mkdir($qrDir, 0777, true);
        }
        $filename = $qrDir . $sanitizedName . ".png";
        
        // Generate QR code image (this will overwrite existing images if any)
        QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);
        
        // Prepare QR info for the database
        $qrImage = basename($filename); // e.g., path1_point5_floor1.png
        
        // Check if an entry for this panorama already exists (matches office system exactly)
        $check = $connect->prepare("SELECT id FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
        $check->execute([$pathId, $pointIndex, $floor]);
        $existingQrInfo = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($existingQrInfo) {
            // Update existing entry (matches office system)
            $updateStmt = $connect->prepare("UPDATE panorama_qrcodes SET mobile_url = ?, qr_filename = ?, updated_at = CURRENT_TIMESTAMP WHERE path_id = ? AND point_index = ? AND floor_number = ?");
            $updateStmt->execute([$qrData, $qrImage, $pathId, $pointIndex, $floor]);
        } else {
            // Insert new entry (matches office system)
            $insertStmt = $connect->prepare("INSERT INTO panorama_qrcodes (path_id, point_index, floor_number, mobile_url, qr_filename) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$pathId, $pointIndex, $floor, $qrData, $qrImage]);
        }
        
        return [
            'success' => true,
            'filename' => $qrImage,
            'url' => $qrData,
            'filepath' => $filename
        ];
        
    } catch (Exception $e) {
        error_log("QR Generation Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate':
            $pathId = $_POST['path_id'] ?? '';
            $pointIndex = $_POST['point_index'] ?? '';
            $floor = $_POST['floor_number'] ?? '1';
            $title = $_POST['title'] ?? '';
            
            if (empty($pathId) || $pointIndex === '') {
                echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
                exit;
            }
            
            $result = generatePanoramaQR($pathId, $pointIndex, $floor, $title);
            echo json_encode($result);
            break;
            
        case 'delete':
            // Delete panorama QR code (matches office system pattern)
            $pathId = $_POST['path_id'] ?? '';
            $pointIndex = $_POST['point_index'] ?? '';
            $floor = $_POST['floor_number'] ?? '1';
            
            if (empty($pathId) || $pointIndex === '') {
                echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
                exit;
            }
            
            try {
                // Get QR filename before deleting from database (matches office system)
                $stmt = $connect->prepare("SELECT qr_filename FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
                $stmt->execute([$pathId, $pointIndex, $floor]);
                $qrRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($qrRecord && !empty($qrRecord['qr_filename'])) {
                    $qrImagePath = "qrcodes/" . $qrRecord['qr_filename'];
                    
                    // Delete file if it exists (matches office system)
                    if (file_exists($qrImagePath)) {
                        unlink($qrImagePath);
                    }
                    
                    // Delete from database (matches office system)
                    $deleteStmt = $connect->prepare("DELETE FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
                    $deleteStmt->execute([$pathId, $pointIndex, $floor]);
                    
                    echo json_encode(['success' => true, 'message' => 'Panorama QR code deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Panorama QR code not found']);
                }
                
            } catch (Exception $e) {
                error_log("Error in panorama QR delete: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Database error occurred while deleting panorama QR code']);
            }
            break;
            
        case 'regenerate_all':
            try {
                // Get all panorama points from database
                $stmt = $connect->query("
                    SELECT DISTINCT path_id, point_index, floor_number, title 
                    FROM panorama_image 
                    WHERE image_filename IS NOT NULL
                ");
                $panoramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $generated = 0;
                $errors = [];
                
                foreach ($panoramas as $pano) {
                    $result = generatePanoramaQR(
                        $pano['path_id'], 
                        $pano['point_index'], 
                        $pano['floor_number'], 
                        $pano['title']
                    );
                    
                    if ($result['success']) {
                        $generated++;
                    } else {
                        $errors[] = "Path {$pano['path_id']}, Point {$pano['point_index']}: " . $result['error'];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'generated' => $generated,
                    'errors' => $errors
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get':
            $pathId = $_GET['path_id'] ?? '';
            $pointIndex = $_GET['point_index'] ?? '';
            $floor = $_GET['floor_number'] ?? '1';
            
            try {
                $stmt = $connect->prepare("
                    SELECT * FROM panorama_qrcodes 
                    WHERE path_id = ? AND point_index = ? AND floor_number = ?
                ");
                $stmt->execute([$pathId, $pointIndex, $floor]);
                $qrData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'qr_data' => $qrData
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'download':
            // Download QR code based on panorama parameters (EXACTLY matches office system)
            $pathId = $_GET['path_id'] ?? '';
            $pointIndex = $_GET['point_index'] ?? '';
            $floorNumber = $_GET['floor_number'] ?? '';
            
            if (!$pathId || $pointIndex === '' || !$floorNumber) {
                echo "No panorama parameters provided.";
                break;
            }
            
            try {
                // Fetch the qr_filename from the panorama_qrcodes table (matches office system)
                $stmt = $connect->prepare("SELECT qr_filename FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
                $stmt->execute([$pathId, $pointIndex, $floorNumber]);
                $qrInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($qrInfo && !empty($qrInfo['qr_filename'])) {
                    $qrImageFilename = $qrInfo['qr_filename'];
                    $qrImagePath = "qrcodes/" . $qrImageFilename;
                    
                    // Check if the file exists (matches office system)
                    if (file_exists($qrImagePath)) {
                        // Set headers for download (matches office system)
                        header('Content-Type: image/png');
                        header('Content-Disposition: attachment; filename="' . basename($qrImageFilename) . '"'); // Use the actual filename for download
                        readfile($qrImagePath);
                        exit;
                    } else {
                        echo "QR code image file not found on server for Path: $pathId, Point: $pointIndex, Floor: $floorNumber (Expected: $qrImageFilename)";
                    }
                } else {
                    echo "QR code information not found in database for Path: $pathId, Point: $pointIndex, Floor: $floorNumber.";
                }
            } catch (PDOException $e) {
                error_log("Error in panorama_qr_api.php download: " . $e->getMessage());
                echo "Database error occurred while trying to download panorama QR code.";
            }
            break;
            
        case 'list_all':
            try {
                $stmt = $connect->query("
                    SELECT pq.*, pi.title as panorama_title, pi.image_filename
                    FROM panorama_qrcodes pq
                    LEFT JOIN panorama_image pi ON (
                        pq.path_id = pi.path_id AND 
                        pq.point_index = pi.point_index AND 
                        pq.floor_number = pi.floor_number
                    )
                    ORDER BY pq.floor_number, pq.path_id, pq.point_index
                ");
                $qrCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'qr_codes' => $qrCodes
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}
?>