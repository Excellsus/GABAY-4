<?php
/**
 * =====================================================
 * GABAY FORGOT PASSWORD PAGE
 * =====================================================
 * 
 * Allows admin users to request a password reset link
 * via their registered email address.
 */

// Enable error logging but hide from display (production safety)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to home
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit;
}

// Wrap in try-catch to prevent 500 errors
try {
    require_once 'connect_db.php';
} catch (Exception $e) {
    error_log("Database connection failed in forgot_password.php: " . $e->getMessage());
    die("Database connection error. Please contact administrator.");
}

// Check if email functionality is available
$emailEnabled = false;
if (file_exists(__DIR__ . '/send_email.php') && file_exists(__DIR__ . '/email_config.php')) {
    try {
        require_once __DIR__ . '/send_email.php';
        $emailEnabled = true;
    } catch (Exception $e) {
        error_log("Email system unavailable: " . $e->getMessage());
    } catch (Error $e) {
        error_log("Email system error: " . $e->getMessage());
    }
}

// Set MySQL timezone to match PHP timezone
try {
    $connect->exec("SET time_zone = '+08:00'"); // Philippines timezone
} catch (Exception $e) {
    // Ignore timezone setting errors
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        $message = "Please enter your username.";
        $message_type = "error";
    } else {
        try {
            // Check if username exists in admin table and get their email
            $stmt = $connect->prepare("SELECT id, username, email FROM admin WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Check if admin has an email registered
                if (empty($admin['email'])) {
                    $message = "No email address registered for this account. Please contact system administrator.";
                    $message_type = "error";
                } else {
                    // Generate unique reset token
                    $token = bin2hex(random_bytes(32));
                    
                    // Store token in database (let MySQL handle the expiry time)
                    // Explicitly set used = 0 to prevent default value issues
                    $stmt = $connect->prepare("
                        INSERT INTO password_resets (admin_id, token, expiry, used, created_at) 
                        VALUES (:admin_id, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), 0, NOW())
                        ON DUPLICATE KEY UPDATE token = :token, expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR), used = 0, created_at = NOW()
                    ");
                    $stmt->execute([
                        ':admin_id' => $admin['id'],
                        ':token' => $token
                    ]);
                    
                    // Build reset URL
                    // Dynamic URL for password reset based on current host
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                    $resetUrl = $protocol . '://' . $host . $scriptDir . '/reset_password.php?token=' . $token;
                    $resetUrl = preg_replace('#([^:])/+#', '$1/', $resetUrl);
                    
                    // Try to send email if email system is available
                    if ($emailEnabled && function_exists('sendPasswordResetEmail')) {
                        $emailResult = sendPasswordResetEmail($admin['email'], $admin['username'], $resetUrl);
                        
                        if ($emailResult['success']) {
                            // Mask email for security (show only first letter and domain)
                            $maskedEmail = substr($admin['email'], 0, 2) . '***@' . substr(strrchr($admin['email'], "@"), 1);
                            // Use session to store success message and redirect to prevent form resubmission
                            $_SESSION['reset_message'] = "Password reset link has been sent to your registered email ({$maskedEmail}). Please check your inbox and spam folder.";
                            $_SESSION['reset_message_type'] = "success";
                            header("Location: forgot_password.php");
                            exit;
                        } else {
                            $message = "Failed to send email. Please contact system administrator.";
                            $message_type = "error";
                            error_log("Password reset email failed for user {$admin['username']}: " . ($emailResult['error'] ?? 'Unknown'));
                        }
                    } else {
                        // Email system not configured - show reset link directly (development/testing mode)
                        $_SESSION['reset_message'] = "Email system not configured. For testing: <a href='{$resetUrl}' target='_blank' style='color: #0066cc;'>Click here to reset password</a>";
                        $_SESSION['reset_message_type'] = "warning";
                        error_log("Password reset requested for {$admin['username']} but email system unavailable. Token: {$token}");
                        header("Location: forgot_password.php");
                        exit;
                    }
                }
            } else {
                // Username does not exist
                $message = "Invalid username. Please check your username and try again.";
                $message_type = "error";
            }
        } catch(PDOException $e) {
            $message = "System error. Please try again later.";
            $message_type = "error";
            error_log("Forgot password error: " . $e->getMessage());
        }
    }
}

// Check for session messages (from redirect after successful submission)
if (isset($_SESSION['reset_message'])) {
    $message = $_SESSION['reset_message'];
    $message_type = $_SESSION['reset_message_type'];
    unset($_SESSION['reset_message']);
    unset($_SESSION['reset_message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password | GABAY Admin</title>
    <link rel="stylesheet" href="login.css" />
</head>
<body>
    <div class="background-blur">
        <div class="green-bubble"></div>
        <div class="gold-bubble"></div>
    </div>

    <div class="container">
        <div class="card">
            <!-- Left: Image -->
            <div class="image-section">
                <img src="./srcImage/news-capitol2.jpg" alt="Negros Occidental Provincial Capitol" />
                <div class="image-overlay">
                    <div class="image-overlay-content">
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <h1>GABAY</h1>
                        </div>
                        <p style="opacity: 0.9; font-size: 0.875rem;">Navigation System for Negros Occidental Provincial Capitol</p>
                    </div>
                </div>
            </div>

            <!-- Right: Form -->
            <div class="form-section">
                <h2>Forgot Password</h2>
                <p>Enter your admin username. We'll send a password reset link to your registered email address.</p>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <div class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" placeholder="Enter your admin username" required />
                    </div>

                    <?php if (!empty($message)): ?>
                    <div class="<?php echo $message_type === 'error' ? 'error-message' : 'info-message'; ?>" style="display: block; <?php echo $message_type === 'success' ? 'background-color: #d1f2eb; color: #0f5132; border-left: 4px solid #198754;' : ''; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Send Reset Link
                    </button>

                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="login.php" style="color: #1a5f3c; text-decoration: none; font-size: 0.9rem;">
                            ← Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="footer" style="margin-top: 1rem;">
            © 2025 GABAY Navigation System. Provincial Government of Negros Occidental.
        </div>
    </div>
</body>
</html>
