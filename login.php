<?php
/**
 * =====================================================
 * GABAY ADMIN LOGIN PAGE
 * =====================================================
 * 
 * Secure login implementation with token-based authentication.
 * Features:
 * - Secure password verification (supports both hashed and legacy plain-text)
 * - Session token generation
 * - Brute force protection (rate limiting)
 * - Security event logging
 * - Return URL support for seamless redirection after login
 */

// Start session for login management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for login message from auth guard (e.g., session expired)
$info_message = "";
if (isset($_SESSION['login_message'])) {
    $info_message = $_SESSION['login_message'];
    unset($_SESSION['login_message']);
}

// If already logged in with valid token, redirect to home page
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['auth_token'])) {
    // Check if there's a return URL
    $returnUrl = $_GET['return'] ?? 'home.php';
    header("Location: " . $returnUrl);
    exit;
}

// Initialize error message variable
$error_message = "";

// Rate limiting: Track login attempts to prevent brute force attacks
function checkRateLimit($username) {
    $maxAttempts = 5; // Maximum attempts allowed
    $timeWindow = 900; // Time window in seconds (15 minutes)
    
    // Initialize attempts tracking in session
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    // Clean up old attempts outside the time window
    $currentTime = time();
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        }
    );
    
    // Check if too many attempts
    if (count($_SESSION['login_attempts']) >= $maxAttempts) {
        $oldestAttempt = min($_SESSION['login_attempts']);
        $waitTime = $timeWindow - ($currentTime - $oldestAttempt);
        return [
            'allowed' => false,
            'wait_time' => ceil($waitTime / 60) // Convert to minutes
        ];
    }
    
    return ['allowed' => true];
}

// Record login attempt
function recordLoginAttempt() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    $_SESSION['login_attempts'][] = time();
}

// Log security event (login page version)
// Note: This is separate from auth_guard.php to avoid function redeclaration
function logLoginSecurityEvent($event, $level = 'info') {
    $logFile = __DIR__ . '/logs/security.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logEntry = "[$timestamp] [$level] [$ip] $event" . PHP_EOL;
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Process login form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    require_once 'connect_db.php';
    
    // Get form data and sanitize
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
        logLoginSecurityEvent("Login attempt with empty credentials", 'warning');
    } else {
        // Check rate limit
        $rateLimit = checkRateLimit($username);
        if (!$rateLimit['allowed']) {
            $error_message = "Too many failed login attempts. Please try again in " . $rateLimit['wait_time'] . " minutes.";
            logLoginSecurityEvent("Rate limit exceeded for username: $username", 'warning');
        } else {
            try {
                // Prepare a statement to select user from the admin table
                $stmt = $connect->prepare("SELECT id, username, password FROM admin WHERE username = :username");
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                // Check if username exists
                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check if the password is stored as a hash
                    if (password_verify($password, $user['password'])) {
                        // Password is hashed and correct
                        $password_correct = true;
                    } else if ($password === $user['password']) {
                        // Password is stored as plain text and matches (legacy support)
                        $password_correct = true;
                        
                        // Log warning about plain-text password
                        logLoginSecurityEvent("Plain-text password detected for user: $username (consider upgrading to hashed)", 'warning');
                    } else {
                        // Password is incorrect
                        $password_correct = false;
                    }
                    
                    if ($password_correct) {
                        // Password is correct - Initialize secure authentication session
                        
                        // Import auth functions
                        require_once 'auth_guard.php';
                        
                        // Initialize auth session with token generation
                        $authToken = initAuthSession($user['id'], $user['username']);
                        
                        // Clear login attempts on successful login
                        unset($_SESSION['login_attempts']);
                        
                        // Log successful login
                        logLoginSecurityEvent("Successful login for user: $username", 'info');
                        
                        // Determine redirect URL
                        $returnUrl = $_GET['return'] ?? 'home.php';
                        
                        // Sanitize return URL to prevent open redirect vulnerability
                        $returnUrl = filter_var($returnUrl, FILTER_SANITIZE_URL);
                        
                        // Ensure return URL is relative (not external)
                        if (parse_url($returnUrl, PHP_URL_HOST) !== null) {
                            $returnUrl = 'home.php';
                        }
                        
                        // Redirect to home page or return URL
                        header("Location: " . $returnUrl);
                        exit;
                    } else {
                        // Password is incorrect
                        recordLoginAttempt();
                        $error_message = "Invalid username or password. Please try again.";
                        logLoginSecurityEvent("Failed login attempt for username: $username (incorrect password)", 'warning');
                    }
                } else {
                    // Username doesn't exist
                    recordLoginAttempt();
                    $error_message = "Invalid username or password. Please try again.";
                    logLoginSecurityEvent("Failed login attempt for non-existent username: $username", 'warning');
                }
            } catch(PDOException $e) {
                $error_message = "Database error. Please try again later.";
                logLoginSecurityEvent("Database error during login: " . $e->getMessage(), 'error');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GABAY | Negros Occidental Provincial Capitol</title>
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
          <h2>Admin Portal</h2>
          <p>Manage the navigation system and content</p>

          <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="input-group">
              <label for="username">Username</label>
              <div class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <input type="text" id="username" name="username" placeholder="Enter admin username" required />
            </div>

            <div class="input-group">
              <label for="password">Password</label>
              <div class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input type="password" id="password" name="password" placeholder="Enter your password" required />
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

            <?php if (!empty($info_message)): ?>
            <div class="info-message" style="display: block; background-color: #e8f4fd; color: #014361; padding: 12px; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #0288d1;">
              <?php echo htmlspecialchars($info_message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div id="errorMessage" class="error-message" style="display: block;">
              <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php else: ?>
            <div id="errorMessage" class="error-message">
              Invalid username or password. Please try again.
            </div>
            <?php endif; ?>

            <button type="submit" class="btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              Sign In
            </button>

            <div style="text-align: center; margin-top: 1rem;">
              <a href="forgot_password.php" style="color: #1a5f3c; text-decoration: none; font-size: 0.9rem;">
                Forgot your password?
              </a>
            </div>
          </form>
        </div>
      </div>
      <div class="footer" style="margin-top: 1rem;">
        © 2023 GABAY Navigation System. Provincial Government of Negros Occidental.
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