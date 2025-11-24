<?php
/**
 * Feedback System Diagnostic Script
 * Checks if the database and visitorFeedback.php are properly configured
 */

require_once "connect_db.php";

echo "=== FEEDBACK SYSTEM DIAGNOSTIC ===\n\n";

// Check 1: Database connection
try {
    $connect->query("SELECT 1");
    echo "✓ Database connection: OK\n";
} catch (PDOException $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

// Check 2: Feedback table exists
try {
    $connect->query("SELECT * FROM feedback LIMIT 1");
    echo "✓ Feedback table: EXISTS\n";
} catch (PDOException $e) {
    echo "❌ Feedback table: NOT FOUND\n";
    exit(1);
}

// Check 3: Required columns
echo "\n--- Checking Columns ---\n";
$stmt = $connect->query("DESCRIBE feedback");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$requiredColumns = ['id', 'visitor_name', 'rating', 'comments', 'submitted_at', 'is_archived', 'deleted_at', 'archived_at', 'office_id'];

foreach ($requiredColumns as $col) {
    if (in_array($col, $columns)) {
        echo "✓ Column '$col': EXISTS\n";
    } else {
        echo "❌ Column '$col': MISSING\n";
    }
}

// Check 4: Archive log table
echo "\n--- Checking Archive Log Table ---\n";
try {
    $connect->query("SELECT * FROM feedback_archive_log LIMIT 1");
    echo "✓ feedback_archive_log table: EXISTS\n";
} catch (PDOException $e) {
    echo "❌ feedback_archive_log table: NOT FOUND\n";
}

// Check 5: Sample query (like visitorFeedback.php uses)
echo "\n--- Testing Sample Query ---\n";
try {
    $stmt = $connect->query("SELECT * FROM feedback WHERE is_archived = 0 AND deleted_at IS NULL LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "✓ Query test: SUCCESS (found " . count($row) . " columns)\n";
        echo "  Sample data: " . $row['visitor_name'] . " rated " . $row['rating'] . " stars\n";
    } else {
        echo "✓ Query test: SUCCESS (no active feedback found, which is OK)\n";
    }
} catch (PDOException $e) {
    echo "❌ Query test: FAILED - " . $e->getMessage() . "\n";
}

// Check 6: Count feedback by status
echo "\n--- Feedback Counts ---\n";
try {
    $stmt = $connect->query("SELECT COUNT(*) as total FROM feedback WHERE is_archived = 0 AND deleted_at IS NULL");
    $active = $stmt->fetch()['total'];
    echo "Active feedback: $active\n";
    
    $stmt = $connect->query("SELECT COUNT(*) as total FROM feedback WHERE is_archived = 1 AND deleted_at IS NULL");
    $archived = $stmt->fetch()['total'];
    echo "Archived feedback: $archived\n";
    
    $stmt = $connect->query("SELECT COUNT(*) as total FROM feedback WHERE deleted_at IS NOT NULL");
    $trash = $stmt->fetch()['total'];
    echo "Trashed feedback: $trash\n";
} catch (PDOException $e) {
    echo "❌ Count test: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
?>
