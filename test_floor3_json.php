<?php
// Diagnostic script to check floor_graph_3.json parsing in PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Floor Graph 3 JSON Parsing Diagnostic</h2>";

$graphFile = __DIR__ . '/floor_graph_3.json';

echo "<h3>1. File Existence Check:</h3>";
if (file_exists($graphFile)) {
    echo "<p style='color: green;'>✅ File exists: " . htmlspecialchars($graphFile) . "</p>";
    echo "<p>File size: " . filesize($graphFile) . " bytes</p>";
} else {
    echo "<p style='color: red;'>❌ File not found</p>";
    exit;
}

echo "<h3>2. Raw File Content (first 500 chars):</h3>";
$rawContent = file_get_contents($graphFile);
echo "<pre>" . htmlspecialchars(substr($rawContent, 0, 500)) . "</pre>";

echo "<h3>3. JSON Parsing Attempt:</h3>";
$graphData = json_decode($rawContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<p style='color: red;'>❌ JSON Parsing FAILED</p>";
    echo "<p><strong>Error Code:</strong> " . json_last_error() . "</p>";
    echo "<p><strong>Error Message:</strong> " . json_last_error_msg() . "</p>";
    
    // Try to find the error location
    switch (json_last_error()) {
        case JSON_ERROR_SYNTAX:
            echo "<p style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
            echo "<strong>⚠️ Syntax Error Detected</strong><br>";
            echo "Common causes:<br>";
            echo "- Trailing commas after last array/object element<br>";
            echo "- Missing commas between elements<br>";
            echo "- Unescaped special characters<br>";
            echo "- Comments in JSON (not allowed)<br>";
            echo "</p>";
            break;
        case JSON_ERROR_UTF8:
            echo "<p style='color: red;'>UTF-8 encoding issue</p>";
            break;
    }
    
    echo "<h3>4. Attempting to Find Syntax Error Location:</h3>";
    echo "<p>Scanning for common JSON syntax errors...</p>";
    
    // Check for trailing commas (common issue)
    if (preg_match_all('/,\s*[\]}]/', $rawContent, $matches, PREG_OFFSET_CAPTURE)) {
        echo "<p style='color: orange;'><strong>⚠️ Found " . count($matches[0]) . " potential trailing comma(s):</strong></p>";
        echo "<ul>";
        foreach ($matches[0] as $idx => $match) {
            $pos = $match[1];
            $line = substr_count(substr($rawContent, 0, $pos), "\n") + 1;
            $snippet = substr($rawContent, max(0, $pos - 30), 60);
            echo "<li>Line ~$line: <code>" . htmlspecialchars($snippet) . "</code></li>";
            if ($idx >= 4) {
                echo "<li>... and " . (count($matches[0]) - 5) . " more</li>";
                break;
            }
        }
        echo "</ul>";
    }
    
} else {
    echo "<p style='color: green;'>✅ JSON parsed successfully!</p>";
    
    echo "<h3>4. Structure Analysis:</h3>";
    
    // Check if rooms property exists
    if (isset($graphData['rooms'])) {
        echo "<p style='color: green;'>✅ 'rooms' property exists</p>";
        echo "<p><strong>Number of rooms:</strong> " . count($graphData['rooms']) . "</p>";
        
        // List all room IDs
        echo "<h4>Available Room IDs:</h4>";
        echo "<ul>";
        foreach (array_keys($graphData['rooms']) as $roomId) {
            $hasDoorPoints = isset($graphData['rooms'][$roomId]['doorPoints']);
            $doorPointsCount = $hasDoorPoints ? count($graphData['rooms'][$roomId]['doorPoints']) : 0;
            $status = $hasDoorPoints ? 
                "<span style='color: green;'>✅ Has $doorPointsCount door point(s)</span>" : 
                "<span style='color: red;'>❌ No doorPoints</span>";
            echo "<li><code>$roomId</code> - $status</li>";
        }
        echo "</ul>";
        
        // Check specific test rooms
        echo "<h4>Testing Specific Rooms:</h4>";
        $testRooms = ['room-1-3', 'room-2-3', 'room-3-3', 'room-4-3', 'room-5-3', 'room-6-3'];
        foreach ($testRooms as $testRoom) {
            if (isset($graphData['rooms'][$testRoom])) {
                if (isset($graphData['rooms'][$testRoom]['doorPoints'])) {
                    $doorPoints = $graphData['rooms'][$testRoom]['doorPoints'];
                    echo "<p style='color: green;'>✅ <code>$testRoom</code>: " . count($doorPoints) . " door point(s)</p>";
                    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . 
                         htmlspecialchars(json_encode($doorPoints, JSON_PRETTY_PRINT)) . "</pre>";
                } else {
                    echo "<p style='color: red;'>❌ <code>$testRoom</code>: No doorPoints property</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ <code>$testRoom</code>: Room not found in graph</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ 'rooms' property NOT found</p>";
        echo "<p>Available top-level properties:</p>";
        echo "<ul>";
        foreach (array_keys($graphData) as $key) {
            echo "<li><code>$key</code></li>";
        }
        echo "</ul>";
    }
}

echo "<h3>5. PHP Version Info:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>JSON extension loaded: " . (extension_loaded('json') ? 'Yes' : 'No') . "</p>";
?>
