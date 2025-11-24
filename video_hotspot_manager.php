<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

header('Content-Type: application/json');
include 'connect_db.php';

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'list':
            // Get all video files from uploads directory
            $videoDir = 'uploads/videos/';
            $videos = [];
            
            if (is_dir($videoDir)) {
                $files = scandir($videoDir);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && preg_match('/\.(mp4|webm|ogg)$/i', $file)) {
                        $filePath = $videoDir . $file;
                        $videos[] = [
                            'id' => md5($file),
                            'name' => pathinfo($file, PATHINFO_FILENAME),
                            'url' => $filePath,
                            'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
                            'created' => date('Y-m-d H:i:s', filemtime($filePath))
                        ];
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'videos' => $videos
            ]);
            break;

        case 'upload':
            if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No video file uploaded or upload error');
            }

            $file = $_FILES['video'];
            $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only MP4, WebM, and OGG videos are allowed.');
            }

            // Create uploads directory if it doesn't exist
            $uploadDir = 'uploads/videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'video_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to save video file');
            }

            echo json_encode([
                'success' => true,
                'video' => [
                    'id' => md5($filename),
                    'name' => pathinfo($file['name'], PATHINFO_FILENAME),
                    'url' => $filepath,
                    'file_size' => filesize($filepath)
                ]
            ]);
            break;

        case 'delete':
            $videoId = $_POST['video_id'] ?? '';
            if (empty($videoId)) {
                throw new Exception('Video ID is required');
            }

            // Find video file by ID
            $videoDir = 'uploads/videos/';
            $files = scandir($videoDir);
            $deleted = false;
            
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && md5($file) === $videoId) {
                    $filePath = $videoDir . $file;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                        $deleted = true;
                        break;
                    }
                }
            }

            if (!$deleted) {
                throw new Exception('Video file not found');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Video deleted successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>