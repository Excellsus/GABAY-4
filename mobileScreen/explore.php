<?php
// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Go up one directory to include connect_db.php from the parent folder
include __DIR__ . '/../connect_db.php'; // Include database connection

$offices = []; // Initialize as empty array
$error_message_php = null; // Variable for PHP errors

$highlight_office_id = null; // This will store the office ID from the URL if provided
if (isset($_GET['office_id']) && is_numeric($_GET['office_id'])) {
    $highlight_office_id = (int)$_GET['office_id'];
}

// --- Log QR Scan if office_id is present in URL ---
if ($highlight_office_id !== null && isset($connect) && $connect) {
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
            error_log("QR Scan logged for office_id: $highlight_office_id, qr_code_info_id: $qr_code_info_id");
        } else {
            error_log("QR Scan attempt for office_id: $highlight_office_id, but no corresponding qrcode_info record found.");
        }
    } catch (PDOException $e) {
        error_log("Error logging QR scan: " . $e->getMessage());
    }
}
// --- End Log QR Scan ---


try {
    // Check if $connect is a valid PDO object
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection object (\$connect) is not valid. Check connect_db.php.");
    }

    // Fetch all office data, including status and office hours for the current day
    $current_day = date('l'); // Gets current day name (Monday, Tuesday, etc.)
    $stmt = $connect->query("SELECT o.id, o.name, o.details, o.contact, o.location, o.status, 
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
            <h2 class="section-title">Explore</h2>
            <p class="section-subtitle">Interactive Floor Plan</p>
        </div>
        <!-- Placeholder for potential future actions -->
        <div class="header-actions"></div>
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
        
        <!-- DEBUG: Test Panorama Button -->
        <div style="position: absolute; top: 80px; right: 10px; z-index: 1001;">
          <button onclick="showPanoramaSplitScreen('path1', 5, 1)" style="padding: 8px 12px; background: #ff4444; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
            TEST PANORAMA
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
      .room-label {
        fill: white;
        stroke: black;
        stroke-width: 0.5px;
        font-weight: bold;
        pointer-events: none;
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
      
      .panorama-marker:hover .camera-bg {
        /* Removed glow effect */
      }
      
      .panorama-marker.active .camera-bg {
        /* Removed glow effect */
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
            <button id="explore-btn" class="drawer-button">
              <i class="fas fa-location-arrow"></i> Explore
            </button>
            <button id="directions-btn" class="drawer-button" style="background: #1a5632;">
              <i class="fas fa-route"></i> Get Directions
            </button>
            <button id="navigate-here-btn" class="drawer-button" style="background: #2196f3;">
              <i class="fas fa-navigation"></i> Navigate Here
            </button>
            <button id="details-btn" class="drawer-button">
              <i class="fas fa-info-circle"></i> More Info
            </button>
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

    <!-- Floor Plan Modal for Explore Button -->
    <div id="explore-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:3000; align-items:center; justify-content:center;">
      <div style="position:relative; width:95vw; max-width:900px; height:80vh; background:#fff; border-radius:16px; box-shadow:0 4px 24px #0002; display:flex; flex-direction:column;">
        <button id="close-explore-modal" style="position:absolute; top:10px; right:10px; font-size:28px; background:none; border:none; cursor:pointer; z-index:10;">&times;</button>
        <iframe id="explore-map-frame" src="../floorPlan.php?selectRoom=1" style="width:100%; height:100%; border:none; border-radius:16px;"></iframe>
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
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 id="panel-office-name" class="modal-title">Office Name</h3>
                <button id="close-panel-btn" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="panel-section">
                    <h4>Status</h4>
                    <label class="switch">
                        <input type="checkbox" id="office-active-toggle">
                        <span class="slider round"></span>
                    </label>
                    <span id="office-status-text">Active</span>
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

    <!-- Bottom Navigation Section -->
    <nav class="bottom-nav" role="navigation" aria-label="Visitor navigation">
      <!-- Explore Link -->
      <a href="explore.php" class="active" aria-label="Explore">
        <i class="fas fa-map-marker-alt"></i>
        <span>Explore</span>
      </a>

      <!-- Rooms Link -->
      <a href="rooms.php" aria-label="Rooms">
        <i class="fas fa-building"></i>
        <span>Rooms</span>
      </a>

      <!-- About Link -->
      <a href="about.php" aria-label="About">
        <i class="fas fa-bars"></i>
        <span>About</span>
      </a>
    </nav>

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
      // Make PHP-derived data available globally first
      const officesData = <?php echo json_encode($offices); ?>;
      const highlightOfficeIdFromPHP = <?php echo json_encode($highlight_office_id); ?>;
      console.log("Offices Data Loaded (explore.php - global init):", officesData ? officesData.length : 0, "offices");
      console.log("Office to highlight from QR (ID - global init):", highlightOfficeIdFromPHP);

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

        // Update the panorama viewer to use an iframe of pano.html with dynamic parameters
        const panoramaViewer = splitScreen.querySelector('.panorama-viewer');
        if (panoramaViewer) {
          const panoUrl = `../Pano/pano.html?path_id=${encodeURIComponent(pathId)}&point_index=${encodeURIComponent(pointIndex)}&floor_number=${encodeURIComponent(floorNumber)}`;
          panoramaViewer.innerHTML = `
            <iframe 
              src="${panoUrl}" 
              style="width: 100%; height: 100%; border: none;"
              allowfullscreen>
            </iframe>
          `;
          console.log('✅ Panorama iframe loaded with URL:', panoUrl);
        } else {
          console.error('❌ Panorama viewer element (.panorama-viewer) not found');
        }

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

        // Move the real SVG element into the split-screen map container so svgPanZoom instance remains attached
        const mapContainer = document.getElementById('split-map-container');
        const svgContainer = document.getElementById('svg-container');
        const svg = svgContainer?.querySelector('svg');

        if (svg && mapContainer && svgContainer) {
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

          // Resize svg-pan-zoom and reapply saved pan/zoom (avoid fit/center to prevent jumps)
          setTimeout(() => {
            if (window.svgPanZoomInstance) {
              try {
                window.svgPanZoomInstance.resize();
                window.svgPanZoomInstance.center(); // Recenter after resize
                setTimeout(() => {
                  const saved = window.__svgPanZoomStateBeforePanorama;
                  if (saved) {
                    try {
                      // We only restore zoom, not pan, to keep it centered
                      if (typeof window.svgPanZoomInstance.zoom === 'function') window.svgPanZoomInstance.zoom(saved.zoom);
                      console.log('Reapplied svgPanZoom zoom after move');
                    } catch (err) {
                      console.warn('Failed to reapply svgPanZoom zoom after move:', err);
                    }
                  }
                }, 20);
              } catch (e) {
                console.warn('Failed to resize/center svgPanZoomInstance after moving SVG:', e);
              }
            }
          }, 50);
        } else {
          console.warn('SVG or containers missing, cannot move SVG. svg:', !!svg, 'mapContainer:', !!mapContainer, 'svgContainer:', !!svgContainer);
        }

        // Show the split screen overlay
        splitScreen.classList.remove('hidden');

        console.log('🎉 PANORAMA SPLIT SCREEN VISIBLE — map should remain interactive');
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
        const oldDoorGroup = panZoomViewport.querySelector('#door-points-group');
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

        // Draw door points if available
        if (window.floorGraph.rooms) {
          console.log('Drawing door points for', Object.keys(window.floorGraph.rooms).length, 'rooms');
          drawDoorPoints(window.floorGraph.rooms);
        }

        // Initialize pathfinding room selection now that graph data is loaded
        if (typeof window.initRoomSelection === 'function') {
          console.log('Initializing pathfinding room selection handlers');
          window.initRoomSelection();
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

        console.log('Path and door drawing completed');
        
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
        
        // Update details button click handler with current office ID
        if (detailsBtn) {
          detailsBtn.onclick = function() {
            window.location.href = `office_details.php?id=${office.id}`;
          };
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
            const bg = m.querySelector('.camera-bg') || m.querySelector('circle');
            if (bg) {
              bg.setAttribute('fill', '#2563eb');
              bg.setAttribute('r', '12');
            }
            const icon = m.querySelector('.camera-icon');
            if (icon) {
              icon.setAttribute('fill', '#ffffff');
            }
          });
        } catch (e) {
          console.warn('Error resetting panorama markers:', e);
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
              // Create camera icon group
              const marker = document.createElementNS(svgNS, 'g');
              marker.classList.add('panorama-marker');
              marker.style.cursor = 'pointer';
              
              // Create background circle
              const bgCircle = document.createElementNS(svgNS, 'circle');
              bgCircle.setAttribute('cx', point.x);
              bgCircle.setAttribute('cy', point.y);
              bgCircle.setAttribute('r', '12');
              bgCircle.setAttribute('fill', '#2563eb');
              bgCircle.setAttribute('stroke', '#ffffff');
              bgCircle.setAttribute('stroke-width', '2');
              bgCircle.setAttribute('class', 'camera-bg');
              
              // Create camera icon
              const cameraIcon = document.createElementNS(svgNS, 'path');
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
            }
          });
        }
        
        console.log(`Path ${path.id} drawn successfully in viewport group`);
      }

      // "YOU ARE HERE" functionality

      // Function to draw door points
      function drawDoorPoints(rooms) {
        console.log('Drawing door points for rooms:', Object.keys(rooms).length);
        const svg = document.querySelector('svg');
        if (!svg) {
          console.warn('Cannot draw door points - no SVG found');
          return;
        }

        let mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
        if (!mainGroup) {
          console.warn('No viewport group found, creating new main group');
          mainGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
          svg.appendChild(mainGroup);
        }

        // Create or get the door points group
        let doorGroup = mainGroup.querySelector('#door-points-group');
        if (!doorGroup) {
          doorGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
          doorGroup.id = 'door-points-group';
          mainGroup.appendChild(doorGroup);
          console.log('Created door points group in:', mainGroup.classList || mainGroup.tagName);
        }

        Object.keys(rooms).forEach(roomId => {
          const room = rooms[roomId];
          if (room.doorPoints && Array.isArray(room.doorPoints)) {
            room.doorPoints.forEach((doorPoint, index) => {
              const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
              circle.id = `door-${roomId}-${index}`;
              circle.setAttribute('cx', doorPoint.x);
              circle.setAttribute('cy', doorPoint.y);
              circle.setAttribute('r', room.style?.pointMarker?.radius || 6);
              circle.setAttribute('fill', room.style?.pointMarker?.color || '#FF6B35');
              circle.setAttribute('stroke', room.style?.pointMarker?.strokeColor || '#000');
              circle.setAttribute('stroke-width', room.style?.pointMarker?.strokeWidth || 1);
              circle.setAttribute('vector-effect', 'non-scaling-stroke');
              circle.classList.add('door-point');
              
              doorGroup.appendChild(circle);
            });
            console.log(`Door points for room ${roomId} drawn successfully`);
          }
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
              
              if (currentRoom && nextRoom && currentRoom.doorPoints && nextRoom.doorPoints) {
                const start = currentRoom.doorPoints[0];
                const end = nextRoom.doorPoints[0];
                
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

      // Function to update room label
      function updateRoomLabel(group, officeName) {
        const roomPath = group.querySelector("path");
        if (!roomPath || !roomPath.id) return;
        
        const match = roomPath.id.match(/room-(\d+)(-\d+)?/);
        if (!match) return;
        
        const roomNumber = match[1];
        const fullRoomId = match[0]; // e.g., "room-1-2"
        
        // Look for existing roomlabel by room number (works for 1st floor: room-1-1 -> roomlabel-1)
        let labelId = `roomlabel-${roomNumber}`;
        let tspanEl = document.querySelector(`#${labelId}`);
        let textEl = null;
        
        // If not found and this is 2nd floor, look for labels containing the room number in their text
        if (!tspanEl && fullRoomId.includes('-2')) {
            // Find tspan elements that contain "Room" + the room number
            const targetText = `Room${roomNumber}`;
            const allTspans = document.querySelectorAll('tspan[id*="roomlabel"]');
            for (let tspan of allTspans) {
                if (tspan.textContent && tspan.textContent.trim() === targetText) {
                    tspanEl = tspan;
                    labelId = tspan.id;
                    break;
                }
            }
        }
        
        if (tspanEl && tspanEl.tagName === 'tspan') {
            // Found existing tspan, get its parent text element
            textEl = tspanEl.parentElement;
        } else {
            // Look for existing text element within the group first (more reliable)
            textEl = group.querySelector("text");

            // If no text in group, try to find by roomlabel ID pattern (1st floor pattern)
            if (!textEl) {
                textEl = document.querySelector(`#${labelId}`);
            }
        }

        if (!textEl) {
            // Create text element if it doesn't exist
            textEl = document.createElementNS("http://www.w3.org/2000/svg", "text");
            textEl.setAttribute("class", "room-label");
            textEl.setAttribute("id", labelId);
            
            const bbox = roomPath.getBBox();
            textEl.setAttribute("x", bbox.x + bbox.width / 2);
            textEl.setAttribute("y", bbox.y + bbox.height / 2);
            
            const svg = group.closest('svg');
            if (svg) {
                // Add to the viewport group so it transforms with pan/zoom
                const mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g') || svg;
                mainGroup.appendChild(textEl);
            } else {
                group.appendChild(textEl);
            }
        }
        
        // Store original x coordinate for centering
        const originalX = parseFloat(textEl.getAttribute("x")) || 0;

        // Set text-anchor to middle for automatic centering
        textEl.setAttribute("text-anchor", "middle");

        // Clear existing content
        textEl.textContent = "";
        while (textEl.firstChild) {
            textEl.removeChild(textEl.firstChild);
        }

        const lineHeight = "1.2em";
        const words = officeName.split(" ");

        if (words.length > 0) {
            words.forEach((word, index) => {
                const newTspan = document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "tspan"
                );
                newTspan.textContent = word;
                newTspan.setAttribute("x", originalX); // Set x for each tspan
                if (index > 0) {
                    newTspan.setAttribute("dy", lineHeight);
                }
                textEl.appendChild(newTspan);
            });
        }
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
      
      // Track current floor
      let currentFloor = 1;

      // Function to load SVG for a specific floor
      function loadFloorMap(floorNumber) {
        console.log(`Loading floor ${floorNumber} map...`);
        currentFloor = floorNumber; // Track the current floor
        
        // Load both SVG and floor graph for pathfinding
        Promise.all([
          fetch(floorMaps[floorNumber]).then(response => {
            if (!response.ok) throw new Error(`SVG fetch failed: ${response.status}`);
            return response.text();
          }),
          fetch(floorGraphs[floorNumber]).then(response => {
            if (!response.ok) throw new Error(`Floor graph fetch failed: ${response.status}`);
            return response.json();
          }).catch(error => {
            console.warn(`Floor graph for floor ${floorNumber} not available:`, error.message);
            return null; // Return null instead of failing completely
          })
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
            
            // Add periodic stability check for mobile
            setInterval(() => {
              if (window.svgPanZoomInstance) {
                const currentZoom = window.svgPanZoomInstance.getZoom();
                const currentPan = window.svgPanZoomInstance.getPan();
                
                // Check for invalid states and fix them
                if (isNaN(currentZoom) || currentZoom <= 0 || currentZoom > 50) {
                  console.warn("Invalid zoom detected, resetting...");
                  window.svgPanZoomInstance.reset();
                  window.svgPanZoomInstance.fit();
                  window.svgPanZoomInstance.center();
                }
                
                if (isNaN(currentPan.x) || isNaN(currentPan.y)) {
                  console.warn("Invalid pan detected, centering...");
                  window.svgPanZoomInstance.center();
                }
              }
            }, 2000); // Check every 2 seconds
          }
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

        // Load initial floor (1st floor)
        loadFloorMap(1);
        
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

        // Explore button logic
        document.getElementById('explore-btn').onclick = function() {
          document.getElementById('explore-modal-overlay').style.display = 'flex';
        };
        document.getElementById('close-explore-modal').onclick = function() {
          document.getElementById('explore-modal-overlay').style.display = 'none';
        };

        // Navigate Here button logic - quickly navigate to selected room
        document.getElementById('navigate-here-btn').onclick = function() {
          if (!window.currentSelectedOffice || !window.currentSelectedOffice.location) {
            alert('Please select a room first');
            return;
          }
          
          // Open pathfinding modal with destination pre-selected
          const startLocationSelect = document.getElementById('start-location');
          const endLocationSelect = document.getElementById('end-location');
          
          // Clear and populate dropdowns
          startLocationSelect.innerHTML = '<option value="">Select starting point...</option>';
          endLocationSelect.innerHTML = '';
          
          // Add all offices on current floor to both dropdowns
          if (officesData) {
            let validOfficesCount = 0;
            
            officesData.forEach(office => {
              if (office.location && office.location.includes(`-${currentFloor}`) && 
                  window.floorGraph && window.floorGraph.rooms && window.floorGraph.rooms[office.location]) {
                validOfficesCount++;
                
                // Add to start dropdown
                const startOption = document.createElement('option');
                startOption.value = office.location;
                startOption.textContent = office.name;
                startLocationSelect.appendChild(startOption);
                
                // Add to end dropdown
                const endOption = document.createElement('option');
                endOption.value = office.location;
                endOption.textContent = office.name;
                // Pre-select the current office as destination
                if (office.location === window.currentSelectedOffice.location) {
                  endOption.selected = true;
                }
                endLocationSelect.appendChild(endOption);
              }
            });
            
            // If no valid offices found, add floor graph rooms as backup
            if (validOfficesCount === 0 && window.floorGraph && window.floorGraph.rooms) {
              Object.keys(window.floorGraph.rooms).forEach(roomId => {
                if (roomId.includes(`-${currentFloor}`)) {
                  const startOption = document.createElement('option');
                  startOption.value = roomId;
                  startOption.textContent = `Room ${roomId}`;
                  startLocationSelect.appendChild(startOption);
                  
                  const endOption = document.createElement('option');
                  endOption.value = roomId;
                  endOption.textContent = `Room ${roomId}`;
                  if (roomId === window.currentSelectedOffice.location) {
                    endOption.selected = true;
                  }
                  endLocationSelect.appendChild(endOption);
                }
              });
            }
          }
          
          // Show pathfinding modal
          document.getElementById('pathfinding-modal-overlay').style.display = 'flex';
        };

        // Directions button logic - open pathfinding modal
        document.getElementById('directions-btn').onclick = function() {
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
          
          let validOfficesCount = 0;
          
          officesData.forEach(office => {
            console.log('Checking office:', office.name, 'location:', office.location, 'includes floor:', office.location && office.location.includes(`-${currentFloor}`));
            
            if (office.location && office.location.includes(`-${currentFloor}`)) { // Only show offices on current floor
              // Verify this location exists in the floor graph
              const roomExists = window.floorGraph && window.floorGraph.rooms && window.floorGraph.rooms[office.location];
              console.log('Room exists in floor graph:', office.location, roomExists);
              
              if (roomExists) {
                validOfficesCount++;
                
                // Add to "From" dropdown (but not if it's already the default from QR code)
                if (office.location !== defaultStartLocation) {
                  const startOption = document.createElement('option');
                  startOption.value = office.location;
                  startOption.textContent = office.name;
                  startLocationSelect.appendChild(startOption);
                }
                
                // Add to "To" dropdown
                const endOption = document.createElement('option');
                endOption.value = office.location;
                endOption.textContent = office.name;
                // Pre-select if this is the currently selected office but not from QR code
                if (window.currentSelectedOffice && office.location === window.currentSelectedOffice.location && !window.highlightOfficeIdFromPHP) {
                  endOption.selected = true;
                }
                endLocationSelect.appendChild(endOption);
              } else {
                console.warn('Office location not found in floor graph:', office.location, 'for office:', office.name);
              }
            }
          });
          
          // If no valid offices found, add all rooms from floor graph as backup
          if (validOfficesCount === 0 && window.floorGraph && window.floorGraph.rooms) {
            console.log('No valid offices found, adding all floor graph rooms as options');
            Object.keys(window.floorGraph.rooms).forEach(roomId => {
              if (roomId.includes(`-${currentFloor}`)) {
                const startOption = document.createElement('option');
                startOption.value = roomId;
                startOption.textContent = `Room ${roomId}`;
                startLocationSelect.appendChild(startOption);
                
                const endOption = document.createElement('option');
                endOption.value = roomId;
                endOption.textContent = `Room ${roomId}`;
                endLocationSelect.appendChild(endOption);
              }
            });
          }
          
          console.log('Added', validOfficesCount, 'valid offices to dropdowns');
          
          // Show pathfinding modal
          document.getElementById('pathfinding-modal-overlay').style.display = 'flex';
        };

        // Pathfinding modal event handlers
        document.getElementById('close-pathfinding-modal').onclick = function() {
          document.getElementById('pathfinding-modal-overlay').style.display = 'none';
        };

        document.getElementById('find-path-btn').onclick = function() {
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
          
          // Critical: Check if floor graph data is loaded before proceeding
          if (!window.floorGraph || !window.floorGraph.rooms) {
            alert('Navigation data is still loading. Please wait a moment and try again.');
            console.error('Floor graph not loaded yet. Cannot proceed with pathfinding.');
            return;
          }
          
          // Verify that the selected rooms exist in the current floor graph
          if (!window.floorGraph.rooms[startLocation] || !window.floorGraph.rooms[endLocation]) {
            console.error('Selected rooms not found in floor graph:', {
              startExists: !!window.floorGraph.rooms[startLocation],
              endExists: !!window.floorGraph.rooms[endLocation],
              availableRooms: Object.keys(window.floorGraph.rooms)
            });
            alert('One or both selected rooms are not available for navigation on this floor. Please try different rooms.');
            return;
          }
          
          console.log(`Get Directions: Finding path from ${startLocation} to ${endLocation}`);
          console.log('Floor graph ready:', !!window.floorGraph, 'with rooms:', Object.keys(window.floorGraph.rooms || {}).length);
          
          // Close the modal immediately
          document.getElementById('pathfinding-modal-overlay').style.display = 'none';
          
          // Clear any existing paths and selections
          if (window.clearAllPaths && typeof window.clearAllPaths === 'function') {
            window.clearAllPaths();
          }
          document.querySelectorAll('.selected-room').forEach(el => {
            el.classList.remove('selected-room');
          });
          
          // Reset the selectedRooms array to simulate desktop pathfinding behavior
          window.selectedRooms = [];
          
          // Get the room elements
          const startRoomElement = document.getElementById(startLocation);
          const endRoomElement = document.getElementById(endLocation);
          
          if (!startRoomElement || !endRoomElement) {
            alert('Selected rooms not found on the map');
            return;
          }
          
          // Use the stored pathfinding handlers with special pathfinding events
          if (startRoomElement._pathfindingHandler && endRoomElement._pathfindingHandler) {
            console.log('Using stored pathfinding handlers...');
            
            // Create special events marked for pathfinding
            const startEvent = new MouseEvent('click', {
              bubbles: true,
              cancelable: true,
              clientX: 0,
              clientY: 0
            });
            startEvent._isPathfindingClick = true;
            
            const endEvent = new MouseEvent('click', {
              bubbles: true,
              cancelable: true,
              clientX: 0,
              clientY: 0
            });
            endEvent._isPathfindingClick = true;
            
            // Call the pathfinding handlers directly
            console.log('Calling pathfinding for start room:', startLocation);
            startRoomElement._pathfindingHandler.call(startRoomElement, startEvent);
            
            // Small delay then handle the end room to complete the path
            setTimeout(() => {
              console.log('Calling pathfinding for end room:', endLocation);
              endRoomElement._pathfindingHandler.call(endRoomElement, endEvent);
              
              // Show success message after pathfinding completes
              setTimeout(() => {
                if (window.selectedRooms && window.selectedRooms.length === 2) {
                  alert('Directions found! Path is highlighted on the map.');
                } else {
                  alert('Pathfinding completed. Check the map for the route.');
                }
              }, 200);
            }, 100);
            
          } else {
            alert('Pathfinding system not ready. Please wait for the map to fully load and try again.');
            console.error('Pathfinding handlers not found on room elements');
          }
        };

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
          document.getElementById('pathfinding-modal-overlay').style.display = 'none';
        };
      });
    </script>
  </body>
</html>
