<?php
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
    .room-label {
      fill: white;
      stroke: black;
      stroke-width: 0.5px;
      font-weight: bold;
      pointer-events: none;
    }
    </style>
    </head><body style="margin:0;padding:0;">
    <div id="svg-container" style="width: 100%; height: 100%;"></div>
    <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
    <script>
    // Get office data from PHP
    const officesData = <?php echo json_encode($offices); ?>;
    
    // Function to update room label
    function updateRoomLabelMain(group, officeName) {
      // First try to find existing text element in the SVG by roomlabel ID
      const roomElement = group.querySelector("path, rect");
      if (!roomElement || !roomElement.id) return;
      
      const roomMatch = roomElement.id.match(/room-(\d+)/);
      if (!roomMatch) return;
      
      const roomNumber = roomMatch[1];
      
      // Look for existing roomlabel text element in the entire SVG
  let textEl = document.querySelector(`#roomlabel-${roomNumber}`);
      
      if (!textEl) {
        // If no existing text element, look for any text element in the group
        textEl = group.querySelector("text");
      }

      if (!textEl) {
        // Remove any duplicate with same id elsewhere (from previous runs)
        const dup = document.querySelector(`#roomlabel-${roomNumber}`);
        if (dup) dup.remove();

        // Create text element inside the same group so transforms apply
        textEl = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textEl.setAttribute("class", "room-label");
        textEl.setAttribute("id", `roomlabel-${roomNumber}`);

        const bbox = roomElement.getBBox();
        textEl.setAttribute("x", bbox.x + bbox.width/2);
        textEl.setAttribute("y", bbox.y + bbox.height/2);

        group.appendChild(textEl);
      }

      // Clear existing tspans
      while (textEl.firstChild) {
        textEl.removeChild(textEl.firstChild);
      }

      // Set text-anchor to middle for centering
      textEl.setAttribute("text-anchor", "middle");

      // Create new tspans for each line
      officeName.split(' ').forEach((line, index) => {
        const tspan = document.createElementNS("http://www.w3.org/2000/svg", "tspan");
        tspan.textContent = line;
        textEl.appendChild(tspan);
      });

      // Adjust position after adding text
      const textBBox = textEl.getBBox();
      const roomBBox = roomElement.getBBox();
      textEl.setAttribute("x", roomBBox.x + roomBBox.width / 2);
      textEl.setAttribute("y", roomBBox.y + roomBBox.height / 2 - textBBox.height / 2 + textBBox.height * 0.25);
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
            let id = el.id;
            let label = id;
            // Try to get label from associated roomlabel element
            const rm = el.id.match(/^room-(\d+)(?:-\d+)?$/);
            if (rm) {
              let roomNum = rm[1];
              let labelEl = svg.querySelector('#roomlabel-' + roomNum);
              if (labelEl) label = labelEl.textContent.trim();
            }
            // Highlight selection
            svg.querySelectorAll('.selectable-room').forEach(x=>x.classList.remove('selected'));
            el.classList.add('selected');
            // Send selection to parent
            window.parent.postMessage({selectedRoomId: id, selectedRoomLabel: label}, '*');
          });
        });

        // Initialize Pan and Zoom
        initializePanZoom(svg);
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
    <title>GABAY Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="floorPlan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="mobileNav.css" />
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
        <div class="flex justify-between items-center w-full">
          <div>
            <h2 class="font-bold">Floor Plan</h2>
            <p class="text-gray-500">View and manage floor plans</p>
          </div>
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
                    <h4>Status</h4>
                    <label class="switch">
                        <input type="checkbox" id="office-active-toggle">
                        <span class="slider round"></span>
                    </label>
                    <span id="office-status-text">Active</span>
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



     <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
     <script src="floorjs/panZoomSetup.js"></script> <!-- Link to the new JS file -->
     <script>
        // Pass PHP office data to JavaScript
        const officesData = <?php echo json_encode($offices); ?>;
        console.log("Offices Data Loaded:", officesData); // For debugging
        
      // Function to update room labels with proper centering
        function updateRoomLabelMain(group, officeName) {
            // First try to find existing text element in the SVG by roomlabel ID
            const roomElement = group.querySelector("path, rect");
            if (!roomElement || !roomElement.id) return;
            
            const roomMatch = roomElement.id.match(/room-(\d+)/);
            if (!roomMatch) return;
            
            const roomNumber = roomMatch[1];
            
            // Look for existing roomlabel text element in the entire SVG
            let textEl = document.querySelector(`#roomlabel-${roomNumber}`);
            
      if (!textEl) {
        // If no existing text element, look for any text element in the group
        textEl = group.querySelector("text");
      }

      if (!textEl) {
        // Remove any duplicate elsewhere first
        const dup = document.querySelector(`#roomlabel-${roomNumber}`);
        if (dup) dup.remove();

        // Create inside the group so any transforms apply correctly
        console.warn(`No existing text element found for room ${roomNumber}, creating new one`);
        textEl = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textEl.setAttribute("class", "room-label");
        textEl.setAttribute("id", `roomlabel-${roomNumber}`);
                
        // Get the room path/rect to position the label at its center
        const bbox = roomElement.getBBox();
        textEl.setAttribute("x", bbox.x + bbox.width/2);
        textEl.setAttribute("y", bbox.y + bbox.height/2);
                
        // Append to the room group
        group.appendChild(textEl);
      }

            // Store original x coordinate for centering
            const originalX = parseFloat(textEl.getAttribute("x")) || 0;

            // Set text-anchor to middle for automatic centering. This is key.
            textEl.setAttribute("text-anchor", "middle");

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
                textEl.appendChild(newTspan);
            });
        }
        
        // Function to update room labels with office names (using updateRoomLabelMain for centering)
    function updateRoomLabels(svg) {
            if (!svg) svg = document.querySelector('svg');
            if (!svg) return;
            
            console.log('updateRoomLabels called, processing rooms...');
            
      svg.querySelectorAll('path[id^="room-"]').forEach(function(el) {
        const match = el.id.match(/^room-(\d+)(?:-\d+)?$/);
        if (!match) return;
        const roomNum = match[1];

        // Match by exact id first, then by common variants across floors
        const office = officesData.find(o => o.location === el.id);
                
        console.log(`Room ${roomNum} (${el.id}):`, office ? `Found office: ${office.name}` : 'No office assigned');
                
        if (office) {
          // Find the parent group and text element
          const parentGroup = el.closest('g[id^="room-"]') || el.closest('g');
          if (parentGroup) {
            // Use the updateRoomLabelMain function for proper centering
            updateRoomLabelMain(parentGroup, office.name);
            console.log(`Updated label for room ${roomNum} to "${office.name}" with centering`);
          } else {
            console.log(`No parent group found for room ${roomNum}`);
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
                console.log('Room label initialization complete');
            }, 200); // Increased delay to ensure SVG is ready
        });
     </script>
   <script>
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

                            // Initialize SVG attributes
                            svg.setAttribute('width', '100%');
                            svg.setAttribute('height', '100%');
                            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                            
                            // Make rooms interactive and add labels
                            svg.querySelectorAll('path[id^="room-"]').forEach(function(el) {
                                el.classList.add('selectable-room');
                            });
                            
                            // Update room labels with office names
                            updateRoomLabels(svg);
                            
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
                                        
                                    } catch (e) {
                                        console.warn('SVG Pan-Zoom initialization error:', e);
                                        // Still dispatch event if pan-zoom fails
                                        window.dispatchEvent(new CustomEvent('floorMapLoaded', { detail: { floor: parseInt(floor, 10) } }));
                                    }
                                }, 100);
                            } else {
                                // Dispatch event even if pan-zoom is not available
                                window.dispatchEvent(new CustomEvent('floorMapLoaded', { detail: { floor: parseInt(floor, 10) } }));
                            }
                            
                            // Initialize additional functionality
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
                    loadFloorMap(floor);
                });
            });
        });
     </script>
   <script src="./floorjs/labelSetup.js"></script> <!-- Add the labeling script -->
   <script src="./floorjs/dragDropSetup.js"></script> <!-- Add the drag/drop script for edit mode -->
   <script src="pathfinding.js"></script> <!-- Add the pathfinding script -->
   </body>
 </html>
