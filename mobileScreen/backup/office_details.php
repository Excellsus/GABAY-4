<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include __DIR__ . '/../connect_db.php';

$office = null;
$error_message = null;
$office_id = null;

// 1. Get and Validate Office ID from URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $office_id = (int)$_GET['id'];
} else {
    $error_message = "Invalid or missing Office ID.";
}

// 2. Fetch Office Data if ID is valid
if ($office_id !== null) {
    try {
        if (!isset($connect) || !$connect) {
            throw new Exception("Database connection object (\$connect) is not valid.");
        }

        // Get current day for office hours
        $current_day = date('l'); // Gets current day name (Monday, Tuesday, etc.)

        // Use prepared statement with JOIN to get all needed data
        $stmt = $connect->prepare("
            SELECT o.id, o.name, o.details, o.services, o.status, o.contact,
                   oh.open_time, oh.close_time,
                   (SELECT image_path FROM office_image 
                    WHERE office_id = o.id 
                    ORDER BY uploaded_at DESC, id DESC LIMIT 1) AS image_path
            FROM offices o
            LEFT JOIN office_hours oh ON o.id = oh.office_id AND oh.day_of_week = :current_day
            WHERE o.id = :id
        ");
        
        $stmt->bindParam(':id', $office_id, PDO::PARAM_INT);
        $stmt->bindParam(':current_day', $current_day, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the office record
        $office = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$office) {
            $error_message = "Office not found.";
        }

    } catch (Exception $e) {
        $error_message = "Error fetching office details: " . $e->getMessage();
        error_log("Error in office_details.php: " . $e->getMessage());
    }
}

// Function to check if office is currently open
function isOfficeOpen($openTime, $closeTime) {
    if (!$openTime || !$closeTime) return null;
    
    // Get current time
    $now = new DateTime();
    $currentHours = (int)$now->format('H');
    $currentMinutes = (int)$now->format('i');
    
    // Parse times
    $openParts = explode(':', $openTime);
    $closeParts = explode(':', $closeTime);
    
    if (count($openParts) < 2 || count($closeParts) < 2) {
        return null;
    }
    
    // Convert to minutes for comparison
    $currentTimeInMinutes = ($currentHours * 60) + $currentMinutes;
    $openTimeInMinutes = ((int)$openParts[0] * 60) + (int)$openParts[1];
    $closeTimeInMinutes = ((int)$closeParts[0] * 60) + (int)$closeParts[1];
    
    return $currentTimeInMinutes >= $openTimeInMinutes && $currentTimeInMinutes <= $closeTimeInMinutes;
}

// Function to format time to 12-hour format
function formatTime($timeStr) {
    if (!$timeStr) return 'N/A';
    
    // Split the time string into parts
    $parts = explode(':', $timeStr);
    if (count($parts) < 2) return 'N/A';
    
    $hours = (int)$parts[0];
    $minutes = (int)$parts[1];
    
    // Determine AM/PM
    $ampm = $hours >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
    $hours = $hours % 12;
    $hours = $hours ? $hours : 12; // Convert 0 to 12
    
    // Format the time
    return sprintf("%d:%02d %s", $hours, $minutes, $ampm);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $office ? htmlspecialchars($office['name']) : 'Office Details'; ?></title>
    <link rel="stylesheet" href="office_details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-back">
            <a href="rooms.php" aria-label="Back to Rooms List"><i class="fas fa-arrow-left"></i></a>
        </div>
        <div class="header-content">
            <h2 class="section-title"><?php echo $office ? htmlspecialchars($office['name']) : 'Office Details'; ?></h2>
            <p class="section-subtitle">Services Offered</p>
        </div>
    </header>

    <div class="drawer-container">
        <div class="drawer-content">
            <main class="content">
                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php elseif ($office): ?>
                    <!-- Office Image Section -->
                    <div class="office-image-container">
                        <?php if (!empty($office['image_path'])): ?>
                            <img src="../office_images/<?php echo htmlspecialchars($office['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($office['name']); ?>" 
                                 class="office-image">
                        <?php else: ?>
                            <div class="office-image-placeholder">
                                <i class="fas fa-building"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Status Badge -->
                    <?php
                    $isOpen = isOfficeOpen($office['open_time'], $office['close_time']);
                    $statusClass = $isOpen ? 'status-open' : 'status-closed';
                    $statusText = $isOpen ? 'Open' : 'Closed';
                    ?>
                  

                    <!-- Operation Hours -->
                    <div class="info-section">
                        <h3><i class="fas fa-clock"></i> Operation Hours</h3>
                        <div class="hours-display">
                            <?php if ($office['open_time'] && $office['close_time']): ?>
                                <p>Today: <?php echo formatTime($office['open_time']); ?> - <?php echo formatTime($office['close_time']); ?></p>
                            <?php else: ?>
                                <p>Hours not available for today</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="info-section">
                        <h3><i class="fas fa-info-circle"></i> About This Office</h3>
                        <div class="description-content">
                            <?php if (!empty($office['details'])): ?>
                                <p class="office-description"><?php echo nl2br(htmlspecialchars($office['details'])); ?></p>
                            <?php else: ?>
                                <p class="no-description">No description available for this office.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact Section -->
                    <div class="info-section">
                        <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                        <div class="contact-content">
                            <?php if (!empty($office['contact'])): ?>
                                <?php
                                $contacts = explode("\n", $office['contact']);
                                foreach ($contacts as $contact):
                                    $contact = trim($contact);
                                    if (!empty($contact)):
                                        // Check if it's an email address
                                        if (filter_var($contact, FILTER_VALIDATE_EMAIL)):
                                ?>
                                            <div class="contact-item">
                                                <i class="fas fa-envelope"></i>
                                                <a href="mailto:<?php echo htmlspecialchars($contact); ?>" class="contact-link">
                                                    <?php echo htmlspecialchars($contact); ?>
                                                </a>
                                            </div>
                                <?php
                                        // Check if it's a phone number (basic check for numbers and common separators)
                                        elseif (preg_match('/^[\d\s\-\+\(\)\.]+$/', $contact)):
                                            // Clean the number for tel: link
                                            $cleanNumber = preg_replace('/[^\d\+]/', '', $contact);
                                ?>
                                            <div class="contact-item">
                                                <i class="fas fa-phone"></i>
                                                <a href="tel:<?php echo $cleanNumber; ?>" class="contact-link">
                                                    <?php echo htmlspecialchars($contact); ?>
                                                </a>
                                            </div>
                                <?php
                                        else:
                                ?>
                                            <div class="contact-item">
                                                <i class="fas fa-info-circle"></i>
                                                <span><?php echo htmlspecialchars($contact); ?></span>
                                            </div>
                                <?php
                                        endif;
                                    endif;
                                endforeach;
                                ?>
                            <?php else: ?>
                                <p class="no-contact">No contact information available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Services Section -->
                    <div class="info-section">
                        <h3><i class="fas fa-list-check"></i> Services Offered</h3>
                        <div class="services-list">
                            <?php if (!empty($office['services'])): ?>
                                <?php 
                                $services = explode("\n", $office['services']);
                                foreach ($services as $service): 
                                    if (trim($service)): ?>
                                        <div class="service-item">
                                            <i class="fas fa-check-circle"></i>
                                            <?php echo htmlspecialchars(trim($service)); ?>
                                        </div>
                                    <?php endif; 
                                endforeach; ?>
                            <?php else: ?>
                                <p class="no-services">No services listed for this office.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

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
</body>
</html>