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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes" />
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
      }
      
      /* Override any Tailwind classes that might interfere */
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
      
      @keyframes pathPulse {
        0% { stroke-opacity: 1; }
        50% { stroke-opacity: 0.6; }
        100% { stroke-opacity: 1; }
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
            <option value="lobby">Lobby (Main Entrance)</option>
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
    <script src="../pathfinding.js"></script> <!-- Add pathfinding functionality -->
    <script>
      // Make PHP-derived data available globally first
      const officesData = <?php echo json_encode($offices); ?>;
      const highlightOfficeIdFromPHP = <?php echo json_encode($highlight_office_id); ?>;
      console.log("Offices Data Loaded (explore.php - global init):", officesData ? officesData.length : 0, "offices");
      console.log("Office to highlight from QR (ID - global init):", highlightOfficeIdFromPHP);

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
            
            // Refresh SVG view
            window.svgPanZoomInstance.resize();
            window.svgPanZoomInstance.fit();
            window.svgPanZoomInstance.center();
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

      // Global pathfinding functions
      window.findPath = function(startLocation, endLocation) {
        console.log('Finding path from', startLocation, 'to', endLocation);
        
        if (!window.floorGraph || !window.floorGraph.rooms) {
          console.error('Floor graph not loaded');
          return null;
        }
        
        const startRoom = window.floorGraph.rooms[startLocation];
        const endRoom = window.floorGraph.rooms[endLocation];
        
        if (!startRoom || !endRoom) {
          console.error('Start or end room not found in graph');
          return null;
        }
        
        // Use A* algorithm from pathfinding.js
        if (typeof aStar === 'function') {
          return aStar(startLocation, endLocation);
        } else {
          console.error('A* algorithm not available');
          return null;
        }
      };

      window.clearPath = function() {
        console.log('Clearing paths');
        if (typeof clearAllPaths === 'function') {
          clearAllPaths();
        }
        
        // Also clear any room highlights
        document.querySelectorAll('.path-highlight').forEach(el => {
          el.classList.remove('path-highlight');
        });
        document.querySelectorAll('.you-are-here').forEach(el => {
          el.classList.remove('you-are-here');
        });
      };

      window.highlightPath = function(path) {
        console.log('Highlighting path:', path);
        if (typeof highlightPath === 'function') {
          highlightPath(path);
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
          
          // Add "YOU ARE HERE" label
          const svg = document.querySelector('svg');
          if (svg) {
            // Remove existing "you are here" labels
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
            label.textContent = "YOU ARE HERE";
            svg.appendChild(label);
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
                svg.appendChild(textEl);
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
      }      // Floor map configuration
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
          
          // Ensure SVG has proper attributes for full container fill
          if (svg) {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
              svg.style.width = '100vw';
              svg.style.height = `${window.innerHeight - 120}px`;
            } else {
              svg.style.width = '100%';
              svg.style.height = '100%';
            }
            
            svg.style.display = 'block';
            svg.removeAttribute('width');
            svg.removeAttribute('height');
            
            // Set viewBox if not present to ensure proper scaling
            if (!svg.getAttribute('viewBox')) {
              const bbox = svg.getBBox();
              svg.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
            }
            
            // Ensure preserveAspectRatio is set for mobile
            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
          }
          
          // Load floor graph for pathfinding
          if (graphData) {
            window.floorGraph = graphData;
            console.log(`Floor ${floorNumber} navigation graph loaded:`, graphData);
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
          
          // Initialize SVG with a short delay to ensure DOM is ready
          setTimeout(() => {
            initializeSVG(svg);
            
            // Force a proper resize after everything is loaded
            setTimeout(() => {
              if (window.svgPanZoomInstance) {
                window.svgPanZoomInstance.resize();
                window.svgPanZoomInstance.fit();
                window.svgPanZoomInstance.center();
              }
            }, 200);
            
            // Initialize pathfinding system only if graph data is available
            if (graphData && typeof initPathfinding === 'function') {
              initPathfinding(floorNumber);
            } else {
              console.log(`Pathfinding disabled for floor ${floorNumber} - no graph data available`);
            }
            
            // Dispatch custom event for pathfinding initialization
            window.dispatchEvent(new CustomEvent('floorMapLoaded', { 
              detail: { floor: floorNumber, graphData: graphData } 
            }));
          }, 100);
          
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
          
          // Add click handler directly to the path
          el.addEventListener('click', function(e) {
            e.stopPropagation();
            // Match by exact room ID (floor-specific)
            const office = officesData.find(o => o.location === el.id);
            if (office) {
              handleRoomClick(office);
            }
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
          
          // Ensure SVG has valid viewBox before initializing
          if (!svg.getAttribute('viewBox')) {
            try {
              const bbox = svg.getBBox();
              if (bbox && !isNaN(bbox.x) && !isNaN(bbox.y) && bbox.width > 0 && bbox.height > 0) {
                svg.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
              } else {
                // Fallback viewBox
                svg.setAttribute('viewBox', '0 0 1000 800');
              }
            } catch (e) {
              console.warn("Could not get SVG bbox, using fallback viewBox");
              svg.setAttribute('viewBox', '0 0 1000 800');
            }
          }
          
          const panZoomInstance = svgPanZoom(svg, {
            zoomEnabled: true,
            controlIconsEnabled: true, // Always show zoom controls
            fit: true,
            center: true,
            minZoom: isMobile ? 0.2 : 0.3,
            maxZoom: isMobile ? 8 : 10,
            zoomScaleSensitivity: isMobile ? 0.3 : 0.5,
            dblClickZoomEnabled: true,
            preventMouseEventsDefault: true,
            touchEnabled: true,
            contain: false,
            beforePan: function(oldPan, newPan) {
              // Simple validation to prevent NaN values
              if (isNaN(newPan.x) || isNaN(newPan.y)) {
                console.warn("Prevented NaN pan values:", newPan);
                return oldPan;
              }
              return newPan;
            },
            customEventsHandler: {
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

                // Handle pinch with improved scaling
                hammer.on('pinchstart', function(ev) {
                  initialScale = instance.getZoom();
                  ev.srcEvent.stopPropagation();
                  ev.srcEvent.preventDefault();
                });

                hammer.on('pinch', function(ev) {
                  ev.srcEvent.stopPropagation();
                  ev.srcEvent.preventDefault();
                  
                  // Apply smooth scaling
                  requestAnimationFrame(() => {
                    const center = {
                      x: ev.center.x,
                      y: ev.center.y
                    };
                    const newScale = initialScale * ev.scale;
                    instance.zoomAtPoint(newScale, center);
                  });
                });

                // Clean up function
                this.destroy = function() {
                  hammer.destroy();
                };
              }
            }
          });

          console.log("svg-pan-zoom initialized successfully");
          console.log("Control icons enabled:", true);
          console.log("Pan-zoom instance created:", panZoomInstance);
          window.svgPanZoomInstance = panZoomInstance;
          window.panZoom = panZoomInstance;

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

          // Force immediate resize and fit
          setTimeout(() => {
            panZoomInstance.resize();
            panZoomInstance.fit();
            panZoomInstance.center();
            
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
            }, 300); // Longer delay to ensure controls are rendered
          }, 50);

          // Remove any existing resize listener
          if (window.panZoomResizeHandler) {
            window.removeEventListener("resize", window.panZoomResizeHandler);
          }

          // Create new resize handler
          window.panZoomResizeHandler = () => {
            if (window.svgPanZoomInstance) {
              requestAnimationFrame(() => {
                try {
                  // Ensure container sizing is correct
                  const svgContainer = document.getElementById('svg-container');
                  const svg = document.querySelector('svg');
                  
                  if (svgContainer && svg) {
                    // Force container to full size
                    svgContainer.style.width = '100%';
                    svgContainer.style.height = '100%';
                    
                    // Force SVG to full size
                    svg.style.width = '100%';
                    svg.style.height = '100%';
                  }
                  
                  window.svgPanZoomInstance.resize();
                  window.svgPanZoomInstance.fit();
                  window.svgPanZoomInstance.center();
                } catch (e) {
                  console.warn("Failed to resize SVG pan-zoom:", e);
                }
              });
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
            // Prevent bounce scrolling on iOS
            document.addEventListener('touchmove', function(e) {
              if (e.target.closest('.svg-container')) {
                e.preventDefault();
              }
            }, { passive: false });
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
          panZoomInstance.fit();
          panZoomInstance.center();
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
            
            // Trigger a resize after floor change
            setTimeout(() => {
              if (window.svgPanZoomInstance) {
                refreshSvgContainer();
              }
            }, 300);
            
            // Additional mobile-specific resize for orientation changes
            if (window.innerWidth <= 768) {
              setTimeout(() => {
                const svg = document.querySelector('svg');
                const svgContainer = document.getElementById('svg-container');
                
                if (svg && svgContainer) {
                  svg.style.width = '100vw';
                  svg.style.height = `${window.innerHeight - 120}px`;
                  svgContainer.style.width = '100vw';
                  svgContainer.style.height = `${window.innerHeight - 120}px`;
                  
                  if (window.svgPanZoomInstance) {
                    window.svgPanZoomInstance.resize();
                    window.svgPanZoomInstance.fit();
                    window.svgPanZoomInstance.center();
                  }
                }
              }, 500);
            }
          });
        });

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
      });
    </script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // Get references to elements
        const detailsDrawer = document.getElementById("details-drawer");
        const drawerHandle = document.getElementById("drawer-handle");
        const mainContent = document.querySelector("main.content"); // Get the main content element
        const floorButtons = document.querySelectorAll('.floor-btn');

        // Add click handlers for floor buttons
        // (Floor button handlers are already set up above)

        // Load initial floor (1st floor)
        // (Initial floor loading is already handled above)

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

        console.log("DOM Content Loaded. Checking for highlightOfficeIdFromPHP:", highlightOfficeIdFromPHP);
        console.log("DOM Content Loaded. Checking officesData:", officesData ? `Available with ${officesData.length} items` : "Not available or empty");

        if (highlightOfficeIdFromPHP !== null && typeof officesData !== 'undefined' && officesData && officesData.length > 0) {
            console.log("QR Code: Proceeding to find office with ID:", highlightOfficeIdFromPHP);
            const officeToHighlight = officesData.find(office => Number(office.id) === highlightOfficeIdFromPHP);
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
                console.warn("QR Code: Office ID", highlightOfficeIdFromPHP, "not found in officesData.");
                console.log("Available office IDs in officesData:", officesData.map(o => o.id));
            }
        } else if (highlightOfficeIdFromPHP !== null) {
            console.warn("QR Code: officesData is not defined, empty, or highlightOfficeIdFromPHP is null. Cannot highlight office from QR.");
        }

        // --- SVG Room Click Handler ---
        function setupRoomClickHandlers() {
          // Wait for SVG to be loaded
          const svg = document.getElementById('svg1');
          if (!svg) {
            setTimeout(setupRoomClickHandlers, 100); // Try again shortly
            return;
          }
          // For each office, try to find a matching SVG element by id or data-room-id
          officesData.forEach(function(office) {
            if (!office.location) return;
            // Try id match
            let el = svg.getElementById ? svg.getElementById(office.location) : document.getElementById(office.location);
            // Fallback: querySelector for [data-room-id]
            if (!el) {
              el = svg.querySelector('[data-room-id="' + office.location + '"]');
            }
            if (el) {
              el.style.cursor = 'pointer';
              // Update the room label
              updateRoomLabel(el, office.name);
              el.addEventListener('click', function(e) {
                e.stopPropagation();
                handleRoomClick(office);
              });
              // Optional: highlight on hover
              el.addEventListener('mouseenter', function() { el.style.opacity = 0.7; });
              el.addEventListener('mouseleave', function() { el.style.opacity = ''; });
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
          startLocationSelect.innerHTML = '';
          endLocationSelect.innerHTML = '';
          
          // Add "Main Entrance" as default start option
          const mainEntranceOption = document.createElement('option');
          mainEntranceOption.value = 'room-1'; // Assuming room-1 is main entrance
          mainEntranceOption.textContent = 'Main Entrance (Lobby)';
          mainEntranceOption.selected = true;
          startLocationSelect.appendChild(mainEntranceOption);
          
          // Add all offices on current floor to both dropdowns
          if (officesData) {
            officesData.forEach(office => {
              if (office.location) {
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
          startLocationSelect.innerHTML = '';
          endLocationSelect.innerHTML = '<option value="">Select destination...</option>';
          
          // If user came from QR code, set their current location as default start
          let defaultStartLocation = null;
          let defaultStartText = 'Lobby (Main Entrance)';
          
          if (window.currentSelectedOffice && window.currentSelectedOffice.location && highlightOfficeIdFromPHP) {
            defaultStartLocation = window.currentSelectedOffice.location;
            defaultStartText = window.currentSelectedOffice.name + ' (YOU ARE HERE)';
          }
          
          // Add default start option
          const defaultStart = document.createElement('option');
          defaultStart.value = defaultStartLocation || 'lobby';
          defaultStart.textContent = defaultStartText;
          defaultStart.selected = true;
          startLocationSelect.appendChild(defaultStart);
          
          // Add lobby option to end dropdown too
          const lobbyEnd = document.createElement('option');
          lobbyEnd.value = 'lobby';
          lobbyEnd.textContent = 'Lobby (Main Entrance)';
          endLocationSelect.appendChild(lobbyEnd);
          
          // Add all available offices on current floor to both dropdowns
          officesData.forEach(office => {
            if (office.location.includes(`-${currentFloor}`)) { // Only show offices on current floor
              // Add to "From" dropdown (but not if it's already the default)
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
              if (window.currentSelectedOffice && office.location === window.currentSelectedOffice.location && !highlightOfficeIdFromPHP) {
                endOption.selected = true;
              }
              endLocationSelect.appendChild(endOption);
            }
          });
          
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
          
          console.log(`Finding path from ${startLocation} to ${endLocation} on floor ${currentFloor}`);
          
          if (window.floorGraph && window.floorGraph.rooms) {
            try {
              // Clear any existing paths
              if (window.clearPath) {
                window.clearPath();
              }
              
              // Get room data
              const startRoom = window.floorGraph.rooms[startLocation];
              const endRoom = window.floorGraph.rooms[endLocation];
              
              if (!startRoom || !endRoom) {
                alert('Selected rooms not found on current floor');
                return;
              }
              
              // Use the pathfinding system from pathfinding.js
              if (typeof aStar === 'function') {
                const path = aStar(startLocation, endLocation);
                if (path && path.length > 0) {
                  // Highlight the path
                  if (typeof highlightPath === 'function') {
                    highlightPath(path);
                  } else {
                    // Fallback highlighting
                    path.forEach(roomId => {
                      const roomEl = document.getElementById(roomId);
                      if (roomEl) {
                        roomEl.classList.add('path-highlight');
                      }
                    });
                  }
                  
                  // Close modal and show success
                  document.getElementById('pathfinding-modal-overlay').style.display = 'none';
                  alert(`Directions found! Path highlighted on the map.`);
                } else {
                  alert('No path found between these locations.');
                }
              } else {
                alert('Pathfinding algorithm not loaded. Please refresh and try again.');
              }
            } catch (error) {
              console.error('Pathfinding error:', error);
              alert('Error finding directions: ' + error.message);
            }
          } else {
            alert('Navigation system not loaded. Please wait and try again.');
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
    <script>
      // Enhanced room click handler function
      function setupRoomClickHandlers(svg) {
        console.log("Setting up room click handlers...");
        
        // Delegate click events to the SVG container
        svg.addEventListener('click', function(event) {
          console.log("SVG click detected");
          
          // Find the clicked room element
          let target = event.target;
          while (target && target !== svg) {
            if (target.matches('path[id^="room-"]')) {
              console.log("Room path clicked:", target.id);
              event.stopPropagation();
              
              // Match by exact room ID (floor-specific)
              const office = officesData.find(o => o.location === target.id);
              if (office) {
                console.log("Found matching office:", office.name);
                handleRoomClick(office);
              }
              break;
            }
            target = target.parentElement;
          }
        });

        // Add touch event handling
        const hammer = new Hammer(svg);
        hammer.on('tap', function(ev) {
          console.log("Tap detected");
          const element = document.elementFromPoint(ev.center.x, ev.center.y);
          if (element) {
            const roomPath = element.closest('path[id^="room-"]') || 
                           element.querySelector('path[id^="room-"]') ||
                           (element.parentElement && element.parentElement.querySelector('path[id^="room-"]'));
            
            if (roomPath) {
              console.log("Room path found on tap:", roomPath.id);
              // Match by exact room ID (floor-specific)
              const office = officesData.find(o => o.location === roomPath.id);
              if (office) {
                console.log("Found matching office on tap:", office.name);
                handleRoomClick(office);
              }
            }
          }
        });
      }

      // Update the SVG initialization to use the new click handler
      const originalInitializeSVG = initializeSVG;
      initializeSVG = function(svg) {
        if (!svg) return;
        console.log("Initializing SVG with enhanced click handling...");
        
        originalInitializeSVG(svg);
        setupRoomClickHandlers(svg);
      };
    </script>
  </body>
</html>
