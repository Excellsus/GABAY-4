<?php
/**
 * =====================================================
 * GABAY PASSWORD RESET PAGE
 * =====================================================
 * 
 * Validates reset token and allows admin to set new password.
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to home
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit;
}

require_once 'connect_db.php';

// Set MySQL timezone to match PHP timezone
try {
    $connect->exec("SET time_zone = '+08:00'"); // Philippines timezone
} catch (Exception $e) {
    // Ignore timezone setting errors
}

$token = $_GET['token'] ?? '';
$message = "";
$message_type = "";
$token_valid = false;
$admin_data = null;

// Validate token
if (!empty($token)) {
    try {
        // Check if token exists and hasn't expired
        $stmt = $connect->prepare("
            SELECT pr.*, a.username, a.email 
            FROM password_resets pr
            JOIN admin a ON pr.admin_id = a.id
            WHERE pr.token = :token 
            AND pr.expiry > NOW()
            AND pr.used = 0
        ");
        $stmt->execute([':token' => $token]);
        $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin_data) {
            $token_valid = true;
        } else {
            $message = "Invalid or expired reset link. Please request a new password reset.";
            $message_type = "error";
        }
    } catch(PDOException $e) {
        $message = "System error. Please try again later.";
        $message_type = "error";
        error_log("Reset password token validation error: " . $e->getMessage());
    }
} else {
    $message = "No reset token provided.";
    $message_type = "error";
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $message = "Please enter and confirm your new password.";
        $message_type = "error";
    } else if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "error";
    } else if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        try {
            // Hash the new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update admin password
            $stmt = $connect->prepare("UPDATE admin SET password = :password WHERE id = :id");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $admin_data['admin_id']
            ]);
            
            // Mark token as used
            $stmt = $connect->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
            $stmt->execute([':token' => $token]);
            
            // Set success message in session
            $_SESSION['login_message'] = "Password reset successful! Please login with your new password.";
            
            // Redirect to login page
            header("Location: login.php");
            exit;
        } catch(PDOException $e) {
            $message = "Failed to update password. Please try again.";
            $message_type = "error";
            error_log("Reset password update error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password | GABAY Admin</title>
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
                <?php if ($token_valid): ?>
                    <h2>Reset Password</h2>
                    <p>Enter your new password for <strong><?php echo htmlspecialchars($admin_data['username']); ?></strong></p>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . htmlspecialchars($token); ?>">
                        <div class="input-group">
                            <label for="password">New Password</label>
                            <div class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" placeholder="Enter new password (min. 8 characters)" required minlength="8" />
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)" style="position: absolute; right: 12px; top: 60%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #64748b;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="eye-open">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="eye-closed" style="display: none;">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="8" />
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 12px; top: 60%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px; color: #64748b;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="eye-open">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="eye-closed" style="display: none;">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>

                        <?php if (!empty($message)): ?>
                        <div class="error-message" style="display: block;">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Reset Password
                        </button>
                    </form>
                <?php else: ?>
                    <h2>Invalid Reset Link</h2>
                    <p style="color: #666; margin-bottom: 1.5rem;">The password reset link is invalid or has expired.</p>

                    <?php if (!empty($message)): ?>
                    <div class="error-message" style="display: block; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>

                    <a href="forgot_password.php" class="btn" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Request New Reset Link
                    </a>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="login.php" style="color: #1a5f3c; text-decoration: none; font-size: 0.9rem;">
                        ← Back to Login
                    </a>
                </div>
            </div>
        </div>
        <div class="footer" style="margin-top: 1rem;">
            © 2025 GABAY Navigation System. Provincial Government of Negros Occidental.
        </div>
    </div>
    <script>
      function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const eyeOpen = button.querySelector('.eye-open');
        const eyeClosed = button.querySelector('.eye-closed');
        
        if (input.type === 'password') {
          input.type = 'text';
          eyeOpen.style.display = 'none';
          eyeClosed.style.display = 'block';
        } else {
          input.type = 'password';
          eyeOpen.style.display = 'block';
          eyeClosed.style.display = 'none';
        }
      }
    </script>
</body>
</html>
