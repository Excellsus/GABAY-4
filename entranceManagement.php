<?php
/**
 * Entrance Management Page
 * 
 * Admin interface for managing building entrance QR codes.
 * Entrances serve as independent starting points for pathfinding navigation.
 */

// Require authentication - redirect to login if not authenticated
require_once 'auth_guard.php';
include 'connect_db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrance Management - GABAY</title>
  <link rel="stylesheet" href="officeManagement.css">
  <style>
    /* Additional styles specific to entrance management */
    .entrance-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    
    .entrance-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .entrance-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .entrance-card.inactive {
      opacity: 0.6;
      background: #f5f5f5;
    }
    
    .entrance-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 15px;
    }
    
    .entrance-title {
      font-size: 18px;
      font-weight: bold;
      color: #1a5632;
      margin-bottom: 5px;
    }
    
    .entrance-floor {
      display: inline-block;
      background: #50c878;
      color: white;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
    }
    
    .entrance-info {
      margin: 10px 0;
      font-size: 14px;
      color: #666;
    }
    
    .entrance-info-row {
      display: flex;
      justify-content: space-between;
      margin: 5px 0;
    }
    
    .entrance-actions {
      display: flex;
      gap: 8px;
      margin-top: 15px;
      flex-wrap: wrap;
    }
    
    .btn-small {
      padding: 6px 12px;
      font-size: 13px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.2s;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-download {
      background: #50c878;
      color: white;
    }
    
    .btn-download:hover {
      background: #45b368;
    }
    
    .btn-toggle {
      background: #ffa500;
      color: white;
    }
    
    .btn-toggle:hover {
      background: #ff8c00;
    }
    
    .btn-toggle.active {
      background: #28a745;
    }
    
    .btn-toggle.active:hover {
      background: #218838;
    }
    
    .btn-delete {
      background: #dc3545;
      color: white;
    }
    
    .btn-delete:hover {
      background: #c82333;
    }
    
    .btn-regenerate {
      background: #007bff;
      color: white;
    }
    
    .btn-regenerate:hover {
      background: #0056b3;
    }
    
    .filter-section {
      display: flex;
      gap: 15px;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    
    .filter-section select {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }
    
    .empty-state-icon {
      font-size: 64px;
      margin-bottom: 20px;
    }
    
    .empty-state h3 {
      font-size: 24px;
      margin-bottom: 10px;
      color: #666;
    }
    
    .empty-state p {
      font-size: 16px;
      margin-bottom: 20px;
    }
    
    .status-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
    }
    
    .status-active {
      background: #d4edda;
      color: #155724;
    }
    
    .status-inactive {
      background: #f8d7da;
      color: #721c24;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <img src="gabay_logo.png" alt="GABAY Logo" class="icon">
        </div>
        <div>
          <h1>GABAY</h1>
          <p>Admin Panel</p>
        </div>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="home.php">Dashboard</a></li>
          <li><a href="officeManagement.php">Office Management</a></li>
          <li><a href="floorPlan.php">Floor Plan</a></li>
          <li><a href="entranceManagement.php" class="active">Entrance Management</a></li>
          <li><a href="visitorFeedback.php">Visitor Feedback</a></li>
          <li><a href="systemSettings.php">System Settings</a></li>
          <li><a href="logout.php">Logout</a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-header">
        <h2>Entrance Management</h2>
        <p>Manage building entrance QR codes for pathfinding navigation</p>
      </div>

      <div class="actions">
        <button class="btn" id="generateAllEntrancesBtn">
          <span>🔄</span> Generate All Entrance QR Codes
        </button>
        <button class="btn" id="refreshEntrancesBtn">
          <span>↻</span> Refresh List
        </button>
      </div>

      <!-- Filter Section -->
      <div class="filter-section">
        <label for="floorFilter"><strong>Filter by Floor:</strong></label>
        <select id="floorFilter">
          <option value="all">All Floors</option>
          <option value="1">Floor 1</option>
          <option value="2">Floor 2</option>
          <option value="3">Floor 3</option>
        </select>
        <span id="entranceCount" style="color: #666; margin-left: auto;"></span>
      </div>

      <!-- Entrance Cards Grid -->
      <div id="entranceGrid" class="entrance-grid">
        <!-- Entrance cards will be loaded here via JavaScript -->
      </div>

      <!-- Empty State -->
      <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-state-icon">🚪</div>
        <h3>No Entrances Found</h3>
        <p>Add entrance definitions to your floor graph JSON files, then generate QR codes.</p>
        <p style="font-size: 14px; color: #999;">
          Edit <code>floor_graph.json</code>, <code>floor_graph_2.json</code>, <code>floor_graph_3.json</code><br>
          and add an <code>"entrances"</code> array with entrance objects.
        </p>
      </div>
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // CSRF Token for POST requests
    window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';
    
    // Load all entrances on page load
    $(document).ready(function() {
      loadEntrances();
      
      // Event listeners
      $('#generateAllEntrancesBtn').on('click', generateAllEntrances);
      $('#refreshEntrancesBtn').on('click', loadEntrances);
      $('#floorFilter').on('change', filterEntrancesByFloor);
    });
    
    /**
     * Load all entrance QR codes from API
     */
    function loadEntrances() {
      $.ajax({
        url: 'entrance_qr_api.php',
        method: 'GET',
        data: { action: 'get_all' },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            renderEntrances(response.entrances);
          } else {
            alert('Error loading entrances: ' + response.error);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          alert('Failed to load entrances. Check console for details.');
        }
      });
    }
    
    /**
     * Render entrance cards in grid
     */
    function renderEntrances(entrances) {
      const $grid = $('#entranceGrid');
      const $emptyState = $('#emptyState');
      const $count = $('#entranceCount');
      
      $grid.empty();
      
      if (entrances.length === 0) {
        $grid.hide();
        $emptyState.show();
        $count.text('0 entrances');
        return;
      }
      
      $grid.show();
      $emptyState.hide();
      $count.text(entrances.length + ' entrance' + (entrances.length !== 1 ? 's' : ''));
      
      entrances.forEach(function(entrance) {
        const isActive = entrance.is_active == 1;
        const statusClass = isActive ? 'status-active' : 'status-inactive';
        const statusText = isActive ? 'Active' : 'Inactive';
        const cardClass = isActive ? '' : 'inactive';
        
        const card = `
          <div class="entrance-card ${cardClass}" data-floor="${entrance.floor}" data-entrance-id="${entrance.entrance_id}">
            <div class="entrance-header">
              <div>
                <div class="entrance-title">${escapeHtml(entrance.label)}</div>
                <span class="entrance-floor">Floor ${entrance.floor}</span>
              </div>
              <span class="status-badge ${statusClass}">${statusText}</span>
            </div>
            
            <div class="entrance-info">
              <div class="entrance-info-row">
                <span><strong>ID:</strong></span>
                <span>${escapeHtml(entrance.entrance_id)}</span>
              </div>
              <div class="entrance-info-row">
                <span><strong>Coordinates:</strong></span>
                <span>(${entrance.x}, ${entrance.y})</span>
              </div>
              <div class="entrance-info-row">
                <span><strong>Path:</strong></span>
                <span>${entrance.nearest_path_id || 'N/A'}</span>
              </div>
              <div class="entrance-info-row">
                <span><strong>Created:</strong></span>
                <span>${formatDate(entrance.created_at)}</span>
              </div>
            </div>
            
            <div class="entrance-actions">
              <a href="${entrance.qr_code_path}" download class="btn-small btn-download">
                ⬇ Download QR
              </a>
              <button class="btn-small btn-toggle ${isActive ? 'active' : ''}" 
                      onclick="toggleEntranceStatus('${entrance.entrance_id}', ${isActive ? 0 : 1})">
                ${isActive ? '✓ Active' : '✗ Inactive'}
              </button>
              <button class="btn-small btn-regenerate" 
                      onclick="regenerateEntranceQR('${entrance.entrance_id}')">
                🔄 Regenerate
              </button>
              <button class="btn-small btn-delete" 
                      onclick="deleteEntrance('${entrance.entrance_id}', '${escapeHtml(entrance.label)}')">
                🗑 Delete
              </button>
            </div>
          </div>
        `;
        
        $grid.append(card);
      });
    }
    
    /**
     * Generate all entrance QR codes
     */
    function generateAllEntrances() {
      if (!confirm('Generate QR codes for all entrances defined in floor graphs?\n\nThis will create new QR codes for entrances that don\'t have them yet.')) {
        return;
      }
      
      $('#generateAllEntrancesBtn').prop('disabled', true).text('⏳ Generating...');
      
      $.ajax({
        url: 'entrance_qr_api.php',
        method: 'POST',
        data: { 
          action: 'generate',
          csrf_token: window.CSRF_TOKEN
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert(response.message);
            loadEntrances(); // Reload list
          } else {
            alert('Error: ' + response.error);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          alert('Failed to generate QR codes. Check console for details.');
        },
        complete: function() {
          $('#generateAllEntrancesBtn').prop('disabled', false).html('<span>🔄</span> Generate All Entrance QR Codes');
        }
      });
    }
    
    /**
     * Toggle entrance active status
     */
    function toggleEntranceStatus(entranceId, newStatus) {
      $.ajax({
        url: 'entrance_qr_api.php',
        method: 'POST',
        data: {
          action: 'toggle_status',
          entrance_id: entranceId,
          is_active: newStatus,
          csrf_token: window.CSRF_TOKEN
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            loadEntrances(); // Reload to show updated status
          } else {
            alert('Error: ' + response.error);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          alert('Failed to toggle status. Check console for details.');
        }
      });
    }
    
    /**
     * Regenerate entrance QR code
     */
    function regenerateEntranceQR(entranceId) {
      if (!confirm('Regenerate QR code for this entrance?')) {
        return;
      }
      
      $.ajax({
        url: 'entrance_qr_api.php',
        method: 'POST',
        data: {
          action: 'regenerate',
          entrance_id: entranceId,
          csrf_token: window.CSRF_TOKEN
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert(response.message);
            loadEntrances();
          } else {
            alert('Error: ' + response.error);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          alert('Failed to regenerate QR code. Check console for details.');
        }
      });
    }
    
    /**
     * Delete entrance QR code
     */
    function deleteEntrance(entranceId, label) {
      if (!confirm(`Delete entrance QR code for "${label}"?\n\nThis will remove the QR code and all scan logs. This action cannot be undone.`)) {
        return;
      }
      
      $.ajax({
        url: 'entrance_qr_api.php',
        method: 'POST',
        data: {
          action: 'delete',
          entrance_id: entranceId,
          csrf_token: window.CSRF_TOKEN
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert(response.message);
            loadEntrances();
          } else {
            alert('Error: ' + response.error);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          alert('Failed to delete entrance. Check console for details.');
        }
      });
    }
    
    /**
     * Filter entrances by floor
     */
    function filterEntrancesByFloor() {
      const selectedFloor = $('#floorFilter').val();
      
      if (selectedFloor === 'all') {
        $('.entrance-card').show();
      } else {
        $('.entrance-card').hide();
        $(`.entrance-card[data-floor="${selectedFloor}"]`).show();
      }
      
      // Update count
      const visibleCount = $('.entrance-card:visible').length;
      $('#entranceCount').text(visibleCount + ' entrance' + (visibleCount !== 1 ? 's' : ''));
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    /**
     * Format date string
     */
    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
  </script>
</body>
</html>
