<?php
// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Go up one directory to include connect_db.php from the parent folder
include __DIR__ . '/../connect_db.php'; // Include database connection

$offices = []; // Initialize as empty array
$error_message = null; // Variable to hold potential error messages

// Check if this is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Get search query if exists
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Check if $connect is a valid PDO object
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection object (\$connect) is not valid. Check connect_db.php.");
    }    // Modify query to include search if present and fetch image
    if (!empty($search_query)) {
        $stmt = $connect->prepare("SELECT o.id, o.name, o.location, 
            (SELECT image_path FROM office_image WHERE office_id = o.id ORDER BY uploaded_at DESC, id DESC LIMIT 1) AS image_path 
            FROM offices o WHERE o.name LIKE :search ORDER BY o.name ASC");
        $search_param = $search_query . "%"; // Changed from %search% to search% to match starting letters
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    } else {
        $stmt = $connect->query("SELECT o.id, o.name, o.location, 
            (SELECT image_path FROM office_image WHERE office_id = o.id ORDER BY uploaded_at DESC, id DESC LIMIT 1) AS image_path 
            FROM offices o ORDER BY o.name ASC");
    }

    if ($stmt === false) {
        $errorInfo = $connect->errorInfo();
        throw new PDOException("Query failed: " . ($errorInfo[2] ?? 'Unknown error'));
    }
    
    if (!empty($search_query)) {
        $stmt->execute();
    }
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If this is an AJAX request, return JSON response
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $offices
        ]);
        exit;
    }

} catch (Exception $e) {
    $error_message = "Error fetching offices: " . $e->getMessage();
    error_log("Error in rooms.php: " . $e->getMessage());
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $error_message
        ]);
        exit;
    }
}

// Only continue with HTML output if not an AJAX request
if ($is_ajax) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation View</title>
    <!-- Link to your CSS files -->
    <link rel="stylesheet" href="rooms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- GABAY Geofencing System -->
    <!-- <script src="js/geofencing.js"></script> -->
</head>
<body>
    <header class="header">
        <div class="header-back">
            <a href="explore.php" aria-label="Back to Explore"><i class="fas fa-arrow-left"></i></a>
        </div>
        <div class="header-content">
            <h2 class="section-title">Rooms</h2>
            <p class="section-subtitle">Explore the rooms available in the building.</p>
        </div>
        <!-- Search icon -->
        <div class="header-actions">
            <button id="searchBtn" class="search-button" aria-label="Search rooms">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </header>

    <!-- Search Modal -->
    <div id="searchModal" class="search-modal">
        <div class="search-modal-content">
            <div class="search-header">
                <div class="search-form">
                    <div class="search-input-container">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput"
                               placeholder="Search rooms..." 
                               value="<?php echo htmlspecialchars($search_query); ?>"
                               class="search-input"
                               autocomplete="off">
                        <?php if (!empty($search_query)): ?>
                            <button type="button" class="clear-search" aria-label="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="close-search" aria-label="Close search">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Loading indicator -->
            <div id="searchLoading" class="search-loading">
                <div class="loading-spinner"></div>
            </div>
        </div>
    </div>

    <!-- Main content area -->
    <main class="content">
        <div class="rooms-grid" id="roomsGrid">
            <?php if ($error_message): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif (empty($offices)): ?>
                <div class="no-results">
                    <?php if (!empty($search_query)): ?>
                        <p>No rooms found matching "<?php echo htmlspecialchars($search_query); ?>"</p>
                        <a href="rooms.php" class="clear-search-link">Clear search</a>
                    <?php else: ?>
                        <p>No offices found in the database.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>                <?php foreach ($offices as $office): ?>
                    <?php
                    // Extract floor number and room number from location
                    // Pattern: room-<roomnumber>-<floornumber>
                    // The second number (after the second dash) indicates the floor
                    $floor_number = 'N/A';
                    $room_number = 'N/A';
                    if (!empty($office['location'])) {
                        $loc = trim($office['location']);

                        // Match room-<roomnumber>-<floornumber> pattern
                        // Example: room-101-1 -> room 101, floor 1
                        if (preg_match('/room-(\d+)-(\d+)/i', $loc, $m)) {
                            $room_number = (string) intval($m[1]);
                            $floor_number = (string) intval($m[2]);
                        }
                        // Fallback: if no floor number found, try textual forms
                        elseif (preg_match('/(\d+)(?:st|nd|rd|th)?\s*floor/i', $loc, $m)) {
                            $floor_number = (string) intval($m[1]);
                        }
                    }
                    ?>
                    <a href="office_details.php?id=<?php echo htmlspecialchars($office['id']); ?>" class="room-card-link">
                        <div class="room-card">
                            <?php if (!empty($office['image_path']) && file_exists(__DIR__ . '/../office_images/' . $office['image_path'])): ?>
                                <img src="../office_images/<?php echo htmlspecialchars($office['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($office['name']); ?>" 
                                     class="room-image">
                            <?php else: ?>
                                <i class="fas fa-door-open room-icon"></i>
                            <?php endif; ?>
                            <div class="room-info">
                                <h3 class="room-name"><?php echo htmlspecialchars($office['name'] ?? 'N/A'); ?></h3>
                                <div class="indicators-container">
                                    <div class="floor-indicator">
                                        <i class="fas fa-layer-group"></i>
                                        <span>Floor <?php echo htmlspecialchars($floor_number); ?></span>
                                    </div>
                                    <div class="room-indicator">
                                        <i class="fas fa-door-closed"></i>
                                        <span>Room: <?php echo htmlspecialchars($room_number); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Add this page to navigation history
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize history if not exists
            window.gabayHistory = window.gabayHistory || [];
            
            // Add rooms page to history
            const currentPage = {
                page: 'rooms',
                title: 'Rooms Directory',
                timestamp: Date.now()
            };
            
            // Only add if it's not the same as the last entry
            const lastEntry = window.gabayHistory[window.gabayHistory.length - 1];
            if (!lastEntry || lastEntry.page !== 'rooms') {
                window.gabayHistory.push(currentPage);
            }
            
            // Update breadcrumbs if function exists
            if (typeof updateBreadcrumbs === 'function') {
                updateBreadcrumbs('rooms', 'Rooms Directory');
            }
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchBtn = document.getElementById('searchBtn');
            const searchModal = document.getElementById('searchModal');
            const closeSearch = document.querySelector('.close-search');
            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.querySelector('.clear-search');
            const roomsGrid = document.getElementById('roomsGrid');
            const searchLoading = document.getElementById('searchLoading');

            let searchTimeout = null;            let originalContent = roomsGrid.innerHTML;

            // Function to create room card HTML
            function createRoomCard(office) {
                // Extract floor number and room number from location
                // Pattern: room-<roomnumber>-<floornumber>
                // The second number (after the second dash) indicates the floor
                let floorNumber = 'N/A';
                let roomNumber = 'N/A';
                if (office.location) {
                    // Match room-<roomnumber>-<floornumber> pattern
                    // Example: room-101-1 -> room 101, floor 1
                    const roomMatch = office.location.match(/room-(\d+)-(\d+)/i);
                    if (roomMatch) {
                        roomNumber = roomMatch[1];
                        floorNumber = roomMatch[2];
                    } else {
                        // Fallback: if location contains direct floor info
                        const floorMatch = office.location.match(/(\d+)(st|nd|rd|th)?\s*floor/i);
                        if (floorMatch) {
                            floorNumber = floorMatch[1];
                        }
                    }
                }
                
                // Create image or icon HTML
                const imageOrIconHTML = office.image_path 
                    ? `<img src="../office_images/${office.image_path}" alt="${office.name || 'Office'}" class="room-image">`
                    : '<i class="fas fa-door-open room-icon"></i>';
                
                return `
                    <a href="office_details.php?id=${office.id}" class="room-card-link">
                        <div class="room-card">
                            ${imageOrIconHTML}
                            <div class="room-info">
                                <h3 class="room-name">${office.name || 'N/A'}</h3>
                                <div class="indicators-container">
                                    <div class="floor-indicator">
                                        <i class="fas fa-layer-group"></i>
                                        <span>Floor ${floorNumber}</span>
                                    </div>
                                    <div class="room-indicator">
                                        <i class="fas fa-door-closed"></i>
                                        <span>Room: ${roomNumber}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                `;
            }

            // Function to perform search
            function performSearch(query) {
                if (searchLoading) {
                    searchLoading.style.display = 'flex';
                }

                if (!query) {
                    roomsGrid.innerHTML = originalContent;
                    if (searchLoading) {
                        searchLoading.style.display = 'none';
                    }
                    return;
                }

                // Convert query to uppercase for case-insensitive comparison
                query = query.toUpperCase();

                fetch(`rooms.php?search=${encodeURIComponent(query)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.length === 0) {
                            roomsGrid.innerHTML = `
                                <div class="no-results">
                                    <p>No rooms found starting with "${query}"</p>
                                    <a href="javascript:void(0)" class="clear-search-link" onclick="document.getElementById('searchInput').value=''; document.getElementById('searchInput').focus();">Clear search</a>
                                </div>
                            `;
                        } else {
                            // Sort the results so exact matches appear first
                            const sortedData = data.data.sort((a, b) => {
                                const aStartsWithQuery = a.name.toUpperCase().startsWith(query);
                                const bStartsWithQuery = b.name.toUpperCase().startsWith(query);
                                
                                if (aStartsWithQuery && !bStartsWithQuery) return -1;
                                if (!aStartsWithQuery && bStartsWithQuery) return 1;
                                return a.name.localeCompare(b.name);
                            });

                            roomsGrid.innerHTML = sortedData.map(office => createRoomCard(office)).join('');
                        }
                    } else {
                        roomsGrid.innerHTML = `<p class="error-message">${data.error}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    roomsGrid.innerHTML = '<p class="error-message">Error performing search. Please try again.</p>';
                })
                .finally(() => {
                    if (searchLoading) {
                        searchLoading.style.display = 'none';
                    }
                });
            }

            // Live search with debouncing
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 200); // Reduced delay to 200ms for faster response
            });

            // Open search modal
            searchBtn.addEventListener('click', function() {
                searchModal.classList.add('active');
                searchInput.focus();
                // Store original content when opening search
                originalContent = roomsGrid.innerHTML;
            });

            // Close search modal
            closeSearch.addEventListener('click', function() {
                searchModal.classList.remove('active');
                searchInput.value = ''; // Clear search input
                roomsGrid.innerHTML = originalContent; // Restore original content
            });

            // Clear search
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    searchInput.focus();
                    roomsGrid.innerHTML = originalContent;
                });
            }

            // Close modal when clicking outside
            searchModal.addEventListener('click', function(e) {
                if (e.target === searchModal) {
                    searchModal.classList.remove('active');
                    searchInput.value = ''; // Clear search input
                    roomsGrid.innerHTML = originalContent; // Restore original content
                }
            });

            // Close modal on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchModal.classList.remove('active');
                    searchInput.value = ''; // Clear search input
                    roomsGrid.innerHTML = originalContent; // Restore original content
                }
            });

            // ===== GEOFENCING ENFORCEMENT =====
            // This will block the UI until the user's location is verified as inside an allowed zone.
            (async function setupGeofenceEnforcement(){
                // First, check if geofencing is enabled
                try {
                    const statusResp = await fetch('../check_geofence_status.php');
                    const statusData = await statusResp.json();
                    
                    if (statusData.success && !statusData.enabled) {
                        console.log('Geofencing is disabled by admin - skipping enforcement');
                        return; // Exit without showing overlay
                    }
                } catch (err) {
                    console.warn('Could not check geofence status, proceeding with enforcement as safety measure', err);
                    // Continue with geofencing if check fails (fail-safe)
                }

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
                    actions.innerHTML = `<button id="geofence-retry" style="padding:10px 16px;border-radius:8px;background:#fff;color:#111;border:none;cursor:pointer;font-weight:bold;">Retry</button>`;
                    document.getElementById('geofence-retry').addEventListener('click', ()=>{ startCheck(); });
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
                            const resp = await fetch('../verify_location.php', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json'},
                                body: JSON.stringify({ lat, lng, office_id: null, page: 'rooms' })
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

                // Auto-start the check when DOM is ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', startCheck);
                } else {
                    startCheck();
                }
            })();
        });
    </script>
</body>
</html>