<?php
/**
 * Test the validateFeedbackIds function with different inputs
 */

require_once "connect_db.php";

// Include the validation function from the API
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

echo "=== TESTING validateFeedbackIds FUNCTION ===\n\n";

// Test 1: JSON string with single ID (like JavaScript sends)
$test1 = '[1]';
echo "Test 1 - JSON string single ID: '$test1'\n";
$result1 = validateFeedbackIds($test1);
echo "Result: " . print_r($result1, true) . "\n";

// Test 2: JSON string with multiple IDs
$test2 = '[1,2,3,4,5]';
echo "Test 2 - JSON string multiple IDs: '$test2'\n";
$result2 = validateFeedbackIds($test2);
echo "Result: " . print_r($result2, true) . "\n";

// Test 3: Single numeric ID
$test3 = 1;
echo "Test 3 - Single numeric ID: $test3\n";
$result3 = validateFeedbackIds($test3);
echo "Result: " . print_r($result3, true) . "\n";

// Test 4: Array (direct PHP array)
$test4 = [1, 2, 3];
echo "Test 4 - PHP array: " . print_r($test4, true);
$result4 = validateFeedbackIds($test4);
echo "Result: " . print_r($result4, true) . "\n";

// Test 5: Invalid - empty string
$test5 = '';
echo "Test 5 - Empty string: '$test5'\n";
$result5 = validateFeedbackIds($test5);
echo "Result: " . ($result5 === false ? 'FALSE (correct)' : print_r($result5, true)) . "\n\n";

// Test 6: Invalid - negative number
$test6 = '[-1]';
echo "Test 6 - Negative number JSON: '$test6'\n";
$result6 = validateFeedbackIds($test6);
echo "Result: " . ($result6 === false ? 'FALSE (correct)' : print_r($result6, true)) . "\n\n";

// Test 7: Invalid - zero
$test7 = '[0]';
echo "Test 7 - Zero JSON: '$test7'\n";
$result7 = validateFeedbackIds($test7);
echo "Result: " . ($result7 === false ? 'FALSE (correct)' : print_r($result7, true)) . "\n\n";

// Test 8: Real world test - get actual feedback ID from database
echo "Test 8 - Real feedback ID from database:\n";
$stmt = $connect->query("SELECT feed_id FROM feedback LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $realId = $row['feed_id'];
    $test8 = json_encode([$realId]);
    echo "Using real ID: $realId (JSON: $test8)\n";
    $result8 = validateFeedbackIds($test8);
    echo "Result: " . print_r($result8, true) . "\n";
    
    // Test if this ID exists in database
    $checkStmt = $connect->prepare("SELECT COUNT(*) as count FROM feedback WHERE feed_id = ?");
    $checkStmt->execute([$realId]);
    $count = $checkStmt->fetch()['count'];
    echo "Database check: " . ($count > 0 ? "✓ ID exists" : "✗ ID not found") . "\n";
} else {
    echo "No feedback entries in database\n";
}

echo "\n=== ALL TESTS COMPLETE ===\n";
?>
