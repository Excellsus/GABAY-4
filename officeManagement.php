<?php
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
    $baseUrl = "http://192.168.68.116/FinalDev/mobileScreen/";
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

// AJAX endpoint for saving office data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'saveOffice') {
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
      $baseUrl = "http://192.168.68.116/FinalDev/mobileScreen/";
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
    <title>GABAY Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="officeManagement.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <!-- Updated Font Awesome to version 6.5.2 for modern icons and styling -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="./mobileNav.js"></script>
    <link rel="stylesheet" href="mobileNav.css" />
      
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
          </div>
          <div class="office-list-content">
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
                $qrImagePath = "qrcodes/office_$officeId.png";
                $imageThumb = isset($officeImages[$officeId]) ? $officeImages[$officeId] : '';

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

                echo '<div class="office-item" style="display: flex; flex-direction: column; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; padding: 8px 12px;">';
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
                // Clock icon button
                echo '<button type="button" class="toggle-hours-edit-btn" data-office-id="' . $officeId . '" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;" title="Edit Office Hours">';
                echo '<img src="./srcImage/time-logo.png" alt="Clock" style="width: 20px; height: 20px;">';
                echo '</button>';
                // Trash button
                echo '<button type="button" class="delete-office-btn" data-office-id="' . $officeId . '" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;">';
                echo '<img src="./srcImage/trash.png" alt="Delete" style="width: 20px; height: 20px;">';
                echo '</button>';
                // QR code button
                echo '<form method="POST" action="download_qr.php" class="qr-download-form" style="margin: 0;">';
                echo '<input type="hidden" name="office_id" value="' . $officeId . '">';
                echo '<button type="button" class="download-qr-btn" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;">';
                echo '<img src="./srcImage/qr-code.png" alt="Download" class="download-icon" style="width: 20px; height: 20px;">';
                echo '</button>';
                echo '</form>';
                // Edit button
                echo '<button type="button" class="edit-office-btn" data-office-id="' . $officeId . '" style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center;">';
                echo '<img src="./srcImage/edit-logo.png" alt="Edit" style="width: 20px; height: 20px;">';
                echo '</button>';
                echo '</div>';
                echo '</div>';
                // Office hours edit form (hidden by default)
                echo '<div class="office-hours-edit-container" id="office-hours-edit-' . $officeId . '" style="display:none; margin-top:8px; background:#f8fafc; border-radius:6px; padding:10px 12px;">';
                echo '<form method="POST" class="office-hours-edit-form" style="margin:0;">';
                echo '<input type="hidden" name="edit_hours_office_id" value="' . $officeId . '">';
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
                        placeholder="List services offered, one per line..."><?= htmlspecialchars($editData['services'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <div style="display: flex; align-items: center; gap: 10px;">
                <label class="form-label">Assign to Room</label>
                <span id="selected-room-label" style="color:#2e7d32;"></span>
              </div>
              <button type="button" id="open-map-btn" class="save-button" style="margin-top:10px;">Pick Room on Map</button>
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
          // Reset to 1st floor view when opening
          floorButtonsModal.forEach(btn => btn.classList.remove('active'));
          document.querySelector('.floor-btn-modal[data-floor="1"]').classList.add('active');
          mapFrame.src = floorMaps[1];
        });
        closeMapBtn.addEventListener('click',function(){
          mapModal.style.display = 'none';
        });
        window.addEventListener('message',function(e){
          if(e.data && e.data.selectedRoomId && e.data.selectedRoomLabel){
            locationInput.value = e.data.selectedRoomId;
            selectedRoomLabel.textContent = e.data.selectedRoomLabel;
            mapModal.style.display = 'none';
          }
        });
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
  <div class="modal-dialog">
    <div class="modal-header">
      <h4 class="modal-title">Download QR Code</h4>
    </div>
    <div class="modal-body">
      <p>Do you want to download this QR code?</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelDownload">Cancel</button>
      <button class="btn btn-primary" id="confirmDownload">Download</button>
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
  let downloadQrForm = null;
  
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
        $('#selected-room-label').text(data.location ? 'Room ' + data.location : '');
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
          $('#formMessage').html('<div style="color: red;">' + response.message + '</div>');
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
    downloadQrForm = $(this).closest('form');
    $('#downloadModal').addClass('active');
  });
  
  // Close download modal when clicking X or Cancel
  $('#closeDownloadModal, #cancelDownload').on('click', function() {
    $('#downloadModal').removeClass('active');
  });
  
  // Handle download confirmation
  $('#confirmDownload').on('click', function() {
    $('#downloadModal').removeClass('active');
    if (downloadQrForm) {
      downloadQrForm.submit();
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
      data: { ajax: 'deleteOffice', office_id: deleteOfficeId },
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
});
</script>
</body>
</html>