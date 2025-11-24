
<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

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

// Office QR Monitoring Data (with doorpoint support)
// Get office QR codes AND door QR codes with last scanned information
// CRITICAL: Only count door-level scans (where door_index IS NOT NULL)
// This excludes legacy office-level scans from statistics
$officeQrMonitoringStmt = $connect->prepare("
    SELECT 
        o.id as office_id,
        o.name as office_name,
        o.location as room_location,
        dqr.id as door_qr_id,
        dqr.door_index,
        dqr.is_active,
        COUNT(DISTINCT qsl.id) as total_scans,
        MAX(qsl.check_in_time) as last_scanned_at,
        COALESCE(
            CASE 
                WHEN MAX(qsl.check_in_time) IS NULL THEN 999
                ELSE DATEDIFF(NOW(), MAX(qsl.check_in_time))
            END, 999
        ) as days_since_last_scan,
        -- Count today's scans (only door-level scans)
        SUM(CASE WHEN DATE(qsl.check_in_time) = CURDATE() THEN 1 ELSE 0 END) as today_scans
    FROM offices o
    INNER JOIN door_qrcodes dqr ON o.id = dqr.office_id
    LEFT JOIN qr_scan_logs qsl ON (
        o.id = qsl.office_id 
        AND dqr.door_index = qsl.door_index 
        AND qsl.door_index IS NOT NULL
    )
    GROUP BY o.id, o.name, o.location, dqr.id, dqr.door_index, dqr.is_active
    ORDER BY days_since_last_scan DESC, total_scans DESC
");
$officeQrMonitoringStmt->execute();
$officeQrMonitoringData = $officeQrMonitoringStmt->fetchAll(PDO::FETCH_ASSOC);

// Count door QR codes by status categories
$activeQrCount = count(array_filter($officeQrMonitoringData, fn($door) => $door['is_active'] == 1));
$inactiveQrCount = count(array_filter($officeQrMonitoringData, fn($door) => $door['is_active'] == 0));
$staleQrCount = count(array_filter($officeQrMonitoringData, fn($door) => $door['is_active'] == 1 && $door['days_since_last_scan'] >= 7 && $door['last_scanned_at'] !== null));
$neverScannedCount = count(array_filter($officeQrMonitoringData, fn($door) => $door['last_scanned_at'] === null));
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
  <title>GABAY Admin Dashboard</title>
  <link rel="stylesheet" href="home.css" />
  <link rel="stylesheet" href="assets/css/system-fonts.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="mobileNav.css" />
    <link rel="stylesheet" href="filter-styles.css"> <!-- Add this line -->
  <script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
  <script src="auth_helper.js"></script>
    <style>
  /* SVG Text Styling - Professional & Accessible with Forced Font Loading */
  .room-label,
  text[id*="roomlabel"],
  text[id*="text-"],
  tspan[id*="roomlabel"],
  svg text,
  svg tspan {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    fill: #1a1a1a !important;
    stroke: #ffffff !important;
    stroke-width: 3px !important;
    stroke-linejoin: round !important;
    paint-order: stroke fill !important;
    text-anchor: middle !important;
    dominant-baseline: central !important;
    pointer-events: none !important;
    user-select: none !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    vector-effect: non-scaling-stroke !important;
    font-display: swap !important;
  }
  
  /* Stale warning pin style: do not change base pin fill; overlay a warning icon instead */
  .panorama-pin .pin-shape { fill: #04aa6d; stroke-width: 1px; }
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
    /* Removed max-height constraint to allow full content display */
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
    min-height: 500px;
    max-height: 800px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    padding: 10px;
  }
  
  .office-qr-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s;
    min-height: 70px;
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
    margin-bottom: 8px;
  }
  
  .office-name strong {
    color: #2e7d32;
    font-size: 16px;
  }
  
  .scan-stats {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: #666;
  }
  
  .scan-count {
    font-weight: 600;
    color: #1976d2;
    font-size: 14px;
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
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 13px;
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
  
  /* Full-width panel styles */
  .full-width {
    width: 100%;
    max-width: none;
    margin: 20px 0;
    box-sizing: border-box;
  }
  
  .activity-panel.full-width,
  .actions-panel.full-width {
    width: 100%;
    padding: 20px 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }
  
  /* Ensure panels don't get constrained by any parent containers */
  .activity-panel.full-width > *,
  .actions-panel.full-width > * {
    max-width: 100%;
  }

  /* Clickable Card Styles */
  .clickable-card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .clickable-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
  }

  .clickable-card:active {
    transform: translateY(-2px);
  }

  .clickable-card::after {
    content: '→';
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    opacity: 0;
    transition: opacity 0.2s ease, right 0.2s ease;
    color: currentColor;
  }

  .clickable-card:hover::after {
    opacity: 0.6;
    right: 15px;
  }

  /* QR Report Modal Styles */
  .report-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    overflow-y: auto;
  }

  .report-modal-content {
    background-color: #fefefe;
    margin: 20px auto;
    border-radius: 12px;
    width: 95%;
    max-width: 1400px;
    max-height: 95vh;
    overflow-y: auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  }

  .report-modal-header {
    padding: 20px 30px;
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .report-modal-header h2 {
    margin: 0;
    font-size: 24px;
  }

  .report-modal-close {
    color: white;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
    transition: transform 0.2s;
  }

  .report-modal-close:hover {
    transform: scale(1.2);
  }

  .report-modal-body {
    padding: 30px;
  }

  .report-controls-compact {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid #e2e8f0;
  }

  .controls-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
  }

  .control-group {
    display: flex;
    flex-direction: column;
  }

  .control-group label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
    font-size: 13px;
  }

  .control-group select,
  .control-group input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
  }

  .control-group select:focus,
  .control-group input:focus {
    outline: none;
    border-color: #2e7d32;
  }

  .button-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  /* Modern Button Styles */
  .btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  }

  .btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  }

  .btn-primary {
    background: linear-gradient(135deg, #2e7d32 0%, #1a5632 100%);
    color: white;
  }

  .btn-primary:hover {
    background: linear-gradient(135deg, #1a5632 0%, #0d3018 100%);
  }

  .btn-primary::before {
    content: "🔄";
    font-size: 14px;
  }

  .btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
  }

  .btn-secondary:hover {
    background: linear-gradient(135deg, #495057 0%, #343a40 100%);
  }

  .btn-secondary:last-child::before {
    content: "🖨️";
    font-size: 14px;
  }

  /* Dropdown Container Styles */
  .dropdown-container {
    position: relative;
    display: inline-block;
  }

  .dropdown-trigger {
    position: relative;
    padding-right: 35px;
  }

  .dropdown-trigger::after {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
  }

  .dropdown-container.active .dropdown-trigger::after {
    transform: translateY(-50%) rotate(180deg);
  }

  .report-section-modal {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
  }

  .report-section-modal h3 {
    margin-top: 0;
    color: #2e7d32;
    border-bottom: 2px solid #e8f5e8;
    padding-bottom: 10px;
    font-size: 18px;
  }

  .summary-grid-modal {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-top: 15px;
  }

  .summary-card-modal {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #e8f5e8 100%);
    border-radius: 8px;
    border: 1px solid #d1e7dd;
  }

  .summary-card-modal h4 {
    font-size: 32px;
    margin: 10px 0;
    color: #2e7d32;
  }

  .summary-card-modal p {
    margin: 0;
    color: #666;
    font-weight: 600;
    font-size: 13px;
  }

  .chart-container-modal {
    position: relative;
    height: 300px;
    margin-top: 15px;
  }

  .chart-container-modal-small {
    position: relative;
    height: 250px;
    margin-top: 15px;
  }

  .report-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
  }

  .report-tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
  }

  .table-scroll {
    max-height: 400px;
    overflow-y: auto;
  }

  .report-section-modal table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }

  .report-section-modal th,
  .report-section-modal td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    font-size: 13px;
  }

  .report-section-modal th {
    background: #f8fafc;
    font-weight: 600;
    color: #2e7d32;
    position: sticky;
    top: 0;
  }

  .report-section-modal tr:hover {
    background: #f8fafc;
  }

  .modal-loading {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    text-align: center;
    z-index: 10001;
  }

  .spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #2e7d32;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Download Dropdown Styles */
  .dropdown-container {
    position: relative;
    display: inline-block;
  }

  .dropdown-trigger {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .download-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    min-width: 220px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    margin-top: 8px;
    z-index: 10002;
    overflow: hidden;
    border: 1px solid #e2e8f0;
  }

  .download-dropdown.show {
    display: block;
    animation: dropdownFadeIn 0.3s ease;
  }

  .download-dropdown a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    color: #333;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 1px solid #f0f0f0;
  }

  .download-dropdown a:last-child {
    border-bottom: none;
  }

  .download-dropdown a:hover {
    background: linear-gradient(135deg, #e8f5e9 0%, #f0f9ff 100%);
    color: #2e7d32;
    padding-left: 22px;
  }

  .download-dropdown a:hover svg {
    transform: scale(1.1);
  }

  .download-dropdown a svg {
    flex-shrink: 0;
    transition: transform 0.2s ease;
  }

  @keyframes dropdownFadeIn {
    from {
      opacity: 0;
      transform: translateY(-5px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media (max-width: 768px) {
    .report-modal-content {
      width: 98%;
      margin: 10px auto;
    }
    
    .controls-row {
      grid-template-columns: 1fr;
    }
    
    .report-charts-grid,
    .report-tables-grid {
      grid-template-columns: 1fr;
    }
  }

  /* Print Styles for Report Modal */
  @media print {
    body * {
      visibility: hidden;
    }
    
    #qr-report-modal,
    #qr-report-modal * {
      visibility: visible;
    }
    
    #qr-report-modal {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      height: auto;
      background: white !important;
      overflow: visible;
    }
    
    .report-modal-overlay {
      position: relative;
      background: white !important;
    }
    
    .report-modal-container {
      max-width: 100%;
      max-height: none;
      overflow: visible;
      box-shadow: none;
    }
    
    .report-modal-content {
      max-height: none;
      overflow: visible;
    }
    
    .report-modal-close,
    .button-row,
    .report-controls-compact {
      display: none !important;
    }
    
    .report-section-modal {
      page-break-inside: avoid;
      margin-bottom: 20px;
      border: 1px solid #ddd;
      padding: 15px;
    }
    
    .report-section-modal h3 {
      color: #2e7d32 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    
    .summary-card-modal {
      border: 1px solid #ddd !important;
      background: #f5f5f5 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    
    table {
      border-collapse: collapse;
      width: 100%;
    }
    
    th, td {
      border: 1px solid #333 !important;
      padding: 8px;
      text-align: left;
    }
    
    th {
      background-color: #e0e0e0 !important;
      font-weight: bold;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    
    canvas {
      max-width: 100% !important;
      height: auto !important;
    }
    
    .chart-container-modal {
      page-break-inside: avoid;
      margin: 20px 0;
    }
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
  <div class="card green clickable-card" onclick="navigateToReport('office')" title="Click to view detailed Office QR scan report">
    <div class="card-left">
      <p>Office Visitors</p>
      <h3><?php echo $totalVisitors; ?></h3>
      <span class="growth">
        <svg class="growth-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        QR Scans
      </span>
      <span class="report-badge">Generate Report</span>
    </div>
    <div class="card-right">
      <svg class="icon large" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
    </div>
  </div>

  <div class="card blue clickable-card" onclick="navigateToReport('panorama')" title="Click to view detailed Panorama QR scan report">
    <div class="card-left">
      <p>Panorama Views</p>
      <h3><?php echo $totalPanoramaViews; ?></h3>
      <span class="growth">
        <svg class="growth-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        360° Views
      </span>
      <span class="report-badge">Generate Report</span>
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

<!-- Most Visited Offices Chart -->
<div class="activity-panel full-width">
  <div class="panel-header">
    <h3>Most Visited Offices</h3>
    <button type="button" id="view-all-offices-btn" class="view-all" style="cursor: pointer;">
      <i class="fa fa-list" style="margin-right: 4px;"></i>
      View All
    </button>
  </div>
  <canvas id="topOfficesChart" height="250"></canvas>
</div>

<!-- Monthly Visitor Log Chart -->
<div class="actions-panel full-width">
  <h3>Monthly Visitor Log</h3>
  <canvas id="visitorLogChart" height="250"></canvas>
</div>

<!-- Office QR Monitoring (moved to top) -->
<div class="actions-panel full-width">
  <div class="panel-header">
    <h3>Office QR Code Monitoring</h3>
    <div class="panel-controls" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
      <div class="office-qr-filter">
        <label for="qr-sort-by" style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Sort by Scans:</label>
        <select id="qr-sort-by" class="time-filter-select">
          <option value="desc">Most Scanned First</option>
          <option value="asc">Least Scanned First</option>
        </select>
      </div>
      <div class="office-qr-filter">
        <label for="qr-filter-by" style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Filter:</label>
        <select id="qr-filter-by" class="time-filter-select">
          <option value="all">All Door QR Codes</option>
          <option value="today">Today's Scans Only</option>
          <option value="latest">Latest Scanned</option>
          <option value="active">Active Only</option>
          <option value="inactive">Inactive Only</option>
          <option value="stale">Stale (7+ days)</option>
          <option value="never">Never Scanned</option>
        </select>
      </div>
      <div class="office-qr-filter">
        <button type="button" onclick="location.reload();" style="padding: 8px 16px; background: #04aa6d; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; margin-top: 23px;" title="Reload data to see latest scans">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
          </svg>
          Refresh Data
        </button>
      </div>
      <div style="margin-top: 23px; font-size: 12px; color: #666;">
        <small>Last updated: <?php echo date('M j, Y g:i:s A'); ?></small>
      </div>
    </div>
  </div>
  <div class="office-qr-monitoring-container">
    <!-- QR Statistics -->
    <div class="office-qr-stats">
      <div class="stat-item">
        <h4><?php echo $activeQrCount; ?></h4>
        <p>Active Door QR Codes</p>
        <small>Ready for visitors</small>
      </div>
      <div class="stat-item">
        <h4><?php echo $staleQrCount; ?></h4>
        <p>Stale Door QR Codes</p>
        <small>Not scanned in 7+ days</small>
      </div>
      <div class="stat-item">
        <h4><?php echo $neverScannedCount; ?></h4>
        <p>Never Scanned</p>
        <small>Door QR codes with no visits</small>
      </div>
    </div>

    <!-- Door QR List -->
    <div class="office-qr-list" id="office-qr-list">
      <?php 
      // Group by office for better display
      $groupedData = [];
      foreach ($officeQrMonitoringData as $door) {
        $officeId = $door['office_id'];
        if (!isset($groupedData[$officeId])) {
          $groupedData[$officeId] = [
            'office_name' => $door['office_name'],
            'room_location' => $door['room_location'],
            'doors' => []
          ];
        }
        $groupedData[$officeId]['doors'][] = $door;
      }

      foreach ($groupedData as $officeId => $officeData): ?>
        <div class="office-group" style="margin-bottom: 30px; border: 2px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
          <!-- Office Header -->
          <div class="office-group-header" style="background: linear-gradient(135deg, #04aa6d 0%, #039e61 100%); padding: 20px 25px; color: white;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0; font-size: 20px; font-weight: 600;"><?php echo htmlspecialchars($officeData['office_name']); ?></h3>
                <p style="margin: 8px 0 0 0; font-size: 15px; opacity: 0.95;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="white" style="vertical-align: middle; margin-right: 6px;">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                  </svg>
                  <?php echo htmlspecialchars($officeData['room_location']); ?>
                </p>
              </div>
              <div style="background: rgba(255,255,255,0.25); padding: 8px 16px; border-radius: 20px; font-size: 15px; font-weight: 600;">
                <?php echo count($officeData['doors']); ?> Door<?php echo count($officeData['doors']) != 1 ? 's' : ''; ?>
              </div>
            </div>
          </div>

          <!-- Door QR Codes List -->
          <div class="door-qr-list">
            <?php foreach ($officeData['doors'] as $door): ?>
              <div class="office-qr-item door-qr-item" 
                   data-status="<?php echo $door['is_active'] ? 'active' : 'inactive'; ?>"
                   data-days="<?php echo $door['days_since_last_scan']; ?>"
                   data-scanned="<?php echo $door['last_scanned_at'] ? 'yes' : 'no'; ?>"
                   data-scan-count="<?php echo $door['total_scans']; ?>"
                   data-today-scans="<?php echo $door['today_scans'] ?? 0; ?>"
                   data-last-scan-timestamp="<?php echo $door['last_scanned_at'] ? strtotime($door['last_scanned_at']) : 0; ?>"
                   style="border-bottom: 1px solid #f3f4f6; padding: 15px 20px;">
                <div class="office-qr-info" style="flex: 1;">
                  <div class="office-name" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span style="background: #f3f4f6; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #374151;">
                      🚪 Door <?php echo $door['door_index'] + 1; ?>
                    </span>
                    <?php if ($door['today_scans'] > 0): ?>
                      <span style="background: #dbeafe; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                        +<?php echo $door['today_scans']; ?> today
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="scan-stats" style="display: flex; gap: 15px; font-size: 13px; color: #6b7280;">
                    <span class="scan-count" style="font-weight: 600; color: #374151;">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm15 0h-2v3h-3v2h3v3h2v-3h3v-2h-3v-3z"/>
                      </svg>
                      <?php echo $door['total_scans']; ?> total scans
                    </span>
                    <?php if ($door['last_scanned_at']): ?>
                      <span class="last-scan">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                          <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/>
                        </svg>
                        Last: <?php echo date('M j, Y g:i A', strtotime($door['last_scanned_at'])); ?>
                      </span>
                    <?php else: ?>
                      <span class="last-scan never" style="color: #ef4444; font-weight: 500;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                        Never scanned
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="office-qr-status">
                  <?php 
                    $statusClass = 'status-active';
                    $statusText = 'Active';
                    $warningIcon = '';
                    
                    if (!$door['is_active']) {
                      $statusClass = 'status-inactive';
                      $statusText = 'Inactive';
                      $warningIcon = '<svg width="14" height="14" viewBox="0 0 24 24" style="margin-right:4px; vertical-align: middle;"><circle cx="12" cy="12" r="10" fill="#9ca3af"/><path d="M15 9l-6 6M9 9l6 6" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>';
                    } elseif ($door['last_scanned_at'] === null) {
                      $statusClass = 'status-warning';
                      $statusText = 'Never Scanned';
                      $warningIcon = '<svg width="14" height="14" viewBox="0 0 24 24" style="margin-right:4px; vertical-align: middle;"><path d="M12 2 2 20h20L12 2z" fill="#f59e0b"/><rect x="11" y="6.5" width="2" height="7" fill="#fff"/><circle cx="12" cy="16.2" r="1.5" fill="#fff"/></svg>';
                    } elseif ($door['days_since_last_scan'] >= 7) {
                      $statusClass = 'status-warning';
                      $statusText = $door['days_since_last_scan'] . ' days ago';
                      $warningIcon = '<svg width="14" height="14" viewBox="0 0 24 24" style="margin-right:4px; vertical-align: middle;"><path d="M12 2 2 20h20L12 2z" fill="#ff4444"/><rect x="11" y="6.5" width="2" height="7" fill="#fff"/><circle cx="12" cy="16.2" r="1.5" fill="#fff"/></svg>';
                    }
                  ?>
                  <span class="qr-status <?php echo $statusClass; ?>" style="display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                    <?php echo $warningIcon . $statusText; ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Panorama Analytics Floor Plan (moved below Office QR Monitoring) -->
<div class="activity-panel full-width">
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
        <span>High View Count (50+ scans)</span>
      </div>
      <div class="legend-item">
        <div class="legend-marker medium-activity"></div>
        <span>Medium View Count (10-49 scans)</span>
      </div>
      <div class="legend-item">
        <div class="legend-marker low-activity"></div>
        <span>Low View Count (1-9 scans)</span>
      </div>
      <div class="legend-item">
        <div class="legend-marker no-activity"></div>
        <span>No Views (0 scans)</span>
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
</div>

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

  // Open QR Report Modal with pre-selected filter
  function navigateToReport(reportType) {
    const selectedMonth = '<?php echo $selectedMonth; ?>';
    const selectedYear = '<?php echo $selectedYear; ?>';
    
    // Set report type
    document.getElementById('modal-report-type').value = reportType;
    
    // Calculate date range based on current dashboard filter
    if (selectedMonth !== 'all' && selectedYear !== 'all') {
      const dateFrom = selectedYear + '-' + selectedMonth.padStart(2, '0') + '-01';
      const lastDay = new Date(selectedYear, selectedMonth, 0).getDate();
      const dateTo = selectedYear + '-' + selectedMonth.padStart(2, '0') + '-' + lastDay;
      document.getElementById('modal-date-from').value = dateFrom;
      document.getElementById('modal-date-to').value = dateTo;
    } else if (selectedYear !== 'all') {
      document.getElementById('modal-date-from').value = selectedYear + '-01-01';
      document.getElementById('modal-date-to').value = selectedYear + '-12-31';
    } else {
      // Default to last 30 days
      const dateTo = new Date();
      const dateFrom = new Date();
      dateFrom.setDate(dateFrom.getDate() - 30);
      document.getElementById('modal-date-from').value = dateFrom.toISOString().split('T')[0];
      document.getElementById('modal-date-to').value = dateTo.toISOString().split('T')[0];
    }
    
    // Show modal
    document.getElementById('qr-report-modal').style.display = 'flex';
    
    // Auto-generate report
    generateModalReport();
  }

  function closeReportModal() {
    document.getElementById('qr-report-modal').style.display = 'none';
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('qr-report-modal');
    if (event.target === modal) {
      closeReportModal();
    }
  }
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
  // Door QR Code Filtering and Sorting
  document.addEventListener('DOMContentLoaded', function() {
    const sortSelect = document.getElementById('qr-sort-by');
    const filterSelect = document.getElementById('qr-filter-by');
    const qrList = document.getElementById('office-qr-list');

    if (!sortSelect || !filterSelect || !qrList) {
      console.warn('Door QR filter controls not found');
      return;
    }

    // Apply filters and sorting
    function applyFiltersAndSort() {
      const sortOrder = sortSelect.value; // 'asc' or 'desc'
      const filterType = filterSelect.value;

      // Get all office groups
      const officeGroups = Array.from(qrList.querySelectorAll('.office-group'));
      
      officeGroups.forEach(group => {
        const doorItems = Array.from(group.querySelectorAll('.door-qr-item'));
        let visibleDoors = 0;

        // Filter doors
        doorItems.forEach(item => {
          let show = true;

          switch(filterType) {
            case 'all':
              show = true;
              break;
            case 'today':
              // Show only doors scanned today
              show = parseInt(item.dataset.todayScans || '0') > 0;
              break;
            case 'latest':
              // Show all doors that have been scanned at least once
              const lastScanTimestamp = parseInt(item.dataset.lastScanTimestamp || '0');
              show = lastScanTimestamp > 0;
              break;
            case 'active':
              show = item.dataset.status === 'active';
              break;
            case 'inactive':
              show = item.dataset.status === 'inactive';
              break;
            case 'stale':
              // Show active doors not scanned in 7+ days (but have been scanned before)
              show = item.dataset.status === 'active' && 
                     parseInt(item.dataset.days || '0') >= 7 &&
                     item.dataset.scanned === 'yes';
              break;
            case 'never':
              // Show doors that have never been scanned
              show = item.dataset.scanned === 'no';
              break;
          }

          if (show) {
            item.style.display = '';
            visibleDoors++;
          } else {
            item.style.display = 'none';
          }
        });

        // Hide office group if no doors are visible
        if (visibleDoors === 0) {
          group.style.display = 'none';
        } else {
          group.style.display = '';

          // Sort visible doors
          const visibleDoorItems = doorItems.filter(item => item.style.display !== 'none');
          
          // Special sorting for "Latest Scanned" filter - sort by timestamp
          if (filterType === 'latest') {
            visibleDoorItems.sort((a, b) => {
              const timestampA = parseInt(a.dataset.lastScanTimestamp || '0');
              const timestampB = parseInt(b.dataset.lastScanTimestamp || '0');
              return timestampB - timestampA; // Most recent first
            });
          } else {
            // Normal sorting by scan count
            visibleDoorItems.sort((a, b) => {
              const countA = parseInt(a.dataset.scanCount || '0');
              const countB = parseInt(b.dataset.scanCount || '0');
              
              if (sortOrder === 'asc') {
                return countA - countB; // Least to most
              } else {
                return countB - countA; // Most to least
              }
            });
          }

          // Re-append in sorted order
          const doorList = group.querySelector('.door-qr-list');
          if (doorList) {
            visibleDoorItems.forEach(item => {
              doorList.appendChild(item);
            });
          }
        }
      });

      // Count visible items for feedback
      const visibleCount = officeGroups.filter(g => g.style.display !== 'none').length;
      console.log(`Showing ${visibleCount} office(s) with matching door QR codes`);
    }

    // Attach event listeners
    sortSelect.addEventListener('change', applyFiltersAndSort);
    filterSelect.addEventListener('change', applyFiltersAndSort);

    // Initial application of filters
    applyFiltersAndSort();

    // Make function globally accessible for external triggers
    window.applyDoorQrFiltersAndSort = applyFiltersAndSort;
  });
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
  
  // Create single consistent gradient for Office QR scans (green)
  const officeGradient = (() => {
    const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, '#81C784');
    gradient.addColorStop(1, '#4CAF50');
    return gradient;
  })();

  // Create single consistent gradient for Panorama views (blue)
  const panoramaGradient = (() => {
    const gradient = visitorCtx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, '#90CAF9');
    gradient.addColorStop(1, '#1976D2');
    return gradient;
  })();

  const visitorLogChart = new Chart(document.getElementById('visitorLogChart'), {
    type: 'bar',
    data: {
      labels: ['Today', 'This Week', 'This Month'],
      datasets: [
        {
          label: 'Office QR Scans',
          data: [dailyCount, weeklyCount, monthlyCount],
          backgroundColor: officeGradient,
          borderRadius: 8
        },
        {
          label: 'Panorama Views',
          data: [dailyPanoramaCount, weeklyPanoramaCount, monthlyPanoramaCount],
          backgroundColor: panoramaGradient,
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

  // Store pan-zoom instances for each floor
  window.panZoomInstances = {};

  // Initialize pan-zoom for panorama map SVGs
  function initializePanZoom(svgElement, floorNumber) {
    if (typeof svgPanZoom !== 'undefined' && svgElement) {
      // Destroy existing instance if it exists
      if (window.panZoomInstances[floorNumber]) {
        window.panZoomInstances[floorNumber].destroy();
      }

      // Create new pan-zoom instance
      const panZoomInstance = svgPanZoom(svgElement, {
        zoomEnabled: true,
        controlIconsEnabled: true,
        fit: true,
        center: true,
        minZoom: 0.5,
        maxZoom: 10,
        dblClickZoomEnabled: true,
        mouseWheelZoomEnabled: true,
        preventMouseEventsDefault: true,
        zoomScaleSensitivity: 0.4,
        beforeZoom: function() {},
        onZoom: function() {},
        beforePan: function() {},
        onPan: function() {}
      });

      // Store instance for later use
      window.panZoomInstances[floorNumber] = panZoomInstance;

      // Handle window resize
      window.addEventListener('resize', () => {
        if (window.panZoomInstances[floorNumber]) {
          window.panZoomInstances[floorNumber].resize();
          window.panZoomInstances[floorNumber].fit();
          window.panZoomInstances[floorNumber].center();
        }
      });

      console.log(`✅ Pan-zoom initialized for floor ${floorNumber}`);
    } else {
      console.error('svg-pan-zoom library not loaded or SVG element not found.');
    }
  }

  // Function to apply consistent font styling to all SVG text elements
  function applyConsistentFontStyling(container) {
    const textElements = container.querySelectorAll('text, tspan');
    textElements.forEach(el => {
      el.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
      el.style.fontWeight = "600";
      el.style.fontSize = "14px";
      el.style.fill = "#1a1a1a";
      el.style.stroke = "#ffffff";
      el.style.strokeWidth = "3px";
      el.style.strokeLinejoin = "round";
      el.style.paintOrder = "stroke fill";
      el.style.vectorEffect = "non-scaling-stroke";
      el.setAttribute('class', 'room-label');
    });
  }

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
          
          // CRITICAL FIX: Namespace all <defs> IDs to prevent conflicts between floors
          // Each floor SVG has duplicate IDs like "swatch51", "linearGradient51", etc.
          // We need to make them unique by adding a floor prefix
          const floorPrefix = `floor${floor}-`;
          
          // Find all elements with IDs in <defs>
          const defsElements = loadedSvg.querySelectorAll('defs [id]');
          const idMap = new Map(); // Track old ID -> new ID mappings
          
          defsElements.forEach(el => {
            const oldId = el.id;
            const newId = floorPrefix + oldId;
            el.id = newId;
            idMap.set(oldId, newId);
          });
          
          // Update all url(#...) and xlink:href references
          const allElements = loadedSvg.querySelectorAll('*');
          allElements.forEach(el => {
            // Update fill attribute
            if (el.hasAttribute('fill')) {
              const fill = el.getAttribute('fill');
              if (fill.startsWith('url(#')) {
                const refId = fill.match(/url\(#([^)]+)\)/)[1];
                if (idMap.has(refId)) {
                  el.setAttribute('fill', `url(#${idMap.get(refId)})`);
                }
              }
            }
            
            // Update stroke attribute
            if (el.hasAttribute('stroke')) {
              const stroke = el.getAttribute('stroke');
              if (stroke.startsWith('url(#')) {
                const refId = stroke.match(/url\(#([^)]+)\)/)[1];
                if (idMap.has(refId)) {
                  el.setAttribute('stroke', `url(#${idMap.get(refId)})`);
                }
              }
            }
            
            // Update xlink:href attribute
            if (el.hasAttribute('xlink:href')) {
              const href = el.getAttribute('xlink:href');
              if (href.startsWith('#')) {
                const refId = href.substring(1);
                if (idMap.has(refId)) {
                  el.setAttribute('xlink:href', `#${idMap.get(refId)}`);
                }
              }
            }
            
            // Update style attribute for inline url() references
            if (el.hasAttribute('style')) {
              let style = el.getAttribute('style');
              const urlMatches = style.match(/url\(#([^)]+)\)/g);
              if (urlMatches) {
                urlMatches.forEach(match => {
                  const refId = match.match(/url\(#([^)]+)\)/)[1];
                  if (idMap.has(refId)) {
                    style = style.replace(match, `url(#${idMap.get(refId)})`);
                  }
                });
                el.setAttribute('style', style);
              }
            }
          });
          
          console.log(`Floor ${floor} IDs namespaced: ${idMap.size} definitions prefixed with "${floorPrefix}"`);
          
          // Copy all child elements
          while (loadedSvg.firstChild) {
            svgElement.appendChild(loadedSvg.firstChild);
          }
          
          console.log(`Floor ${floor} SVG loaded successfully`);
          console.log(`Floor ${floor} IDs namespaced: ${idMap.size} definitions prefixed with "${floorPrefix}"`);
          
          // Apply consistent font styling to all text elements in the SVG
          applyConsistentFontStyling(svgElement);
          
          // Initialize pan-zoom for this floor's SVG
          initializePanZoom(svgElement, floor);
        } else {
          throw new Error(`Invalid SVG content for floor ${floor}`);
        }
        
        // Add panorama markers for this floor with a delay to ensure pan-zoom is ready
        // Pan-zoom wraps content in a viewport <g>, so markers must be added after initialization
        setTimeout(() => {
          addPanoramaMarkers(floor);
        }, 150);
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
    
    // Find the pan-zoom viewport group (svg-pan-zoom wraps content in a <g> element)
    // Look for the first <g> child which is the viewport transform group
    const viewport = svgElement.querySelector('g[transform]') || svgElement.querySelector('g') || svgElement;
    console.log(`📦 Using viewport element:`, viewport.tagName, viewport.id || '(no id)');
    
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
      
      // Pin size multiplier - ADJUST THIS VALUE TO CHANGE PIN SIZE
      // 0.5 = small pins, 1.0 = default, 1.5 = large pins, 2.0 = extra large pins
      const pinScale = 0.6;
      
      // Calculate scaled dimensions
      const pinHeight = 30 * pinScale;
      const pinWidth = 22 * pinScale;
      const pinWidthNarrow = 12 * pinScale;
      const pinNeck = 8 * pinScale;
      const circleRadius = 12 * pinScale;
      const circleCenterY = -12 * pinScale;
      const textY = -8 * pinScale;
      
      // Create pin shape (teardrop/map pin) with scaled dimensions
      const pin = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      pin.setAttribute('d', `M0,${-pinHeight} C${-pinWidthNarrow},${-pinHeight} ${-pinWidth},${-pinHeight + 10 * pinScale} ${-pinWidth},${-pinNeck} C${-pinWidth},${pinNeck / 2} 0,${pinHeight} 0,${pinHeight} C0,${pinHeight} ${pinWidth},${pinNeck / 2} ${pinWidth},${-pinNeck} C${pinWidth},${-pinHeight + 10 * pinScale} ${pinWidthNarrow},${-pinHeight} 0,${-pinHeight} Z`);
      pin.setAttribute('class', 'pin-shape');
      
      // Create inner circle for count with scaled dimensions
      const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      circle.setAttribute('cx', '0');
      circle.setAttribute('cy', circleCenterY);
      circle.setAttribute('r', circleRadius);
      circle.setAttribute('class', 'pin-circle');
      
      // Create text for scan count with scaled position and font size
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', '0');
      text.setAttribute('y', textY);
      text.setAttribute('text-anchor', 'middle');
      text.setAttribute('class', 'pin-text');
      // Scale font size proportionally with the pin (base font size is 12px)
      text.setAttribute('font-size', `${12 * pinScale}px`);
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

      // Add marker to the pan-zoom viewport group
      // This ensures markers transform with the floor plan during pan/zoom
      viewport.appendChild(markerGroup);
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
    
    // Reset pan-zoom for the newly shown floor
    if (window.panZoomInstances[floorNumber]) {
      setTimeout(() => {
        window.panZoomInstances[floorNumber].fit();
        window.panZoomInstances[floorNumber].center();
        console.log(`🔄 Reset pan-zoom for floor ${floorNumber}`);
      }, 100);
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
        console.log(`  Filtering panorama data by: ${filterValue}`);
        
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
        
      });
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

<!-- All Offices Modal -->
<div id="all-offices-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: white; border-radius: 16px; width: 90%; max-width: 800px; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
    <!-- Modal Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #e5e7eb;">
      <h3 style="margin: 0; font-size: 20px; font-weight: 600; color: #1a1a1a;">
        <i class="fa fa-building" style="margin-right: 8px; color: #04aa6d;"></i>
        All Offices - Visitor Statistics
      </h3>
      <button id="close-all-offices-modal" style="background: none; border: none; font-size: 28px; color: #6b7280; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s;">
        ×
      </button>
    </div>
    
    <!-- Modal Body -->
    <div style="padding: 20px 24px; overflow-y: auto; flex: 1;">
      <div id="all-offices-content">
        <div style="text-align: center; padding: 40px; color: #9ca3af;">
          <i class="fa fa-spinner fa-spin" style="font-size: 32px;"></i>
          <p style="margin-top: 12px;">Loading offices...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // View All Offices functionality
  document.getElementById('view-all-offices-btn').addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const modal = document.getElementById('all-offices-modal');
    const contentDiv = document.getElementById('all-offices-content');
    
    modal.style.display = 'flex';
    
    // Get current month and year filters from the form
    const selectedMonth = document.getElementById('month') ? document.getElementById('month').value : 'all';
    const selectedYear = document.getElementById('year') ? document.getElementById('year').value : 'all';
    
    fetch(`get_all_offices_stats.php?month=${selectedMonth}&year=${selectedYear}`)
      .then(response => {
        console.log('Response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Received data:', data);
        if (data.success) {
          const offices = data.offices;
          
          if (offices.length === 0) {
            contentDiv.innerHTML = `
              <div style="text-align: center; padding: 40px; color: #9ca3af;">
                <i class="fa fa-inbox" style="font-size: 48px; color: #d1d5db;"></i>
                <p style="margin-top: 12px; font-size: 16px;">No office data available</p>
              </div>
            `;
            return;
          }
          
          let html = `
            <div style="margin-bottom: 16px; padding: 12px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #0284c7;">
              <p style="margin: 0; font-size: 14px; color: #0c4a6e;">
                <strong>Total Offices:</strong> ${offices.length} | 
                <strong>Total Scans:</strong> ${offices.reduce((sum, o) => sum + parseInt(o.scan_count), 0).toLocaleString()}
              </p>
            </div>
            <div style="overflow-x: auto;">
              <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                  <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151;">#</th>
                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151;">Office Name</th>
                    <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151;">Floor</th>
                    <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151;">Status</th>
                    <th style="padding: 12px 16px; text-align: right; font-weight: 600; color: #374151;">QR Scans</th>
                  </tr>
                </thead>
                <tbody>
          `;
          
          offices.forEach((office, index) => {
            const floor = office.floor || 'N/A';
            const status = office.status === 'active' 
              ? '<span style="padding: 4px 8px; background: #dcfce7; color: #166534; border-radius: 6px; font-size: 12px; font-weight: 500;">Active</span>'
              : '<span style="padding: 4px 8px; background: #fee2e2; color: #991b1b; border-radius: 6px; font-size: 12px; font-weight: 500;">Inactive</span>';
            
            html += `
              <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.15s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                <td style="padding: 12px 16px; color: #6b7280;">${index + 1}</td>
                <td style="padding: 12px 16px; font-weight: 500; color: #1f2937;">${office.name}</td>
                <td style="padding: 12px 16px; text-align: center; color: #6b7280;">${floor}F</td>
                <td style="padding: 12px 16px; text-align: center;">${status}</td>
                <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #04aa6d;">${parseInt(office.scan_count).toLocaleString()}</td>
              </tr>
            `;
          });
          
          html += `
                </tbody>
              </table>
            </div>
          `;
          
          contentDiv.innerHTML = html;
        } else {
          contentDiv.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #ef4444;">
              <i class="fa fa-exclamation-circle" style="font-size: 48px;"></i>
              <p style="margin-top: 12px;">Error loading data: ${data.error || 'Unknown error'}</p>
            </div>
          `;
        }
      })
      .catch(error => {
        console.error('Error fetching offices:', error);
        contentDiv.innerHTML = `
          <div style="text-align: center; padding: 40px; color: #ef4444;">
            <i class="fa fa-exclamation-circle" style="font-size: 48px;"></i>
            <p style="margin-top: 12px;">Failed to load office data</p>
          </div>
        `;
      });
  });
  
  // Close modal functionality
  document.getElementById('close-all-offices-modal').addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('all-offices-modal').style.display = 'none';
  });
  
  // Close modal when clicking outside
  document.getElementById('all-offices-modal').addEventListener('click', function(e) {
    if (e.target === this) {
      this.style.display = 'none';
    }
  });
  
  // Add hover effect to close button
  document.getElementById('close-all-offices-modal').addEventListener('mouseenter', function() {
    this.style.background = '#f3f4f6';
  });
  document.getElementById('close-all-offices-modal').addEventListener('mouseleave', function() {
    this.style.background = 'none';
  });
</script>

<!-- Add labelSetup.js for consistent font styling -->
<script src="./floorjs/labelSetup.js"></script>

<!-- QR Report Modal -->
<div id="qr-report-modal" class="report-modal">
  <div class="report-modal-content">
    <div class="report-modal-header">
      <h2>QR Scan Reports</h2>
      <span class="report-modal-close" onclick="closeReportModal()">&times;</span>
    </div>
    
    <div class="report-modal-body">
      <!-- Report Controls -->
      <div class="report-controls-compact">
        <div class="controls-row">
          <div class="control-group">
            <label for="modal-report-type">Report Type</label>
            <select id="modal-report-type" onchange="generateModalReport()">
              <option value="all">All QR Scans</option>
              <option value="office">Office QR Only</option>
              <option value="panorama">Panorama QR Only</option>
            </select>
          </div>
          <div class="control-group">
            <label for="modal-date-from">From Date</label>
            <input type="date" id="modal-date-from" onchange="generateModalReport()">
          </div>
          <div class="control-group">
            <label for="modal-date-to">To Date</label>
            <input type="date" id="modal-date-to" onchange="generateModalReport()">
          </div>
          <div class="control-group">
            <label for="modal-group-by">Group By</label>
            <select id="modal-group-by" onchange="generateModalReport()">
              <option value="day">Daily</option>
              <option value="week">Weekly</option>
              <option value="month">Monthly</option>
            </select>
          </div>
        </div>
        <div class="button-row">
          <div class="dropdown-container">
            <button class="btn btn-secondary dropdown-trigger" onclick="toggleDownloadMenu(event)" type="button">
              Download ▼
            </button>
            <div class="download-dropdown" id="download-dropdown">
              <a href="#" onclick="exportModalExcel(); return false;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                  <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                  <path d="M5.18 10.5a1.04 1.04 0 0 0 .605.969 1.21 1.21 0 0 0 1.228-.093l.29-.196V12h.5v-.82l.29.196a1.21 1.21 0 0 0 1.228.093A1.04 1.04 0 0 0 9.9 10.5v-.5A1.04 1.04 0 0 0 9.295 9.03a1.21 1.21 0 0 0-1.228.093l-.29.196V8.5h-.5v.82l-.29-.196a1.21 1.21 0 0 0-1.228-.093A1.04 1.04 0 0 0 5.1 10v.5z"/>
                </svg>
                Download as Excel
              </a>
              <a href="#" onclick="exportModalPDF(); return false;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                  <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                  <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Download as CSV
              </a>
            </div>
          </div>
          <button class="btn btn-secondary" onclick="printModalReport()">Print</button>
        </div>
      </div>

      <!-- Summary Section -->
      <div class="report-section-modal" id="modal-summary-section">
        <h3>Summary Statistics</h3>
        <div class="summary-grid-modal" id="modal-summary-grid"></div>
      </div>

      <!-- Charts Section -->
      <div class="report-section-modal">
        <h3>Scan Timeline</h3>
        <div class="chart-container-modal">
          <canvas id="modal-timeline-chart"></canvas>
        </div>
      </div>

      <div class="report-charts-grid">
        <div class="report-section-modal">
          <h3>Hourly Distribution</h3>
          <div class="chart-container-modal-small">
            <canvas id="modal-hourly-chart"></canvas>
          </div>
        </div>

        <div class="report-section-modal">
          <h3>Day of Week</h3>
          <div class="chart-container-modal-small">
            <canvas id="modal-daily-chart"></canvas>
          </div>
        </div>
      </div>

      <!-- Top Tables -->
      <div class="report-tables-grid">
        <div class="report-section-modal" id="modal-offices-section">
          <h3>Most Visited Offices</h3>
          <div class="table-scroll">
            <table id="modal-offices-table">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Office Name</th>
                  <th>Title</th>
                  <th>Scans</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <div class="report-section-modal" id="modal-panoramas-section">
          <h3>Most Viewed Panoramas</h3>
          <div class="table-scroll">
            <table id="modal-panoramas-table">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Location</th>
                  <th>Floor</th>
                  <th>Views</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="modal-loading" class="modal-loading" style="display: none;">
        <div class="spinner"></div>
        <p>Loading report data...</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  let modalTimelineChart = null;
  let modalHourlyChart = null;
  let modalDailyChart = null;
  let currentModalReportData = null;

  async function generateModalReport() {
    const reportType = document.getElementById('modal-report-type').value;
    const dateFrom = document.getElementById('modal-date-from').value;
    const dateTo = document.getElementById('modal-date-to').value;
    const groupBy = document.getElementById('modal-group-by').value;

    document.getElementById('modal-loading').style.display = 'flex';

    try {
      const params = new URLSearchParams({
        action: 'generate_report',
        report_type: reportType,
        group_by: groupBy
      });

      if (dateFrom) params.append('date_from', dateFrom);
      if (dateTo) params.append('date_to', dateTo);

      const response = await fetch(`qr_report_api.php?${params}`);
      const result = await response.json();

      if (result.success) {
        currentModalReportData = result.data;
        renderModalReport(result.data);
      } else {
        alert('Error generating report: ' + result.error);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Failed to generate report');
    }

    document.getElementById('modal-loading').style.display = 'none';
  }

  function renderModalReport(data) {
    renderModalSummary(data.summary);
    renderModalTimeline(data.timeline);
    renderModalHourlyDistribution(data.hourly_distribution);
    renderModalDailyComparison(data.daily_comparison);
    renderModalTopOffices(data.top_offices);
    renderModalTopPanoramas(data.top_panoramas);
  }

  function renderModalSummary(summary) {
    const grid = document.getElementById('modal-summary-grid');
    grid.innerHTML = `
      <div class="summary-card-modal">
        <p>Office QR Scans</p>
        <h4>${summary.office.toLocaleString()}</h4>
      </div>
      <div class="summary-card-modal">
        <p>Panorama QR Scans</p>
        <h4>${summary.panorama.toLocaleString()}</h4>
      </div>
      <div class="summary-card-modal">
        <p>Total Scans</p>
        <h4>${summary.total.toLocaleString()}</h4>
      </div>
    `;
  }

  function renderModalTimeline(timeline) {
    const ctx = document.getElementById('modal-timeline-chart').getContext('2d');
    
    if (modalTimelineChart) modalTimelineChart.destroy();

    modalTimelineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: timeline.map(d => d.period),
        datasets: [
          {
            label: 'Office QR',
            data: timeline.map(d => d.office),
            borderColor: '#2e7d32',
            backgroundColor: 'rgba(46, 125, 50, 0.1)',
            tension: 0.4
          },
          {
            label: 'Panorama QR',
            data: timeline.map(d => d.panorama),
            borderColor: '#1976d2',
            backgroundColor: 'rgba(25, 118, 210, 0.1)',
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  function renderModalHourlyDistribution(hourly) {
    const ctx = document.getElementById('modal-hourly-chart').getContext('2d');
    
    if (modalHourlyChart) modalHourlyChart.destroy();

    modalHourlyChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: hourly.map(d => d.hour),
        datasets: [
          {
            label: 'Office QR',
            data: hourly.map(d => d.office),
            backgroundColor: '#4CAF50'
          },
          {
            label: 'Panorama QR',
            data: hourly.map(d => d.panorama),
            backgroundColor: '#1976D2'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  function renderModalDailyComparison(daily) {
    const ctx = document.getElementById('modal-daily-chart').getContext('2d');
    
    if (modalDailyChart) modalDailyChart.destroy();

    modalDailyChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: daily.map(d => d.day.substring(0, 3)),
        datasets: [
          {
            label: 'Office',
            data: daily.map(d => d.office),
            backgroundColor: '#4CAF50'
          },
          {
            label: 'Panorama',
            data: daily.map(d => d.panorama),
            backgroundColor: '#1976D2'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  function renderModalTopOffices(offices) {
    const tbody = document.querySelector('#modal-offices-table tbody');
    if (offices.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No data available</td></tr>';
      return;
    }
    tbody.innerHTML = offices.map((office, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${office.name}</td>
        <td>${office.title}</td>
        <td><strong>${office.scan_count}</strong></td>
      </tr>
    `).join('');
  }

  function renderModalTopPanoramas(panoramas) {
    const tbody = document.querySelector('#modal-panoramas-table tbody');
    if (panoramas.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No data available</td></tr>';
      return;
    }
    tbody.innerHTML = panoramas.map((panorama, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${panorama.location_name}</td>
        <td> ${panorama.floor_number}</td>
        <td><strong>${panorama.view_count}</strong></td>
      </tr>
    `).join('');
  }

  function toggleDownloadMenu(event) {
    event.stopPropagation();
    event.preventDefault();
    const dropdown = document.getElementById('download-dropdown');
    if (dropdown) {
      dropdown.classList.toggle('show');
      console.log('Download menu toggled:', dropdown.classList.contains('show'));
    } else {
      console.error('Download dropdown element not found!');
    }
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('download-dropdown');
    if (dropdown && !event.target.closest('.dropdown-container')) {
      dropdown.classList.remove('show');
    }
  });

  function exportModalExcel() {
    const reportType = document.getElementById('modal-report-type').value;
    const dateFrom = document.getElementById('modal-date-from').value;
    const dateTo = document.getElementById('modal-date-to').value;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'qr_report_api.php?action=export_excel';

    const fields = {
      csrf_token: window.CSRF_TOKEN,
      report_type: reportType,
      date_from: dateFrom,
      date_to: dateTo
    };

    for (const [key, value] of Object.entries(fields)) {
      if (value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
      }
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    // Close dropdown
    document.getElementById('download-dropdown').classList.remove('show');
  }

  function exportModalPDF() {
    console.log('CSV export clicked');
    const reportType = document.getElementById('modal-report-type').value;
    const dateFrom = document.getElementById('modal-date-from').value;
    const dateTo = document.getElementById('modal-date-to').value;

    // Close dropdown
    document.getElementById('download-dropdown').classList.remove('show');

    // Submit form to generate CSV
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'qr_report_api.php?action=export_csv';
    form.style.display = 'none';

    const fields = {
      csrf_token: window.CSRF_TOKEN,
      report_type: reportType,
      date_from: dateFrom || '',
      date_to: dateTo || ''
    };

    for (const [key, value] of Object.entries(fields)) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = value;
      form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  }

  function exportModalCSV() {
    const reportType = document.getElementById('modal-report-type').value;
    const dateFrom = document.getElementById('modal-date-from').value;
    const dateTo = document.getElementById('modal-date-to').value;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'qr_report_api.php?action=export_csv';

    const fields = {
      csrf_token: window.CSRF_TOKEN,
      report_type: reportType,
      date_from: dateFrom,
      date_to: dateTo
    };

    for (const [key, value] of Object.entries(fields)) {
      if (value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
      }
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  }

  function printModalReport() {
    console.log('Print button clicked');
    
    if (!currentModalReportData) {
      alert('Please generate a report first');
      return;
    }

    try {
      // Directly trigger print - browser will handle it
      window.print();
      console.log('Print dialog opened');
    } catch (e) {
      console.error('Print error:', e);
      alert('Failed to open print dialog. Please try using Ctrl+P instead.');
    }
  }
</script>

</body>
</html>
