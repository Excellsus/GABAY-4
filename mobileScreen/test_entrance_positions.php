<!DOCTYPE html>
<html>
<head>
    <title>Test Entrance Position Update</title>
</head>
<body>
    <h1>Entrance Position Update Test</h1>
    <p>Open browser console and check for these messages:</p>
    <ul>
        <li>📍 Fetched X entrance positions from database</li>
        <li>📍 Updating entrance X from (old) to (new)</li>
        <li>📍 Updating scanned entrance position</li>
        <li>🔄 Redrawing YOU ARE HERE marker with updated entrance position</li>
        <li>🔄 Redrawing entrance icons with updated positions</li>
    </ul>
    
    <h2>Instructions:</h2>
    <ol>
        <li>Scan an entrance QR code</li>
        <li>Watch the console logs</li>
        <li>Verify the green "YOU ARE HERE" marker appears at the DATABASE coordinates (not JSON)</li>
        <li>Verify the entrance icon appears at the DATABASE coordinates</li>
    </ol>
    
    <h2>Current Database Positions:</h2>
    <?php
    require_once '../connect_db.php';
    $stmt = $connect->query('SELECT entrance_id, floor, x, y FROM entrance_qrcodes WHERE is_active = 1 ORDER BY floor, entrance_id');
    echo '<table border="1" cellpadding="10">';
    echo '<tr><th>Entrance ID</th><th>Floor</th><th>X</th><th>Y</th></tr>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['entrance_id']) . '</td>';
        echo '<td>' . $row['floor'] . '</td>';
        echo '<td>' . $row['x'] . '</td>';
        echo '<td>' . $row['y'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>
    
    <p><strong>Expected behavior:</strong> The "YOU ARE HERE" marker and entrance icons should use the coordinates from the table above, NOT from the JSON files.</p>
</body>
</html>
