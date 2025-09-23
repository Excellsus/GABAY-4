<?php
// Start session if needed (optional, depends if you store user info)
// session_start();

// Go up one directory to include connect_db.php from the parent folder
include __DIR__ . '/../connect_db.php'; // Include database connection

// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = ''; // To store success or error messages
$office_id = null;
$office_name = null;

// --- Get Office ID and Name (if provided in URL) ---
if (isset($_GET['office_id']) && is_numeric($_GET['office_id'])) {
    $office_id = (int)$_GET['office_id'];
    try {
        // Fetch office name for display (assuming 'offices' table and 'name' column)
        $stmt_office = $connect->prepare("SELECT name FROM offices WHERE id = :office_id");
        $stmt_office->bindParam(':office_id', $office_id, PDO::PARAM_INT);
        $stmt_office->execute();
        $office_result = $stmt_office->fetch(PDO::FETCH_ASSOC);
        if ($office_result) {
            $office_name = $office_result['name'];
        } else {
            $office_id = null; // Reset if office not found
        }
    } catch (PDOException $e) {
        error_log("Error fetching office name for feedback: " . $e->getMessage());
        $office_id = null; // Reset on error
    }
}
// --- End Get Office ID ---

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $visitor_name = !empty($_POST['visitor_name']) ? trim($_POST['visitor_name']) : 'Anonymous'; // Default to Anonymous
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $comment = !empty($_POST['comment']) ? trim($_POST['comment']) : null;
    // Get office_id from the hidden field
    $submitted_office_id = isset($_POST['office_id']) && is_numeric($_POST['office_id']) ? (int)$_POST['office_id'] : null;

    try {
        // Use $connect, correct columns (comments, office_id), remove submitted_at (handled by DB default)
        $stmt = $connect->prepare("INSERT INTO feedback (visitor_name, rating, comments, office_id) VALUES (:visitor_name, :rating, :comments, :office_id)");
        $stmt->bindParam(':visitor_name', $visitor_name, PDO::PARAM_STR);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':comments', $comment, PDO::PARAM_STR); // Use :comments placeholder
        $stmt->bindParam(':office_id', $submitted_office_id, $submitted_office_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT); // Bind office_id
        $stmt->execute();
        
        // Log the feedback activity
        $activityStmt = $connect->prepare("INSERT INTO activities (activity_type, activity_text, created_at, office_id) VALUES (?, ?, NOW(), ?)");
        $activityText = $submitted_office_id ? "New feedback received for " . $office_name : "New feedback received";
        $activityStmt->execute(['feedback', $activityText, $submitted_office_id]);
        
        $message = "Thank you for your feedback!";
    } catch (PDOException $e) {
        $message = "Error submitting feedback. Please check input and try again."; // User-friendly error
        error_log("Feedback submission error: " . $e->getMessage()); // Log detailed error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Corrected closing tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Interactive Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="feedback.css">
</head>
<body>

    <!-- Header -->
    <header class="app-header">
        <div class="header-left">
            <a href="javascript:history.back()" class="back-button" aria-label="Go Back">
                <i class="fas fa-chevron-left"></i>
            </a>
        </div>
        <div class="header-title">
            <h2 class="section-title">
                Provide Feedback
                <?php // Display office name if available ?>
                <?php if ($office_name): echo ' for ' . htmlspecialchars($office_name); endif; ?>
            </h2>
        </div>
        <div class="header-actions"></div>
    </header>

    <!-- Main Content Area -->
    <main class="content">
        <?php // Display success or error message ?>
        <?php if (!empty($message)): ?>
            <div class="feedback-message <?php echo (strpos($message, 'Error') === 0) ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php // Determine the form action URL, keeping the office_id if present ?>
        <?php $form_action = 'feedback.php' . ($office_id ? '?office_id=' . $office_id : ''); ?>
        <form action="<?php echo htmlspecialchars($form_action); ?>" method="post" id="feedback-form">

            <?php // Hidden field to store office_id if it was passed in the URL ?>
            <?php if ($office_id): ?>
                <input type="hidden" name="office_id" value="<?php echo htmlspecialchars($office_id); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="visitor_name">Your Name (Optional):</label>
                <input type="text" id="visitor_name" name="visitor_name" placeholder="Enter your name">
            </div>

            <div class="form-group rating-group">
                <label>Rate your experience:</label>
                <div class="stars">
                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars">★</label> <!-- Added required -->
                    <input type="radio" id="star4" name="rating" value="4" required><label for="star4" title="4 stars">★</label> <!-- Added required -->
                    <input type="radio" id="star3" name="rating" value="3" required><label for="star3" title="3 stars">★</label> <!-- Added required -->
                    <input type="radio" id="star2" name="rating" value="2" required><label for="star2" title="2 stars">★</label> <!-- Added required -->
                    <input type="radio" id="star1" name="rating" value="1" required><label for="star1" title="1 star">★</label> <!-- Added required -->
                </div>
            </div>

            <div class="form-group">
                <label for="comment">Comments:</label>
                <textarea id="comment" name="comment" rows="5" placeholder="Tell us what you think..."></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="submit-button">Submit Feedback</button>
            </div>
        </form>
    </main>

    <!-- Basic CSS for the messages (add to your feedback.css or keep here) -->
    <style>
        .feedback-message {
            padding: 15px; margin-bottom: 20px; border-radius: 5px;
            text-align: center; font-weight: bold;
        }
        .feedback-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .feedback-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>

</body>
</html>
