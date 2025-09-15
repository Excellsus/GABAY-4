<?php
/**
 * Setup script for panorama_image table
 * Run this once to create the table and add sample data
 */

include 'connect_db.php';

try {
    // Create the panorama_image table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `panorama_image` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `path_id` varchar(50) NOT NULL COMMENT 'References the path ID from floor_graph.json (e.g., path1, path2)',
      `point_index` int(11) NOT NULL COMMENT 'Index of the point within the path (0-based)',
      `point_x` decimal(10,2) NOT NULL COMMENT 'X coordinate of the panorama point',
      `point_y` decimal(10,2) NOT NULL COMMENT 'Y coordinate of the panorama point',
      `floor_number` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Floor number (1, 2, 3, etc.)',
      `image_filename` varchar(255) NOT NULL COMMENT 'Filename of the panorama image (stored in Pano/ directory)',
      `original_filename` varchar(255) DEFAULT NULL COMMENT 'Original filename when uploaded',
      `title` varchar(100) DEFAULT NULL COMMENT 'Optional title/description for the panorama',
      `description` text DEFAULT NULL COMMENT 'Optional detailed description',
      `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
      `mime_type` varchar(50) DEFAULT NULL COMMENT 'MIME type of the image (image/jpeg, image/png, etc.)',
      `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this panorama is active/visible',
      `uploaded_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who uploaded (future use)',
      `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Upload timestamp',
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp',
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_path_point` (`path_id`, `point_index`, `floor_number`),
      KEY `idx_floor_number` (`floor_number`),
      KEY `idx_coordinates` (`point_x`, `point_y`),
      KEY `idx_path_id` (`path_id`),
      KEY `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores panorama images for point markers with isPano attribute'
    ";
    
    $connect->exec($createTableSQL);
    echo "✓ Table 'panorama_image' created successfully!\n<br>";
    
    // Check if we should add sample data
    $stmt = $connect->query("SELECT COUNT(*) FROM panorama_image");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "Adding sample data...\n<br>";
        
        // Add sample panorama records (you can modify these)
        $sampleData = [
            [
                'path_id' => 'path1',
                'point_index' => 0,
                'point_x' => 130.00,
                'point_y' => 110.00,
                'floor_number' => 1,
                'image_filename' => 'sample_entrance.jpg',
                'title' => 'Main Entrance',
                'description' => 'View from the main entrance lobby'
            ],
            [
                'path_id' => 'path1',
                'point_index' => 1,
                'point_x' => 130.00,
                'point_y' => 175.00,
                'floor_number' => 1,
                'image_filename' => 'sample_hallway.jpg',
                'title' => 'Central Hallway',
                'description' => 'Central corridor view'
            ],
            [
                'path_id' => 'path2',
                'point_index' => 0,
                'point_x' => 467.00,
                'point_y' => 220.00,
                'floor_number' => 1,
                'image_filename' => 'sample_corridor.jpg',
                'title' => 'Main Corridor',
                'description' => 'Primary walkway through the building'
            ]
        ];
        
        $insertSQL = "
            INSERT INTO panorama_image 
            (path_id, point_index, point_x, point_y, floor_number, image_filename, title, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $connect->prepare($insertSQL);
        
        foreach ($sampleData as $data) {
            $stmt->execute([
                $data['path_id'],
                $data['point_index'],
                $data['point_x'],
                $data['point_y'],
                $data['floor_number'],
                $data['image_filename'],
                $data['title'],
                $data['description']
            ]);
        }
        
        echo "✓ Sample data added successfully!\n<br>";
    } else {
        echo "Table already contains $count records.\n<br>";
    }
    
    // Create Pano directory if it doesn't exist
    if (!is_dir('Pano')) {
        mkdir('Pano', 0755, true);
        echo "✓ Created 'Pano' directory for storing panorama images.\n<br>";
    } else {
        echo "✓ 'Pano' directory already exists.\n<br>";
    }
    
    echo "\n<br><strong>Setup completed successfully!</strong>\n<br>";
    echo "You can now:\n<br>";
    echo "1. Upload panorama images from the admin floor plan interface\n<br>";
    echo "2. View panoramas in the mobile explore interface\n<br>";
    echo "3. Manage panoramas using the API endpoints\n<br>";
    
} catch (PDOException $e) {
    echo "Error setting up panorama table: " . $e->getMessage() . "\n<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n<br>";
}

// Show current table structure
try {
    echo "\n<br><strong>Current table structure:</strong>\n<br>";
    $stmt = $connect->query("DESCRIBE panorama_image");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Could not display table structure: " . $e->getMessage();
}

?>