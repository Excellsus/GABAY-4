<?php
// Test script to check if there are any offices assigned to floor 3
require_once 'connect_db.php';

echo "<h2>Floor 3 Office Check</h2>";

// Check for offices with floor 3 locations
$stmt = $connect->query("SELECT id, name, location FROM offices WHERE location LIKE '%room-%-3%' ORDER BY location");
$floor3Offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Offices assigned to Floor 3 rooms:</h3>";
if (count($floor3Offices) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Office Name</th><th>Location</th></tr>";
    foreach ($floor3Offices as $office) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($office['id']) . "</td>";
        echo "<td>" . htmlspecialchars($office['name']) . "</td>";
        echo "<td>" . htmlspecialchars($office['location']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Now try to generate QR for the first floor 3 office
    echo "<h3>Testing QR Generation for first floor 3 office:</h3>";
    $testOffice = $floor3Offices[0];
    echo "<p>Testing office: " . htmlspecialchars($testOffice['name']) . " (ID: {$testOffice['id']}, Location: {$testOffice['location']})</p>";
    
    // Check floor graph
    $roomId = $testOffice['location'];
    $floorNumber = 1;
    if (preg_match('/room-\d+-(\d+)/', $roomId, $matches)) {
        $floorNumber = (int)$matches[1];
    }
    
    echo "<p>Detected floor number: $floorNumber</p>";
    
    $graphFile = __DIR__ . '/floor_graph' . ($floorNumber > 1 ? "_$floorNumber" : '') . '.json';
    echo "<p>Looking for graph file: " . htmlspecialchars($graphFile) . "</p>";
    
    if (file_exists($graphFile)) {
        echo "<p style='color: green;'>✅ Floor graph file EXISTS</p>";
        
        $graphData = json_decode(file_get_contents($graphFile), true);
        
        // Check for JSON parsing errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "<p style='color: red;'>❌ JSON PARSING ERROR: " . json_last_error_msg() . "</p>";
            echo "<p><strong>Error Code:</strong> " . json_last_error() . "</p>";
            echo "<p><strong>Solution:</strong> Run <a href='test_floor3_json.php' target='_blank'>test_floor3_json.php</a> for detailed diagnostics</p>";
        } elseif (!isset($graphData['rooms'])) {
            echo "<p style='color: red;'>❌ JSON parsed but 'rooms' property is missing or null</p>";
            echo "<p>Available properties: " . implode(', ', array_keys($graphData ?: [])) . "</p>";
            echo "<p><strong>Solution:</strong> Run <a href='test_floor3_json.php' target='_blank'>test_floor3_json.php</a> for detailed diagnostics</p>";
        } elseif (isset($graphData['rooms'][$roomId])) {
            echo "<p style='color: green;'>✅ Room '$roomId' found in graph</p>";
            
            if (isset($graphData['rooms'][$roomId]['doorPoints'])) {
                $doorPoints = $graphData['rooms'][$roomId]['doorPoints'];
                echo "<p style='color: green;'>✅ Door points found: " . count($doorPoints) . " doors</p>";
                echo "<pre>" . htmlspecialchars(json_encode($doorPoints, JSON_PRETTY_PRINT)) . "</pre>";
            } else {
                echo "<p style='color: red;'>❌ No doorPoints property found for room '$roomId'</p>";
                echo "<p>Room data:</p><pre>" . htmlspecialchars(json_encode($graphData['rooms'][$roomId], JSON_PRETTY_PRINT)) . "</pre>";
            }
        } else {
            echo "<p style='color: red;'>❌ Room '$roomId' NOT found in floor graph</p>";
            echo "<p>Available rooms in graph:</p><ul>";
            foreach (array_keys($graphData['rooms']) as $room) {
                echo "<li>" . htmlspecialchars($room) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ Floor graph file NOT FOUND</p>";
    }
    
} else {
    echo "<p style='color: orange;'>⚠️ No offices are currently assigned to floor 3 rooms.</p>";
    echo "<p>To test floor 3 QR generation, you need to first assign an office to a room on floor 3 (room-1-3, room-2-3, etc.)</p>";
}

// Also show all offices to see what's assigned where
echo "<h3>All Offices in Database:</h3>";
$allStmt = $connect->query("SELECT id, name, location FROM offices ORDER BY id");
$allOffices = $allStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Office Name</th><th>Location</th><th>Floor</th></tr>";
foreach ($allOffices as $office) {
    $floor = 'Unassigned';
    if ($office['location'] && preg_match('/room-\d+-(\d+)/', $office['location'], $m)) {
        $floor = "Floor " . $m[1];
    }
    echo "<tr>";
    echo "<td>" . htmlspecialchars($office['id']) . "</td>";
    echo "<td>" . htmlspecialchars($office['name']) . "</td>";
    echo "<td>" . htmlspecialchars($office['location']) . "</td>";
    echo "<td>" . htmlspecialchars($floor) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
