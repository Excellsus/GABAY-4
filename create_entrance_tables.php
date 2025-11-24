<?php
/**
 * Database Migration: Create Entrance QR Code Tables
 * 
 * This script creates the necessary tables for the entrance QR code system.
 * Entrances act as standalone starting points for pathfinding, independent of offices.
 * They are excluded from office statistics by using separate tables.
 * 
 * Run this script once to set up the schema.
 */

require_once 'connect_db.php';

try {
    // Start transaction
    $connect->beginTransaction();
    
    // 1. Create entrance_qrcodes table
    // Stores QR code information for building entrances
    // No office_id FK - entrances are independent entities
    $sql_entrance_qrcodes = "
    CREATE TABLE IF NOT EXISTS `entrance_qrcodes` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `entrance_id` VARCHAR(50) NOT NULL COMMENT 'Unique entrance identifier (e.g., entrance_main_1)',
      `floor` INT(11) NOT NULL COMMENT 'Floor number where entrance is located',
      `label` VARCHAR(255) NOT NULL COMMENT 'Human-readable entrance name (e.g., Main Entrance)',
      `x` DECIMAL(10,2) NOT NULL COMMENT 'X coordinate on floor SVG',
      `y` DECIMAL(10,2) NOT NULL COMMENT 'Y coordinate on floor SVG',
      `nearest_path_id` VARCHAR(100) DEFAULT NULL COMMENT 'Closest walkable path ID from floor graph',
      `qr_code_data` TEXT NOT NULL COMMENT 'QR code URL: explore.php?entrance_qr=1&entrance_id=X&floor=Y',
      `qr_code_image` VARCHAR(255) NOT NULL COMMENT 'Filename: entrance_main_1_floor_1.png',
      `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Toggle to activate/deactivate entrance QR',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_entrance_id` (`entrance_id`),
      KEY `idx_floor` (`floor`),
      KEY `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='QR codes for building entrances - independent of offices';
    ";
    
    $connect->exec($sql_entrance_qrcodes);
    echo "✓ Created table: entrance_qrcodes\n";
    
    // 2. Create entrance_scan_logs table
    // Separate from qr_scan_logs to exclude entrance scans from office statistics
    $sql_entrance_scan_logs = "
    CREATE TABLE IF NOT EXISTS `entrance_scan_logs` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `entrance_id` VARCHAR(50) NOT NULL COMMENT 'FK to entrance_qrcodes.entrance_id',
      `entrance_qr_id` INT(11) NOT NULL COMMENT 'FK to entrance_qrcodes.id',
      `check_in_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `session_id` VARCHAR(255) DEFAULT NULL COMMENT 'PHP session ID for deduplication',
      `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'Browser user agent',
      `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 address',
      PRIMARY KEY (`id`),
      KEY `idx_entrance_id` (`entrance_id`),
      KEY `idx_entrance_qr_id` (`entrance_qr_id`),
      KEY `idx_check_in_time` (`check_in_time`),
      CONSTRAINT `entrance_scan_logs_ibfk_1` 
        FOREIGN KEY (`entrance_qr_id`) 
        REFERENCES `entrance_qrcodes` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Scan logs for entrance QR codes - isolated from office statistics';
    ";
    
    $connect->exec($sql_entrance_scan_logs);
    echo "✓ Created table: entrance_scan_logs\n";
    
    // Commit transaction only if we're in one
    if ($connect->inTransaction()) {
        $connect->commit();
    }
    
    echo "\n========================================\n";
    echo "✅ SUCCESS: Entrance tables created!\n";
    echo "========================================\n";
    echo "Next steps:\n";
    echo "1. Add entrances to floor graph JSON files\n";
    echo "2. Access entranceManagement.php to generate QR codes\n";
    echo "3. Print and place entrance QR codes at physical locations\n";
    echo "4. Scan entrance QRs in mobile view to test pathfinding\n";
    
} catch (PDOException $e) {
    // Rollback on error
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    
    echo "\n========================================\n";
    echo "❌ ERROR: Migration failed\n";
    echo "========================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nIf tables already exist, this is normal.\n";
    echo "To recreate tables, manually DROP them first:\n";
    echo "DROP TABLE IF EXISTS entrance_scan_logs;\n";
    echo "DROP TABLE IF EXISTS entrance_qrcodes;\n";
    exit(1);
}
?>
