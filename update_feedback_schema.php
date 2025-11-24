<?php
/**
 * Database Schema Update for Feedback Archive System
 * 
 * This script adds the necessary columns and tables for:
 * - Archiving feedback
 * - Soft deletion
 * - Audit trail
 * 
 * Run this once to update your database schema
 */

require_once "connect_db.php";

try {
    // Note: ALTER TABLE doesn't support transactions in MySQL/MariaDB
    // So we'll execute each statement individually without transaction
    
    // 1. Add archive status column to feedback table
    try {
        $connect->exec("
            ALTER TABLE feedback 
            ADD COLUMN is_archived TINYINT(1) DEFAULT 0 COMMENT 'Archive status: 0=active, 1=archived'
        ");
        echo "✓ Added is_archived column to feedback table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ is_archived column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Add soft delete column to feedback table
    try {
        $connect->exec("
            ALTER TABLE feedback 
            ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete timestamp'
        ");
        echo "✓ Added deleted_at column to feedback table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ deleted_at column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Add archived timestamp
    try {
        $connect->exec("
            ALTER TABLE feedback 
            ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Archive timestamp'
        ");
        echo "✓ Added archived_at column to feedback table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ archived_at column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Create feedback_archive_log table for audit trail
    $connect->exec("
        CREATE TABLE IF NOT EXISTS feedback_archive_log (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            feedback_id INT(11) NOT NULL,
            action ENUM('archived', 'unarchived', 'deleted', 'restored') NOT NULL,
            action_by VARCHAR(100) DEFAULT 'Admin User',
            action_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT NULL,
            INDEX idx_feedback_id (feedback_id),
            INDEX idx_action_at (action_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        COMMENT='Audit log for feedback archive and delete operations'
    ");
    echo "✓ Created feedback_archive_log table\n";
    
    // 5. Add indexes for performance
    try {
        $connect->exec("
            CREATE INDEX idx_is_archived ON feedback(is_archived)
        ");
        echo "✓ Added index on is_archived column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "✓ Index on is_archived already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $connect->exec("
            CREATE INDEX idx_deleted_at ON feedback(deleted_at)
        ");
        echo "✓ Added index on deleted_at column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "✓ Index on deleted_at already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ Database schema updated successfully!\n";
    echo "You can now use the archive and delete features.\n";
    
} catch (PDOException $e) {
    echo "❌ Error updating database schema: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
    exit(1);
}
?>
