<?php
/**
 * Feedback Management API
 * 
 * Handles archive, delete, restore, and batch operations for visitor feedback
 * 
 * Features:
 * - Archive single/multiple feedback entries
 * - Delete single/multiple feedback entries (soft delete)
 * - Restore archived/deleted feedback
 * - Permanent deletion
 * - Comprehensive error handling
 * - Activity logging
 */

require_once "connect_db.php";

// Set JSON response header
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
error_reporting(E_ALL);

/**
 * Send JSON response and exit
 */
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

/**
 * Log feedback action to audit trail
 */
function logFeedbackAction($connect, $feedbackId, $action, $notes = null) {
    try {
        $stmt = $connect->prepare("
            INSERT INTO feedback_archive_log (feedback_id, action, action_by, notes)
            VALUES (:feedback_id, :action, 'Admin User', :notes)
        ");
        $stmt->execute([
            ':feedback_id' => $feedbackId,
            ':action' => $action,
            ':notes' => $notes
        ]);
        
        // Also log to activities table for consistency
        $activityStmt = $connect->prepare("
            INSERT INTO activities (activity_type, activity_text, created_at)
            VALUES ('feedback', :text, NOW())
        ");
        $activityStmt->execute([
            ':text' => "Feedback #$feedbackId was $action"
        ]);
        
    } catch (PDOException $e) {
        error_log("Failed to log feedback action: " . $e->getMessage());
    }
}

/**
 * Validate feedback IDs
 */
function validateFeedbackIds($ids) {
    if (empty($ids)) {
        return false;
    }
    
    // If it's a JSON string, decode it
    if (is_string($ids)) {
        $decoded = json_decode($ids, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $ids = $decoded;
        } else {
            // Not JSON, treat as single ID
            $ids = [$ids];
        }
    }
    
    // If single ID, convert to array
    if (!is_array($ids)) {
        $ids = [$ids];
    }
    
    // Validate all IDs are numeric
    foreach ($ids as $id) {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
    }
    
    return $ids;
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Validate database connection
if (!isset($connect) || !$connect) {
    sendResponse(false, 'Database connection error');
}

try {
    switch ($action) {
        
        // ==================== ARCHIVE OPERATIONS ====================
        
        case 'archive':
            // Archive single or multiple feedback entries
            $ids = $_POST['ids'] ?? null;
            $ids = validateFeedbackIds($ids);
            
            if (!$ids) {
                sendResponse(false, 'Invalid feedback ID(s)');
            }
            
            $connect->beginTransaction();
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // Update feedback status to archived
            $stmt = $connect->prepare("
                UPDATE feedback 
                SET is_archived = 1, archived_at = NOW() 
                WHERE feed_id IN ($placeholders) AND deleted_at IS NULL
            ");
            $stmt->execute($ids);
            $affectedRows = $stmt->rowCount();
            
            // Log each action
            foreach ($ids as $id) {
                logFeedbackAction($connect, $id, 'archived', 'Archived by admin');
            }
            
            $connect->commit();
            
            $message = $affectedRows === 1 
                ? "1 feedback entry archived successfully" 
                : "$affectedRows feedback entries archived successfully";
            
            sendResponse(true, $message, ['archived_count' => $affectedRows]);
            break;
            
        case 'unarchive':
            // Restore feedback from archive
            $ids = $_POST['ids'] ?? null;
            $ids = validateFeedbackIds($ids);
            
            if (!$ids) {
                sendResponse(false, 'Invalid feedback ID(s)');
            }
            
            $connect->beginTransaction();
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $stmt = $connect->prepare("
                UPDATE feedback 
                SET is_archived = 0, archived_at = NULL 
                WHERE feed_id IN ($placeholders) AND deleted_at IS NULL
            ");
            $stmt->execute($ids);
            $affectedRows = $stmt->rowCount();
            
            foreach ($ids as $id) {
                logFeedbackAction($connect, $id, 'unarchived', 'Restored from archive');
            }
            
            $connect->commit();
            
            $message = $affectedRows === 1 
                ? "1 feedback entry restored from archive" 
                : "$affectedRows feedback entries restored from archive";
            
            sendResponse(true, $message, ['unarchived_count' => $affectedRows]);
            break;
            
        // ==================== DELETE OPERATIONS ====================
        
        case 'delete':
            // Soft delete (move to trash)
            $ids = $_POST['ids'] ?? null;
            $ids = validateFeedbackIds($ids);
            
            if (!$ids) {
                sendResponse(false, 'Invalid feedback ID(s)');
            }
            
            $connect->beginTransaction();
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // Soft delete by setting deleted_at timestamp
            $stmt = $connect->prepare("
                UPDATE feedback 
                SET deleted_at = NOW() 
                WHERE feed_id IN ($placeholders) AND deleted_at IS NULL
            ");
            $stmt->execute($ids);
            $affectedRows = $stmt->rowCount();
            
            foreach ($ids as $id) {
                logFeedbackAction($connect, $id, 'deleted', 'Moved to trash (soft delete)');
            }
            
            $connect->commit();
            
            $message = $affectedRows === 1 
                ? "1 feedback entry moved to trash" 
                : "$affectedRows feedback entries moved to trash";
            
            sendResponse(true, $message, ['deleted_count' => $affectedRows]);
            break;
            
        case 'restore':
            // Restore from trash
            $ids = $_POST['ids'] ?? null;
            $ids = validateFeedbackIds($ids);
            
            if (!$ids) {
                sendResponse(false, 'Invalid feedback ID(s)');
            }
            
            $connect->beginTransaction();
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $stmt = $connect->prepare("
                UPDATE feedback 
                SET deleted_at = NULL 
                WHERE feed_id IN ($placeholders) AND deleted_at IS NOT NULL
            ");
            $stmt->execute($ids);
            $affectedRows = $stmt->rowCount();
            
            foreach ($ids as $id) {
                logFeedbackAction($connect, $id, 'restored', 'Restored from trash');
            }
            
            $connect->commit();
            
            $message = $affectedRows === 1 
                ? "1 feedback entry restored" 
                : "$affectedRows feedback entries restored";
            
            sendResponse(true, $message, ['restored_count' => $affectedRows]);
            break;
            
        case 'delete_permanent':
            // Permanent deletion (cannot be undone)
            $ids = $_POST['ids'] ?? null;
            $confirm = $_POST['confirm'] ?? false;
            
            $ids = validateFeedbackIds($ids);
            
            if (!$ids || !$confirm) {
                sendResponse(false, 'Confirmation required for permanent deletion');
            }
            
            $connect->beginTransaction();
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // Log before deletion
            foreach ($ids as $id) {
                logFeedbackAction($connect, $id, 'deleted', 'Permanently deleted');
            }
            
            // Permanently delete from database
            $stmt = $connect->prepare("
                DELETE FROM feedback 
                WHERE feed_id IN ($placeholders)
            ");
            $stmt->execute($ids);
            $affectedRows = $stmt->rowCount();
            
            $connect->commit();
            
            $message = $affectedRows === 1 
                ? "1 feedback entry permanently deleted" 
                : "$affectedRows feedback entries permanently deleted";
            
            sendResponse(true, $message, ['permanently_deleted' => $affectedRows]);
            break;
            
        // ==================== STATS AND INFO ====================
        
        case 'get_stats':
            // Get statistics about feedback status
            $stmt = $connect->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_archived = 1 AND deleted_at IS NULL THEN 1 ELSE 0 END) as archived,
                    SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted,
                    SUM(CASE WHEN is_archived = 0 AND deleted_at IS NULL THEN 1 ELSE 0 END) as active
                FROM feedback
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Statistics retrieved', $stats);
            break;
            
        case 'get_archive_log':
            // Get audit log for specific feedback
            $feedbackId = $_GET['feedback_id'] ?? null;
            
            if (!$feedbackId || !is_numeric($feedbackId)) {
                sendResponse(false, 'Invalid feedback ID');
            }
            
            $stmt = $connect->prepare("
                SELECT * FROM feedback_archive_log 
                WHERE feedback_id = ? 
                ORDER BY action_at DESC
            ");
            $stmt->execute([$feedbackId]);
            $log = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Archive log retrieved', $log);
            break;
            
        default:
            sendResponse(false, 'Invalid action specified');
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    
    error_log("Feedback management error: " . $e->getMessage());
    sendResponse(false, 'Database error occurred. Please try again.');
    
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred. Please try again.');
}
?>
