<?php
include("connect_db.php");
$stmt = $connect->prepare("SELECT * FROM admin WHERE username = 'admin_user' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent activities with office names
$activityStmt = $connect->prepare("
    SELECT a.*, o.name as office_name, 
    TIMESTAMPDIFF(SECOND, a.created_at, NOW()) as seconds_ago,
    TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) as minutes_ago,
    TIMESTAMPDIFF(HOUR, a.created_at, NOW()) as hours_ago,
    TIMESTAMPDIFF(DAY, a.created_at, NOW()) as days_ago
    FROM activities a 
    LEFT JOIN offices o ON a.office_id = o.id 
    ORDER BY a.created_at DESC 
    LIMIT 4
");
$activityStmt->execute();
$activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to format time ago
function getTimeAgo($activity) {
    if ($activity['seconds_ago'] < 60) {
        return "just now";
    } elseif ($activity['minutes_ago'] < 60) {
        $mins = $activity['minutes_ago'];
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($activity['hours_ago'] < 24) {
        $hours = $activity['hours_ago'];
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } else {
        $days = $activity['days_ago'];
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    }
}

// Function to get appropriate icon
function getActivityIcon($type) {
    switch ($type) {
        case 'office':
            return 'fa-building';
        case 'file':
            return 'fa-map-location-dot';
        case 'feedback':
            return 'fa-comment';
        default:
            return 'fa-user';
    }
}

// AJAX endpoint for updating account settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'updateAccount') {
  $result = ['success' => false];
  
  try {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($email)) {
      throw new Exception("Email is required");
    }
    
    if (!empty($password) && $password !== $confirm_password) {
      throw new Exception("Passwords do not match");
    }
    
    // Update the admin record
    if (!empty($password)) {
      // Update email and password
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $connect->prepare("UPDATE admin SET email = ?, password = ? WHERE username = 'admin_user'");
      $stmt->execute([$email, $hashedPassword]);
    } else {
      // Update email only
      $stmt = $connect->prepare("UPDATE admin SET email = ? WHERE username = 'admin_user'");
      $stmt->execute([$email]);
    }
    
    $result = ['success' => true, 'message' => 'Account updated successfully'];
  } catch (Exception $e) {
    $result = ['success' => false, 'message' => $e->getMessage()];
  }
  
  echo json_encode($result);
  exit;
}

// Handle geofence coordinate updates
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
    
    $geofenceSuccess = "Geofence coordinates and radius updated successfully!";
}

// Handle test location request
if (isset($_POST['action']) && $_POST['action'] === 'test_location') {
    $testLat = floatval($_POST['test_latitude']);
    $testLng = floatval($_POST['test_longitude']);
    
    // Calculate distance from current geofence center
    $centerLat = 10.6496; // Default
    $centerLng = 122.96192;
    
    // Try to read current coordinates from JavaScript file or DB
    $jsFile = 'mobileScreen/js/leafletGeofencing.js';
    try {
        if (isset($connect) && $connect) {
            $stmt = $connect->query("SELECT center_lat, center_lng, radius1, radius2, radius3 FROM geofences WHERE name = 'default' LIMIT 1");
            $g = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($g) {
                $centerLat = floatval($g['center_lat']);
                $centerLng = floatval($g['center_lng']);
            }
        }
    } catch (Exception $e) {
        // fallback to JS file
    }
    
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
        'zone1' => $distance <= $currentRadius1 ? 'INSIDE' : 'OUTSIDE',
        'zone2' => $distance <= $currentRadius2 ? 'INSIDE' : 'OUTSIDE', 
        'zone3' => $distance <= $currentRadius3 ? 'INSIDE' : 'OUTSIDE'
    ];
}

// Read current geofence coordinates from DB or JavaScript file
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GABAY Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="systemSetting.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="mobileNav.css" />
  <style>
    /* Custom modal styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s, visibility 0.3s;
    }
    
    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    
    .modal-container {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      transform: translateY(-20px);
      transition: transform 0.3s;
    }
    
    .modal-overlay.active .modal-container {
      transform: translateY(0);
    }
    
    .modal-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: #333;
    }
    
    .modal-content {
      margin-bottom: 20px;
      color: #555;
      line-height: 1.5;
    }
    
    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    .modal-btn {
      padding: 10px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 500;
      transition: background-color 0.2s;
    }
    
    .modal-btn-cancel {
      background-color: #e2e8f0;
      color: #4a5568;
    }
    
    .modal-btn-cancel:hover {
      background-color: #cbd5e0;
    }
    
    .modal-btn-confirm {
      background-color: #e53e3e;
      color: white;
    }
    
    .modal-btn-confirm:hover {
      background-color: #c53030;
    }
  </style>
</head>
<body>
  <div class="container">
     
    <!-- Mobile Navigation -->
  <div class="mobile-nav">
    <div class="mobile-nav-header">
      <div class="mobile-logo-container">
        <img src="./srcImage/images-removebg-preview.png" alt="GABAY Logo">
        <div>
          <h1>GABAY</h1>
          <p>Admin Portal</p>
        </div>
      </div>
      <div class="hamburger-icon" onclick="toggleMobileMenu()">
        <i class="fa fa-bars"></i>
      </div>
    </div>
    
    <div class="mobile-menu" id="mobileMenu">
      <a href="home.php">Dashboard</a>
      <a href="officeManagement.php">Office Management</a>
      <a href="floorPlan.php">Floor Plans</a>
      <a href="visitorFeedback.php">Visitor Feedback</a>
      <a href="systemSettings.php" class="active">System Settings</a>
    </div>
  </div>
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <img src="./srcImage/images-removebg-preview.png" alt="Logo" class="icon" />
        </div>
        <div>
          <h1>GABAY</h1>
          <p>Admin Portal</p>
        </div>
      </div>

      <nav class="sidebar-nav">
        <ul>
          <li><a href="home.php">Dashboard</a></li>
          <li><a href="officeManagement.php">Office Management</a></li>
          <li><a href="floorPlan.php">Floor Plans</a></li>
          <li><a href="visitorFeedback.php">Visitor Feedback</a></li>
          <li><a href="systemSettings.php" class="active">System Settings</a></li>
        </ul>
      </nav>

      <div class="sidebar-footer">
        <div class="profile">
          <div class="avatar">AD</div>
          <div>
            <p>Admin User</p>
            <span>Super Admin</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Top Bar -->
      <header class="header">
        <div>
          <h2>Admin Settings</h2>
          <p>Manage your account and system preferences.</p>
        </div>
      </header>

      <div class="gabay-main-content">
        <!-- Content Grid -->
        <div class="gabay-grid">

          <!-- Account Settings -->
          <form id="accountSettingsForm" class="gabay-card">
            <h3 class="gabay-card-title">Account Settings</h3>
            <div class="gabay-form-group">
              <label>Username</label>
              <input type="text" value="<?= htmlspecialchars($admin['username']) ?>" disabled />
            </div>
            <div class="gabay-form-group">
              <label>Email</label>
              <input type="email" name="email" id="email" value="<?= htmlspecialchars($admin['email']) ?>" required />
            </div>
            <div class="gabay-form-group">
              <label>Change Password</label>
              <input type="password" name="password" id="password" placeholder="Enter new password" />
            </div>
            <div class="gabay-form-group">
              <label>Confirm Password</label>
              <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" />
            </div>
            <div id="updateMessage" style="margin-bottom: 10px;"></div>
            <div class="gabay-button-wrapper">
              <button type="submit" class="gabay-btn gabay-btn-green">Update Account</button>
            </div>
          </form>

          <!-- System Preferences -->
          <div class="gabay-card">
            <h3 class="gabay-card-title">Recent Activity</h3>

            <div class="activity-list" id="activityList">
              <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                  <div class="activity-icon">
                    <i class="fa <?php echo getActivityIcon($activity['activity_type']); ?>"></i>
                  </div>
                  <div class="activity-details">
                    <p class="activity-text"><?php echo htmlspecialchars($activity['activity_text']); ?></p>
                    <span class="activity-time"><?php echo getTimeAgo($activity); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Logout Button -->
            <div class="logout-section">
              <button id="logoutBtn" class="drawer-button">
                <i class="fa fa-sign-out"></i> Logout
              </button>
            </div>
          </div>

          <!-- Geofencing Configuration Section -->
          <div class="gabay-card" style="grid-column: 1 / -1;">
            <h3 class="gabay-card-title">📍 Geofencing Configuration</h3>
            <p style="color: #666; margin-bottom: 20px;">Set up your building coordinates and geofence radius zones for mobile visitor access control.</p>
            
            <?php if (isset($geofenceSuccess)): ?>
              <div class="success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>✅ Success!</strong> <?= htmlspecialchars($geofenceSuccess) ?>
              </div>
            <?php endif; ?>

            <div class="gabay-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
              
              <!-- Current Configuration Display -->
              <div style="background: #e6f4f1; padding: 15px; border-radius: 8px;">
                <h4 style="margin-top: 0;">Current Configuration:</h4>
                <p><strong>Latitude:</strong> <?= $currentLat ?></p>
                <p><strong>Longitude:</strong> <?= $currentLng ?></p>
                <div style="display: flex; align-items: center; margin: 10px 0;">
                  <div style="width: 20px; height: 20px; border-radius: 50%; background: #ff4444; margin-right: 10px;"></div>
                  <span>Zone 1 (Main Building): <?= $currentRadius1 ?>m radius</span>
                </div>
                <div style="display: flex; align-items: center; margin: 10px 0;">
                  <div style="width: 20px; height: 20px; border-radius: 50%; background: #4CAF50; margin-right: 10px;"></div>
                  <span>Zone 2 (Complex): <?= $currentRadius2 ?>m radius</span>
                </div>
                <div style="display: flex; align-items: center; margin: 10px 0;">
                  <div style="width: 20px; height: 20px; border-radius: 50%; background: #2196F3; margin-right: 10px;"></div>
                  <span>Zone 3 (Grounds): <?= $currentRadius3 ?>m radius</span>
                </div>
              </div>

              <!-- Update Coordinates Form -->
              <form method="POST" class="gabay-form">
                <input type="hidden" name="action" value="update_coordinates">
                
                <div class="gabay-form-group">
                  <label>Building Latitude:</label>
                  <input type="number" name="latitude" step="0.000001" value="<?= $currentLat ?>" required>
                  <small style="color: #666;">Example: 14.599512 (6+ decimal places)</small>
                </div>
                
                <div class="gabay-form-group">
                  <label>Building Longitude:</label>
                  <input type="number" name="longitude" step="0.000001" value="<?= $currentLng ?>" required>
                  <small style="color: #666;">Example: 120.984222</small>
                </div>
                
                <h4 style="margin: 20px 0 10px 0;">Geofence Radius Zones (meters):</h4>
                
                <div class="gabay-form-group">
                  <label>🔴 Zone 1 - Main Building:</label>
                  <input type="number" name="radius1" value="<?= $currentRadius1 ?>" min="10" max="1000" required>
                </div>
                
                <div class="gabay-form-group">
                  <label>🟢 Zone 2 - Building Complex:</label>
                  <input type="number" name="radius2" value="<?= $currentRadius2 ?>" min="20" max="2000" required>
                </div>
                
                <div class="gabay-form-group">
                  <label>🔵 Zone 3 - Outer Grounds:</label>
                  <input type="number" name="radius3" value="<?= $currentRadius3 ?>" min="30" max="3000" required>
                </div>
                
                <div class="gabay-button-wrapper">
                  <button type="submit" class="gabay-btn gabay-btn-green">💾 Update Geofence</button>
                </div>
              </form>

            </div>

            <!-- Test Location Section -->
            <div class="gabay-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
              
              <form method="POST" class="gabay-form">
                <h4 style="margin-top: 0;">🧪 Test Location</h4>
                <p style="color: #666; font-size: 14px;">Test if a GPS coordinate is inside your geofence zones.</p>
                
                <input type="hidden" name="action" value="test_location">
                
                <div class="gabay-form-group">
                  <label>Test Latitude:</label>
                  <input type="number" name="test_latitude" step="0.000001" placeholder="14.599500" required>
                </div>
                
                <div class="gabay-form-group">
                  <label>Test Longitude:</label>
                  <input type="number" name="test_longitude" step="0.000001" placeholder="120.984200" required>
                </div>
                
                <div class="gabay-button-wrapper">
                  <button type="submit" class="gabay-btn" style="background: #4CAF50;">🎯 Test Location</button>
                </div>
              </form>
              
              <?php if (isset($testResult)): ?>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px;">
                  <h4 style="margin-top: 0;">🧪 Test Results:</h4>
                  <p><strong>Distance from center:</strong> <?= $testResult['distance'] ?> meters</p>
                  <div style="display: flex; align-items: center; margin: 10px 0;">
                    <div style="width: 20px; height: 20px; border-radius: 50%; background: #ff4444; margin-right: 10px;"></div>
                    <span>Zone 1 (<?= $currentRadius1 ?>m): <strong><?= $testResult['zone1'] ?></strong></span>
                  </div>
                  <div style="display: flex; align-items: center; margin: 10px 0;">
                    <div style="width: 20px; height: 20px; border-radius: 50%; background: #4CAF50; margin-right: 10px;"></div>
                    <span>Zone 2 (<?= $currentRadius2 ?>m): <strong><?= $testResult['zone2'] ?></strong></span>
                  </div>
                  <div style="display: flex; align-items: center; margin: 10px 0;">
                    <div style="width: 20px; height: 20px; border-radius: 50%; background: #2196F3; margin-right: 10px;"></div>
                    <span>Zone 3 (<?= $currentRadius3 ?>m): <strong><?= $testResult['zone3'] ?></strong></span>
                  </div>
                </div>
              <?php endif; ?>

            </div>

            <!-- Interactive Map -->
            <div>
              <h4>🗺️ Interactive Map</h4>
              <p style="color: #666; font-size: 14px; margin-bottom: 10px;">Visual representation of your geofence zones</p>
              <div id="geofence-map" style="height: 400px; border-radius: 8px; overflow: hidden; margin: 10px 0;"></div>
            </div>

            <!-- Quick Setup Instructions -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
              <h4>📋 Quick Setup Instructions</h4>
              
              <div style="margin: 15px 0;">
                <h5 style="color: #667eea;">📍 Step 1: Find Your Building Coordinates</h5>
                <ol style="color: #666; line-height: 1.8;">
                  <li>Open <a href="https://www.google.com/maps" target="_blank" style="color: #667eea;">Google Maps</a></li>
                  <li>Search for your building or navigate to it</li>
                  <li>Right-click on the exact building location</li>
                  <li>Click on the coordinates that appear (they'll copy to clipboard)</li>
                  <li>Paste the coordinates in the form above</li>
                </ol>
              </div>
              
              <div style="margin: 15px 0;">
                <h5 style="color: #667eea;">🎯 Step 2: Set Radius Zones</h5>
                <ul style="color: #666; line-height: 1.8;">
                  <li><strong>Zone 1 (Main Building):</strong> Tight security - usually 30-100 meters</li>
                  <li><strong>Zone 2 (Complex):</strong> Building grounds - usually 100-300 meters</li>
                  <li><strong>Zone 3 (Outer Grounds):</strong> Extended area - usually 200-500 meters</li>
                </ul>
              </div>
              
              <div style="margin: 15px 0;">
                <h5 style="color: #667eea;">✅ Step 3: Test Your Setup</h5>
                <ol style="color: #666; line-height: 1.8;">
                  <li>Use the test form above to verify coordinates</li>
                  <li>Visit the <a href="mobileScreen/explore.php" target="_blank" style="color: #667eea;">Mobile Interface</a> on your phone</li>
                  <li>Ensure location permissions are granted when prompted</li>
                </ol>
              </div>
            </div>

          </div>

        </div>
      </div>
    </main>
  </div>

  <!-- Add the button styles -->
  <style>
    .logout-section {
      display: flex;
      justify-content: center;
      padding: 15px;
      margin-top: 20px;
    }

    .drawer-button {
      padding: 10px 20px;
      border-radius: 8px;
      border: none;
      background: #1976d2;
      color: white;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
      flex: 1;
      justify-content: center;
      max-width: 200px;
    }

    .drawer-button:hover {
      background: #1565c0;
    }

    .drawer-button i {
      font-size: 1.1em;
    }

    #logoutBtn {
      background: #ef4444;
    }

    #logoutBtn:hover {
      background: #dc2626;
    }
  </style>

  <!-- Custom Logout Modal -->
  <div class="modal-overlay" id="logoutModal">
    <div class="modal-container">
      <div class="modal-title">Confirm Logout</div>
      <div class="modal-content">
        Are you sure you want to log out of your account?
      </div>
      <div class="modal-buttons">
        <button class="modal-btn modal-btn-cancel" id="cancelLogout">Cancel</button>
        <button class="modal-btn modal-btn-confirm" id="confirmLogout">Logout</button>
      </div>
    </div>
  </div>

  <script src="darkMode.js"></script>
  
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Account settings form submission
    $('#accountSettingsForm').on('submit', function(e) {
      e.preventDefault();
      
      const email = $('#email').val();
      const password = $('#password').val();
      const confirm_password = $('#confirm_password').val();
      
      // Basic validation
      if (!email) {
        $('#updateMessage').html('<div style="color: red;">Email is required</div>');
        return;
      }
      
      if (password !== confirm_password) {
        $('#updateMessage').html('<div style="color: red;">Passwords do not match</div>');
        return;
      }
      
      // AJAX request
      $.ajax({
        type: 'POST',
        url: 'systemSettings.php',
        data: {
          ajax: 'updateAccount',
          email: email,
          password: password,
          confirm_password: confirm_password
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            $('#updateMessage').html('<div style="color: green;">' + response.message + '</div>');
            // Clear password fields
            $('#password').val('');
            $('#confirm_password').val('');
          } else {
            $('#updateMessage').html('<div style="color: red;">' + response.message + '</div>');
          }
          
          // Clear message after 3 seconds
          setTimeout(function() {
            $('#updateMessage').html('');
          }, 3000);
        },
        error: function() {
          $('#updateMessage').html('<div style="color: red;">An error occurred. Please try again.</div>');
        }
      });
    });
    
    // Logout modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');
    
    // Show logout modal
    function showLogoutModal() {
      if (logoutModal) {
        logoutModal.classList.add('active');
      }
    }

    // Hide logout modal
    function hideLogoutModal() {
      if (logoutModal) {
        logoutModal.classList.remove('active');
      }
    }
    
    // Add click event listeners for logout functionality
    if (logoutBtn) {
      logoutBtn.addEventListener('click', showLogoutModal);
    }
    
    if (cancelLogout) {
      cancelLogout.addEventListener('click', hideLogoutModal);
    }
    
    if (confirmLogout) {
      confirmLogout.addEventListener('click', function() {
        window.location.href = "login.php";
      });
    }
    
    // Close modal when clicking outside
    if (logoutModal) {
      logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
          hideLogoutModal();
        }
      });
    }

    // Auto-refresh activities every 30 seconds
    setInterval(function() {
      fetch('get_activities.php')
        .then(response => response.text())
        .then(html => {
          const activityList = document.getElementById('activityList');
          if (activityList) {
            activityList.innerHTML = html;
          }
        })
        .catch(error => console.error('Error refreshing activities:', error));
    }, 30000);
    
    // Initialize geofence map if element exists
    const geofenceMapEl = document.getElementById('geofence-map');
    if (geofenceMapEl) {
      // Initialize map
      const map = L.map('geofence-map').setView([<?= $currentLat ?>, <?= $currentLng ?>], 16);
      
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
      
      <?php if (isset($testResult) && isset($_POST['test_latitude']) && isset($_POST['test_longitude'])): ?>
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
    }
  });
  </script>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

</body>
</html>