<?php
// mobileScreen/verify_location.php
// Verifies a submitted lat/lng against the configured geofence center and radii.
header('Content-Type: application/json');
ini_set('display_errors', 0);
session_start();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$lat = isset($input['lat']) ? floatval($input['lat']) : null;
$lng = isset($input['lng']) ? floatval($input['lng']) : null;
$office_id = isset($input['office_id']) ? intval($input['office_id']) : null;
$page = isset($input['page']) ? $input['page'] : 'unknown';

if ($lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Missing coordinates']);
    exit;
}

// Try to read geofence from DB if office_id provided and connect_db.php available
$center = null;
$zones = null; // array of radii in meters [zone1, zone2, zone3]

if (file_exists(__DIR__ . '/../connect_db.php')) {
    include __DIR__ . '/../connect_db.php';
    try {
        // Prefer reading geofence from geofences table. Try office-specific first, then default.
        if (isset($connect)) {
            if ($office_id) {
                $stmt = $connect->prepare('SELECT center_lat, center_lng, radius1, radius2, radius3 FROM geofences WHERE office_id = :office_id LIMIT 1');
                $stmt->execute([':office_id' => $office_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $center = [floatval($row['center_lat']), floatval($row['center_lng'])];
                    $zones = [intval($row['radius1']), intval($row['radius2']), intval($row['radius3'])];
                }
            }
            if (!$center) {
                $stmt = $connect->prepare("SELECT center_lat, center_lng, radius1, radius2, radius3 FROM geofences WHERE name = 'default' LIMIT 1");
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $center = [floatval($row['center_lat']), floatval($row['center_lng'])];
                    $zones = [intval($row['radius1']), intval($row['radius2']), intval($row['radius3'])];
                }
            }
        }
    } catch (Exception $e) {
        // ignore DB errors and fallback to JS file
    }
}

// Fallback: read from JS config file
$jsFile = __DIR__ . '/js/leafletGeofencing.js';
if (file_exists($jsFile)) {
    $js = file_get_contents($jsFile);
    // extract center: [lat, lng]
    if (preg_match('/center:\s*\[([\d\.-]+),\s*([\d\.-]+)\]/', $js, $m)) {
        $center = [floatval($m[1]), floatval($m[2])];
    }
    // extract radii in order
    preg_match('/Main Palace Building".*,?radius:\s*(\d+)/s', $js, $r1);
    preg_match('/Palace Complex".*,?radius:\s*(\d+)/s', $js, $r2);
    preg_match('/Government Building Grounds".*,?radius:\s*(\d+)/s', $js, $r3);
    $zones = [
        isset($r1[1]) ? intval($r1[1]) : 50,
        isset($r2[1]) ? intval($r2[1]) : 100,
        isset($r3[1]) ? intval($r3[1]) : 150
    ];
}

if (!$center) {
    // Last resort: default center (same as admin defaults)
    $center = [14.5995, 120.9842];
    $zones = $zones ?? [50,100,150];
}

// Haversine distance
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // m
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

$distance = haversine($center[0], $center[1], $lat, $lng);

$result = [
    'distance' => round($distance,2),
    'inside_zone1' => $distance <= $zones[0],
    'inside_zone2' => $distance <= $zones[1],
    'inside_zone3' => $distance <= $zones[2]
];

// Optionally log the check (if DB available)
if (isset($connect) && $connect && $office_id) {
    try {
        $stmtLog = $connect->prepare('INSERT INTO qr_scan_logs (office_id, lat, lng, scanned_at, note) VALUES (:office, :lat, :lng, NOW(), :note)');
        $note = 'geofence_check';
        $stmtLog->execute([':office'=>$office_id, ':lat'=>$lat, ':lng'=>$lng, ':note'=>$note]);
    } catch (Exception $e) {
        // ignore logging errors
    }
}

echo json_encode(['success' => true, 'result' => $result, 'center' => $center, 'zones' => $zones]);

?>
