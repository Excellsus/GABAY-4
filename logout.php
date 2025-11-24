<?php
/**
 * =====================================================
 * GABAY ADMIN LOGOUT
 * =====================================================
 * 
 * Secure logout implementation that:
 * - Destroys authentication tokens
 * - Clears all session data
 * - Removes session cookies
 * - Logs the logout event
 * - Redirects to login page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout event before destroying session
$loggedUser = $_SESSION['admin_username'] ?? 'unknown';
$logFile = __DIR__ . '/logs/security.log';
$logDir = dirname($logFile);

if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$logEntry = "[$timestamp] [info] [$loggedUser@$ip] User logged out" . PHP_EOL;
@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Clear all session variables
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

// Redirect to login page with logout message
session_start(); // Start new session for the message
$_SESSION['login_message'] = 'You have been logged out successfully.';
header("Location: login.php");
exit;
