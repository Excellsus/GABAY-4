<?php
require_once "connect_db.php";

// Get filter settings
$sortOrder = $_GET['sort'] ?? 'newest';
$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$selectedMonth = $_GET['month'] ?? 'all';
$selectedYear = $_GET['year'] ?? 'all';

// Build the query
$query = "SELECT * FROM feedback WHERE 1=1";
$params = [];

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
  <title>Visitor Feedback</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="visitorFeedback.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="./mobileNav.js"></script>
  <link rel="stylesheet" href="filter-styles.css"> <!-- Add this line -->
  <link rel="stylesheet" href="mobileNav.css" />
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
          <a href="visitorFeedback.php" class="reset-button">Reset</a>
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

    <!-- Feedback Container -->
    <div class="feedback-container">
      <div class="feedback-scroll">
        <?php if ($totalFeedback > 0): ?>
          <?php foreach ($feedbackList as $feedback): ?>
            <div class="feedback-item">
              <div class="feedback-user">
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
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-feedback">
            <h3>No feedback found</h3>
            <p>No feedback entries match your current filters.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script>
  // Auto-submit form when any filter changes
  ['sort', 'rating', 'month', 'year'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
      document.getElementById('filterForm').submit();
    });
  });
</script>

</body>
</html>
