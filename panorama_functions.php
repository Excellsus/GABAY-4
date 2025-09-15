<?php
/**
 * Panorama Image Database Helper Functions
 * For use with the panorama_image table
 */

// Include your database connection
// include 'connect_db.php';

/**
 * Get all panorama images for a specific floor
 * Used by explore.php to load panorama data for mobile view
 */
function getPanoramasByFloor($connect, $floor_number = 1) {
    try {
        $stmt = $connect->prepare("
            SELECT * FROM panorama_image 
            WHERE floor_number = ? AND is_active = 1 
            ORDER BY path_id, point_index
        ");
        $stmt->execute([$floor_number]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching panoramas for floor $floor_number: " . $e->getMessage());
        return [];
    }
}

/**
 * Get panorama for a specific point
 * Used by admin interface to check if a point has a panorama
 */
function getPanoramaByPoint($connect, $path_id, $point_index, $floor_number = 1) {
    try {
        $stmt = $connect->prepare("
            SELECT * FROM panorama_image 
            WHERE path_id = ? AND point_index = ? AND floor_number = ? AND is_active = 1
        ");
        $stmt->execute([$path_id, $point_index, $floor_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching panorama for point $path_id:$point_index: " . $e->getMessage());
        return null;
    }
}

/**
 * Insert or update panorama image
 * Used when admin uploads a new panorama or updates existing one
 */
function savePanoramaImage($connect, $data) {
    try {
        // Check if panorama already exists for this point
        $existing = getPanoramaByPoint($connect, $data['path_id'], $data['point_index'], $data['floor_number']);
        
        if ($existing) {
            // Update existing panorama
            $stmt = $connect->prepare("
                UPDATE panorama_image SET 
                    image_filename = ?, 
                    original_filename = ?, 
                    title = ?, 
                    description = ?, 
                    file_size = ?, 
                    mime_type = ?, 
                    updated_at = NOW() 
                WHERE path_id = ? AND point_index = ? AND floor_number = ?
            ");
            $result = $stmt->execute([
                $data['image_filename'],
                $data['original_filename'] ?? null,
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['file_size'] ?? null,
                $data['mime_type'] ?? null,
                $data['path_id'],
                $data['point_index'],
                $data['floor_number']
            ]);
            return $result ? $existing['id'] : false;
        } else {
            // Insert new panorama
            $stmt = $connect->prepare("
                INSERT INTO panorama_image 
                (path_id, point_index, point_x, point_y, floor_number, image_filename, original_filename, title, description, file_size, mime_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $data['path_id'],
                $data['point_index'],
                $data['point_x'],
                $data['point_y'],
                $data['floor_number'],
                $data['image_filename'],
                $data['original_filename'] ?? null,
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['file_size'] ?? null,
                $data['mime_type'] ?? null
            ]);
            return $result ? $connect->lastInsertId() : false;
        }
    } catch (PDOException $e) {
        error_log("Error saving panorama image: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete panorama image
 * Used when admin removes a panorama from a point
 */
function deletePanoramaImage($connect, $path_id, $point_index, $floor_number = 1) {
    try {
        // Get the image filename before deleting to remove the file
        $panorama = getPanoramaByPoint($connect, $path_id, $point_index, $floor_number);
        
        $stmt = $connect->prepare("
            DELETE FROM panorama_image 
            WHERE path_id = ? AND point_index = ? AND floor_number = ?
        ");
        $result = $stmt->execute([$path_id, $point_index, $floor_number]);
        
        // Return the filename so it can be deleted from filesystem
        return $result ? ($panorama['image_filename'] ?? null) : false;
    } catch (PDOException $e) {
        error_log("Error deleting panorama image: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all panoramas for admin management
 * Used in admin interface to show all uploaded panoramas
 */
function getAllPanoramas($connect, $floor_number = null) {
    try {
        $sql = "SELECT * FROM panorama_image";
        $params = [];
        
        if ($floor_number !== null) {
            $sql .= " WHERE floor_number = ?";
            $params[] = $floor_number;
        }
        
        $sql .= " ORDER BY uploaded_at DESC";
        
        $stmt = $connect->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching all panoramas: " . $e->getMessage());
        return [];
    }
}

/**
 * Update floor graph JSON with panorama data
 * Used to sync database panorama data with floor_graph.json
 */
function updateFloorGraphWithPanoramas($connect, $floor_number = 1) {
    try {
        $panoramas = getPanoramasByFloor($connect, $floor_number);
        $floorGraphFile = "floor_graph.json";
        
        if ($floor_number == 2) {
            $floorGraphFile = "floor_graph_2.json";
        } elseif ($floor_number == 3) {
            $floorGraphFile = "floor_graph_3.json";
        }
        
        if (!file_exists($floorGraphFile)) {
            error_log("Floor graph file not found: $floorGraphFile");
            return false;
        }
        
        $floorGraph = json_decode(file_get_contents($floorGraphFile), true);
        if (!$floorGraph) {
            error_log("Error decoding floor graph JSON");
            return false;
        }
        
        // Update panorama data in walkable paths
        foreach ($floorGraph['walkablePaths'] as &$path) {
            foreach ($path['pathPoints'] as $pointIndex => &$point) {
                // Find matching panorama in database
                $panorama = null;
                foreach ($panoramas as $pano) {
                    if ($pano['path_id'] === $path['id'] && $pano['point_index'] === $pointIndex) {
                        $panorama = $pano;
                        break;
                    }
                }
                
                if ($panorama) {
                    $point['isPano'] = true;
                    $point['panoImage'] = $panorama['image_filename'];
                    $point['panoTitle'] = $panorama['title'];
                    $point['panoDescription'] = $panorama['description'];
                } else {
                    // Remove panorama data if not in database
                    unset($point['panoImage']);
                    unset($point['panoTitle']);
                    unset($point['panoDescription']);
                }
            }
        }
        
        // Save updated floor graph
        $result = file_put_contents($floorGraphFile, json_encode($floorGraph, JSON_PRETTY_PRINT));
        return $result !== false;
        
    } catch (Exception $e) {
        error_log("Error updating floor graph with panoramas: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate unique filename for uploaded panorama
 */
function generatePanoramaFilename($originalFilename, $pathId, $pointIndex) {
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $timestamp = time();
    $random = uniqid();
    return "pano_{$pathId}_{$pointIndex}_{$timestamp}_{$random}.{$extension}";
}

/**
 * Validate uploaded panorama file
 */
function validatePanoramaFile($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return "Invalid file type. Only JPEG, PNG, and WebP images are allowed.";
    }
    
    if ($file['size'] > $maxSize) {
        return "File size too large. Maximum size is 10MB.";
    }
    
    return true; // Valid
}

?>