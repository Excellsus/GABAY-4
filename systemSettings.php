<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

include("connect_db.php");

// Get the current logged-in admin user from session using admin_id (set by auth_guard.php)
if (!isset($_SESSION['admin_id'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$stmt = $connect->prepare("SELECT * FROM admin WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// If admin not found, redirect to login
if (!$admin) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Store current username for comparison (used in username change validation)
$current_username = $admin['username'];

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
  
  // Validate CSRF token
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
  }
  
  try {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($username)) {
      throw new Exception("Username is required");
    }
    
    if (empty($email)) {
      throw new Exception("Email is required");
    }
    
    if (!empty($password) && $password !== $confirm_password) {
      throw new Exception("Passwords do not match");
    }
    
    // Check if username already exists (if changed)
    if ($username !== $current_username) {
      $checkStmt = $connect->prepare("SELECT id FROM admin WHERE username = ?");
      $checkStmt->execute([$username]);
      if ($checkStmt->fetch()) {
        throw new Exception("Username already exists. Please choose a different username.");
      }
    }
    
    // Update the admin record
    if (!empty($password)) {
      // Update username, email and password
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $connect->prepare("UPDATE admin SET username = ?, email = ?, password = ? WHERE username = ?");
      $stmt->execute([$username, $email, $hashedPassword, $current_username]);
    } else {
      // Update username and email only
      $stmt = $connect->prepare("UPDATE admin SET username = ?, email = ? WHERE username = ?");
      $stmt->execute([$username, $email, $current_username]);
    }
    
    // Update session username if it changed
    if ($username !== $current_username) {
      $_SESSION['username'] = $username;
    }
    
    $result = ['success' => true, 'message' => 'Account updated successfully'];
  } catch (Exception $e) {
    $result = ['success' => false, 'message' => $e->getMessage()];
  }
  
  echo json_encode($result);
  exit;
}

// AJAX endpoint for toggling geofence status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'toggleGeofence') {
  $result = ['success' => false];
  
  // Validate CSRF token
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
  }
  
  try {
    $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true' ? 1 : 0;
    
    // Update or insert geofence enabled status in database
    if (isset($connect) && $connect) {
      $name = 'default';
      // Check if record exists
      $stmt = $connect->prepare('SELECT id FROM geofences WHERE name = :name LIMIT 1');
      $stmt->execute([':name' => $name]);
      $existing = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($existing && isset($existing['id'])) {
        // Update existing record
        $upd = $connect->prepare('UPDATE geofences SET enabled = :enabled WHERE id = :id');
        $upd->execute([':enabled' => $enabled, ':id' => $existing['id']]);
      } else {
        // Insert new record with default values
        $ins = $connect->prepare('INSERT INTO geofences (name, enabled, center_lat, center_lng, radius1, radius2, radius3) VALUES (:name, :enabled, 10.6496, 122.96192, 50, 100, 150)');
        $ins->execute([':name' => $name, ':enabled' => $enabled]);
      }
      
      $result = [
        'success' => true, 
        'message' => $enabled ? 'Geofencing enabled successfully' : 'Geofencing disabled successfully',
        'enabled' => $enabled
      ];
    } else {
      throw new Exception("Database connection not available");
    }
  } catch (Exception $e) {
    $result = ['success' => false, 'message' => $e->getMessage()];
  }
  
  header('Content-Type: application/json');
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

// Read current geofence coordinates and enabled status from DB or JavaScript file
$currentLat = 10.6496;
$currentLng = 122.96192;
$currentRadius1 = 50;
$currentRadius2 = 100;
$currentRadius3 = 150;
$geofenceEnabled = true; // Default to enabled for safety

$jsFile = 'mobileScreen/js/leafletGeofencing.js';
// Prefer DB geofence if available
try {
    if (isset($connect) && $connect) {
        $stmt = $connect->query("SELECT center_lat, center_lng, radius1, radius2, radius3, enabled FROM geofences WHERE name = 'default' LIMIT 1");
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($g) {
            $currentLat = floatval($g['center_lat']);
            $currentLng = floatval($g['center_lng']);
            $currentRadius1 = intval($g['radius1']);
            $currentRadius2 = intval($g['radius2']);
            $currentRadius3 = intval($g['radius3']);
            $geofenceEnabled = isset($g['enabled']) ? (bool)$g['enabled'] : true;
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
  <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
  <title>GABAY Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="systemSetting.css" />
  <link rel="stylesheet" href="assets/css/system-fonts.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="mobileNav.css" />
  <script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
  <script src="auth_helper.js"></script>
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
              <input type="text" name="username" id="username" value="<?= htmlspecialchars($admin['username']) ?>" required />
            </div>
            <div class="gabay-form-group">
              <label>Email</label>
              <input type="email" name="email" id="email" value="<?= htmlspecialchars($admin['email']) ?>" required />
            </div>
            <div class="gabay-form-group">
              <label>Change Password</label>
              <div style="position: relative;">
                <input type="password" name="password" id="password" placeholder="Enter new password" style="padding-right: 45px;" />
                <button type="button" class="password-toggle" onclick="togglePassword('password', this)" style="position: absolute; right: 12px; top: 60%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #666; z-index: 10;">
                  <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                  </svg>
                </button>
              </div>
            </div>
            <div class="gabay-form-group">
              <label>Confirm Password</label>
              <div style="position: relative;">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" style="padding-right: 45px;" />
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 12px; top: 60%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #666; z-index: 10;">
                  <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                  </svg>
                </button>
              </div>
              <div id="passwordMatchMessage" style="margin-top: 8px; font-size: 0.9em; display: none;"></div>
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

            <!-- Geofence Enable/Disable Toggle -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #e0e0e0;">
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                  <h4 style="margin: 0 0 5px 0; color: #333;">
                    <i class="fa fa-shield" style="margin-right: 8px;"></i>Geofencing System Status
                  </h4>
                  <p style="margin: 0; color: #666; font-size: 0.9em;">
                    Control whether geofencing is enforced for mobile visitors
                  </p>
                </div>
                <label class="geofence-toggle-switch">
                  <input type="checkbox" id="geofenceEnabled" <?= isset($geofenceEnabled) && $geofenceEnabled ? 'checked' : '' ?>>
                  <span class="geofence-toggle-slider"></span>
                </label>
              </div>
              <div id="geofenceStatusMessage" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none;"></div>
            </div>

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

            <!-- GPS Coordinate Getter Section -->
            <div class="gabay-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
              
              <form method="POST" class="gabay-form" id="gpsCoordinateForm">
                <h4 style="margin-top: 0;">📍 Get GPS Coordinates</h4>
                <p style="color: #666; font-size: 14px;">Get your current GPS location and test if it's inside your geofence zones.</p>
                
                <input type="hidden" name="action" value="test_location">
                
                <div class="gabay-form-group">
                  <label>Current Latitude:</label>
                  <input type="number" 
                         name="test_latitude" 
                         id="gpsLatitude" 
                         step="0.000001" 
                         placeholder="Click 'Get My Location' to fetch" 
                         readonly
                         required>
                </div>
                
                <div class="gabay-form-group">
                  <label>Current Longitude:</label>
                  <input type="number" 
                         name="test_longitude" 
                         id="gpsLongitude" 
                         step="0.000001" 
                         placeholder="Click 'Get My Location' to fetch" 
                         readonly
                         required>
                </div>

                <!-- GPS Accuracy and Status Display -->
                <div id="gpsStatus" style="display: none; margin: 10px 0; padding: 10px; border-radius: 6px; font-size: 13px;">
                  <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                    <i class="fas fa-circle-notch fa-spin" id="gpsLoadingIcon"></i>
                    <span id="gpsStatusText">Getting location...</span>
                  </div>
                  <div id="gpsAccuracy" style="font-size: 12px; color: #666;"></div>
                </div>
                
                <div class="gabay-button-wrapper">
                  <button type="button" 
                          id="getLocationBtn" 
                          class="gabay-btn" 
                          style="background: #667eea;">
                    <i class="fas fa-location-arrow"></i> Get My Location
                  </button>
                </div>

                <!-- Help text for GPS accuracy -->
                <div style="margin-top: 10px; padding: 8px; background: #f0f4f8; border-radius: 4px; font-size: 12px; color: #666;">
                  <strong>💡 Tips for accuracy:</strong>
                  <ul style="margin: 5px 0; padding-left: 20px;">
                    <li>Enable high accuracy mode in browser</li>
                    <li>Use outdoors or near windows</li>
                    <li>Wait for accuracy &lt; 20m for best results</li>
                  </ul>
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
    /* Geofence Toggle Switch Styles */
    .geofence-toggle-switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 34px;
    }

    .geofence-toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .geofence-toggle-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: 0.4s;
      border-radius: 34px;
    }

    .geofence-toggle-slider:before {
      position: absolute;
      content: "";
      height: 26px;
      width: 26px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: 0.4s;
      border-radius: 50%;
    }

    .geofence-toggle-switch input:checked + .geofence-toggle-slider {
      background-color: #4CAF50;
    }

    .geofence-toggle-switch input:focus + .geofence-toggle-slider {
      box-shadow: 0 0 1px #4CAF50;
    }

    .geofence-toggle-switch input:checked + .geofence-toggle-slider:before {
      transform: translateX(26px);
    }

    .geofence-toggle-switch input:disabled + .geofence-toggle-slider {
      opacity: 0.5;
      cursor: not-allowed;
    }

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
  // Password visibility toggle function
  function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const eyeOpen = button.querySelector('.eye-open');
    const eyeClosed = button.querySelector('.eye-closed');
    
    if (input.type === 'password') {
      input.type = 'text';
      eyeOpen.style.display = 'none';
      eyeClosed.style.display = 'block';
    } else {
      input.type = 'password';
      eyeOpen.style.display = 'block';
      eyeClosed.style.display = 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Real-time password matching validation
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordMatchMessage = document.getElementById('passwordMatchMessage');
    
    function checkPasswordMatch() {
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;
      
      // Only show message if confirm password field has content
      if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
          passwordMatchMessage.style.display = 'block';
          passwordMatchMessage.style.color = '#2e7d32';
          passwordMatchMessage.innerHTML = '✓ Passwords match';
          confirmPasswordInput.style.borderColor = '#4CAF50';
        } else {
          passwordMatchMessage.style.display = 'block';
          passwordMatchMessage.style.color = '#c62828';
          passwordMatchMessage.innerHTML = '✗ Passwords do not match';
          confirmPasswordInput.style.borderColor = '#f44336';
        }
      } else {
        passwordMatchMessage.style.display = 'none';
        confirmPasswordInput.style.borderColor = '';
      }
    }
    
    // Add event listeners for real-time validation
    if (passwordInput && confirmPasswordInput) {
      passwordInput.addEventListener('input', checkPasswordMatch);
      confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    
    // Account settings form submission
    $('#accountSettingsForm').on('submit', function(e) {
      e.preventDefault();
      
      const username = $('#username').val();
      const email = $('#email').val();
      const password = $('#password').val();
      const confirm_password = $('#confirm_password').val();
      
      // Basic validation
      if (!username) {
        $('#updateMessage').html('<div style="color: red;">Username is required</div>');
        return;
      }
      
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
          username: username,
          email: email,
          password: password,
          confirm_password: confirm_password,
          csrf_token: '<?php echo csrfToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            $('#updateMessage').html('<div style="color: green;">' + response.message + '</div>');
            // Clear password fields
            $('#password').val('');
            $('#confirm_password').val('');
            // Clear password match message
            $('#passwordMatchMessage').hide();
            $('#confirm_password').css('borderColor', '');
          } else {
            $('#updateMessage').html('<div style="color: red;">' + response.message + '</div>');
          }
          
          // Clear message after 5 seconds
          setTimeout(function() {
            $('#updateMessage').html('');
          }, 5000);
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
        window.location.href = "logout.php";
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

    // ===== GEOFENCE TOGGLE FUNCTIONALITY =====
    const geofenceToggle = document.getElementById('geofenceEnabled');
    const geofenceStatusMessage = document.getElementById('geofenceStatusMessage');
    
    if (geofenceToggle) {
      geofenceToggle.addEventListener('change', function() {
        const enabled = this.checked;
        
        // Disable toggle while processing
        geofenceToggle.disabled = true;
        
        // Show loading message
        geofenceStatusMessage.style.display = 'block';
        geofenceStatusMessage.style.background = '#e3f2fd';
        geofenceStatusMessage.style.color = '#1976d2';
        geofenceStatusMessage.style.border = '1px solid #2196F3';
        geofenceStatusMessage.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i> ' + (enabled ? 'Enabling geofencing...' : 'Disabling geofencing...');
        
        // AJAX request to update geofence status (include CSRF token)
        $.ajax({
          type: 'POST',
          url: 'systemSettings.php',
          data: {
            ajax: 'toggleGeofence',
            enabled: enabled ? 'true' : 'false',
            csrf_token: (window.CSRF_TOKEN || $('meta[name="csrf-token"]').attr('content') || '')
          },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              // Show success message
              geofenceStatusMessage.style.background = '#e8f5e9';
              geofenceStatusMessage.style.color = '#2e7d32';
              geofenceStatusMessage.style.border = '1px solid #4CAF50';
              geofenceStatusMessage.innerHTML = '<i class="fa fa-check-circle"></i> ' + response.message;
              
              // Hide message after 3 seconds
              setTimeout(function() {
                geofenceStatusMessage.style.display = 'none';
              }, 3000);
            } else {
              // Show error message
              geofenceStatusMessage.style.background = '#ffebee';
              geofenceStatusMessage.style.color = '#c62828';
              geofenceStatusMessage.style.border = '1px solid #f44336';
              geofenceStatusMessage.innerHTML = '<i class="fa fa-exclamation-circle"></i> Error: ' + response.message;
              
              // Revert toggle state
              geofenceToggle.checked = !enabled;
            }
          },
          error: function() {
            // Show error message
            geofenceStatusMessage.style.background = '#ffebee';
            geofenceStatusMessage.style.color = '#c62828';
            geofenceStatusMessage.style.border = '1px solid #f44336';
            geofenceStatusMessage.innerHTML = '<i class="fa fa-exclamation-circle"></i> An error occurred. Please try again.';
            
            // Revert toggle state
            geofenceToggle.checked = !enabled;
          },
          complete: function() {
            // Re-enable toggle
            geofenceToggle.disabled = false;
          }
        });
      });
    }

    // ===== GPS COORDINATE GETTER FUNCTIONALITY =====
    
    // Get references to GPS form elements
    const getLocationBtn = document.getElementById('getLocationBtn');
    const gpsLatInput = document.getElementById('gpsLatitude');
    const gpsLngInput = document.getElementById('gpsLongitude');
    const gpsStatus = document.getElementById('gpsStatus');
    const gpsStatusText = document.getElementById('gpsStatusText');
    const gpsAccuracy = document.getElementById('gpsAccuracy');
    const gpsLoadingIcon = document.getElementById('gpsLoadingIcon');
    
    // Store the watchPosition ID for cleanup
    let watchId = null;
    let bestAccuracy = Infinity;
    let locationTimeout = null;

    /**
     * Update GPS status display with appropriate styling
     * @param {string} message - Status message to display
     * @param {string} type - Status type: 'loading', 'success', 'error', 'info'
     */
    function updateGPSStatus(message, type = 'info') {
      if (!gpsStatus || !gpsStatusText || !gpsLoadingIcon) return;
      
      gpsStatus.style.display = 'block';
      gpsStatusText.textContent = message;
      
      // Update styling based on status type
      switch(type) {
        case 'loading':
          gpsStatus.style.background = '#e3f2fd';
          gpsStatus.style.border = '1px solid #2196F3';
          gpsStatus.style.color = '#1976d2';
          gpsLoadingIcon.style.display = 'inline-block';
          gpsLoadingIcon.className = 'fas fa-circle-notch fa-spin';
          break;
        case 'success':
          gpsStatus.style.background = '#e8f5e9';
          gpsStatus.style.border = '1px solid #4CAF50';
          gpsStatus.style.color = '#2e7d32';
          gpsLoadingIcon.style.display = 'inline-block';
          gpsLoadingIcon.className = 'fas fa-check-circle';
          break;
        case 'error':
          gpsStatus.style.background = '#ffebee';
          gpsStatus.style.border = '1px solid #f44336';
          gpsStatus.style.color = '#c62828';
          gpsLoadingIcon.style.display = 'inline-block';
          gpsLoadingIcon.className = 'fas fa-exclamation-circle';
          break;
        case 'info':
          gpsStatus.style.background = '#fff3e0';
          gpsStatus.style.border = '1px solid #ff9800';
          gpsStatus.style.color = '#e65100';
          gpsLoadingIcon.style.display = 'inline-block';
          gpsLoadingIcon.className = 'fas fa-info-circle';
          break;
      }
    }

    /**
     * Update GPS accuracy display with visual indicators
     * @param {number} accuracy - Accuracy in meters
     */
    function updateAccuracyDisplay(accuracy) {
      if (!gpsAccuracy) return;
      
      let qualityText = '';
      let qualityColor = '';
      
      // Categorize accuracy quality
      if (accuracy <= 10) {
        qualityText = 'Excellent';
        qualityColor = '#4CAF50';
      } else if (accuracy <= 20) {
        qualityText = 'Good';
        qualityColor = '#8BC34A';
      } else if (accuracy <= 50) {
        qualityText = 'Fair';
        qualityColor = '#FFC107';
      } else if (accuracy <= 100) {
        qualityText = 'Poor';
        qualityColor = '#FF9800';
      } else {
        qualityText = 'Very Poor';
        qualityColor = '#f44336';
      }
      
      gpsAccuracy.innerHTML = `
        <span style="color: ${qualityColor}; font-weight: bold;">●</span>
        Accuracy: <strong>${accuracy.toFixed(1)}m</strong> (${qualityText})
      `;
    }

    /**
     * Handle successful geolocation retrieval
     * @param {GeolocationPosition} position - Position object from Geolocation API
     */
    function handleLocationSuccess(position) {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      const accuracy = position.coords.accuracy;
      
      console.log('GPS Location received:', { lat, lng, accuracy });
      
      // Update accuracy display
      updateAccuracyDisplay(accuracy);
      
      // Only update coordinates if this is more accurate than previous reading
      // or if it's the first reading
      if (accuracy < bestAccuracy || bestAccuracy === Infinity) {
        bestAccuracy = accuracy;
        
        // Populate the input fields with 5 decimal places for display (≈ 1.1m accuracy)
        // This provides clean display while maintaining sufficient precision
        gpsLatInput.value = lat.toFixed(5);
        gpsLngInput.value = lng.toFixed(5);
        
        // Update status based on accuracy quality
        if (accuracy <= 20) {
          updateGPSStatus('✓ Location acquired with high accuracy!', 'success');
          
          // Stop watching for better accuracy after 3 seconds if accuracy is good
          if (locationTimeout) clearTimeout(locationTimeout);
          locationTimeout = setTimeout(() => {
            stopWatchingLocation();
          }, 3000);
          
        } else if (accuracy <= 50) {
          updateGPSStatus('Location acquired. Improving accuracy...', 'info');
        } else {
          updateGPSStatus('Location acquired but accuracy is low. Refining...', 'info');
        }
      }
    }

    /**
     * Handle geolocation errors with detailed user-friendly messages
     * @param {GeolocationPositionError} error - Error object from Geolocation API
     */
    function handleLocationError(error) {
      console.error('GPS Error:', error);
      
      let errorMessage = '';
      
      switch(error.code) {
        case error.PERMISSION_DENIED:
          errorMessage = '❌ Location access denied. Please enable location permissions in your browser settings.';
          break;
        case error.POSITION_UNAVAILABLE:
          errorMessage = '❌ Location information unavailable. Please check your device GPS settings.';
          break;
        case error.TIMEOUT:
          errorMessage = '⏱️ Location request timed out. Please try again.';
          break;
        default:
          errorMessage = '❌ An unknown error occurred while getting location.';
      }
      
      updateGPSStatus(errorMessage, 'error');
      
      // Reset button state
      if (getLocationBtn) {
        getLocationBtn.disabled = false;
        getLocationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get My Location';
      }
      
      // Stop watching on error
      stopWatchingLocation();
    }

    /**
     * Stop watching user's location
     */
    function stopWatchingLocation() {
      if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
        console.log('Stopped watching location');
      }
      
      // Reset button state
      if (getLocationBtn) {
        getLocationBtn.disabled = false;
        getLocationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get My Location';
      }
    }

    /**
     * Start getting user's GPS coordinates with high accuracy
     */
    function getGPSCoordinates() {
      // Check if Geolocation API is supported
      if (!navigator.geolocation) {
        updateGPSStatus('❌ Geolocation is not supported by your browser.', 'error');
        return;
      }
      
      // Reset state
      bestAccuracy = Infinity;
      if (locationTimeout) clearTimeout(locationTimeout);
      
      // Update UI to show loading state
      updateGPSStatus('📡 Acquiring GPS signal...', 'loading');
      
      if (getLocationBtn) {
        getLocationBtn.disabled = true;
        getLocationBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Getting Location...';
      }
      
      // Clear previous values
      if (gpsLatInput) gpsLatInput.value = '';
      if (gpsLngInput) gpsLngInput.value = '';
      
      // Geolocation options for maximum accuracy
      const options = {
        enableHighAccuracy: true,    // Request high accuracy (uses GPS)
        timeout: 30000,              // Wait up to 30 seconds
        maximumAge: 0                // Don't use cached position
      };
      
      // Use watchPosition for continuous updates to get best accuracy
      // This is better than getCurrentPosition for accuracy improvement
      watchId = navigator.geolocation.watchPosition(
        handleLocationSuccess,
        handleLocationError,
        options
      );
      
      // Fallback: Stop watching after 30 seconds even if accuracy isn't perfect
      setTimeout(() => {
        if (watchId !== null) {
          stopWatchingLocation();
          
          // If we have any coordinates at all, consider it a success
          if (gpsLatInput && gpsLatInput.value) {
            updateGPSStatus('Location acquired. You can now test.', 'success');
          } else {
            updateGPSStatus('⏱️ Could not get location within time limit. Please try again.', 'error');
          }
        }
      }, 30000);
    }

    // Add click event listener to "Get My Location" button
    if (getLocationBtn) {
      getLocationBtn.addEventListener('click', getGPSCoordinates);
    }

    // Cleanup: Stop watching location when user leaves the page
    window.addEventListener('beforeunload', stopWatchingLocation);

    // ===== END GPS COORDINATE GETTER FUNCTIONALITY =====

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