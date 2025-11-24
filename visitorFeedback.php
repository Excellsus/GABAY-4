<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

require_once "connect_db.php";

// Get filter settings
$sortOrder = $_GET['sort'] ?? 'newest';
$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$selectedMonth = $_GET['month'] ?? 'all';
$selectedYear = $_GET['year'] ?? 'all';
$viewMode = $_GET['view'] ?? 'active'; // active, archived, trash

// Build the query with view mode filter
$query = "SELECT * FROM feedback WHERE 1=1";
$params = [];

// Apply view mode filter
if ($viewMode === 'archived') {
  $query .= " AND is_archived = 1 AND deleted_at IS NULL";
} elseif ($viewMode === 'trash') {
  $query .= " AND deleted_at IS NOT NULL";
} else {
  // Default: show only active (not archived, not deleted)
  $query .= " AND is_archived = 0 AND deleted_at IS NULL";
}

if ($ratingFilter > 0) {
  $query .= " AND rating = ?";
  $params[] = $ratingFilter;
}

if ($selectedMonth !== 'all') {
  $query .= " AND MONTH(submitted_at) = ?";
  $params[] = $selectedMonth;
}

if ($selectedYear !== 'all') {
  $query .= " AND YEAR(submitted_at) = ?";
  $params[] = $selectedYear;
}

$query .= $sortOrder === 'oldest' ? " ORDER BY submitted_at ASC" : " ORDER BY submitted_at DESC";

$stmt = $connect->prepare($query);
$stmt->execute($params);
$feedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalFeedback = count($feedbackList);
$averageRating = $totalFeedback > 0 ? array_sum(array_column($feedbackList, 'rating')) / $totalFeedback : 0;
$highestRating = $totalFeedback > 0 ? max(array_column($feedbackList, 'rating')) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
  <title>Visitor Feedback</title>
  <link rel="stylesheet" href="visitorFeedback.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="filter-styles.css">
  <link rel="stylesheet" href="mobileNav.css" />
  <script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
  <script src="auth_helper.js"></script>
  
  <style>
    /* Batch selection and action styles */
    .batch-actions-bar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: linear-gradient(135deg, #1a5632 0%, #2d5a2d 100%);
      color: white;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: none;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      animation: slideDown 0.3s ease;
    }
    
    .batch-actions-bar.active {
      display: flex;
    }
    
    @keyframes slideDown {
      from { transform: translateY(-20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    
    .batch-info {
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 14px;
    }
    
    .batch-actions {
      display: flex;
      gap: 10px;
    }
    
    .batch-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .batch-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .batch-btn-archive {
      background: #3b82f6;
      color: white;
    }
    
    .batch-btn-delete {
      background: #ef4444;
      color: white;
    }
    
    .batch-btn-restore {
      background: #10b981;
      color: white;
    }
    
    .batch-btn-cancel {
      background: rgba(255,255,255,0.2);
      color: white;
    }
    
    .feedback-checkbox {
      width: 18px;
      height: 18px;
      cursor: pointer;
      accent-color: #1a5632;
    }
    
    .feedback-item {
      position: relative;
      transition: all 0.3s ease;
    }
    
    .feedback-item.selected {
      background: rgba(26, 86, 50, 0.05);
      border-left: 4px solid #1a5632;
    }
    
    .feedback-actions {
      display: flex;
      gap: 8px;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 1px solid #e5e7eb;
    }
    
    .action-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    
    .action-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .action-btn-archive {
      background: #dbeafe;
      color: #1e40af;
    }
    
    .action-btn-delete {
      background: #fee2e2;
      color: #991b1b;
    }
    
    .action-btn-restore {
      background: #d1fae5;
      color: #065f46;
    }
    
    .view-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 2px solid #e5e7eb;
    }
    
    .view-tab {
      padding: 10px 20px;
      background: none;
      border: none;
      border-bottom: 3px solid transparent;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      color: #6b7280;
      transition: all 0.3s ease;
      position: relative;
    }
    
    .view-tab:hover {
      color: #1a5632;
    }
    
    .view-tab.active {
      color: #1a5632;
      border-bottom-color: #1a5632;
    }
    
    .view-tab .badge {
      display: inline-block;
      background: #1a5632;
      color: white;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 11px;
      margin-left: 6px;
    }
    
    .select-all-container {
      padding: 10px 15px;
      background: #f9fafb;
      border-radius: 6px;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .select-all-container label {
      font-size: 14px;
      color: #374151;
      cursor: pointer;
      user-select: none;
    }
    
    /* Mobile responsive styles */
    @media (max-width: 768px) {
      .batch-actions-bar {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
      }
      
      .batch-actions {
        flex-wrap: wrap;
      }
      
      .batch-btn {
        flex: 1;
        justify-content: center;
      }
      
      .view-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      
      .view-tab {
        white-space: nowrap;
      }
    }
    
    /* Empty state styles */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #6b7280;
    }
    
    .empty-state i {
      font-size: 64px;
      margin-bottom: 20px;
      opacity: 0.5;
    }
    
    .empty-state h3 {
      font-size: 20px;
      margin-bottom: 10px;
      color: #374151;
    }
    
    .empty-state p {
      font-size: 14px;
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
      <a href="home.php">Dashboard</a>
      <a href="officeManagement.php">Office Management</a>
      <a href="floorPlan.php">Floor Plans</a>
      <a href="visitorFeedback.php" class="active">Visitor Feedback</a>
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
        <li><a href="floorPlan.php">Floor Plans</a></li>
        <li><a href="visitorFeedback.php" class="active">Visitor Feedback</a></li>
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

  <main class="main-content">
    <header class="header">
      <div>
        <h2>Feedback Details</h2>
        <p>Review and analyze visitor feedback data.</p>
      </div>

      <form id="filterForm" method="GET" action="visitorFeedback.php" class="filter-controls">
        <!-- Hidden input to preserve view mode -->
        <input type="hidden" name="view" id="viewModeInput" value="<?= htmlspecialchars($viewMode) ?>">
        
        <div class="filter-item">
          <label class="filter-label" for="sort">Sort By</label>
          <select class="filter-select" name="sort" id="sort">
            <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= $sortOrder === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="filter-label" for="rating">Min Rating</label>
          <select class="filter-select" name="rating" id="rating">
            <option value="0" <?= $ratingFilter == 0 ? 'selected' : '' ?>>All Ratings</option>
            <option value="5" <?= $ratingFilter == 5 ? 'selected' : '' ?>>5 Stars</option>
            <option value="4" <?= $ratingFilter == 4 ? 'selected' : '' ?>>4 Stars</option>
            <option value="3" <?= $ratingFilter == 3 ? 'selected' : '' ?>>3 Stars</option>
            <option value="2" <?= $ratingFilter == 2 ? 'selected' : '' ?>>2 Stars</option>
            <option value="1" <?= $ratingFilter == 1 ? 'selected' : '' ?>>1 Star</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="filter-label" for="month">Month</label>
          <select class="filter-select" name="month" id="month">
            <option value="all" <?= $selectedMonth == 'all' ? 'selected' : '' ?>>All Months</option>
            <?php
              for ($m = 1; $m <= 12; $m++) {
                $monthVal = str_pad($m, 2, '0', STR_PAD_LEFT);
                $monthName = date('F', mktime(0, 0, 0, $m, 10));
                $selected = $selectedMonth == $monthVal ? 'selected' : '';
                echo "<option value=\"$monthVal\" $selected>$monthName</option>";
              }
            ?>
          </select>
        </div>
        <div class="filter-item">
          <label class="filter-label" for="year">Year</label>
          <select class="filter-select" name="year" id="year">
            <option value="all" <?= $selectedYear == 'all' ? 'selected' : '' ?>>All Years</option>
            <?php
              $currentYear = date('Y');
              for ($y = $currentYear; $y >= 2020; $y--) {
                $selected = $selectedYear == $y ? 'selected' : '';
                echo "<option value=\"$y\" $selected>$y</option>";
              }
            ?>
          </select>
        </div>
        <div class="filter-actions">
          <button type="submit" class="filter-button">Apply</button>
          <a href="visitorFeedback.php?view=<?= htmlspecialchars($viewMode) ?>" class="reset-button">Reset</a>
        </div>
      </form>

    </header>

    <!-- Stats Cards -->
    <div class="stats-cards">
      <div class="stat-card">
        <h3><?= $totalFeedback ?></h3>
        <p>Total Feedbacks</p>
      </div>
      <div class="stat-card">
        <h3><?= number_format($averageRating, 1) ?></h3>
        <p>Average Rating</p>
      </div>
      <div class="stat-card">
        <h3><?= number_format($highestRating, 1) ?> ★</h3>
        <p>Highest Rating</p>
      </div>
    </div>

    <!-- View Tabs -->
    <div class="view-tabs">
      <button class="view-tab <?= $viewMode === 'active' ? 'active' : '' ?>" onclick="switchView('active')">
        <i class="fa fa-list"></i> Active
      </button>
      <button class="view-tab <?= $viewMode === 'archived' ? 'active' : '' ?>" onclick="switchView('archived')">
        <i class="fa fa-archive"></i> Archived
      </button>
      <button class="view-tab <?= $viewMode === 'trash' ? 'active' : '' ?>" onclick="switchView('trash')">
        <i class="fa fa-trash"></i> Trash
      </button>
    </div>

    <!-- Batch Actions Bar (shown when items are selected) -->
    <div class="batch-actions-bar" id="batchActionsBar">
      <div class="batch-info">
        <span id="selectedCount">0</span> selected
      </div>
      <div class="batch-actions">
        <?php if ($viewMode === 'active'): ?>
          <button class="batch-btn batch-btn-archive" onclick="batchArchive()">
            <i class="fa fa-archive"></i> Archive Selected
          </button>
          <button class="batch-btn batch-btn-delete" onclick="batchDelete()">
            <i class="fa fa-trash"></i> Delete Selected
          </button>
        <?php elseif ($viewMode === 'archived'): ?>
          <button class="batch-btn batch-btn-restore" onclick="batchUnarchive()">
            <i class="fa fa-undo"></i> Restore Selected
          </button>
          <button class="batch-btn batch-btn-delete" onclick="batchDelete()">
            <i class="fa fa-trash"></i> Delete Selected
          </button>
        <?php elseif ($viewMode === 'trash'): ?>
          <button class="batch-btn batch-btn-restore" onclick="batchRestore()">
            <i class="fa fa-undo"></i> Restore Selected
          </button>
          <button class="batch-btn batch-btn-delete" onclick="batchDeletePermanent()">
            <i class="fa fa-times-circle"></i> Delete Permanently
          </button>
        <?php endif; ?>
        <button class="batch-btn batch-btn-cancel" onclick="cancelSelection()">
          <i class="fa fa-times"></i> Cancel
        </button>
      </div>
    </div>

    <!-- Feedback Container -->
    <div class="feedback-container">
      <div class="feedback-scroll">
        <?php if ($totalFeedback > 0): ?>
          <!-- Select All Option -->
          <div class="select-all-container">
            <input type="checkbox" id="selectAll" class="feedback-checkbox" onchange="toggleSelectAll(this)">
            <label for="selectAll">Select All</label>
          </div>
          
          <?php foreach ($feedbackList as $feedback): ?>
            <div class="feedback-item" data-feedback-id="<?= $feedback['feed_id'] ?>">
              <div class="feedback-user">
                <input type="checkbox" class="feedback-checkbox feedback-item-checkbox" 
                       data-id="<?= $feedback['feed_id'] ?>" onchange="updateBatchActions()">
                <div class="user-info">
                  <h4><?= htmlspecialchars($feedback['visitor_name']) ?></h4>
                </div>
              </div>
              <div class="feedback-message">
                <p><?= htmlspecialchars($feedback['comments']) ?></p>
              </div>
              <div class="feedback-meta">
                <div class="rating">
                  <?php
                    $fullStars = floor($feedback['rating']);
                    $emptyStars = floor(5 - $feedback['rating']);
                    echo str_repeat('<span class="star filled">★</span>', $fullStars);
                    echo str_repeat('<span class="star">☆</span>', $emptyStars);
                  ?>
                  <span class="rating-number"><?= number_format($feedback['rating'], 1) ?></span>
                </div>
                <div class="submitted">
                  Submitted: <span class="date"><?= date("F j, Y", strtotime($feedback['submitted_at'])) ?></span>
                </div>
              </div>
              
              <!-- Individual Action Buttons -->
              <div class="feedback-actions">
                <?php if ($viewMode === 'active'): ?>
                  <button class="action-btn action-btn-archive" onclick="archiveSingle(<?= $feedback['feed_id'] ?>)">
                    <i class="fa fa-archive"></i> Archive
                  </button>
                  <button class="action-btn action-btn-delete" onclick="deleteSingle(<?= $feedback['feed_id'] ?>)">
                    <i class="fa fa-trash"></i> Delete
                  </button>
                <?php elseif ($viewMode === 'archived'): ?>
                  <button class="action-btn action-btn-restore" onclick="unarchiveSingle(<?= $feedback['feed_id'] ?>)">
                    <i class="fa fa-undo"></i> Restore
                  </button>
                  <button class="action-btn action-btn-delete" onclick="deleteSingle(<?= $feedback['feed_id'] ?>)">
                    <i class="fa fa-trash"></i> Delete
                  </button>
                <?php elseif ($viewMode === 'trash'): ?>
                  <button class="action-btn action-btn-restore" onclick="restoreSingle(<?= $feedback['feed_id'] ?>)">
                    <i class="fa fa-undo"></i> Restore
                  </button>
                  <button class="action-btn action-btn-delete" onclick="deletePermanentSingle(<?= $feedback['feed_id'] ?>)">
                    <i class="fa fa-times-circle"></i> Delete Forever
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <?php if ($viewMode === 'archived'): ?>
              <i class="fa fa-archive"></i>
              <h3>No Archived Feedback</h3>
              <p>Archived feedback entries will appear here.</p>
            <?php elseif ($viewMode === 'trash'): ?>
              <i class="fa fa-trash"></i>
              <h3>Trash is Empty</h3>
              <p>Deleted feedback entries will appear here for 30 days before permanent deletion.</p>
            <?php else: ?>
              <i class="fa fa-inbox"></i>
              <h3>No Feedback Found</h3>
              <p>No feedback entries match your current filters.</p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script>
  // ==================== FILTER AUTO-SUBMIT ====================
  // Auto-submit form when any filter changes
  ['sort', 'rating', 'month', 'year'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener('change', () => {
        document.getElementById('filterForm').submit();
      });
    }
  });

  // ==================== VIEW MODE SWITCHING ====================
  /**
   * Switch between Active, Archived, and Trash views
   */
  function switchView(mode) {
    const url = new URL(window.location.href);
    url.searchParams.set('view', mode);
    window.location.href = url.toString();
  }

  // ==================== BATCH SELECTION ====================
  /**
   * Toggle select all checkboxes
   */
  function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.feedback-item-checkbox');
    checkboxes.forEach(cb => {
      cb.checked = checkbox.checked;
      updateItemSelection(cb);
    });
    updateBatchActions();
  }

  /**
   * Update visual state when checkbox changes
   */
  function updateItemSelection(checkbox) {
    const feedbackItem = checkbox.closest('.feedback-item');
    if (checkbox.checked) {
      feedbackItem.classList.add('selected');
    } else {
      feedbackItem.classList.remove('selected');
    }
  }

  /**
   * Update batch actions bar visibility and selected count
   */
  function updateBatchActions() {
    const checkboxes = document.querySelectorAll('.feedback-item-checkbox:checked');
    const count = checkboxes.length;
    const batchBar = document.getElementById('batchActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAll');

    selectedCount.textContent = count;

    if (count > 0) {
      batchBar.classList.add('active');
    } else {
      batchBar.classList.remove('active');
    }

    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.feedback-item-checkbox');
    if (selectAll) {
      selectAll.checked = count === allCheckboxes.length && count > 0;
      selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
    }

    // Update item selection visual state
    checkboxes.forEach(cb => updateItemSelection(cb));
    
    // Update unselected items
    document.querySelectorAll('.feedback-item-checkbox:not(:checked)').forEach(cb => {
      cb.closest('.feedback-item').classList.remove('selected');
    });
  }

  /**
   * Cancel selection and hide batch actions bar
   */
  function cancelSelection() {
    document.querySelectorAll('.feedback-item-checkbox').forEach(cb => {
      cb.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    updateBatchActions();
  }

  /**
   * Get array of selected feedback IDs
   */
  function getSelectedIds() {
    const checkboxes = document.querySelectorAll('.feedback-item-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.dataset.id);
  }

  // ==================== API CALLS ====================
  /**
   * Make API call to feedback management endpoint
   */
  async function callFeedbackAPI(action, ids, confirm = false) {
    try {
      const formData = new FormData();
      formData.append('action', action);
      formData.append('ids', JSON.stringify(ids));
      if (confirm) {
        formData.append('confirm', 'true');
      }

      const response = await fetch('feedback_management_api.php', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const result = await response.json();
      return result;

    } catch (error) {
      console.error('API Error:', error);
      return {
        success: false,
        message: 'Connection error. Please check your internet connection and try again.'
      };
    }
  }

  /**
   * Show notification message
   */
  function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      background: ${type === 'success' ? '#10b981' : '#ef4444'};
      color: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 10000;
      animation: slideIn 0.3s ease;
      max-width: 400px;
      font-size: 14px;
    `;
    notification.textContent = message;

    // Add animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
      }
    `;
    document.head.appendChild(style);

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => {
        notification.remove();
        style.remove();
      }, 300);
    }, 3000);
  }

  /**
   * Reload page to reflect changes
   */
  function reloadPage() {
    // Small delay before reload for better UX
    setTimeout(() => {
      window.location.reload();
    }, 1000);
  }

  // ==================== SINGLE ITEM ACTIONS ====================
  /**
   * Archive a single feedback entry
   */
  async function archiveSingle(id) {
    if (!confirm('Archive this feedback entry?')) return;

    const result = await callFeedbackAPI('archive', [id]);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Unarchive a single feedback entry
   */
  async function unarchiveSingle(id) {
    const result = await callFeedbackAPI('unarchive', [id]);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Delete a single feedback entry (soft delete)
   */
  async function deleteSingle(id) {
    if (!confirm('Move this feedback entry to trash?')) return;

    const result = await callFeedbackAPI('delete', [id]);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Restore a single feedback entry from trash
   */
  async function restoreSingle(id) {
    const result = await callFeedbackAPI('restore', [id]);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Permanently delete a single feedback entry
   */
  async function deletePermanentSingle(id) {
    if (!confirm('⚠️ PERMANENT DELETION\n\nThis action cannot be undone. The feedback entry will be permanently deleted from the database.\n\nAre you absolutely sure?')) {
      return;
    }

    const result = await callFeedbackAPI('delete_permanent', [id], true);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  // ==================== BATCH ACTIONS ====================
  /**
   * Archive selected feedback entries
   */
  async function batchArchive() {
    const ids = getSelectedIds();
    
    if (ids.length === 0) {
      showNotification('Please select at least one feedback entry', 'error');
      return;
    }

    if (!confirm(`Archive ${ids.length} feedback ${ids.length === 1 ? 'entry' : 'entries'}?`)) {
      return;
    }

    const result = await callFeedbackAPI('archive', ids);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Unarchive selected feedback entries
   */
  async function batchUnarchive() {
    const ids = getSelectedIds();
    
    if (ids.length === 0) {
      showNotification('Please select at least one feedback entry', 'error');
      return;
    }

    const result = await callFeedbackAPI('unarchive', ids);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Delete selected feedback entries (soft delete)
   */
  async function batchDelete() {
    const ids = getSelectedIds();
    
    if (ids.length === 0) {
      showNotification('Please select at least one feedback entry', 'error');
      return;
    }

    if (!confirm(`Move ${ids.length} feedback ${ids.length === 1 ? 'entry' : 'entries'} to trash?`)) {
      return;
    }

    const result = await callFeedbackAPI('delete', ids);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Restore selected feedback entries from trash
   */
  async function batchRestore() {
    const ids = getSelectedIds();
    
    if (ids.length === 0) {
      showNotification('Please select at least one feedback entry', 'error');
      return;
    }

    const result = await callFeedbackAPI('restore', ids);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  /**
   * Permanently delete selected feedback entries
   */
  async function batchDeletePermanent() {
    const ids = getSelectedIds();
    
    if (ids.length === 0) {
      showNotification('Please select at least one feedback entry', 'error');
      return;
    }

    if (!confirm(`⚠️ PERMANENT DELETION\n\nYou are about to permanently delete ${ids.length} feedback ${ids.length === 1 ? 'entry' : 'entries'}.\n\nThis action CANNOT be undone. The data will be lost forever.\n\nAre you absolutely sure?`)) {
      return;
    }

    // Second confirmation for safety
    if (!confirm('This is your final warning. Click OK to permanently delete the selected feedback entries.')) {
      return;
    }

    const result = await callFeedbackAPI('delete_permanent', ids, true);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      reloadPage();
    }
  }

  // ==================== INITIALIZATION ====================
  /**
   * Initialize on page load
   */
  document.addEventListener('DOMContentLoaded', function() {
    // Add change listeners to all checkboxes
    document.querySelectorAll('.feedback-item-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', updateBatchActions);
    });

    // Initialize batch actions bar state
    updateBatchActions();
  });

  // ==================== KEYBOARD SHORTCUTS ====================
  /**
   * Handle keyboard shortcuts for power users
   */
  document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + A: Select all
    if ((e.ctrlKey || e.metaKey) && e.key === 'a' && e.target.tagName !== 'INPUT') {
      e.preventDefault();
      const selectAll = document.getElementById('selectAll');
      if (selectAll) {
        selectAll.checked = true;
        toggleSelectAll(selectAll);
      }
    }

    // Escape: Cancel selection
    if (e.key === 'Escape') {
      cancelSelection();
    }
  });
</script>

</body>
</html>
