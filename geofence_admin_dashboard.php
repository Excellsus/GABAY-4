<?php
session_start();
include 'connect_db.php';

// Handle form submission to update coordinates
if (isset($_POST['action']) && $_POST['action'] === 'update_coordinates') {
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $radius1 = intval($_POST['radius1']);
    $radius2 = intval($_POST['radius2']);
    $radius3 = intval($_POST['radius3']);
    
    // Read the current JavaScript file
    $jsFile = 'mobileScreen/js/leafletGeofencing.js';
    $jsContent = file_get_contents($jsFile);
    
    // Update coordinates in the JavaScript
    $newCoordinates = "[$latitude, $longitude]";
    
    // Replace center coordinate
    $jsContent = preg_replace(
        '/center: \[[\d\.-]+, [\d\.-]+\]/',
        "center: $newCoordinates",
        $jsContent
    );
    
    // Update radius values with multiline matching
    $jsContent = preg_replace(
        '/(name: "Main Palace Building".*?radius: )\d+/s',
        '${1}' . $radius1,
        $jsContent
    );
    
    $jsContent = preg_replace(
        '/(name: "Palace Complex".*?radius: )\d+/s',
        '${1}' . $radius2,
        $jsContent
    );
    
    $jsContent = preg_replace(
        '/(name: "Government Building Grounds".*?radius: )\d+/s',
        '${1}' . $radius3,
        $jsContent
    );
    
    // Also update the geofenceConfig object directly for safety
    $jsContent = preg_replace('/center: \[[\d\.-]+, [\d\.-]+\]/', "center: $newCoordinates", $jsContent);
    $jsContent = preg_replace('/\{ name: "Main Palace Building", radius: \d+ \}/', "{ name: \"Main Palace Building\", radius: $radius1 }", $jsContent);
    $jsContent = preg_replace('/\{ name: "Palace Complex", radius: \d+ \}/', "{ name: \"Palace Complex\", radius: $radius2 }", $jsContent);
    $jsContent = preg_replace('/\{ name: "Government Building Grounds", radius: \d+ \}/', "{ name: \"Government Building Grounds\", radius: $radius3 }", $jsContent);

    // Write back to file
    file_put_contents($jsFile, $jsContent);
    
    // Save to DB geofences table (create or update a default record)
    try {
        if (isset($connect) && $connect) {
            $name = 'default';
            // Try update first
            $stmt = $connect->prepare('SELECT id FROM geofences WHERE name = :name LIMIT 1');
            $stmt->execute([':name' => $name]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing && isset($existing['id'])) {
                $upd = $connect->prepare('UPDATE geofences SET center_lat = :lat, center_lng = :lng, radius1 = :r1, radius2 = :r2, radius3 = :r3 WHERE id = :id');
                $upd->execute([':lat'=>$latitude, ':lng'=>$longitude, ':r1'=>$radius1, ':r2'=>$radius2, ':r3'=>$radius3, ':id'=>$existing['id']]);
            } else {
                $ins = $connect->prepare('INSERT INTO geofences (name, center_lat, center_lng, radius1, radius2, radius3) VALUES (:name, :lat, :lng, :r1, :r2, :r3)');
                $ins->execute([':name'=>$name, ':lat'=>$latitude, ':lng'=>$longitude, ':r1'=>$radius1, ':r2'=>$radius2, ':r3'=>$radius3]);
            }
        }
    } catch (Exception $e) {
        // ignore DB errors but do not fail admin update
        error_log('Geofence DB save failed: ' . $e->getMessage());
    }
    
    // Also update the admin dashboard coordinates (internal variables)
    $adminFile = 'geofence_admin_dashboard.php';
    if (file_exists($adminFile)) {
        $adminContent = file_get_contents($adminFile);
        $adminContent = preg_replace(
            '/\$currentLat = [\d\.-]+;/',
            "\$currentLat = $latitude;",
            $adminContent
        );
        $adminContent = preg_replace(
            '/\$currentLng = [\d\.-]+;/',
            "\$currentLng = $longitude;",
            $adminContent
        );
        file_put_contents($adminFile, $adminContent);
    }
    
    $success = "Coordinates and radius updated successfully!";
}

// Handle test location request
if (isset($_POST['action']) && $_POST['action'] === 'test_location') {
    $testLat = floatval($_POST['test_latitude']);
    $testLng = floatval($_POST['test_longitude']);
    
    // Calculate distance from current geofence center
    $centerLat = 14.5995; // Default, will be updated if coordinates are set
    $centerLng = 120.9842;
    
    // Try to read current coordinates from JavaScript file
    $jsFile = 'mobileScreen/js/leafletGeofencing.js';
    if (file_exists($jsFile)) {
        $jsContent = file_get_contents($jsFile);
        if (preg_match('/center: \[([\d\.-]+), ([\d\.-]+)\]/', $jsContent, $matches)) {
            $centerLat = floatval($matches[1]);
            $centerLng = floatval($matches[2]);
        }
    }
    
    // Calculate distance using Haversine formula
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($testLat - $centerLat);
    $dLng = deg2rad($testLng - $centerLng);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($centerLat)) * cos(deg2rad($testLat)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    $testResult = [
        'distance' => round($distance, 2),
        'zone1' => $distance <= 50 ? 'INSIDE' : 'OUTSIDE',
        'zone2' => $distance <= 100 ? 'INSIDE' : 'OUTSIDE', 
        'zone3' => $distance <= 150 ? 'INSIDE' : 'OUTSIDE'
    ];
}

// Read current coordinates from JavaScript file
$currentLat = 10.6496;
$currentLng = 122.96192;
$currentRadius1 = 50;
$currentRadius2 = 100;
$currentRadius3 = 150;

$jsFile = 'mobileScreen/js/leafletGeofencing.js';
// Prefer DB geofence if available
try {
    if (isset($connect) && $connect) {
        $stmt = $connect->query("SELECT center_lat, center_lng, radius1, radius2, radius3 FROM geofences WHERE name = 'default' LIMIT 1");
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($g) {
            $currentLat = floatval($g['center_lat']);
            $currentLng = floatval($g['center_lng']);
            $currentRadius1 = intval($g['radius1']);
            $currentRadius2 = intval($g['radius2']);
            $currentRadius3 = intval($g['radius3']);
        }
    }
} catch (Exception $e) {
    // ignore and fallback to JS file
}

if (file_exists($jsFile)) {
    $jsContent = file_get_contents($jsFile);
    if (preg_match('/center: \[([\d\.-]+), ([\d\.-]+)\]/', $jsContent, $matches)) {
        $currentLat = floatval($matches[1]);
        $currentLng = floatval($matches[2]);
    }
    
    // Extract radius values
    if (preg_match('/name: "Main Palace Building".*?radius: (\d+)/', $jsContent, $matches)) {
        $currentRadius1 = intval($matches[1]);
    }
    if (preg_match('/name: "Palace Complex".*?radius: (\d+)/', $jsContent, $matches)) {
        $currentRadius2 = intval($matches[1]);
    }
    if (preg_match('/name: "Government Building Grounds".*?radius: (\d+)/', $jsContent, $matches)) {
        $currentRadius3 = intval($matches[1]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GABAY Geofencing Configuration</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f7fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }
        
        input[type="number"], input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        input[type="number"]:focus, input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-test {
            background: #4CAF50;
        }
        
        .btn-test:hover {
            background: #45a049;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .map-container {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .current-config {
            background: #e6f4f1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .test-result {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        
        .zone-info {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        
        .zone-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📍 GABAY Geofencing Configuration</h1>
        <p>Set up your building coordinates and geofence radius zones</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="success">
            <strong>✅ Success!</strong> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>🏢 Building Location Setup</h2>
            
            <div class="current-config">
                <h3>Current Configuration:</h3>
                <p><strong>Latitude:</strong> <?= $currentLat ?></p>
                <p><strong>Longitude:</strong> <?= $currentLng ?></p>
                <div class="zone-info">
                    <div class="zone-color" style="background: #ff4444;"></div>
                    <span>Zone 1 (Main Building): <?= $currentRadius1 ?>m radius</span>
                </div>
                <div class="zone-info">
                    <div class="zone-color" style="background: #4CAF50;"></div>
                    <span>Zone 2 (Complex): <?= $currentRadius2 ?>m radius</span>
                </div>
                <div class="zone-info">
                    <div class="zone-color" style="background: #2196F3;"></div>
                    <span>Zone 3 (Grounds): <?= $currentRadius3 ?>m radius</span>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_coordinates">
                
                <div class="form-group">
                    <label>Building Latitude:</label>
                    <input type="number" name="latitude" step="0.000001" value="<?= $currentLat ?>" required>
                    <small style="color: #666;">Example: 14.599512 (6+ decimal places for precision)</small>
                </div>
                
                <div class="form-group">
                    <label>Building Longitude:</label>
                    <input type="number" name="longitude" step="0.000001" value="<?= $currentLng ?>" required>
                    <small style="color: #666;">Example: 120.984222</small>
                </div>
                
                <h3 style="margin: 25px 0 15px 0;">Geofence Radius Zones (meters):</h3>
                
                <div class="form-group">
                    <label>🔴 Zone 1 - Main Building (Strict Access):</label>
                    <input type="number" name="radius1" value="<?= $currentRadius1 ?>" min="10" max="1000" required>
                </div>
                
                <div class="form-group">
                    <label>🟢 Zone 2 - Building Complex:</label>
                    <input type="number" name="radius2" value="<?= $currentRadius2 ?>" min="20" max="2000" required>
                </div>
                
                <div class="form-group">
                    <label>🔵 Zone 3 - Outer Grounds:</label>
                    <input type="number" name="radius3" value="<?= $currentRadius3 ?>" min="30" max="3000" required>
                </div>
                
                <button type="submit" class="btn">💾 Update Configuration</button>
            </form>
        </div>

        <div class="card">
            <h2>🧪 Test Location</h2>
            <p>Test if a specific GPS coordinate would be inside or outside your geofence zones.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="test_location">
                
                <div class="form-group">
                    <label>Test Latitude:</label>
                    <input type="number" name="test_latitude" step="0.000001" placeholder="14.599500" required>
                </div>
                
                <div class="form-group">
                    <label>Test Longitude:</label>
                    <input type="number" name="test_longitude" step="0.000001" placeholder="120.984200" required>
                </div>
                
                <button type="submit" class="btn btn-test">🎯 Test Location</button>
            </form>
            
            <?php if (isset($testResult)): ?>
                <div class="test-result">
                    <h3>🧪 Test Results:</h3>
                    <p><strong>Distance from center:</strong> <?= $testResult['distance'] ?> meters</p>
                    <div class="zone-info">
                        <div class="zone-color" style="background: #ff4444;"></div>
                        <span>Zone 1 (<?= $currentRadius1 ?>m): <strong><?= $testResult['zone1'] ?></strong></span>
                    </div>
                    <div class="zone-info">
                        <div class="zone-color" style="background: #4CAF50;"></div>
                        <span>Zone 2 (<?= $currentRadius2 ?>m): <strong><?= $testResult['zone2'] ?></strong></span>
                    </div>
                    <div class="zone-info">
                        <div class="zone-color" style="background: #2196F3;"></div>
                        <span>Zone 3 (<?= $currentRadius3 ?>m): <strong><?= $testResult['zone3'] ?></strong></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>🗺️ Interactive Map</h2>
        <p>Visual representation of your geofence zones</p>
        <div id="config-map" class="map-container"></div>
    </div>

    <div class="card">
        <h2>📋 Quick Setup Instructions</h2>
        
        <h3>📍 Step 1: Find Your Building Coordinates</h3>
        <ol>
            <li>Open <a href="https://www.google.com/maps" target="_blank">Google Maps</a></li>
            <li>Search for your building or navigate to it</li>
            <li>Right-click on the exact building location</li>
            <li>Click on the coordinates that appear (they'll copy to clipboard)</li>
            <li>Paste the coordinates in the form above</li>
        </ol>
        
        <h3>🎯 Step 2: Set Radius Zones</h3>
        <ul>
            <li><strong>Zone 1 (Main Building):</strong> Tight security - usually 30-100 meters</li>
            <li><strong>Zone 2 (Complex):</strong> Building grounds - usually 100-300 meters</li>
            <li><strong>Zone 3 (Outer Grounds):</strong> Extended area - usually 200-500 meters</li>
        </ul>
        
        <h3>✅ Step 3: Test Your Setup</h3>
        <ol>
            <li>Use the test form above to verify coordinates</li>
            <li>Visit <a href="mobileScreen/explore.php" target="_blank">Mobile Interface</a> on your phone</li>
            <li>Check the <a href="geofence_admin_dashboard.php" target="_blank">Admin Dashboard</a></li>
        </ol>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="mobileScreen/js/leafletGeofencing.js"></script>
    <script>
        // Initialize map
        const map = L.map('config-map').setView([<?= $currentLat ?>, <?= $currentLng ?>], 16);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add geofence zones
        const zones = [
            { name: "Zone 1 - Main Building", radius: <?= $currentRadius1 ?>, color: '#ff4444' },
            { name: "Zone 2 - Building Complex", radius: <?= $currentRadius2 ?>, color: '#4CAF50' },
            { name: "Zone 3 - Outer Grounds", radius: <?= $currentRadius3 ?>, color: '#2196F3' }
        ];
        
        zones.forEach(zone => {
            L.circle([<?= $currentLat ?>, <?= $currentLng ?>], {
                color: zone.color,
                fillColor: zone.color,
                fillOpacity: 0.1,
                radius: zone.radius,
                weight: 2
            }).addTo(map).bindPopup(`<strong>${zone.name}</strong><br>Radius: ${zone.radius}m`);
        });
        
        // Add center marker
        L.marker([<?= $currentLat ?>, <?= $currentLng ?>], {
            icon: L.divIcon({
                className: 'center-marker',
                html: '<div style="background: #667eea; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">🏢</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(map).bindPopup('<strong>Building Center</strong><br>Lat: <?= $currentLat ?><br>Lng: <?= $currentLng ?>');
        
        <?php if (isset($testResult)): ?>
        // Add test point marker
        L.marker([<?= $_POST['test_latitude'] ?>, <?= $_POST['test_longitude'] ?>], {
            icon: L.divIcon({
                className: 'test-marker',
                html: '<div style="background: #ff6b35; color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">📍</div>',
                iconSize: [25, 25],
                iconAnchor: [12.5, 12.5]
            })
        }).addTo(map).bindPopup('<strong>Test Point</strong><br>Distance: <?= $testResult['distance'] ?>m<br>Status: <?= $testResult['zone1'] === 'INSIDE' ? 'ACCESS GRANTED' : 'ACCESS DENIED' ?>');
        
        // Fit map to show both points
        const group = new L.featureGroup([
            L.marker([<?= $currentLat ?>, <?= $currentLng ?>]),
            L.marker([<?= $_POST['test_latitude'] ?>, <?= $_POST['test_longitude'] ?>])
        ]);
        map.fitBounds(group.getBounds().pad(0.1));
        <?php endif; ?>
    </script>
</body>
</html>
