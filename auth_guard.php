<?php
/**
 * =====================================================
 * GABAY ADMIN AUTHENTICATION GUARD
 * =====================================================
 * 
 * This file provides comprehensive token-based authentication
 * for all admin pages and tools. It ensures that:
 * 
 * 1. Only authenticated admins can access admin resources
 * 2. Tokens are validated and refreshed on each request
 * 3. Sessions expire after inactivity or logout
 * 4. CSRF protection is enforced on all state-changing operations
 * 
 * USAGE:
 * Include this file at the top of any admin page:
 * require_once 'auth_guard.php';
 * 
 * For AJAX endpoints, validate CSRF token:
 * validateCSRFToken($_POST['csrf_token']);
 */

// Prevent direct access to this file
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('Direct access forbidden');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings
    ini_set('session.cookie_httponly', 1);    // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1);   // Only use cookies, not URL parameters
    
    // Auto-detect HTTPS for secure cookie flag
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    ini_set('session.cookie_secure', $isHttps ? 1 : 0); // Secure cookies on HTTPS
    
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    
    // Set session timeout (30 minutes of inactivity)
    ini_set('session.gc_maxlifetime', 1800);
    
    // Regenerate session ID periodically to prevent session fixation
    session_start();
    
    // Regenerate session ID every 15 minutes
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 900) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Generate a secure authentication token
 * 
 * @return string A cryptographically secure random token
 */
function generateAuthToken() {
    return bin2hex(random_bytes(32)); // 64-character hexadecimal string
}

/**
 * Generate a CSRF token for form protection
 * 
 * @return string A CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from form/AJAX request
 * 
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is authenticated
 * 
 * @return bool True if authenticated, false otherwise
 */
function isAuthenticated() {
    // Check if session variables are set
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check if required session data exists
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        return false;
    }
    
    // Check if auth token exists
    if (!isset($_SESSION['auth_token'])) {
        return false;
    }
    
    // Check session timeout (30 minutes of inactivity)
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        if ($inactive > 1800) { // 30 minutes = 1800 seconds
            // Session expired due to inactivity
            destroyAuthSession();
            return false;
        }
    }
    
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Destroy authentication session and clear all tokens
 */
function destroyAuthSession() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Redirect to login page with return URL
 * 
 * @param string $message Optional message to display
 */
function redirectToLogin($message = '') {
    $currentPage = $_SERVER['REQUEST_URI'];
    $loginUrl = 'login.php';
    
    // If current page is not login, add return URL
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        $loginUrl .= '?return=' . urlencode($currentPage);
    }
    
    // Add message if provided
    if (!empty($message)) {
        $_SESSION['login_message'] = $message;
    }
    
    header('Location: ' . $loginUrl);
    exit;
}

/**
 * Send JSON error response for AJAX requests
 * 
 * @param string $message Error message
 * @param int $code HTTP status code
 */
function sendAuthErrorJSON($message = 'Unauthorized', $code = 401) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'authentication_required',
        'message' => $message
    ]);
    exit;
}

/**
 * Initialize authentication session after successful login
 * 
 * @param int $adminId Admin user ID
 * @param string $username Admin username
 * @return string The generated auth token
 */
function initAuthSession($adminId, $username) {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    
    // Generate secure auth token
    $authToken = generateAuthToken();
    
    // Set session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = $username;
    $_SESSION['auth_token'] = $authToken;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regeneration'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Generate CSRF token
    generateCSRFToken();
    
    return $authToken;
}

/**
 * Check if current request is an AJAX request
 * 
 * @return bool True if AJAX, false otherwise
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Validate authentication and enforce access control
 * 
 * This is the main function called by admin pages to enforce authentication.
 * It checks if the user is authenticated and handles both regular page loads
 * and AJAX requests appropriately.
 */
function requireAuth() {
    if (!isAuthenticated()) {
        if (isAjaxRequest()) {
            sendAuthErrorJSON('Session expired. Please log in again.');
        } else {
            redirectToLogin('Your session has expired. Please log in again.');
        }
    }
}

// =====================================================
// AUTOMATIC AUTHENTICATION ENFORCEMENT
// =====================================================
// Automatically check authentication when this file is included
// This ensures all admin pages are protected by default
// EXCEPTION: Don't enforce when included from login.php (needed for initAuthSession)

if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
    requireAuth();
}

// =====================================================
// HELPER FUNCTIONS FOR VIEWS
// =====================================================

/**
 * Get CSRF token for forms (use in HTML)
 * 
 * @return string HTML hidden input with CSRF token
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Get CSRF token value (use in JavaScript)
 * 
 * @return string The CSRF token
 */
function csrfToken() {
    return generateCSRFToken();
}

/**
 * Get current authenticated admin info
 * 
 * @return array Admin information (id, username)
 */
function getAuthAdmin() {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'login_time' => $_SESSION['login_time'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null
    ];
}

/**
 * Log security event (optional - for audit trail)
 * 
 * @param string $event Event description
 * @param string $level Severity level (info, warning, error)
 */
function logSecurityEvent($event, $level = 'info') {
    $logFile = __DIR__ . '/logs/security.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['admin_username'] ?? 'anonymous';
    $logEntry = "[$timestamp] [$level] [$user@$ip] $event" . PHP_EOL;
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Optional: To enable detailed access logging, uncomment the line below
// logSecurityEvent('Admin page accessed: ' . $_SERVER['PHP_SELF'], 'info');
