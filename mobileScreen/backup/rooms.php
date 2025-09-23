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
    }    // Modify query to include search if present
    if (!empty($search_query)) {
        $stmt = $connect->prepare("SELECT id, name FROM offices WHERE name LIKE :search ORDER BY name ASC");
        $search_param = $search_query . "%"; // Changed from %search% to search% to match starting letters
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    } else {
        $stmt = $connect->query("SELECT id, name FROM offices ORDER BY name ASC");
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
</head>
<body>
    <header class="header">
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
                    <a href="office_details.php?id=<?php echo htmlspecialchars($office['id']); ?>" class="room-card-link">
                        <div class="room-card">
                            <i class="fas fa-door-open room-icon"></i>
                            <div class="room-info">
                                <h3 class="room-name"><?php echo htmlspecialchars($office['name'] ?? 'N/A'); ?></h3>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bottom Navigation Section -->
    <nav class="bottom-nav" role="navigation" aria-label="Visitor navigation">
        <!-- Explore Link -->
        <a href="explore.php" aria-label="Explore">
            <i class="fas fa-map-marker-alt"></i>
            <span>Explore</span>
        </a>

        <!-- Rooms Link -->
        <a href="rooms.php" class="active" aria-label="Rooms">
            <i class="fas fa-building"></i>
            <span>Rooms</span>
        </a>

        <!-- About Link -->
        <a href="about.php" aria-label="About">
            <i class="fas fa-bars"></i>
            <span>About</span>
        </a>
    </nav>

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
                return `
                    <a href="office_details.php?id=${office.id}" class="room-card-link">
                        <div class="room-card">
                            <i class="fas fa-door-open room-icon"></i>
                            <div class="room-info">
                                <h3 class="room-name">${office.name || 'N/A'}</h3>
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
        });
    </script>
</body>
</html>