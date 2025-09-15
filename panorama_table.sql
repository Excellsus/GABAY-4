-- Table structure for panorama_image
-- This table stores panorama images that can be assigned to point markers with isPano attribute
-- Used by both admin (floorPlan.php) and mobile (explore.php) interfaces

CREATE TABLE `panorama_image` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores panorama images for point markers with isPano attribute';

-- Sample data (based on your floor_graph.json)
INSERT INTO `panorama_image` (`path_id`, `point_index`, `point_x`, `point_y`, `floor_number`, `image_filename`, `title`, `description`) VALUES
('path1', 0, 130.00, 110.00, 1, 'entrance_lobby.jpg', 'Main Entrance Lobby', 'View from the main entrance looking into the building'),
('path1', 1, 130.00, 175.00, 1, 'hallway_central.jpg', 'Central Hallway', 'Central corridor connecting various offices'),
('path2', 0, 467.00, 220.00, 1, 'main_corridor.jpg', 'Main Corridor', 'Primary walkway through the building'),
('path2', 1, 615.00, 220.00, 1, 'office_area_view.jpg', 'Office Area View', 'View of the main office area');

-- Query to get panorama data for a specific floor (for explore.php)
-- SELECT * FROM panorama_image WHERE floor_number = 1 AND is_active = 1 ORDER BY path_id, point_index;

-- Query to get panorama for a specific point (for admin editing)
-- SELECT * FROM panorama_image WHERE path_id = 'path1' AND point_index = 0 AND floor_number = 1;

-- Query to update panorama image for a point
-- UPDATE panorama_image SET image_filename = ?, title = ?, description = ?, updated_at = NOW() 
-- WHERE path_id = ? AND point_index = ? AND floor_number = ?;

-- Query to insert new panorama image
-- INSERT INTO panorama_image (path_id, point_index, point_x, point_y, floor_number, image_filename, original_filename, title, description, file_size, mime_type) 
-- VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);

-- Query to delete panorama image
-- DELETE FROM panorama_image WHERE path_id = ? AND point_index = ? AND floor_number = ?;

-- Query to get all panoramas for admin management
-- SELECT p.*, COUNT(*) as usage_count FROM panorama_image p GROUP BY p.id ORDER BY p.uploaded_at DESC;