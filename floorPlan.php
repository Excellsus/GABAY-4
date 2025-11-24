<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect_db.php'; // Include database connection

$offices = []; // Initialize as empty array

try {
    // Check if $connect is a valid PDO object
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection object (\$connect) is not valid. Check connect_db.php.");
    }

    // Fetch all office data
    $stmt = $connect->query("SELECT id, name, details, contact, location, status FROM offices");

    // Check if query execution was successful
    if ($stmt === false) {
        // Query failed, get error info
        $errorInfo = $connect->errorInfo();
        throw new PDOException("Query failed: " . ($errorInfo[2] ?? 'Unknown error - Check table/column names and permissions.'));
    }

    // Fetch the data
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { // Catches PDOException and general Exception
    error_log("Error in floorPlan.php: " . $e->getMessage()); // Log error to PHP error log
    // You could output a message here, but it might break the page structure. Logging is safer.
}

// --- API endpoint: getRoomList ---
if (isset($_GET['getRoomList'])) {
    // Load SVG and extract room groups
    $svgFile = __DIR__ . '/SVG/Capitol_1st_floor_layout_20_modified.svg';
    $rooms = [];
    if (file_exists($svgFile)) {
        $svg = simplexml_load_file($svgFile);
        $svg->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
        // Only look for elements with id starting with "room-"
        foreach ($svg->xpath('//*[starts-with(@id, "room-")]') as $path) {
            $pid = (string)$path['id'];
            if (preg_match('/room-(\d+)-1/', $pid, $m)) {
                // Try to get the label from associated text element
                $label = $pid;
                $text = $svg->xpath('//svg:text[@id="roomlabel-' . $m[1] . '"]');
                if ($text && isset($text[0])) {
                    $label = trim((string)$text[0]);
                }
                $rooms[] = [
                    'id' => $pid,
                    'label' => $label
                ];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($rooms);
    exit;
}
// --- API endpoint: selectRoom mode for iframe ---
if (isset($_GET['selectRoom'])) {
    $floor = $_GET['floor'] ?? '1'; // Default to floor 1 if not specified
    $svgWebPath = '';
    switch ($floor) {
        case '2':
            $svgWebPath = 'SVG/Capitol_2nd_floor_layout_6_modified.svg';
            break;
        case '3':
            $svgWebPath = 'SVG/Capitol_3rd_floor_layout_6.svg';
            break;
        default:
            $svgWebPath = 'SVG/Capitol_1st_floor_layout_20_modified.svg';
            break;
    }
    $svgFile = __DIR__ . '/' . $svgWebPath;

    // Minimal HTML/JS to show SVG and allow clicking a room to select
    ?>
    <!DOCTYPE html>
    <html><head><title>Select Room</title>
    <style>
    svg { width: 100%; height: 100%; } 
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
    .highlighted-room {
      stroke: #43a047 !important;
      stroke-width: 6 !important;
      animation: pulse-green 1.5s ease-in-out infinite;
    }
    @keyframes pulse-green {
      0%, 100% { stroke-width: 6; opacity: 1; }
      50% { stroke-width: 8; opacity: 0.8; }
    }
    </style>
    </head><body style="margin:0;padding:0;">
    <div id="svg-container" style="width: 100%; height: 100%;"></div>
    <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
    <script>
    // Get office data from PHP
    const officesData = <?php echo json_encode($offices); ?>;
    
    // Get the room to highlight (if editing existing office)
    const urlParams = new URLSearchParams(window.location.search);
    const highlightRoomId = urlParams.get('highlightRoom');
    
    // Function to update room label
    // Function to force font application on all SVG text elements
    function applyConsistentFontStyling(container) {
      const textElements = container.querySelectorAll('text, tspan');
      textElements.forEach(el => {
        el.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
        el.style.fontWeight = '600';
        el.style.fontSize = '14px';
        el.style.fill = '#1a1a1a';
        el.style.stroke = '#ffffff';
        el.style.strokeWidth = '3px';
        el.style.strokeLinejoin = 'round';
        el.style.paintOrder = 'stroke fill';
        el.style.textAnchor = 'middle';
        el.style.dominantBaseline = 'central';
        el.style.vectorEffect = 'non-scaling-stroke';
        el.setAttribute('class', 'room-label');
      });
    }

        function labelBelongsToRoom(tspanEl, roomNumber) {
            if (!tspanEl) return false;
            const parentText = tspanEl.closest('text');
            if (!parentText || !parentText.id) return false;
            const parentId = parentText.id.trim();
            if (!parentId.startsWith('text-')) return false;
            if (parentId === `text-${roomNumber}`) return true;
            return parentId.startsWith(`text-${roomNumber}-`);
        }

        function findLabelTspanForRoom(roomNumber) {
            const directMatch = document.getElementById(`roomlabel-${roomNumber}`);
            if (labelBelongsToRoom(directMatch, roomNumber)) {
                return directMatch;
            }

            const textMatches = document.querySelectorAll(`text[id^="text-${roomNumber}"]`);
            for (const textNode of textMatches) {
                const tspanCandidate = textNode.querySelector('tspan');
                if (tspanCandidate) {
                    return tspanCandidate;
                }
            }

            const allLabels = document.querySelectorAll('tspan[id^="roomlabel-"]');
            for (const candidate of allLabels) {
                if (labelBelongsToRoom(candidate, roomNumber)) {
                    return candidate;
                }
            }

            return null;
        }

        function getLabelTextFromGroup(group) {
            if (!group) return '';
            const textEl = group.querySelector('text');
            if (!textEl) return '';
            const tspans = textEl.querySelectorAll('tspan');
            if (tspans.length) {
                return Array.from(tspans)
                    .map(t => (t.textContent || '').trim())
                    .filter(Boolean)
                    .join(' ') || '';
            }
            return (textEl.textContent || '').trim();
        }

    function updateRoomLabelMain(group, officeName) {
      // First try to find existing text element in the SVG by roomlabel ID
      const roomElement = group.querySelector("path, rect");
      if (!roomElement || !roomElement.id) return;
      
            const roomMatch = roomElement.id.match(/room-(\d+)(?:-(\d+))?/);
      if (!roomMatch) return;
      
            const roomNumber = roomMatch[1];
            const fullRoomId = roomMatch[0];
            let labelId = `roomlabel-${roomNumber}`;
            let tspanEl = findLabelTspanForRoom(roomNumber);
            let textEl = null;
            let originalX;
            let originalY;

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
      } else {
        // Look for existing text element within the group first (more reliable)
        textEl = group.querySelector("text");
        if (textEl) {
          originalX = parseFloat(textEl.getAttribute("x"));
          originalY = parseFloat(textEl.getAttribute("y"));
        }
        
        // If no text in group, try to find by roomlabel ID pattern (1st floor pattern)
        if (!textEl) {
          textEl = document.querySelector(`#${labelId}`);
          if (textEl) {
            originalX = parseFloat(textEl.getAttribute("x"));
            originalY = parseFloat(textEl.getAttribute("y"));
          }
        }
      }

      if (!textEl) {
        // Remove any duplicate with same id elsewhere (from previous runs)
        const dup = document.querySelector(`#${labelId}`);
        if (dup) dup.remove();

        // Create text element inside the same group so transforms apply
        textEl = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textEl.setAttribute("class", "room-label");
        textEl.setAttribute("id", labelId);
        
        // Add responsive text class based on name length  
        if (officeName.length > 25) {
          textEl.classList.add("room-label-large");
        } else if (officeName.length > 15) {
          textEl.classList.add("room-label-small");
        }

        // Use room center as fallback if no original coordinates found
        const bbox = roomElement.getBBox();
    originalX = originalX || (bbox.x + bbox.width/2);
    originalY = originalY || (bbox.y + bbox.height/2);
        
        textEl.setAttribute("x", originalX);
        textEl.setAttribute("y", originalY);

        group.appendChild(textEl);
      }

      // Set text-anchor to middle for centering
      textEl.setAttribute("text-anchor", "middle");
      textEl.setAttribute("dominant-baseline", "central");

      // Clear existing content
      textEl.textContent = "";
      while (textEl.firstChild) {
        textEl.removeChild(textEl.firstChild);
      }

      const lineHeight = "1.2em";
      const words = officeName.split(" ");

      // Create tspans for each word to handle multi-line text
      words.forEach((word, index) => {
        const newTspan = document.createElementNS(
          "http://www.w3.org/2000/svg",
          "tspan"
        );
        newTspan.textContent = word;
        // The x attribute must be set on each tspan to align them vertically.
        newTspan.setAttribute("x", originalX); 
        // Move to the next line after the first word.
        if (index > 0) {
          newTspan.setAttribute("dy", lineHeight);
        }
                if (index === 0) {
                    newTspan.setAttribute("id", labelId);
                }
        textEl.appendChild(newTspan);
      });

      // Set final position using preserved coordinates
      textEl.setAttribute("x", originalX);
      textEl.setAttribute("y", originalY);
      
      // Force apply consistent font styling
      applyConsistentFontStyling(group);
    }

    function initializePanZoom(svgElement) {
        if (typeof svgPanZoom !== 'undefined' && svgElement) {
            const panZoomInstance = svgPanZoom(svgElement, {
                zoomEnabled: true,
                controlIconsEnabled: true,
                fit: true,
                center: true,
                minZoom: 0.5,
                maxZoom: 10
            });
            window.addEventListener('resize', () => {
                panZoomInstance.resize();
                panZoomInstance.fit();
                panZoomInstance.center();
            });
        } else {
            console.error('svg-pan-zoom library not loaded or SVG element not found.');
        }
    }

    // Fetch and display the SVG
    fetch('<?php echo $svgWebPath; ?>')
      .then(response => response.text())
      .then(svgData => {
        const container = document.getElementById('svg-container');
        container.innerHTML = svgData;
        const svg = container.querySelector('svg');
        if (!svg) {
            console.error('SVG element not found in fetched data.');
            return;
        }
        svg.id = 'svg1'; // Assign ID for panzoom
        
        // Make room elements clickable and map labels for any floor suffix (-1, -2, -3)
        svg.querySelectorAll('path[id^="room-"]').forEach(function(el) {
          el.classList.add('selectable-room');
          const parentGroup = el.closest('g');
          
          // Find if this room has an office assigned
          if (parentGroup) {
            const roomMatch = el.id.match(/^room-(\d+)(?:-\d+)?$/);
            if (roomMatch) {
              const roomNum = roomMatch[1];
              // Only match offices with the exact room ID (floor-specific)
              const office = officesData.find(o => o.location === el.id);
              if (office) {
                updateRoomLabelMain(parentGroup, office.name);
              }
            }
          }
          
          el.addEventListener('click', function(e) {
            e.stopPropagation();
                        const id = el.id;
                        let label = '';
                        const rm = id.match(/^room-(\d+)(?:-(\d+))?$/);

                        if (rm) {
                            const roomNum = rm[1];
                            const fullRoomId = id;
                            const parentGroup = el.closest('g[id^="room-"]') || el.closest('g');
                            label = getLabelTextFromGroup(parentGroup);

                                            if (!label) {
                                                const office = officesData.find(o => o.location === fullRoomId);
                                                if (office) {
                                                    label = office.name;
                                                } else {
                                                    label = `Room ${roomNum}`;
                                                }
                                            }
                        }

                        if (!label) {
                            label = id;
                        }
            // Highlight selection
            svg.querySelectorAll('.selectable-room').forEach(x=>{
              x.classList.remove('selected');
              x.classList.remove('highlighted-room'); // Remove previous highlight when new room selected
            });
            el.classList.add('selected');
            // Send selection to parent
            window.parent.postMessage({selectedRoomId: id, selectedRoomLabel: label}, '*');
          });
        });
        
        // Highlight the current room if editing existing office
        if (highlightRoomId) {
          const roomToHighlight = svg.querySelector(`[id="${highlightRoomId}"]`);
          if (roomToHighlight) {
            roomToHighlight.classList.add('highlighted-room');
            
            // Pan and zoom to the highlighted room after a short delay
            setTimeout(() => {
              const bbox = roomToHighlight.getBBox();
              const svgRect = svg.getBoundingClientRect();
              const centerX = bbox.x + bbox.width / 2;
              const centerY = bbox.y + bbox.height / 2;
              
              // Scroll into view with some padding
              const padding = 100;
              const viewBox = svg.viewBox.baseVal;
              viewBox.x = centerX - viewBox.width / 2;
              viewBox.y = centerY - viewBox.height / 2;
            }, 500);
          }
        }

        // Initialize Pan and Zoom
        initializePanZoom(svg);
        
        // Force apply consistent font styling to all text elements after SVG load
        setTimeout(() => {
          applyConsistentFontStyling(container);
        }, 100);
      })
      .catch(error => {
        console.error('Error loading or processing SVG:', error);
      });
    </script>
    </body></html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    <title>GABAY Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="floorPlan.css">
    <link rel="stylesheet" href="assets/css/system-fonts.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="mobileNav.css" />
  <script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
  <script src="auth_helper.js"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              "negros-green": "#1A5632",
              "negros-light": "#E8F5E9",
              "negros-dark": "#0D3018",
              "negros-gold": "#FFD700",
            },
          },
        },
      };
    </script>
  </head>
  <body>
    <div  class="container">
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
      <a href="floorPlan.php"  class="active">Floor Plan</a>
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
          <li><a href="officeManagement.php">Office Management</a></li>
          <li><a href="floorPlan.php" class="active">Floor Plans</a></li>
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
      <div class="main-content">
      <header class="header">
        <div>
          <h2>Floor Plan</h2>
          <p>View and manage floor plans</p>
        </div>
        <div class="actions">
          <div class="flex gap-3 items-center">
            <!-- Active Offices Counter -->
            <div id="active-offices-counter" style="padding: 6px 14px; background: #f0f9ff; border: 2px solid #0284c7; border-radius: 12px; font-size: 14px; font-weight: 600; color: #0284c7; display: flex; align-items: center; gap: 6px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
              <span id="active-count">0</span>
              <span style="font-weight: 500;">Active Offices In This Floor</span>
            </div>
            
            <!-- Floor Buttons -->
            <div class="flex gap-2">
              <button class="floor-select-btn active" data-floor="1" 
                style="padding: 4px 12px; border: 1px solid #04aa6d; background: #04aa6d; color: white; 
                border-radius: 12px; font-size: 14px; font-weight: 500; min-width: 40px; transition: all 0.2s ease;">
                1F
              </button>
              <button class="floor-select-btn" data-floor="2"
                style="padding: 4px 12px; border: 1px solid #04aa6d; background: white; color: #04aa6d; 
                border-radius: 12px; font-size: 14px; font-weight: 500; min-width: 40px; transition: all 0.2s ease;">
                2F
              </button>
              <button class="floor-select-btn" data-floor="3"
                style="padding: 4px 12px; border: 1px solid #04aa6d; background: white; color: #04aa6d; 
                border-radius: 12px; font-size: 14px; font-weight: 500; min-width: 40px; transition: all 0.2s ease;">
                3F
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Tooltip Element -->
      <div id="floorplan-tooltip" class="absolute bg-black text-white text-xs px-2 py-1 rounded shadow-lg pointer-events-none hidden z-50"></div>


        <!-- SVG Container -->
        <div class="flex-grow bg-white rounded-xl p-3 card-shadow overflow-hidden flex relative h-auto"> <!-- Added relative class -->
          <div class="floor-plan-container flex-grow relative"> <!-- Added relative class -->

            <!-- Edit Button using Tailwind -->
            <button id="edit-floorplan-btn" class="absolute top-4 right-4 z-10 bg-negros-green text-white px-3 py-1 rounded-md text-sm hover:bg-negros-dark transition-colors cursor-pointer">
              Edit
          </button>
                        <button id="cancel-edit-floorplan-btn" class="absolute top-4 right-20 z-10 bg-gray-500 text-white px-3 py-1 rounded-md text-sm hover:bg-gray-600 transition-colors cursor-pointer hidden">
                            Cancel
                        </button>
          
          <!-- Removed invalid XML declaration -->
          <!-- Created with Inkscape (http://www.inkscape.org/) -->
          
          <?php
          $svgFile = 'SVG/Capitol_1st_floor_layout_20_modified.svg';
          if (file_exists($svgFile)) {
              echo file_get_contents($svgFile);
          } else {
              echo '<div class="text-center p-10 text-red-500">SVG file not found. Please place Capitol_1st_floor_layout_20_modified.svg in the SVG directory.</div>';
          }
          ?>
          
          </div>
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
                    <h4>Office Status</h4>
                    <label class="switch">
                        <input type="checkbox" id="office-active-toggle">
                        <span class="slider round"></span>
                    </label>
                    <span id="office-status-text">Active</span>
                </div>
                
                <!-- Door Status Section -->
                <div class="panel-section" id="door-status-section">
                    <h4>Entry Points (Doors)</h4>
                    <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                        Control which doors are accessible for this office
                    </p>
                    <div id="door-controls-container">
                        <!-- Door toggles will be dynamically added here -->
                    </div>
                </div>
                <!-- You can add more sections here to display office.details, office.contact etc. -->
                <!-- For example:
                <div class="panel-section">
                    <h4>Details</h4>
                    <p id="panel-office-details"></p>
                </div>
                <div class="panel-section">
                    <h4>Contact</h4>
                    <p id="panel-office-contact"></p>
                </div>
                -->
            </div>
        </div>
    </div>

    <!-- Panorama Editor Modal -->
    <div id="panorama-editor-modal" class="modal-overlay">
        <div class="modal-dialog" style="max-width: 600px;">
            <div class="modal-header" style="position: relative; padding-left: 60px;">
                <button id="close-panorama-modal-btn" class="panorama-back-arrow" title="Go back" style="
                    position: absolute;
                    left: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: #e8e8e8;
                    border: none;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    font-size: 20px;
                    font-weight: normal;
                    color: #333;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    line-height: 1;
                ">←</button>
                <h3 class="modal-title">Edit Panorama Point</h3>
            </div>
            <div class="modal-body">
                <div id="panorama-point-info" class="mb-4 text-sm text-gray-600 bg-gray-100 p-2 rounded">
                    <!-- Point info will be populated by JS -->
                </div>

                <!-- Panorama Status Toggle -->
                <div class="mb-4 p-3 bg-gray-50 rounded-lg border">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700">Panorama Status</h4>
                            <p class="text-xs text-gray-500">Toggle panorama visibility and accessibility</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span id="panorama-status-text" class="text-sm font-medium text-gray-600">Active</span>
                            <label class="switch">
                                <input type="checkbox" id="panorama-active-toggle" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="panorama-file-input" class="block text-sm font-medium text-gray-700 mb-1">Upload New Panorama</label>
                    <input type="file" id="panorama-file-input" accept="image/jpeg, image/png, image/webp" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-negros-light file:text-negros-green hover:file:bg-negros-green/20">
                </div>

                <div id="panorama-preview-container" class="mb-4 p-2 border rounded-lg bg-gray-50" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                    <span class="text-gray-400">Image preview</span>
                </div>

                <!-- QR Code Download (Simple like office system) -->
                <div id="panorama-qr-download" class="mb-4" style="display: none;">
                    <button id="download-panorama-qr-btn" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                        <img src="./srcImage/qr-code.png" alt="QR Code" style="width: 16px; height: 16px;">
                        Download QR Code
                    </button>
                </div>

                <div class="flex justify-end gap-3">
                    <button id="edit-hotspots-btn" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors text-sm font-medium" style="display: none;">🔗 Edit Hotspots</button>
                    <button id="remove-panorama-btn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors text-sm font-medium">Remove</button>
                    <button id="cancel-panorama-upload-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors text-sm font-medium">Cancel</button>
                    <button id="upload-panorama-btn" class="px-4 py-2 bg-negros-green text-white rounded-md hover:bg-negros-dark transition-colors text-sm font-medium">Upload Image</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Swap Confirmation Modal -->
    <div id="swap-confirmation-modal" class="modal-overlay">
        <div class="modal-dialog" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fa fa-exchange" style="color: #f59e0b;"></i> Swap Room Assignments?
                </h3>
            </div>
            <div class="modal-body">
                <div class="mb-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-sm text-gray-700 text-center mb-3">
                        <i class="fa fa-question-circle text-yellow-600"></i> Are you sure you want to swap these offices?
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-center justify-center gap-3">
                            <div class="flex-1 p-3 bg-white rounded-lg border border-blue-300 text-center">
                                <div class="text-xs text-gray-500 mb-1">From Room</div>
                                <div id="swap-from-room" class="text-sm font-semibold text-blue-700"></div>
                                <div id="swap-from-office" class="text-base font-bold text-gray-800 mt-1"></div>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fa fa-exchange text-2xl text-yellow-600"></i>
                            </div>
                            <div class="flex-1 p-3 bg-white rounded-lg border border-green-300 text-center">
                                <div class="text-xs text-gray-500 mb-1">To Room</div>
                                <div id="swap-to-room" class="text-sm font-semibold text-green-700"></div>
                                <div id="swap-to-office" class="text-base font-bold text-gray-800 mt-1"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center gap-3">
                    <button id="cancel-swap-confirmation-btn" class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors text-sm font-medium">
                        <i class="fa fa-times"></i> No, Cancel
                    </button>
                    <button id="confirm-swap-confirmation-btn" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                        <i class="fa fa-check"></i> Yes, Swap
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Entrance QR Code Modal -->
    <div id="entrance-qr-modal" class="modal-overlay">
        <div class="modal-dialog" style="max-width: 600px;">
            <div class="modal-header" style="position: relative; padding-left: 60px;">
                <button id="close-entrance-modal-btn" class="panorama-back-arrow" title="Go back" style="
                    position: absolute;
                    left: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: #e8e8e8;
                    border: none;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    font-size: 20px;
                    font-weight: normal;
                    color: #333;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    line-height: 1;
                ">←</button>
                <h3 class="modal-title">Entrance QR Code</h3>
            </div>
            <div class="modal-body">
                <div id="entrance-point-info" class="mb-4 text-sm text-gray-600 bg-gray-100 p-2 rounded">
                    <!-- Entrance info will be populated by JS -->
                </div>

                <!-- Entrance Info Display -->
                <div class="mb-4 p-3 bg-gray-50 rounded-lg border">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fa fa-sign-in text-white text-xl"></i>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Entrance</div>
                            <div id="entrance-label-display" class="text-base font-medium text-gray-800"></div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fa fa-layer-group text-green-600"></i>
                            <span>Floor: <strong id="entrance-floor-display"></strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa fa-hashtag text-green-600"></i>
                            <span>ID: <strong id="entrance-id-display"></strong></span>
                        </div>
                    </div>
                </div>

                <!-- QR Code Preview -->
                <div id="entrance-qr-preview" class="mb-4 p-2 border rounded-lg bg-gray-50" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                    <img id="entrance-qr-image" src="" alt="QR Code Preview" style="max-width: 192px; max-height: 192px;">
                </div>

                <!-- QR Not Found Message -->
                <div id="entrance-qr-not-found" class="mb-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200" style="display: none;">
                    <div class="flex items-start gap-3">
                        <i class="fa fa-exclamation-triangle text-yellow-600 text-xl mt-1"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 mb-1">QR Code Not Generated</p>
                            <p class="text-xs text-gray-500">This entrance doesn't have a QR code yet. Click below to generate entrance QR codes.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3">
                    <button id="generate-entrance-qr-btn" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors text-sm font-medium" style="display: none;">
                        <i class="fa fa-magic"></i> Generate QR Code
                    </button>
                    <button id="download-entrance-qr-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors text-sm font-medium">
                        <i class="fa fa-download"></i> Download
                    </button>
                    <button id="regenerate-entrance-qr-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                        <i class="fa fa-refresh"></i> Regenerate
                    </button>
                </div>
            </div>
        </div>
    </div>

     <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
     <script src="floorjs/panZoomSetup.js"></script> <!-- Link to the new JS file -->
     <script>
        // Pass PHP office data to JavaScript (make globally accessible)
        const officesData = <?php echo json_encode($offices); ?>;
        window.officesData = officesData; // Make globally accessible across script tags
        console.log("Offices Data Loaded:", officesData); // For debugging

        function applyConsistentFontStyling(container) {
            if (!container) return;
            container.querySelectorAll('text, tspan').forEach(el => {
                el.style.fontFamily = "'Segoe UI', -apple-system, BlinkMacSystemFont, system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif";
                el.style.fontWeight = "600";
                el.style.fontSize = "14px";
                el.style.fill = "#1a1a1a";
                el.style.stroke = "#ffffff";
                el.style.strokeWidth = "3px";
                el.style.strokeLinejoin = "round";
                el.style.paintOrder = "stroke fill";
                el.style.vectorEffect = "non-scaling-stroke";
                el.setAttribute('text-anchor', 'middle');
                el.setAttribute('dominant-baseline', 'central');
                el.classList.add('room-label');
            });
        }

        function labelBelongsToRoom(tspanEl, roomNumber) {
            if (!tspanEl) return false;
            const parentText = tspanEl.closest('text');
            if (!parentText || !parentText.id) return false;
            const parentId = parentText.id.trim();
            if (!parentId.startsWith('text-')) return false;
            if (parentId === `text-${roomNumber}`) return true;
            return parentId.startsWith(`text-${roomNumber}-`);
        }

        function findLabelTspanForRoom(roomNumber) {
            const directMatch = document.getElementById(`roomlabel-${roomNumber}`);
            if (labelBelongsToRoom(directMatch, roomNumber)) {
                return directMatch;
            }

            const textMatches = document.querySelectorAll(`text[id^="text-${roomNumber}"]`);
            for (const textNode of textMatches) {
                const tspanCandidate = textNode.querySelector('tspan');
                if (tspanCandidate) {
                    return tspanCandidate;
                }
            }

            const allLabels = document.querySelectorAll('tspan[id^="roomlabel-"]');
            for (const candidate of allLabels) {
                if (labelBelongsToRoom(candidate, roomNumber)) {
                    return candidate;
                }
            }

            return null;
        }

      // Function to update room labels with proper centering
        function updateRoomLabelMain(group, officeName) {
            // First try to find existing text element in the SVG by roomlabel ID
            const roomElement = group.querySelector("path, rect");
            if (!roomElement || !roomElement.id) return;
            
            const roomMatch = roomElement.id.match(/room-(\d+)(?:-(\d+))?/);
            if (!roomMatch) return;
            
            const roomNumber = roomMatch[1];
            const fullRoomId = roomMatch[0];
            let labelId = `roomlabel-${roomNumber}`;
            let tspanEl = findLabelTspanForRoom(roomNumber);
            let textEl = null;
            let originalX;
            let originalY;

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
            } else {
                // Look for existing text element within the group first (more reliable)
                textEl = group.querySelector("text");
                if (textEl) {
                    originalX = parseFloat(textEl.getAttribute("x"));
                    originalY = parseFloat(textEl.getAttribute("y"));
                }

                if (!textEl) {
                    textEl = document.querySelector(`#${labelId}`);
                    if (textEl) {
                        originalX = parseFloat(textEl.getAttribute("x"));
                        originalY = parseFloat(textEl.getAttribute("y"));
                    }
                }
            }

            if (!textEl) {
                // Remove any duplicate elsewhere first
                const dup = document.querySelector(`#${labelId}`);
                if (dup) dup.remove();

                // Create inside the group so any transforms apply correctly
                console.warn(`No existing text element found for ${fullRoomId}, creating new one`);
                textEl = document.createElementNS("http://www.w3.org/2000/svg", "text");
                textEl.setAttribute("class", "room-label");
                textEl.setAttribute("id", labelId);

                // Get the room path/rect to position the label at its center
                const bbox = roomElement.getBBox();
                originalX = bbox.x + bbox.width / 2;
                originalY = bbox.y + bbox.height / 2;
                textEl.setAttribute("x", originalX);
                textEl.setAttribute("y", originalY);

                // Append to the room group
                group.appendChild(textEl);
            }

            if (typeof originalX !== 'number' || Number.isNaN(originalX)) {
                originalX = parseFloat(textEl.getAttribute("x")) || 0;
            }
            if (typeof originalY !== 'number' || Number.isNaN(originalY)) {
                originalY = parseFloat(textEl.getAttribute("y")) || 0;
            }

            // Set text-anchor to middle for automatic centering. This is key.
            textEl.setAttribute("text-anchor", "middle");
            textEl.setAttribute("dominant-baseline", "central");

            // Clear existing content
            textEl.textContent = "";
            while (textEl.firstChild) {
                textEl.removeChild(textEl.firstChild);
            }

            const lineHeight = "1.2em";
            const words = officeName.split(" ");

            // Create tspans for each word to handle multi-line text
            words.forEach((word, index) => {
                const newTspan = document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "tspan"
                );
                newTspan.textContent = word;
                // The x attribute must be set on each tspan to align them vertically.
                newTspan.setAttribute("x", originalX); 
                // Move to the next line after the first word.
                if (index > 0) {
                    newTspan.setAttribute("dy", lineHeight);
                }
                if (index === 0) {
                    newTspan.setAttribute("id", labelId);
                }
                textEl.appendChild(newTspan);
            });

            textEl.setAttribute("x", originalX);
            textEl.setAttribute("y", originalY);

            applyConsistentFontStyling(group);
        }
        
        // Expose updateRoomLabelMain globally for use in other scripts (like dragDropSetup.js)
        window.updateRoomLabelMain = updateRoomLabelMain;
        
        // Function to update room labels with office names (using updateRoomLabelMain for centering)
    function updateRoomLabels(svg) {
            if (!svg) svg = document.querySelector('svg');
            if (!svg) return;
            
            console.log('updateRoomLabels called, processing rooms...');
            
            // Use global officesData to ensure it's accessible across contexts
            const officesDataToUse = window.officesData || officesData || [];
            console.log('updateRoomLabels: Using offices data with', officesDataToUse.length, 'offices');
            
            svg.querySelectorAll('path[id^="room-"]').forEach(function(el) {
                const match = el.id.match(/^room-(\d+)(?:-(\d+))?$/);
        if (!match) return;
                const roomNum = match[1];
                const floorNum = match[2] || '';

        // Match by exact id first, then by common variants across floors
        const office = officesDataToUse.find(o => o.location === el.id);
                
        console.log(`Room ${roomNum} (${el.id}):`, office ? `Found office: ${office.name}` : 'No office assigned');
                
        if (office) {
          // Find the parent group and text element
          const parentGroup = el.closest('g[id^="room-"]') || el.closest('g');
          if (parentGroup) {
                        // Mark room metadata for drag/drop and status toggles
                        parentGroup.setAttribute('data-room', 'true');
                        parentGroup.dataset.roomNumber = roomNum;
                        if (floorNum) {
                            parentGroup.dataset.floorNumber = floorNum;
                        } else {
                            delete parentGroup.dataset.floorNumber;
                        }

                        parentGroup.dataset.officeId = office.id;

                        const roomElement = parentGroup.querySelector('path, rect');
                        if (roomElement) {
                            roomElement.dataset.officeId = office.id;
                            roomElement.dataset.roomNumber = roomNum;
                            if (floorNum) {
                                roomElement.dataset.floorNumber = floorNum;
                            } else {
                                delete roomElement.dataset.floorNumber;
                            }
                        }

            // Use the updateRoomLabelMain function for proper centering
            updateRoomLabelMain(parentGroup, office.name);
            console.log(`Updated label for room ${roomNum} to "${office.name}" with centering`);
          } else {
            console.log(`No parent group found for room ${roomNum}`);
          }
                } else {
                    const parentGroup = el.closest('g[id^="room-"]') || el.closest('g');
                    if (parentGroup) {
                        parentGroup.setAttribute('data-room', 'true');
                        parentGroup.dataset.roomNumber = roomNum;
                        if (floorNum) {
                            parentGroup.dataset.floorNumber = floorNum;
                        } else {
                            delete parentGroup.dataset.floorNumber;
                        }

                        delete parentGroup.dataset.officeId;

                        const roomElement = parentGroup.querySelector('path, rect');
                        if (roomElement) {
                            delete roomElement.dataset.officeId;
                            roomElement.dataset.roomNumber = roomNum;
                            if (floorNum) {
                                roomElement.dataset.floorNumber = floorNum;
                            } else {
                                delete roomElement.dataset.floorNumber;
                            }
                        }
                    }
        }
      });
        }
        
        // Initialize labels on page load for embedded SVG
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for SVG to be fully rendered
            setTimeout(() => {
                console.log('Initializing room labels for embedded SVG...');
                updateRoomLabels();
                if (typeof window.refreshDragDropRooms === 'function') {
                    window.refreshDragDropRooms();
                }
                console.log('Room label initialization complete');
                
                // Also apply panorama status for the initial floor
                setTimeout(() => {
                    const initialFloor = 1; // Default floor
                    applyPanoramaStatusToMarkers(initialFloor);
                }, 300);
            }, 200); // Increased delay to ensure SVG is ready
        });

        // Also handle full page load as backup
        window.addEventListener('load', function() {
            setTimeout(() => {
                const currentFloor = (typeof window.getCurrentFloor === 'function') ? window.getCurrentFloor() : 1;
                console.log('Page fully loaded, applying panorama status for floor', currentFloor);
                applyPanoramaStatusToMarkers(currentFloor);
            }, 500);
        });
     </script>
   <script>
        // Global panorama status cache to persist across tab switches
        window.panoramaStatusCache = {};
        
        // Floor switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const floorButtons = document.querySelectorAll('.floor-select-btn');
            const floorMaps = {
                1: 'SVG/Capitol_1st_floor_layout_20_modified.svg',
                2: 'SVG/Capitol_2nd_floor_layout_6_modified.svg',
                3: 'SVG/Capitol_3rd_floor_layout_6.svg'
            };

            const floorGraph = {
                1: 'floor_graph.json',
                2: 'floor_graph_2.json',
                3: 'floor_graph_3.json' // Assuming you'll have a 3rd floor graph
            };

            function loadFloorMap(floor) {
                const container = document.querySelector('.floor-plan-container');
                if (!container) {
                    console.error('Floor plan container not found');
                    return;
                }

                if (typeof window.forceExitDragDropEditMode === 'function') {
                    window.forceExitDragDropEditMode();
                }

                // Destroy any existing panZoom instance before loading new SVG
                if (window.panZoom && typeof window.panZoom.destroy === 'function') {
                    try { window.panZoom.destroy(); } catch(e){ console.warn('Error destroying previous panZoom:', e);} 
                    window.panZoom = null;
                }

                fetch(floorMaps[floor])
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(svgContent => {
                        // Clean up SVG content to prevent transform errors
                        svgContent = svgContent.replace(/<\?xml[^>]*>/i, '');
                        container.innerHTML = svgContent;
                        
                        const svg = container.querySelector('svg');
                        if (svg) {
                            svg.setAttribute('id', 'svg1'); // Ensure consistent ID

                            // Ensure SVG has valid viewBox
                            if (!svg.getAttribute('viewBox')) {
                                const width = svg.getAttribute('width') || '1000';
                                const height = svg.getAttribute('height') || '1000';
                                svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
                            }
                            // Re-add the edit button
                            const editButton = document.createElement('button');
                            editButton.id = 'edit-floorplan-btn';
                            editButton.className = 'absolute top-4 right-4 z-10 bg-negros-green text-white px-3 py-1 rounded-md text-sm hover:bg-negros-dark transition-colors cursor-pointer';
                            editButton.textContent = 'Edit';
                            container.appendChild(editButton);

                            const cancelButton = document.createElement('button');
                            cancelButton.id = 'cancel-edit-floorplan-btn';
                            cancelButton.className = 'absolute top-4 right-20 z-10 bg-gray-500 text-white px-3 py-1 rounded-md text-sm hover:bg-gray-600 transition-colors cursor-pointer hidden';
                            cancelButton.textContent = 'Cancel';
                            container.appendChild(cancelButton);

                            if (typeof window.initializeDragDropEditButton === 'function') {
                                window.initializeDragDropEditButton(editButton);
                            }
                            if (typeof window.initializeDragDropCancelButton === 'function') {
                                window.initializeDragDropCancelButton(cancelButton);
                            }

                            // Initialize SVG attributes
                            svg.setAttribute('width', '100%');
                            svg.setAttribute('height', '100%');
                            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                            
                            // Make rooms interactive and add labels
                            svg.querySelectorAll('path[id^="room-"]').forEach(function(el) {
                                el.classList.add('selectable-room');
                            });
                            
                            // Update room labels with office names
                            console.log('Floor switch: Updating room labels...');
                            try {
                                updateRoomLabels(svg);
                                console.log('Floor switch: Room labels updated successfully');
                            } catch(e) {
                                console.error('Floor switch: Error in updateRoomLabels:', e);
                            }
                            
                            console.log('Floor switch: Checking data attributes...');
                            
                            // Immediate check to see if data attributes are set
                            try {
                                const immediateCheck = document.querySelectorAll('g[data-room="true"]');
                                console.log(`Floor switch: Immediately after updateRoomLabels, found ${immediateCheck.length} rooms with data-room="true"`);
                                
                                console.log('Floor switch: DEBUG - About to check if rooms exist for sample logging...');
                                
                                // Log a sample to verify attributes
                                if (immediateCheck.length > 0) {
                                    console.log('Floor switch: DEBUG - Inside if block, getting first room...');
                                    const sample = immediateCheck[0];
                                    console.log('Floor switch: DEBUG - Got sample room, checking attributes...');
                                    console.log('Floor switch: Sample room data:', {
                                        hasDataRoom: sample.hasAttribute('data-room'),
                                        dataOfficeId: sample.dataset.officeId,
                                        roomNumber: sample.dataset.roomNumber,
                                        elementId: sample.id,
                                        tagName: sample.tagName
                                    });
                                    console.log('Floor switch: DEBUG - Sample data logged successfully');
                                } else {
                                    console.warn('Floor switch: DEBUG - immediateCheck.length is 0, no rooms to sample');
                                }
                                console.log('Floor switch: DEBUG - Exiting try block for data attribute check');
                            } catch(e) {
                                console.error('Floor switch: Error checking data attributes:', e);
                                console.error('Floor switch: Error stack:', e.stack);
                            }
                            
                            if (typeof window.refreshDragDropRooms === 'function') {
                                window.refreshDragDropRooms();
                            }
                            
                            console.log('Floor switch: Setting up click handler attachment...');
                            
                            // CRITICAL: Attach tooltip handlers FIRST, then click handlers
                            // Tooltip handler clones elements which removes event listeners
                            setTimeout(() => {
                                try {
                                    console.log('Floor switch: Attaching tooltip handlers first...');
                                    
                                    // Attach tooltip handlers first (this clones elements and removes listeners)
                                    if (typeof window.attachRoomTooltipHandlers === 'function') {
                                        window.attachRoomTooltipHandlers();
                                        console.log('Floor switch: ✅ Tooltip handlers attached');
                                    }
                                    
                                    // Now attach click handlers AFTER tooltip handlers
                                    setTimeout(() => {
                                        console.log('Floor switch: Now attaching room click handlers...');
                                        
                                        const roomsWithData = document.querySelectorAll('g[data-room="true"]');
                                        console.log(`Floor switch: Found ${roomsWithData.length} rooms with data-room="true"`);
                                        
                                        if (roomsWithData.length > 0) {
                                            if (typeof window.attachRoomClickHandlers === 'function') {
                                                console.log('Floor switch: Calling attachRoomClickHandlers...');
                                                window.attachRoomClickHandlers();
                                                console.log('Floor switch: ✅ Room click handlers attached successfully');
                                            } else {
                                                console.error('Floor switch: ❌ window.attachRoomClickHandlers is not a function!');
                                            }
                                        } else {
                                            console.warn('Floor switch: No rooms with data-room="true" found, will retry...');
                                            
                                            // Retry after another delay
                                            setTimeout(() => {
                                                console.log('Floor switch: Retry attaching room click handlers...');
                                                const roomsWithDataRetry = document.querySelectorAll('g[data-room="true"]');
                                                console.log(`Floor switch: Found ${roomsWithDataRetry.length} rooms with data-room="true" on retry`);
                                                
                                                if (typeof window.attachRoomClickHandlers === 'function') {
                                                    window.attachRoomClickHandlers();
                                                    console.log('Floor switch: ✅ Room click handlers attached successfully on retry');
                                                } else {
                                                    console.error('Floor switch: ❌ window.attachRoomClickHandlers is still not a function!');
                                                }
                                            }, 500);
                                        }
                                    }, 200); // Wait 200ms after tooltips to attach click handlers
                                    
                                } catch(e) {
                                    console.error('Floor switch: Error in handler attachment:', e);
                                }
                            }, 300); // Start with 300ms delay
                            
                            // Initialize pan-zoom functionality
                            if (typeof svgPanZoom !== 'undefined') {
                                // Wait for SVG to be properly loaded
                                setTimeout(() => {
                                    try {
                                        const panZoomInstance = svgPanZoom(svg, {
                                            zoomEnabled: true,
                                            controlIconsEnabled: true,
                                            fit: true,
                                            center: true,
                                            minZoom: 0.1,
                                            maxZoom: 10,
                                            zoomScaleSensitivity: 0.5,
                                            onZoom: () => applyNonScalingLabels(panZoomInstance, svg),
                                            onPan: () => applyNonScalingLabels(panZoomInstance, svg)
                                        });

                                        window.panZoom = panZoomInstance; // Store globally

                                        // Apply non-scaling labels after a short delay
                                        setTimeout(() => applyNonScalingLabels(panZoomInstance, svg), 150);

                            // Dispatch event for pathfinding.js
                            window.dispatchEvent(new CustomEvent('floorMapLoaded', { detail: { floor: parseInt(floor, 10) } }));
                            
                            // Apply panorama status after markers are created
                            setTimeout(() => applyPanoramaStatusToMarkers(parseInt(floor, 10)), 300);
                            
                            // Apply door statuses after floor switch (wait for entry points to be drawn)
                            setTimeout(() => {
                                console.log('Floor switch: Reapplying door statuses...');
                                loadAndApplyAllDoorStatuses();
                            }, 800); // Wait 800ms for entry points to be drawn by pathfinding system
                            
                            // Draw entrance icons after floor loads
                            setTimeout(() => {
                                if (typeof window.drawEntranceIcons === 'function') {
                                    window.drawEntranceIcons(parseInt(floor, 10));
                                }
                            }, 500);
                            
                        } catch (e) {
                            console.warn('SVG Pan-Zoom initialization error:', e);
                            // Still dispatch event if pan-zoom fails
                            window.dispatchEvent(new CustomEvent('floorMapLoaded', { detail: { floor: parseInt(floor, 10) } }));
                            // Apply panorama status even if pan-zoom fails
                            setTimeout(() => applyPanoramaStatusToMarkers(parseInt(floor, 10)), 300);
                            // Apply door statuses even if pan-zoom fails
                            setTimeout(() => {
                                console.log('Floor switch: Reapplying door statuses (no pan-zoom)...');
                                loadAndApplyAllDoorStatuses();
                            }, 800);
                            
                            // Draw entrance icons even if pan-zoom fails
                            setTimeout(() => {
                                if (typeof window.drawEntranceIcons === 'function') {
                                    window.drawEntranceIcons(parseInt(floor, 10));
                                }
                            }, 500);
                        }
                    }, 100);
                } else {
                    // Dispatch event even if pan-zoom is not available
                    window.dispatchEvent(new CustomEvent('floorMapLoaded', { detail: { floor: parseInt(floor, 10) } }));
                    // Apply panorama status without pan-zoom
                    setTimeout(() => applyPanoramaStatusToMarkers(parseInt(floor, 10)), 300);
                    // Apply door statuses without pan-zoom
                    setTimeout(() => {
                        console.log('Floor switch: Reapplying door statuses (no pan-zoom or SVG)...');
                        loadAndApplyAllDoorStatuses();
                    }, 800);
                    
                    // Draw entrance icons without pan-zoom
                    setTimeout(() => {
                        if (typeof window.drawEntranceIcons === 'function') {
                            window.drawEntranceIcons(parseInt(floor, 10));
                        }
                    }, 500);
                }                            // Initialize additional functionality
                            if (typeof initializeLabels === 'function') {
                                initializeLabels();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading floor map:', error);
                        container.innerHTML = `<div class="text-center p-10 text-red-500">Error loading floor ${floor} map: ${error.message}</div>`;
                    });
            }

            function applyNonScalingLabels(panZoomInstance, svg) {
                if (!panZoomInstance || typeof panZoomInstance.getSizes !== 'function') return;
                try {
                    const sizes = panZoomInstance.getSizes();
                    const scale = sizes.realZoom;
                    if (scale) {
                        svg.querySelectorAll('tspan[id^="roomlabel-"]').forEach(label => {
                            label.setAttribute('vector-effect', 'non-scaling-stroke');
                            label.setAttribute('transform', `scale(${1/scale})`);
                        });
                    }
                } catch (e) {
                    console.warn('Error applying non-scaling labels:', e);
                }
            }

            // Keep track of current floor
            let currentFloor = '1';
            // Expose current floor globally for other scripts (panorama, pathfinding, etc.)
            window.getCurrentFloor = function() { return parseInt(currentFloor, 10); };
            
            // Function to count and display active offices on current floor
            function updateActiveOfficesCounter(floor) {
                // Use global officesData that gets updated in real-time
                const officesDataToUse = window.officesData || officesData || [];
                
                // Count active offices on the specified floor
                const activeOffices = officesDataToUse.filter(office => {
                    if (!office.location) return false;
                    
                    // Extract floor number from location (e.g., "room-101-1" -> floor 1)
                    const locationParts = office.location.split('-');
                    const officeFloor = locationParts[locationParts.length - 1];
                    
                    // Check if office is on current floor and is active
                    return officeFloor === floor && office.status === 'active';
                });
                
                const count = activeOffices.length;
                const counterElement = document.getElementById('active-count');
                
                if (counterElement) {
                    // Animate the counter update
                    counterElement.style.transition = 'transform 0.3s ease';
                    counterElement.style.transform = 'scale(1.3)';
                    
                    setTimeout(() => {
                        counterElement.textContent = count;
                        counterElement.style.transform = 'scale(1)';
                    }, 150);
                }
                
                console.log(`Floor ${floor}: ${count} active offices (real-time update)`);
            }
            
            // Make function globally accessible
            window.updateActiveOfficesCounter = updateActiveOfficesCounter;
            
            // Update counter on initial load
            updateActiveOfficesCounter(currentFloor);

            // Handle browser tab/window focus events to reapply panorama states
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // Tab/window became visible again, reapply panorama states
                    console.log('Tab became visible, reapplying panorama states...');
                    setTimeout(() => {
                        const floor = parseInt(currentFloor, 10);
                        applyPanoramaStatusToMarkers(floor);
                    }, 200);
                }
            });

            // Also handle window focus event as a backup
            window.addEventListener('focus', function() {
                console.log('Window focused, reapplying panorama states...');
                setTimeout(() => {
                    const floor = parseInt(currentFloor, 10);
                    applyPanoramaStatusToMarkers(floor);
                }, 200);
            });

            // Periodic check to ensure panorama states are maintained (every 30 seconds)
            setInterval(() => {
                if (!document.hidden) { // Only when tab is visible
                    const floor = parseInt(currentFloor, 10);
                    const markers = document.querySelectorAll('.panorama-marker');
                    if (markers.length > 0) {
                        console.log('Periodic check: reapplying panorama states...');
                        applyPanoramaStatusToMarkers(floor);
                    }
                }
            }, 30000); // Every 30 seconds

            floorButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const floor = this.getAttribute('data-floor');
                    
                    // Only reload if selecting a different floor
                    if (floor === currentFloor) {
                        return; // Do nothing if clicking the current floor
                    }
                    
                    // Update button styles
                    floorButtons.forEach(btn => {
                        btn.style.background = 'white';
                        btn.style.color = '#04aa6d';
                    });
                    this.style.background = '#04aa6d';
                    this.style.color = 'white';

                    // Load the corresponding floor map
                    currentFloor = floor;
                    // Update global accessor immediately
                    window.currentFloor = parseInt(currentFloor, 10);
                    
                    // Update active offices counter
                    updateActiveOfficesCounter(floor);
                    
                    loadFloorMap(floor);
                });
            });
        });
     </script>
   <script src="./floorjs/labelSetup.js"></script> <!-- Add the labeling script -->
   <script src="./floorjs/dragDropSetup.js"></script> <!-- Add the drag/drop script for edit mode -->
   <script src="pathfinding.js"></script> <!-- Add the pathfinding script -->
   <script>
    // Override initRoomSelection to disable room click handlers in admin panel
    // This prevents pathfinding from being triggered when clicking rooms
    window.initRoomSelection = function() {
        console.log('Room selection disabled in admin panel - room clicks will not trigger pathfinding');
        // Do nothing - this prevents roomClickHandler from being attached to room elements
    };
   </script>
   <script>
    // --- Office Status Modal for Room Clicks ---
    const officeModal = document.getElementById('office-details-modal');
    const officePanelName = document.getElementById('panel-office-name');
    const officeActiveToggle = document.getElementById('office-active-toggle');
    const officeStatusText = document.getElementById('office-status-text');
    const closePanelBtn = document.getElementById('close-panel-btn');
    
    let currentOfficeData = {};

    function openOfficePanel(officeId, officeName, officeStatus) {
        currentOfficeData = { id: officeId, name: officeName, status: officeStatus };
        
        officePanelName.textContent = officeName;
        officeActiveToggle.checked = (officeStatus === 'active');
        officeStatusText.textContent = (officeStatus === 'active') ? 'Active' : 'Inactive';
        officeStatusText.style.color = (officeStatus === 'active') ? '#4caf50' : '#f44336';
        
        // Load door status for this office
        loadDoorControls(officeId);
        
        officeModal.classList.add('active');
    }
    
    // Make openOfficePanel globally accessible for labelSetup.js
    window.openOfficePanel = openOfficePanel;

    // Door Management Functions
    function loadDoorControls(officeId, retryCount = 0) {
        const doorControlsContainer = document.getElementById('door-controls-container');
        doorControlsContainer.innerHTML = '<p style="text-align: center; color: #666;">Loading entry points...</p>';
        
        // Get the room location for this office (use window.officesData for cross-script access)
        const office = (window.officesData || []).find(o => o.id == officeId);
        console.log('loadDoorControls - Office data:', office);
        
        if (!office || !office.location) {
            console.warn('No office or location found for ID:', officeId);
            doorControlsContainer.innerHTML = '<p style="text-align: center; color: #999;">No location set for this office</p>';
            return;
        }
        
        // Get entry points from floor graph
        const roomId = office.location;
        console.log('loadDoorControls - Room ID:', roomId);
        console.log('loadDoorControls - window.floorGraph:', window.floorGraph);
        
        // If floor graph isn't loaded yet, retry up to 5 times
        if (!window.floorGraph || !window.floorGraph.rooms) {
            if (retryCount < 5) {
                console.log(`Floor graph not ready yet, retrying in ${300 + (retryCount * 200)}ms... (attempt ${retryCount + 1}/5)`);
                setTimeout(() => loadDoorControls(officeId, retryCount + 1), 300 + (retryCount * 200));
                return;
            } else {
                console.error('Floor graph failed to load after 5 attempts');
                doorControlsContainer.innerHTML = '<p style="text-align: center; color: #f44336;">Floor graph not loaded. Please refresh the page.</p>';
                return;
            }
        }
        
        // Check if the specific room exists in the floor graph
        if (!window.floorGraph.rooms[roomId]) {
            console.warn('Room not found in floor graph:', { 
                roomId: roomId,
                availableRooms: Object.keys(window.floorGraph.rooms)
            });
            doorControlsContainer.innerHTML = '<p style="text-align: center; color: #999;">No entry points defined for this room</p>';
            return;
        }
        
        const room = window.floorGraph.rooms[roomId];
        const entryPoints = room.entryPoints || room.doorPoints || [];
        console.log('loadDoorControls - Entry points found:', entryPoints);
        
        if (entryPoints.length === 0) {
            doorControlsContainer.innerHTML = '<p style="text-align: center; color: #999;">No entry points defined for this room</p>';
            return;
        }
        
        // Fetch door statuses from server
        fetch(`door_status_api.php?action=get&office_id=${officeId}`)
            .then(response => response.json())
            .then(data => {
                const doorStatuses = data.doors || {};
                console.log('loadDoorControls - Door statuses from server:', doorStatuses);
                renderDoorControls(officeId, roomId, entryPoints, doorStatuses);
            })
            .catch(error => {
                console.error('Error loading door statuses:', error);
                renderDoorControls(officeId, roomId, entryPoints, {});
            });
    }
    
    function renderDoorControls(officeId, roomId, entryPoints, doorStatuses) {
        const doorControlsContainer = document.getElementById('door-controls-container');
        doorControlsContainer.innerHTML = '';
        
        // Fetch door QR code statuses from server
        fetch(`qr_api.php?action=get_door_qr_status&office_id=${officeId}`)
            .then(response => response.json())
            .then(qrData => {
                const doorQRStatuses = qrData.door_qr_status || {};
                
                entryPoints.forEach((entryPoint, index) => {
                    const doorId = `${roomId}-door-${index}`;
                    const isActive = doorStatuses[doorId] !== false; // Default to active if not specified
                    
                    // Check QR code status for this door
                    const qrStatus = doorQRStatuses[index];
                    const hasQR = qrStatus && qrStatus.exists;
                    const qrIsActive = qrStatus && qrStatus.is_active;
                    
                    const doorItem = document.createElement('div');
                    doorItem.className = `door-control-item ${isActive ? '' : 'inactive'}`;
                    doorItem.style.cssText = 'display: flex; flex-direction: column; gap: 8px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 10px;';
                    
                    // Create QR status indicator HTML
                    let qrStatusHTML = '';
                    if (!hasQR) {
                        qrStatusHTML = `
                            <div style="display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px; font-size: 12px; color: #92400e;">
                                <i class="fa fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                <span><strong>No QR Code:</strong> Generate QR code in Office Management to enable scanning</span>
                            </div>
                        `;
                    } else if (!qrIsActive) {
                        qrStatusHTML = `
                            <div style="display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: #fee2e2; border-left: 3px solid #ef4444; border-radius: 4px; font-size: 12px; color: #991b1b;">
                                <i class="fa fa-times-circle" style="color: #ef4444;"></i>
                                <span><strong>QR Code Inactive:</strong> To activate the QR code for this door, please enable it in Office Management</span>
                            </div>
                        `;
                    } else {
                        qrStatusHTML = `
                            <div style="display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: #d1fae5; border-left: 3px solid #10b981; border-radius: 4px; font-size: 12px; color: #065f46;">
                                <i class="fa fa-check-circle" style="color: #10b981;"></i>
                                <span><strong>QR Code Active:</strong> This door's QR code is active and can be scanned</span>
                            </div>
                        `;
                    }
                    
                    doorItem.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="door-control-label">
                                <i class="fa fa-door-open"></i>
                                <span>Door ${index + 1}</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" class="door-toggle" data-door-id="${doorId}" data-room-id="${roomId}" data-door-index="${index}" ${isActive ? 'checked' : ''}>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        ${qrStatusHTML}
                    `;
                    
                    const toggle = doorItem.querySelector('.door-toggle');
                    toggle.addEventListener('change', function() {
                        handleDoorToggle(officeId, roomId, index, doorId, this.checked, doorItem);
                    });
                    
                    doorControlsContainer.appendChild(doorItem);
                });
            })
            .catch(error => {
                console.error('Error fetching door QR statuses:', error);
                // Fallback to basic rendering without QR status
                entryPoints.forEach((entryPoint, index) => {
                    const doorId = `${roomId}-door-${index}`;
                    const isActive = doorStatuses[doorId] !== false;
                    
                    const doorItem = document.createElement('div');
                    doorItem.className = `door-control-item ${isActive ? '' : 'inactive'}`;
                    doorItem.innerHTML = `
                        <div class="door-control-label">
                            <i class="fa fa-door-open"></i>
                            <span>Door ${index + 1}</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" class="door-toggle" data-door-id="${doorId}" data-room-id="${roomId}" data-door-index="${index}" ${isActive ? 'checked' : ''}>
                            <span class="slider round"></span>
                        </label>
                    `;
                    
                    const toggle = doorItem.querySelector('.door-toggle');
                    toggle.addEventListener('change', function() {
                        handleDoorToggle(officeId, roomId, index, doorId, this.checked, doorItem);
                    });
                    
                    doorControlsContainer.appendChild(doorItem);
                });
            });
    }
    
    function handleDoorToggle(officeId, roomId, doorIndex, doorId, isActive, doorItem) {
        // Update visual state immediately for better UX
        if (isActive) {
            doorItem.classList.remove('inactive');
        } else {
            doorItem.classList.add('inactive');
        }
        
        // Update door marker on floor plan
        updateDoorMarkerVisual(roomId, doorIndex, isActive);
        
        // Save to server
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('office_id', officeId);
        formData.append('door_id', doorId);
        formData.append('is_active', isActive ? '1' : '0');
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
        
        fetch('door_status_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Door ${doorId} status updated to:`, isActive);
            } else {
                console.error('Failed to update door status:', data.error);
                alert('Failed to update door status: ' + (data.error || 'Unknown error'));
                // Revert toggle
                const toggle = doorItem.querySelector('.door-toggle');
                toggle.checked = !isActive;
                if (!isActive) {
                    doorItem.classList.remove('inactive');
                } else {
                    doorItem.classList.add('inactive');
                }
                updateDoorMarkerVisual(roomId, doorIndex, !isActive);
            }
        })
        .catch(error => {
            console.error('Error updating door status:', error);
            alert('Error updating door status: ' + error.message);
            // Revert toggle
            const toggle = doorItem.querySelector('.door-toggle');
            toggle.checked = !isActive;
            if (!isActive) {
                doorItem.classList.remove('inactive');
            } else {
                doorItem.classList.add('inactive');
            }
            updateDoorMarkerVisual(roomId, doorIndex, !isActive);
        });
    }
    
    function updateDoorMarkerVisual(roomId, doorIndex, isActive) {
        const marker = document.getElementById(`entry-point-${roomId}-${doorIndex}`);
        if (marker) {
            if (isActive) {
                marker.classList.remove('inactive');
            } else {
                marker.classList.add('inactive');
            }
        }
    }

    // Load all door statuses and apply to markers on page load
    function loadAndApplyAllDoorStatuses() {
        fetch('door_status_api.php?action=get_all')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.doors) {
                    console.log('Loaded door statuses:', data.doors);
                    
                    // Apply inactive status to each door marker
                    for (const officeId in data.doors) {
                        const officeDoors = data.doors[officeId];
                        
                        for (const doorId in officeDoors) {
                            const isActive = officeDoors[doorId];
                            
                            // Extract room ID and door index from door_id
                            // Format: room-101-1-door-0
                            const match = doorId.match(/^(room-\d+-\d+)-door-(\d+)$/);
                            if (match) {
                                const roomId = match[1];
                                const doorIndex = match[2];
                                
                                // Update the marker visual
                                updateDoorMarkerVisual(roomId, doorIndex, isActive);
                            }
                        }
                    }
                } else {
                    console.warn('Failed to load door statuses:', data.error);
                }
            })
            .catch(error => {
                console.error('Error loading door statuses:', error);
            });
    }
    
    // Call this after floor graph is loaded and entry points are drawn
    // We'll add a small delay to ensure DOM is ready
    setTimeout(() => {
        loadAndApplyAllDoorStatuses();
    }, 500);

    // Draw entrance icons on the floor plan
    window.drawEntranceIcons = function(floor) {
        console.log(`🟢 [ENTRANCE ICONS] Starting drawEntranceIcons for floor ${floor}...`);
        
        // Determine the floor graph file to load
        let floorGraphFile = 'floor_graph.json'; // Default floor 1
        if (floor === 2) {
            floorGraphFile = 'floor_graph_2.json';
        } else if (floor === 3) {
            floorGraphFile = 'floor_graph_3.json';
        }
        
        // Fetch floor graph data
        fetch(floorGraphFile)
            .then(response => response.json())
            .then(floorGraph => {
                // Check if entrances exist in floor graph
                if (!floorGraph.entrances || floorGraph.entrances.length === 0) {
                    console.log(`No entrances defined for floor ${floor}`);
                    return;
                }
                
                console.log(`Found ${floorGraph.entrances.length} entrances on floor ${floor}:`, floorGraph.entrances);
                
                // Get the SVG element (use getCapitolSVG from pathfinding.js if available)
                const svg = typeof getCapitolSVG === 'function' ? getCapitolSVG() : 
                           (document.querySelector('#svg1') || document.querySelector('svg'));
                if (!svg) {
                    console.error('SVG element not found for entrance icons');
                    return;
                }
                console.log('SVG element found:', svg);
                
                const svgNS = "http://www.w3.org/2000/svg";
                
                // Get or create the main group (svg-pan-zoom viewport)
                let mainGroup = svg.querySelector('.svg-pan-zoom_viewport') || svg.querySelector('g');
                if (!mainGroup) {
                    console.warn('No main group found, creating new one');
                    mainGroup = document.createElementNS(svgNS, 'g');
                    svg.appendChild(mainGroup);
                }
                console.log('Main group found:', mainGroup);
                
                // Get or create entrance icons group
                let entranceIconGroup = mainGroup.querySelector('#entrance-icon-group');
                if (!entranceIconGroup) {
                    console.log('Creating new entrance-icon-group');
                    entranceIconGroup = document.createElementNS(svgNS, 'g');
                    entranceIconGroup.setAttribute('id', 'entrance-icon-group');
                    mainGroup.appendChild(entranceIconGroup);
                }
                console.log('Entrance icon group:', entranceIconGroup);
                
                // Clear existing entrance icons
                entranceIconGroup.innerHTML = '';
                
                // Draw each entrance icon
                floorGraph.entrances.forEach((entrance, index) => {
                    console.log(`Drawing entrance icon: ${entrance.label} at (${entrance.x}, ${entrance.y})`);
                    
                    // Create entrance marker group
                    const marker = document.createElementNS(svgNS, 'g');
                    marker.setAttribute('class', 'entrance-icon-marker');
                    marker.setAttribute('id', `entrance-icon-${entrance.id}`);
                    marker.style.cursor = 'pointer';
                    
                    // Create background circle (green for entrance)
                    const bgCircle = document.createElementNS(svgNS, 'circle');
                    bgCircle.setAttribute('cx', entrance.x);
                    bgCircle.setAttribute('cy', entrance.y);
                    bgCircle.setAttribute('r', '14'); // Slightly larger than door icons
                    bgCircle.setAttribute('fill', '#10B981'); // Green background
                    bgCircle.setAttribute('stroke', '#ffffff');
                    bgCircle.setAttribute('stroke-width', '2');
                    bgCircle.setAttribute('class', 'entrance-bg');
                    bgCircle.setAttribute('vector-effect', 'non-scaling-stroke');
                    
                    // Create entrance icon using the SVG path from entrance-14-svgrepo-com.svg
                    const entranceIcon = document.createElementNS(svgNS, 'path');
                    entranceIcon.setAttribute(
                        'd',
                        'm 4,0 0,4 2,0 0,-2 6,0 0,10 -6,0 0,-2 -2,0 0,4 10,0 0,-14 z m 3,3.5 0,2.25 -6,0 0,2.5 6,0 0,2.25 4,-3.5 z'
                    );
                    entranceIcon.setAttribute('fill', '#ffffff');
                    // Adjust transform to center and scale the entrance icon
                    entranceIcon.setAttribute(
                        'transform',
                        `translate(${entrance.x - 7}, ${entrance.y - 7}) scale(1)`
                    );
                    entranceIcon.setAttribute('class', 'entrance-icon');
                    entranceIcon.style.pointerEvents = 'none'; // Make icon non-interactive
                    
                    marker.appendChild(bgCircle);
                    marker.appendChild(entranceIcon);
                    
                    // Add hover effects
                    marker.addEventListener('mouseenter', () => {
                        bgCircle.setAttribute('fill', '#34D399'); // Lighter green
                        bgCircle.setAttribute('r', '16');
                    });
                    
                    marker.addEventListener('mouseleave', () => {
                        bgCircle.setAttribute('fill', '#10B981');
                        bgCircle.setAttribute('r', '14');
                    });
                    
                    // Add click handler to open entrance QR modal
                    marker.addEventListener('click', () => {
                        openEntranceQRModal(entrance);
                    });
                    
                    // Add tooltip on hover
                    const title = document.createElementNS(svgNS, 'title');
                    title.textContent = `${entrance.label} - Click to download QR code`;
                    marker.appendChild(title);
                    
                    entranceIconGroup.appendChild(marker);
                    console.log(`Successfully added entrance icon for ${entrance.label} at (${entrance.x}, ${entrance.y})`);
                });
                
                console.log(`Finished drawing ${floorGraph.entrances.length} entrance icons`);
                console.log('Entrance icon group innerHTML length:', entranceIconGroup.innerHTML.length);
            })
            .catch(error => {
                console.error(`Error loading floor graph for entrance icons:`, error);
            });
    }
    
    // Open entrance QR modal
    function openEntranceQRModal(entrance) {
        console.log(`Opening entrance QR modal for:`, entrance);
        
        // Store current entrance data
        window.currentEntrance = entrance;
        
        // Update info section (like panorama modal)
        const infoDiv = document.getElementById('entrance-point-info');
        infoDiv.innerHTML = `<strong>${entrance.label}</strong> on Floor ${entrance.floor} | ID: ${entrance.id}`;
        
        // Update entrance details
        document.getElementById('entrance-label-display').textContent = entrance.label;
        document.getElementById('entrance-floor-display').textContent = entrance.floor;
        document.getElementById('entrance-id-display').textContent = entrance.id;
        
        const qrImage = document.getElementById('entrance-qr-image');
        const qrPreview = document.getElementById('entrance-qr-preview');
        const qrNotFound = document.getElementById('entrance-qr-not-found');
        const downloadBtn = document.getElementById('download-entrance-qr-btn');
        const regenerateBtn = document.getElementById('regenerate-entrance-qr-btn');
        const generateBtn = document.getElementById('generate-entrance-qr-btn');
        
        // Fetch QR filename from database
        fetch(`entrance_qr_api.php?action=get_all`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.entrances) {
                    const entranceData = data.entrances.find(e => e.entrance_id === entrance.id);
                    
                    if (entranceData && entranceData.qr_code_image) {
                        // QR exists in database, check if file exists
                        const qrImagePath = `entrance_qrcodes/${entranceData.qr_code_image}`;
                        window.currentEntranceQRPath = qrImagePath; // Store for download
                        
                        fetch(qrImagePath, { method: 'HEAD' })
                            .then(response => {
                                if (response.ok) {
                                    // QR exists, show preview
                                    qrImage.src = qrImagePath + '?' + new Date().getTime(); // Cache bust
                                    qrPreview.style.display = 'block';
                                    qrNotFound.style.display = 'none';
                                    downloadBtn.disabled = false;
                                    downloadBtn.style.display = 'inline-block';
                                    regenerateBtn.disabled = false;
                                    regenerateBtn.style.display = 'inline-block';
                                    generateBtn.style.display = 'none';
                                } else {
                                    // QR file not found
                                    qrPreview.style.display = 'none';
                                    qrNotFound.style.display = 'block';
                                    downloadBtn.style.display = 'none';
                                    regenerateBtn.style.display = 'none';
                                    generateBtn.style.display = 'inline-block';
                                }
                            });
                    } else {
                        // No QR in database - show generate button
                        qrPreview.style.display = 'none';
                        qrNotFound.style.display = 'block';
                        downloadBtn.style.display = 'none';
                        regenerateBtn.style.display = 'none';
                        generateBtn.style.display = 'inline-block';
                        window.currentEntranceQRPath = null;
                    }
                } else {
                    // API error - show generate button
                    qrPreview.style.display = 'none';
                    qrNotFound.style.display = 'block';
                    downloadBtn.style.display = 'none';
                    regenerateBtn.style.display = 'none';
                    generateBtn.style.display = 'inline-block';
                    window.currentEntranceQRPath = null;
                }
            })
            .catch(error => {
                console.error('Error loading entrance data:', error);
                qrPreview.style.display = 'none';
                qrNotFound.style.display = 'block';
                downloadBtn.style.display = 'none';
                regenerateBtn.style.display = 'none';
                generateBtn.style.display = 'inline-block';
                window.currentEntranceQRPath = null;
            });
        
        // Show modal
        document.getElementById('entrance-qr-modal').classList.add('active');
    }
    
    // Close entrance QR modal
    function closeEntranceQRModal() {
        document.getElementById('entrance-qr-modal').classList.remove('active');
        window.currentEntrance = null;
    }
    
    // Download entrance QR code
    function downloadEntranceQR() {
        if (!window.currentEntrance || !window.currentEntranceQRPath) {
            alert('No QR code available for download. Please generate entrance QR codes first.');
            return;
        }
        
        const entrance = window.currentEntrance;
        const qrImagePath = window.currentEntranceQRPath;
        
        // Create download link with proper attributes
        const link = document.createElement('a');
        link.setAttribute('href', qrImagePath);
        link.setAttribute('download', `${entrance.id}_QR.png`);
        link.style.display = 'none';
        document.body.appendChild(link);
        
        // Trigger download
        try {
            link.click();
            console.log(`Downloaded QR code for ${entrance.label}`);
        } catch (error) {
            console.error('Download failed:', error);
            alert('Download failed. Please try again.');
        } finally {
            document.body.removeChild(link);
        }
    }
    
    // Regenerate entrance QR code
    function regenerateEntranceQR() {
        if (!window.currentEntrance) return;
        
        const entrance = window.currentEntrance;
        
        // Show loading state
        const regenerateBtn = document.getElementById('regenerate-entrance-qr-btn');
        const originalText = regenerateBtn.innerHTML;
        regenerateBtn.disabled = true;
        regenerateBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
        
        // Call API to regenerate QR
        fetch('entrance_qr_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=regenerate&entrance_id=${entrance.id}&csrf_token=${window.CSRF_TOKEN}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload modal to show new QR
                closeEntranceQRModal();
                setTimeout(() => openEntranceQRModal(entrance), 100);
                alert('QR code regenerated successfully!');
            } else {
                alert('Failed to regenerate QR code: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error regenerating QR:', error);
            alert('Error regenerating QR code. Please try again.');
        })
        .finally(() => {
            regenerateBtn.disabled = false;
            regenerateBtn.innerHTML = originalText;
        });
    }
    
    // Generate entrance QR code (first time)
    function generateEntranceQR() {
        if (!window.currentEntrance) return;
        
        const entrance = window.currentEntrance;
        
        // Show loading state
        const generateBtn = document.getElementById('generate-entrance-qr-btn');
        const originalText = generateBtn.innerHTML;
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
        
        // Call API to generate ALL entrance QR codes (it will skip existing ones)
        fetch('entrance_qr_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=generate&csrf_token=${window.CSRF_TOKEN}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload modal to show new QR
                closeEntranceQRModal();
                setTimeout(() => openEntranceQRModal(entrance), 100);
                alert('QR code generated successfully!');
            } else {
                alert('Failed to generate QR code: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error generating QR:', error);
            alert('Error generating QR code. Please try again.');
        })
        .finally(() => {
            generateBtn.disabled = false;
            generateBtn.innerHTML = originalText;
        });
    }
    
    // Add event listeners for entrance modal
    document.getElementById('close-entrance-modal-btn').addEventListener('click', closeEntranceQRModal);
    document.getElementById('download-entrance-qr-btn').addEventListener('click', downloadEntranceQR);
    document.getElementById('regenerate-entrance-qr-btn').addEventListener('click', regenerateEntranceQR);
    document.getElementById('generate-entrance-qr-btn').addEventListener('click', generateEntranceQR);
    
    // Close modal when clicking outside
    document.getElementById('entrance-qr-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEntranceQRModal();
        }
    });


    function closeOfficePanel() {
        officeModal.classList.remove('active');
    }

    closePanelBtn.addEventListener('click', closeOfficePanel);
    
    // Close modal when clicking outside
    officeModal.addEventListener('click', function(e) {
        if (e.target === officeModal) {
            closeOfficePanel();
        }
    });

    // Handle status toggle
    officeActiveToggle.addEventListener('change', function() {
        const newStatus = this.checked ? 'active' : 'inactive';
        updateOfficeStatus(currentOfficeData.id, newStatus);
    });

    function updateOfficeStatus(officeId, newStatus) {
        const formData = new FormData();
        formData.append('office_id', officeId);
        formData.append('status', newStatus);

        fetch('update_office_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Office status updated successfully');
                officeStatusText.textContent = (newStatus === 'active') ? 'Active' : 'Inactive';
                officeStatusText.style.color = (newStatus === 'active') ? '#4caf50' : '#f44336';
                
                // Update the office data in memory (both global references)
                const officeIndex = officesData.findIndex(o => o.id == officeId);
                if (officeIndex !== -1) {
                    officesData[officeIndex].status = newStatus;
                }
                if (window.officesData) {
                    const windowOfficeIndex = window.officesData.findIndex(o => o.id == officeId);
                    if (windowOfficeIndex !== -1) {
                        window.officesData[windowOfficeIndex].status = newStatus;
                    }
                }
                
                // Update visual indication on the floor plan
                updateRoomVisualStatus(officeId, newStatus);
                
                // Update active offices counter in real-time
                const currentFloor = window.getCurrentFloor ? window.getCurrentFloor().toString() : '1';
                if (typeof window.updateActiveOfficesCounter === 'function') {
                    window.updateActiveOfficesCounter(currentFloor);
                } else if (typeof updateActiveOfficesCounter === 'function') {
                    updateActiveOfficesCounter(currentFloor);
                }
            } else {
                alert('Failed to update office status: ' + (data.error || 'Unknown error'));
                // Revert toggle state on error
                officeActiveToggle.checked = !officeActiveToggle.checked;
            }
        })
        .catch(error => {
            console.error('Error updating office status:', error);
            alert('Error updating office status: ' + error.message);
            // Revert toggle state on error
            officeActiveToggle.checked = !officeActiveToggle.checked;
        });
    }

    function updateRoomVisualStatus(officeId, status) {
        // Find the room element associated with this office
        const roomGroup = document.querySelector(`g[data-office-id="${officeId}"]`);
        if (roomGroup) {
            const roomPath = roomGroup.querySelector('path[id^="room-"], rect[id^="room-"]');
            if (roomPath) {
                if (status === 'inactive') {
                    roomPath.style.opacity = '0.5';
                    roomPath.style.filter = 'grayscale(100%)';
                } else {
                    roomPath.style.opacity = '1';
                    roomPath.style.filter = 'none';
                }
            }
        }
    }

    // Attach click handlers to rooms after SVG loads
    function attachRoomClickHandlers() {
        console.log('Attaching room click handlers for office status modal...');
        
        const roomGroups = document.querySelectorAll('g[data-room="true"]');
        console.log(`Found ${roomGroups.length} room groups with data-room="true"`);
        
        roomGroups.forEach(roomGroup => {
            const officeId = roomGroup.dataset.officeId;
            if (!officeId) {
                console.warn('Room group has no officeId:', roomGroup);
                return;
            }
            
            const office = (window.officesData || []).find(o => o.id == officeId);
            if (!office) {
                console.warn(`No office found for ID ${officeId}`);
                return;
            }
            
            console.log(`Attaching click handler to office: ${office.name} (ID: ${officeId})`);
            
            const roomElement = roomGroup.querySelector('path[id^="room-"], rect[id^="room-"]');
            if (roomElement) {
                // Remove any existing click listener to avoid duplicates
                roomElement.removeEventListener('click', roomElement._officeClickHandler);
                
                // Make room visually clickable
                roomElement.style.cursor = 'pointer';
                roomElement.classList.add('clickable-room');
                
                // Create and store the click handler
                roomElement._officeClickHandler = function(e) {
                    // Only trigger if not in drag/drop edit mode
                    if (window.isEditMode) return;
                    
                    e.stopPropagation();
                    console.log(`Room clicked: ${office.name}`);
                    openOfficePanel(office.id, office.name, office.status);
                };
                
                roomElement.addEventListener('click', roomElement._officeClickHandler);
                
                // Add hover effect
                roomElement.addEventListener('mouseenter', function() {
                    if (!window.isEditMode) {
                        this.style.strokeWidth = '3';
                        this.style.filter = 'brightness(1.1)';
                    }
                });
                
                roomElement.addEventListener('mouseleave', function() {
                    if (!window.isEditMode) {
                        this.style.strokeWidth = '';
                        this.style.filter = office.status === 'inactive' ? 'grayscale(100%)' : 'none';
                    }
                });
                
                // Apply visual status on load
                updateRoomVisualStatus(office.id, office.status);
            }
        });
    }
    
    // Make function globally accessible for floor switching
    window.attachRoomClickHandlers = attachRoomClickHandlers;

    // Call this function after SVG and labels are loaded
    window.addEventListener('load', function() {
        setTimeout(() => {
            attachRoomClickHandlers();
        }, 800); // Wait for all initialization to complete
    });
   </script>
   <script>
    // --- Panorama Editor Modal ---
    const panoramaModal = document.getElementById('panorama-editor-modal');
    const closePanoramaBtn = document.getElementById('close-panorama-modal-btn');
    const cancelPanoramaBtn = document.getElementById('cancel-panorama-upload-btn');
    const panoramaPointInfo = document.getElementById('panorama-point-info');
    const fileInput = document.getElementById('panorama-file-input');
    const previewContainer = document.getElementById('panorama-preview-container');
    const uploadBtn = document.getElementById('upload-panorama-btn');
    const removeBtn = document.getElementById('remove-panorama-btn');
    const panoramaActiveToggle = document.getElementById('panorama-active-toggle');
    const panoramaStatusText = document.getElementById('panorama-status-text');

    let currentPanoData = {};

  function openPanoramaEditor(pathId, pointIndex, currentImage) {
    // Determine current floor via helper or fallback to 1
    const floorNum = (typeof window.getCurrentFloor === 'function') ? window.getCurrentFloor() : 1;

    // Attempt to locate coordinates from loaded floorGraph data
    let pointX = 0, pointY = 0;
    try {
      const graph = floorGraph && floorGraph.walkablePaths ? floorGraph : (window.floorGraph || {});
      if (graph.walkablePaths) {
        const pathObj = graph.walkablePaths.find(p => p.id === pathId);
        if (pathObj && pathObj.pathPoints && pathObj.pathPoints[pointIndex]) {
          pointX = pathObj.pathPoints[pointIndex].x || 0;
          pointY = pathObj.pathPoints[pointIndex].y || 0;
        }
      }
    } catch(e) { console.warn('Could not derive panorama point coordinates:', e); }

        currentPanoData = { pathId, pointIndex, currentImage, pointX, pointY, floor: floorNum };
        
                // Populate basic point info and placeholders for Title/Description
                panoramaPointInfo.innerHTML = `
                        <div style="font-size:14px;margin-bottom:6px;">
                            <strong>Path ID:</strong> ${pathId}<br>
                            <strong>Point Index:</strong> ${pointIndex}<br>
                            <strong>Floor:</strong> ${floorNum}
                        </div>
                        <div id="panorama-meta" style="font-size:13px;color:#333;">
                            <div><strong>Title:</strong> <span id="panorama-title" style="font-weight:600;color:#111;">Loading...</span></div>
                            <div style="margin-top:6px;"><strong>Description:</strong>
                                <div id="panorama-description" style="margin-top:4px;color:#444;">Loading...</div>
                            </div>
                        </div>
                `;

        // Fetch current panorama status and update toggle
        fetchPanoramaStatus(pathId, pointIndex, floorNum);

        if (currentImage) {
            previewContainer.innerHTML = `<img src="Pano/${currentImage}" class="max-w-full max-h-48 object-contain">`;
            removeBtn.style.display = 'inline-block';
            document.getElementById('edit-hotspots-btn').style.display = 'inline-block';
            // Fetch metadata (title/description) for the panorama and populate fields
            (function(){
                const metaParams = new URLSearchParams({ action: 'get', path_id: pathId, point_index: pointIndex, floor_number: floorNum });
                fetch('panorama_api.php?' + metaParams.toString())
                    .then(r => r.json())
                    .then(data => {
                        const titleEl = document.getElementById('panorama-title');
                        const descEl = document.getElementById('panorama-description');
                        if (data && data.success && data.panorama) {
                            if (titleEl) titleEl.textContent = data.panorama.title || '(no title)';
                            if (descEl) descEl.textContent = data.panorama.description || '(no description)';
                        } else {
                            if (titleEl) titleEl.textContent = '(no title)';
                            if (descEl) descEl.textContent = '(no description)';
                        }
                    })
                    .catch(err => {
                        console.warn('Failed to fetch panorama metadata:', err);
                        const titleEl = document.getElementById('panorama-title');
                        const descEl = document.getElementById('panorama-description');
                        if (titleEl) titleEl.textContent = '(error)';
                        if (descEl) descEl.textContent = '(error)';
                    });
            })();
        } else {
      // Attempt a just-in-time fetch in case JSON not yet refreshed (multi-floor timing)
      previewContainer.innerHTML = '<span class="text-gray-400">Checking for existing panorama...</span>';
      removeBtn.style.display = 'none';
      const params = new URLSearchParams({
        action: 'get',  
        path_id: pathId,
        point_index: pointIndex,
        floor_number: floorNum
      });
      fetch('panorama_api.php?' + params.toString())
        .then(r => r.json())
        .then(data => {
          if (data.success && data.panorama && data.panorama.image_filename) {
            currentPanoData.currentImage = data.panorama.image_filename;
            previewContainer.innerHTML = `<img src="Pano/${data.panorama.image_filename}" class="max-w-full max-h-48 object-contain">`;
            removeBtn.style.display = 'inline-block';
            document.getElementById('edit-hotspots-btn').style.display = 'inline-block';
                        // Populate metadata fields if available
                        const titleEl = document.getElementById('panorama-title');
                        const descEl = document.getElementById('panorama-description');
                        if (titleEl) titleEl.textContent = data.panorama.title || '(no title)';
                        if (descEl) descEl.textContent = data.panorama.description || '(no description)';
          } else {
            previewContainer.innerHTML = '<span class="text-gray-400">No panorama image assigned.</span>';
            document.getElementById('edit-hotspots-btn').style.display = 'none';
                        const titleEl = document.getElementById('panorama-title');
                        const descEl = document.getElementById('panorama-description');
                        if (titleEl) titleEl.textContent = '(no title)';
                        if (descEl) descEl.textContent = '(no description)';
          }
        })
        .catch(err => {
          console.warn('Fallback pano fetch failed:', err);
          previewContainer.innerHTML = '<span class="text-gray-400">No panorama image assigned.</span>';
                    const titleEl = document.getElementById('panorama-title');
                    const descEl = document.getElementById('panorama-description');
                    if (titleEl) titleEl.textContent = '(error)';
                    if (descEl) descEl.textContent = '(error)';
        });
    }

        fileInput.value = ''; // Clear previous selection
        panoramaModal.classList.add('active');
    }

    function closePanoramaModal() {
    panoramaModal.classList.remove('active');
    // Reset all panorama markers to inactive (blue) when modal closes
    try {
      document.querySelectorAll('.panorama-marker').forEach(m => {
        m.classList.remove('active');
        const bg = m.querySelector('.camera-bg');
        if (bg) {
          bg.setAttribute('fill', '#2563eb');
          bg.setAttribute('r', '12');
        }
      });
    } catch (e) {
      console.warn('Failed to reset panorama markers on modal close:', e);
    }
    }

    function previewFile() {
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-48 object-contain">`;
            }
            reader.readAsDataURL(file);
        }
    }

    closePanoramaBtn.addEventListener('click', closePanoramaModal);
    cancelPanoramaBtn.addEventListener('click', closePanoramaModal);
    fileInput.addEventListener('change', previewFile);
    
    // Add hover effect for back arrow button
    closePanoramaBtn.addEventListener('mouseenter', () => {
        closePanoramaBtn.style.background = '#d8d8d8';
    });
    closePanoramaBtn.addEventListener('mouseleave', () => {
        closePanoramaBtn.style.background = '#e8e8e8';
    });
    
    // Modal functions for panorama operations
    function showPanoramaModal(title, message, type = 'info') {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            z-index: 10001;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease;
        `;

        const modal = document.createElement('div');
        modal.style.cssText = `
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            border-radius: 20px;
            padding: 30px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: scaleIn 0.2s ease;
            color: white;
            text-align: center;
        `;

        const iconMap = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const icon = iconMap[type] || iconMap.info;

        modal.innerHTML = `
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes scaleIn {
                    from { transform: scale(0.9); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
            </style>
            <div style="font-size: 60px; margin-bottom: 20px;">${icon}</div>
            <div style="font-size: 24px; font-weight: 700; margin-bottom: 15px;">${title}</div>
            <div style="font-size: 16px; margin-bottom: 25px; color: rgba(255, 255, 255, 0.9);">${message}</div>
            <button id="panoramaModalOkBtn" style="
                padding: 12px 40px;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                background: linear-gradient(135deg, #04aa6d, #036551);
                color: white;
                transition: all 0.3s ease;
            ">OK</button>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const okBtn = modal.querySelector('#panoramaModalOkBtn');
        okBtn.addEventListener('click', () => document.body.removeChild(overlay));
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) document.body.removeChild(overlay);
        });
    }

    function showPanoramaMetadataModal(callback) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            z-index: 10001;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease;
        `;

        const modal = document.createElement('div');
        modal.style.cssText = `
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            border-radius: 20px;
            padding: 30px;
            max-width: 550px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: scaleIn 0.2s ease;
            color: white;
        `;

        modal.innerHTML = `
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes scaleIn {
                    from { transform: scale(0.9); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
                .metadata-label {
                    display: block;
                    color: #04aa6d;
                    font-size: 14px;
                    font-weight: 600;
                    margin-bottom: 8px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .metadata-input {
                    width: 100%;
                    padding: 12px 16px;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 8px;
                    color: #fff;
                    font-size: 14px;
                    font-family: inherit;
                    transition: all 0.3s ease;
                    box-sizing: border-box;
                }
                .metadata-input:focus {
                    outline: none;
                    border-color: #04aa6d;
                    background: rgba(255, 255, 255, 0.08);
                    box-shadow: 0 0 0 3px rgba(4, 170, 109, 0.1);
                }
                .metadata-input::placeholder {
                    color: rgba(255, 255, 255, 0.4);
                }
                textarea.metadata-input {
                    min-height: 100px;
                    resize: vertical;
                }
            </style>
            <div style="font-size: 24px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span>📝</span> Panorama Details
            </div>
            <div style="font-size: 14px; margin-bottom: 25px; color: rgba(255, 255, 255, 0.7);">
                Add optional title and description for this panorama
            </div>
            
            <div style="margin-bottom: 20px;">
                <label class="metadata-label">Title (Optional)</label>
                <input 
                    type="text" 
                    class="metadata-input" 
                    id="panoramaTitleInput" 
                    placeholder="Enter panorama title..." 
                    maxlength="100"
                />
            </div>
            
            <div style="margin-bottom: 25px;">
                <label class="metadata-label">Description (Optional)</label>
                <textarea 
                    class="metadata-input" 
                    id="panoramaDescriptionInput" 
                    placeholder="Enter panorama description..." 
                    maxlength="500"
                ></textarea>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button id="panoramaMetadataCancelBtn" style="
                    flex: 1;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 10px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    background: rgba(255, 255, 255, 0.1);
                    color: white;
                    transition: all 0.3s ease;
                ">Cancel</button>
                <button id="panoramaMetadataConfirmBtn" style="
                    flex: 1;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 10px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    background: linear-gradient(135deg, #04aa6d, #036551);
                    color: white;
                    transition: all 0.3s ease;
                ">Upload</button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const titleInput = modal.querySelector('#panoramaTitleInput');
        const descInput = modal.querySelector('#panoramaDescriptionInput');
        const cancelBtn = modal.querySelector('#panoramaMetadataCancelBtn');
        const confirmBtn = modal.querySelector('#panoramaMetadataConfirmBtn');

        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(overlay);
            // Don't call callback - cancel the upload
        });

        confirmBtn.addEventListener('click', () => {
            const title = titleInput.value.trim();
            const description = descInput.value.trim();
            document.body.removeChild(overlay);
            callback(title, description);
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(overlay);
                // Don't call callback - cancel the upload
            }
        });

        setTimeout(() => titleInput.focus(), 100);
    }

    function showPanoramaConfirmModal(title, message, onConfirm) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            z-index: 10001;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease;
        `;

        const modal = document.createElement('div');
        modal.style.cssText = `
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: scaleIn 0.2s ease;
            color: white;
        `;

        modal.innerHTML = `
            <div style="font-size: 28px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
                ${title}
            </div>
            <div style="font-size: 16px; margin-bottom: 20px; color: rgba(255, 255, 255, 0.9); line-height: 1.6;">
                ${message}
            </div>
            <div style="background: rgba(220, 53, 69, 0.15); padding: 10px 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; font-size: 14px; color: rgba(255, 107, 107, 0.9);">
                <span>⚠️</span> This action cannot be undone
            </div>
            <div style="display: flex; gap: 15px;">
                <button id="panoramaConfirmCancelBtn" style="
                    flex: 1;
                    padding: 15px 24px;
                    border: none;
                    border-radius: 12px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    background: rgba(255, 255, 255, 0.1);
                    color: white;
                    transition: all 0.3s ease;
                ">Cancel</button>
                <button id="panoramaConfirmBtn" style="
                    flex: 1;
                    padding: 15px 24px;
                    border: none;
                    border-radius: 12px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    background: linear-gradient(135deg, #dc3545, #bd2130);
                    color: white;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
                ">Confirm</button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const cancelBtn = modal.querySelector('#panoramaConfirmCancelBtn');
        const confirmBtn = modal.querySelector('#panoramaConfirmBtn');

        cancelBtn.addEventListener('click', () => document.body.removeChild(overlay));
        confirmBtn.addEventListener('click', () => {
            document.body.removeChild(overlay);
            onConfirm();
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) document.body.removeChild(overlay);
        });
    }
    
    // Edit Hotspots button functionality
    document.getElementById('edit-hotspots-btn').addEventListener('click', () => {
        if (!currentPanoData.currentImage) {
            showPanoramaModal('❌ No Panorama Image', 'No panorama image available. Please upload a panorama first.', 'error');
            return;
        }
        
        // Open hotspot editor in new window (using the fixed photosphere editor)
        const editorUrl = new URL('panorama_viewer_photosphere.php', window.location.origin + window.location.pathname.replace(/[^\/]*$/, ''));
        editorUrl.searchParams.set('image', `Pano/${currentPanoData.currentImage}`);
        editorUrl.searchParams.set('pathId', currentPanoData.pathId);
        editorUrl.searchParams.set('pointIndex', currentPanoData.pointIndex);
        editorUrl.searchParams.set('floor', currentPanoData.floor || 1);
        
        // Open editor in new window with cache-buster to avoid stale cached scripts
        const cacheBustedUrl = editorUrl.toString() + (editorUrl.toString().includes('?') ? '&' : '?') + 'cb=' + Date.now();
        const editorWindow = window.open(cacheBustedUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');

        if (!editorWindow) {
            showPanoramaModal('⚠️ Pop-up Blocked', 'Please allow pop-ups for this site to use the hotspot editor.', 'warning');
            return;
        }

        // Poll the popup for the reloadPhotosphereHotspots API and call it when available.
        const maxPollMs = 5000; // stop after 5s
        const start = Date.now();
        const pollInterval = 250;
        const pollHandle = setInterval(() => {
            try {
                if (editorWindow && !editorWindow.closed && typeof editorWindow.reloadPhotosphereHotspots === 'function') {
                    try {
                        editorWindow.reloadPhotosphereHotspots();
                    } catch (e) {
                        // ignore errors calling cross-window function
                    }
                    clearInterval(pollHandle);
                }
                // If popup navigated and defines the function on a nested window, try accessing its window object
                if (editorWindow && !editorWindow.closed && editorWindow.window && typeof editorWindow.window.reloadPhotosphereHotspots === 'function') {
                    try { editorWindow.window.reloadPhotosphereHotspots(); } catch (e) {}
                    clearInterval(pollHandle);
                }
            } catch (err) {
                // Accessing popup may throw until it's fully loaded; ignore
            }
            if (Date.now() - start > maxPollMs) {
                clearInterval(pollHandle);
            }
        }, pollInterval);
    });

    // Panorama upload functionality
    uploadBtn.addEventListener('click', () => {
        const file = fileInput.files[0];
        if (!file) {
            showPanoramaModal('❌ No File Selected', 'Please select a file to upload.', 'error');
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showPanoramaModal('❌ Invalid File Type', 'Please select a JPEG, PNG, or WebP image.', 'error');
            return;
        }

        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            showPanoramaModal('❌ File Too Large', 'File size too large. Maximum size is 10MB.', 'error');
            return;
        }

        // Show metadata input modal
        showPanoramaMetadataModal((title, description) => {
            // Create FormData for upload
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('panorama_file', file);
            formData.append('path_id', currentPanoData.pathId);
            formData.append('point_index', currentPanoData.pointIndex);
            // Use captured coordinates
            formData.append('point_x', currentPanoData.pointX || 0);
            formData.append('point_y', currentPanoData.pointY || 0);
            formData.append('floor_number', currentPanoData.floor || 1);
            
            // Optional fields
            if (title) formData.append('title', title);
            if (description) formData.append('description', description);
            
            uploadPanoramaFile(formData);
        });
    });

    function uploadPanoramaFile(formData) {

        // Show loading state
        uploadBtn.textContent = 'Uploading...';
        uploadBtn.disabled = true;

        // Upload to server
        // Show loading state
        uploadBtn.textContent = 'Uploading...';
        uploadBtn.disabled = true;

        fetch('panorama_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPanoramaModal('✅ Upload Successful', 'Panorama uploaded successfully!', 'success');
                // Update preview immediately
                if (data.filename) {
                    previewContainer.innerHTML = `<img src="Pano/${data.filename}" class="max-w-full max-h-48 object-contain">`;
                    removeBtn.style.display = 'inline-block';
                    currentPanoData.currentImage = data.filename;
                    
                    // Generate QR code automatically (like office system)
                    generatePanoramaQR();
                }
            } else {
                showPanoramaModal('❌ Upload Failed', data.error || 'Unknown error', 'error');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            showPanoramaModal('❌ Upload Failed', error.message, 'error');
        })
        .finally(() => {
            uploadBtn.textContent = 'Upload Image';
            uploadBtn.disabled = false;
        });
    }

    // Panorama removal functionality
    removeBtn.addEventListener('click', () => {
        showPanoramaConfirmModal(
            '🗑️ Remove Panorama?',
            'Are you sure you want to remove this panorama image? This action cannot be undone.',
            () => {
                // Create FormData for deletion
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('path_id', currentPanoData.pathId);
                formData.append('point_index', currentPanoData.pointIndex);
                formData.append('floor_number', currentPanoData.floor || 1);

                // Show loading state
                removeBtn.textContent = 'Removing...';
                removeBtn.disabled = true;
                
                removePanoramaFile(formData);
            }
        );
    });

    function removePanoramaFile(formData) {

        // Send delete request to server
        fetch('panorama_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPanoramaModal('✅ Removal Successful', 'Panorama removed successfully!', 'success');
                previewContainer.innerHTML = '<span class="text-gray-400">No panorama image assigned.</span>';
                removeBtn.style.display = 'none';
                currentPanoData.currentImage = '';
                
                // Delete QR code automatically (like office system)
                deletePanoramaQR();
            } else {
                showPanoramaModal('❌ Removal Failed', data.error || 'Unknown error', 'error');
            }
        })
        .catch(error => {
            console.error('Removal error:', error);
            showPanoramaModal('❌ Removal Failed', error.message, 'error');
        })
        .finally(() => {
            removeBtn.textContent = 'Remove';
            removeBtn.disabled = false;
        });
    }

    // --- Simple QR Code Management (like office system) ---
    const qrDownloadSection = document.getElementById('panorama-qr-download');
    const downloadPanoramaQRBtn = document.getElementById('download-panorama-qr-btn');

    // Show QR download button when panorama exists
    function updateQRDownloadButton() {
        if (currentPanoData.currentImage) {
            qrDownloadSection.style.display = 'block';
        } else {
            qrDownloadSection.style.display = 'none';
        }
    }

    // Generate QR code automatically (like office system)
    function generatePanoramaQR() {
        const formData = new FormData();
        formData.append('action', 'generate');
        formData.append('path_id', currentPanoData.pathId);
        formData.append('point_index', currentPanoData.pointIndex);
        formData.append('floor_number', currentPanoData.floor || 1);

        fetch('panorama_qr_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('QR code generated automatically for panorama');
            } else {
                console.warn('QR generation failed:', data.error);
            }
        })
        .catch(error => {
            console.error('QR generation error:', error);
        });
    }

    // Delete QR code automatically (like office system)
    function deletePanoramaQR() {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('path_id', currentPanoData.pathId);
        formData.append('point_index', currentPanoData.pointIndex);
        formData.append('floor_number', currentPanoData.floor || 1);

        fetch('panorama_qr_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('QR code deleted automatically for panorama');
            } else {
                console.warn('QR deletion failed:', data.error);
            }
        })
        .catch(error => {
            console.error('QR deletion error:', error);
        });
    }

    // Download QR code (direct download like office system)
    downloadPanoramaQRBtn.addEventListener('click', () => {
        const params = new URLSearchParams({
            action: 'download',
            path_id: currentPanoData.pathId,
            point_index: currentPanoData.pointIndex,
            floor_number: currentPanoData.floor || 1
        });
        
        // Create download link
        const downloadUrl = 'panorama_qr_api.php?' + params.toString();
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = `panorama_qr_floor${currentPanoData.floor}_path${currentPanoData.pathId}_point${currentPanoData.pointIndex}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Function to fetch current panorama status
    function fetchPanoramaStatus(pathId, pointIndex, floorNum) {
        const params = new URLSearchParams({
            action: 'get_status',
            path_id: pathId,
            point_index: pointIndex,
            floor_number: floorNum
        });
        
        fetch('panorama_api.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Prioritize is_active field from database
                    let isActive = true; // default
                    if (data.hasOwnProperty('is_active') && data.is_active !== null) {
                        isActive = data.is_active === 1 || data.is_active === '1' || data.is_active === true;
                    } else if (data.hasOwnProperty('status') && data.status !== null) {
                        // Fallback to status field
                        isActive = data.status === 'active';
                    }
                    
                    panoramaActiveToggle.checked = isActive;
                    panoramaStatusText.textContent = isActive ? 'Active' : 'Inactive';
                    panoramaStatusText.style.color = isActive ? '#4caf50' : '#f44336';
                    console.log(`Fetched panorama status: is_active=${data.is_active}, final=${isActive}`);
                } else {
                    // Default to active if no record exists
                    panoramaActiveToggle.checked = true;
                    panoramaStatusText.textContent = 'Active';
                    panoramaStatusText.style.color = '#4caf50';
                }
            })
            .catch(error => {
                console.warn('Error fetching panorama status:', error);
                // Default to active on error
                panoramaActiveToggle.checked = true;
                panoramaStatusText.textContent = 'Active';
                panoramaStatusText.style.color = '#4caf50';
            });
    }

    // Function to update panorama status
    function updatePanoramaStatus(pathId, pointIndex, floorNum, isActive) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('path_id', pathId);
        formData.append('point_index', pointIndex);
        formData.append('floor_number', floorNum);
        formData.append('is_active', isActive ? '1' : '0'); // Send as 1/0 for database
        formData.append('status', isActive ? 'active' : 'inactive'); // Keep status field for compatibility

        fetch('panorama_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Panorama status updated successfully');
                panoramaStatusText.textContent = isActive ? 'Active' : 'Inactive';
                panoramaStatusText.style.color = isActive ? '#4caf50' : '#f44336';
                
                // Clear the cached status data for this floor to force refresh
                const currentFloor = (typeof window.getCurrentFloor === 'function') ? window.getCurrentFloor() : 1;
                const cacheKey = `floor_${currentFloor}`;
                if (window.panoramaStatusCache && window.panoramaStatusCache[cacheKey]) {
                    delete window.panoramaStatusCache[cacheKey];
                    console.log(`Cleared cache for ${cacheKey} due to status update`);
                }
                
                // Update panorama marker visibility on the floor plan immediately
                updatePanoramaMarkerVisibility(pathId, pointIndex, isActive);
                
                // Pathfinding disabled in admin panel - no graph reload needed
                // if (typeof initPathfinding === 'function') {
                //     setTimeout(() => { 
                //         initPathfinding(currentFloor); 
                //         setTimeout(() => applyPanoramaStatusToMarkers(currentFloor), 200);
                //     }, 300);
                // }
            } else {
                alert('Failed to update panorama status: ' + (data.error || 'Unknown error'));
                // Revert toggle state on error
                panoramaActiveToggle.checked = !isActive;
            }
        })
        .catch(error => {
            console.error('Error updating panorama status:', error);
            alert('Error updating panorama status: ' + error.message);
            // Revert toggle state on error
            panoramaActiveToggle.checked = !isActive;
        });
    }

    // Function to update panorama marker visibility on floor plan
    function updatePanoramaMarkerVisibility(pathId, pointIndex, isActive) {
        const markerSelector = `.panorama-marker[data-path-id="${pathId}"][data-point-index="${pointIndex}"]`;
        const marker = document.querySelector(markerSelector);
        
        if (marker) {
            const bgCircle = marker.querySelector('.camera-bg');
            const cameraIcon = marker.querySelector('.camera-icon');
            
            if (isActive) {
                marker.style.opacity = '1';
                marker.classList.remove('inactive');
                marker.style.cursor = 'pointer';
                if (bgCircle) {
                    bgCircle.setAttribute('fill', '#2563eb'); // Blue for active
                    bgCircle.setAttribute('stroke', '#ffffff');
                }
                if (cameraIcon) {
                    cameraIcon.setAttribute('fill', '#ffffff');
                }
            } else {
                marker.style.opacity = '0.5';
                marker.classList.add('inactive');
                marker.style.cursor = 'not-allowed';
                if (bgCircle) {
                    bgCircle.setAttribute('fill', '#9ca3af'); // Gray for inactive
                    bgCircle.setAttribute('stroke', '#6b7280');
                }
                if (cameraIcon) {
                    cameraIcon.setAttribute('fill', '#d1d5db');
                }
            }
            
            // Force browser to recognize the changes
            marker.style.transform = 'translateZ(0)';
            setTimeout(() => {
                marker.style.transform = '';
            }, 1);
        } else {
            console.warn(`Marker not found: ${markerSelector}`);
        }
    }

    // Function to apply panorama status to all markers on current floor
    function applyPanoramaStatusToMarkers(floorNumber, retryCount = 0) {
        console.log(`Applying panorama status for floor ${floorNumber} (attempt ${retryCount + 1})`);
        
        // Get all panorama markers on the current floor
        const markers = document.querySelectorAll('.panorama-marker');
        if (markers.length === 0 && retryCount < 5) {
            console.log(`No panorama markers found, retrying in ${500 + (retryCount * 200)}ms...`);
            setTimeout(() => applyPanoramaStatusToMarkers(floorNumber, retryCount + 1), 500 + (retryCount * 200));
            return;
        }
        
        // Check if we have cached data for this floor
        const cacheKey = `floor_${floorNumber}`;
        if (window.panoramaStatusCache[cacheKey]) {
            console.log(`Using cached panorama status data for floor ${floorNumber}`);
            applyStatusFromCache(floorNumber, markers);
            return;
        }
        
        // Fetch status for all panoramas on this floor
        fetch(`panorama_api.php?action=list&floor_number=${floorNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.panoramas) {
                    console.log(`Found ${data.panoramas.length} panoramas for floor ${floorNumber}`);
                    
                    // Create a map of panorama statuses based on is_active column and cache it
                    const statusMap = {};
                    data.panoramas.forEach(pano => {
                        const key = `${pano.path_id}_${pano.point_index}`;
                        // Prioritize is_active column from database
                        let isActive = true; // default to active
                        if (pano.hasOwnProperty('is_active') && pano.is_active !== null) {
                            isActive = pano.is_active === 1 || pano.is_active === '1' || pano.is_active === true;
                        } else if (pano.hasOwnProperty('status') && pano.status !== null) {
                            // Fallback to status field if is_active not available
                            isActive = pano.status === 'active';
                        }
                        statusMap[key] = isActive;
                        console.log(`Panorama ${key}: is_active = ${pano.is_active}, final state = ${isActive}`);
                    });
                    
                    // Cache the status data
                    window.panoramaStatusCache[cacheKey] = statusMap;
                    
                    // Apply status to each marker
                    console.log('Applying status from is_active field to markers:', statusMap);
                    applyStatusToMarkers(markers, statusMap);
                } else {
                    console.warn('Failed to fetch panorama statuses:', data.error || 'Unknown error');
                    // On error, check cache or assume all are active
                    if (window.panoramaStatusCache[cacheKey]) {
                        applyStatusFromCache(floorNumber, markers);
                    } else {
                        applyDefaultActiveStatus(markers);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching panorama statuses:', error);
                // On error, check cache or assume all are active
                if (window.panoramaStatusCache[cacheKey]) {
                    console.log('Using cached data due to fetch error');
                    applyStatusFromCache(floorNumber, markers);
                } else {
                    applyDefaultActiveStatus(markers);
                }
            });
    }

    // Helper function to apply status from cache
    function applyStatusFromCache(floorNumber, markers) {
        const cacheKey = `floor_${floorNumber}`;
        const statusMap = window.panoramaStatusCache[cacheKey];
        if (statusMap) {
            applyStatusToMarkers(markers, statusMap);
        }
    }

    // Helper function to apply status to markers
    function applyStatusToMarkers(markers, statusMap) {
        markers.forEach(marker => {
            const pathId = marker.getAttribute('data-path-id');
            const pointIndex = marker.getAttribute('data-point-index');
            const key = `${pathId}_${pointIndex}`;
            
            // Check if we have status data for this marker
            if (statusMap.hasOwnProperty(key)) {
                const isActive = statusMap[key];
                console.log(`Applying is_active status to ${pathId} point ${pointIndex}: ${isActive ? 'active' : 'inactive'} (based on database is_active column)`);
                updatePanoramaMarkerVisibility(pathId, pointIndex, isActive);
            } else {
                // No status data found, assume active (default)
                console.log(`No is_active data for ${pathId} point ${pointIndex}, assuming active`);
                updatePanoramaMarkerVisibility(pathId, pointIndex, true);
            }
        });
    }

    // Helper function to apply default active status
    function applyDefaultActiveStatus(markers) {
        markers.forEach(marker => {
            const pathId = marker.getAttribute('data-path-id');
            const pointIndex = marker.getAttribute('data-point-index');
            updatePanoramaMarkerVisibility(pathId, pointIndex, true);
        });
    }

    // Panorama status toggle event handler
    panoramaActiveToggle.addEventListener('change', function() {
        const isActive = this.checked;
        updatePanoramaStatus(
            currentPanoData.pathId,
            currentPanoData.pointIndex,
            currentPanoData.floor,
            isActive
        );
    });

    // Enhance the openPanoramaEditor function to show QR download button
    const originalOpenPanoramaEditor = openPanoramaEditor;
    openPanoramaEditor = function(pathId, pointIndex, currentImage) {
        originalOpenPanoramaEditor(pathId, pointIndex, currentImage);
        // Update QR download button after modal data is set
        setTimeout(updateQRDownloadButton, 100);
    };

   </script>
   <script>
    // --- Swap Confirmation Modal (triggered from drag and drop) ---
    const swapConfirmationModal = document.getElementById('swap-confirmation-modal');
    const cancelSwapConfirmationBtn = document.getElementById('cancel-swap-confirmation-btn');
    const confirmSwapConfirmationBtn = document.getElementById('confirm-swap-confirmation-btn');
    
    let pendingSwapData = null;

    // Function to show swap confirmation (called from dragDropSetup.js)
    window.showSwapConfirmation = function(draggedOffice, targetOffice, draggedRoomId, targetRoomId, onConfirm, onCancel) {
        // Extract room numbers and floor from IDs
        const draggedMatch = draggedRoomId.match(/room-(\d+)(?:-(\d+))?/);
        const targetMatch = targetRoomId.match(/room-(\d+)(?:-(\d+))?/);
        
        const draggedRoomNum = draggedMatch ? `Room ${draggedMatch[1]}` : draggedRoomId;
        const targetRoomNum = targetMatch ? `Room ${targetMatch[1]}` : targetRoomId;
        
        // Populate modal
        document.getElementById('swap-from-room').textContent = draggedRoomNum;
        document.getElementById('swap-from-office').textContent = draggedOffice.name;
        document.getElementById('swap-to-room').textContent = targetRoomNum;
        document.getElementById('swap-to-office').textContent = targetOffice.name;
        
        // Store callbacks
        pendingSwapData = { onConfirm, onCancel };
        
        // Show modal
        swapConfirmationModal.classList.add('active');
    };

    // Cancel swap
    cancelSwapConfirmationBtn.addEventListener('click', function() {
        swapConfirmationModal.classList.remove('active');
        if (pendingSwapData && pendingSwapData.onCancel) {
            pendingSwapData.onCancel();
        }
        pendingSwapData = null;
    });

    // Confirm swap
    confirmSwapConfirmationBtn.addEventListener('click', function() {
        swapConfirmationModal.classList.remove('active');
        if (pendingSwapData && pendingSwapData.onConfirm) {
            pendingSwapData.onConfirm();
        }
        pendingSwapData = null;
    });

    // Close modal when clicking outside
    swapConfirmationModal.addEventListener('click', function(e) {
        if (e.target === swapConfirmationModal) {
            cancelSwapConfirmationBtn.click();
        }
    });
   </script>
   </body>
 </html>
