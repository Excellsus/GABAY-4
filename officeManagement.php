<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

include 'connect_db.php';

// Handle edit selection
$editData = ['id' => '', 'name' => '', 'details' => '', 'contact' => '', 'location' => '', 'services' => '']; // Added services
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['office_id']) && !isset($_POST['ajax'])) {
  $stmt = $connect->prepare("SELECT id, name, details, contact, location, services FROM offices WHERE id = ?"); // Added services
  $stmt->execute([$_POST['office_id']]); // Fetch services
  $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// For edit form: fetch latest image for office if exists
if (!empty($editData['id'])) {
  $stmt = $connect->prepare("SELECT image_path FROM office_image WHERE office_id = ? ORDER BY uploaded_at DESC, id DESC LIMIT 1");
  $stmt->execute([$editData['id']]);
  $editData['image'] = $stmt->fetchColumn();
}

// 1. Load office hours for edit form
$officeHours = [
  'Monday' => ['open' => '', 'close' => ''],
  'Tuesday' => ['open' => '', 'close' => ''],
  'Wednesday' => ['open' => '', 'close' => ''],
  'Thursday' => ['open' => '', 'close' => ''],
  'Friday' => ['open' => '', 'close' => ''],
  'Saturday' => ['open' => '', 'close' => ''],
  'Sunday' => ['open' => '', 'close' => ''],
];
if (!empty($editData['id'])) {
  $stmt = $connect->prepare("SELECT day_of_week, open_time, close_time FROM office_hours WHERE office_id = ?");
  $stmt->execute([$editData['id']]);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $officeHours[$row['day_of_week']] = [
      'open' => $row['open_time'],
      'close' => $row['close_time']
    ];
  }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['location']) && !isset($_POST['ajax'])) {
  $office_id = $_POST['office_id'] ?? '';
  $name = $_POST['name'];
  $details = $_POST['details'];
  $contact = $_POST['contact'];
  $location = $_POST['location'];
  $services = $_POST['services'];
  $imageFileName = '';

  // Check if another office already uses this location
  $query = "SELECT id FROM offices WHERE location = ?" . ($office_id ? " AND id != ?" : "");
  $params = $office_id ? [$location, $office_id] : [$location];
  $stmt = $connect->prepare($query);
  $stmt->execute($params);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($existing) {
    echo "<script>alert('Room is already occupied.');window.location='officeManagement.php';</script>";
    exit;
  }

  if ($office_id) {
    $stmt = $connect->prepare("UPDATE offices SET name=?, details=?, contact=?, location=?, services=? WHERE id=?");
    $stmt->execute([$name, $details, $contact, $location, $services, $office_id]);
    
    // Log update activity with office_id
    $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
    $activityStmt->execute(['office', "Office '$name' was updated", $office_id]);
  } else {
    $stmt = $connect->prepare("INSERT INTO offices (name, details, contact, location, services) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $details, $contact, $location, $services]);
    $office_id = $connect->lastInsertId();
    
    // Log new office activity with office_id
    $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
    $activityStmt->execute(['office', "New office '$name' added", $office_id]);
    
    // --- QR CODE GENERATION ---
    require_once __DIR__ . '/phpqrcode/qrlib.php';
    // Set your base URL (should match generate_qrcodes.php)
    $baseUrl = "https://192.168.254.164gabay/mobileScreen/";
    $qrDir = __DIR__ . '/qrcodes/';
    if (!file_exists($qrDir)) { mkdir($qrDir, 0777, true); }
    $qrData = $baseUrl . "explore.php?office_id=" . $office_id;
    // Sanitize filename (copied from generate_qrcodes.php)
    function sanitize_filename($string) {
      $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
      $string = preg_replace('/[\s_]+/', '_', $string);
      $string = trim($string, '_');
      if (empty($string)) { return 'office'; }
      return $string;
    }
    $sanitizedOfficeName = sanitize_filename($name);
    $filename = $qrDir . $sanitizedOfficeName . "_" . $office_id . ".png";
    \QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);
    $qrImage = basename($filename);
    // Insert into qrcode_info
    $check = $connect->prepare("SELECT id FROM qrcode_info WHERE office_id = ?");
    $check->execute([$office_id]);
    $existingQrInfo = $check->fetch(PDO::FETCH_ASSOC);
    if ($existingQrInfo) {
      $updateStmt = $connect->prepare("UPDATE qrcode_info SET qr_code_data = ?, qr_code_image = ? WHERE office_id = ?");
      $updateStmt->execute([$qrData, $qrImage, $office_id]);
    } else {
      $insertStmt = $connect->prepare("INSERT INTO qrcode_info (office_id, qr_code_data, qr_code_image) VALUES (?, ?, ?)");
      $insertStmt->execute([$office_id, $qrData, $qrImage]);
    }
    // --- END QR CODE GENERATION ---
  }

  // Handle image upload if present
  if (isset($_FILES['office_image']) && $_FILES['office_image']['error'] === UPLOAD_ERR_OK && $office_id) {
    $uploadDir = __DIR__ . '/office_images/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }
    $tmpName = $_FILES['office_image']['tmp_name'];
    $origName = basename($_FILES['office_image']['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed)) {
      $imageFileName = uniqid('office_', true) . '.' . $ext;
      $destPath = $uploadDir . $imageFileName;
      if (move_uploaded_file($tmpName, $destPath)) {
        // Insert new image record
        $stmt = $connect->prepare("INSERT INTO office_image (office_id, image_path) VALUES (?, ?)");
        $stmt->execute([$office_id, $imageFileName]);
      }
    }
  }

  // Save office hours
  if ($office_id && isset($_POST['office_hours']) && is_array($_POST['office_hours'])) {
    // Delete old hours
    $connect->prepare("DELETE FROM office_hours WHERE office_id = ?")->execute([$office_id]);
    // Insert new hours
    $insertHours = $connect->prepare("INSERT INTO office_hours (office_id, day_of_week, open_time, close_time) VALUES (?, ?, ?, ?)");
    foreach ($_POST['office_hours'] as $day => $times) {
      $open = $times['open'] ?? null;
      $close = $times['close'] ?? null;
      if ($open && $close) {
        $insertHours->execute([$office_id, $day, $open, $close]);
      }
    }
  }

  header("Location: officeManagement.php");
  exit;
}

// AJAX endpoint for getting office details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'getOffice') {
  $stmt = $connect->prepare("SELECT id, name, details, contact, location, services FROM offices WHERE id = ?"); // Added services
  $stmt->execute([$_POST['office_id']]);
  $officeData = $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode($officeData);
  exit;
}

// AJAX endpoint for checking room occupancy in real-time
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'checkRoomOccupancy') {
  $location = $_POST['location'] ?? '';
  $office_id = $_POST['office_id'] ?? ''; // Current office being edited (if any)
  
  if (empty($location)) {
    echo json_encode(['occupied' => false, 'officeName' => '']);
    exit;
  }
  
  // Check if another office already uses this location
  $query = "SELECT id, name FROM offices WHERE location = ?" . ($office_id ? " AND id != ?" : "");
  $params = $office_id ? [$location, $office_id] : [$location];
  $stmt = $connect->prepare($query);
  $stmt->execute($params);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if ($existing) {
    echo json_encode(['occupied' => true, 'officeName' => $existing['name'], 'officeId' => $existing['id']]);
  } else {
    echo json_encode(['occupied' => false, 'officeName' => '']);
  }
  exit;
}

// AJAX endpoint for saving office data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'saveOffice') {
  // Validate CSRF token
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
  }
  
  $office_id = $_POST['office_id'] ?? '';
  $name = $_POST['name'];
  $details = $_POST['details'];
  $contact = $_POST['contact'];
  $location = $_POST['location'];
  $services = $_POST['services'];
  $result = ['success' => false];
  $imageFileName = '';

  // Check if another office already uses this location
  $query = "SELECT id FROM offices WHERE location = ?" . ($office_id ? " AND id != ?" : "");
  $params = $office_id ? [$location, $office_id] : [$location];
  $stmt = $connect->prepare($query);
  $stmt->execute($params);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($existing) {
    $result = ['success' => false, 'message' => 'Room is already occupied.'];
    echo json_encode($result);
    exit;
  }

  try {
    if ($office_id) {
      $stmt = $connect->prepare("UPDATE offices SET name=?, details=?, contact=?, location=?, services=? WHERE id=?");
      $stmt->execute([$name, $details, $contact, $location, $services, $office_id]);
      
      // Log update activity with office_id
      $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
      $activityStmt->execute(['office', "Office '$name' was updated", $office_id]);
    } else {
      $stmt = $connect->prepare("INSERT INTO offices (name, details, contact, location, services) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$name, $details, $contact, $location, $services]);
      $office_id = $connect->lastInsertId();
      
      // Log new office activity with office_id
      $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
      $activityStmt->execute(['office', "New office '$name' added", $office_id]);
      
      // --- QR CODE GENERATION (AJAX) ---
      require_once __DIR__ . '/phpqrcode/qrlib.php';
      $baseUrl = "https://192.168.254.164/gabay/mobileScreen/";
      $qrDir = __DIR__ . '/qrcodes/';
      if (!file_exists($qrDir)) { mkdir($qrDir, 0777, true); }
      $qrData = $baseUrl . "explore.php?office_id=" . $office_id;
      function sanitize_filename_ajax($string) {
        $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
        $string = preg_replace('/[\s_]+/', '_', $string);
        $string = trim($string, '_');
        if (empty($string)) { return 'office'; }
        return $string;
      }
      $sanitizedOfficeName = sanitize_filename_ajax($name);
      $filename = $qrDir . $sanitizedOfficeName . "_" . $office_id . ".png";
      \QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);
      $qrImage = basename($filename);
      $check = $connect->prepare("SELECT id FROM qrcode_info WHERE office_id = ?");
      $check->execute([$office_id]);
      $existingQrInfo = $check->fetch(PDO::FETCH_ASSOC);
      if ($existingQrInfo) {
        $updateStmt = $connect->prepare("UPDATE qrcode_info SET qr_code_data = ?, qr_code_image = ? WHERE office_id = ?");
        $updateStmt->execute([$qrData, $qrImage, $office_id]);
      } else {
        $insertStmt = $connect->prepare("INSERT INTO qrcode_info (office_id, qr_code_data, qr_code_image) VALUES (?, ?, ?)");
        $insertStmt->execute([$office_id, $qrData, $qrImage]);
      }
      // --- END QR CODE GENERATION (AJAX) ---
    }
    // Handle image upload if present (AJAX with base64)
    if (!empty($_POST['office_image_base64']) && $office_id) {
      $base64 = $_POST['office_image_base64'];
      if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
        $base64 = substr($base64, strpos($base64, ',') + 1);
        $ext = strtolower($type[1]);
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
          $imageFileName = uniqid('office_', true) . '.' . $ext;
          $uploadDir = __DIR__ . '/office_images/';
          if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
          }
          $filePath = $uploadDir . $imageFileName;
          if (file_put_contents($filePath, base64_decode($base64))) {
            $stmt = $connect->prepare("INSERT INTO office_image (office_id, image_path) VALUES (?, ?)");
            $stmt->execute([$office_id, $imageFileName]);
          }
        }
      }
    }
    $result = ['success' => true, 'message' => 'Office saved successfully', 'office_id' => $office_id];
  } catch (Exception $e) {
    $result = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
  }
  echo json_encode($result);
  exit;
}

// AJAX endpoint for deleting office
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'deleteOffice') {
  // Validate CSRF token
  if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
  }
  
  $office_id = $_POST['office_id'] ?? '';
  $result = ['success' => false];
  if ($office_id) {
    try {
      // Get office name before deletion
      $nameStmt = $connect->prepare("SELECT name FROM offices WHERE id = ?");
      $nameStmt->execute([$office_id]);
      $officeName = $nameStmt->fetchColumn();

      // Log delete activity BEFORE deleting the office
      if ($officeName) {
        $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), NULL)");
        $activityStmt->execute(['office', "Office '$officeName' was deleted"]);
      }

      // Delete QR code image if it exists
      $qrImagePath = __DIR__ . "/qrcodes/office_{$office_id}.png";
      if (file_exists($qrImagePath)) {
        @unlink($qrImagePath);
      }
      
      // Delete the office
      $stmt = $connect->prepare('DELETE FROM offices WHERE id = ?');
      $stmt->execute([$office_id]);
      
      $result['success'] = true;
    } catch (PDOException $e) {
      if ($e->getCode() == '23000' && strpos($e->getMessage(), '1451') !== false) {
        $result['message'] = 'Cannot delete this office because it is referenced in scan logs or other records.';
      } else {
        $result['message'] = 'Error: ' . $e->getMessage();
      }
    } catch (Exception $e) {
      $result['message'] = 'Error: ' . $e->getMessage();
    }
  } else {
    $result['message'] = 'Invalid office ID.';
  }
  echo json_encode($result);
  exit;
}

// AJAX endpoint for saving office hours
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'saveOfficeHours') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $office_id = $_POST['edit_hours_office_id'] ?? '';
    $result = ['success' => false];

    if ($office_id) {
        // Remove old hours
        $connect->prepare("DELETE FROM office_hours WHERE office_id = ?")->execute([$office_id]);
        // Insert new hours
        $insertHours = $connect->prepare("INSERT INTO office_hours (office_id, day_of_week, open_time, close_time) VALUES (?, ?, ?, ?)");
        if (isset($_POST['office_hours']) && is_array($_POST['office_hours'])) {
            foreach ($_POST['office_hours'] as $day => $times) {
                $open = $times['open'] ?? null;
                $close = $times['close'] ?? null;
                if ($open && $close) {
                    $insertHours->execute([$office_id, $day, $open, $close]);
                }
            }
        }
        $result['success'] = true;
    } else {
        $result['message'] = 'Invalid office ID.';
    }
    echo json_encode($result);
    exit;
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
    <link rel="stylesheet" href="officeManagement.css" />
  <link rel="stylesheet" href="assets/css/system-fonts.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <!-- Updated Font Awesome to version 6.5.2 for modern icons and styling -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="./mobileNav.js"></script>
    <link rel="stylesheet" href="mobileNav.css" />
    <script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
    <script src="auth_helper.js"></script>
      
  <style>
   /* Modal Dialog Styles */
.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s, visibility 0.3s;
}

.modal-overlay.active {
  opacity: 1;
  visibility: visible;
  display: flex;
}

.modal-dialog {
  background-color: white;
  padding: 30px;
  border-radius: 10px;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  transform: translateY(-20px);
  transition: transform 0.3s;
}

.modal-overlay.active .modal-dialog {
  transform: translateY(0);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 15px;
  color: #333;
}

.modal-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #666;
}

.modal-body {
  margin-bottom: 20px;
  color: #555;
  line-height: 1.5;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn {
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #4a5568;
}

.btn-secondary:hover {
  background-color: #cbd5e0;
}

.btn-primary {
  background-color: #e53e3e;
  color: white;
}

.btn-primary:hover {
  background-color: #c53030;
}
.floor-btn-modal {
    padding: 4px 12px;
    border: 1px solid #04aa6d;
    background: white;
    color: #04aa6d;
    border-radius: 12px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}
.floor-btn-modal.active {
    background: #04aa6d;
    color: white;
}

/* Toggle Switch Styles */
.toggle-switch {
  position: relative;
  display: inline-block;
}

.toggle-switch input[type="checkbox"] {
  display: none;
}

.toggle-label {
  display: block;
  width: 48px;
  height: 24px;
  background-color: #ccc;
  border-radius: 24px;
  cursor: pointer;
  transition: background-color 0.3s;
  position: relative;
}

.toggle-slider {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background-color: white;
  border-radius: 50%;
  transition: transform 0.3s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.toggle-switch input[type="checkbox"]:checked + .toggle-label {
  background-color: #2e7d32;
}

.toggle-switch input[type="checkbox"]:checked + .toggle-label .toggle-slider {
  transform: translateX(24px);
}

.toggle-switch input[type="checkbox"]:disabled + .toggle-label {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Door QR List Styles */
.door-qr-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  margin-bottom: 10px;
}

.door-qr-info {
  flex: 1;
}

.door-qr-name {
  font-weight: 600;
  color: #333;
  margin-bottom: 4px;
}

.door-qr-status {
  font-size: 0.85em;
  color: #666;
}

.door-qr-status.active {
  color: #2e7d32;
}

.door-qr-status.inactive {
  color: #d32f2f;
}

.door-qr-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.door-qr-toggle {
  margin-right: 10px;
}

.door-qr-btn {
  padding: 6px 12px;
  border: 1px solid #04aa6d;
  background: white;
  color: #04aa6d;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: all 0.2s ease;
}

.door-qr-btn:hover {
  background: #04aa6d;
  color: white;
}

.door-qr-btn.download {
  background: #04aa6d;
  color: white;
}

.door-qr-btn.download:hover {
  background: #038f5a;
}

.door-qr-btn.delete {
  border-color: #d32f2f;
  color: #d32f2f;
}

.door-qr-btn.delete:hover {
  background: #d32f2f;
  color: white;
}

.door-qr-empty {
  text-align: center;
  padding: 30px;
  color: #666;
}

/* Action Button Tooltip Styles */
.action-btn-wrapper {
  position: relative;
  display: inline-block;
}

.action-tooltip {
  position: fixed; /* Changed from absolute to fixed to avoid clipping */
  background-color: #2d3748;
  color: white;
  padding: 6px 10px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s ease;
  pointer-events: none;
  z-index: 10000; /* Higher z-index to ensure visibility */
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.action-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #2d3748;
}

.action-btn-wrapper:hover .action-tooltip {
  opacity: 1;
  visibility: visible;
}

/* Search and Filter Styles */
.office-list-controls {
  display: flex;
  gap: 10px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.search-box input:focus,
.floor-filter select:focus {
  outline: none;
  border-color: #04aa6d;
  box-shadow: 0 0 0 3px rgba(4, 170, 109, 0.1);
}

.search-box input::placeholder {
  color: #a0aec0;
}

/* Office item transition for filter animations */
.office-item {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.office-item[style*="display: none"] {
  opacity: 0;
  transform: scale(0.98);
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
    <a href="officeManagement.php" class="active">Office Management</a>
    <a href="floorPlan.php">Floor Plans</a>
    <a href="visitorFeedback.php">Visitor Feedback</a>
    <a href="systemSettings.php">System Settings</a>
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
          <li><a href="officeManagement.php" class="active">Office Management</a></li>
          <li><a href="floorPlan.php">Floor Plans</a></li>
          <li><a href="visitorFeedback.php">Visitor Feedback</a></li>
          <li><a href="systemSettings.php">System Settings</a></li>
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
      <header class="header">
        <div>
          <h2>Office Management</h2>
          <p>Manage office rooms and their details.</p>
        </div>
      </header>

      <!-- Content Grid -->
      <div class="content-grid">
        <!-- Form Card -->
        <div class="office-list-card">
          <div class="office-list-header">
              <h3 class="office-list-title">Office List</h3>
              
              <!-- Search and Filter Controls -->
              <div class="office-list-controls" style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                <!-- Search Box -->
                <div class="search-box" style="flex: 1; min-width: 200px;">
                  <input type="text" id="office-search" placeholder="Search offices..." 
                         style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                
                <!-- Floor Filter -->
                <div class="floor-filter" style="min-width: 150px;">
                  <select id="floor-filter" 
                          style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; cursor: pointer;">
                    <option value="all">All Floors</option>
                    <option value="1">Floor 1</option>
                    <option value="2">Floor 2</option>
                    <option value="3">Floor 3</option>
                    <option value="unassigned">Unassigned</option>
                  </select>
                </div>
              </div>
          </div>
          <div class="office-list-content" id="office-list-content">
          <?php 
              if (!isset($connect)) { die('Database connection not established.'); }
              
              $stmt = $connect->query("SELECT * FROM offices ORDER BY name ASC");
              
              // In the office list, fetch and show the latest image for each office
              $officeImages = [];
              $stmtImg = $connect->query("SELECT office_id, image_path FROM office_image WHERE (office_id, uploaded_at) IN (SELECT office_id, MAX(uploaded_at) FROM office_image GROUP BY office_id)");
              while ($imgRow = $stmtImg->fetch(PDO::FETCH_ASSOC)) {
                $officeImages[$imgRow['office_id']] = $imgRow['image_path'];
              }
              
              // In the office list loop, add image thumbnail if exists
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $officeId = $row['id'];
                $officeName = $row['name'];
                $officeLocation = $row['location'] ?? '';
                $qrImagePath = "qrcodes/office_$officeId.png";
                $imageThumb = isset($officeImages[$officeId]) ? $officeImages[$officeId] : '';
                
                // Extract floor number from location (e.g., room-101-1 -> floor 1)
                $floorNumber = 'unassigned';
                if (!empty($officeLocation) && preg_match('/room-\d+-(\d+)/', $officeLocation, $matches)) {
                  $floorNumber = $matches[1];
                }

                // Fetch office hours for this office
                $hoursStmt = $connect->prepare("SELECT day_of_week, open_time, close_time FROM office_hours WHERE office_id = ?");
                $hoursStmt->execute([$officeId]);
                $officeHoursList = [];
                while ($h = $hoursStmt->fetch(PDO::FETCH_ASSOC)) {
                  $officeHoursList[$h['day_of_week']] = [
                    'open' => $h['open_time'],
                    'close' => $h['close_time']
                  ];
                }

                echo '<div class="office-item" data-office-name="' . htmlspecialchars(strtolower($officeName)) . '" data-floor="' . $floorNumber . '" style="display: flex; flex-direction: column; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; padding: 8px 12px;">';
                // Main row (flex)
                echo '<div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">';
                // Only icon and name are clickable
                echo '<div class="office-btn" data-office-id="' . $row['id'] . '" style="flex: 1; cursor: pointer; display: flex; align-items: center;">';
                echo '<i class="fas fa-door-open" style="margin-right: 15px; color: #2e7d32; font-size: 28px;"></i>';
                echo '<div class="office-info">';
                echo '<p class="office-name" style="font-weight: bold; margin: 0;">' . htmlspecialchars($row['name']) . '</p>';
                echo '</div>';
                echo '</div>';
                // Action buttons (not inside .office-btn)
                echo '<div style="display: flex; align-items: center; gap: 16px;">';
                if (!empty($imageThumb)) {
                  echo '<img src="office_images/' . htmlspecialchars($imageThumb) . '" alt="Office Image" style="width:32px;height:32px;object-fit:cover;border-radius:5px;margin-right:10px;border:1px solid #ccc;">';
                }
                // Clock icon button with tooltip
                echo '<div class="action-btn-wrapper" style="position: relative;">';
                echo '<button type="button" class="toggle-hours-edit-btn action-btn-with-tooltip" data-office-id="' . $officeId . '" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;">';
                echo '<img src="./srcImage/time-logo.png" alt="Clock" style="width: 20px; height: 20px;">';
                echo '</button>';
                echo '<span class="action-tooltip">Edit Office Hours</span>';
                echo '</div>';
                
                // Trash button with tooltip
                echo '<div class="action-btn-wrapper" style="position: relative;">';
                echo '<button type="button" class="delete-office-btn action-btn-with-tooltip" data-office-id="' . $officeId . '" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;">';
                echo '<img src="./srcImage/trash.png" alt="Delete" style="width: 20px; height: 20px;">';
                echo '</button>';
                echo '<span class="action-tooltip">Delete Office</span>';
                echo '</div>';
                
                // Office QR code button with tooltip
                echo '<div class="action-btn-wrapper" style="position: relative;">';
                echo '<form method="POST" action="download_qr.php" class="qr-download-form" style="margin: 0;">';
                echo '<input type="hidden" name="office_id" value="' . $officeId . '">';
                echo '<button type="button" class="download-qr-btn action-btn-with-tooltip" data-office-id="' . $officeId . '" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;">';
                echo '<img src="./srcImage/qr-code.png" alt="Download" class="download-icon" style="width: 20px; height: 20px;">';
                echo '</button>';
                echo '</form>';
                echo '<span class="action-tooltip">Manage QR Codes</span>';
                echo '</div>';
                
                // Edit button with tooltip
                echo '<div class="action-btn-wrapper" style="position: relative;">';
                echo '<button type="button" class="edit-office-btn action-btn-with-tooltip" data-office-id="' . $officeId . '" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;">';
                echo '<img src="./srcImage/edit-logo.png" alt="Edit" style="width: 20px; height: 20px;">';
                echo '</button>';
                echo '<span class="action-tooltip">Edit Office</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                // Office hours edit form (hidden by default)
                echo '<div class="office-hours-edit-container" id="office-hours-edit-' . $officeId . '" style="display:none; margin-top:8px; background:#f8fafc; border-radius:6px; padding:10px 12px;">';
                echo '<form method="POST" class="office-hours-edit-form" style="margin:0;">';
                echo '<input type="hidden" name="edit_hours_office_id" value="' . $officeId . '">';
                echo '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
                // Header and table header in green

                echo '<div style="font-weight:600;color:#2e7d32;margin-bottom:6px;">Office Hours</div>';
                echo '<table style="width:100%;font-size:0.98em;">';
                echo '<thead><tr style="color:#2e7d32;"><th style="text-align:left;">Day</th><th>Open</th><th>Close</th></tr></thead><tbody>';
                foreach (["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"] as $day) {
                  $open = isset($officeHoursList[$day]['open']) ? $officeHoursList[$day]['open'] : '';
                  $close = isset($officeHoursList[$day]['close']) ? $officeHoursList[$day]['close'] : '';
                  echo '<tr>';
                  echo '<td style="color:#2e7d32;">' . $day . '</td>';
                  echo '<td><input type="time" name="office_hours[' . $day . '][open]" value="' . htmlspecialchars($open) . '" style="width:90px;"></td>';
                  echo '<td><input type="time" name="office_hours[' . $day . '][close]" value="' . htmlspecialchars($close) . '" style="width:90px;"></td>';
                  echo '</tr>';
                }
                echo '</tbody></table>';
                // Save button in green

                echo '<div style="text-align:right;margin-top:10px;"><button type="submit" class="save-hours-btn" style="background:#2e7d32;color:#fff;border:none;border-radius:6px;padding:7px 18px;font-weight:500;">Add / Save Office</button></div>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
              }
          ?>
          </div>
        </div>
        <!-- Move the add/edit form below the office list -->
        <div class="form-card">
          <form id="officeForm" class="form-content" enctype="multipart/form-data">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <div>
                <label for="office-name" class="form-label" style="margin-bottom:0;">Office Name</label>
                <input type="text" name="name" id="office-name" class="form-input"
                       value="<?= htmlspecialchars($editData['name'] ?? '') ?>" required style="margin-bottom:0;" />
              </div>
              <div id="room-id-display" style="font-size: 1.1em; color: #2e7d32; font-weight: 500;">
                <?php if (!empty($editData['location'])): ?>
                  Room ID: <span id="room-id-value"><?= htmlspecialchars($editData['location']) ?></span>
                <?php else: ?>
                  <span id="room-id-value"></span>
                <?php endif; ?>
              </div>
            </div>
            <input type="hidden" name="office_id" id="office_id" value="<?= htmlspecialchars($editData['id'] ?? '') ?>">
            <input type="hidden" name="location" id="location" value="<?= htmlspecialchars($editData['location'] ?? '') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
              <label for="details" class="form-label">Details</label>
              <textarea name="details" id="details" class="form-input" rows="1"
                        required><?= htmlspecialchars($editData['details'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label for="contact" class="form-label">Contact Information (optional)</label>
              <input type="text" name="contact" id="contact" class="form-input"
                     value="<?= htmlspecialchars($editData['contact'] ?? '') ?>" />
            </div>
            <div class="form-group">
              <label for="services" class="form-label">Services Offered</label>
              <textarea name="services" id="services" class="form-input" rows="1"
                        style="font-family: Arial, sans-serif;"
                        placeholder="List services offered, one per line..."><?= htmlspecialchars($editData['services'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <div style="display: flex; align-items: center; gap: 10px;">
                <label class="form-label">Assign to Room</label>
                <span id="selected-room-label" style="color:#2e7d32;"></span>
              </div>
              <button type="button" id="open-map-btn" class="save-button" style="margin-top:10px;">Pick Room on Map</button>
              <!-- Room occupancy status indicator -->
              <div id="room-occupancy-status" style="display: none; margin-top: 10px; padding: 10px; border-radius: 6px; font-size: 14px;"></div>
            </div>
            <div class="form-group">
              <label for="office-image" class="form-label">Office Image (optional)</label>
              <input type="file" name="office_image" id="office-image" accept="image/*" class="form-input" />
              <div id="image-preview" style="margin-top:8px;">
                <?php if (!empty($editData['image'])): ?>
                  <img src="office_images/<?= htmlspecialchars($editData['image']) ?>" alt="Office Image" style="max-width:120px;max-height:120px;border-radius:6px;" />
                <?php endif; ?>
              </div>
            </div>
            <div class="form-footer">
              <button type="submit" class="save-button">Add / Save Office</button>
              <button type="button" class="clear-button" style="margin-left: 10px; background: #e53e3e; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Clear Form</button>
              <div id="formMessage" style="margin-top: 10px;"></div>
            </div>
          </form>
        </div>
        <div id="map-modal-overlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
          <div style="position:relative;width:80vw;max-width:1200px;aspect-ratio:1918/630;background:#fff;border-radius:10px;box-shadow:0 4px 24px #0002;display:flex;flex-direction:column;">
            <div style="padding: 10px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
                <div class="floor-selector-modal" style="display: flex; gap: 5px;">
                    <button class="floor-btn-modal active" data-floor="1">1F</button>
                    <button class="floor-btn-modal" data-floor="2">2F</button>
                    <button class="floor-btn-modal" data-floor="3">3F</button>
                </div>
                <button id="close-map-btn" style="font-size:22px;background:none;border:none;cursor:pointer;">&times;</button>
            </div>
            <iframe id="room-map-frame" src="floorPlan.php?selectRoom=1&floor=1" style="width:100%;height:100%;border:none;border-radius:0 0 10px 10px;"></iframe>
          </div>
        </div>
        <script>
        const openMapBtn = document.getElementById('open-map-btn');
        const mapModal = document.getElementById('map-modal-overlay');
        const closeMapBtn = document.getElementById('close-map-btn');
        const selectedRoomLabel = document.getElementById('selected-room-label');
        const locationInput = document.getElementById('location');
        const mapFrame = document.getElementById('room-map-frame');

        // --- Floor switching logic for modal ---
        const floorButtonsModal = document.querySelectorAll('.floor-btn-modal');
        const floorMaps = {
            1: 'floorPlan.php?selectRoom=1&floor=1',
            2: 'floorPlan.php?selectRoom=1&floor=2',
            3: 'floorPlan.php?selectRoom=1&floor=3'
        };

        floorButtonsModal.forEach(button => {
            button.addEventListener('click', function() {
                const floor = this.getAttribute('data-floor');
                
                // Update button styles
                floorButtonsModal.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Load the corresponding floor map in the iframe
                if(mapFrame) {
                    mapFrame.src = floorMaps[floor];
                }
            });
        });
        // --- End floor switching ---

        openMapBtn.addEventListener('click',function(){
          mapModal.style.display = 'flex';
          
          // Get current location to determine floor
          const currentLocation = locationInput.value;
          let targetFloor = 1; // Default to floor 1
          
          // Extract floor number from location (format: room-101-1 -> floor 1)
          if (currentLocation) {
            const floorMatch = currentLocation.match(/room-\d+-(\d+)/);
            if (floorMatch && floorMatch[1]) {
              targetFloor = parseInt(floorMatch[1]);
            }
          }
          
          // Update floor button styles
          floorButtonsModal.forEach(btn => btn.classList.remove('active'));
          const targetButton = document.querySelector('.floor-btn-modal[data-floor="' + targetFloor + '"]');
          if (targetButton) {
            targetButton.classList.add('active');
          }
          
          // Load the floor map with current room highlighted
          if (currentLocation) {
            mapFrame.src = floorMaps[targetFloor] + '&highlightRoom=' + encodeURIComponent(currentLocation);
          } else {
            mapFrame.src = floorMaps[targetFloor];
          }
        });
        closeMapBtn.addEventListener('click',function(){
          mapModal.style.display = 'none';
        });
        window.addEventListener('message',function(e){
          if(e.data && e.data.selectedRoomId && e.data.selectedRoomLabel){
            locationInput.value = e.data.selectedRoomId;
            
            // Extract floor number from room ID (format: room-101-1 -> floor 1)
            const floorMatch = e.data.selectedRoomId.match(/room-\d+-(\d+)/);
            const floorNumber = floorMatch ? floorMatch[1] : '';
            
            // Display room label with floor information
            if (floorNumber) {
              selectedRoomLabel.textContent = e.data.selectedRoomLabel + ' (Floor ' + floorNumber + ')';
            } else {
              selectedRoomLabel.textContent = e.data.selectedRoomLabel;
            }
            
            mapModal.style.display = 'none';
            
            // Check room occupancy in real-time
            checkRoomOccupancy(e.data.selectedRoomId);
          }
        });
        
        // Function to check if selected room is occupied
        function checkRoomOccupancy(roomLocation) {
          const officeId = document.getElementById('office_id').value;
          const statusDiv = document.getElementById('room-occupancy-status');
          
          // Show loading state
          statusDiv.style.display = 'block';
          statusDiv.style.background = '#e3f2fd';
          statusDiv.style.color = '#1976d2';
          statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking room availability...';
          
          $.ajax({
            url: 'officeManagement.php',
            method: 'POST',
            data: {
              ajax: 'checkRoomOccupancy',
              location: roomLocation,
              office_id: officeId
            },
            dataType: 'json',
            success: function(response) {
              if (response.occupied) {
                // Room is occupied
                statusDiv.style.background = '#fee';
                statusDiv.style.color = '#c62828';
                statusDiv.style.border = '1px solid #ef5350';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Room Occupied!</strong> This room is currently assigned to <strong>"' + response.officeName + '"</strong>. Please choose a different room.';
                
                // Disable the save button
                document.querySelector('.save-button[type="submit"]').disabled = true;
                document.querySelector('.save-button[type="submit"]').style.opacity = '0.5';
                document.querySelector('.save-button[type="submit"]').style.cursor = 'not-allowed';
              } else {
                // Room is available
                statusDiv.style.background = '#e8f5e9';
                statusDiv.style.color = '#2e7d32';
                statusDiv.style.border = '1px solid #66bb6a';
                statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Room Available!</strong> This room is ready to be assigned.';
                
                // Enable the save button
                document.querySelector('.save-button[type="submit"]').disabled = false;
                document.querySelector('.save-button[type="submit"]').style.opacity = '1';
                document.querySelector('.save-button[type="submit"]').style.cursor = 'pointer';
              }
            },
            error: function() {
              statusDiv.style.background = '#fff3e0';
              statusDiv.style.color = '#e65100';
              statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error checking room availability. Please try again.';
            }
          });
        }
        </script>
      </div>
    </main>
  </div>
  
<!-- Save Changes Confirmation Modal -->
<div id="saveModal" class="modal-overlay">
  <div class="modal-dialog">
    <div class="modal-header">
      <h4 class="modal-title">Save Changes</h4>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to save these changes?</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelSave">Cancel</button>
      <button class="btn btn-primary" id="confirmSave">Save</button>
    </div>
  </div>
</div>

<!-- Download QR Confirmation Modal -->
<div id="downloadModal" class="modal-overlay">
  <div class="modal-dialog" style="max-width: 650px;">
    <div class="modal-header">
      <h4 class="modal-title">QR Code Management</h4>
      <button class="modal-close" id="closeDownloadModal">&times;</button>
    </div>
    <div class="modal-body">
      <!-- Door QR Management Content (Removed tabs) -->
      <div style="margin-bottom: 15px;">
        <p style="margin-bottom: 15px;">Manage individual door QR codes for this office:</p>
        <button class="btn" id="generate-all-doors-btn" style="background: #04aa6d; color: white; padding: 8px 16px; border-radius: 5px;">
          Generate All Door QR Codes
        </button>
      </div>
      
      <div id="door-qr-list" style="margin-top: 15px;">
        <!-- Door QR codes will be loaded here -->
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelDownload">Close</button>
    </div>
  </div>
</div>

<!-- Delete Office Confirmation Modal -->
<div id="deleteModal" class="modal-overlay">
  <div class="modal-dialog">
    <div class="modal-header">
      <h4 class="modal-title">Delete Office</h4>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to delete this office? This action cannot be undone.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelDelete">Cancel</button>
      <button class="btn btn-primary" id="confirmDelete">Delete</button>
    </div>
  </div>
</div>

  <script>
    $(document).ready(function() {
  // Store the original form data
  let formData = null;
  
  // Function to clear the form
  function clearForm() {
    $('#office_id').val('');
    $('#office-name').val('');
    $('#details').val('');
    $('#contact').val('');
    $('#location').val('');
    $('#services').val('');
    $('#room-id-value').text('');
    $('#selected-room-label').text('');
    $('#image-preview').empty();
    $('#office-image').val('');
    
    // Clear room occupancy status and re-enable save button
    $('#room-occupancy-status').hide();
    $('.save-button[type="submit"]').prop('disabled', false).css({
      'opacity': '1',
      'cursor': 'pointer'
    });
  }

  // Handle clear button click
  $('.clear-button').on('click', function() {
    clearForm();
  });

  // Handle edit button click
  $(document).on('click', '.edit-office-btn', function(e) {
    e.stopPropagation();
    const officeId = $(this).data('office-id');
    
    // First clear the form
    clearForm();
    
    // Then load the office data
    $.ajax({
      type: 'POST',
      url: 'officeManagement.php',
      data: {
        ajax: 'getOffice',
        office_id: officeId
      },
      dataType: 'json',
      success: function(data) {
        $('#office_id').val(data.id);
        $('#office-name').val(data.name);
        $('#details').val(data.details);
        $('#contact').val(data.contact);
        $('#location').val(data.location);
        $('#services').val(data.services);
        $('#room-id-value').text(data.location ? data.location : '');
        
        // Display room label with floor information
        if (data.location) {
          // Extract room number and floor from location (format: room-101-1)
          const roomMatch = data.location.match(/room-(\d+)-(\d+)/);
          if (roomMatch) {
            const roomNumber = roomMatch[1];
            const floorNumber = roomMatch[2];
            $('#selected-room-label').text('Room ' + roomNumber + ' (Floor ' + floorNumber + ')');
          } else {
            $('#selected-room-label').text(data.location);
          }
        } else {
          $('#selected-room-label').text('');
        }
      },
      error: function() {
        alert('Failed to load office details');
      }
    });
  });

  // Handle form submission using modal
  $('#officeForm').on('submit', function(e) {
    e.preventDefault();
    // Use FormData for AJAX and base64 encode image
    var fd = new FormData(this);
    var fileInput = document.getElementById('office-image');
    if (fileInput && fileInput.files && fileInput.files[0]) {
      var reader = new FileReader();
      reader.onload = function(ev) {
        fd.append('office_image_base64', ev.target.result);
        fd.append('ajax', 'saveOffice');
        $('#saveModal').addClass('active');
        // Save FormData for later
        formData = fd;
      };
      reader.readAsDataURL(fileInput.files[0]);
    } else {
      fd.append('ajax', 'saveOffice');
      formData = fd;
      $('#saveModal').addClass('active');
    }
  });
  
  // Close save modal when clicking X or Cancel
  $('#closeSaveModal, #cancelSave').on('click', function() {
    $('#saveModal').removeClass('active');
  });
  
  // Handle save confirmation
  $('#confirmSave').on('click', function() {
    $('#saveModal').removeClass('active');
    // Use FormData for AJAX
    $.ajax({
      type: 'POST',
      url: 'officeManagement.php',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $('#formMessage').html('<div style="color: green;">' + response.message + '</div>');
          setTimeout(function() {
            $('#formMessage').html('');
            location.reload();
          }, 3000);
        } else {
          // Check if it's a room occupancy error
          if (response.message && response.message.includes('occupied')) {
            $('#formMessage').html('<div style="color: red; padding: 10px; background: #fee; border: 1px solid #ef5350; border-radius: 6px;"><i class="fas fa-exclamation-triangle"></i> <strong>Cannot Save:</strong> The chosen room is already occupied by another office. Please select a different room.</div>');
          } else {
            $('#formMessage').html('<div style="color: red;">' + response.message + '</div>');
          }
        }
      },
      error: function() {
        $('#formMessage').html('<div style="color: red;">An error occurred. Please try again.</div>');
      }
    });
  });
  
  // Handle QR download button click
  $(document).on('click', '.download-qr-btn', function(e) {
    e.preventDefault();
    const form = $(this).closest('form');
    const officeId = form.find('input[name="office_id"]').val();
    
    // Load door QR data only
    loadDoorQrData(officeId);
    
    $('#downloadModal').addClass('active');
  });
  
  // Close download modal when clicking X or Cancel
  $('#closeDownloadModal, #cancelDownload').on('click', function() {
    $('#downloadModal').removeClass('active');
  });
  
  // QR Status Management Functions
  let currentQrOfficeId = null;
  let qrStatusCache = {};
  
  function loadQrStatus(officeId) {
    currentQrOfficeId = officeId;
    
    // Check cache first
    if (qrStatusCache[officeId]) {
      updateQrStatusUI(qrStatusCache[officeId]);
      return;
    }
    
    $.ajax({
      type: 'GET',
      url: 'qr_api.php',
      data: {
        operation: 'getQrStatus',
        office_id: officeId
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          qrStatusCache[officeId] = response.is_active;
          updateQrStatusUI(response.is_active);
        } else {
          console.error('Failed to load QR status:', response.message);
          // Default to active if can't load status
          updateQrStatusUI(true);
        }
      },
      error: function() {
        console.error('Error loading QR status');
        // Default to active if can't load status
        updateQrStatusUI(true);
      }
    });
  }
  
  function updateQrStatusUI(isActive) {
    const toggle = $('#qr-status-toggle');
    const statusText = $('#qr-status-text');
    const statusDesc = $('#qr-status-desc');
    
    toggle.prop('checked', isActive);
    
    if (isActive) {
      statusText.text('Active').css('color', '#2e7d32');
      statusDesc.text('visible to visitors').css('color', '#2e7d32');
    } else {
      statusText.text('Inactive').css('color', '#e53e3e');
      statusDesc.text('hidden from visitors').css('color', '#e53e3e');
    }
  }
  
  function updateQrStatus(officeId, isActive) {
    $.ajax({
      type: 'POST',
      url: 'qr_api.php',
      data: {
        operation: 'updateQrStatus',
        office_id: officeId,
        is_active: isActive
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          qrStatusCache[officeId] = response.is_active;
          updateQrStatusUI(response.is_active);
        } else {
          console.error('Failed to update QR status:', response.message);
          alert('Failed to update QR status: ' + response.message);
          // Revert toggle state
          updateQrStatusUI(!isActive);
        }
      },
      error: function() {
        console.error('Error updating QR status');
        alert('Error updating QR status. Please try again.');
        // Revert toggle state
        updateQrStatusUI(!isActive);
      }
    });
  }
  
  // Handle QR status toggle change
  $(document).on('change', '#qr-status-toggle', function() {
    if (currentQrOfficeId) {
      const isActive = $(this).is(':checked');
      updateQrStatus(currentQrOfficeId, isActive);
    }
  });
  
  // Handle browser tab visibility change for QR status caching
  $(document).on('visibilitychange', function() {
    if (!document.hidden && currentQrOfficeId && $('#downloadModal').hasClass('active')) {
      // Refresh QR status when tab becomes visible
      delete qrStatusCache[currentQrOfficeId];
      loadQrStatus(currentQrOfficeId);
    }
  });
  
  $(window).on('focus', function() {
    if (currentQrOfficeId && $('#downloadModal').hasClass('active')) {
      // Refresh QR status when window gains focus
      delete qrStatusCache[currentQrOfficeId];
      loadQrStatus(currentQrOfficeId);
    }
  });
  
  // Delete office confirmation
  let deleteOfficeId = null;
  $(document).on('click', '.delete-office-btn', function(e) {
    e.preventDefault();
    deleteOfficeId = $(this).data('office-id');
    $('#deleteModal').addClass('active');
  });
  $('#cancelDelete').on('click', function() {
    $('#deleteModal').removeClass('active');
    deleteOfficeId = null;
  });
  $('#confirmDelete').on('click', function() {
    if (!deleteOfficeId) return;
    $.ajax({
      type: 'POST',
      url: 'officeManagement.php',
      data: { 
        ajax: 'deleteOffice', 
        office_id: deleteOfficeId,
        csrf_token: (window.CSRF_TOKEN || $('meta[name="csrf-token"]').attr('content') || '')
      },
      dataType: 'json',
      success: function(response) {
        $('#deleteModal').removeClass('active');
        if (response.success) {
          location.reload();
        } else {
          alert(response.message || 'Failed to delete office.');
        }
      },
      error: function() {
        $('#deleteModal').removeClass('active');
        alert('An error occurred while deleting the office.');
      }
    });
  });

  // Toggle office hours visibility
  $(document).on('click', '.toggle-hours-btn', function() {
    var officeId = $(this).data('office-id');
    var container = $('#office-hours-' + officeId);
    container.slideToggle(180);
  });

  // Toggle office hours edit form visibility (clock icon)
  $(document).on('click', '.toggle-hours-edit-btn', function(e) {
    e.stopPropagation(); // Prevent bubbling to .office-btn
    var officeId = $(this).data('office-id');
    // Hide all other office-hours-edit containers
    $('.office-hours-edit-container').not('#office-hours-edit-' + officeId).slideUp(180);
    // Toggle this one
    $('#office-hours-edit-' + officeId).slideToggle(180);
  });

  // Prevent .office-btn click when clicking inside .office-hours-edit-container
  $(document).on('click', '.office-hours-edit-container', function(e) {
    e.stopPropagation();
  });

  // Handle office hours edit form submit (AJAX)
  $(document).on('submit', '.office-hours-edit-form', function(e) {
    e.preventDefault();
    var form = $(this);
    var officeId = form.find('input[name="edit_hours_office_id"]').val();
    var formData = form.serialize() + '&ajax=saveOfficeHours';
    $.ajax({
      type: 'POST',
      url: 'officeManagement.php',
      data: formData,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          form.closest('.office-hours-edit-container').slideUp(180);
        } else {
          alert(response.message || 'Failed to save office hours.');
        }
      },
      error: function() {
        alert('An error occurred while saving office hours.');
      }
    });
  });

  // Add stopPropagation to all action buttons to prevent triggering office-btn click
  $(document).on('click', '.toggle-hours-edit-btn, .delete-office-btn, .download-qr-btn', function(e) {
    e.stopPropagation();
  });
  
  // ============================================================
  // DOOR QR CODE MANAGEMENT
  // ============================================================
  
  let currentDoorQrOfficeId = null;
  
  // Load door QR data for an office
  function loadDoorQrData(officeId) {
    currentDoorQrOfficeId = officeId;
    $('#door-qr-list').html('<p style="text-align: center; color: #666; padding: 20px;"><i class="fa fa-spinner fa-spin"></i> Loading door information...</p>');
    
    // First get office details
    $.ajax({
      type: 'POST',
      url: 'officeManagement.php',
      data: {
        ajax: 'getOffice',
        office_id: officeId
      },
      dataType: 'json',
      success: function(office) {
        if (!office.location) {
          $('#door-qr-list').html('<div class="door-qr-empty"><p style="color: #e53e3e;">❌ This office has no location assigned.</p><p style="font-size: 0.9em; color: #666;">Please assign a location in the floor plan first.</p></div>');
          return;
        }
        
        // Load floor graph to get entry points
        const roomId = office.location;
        let floorNumber = 1;
        const floorMatch = roomId.match(/room-\d+-(\d+)/);
        if (floorMatch) {
          floorNumber = parseInt(floorMatch[1]);
        }
        
        // Use relative path from current page location
        const graphPath = './' + 'floor_graph' + (floorNumber > 1 ? '_' + floorNumber : '') + '.json';
        
        console.log('Loading floor graph:', graphPath, 'for room:', roomId);
        
        $.getJSON(graphPath, function(graphData) {
          console.log('Floor graph loaded successfully', graphData);
          
          if (!graphData.rooms || !graphData.rooms[roomId] || !graphData.rooms[roomId].doorPoints) {
            console.error('No doorPoints found for room:', roomId);
            console.log('Available rooms:', Object.keys(graphData.rooms || {}));
            console.log('Room data:', graphData.rooms ? graphData.rooms[roomId] : 'No rooms object');
            $('#door-qr-list').html('<div class="door-qr-empty"><p style="color: #e53e3e;">❌ No door points found for this room.</p><p style="font-size: 0.9em; color: #666;">Configure doorPoints in the floor graph file.</p><p style="font-size: 0.85em; color: #999;">Looking for: ' + roomId + '</p></div>');
            return;
          }
          
          const doorPoints = graphData.rooms[roomId].doorPoints;
          console.log('Found doorPoints:', doorPoints);
          
          // Now check which doors already have QR codes
          $.ajax({
            type: 'GET',
            url: 'door_qr_api.php',
            data: {
              action: 'get_all',
              office_id: officeId
            },
            dataType: 'json',
            success: function(response) {
              console.log('Door QR API response:', response);
              const existingQrs = response.doors || [];
              renderDoorQrList(office, doorPoints, existingQrs);
            },
            error: function(jqXHR, textStatus, errorThrown) {
              console.error('Door QR API error:', textStatus, errorThrown);
              renderDoorQrList(office, doorPoints, []);
            }
          });
        }).fail(function(jqXHR, textStatus, errorThrown) {
          console.error('Failed to load floor graph:', textStatus, errorThrown);
          console.error('Attempted path:', graphPath);
          $('#door-qr-list').html('<div class="door-qr-empty"><p style="color: #e53e3e;">❌ Failed to load floor graph data.</p><p style="font-size: 0.9em; color: #666;">Path: ' + graphPath + '</p><p style="font-size: 0.85em; color: #999;">Error: ' + textStatus + '</p></div>');
        });
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error('Failed to load office data:', textStatus, errorThrown);
        $('#door-qr-list').html('<div class="door-qr-empty"><p style="color: #e53e3e;">❌ Failed to load office data.</p><p style="font-size: 0.85em; color: #999;">Error: ' + textStatus + '</p></div>');
      }
    });
  }
  
  // Render the list of doors with QR management options
  function renderDoorQrList(office, doorPoints, existingQrs) {
    console.log('renderDoorQrList called with:', {office, doorPoints, existingQrs});
    
    let html = '<div style="margin-bottom: 15px; padding: 12px; background: #e8f5e9; border-radius: 6px;">';
    html += '<div style="font-weight: 600; color: #2e7d32; margin-bottom: 4px;">' + office.name + '</div>';
    html += '<div style="font-size: 0.9em; color: #666;">Room: ' + office.location + ' • ' + doorPoints.length + ' door(s)</div>';
    html += '</div>';
    
    if (doorPoints.length === 0) {
      html += '<div class="door-qr-empty"><p>No doors found for this office.</p></div>';
      $('#door-qr-list').html(html);
      return;
    }
    
    doorPoints.forEach(function(door, index) {
      const existingQr = existingQrs.find(qr => qr.door_index == index);
      const hasQr = !!existingQr;
      const isActive = hasQr ? existingQr.is_active : true;
      
      html += '<div class="door-qr-item">';
      
      // Door info
      html += '<div class="door-qr-info">';
      html += '<div class="door-qr-name">Door ' + (index + 1) + '</div>';
      html += '<div class="door-qr-status ' + (isActive ? 'active' : 'inactive') + '">';
      if (hasQr) {
        html += '<i class="fa fa-check-circle"></i> QR Code: ' + (isActive ? 'Active' : 'Inactive');
      } else {
        html += '<i class="fa fa-circle-o"></i> No QR code generated';
      }
      html += '</div>';
      html += '</div>';
      
      // Actions
      html += '<div class="door-qr-actions">';
      
      if (hasQr) {
        // Toggle switch
        html += '<div class="door-qr-toggle toggle-switch">';
        html += '<input type="checkbox" id="door-qr-toggle-' + office.id + '-' + index + '" ' + (isActive ? 'checked' : '') + ' onchange="toggleDoorQrStatus(' + office.id + ', ' + index + ', this.checked)">';
        html += '<label for="door-qr-toggle-' + office.id + '-' + index + '" class="toggle-label">';
        html += '<span class="toggle-slider"></span>';
        html += '</label>';
        html += '</div>';
        
        // Download button - DISABLED if inactive
        if (isActive) {
          html += '<button class="door-qr-btn download" onclick="downloadDoorQr(' + office.id + ', ' + index + ', \'' + existingQr.qr_code_image + '\')" title="Download QR Code">';
          html += '<i class="fa fa-download"></i>';
          html += '</button>';
        } else {
          html += '<button class="door-qr-btn download" disabled title="Cannot download inactive QR code" style="opacity: 0.3; cursor: not-allowed;">';
          html += '<i class="fa fa-download"></i>';
          html += '</button>';
        }
        
        // Regenerate button
        html += '<button class="door-qr-btn" onclick="regenerateDoorQr(' + office.id + ', ' + index + ')" title="Regenerate QR Code">';
        html += '<i class="fa fa-refresh"></i>';
        html += '</button>';
        
        // Delete button
        html += '<button class="door-qr-btn delete" onclick="deleteDoorQr(' + office.id + ', ' + index + ')" title="Delete QR Code">';
        html += '<i class="fa fa-trash"></i>';
        html += '</button>';
      } else {
        // Generate button
        html += '<button class="door-qr-btn download" onclick="generateSingleDoorQr(' + office.id + ', ' + index + ')" style="width: 140px;">';
        html += '<i class="fa fa-qrcode"></i> Generate QR';
        html += '</button>';
      }
      
      html += '</div>';
      html += '</div>';
    });
    
    console.log('Setting door-qr-list HTML. Length:', html.length);
    console.log('HTML preview:', html.substring(0, 200));
    $('#door-qr-list').html(html);
    console.log('door-qr-list updated. Current content:', $('#door-qr-list').html().substring(0, 200));
  }
  
  // Generate all missing door QR codes
  $('#generate-all-doors-btn').on('click', function() {
    if (!currentDoorQrOfficeId) return;
    
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');
    
    $.ajax({
      type: 'POST',
      url: 'door_qr_api.php',
      data: {
        action: 'generate',
        office_id: currentDoorQrOfficeId,
        csrf_token: window.CSRF_TOKEN
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          alert('✅ Successfully generated ' + response.doors.length + ' door QR codes!');
          loadDoorQrData(currentDoorQrOfficeId);
        } else {
          const errorMsg = response.error || response.message || 'Unknown error';
          console.error('QR Generation Error:', response);
          alert('❌ Error: ' + errorMsg);
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX Error:', {
          status: status,
          error: error,
          response: xhr.responseText
        });
        
        let errorMsg = 'Failed to generate door QR codes. ';
        try {
          const response = JSON.parse(xhr.responseText);
          errorMsg += response.error || response.message || error;
        } catch(e) {
          errorMsg += 'Server error: ' + error;
        }
        alert('❌ ' + errorMsg);
      },
      complete: function() {
        btn.prop('disabled', false).html('Generate All Door QR Codes');
      }
    });
  });
  
  // Global functions for door QR actions
  window.generateSingleDoorQr = function(officeId, doorIndex) {
    $.ajax({
      type: 'POST',
      url: 'door_qr_api.php',
      data: {
        action: 'generate',
        office_id: officeId,
        csrf_token: window.CSRF_TOKEN
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          alert('✅ Door QR code generated successfully!');
          loadDoorQrData(officeId);
        } else {
          // Enhanced error display with detailed message
          const errorMsg = response.error || response.message || 'Unknown error';
          console.error('QR Generation Error:', response);
          alert('❌ Error: ' + errorMsg);
        }
      },
      error: function(xhr, status, error) {
        // Enhanced error handling with response details
        console.error('AJAX Error:', {
          status: status,
          error: error,
          response: xhr.responseText
        });
        
        let errorMsg = 'Failed to generate QR code. ';
        try {
          const response = JSON.parse(xhr.responseText);
          errorMsg += response.error || response.message || error;
        } catch(e) {
          errorMsg += 'Server error: ' + error;
        }
        alert('❌ ' + errorMsg);
      }
    });
  };
  
  window.regenerateDoorQr = function(officeId, doorIndex) {
    if (!confirm('Regenerate QR code for Door ' + (doorIndex + 1) + '?\n\nThis will create a new QR code image.')) return;
    
    // Delete then generate
    $.ajax({
      type: 'POST',
      url: 'door_qr_api.php',
      data: {
        action: 'delete',
        office_id: officeId,
        door_index: doorIndex,
        csrf_token: window.CSRF_TOKEN
      },
      dataType: 'json',
      success: function() {
        window.generateSingleDoorQr(officeId, doorIndex);
      },
      error: function() {
        alert('❌ Failed to regenerate QR code. Please try again.');
      }
    });
  };
  
  window.deleteDoorQr = function(officeId, doorIndex) {
    if (!confirm('Delete QR code for Door ' + (doorIndex + 1) + '?\n\nThis action cannot be undone.')) return;
    
    $.ajax({
      type: 'POST',
      url: 'door_qr_api.php',
      data: {
        action: 'delete',
        office_id: officeId,
        door_index: doorIndex,
        csrf_token: window.CSRF_TOKEN
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          alert('✅ Door QR code deleted successfully!');
          loadDoorQrData(officeId);
        } else {
          alert('❌ Error: ' + response.error);
        }
      },
      error: function() {
        alert('❌ Failed to delete QR code. Please try again.');
      }
    });
  };
  
  window.downloadDoorQr = function(officeId, doorIndex, filename) {
    const link = document.createElement('a');
    link.href = 'qrcodes/doors/' + filename;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };
  
  window.toggleDoorQrStatus = function(officeId, doorIndex, isActive) {
    $.ajax({
      type: 'POST',
      url: 'door_qr_api.php',
      data: {
        action: 'toggle_status',
        office_id: officeId,
        door_index: doorIndex,
        is_active: isActive ? 1 : 0,
        csrf_token: window.CSRF_TOKEN
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Update UI to reflect new status
          const statusEl = $('#door-qr-toggle-' + officeId + '-' + doorIndex).closest('.door-qr-item').find('.door-qr-status');
          if (isActive) {
            statusEl.removeClass('inactive').addClass('active').html('<i class="fa fa-check-circle"></i> QR Code: Active');
          } else {
            statusEl.removeClass('active').addClass('inactive').html('<i class="fa fa-check-circle"></i> QR Code: Inactive');
          }
        } else {
          alert('❌ Error: ' + response.error);
          // Revert toggle
          $('#door-qr-toggle-' + officeId + '-' + doorIndex).prop('checked', !isActive);
        }
      },
      error: function() {
        alert('❌ Failed to update status. Please try again.');
        // Revert toggle
        $('#door-qr-toggle-' + officeId + '-' + doorIndex).prop('checked', !isActive);
      }
    });
  };
  
  // ============================================================
  // SEARCH AND FILTER FUNCTIONALITY
  // ============================================================
  
  const officeSearch = document.getElementById('office-search');
  const floorFilter = document.getElementById('floor-filter');
  const officeItems = document.querySelectorAll('.office-item');
  
  // Search functionality
  if (officeSearch) {
    officeSearch.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase().trim();
      filterOffices();
    });
  }
  
  // Floor filter functionality
  if (floorFilter) {
    floorFilter.addEventListener('change', function() {
      filterOffices();
    });
  }
  
  // ============================================================
  // TOOLTIP POSITIONING
  // ============================================================
  
  // Position tooltips dynamically to avoid clipping
  document.querySelectorAll('.action-btn-wrapper').forEach(wrapper => {
    const button = wrapper.querySelector('.action-btn-with-tooltip');
    const tooltip = wrapper.querySelector('.action-tooltip');
    
    if (button && tooltip) {
      button.addEventListener('mouseenter', function() {
        const rect = button.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        // Position tooltip above the button
        tooltip.style.left = (rect.left + rect.width / 2) + 'px';
        tooltip.style.top = (rect.top - tooltipRect.height - 10) + 'px';
        tooltip.style.transform = 'translateX(-50%)';
      });
    }
  });
  
  // Combined filter function
  function filterOffices() {
    const searchTerm = officeSearch ? officeSearch.value.toLowerCase().trim() : '';
    const selectedFloor = floorFilter ? floorFilter.value : 'all';
    
    let visibleCount = 0;
    
    officeItems.forEach(item => {
      const officeName = item.getAttribute('data-office-name') || '';
      const officeFloor = item.getAttribute('data-floor') || 'unassigned';
      
      // Check search match
      const matchesSearch = searchTerm === '' || officeName.includes(searchTerm);
      
      // Check floor match
      const matchesFloor = selectedFloor === 'all' || officeFloor === selectedFloor;
      
      // Show or hide item
      if (matchesSearch && matchesFloor) {
        item.style.display = 'flex';
        visibleCount++;
      } else {
        item.style.display = 'none';
      }
    });
    
    // Show "no results" message if needed
    const listContent = document.getElementById('office-list-content');
    let noResultsMsg = document.getElementById('no-results-message');
    
    if (visibleCount === 0 && listContent) {
      if (!noResultsMsg) {
        noResultsMsg = document.createElement('div');
        noResultsMsg.id = 'no-results-message';
        noResultsMsg.style.cssText = 'text-align: center; padding: 40px 20px; color: #718096;';
        noResultsMsg.innerHTML = '<i class="fa fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><p style="font-size: 16px; margin: 0;">No offices found matching your criteria.</p>';
        listContent.appendChild(noResultsMsg);
      }
      noResultsMsg.style.display = 'block';
    } else if (noResultsMsg) {
      noResultsMsg.style.display = 'none';
    }
  }
  
});
</script>
</body>
</html>