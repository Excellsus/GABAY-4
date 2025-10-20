<?php
include 'connect_db.php';

// Get selected month/year or use current
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Prepare WHERE clause for office QR scans
$whereClause = "";
$params = [];

if ($selectedMonth !== 'all') {
    $whereClause .= " AND MONTH(check_in_time) = :month";
    $params[':month'] = $selectedMonth;
}
if ($selectedYear !== 'all') {
    $whereClause .= " AND YEAR(check_in_time) = :year";
    $params[':year'] = $selectedYear;
}

// Prepare WHERE clause for panorama QR scans
$panoramaWhereClause = "";
$panoramaParams = [];

if ($selectedMonth !== 'all') {
    $panoramaWhereClause .= " AND MONTH(scan_timestamp) = :month";
    $panoramaParams[':month'] = $selectedMonth;
}
if ($selectedYear !== 'all') {
    $panoramaWhereClause .= " AND YEAR(scan_timestamp) = :year";
    $panoramaParams[':year'] = $selectedYear;
}

// Total Office Visitors
$visitorStmt = $connect->prepare("SELECT COUNT(*) FROM qr_scan_logs WHERE 1=1 $whereClause");
$visitorStmt->execute($params);
$totalVisitors = $visitorStmt->fetchColumn();

// Total Panorama Views
$panoramaStmt = $connect->prepare("SELECT COUNT(*) FROM panorama_qr_scans WHERE 1=1 $panoramaWhereClause");
$panoramaStmt->execute($panoramaParams);
$totalPanoramaViews = $panoramaStmt->fetchColumn();

// Total Combined Visitors (Office + Panorama)
$totalCombinedVisitors = $totalVisitors + $totalPanoramaViews;

// Most Visited Offices
$topOfficesStmt = $connect->prepare("
    SELECT o.name, COUNT(l.id) as scan_count
    FROM qr_scan_logs l
    JOIN offices o ON o.id = l.office_id
    WHERE 1=1 $whereClause
    GROUP BY o.id
    ORDER BY scan_count DESC
    LIMIT 5
");
$topOfficesStmt->execute($params);
$topOffices = $topOfficesStmt->fetchAll(PDO::FETCH_ASSOC);

// Most Viewed Panoramas with coordinates
$topPanoramasStmt = $connect->prepare("
    SELECT 
        CONCAT('Floor ', pq.floor_number, ' - ', pq.path_id, ' Point ', pq.point_index) as location_name,
        COUNT(ps.id) as view_count
    FROM panorama_qr_scans ps
    JOIN panorama_qrcodes pq ON pq.id = ps.qr_id
    WHERE 1=1 $panoramaWhereClause
    GROUP BY pq.id
    ORDER BY view_count DESC
    LIMIT 5
");
$topPanoramasStmt->execute($panoramaParams);
$topPanoramas = $topPanoramasStmt->fetchAll(PDO::FETCH_ASSOC);

// Get panorama locations with coordinates and scan counts for map display
$panoramaMapWhereClause = str_replace('scan_timestamp', 'ps.scan_timestamp', $panoramaWhereClause);

// Safely detect whether panorama_qrcodes.last_scanned_at exists (migration may be pending)
$hasLastScanned = false;
try {
  $colCheck = $connect->prepare("SHOW COLUMNS FROM panorama_qrcodes LIKE 'last_scanned_at'");
  $colCheck->execute();
  if ($colCheck->fetch()) $hasLastScanned = true;
} catch (Exception $e) {
  // Ignore any error and proceed without last_scanned_at
  $hasLastScanned = false;
}

if ($hasLastScanned) {
  $sql = "\n    SELECT \n        pi.floor_number,\n        pi.path_id,\n        pi.point_index,\n        pi.point_x,\n        pi.point_y,\n        pi.title,\n        COALESCE(COUNT(ps.id), 0) as scan_count,\n        pq.last_scanned_at,\n        pq.qr_code_data\n    FROM panorama_image pi\n    LEFT JOIN panorama_qrcodes pq ON (\n        pi.path_id = pq.path_id AND\n        pi.point_index = pq.point_index AND\n        pi.floor_number = pq.floor_number\n    )\n    LEFT JOIN panorama_qr_scans ps ON pq.id = ps.qr_id\n    WHERE pi.is_active = 1 $panoramaMapWhereClause\n    GROUP BY pi.id\n    ORDER BY pi.floor_number, scan_count DESC\n    ";
} else {
  // Fallback: return NULLs for last_scanned_at and qr_code_data so JS can handle gracefully
  $sql = "\n    SELECT \n        pi.floor_number,\n        pi.path_id,\n        pi.point_index,\n        pi.point_x,\n        pi.point_y,\n        pi.title,\n        COALESCE(COUNT(ps.id), 0) as scan_count,\n        NULL AS last_scanned_at,\n        NULL AS qr_code_data\n    FROM panorama_image pi\n    LEFT JOIN panorama_qrcodes pq ON (\n        pi.path_id = pq.path_id AND\n        pi.point_index = pq.point_index AND\n        pi.floor_number = pq.floor_number\n    )\n    LEFT JOIN panorama_qr_scans ps ON pq.id = ps.qr_id\n    WHERE pi.is_active = 1 $panoramaMapWhereClause\n    GROUP BY pi.id\n    ORDER BY pi.floor_number, scan_count DESC\n    ";
}

$panoramaMapStmt = $connect->prepare($sql);
$panoramaMapStmt->execute($panoramaParams);
$panoramaMapData = $panoramaMapStmt->fetchAll(PDO::FETCH_ASSOC);

// Visitor Log Counts (Office QR Scans)
$dailyStmt = $connect->prepare("SELECT COUNT(*) FROM qr_scan_logs WHERE DATE(check_in_time) = CURDATE() $whereClause");
$weeklyStmt = $connect->prepare("SELECT COUNT(*) FROM qr_scan_logs WHERE WEEK(check_in_time) = WEEK(CURDATE()) AND YEAR(check_in_time) = YEAR(CURDATE()) $whereClause");
$monthlyStmt = $connect->prepare("SELECT COUNT(*) FROM qr_scan_logs WHERE MONTH(check_in_time) = MONTH(CURDATE()) AND YEAR(check_in_time) = YEAR(CURDATE()) $whereClause");

$dailyStmt->execute($params);
$weeklyStmt->execute($params);
$monthlyStmt->execute($params);

$dailyCount = $dailyStmt->fetchColumn();
$weeklyCount = $weeklyStmt->fetchColumn();
$monthlyCount = $monthlyStmt->fetchColumn();

// Panorama QR Scan Counts
$dailyPanoramaStmt = $connect->prepare("SELECT COUNT(*) FROM panorama_qr_scans WHERE DATE(scan_timestamp) = CURDATE() $panoramaWhereClause");
$weeklyPanoramaStmt = $connect->prepare("SELECT COUNT(*) FROM panorama_qr_scans WHERE WEEK(scan_timestamp) = WEEK(CURDATE()) AND YEAR(scan_timestamp) = YEAR(CURDATE()) $panoramaWhereClause");
$monthlyPanoramaStmt = $connect->prepare("SELECT COUNT(*) FROM panorama_qr_scans WHERE MONTH(scan_timestamp) = MONTH(CURDATE()) AND YEAR(scan_timestamp) = YEAR(CURDATE()) $panoramaWhereClause");

$dailyPanoramaStmt->execute($panoramaParams);
$weeklyPanoramaStmt->execute($panoramaParams);
$monthlyPanoramaStmt->execute($panoramaParams);

$dailyPanoramaCount = $dailyPanoramaStmt->fetchColumn();
$weeklyPanoramaCount = $weeklyPanoramaStmt->fetchColumn();
$monthlyPanoramaCount = $monthlyPanoramaStmt->fetchColumn();

// Combined Counts (Office + Panorama)
$dailyCombinedCount = $dailyCount + $dailyPanoramaCount;
$weeklyCombinedCount = $weeklyCount + $weeklyPanoramaCount;
$monthlyCombinedCount = $monthlyCount + $monthlyPanoramaCount;

// Feedback
$ratingStmt = $connect->prepare("SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total_reviews FROM feedback WHERE rating IS NOT NULL");
$ratingStmt->execute();
$feedbackData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
$avgRating = $feedbackData['avg_rating'] ?? '0.0';
$totalReviews = $feedbackData['total_reviews'] ?? 0;

// Office QR Monitoring Data (similar to panorama monitoring)
// Get office QR codes with last scanned information
$officeQrMonitoringStmt = $connect->prepare("
    SELECT 
        o.id as office_id,
        o.name as office_name,
        o.location as room_location,
        qc.is_active,
        COUNT(qsl.id) as total_scans,
        MAX(qsl.check_in_time) as last_scanned_at,
        COALESCE(
            CASE 
                WHEN MAX(qsl.check_in_time) IS NULL THEN 999
                ELSE DATEDIFF(NOW(), MAX(qsl.check_in_time))
            END, 999
        ) as days_since_last_scan
    FROM offices o
    LEFT JOIN qrcode_info qc ON o.id = qc.office_id  
    LEFT JOIN qr_scan_logs qsl ON o.id = qsl.office_id
    WHERE qc.office_id IS NOT NULL
    GROUP BY o.id, o.name, o.location, qc.is_active
    ORDER BY days_since_last_scan DESC, total_scans DESC
");
$officeQrMonitoringStmt->execute();
$officeQrMonitoringData = $officeQrMonitoringStmt->fetchAll(PDO::FETCH_ASSOC);

// Count offices by QR status categories
$activeQrCount = count(array_filter($officeQrMonitoringData, fn($office) => $office['is_active'] == 1));
$inactiveQrCount = count(array_filter($officeQrMonitoringData, fn($office) => $office['is_active'] == 0));
$staleQrCount = count(array_filter($officeQrMonitoringData, fn($office) => $office['days_since_last_scan'] >= 7));
$neverScannedCount = count(array_filter($officeQrMonitoringData, fn($office) => $office['last_scanned_at'] === null));
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GABAY Admin Dashboard</title>
  <link rel="stylesheet" href="home.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="mobileNav.css" />
    <link rel="stylesheet" href="filter-styles.css"> <!-- Add this line -->
    <style>
  /* Stale warning pin style: do not change base pin fill; overlay a warning icon instead */
  .panorama-pin .pin-shape { fill: #04aa6d; stroke: #1976d2; stroke-width: 1px; }
  .panorama-pin .pin-circle { fill: #fff; }
      .panorama-pin .pin-text { font-size: 12px; fill: #111; font-weight: 600; }

      /* Hover tooltip base (positioned absolutely) */
      .panorama-hover-tooltip { box-shadow: 0 6px 18px rgba(0,0,0,0.2); }
      /* Legend marker for stale-warning */
    .legend-marker.stale-warning { width: 18px; height: 18px; background: #ff4444; border-radius: 4px; border: 1px solid #8b0000; display: inline-block; }
    /* small svg overlay icon for warning shown above pin */
    .panorama-pin .warning-icon { opacity: 0.0; transition: opacity 0.15s ease; }
    .panorama-pin.stale-warning .warning-icon { opacity: 1.0; }
  /* Force warning toggle styles */
  .force-warning-toggle { display: inline-flex; align-items: center; gap: 8px; margin-left: 12px; }
  .force-warning-toggle label { font-size: 13px; color: #333; }
  .force-warning-toggle input[type="checkbox"] { width: 18px; height: 18px; }

  /* Office QR Monitoring Styles */
  .office-qr-monitoring-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-height: 400px;
  }
  
  .office-qr-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
  }
  
  .office-qr-stats .stat-item {
    flex: 1;
    text-align: center;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
  }
  
  .office-qr-stats .stat-item h4 {
    font-size: 24px;
    font-weight: 700;
    color: #2e7d32;
    margin: 0 0 5px 0;
  }
  
  .office-qr-stats .stat-item p {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin: 0 0 3px 0;
  }
  
  .office-qr-stats .stat-item small {
    font-size: 12px;
    color: #666;
  }
  
  .office-qr-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
  }
  
  .office-qr-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s;
  }
  
  .office-qr-item:hover {
    background-color: #f8fafc;
  }
  
  .office-qr-item:last-child {
    border-bottom: none;
  }
  
  .office-qr-info {
    flex: 1;
  }
  
  .office-name {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
  }
  
  .office-name strong {
    color: #2e7d32;
    font-size: 14px;
  }
  
  .scan-stats {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #666;
  }
  
  .scan-count {
    font-weight: 600;
    color: #1976d2;
  }
  
  .last-scan.never {
    color: #e53e3e;
    font-weight: 600;
  }
  
  .office-qr-status {
    display: flex;
    align-items: center;
  }
  
  .qr-status {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
  }
  
  .status-active {
    background: #e8f5e8;
    color: #2e7d32;
  }
  
  .status-inactive {
    background: #fce4ec;
    color: #c2185b;
  }
  
  .status-warning {
    background: #fff3e0;
    color: #f57c00;
    border: 1px solid #ffb74d;
  }
  
  .office-qr-filter {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  
  .office-qr-filter label {
    font-size: 13px;
    color: #333;
    font-weight: 500;
  }
  
  .office-qr-filter select {
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 13px;
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
      <a href="home.php" class="active">Dashboard</a>
      <a href="officeManagement.php">Office Management</a>
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
          <li><a href="home.php" class="active">Dashboard</a></li>
          <li><a href="officeManagement.php">Office Management</a></li>
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
          <h2>Dashboard Overview</h2>
          <p>Welcome back, Admin. Here's what's happening.</p>
        </div>


    <div class="filter-actions">
        <form method="GET" action="home.php" class="filter-controls">
          <div class="filter-item">
            <label for="month" class="filter-label">Month:</label>
            <select name="month" id="month" class="filter-select">
              <option value="all" <?= $selectedMonth == 'all' ? 'selected' : '' ?>>All Months</option>
              <?php
                for ($m = 1; $m <= 12; $m++) {
                  $monthValue = str_pad($m, 2, '0', STR_PAD_LEFT);
                  $monthName = date('F', mktime(0, 0, 0, $m, 10));
                  $selected = $monthValue == $selectedMonth ? 'selected' : '';
                  echo "<option value=\"$monthValue\" $selected>$monthName</option>";
                }
              ?>
            </select>
          </div>

          <div class="filter-item">
            <label for="year" class="filter-label">Year:</label>
            <select name="year" id="year" class="filter-select">
              <option value="all" <?= $selectedYear == 'all' ? 'selected' : '' ?>>All Years</option>
              <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= 2020; $y--) {
                  $selected = $y == $selectedYear ? 'selected' : '';
                  echo "<option value=\"$y\" $selected>$y</option>";
                }
              ?>
            </select>
          </div>
          <div class="filter-actions">
        <button type="submit" class="filter-button">Filter</button>
        <a href="home.php" class="reset-button">Reset</a>
    </div>
      </header>

      <section class="cards">
  <div class="card green">
    <div class="card-left">
      <p>Office Visitors</p>
      <h3><?php echo $totalVisitors; ?></h3>
      <span class="growth">
        <svg class="growth-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        QR Scans
      </span>
    </div>
    <div class="card-right">
      <svg class="icon large" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
    </div>
  </div>

  <div class="card blue">
    <div class="card-left">
      <p>Panorama Views</p>
      <h3><?php echo $totalPanoramaViews; ?></h3>
      <span class="growth">
        <svg class="growth-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        360° Views
      </span>
    </div>
    <div class="card-right">
      <svg class="icon large" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
      </svg>
    </div>
  </div>

  <div class="card yellow">
    <div class="card-left">
      <p>Active Offices</p>
      <h3>
        <?php
          $officeCountStmt = $connect->query("SELECT COUNT(*) FROM offices WHERE status = 'active'");
          echo $officeCountStmt->fetchColumn();
        ?>
      </h3>
      <span class="growth">
        <svg class="growth-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        Updated live
      </span>
    </div>
    <div class="card-right">
      <svg class="icon large" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
    </div>
  </div>

  <div class="card purple">
    <div class="card-left">
      <p>Feedback Rating</p>
      <h3><?php echo $avgRating; ?></h3>
      <span class="rating">
        <svg class="star-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z" />
        </svg>
        from <?php echo $totalReviews; ?> reviews
      </span>
    </div>
    <div class="card-right">
      <svg class="icon large" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
  </div>
</section>

<section class="content-area">
  <!-- Most Visited Offices Chart -->
  <div class="activity-panel">
    <div class="panel-header">
      <h3>Most Visited Offices</h3>
    </div>
    <canvas id="topOfficesChart" height="250"></canvas>
  </div>

  <!-- Monthly Visitor Log Chart -->
  <div class="actions-panel">
    <h3>Monthly Visitor Log</h3>
    <canvas id="visitorLogChart" height="250"></canvas>
  </div>
</section>

<section class="content-area">
  <!-- Panorama Analytics Floor Plan -->
  <div class="activity-panel">
    <div class="panel-header">
      <h3>Panorama Views by Location</h3>
      <div class="panel-controls">
        <!-- Refresh button removed per UI update -->
        <!-- Force-show warnings toggle for testing -->
        <div class="force-warning-toggle" title="Force-show stale warnings for testing">
          <label for="force-warnings-checkbox">Force Warnings</label>
          <input type="checkbox" id="force-warnings-checkbox" />
        </div>
        <!-- Warning count display -->
        <div id="panorama-warning-count" style="display:inline-flex;align-items:center;gap:6px;margin-left:12px;">
          <!-- small inline warning icon -->
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 2 2 20h20L12 2z" fill="#ff4444" stroke="#8b0000" stroke-width="0.6"></path>
            <rect x="11" y="6.5" width="2" height="7" fill="#fff"></rect>
            <circle cx="12" cy="16.2" r="1.5" fill="#fff"></circle>
          </svg>
          <span style="font-weight:600">=</span>
          <span id="panorama-warning-number" style="font-weight:700;color:#b91c1c;">0</span>
        </div>
        <div class="panorama-filter">
          <label for="panorama-time-filter">Show:</label>
          <select id="panorama-time-filter" class="time-filter-select">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
          </select>
        </div>
        <div class="floor-selector">
          <button type="button" class="floor-btn active" data-floor="1">Floor 1</button>
          <button type="button" class="floor-btn" data-floor="2">Floor 2</button>
          <button type="button" class="floor-btn" data-floor="3">Floor 3</button>
        </div>
      </div>
    </div>
    <div class="panorama-map-container">
      <div id="floor-1-map" class="floor-map active">
        <svg id="floor-1-svg" class="floor-svg" viewBox="0 0 1917 629">
          <!-- Floor 1 base map will be loaded here -->
        </svg>
      </div>
      <div id="floor-2-map" class="floor-map">
        <svg id="floor-2-svg" class="floor-svg" viewBox="0 0 1917 629">
          <!-- Floor 2 base map will be loaded here -->
        </svg>
      </div>
      <div id="floor-3-map" class="floor-map">
        <svg id="floor-3-svg" class="floor-svg" viewBox="0 0 1917 629">
          <!-- Floor 3 base map will be loaded here -->
        </svg>
      </div>
    </div>
    <div class="map-legend">
      <div class="legend-item">
        <div class="legend-marker high-activity"></div>
        <span>High Activity (50+ views)</span>
      </div>
      <div class="legend-item">
        <div class="legend-marker medium-activity"></div>
        <span>Medium Activity (10-49 views)</span>
      </div>
      <div class="legend-item">
        <div class="legend-marker low-activity"></div>
        <span>Low Activity (1-9 views)</span>
      </div>
      <div class="legend-item">
        <div class="legend-marker no-activity"></div>
        <span>No Activity (0 views)</span>
      </div>
      <div class="legend-item">
        <div class="legend-marker stale-warning" style="display:inline-flex;align-items:center;justify-content:center;padding:2px;">
          <svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 2 2 20h20L12 2z" fill="#ff4444" stroke="#8b0000" stroke-width="0.6"></path>
            <rect x="11" y="6.5" width="2" height="7" fill="#fff"></rect>
            <circle cx="12" cy="16.2" r="1.7" fill="#fff"></circle>
          </svg>
        </div>
        <span>Warning - Not scanned 7 days</span>
      </div>
    </div>
  </div>

  <!-- Office QR Monitoring -->
  <div class="actions-panel">
    <div class="panel-header">
      <h3>Office QR Code Monitoring</h3>
      <div class="panel-controls">
        <!-- Warning count display -->
        <div id="office-qr-warning-count" style="display:inline-flex;align-items:center;gap:6px;margin-right:12px;">
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 2 2 20h20L12 2z" fill="#ff4444" stroke="#8b0000" stroke-width="0.6"></path>
            <rect x="11" y="6.5" width="2" height="7" fill="#fff"></rect>
            <circle cx="12" cy="16.2" r="1.5" fill="#fff"></circle>
          </svg>
          <span style="font-weight:600">=</span>
          <span id="office-qr-warning-number" style="font-weight:700;color:#b91c1c;"><?php echo $staleQrCount + $neverScannedCount; ?></span>
        </div>
        <div class="office-qr-filter">
          <label for="office-qr-status-filter">Show:</label>
          <select id="office-qr-status-filter" class="time-filter-select">
            <option value="all">All Offices</option>
            <option value="active">Active QR Codes</option>
            <option value="inactive">Inactive QR Codes</option>
            <option value="stale">Stale (7+ days)</option>
            <option value="never">Never Scanned</option>
          </select>
        </div>
      </div>
    </div>
    <div class="office-qr-monitoring-container">
      <div class="office-qr-stats">
        <div class="stat-item">
          <h4><?php echo $activeQrCount; ?></h4>
          <p>Active QR Codes</p>
          <small>Ready for visitors</small>
        </div>
        <div class="stat-item">
          <h4><?php echo $staleQrCount; ?></h4>
          <p>Stale QR Codes</p>
          <small>Not scanned in 7+ days</small>
        </div>
        <div class="stat-item">
          <h4><?php echo $neverScannedCount; ?></h4>
          <p>Never Scanned</p>
          <small>QR codes with no visits</small>
        </div>
      </div>
      <div class="office-qr-list" id="office-qr-list">
        <?php foreach ($officeQrMonitoringData as $office): ?>
          <div class="office-qr-item" 
               data-status="<?php echo $office['is_active'] ? 'active' : 'inactive'; ?>"
               data-days="<?php echo $office['days_since_last_scan']; ?>"
               data-scanned="<?php echo $office['last_scanned_at'] ? 'yes' : 'no'; ?>">
            <div class="office-qr-info">
              <div class="office-name">
                <strong><?php echo htmlspecialchars($office['office_name']); ?></strong>
              </div>
              <div class="scan-stats">
                <span class="scan-count"><?php echo $office['total_scans']; ?> scans</span>
                <?php if ($office['last_scanned_at']): ?>
                  <span class="last-scan">Last: <?php echo date('M j, Y', strtotime($office['last_scanned_at'])); ?></span>
                <?php else: ?>
                  <span class="last-scan never">Never scanned</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="office-qr-status">
              <?php 
                $statusClass = 'status-active';
                $statusText = 'Active';
                $warningIcon = '';
                
                if (!$office['is_active']) {
                  $statusClass = 'status-inactive';
                  $statusText = 'Inactive';
                } elseif ($office['days_since_last_scan'] >= 7) {
                  $statusClass = 'status-warning';
                  $statusText = $office['last_scanned_at'] ? $office['days_since_last_scan'] . ' days ago' : 'Never';
                  $warningIcon = '<svg width="16" height="16" viewBox="0 0 24 24" style="margin-right:4px;"><path d="M12 2 2 20h20L12 2z" fill="#ff4444"/><rect x="11" y="6.5" width="2" height="7" fill="#fff"/><circle cx="12" cy="16.2" r="1.5" fill="#fff"/></svg>';
                }
              ?>
              <span class="qr-status <?php echo $statusClass; ?>">
                <?php echo $warningIcon . $statusText; ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

    </main>

  </div>
  <script>
  const topOffices = <?php echo json_encode($topOffices); ?>;
  const topPanoramas = <?php echo json_encode($topPanoramas); ?>;
  let panoramaMapData = <?php echo json_encode($panoramaMapData); ?>;
  window.panoramaMapData = panoramaMapData; // Make sure it's also available globally
  const panoramaHasLastScanned = <?php echo (isset($hasLastScanned) && $hasLastScanned) ? 'true' : 'false'; ?>;
  window.panoramaHasLastScanned = panoramaHasLastScanned;
  console.log('🚀 Initial panoramaMapData loaded:', panoramaMapData);
  const dailyCount = <?php echo $dailyCount; ?>;
  const weeklyCount = <?php echo $weeklyCount; ?>;
  const monthlyCount = <?php echo $monthlyCount; ?>;
  const dailyPanoramaCount = <?php echo $dailyPanoramaCount; ?>;
  const weeklyPanoramaCount = <?php echo $weeklyPanoramaCount; ?>;
  const monthlyPanoramaCount = <?php echo $monthlyPanoramaCount; ?>;
  const dailyCombinedCount = <?php echo $dailyCombinedCount; ?>;
  const weeklyCombinedCount = <?php echo $weeklyCombinedCount; ?>;
  const monthlyCombinedCount = <?php echo $monthlyCombinedCount; ?>;
  
  // Office QR Monitoring Data
  const officeQrMonitoringData = <?php echo json_encode($officeQrMonitoringData); ?>;
  const activeQrCount = <?php echo $activeQrCount; ?>;
  const inactiveQrCount = <?php echo $inactiveQrCount; ?>;
  const staleQrCount = <?php echo $staleQrCount; ?>;
  const neverScannedCount = <?php echo $neverScannedCount; ?>;
  console.log('🏢 Office QR monitoring data loaded:', officeQrMonitoringData);
</script>

<script>
  // Force-show warnings toggle - persist in localStorage
  const FORCE_WARN_KEY = 'forceShowPanoramaWarnings';
  window.forceShowPanoramaWarnings = (localStorage.getItem(FORCE_WARN_KEY) === '1');

  function setForceWarnState(enabled) {
    window.forceShowPanoramaWarnings = !!enabled;
    localStorage.setItem(FORCE_WARN_KEY, enabled ? '1' : '0');
    // Re-render markers for all floors
    clearAllPanoramaMarkers();
    for (let f = 1; f <= 3; f++) addPanoramaMarkers(f);
    // Update header count
    updatePanoramaWarningCount();
  }

  document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.getElementById('force-warnings-checkbox');
    if (checkbox) {
      checkbox.checked = window.forceShowPanoramaWarnings;
      checkbox.addEventListener('change', (e) => {
        setForceWarnState(e.target.checked);
      });
    }
    // Update warning count on load
    updatePanoramaWarningCount();
  });

  // Compute and update the number of stale panoramas shown in the header
  function computeStaleCount() {
    const dataSource = window.panoramaMapData || panoramaMapData || [];
    if (!dataSource || dataSource.length === 0) return 0;
    let count = 0;
    dataSource.forEach(p => {
      if (window.forceShowPanoramaWarnings) {
        count++;
      } else if (window.panoramaHasLastScanned) {
        if (!p.last_scanned_at) {
          count++;
        } else {
          const last = new Date(p.last_scanned_at + ' UTC');
          const now = new Date();
          const days = Math.floor((now - last) / (1000*60*60*24));
          if (days >= 7) count++;
        }
      }
    });
    return count;
  }

  function updatePanoramaWarningCount() {
    const el = document.getElementById('panorama-warning-number');
    if (!el) return;
    const staleCount = computeStaleCount();
    el.textContent = staleCount;
  }

  // Update count after data refresh or when toggling force-show
  window.updatePanoramaWarningCount = updatePanoramaWarningCount;
</script>

<script>
  // Top Offices Chart
  const officeNames = topOffices.map(office => office.name);
  const scanCounts = topOffices.map(office => office.scan_count);

  // Create gradients for the chart
  const canvas = document.getElementById('topOfficesChart');
  const ctx = canvas.getContext('2d');
  
  // Create gradient colors
  const gradients = [
    // Red gradient - original to lighter
    (() => {
      const gradient = ctx.createLinearGradient(0, 0, 400, 0);
      gradient.addColorStop(0, '#fc0000'); // original red on left
      gradient.addColorStop(1, '#f6a6a6'); // softer red on right (not white)
      return gradient;
    })(),
    // Green gradient - original to lighter
    (() => {
      const gradient = ctx.createLinearGradient(0, 0, 400, 0);
      gradient.addColorStop(0, '#0f9124'); // original green on left
      gradient.addColorStop(1, '#66c47a'); // softer green on right (not white)
      return gradient;
    })(),
    // Blue gradient - original to lighter
    (() => {
      const gradient = ctx.createLinearGradient(0, 0, 400, 0);
      gradient.addColorStop(0, '#74c0fc'); // original blue on left
      gradient.addColorStop(1, '#5fb3ff'); // lighter blue on right
      return gradient;
    })(),
    // Purple gradient - original to lighter
    (() => {
      const gradient = ctx.createLinearGradient(0, 0, 400, 0);
      gradient.addColorStop(0, '#b197fc'); // original purple on left
      gradient.addColorStop(1, '#8f6ef0'); // deeper purple on right (not white)
      return gradient;
    })(),
    // Orange gradient - original to lighter
    (() => {
      const gradient = ctx.createLinearGradient(0, 0, 400, 0);
      gradient.addColorStop(0, '#ffa94d'); // original orange on left
      gradient.addColorStop(1, '#ffb76b'); // softer orange on right
      return gradient;
    })()
  ];

  const topOfficesChart = new Chart(document.getElementById('topOfficesChart'), {
    type: 'bar',
    data: {
      labels: officeNames,
      datasets: [{
        label: 'QR Scans',
        data: scanCounts,
        backgroundColor: gradients,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        title: {
          display: false
        }
      },
      scales: {
        x: {
          beginAtZero: true
        }
      }
    }
  });

  // Monthly Visitor Log Chart
  const visitorCanvas = document.getElementById('visitorLogChart');
  const visitorCtx = visitorCanvas.getContext('2d');
  
  // Create gradients for visitor log chart
  const officeGradients = [
    // Green gradient for Office QR scans
    (() => {
      const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
      gradient.addColorStop(0, '#81C784');
      gradient.addColorStop(1, '#4CAF50');
      return gradient;
    })(),
    (() => {
      const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
      gradient.addColorStop(0, '#fcf7e2ff');
      gradient.addColorStop(1, '#ffcb30ff');
      return gradient;
    })(),
    (() => {
      const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
      gradient.addColorStop(0, '#a58fcbff');
      gradient.addColorStop(1, '#551db6ff');
      return gradient;
    })()
  ];

  const panoramaGradients = [
    // Blue gradients for Panorama views
    (() => {
      const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
      gradient.addColorStop(0, '#90CAF9');
      gradient.addColorStop(1, '#1976D2');
      return gradient;
    })(),
    (() => {
      const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
      gradient.addColorStop(0, '#BBDEFB');
      gradient.addColorStop(1, '#1565C0');
      return gradient;
    })(),
    (() => {
      const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
      gradient.addColorStop(0, '#E3F2FD');
      gradient.addColorStop(1, '#0D47A1');
      return gradient;
    })()
  ];

  const visitorLogChart = new Chart(document.getElementById('visitorLogChart'), {
    type: 'bar',
    data: {
      labels: ['Today', 'This Week', 'This Month'],
      datasets: [
        {
          label: 'Office QR Scans',
          data: [dailyCount, weeklyCount, monthlyCount],
          backgroundColor: officeGradients,
          borderRadius: 8
        },
        {
          label: 'Panorama Views',
          data: [dailyPanoramaCount, weeklyPanoramaCount, monthlyPanoramaCount],
          backgroundColor: panoramaGradients,
          borderRadius: 8
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { 
          display: true,
          position: 'top'
        },
        title: {
          display: false
        }
      },
      scales: {
        x: {
          stacked: false
        },
        y: {
          beginAtZero: true,
          stacked: false
        }
      }
    }
  });

  // Load floor plan SVGs and add panorama markers
  async function loadFloorPlans() {
    const floorSvgs = {
      1: 'SVG/Capitol_1st_floor_layout_20_modified.svg',
      2: 'SVG/Capitol_2nd_floor_layout_6_modified.svg',
      3: 'SVG/Capitol_3rd_floor_layout_6.svg'
    };

    for (const [floor, svgPath] of Object.entries(floorSvgs)) {
      try {
        console.log(`Loading floor ${floor} from ${svgPath}`);
        const response = await fetch(svgPath);
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const svgText = await response.text();
        const svgElement = document.getElementById(`floor-${floor}-svg`);
        
        if (!svgElement) {
          throw new Error(`SVG container for floor ${floor} not found`);
        }
        
        // Parse the SVG text to extract only the inner content
        const parser = new DOMParser();
        const svgDoc = parser.parseFromString(svgText, 'image/svg+xml');
        const loadedSvg = svgDoc.querySelector('svg');
        
        if (loadedSvg) {
          // Copy all child elements from the loaded SVG
          while (svgElement.firstChild) {
            svgElement.removeChild(svgElement.firstChild);
          }
          
          // Copy all attributes from the loaded SVG except id
          Array.from(loadedSvg.attributes).forEach(attr => {
            if (attr.name !== 'id') {
              svgElement.setAttribute(attr.name, attr.value);
            }
          });
          
          // Copy all child elements
          while (loadedSvg.firstChild) {
            svgElement.appendChild(loadedSvg.firstChild);
          }
          
          console.log(`Floor ${floor} SVG loaded successfully`);
        } else {
          throw new Error(`Invalid SVG content for floor ${floor}`);
        }
        
        // Add panorama markers for this floor with a small delay
        setTimeout(() => {
          addPanoramaMarkers(floor);
        }, 50);
      } catch (error) {
        console.error(`Error loading floor ${floor} SVG:`, error);
        
        // Show error message in the SVG container
        const svgElement = document.getElementById(`floor-${floor}-svg`);
        if (svgElement) {
          svgElement.innerHTML = `
            <text x="50%" y="50%" text-anchor="middle" fill="#666" font-size="16">
              Error loading Floor ${floor} map
            </text>
          `;
        }
      }
    }
  }

  // Add panorama markers to floor plan
  function addPanoramaMarkers(floorNumber) {
    console.log(`🏗️ Adding panorama markers for floor ${floorNumber}`);
    
    const svgElement = document.getElementById(`floor-${floorNumber}-svg`);
    if (!svgElement) {
      console.error(`❌ SVG element not found for floor ${floorNumber}`);
      return;
    }
    
    // Check if we have panorama data
    if (!window.panoramaMapData && !panoramaMapData) {
      console.error(`❌ No panorama data available`);
      return;
    }
    
    // Use the global panorama data
    const dataSource = window.panoramaMapData || panoramaMapData;
    const floorPanoramas = dataSource.filter(p => p.floor_number == floorNumber);
    console.log(`📍 Found ${floorPanoramas.length} panoramas for floor ${floorNumber}:`, floorPanoramas);
    
    if (floorPanoramas.length === 0) {
      console.log(`ℹ️ No panoramas to add for floor ${floorNumber}`);
      return;
    }
    
    let markersAdded = 0;
    
    floorPanoramas.forEach((panorama, index) => {
      console.log(`🎯 Adding marker ${index + 1}/${floorPanoramas.length}:`, panorama);
      // Determine marker class based on scan count
      let markerClass = 'no-activity';
      if (panorama.scan_count >= 50) markerClass = 'high-activity';
      else if (panorama.scan_count >= 10) markerClass = 'medium-activity';
      else if (panorama.scan_count >= 1) markerClass = 'low-activity';

      // Create pin marker group
      const markerGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
      markerGroup.setAttribute('class', `panorama-pin ${markerClass}`);
      markerGroup.setAttribute('transform', `translate(${panorama.point_x}, ${panorama.point_y})`);
      
      // Create pin shape (teardrop/map pin) - Made larger
      const pin = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      pin.setAttribute('d', 'M0,-30 C-12,-30 -22,-20 -22,-8 C-22,4 0,30 0,30 C0,30 22,4 22,-8 C22,-20 12,-30 0,-30 Z');
      pin.setAttribute('class', 'pin-shape');
      
      // Create inner circle for count - Made larger
      const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      circle.setAttribute('cx', '0');
      circle.setAttribute('cy', '-12');
      circle.setAttribute('r', '12');
      circle.setAttribute('class', 'pin-circle');
      
      // Create text for scan count
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', '0');
      text.setAttribute('y', '-8');
      text.setAttribute('text-anchor', 'middle');
      text.setAttribute('class', 'pin-text');
      text.textContent = panorama.scan_count;
      
      // Add tooltip
      const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
      title.textContent = `${panorama.title || 'Panorama'} - ${panorama.scan_count} views`;
      
      markerGroup.appendChild(pin);
      markerGroup.appendChild(circle);
      markerGroup.appendChild(text);
      markerGroup.appendChild(title);

      // Compute staleness and add warning if needed (>=7 days)
      let isStale = false;
      let daysSince = null;

      // Force-show toggle: override DB-based checks for testing
      if (window.forceShowPanoramaWarnings) {
        isStale = true;
      } else {
        // Only compute staleness when the DB provides last_scanned_at (migration applied)
        if (window.panoramaHasLastScanned) {
          if (panorama.last_scanned_at) {
            const last = new Date(panorama.last_scanned_at + ' UTC');
            const now = new Date();
            daysSince = Math.floor((now - last) / (1000 * 60 * 60 * 24));
            if (daysSince >= 7) isStale = true;
          } else {
            // Never scanned -> considered stale
            isStale = true;
          }
        } else {
          // If the DB doesn't have last_scanned_at, do NOT mark pins stale
          isStale = false;
        }
      }

      if (isStale) {
        markerGroup.classList.add('stale-warning');
        // update title to include warning
        let warnText = title.textContent + '\nWARNING: ';
        if (daysSince === null) warnText += 'Never scanned';
        else if (daysSince < 30) warnText += `Not scanned for ${daysSince} day${daysSince === 1 ? '' : 's'}`;
        else warnText += `Not scanned for ${Math.floor(daysSince/7)} week(s)`;
        title.textContent = warnText;

        // Add custom hover tooltip for clearer UI
        const tooltip = document.createElement('div');
        tooltip.className = 'panorama-hover-tooltip';
        tooltip.style.position = 'absolute';
        tooltip.style.padding = '6px 8px';
        tooltip.style.borderRadius = '6px';
        tooltip.style.background = 'rgba(220, 38, 38, 0.95)';
        tooltip.style.color = 'white';
        tooltip.style.fontSize = '12px';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.zIndex = 10000;
        tooltip.style.display = 'none';
        tooltip.textContent = (daysSince === null) ? 'Never scanned' : `Not scanned for ${daysSince} day${daysSince === 1 ? '' : 's'}`;
        document.body.appendChild(tooltip);

        markerGroup.addEventListener('mouseenter', (ev) => {
          tooltip.style.display = 'block';
          tooltip.style.left = `${ev.clientX + 10}px`;
          tooltip.style.top = `${ev.clientY + 10}px`;
        });
        markerGroup.addEventListener('mousemove', (ev) => {
          tooltip.style.left = `${ev.clientX + 10}px`;
          tooltip.style.top = `${ev.clientY + 10}px`;
        });
        markerGroup.addEventListener('mouseleave', () => {
          tooltip.style.display = 'none';
        });

        // Add warning SVG icon above the pin
        const warningGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        warningGroup.setAttribute('transform', 'translate(0, -52)'); // position above the pin
        warningGroup.setAttribute('class', 'warning-icon');

        // Triangle background
        const tri = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        tri.setAttribute('d', 'M0,-6 L8,10 L-8,10 Z');
        tri.setAttribute('fill', '#ff4444');
        tri.setAttribute('stroke', '#8b0000');
        tri.setAttribute('stroke-width', '0.8');

        // Exclamation bar
        const bar = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bar.setAttribute('x', '-1');
        bar.setAttribute('y', '0');
        bar.setAttribute('width', '2');
        bar.setAttribute('height', '6');
        bar.setAttribute('fill', '#fff');

        // Exclamation dot
        const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('cx', '0');
        dot.setAttribute('cy', '8');
        dot.setAttribute('r', '1.4');
        dot.setAttribute('fill', '#fff');

        warningGroup.appendChild(tri);
        warningGroup.appendChild(bar);
        warningGroup.appendChild(dot);
        markerGroup.appendChild(warningGroup);
      }

      svgElement.appendChild(markerGroup);
      markersAdded++;

      console.log(`✅ Added marker for ${panorama.title} at (${panorama.point_x}, ${panorama.point_y}) with ${panorama.scan_count} scans`);
    });
    
    console.log(`🎉 Successfully added ${markersAdded} markers to floor ${floorNumber}`);
  }

  // Floor selector functionality
  window.showFloor = function(floorNumber, clickedButton, event) {
    // Prevent any default behavior or form submission
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    
    console.log(`Switching to floor ${floorNumber}`);
    
    // Hide all floor maps
    document.querySelectorAll('.floor-map').forEach(map => {
      map.classList.remove('active');
    });
    
    // Show selected floor
    const targetFloor = document.getElementById(`floor-${floorNumber}-map`);
    if (targetFloor) {
      targetFloor.classList.add('active');
      console.log(`Floor ${floorNumber} map activated`);
    } else {
      console.error(`Floor ${floorNumber} map not found`);
    }
    
    // Update button states
    document.querySelectorAll('.floor-btn').forEach(btn => {
      btn.classList.remove('active');
    });
    
    // Add active class to clicked button
    if (clickedButton) {
      clickedButton.classList.add('active');
      console.log(`Button for floor ${floorNumber} activated`);
    } else {
      // Fallback: find the button by floor number
      const targetBtn = document.querySelector(`.floor-btn[onclick*="${floorNumber}"]`);
      if (targetBtn) {
        targetBtn.classList.add('active');
      }
    }
    
    return false; // Prevent any form submission
  }

  // Initialize floor button event listeners
  function initializeFloorButtons() {
    document.querySelectorAll('.floor-btn').forEach(button => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const floorNumber = this.getAttribute('data-floor');
        showFloor(parseInt(floorNumber), this, event);
        
        return false;
      });
    });
    
    // Refresh button removed from the UI (no handler needed)
    
    // Add panorama filter event listener
    const panoramaFilter = document.getElementById('panorama-time-filter');
    if (panoramaFilter) {
      panoramaFilter.addEventListener('change', function(event) {
        const filterValue = this.value;
        console.log(`� Filtering panorama data by: ${filterValue}`);
        
        // Update markers based on filter
        applyPanoramaTimeFilter(filterValue);
      });
    }
  }

  // Function to apply time-based filtering to panorama markers
  async function applyPanoramaTimeFilter(filterValue) {
    try {
      // Get filtered panorama data
      const response = await fetch(`get_panorama_data.php?filter=${filterValue}`);
      const data = await response.json();
      
      if (data.success) {
        // Update global panorama data with filtered results
        window.panoramaMapData = data.panorama_data;
        panoramaMapData = data.panorama_data;
        
        // Clear existing markers and reload with filtered data
        clearAllPanoramaMarkers();
        
        // Re-add markers for all floors with new data
        for (let floor = 1; floor <= 3; floor++) {
          addPanoramaMarkers(floor);
        }
        
        console.log(`📊 Applied ${filterValue} filter: ${data.panorama_data.length} panoramas shown`);
        
        // Show notification about filter
        const filterText = {
          'all': 'All Time',
          'today': 'Today', 
          'week': 'This Week',
          'month': 'This Month'
        };
        showNotification(`Showing panorama views for: ${filterText[filterValue]}`, 'info');
        
      } else {
        console.error('Failed to apply filter:', data.error);
        showNotification('Failed to apply time filter', 'error');
      }
    } catch (error) {
      console.error('Error applying time filter:', error);
      showNotification('Error applying time filter', 'error');
    }
  }

  // Function to refresh panorama data and markers
  async function refreshPanoramaData() {
    console.log('🔄 Refreshing panorama data...');
    
    try {
      // Get current filter value
      const filterSelect = document.getElementById('panorama-time-filter');
      const currentFilter = filterSelect ? filterSelect.value : 'all';
      
      const response = await fetch(`get_panorama_data.php?filter=${currentFilter}`);
      console.log('📡 Response received:', response.status);
      
      const data = await response.json();
      console.log('📊 Data received:', data);
      
      if (data.success) {
        // Check if there are new panoramas
        const oldCount = window.panoramaMapData ? window.panoramaMapData.length : 0;
        const newCount = data.panorama_data.length;
        
        console.log(`📈 Panorama count - Old: ${oldCount}, New: ${newCount}`);
        
        // Update global panorama data
        window.panoramaMapData = data.panorama_data;
        panoramaMapData = data.panorama_data; // Also update the original variable
        
        console.log('🗂️ Updated panoramaMapData:', panoramaMapData);
        
        // Clear existing markers and reload them
        clearAllPanoramaMarkers();
        console.log('🧹 Cleared all existing markers');
        
        // Re-add markers for all floors
        for (let floor = 1; floor <= 3; floor++) {
          const floorPanoramas = panoramaMapData.filter(p => p.floor_number == floor);
          console.log(`📍 Floor ${floor}: ${floorPanoramas.length} panoramas`, floorPanoramas);
          addPanoramaMarkers(floor);
        }

        // Update warning count after re-adding markers
        updatePanoramaWarningCount();
        
        console.log(`✅ Panorama data refreshed successfully. Total: ${newCount} panoramas`);
        return true;
      } else {
        console.error('❌ Failed to refresh panorama data:', data.error);
        showNotification('Failed to refresh panorama data', 'error');
        return false;
      }
    } catch (error) {
      console.error('💥 Error refreshing panorama data:', error);
      showNotification('Error refreshing panorama data', 'error');
      return false;
    }
  }

  // Function to show notifications
  function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    Object.assign(notification.style, {
      position: 'fixed',
      top: '20px',
      right: '20px',
      padding: '12px 16px',
      borderRadius: '6px',
      color: 'white',
      fontSize: '14px',
      fontWeight: '500',
      zIndex: '10000',
      boxShadow: '0 4px 12px rgba(0, 0, 0, 0.2)',
      transform: 'translateX(400px)',
      transition: 'transform 0.3s ease'
    });
    
    // Set background color based on type
    switch (type) {
      case 'success':
        notification.style.backgroundColor = '#4caf50';
        break;
      case 'error':
        notification.style.backgroundColor = '#f44336';
        break;
      default:
        notification.style.backgroundColor = '#2196f3';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
      notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
      notification.style.transform = 'translateX(400px)';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 4000);
  }

  // Function to clear all panorama markers from all floors
  function clearAllPanoramaMarkers() {
    for (let floor = 1; floor <= 3; floor++) {
      const svgElement = document.getElementById(`floor-${floor}-svg`);
      if (svgElement) {
        // Remove all existing panorama pins
        const existingPins = svgElement.querySelectorAll('.panorama-pin');
        existingPins.forEach(pin => pin.remove());
      }
    }
  }

  // Auto-refresh panorama data every 30 seconds
  function startAutoRefresh() {
    setInterval(() => {
      refreshPanoramaData();
    }, 30000); // Refresh every 30 seconds
  }

  // Initialize floor plans after DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeFloorButtons();
    setTimeout(() => {
      loadFloorPlans();
      // Force refresh after initial load to get latest data
      setTimeout(() => {
        console.log('🔄 Force refresh after initial load...');
        refreshPanoramaData();
      }, 2000);
      startAutoRefresh(); // Start auto-refresh
    }, 100);
  });

  // If DOM is already ready, initialize immediately
  if (document.readyState === 'loading') {
    // Do nothing, event listener above will handle it
  } else {
    initializeFloorButtons();
    setTimeout(() => {
      loadFloorPlans();
      // Force refresh after initial load to get latest data
      setTimeout(() => {
        console.log('🔄 Force refresh after initial load...');
        refreshPanoramaData();
      }, 2000);
      startAutoRefresh(); // Start auto-refresh
    }, 100);
  }

  // Expose refresh function globally for manual calls
  window.refreshPanoramaData = refreshPanoramaData;

  // Refresh when page becomes visible (user returns to tab)
  document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
      console.log('Page became visible, checking for new panoramas...');
      setTimeout(() => {
        refreshPanoramaData();
      }, 1000); // Small delay to ensure any ongoing operations complete
    }
  });

  // Office QR Monitoring JavaScript
  function initializeOfficeQrMonitoring() {
    const filterSelect = document.getElementById('office-qr-status-filter');
    const officeItems = document.querySelectorAll('.office-qr-item');
    
    if (filterSelect) {
      filterSelect.addEventListener('change', function() {
        const filterValue = this.value;
        console.log(`Filtering office QR items by: ${filterValue}`);
        
        officeItems.forEach(item => {
          let showItem = true;
          
          switch (filterValue) {
            case 'active':
              showItem = item.dataset.status === 'active';
              break;
            case 'inactive':
              showItem = item.dataset.status === 'inactive';
              break;
            case 'stale':
              showItem = parseInt(item.dataset.days) >= 7 && item.dataset.scanned === 'yes';
              break;
            case 'never':
              showItem = item.dataset.scanned === 'no';
              break;
            case 'all':
            default:
              showItem = true;
              break;
          }
          
          item.style.display = showItem ? 'flex' : 'none';
        });
        
        // Update counts after filtering
        updateOfficeQrCounts(filterValue);
      });
    }
  }
  
  function updateOfficeQrCounts(filterValue) {
    const visibleItems = document.querySelectorAll('.office-qr-item[style*="display: flex"], .office-qr-item:not([style*="display: none"])');
    const warningNumber = document.getElementById('office-qr-warning-number');
    
    let warningCount = 0;
    visibleItems.forEach(item => {
      const isStale = parseInt(item.dataset.days) >= 7;
      const neverScanned = item.dataset.scanned === 'no';
      if (isStale || neverScanned) {
        warningCount++;
      }
    });
    
    if (warningNumber) {
      warningNumber.textContent = warningCount;
    }
  }

  // Auto-submit form when any filter changes
  ['month', 'year'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener('change', () => {
        document.querySelector('.filter-controls').submit(); // Changed to .filter-controls

      });
    }
  });
  
  // Initialize office QR monitoring when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeOfficeQrMonitoring();
  });
</script>
</body>
</html>
