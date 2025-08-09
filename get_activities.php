<?php
include 'connect_db.php';

// Fetch recent activities with office names
$activityStmt = $connect->prepare("
    SELECT a.*, o.name as office_name, 
    TIMESTAMPDIFF(SECOND, a.created_at, NOW()) as seconds_ago,
    TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) as minutes_ago,
    TIMESTAMPDIFF(HOUR, a.created_at, NOW()) as hours_ago,
    TIMESTAMPDIFF(DAY, a.created_at, NOW()) as days_ago
    FROM activities a 
    LEFT JOIN offices o ON a.office_id = o.id 
    ORDER BY a.created_at DESC 
    LIMIT 4
");
$activityStmt->execute();
$activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to format time ago
function getTimeAgo($activity) {
    if ($activity['seconds_ago'] < 60) {
        return "just now";
    } elseif ($activity['minutes_ago'] < 60) {
        $mins = $activity['minutes_ago'];
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($activity['hours_ago'] < 24) {
        $hours = $activity['hours_ago'];
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } else {
        $days = $activity['days_ago'];
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    }
}

// Function to get appropriate icon
function getActivityIcon($type) {
    switch ($type) {
        case 'office':
            return 'fa-building';
        case 'file':
            return 'fa-map-location-dot';
        case 'feedback':
            return 'fa-comment';
        default:
            return 'fa-user';
    }
}

// Return only the activity list HTML
foreach ($activities as $activity): ?>
    <div class="activity-item">
        <div class="activity-icon">
            <i class="fa <?php echo getActivityIcon($activity['activity_type']); ?>"></i>
        </div>
        <div class="activity-details">
            <p class="activity-text"><?php echo htmlspecialchars($activity['activity_text']); ?></p>
            <span class="activity-time"><?php echo getTimeAgo($activity); ?></span>
        </div>
    </div>
<?php endforeach;

if (empty($activities)): ?>
    <div class="empty-state">
        <p>No recent activities</p>
    </div>
<?php endif; ?> 