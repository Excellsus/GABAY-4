<?php
// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session for tracking scanned QRs
session_start();

// Go up one directory to include connect_db.php from the parent folder
include __DIR__ . '/../connect_db.php'; // Include database connection

$offices = []; // Initialize as empty array
$error_message_php = null; // Variable for PHP errors

$highlight_office_id = null; // This will store the office ID from the URL if provided
if (isset($_GET['office_id']) && is_numeric($_GET['office_id'])) {
    $highlight_office_id = (int)$_GET['office_id'];
}

// Handle panorama QR scan
$scanned_panorama = null;
if (isset($_GET['scanned_panorama'])) {
    // Parse the panorama scan data: path_id:path1_point:8_floor:1 OR path_id:path3_floor2_point:2_floor:2
    $panorama_data = $_GET['scanned_panorama'];
    // Fixed regex to handle path IDs with underscores like "path3_floor2"
    if (preg_match('/path_id:(.+?)_point:(\d+)_floor:(\d+)/', $panorama_data, $matches)) {
        $scanned_panorama = [
            'path_id' => $matches[1],
            'point_index' => (int)$matches[2],
            'floor' => (int)$matches[3]
        ];
        
        // Debug logging to see what's being parsed
        error_log("Panorama QR parsed: path_id='{$matches[1]}', point_index={$matches[2]}, floor={$matches[3]}");
    } else {
        error_log("Panorama QR scan failed to parse: $panorama_data");
    }
}

// --- Log QR Scan if office_id is present in URL ---
if ($highlight_office_id !== null && isset($connect) && $connect) {
    // Create unique session key for this office scan
    $office_scan_key = "office_scanned_" . $highlight_office_id;
    
    // Check if we've already logged this office scan in this session
    if (!isset($_SESSION[$office_scan_key])) {
        try {
            // First, find the id from qrcode_info table that corresponds to this office_id
            // This id will be used as qr_code_id in qr_scan_logs
            $stmt_qr_info = $connect->prepare("SELECT id FROM qrcode_info WHERE office_id = :office_id LIMIT 1");
            $stmt_qr_info->bindParam(':office_id', $highlight_office_id, PDO::PARAM_INT);
            $stmt_qr_info->execute();
            $qr_info_record = $stmt_qr_info->fetch(PDO::FETCH_ASSOC);

            if ($qr_info_record && isset($qr_info_record['id'])) {
                $qr_code_info_id = $qr_info_record['id'];

                // Now, insert the scan log
                $stmt_log = $connect->prepare("INSERT INTO qr_scan_logs (office_id, qr_code_id, check_in_time) VALUES (:office_id, :qr_code_id, NOW())");
                $stmt_log->bindParam(':office_id', $highlight_office_id, PDO::PARAM_INT);
                $stmt_log->bindParam(':qr_code_id', $qr_code_info_id, PDO::PARAM_INT);
                $stmt_log->execute();
                
                // Mark this office as scanned in the session to prevent duplicates
                $_SESSION[$office_scan_key] = true;
                
                error_log("Office QR Scan logged for office_id: $highlight_office_id, qr_code_info_id: $qr_code_info_id");
            } else {
                error_log("Office QR Scan attempt for office_id: $highlight_office_id, but no corresponding qrcode_info record found.");
            }
        } catch (PDOException $e) {
            error_log("Error logging office QR scan: " . $e->getMessage());
        }
    } else {
        error_log("Office QR Scan skipped (already logged in session) for office_id: $highlight_office_id");
    }
}
// --- End Log QR Scan ---

// --- Log Panorama QR Scan ---
if ($scanned_panorama !== null && isset($connect) && $connect) {
    // Create unique session key for this panorama scan
    $panorama_scan_key = "panorama_scanned_" . $scanned_panorama['path_id'] . "_" . $scanned_panorama['point_index'] . "_" . $scanned_panorama['floor'];
    
    // Check if we've already logged this panorama scan in this session
    if (!isset($_SESSION[$panorama_scan_key])) {
        try {
            // Find the QR code record for this panorama
            $stmt_pano_qr = $connect->prepare("SELECT id FROM panorama_qrcodes WHERE path_id = ? AND point_index = ? AND floor_number = ?");
            $stmt_pano_qr->execute([$scanned_panorama['path_id'], $scanned_panorama['point_index'], $scanned_panorama['floor']]);
            $pano_qr_record = $stmt_pano_qr->fetch(PDO::FETCH_ASSOC);

            if ($pano_qr_record) {
                // Log the panorama QR scan
                $stmt_pano_log = $connect->prepare("INSERT INTO panorama_qr_scans (qr_id, user_agent, ip_address) VALUES (?, ?, ?)");
                $stmt_pano_log->execute([
                    $pano_qr_record['id'],
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                // Mark this panorama as scanned in the session to prevent duplicates
                $_SESSION[$panorama_scan_key] = true;
                
                error_log("Panorama QR Scan logged for path_id: {$scanned_panorama['path_id']}, point: {$scanned_panorama['point_index']}, floor: {$scanned_panorama['floor']}");
            }
        } catch (PDOException $e) {
            error_log("Error logging panorama QR scan: " . $e->getMessage());
        }
    } else {
        error_log("Panorama QR Scan skipped (already logged in session) for path_id: {$scanned_panorama['path_id']}, point: {$scanned_panorama['point_index']}, floor: {$scanned_panorama['floor']}");
    }
}
// --- End Log Panorama QR Scan ---

try {
    // Check if $connect is a valid PDO object
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection object (\$connect) is not valid. Check connect_db.php.");
    }

    // Fetch all office data, including status and office hours for the current day
    $current_day = date('l'); // Gets current day name (Monday, Tuesday, etc.)
    $stmt = $connect->query("SELECT o.id, o.name, o.details, o.services, o.contact, o.location, o.status, 
        oh.open_time, oh.close_time,
        (SELECT image_path FROM office_image WHERE office_id = o.id ORDER BY uploaded_at DESC, id DESC LIMIT 1) AS image_path 
        FROM offices o 
        LEFT JOIN office_hours oh ON o.id = oh.office_id AND oh.day_of_week = '$current_day'");

    // Check if query execution was successful
    if ($stmt === false) {
        // Query failed, get error info
        $errorInfo = $connect->errorInfo();
        throw new PDOException("Query failed: " . ($errorInfo[2] ?? 'Unknown error - Check table/column names and permissions.'));
    }

    // Fetch the data
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { // Catches PDOException and general Exception
    $error_message_php = "Error fetching office data: " . $e->getMessage();
    error_log("Error in explore.php (PHP part): " . $e->getMessage()); // Log error to PHP error log
}

?>
  
  <!DOCTYPE html>
  <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Visitor Navigation</title>
    <link rel="stylesheet" href="explore.css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
     <link
       rel="stylesheet"
       href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"

      />
    <!-- A-Frame VR Scripts for Panorama Viewer -->
    <script src="https://aframe.io/releases/1.5.0/aframe.min.js"></script>
    <script src="https://unpkg.com/aframe-mouse-wheel-component/dist/aframe-mouse-wheel-component.min.js"></script>
    <script src="https://unpkg.com/aframe-touch-zoom-component/dist/aframe-touch-zoom-component.min.js"></script>
    <body>
      <header class="header">
        <div class="header-content">
            <h2 class="section-title">GABAY Navigation</h2>
            <p class="section-subtitle">Interactive Building Explorer</p>
        </div>
        <div class="header-actions">
            <button id="rooms-list-button" class="rooms-list-button-header" title="View All Offices & Rooms" onclick="window.location.href='rooms.php'">
                <i class="fas fa-list"></i>
            </button>
            <button id="feedback-button" class="feedback-button-header" title="Provide Feedback" onclick="window.location.href='feedback.php'">
                <i class="fas fa-comment-alt"></i>
            </button>
            <button id="help-button" class="help-button-header" title="Help & User Guide">
                <i class="fas fa-question-circle"></i>
            </button>
        </div>
      </header>
    </head>
      <!-- Main content area -->
    <!-- Removed complex height style, will be handled by CSS -->
    <main class="content">       
      <!-- Tooltip Element -->
      <div id="floorplan-tooltip" class="absolute bg-black text-white text-xs px-2 py-1 rounded shadow-lg pointer-events-none hidden z-50"></div>

        <?php if ($error_message_php): ?>
            <p class="error-message" style="text-align: center; padding: 10px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin: 10px;"><?php echo htmlspecialchars($error_message_php); ?></p>
        <?php endif; ?>

        <!-- Floor Selector Buttons -->
        <div class="floor-selector">
          <button class="floor-btn active" data-floor="1">1F</button>
          <button class="floor-btn" data-floor="2">2F</button>
          <button class="floor-btn" data-floor="3">3F</button>
        </div>
        
        <!-- Placeholder: Switch to 3D View -->
        <div style="position: absolute; top: 80px; right: 10px; z-index: 1001;">
          <button id="switch-3d-view-btn" style="padding: 8px 12px; background: #ff8c00; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
            Switch to 3D View
          </button>
        </div>

        <!-- SVG Container -->
        <div class="svg-container" id="svg-container">
          <!-- SVG will be loaded here -->
        </div>
    </main>

    <style>
      /* Floor selector styles */
      .floor-selector {
        position: absolute;
        top: 5px;
        right: 10px;
        z-index: 1000;
        display: flex;
        gap: 4px;
        background: rgba(255, 255, 255, 0.8);
        padding: 4px;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      }

      .floor-btn {
        padding: 4px 8px;
        border: 1px solid #04aa6d;
        background: white;
        color: #04aa6d;
        border-radius: 12px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        min-width: 32px;
        text-align: center;
        transition: all 0.2s ease;
      }

      .floor-btn:hover {
        background: #e6f4f1;
      }

      .floor-btn.active {
        background: #04aa6d;
        color: white;
      }

      /* SVG styles - ensure full coverage */
      svg { 
        width: 100% !important; 
        height: 100% !important;
        max-width: none !important;
        max-height: none !important;
        display: block !important;
        position: absolute;
        top: 0;
        left: 0;
        /* Allow pinch gestures while preventing unwanted touch behaviors */
        touch-action: pan-x pan-y pinch-zoom !important;
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
      }
      
      /* Override any Tailwind classes that might interfere */
      .svg-container {
        max-width: none !important;
        max-height: none !important;
        /* Essential touch handling for mobile - allow pinch-zoom */
        touch-action: pan-x pan-y pinch-zoom !important;
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
        /* Prevent text selection and context menus */
        -webkit-user-drag: none !important;
        -khtml-user-drag: none !important;
        -moz-user-drag: none !important;
        -o-user-drag: none !important;
        
      }
      
      .svg-container * {
        max-width: none !important;
        max-height: none !important;
      }
      
      /* Mobile-specific SVG adjustments */
      @media (max-width: 768px) {
        svg {
          width: 100vw !important;
          height: 100% !important;
          min-height: calc(100vh - 120px) !important;
          position: absolute !important;
          top: 0 !important;
          left: 0 !important;
        }
        
        .svg-container {
          width: 100vw !important;
          height: 100% !important;
          min-height: calc(100vh - 120px) !important;
          position: relative !important;
          overflow: hidden !important;
          /* Allow pinch-to-zoom while preventing page scroll */
          touch-action: pan-x pan-y pinch-zoom !important;
          -webkit-overflow-scrolling: touch !important;
          -webkit-transform: translateZ(0) !important;
          transform: translateZ(0) !important;
        }
        
        /* Adjust floor selector for mobile */
        .floor-selector {
          position: absolute;
          top: 10px;
          right: 10px;
          z-index: 1000;
          display: flex;
          gap: 4px;
          background: rgba(255, 255, 255, 0.9);
          padding: 6px;
          border-radius: 20px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .floor-btn {
          padding: 6px 12px;
          font-size: 14px;
          min-width: 36px;
        }
      } 
      .selectable-room { 
        cursor: pointer; 
        stroke: #1976d2; 
        stroke-width: 2; 
      } 
      .selectable-room.selected { 
        stroke: #43a047; 
        stroke-width: 4; 
      }
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
      
      /* High contrast variant for better accessibility */
      .room-label-high-contrast,
      .high-contrast text[id*="roomlabel"],
      .high-contrast tspan[id*="roomlabel"] {
        fill: #000000;
        stroke: #ffffff;
        stroke-width: 4px;
      }
      
      /* Mobile-optimized text sizing */
      @media (max-width: 768px) {
        .room-label,
        text[id*="roomlabel"],
        text[id*="text-"],
        tspan[id*="roomlabel"] {
          font-size: 16px;
          stroke-width: 4px;
        }
      }
      
      /* Large text for rooms with longer names */
      .room-label-large {
        font-size: 12px;
        line-height: 1.2;
      }
      
      /* Small text for compact rooms */
      .room-label-small {
        font-size: 10px;
        stroke-width: 2px;
      }
      .room-inactive {
        opacity: 0.5;
      }
      .text-label-inactive {
        opacity: 0.5;
      }
      .interactive-room {
        transition: opacity 0.3s ease;
      }
      .interactive-room:hover {
        opacity: 0.8;
      }
      
      /* YOU ARE HERE styles */
      .you-are-here {
        stroke: #ff4444 !important;
        stroke-width: 4 !important;
        fill: rgba(255, 68, 68, 0.2) !important;
        animation: pulse 2s infinite;
      }
      
      @keyframes pulse {
        0% { stroke-opacity: 1; }
        50% { stroke-opacity: 0.5; }
        100% { stroke-opacity: 1; }
      }
      
      .you-are-here-label {
        animation: bounce 2s infinite;
        vector-effect: non-scaling-stroke;
        font-size: 14px !important;
        pointer-events: none;
      }
      
      @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-5px); }
        60% { transform: translateY(-3px); }
      }
      
      /* Path highlighting styles */
      .path-highlight {
        stroke: #ff4444 !important;
        stroke-width: 3 !important;
        fill: rgba(255, 68, 68, 0.1) !important;
        stroke-dasharray: 5,5;
        animation: pathPulse 1.5s infinite;
      }
      
      /* Selected room styles for pathfinding */
      .selected-room {
        stroke: #43a047 !important;
        stroke-width: 4 !important;
        fill: rgba(67, 160, 71, 0.2) !important;
        animation: selectedPulse 2s infinite;
      }
      
      @keyframes selectedPulse {
        0% { stroke-opacity: 1; }
        50% { stroke-opacity: 0.6; }
        100% { stroke-opacity: 1; }
      }
      
      @keyframes pathPulse {
        0% { stroke-opacity: 1; }
        50% { stroke-opacity: 0.6; }
        100% { stroke-opacity: 1; }
      }
      
      /* Panorama marker styles for mobile */
      .panorama-marker {
        transition: all 0.2s ease;
        cursor: pointer !important;
      }
      
      .panorama-marker .camera-bg {
        transition: all 0.3s ease;
      }
      
      /* Stair marker styling */
      .stair-marker {
        transition: transform 0.2s ease;
        cursor: pointer;
        /* Prevent any inherited opacity changes on the group */
        opacity: 1 !important;
      }

      .stair-marker .stair-bg {
        fill: #971812;
        stroke: #ffffff;
        stroke-width: 1.5;
        /* Only animate color and radius; do not animate opacity */
        transition: fill 0.12s ease, r 0.12s ease;
        vector-effect: non-scaling-stroke;
        opacity: 1 !important; /* Force full opacity */
      }

      .stair-marker .stair-icon {
        fill: #ffffff;
        pointer-events: none;
        opacity: 1 !important;
      }

      .stair-marker.active .stair-bg {
        fill: #fbbf24;
        opacity: 1 !important;
      }

      /* On hover, only change fill color; explicitly keep opacity at 1 and avoid CSS opacity transitions */
      .stair-marker:hover:not(.active) .stair-bg {
        fill: #FF6B35;
        opacity: 1 !important;
        transition: fill 0.12s ease; /* ensure no opacity transition is applied */
      }
      
      /* Touch-friendly sizing for mobile panorama markers */
      @media (max-width: 768px) {
        .panorama-marker {
          /* Markers will be slightly larger on mobile for easier touch interaction */
          touch-action: manipulation;
        }
      }
    </style>

    <!-- Details drawer - slides up from bottom -->
    <div class="details-drawer" id="details-drawer">
      <div class="drawer-handle" id="drawer-handle">
        <div class="handle-bar"></div>
      </div>
      <div class="drawer-content">
        <!-- Location information section -->
        <div class="location-info">
          <div class="location-header">
            <div class="location-icon" id="drawer-office-image-container">
              <!-- Office image will be injected here -->
            </div>
            <div class="location-title">
              <h2 id="drawer-office-name">Office Name</h2>
              <div id="drawer-office-status"></div>
            </div>
          </div>
          <hr />
          <div class="details-section">
            <div class="detail-row">
              <i class="fas fa-info-circle"></i>
              <div>
                <div id="drawer-office-details">Details: ...</div>
              </div>
            </div>
            <div class="detail-row">
              <i class="fas fa-phone"></i>
              <div>
                <div id="drawer-office-contact">Contact: ...</div>
              </div>
            </div>
          </div>
          <div class="button-container">
            <div class="primary-actions">
              <button class="drawer-btn primary" id="directions-btn">
                <div class="btn-icon">
                  <i class="fas fa-route"></i>
                </div>
                <div class="btn-content">
                  <span class="btn-title">Get Directions</span>
                  <span class="btn-subtitle">Find your way</span>
                </div>
              </button>
              <button class="drawer-btn primary" id="details-btn">
                <div class="btn-icon">
                  <i class="fas fa-file-alt"></i>
                </div>
                <div class="btn-content">
                  <span class="btn-title">Office Details</span>
                  <span class="btn-subtitle">View full information</span>
                </div>
              </button>
            </div>
            <div class="secondary-actions">
              <button class="drawer-btn secondary" id="call-btn" style="display: none;">
                <i class="fas fa-phone"></i>
                <span>Call</span>
              </button>
              <button class="drawer-btn secondary" id="share-btn">
                <i class="fas fa-share"></i>
                <span>Share</span>
              </button>
              <button class="drawer-btn secondary" id="favorite-btn">
                <i class="far fa-heart"></i>
                <span>Save</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <style>
      /* Add styles for the button container and buttons */
      .button-container {
        display: flex;
        gap: 10px;
        padding: 15px;
        justify-content: center;
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

      #details-btn {
        background: #04aa6d;
      }

      #details-btn:hover {
        background: #038857;
      }

      #directions-btn {
        background: #1a5632;
      }

      #directions-btn:hover {
        background: #0d3018;
      }
    </style>



    <!-- 3D Demo Modal -->
    <div id="three-demo-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:4000; align-items:center; justify-content:center;">
      <div style="position:relative; width:95vw; max-width:1100px; height:90vh; background:#000; border-radius:12px; box-shadow:0 8px 40px rgba(0,0,0,0.6); overflow:hidden;">
        <button id="close-three-demo" aria-label="Close 3D demo" style="position:absolute; top:12px; right:12px; font-size:28px; background:rgba(255,255,255,0.08); color:#fff; border:none; width:44px; height:44px; border-radius:8px; cursor:pointer; z-index:12;">&times;</button>
        <iframe id="three-demo-frame" src="../3d_demo/three_demo.html" style="width:100%; height:100%; border:none; background:#000;" allowfullscreen></iframe>
      </div>
    </div>

    <!-- Pathfinding Modal for Directions -->
    <div id="pathfinding-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:3000; align-items:center; justify-content:center;">
      <div style="position:relative; width:95vw; max-width:400px; background:#fff; border-radius:16px; box-shadow:0 4px 24px #0002; padding:20px;">
        <button id="close-pathfinding-modal" style="position:absolute; top:10px; right:15px; font-size:24px; background:none; border:none; cursor:pointer;">&times;</button>
        <h3 style="margin: 0 0 20px 0; color: #1a5632; text-align: center;">Get Directions</h3>
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">From:</label>
          <select id="start-location" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px;">
            <option value="">Select starting point...</option>
            <!-- Other rooms will be populated dynamically -->
          </select>
        </div>
        
        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">To:</label>
          <select id="end-location" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px;">
            <option value="">Select destination...</option>
          </select>
        </div>
        
        <div style="display: flex; gap: 10px;">
          <button id="find-path-btn" style="flex: 1; padding: 12px; background: #1a5632; color: white; border: none; border-radius: 8px; font-weight: bold;">
            Find Path
          </button>
          <button id="clear-path-btn" style="flex: 1; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 8px; font-weight: bold;">
            Clear Path
          </button>
        </div>
      </div>
    </div>

    <!-- Office Details Modal -->
    <div id="office-details-modal" class="modal-overlay">
        <div class="modal-dialog office-details-dialog">
            <div class="modal-header">
                <h3 id="modal-office-name" class="modal-title">Office Name</h3>
                <button id="close-details-modal" class="modal-close">&times;</button>
            </div>
            <div class="modal-body office-modal-body">
                <!-- Office Image Section -->
                <div id="modal-office-image-container" class="office-image-container">
                    <!-- Office image will be injected here -->
                </div>

                <!-- Status and Location Section -->
                <div class="status-location-row">
                    <div id="modal-status-badge" class="status-badge">
                        <!-- Status will be injected here -->
                    </div>
                    <div id="modal-floor-indicator" class="floor-indicator-modal">
                        <!-- Floor indicator will be injected here -->
                    </div>
                </div>

                <!-- Office Details Section -->
                <div class="info-section">
                    <h4><i class="fas fa-info-circle"></i> Description</h4>
                    <div id="modal-office-description" class="office-description">
                        <!-- Description will be injected here -->
                    </div>
                </div>

                <!-- Services Section -->
                <div class="info-section">
                    <h4><i class="fas fa-concierge-bell"></i> Services</h4>
                    <div id="modal-office-services" class="services-list">
                        <!-- Services will be injected here -->
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="info-section">
                    <h4><i class="fas fa-phone"></i> Contact Information</h4>
                    <div id="modal-office-contact" class="contact-content">
                        <!-- Contact info will be injected here -->
                    </div>
                </div>

                <!-- Hours Section -->
                <div class="info-section">
                    <h4><i class="fas fa-clock"></i> Operating Hours</h4>
                    <div id="modal-office-hours" class="hours-display">
                        <!-- Hours will be injected here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panorama Split-Screen Container -->
    <div id="panorama-split-screen" class="panorama-split-screen hidden">
        <!-- Top Panel: Panorama Viewer -->
        <div class="panorama-panel">
            <div class="panorama-header">
                <button id="close-panorama" class="close-panorama-btn">
                    <i class="fas fa-times"></i>
                </button>
                <div class="panorama-title">
                    <h3 id="panorama-location-title">Panorama View</h3>
                    <p id="panorama-description">360° view of the location</p>
                </div>
            </div>
            
            <!-- A-Frame Panorama Viewer -->
            <div class="panorama-viewer">
                <a-scene 
                    id="panorama-scene"
                    mouse-wheel="fovMin: 30; fovMax: 100;" 
                    touch-zoom
                    embedded
                    style="height: 100%; width: 100%;">
                    <!-- Camera with look controls -->
                    <a-entity camera look-controls wasd-controls-enabled="false">
                        <a-entity cursor="rayOrigin: mouse"></a-entity>
                    </a-entity>
                    <!-- Sky element for panorama image -->
                    <a-sky id="panorama-sky" src=""></a-sky>
                </a-scene>
            </div>
        </div>
        
        <!-- Bottom Panel: Map View -->
        <div class="map-panel">
            <div class="map-header">
                <h4>Floor Plan</h4>
                <button id="exit-panorama-mode" class="btn-sm">
                    <i class="fas fa-expand"></i> Full Map
                </button>
            </div>
            <div class="map-container" id="split-map-container">
                <!-- Map will be moved here in split-screen mode -->
            </div>
        </div>
    </div>



    <!-- Floor Plan JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
    <script src="../floorjs/labelSetup.js"></script>
    <!-- Add the actual pathfinding.js from desktop -->
    <script>
      // Set the correct path for floor_graph.json for mobile subdirectory
      window.FLOOR_GRAPH_BASE_PATH = '../';
      
      // ========================================
      // MOBILE PANORAMA INTEGRATION OVERRIDE
      // ========================================
      
      // Override the desktop panorama editor function for mobile BEFORE pathfinding.js loads
      window.openPanoramaEditor = function(pathId, pointIndex, currentImage) {
        console.log('Mobile panorama click detected - SUPPRESSING ALL ALERTS');
        
        // Completely disable alert for this session
        window.alert = function() { 
          console.log('Alert completely disabled for mobile panorama mode'); 
          return;
        };
        
        // Show split screen immediately with default test values
        showPanoramaSplitScreen('path1', 5, 1);
        
        return false; // Prevent any further event handling
      };
    </script>
    <script src="../pathfinding.js"></script>
    
    <!-- Add labelSetup.js for office details modal functionality -->
    <script src="../floorjs/labelSetup.js"></script>
    <script>
      // ===== GLOBAL INITIALIZATION =====
      
      // Initialize critical flags early to prevent stability check issues
      window.isFloorTransitioning = false;
      
      // Make PHP-derived data available globally first
      const officesData = <?php echo json_encode($offices); ?>;
      const highlightOfficeIdFromPHP = <?php echo json_encode($highlight_office_id); ?>;
      const scannedPanoramaFromPHP = <?php echo json_encode($scanned_panorama); ?>;
      console.log("Offices Data Loaded (explore.php - global init):", officesData ? officesData.length : 0, "offices");
      console.log("Office to highlight from QR (ID - global init):", highlightOfficeIdFromPHP);
      console.log("🎯 Scanned Panorama from QR (global init):", scannedPanoramaFromPHP);

      // Ensure the variable is available globally
      window.highlightOfficeIdFromPHP = highlightOfficeIdFromPHP;

      // Global variables for pathfinding.js compatibility
      window.floorGraph = {};
      window.selectedRooms = [];
      window.pathResult = [];
      
      // CRITICAL: Disable desktop pathfinding room click handlers
      window.MOBILE_MODE = true; // Flag to prevent desktop pathfinding initialization

      // ==== PAN / ZOOM CONFIG (Mobile) ====
      // You can tweak these at runtime from the console or set new defaults here.
      // PAN_DAMPING: Multiplier applied after zoom compensation ( <1 slows, >1 speeds )
      // PAN_EXPONENT: Raises the (1/zoom) factor to this power for non‑linear sensitivity.
      //   Example: exponent 1.2 will make panning slower when highly zoomed in compared to linear.
      window.PAN_DAMPING = 1;     // 1:1 finger movement ratio for precise control
      window.PAN_EXPONENT = 0.1;     // Linear response - SVG follows finger exactly
      window.PAN_MIN_STEP_PX = 0.1; // Higher minimum movement for better responsiveness

      // Helper to update pan sensitivity programmatically
      window.setPanSensitivity = function({damping, exponent, minStep} = {}) {
        if (typeof damping === 'number') window.PAN_DAMPING = damping;
        if (typeof exponent === 'number') window.PAN_EXPONENT = exponent;
        if (typeof minStep === 'number') window.PAN_MIN_STEP_PX = minStep;
        console.log('[PanSensitivity] Updated:', {
          PAN_DAMPING: window.PAN_DAMPING,
            PAN_EXPONENT: window.PAN_EXPONENT,
            PAN_MIN_STEP_PX: window.PAN_MIN_STEP_PX
        });
      };

      // Define global utility functions
      function isOfficeOpen(openTime, closeTime) {
        if (!openTime || !closeTime) return null;
        const now = new Date();
        const currentHours = now.getHours();
        const currentMinutes = now.getMinutes();
        const [openHours, openMinutes] = openTime.split(':').map(Number);
        const [closeHours, closeMinutes] = closeTime.split(':').map(Number);
        const currentTimeInMinutes = currentHours * 60 + currentMinutes;
        const openTimeInMinutes = openHours * 60 + openMinutes;
        const closeTimeInMinutes = closeHours * 60 + closeMinutes;
        return currentTimeInMinutes >= openTimeInMinutes && currentTimeInMinutes <= closeTimeInMinutes;
      }

      // Simple function to show panorama split screen (move original SVG so pan/zoom stays functional)
      async function showPanoramaSplitScreen(pathId = 'path1', pointIndex = 5, floorNumber = 1) {
        console.log(`🚀 SHOWING PANORAMA SPLIT SCREEN for path:${pathId}, point:${pointIndex}, floor:${floorNumber}`);

        const splitScreen = document.getElementById('panorama-split-screen');
        if (!splitScreen) {
          console.error('❌ Split screen container not found!');
          return;
        }
        


        // Update the panorama viewer to use Photo Sphere Viewer iframe with dynamic parameters
        const panoramaViewer = splitScreen.querySelector('.panorama-viewer');
        if (panoramaViewer) {
          const panoUrl = `../Pano/pano_photosphere.html?path_id=${encodeURIComponent(pathId)}&point_index=${encodeURIComponent(pointIndex)}&floor_number=${encodeURIComponent(floorNumber)}`;
          panoramaViewer.innerHTML = `
            <iframe 
              src="${panoUrl}" 
              style="width: 100%; height: 100%; border: none;"
              allowfullscreen>
            </iframe>
          `;
          console.log('✅ GABAY Photo Sphere panorama iframe loaded with URL:', panoUrl);
        } else {
          console.error('❌ Panorama viewer element (.panorama-viewer) not found');
        }

        console.log('📍 About to move SVG to split-screen...');

        // Update browser URL to reflect current panorama parameters
        const newUrl = `${window.location.pathname}?path_id=${encodeURIComponent(pathId)}&point_index=${encodeURIComponent(pointIndex)}&floor_number=${encodeURIComponent(floorNumber)}`;
        window.history.pushState({
          pathId: pathId,
          pointIndex: pointIndex,
          floorNumber: floorNumber,
          splitScreenActive: true
        }, '', newUrl);
        console.log('✅ Browser URL updated to:', newUrl);

        // Populate panorama title and description in the split-screen header (use API and fallbacks)
        try {
          const panoramaTitleEl = splitScreen.querySelector('#panorama-location-title') || document.getElementById('panorama-location-title');
          const panoramaDescEl = splitScreen.querySelector('#panorama-description') || document.getElementById('panorama-description');

          // Default placeholders
          const TITLE_FALLBACK = 'Untitled Panorama';
          const DESC_FALLBACK = 'No description available.';

          // Attempt to fetch metadata via existing helper (non-blocking if API fails)
          const meta = await fetchPanoramaData(pathId, pointIndex, floorNumber);

          if (panoramaTitleEl) panoramaTitleEl.textContent = (meta && meta.title) ? meta.title : TITLE_FALLBACK;
          if (panoramaDescEl) panoramaDescEl.textContent = (meta && meta.description) ? meta.description : DESC_FALLBACK;
        } catch (err) {
          console.warn('Failed to populate panorama metadata, applying placeholders.', err);
          const panoramaTitleEl = splitScreen.querySelector('#panorama-location-title') || document.getElementById('panorama-location-title');
          const panoramaDescEl = splitScreen.querySelector('#panorama-description') || document.getElementById('panorama-description');
          if (panoramaTitleEl) panoramaTitleEl.textContent = 'Untitled Panorama';
          if (panoramaDescEl) panoramaDescEl.textContent = 'No description available.';
        }

        console.log('📍 About to move SVG to split-screen...');

        // Move the SVG to split-screen using helper function
        const mapMoveResult = moveMapToSplitScreen();
        
        if (mapMoveResult !== false) {
          // Show the split screen overlay
          splitScreen.classList.remove('hidden');
          console.log('🎉 PANORAMA SPLIT SCREEN VISIBLE — map should remain interactive');
        } else {
          console.error('❌ Failed to move map to split-screen, aborting split-screen display');
          return;
        }
      }

      // ===== SPLIT-SCREEN MAP MANAGEMENT =====
      
      // Helper function to move SVG to split-screen container
      function moveMapToSplitScreen() {
        console.log('🗺️ Starting moveMapToSplitScreen...');
        
        const mapContainer = document.getElementById('split-map-container');
        const svgContainer = document.getElementById('svg-container');
        const svg = svgContainer?.querySelector('svg');

        console.log('🔍 Element check:', {
          mapContainer: !!mapContainer,
          svgContainer: !!svgContainer, 
          svg: !!svg,
          svgPanZoomInstance: !!window.svgPanZoomInstance
        });

        if (!svg || !mapContainer || !svgContainer) {
          console.error('❌ Cannot move map to split-screen - missing elements:', {
            svg: !!svg,
            mapContainer: !!mapContainer,
            svgContainer: !!svgContainer
          });
          return false;
        }

        // Store original container for restore
        window.__originalSvgContainerForPanorama = svgContainer;

        // Save current pan/zoom state so we can restore it after move
        if (window.svgPanZoomInstance && typeof window.svgPanZoomInstance.getPan === 'function') {
          try {
            window.__svgPanZoomStateBeforePanorama = {
              zoom: window.svgPanZoomInstance.getZoom(),
              pan: window.svgPanZoomInstance.getPan()
            };
            console.log('Saved svgPanZoom state before panorama:', window.__svgPanZoomStateBeforePanorama);
          } catch (e) {
            console.warn('Could not read svgPanZoom state before panorama:', e);
          }
        }

        console.log('✅ Moving original SVG into split-screen map container');
        mapContainer.innerHTML = '';
        mapContainer.appendChild(svg);

        // Hide the original container (it no longer contains the svg)
        svgContainer.style.display = 'none';

        // Safely resize and reset pan/zoom after moving SVG
        setTimeout(() => {
          if (window.svgPanZoomInstance) {
            try {
              console.log('🔧 Resizing and resetting pan-zoom after SVG move');
              window.svgPanZoomInstance.resize();
              window.svgPanZoomInstance.fit();
              window.svgPanZoomInstance.center();
              console.log('✅ Pan-zoom safely reset after SVG move');
            } catch (e) {
              console.warn('Failed to reset svgPanZoomInstance after moving SVG, will recreate:', e);
              // Clear the corrupted instance so it can be recreated
              window.svgPanZoomInstance = null;
            }
          }
        }, 100);
        
        console.log('🎉 Map moved to split-screen container successfully');
        return true;
      }

      // Function to hide panorama split screen
      function hidePanoramaSplitScreen() {
        console.log('Hiding panorama split screen and restoring SVG');

        const splitScreen = document.getElementById('panorama-split-screen');
        const svgContainer = document.getElementById('svg-container');
        const mapContainer = document.getElementById('split-map-container');

        if (splitScreen) {
          splitScreen.classList.add('hidden');
        }

        // If we moved the SVG earlier, move it back to its original container
        const svg = mapContainer?.querySelector('svg');
        const original = window.__originalSvgContainerForPanorama || svgContainer;
          if (svg && original) {
          console.log('✅ Restoring SVG back to original container');
          original.appendChild(svg);
          // Show the original container
          original.style.display = 'block';

          // Restore svg-pan-zoom state (resize then reapply saved zoom/pan)
          setTimeout(() => {
            if (window.svgPanZoomInstance) {
              try {
                window.svgPanZoomInstance.resize();
                setTimeout(() => {
                  const saved = window.__svgPanZoomStateBeforePanorama;
                  if (saved) {
                    try {
                      if (typeof window.svgPanZoomInstance.zoom === 'function') window.svgPanZoomInstance.zoom(saved.zoom);
                      if (typeof window.svgPanZoomInstance.pan === 'function') window.svgPanZoomInstance.pan(saved.pan);
                      console.log('Reapplied svgPanZoom state after restore');
                    } catch (err) {
                      console.warn('Failed to reapply svgPanZoom state after restore:', err);
                    }
                  }
                }, 20);
              } catch (e) {
                console.warn('Failed to refresh svgPanZoomInstance after restoring SVG:', e);
              }
            }
          }, 50);
        } else if (svgContainer) {
          // Fallback: ensure original container is visible
          svgContainer.style.display = 'block';
        }
        
        // Restore original URL without panorama parameters
        const baseUrl = window.location.pathname;
        window.history.pushState({
          splitScreenActive: false
        }, '', baseUrl);
        console.log('✅ Browser URL restored to:', baseUrl);
        
        // Reset panorama marker visuals when exiting panorama mode
        try { resetPanoramaMarkers(); } catch (e) { console.warn('resetPanoramaMarkers not available on hide', e); }
      }

      // Global function to refresh SVG container
      function refreshSvgContainer() {
        if (window.svgPanZoomInstance) {
          requestAnimationFrame(() => {
            // Ensure containers are properly sized for mobile
            const svgContainer = document.getElementById('svg-container');
            const svg = document.querySelector('svg');
            
            if (svgContainer) {
              // For mobile, use viewport dimensions
              const isMobile = window.innerWidth <= 768;
              if (isMobile) {
                svgContainer.style.width = '100vw';
                svgContainer.style.height = `${window.innerHeight - 120}px`; // Account for header and nav
              } else {
                svgContainer.style.width = '100%';
                svgContainer.style.height = '100%';
              }
            }
            
            if (svg) {
              const isMobile = window.innerWidth <= 768;
              if (isMobile) {
                svg.style.width = '100vw';
                svg.style.height = `${window.innerHeight - 120}px`;
              } else {
                svg.style.width = '100%';
                svg.style.height = '100%';
              }
            }
            
            // Refresh SVG view with proper sequencing (resize only; do not auto-fit to preserve user zoom/pan)
            if (window.svgPanZoomInstance && typeof window.svgPanZoomInstance.resize === 'function') {
              try {
                window.svgPanZoomInstance.resize();
              } catch (e) {
                console.warn("Failed to refresh SVG container:", e);
              }
            }
          });
        }
      }

      // ========================================
      // PANORAMA SPLIT-SCREEN FUNCTIONALITY
      // ========================================
      
      // Global panorama state
      window.panoramaMode = false;
      let originalSvgContainer = null;
      
      // Function to enter split-screen panorama mode
      function enterPanoramaMode(panoramaData) {
        console.log('Entering panorama mode with data:', panoramaData);
        
        const splitScreen = document.getElementById('panorama-split-screen');
        const mapContainer = document.getElementById('split-map-container');
        const svgContainer = document.getElementById('svg-container');
        const panoramaSky = document.getElementById('panorama-sky');
        const panoramaTitle = document.getElementById('panorama-location-title');
        const panoramaDescription = document.getElementById('panorama-description');
        
        if (!splitScreen || !mapContainer || !svgContainer || !panoramaSky) {
          console.error('Split-screen elements not found');
          return;
        }
        
        // Store reference to original container
        originalSvgContainer = svgContainer;
        
        // Set panorama image
        const imagePath = `../Pano/${panoramaData.image_filename}`;
        panoramaSky.setAttribute('src', imagePath);
        
        // Update panorama info
        panoramaTitle.textContent = panoramaData.title || 'Panorama View';
        panoramaDescription.textContent = panoramaData.description || '360° view of the location';
        
        // Move SVG to split-screen map container
        const svg = svgContainer.querySelector('svg');
        if (svg) {
          mapContainer.appendChild(svg);
        }
        
        // Show split-screen and hide original container
        splitScreen.classList.remove('hidden');
        svgContainer.style.display = 'none';
        
        // Update global state
        window.panoramaMode = true;
        
        // Refresh SVG view in new container
        setTimeout(() => {
          refreshSvgInSplitScreen();
        }, 100);
      }
      
      // Function to exit panorama mode
      function exitPanoramaMode() {
        console.log('Exiting panorama mode');
        
        const splitScreen = document.getElementById('panorama-split-screen');
        const mapContainer = document.getElementById('split-map-container');
        const svgContainer = document.getElementById('svg-container');
        
        if (!splitScreen || !mapContainer || !svgContainer || !originalSvgContainer) {
          console.error('Cannot exit panorama mode - elements not found');
          return;
        }
        
        // Move SVG back to original container
        const svg = mapContainer.querySelector('svg');
        if (svg) {
          originalSvgContainer.appendChild(svg);
        }
        
        // Hide split-screen and show original container
        splitScreen.classList.add('hidden');
        svgContainer.style.display = 'block';
        
        // Update global state
        window.panoramaMode = false;
        
        // Refresh SVG view in original container
        setTimeout(() => {
          refreshSvgContainer();
        }, 100);
      }
      
      // Function to refresh SVG in split-screen mode
      function refreshSvgInSplitScreen() {
        if (!window.panoramaMode || !window.svgPanZoomInstance) return;
        
        requestAnimationFrame(() => {
          const mapContainer = document.getElementById('split-map-container');
          const svg = mapContainer?.querySelector('svg');
          
          if (svg && mapContainer) {
            // Set appropriate dimensions for split-screen
            svg.style.width = '100%';
            svg.style.height = '100%';
            
            // Refresh pan-zoom instance (resize only to preserve user pan/zoom)
            try {
              if (typeof window.svgPanZoomInstance.resize === 'function') {
                window.svgPanZoomInstance.resize();
              }
            } catch (e) {
              console.warn("Failed to refresh SVG in split-screen:", e);
            }
          }
        });
      }

      // Office Details Modal Functions
      function showOfficeDetailsModal(office) {
        console.log('Showing office details modal for:', office);
        
        const modal = document.getElementById('office-details-modal');
        const modalOfficeName = document.getElementById('modal-office-name');
        const modalImageContainer = document.getElementById('modal-office-image-container');
        const modalStatusBadge = document.getElementById('modal-status-badge');
        const modalDescription = document.getElementById('modal-office-description');
        const modalServices = document.getElementById('modal-office-services');
        const modalContact = document.getElementById('modal-office-contact');
        const modalHours = document.getElementById('modal-office-hours');
        const modalFloorIndicator = document.getElementById('modal-floor-indicator');

        if (!modal) {
          console.error('Office details modal not found');
          return;
        }

        // Set office name
        if (modalOfficeName) {
          modalOfficeName.textContent = office.name || 'Office Details';
        }

        // Set office image
        if (modalImageContainer) {
          if (office.image_path) {
            modalImageContainer.innerHTML = `<img src="../office_images/${office.image_path}" alt="${office.name || 'Office'}" />`;
          } else {
            modalImageContainer.innerHTML = `<div class="office-image-placeholder"><i class="fas fa-building"></i></div>`;
          }
        }

        // Set status badge
        if (modalStatusBadge) {
          const isOpen = isOfficeOpen(office.open_time, office.close_time);
          let statusClass = 'status-badge ';
          let statusText = office.status || 'Unknown';
          
          if (isOpen === null) {
            if (office.status && office.status.toLowerCase() === 'open') {
              statusClass += 'status-open';
            } else if (office.status && office.status.toLowerCase() === 'closed') {
              statusClass += 'status-closed';
            }
          } else {
            if (isOpen) {
              statusClass += 'status-open';
              statusText = 'Open';
            } else {
              statusClass += 'status-closed';
              statusText = 'Closed';
            }
          }
          
          let statusContent = statusText;
          if (office.open_time && office.close_time) {
            const formatTime = (timeStr) => {
              const [hours, minutes] = timeStr.split(':');
              const hour = parseInt(hours);
              const ampm = hour >= 12 ? 'PM' : 'AM';
              const hour12 = hour % 12 || 12;
              return `${hour12}:${minutes} ${ampm}`;
            };
            statusContent += ` (${formatTime(office.open_time)} - ${formatTime(office.close_time)})`;
          }
          
          modalStatusBadge.className = statusClass;
          modalStatusBadge.innerHTML = `<i class="fas fa-circle"></i> ${statusContent}`;
        }

        // Set floor indicator
        if (modalFloorIndicator) {
          let floorNumber = 'N/A';
          if (office.location) {
            // Try to extract floor number from room ID patterns
            const roomMatch = office.location.match(/room-(\d)(\d{2})-/);
            if (roomMatch) {
              floorNumber = roomMatch[1];
            } else {
              // Alternative: if location contains direct floor info
              const floorMatch = office.location.match(/(\d+)(st|nd|rd|th)?\s*floor/i);
              if (floorMatch) {
                floorNumber = floorMatch[1];
              } else {
                // If room number starts with floor digit
                const numberMatch = office.location.match(/\b([1-9])0\d\b/);
                if (numberMatch) {
                  floorNumber = numberMatch[1];
                }
              }
            }
          }
          modalFloorIndicator.innerHTML = `<i class="fas fa-layer-group"></i> <span>Floor ${floorNumber}</span>`;
        }

        // Set description
        if (modalDescription) {
          if (office.details && office.details.trim()) {
            modalDescription.textContent = office.details;
            modalDescription.className = 'office-description';
          } else {
            modalDescription.textContent = 'No description available.';
            modalDescription.className = 'no-description';
          }
        }

        // Set services
        if (modalServices) {
          if (office.services && office.services.trim()) {
            const services = office.services.split('\n').filter(s => s.trim());
            if (services.length > 0) {
              modalServices.innerHTML = services.map(service => 
                `<div class="service-item"><i class="fas fa-check-circle"></i> ${service.trim()}</div>`
              ).join('');
            } else {
              modalServices.innerHTML = '<div class="no-services">No services information available.</div>';
            }
          } else {
            modalServices.innerHTML = '<div class="no-services">No services information available.</div>';
          }
        }

        // Set contact information
        if (modalContact) {
          if (office.contact && office.contact.trim()) {
            // Try to detect if it's a phone number, email, or general contact info
            const contact = office.contact.trim();
            let contactHtml = '';
            
            if (contact.match(/^\+?[\d\s\-\(\)]+$/)) {
              // Looks like a phone number
              contactHtml = `<div class="contact-item"><i class="fas fa-phone"></i> <a href="tel:${contact.replace(/\s/g, '')}" class="contact-link">${contact}</a></div>`;
            } else if (contact.includes('@')) {
              // Looks like an email
              contactHtml = `<div class="contact-item"><i class="fas fa-envelope"></i> <a href="mailto:${contact}" class="contact-link">${contact}</a></div>`;
            } else {
              // General contact info
              contactHtml = `<div class="contact-item"><i class="fas fa-info-circle"></i> <span>${contact}</span></div>`;
            }
            
            modalContact.innerHTML = contactHtml;
          } else {
            modalContact.innerHTML = '<div class="no-contact">No contact information available.</div>';
          }
        }

        // Set hours information
        if (modalHours) {
          if (office.open_time && office.close_time) {
            const formatTime = (timeStr) => {
              const [hours, minutes] = timeStr.split(':');
              const hour = parseInt(hours);
              const ampm = hour >= 12 ? 'PM' : 'AM';
              const hour12 = hour % 12 || 12;
              return `${hour12}:${minutes} ${ampm}`;
            };
            
            const currentDay = new Date().toLocaleDateString('en-US', { weekday: 'long' });
            modalHours.innerHTML = `
              <div class="today-hours">
                <span class="day-label">Today:</span> ${formatTime(office.open_time)} - ${formatTime(office.close_time)}
              </div>
              <div class="weekly-hours">
                <div class="hours-row ${currentDay === 'Monday' ? 'today' : ''}">
                  <span class="day-name">Monday</span>
                  <span class="hours">${formatTime(office.open_time)} - ${formatTime(office.close_time)}</span>
                </div>
                <div class="hours-row ${currentDay === 'Tuesday' ? 'today' : ''}">
                  <span class="day-name">Tuesday</span>
                  <span class="hours">${formatTime(office.open_time)} - ${formatTime(office.close_time)}</span>
                </div>
                <div class="hours-row ${currentDay === 'Wednesday' ? 'today' : ''}">
                  <span class="day-name">Wednesday</span>
                  <span class="hours">${formatTime(office.open_time)} - ${formatTime(office.close_time)}</span>
                </div>
                <div class="hours-row ${currentDay === 'Thursday' ? 'today' : ''}">
                  <span class="day-name">Thursday</span>
                  <span class="hours">${formatTime(office.open_time)} - ${formatTime(office.close_time)}</span>
                </div>
                <div class="hours-row ${currentDay === 'Friday' ? 'today' : ''}">
                  <span class="day-name">Friday</span>
                  <span class="hours">${formatTime(office.open_time)} - ${formatTime(office.close_time)}</span>
                </div>
                <div class="hours-row ${currentDay === 'Saturday' ? 'today' : ''}">
                  <span class="day-name">Saturday</span>
                  <span class="hours">Closed</span>
                </div>
                <div class="hours-row ${currentDay === 'Sunday' ? 'today' : ''}">
                  <span class="day-name">Sunday</span>
                  <span class="hours">Closed</span>
                </div>
              </div>
            `;
          } else {
            modalHours.innerHTML = '<div class="no-hours">No hours information available.</div>';
          }
        }

        // Show the modal
        modal.classList.add('active');
      }

      function hideOfficeDetailsModal() {
        const modal = document.getElementById('office-details-modal');
        if (modal) {
          modal.classList.remove('active');
        }
      }

      // Enhanced Drawer Functions
      function setupEnhancedDrawerListeners() {
        // Share functionality
        const shareBtn = document.getElementById('share-btn');
        if (shareBtn) {
          shareBtn.addEventListener('click', () => {
            if (window.currentSelectedOffice) {
              shareOffice(window.currentSelectedOffice.id);
            }
          });
        }
        
        // Favorite functionality
        const favoriteBtn = document.getElementById('favorite-btn');
        if (favoriteBtn) {
          favoriteBtn.addEventListener('click', () => {
            if (window.currentSelectedOffice) {
              toggleFavorite(window.currentSelectedOffice.id);
            }
          });
        }
        
        // Call functionality
        const callBtn = document.getElementById('call-btn');
        if (callBtn) {
          callBtn.addEventListener('click', () => {
            if (window.currentSelectedOffice && window.currentSelectedOffice.contact) {
              const phone = extractPhoneNumber(window.currentSelectedOffice.contact);
              if (phone) {
                window.location.href = `tel:${phone}`;
              }
            }
          });
        }
        
        // Add ripple effect to all drawer buttons
        document.querySelectorAll('.drawer-btn').forEach(button => {
          button.addEventListener('click', createRippleEffect);
        });
      }

      function shareOffice(officeId) {
        const office = officesData.find(o => o.id == officeId);
        if (!office) return;
        
        const shareData = {
          title: office.name,
          text: `Check out ${office.name} in GABAY Office Directory`,
          url: `${window.location.origin}${window.location.pathname}?office_id=${officeId}`
        };
        
        if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
          navigator.share(shareData).catch(err => {
            console.log('Error sharing:', err);
            fallbackShare(shareData.url);
          });
        } else {
          fallbackShare(shareData.url);
        }
      }

      function fallbackShare(url) {
        if (navigator.clipboard) {
          navigator.clipboard.writeText(url).then(() => {
            showMobileToast('Link copied to clipboard!', 'success');
          }).catch(() => {
            legacyCopyToClipboard(url);
          });
        } else {
          legacyCopyToClipboard(url);
        }
      }

      function legacyCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
          document.execCommand('copy');
          showMobileToast('Link copied to clipboard!', 'success');
        } catch (err) {
          showMobileToast('Could not copy link', 'error');
        }
        
        document.body.removeChild(textArea);
      }

      function toggleFavorite(officeId) {
        let favorites = JSON.parse(localStorage.getItem('gabay_favorites') || '[]');
        const index = favorites.indexOf(officeId);
        const favoriteBtn = document.getElementById('favorite-btn');
        const icon = favoriteBtn.querySelector('i');
        
        if (index > -1) {
          favorites.splice(index, 1);
          icon.className = 'far fa-heart';
          favoriteBtn.classList.remove('favorited');
          showMobileToast('Removed from favorites', 'success');
        } else {
          favorites.push(officeId);
          icon.className = 'fas fa-heart';
          favoriteBtn.classList.add('favorited');
          showMobileToast('Added to favorites', 'success');
        }
        
        localStorage.setItem('gabay_favorites', JSON.stringify(favorites));
      }

      function updateFavoriteButton(officeId) {
        const favorites = JSON.parse(localStorage.getItem('gabay_favorites') || '[]');
        const favoriteBtn = document.getElementById('favorite-btn');
        const icon = favoriteBtn.querySelector('i');
        
        if (favorites.includes(officeId)) {
          icon.className = 'fas fa-heart';
          favoriteBtn.classList.add('favorited');
        } else {
          icon.className = 'far fa-heart';
          favoriteBtn.classList.remove('favorited');
        }
      }

      function extractPhoneNumber(contact) {
        if (!contact) return null;
        const phoneRegex = /[\+]?[1-9]?[\d\s\-\(\)]{7,}/;
        const match = contact.match(phoneRegex);
        return match ? match[0].replace(/\s/g, '') : null;
      }

      function createRippleEffect(event) {
        const button = event.currentTarget;
        const rect = button.getBoundingClientRect();
        const ripple = document.createElement('span');
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        button.appendChild(ripple);
        
        setTimeout(() => {
          ripple.remove();
        }, 600);
      }

      function showMobileToast(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `mobile-toast ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        
        setTimeout(() => {
          toast.classList.remove('show');
          setTimeout(() => toast.remove(), 300);
        }, duration);
      }
      
      // Function to fetch panorama data from API
      async function fetchPanoramaData(pathId, pointIndex, floorNumber) {
        try {
          const response = await fetch(`../panorama_api.php?action=get&path_id=${pathId}&point_index=${pointIndex}&floor_number=${floorNumber}`);
          const data = await response.json();
          
          if (data.success && data.panorama) {
            return data.panorama;
          }
          return null;
        } catch (error) {
          console.error('Error fetching panorama data:', error);
          return null;
        }
      }
      
      // Function to handle panorama point clicks
      window.handlePanoramaClick = async function(pathId, pointIndex, floorNumber = 1) {
        console.log(`Panorama point clicked: path=${pathId}, point=${pointIndex}, floor=${floorNumber}`);
        
        try {
          const panoramaData = await fetchPanoramaData(pathId, pointIndex, floorNumber);
          
          if (panoramaData) {
            console.log('Panorama data received:', panoramaData);
            enterPanoramaMode(panoramaData);
          } else {
            console.warn('No panorama data found for this point');
            alert('No panorama available for this location.');
          }
        } catch (error) {
          console.error('Error handling panorama click:', error);
          alert('Error loading panorama. Please try again.');
        }
      };
      
      // Event listeners for panorama controls
      document.addEventListener('DOMContentLoaded', () => {
        // Handle any pending panorama clicks
        if (window.pendingPanoramaClick && window.handlePanoramaClick) {
          console.log('Processing pending panorama click:', window.pendingPanoramaClick);
          const { pathId, pointIndex, floorNumber } = window.pendingPanoramaClick;
          window.handlePanoramaClick(pathId, pointIndex, floorNumber);
          window.pendingPanoramaClick = null;
        }
        
        // Ensure panorama markers are reset on load (no panorama open)
        try { resetPanoramaMarkers(); } catch (e) { console.warn('resetPanoramaMarkers not available on load', e); }

        // Close panorama button
        const closePanoramaBtn = document.getElementById('close-panorama');
        if (closePanoramaBtn) {
          closePanoramaBtn.addEventListener('click', hidePanoramaSplitScreen);
        }
        
        // Exit panorama mode button
        const exitPanoramaModeBtn = document.getElementById('exit-panorama-mode');
        if (exitPanoramaModeBtn) {
          exitPanoramaModeBtn.addEventListener('click', hidePanoramaSplitScreen);
        }
        
        // Handle escape key to exit panorama mode
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            const splitScreen = document.getElementById('panorama-split-screen');
            if (splitScreen && !splitScreen.classList.contains('hidden')) {
              hidePanoramaSplitScreen();
            }
          }
        });

        // 3D demo modal wiring
        const switch3dBtn = document.getElementById('switch-3d-view-btn');
        const threeDemoModal = document.getElementById('three-demo-modal');
        const closeThreeDemoBtn = document.getElementById('close-three-demo');
        const threeDemoFrame = document.getElementById('three-demo-frame');

        if (switch3dBtn && threeDemoModal) {
          switch3dBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            // Show modal
            threeDemoModal.style.display = 'flex';
            // Optional: reload iframe to ensure fresh state
            try {
              threeDemoFrame.contentWindow.location.reload();
            } catch (e) {
              // ignore cross-origin or not yet loaded
            }
          });
        }

        if (closeThreeDemoBtn && threeDemoModal) {
          closeThreeDemoBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            threeDemoModal.style.display = 'none';
            // stop any audio/animation by resetting iframe src (lightweight)
            try { threeDemoFrame.src = threeDemoFrame.src; } catch (e) {}
          });
        }

        // Close modal when clicking outside the content
        if (threeDemoModal) {
          threeDemoModal.addEventListener('click', (e) => {
            if (e.target === threeDemoModal) {
              threeDemoModal.style.display = 'none';
              try { threeDemoFrame.src = threeDemoFrame.src; } catch (e) {}
            }
          });
        }

        // Office Details Modal Event Handlers
        const closeDetailsModalBtn = document.getElementById('close-details-modal');
        const officeDetailsModal = document.getElementById('office-details-modal');
        
        if (closeDetailsModalBtn) {
          closeDetailsModalBtn.addEventListener('click', hideOfficeDetailsModal);
        }
        
        if (officeDetailsModal) {
          // Close modal when clicking outside the content
          officeDetailsModal.addEventListener('click', (e) => {
            if (e.target === officeDetailsModal) {
              hideOfficeDetailsModal();
            }
          });
        }
        
        // Handle escape key to close office details modal
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            const modal = document.getElementById('office-details-modal');
            if (modal && modal.classList.contains('active')) {
              hideOfficeDetailsModal();
            }
          }
        });
      });

      // Safe function to draw paths and doors only when everything is ready
      function drawPathsAndDoorsWhenReady() {
        console.log('Attempting to draw paths and doors...');
        const svg = document.querySelector('svg');
        const panZoomViewport = svg ? svg.querySelector('.svg-pan-zoom_viewport') : null;

        // Ensure the pan-zoom viewport is ready before drawing
        if (!panZoomViewport) {
          console.warn('Pan-zoom viewport not ready. Drawing will be deferred.');
          // Add a listener to try again once the pan-zoom is ready
          document.addEventListener('panZoomReady', drawPathsAndDoorsWhenReady, { once: true });
          return;
        }

        console.log('Pan-zoom viewport is ready. Proceeding with drawing.');

        // Clear any old groups to prevent duplication on floor change
        const oldPathGroup = panZoomViewport.querySelector('#walkable-path-group');
        if (oldPathGroup) oldPathGroup.remove();
        const oldMarkerGroup = panZoomViewport.querySelector('#marker-group');
        if (oldMarkerGroup) oldMarkerGroup.remove();
  const oldDoorGroup = panZoomViewport.querySelector('#entry-points-group');
        if (oldDoorGroup) oldDoorGroup.remove();

        if (!window.pendingGraphData) {
          console.warn('No pending graph data available for drawing');
          return;
        }

        // Set the floor graph
        window.floorGraph = window.pendingGraphData;

        // Draw walkable paths first
        if (window.floorGraph.walkablePaths && Array.isArray(window.floorGraph.walkablePaths)) {
          console.log('Drawing', window.floorGraph.walkablePaths.length, 'walkable paths');
          window.floorGraph.walkablePaths.forEach((path, index) => {
            try {
              console.log(`Drawing path ${index + 1}/${window.floorGraph.walkablePaths.length}: ${path.id}`);
              drawWalkablePath(path);
            } catch (error) {
              console.error(`Error drawing path ${path.id}:`, error);
            }
          });
        }

        // Draw entry points if available
        if (window.floorGraph.rooms) {
          console.log('Drawing entry points for', Object.keys(window.floorGraph.rooms).length, 'rooms');
          drawEntryPoints(window.floorGraph.rooms);
        }

        // Initialize pathfinding room selection now that graph data is loaded
        if (typeof window.initRoomSelection === 'function') {
          console.log('Initializing pathfinding room selection handlers');
          window.initRoomSelection();
        }

        // Re-render any active path for the currently loaded floor
        if (window.activeRoute && typeof renderActiveRouteForFloor === 'function') {
          try {
            let targetFloor = null;
            if (typeof window.currentFloorNumber === 'number' && !Number.isNaN(window.currentFloorNumber)) {
              targetFloor = window.currentFloorNumber;
            } else if (Array.isArray(window.activeRoute.floors) && window.activeRoute.floors.length > 0) {
              targetFloor = window.activeRoute.floors[0];
            }

            if (targetFloor !== null) {
              renderActiveRouteForFloor(targetFloor);
            }
          } catch (error) {
            console.warn('Unable to re-render active route after floor load:', error);
          }
        }

        // Handle office highlighting for QR scan
        if (window.highlightOfficeIdFromPHP) {
          const targetOffice = officesData.find(office => office.id == window.highlightOfficeIdFromPHP);
          if (targetOffice && targetOffice.location) {
            console.log('Highlighting office from QR scan:', targetOffice.name, 'at location:', targetOffice.location);
            setTimeout(() => {
              window.showYouAreHere(targetOffice.location);
              handleRoomClick(targetOffice);
            }, 500);
          }
        }

        // Clear the pending data
        window.pendingGraphData = null;

  console.log('Path and entry point drawing completed');
        
        // Debug: Check if panorama markers were created
        setTimeout(() => {
          const markers = document.querySelectorAll('.path-marker.point-marker');
          console.log('Panorama markers created:', markers.length);
          markers.forEach((marker, index) => {
            console.log(`Marker ${index}:`, {
              pathId: marker.getAttribute('data-path-id'),
              pointIndex: marker.getAttribute('data-point-index'),
              position: { x: marker.getAttribute('cx'), y: marker.getAttribute('cy') },
              radius: marker.getAttribute('r'),
              fill: marker.getAttribute('fill')
            });
          });
          
          if (markers.length === 0) {
            console.warn('No panorama markers found! Checking floor graph data...');
            if (window.floorGraph && window.floorGraph.walkablePaths) {
              window.floorGraph.walkablePaths.forEach(path => {
                const panoPoints = path.pathPoints.filter(point => point.isPano);
                console.log(`Path ${path.id} has ${panoPoints.length} panorama points:`, panoPoints);
              });
            }
          }
        }, 1000);
        
        // Override panorama marker click handlers for mobile
        setTimeout(() => {
          // Look for panorama markers with multiple possible selectors
          let markers = document.querySelectorAll('.path-marker.point-marker');
          console.log('Found', markers.length, 'markers with .path-marker.point-marker');
          
          if (markers.length === 0) {
            markers = document.querySelectorAll('.point-marker');
            console.log('Found', markers.length, 'markers with .point-marker');
          }
          
          if (markers.length === 0) {
            markers = document.querySelectorAll('[class*="point-marker"]');
            console.log('Found', markers.length, 'markers with class containing point-marker');
          }
          
          // Also check for any elements with onclick handlers that call openPanoramaEditor
          const elementsWithPanoramaClick = document.querySelectorAll('[onclick*="openPanoramaEditor"]');
          console.log('Found', elementsWithPanoramaClick.length, 'elements with openPanoramaEditor onclick');
          
          if (markers.length === 0 && elementsWithPanoramaClick.length === 0) {
            console.warn('No panorama markers found! Retrying in 2 seconds...');
            setTimeout(() => {
              const retryMarkers = document.querySelectorAll('.path-marker.point-marker, .point-marker, [onclick*="openPanoramaEditor"]');
              console.log('Retry found', retryMarkers.length, 'panorama markers');
              if (retryMarkers.length > 0) {
                setupPanoramaMarkerHandlers(retryMarkers);
              }
            }, 2000);
            return;
          }
          
          // Combine all found markers
          const allMarkers = [...markers, ...elementsWithPanoramaClick];
          setupPanoramaMarkerHandlers(allMarkers);
        }, 1500); // Give extra time for pathfinding to complete
        
        function setupPanoramaMarkerHandlers(markers) {
          console.log('Setting up click handlers for', markers.length, 'panorama markers');
          
          markers.forEach((marker, index) => {
            console.log('Setting up marker', index, 'with classes:', marker.className);
            
            // Remove existing event listeners by cloning the element
            const newMarker = marker.cloneNode(true);
            marker.parentNode.replaceChild(newMarker, marker);
            
            // Add new mobile-specific click handler
            newMarker.addEventListener('click', (e) => {
              console.log('🎯 PANORAMA MARKER CLICKED! Marker index:', index);
              e.preventDefault();
              e.stopPropagation();
              
              // Disable alerts completely
              window.alert = function() { 
                console.log('Alert disabled for panorama mode'); 
              };
              
              // Show split screen immediately
              const pathId = newMarker.getAttribute('data-path-id') || 'path1';
              const pointIndex = newMarker.getAttribute('data-point-index') || 5;
              const floorNumber = newMarker.getAttribute('data-floor-number') || 1;
              showPanoramaSplitScreen(pathId, pointIndex, floorNumber);
            });
            
            // Also add touch event for mobile
            newMarker.addEventListener('touchend', (e) => {
              console.log('🎯 PANORAMA MARKER TOUCHED! Marker index:', index);
              e.preventDefault();
              e.stopPropagation();
              
              // Disable alerts completely
              window.alert = function() { 
                console.log('Alert disabled for panorama mode'); 
              };
              
              // Show split screen immediately
              const pathId = newMarker.getAttribute('data-path-id') || 'path1';
              const pointIndex = newMarker.getAttribute('data-point-index') || 5;
              const floorNumber = newMarker.getAttribute('data-floor-number') || 1;
              showPanoramaSplitScreen(pathId, pointIndex, floorNumber);
            });
            
            // Add visual feedback to make sure markers are clickable
            newMarker.style.cursor = 'pointer';
            newMarker.style.filter = 'drop-shadow(0 0 3px rgba(255, 68, 68, 0.8))';
          });
        }
      }

      // Global function to populate and show drawer
      function populateAndShowDrawerWithData(office) {
        console.log("Attempting to populate and show drawer with data:", office);
        if (!office) {
          console.warn("populateAndShowDrawerWithData: No office data provided.");
          return;
        }

        const detailsDrawer = document.getElementById("details-drawer");
        const drawerOfficeNameEl = document.getElementById("drawer-office-name");
        const drawerOfficeDetailsEl = document.getElementById("drawer-office-details");
        const drawerOfficeContactEl = document.getElementById("drawer-office-contact");
        const drawerOfficeStatusEl = document.getElementById("drawer-office-status");
        const detailsBtn = document.getElementById("details-btn");

        if (!detailsDrawer || !drawerOfficeNameEl || !drawerOfficeDetailsEl || !drawerOfficeContactEl) {
          console.error("One or more drawer elements are missing from the DOM for QR display.");
          return;
        }

        drawerOfficeNameEl.textContent = office.name || 'N/A';
        drawerOfficeDetailsEl.textContent = 'Details: ' + (office.details || 'No details available.');
        drawerOfficeContactEl.textContent = 'Contact: ' + (office.contact || 'No contact info.');
        
        // Update details button click handler to show modal
        if (detailsBtn) {
          detailsBtn.onclick = function() {
            showOfficeDetailsModal(office);
          };
        }

        // Initialize enhanced drawer features
        setupEnhancedDrawerListeners();
        updateFavoriteButton(office.id);
        
        // Show call button if contact has phone number
        const callBtn = document.getElementById('call-btn');
        if (callBtn && office.contact && extractPhoneNumber(office.contact)) {
          callBtn.style.display = 'flex';
        } else if (callBtn) {
          callBtn.style.display = 'none';
        }

        const imageContainer = document.getElementById("drawer-office-image-container");
        if (imageContainer) {
          imageContainer.innerHTML = '';
          if (office.image_path) {
            imageContainer.innerHTML = `<img src="../office_images/${office.image_path}" alt="Office Image" style="width:100%;height:100%;object-fit:cover;">`;
          } else {
            imageContainer.innerHTML = `<i class='fas fa-door-open' style='font-size:2.2em;color:#04aa6d;'></i>`;
          }
        }

        if (drawerOfficeStatusEl) {
          let status = office.status || 'Unknown';
          let color = '#64748b';
          let bg = '#e0e7ef';
          
          const isOpen = isOfficeOpen(office.open_time, office.close_time);
          
          if (isOpen === null) {
            if (status.toLowerCase() === 'open') { color = '#04aa6d'; bg = '#e6f4f1'; }
            else if (status.toLowerCase() === 'closed') { color = '#ef4444'; bg = '#fee2e2'; }
            else if (status.toLowerCase() === 'maintenance') { color = '#f59e42'; bg = '#fef9c3'; }
          } else {
            if (!isOpen) {
              status = 'Closed';
              color = '#ef4444';
              bg = '#fee2e2';
            } else {
              status = 'Open';
              color = '#04aa6d';
              bg = '#e6f4f1';
            }
          }

          let statusText = status;
          if (office.open_time && office.close_time) {
            const formatTime = (timeStr) => {
              const [hours, minutes] = timeStr.split(':');
              const hour = parseInt(hours);
              const ampm = hour >= 12 ? 'PM' : 'AM';
              const hour12 = hour % 12 || 12;
              return `${hour12}:${minutes} ${ampm}`;
            };
            statusText += ` (${formatTime(office.open_time)} - ${formatTime(office.close_time)})`;
          }

          drawerOfficeStatusEl.innerHTML = `<span style="display:inline-block; padding:2px 10px; border-radius:12px; background:${bg}; color:${color}; font-weight:600; font-size:0.98em; letter-spacing:0.01em;"><i class='fas fa-circle' style='font-size:0.7em; color:${color}; margin-right:6px;'></i> ${statusText}</span>`;
        }

        if (window.openDrawer) {
          console.log("Calling window.openDrawer() from populateAndShowDrawerWithData.");
          window.openDrawer();
        } else {
          console.error("window.openDrawer is not available. Cannot open drawer for QR office.");
        }
      }

      // Global function to handle room clicks
      function handleRoomClick(office) {
        console.log("handleRoomClick called with office:", office);
        
        // Store selected office globally for pathfinding
        window.currentSelectedOffice = office;
        
        populateAndShowDrawerWithData(office);
        setTimeout(refreshSvgContainer, 250);
      }

      // Integration with desktop pathfinding.js
      
  // NOTE: selectedRooms & pathResult are already declared near the top for compatibility.
  // Avoid re-declaring here to prevent accidental state resets.

      // Mobile room click handler - only shows office details, no pathfinding
      function mobileRoomClickHandler(event) {
        // Stop event propagation to prevent desktop pathfinding handlers
        event.stopPropagation();
        event.preventDefault();
        
        const roomId = this.id;
        console.log('Mobile room clicked:', roomId);

        // Always handle as normal office selection (no pathfinding)
        const office = officesData.find(o => o.location === roomId);
        if (office) {
          handleRoomClick(office);
        } else {
          console.log('No office found for room:', roomId);
        }
        
        // Explicitly prevent any pathfinding behavior
        return false;
      }

      // Use desktop pathfinding system (already defined by pathfinding.js)
      // The functions togglePathfindingMode, drawCompletePath, clearAllPaths, etc. 
      // are now provided by the included pathfinding.js file

      // Store the original initRoomSelection function
      const originalInitRoomSelection = window.initRoomSelection;
      
      // Make sure roomClickHandler is available globally (from pathfinding.js)
      if (typeof roomClickHandler !== 'undefined') {
        window.roomClickHandler = roomClickHandler;
      }
      
      // Override initRoomSelection to allow pathfinding handlers while preventing mobile conflicts
      window.initRoomSelection = function() {
        console.log('Mobile-adapted initRoomSelection called');
        
        // Ensure floor graph is loaded before initializing pathfinding handlers
        if (!window.floorGraph || !window.floorGraph.rooms) {
          console.warn('Floor graph not ready yet, deferring pathfinding handler initialization');
          return;
        }
        
        console.log('Floor graph ready with', Object.keys(window.floorGraph.rooms).length, 'rooms, initializing pathfinding handlers');
        
        // Only attach pathfinding handlers to rooms, don't interfere with mobile room click behavior
        document.querySelectorAll('[id^="room-"]').forEach(el => {
          // Remove any existing pathfinding click listeners to avoid duplicates
          if (el._pathfindingHandler) {
            el.removeEventListener('click', el._pathfindingHandler);
          }
          
          // Store the pathfinding handler separately so it can be called programmatically
          el._pathfindingHandler = function(event) {
            // Only run pathfinding logic if explicitly called (not from normal mobile clicks)
            if (event && event._isPathfindingClick) {
              // Double-check that floor graph is still available
              if (!window.floorGraph || !window.floorGraph.rooms) {
                console.error('Floor graph lost during pathfinding operation');
                return;
              }
              // Call the global roomClickHandler function from pathfinding.js
              if (typeof window.roomClickHandler === 'function') {
                return window.roomClickHandler.call(this, event);
              } else {
                console.error('roomClickHandler not available globally');
              }
            }
          };
          
          // Don't add the event listener - we'll call the handler programmatically from the modal
        });
        
        console.log('Pathfinding handlers prepared for programmatic use');
      };

      // Function to open panorama viewer
      function openPanoramaViewer(imagePath) {
        console.log('Opening panorama viewer for:', imagePath);
        
        // Create panorama modal
        const modal = document.createElement('div');
        modal.id = 'panorama-modal';
        modal.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          width: 100vw;
          height: 100vh;
          background: rgba(0, 0, 0, 0.9);
          z-index: 9999;
          display: flex;
          align-items: center;
          justify-content: center;
        `;

        const img = document.createElement('img');
        img.src = `../Pano/${imagePath}`;
        img.style.cssText = `
          max-width: 95vw;
          max-height: 95vh;
          object-fit: contain;
          border-radius: 8px;
        `;

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.style.cssText = `
          position: absolute;
          top: 20px;
          right: 20px;
          background: rgba(255, 255, 255, 0.9);
          border: none;
          width: 40px;
          height: 40px;
          border-radius: 50%;
          font-size: 24px;
          cursor: pointer;
          z-index: 10000;
        `;

        // Close and reset active panorama marker state
        closeBtn.onclick = () => {
          modal.remove();
          try { resetPanoramaMarkers(); } catch (e) { console.warn('resetPanoramaMarkers failed', e); }
        };

        modal.onclick = (e) => {
          if (e.target === modal) {
            modal.remove();
            try { resetPanoramaMarkers(); } catch (e) { console.warn('resetPanoramaMarkers failed', e); }
          }
        };

        modal.appendChild(img);
        modal.appendChild(closeBtn);
        document.body.appendChild(modal);
      }

      // Helper to reset all panorama markers to inactive (blue) state
      function resetPanoramaMarkers() {
        try {
          document.querySelectorAll('.panorama-marker').forEach(m => {
            m.classList.remove('active');
            
            // Use the unique IDs for better performance
            const pathId = m.getAttribute('data-path-id');
            const pointIndex = m.getAttribute('data-point-index');
            
            if (pathId && pointIndex) {
              const bgCircle = document.getElementById(`camera-circle-${pathId}-${pointIndex}`);
              const icon = document.getElementById(`camera-icon-${pathId}-${pointIndex}`);
              
              if (bgCircle) {
                bgCircle.setAttribute('fill', '#2563eb');
                bgCircle.setAttribute('r', '12');
                bgCircle.removeAttribute('filter');
              }
              if (icon) {
                icon.setAttribute('fill', '#ffffff');
              }
            } else {
              // Fallback to querySelector method
              const bg = m.querySelector('.camera-bg') || m.querySelector('circle');
              if (bg) {
                bg.setAttribute('fill', '#2563eb');
                bg.setAttribute('r', '12');
              }
              const icon = m.querySelector('.camera-icon');
              if (icon) {
                icon.setAttribute('fill', '#ffffff');
              }
            }
          });
        } catch (e) {
          console.warn('Error resetting panorama markers:', e);
        }
      }

      // Enhanced text accessibility and readability with consistent font enforcement
      function enhanceSVGTextAccessibility() {
        const textElements = document.querySelectorAll('svg text, svg tspan');
        
        textElements.forEach(textEl => {
          // Apply consistent CSS class
          if (!textEl.classList.contains('room-label')) {
            textEl.classList.add('room-label');
          }
          
          // Force inline styles to override any default fonts during floor changes
          textEl.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
          textEl.style.fontWeight = "600";
          textEl.style.fontSize = "14px";
          textEl.style.fill = "#1a1a1a";
          textEl.style.stroke = "#ffffff";
          textEl.style.strokeWidth = "3px";
          textEl.style.strokeLinejoin = "round";
          textEl.style.paintOrder = "stroke fill";
          textEl.style.textAnchor = "middle";
          textEl.style.dominantBaseline = "central";
          textEl.style.vectorEffect = "non-scaling-stroke";
          
          // Apply high contrast mode if system prefers reduced motion (accessibility indicator)
          if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            textEl.classList.add('room-label-high-contrast');
          }
        });
      }

      // Function to optimize text for different zoom levels
      function optimizeTextForZoom() {
        if (!window.svgPanZoomInstance) return;
        
        try {
          const currentZoom = window.svgPanZoomInstance.getZoom();
          const textElements = document.querySelectorAll('svg text, svg tspan');
          
          textElements.forEach(textEl => {
            // Adjust stroke width based on zoom level for optimal readability
            const baseStrokeWidth = 3;
            const adjustedStrokeWidth = Math.max(1, baseStrokeWidth / Math.sqrt(currentZoom));
            textEl.setAttribute('stroke-width', adjustedStrokeWidth.toString());
          });
        } catch (e) {
          console.warn('Error optimizing text for zoom:', e);
        }
      }

      // Function to draw walkable paths on the SVG (simplified version)
      function drawWalkablePath(path) {
        console.log('Drawing walkable path:', path.id);
        const svg = document.querySelector('svg');
        if (!svg || !path.pathPoints || path.pathPoints.length === 0) {
          console.warn('Cannot draw path - missing SVG or path points');
          return;
        }

        const svgNS = "http://www.w3.org/2000/svg";
        const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g') || svg;
        
        if (!mainGroup) {
          console.warn('Cannot draw path - no main group found');
          return;
        }
        
        // Create or get the walkable path group
        let pathGroup = mainGroup.querySelector('#walkable-path-group');
        if (!pathGroup) {
          pathGroup = document.createElementNS(svgNS, 'g');
          pathGroup.id = 'walkable-path-group';
          mainGroup.appendChild(pathGroup);
          console.log('Created walkable path group in:', mainGroup.classList || mainGroup.tagName);
        }

        // Create path element
        const pathElement = document.createElementNS(svgNS, 'path');
        pathElement.id = `walkable-path-${path.id}`;
        
        // Build path data
        let pathData = `M ${path.pathPoints[0].x} ${path.pathPoints[0].y}`;
        for (let i = 1; i < path.pathPoints.length; i++) {
          pathData += ` L ${path.pathPoints[i].x} ${path.pathPoints[i].y}`;
        }
        
        pathElement.setAttribute('d', pathData);
        pathElement.setAttribute('fill', 'none');
        pathElement.setAttribute('stroke', path.style?.color || '#4CAF50');
        pathElement.setAttribute('stroke-width', path.style?.width || 3);
        pathElement.setAttribute('opacity', path.style?.opacity || 0.8);
        pathElement.setAttribute('vector-effect', 'non-scaling-stroke');
        pathElement.classList.add('walkable-path');
        
        pathGroup.appendChild(pathElement);

        // Add panorama point markers if they exist
        if (path.style && path.style.pointMarker) {
          // Create or get the marker group
          let markerGroup = mainGroup.querySelector('#marker-group');
          if (!markerGroup) {
            markerGroup = document.createElementNS(svgNS, 'g');
            markerGroup.setAttribute('id', 'marker-group');
            mainGroup.appendChild(markerGroup);
          }

          // Add markers for each point that has isPano property
          path.pathPoints.forEach((point, index) => {
            if (point.isPano) {
              // Create unique IDs for this panorama marker
              const markerId = `panorama-marker-${path.id}-${index}`;
              const circleId = `camera-circle-${path.id}-${index}`;
              const iconId = `camera-icon-${path.id}-${index}`;
              
              // Create camera icon group
              const marker = document.createElementNS(svgNS, 'g');
              marker.setAttribute('id', markerId);
              marker.classList.add('panorama-marker');
              marker.style.cursor = 'pointer';
              
              // Create background circle
              const bgCircle = document.createElementNS(svgNS, 'circle');
              bgCircle.setAttribute('id', circleId);
              bgCircle.setAttribute('cx', point.x);
              bgCircle.setAttribute('cy', point.y);
              bgCircle.setAttribute('r', '12');
              bgCircle.setAttribute('fill', '#2563eb');
              bgCircle.setAttribute('stroke', '#ffffff');
              bgCircle.setAttribute('stroke-width', '2');
              bgCircle.setAttribute('class', 'camera-bg');
              
              // Create camera icon
              const cameraIcon = document.createElementNS(svgNS, 'path');
              cameraIcon.setAttribute('id', iconId);
              cameraIcon.setAttribute('d', 'M14 4h-1l-2-2h-2l-2 2h-1c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-8c0-1.1-.9-2-2-2zm-4 7c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z');
              cameraIcon.setAttribute('fill', '#ffffff');
              cameraIcon.setAttribute('transform', `translate(${point.x - 8}, ${point.y - 8}) scale(0.8)`);
              cameraIcon.setAttribute('class', 'camera-icon');
              
              marker.appendChild(bgCircle);
              marker.appendChild(cameraIcon);
              
              // Add data attributes for panorama identification
              marker.setAttribute('data-path-id', path.id);
              marker.setAttribute('data-point-index', index);
              marker.setAttribute('data-floor-number', window.currentFloorNumber || 1);
              marker.setAttribute('data-panorama-id', `${path.id}-${index}`);
              
              // Add click event for panorama marker
              marker.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log(`Mobile panorama marker clicked: Path ${path.id}, Point ${index}`);

                // Reset all other panorama markers to default (blue)
                document.querySelectorAll('.panorama-marker').forEach(m => {
                  m.classList.remove('active');
                  const bg = m.querySelector('.camera-bg');
                  if (bg) {
                    bg.setAttribute('r', '12');
                    bg.setAttribute('fill', '#2563eb'); // default blue
                    bg.setAttribute('stroke', '#ffffff');
                    bg.removeAttribute('filter');
                  }
                });

                // Highlight this marker as active (yellow + larger)
                this.classList.add('active');
                const bg = this.querySelector('.camera-bg');
                if (bg) {
                  bg.setAttribute('r', '15');
                  bg.setAttribute('fill', '#fbbf24'); // yellow active color
                  bg.setAttribute('stroke', '#ffffff');
                  // Optionally add a subtle glow/filter via inline filter reference if defined in CSS
                  // bg.setAttribute('filter', 'url(#panorama-active-glow)');
                }

                // Call the dynamic panorama function with the correct parameters
                showPanoramaSplitScreen(path.id, index, window.currentFloorNumber || 1);
              });

              // Add hover effects
              marker.addEventListener('mouseenter', function() {
                if (!this.classList.contains('active')) {
                  const bg = this.querySelector('.camera-bg');
                  if (bg) {
                    bg.setAttribute('fill', '#3b82f6');
                    // Do not add glow here to avoid movement side-effects
                  }
                }
              });

              marker.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                  const bg = this.querySelector('.camera-bg');
                  if (bg) {
                    bg.setAttribute('fill', '#2563eb');
                    // Keep radius unchanged here; active state handles size
                  }
                }
              });

              markerGroup.appendChild(marker);
              
              // Check if this panorama should be highlighted from QR scan
              if (scannedPanoramaFromPHP && 
                  scannedPanoramaFromPHP.path_id === path.id && 
                  scannedPanoramaFromPHP.point_index === index) {
                // Floor check removed - we now load the correct floor initially
                
                // Auto-open the scanned panorama and highlight it like a clicked camera
                setTimeout(() => {
                  // Set this marker as active (normal yellow highlighting like manual click)
                  marker.classList.add('active');
                  const bg = marker.querySelector('.camera-bg');
                  if (bg) {
                    bg.setAttribute('r', '15');
                    bg.setAttribute('fill', '#fbbf24'); // yellow active color (same as manual click)
                    bg.setAttribute('stroke', '#ffffff');
                  }
                  
                  console.log('Auto-opening scanned panorama:', path.id, index);
                  
                  // Show loading message for scanned panorama
                  const loadingMsg = document.createElement('div');
                  loadingMsg.id = 'panorama-loading-msg';
                  loadingMsg.style.cssText = `
                    position: fixed; 
                    top: 20px; 
                    left: 50%; 
                    transform: translateX(-50%); 
                    background: rgba(16, 185, 129, 0.9); 
                    color: white; 
                    padding: 12px 20px; 
                    border-radius: 8px; 
                    z-index: 9999;
                    font-size: 14px;
                    font-weight: 500;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    backdrop-filter: blur(10px);
                  `;
                  loadingMsg.innerHTML = '📍 Opening scanned panorama view...';
                  document.body.appendChild(loadingMsg);
                  
                  // Automatically open the panorama viewer after a short delay
                  setTimeout(() => {
                    // Remove loading message
                    if (loadingMsg.parentNode) {
                      loadingMsg.parentNode.removeChild(loadingMsg);
                    }
                    
                    // Call the function directly to open panorama split screen
                    showPanoramaSplitScreen(path.id, index, window.currentFloorNumber || 1);
                    
                  }, 1500);
                  
                }, 500);
              }
            }
          });
        }
        
        console.log(`Path ${path.id} drawn successfully in viewport group`);
      }

      // "YOU ARE HERE" functionality

      // Function to draw entry points
      function drawEntryPoints(rooms) {
        console.log('Drawing entry points for rooms:', Object.keys(rooms).length);
        const svg = document.querySelector('svg');
        if (!svg) {
          console.warn('Cannot draw entry points - no SVG found');
          return;
        }

        let mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
        if (!mainGroup) {
          console.warn('No viewport group found, creating new main group');
          mainGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
          svg.appendChild(mainGroup);
        }

        // Create or get the entry points group
        let entryGroup = mainGroup.querySelector('#entry-points-group');
        if (!entryGroup) {
          entryGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
          entryGroup.id = 'entry-points-group';
          mainGroup.appendChild(entryGroup);
          console.log('Created entry points group in:', mainGroup.classList || mainGroup.tagName);
        }

        // Clear previous markers before drawing new ones
        entryGroup.innerHTML = '';
        entryGroup.style.opacity = '1';
        entryGroup.style.visibility = 'visible';

        const svgNS = 'http://www.w3.org/2000/svg';
        // Stair icon path - extracted from stairs-floor-svgrepo-com.svg (white stairs portion)
        const STAIR_ICON_PATH = 'M404.251,202.091h-47.17v-94.341h47.17c3.722,0,6.739-3.014,6.739-6.738c0-3.725-3.018-6.739-6.739-6.739h-53.909c-0.213,0-0.393,0.102-0.599,0.121c-0.41,0.052-0.799,0.113-1.197,0.241c-0.721,0.204-1.361,0.516-1.962,0.933c-0.01,0.006-0.021,0.007-0.032,0.015l-0.083,0.063c-0.336,0.242-0.698,0.437-0.983,0.737l-53.149,39.844c-0.244,0.192-0.523,0.329-0.738,0.553l-53.393,40.029c-0.158,0.134-0.349,0.221-0.493,0.369l-53.638,40.212c-0.077,0.07-0.175,0.112-0.249,0.186l-53.883,40.395l-0.003,0.002l-26.23,19.665c-2.978,2.23-3.583,6.455-1.353,9.43c1.326,1.771,3.349,2.698,5.399,2.698c1.405,0,2.823-0.441,4.034-1.349l16.175-12.127v87.529h-20.217c-3.722,0-6.738,3.014-6.738,6.739v40.432c0,3.725,3.018,6.739,6.738,6.739H404.25c3.722,0,6.739-3.014,6.739-6.739V208.829C410.989,205.105,407.972,202.091,404.251,202.091z M336.864,202.091c-3.722,0-6.739,3.014-6.739,6.738v33.694H303.17v-97.582l40.432-30.31v87.461h-6.738V202.091z M282.955,242.523c-3.722,0-6.738,3.014-6.738,6.738v26.955h-26.955v-90.86l40.432-30.31v87.477L282.955,242.523L282.955,242.523z M222.307,276.217c-3.722,0-6.739,3.014-6.739,6.738v40.432h-20.215v-97.615l40.432-30.311v80.756H222.307z M141.442,266.187l40.432-30.311v87.513h-13.477c-3.722,0-6.739,3.014-6.739,6.739v33.693h-20.215L141.442,266.187L141.442,266.187z M397.513,404.251H114.487v-26.955h53.909c3.722,0,6.739-3.014,6.739-6.739v-33.693h47.171c3.722,0,6.738-3.014,6.738-6.739v-40.432h53.909c3.722,0,6.739-3.014,6.739-6.739V256h47.171c3.722,0,6.739-3.014,6.739-6.739v-33.693h53.909v188.683H397.513z';

        Object.keys(rooms).forEach(roomId => {
          const room = rooms[roomId];
          const entryPoints = Array.isArray(room?.entryPoints) && room.entryPoints.length
            ? room.entryPoints
            : Array.isArray(room?.doorPoints) && room.doorPoints.length
              ? room.doorPoints
              : [];

          if (!entryPoints.length) {
            return;
          }

          const markerStyle = room?.style?.pointMarker || {};
          const defaultStyle = {
            radius: 6,
            color: '#FF6B35',
            strokeColor: '#000',
            strokeWidth: 1,
            visible: true,
            hoverColor: '#1A5DBA',
            activeColor: '#fbbf24'
          };

          const resolvedStyle = {
            ...defaultStyle,
            ...markerStyle
          };

          // Detect if this is a stair room
          const isStairRoom = Boolean(
            (typeof room?.type === 'string' && room.type.toLowerCase() === 'stair') ||
            room?.stairKey ||
            (typeof roomId === 'string' && roomId.toLowerCase().includes('stair'))
          );

          entryPoints.forEach((entryPoint, index) => {
            if (!entryPoint || typeof entryPoint.x !== 'number' || typeof entryPoint.y !== 'number') {
              return;
            }

            const pointVisible = entryPoint.visible ?? resolvedStyle.visible;
            if (pointVisible === false) {
              return;
            }

            const radius = entryPoint.radius ?? resolvedStyle.radius;
            // Use a larger display radius for stair markers so they match panorama marker sizing
            const displayRadius = isStairRoom ? Math.max(radius, 12) : radius;
            // Change stair marker background to match stair color (#971812) while leaving camera markers unchanged
            const baseColor = entryPoint.color || markerStyle.color || (isStairRoom ? '#971812' : resolvedStyle.color);
            // For stair rooms use orange hover to match stair color; fall back to configured hover otherwise
            const hoverColor = entryPoint.hoverColor || markerStyle.hoverColor || (isStairRoom ? '#FF6B35' : resolvedStyle.hoverColor || '#1A5DBA');
            const activeColor = entryPoint.activeColor || markerStyle.activeColor || resolvedStyle.activeColor;
            const strokeColor = entryPoint.strokeColor || (isStairRoom ? '#ffffff' : resolvedStyle.strokeColor);
            const strokeWidth = entryPoint.strokeWidth ?? (isStairRoom ? 1.5 : resolvedStyle.strokeWidth);

            if (isStairRoom) {
              // Create stair marker group with icon (similar to camera markers)
              const marker = document.createElementNS(svgNS, 'g');
              marker.id = `entry-${roomId}-${index}`;
              marker.classList.add('stair-marker', 'entry-point', 'door-point');
              marker.setAttribute('transform', `translate(${entryPoint.x}, ${entryPoint.y})`);

              // Background circle
              const bgCircle = document.createElementNS(svgNS, 'circle');
              bgCircle.setAttribute('class', 'stair-bg');
              bgCircle.setAttribute('cx', 0);
              bgCircle.setAttribute('cy', 0);
              // Use displayRadius so stairs match camera marker size (camera uses r=12)
              bgCircle.setAttribute('r', displayRadius);
              bgCircle.setAttribute('fill', baseColor);
              bgCircle.setAttribute('stroke', strokeColor);
              // Use a slightly thicker stroke like camera markers
              bgCircle.setAttribute('stroke-width', isStairRoom ? 2 : strokeWidth);
              bgCircle.setAttribute('vector-effect', 'non-scaling-stroke');

              // Stair icon
              const icon = document.createElementNS(svgNS, 'path');
              icon.setAttribute('class', 'stair-icon');
              icon.setAttribute('d', STAIR_ICON_PATH);
              icon.setAttribute('fill', '#ffffff');
              // Scale and center the icon (original viewBox ~0 0 512 512)
              // Choose a scale that produces an icon similar in pixel-size to the camera icon
              const ICON_SCALE = 0.035; // ~13px icon from 512 viewBox when camera bg r=12
              const ICON_TRANSLATE = -256 * ICON_SCALE; // center the 512-viewBox icon
              icon.setAttribute('transform', `translate(${ICON_TRANSLATE}, ${ICON_TRANSLATE}) scale(${ICON_SCALE})`);
              icon.setAttribute('vector-effect', 'non-scaling-stroke');
              icon.style.pointerEvents = 'none';

              marker.appendChild(bgCircle);
              marker.appendChild(icon);

              // Hover effects
              // Change fill color on hover to the configured hoverColor and do not modify opacity or radius
              marker.addEventListener('mouseenter', () => {
                if (!marker.classList.contains('active')) {
                  bgCircle.setAttribute('fill', hoverColor);
                  // Ensure full opacity when hovering
                  bgCircle.setAttribute('opacity', '1');
                }
              });

              marker.addEventListener('mouseleave', () => {
                if (!marker.classList.contains('active')) {
                  bgCircle.setAttribute('fill', baseColor);
                  bgCircle.setAttribute('opacity', '1');
                }
              });

              entryGroup.appendChild(marker);
            } else {
              // Regular entry point (non-stair) - keep as circle
              const circle = document.createElementNS(svgNS, 'circle');
              circle.id = `entry-${roomId}-${index}`;
              circle.setAttribute('cx', entryPoint.x);
              circle.setAttribute('cy', entryPoint.y);
              circle.setAttribute('r', radius);
              circle.setAttribute('fill', baseColor);
              circle.setAttribute('stroke', strokeColor);
              circle.setAttribute('stroke-width', strokeWidth);
              circle.setAttribute('vector-effect', 'non-scaling-stroke');

              circle.classList.add('entry-point', 'door-point');

              entryGroup.appendChild(circle);
            }
          });

          console.log(`Entry points for room ${roomId} drawn successfully`);
        });
      }

      window.clearPath = function() {
        console.log('Clearing paths');
        
        // Clear path highlights from rooms
        document.querySelectorAll('.path-highlight').forEach(el => {
          el.classList.remove('path-highlight');
        });
        document.querySelectorAll('.you-are-here').forEach(el => {
          el.classList.remove('you-are-here');
        });

        // Clear path lines and labels from the entire SVG
        const svg = document.querySelector('svg');
        if (svg) {
          const pathLines = svg.querySelectorAll('.path-line');
          pathLines.forEach(line => line.remove());
          
          // Clear path step labels
          const pathLabels = svg.querySelectorAll('.path-step-label');
          pathLabels.forEach(label => label.remove());
          
          // Clear "you are here" labels
          const youAreHereLabels = svg.querySelectorAll('.you-are-here-label');
          youAreHereLabels.forEach(label => label.remove());
        }
      };

      window.highlightPath = function(path) {
        console.log('Highlighting path:', path);
        
        if (!path || path.length === 0) {
          console.log('No path to highlight');
          return;
        }

        // Clear existing highlights
        window.clearPath();

        // Highlight each room in the path
        path.forEach((roomId, index) => {
          const roomElement = document.getElementById(roomId);
          if (roomElement) {
            roomElement.classList.add('path-highlight');
            
            // Add step numbers to the path
            setTimeout(() => {
              const bbox = roomElement.getBBox();
              const label = document.createElementNS("http://www.w3.org/2000/svg", "text");
              label.setAttribute("class", "path-step-label");
              label.setAttribute("x", bbox.x + bbox.width / 2);
              label.setAttribute("y", bbox.y + bbox.height / 2);
              label.setAttribute("text-anchor", "middle");
              label.setAttribute("dominant-baseline", "middle");
              label.setAttribute("fill", "#ffffff");
              label.setAttribute("font-weight", "bold");
              label.setAttribute("font-size", "16");
              label.setAttribute("stroke", "#000");
              label.setAttribute("stroke-width", "1");
              label.setAttribute("vector-effect", "non-scaling-stroke");
              label.textContent = index + 1;
              
              const svg = document.querySelector('svg');
              if (svg) {
                // Add to the viewport group so it transforms with pan/zoom
                const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g') || svg;
                mainGroup.appendChild(label);
              }
            }, 100);
          }
        });

        // Draw connecting lines between path points
        if (window.floorGraph && window.floorGraph.rooms) {
          const svg = document.querySelector('svg');
          if (svg) {
            for (let i = 0; i < path.length - 1; i++) {
              const currentRoom = window.floorGraph.rooms[path[i]];
              const nextRoom = window.floorGraph.rooms[path[i + 1]];
              
              if (currentRoom && nextRoom) {
                    const currentEntryPoints = Array.isArray(currentRoom.entryPoints) && currentRoom.entryPoints.length
                      ? currentRoom.entryPoints
                      : Array.isArray(currentRoom.doorPoints) ? currentRoom.doorPoints : [];
                    const nextEntryPoints = Array.isArray(nextRoom.entryPoints) && nextRoom.entryPoints.length
                      ? nextRoom.entryPoints
                      : Array.isArray(nextRoom.doorPoints) ? nextRoom.doorPoints : [];

                    const start = currentEntryPoints[0];
                    const end = nextEntryPoints[0];

                    if (!start || !end) {
                      continue;
                    }
                
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', start.x);
                line.setAttribute('y1', start.y);
                line.setAttribute('x2', end.x);
                line.setAttribute('y2', end.y);
                line.setAttribute('stroke', '#ff4444');
                line.setAttribute('stroke-width', '4');
                line.setAttribute('stroke-dasharray', '10,5');
                line.setAttribute('vector-effect', 'non-scaling-stroke');
                line.classList.add('path-line');
                
                // Add to the viewport group so it transforms with pan/zoom
                const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g') || svg;
                mainGroup.appendChild(line);
              }
            }
          }
        }
      };

      // "YOU ARE HERE" functionality
      window.showYouAreHere = function(officeLocation) {
        console.log('Showing YOU ARE HERE for:', officeLocation);
        
        // Clear existing highlights
        document.querySelectorAll('.you-are-here').forEach(el => {
          el.classList.remove('you-are-here');
        });
        
        // Find and highlight the current office
        const roomElement = document.getElementById(officeLocation);
        if (roomElement) {
          roomElement.classList.add('you-are-here');
          
          // Add "YOU ARE HERE" label to the proper viewport group
          const svg = document.querySelector('svg');
          if (svg) {
            // Find the main group that gets transformed during pan/zoom
            const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g') || svg;
            
            // Remove existing "you are here" labels from the entire SVG
            svg.querySelectorAll('.you-are-here-label').forEach(label => label.remove());
            
            // Create new label
            const bbox = roomElement.getBBox();
            const label = document.createElementNS("http://www.w3.org/2000/svg", "text");
            label.setAttribute("class", "you-are-here-label");
            label.setAttribute("x", bbox.x + bbox.width / 2);
            label.setAttribute("y", bbox.y - 10);
            label.setAttribute("text-anchor", "middle");
            label.setAttribute("fill", "#ff4444");
            label.setAttribute("font-weight", "bold");
            label.setAttribute("font-size", "14");
            label.setAttribute("vector-effect", "non-scaling-stroke");
            label.textContent = "YOU ARE HERE";
            
            // Add the label to the same group as the rooms so it transforms with them
            mainGroup.appendChild(label);
            
            console.log('YOU ARE HERE label added to viewport group:', mainGroup);
          }
        }
      };

      function labelBelongsToRoom(tspanEl, roomNumber) {
        if (!tspanEl) return false;
        const parentText = tspanEl.closest('text');
        if (!parentText || !parentText.id) return false;
        const parentId = parentText.id.trim();
        if (!parentId.startsWith('text-')) return false;
        if (parentId === `text-${roomNumber}`) return true;
        return parentId.startsWith(`text-${roomNumber}-`);
      }

      function findLabelTspanForRoom(roomNumber, scopeElement) {
        const scope = scopeElement || document;

        const directMatch = scope.querySelector(`#roomlabel-${roomNumber}`);
        if (labelBelongsToRoom(directMatch, roomNumber)) {
          return directMatch;
        }

        const textMatches = scope.querySelectorAll(`text[id^="text-${roomNumber}"]`);
        for (const textNode of textMatches) {
          const candidate = textNode.querySelector('tspan');
          if (candidate) {
            return candidate;
          }
        }

        const allLabels = scope.querySelectorAll('tspan[id^="roomlabel-"]');
        for (const candidate of allLabels) {
          if (labelBelongsToRoom(candidate, roomNumber)) {
            return candidate;
          }
        }

        return null;
      }

      // Function to update room label with exact room matching
      function updateRoomLabel(targetElement, officeName) {
        if (!targetElement) return;

        let group = null;
        let roomElement = null;

        const tagName = targetElement.tagName ? targetElement.tagName.toLowerCase() : '';

        if (tagName === 'g') {
          group = targetElement;
          roomElement = targetElement.querySelector('path, rect');
        } else if (targetElement.matches && targetElement.matches('path, rect')) {
          roomElement = targetElement;
          group = targetElement.closest('g') || targetElement.parentNode;
        } else if (targetElement.closest) {
          group = targetElement.closest('g');
          if (group) {
            roomElement = group.querySelector('path, rect');
          }
        }

        if (!group) {
          console.warn('updateRoomLabel: No parent group found for target element', targetElement);
          return;
        }

        if (!roomElement || !roomElement.id) {
          roomElement = group.querySelector('path, rect');
        }

        if (!roomElement || !roomElement.id) return;

        const roomMatch = roomElement.id.match(/room-(\d+)(?:-(\d+))?/);
        if (!roomMatch) return;

        const roomNumber = roomMatch[1];
        const floorNumber = roomMatch[2] || '';
        const svgRoot = roomElement.ownerSVGElement || group.ownerSVGElement || document.querySelector('svg');

        group.dataset.room = 'true';
        group.dataset.roomNumber = roomNumber;
        if (floorNumber) {
          group.dataset.floorNumber = floorNumber;
        } else {
          delete group.dataset.floorNumber;
        }

        if (roomElement.dataset) {
          roomElement.dataset.room = 'true';
          roomElement.dataset.roomNumber = roomNumber;
          if (floorNumber) {
            roomElement.dataset.floorNumber = floorNumber;
          } else {
            delete roomElement.dataset.floorNumber;
          }
        }

        let labelId = `roomlabel-${roomNumber}`;
        let textEl = group.querySelector('text');
        let originalX;
        let originalY;

        let tspanEl = findLabelTspanForRoom(roomNumber, svgRoot);

        if (!tspanEl && textEl) {
          const existingTspan = textEl.querySelector('tspan');
          if (existingTspan) {
            tspanEl = existingTspan;
          }
        }

        if (!tspanEl) {
          const groupTspan = group.querySelector('text tspan');
          if (groupTspan) {
            tspanEl = groupTspan;
          }
        }

        if (tspanEl && tspanEl.tagName === 'tspan') {
          labelId = tspanEl.id || labelId;
          const parentText = tspanEl.closest('text');
          if (parentText) {
            textEl = parentText;
            const referenceTspan = parentText.querySelector('tspan') || tspanEl;
            originalX = parseFloat(referenceTspan.getAttribute('x')) || parseFloat(parentText.getAttribute('x'));
            originalY = parseFloat(referenceTspan.getAttribute('y')) || parseFloat(parentText.getAttribute('y'));
          } else {
            textEl = tspanEl.parentElement;
            originalX = parseFloat(tspanEl.getAttribute('x'));
            originalY = parseFloat(tspanEl.getAttribute('y'));
          }
        } else if (textEl) {
          originalX = parseFloat(textEl.getAttribute('x'));
          originalY = parseFloat(textEl.getAttribute('y'));
        }

        if (!textEl) {
          if (svgRoot) {
            const dup = svgRoot.querySelector(`#${labelId}`);
            if (dup) {
              dup.remove();
            }
          }

          const bbox = roomElement.getBBox();
          originalX = originalX || bbox.x + bbox.width / 2;
          originalY = originalY || bbox.y + bbox.height / 2;

          textEl = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          textEl.setAttribute('class', 'room-label');
          textEl.setAttribute('id', labelId);
          textEl.setAttribute('x', originalX);
          textEl.setAttribute('y', originalY);

          group.appendChild(textEl);
        }

        textEl.setAttribute('text-anchor', 'middle');
        textEl.setAttribute('dominant-baseline', 'central');
        textEl.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
        textEl.style.fontWeight = '600';
        textEl.style.fontSize = '14px';
        textEl.style.fill = '#1a1a1a';
        textEl.style.stroke = '#ffffff';
        textEl.style.strokeWidth = '3px';
        textEl.style.strokeLinejoin = 'round';
        textEl.style.paintOrder = 'stroke fill';
        textEl.style.vectorEffect = 'non-scaling-stroke';

        if (textEl.classList) {
          textEl.classList.remove('room-label-small', 'room-label-large');
          if (officeName.length > 25) {
            textEl.classList.add('room-label-large');
          } else if (officeName.length > 15) {
            textEl.classList.add('room-label-small');
          }
        }

        textEl.textContent = '';
        while (textEl.firstChild) {
          textEl.removeChild(textEl.firstChild);
        }

        const lineHeight = '1.2em';
        const words = officeName.split(' ');

        words.forEach((word, index) => {
          const newTspan = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
          newTspan.textContent = word;
          newTspan.setAttribute('x', originalX);
          newTspan.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
          newTspan.style.fontWeight = '600';
          newTspan.style.fontSize = '14px';

          if (index === 0) {
            newTspan.setAttribute('id', labelId);
          }

          if (index > 0) {
            newTspan.setAttribute('dy', lineHeight);
          }
          textEl.appendChild(newTspan);
        });

        textEl.setAttribute('x', originalX);
        textEl.setAttribute('y', originalY);
      }
      
      // Floor map configuration
      const floorMaps = {
        1: '../SVG/Capitol_1st_floor_layout_20_modified.svg',
        2: '../SVG/Capitol_2nd_floor_layout_6_modified.svg',
        3: '../SVG/Capitol_3rd_floor_layout_6.svg'
      };

      // Floor graph configuration for pathfinding
      const floorGraphs = {
        1: '../floor_graph.json',
        2: '../floor_graph_2.json', 
        3: '../floor_graph_3.json'
      };

      const getFloorFromLocation = (location) => {
        if (!location || typeof location !== 'string') return null;
        const parts = location.split('-');
        const possibleFloor = parseInt(parts[parts.length - 1], 10);
        return Number.isNaN(possibleFloor) ? null : possibleFloor;
      };
      
      // Track current floor
      let currentFloor = 1;

      // Function to load SVG for a specific floor
      function loadFloorMap(floorNumber) {
        console.log(`Loading floor ${floorNumber} map...`);
        currentFloor = floorNumber; // Track the current floor
        
        // Load both SVG and floor graph for pathfinding
        return Promise.all([
          fetch(floorMaps[floorNumber]).then(response => {
            if (!response.ok) throw new Error(`SVG fetch failed: ${response.status}`);
            return response.text();
          }),
          (typeof window.ensureFloorGraphLoaded === 'function'
            ? window.ensureFloorGraphLoaded(floorNumber).catch(error => {
                console.warn(`Floor graph for floor ${floorNumber} not available:`, error.message);
                return null;
              })
            : fetch(floorGraphs[floorNumber]).then(response => {
                if (!response.ok) throw new Error(`Floor graph fetch failed: ${response.status}`);
                return response.json();
              }).catch(error => {
                console.warn(`Floor graph for floor ${floorNumber} not available:`, error.message);
                return null; // Return null instead of failing completely
              })
          )
        ])
        .then(([svgText, graphData]) => {
          // Load SVG
          document.getElementById('svg-container').innerHTML = svgText;
          const svg = document.querySelector('svg');
          
          // Ensure SVG has proper attributes matching floorPlan.php
          if (svg) {
            svg.setAttribute('id', 'svg1'); // Ensure consistent ID
            svg.setAttribute('width', '100%');
            svg.setAttribute('height', '100%');
            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
            
            // Ensure SVG has valid viewBox (matches floorPlan.php logic)
            if (!svg.getAttribute('viewBox')) {
              try {
                // Force getBBox calculation after adding to DOM
                const bbox = svg.getBBox();
                if (bbox && !isNaN(bbox.x) && !isNaN(bbox.y) && bbox.width > 0 && bbox.height > 0) {
                  svg.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
                  console.log(`Set SVG viewBox from getBBox: ${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
                } else {
                  // Use known Capitol building SVG dimensions as fallback
                  svg.setAttribute('viewBox', '0 0 1917.8289 629.6413');
                  console.log('Set SVG viewBox to Capitol building default dimensions');
                }
              } catch (e) {
                console.warn("Could not get SVG bbox, using Capitol building fallback viewBox");
                svg.setAttribute('viewBox', '0 0 1917.8289 629.6413');
              }
            } else {
              console.log('SVG viewBox already set:', svg.getAttribute('viewBox'));
            }
          }
          
          // Load floor graph for pathfinding
          if (graphData) {
            window.floorGraph = graphData;
            console.log(`Floor ${floorNumber} navigation graph loaded:`, graphData);
            console.log(`Available rooms in floor ${floorNumber}:`, Object.keys(graphData.rooms || {}));
            console.log('Sample room data:', Object.keys(graphData.rooms || {}).slice(0, 3).map(roomId => ({ 
              id: roomId, 
              data: graphData.rooms[roomId] 
            })));
          } else {
            window.floorGraph = null;
            console.log(`Floor ${floorNumber} loaded without navigation graph (pathfinding disabled)`);
          }
          
          if (window.svgPanZoomInstance) {
            window.svgPanZoomInstance.destroy();
          }
          
          // Remove any existing custom zoom controls when switching floors
          const existingCustomControls = document.getElementById('custom-zoom-controls');
          if (existingCustomControls) {
            existingCustomControls.remove();
          }
          
          // Store the graph data for later use when pan-zoom is ready
          window.pendingGraphData = graphData;
          window.currentFloorNumber = floorNumber;
          
          // Initialize SVG interactivity (adds room handlers)
          initializeSVG(svg);
          
          // Force consistent font styling after SVG load
          setTimeout(() => {
            enhanceSVGTextAccessibility();
            optimizeTextForZoom();
          }, 100);
          
          // Initialize pan-zoom, which will then trigger the panZoomReady event
          initializePanZoom(svg);

          // Drawing of paths and markers is now handled by the event listener for 'panZoomReady'
          // which is triggered inside initializePanZoom.

          console.log(`Floor ${floorNumber} map and navigation graph loaded successfully`);
        })
        .catch(error => {
          console.error(`Error loading floor ${floorNumber} data:`, error);
          document.getElementById('svg-container').innerHTML = `<p style="color:red;">Floor ${floorNumber} map not found.</p>`;
        });
      }

      // Enhanced function to switch to a specific floor with smooth transitions
      function switchToFloorSmooth(targetFloor, targetPathId, targetPointIndex) {
        console.log(`🏢 Smooth switching to floor ${targetFloor} for panorama navigation...`);
        
        // Set transition flag to prevent stability checks during transition
        window.isFloorTransitioning = true;
        console.log('🏢 Smooth floor transition flag set to TRUE');
        
        // Safety timeout - clear transition flag if it gets stuck (backup mechanism)
        setTimeout(() => {
          if (window.isFloorTransitioning) {
            console.warn('⚠️ Smooth floor transition flag stuck, forcing clear after 8 seconds');
            window.isFloorTransitioning = false;
          }
        }, 8000);
        
        // Store current state for restoration if needed
        const currentState = {
          floor: window.currentFloorNumber,
          zoom: window.svgPanZoomInstance ? window.svgPanZoomInstance.getZoom() : 1,
          pan: window.svgPanZoomInstance ? window.svgPanZoomInstance.getPan() : {x: 0, y: 0}
        };
        
        // Clean up existing pan-zoom instance to prevent matrix corruption
        if (window.svgPanZoomInstance) {
          try {
            window.svgPanZoomInstance.destroy();
          } catch (e) {
            console.warn("Error destroying pan-zoom instance:", e.message);
          }
          window.svgPanZoomInstance = null;
        }
        
        // Update the active floor button with smooth transition
        const floorButtons = document.querySelectorAll('.floor-btn');
        floorButtons.forEach(btn => {
          btn.style.transition = 'all 0.3s ease';
          btn.classList.remove('active');
          if (parseInt(btn.getAttribute('data-floor')) === targetFloor) {
            btn.classList.add('active');
            btn.style.transform = 'scale(1.1)';
            setTimeout(() => {
              btn.style.transform = 'scale(1)';
            }, 200);
            console.log(`✅ Updated floor button to floor ${targetFloor} with animation`);
          }
        });
        
        // Update current floor tracking
        window.currentFloorNumber = targetFloor;
        
        // Enhanced floor map loading with navigation context
        loadFloorMapForNavigation(targetFloor, targetPathId, targetPointIndex);
        
        console.log(`🎯 Smooth floor switch to ${targetFloor} initiated with target path ${targetPathId}, point ${targetPointIndex}`);
      }

      // Function to switch to a specific floor (legacy - called by floor buttons)
      function switchToFloor(targetFloor) {
        console.log(`🏢 Switching to floor ${targetFloor}...`);
        
        // Set transition flag to prevent stability checks during transition
        window.isFloorTransitioning = true;
        console.log('🏢 Floor transition flag set to TRUE');
        
        // Safety timeout - clear transition flag if it gets stuck (backup mechanism)
        setTimeout(() => {
          if (window.isFloorTransitioning) {
            console.warn('⚠️ Floor transition flag stuck, forcing clear after 10 seconds');
            window.isFloorTransitioning = false;
          }
        }, 10000);
        
        // Clean up existing pan-zoom instance to prevent matrix corruption
        if (window.svgPanZoomInstance) {
          try {
            window.svgPanZoomInstance.destroy();
          } catch (e) {
            console.warn("Error destroying pan-zoom instance:", e.message);
          }
          window.svgPanZoomInstance = null;
        }
        
        // Update the active floor button
        const floorButtons = document.querySelectorAll('.floor-btn');
        floorButtons.forEach(btn => {
          btn.classList.remove('active');
          if (parseInt(btn.getAttribute('data-floor')) === targetFloor) {
            btn.classList.add('active');
            console.log(`✅ Updated floor button to floor ${targetFloor}`);
          }
        });
        
        // Update current floor tracking
        window.currentFloorNumber = targetFloor;
        
        // Load the target floor map
        loadFloorMap(targetFloor);
        
        console.log(`🎯 Floor switch to ${targetFloor} initiated`);
      }

      // Navigation status functions
      function showNavigationStatus(message) {
        let statusDiv = document.getElementById('navigation-status');
        if (!statusDiv) {
          statusDiv = document.createElement('div');
          statusDiv.id = 'navigation-status';
          statusDiv.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(4, 170, 109, 0.95);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: bold;
            z-index: 10000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            font-size: 16px;
            text-align: center;
            min-width: 200px;
          `;
          document.body.appendChild(statusDiv);
        }
        statusDiv.textContent = message;
        statusDiv.style.display = 'block';
        console.log(`📢 Navigation status: ${message}`);
      }

      function hideNavigationStatus() {
        const statusDiv = document.getElementById('navigation-status');
        if (statusDiv) {
          statusDiv.style.display = 'none';
        }
      }

      // Function to initialize SVG interactivity
      function initializeSVG(svg) {
        if (!svg) return;
        console.log("Initializing SVG interactivity...");
        
        // Make rooms clickable and update labels (preserve original room IDs)
        svg.querySelectorAll('path[id^="room-"]').forEach(function(el) {
          el.classList.add('selectable-room', 'interactive-room');
          
          // Add click handler directly to the path - MOBILE ONLY (no pathfinding)
          el.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            console.log('Mobile room clicked (initializeSVG):', el.id);
            
            // Match by exact room ID (floor-specific)
            const office = officesData.find(o => o.location === el.id);
            if (office) {
              handleRoomClick(office);
            }
            
            // Explicitly prevent any pathfinding behavior
            return false;
          });

          const parentGroup = el.closest('g');
          
          if (parentGroup) {
            // Extract room number for group ID
            const roomMatch = el.id.match(/^room-(\d+)(?:-\d+)?$/);
            if (roomMatch) {
              const roomNum = roomMatch[1];
              // Ensure the parent group has the same ID format
              parentGroup.id = `group-${roomNum}`;
              
              // Find matching office by exact room ID (floor-specific)
              const office = officesData.find(o => o.location === el.id);

              if (office) {
                updateRoomLabel(parentGroup, office.name);
                
                // Update room appearance based on office status
                if (office.status && office.status.toLowerCase() === 'inactive') {
                  el.classList.add('room-inactive');
                  const textEl = parentGroup.querySelector('text');
                  if (textEl) {
                    textEl.classList.add('text-label-inactive');
                  }
                }

                console.log(`Room ${el.id} initialized with office:`, office.name);
                
                // Add hover effect
                el.addEventListener('mouseenter', () => {
                  el.style.opacity = '0.7';
                });
                el.addEventListener('mouseleave', () => {
                  el.style.opacity = '';
                });

                // Add touch effects for mobile
                el.addEventListener('touchstart', () => {
                  el.style.opacity = '0.7';
                });
                el.addEventListener('touchend', () => {
                  el.style.opacity = '';
                });
              }
            }
          }
        });

        // Initialize pan-zoom functionality
        if (typeof svgPanZoom === "function") {
          initializePanZoom(svg);
        }
      }

      // Function to initialize pan-zoom functionality
      function initializePanZoom(svg) {
        try {
          const isMobile = window.innerWidth <= 768;
          
          // Destroy any existing instance first
          if (window.svgPanZoomInstance) {
            try {
              window.svgPanZoomInstance.destroy();
            } catch (e) {
              console.warn("Error destroying previous instance:", e);
            }
            window.svgPanZoomInstance = null;
          }
          
          // Ensure SVG has valid viewBox before initializing (matches floorPlan.php)
          if (!svg.getAttribute('viewBox')) {
            // Use Capitol building SVG dimensions for proper initialization
            svg.setAttribute('viewBox', '0 0 1917.8289 629.6413');
            console.log('Set default viewBox for pan-zoom initialization');
          }
          
          const panZoomInstance = svgPanZoom(svg, {
            zoomEnabled: true,
            controlIconsEnabled: true,
            fit: true,
            center: true,
            minZoom: 0.5,
            maxZoom: 10,
            zoomScaleSensitivity: isMobile ? 0.3 : 0.5,
            dblClickZoomEnabled: false,
            preventMouseEventsDefault: true,
            mouseWheelZoomEnabled: true,
            panEnabled: true,
            touchEnabled: true,
            contain: false,
            // Simple pan constraints
            beforePan: function(oldPan, newPan) {
              // Basic validation only
              if (isNaN(newPan.x) || isNaN(newPan.y)) {
                return oldPan;
              }
              return newPan;
            },
            // Simple zoom constraints
            beforeZoom: function(oldZoom, newZoom) {
              return Math.max(0.5, Math.min(10, newZoom));
            },
            // Custom events handler for smooth mobile gestures
            customEventsHandler: isMobile ? {
              haltEventListeners: ['touchstart', 'touchend', 'touchmove', 'touchleave', 'touchcancel'],
              init: function(options) {
                const instance = options.instance;
                let initialScale = 1;
                let pannedX = 0;
                let pannedY = 0;

                // Init Hammer with better touch recognition for mobile
                const hammer = new Hammer(options.svgElement, {
                  touchAction: 'none',
                  inputClass: Hammer.SUPPORT_POINTER_EVENTS ? Hammer.PointerEventInput : Hammer.TouchInput,
                  recognizers: [
                    [Hammer.Pinch, { enable: true }],
                    [Hammer.Pan, { direction: Hammer.DIRECTION_ALL, threshold: 0 }]
                  ]
                });

                // Configure Hammer gestures for mobile
                hammer.get('pinch').set({ enable: true });
                hammer.get('pan').set({ 
                  direction: Hammer.DIRECTION_ALL,
                  threshold: 2 // Slightly higher threshold for mobile
                });

                // Handle pan with improved tracking
                hammer.on('panstart', function(ev) {
                  pannedX = 0;
                  pannedY = 0;
                  ev.srcEvent.stopPropagation();
                  ev.srcEvent.preventDefault();
                });

                hammer.on('panmove', function(ev) {
                  const deltaX = ev.deltaX - pannedX;
                  const deltaY = ev.deltaY - pannedY;
                  pannedX = ev.deltaX;
                  pannedY = ev.deltaY;
                  
                  // Apply the pan with smooth movement
                  requestAnimationFrame(() => {
                    instance.panBy({
                      x: deltaX / instance.getZoom(),
                      y: deltaY / instance.getZoom()
                    });
                  });
                });

                // Handle pinch with improved scaling - THIS IS THE KEY FOR SMOOTH ZOOM
                hammer.on('pinchstart', function(ev) {
                  initialScale = instance.getZoom();
                  ev.srcEvent.stopPropagation();
                  ev.srcEvent.preventDefault();
                });

                hammer.on('pinch', function(ev) {
                  ev.srcEvent.stopPropagation();
                  ev.srcEvent.preventDefault();

                  // Apply smooth scaling with requestAnimationFrame for real-time updates
                  requestAnimationFrame(() => {
                    const newScale = initialScale * ev.scale;

                    // Convert screen/client coordinates (ev.center) to SVG coordinates
                    // so svg-pan-zoom zoomAtPoint receives the correct anchor and
                    // does not translate the viewport unexpectedly.
                    let svgPoint;
                    try {
                      const svgEl = options.svgElement;
                      // Create an SVGPoint and transform by the inverse screen CTM
                      const pt = svgEl.createSVGPoint();
                      pt.x = ev.center.x;
                      pt.y = ev.center.y;
                      const ctm = svgEl.getScreenCTM();
                      if (ctm && typeof ctm.inverse === 'function') {
                        svgPoint = pt.matrixTransform(ctm.inverse());
                      } else if (ctm) {
                        // Older browsers: compute inverse manually
                        const inv = ctm.inverse();
                        svgPoint = pt.matrixTransform(inv);
                      } else {
                        // Fallback to using bounding rect offsets
                        const rect = svgEl.getBoundingClientRect();
                        svgPoint = { x: ev.center.x - rect.left, y: ev.center.y - rect.top };
                      }
                    } catch (err) {
                      // Fallback: use bounding rect offsets if anything goes wrong
                      const rect = options.svgElement.getBoundingClientRect();
                      svgPoint = { x: ev.center.x - rect.left, y: ev.center.y - rect.top };
                    }

                    instance.zoomAtPoint(newScale, svgPoint);
                  });
                });

                // Clean up function
                this.destroy = function() {
                  hammer.destroy();
                };
              }
            } : undefined
          });

          console.log("svg-pan-zoom initialized successfully");
          console.log("Control icons enabled:", true);
          console.log("Pan-zoom instance created:", panZoomInstance);
          window.svgPanZoomInstance = panZoomInstance;
          window.panZoom = panZoomInstance;
          
          // Debug: Check SVG structure after pan-zoom initialization
          console.log("SVG structure after pan-zoom init:");
          console.log("SVG children:", svg.children);
          const viewport = svg.querySelector('.svg-pan-zoom_viewport');
          console.log("Viewport group found:", viewport);
          if (viewport) {
            console.log("Viewport transform:", viewport.getAttribute('transform'));
            console.log("Viewport children:", viewport.children);
          }

          // Check if controls were actually created
          setTimeout(() => {
            const controls = document.querySelector('.svg-pan-zoom-control');
            console.log("Zoom controls found:", controls);
            if (controls) {
              console.log("Controls position:", controls.style.position, controls.style.right, controls.style.top);
              console.log("Controls z-index:", controls.style.zIndex);
            } else {
              console.warn("Zoom controls not found in DOM!");
            }
          }, 100);

          // Force immediate resize and fit to match floorPlan.php behavior
          // Use requestAnimationFrame to ensure DOM is fully rendered before positioning
          requestAnimationFrame(() => {
            // Ensure proper sizing first
            panZoomInstance.resize();
            
            // Wait for next frame to ensure resize has taken effect
            requestAnimationFrame(() => {
              // Reset to show entire SVG with proper fit and center
              panZoomInstance.reset();
              panZoomInstance.fit();
              panZoomInstance.center();
              
              console.log("SVG positioned and fitted to container");
              
              // Always create custom zoom controls for better visibility and control
              setTimeout(() => {
                // First try to find built-in controls
                const controls = document.querySelector('.svg-pan-zoom-control');
                if (controls) {
                  controls.style.display = 'block';
                  controls.style.visibility = 'visible';
                  controls.style.opacity = '1';
                  controls.style.zIndex = '999';
                  console.log("Built-in zoom controls found and made visible");
                }
                
                // Always create custom zoom controls as backup/primary controls
                console.log("Creating custom zoom controls for better reliability");
                createCustomZoomControls(panZoomInstance);
                
                // Emit event that pan-zoom is fully ready
                console.log("Pan-zoom initialization complete, dispatching ready event");
                
                // Add a small delay to ensure the viewport is rendered, then dispatch the ready event
                setTimeout(() => {
                  document.dispatchEvent(new CustomEvent('panZoomReady'));
                  console.log('panZoomReady event dispatched.');
                  
                  // Clear floor transition flag - it's now safe for stability checks
                  window.isFloorTransitioning = false;
                  console.log("🎯 Floor transition completed, stability checks re-enabled");
                }, 50); // A minimal delay is still helpful
              }, 100); // Shorter delay for better performance
            });
          });

          // Remove any existing resize listener
          if (window.panZoomResizeHandler) {
            window.removeEventListener("resize", window.panZoomResizeHandler);
          }

          // Create resize handler that matches floorPlan.php behavior
          window.panZoomResizeHandler = () => {
            if (window.svgPanZoomInstance && typeof window.svgPanZoomInstance.resize === 'function') {
              try {
                // Use requestAnimationFrame for smooth resize handling
                requestAnimationFrame(() => {
                  if (window.svgPanZoomInstance && typeof window.svgPanZoomInstance.resize === 'function') {
                    window.svgPanZoomInstance.resize();
                    // Add small delay before fit and center to ensure resize has taken effect
                    setTimeout(() => {
                      if (window.svgPanZoomInstance && typeof window.svgPanZoomInstance.fit === 'function') {
                        window.svgPanZoomInstance.fit();
                        window.svgPanZoomInstance.center();
                      }
                    }, 10);
                  }
                });
              } catch (e) {
                console.warn("Failed to resize SVG pan-zoom:", e);
              }
            }
          };

          // Add the new resize listener
          window.addEventListener("resize", window.panZoomResizeHandler);
          
          // Add orientation change handler for mobile
          window.addEventListener("orientationchange", () => {
            setTimeout(() => {
              if (window.svgPanZoomInstance) {
                refreshSvgContainer();
              }
            }, 100);
          });
          
          // Add mobile-specific touch optimization
          if (window.innerWidth <= 768) {
            console.log("Mobile device detected - SVG pinch-to-zoom enabled via Hammer.js");
          }
          
          // Store stability check interval ID for cleanup
          if (window.svgStabilityCheckInterval) {
            clearInterval(window.svgStabilityCheckInterval);
          }
          
          // Add stability check with proper error handling and cleanup (delayed start)
          setTimeout(() => {
            window.svgStabilityCheckInterval = setInterval(() => {
              // Skip check during floor transitions or initial loading
              if (window.isFloorTransitioning || !document.readyState === 'complete') {
                return;
              }
              
              if (window.svgPanZoomInstance) {
              try {
                const currentZoom = window.svgPanZoomInstance.getZoom();
                const currentPan = window.svgPanZoomInstance.getPan();
                
                // Only fix if values are clearly invalid and instance is stable
                if (isNaN(currentZoom) || currentZoom <= 0 || currentZoom > 50) {
                  console.warn("Invalid zoom detected, attempting gentle fix...");
                  try {
                    window.svgPanZoomInstance.fit();
                    window.svgPanZoomInstance.center();
                  } catch (e) {
                    console.warn("Could not fix zoom, will recreate on next floor load:", e.message);
                  }
                }
                
                if (isNaN(currentPan.x) || isNaN(currentPan.y)) {
                  console.warn("Invalid pan detected, centering...");
                  try {
                    window.svgPanZoomInstance.center();
                  } catch (e) {
                    console.warn("Could not fix pan, will recreate on next floor load:", e.message);
                  }
                }
              } catch (error) {
                // Instance might be corrupted, clear it for recreation
                console.warn("SVG pan-zoom instance corrupted, clearing for recreation:", error.message);
                window.svgPanZoomInstance = null;
              }
            }
          }, 3000); // Check every 3 seconds, less frequent
          }, 2000); // Delay starting stability checks by 2 seconds
        } catch (e) {
          console.error("Error initializing svg-pan-zoom:", e);
        }
      }

      // Function to create custom zoom controls if svg-pan-zoom controls don't appear
      function createCustomZoomControls(panZoomInstance) {
        // Remove any existing custom controls
        const existingControls = document.getElementById('custom-zoom-controls');
        if (existingControls) {
          existingControls.remove();
        }

        // Create control container
        const controlsContainer = document.createElement('div');
        controlsContainer.id = 'custom-zoom-controls';
        controlsContainer.style.cssText = `
          position: fixed !important;
          bottom: 120px !important;
          right: 20px !important;
          z-index: 999 !important;
          display: flex !important;
          flex-direction: column !important;
          gap: 8px !important;
          background: rgba(255, 255, 255, 0.95) !important;
          padding: 8px !important;
          border-radius: 12px !important;
          box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
          pointer-events: auto !important;
          border: 1px solid rgba(0,0,0,0.1) !important;
        `;

        // Helper function to create buttons with unified event handling
        function createZoomButton(text, action, actionName) {
          const button = document.createElement('button');
          button.innerHTML = text;
          button.style.cssText = `
            width: 48px !important;
            height: 48px !important;
            border: none !important;
            background: #ffffff !important;
            color: #333333 !important;
            font-size: 24px !important;
            font-weight: bold !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15) !important;
            touch-action: manipulation !important;
            user-select: none !important;
            border: 1px solid rgba(0,0,0,0.1) !important;
          `;

          // Unified action handler
          const executeAction = (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log(actionName + " triggered");
            try {
              action();
            } catch (err) {
              console.warn(actionName + " failed:", err);
            }
          };

          // For mobile devices, use touchend instead of click for better responsiveness
          const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                           (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) ||
                           window.innerWidth <= 768;

          if (isMobile) {
            let touchStarted = false;
            
            button.addEventListener('touchstart', (e) => {
              e.preventDefault();
              e.stopPropagation();
              touchStarted = true;
              button.style.background = '#f0f0f0 !important';
              button.style.transform = 'scale(0.95) !important';
            });
            
            button.addEventListener('touchend', (e) => {
              e.preventDefault();
              e.stopPropagation();
              if (touchStarted) {
                executeAction(e);
                touchStarted = false;
              }
              button.style.background = '#ffffff !important';
              button.style.transform = 'scale(1) !important';
            });
            
            button.addEventListener('touchcancel', (e) => {
              touchStarted = false;
              button.style.background = '#ffffff !important';
              button.style.transform = 'scale(1) !important';
            });
          } else {
            // Desktop click handling
            button.addEventListener('click', executeAction);
            
            // Hover effects for desktop
            button.addEventListener('mouseenter', () => {
              button.style.background = '#f0f0f0 !important';
              button.style.transform = 'scale(1.05) !important';
              button.style.boxShadow = '0 4px 12px rgba(0,0,0,0.25) !important';
            });
            button.addEventListener('mouseleave', () => {
              button.style.background = '#ffffff !important';
              button.style.transform = 'scale(1) !important';
              button.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15) !important';
            });
            button.addEventListener('mousedown', () => {
              button.style.transform = 'scale(0.95) !important';
            });
            button.addEventListener('mouseup', () => {
              button.style.transform = 'scale(1) !important';
            });
          }

          return button;
        }

        // Create zoom in button
        const zoomInBtn = createZoomButton('+', () => panZoomInstance.zoomIn(), 'Zoom in');
        
        // Create zoom out button
        const zoomOutBtn = createZoomButton('−', () => panZoomInstance.zoomOut(), 'Zoom out');
        
        // Create reset button
        const resetBtn = createZoomButton('⌘', () => {
          // Reset to original view with proper fit and center
          panZoomInstance.reset();
          setTimeout(() => {
            panZoomInstance.fit();
            panZoomInstance.center();
          }, 10); // Small delay to ensure reset takes effect
        }, 'Reset');

        // Add buttons to container
        controlsContainer.appendChild(zoomInBtn);
        controlsContainer.appendChild(zoomOutBtn);
        controlsContainer.appendChild(resetBtn);

        // Add to document body for better positioning
        document.body.appendChild(controlsContainer);
        console.log("Custom zoom controls created and added to document body");
      }

      // Initialize floor buttons on document load
      document.addEventListener("DOMContentLoaded", function() {
        // Initialize text accessibility enhancements
        setTimeout(() => {
          enhanceSVGTextAccessibility();
          optimizeTextForZoom();
        }, 1000);
        
        // Listen for zoom changes to optimize text
        setTimeout(() => {
          if (window.svgPanZoomInstance && typeof window.svgPanZoomInstance.setOnZoom === 'function') {
            const originalOnZoom = window.svgPanZoomInstance.options.onZoom || (() => {});
            window.svgPanZoomInstance.setOnZoom(() => {
              originalOnZoom();
              optimizeTextForZoom();
            });
          }
        }, 1500);
        
        const floorButtons = document.querySelectorAll('.floor-btn');
        
        floorButtons.forEach(button => {
          button.addEventListener('click', function() {
            const floor = parseInt(this.getAttribute('data-floor'));
            // Update active state of buttons
            floorButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            // Load the selected floor map
            loadFloorMap(floor);
          });
        });

        // Add the event listener for drawing ONCE on DOM load.
        // It will be triggered every time panZoomReady is dispatched.
        document.addEventListener('panZoomReady', drawPathsAndDoorsWhenReady);

        // Determine initial floor to load - check for scanned panorama first
        let initialFloor = 1; // Default to floor 1
        if (scannedPanoramaFromPHP && scannedPanoramaFromPHP.floor) {
          initialFloor = scannedPanoramaFromPHP.floor;
          console.log(`🎯 Scanned panorama detected for floor ${initialFloor}, loading that floor first`);
          
          // Update the active floor button to match the scanned panorama floor
          setTimeout(() => {
            const floorButtons = document.querySelectorAll('.floor-btn');
            floorButtons.forEach(btn => {
              btn.classList.remove('active');
              if (parseInt(btn.getAttribute('data-floor')) === initialFloor) {
                btn.classList.add('active');
              }
            });
          }, 100);
        }
        
        // Load the determined initial floor
        loadFloorMap(initialFloor);
        
        // Verify zoom controls are created after a delay
        setTimeout(() => {
          const customControls = document.getElementById('custom-zoom-controls');
          const svgControls = document.querySelector('.svg-pan-zoom-control');
          console.log("=== ZOOM CONTROLS STATUS ===");
          console.log("Custom controls found:", !!customControls);
          console.log("SVG-pan-zoom controls found:", !!svgControls);
          if (customControls) {
            console.log("Custom controls display:", customControls.style.display);
            console.log("Custom controls visibility:", customControls.style.visibility);
            console.log("Custom controls z-index:", customControls.style.zIndex);
            console.log("Custom controls position:", customControls.getBoundingClientRect());
          }
          console.log("=== END STATUS ===");
        }, 1000);
        
        // Mobile-specific initialization
        if (window.innerWidth <= 768) {
          // Force mobile layout after DOM load
          setTimeout(() => {
            const svgContainer = document.getElementById('svg-container');
            const svg = document.querySelector('svg');
            
            if (svgContainer) {
              svgContainer.style.width = '100vw';
              svgContainer.style.height = `${window.innerHeight - 120}px`;
            }
            
            if (svg) {
              svg.style.width = '100vw';
              svg.style.height = `${window.innerHeight - 120}px`;
            }
            
            // Trigger resize if pan-zoom is available
            if (window.svgPanZoomInstance) {
              refreshSvgContainer();
            }
          }, 200);
        }

        // ===== GEOFENCING ENFORCEMENT =====
        // This will block the UI until the user's location is verified as inside an allowed zone.
        (function setupGeofenceEnforcement(){
          // Create overlay element
          const overlay = document.createElement('div');
          overlay.id = 'geofence-overlay';
          overlay.style.cssText = `position:fixed;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;z-index:99999;background:rgba(0,0,0,0.85);color:#fff;padding:20px;`;
          overlay.innerHTML = `
            <div style="max-width:420px;text-align:center;">
              <div style="font-size:28px;margin-bottom:8px;">📍 Verifying your location...</div>
              <div id="geofence-msg" style="margin-bottom:12px;opacity:0.95">Please allow location access so we can confirm you're inside the permitted area.</div>
              <div id="geofence-actions" style="margin-top:8px;"></div>
            </div>`;

          // Add overlay to DOM but hidden initially
          document.body.appendChild(overlay);

          function showMessage(msg, html=false){
            const el = document.getElementById('geofence-msg');
            if (!el) return;
            el[html? 'innerHTML' : 'textContent'] = msg;
          }

          function allowAccess(){
            overlay.style.transition = 'opacity 0.25s';
            overlay.style.opacity = '0';
            setTimeout(()=> overlay.remove(), 300);
          }

          function denyAccess(msg){
            showMessage(msg + '\n\nYou will not be able to use this app outside the allowed area.');
            const actions = document.getElementById('geofence-actions');
            actions.innerHTML = `<button id="geofence-retry" style="padding:10px 16px;margin-right:8px;border-radius:8px;background:#fff;color:#111;border:none;">Retry</button><button id="geofence-info" style="padding:10px 16px;border-radius:8px;background:#667eea;color:#fff;border:none;">Contact Admin</button>`;
            document.getElementById('geofence-retry').addEventListener('click', ()=>{ startCheck(); });
            document.getElementById('geofence-info').addEventListener('click', ()=>{ window.location.href = '/FinalDev/geofence_admin_dashboard.php'; });
          }

          async function startCheck(){
            showMessage('Requesting location from device...');

            if (!navigator.geolocation){
              denyAccess('Geolocation is not supported by your browser.');
              return;
            }

            navigator.geolocation.getCurrentPosition(async (pos) => {
              const lat = pos.coords.latitude;
              const lng = pos.coords.longitude;
              showMessage('Checking location against allowed area...');

              try{
                const resp = await fetch('verify_location.php', {
                  method: 'POST',
                  headers: {'Content-Type':'application/json'},
                  body: JSON.stringify({ lat, lng, office_id: highlightOfficeIdFromPHP || null })
                });
                const data = await resp.json();
                if (data && data.success && data.result){
                  const r = data.result;
                  // Permit only if inside zone1 (strict) or zone2 if you prefer
                  if (r.inside_zone1 || r.inside_zone2){
                    showMessage('Location verified — access granted.');
                    setTimeout(allowAccess, 600);
                  } else {
                    denyAccess('Access denied — your device appears to be outside the allowed geofence.');
                  }
                } else {
                  denyAccess('Could not verify location (server error).');
                }
              }catch(err){
                console.error('Geofence verification failed', err);
                denyAccess('Network error while verifying location.');
              }

            }, (err) => {
              console.warn('Geolocation error', err);
              denyAccess('Unable to read your location: ' + (err.message || 'Permission denied'));
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
          }

          // Begin check immediately on DOM load
          startCheck();
        })();

        // Get references to elements
        const detailsDrawer = document.getElementById("details-drawer");
        const drawerHandle = document.getElementById("drawer-handle");
        const mainContent = document.querySelector("main.content"); // Get the main content element

        // --- Basic Checks ---
        if (!detailsDrawer || !drawerHandle || !mainContent) {
          console.error("Drawer, handle, or main content element not found!");
          return; // Stop if essential elements are missing
        }
        // --- End Basic Checks ---

        // Drawer state tracking
        let isDragging = false;
        let startY = 0;
        let startTranslate = 0;

        // Calculate initial drawer state and dimensions
        const drawerHeight = detailsDrawer.offsetHeight;
        const minTranslate = 0; // Fully open state (translateY = 0)
        const handleHeight = 40; // Approximate height of the handle area visible when closed
        const maxTranslate = drawerHeight - handleHeight; // Mostly closed state (only handle showing)
        let currentTranslate = calculateTranslateY(detailsDrawer); // Get initial position

        // Threshold to determine if drawer should snap open or closed
        const snapThreshold = drawerHeight * 0.4; // Snap open if dragged more than 40% up

        // Function to update main content height based on drawer position
        function adjustMainContentHeight(translateY) {
          if (mainContent) {
            // Calculate how much vertical space the drawer occupies *above the bottom nav*
            const navHeight = 60; // Assuming bottom nav is 60px
            const occupiedDrawerHeight = Math.max(0, drawerHeight - translateY - navHeight);
            // Calculate the new height for the main content area
            // Viewport height - header height - nav height - occupied drawer height
            const headerHeight = 80; // Assuming header is 80px
            const newMainHeight = `calc(100vh - ${headerHeight}px - ${navHeight}px - ${occupiedDrawerHeight}px - -85px)`; // <<< Added 20px gap
            mainContent.style.height = newMainHeight;
            // console.log(`Adjusting main content height. Drawer Occupied: ${occupiedDrawerHeight}px, New Height: ${newMainHeight}`); // Optional debug log
          }
        }

        // Function to open the drawer fully (callable from other scripts)
        window.openDrawer = function() {
          detailsDrawer.style.transition = "transform 0.2s ease";
          detailsDrawer.style.transform = `translateY(${minTranslate}px)`;
          currentTranslate = minTranslate;
        }

        // Handle starting a drag
        function handleDragStart(e) {
          isDragging = true;
          startY = getClientY(e); // Get initial touch/mouse position
          startTranslate = calculateTranslateY(detailsDrawer); // Get current drawer position
          detailsDrawer.classList.add("dragging"); // Add class to disable transitions

          // Add event listeners for move and end events
          if (e.type === "mousedown") {
            document.addEventListener("mousemove", handleDragMove);
            document.addEventListener("mouseup", handleDragEnd);
          } else if (e.type === "touchstart") {
            document.addEventListener("touchmove", handleDragMove, { passive: false });
            document.addEventListener("touchend", handleDragEnd);
          }
          e.preventDefault(); // Prevent text selection/page scroll during drag
        }

        // Handle drag movement
        function handleDragMove(e) {
          if (!isDragging) return;

          const currentY = getClientY(e);
          const deltaY = currentY - startY; // How far we've moved
          let newTranslate = startTranslate + deltaY; // Calculate new position

          // Clamp the translation within bounds (minTranslate to maxTranslate)
          newTranslate = Math.max(minTranslate, Math.min(maxTranslate, newTranslate));

          // Apply new position directly (no transition during drag)
          detailsDrawer.style.transform = `translateY(${newTranslate}px)`;
          currentTranslate = newTranslate; // Store current position

          adjustMainContentHeight(currentTranslate); // Update main content height during drag
          e.preventDefault(); // Prevent scrolling while dragging drawer
        }

        // Handle end of drag
        function handleDragEnd(e) {
          if (!isDragging) return;
          isDragging = false;
          detailsDrawer.classList.remove("dragging"); // Re-enable transitions

          // Snap to open or closed position based on threshold
          // Snap open if it's dragged further up than (maxTranslate - snapThreshold)
          const snappedPosition = (currentTranslate < (maxTranslate - snapThreshold)) ? minTranslate : maxTranslate;

          // Apply smooth transition to final snapped position
          detailsDrawer.style.transition = "transform 0.2s ease";
          detailsDrawer.style.transform = `translateY(${snappedPosition}px)`;
          currentTranslate = snappedPosition;
          adjustMainContentHeight(currentTranslate); // Update height after snap

          // Remove move and end event listeners
          document.removeEventListener("mousemove", handleDragMove);
          document.removeEventListener("mouseup", handleDragEnd);
          document.removeEventListener("touchmove", handleDragMove);
          document.removeEventListener("touchend", handleDragEnd);
        }

        // Handle click on the handle to toggle drawer
        function handleClick() {
          // Toggle between open (minTranslate) and closed (maxTranslate) positions
          const newPosition = (currentTranslate === minTranslate) ? maxTranslate : minTranslate;
          detailsDrawer.style.transition = "transform 0.2s ease";
          detailsDrawer.style.transform = `translateY(${newPosition}px)`;
          currentTranslate = newPosition;
          adjustMainContentHeight(currentTranslate); // Update height on click toggle
        }

        // Helper function to get clientY from mouse or touch events
        function getClientY(e) {
          return e.type.includes("touch") ? e.touches[0].clientY : e.clientY;
        }

        // Helper function to calculate the current translateY value from the transform style
        function calculateTranslateY(element) {
          const transform = window.getComputedStyle(element).getPropertyValue("transform");
          if (transform === "none") return maxTranslate; // Default to closed if no transform
          const matrix = transform.match(/^matrix\((.+)\)$/);
          return matrix ? parseFloat(matrix[1].split(", ")[5]) : maxTranslate;
        }

        // Add event listeners for dragging and clicking the handle
        drawerHandle.addEventListener("mousedown", handleDragStart);
        drawerHandle.addEventListener("touchstart", handleDragStart, { passive: false });
        drawerHandle.addEventListener("click", handleClick); // Add click listener too

        // Set initial main content height based on initial drawer state
        adjustMainContentHeight(currentTranslate);

        // Navigation functionality
        const navLinks = document.querySelectorAll(".bottom-nav a");

        navLinks.forEach((link) => {
          link.addEventListener("click", function (e) {
            // Remove active class from all links
            navLinks.forEach((l) => l.classList.remove("active"));

            // Add active class to clicked link
            this.classList.add("active");

            // Optional: prevent default behavior if you want to handle navigation in JS
            // e.preventDefault();

            // You can add custom navigation logic here
            const section = this.getAttribute("href").substring(1);
            console.log("Navigating to:", section);

            // Example: Show/hide different content based on navigation
            if (section === "explore") {
              // Show explore content
            } else if (section === "rooms") {
              // Show rooms content
            } else if (section === "about") {
              // Show about content
            }
          });
        });

        // --- Logic for QR Code Office Highlight ---
        // This uses officesData and highlightOfficeIdFromPHP defined in the script tag above this one.

        console.log("DOM Content Loaded. Checking for highlightOfficeIdFromPHP:", window.highlightOfficeIdFromPHP);
        console.log("DOM Content Loaded. Checking officesData:", officesData ? `Available with ${officesData.length} items` : "Not available or empty");

        if (window.highlightOfficeIdFromPHP !== null && typeof officesData !== 'undefined' && officesData && officesData.length > 0) {
            console.log("QR Code: Proceeding to find office with ID:", window.highlightOfficeIdFromPHP);
            const officeToHighlight = officesData.find(office => Number(office.id) === window.highlightOfficeIdFromPHP);
            if (officeToHighlight) {
                console.log("QR Code: Found office to highlight:", officeToHighlight);
                
                // Store as current selected office for pathfinding
                window.currentSelectedOffice = officeToHighlight;
                
                setTimeout(() => {
                    console.log("QR Code: setTimeout triggered. Calling populateAndShowDrawerWithData and showYouAreHere.");
                    populateAndShowDrawerWithData(officeToHighlight);
                    
                    // Show "YOU ARE HERE" indicator
                    if (officeToHighlight.location && window.showYouAreHere) {
                        window.showYouAreHere(officeToHighlight.location);
                    }
                    
                    // Auto-center on the highlighted office
                    setTimeout(() => {
                        const roomElement = document.getElementById(officeToHighlight.location);
                        if (roomElement && window.svgPanZoomInstance) {
                            const bbox = roomElement.getBBox();
                            const svg = document.querySelector('svg');
                            if (svg) {
                                const svgRect = svg.getBoundingClientRect();
                                const centerX = bbox.x + bbox.width / 2;
                                const centerY = bbox.y + bbox.height / 2;
                                
                                // Pan to the room location
                                window.svgPanZoomInstance.pan({x: centerX, y: centerY});
                                window.svgPanZoomInstance.zoom(1.5); // Zoom in a bit
                            }
                        }
                    }, 500);
                }, 300); // 300ms delay to ensure UI is ready
            } else {
                console.warn("QR Code: Office ID", window.highlightOfficeIdFromPHP, "not found in officesData.");
                console.log("Available office IDs in officesData:", officesData.map(o => o.id));
            }
        } else if (window.highlightOfficeIdFromPHP !== null) {
            console.warn("QR Code: officesData is not defined, empty, or highlightOfficeIdFromPHP is null. Cannot highlight office from QR.");
        }

        // --- SVG Room Click Handler (Enhanced with Desktop-style Pathfinding) ---
        function setupRoomClickHandlers() {
          // Wait for SVG to be loaded
          const svg = document.getElementById('svg1');
          if (!svg) {
            setTimeout(setupRoomClickHandlers, 100); // Try again shortly
            return;
          }
          
          // Set up click handlers for ALL room elements (not just office-assigned ones)
          const roomElements = svg.querySelectorAll('[id^="room-"]');
          console.log(`Found ${roomElements.length} room elements for click handling`);
          
          roomElements.forEach(function(el) {
            const roomId = el.id;
            
            // Remove any existing event listeners
            el.removeEventListener('click', mobileRoomClickHandler);
            
            // Add the mobile room click handler that integrates with desktop pathfinding
            el.addEventListener('click', mobileRoomClickHandler);
            
            el.style.cursor = 'pointer';
            
            // Optional: highlight on hover
            el.addEventListener('mouseenter', function() { 
              if (window.pathfindingMode) {
                el.style.opacity = 0.7; 
                el.style.stroke = '#ff4444';
                el.style.strokeWidth = '3';
              } else {
                el.style.opacity = 0.8;
              }
            });
            el.addEventListener('mouseleave', function() { 
              if (!el.classList.contains('selected-room')) {
                el.style.opacity = ''; 
                el.style.stroke = '';
                el.style.strokeWidth = '';
              }
            });
          });
          
          // Also set up office-specific labeling
          officesData.forEach(function(office) {
            if (!office.location) return;
            let el = svg.getElementById ? svg.getElementById(office.location) : document.getElementById(office.location);
            if (!el) {
              el = svg.querySelector('[data-room-id="' + office.location + '"]');
            }
            if (el) {
              // Update the room label with office name
              updateRoomLabel(el, office.name);
            }
          });
        }
        setupRoomClickHandlers();



        // Directions button logic - open pathfinding modal
        document.getElementById('directions-btn').onclick = async function() {
          // Populate both dropdowns with all available locations
          const startLocationSelect = document.getElementById('start-location');
          const endLocationSelect = document.getElementById('end-location');
          
          // Clear existing options
          startLocationSelect.innerHTML = '<option value="">Select starting point...</option>';
          endLocationSelect.innerHTML = '<option value="">Select destination...</option>';
          
          // If user came from QR code, set their current location as default start
          let defaultStartLocation = null;
          let defaultStartText = null;
          
          if (window.currentSelectedOffice && window.currentSelectedOffice.location && window.highlightOfficeIdFromPHP) {
            defaultStartLocation = window.currentSelectedOffice.location;
            defaultStartText = window.currentSelectedOffice.name + ' (YOU ARE HERE)';
            
            // Add default start option only if user came from QR code
            const defaultStart = document.createElement('option');
            defaultStart.value = defaultStartLocation;
            defaultStart.textContent = defaultStartText;
            defaultStart.selected = true;
            startLocationSelect.appendChild(defaultStart);
          }
          
          // Add all available offices on current floor to both dropdowns
          console.log('Populating dropdowns with offices for floor:', currentFloor);
          console.log('Available floorGraph rooms:', window.floorGraph ? Object.keys(window.floorGraph.rooms || {}) : 'No floor graph loaded');
          
          let addedOptions = 0;

          const officeOptions = (officesData || [])
            .filter(office => office.location)
            .map(office => {
              const floorNumber = getFloorFromLocation(office.location);
              return {
                value: office.location,
                label: `${office.name}${floorNumber ? ` (Floor ${floorNumber})` : ''}`,
                floor: floorNumber
              };
            })
            .sort((a, b) => {
              if (a.floor == null && b.floor != null) return 1;
              if (b.floor == null && a.floor != null) return -1;
              if (a.floor !== b.floor) return (a.floor || 0) - (b.floor || 0);
              return a.label.localeCompare(b.label);
            });

          officeOptions.forEach(option => {
            if (option.value !== defaultStartLocation) {
              const startOption = document.createElement('option');
              startOption.value = option.value;
              startOption.textContent = option.label;
              startOption.dataset.floorNumber = option.floor || '';
              startLocationSelect.appendChild(startOption);
            }

            const endOption = document.createElement('option');
            endOption.value = option.value;
            endOption.textContent = option.label;
            endOption.dataset.floorNumber = option.floor || '';
            if (window.currentSelectedOffice && option.value === window.currentSelectedOffice.location && !window.highlightOfficeIdFromPHP) {
              endOption.selected = true;
            }
            endLocationSelect.appendChild(endOption);
            addedOptions++;
          });

          if (addedOptions === 0 && typeof window.ensureFloorGraphLoaded === 'function') {
            const floorsToLoad = [1, 2, 3];
            for (const floor of floorsToLoad) {
              try {
                const graph = await window.ensureFloorGraphLoaded(floor);
                if (graph && graph.rooms) {
                  Object.keys(graph.rooms).forEach(roomId => {
                    const label = `Room ${roomId} (Floor ${floor})`;
                    if (roomId !== defaultStartLocation) {
                      const startOption = document.createElement('option');
                      startOption.value = roomId;
                      startOption.textContent = label;
                      startOption.dataset.floorNumber = floor;
                      startLocationSelect.appendChild(startOption);
                    }
                    const endOption = document.createElement('option');
                    endOption.value = roomId;
                    endOption.textContent = label;
                    endOption.dataset.floorNumber = floor;
                    endLocationSelect.appendChild(endOption);
                    addedOptions++;
                  });
                }
              } catch (error) {
                console.warn('Failed to preload floor graph for dropdown fallback:', floor, error);
              }
            }
          }

          // Show pathfinding modal
          document.getElementById('pathfinding-modal-overlay').style.display = 'flex';
        };

        // Pathfinding modal event handlers
        document.getElementById('close-pathfinding-modal').onclick = function() {
          document.getElementById('pathfinding-modal-overlay').style.display = 'none';
        };

        document.getElementById('find-path-btn').onclick = async function() {
          const startLocation = document.getElementById('start-location').value;
          const endLocation = document.getElementById('end-location').value;
          
          if (!startLocation || !endLocation) {
            alert('Please select both start and destination locations');
            return;
          }
          
          if (startLocation === endLocation) {
            alert('Start and end locations cannot be the same.');
            return;
          }
          
          document.getElementById('pathfinding-modal-overlay').style.display = 'none';

          if (typeof window.activateRouteBetweenRooms !== 'function') {
            alert('Pathfinding system is not ready yet. Please wait for the map to load.');
            return;
          }

          try {
            const startFloorNumber = getFloorFromLocation(startLocation);
            if (startFloorNumber && startFloorNumber !== currentFloor) {
              await loadFloorMap(startFloorNumber);
            }

            if (typeof window.resetActiveRoute === 'function') {
              window.resetActiveRoute();
            }

            const route = await window.activateRouteBetweenRooms(startLocation, endLocation);

            if (!route) {
              alert('No available route between the selected locations.');
              return;
            }

            window.selectedRooms = [startLocation, endLocation];

            const firstFloor = route.floors ? route.floors[0] : startFloorNumber;
            if (firstFloor && firstFloor !== currentFloor) {
              await loadFloorMap(firstFloor);
            }

            if (route.type === 'multi-floor') {
              alert('Multi-floor route ready! Follow the green path and switch floors as instructed in the panel.');
            } else {
              alert('Directions found! Path is highlighted on the map.');
            }
          } catch (error) {
            console.error('Error computing route via modal:', error);
            alert('Unable to calculate directions right now. Please try again.');
          }
        };

        // Initialize navigation enhancement functions
        initializeNavigationEnhancements();
        
        document.getElementById('clear-path-btn').onclick = function() {
          // Clear all path highlights
          if (window.clearPath) {
            window.clearPath();
          } else {
            // Fallback clearing
            document.querySelectorAll('.path-highlight').forEach(el => {
              el.classList.remove('path-highlight');
            });
            if (typeof clearAllPaths === 'function') {
              clearAllPaths();
            }
          }
          if (typeof window.resetActiveRoute === 'function') {
            window.resetActiveRoute();
          }
          window.selectedRooms = [];
          document.getElementById('pathfinding-modal-overlay').style.display = 'none';
        };
      });

      // === NAVIGATION ENHANCEMENT FUNCTIONS ===
      
      function initializeNavigationEnhancements() {
        // Initialize back navigation tracking
        initBackNavigation();
        
        // Initialize breadcrumb system
        initBreadcrumbs();
        
        // Initialize user guide
        initUserGuide();
        
        // Add CSS dynamically if not present
        addNavigationStyles();
      }
      
      function initBackNavigation() {
        // Track page history for better back navigation
        window.gabayHistory = window.gabayHistory || [];
        
        // Add current page to history if not already there
        const currentPage = {
          page: 'explore',
          title: 'GABAY Navigation',
          timestamp: Date.now()
        };
        
        if (!window.gabayHistory.find(h => h.page === 'explore')) {
          window.gabayHistory.push(currentPage);
        }
        
        // Create back button for mobile if not exists
        if (!document.getElementById('mobile-back-btn') && window.innerWidth <= 768) {
          createMobileBackButton();
        }
      }
      
      function createMobileBackButton() {
        const backBtn = document.createElement('button');
        backBtn.id = 'mobile-back-btn';
        backBtn.className = 'mobile-back-btn';
        backBtn.innerHTML = '<i class="fas fa-arrow-left"></i>';
        backBtn.title = 'Go Back';
        backBtn.style.display = 'none'; // Initially hidden
        
        backBtn.onclick = function() {
          if (window.gabayHistory && window.gabayHistory.length > 1) {
            // Remove current page from history
            window.gabayHistory.pop();
            const previousPage = window.gabayHistory[window.gabayHistory.length - 1];
            
            if (previousPage && previousPage.page !== 'explore') {
              window.location.href = previousPage.page + '.php';
            } else {
              // Stay on explore page but reset state
              resetExploreState();
            }
          }
        };
        
        document.body.appendChild(backBtn);
      }
      
      function showBackButton() {
        const backBtn = document.getElementById('mobile-back-btn');
        if (backBtn) {
          backBtn.style.display = 'flex';
        }
      }
      
      function hideBackButton() {
        const backBtn = document.getElementById('mobile-back-btn');
        if (backBtn) {
          backBtn.style.display = 'none';
        }
      }
      
      function initBreadcrumbs() {
        // Create breadcrumb container if not exists
        if (!document.getElementById('breadcrumb-nav')) {
          const breadcrumbContainer = document.createElement('nav');
          breadcrumbContainer.id = 'breadcrumb-nav';
          breadcrumbContainer.className = 'breadcrumb-nav';
          
          const breadcrumbList = document.createElement('ol');
          breadcrumbList.className = 'breadcrumb-list';
          
          // Add home breadcrumb
          const homeCrumb = document.createElement('li');
          homeCrumb.className = 'breadcrumb-item';
          homeCrumb.innerHTML = '<i class="fas fa-home"></i> <span>GABAY</span>';
          breadcrumbList.appendChild(homeCrumb);
          
          breadcrumbContainer.appendChild(breadcrumbList);
          
          // Insert after header or at top of main content
          const header = document.querySelector('header') || document.querySelector('main');
          if (header) {
            header.parentNode.insertBefore(breadcrumbContainer, header.nextSibling);
          }
        }
      }
      
      function updateBreadcrumbs(pageName, pageTitle) {
        const breadcrumbList = document.querySelector('.breadcrumb-list');
        if (!breadcrumbList) return;
        
        // Remove all breadcrumbs after home
        const items = breadcrumbList.querySelectorAll('.breadcrumb-item');
        for (let i = 1; i < items.length; i++) {
          items[i].remove();
        }
        
        // Add current page breadcrumb if not home
        if (pageName !== 'explore') {
          const currentCrumb = document.createElement('li');
          currentCrumb.className = 'breadcrumb-item active';
          currentCrumb.innerHTML = `<span>${pageTitle}</span>`;
          breadcrumbList.appendChild(currentCrumb);
        }
      }
      
      function initUserGuide() {
        // Always set up the help button event listener
        createHelpButton();
        
        // Create user guide modal if not exists
        if (!document.getElementById('user-guide-modal')) {
          createUserGuideModal();
        }
        
        // Check if this is the user's first visit
        checkAndShowFirstTimeGuide();
      }
      
      function checkAndShowFirstTimeGuide() {
        // Check if the user has disabled the guide
        const guideDisabled = localStorage.getItem('gabay_guide_disabled');
        if (guideDisabled === 'true') {
          console.log('User has disabled the guide, skipping auto-show');
          return;
        }
        
        // Check if the user has visited before
        const hasVisitedBefore = localStorage.getItem('gabay_has_visited');
        const lastGuideShown = localStorage.getItem('gabay_last_guide_shown');
        
        // Show guide for first-time users or if it's been more than 30 days since last shown
        const thirtyDaysAgo = Date.now() - (30 * 24 * 60 * 60 * 1000);
        const shouldShowGuide = !hasVisitedBefore || 
                               (!lastGuideShown || parseInt(lastGuideShown) < thirtyDaysAgo);
        
        if (shouldShowGuide) {
          console.log('Showing user guide for new or returning visitor');
          
          // Wait a bit for the page to load before showing the guide
          setTimeout(() => {
            showUserGuideWelcome();
            
            // Mark that we've shown the guide
            localStorage.setItem('gabay_has_visited', 'true');
            localStorage.setItem('gabay_last_guide_shown', Date.now().toString());
          }, 1500); // Show after 1.5 seconds to let the page settle
        } else {
          // Not first time and within 30 days, just mark as visited
          localStorage.setItem('gabay_has_visited', 'true');
          console.log('User has visited recently, skipping guide');
        }
      }
      
      function showUserGuideWelcome() {
        console.log('Showing first-time interactive tour');
        
        // Remove help button pulse animation since guide is being shown
        const helpBtn = document.getElementById('help-button');
        if (helpBtn) {
          helpBtn.classList.remove('first-time-pulse');
        }
        
        // Show the interactive tour for first-time users
        startInteractiveTour();
      }
      
      // Interactive Tour System
      let currentTourStep = 0;
      const tourSteps = [
        {
          title: "Welcome to GABAY!",
          description: "Let's take a quick interactive tour to show you how to navigate the building easily.",
          target: null,
          position: "center",
          showAnimation: "welcome",
          action: "start"
        },
        {
          title: "Navigate with Touch",
          description: "👆 Drag with one finger to move around\n🤏 Pinch with two fingers to zoom in/out\nThe floor plan responds to your touch!",
          target: "#svg-container",
          position: "bottom",
          showAnimation: "pinch-zoom",
          action: "interact",
          interactionType: "pan-zoom"
        },
        {
          title: "Switch Between Floors",
          description: "Tap these buttons to explore different floors of the building.",
          target: ".floor-selector",
          position: "bottom-left",
          showAnimation: "tap",
          action: "interact",
          interactionType: "floor-switch"
        },
        {
          title: "Click on Any Office",
          description: "Tap any room on the floor plan to see office details and get information.",
          target: ".selectable-room:first-of-type",
          position: "top",
          showAnimation: "tap-room",
          action: "interact",
          interactionType: "room-click"
        },
        {
          title: "Office Details Drawer",
          description: "When you select an office, this drawer slides up with details. You can drag it up and down!",
          target: "#details-drawer",
          position: "top",
          showAnimation: "drag-vertical",
          action: "interact",
          interactionType: "drawer-drag"
        },
        {
          title: "360° Panorama Views",
          description: "Look for camera icons like this! Tap them to see immersive 360° views of locations.",
          target: ".panorama-marker:first-of-type",
          position: "bottom",
          showAnimation: "tap-camera",
          action: "interact",
          interactionType: "panorama-click"
        },
        {
          title: "View All Offices",
          description: "Need to browse all offices? Tap this list button to see the complete directory.",
          target: "#rooms-list-button",
          position: "bottom-right",
          showAnimation: "tap",
          action: "interact",
          interactionType: "rooms-list"
        },
        {
          title: "You're All Set!",
          description: "Great! You now know how to navigate GABAY. The help button is always available if you need a refresher.",
          target: "#help-button",
          position: "bottom-left",
          showAnimation: "success",
          action: "complete"
        }
      ];
      
      function startInteractiveTour() {
        console.log('Starting interactive tour');
        
        const tourOverlay = document.getElementById('interactive-tour-overlay');
        if (!tourOverlay) {
          console.error('Tour overlay not found - creating it now');
          createUserGuideModal();
          
          // Try again after creation
          const newTourOverlay = document.getElementById('interactive-tour-overlay');
          if (!newTourOverlay) {
            console.error('Failed to create tour overlay');
            return;
          }
        }
        
        const overlay = document.getElementById('interactive-tour-overlay');
        currentTourStep = 0;
        overlay.classList.remove('hidden');
        overlay.style.display = 'block';
        overlay.style.opacity = '1';
        
        // Mobile-specific body handling
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.height = '100%';
        
        // Prevent scrolling on mobile
        if (window.innerWidth <= 768) {
          document.addEventListener('touchmove', preventScroll, { passive: false });
        }
        
        console.log('Tour overlay shown, starting first step');
        
        // Start the first step
        displayTourStep(currentTourStep);
      }
      
      function preventScroll(e) {
        e.preventDefault();
      }
      
      function displayTourStep(stepIndex) {
        const step = tourSteps[stepIndex];
        if (!step) {
          console.log('No step found for index:', stepIndex);
          endInteractiveTour();
          return;
        }
        
        console.log(`Displaying tour step ${stepIndex + 1}: ${step.title}`);
        
        // Update step indicator
        const currentStepEl = document.querySelector('.current-step');
        const totalStepsEl = document.querySelector('.total-steps');
        console.log('Step indicator elements:', { currentStepEl, totalStepsEl });
        
        if (currentStepEl) currentStepEl.textContent = stepIndex + 1;
        if (totalStepsEl) totalStepsEl.textContent = tourSteps.length;
        
        // Update tooltip content
        const tooltip = document.getElementById('tour-tooltip');
        console.log('Tour tooltip element:', tooltip);
        
        if (!tooltip) {
          console.error('Tour tooltip not found!');
          return;
        }
        
        const titleEl = tooltip.querySelector('.tooltip-title');
        const descEl = tooltip.querySelector('.tooltip-description');
        const animationEl = tooltip.querySelector('.tooltip-animation');
        const actionsEl = tooltip.querySelector('.tooltip-actions');
        
        console.log('Tooltip sub-elements:', { titleEl, descEl, animationEl, actionsEl });
        
        if (titleEl) titleEl.textContent = step.title;
        if (descEl) {
          // Handle line breaks in descriptions
          if (step.description.includes('\n')) {
            descEl.innerHTML = step.description.replace(/\n/g, '<br>');
          } else {
            descEl.textContent = step.description;
          }
        }
        
        // Update animation
        if (animationEl) {
          animationEl.innerHTML = getTourAnimation(step.showAnimation);
        }
        
        // Update action buttons
        if (actionsEl) {
          if (step.action === 'start') {
            actionsEl.innerHTML = `
              <button class="tour-btn secondary" onclick="skipInteractiveTour()">
                Skip Tour
              </button>
              <button class="tour-btn primary" onclick="nextTourStep()">
                Start Tour <i class="fas fa-arrow-right"></i>
              </button>
            `;
          } else if (step.action === 'complete') {
            actionsEl.innerHTML = `
              <button class="tour-btn primary large" onclick="endInteractiveTour()">
                <i class="fas fa-check"></i> Finish Tour
              </button>
            `;
          } else {
            actionsEl.innerHTML = `
              <button class="tour-btn secondary" onclick="skipInteractiveTour()">
                Skip
              </button>
              <button class="tour-btn primary" onclick="nextTourStep()">
                Next <i class="fas fa-arrow-right"></i>
              </button>
            `;
          }
        }
        
        // Position tooltip and spotlight
        positionTourElements(step);
        
        // Set up interactions if needed
        if (step.action === 'interact') {
          setupTourInteraction(step);
        }
      }
      
      function getTourAnimation(animationType) {
        const animations = {
          welcome: '<i class="fas fa-hand-wave animated-wave"></i>',
          'pinch-zoom': '<div class="pinch-zoom-demo"><i class="fas fa-hand-paper hand-1"></i><i class="fas fa-hand-paper hand-2"></i></div>',
          tap: '<i class="fas fa-hand-pointer animated-tap"></i>',
          'tap-room': '<div class="tap-room-demo"><i class="fas fa-hand-pointer"></i><div class="room-highlight"></div></div>',
          'drag-vertical': '<i class="fas fa-hand-paper animated-drag-vertical"></i>',
          'tap-camera': '<div class="camera-tap-demo"><i class="fas fa-camera"></i><i class="fas fa-hand-pointer"></i></div>',
          success: '<i class="fas fa-check-circle animated-success"></i>'
        };
        return animations[animationType] || '<i class="fas fa-hand-pointer"></i>';
      }
      
      function positionTourElements(step) {
        const spotlight = document.getElementById('tour-spotlight');
        const tooltip = document.getElementById('tour-tooltip');
        
        if (step.target) {
          const targetEl = document.querySelector(step.target);
          if (targetEl) {
            const rect = targetEl.getBoundingClientRect();
            
            // Position spotlight
            spotlight.style.left = (rect.left - 10) + 'px';
            spotlight.style.top = (rect.top - 10) + 'px';
            spotlight.style.width = (rect.width + 20) + 'px';
            spotlight.style.height = (rect.height + 20) + 'px';
            spotlight.classList.remove('center');
            
            // Position tooltip
            positionTooltip(tooltip, rect, step.position);
          }
        } else {
          // Center position for welcome/completion steps
          spotlight.classList.add('center');
          tooltip.style.position = 'fixed';
          
          // Mobile-responsive positioning
          if (window.innerWidth <= 768) {
            tooltip.style.left = '50%';
            tooltip.style.top = 'auto';
            tooltip.style.bottom = '20px';
            tooltip.style.transform = 'translateX(-50%)';
            tooltip.style.maxHeight = '70vh';
          } else {
            tooltip.style.left = '50%';
            tooltip.style.top = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
          }
          
          tooltip.style.zIndex = '15002';
          tooltip.style.display = 'block';
          tooltip.style.visibility = 'visible';
          
          console.log('Responsive centered tooltip positioning applied');
        }
      }
      
      function positionTooltip(tooltip, targetRect, position) {
        // On mobile, always position at bottom for better UX
        if (window.innerWidth <= 768) {
          tooltip.style.left = '50%';
          tooltip.style.top = 'auto';
          tooltip.style.bottom = '20px';
          tooltip.style.transform = 'translateX(-50%)';
          tooltip.style.maxHeight = '60vh';
          tooltip.style.overflowY = 'auto';
          return;
        }
        
        // Desktop positioning logic
        const tooltipRect = tooltip.getBoundingClientRect();
        let left, top, transform = '';
        
        switch (position) {
          case 'top':
            left = targetRect.left + (targetRect.width / 2);
            top = targetRect.top - 20;
            transform = 'translate(-50%, -100%)';
            break;
          case 'bottom':
            left = targetRect.left + (targetRect.width / 2);
            top = targetRect.bottom + 20;
            transform = 'translate(-50%, 0%)';
            break;
          case 'bottom-left':
            left = targetRect.left;
            top = targetRect.bottom + 20;
            transform = 'translate(0%, 0%)';
            break;
          case 'bottom-right':
            left = targetRect.right;
            top = targetRect.bottom + 20;
            transform = 'translate(-100%, 0%)';
            break;
          default:
            left = targetRect.left + (targetRect.width / 2);
            top = targetRect.bottom + 20;
            transform = 'translate(-50%, 0%)';
        }
        
        // Ensure tooltip stays within viewport
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        if (left < 10) left = 10;
        if (left > viewportWidth - 350) left = viewportWidth - 350;
        if (top < 10) top = 10;
        if (top > viewportHeight - 200) top = viewportHeight - 200;
        
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        tooltip.style.transform = transform;
      }
      
      function setupTourInteraction(step) {
        // Add event listeners for the specific interaction type
        const targetEl = step.target ? document.querySelector(step.target) : null;
        
        if (!targetEl) return;
        
        // Add visual emphasis
        targetEl.classList.add('tour-highlight');
        
        // Remove highlight after interaction or timeout
        setTimeout(() => {
          targetEl.classList.remove('tour-highlight');
        }, 5000);
      }
      
      function nextTourStep() {
        currentTourStep++;
        if (currentTourStep >= tourSteps.length) {
          endInteractiveTour();
        } else {
          displayTourStep(currentTourStep);
        }
      }
      
      function skipInteractiveTour() {
        console.log('User skipped interactive tour');
        endInteractiveTour();
        
        // Mark as visited but allow re-showing
        localStorage.setItem('gabay_has_visited', 'true');
      }
      
      function endInteractiveTour() {
        console.log('Ending interactive tour');
        
        const tourOverlay = document.getElementById('interactive-tour-overlay');
        if (tourOverlay) {
          tourOverlay.classList.add('hidden');
        }
        
        // Restore mobile scrolling
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        document.body.style.width = 'auto';
        document.body.style.height = 'auto';
        
        // Remove mobile scroll prevention
        if (window.innerWidth <= 768) {
          document.removeEventListener('touchmove', preventScroll);
        }
        
        // Remove any tour highlights
        document.querySelectorAll('.tour-highlight').forEach(el => {
          el.classList.remove('tour-highlight');
        });
        
        // Mark as completed
        localStorage.setItem('gabay_has_visited', 'true');
        localStorage.setItem('gabay_last_guide_shown', Date.now().toString());
        localStorage.setItem('gabay_tour_completed', 'true');
      }
      
      function createHelpButton() {
        // Button already exists in header, just add event listener
        const helpBtn = document.getElementById('help-button');
        console.log('Help button found:', helpBtn);
        if (helpBtn) {
          helpBtn.onclick = function(e) {
            e.preventDefault();
            console.log('Help button clicked');
            
            // Remove any first-time user styling when manually clicked
            helpBtn.classList.remove('first-time-pulse');
            
            // Always show the clean version when manually accessed
            cleanupWelcomeGuide();
            showUserGuide();
          };
          console.log('Help button event listener added');
          
          // Add pulse animation for first-time users
          const hasVisitedBefore = localStorage.getItem('gabay_has_visited');
          if (!hasVisitedBefore) {
            setTimeout(() => {
              helpBtn.classList.add('first-time-pulse');
              console.log('Added first-time pulse animation to help button');
            }, 3000); // Start pulsing after 3 seconds
          }
        } else {
          console.error('Help button not found in DOM');
        }
      }
      
      function createUserGuideModal() {
        const modal = document.createElement('div');
        modal.id = 'user-guide-modal';
        modal.className = 'user-guide-modal';
        modal.innerHTML = `
          <div class="user-guide-content">
            <div class="user-guide-header">
              <h2><i class="fas fa-compass"></i> GABAY Quick Guide</h2>
              <button class="close-guide-btn" onclick="hideUserGuide()">
                <i class="fas fa-times"></i>
              </button>
            </div>
            
            <div class="guide-step">
              <div class="step-number">1</div>
              <div class="step-content">
                <h3><i class="fas fa-hand-paper"></i> Navigate the Floor Plan</h3>
                <p><strong>👆 Drag</strong> with one finger to move around</p>
                <p><strong>🤏 Pinch</strong> with two fingers to zoom in/out</p>
              </div>
            </div>

            <div class="guide-step">
              <div class="step-number">2</div>
              <div class="step-content">
                <h3><i class="fas fa-layer-group"></i> Switch Floors</h3>
                <p>Tap the <strong>1F, 2F, 3F</strong> buttons in the top-right corner to explore different floors</p>
              </div>
            </div>

            <div class="guide-step">
              <div class="step-number">3</div>
              <div class="step-content">
                <h3><i class="fas fa-mouse-pointer"></i> Select an Office</h3>
                <p>Tap any <strong>room</strong> on the floor plan to view office details and information</p>
              </div>
            </div>

            <div class="guide-step">
              <div class="step-number">4</div>
              <div class="step-content">
                <h3><i class="fas fa-hand-point-up"></i> Use the Details Drawer</h3>
                <p>When you select an office, a drawer slides up from the bottom. <strong>Drag it up/down</strong> to see more details</p>
              </div>
            </div>

            <div class="guide-step">
              <div class="step-number">5</div>
              <div class="step-content">
                <h3><i class="fas fa-camera"></i> View 360° Panoramas</h3>
                <p>Look for <strong>camera icons</strong> and tap them to see immersive 360° views of locations</p>
              </div>
            </div>

            <div class="guide-step">
              <div class="step-number">6</div>
              <div class="step-content">
                <h3><i class="fas fa-list"></i> Browse All Offices</h3>
                <p>Tap the <strong>list button</strong> in the top navigation to see all offices in a directory</p>
              </div>
            </div>

            <div class="guide-step">
              <div class="step-number">7</div>
              <div class="step-content">
                <h3><i class="fas fa-route"></i> Get Directions</h3>
                <p>In office details, tap <strong>"Get Directions"</strong> to find the best path to your destination</p>
              </div>
            </div>

            <div class="guide-step">
              <div class="step-number">8</div>
              <div class="step-content">
                <h3><i class="fas fa-comment-alt"></i> Give Feedback</h3>
                <p>Tap the <strong>feedback button</strong> in the header to rate your experience and report issues</p>
              </div>
            </div>
            
            <div class="user-guide-footer">
              <button class="got-it-btn" onclick="hideUserGuide()">
                <i class="fas fa-check"></i> Got It!
              </button>
              <p><i class="fas fa-info-circle"></i> Need more help? Contact the front desk for assistance.</p>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
      }
      
      function showUserGuide() {
        console.log('showUserGuide called');
        const modal = document.getElementById('user-guide-modal');
        if (modal) {
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
          console.log('Simple guide modal displayed');
        } else {
          console.error('User guide modal not found');
        }
      }
      
      function hideUserGuide() {
        const modal = document.getElementById('user-guide-modal');
        if (modal) {
          modal.style.display = 'none';
          document.body.style.overflow = 'auto';
          
          // Remove welcome styling if present
          cleanupWelcomeGuide();
        }
      }
      
      function skipUserGuide() {
        console.log('User skipped the guide');
        hideUserGuide();
        
        // Still mark as visited but don't update the "last shown" timestamp
        // This means the guide could show again on next visit if it's been 30+ days
        localStorage.setItem('gabay_has_visited', 'true');
      }
      
      function dontShowGuideAgain() {
        console.log('User chose not to show guide again');
        hideUserGuide();
        
        // Mark as visited and set the "last shown" to far future
        localStorage.setItem('gabay_has_visited', 'true');
        localStorage.setItem('gabay_last_guide_shown', (Date.now() + (365 * 24 * 60 * 60 * 1000)).toString()); // 1 year from now
        localStorage.setItem('gabay_guide_disabled', 'true');
      }
      
      function cleanupWelcomeGuide() {
        // Remove welcome-specific elements from the modal
        const modal = document.getElementById('user-guide-modal');
        if (modal) {
          const welcomeSection = modal.querySelector('.welcome-section');
          if (welcomeSection) {
            welcomeSection.remove();
          }
          
          // Reset header text
          const header = modal.querySelector('.user-guide-header h2');
          if (header) {
            header.innerHTML = '<i class="fas fa-compass"></i> GABAY User Guide';
          }
          
          // Reset footer text
          const footer = modal.querySelector('.user-guide-footer p');
          if (footer) {
            footer.innerHTML = '<i class="fas fa-info-circle"></i> Need more help? Contact the front desk for assistance.';
          }
        }
      }
      
      function resetExploreState() {
        // Clear any active selections
        window.selectedRooms = [];
        
        // Clear paths
        if (window.clearPath) {
          window.clearPath();
        }
        
        // Reset modal states
        const modals = document.querySelectorAll('.modal-overlay, .user-guide-modal');
        modals.forEach(modal => {
          modal.style.display = 'none';
        });
        
        // Reset drawer state
        const drawer = document.getElementById('details-drawer');
        if (drawer) {
          drawer.classList.remove('open');
        }
        
        document.body.style.overflow = 'auto';
      }
      
      function addNavigationStyles() {
        // Check if styles already added
        if (document.getElementById('navigation-enhancement-styles')) {
          return;
        }
        
        const styleSheet = document.createElement('style');
        styleSheet.id = 'navigation-enhancement-styles';
        styleSheet.textContent = `
          /* Mobile Back Button */
          .mobile-back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 44px;
            height: 44px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 1000;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
          }
          
          .mobile-back-btn:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: scale(1.05);
          }
          
          .mobile-back-btn:active {
            transform: scale(0.95);
          }
          
          /* Breadcrumb Navigation */
          .breadcrumb-nav {
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 16px;
            border-bottom: 1px solid #e0e0e0;
            backdrop-filter: blur(10px);
          }
          
          .breadcrumb-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
          }
          
          .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #666;
          }
          
          .breadcrumb-item:not(:last-child)::after {
            content: '›';
            margin-left: 8px;
            color: #999;
            font-weight: bold;
          }
          
          .breadcrumb-item.active {
            color: #333;
            font-weight: 500;
          }
          
          .breadcrumb-item i {
            font-size: 12px;
          }
          
          /* Help Button in Header */
          .help-button-header {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
          }
          
          .help-button-header:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
          }
          
          .help-button-header:active {
            transform: scale(0.95);
          }
          
          /* First-time user pulse animation */
          .help-button-header.first-time-pulse {
            animation: firstTimePulse 2s ease-in-out infinite;
            position: relative;
          }
          
          .help-button-header.first-time-pulse::after {
            content: "NEW";
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            animation: newBadgePulse 1s ease-in-out infinite alternate;
          }
          
          @keyframes firstTimePulse {
            0%, 100% {
              transform: scale(1);
              box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
            }
            50% {
              transform: scale(1.1);
              box-shadow: 0 4px 16px rgba(76, 175, 80, 0.6);
            }
          }
          
          @keyframes newBadgePulse {
            from {
              transform: scale(1);
              opacity: 1;
            }
            to {
              transform: scale(1.2);
              opacity: 0.8;
            }
          }
          
          /* Interactive Tour Overlay System */
          .interactive-tour-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 15000;
            pointer-events: auto;
            transition: all 0.3s ease;
            display: block;
            opacity: 1;
          }
          
          .interactive-tour-overlay.hidden {
            opacity: 0;
            pointer-events: none;
            display: none;
          }
          
          .tour-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(3px);
            z-index: 15000;
          }
          
          .tour-spotlight {
            position: absolute;
            background: transparent;
            border-radius: 12px;
            box-shadow: 
              0 0 0 4px rgba(76, 175, 80, 0.6),
              0 0 0 8px rgba(76, 175, 80, 0.3),
              0 0 30px rgba(76, 175, 80, 0.4);
            z-index: 15001;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            animation: spotlightPulse 2s ease-in-out infinite;
          }
          
          .tour-spotlight.center {
            left: 50% !important;
            top: 30% !important;
            width: 200px !important;
            height: 200px !important;
            transform: translate(-50%, -50%);
            border-radius: 50%;
          }
          
          @media (max-width: 768px) {
            .tour-spotlight {
              border-radius: 16px;
              box-shadow: 
                0 0 0 6px rgba(76, 175, 80, 0.7),
                0 0 0 12px rgba(76, 175, 80, 0.4),
                0 0 40px rgba(76, 175, 80, 0.5);
            }
            
            .tour-spotlight.center {
              top: 25% !important;
              width: 240px !important;
              height: 240px !important;
            }
          }
          
          @media (max-width: 480px) {
            .tour-spotlight.center {
              top: 20% !important;
              width: 200px !important;
              height: 200px !important;
            }
          }
          
          @keyframes spotlightPulse {
            0%, 100% { 
              box-shadow: 
                0 0 0 4px rgba(76, 175, 80, 0.6),
                0 0 0 8px rgba(76, 175, 80, 0.3),
                0 0 30px rgba(76, 175, 80, 0.4);
            }
            50% { 
              box-shadow: 
                0 0 0 6px rgba(76, 175, 80, 0.8),
                0 0 0 12px rgba(76, 175, 80, 0.4),
                0 0 40px rgba(76, 175, 80, 0.6);
            }
          }
          
          .tour-tooltip {
            position: fixed;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 16px;
            box-shadow: 
              0 20px 40px rgba(0, 0, 0, 0.15),
              0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 90vw;
            min-width: 280px;
            width: 90vw;
            z-index: 15002;
            border: 2px solid rgba(76, 175, 80, 0.2);
            animation: tooltipSlideIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: block;
            visibility: visible;
            opacity: 1;
            margin: 10px;
          }
          
          /* Mobile-First Responsive Design for Tour */
          @media (max-width: 768px) {
            .tour-tooltip {
              max-width: 95vw;
              width: 95vw;
              min-width: unset;
              margin: 5px;
              border-radius: 12px;
              left: 50% !important;
              top: auto !important;
              bottom: 20px !important;
              transform: translateX(-50%) !important;
              max-height: 70vh;
              overflow-y: auto;
            }
          }
          
          @media (max-width: 480px) {
            .tour-tooltip {
              max-width: 98vw;
              width: 98vw;
              margin: 2px;
              border-radius: 8px;
              bottom: 10px !important;
              max-height: 75vh;
            }
          }
          
          @keyframes tooltipSlideIn {
            from {
              opacity: 0;
              transform: scale(0.8) translateY(20px);
            }
            to {
              opacity: 1;
              transform: scale(1) translateY(0);
            }
          }
          
          .tooltip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px 8px;
            border-bottom: 1px solid #e9ecef;
          }
          
          .step-indicator {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
          }
          
          @media (max-width: 768px) {
            .tooltip-header {
              padding: 20px 16px 12px;
              position: sticky;
              top: 0;
              background: linear-gradient(135deg, #ffffff, #f8f9fa);
              z-index: 1;
            }
            
            .step-indicator {
              padding: 8px 16px;
              font-size: 16px;
            }
          }
          
          .close-tour-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: #666;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s ease;
            min-width: 40px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
          }
          
          .close-tour-btn:hover {
            background: rgba(0, 0, 0, 0.1);
            transform: scale(1.1);
          }
          
          @media (max-width: 768px) {
            .close-tour-btn {
              font-size: 22px;
              padding: 12px;
              min-width: 48px;
              min-height: 48px;
            }
          }
          
          .tooltip-content {
            padding: 16px 20px;
            text-align: center;
          }
          
          .tooltip-title {
            margin: 0 0 12px 0;
            color: #2c3e50;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.3;
          }
          
          .tooltip-description {
            margin: 0 0 16px 0;
            color: #555;
            line-height: 1.6;
            font-size: 15px;
          }
          
          @media (max-width: 768px) {
            .tooltip-content {
              padding: 20px 16px;
            }
            
            .tooltip-title {
              font-size: 22px;
              margin: 0 0 16px 0;
            }
            
            .tooltip-description {
              font-size: 16px;
              line-height: 1.7;
              margin: 0 0 20px 0;
            }
          }
          
          @media (max-width: 480px) {
            .tooltip-content {
              padding: 16px 12px;
            }
            
            .tooltip-title {
              font-size: 20px;
            }
            
            .tooltip-description {
              font-size: 15px;
            }
          }
          
          .tooltip-animation {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 60px;
            margin-bottom: 16px;
          }
          
          @media (max-width: 768px) {
            .tooltip-animation {
              height: 80px;
              margin-bottom: 20px;
            }
          }
          
          @media (max-width: 480px) {
            .tooltip-animation {
              height: 70px;
              margin-bottom: 16px;
            }
          }
          
          .tooltip-actions {
            display: flex;
            gap: 12px;
            padding: 16px 20px;
            border-top: 1px solid #e9ecef;
          }
          
          .tour-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            min-height: 44px;
            touch-action: manipulation;
          }
          
          @media (max-width: 768px) {
            .tooltip-actions {
              padding: 20px 16px;
              gap: 16px;
              flex-direction: column;
            }
            
            .tour-btn {
              flex: none;
              padding: 16px 24px;
              font-size: 16px;
              font-weight: 700;
              min-height: 52px;
              border-radius: 30px;
              box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
          }
          
          @media (max-width: 480px) {
            .tooltip-actions {
              padding: 16px 12px;
              gap: 12px;
            }
            
            .tour-btn {
              padding: 18px 24px;
              font-size: 17px;
              min-height: 56px;
            }
          }
          
          .tour-btn.primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
          }
          
          .tour-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
          }
          
          .tour-btn.secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #dee2e6;
          }
          
          .tour-btn.secondary:hover {
            background: #e9ecef;
            transform: translateY(-1px);
          }
          
          .tour-btn.large {
            padding: 16px 32px;
            font-size: 16px;
          }
          
          /* Tour Highlight Effect */
          .tour-highlight {
            position: relative;
            z-index: 14999;
          }
          
          .tour-highlight::after {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            background: rgba(76, 175, 80, 0.2);
            border-radius: 8px;
            animation: highlightPulse 1.5s ease-in-out infinite;
            pointer-events: none;
          }
          
          @keyframes highlightPulse {
            0%, 100% { 
              background: rgba(76, 175, 80, 0.2);
              transform: scale(1);
            }
            50% { 
              background: rgba(76, 175, 80, 0.4);
              transform: scale(1.05);
            }
          }
          
          /* User Guide Modal (Fallback) */
          .user-guide-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
            backdrop-filter: blur(5px);
          }
          
          .user-guide-content {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
          }
          
          .user-guide-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 16px 16px 0 0;
          }
          
          .user-guide-header h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
          }
          
          .user-guide-header h2 i {
            color: #4CAF50;
          }
          
          .close-guide-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
          }
          
          .close-guide-btn:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
          }
          
          .user-guide-body {
            padding: 24px;
          }
          
          .guide-section {
            margin-bottom: 32px;
          }
          
          .guide-section:last-child {
            margin-bottom: 0;
          }
          
          .guide-section h3 {
            color: #333;
            margin: 0 0 16px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e0e0;
          }
          
          .guide-section h3 i {
            color: #4CAF50;
            font-size: 16px;
          }
          
          .guide-section ul {
            margin: 0;
            padding-left: 24px;
          }
          
          .guide-section li {
            margin-bottom: 12px;
            line-height: 1.6;
            color: #555;
          }
          
          .guide-section li strong {
            color: #333;
            font-weight: 600;
          }
          
          .user-guide-footer {
            padding: 16px 24px 24px;
            background: #f8f9fa;
            border-radius: 0 0 16px 16px;
            text-align: center;
          }
          
          .guide-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
          }
          
          .guide-action-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            min-width: 120px;
            justify-content: center;
          }
          
          .skip-btn {
            background: #6c757d;
            color: white;
          }
          
          .skip-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
          }
          
          .dont-show-btn {
            background: #dc3545;
            color: white;
          }
          
          .dont-show-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
          }
          
          .got-it-btn {
            background: #4CAF50;
            color: white;
          }
          
          .got-it-btn:hover {
            background: #45a049;
            transform: translateY(-1px);
          }
          
          .user-guide-footer p {
            margin: 0;
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
          }
          
          .user-guide-footer i {
            color: #4CAF50;
          }
          
          /* Simple Guide Step Styles */
          .user-guide-content {
            max-height: 60vh;
            overflow-y: auto;
            padding: 10px;
          }
          
          .guide-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #007bff;
          }
          
          .step-number {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 15px;
            flex-shrink: 0;
            margin-top: 2px;
          }
          
          .step-content h3 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
          }
          
          .step-content p {
            margin: 4px 0;
            color: #666;
            font-size: 14px;
            line-height: 1.4;
          }
          
          .step-content h3 i {
            color: #007bff;
            margin-right: 8px;
          }
          
          .got-it-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 auto 15px;
            transition: all 0.2s;
            min-height: 48px;
          }
          
          .got-it-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
          }
          
          /* Mobile optimization for guide */
          @media (max-width: 768px) {
            .user-guide-content {
              max-height: 50vh;
              padding: 5px;
            }
            
            .guide-step {
              margin-bottom: 15px;
              padding: 12px;
            }
            
            .step-number {
              width: 26px;
              height: 26px;
              font-size: 12px;
              margin-right: 12px;
            }
            
            .step-content h3 {
              font-size: 15px;
            }
            
            .step-content p {
              font-size: 13px;
            }
            
            .got-it-btn {
              width: 100%;
              justify-content: center;
              min-height: 52px;
            }
          }
          
          /* Tour Animation Elements */
          .animated-wave {
            animation: wave 1s ease-in-out infinite;
            color: #4CAF50;
            font-size: 36px;
          }
          
          .animated-tap {
            animation: tap 1.5s ease-in-out infinite;
            color: #2196F3;
            font-size: 32px;
          }
          
          .animated-drag-vertical {
            animation: dragVertical 2s ease-in-out infinite;
            color: #FF9800;
            font-size: 32px;
          }
          
          .animated-success {
            animation: success 0.8s ease-out;
            color: #4CAF50;
            font-size: 48px;
          }
          
          @media (max-width: 768px) {
            .animated-wave {
              font-size: 48px;
            }
            
            .animated-tap {
              font-size: 42px;
            }
            
            .animated-drag-vertical {
              font-size: 42px;
            }
            
            .animated-success {
              font-size: 60px;
            }
          }
          
          @media (max-width: 480px) {
            .animated-wave {
              font-size: 40px;
            }
            
            .animated-tap {
              font-size: 36px;
            }
            
            .animated-drag-vertical {
              font-size: 36px;
            }
            
            .animated-success {
              font-size: 52px;
            }
          }
          
          .pinch-zoom-demo {
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: center;
          }
          
          .pinch-zoom-demo .hand-1 {
            animation: pinchIn 2s ease-in-out infinite;
            color: #2196F3;
            font-size: 28px;
          }
          
          .pinch-zoom-demo .hand-2 {
            animation: pinchOut 2s ease-in-out infinite;
            color: #2196F3;
            font-size: 28px;
          }
          
          @media (max-width: 768px) {
            .pinch-zoom-demo {
              gap: 30px;
            }
            
            .pinch-zoom-demo .hand-1,
            .pinch-zoom-demo .hand-2 {
              font-size: 36px;
            }
          }
          
          @media (max-width: 480px) {
            .pinch-zoom-demo {
              gap: 25px;
            }
            
            .pinch-zoom-demo .hand-1,
            .pinch-zoom-demo .hand-2 {
              font-size: 32px;
            }
          }
          
          .tap-room-demo {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
          }
          
          .tap-room-demo .fa-hand-pointer {
            animation: tapRoom 1.5s ease-in-out infinite;
            color: #2196F3;
            font-size: 28px;
            z-index: 2;
          }
          
          .room-highlight {
            position: absolute;
            width: 40px;
            height: 30px;
            background: rgba(76, 175, 80, 0.3);
            border: 2px solid #4CAF50;
            border-radius: 4px;
            animation: roomPulse 1.5s ease-in-out infinite;
          }
          
          .camera-tap-demo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
          }
          
          .camera-tap-demo .fa-camera {
            color: #FF6B35;
            font-size: 32px;
            animation: cameraPulse 1.5s ease-in-out infinite;
          }
          
          .camera-tap-demo .fa-hand-pointer {
            color: #2196F3;
            font-size: 24px;
            animation: tapCamera 1.5s ease-in-out infinite;
          }
          
          /* Animation Keyframes */
          @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            75% { transform: rotate(-10deg); }
          }
          
          @keyframes tap {
            0%, 100% { transform: scale(1) translateY(0); }
            50% { transform: scale(1.2) translateY(-10px); }
          }
          
          @keyframes dragVertical {
            0%, 100% { transform: translateY(0); }
            25% { transform: translateY(-15px); }
            75% { transform: translateY(15px); }
          }
          
          @keyframes success {
            0% { transform: scale(0) rotate(0deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(180deg); opacity: 1; }
            100% { transform: scale(1) rotate(360deg); opacity: 1; }
          }
          
          @keyframes pinchIn {
            0%, 100% { transform: translateX(0) scale(1); }
            50% { transform: translateX(10px) scale(0.8); }
          }
          
          @keyframes pinchOut {
            0%, 100% { transform: translateX(0) scale(1); }
            50% { transform: translateX(-10px) scale(0.8); }
          }
          
          @keyframes tapRoom {
            0%, 100% { transform: scale(1) translateY(0); opacity: 0.7; }
            50% { transform: scale(1.3) translateY(-8px); opacity: 1; }
          }
          
          @keyframes roomPulse {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 0.9; }
          }
          
          @keyframes cameraPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
          }
          
          @keyframes tapCamera {
            0%, 100% { transform: translateX(0) scale(1); }
            50% { transform: translateX(-8px) scale(1.2); }
          }
          
          /* Quick Tips Styling */
          .quick-tips {
            display: flex;
            flex-direction: column;
            gap: 16px;
          }
          
          .tip-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            border-left: 4px solid #4CAF50;
            transition: transform 0.2s ease;
          }
          
          .tip-item:hover {
            transform: translateX(4px);
          }
          
          .tip-item i {
            color: #4CAF50;
            font-size: 20px;
            width: 24px;
            text-align: center;
          }
          
          .tip-item span {
            flex: 1;
            color: #2c3e50;
            line-height: 1.4;
          }
          
          .start-interactive-tour-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 12px;
          }
          
          .start-interactive-tour-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
          }
          
          /* Dark mode adjustments */
          @media (prefers-color-scheme: dark) {
            .breadcrumb-nav {
              background: rgba(33, 37, 41, 0.95);
              border-bottom-color: #495057;
            }
            
            .breadcrumb-item {
              color: #adb5bd;
            }
            
            .breadcrumb-item.active {
              color: #f8f9fa;
            }
            
            .user-guide-content {
              background: #212529;
              color: #f8f9fa;
            }
            
            .user-guide-header {
              background: linear-gradient(135deg, #343a40, #495057);
              border-bottom-color: #495057;
            }
            
            .user-guide-header h2 {
              color: #f8f9fa;
            }
            
            .close-guide-btn {
              color: #adb5bd;
            }
            
            .close-guide-btn:hover {
              background: rgba(255, 255, 255, 0.1);
              color: #f8f9fa;
            }
            
            .guide-section h3 {
              color: #f8f9fa;
              border-bottom-color: #495057;
            }
            
            .guide-section li {
              color: #adb5bd;
            }
            
            .guide-section li strong {
              color: #f8f9fa;
            }
            
            .user-guide-footer {
              background: #343a40;
            }
            
            .user-guide-footer p {
              color: #adb5bd;
            }
          }
          
          /* Welcome section styling */
          .welcome-section {
            background: linear-gradient(135deg, #e8f5e8, #f0f9f0);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #4CAF50;
            margin-bottom: 24px !important;
          }
          
          .welcome-message h3 {
            color: #2e7d32;
            margin: 0 0 12px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
          }
          
          .welcome-message h3 i {
            color: #4CAF50;
            animation: wave 1s ease-in-out infinite alternate;
          }
          
          @keyframes wave {
            from { transform: rotate(0deg); }
            to { transform: rotate(15deg); }
          }
          
          .welcome-message p {
            color: #1b5e20;
            line-height: 1.6;
            margin: 0 0 16px 0;
            font-size: 16px;
          }
          
          .welcome-highlight {
            background: rgba(76, 175, 80, 0.1);
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(76, 175, 80, 0.3);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #1b5e20;
          }
          
          .welcome-highlight i {
            color: #f57f17;
            margin-top: 2px;
            flex-shrink: 0;
          }
          
          .welcome-highlight strong {
            color: #2e7d32;
          }
          
          /* Mobile responsive adjustments */
          @media (max-width: 768px) {
            .user-guide-content {
              margin: 0;
              border-radius: 0;
              max-height: 100vh;
              height: 100vh;
            }
            
            .user-guide-header {
              border-radius: 0;
            }
            
            .user-guide-footer {
              border-radius: 0;
            }
            
            .breadcrumb-nav {
              display: none; /* Hide breadcrumbs on mobile for cleaner look */
            }
            
            .welcome-message h3 {
              font-size: 18px;
            }
            
            .welcome-highlight {
              font-size: 13px;
            }
            
            .guide-actions {
              flex-direction: column;
              gap: 8px;
            }
            
            .guide-action-btn {
              min-width: 100%;
              padding: 12px 16px;
              font-size: 16px;
            }
            
            /* Mobile Interactive Tour Adjustments */
            .tour-tooltip {
              max-width: 90vw;
              min-width: 280px;
              margin: 10px;
            }
            
            .tooltip-title {
              font-size: 18px;
            }
            
            .tooltip-description {
              font-size: 14px;
            }
            
            .tooltip-actions {
              flex-direction: column;
              gap: 8px;
            }
            
            .tour-btn {
              padding: 14px 20px;
              font-size: 16px;
            }
            
            .tooltip-animation {
              height: 50px;
            }
            
            .animated-wave,
            .animated-tap,
            .animated-drag-vertical {
              font-size: 28px;
            }
            
            .animated-success {
              font-size: 36px;
            }
            
            .tip-item {
              padding: 16px;
            }
            
            .tip-item i {
              font-size: 18px;
            }
            
            .start-interactive-tour-btn {
              padding: 18px 24px;
              font-size: 18px;
            }
          }
          
          /* Dark mode adjustments for welcome section */
          @media (prefers-color-scheme: dark) {
            .welcome-section {
              background: linear-gradient(135deg, #1a3d1a, #2d5a2d);
              border-left-color: #4CAF50;
            }
            
            .welcome-message h3 {
              color: #81c784;
            }
            
            .welcome-message p {
              color: #c8e6c9;
            }
            
            .welcome-highlight {
              background: rgba(76, 175, 80, 0.2);
              border-color: rgba(76, 175, 80, 0.4);
              color: #c8e6c9;
            }
            
            .welcome-highlight strong {
              color: #81c784;
            }
            
            .guide-action-btn {
              border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .skip-btn {
              background: #495057;
            }
            
            .skip-btn:hover {
              background: #6c757d;
            }
            
            .dont-show-btn {
              background: #dc3545;
            }
            
            .got-it-btn {
              background: #4CAF50;
            }
            
            /* Dark mode for Interactive Tour */
            .tour-tooltip {
              background: linear-gradient(135deg, #2c3e50, #34495e);
              border-color: rgba(76, 175, 80, 0.3);
            }
            
            .tooltip-header {
              border-bottom-color: #495057;
            }
            
            .tooltip-title {
              color: #f8f9fa;
            }
            
            .tooltip-description {
              color: #adb5bd;
            }
            
            .close-tour-btn {
              color: #adb5bd;
            }
            
            .close-tour-btn:hover {
              background: rgba(255, 255, 255, 0.1);
              color: #f8f9fa;
            }
            
            .tour-btn.secondary {
              background: #495057;
              color: #adb5bd;
              border-color: #6c757d;
            }
            
            .tour-btn.secondary:hover {
              background: #6c757d;
              color: #f8f9fa;
            }
            
            .tooltip-actions {
              border-top-color: #495057;
            }
            
            .tip-item {
              background: linear-gradient(135deg, #343a40, #495057);
              color: #f8f9fa;
            }
            
            .tip-item span {
              color: #adb5bd;
            }
          }
        `;
        
        document.head.appendChild(styleSheet);
      }

      // Debug function to verify panorama marker IDs
      function verifyPanoramaMarkerIDs() {
        const markers = document.querySelectorAll('.panorama-marker');
        const circles = document.querySelectorAll('[id^="camera-circle-"]');
        const icons = document.querySelectorAll('[id^="camera-icon-"]');
        
        console.log('=== PANORAMA MARKER ID VERIFICATION ===');
        console.log(`Found ${markers.length} panorama markers`);
        console.log(`Found ${circles.length} camera circles with IDs`);
        console.log(`Found ${icons.length} camera icons with IDs`);
        
        const idMap = new Map();
        let duplicates = 0;
        
        markers.forEach((marker, index) => {
          const markerId = marker.getAttribute('id');
          const pathId = marker.getAttribute('data-path-id');
          const pointIndex = marker.getAttribute('data-point-index');
          const panoramaId = marker.getAttribute('data-panorama-id');
          
          if (idMap.has(markerId)) {
            console.warn(`⚠️  Duplicate marker ID found: ${markerId}`);
            duplicates++;
          } else {
            idMap.set(markerId, true);
          }
          
          const circleId = `camera-circle-${pathId}-${pointIndex}`;
          const iconId = `camera-icon-${pathId}-${pointIndex}`;
          const hasCircle = !!document.getElementById(circleId);
          const hasIcon = !!document.getElementById(iconId);
          
          console.log(`Marker ${index + 1}:`, {
            markerId,
            pathId,
            pointIndex,
            panoramaId,
            circleId,
            iconId,
            hasCircle,
            hasIcon,
            valid: hasCircle && hasIcon
          });
        });
        
        console.log(`✅ All markers verified. ${duplicates === 0 ? 'No duplicates found.' : `⚠️  ${duplicates} duplicates found!`}`);
        console.log('=== END VERIFICATION ===');
        
        return {
          totalMarkers: markers.length,
          totalCircles: circles.length,
          totalIcons: icons.length,
          duplicates: duplicates,
          valid: duplicates === 0 && markers.length === circles.length && circles.length === icons.length
        };
      }

      // Auto-verify panorama marker IDs after floor loads
      function autoVerifyPanoramaIDs() {
        setTimeout(() => {
          const result = verifyPanoramaMarkerIDs();
          if (result.valid) {
            console.log('✅ Panorama marker IDs are valid and ready for hotspot integration');
          } else {
            console.warn('⚠️  Panorama marker ID issues detected. Check console for details.');
          }
        }, 2500); // Wait for all floor loading to complete
      }

      // Call verification after page loads
      document.addEventListener('DOMContentLoaded', autoVerifyPanoramaIDs);

      // ===== URL PARAMETER HANDLING =====
      // Check if page loaded with panorama parameters and auto-open split screen
      document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const pathId = urlParams.get('path_id');
        const pointIndex = urlParams.get('point_index');
        const floorNumber = urlParams.get('floor_number');
        
        if (pathId && pointIndex) {
          console.log('🔗 Page loaded with panorama parameters, auto-opening split screen');
          
          // Wait for floor to load, then show panorama
          setTimeout(() => {
            showPanoramaSplitScreen(pathId, pointIndex, floorNumber || '1');
          }, 2000);
        }
      });

        // ===== PANORAMA NAVIGATION MESSAGE HANDLER =====
      // Listen for navigation messages from panorama iframe
      window.addEventListener('message', function(event) {
        console.log('📧 Message received in mobile explore.php:', event.data);
        console.log('📍 Message origin:', event.origin);
        console.log('📤 Message source:', event.source);
        
        // Validate message format
        if (event.data && event.data.type === 'panoramaNavigation') {
          const { targetPathId, targetPointIndex, targetFloorNumber, updateURL, source, highlightCamera, navigationContext } = event.data;
          
          console.log(`🚀 Panorama navigation request: path=${targetPathId}, point=${targetPointIndex}, floor=${targetFloorNumber}`);
          console.log(`🔍 Current floor: ${window.currentFloorNumber}, Target floor: ${targetFloorNumber}`);
          console.log(`🔗 Update URL requested: ${updateURL}, Source: ${source}`);
          console.log(`🎯 Highlight camera requested: ${highlightCamera}`);
          
          // Enhanced context from panorama viewer
          if (navigationContext) {
            console.log('📋 Navigation context:', navigationContext);
          }
          
          // Convert to numbers for proper comparison
          const currentFloor = parseInt(window.currentFloorNumber) || 1;
          const targetFloor = parseInt(targetFloorNumber) || 1;
          const isEnhancedNavigation = source === 'enhancedHotspotNavigation';
          const isCrossFloor = navigationContext ? navigationContext.isCrossFloor : (targetFloor !== currentFloor);
          
          // Store navigation target for post-floor-load processing
          window.pendingPanoramaNavigation = {
            targetPathId,
            targetPointIndex, 
            targetFloorNumber,
            source,
            navigationContext,
            timestamp: Date.now()
          };
          
          // Always update URL for navigation
          console.log('🔗 Updating URL for panorama navigation...');
          updateURLForNavigation(targetPathId, targetPointIndex, targetFloorNumber);
          
          // Enhanced navigation handling
          if (isCrossFloor) {
            console.log(`🏢 Cross-floor navigation: switching from floor ${currentFloor} to floor ${targetFloor}`);
            console.log('💡 Using CLEAN floor switch approach (like pressing floor button)');
            
            // Enhanced loading message with context
            const navigationTitle = navigationContext && navigationContext.title 
              ? `Going to: ${navigationContext.title}` 
              : `Switching to Floor ${targetFloor}...`;
            
            showSmoothNavigationStatus(navigationTitle, targetFloor, targetPointIndex);
            
            // STEP 1: Close split-screen cleanly to avoid SVG corruption
            const splitScreen = document.getElementById('panorama-split-screen');
            let wasSplitScreenActive = false;
            if (splitScreen && !splitScreen.classList.contains('hidden')) {
              wasSplitScreenActive = true;
              console.log('🔄 Closing split-screen for clean floor transition');
              hidePanoramaSplitScreen(); // This properly restores SVG to main container
            }
            
            // STEP 2: Do clean floor switch (like pressing floor button)
            console.log('🏢 Performing clean floor switch (button-style)');
            switchToFloor(targetFloor);
            
            // STEP 3: Wait for floor to load completely, then reopen split-screen
            setTimeout(() => {
              console.log('🔄 Floor should be loaded, reopening split-screen with new floor');
              
              if (wasSplitScreenActive) {
                // Reopen split-screen with new floor data
                showPanoramaSplitScreen(targetPathId, targetPointIndex, targetFloor);
              }
              
              // Highlight marker after split-screen is ready
              setTimeout(() => {
                console.log('🎯 Highlighting marker on new floor');
                highlightPanoramaMarkerSmooth(targetPathId, targetPointIndex, targetFloorNumber);
                hideSmoothNavigationStatus();
              }, 1000);
              
            }, 2000); // Give more time for clean floor switch to complete
            
          } else {
            console.log(`✅ Same floor navigation (Floor ${currentFloor}), highlighting immediately`);
            
            const navigationTitle = navigationContext && navigationContext.title 
              ? `Going to: ${navigationContext.title}` 
              : `Navigating to Point ${targetPointIndex}...`;
            
            showSmoothNavigationStatus(navigationTitle, currentFloor, targetPointIndex);
            
            // Same floor, enhanced immediate highlighting
            if (isEnhancedNavigation) {
              setTimeout(() => {
                highlightPanoramaMarkerSmooth(targetPathId, targetPointIndex, targetFloorNumber);
                setTimeout(() => hideSmoothNavigationStatus(), 1000);
              }, 200);
            } else {
              setTimeout(() => {
                highlightPanoramaMarker(targetPathId, targetPointIndex, targetFloorNumber);
                setTimeout(() => hideSmoothNavigationStatus(), 1000);
              }, 300);
            }
          }
          
          // Handle specific camera highlighting requests (regardless of floor switching)
          if (highlightCamera || source === 'cameraUpdate') {
            console.log('🎯 Explicit camera highlighting requested');
            const highlightDelay = isCrossFloor ? (isEnhancedNavigation ? 1200 : 1800) : 200;
            setTimeout(() => {
              if (isEnhancedNavigation) {
                highlightPanoramaMarkerSmooth(targetPathId, targetPointIndex, targetFloorNumber);
              } else {
                highlightPanoramaMarker(targetPathId, targetPointIndex, targetFloorNumber);
              }
            }, highlightDelay);
          }
        } else {
          console.log('❌ Invalid message format or type:', event.data);
        }
      });      // Function to highlight and activate a specific panorama marker
      function highlightPanoramaMarker(pathId, pointIndex, floorNumber) {
        console.log(`🎯 Highlighting panorama marker: path=${pathId}, point=${pointIndex}, floor=${floorNumber}`);
        console.log('🔍 Function called, about to search for markers...');
        
        // Find the target marker by data attributes
        const markers = document.querySelectorAll('.path-marker.point-marker, .point-marker');
        let targetMarker = null;
        
        markers.forEach(marker => {
          const markerPathId = marker.getAttribute('data-path-id');
          const markerPointIndex = marker.getAttribute('data-point-index');
          
          if (markerPathId === pathId && markerPointIndex == pointIndex) {
            targetMarker = marker;
          }
        });
        
        if (!targetMarker) {
          console.warn(`❌ Panorama marker not found: path=${pathId}, point=${pointIndex}`);
          
          // Debug: Show what markers ARE available
          console.log('🔍 Available markers on current floor:');
          markers.forEach((marker, index) => {
            console.log(`  Marker ${index + 1}:`, {
              pathId: marker.getAttribute('data-path-id'),
              pointIndex: marker.getAttribute('data-point-index'),
              floorNumber: marker.getAttribute('data-floor-number'),
              element: marker
            });
          });
          
          console.log(`🎯 Looking for: path="${pathId}", point="${pointIndex}"`);
          console.log(`🎯 Current floor number: ${window.currentFloorNumber}`);
          
          // Try to find similar markers
          const similarMarkers = [];
          markers.forEach(marker => {
            const markerPath = marker.getAttribute('data-path-id');
            if (markerPath === pathId) {
              similarMarkers.push({
                pathId: markerPath,
                pointIndex: marker.getAttribute('data-point-index'),
                floorNumber: marker.getAttribute('data-floor-number')
              });
            }
          });
          
          if (similarMarkers.length > 0) {
            console.log(`💡 Found ${similarMarkers.length} markers with same path (${pathId}):`, similarMarkers);
          }
          
          return;
        }
        
        // Reset all markers to inactive state using comprehensive reset function
        resetAllCameraHighlights();
        
        // Additional reset for markers found by the original query (for backwards compatibility)
        markers.forEach(marker => {
          marker.classList.remove('active', 'selected', 'highlighted');
          const bg = marker.querySelector('.camera-bg');
          if (bg) {
            bg.setAttribute('r', '12');
            bg.setAttribute('fill', '#2563eb');
            bg.setAttribute('stroke', '#ffffff');
            bg.removeAttribute('filter');
          }
        });
        
        // Highlight the target marker (same as click behavior)
        targetMarker.classList.add('active');
        const bg = targetMarker.querySelector('.camera-bg');
        if (bg) {
          bg.setAttribute('r', '15');
          bg.setAttribute('fill', '#fbbf24'); // yellow active color
          bg.setAttribute('stroke', '#ffffff');
        }
        
        // Update the panorama iframe to show the new panorama
        const panoramaViewer = document.querySelector('.panorama-viewer');
        if (panoramaViewer) {
          const panoUrl = `../Pano/pano_photosphere.html?path_id=${encodeURIComponent(pathId)}&point_index=${encodeURIComponent(pointIndex)}&floor_number=${encodeURIComponent(floorNumber)}`;
          panoramaViewer.innerHTML = `
            <iframe 
              src="${panoUrl}" 
              style="width: 100%; height: 100%; border: none;"
              allowfullscreen>
            </iframe>
          `;
          console.log('✅ Updated panorama viewer to new URL:', panoUrl);
        }
        
        // Update browser URL to reflect navigation
        const newUrl = `${window.location.pathname}?path_id=${encodeURIComponent(pathId)}&point_index=${encodeURIComponent(pointIndex)}&floor_number=${encodeURIComponent(floorNumber)}`;
        window.history.pushState({
          pathId: pathId,
          pointIndex: pointIndex,  
          floorNumber: floorNumber,
          splitScreenActive: true
        }, '', newUrl);
        console.log('✅ Browser URL updated via hotspot navigation to:', newUrl);
        
        console.log('✅ Panorama marker highlighted and panorama updated successfully');
      }

      // Function to update URL for navigation (like camera click does)
      function updateURLForNavigation(pathId, pointIndex, floorNumber) {
        console.log('🔗 Updating URL for navigation:', {pathId, pointIndex, floorNumber});
        
        try {
          // Create new URL with updated panorama parameters
          const params = new URLSearchParams(window.location.search);
          
          // Update panorama-specific parameters
          params.set('path_id', pathId);
          params.set('point_index', pointIndex);
          params.set('floor_number', floorNumber);
          
          // Also set the floor parameter for consistency with floor buttons
          params.set('floor', floorNumber);
          
          // Preserve other parameters like office_id if they exist
          // This ensures we don't lose the office context when navigating via hotspots
          
          const newUrl = `${window.location.pathname}?${params.toString()}`;
          
          // Use pushState to update URL (creates history entry like camera click)
          window.history.pushState({
            pathId: pathId,
            pointIndex: pointIndex,
            floorNumber: floorNumber,
            floor: floorNumber,
            source: 'hotspotNavigation',
            splitScreenActive: true,
            timestamp: Date.now()
          }, '', newUrl);
          
          console.log('✅ URL updated for hotspot navigation:', newUrl);
          
          // Trigger any URL change listeners if they exist
          if (typeof window.onPanoramaURLChange === 'function') {
            window.onPanoramaURLChange(pathId, pointIndex, floorNumber);
          }
          
          // Dispatch a custom event for other components that might be listening
          window.dispatchEvent(new CustomEvent('panoramaNavigated', {
            detail: { pathId, pointIndex, floorNumber, source: 'hotspotNavigation' }
          }));
          
        } catch (error) {
          console.error('❌ Error updating URL for navigation:', error);
        }
      }

      // Function to reset all camera highlights (called by panorama viewer)
      function resetAllCameraHighlights() {
        console.log('🔄 Resetting all camera highlights');
        
        try {
          // Find all possible camera elements with different selectors
          const cameraSelectors = [
            '.camera-circle',
            '.point-marker',
            '.path-marker', 
            '[data-path-id]',
            '.panorama-marker',
            '[class*="camera"]'
          ];
          
          let resetCount = 0;
          cameraSelectors.forEach(selector => {
            const cameras = document.querySelectorAll(selector);
            cameras.forEach(cam => {
              // Remove all possible highlight classes
              cam.classList.remove('selected', 'active', 'highlighted', 'current');
              
              // Reset camera background styling
              const bg = cam.querySelector('.camera-bg');
              if (bg) {
                bg.setAttribute('r', '12');
                bg.setAttribute('fill', '#2563eb'); // blue inactive color
                bg.setAttribute('stroke', '#ffffff');
                bg.removeAttribute('filter');
              }
              
              // Reset any SVG circle elements directly
              if (cam.tagName === 'circle' || cam.classList.contains('camera-bg')) {
                cam.setAttribute('r', '12');
                cam.setAttribute('fill', '#2563eb');
                cam.setAttribute('stroke', '#ffffff');
                cam.removeAttribute('filter');
              }
              
              resetCount++;
            });
          });
          
          console.log(`✅ Reset ${resetCount} camera elements`);
          return resetCount;
          
        } catch (error) {
          console.error('❌ Error resetting camera highlights:', error);
          return 0;
        }
      }

      // Make reset function globally available
      window.resetAllCameraHighlights = resetAllCameraHighlights;

      // ===== ENHANCED SMOOTH NAVIGATION FUNCTIONS =====
      
      // Enhanced smooth navigation functions
      function loadFloorMapForNavigation(floorNumber, targetPathId, targetPointIndex) {
        console.log(`🗺️ Loading floor ${floorNumber} map for navigation to path ${targetPathId}, point ${targetPointIndex}`);
        
        // Show navigation progress
        showSmoothNavigationStatus(`Loading Floor ${floorNumber}...`, floorNumber, targetPointIndex);
        
        // Use existing loadFloorMap but with navigation hooks
        const originalLoadFloorMap = loadFloorMap;
        
        // Create a wrapper that adds navigation-specific handling
        loadFloorMap(floorNumber).then(() => {
          console.log(`✅ Floor ${floorNumber} loaded successfully for navigation`);
          
          // Wait for floor to fully render
          setTimeout(() => {
            // Process pending navigation if it matches current request
            if (window.pendingPanoramaNavigation && 
                window.pendingPanoramaNavigation.targetPathId === targetPathId &&
                window.pendingPanoramaNavigation.targetPointIndex === targetPointIndex) {
              
              console.log('🎯 Processing pending panorama navigation after floor load');
              
              // Note: Split-screen handling is now done via clean approach (close->switch->reopen)
              // No need to manually move SVG here as showPanoramaSplitScreen handles it properly
              
              // Highlight the target marker
              highlightPanoramaMarkerSmooth(targetPathId, targetPointIndex, floorNumber);
              
              // Update status
              showSmoothNavigationStatus(`Arrived at Floor ${floorNumber}`, floorNumber, targetPointIndex);
              
              // Restore split-screen visibility
              if (splitScreen) {
                splitScreen.style.opacity = '1';
                splitScreen.style.pointerEvents = 'auto';
              }
              
              // Clear pending navigation
              window.pendingPanoramaNavigation = null;
              
              // Hide status after success
              setTimeout(() => {
                hideSmoothNavigationStatus();
              }, 1500);
            }
          }, 800); // Allow time for pathfinding and markers to be drawn
        }).catch(error => {
          console.error('❌ Error loading floor for navigation:', error);
          showSmoothNavigationStatus(`Error loading Floor ${floorNumber}`, floorNumber, targetPointIndex, 'error');
          setTimeout(() => hideSmoothNavigationStatus(), 3000);
        });
      }

      function highlightPanoramaMarkerSmooth(pathId, pointIndex, floorNumber) {
        console.log(`🎯 Smooth highlighting panorama marker: path=${pathId}, point=${pointIndex}, floor=${floorNumber}`);
        
        // Wait for markers to be ready with improved logic
        const waitForMarkersAndHighlight = (attempts = 0) => {
          const maxAttempts = 15;
          
          // Check if we're still in a floor transition (with timeout protection)
          if (window.isFloorTransitioning && attempts < 10) {
            console.log(`🔄 Still in floor transition, waiting... (attempt ${attempts + 1}/10)`);
            setTimeout(() => waitForMarkersAndHighlight(attempts + 1), 200);
            return;
          } else if (window.isFloorTransitioning && attempts >= 10) {
            console.warn('⚠️ Floor transition taking too long, proceeding anyway');
            window.isFloorTransitioning = false; // Force clear the stuck flag
          }
          
          // Check if SVG is loaded and processed
          const svg = document.querySelector('#svg-container svg');
          if (!svg) {
            console.log('⏳ SVG not yet loaded, waiting...');
            if (attempts < maxAttempts) {
              setTimeout(() => waitForMarkersAndHighlight(attempts + 1), 300);
            } else {
              console.log('❌ SVG never loaded properly');
            }
            return;
          }
          
          const markers = document.querySelectorAll('.path-marker.point-marker, .point-marker');
          console.log(`🔍 Found ${markers.length} total markers in DOM`);
          
          let targetMarker = null;
          
          markers.forEach(marker => {
            const markerPathId = marker.getAttribute('data-path-id');
            const markerPointIndex = marker.getAttribute('data-point-index');
            
            if (markerPathId === pathId && markerPointIndex == pointIndex) {
              targetMarker = marker;
            }
          });
          
          if (targetMarker) {
            console.log('✅ Target marker found, applying smooth highlight animation');
            
            // Reset all markers first with smooth animation
            markers.forEach(marker => {
              marker.style.transition = 'all 0.3s ease';
              marker.classList.remove('active', 'selected', 'highlighted');
              const bg = marker.querySelector('.camera-bg');
              if (bg) {
                bg.style.transition = 'all 0.3s ease';
                bg.setAttribute('r', '12');
                bg.setAttribute('fill', '#2563eb');
                bg.setAttribute('stroke', '#ffffff');
                bg.removeAttribute('filter');
              }
            });
            
            // Highlight target marker with animation
            setTimeout(() => {
              targetMarker.classList.add('active');
              targetMarker.style.transform = 'scale(1.2)';
              
              const bg = targetMarker.querySelector('.camera-bg');
              if (bg) {
                bg.setAttribute('r', '15');
                bg.setAttribute('fill', '#fbbf24'); // yellow active color
                bg.setAttribute('stroke', '#ffffff');
                bg.style.filter = 'drop-shadow(0 0 8px rgba(251, 191, 36, 0.8))';
              }
              
              // Reset scale after animation
              setTimeout(() => {
                targetMarker.style.transform = 'scale(1)';
              }, 300);
              
              console.log('✅ Marker highlighted with smooth animation');
              
              // Update panorama iframe
              updatePanoramaIframeSmooth(pathId, pointIndex, floorNumber);
              
            }, 100);
            
          } else if (attempts < maxAttempts) {
            console.log(`⏳ Markers not ready yet, attempt ${attempts + 1}/${maxAttempts}`);
            
            // Debug what markers ARE available
            if (attempts > 5 && markers.length > 0) {
              console.log('🔍 Debug - Available markers:');
              markers.forEach((marker, index) => {
                console.log(`  Marker ${index + 1}:`, {
                  pathId: marker.getAttribute('data-path-id'),
                  pointIndex: marker.getAttribute('data-point-index'),
                  floorNumber: marker.getAttribute('data-floor-number'),
                  classes: marker.className
                });
              });
              console.log(`🎯 Looking for: path="${pathId}", point="${pointIndex}"`);
            }
            
            const delay = attempts < 5 ? 200 : 500; // Longer delays after first few attempts
            setTimeout(() => waitForMarkersAndHighlight(attempts + 1), delay);
          } else {
            console.warn(`❌ Could not find panorama marker after ${maxAttempts} attempts`);
            
            // Final debug attempt
            console.log('🔍 Final debug - all available markers:');
            markers.forEach((marker, index) => {
              console.log(`  Marker ${index + 1}:`, {
                pathId: marker.getAttribute('data-path-id'),
                pointIndex: marker.getAttribute('data-point-index'),
                floorNumber: marker.getAttribute('data-floor-number'),
                classes: marker.className,
                element: marker
              });
            });
            
            // Fallback to regular highlighting
            highlightPanoramaMarker(pathId, pointIndex, floorNumber);
          }
        };
        
        waitForMarkersAndHighlight();
      }

      function updatePanoramaIframeSmooth(pathId, pointIndex, floorNumber) {
        const panoramaViewer = document.querySelector('.panorama-viewer');
        if (panoramaViewer) {
          // Add smooth transition
          panoramaViewer.style.transition = 'opacity 0.3s ease';
          panoramaViewer.style.opacity = '0.7';
          
          setTimeout(() => {
            const panoUrl = `../Pano/pano_photosphere.html?path_id=${encodeURIComponent(pathId)}&point_index=${encodeURIComponent(pointIndex)}&floor_number=${encodeURIComponent(floorNumber)}`;
            panoramaViewer.innerHTML = `
              <iframe 
                src="${panoUrl}" 
                style="width: 100%; height: 100%; border: none;"
                allowfullscreen>
              </iframe>
            `;
            
            // Restore opacity after iframe loads
            setTimeout(() => {
              panoramaViewer.style.opacity = '1';
            }, 500);
            
            console.log('✅ Panorama iframe updated smoothly to:', panoUrl);
          }, 150);
        }
      }

      function showSmoothNavigationStatus(message, floorNumber, pointIndex, type = 'info') {
        let statusDiv = document.getElementById('smooth-navigation-status');
        if (!statusDiv) {
          statusDiv = document.createElement('div');
          statusDiv.id = 'smooth-navigation-status';
          statusDiv.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            background: linear-gradient(135deg, rgba(4, 170, 109, 0.95), rgba(26, 86, 50, 0.95));
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            font-weight: 600;
            z-index: 5000;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            font-size: 16px;
            text-align: center;
            min-width: 280px;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
          `;
          document.body.appendChild(statusDiv);
        }
        
        // Update styling based on type
        if (type === 'error') {
          statusDiv.style.background = 'linear-gradient(135deg, rgba(239, 68, 68, 0.95), rgba(185, 28, 28, 0.95))';
        } else {
          statusDiv.style.background = 'linear-gradient(135deg, rgba(4, 170, 109, 0.95), rgba(26, 86, 50, 0.95))';
        }
        
        statusDiv.innerHTML = `
          <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
            <div style="font-size: 24px; animation: ${type === 'error' ? 'shake' : 'pulse'} 1.5s ease-in-out infinite;">
              ${type === 'error' ? '⚠️' : '🧭'}
            </div>
            <div>
              <div style="font-size: 16px; margin-bottom: 4px;">${message}</div>
              <div style="font-size: 12px; opacity: 0.8;">
                ${floorNumber ? `Floor ${floorNumber}` : ''}${pointIndex ? ` • Point ${pointIndex}` : ''}
              </div>
            </div>
          </div>
        `;
        
        // Animate in
        requestAnimationFrame(() => {
          statusDiv.style.opacity = '1';
          statusDiv.style.transform = 'translate(-50%, -50%) scale(1)';
        });
        
        console.log(`📢 Smooth navigation status: ${message}`);
        
        // Add animations if not exist
        if (!document.getElementById('navigation-animations')) {
          const style = document.createElement('style');
          style.id = 'navigation-animations';
          style.textContent = `
            @keyframes pulse {
              0%, 100% { transform: scale(1); }
              50% { transform: scale(1.1); }
            }
            @keyframes shake {
              0%, 100% { transform: translateX(0); }
              25% { transform: translateX(-3px); }
              75% { transform: translateX(3px); }
            }
          `;
          document.head.appendChild(style);
        }
      }

      function hideSmoothNavigationStatus() {
        const statusDiv = document.getElementById('smooth-navigation-status');
        if (statusDiv) {
          statusDiv.style.transition = 'all 0.3s ease';
          statusDiv.style.opacity = '0';
          statusDiv.style.transform = 'translate(-50%, -50%) scale(0.8)';
          setTimeout(() => {
            if (statusDiv.parentNode) {
              statusDiv.parentNode.removeChild(statusDiv);
            }
          }, 300);
        }
      }

      // ===== TEST FUNCTIONS FOR DEBUGGING =====
      
      // User guide test functions
      window.resetUserGuideStatus = function() {
        localStorage.removeItem('gabay_has_visited');
        localStorage.removeItem('gabay_last_guide_shown');
        localStorage.removeItem('gabay_guide_disabled');
        console.log('✅ User guide status reset - reload page to see first-time experience');
      };
      
      window.showUserGuideNow = function() {
        console.log('🧪 Testing user guide display');
        showUserGuideWelcome();
      };
      
      window.testHelpButtonPulse = function() {
        const helpBtn = document.getElementById('help-button');
        if (helpBtn) {
          helpBtn.classList.add('first-time-pulse');
          console.log('✅ Help button pulse animation added');
        }
      };
      
      window.testInteractiveTour = function() {
        console.log('🧪 Testing interactive tour');
        startInteractiveTour();
      };
      
      window.debugTourElements = function() {
        console.log('=== TOUR DEBUG INFO ===');
        const overlay = document.getElementById('interactive-tour-overlay');
        const tooltip = document.getElementById('tour-tooltip');
        const spotlight = document.getElementById('tour-spotlight');
        const backdrop = document.querySelector('.tour-backdrop');
        
        console.log('Tour overlay:', overlay);
        console.log('Tour tooltip:', tooltip);
        console.log('Tour spotlight:', spotlight);
        console.log('Tour backdrop:', backdrop);
        
        if (overlay) {
          console.log('Overlay classes:', overlay.className);
          console.log('Overlay style display:', overlay.style.display);
          console.log('Overlay style opacity:', overlay.style.opacity);
          console.log('Overlay computed style:', getComputedStyle(overlay));
        }
        
        // Force show tour for debugging
        if (overlay && tooltip) {
          overlay.classList.remove('hidden');
          overlay.style.display = 'block';
          overlay.style.opacity = '1';
          overlay.style.visibility = 'visible';
          
          tooltip.style.display = 'block';
          tooltip.style.opacity = '1';
          tooltip.style.visibility = 'visible';
          
          console.log('✅ Forced tour elements to be visible');
        }
        console.log('=== END DEBUG ===');
      };
      
      window.createSimpleTourTest = function() {
        // Remove any existing tour overlay
        const existing = document.getElementById('interactive-tour-overlay');
        if (existing) existing.remove();
        
        // Create a simple test overlay
        const testOverlay = document.createElement('div');
        testOverlay.id = 'interactive-tour-overlay';
        testOverlay.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.8);
          z-index: 15000;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 24px;
        `;
        testOverlay.innerHTML = `
          <div style="text-align: center; padding: 40px; background: rgba(76, 175, 80, 0.9); border-radius: 16px;">
            <h2>🎉 Tour Test Working!</h2>
            <p>The tour overlay is functioning correctly.</p>
            <button onclick="document.getElementById('interactive-tour-overlay').remove()" 
                    style="padding: 12px 24px; background: white; color: #333; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
              Close Test
            </button>
          </div>
        `;
        
        document.body.appendChild(testOverlay);
        console.log('✅ Simple tour test overlay created and shown');
      };
      
      // Handle mobile orientation changes
      function handleOrientationChange() {
        if (currentTourStep >= 0 && currentTourStep < tourSteps.length) {
          setTimeout(() => {
            const step = tourSteps[currentTourStep];
            if (step) {
              positionTourElements(step);
            }
          }, 300); // Wait for orientation change to complete
        }
      }
      
      // Add orientation change listener
      window.addEventListener('orientationchange', handleOrientationChange);
      window.addEventListener('resize', handleOrientationChange);
      
      window.showTourStep = function(stepNumber) {
        if (stepNumber >= 0 && stepNumber < tourSteps.length) {
          currentTourStep = stepNumber;
          displayTourStep(stepNumber);
          
          const tourOverlay = document.getElementById('interactive-tour-overlay');
          if (tourOverlay) {
            tourOverlay.classList.remove('hidden');
          }
        } else {
          console.log('Invalid step number. Available steps: 0-' + (tourSteps.length - 1));
        }
      };
      
      // Original test function to manually trigger navigation
      window.testPanoramaNavigation = function(pathId, pointIndex, floorNumber) {
        console.log('🧪 TEST: Manual navigation triggered');
        highlightPanoramaMarker(pathId, pointIndex, floorNumber);
      };

      // Enhanced test functions for smooth navigation
      window.testSmoothFloorNavigation = function(targetFloor, pathId, pointIndex) {
        console.log('🧪 Testing smooth floor navigation:', {targetFloor, pathId, pointIndex});
        switchToFloorSmooth(targetFloor, pathId || 'path1', pointIndex || 1);
      };

      window.testSmoothMarkerHighlight = function(pathId, pointIndex, floorNumber) {
        console.log('🧪 Testing smooth marker highlight:', {pathId, pointIndex, floorNumber});
        highlightPanoramaMarkerSmooth(pathId || 'path1', pointIndex || 1, floorNumber || 1);
      };

      window.testHotspotNavigation = function(pathId, pointIndex, floorNumber) {
        console.log('🧪 Testing hotspot navigation message:', {pathId, pointIndex, floorNumber});
        
        const testEvent = {
          data: {
            type: 'panoramaNavigation',
            targetPathId: pathId || 'path2',
            targetPointIndex: pointIndex || 2,
            targetFloorNumber: floorNumber || 2,
            source: 'testHotspot',
            updateURL: true,
            highlightCamera: true
          }
        };
        
        window.dispatchEvent(new MessageEvent('message', testEvent));
      };
      
      // ===== CLEANUP AND ERROR PREVENTION =====
      
      // Add cleanup on page unload to prevent memory leaks and matrix corruption
      window.addEventListener('beforeunload', () => {
        // Clear stability check interval
        if (window.svgStabilityCheckInterval) {
          clearInterval(window.svgStabilityCheckInterval);
          window.svgStabilityCheckInterval = null;
        }
        
        // Properly destroy pan-zoom instance
        if (window.svgPanZoomInstance) {
          try {
            window.svgPanZoomInstance.destroy();
          } catch (e) {
            console.warn("Error during cleanup:", e.message);
          }
          window.svgPanZoomInstance = null;
        }
        
        // Clear transition flag
        window.isFloorTransitioning = false;
      });
      
      // Initialize transition flag on page load
      window.isFloorTransitioning = false;
    </script>
  </body>
</html>
