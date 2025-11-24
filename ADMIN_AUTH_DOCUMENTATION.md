# GABAY Admin Authentication System Documentation

## Overview

This document describes the comprehensive token-based authentication system implemented for all GABAY admin tools and pages. The system ensures that only authenticated administrators can access admin resources, with automatic session management, token expiration, and CSRF protection.

## Features

✅ **Token-Based Authentication** - Secure cryptographic tokens generated for each session
✅ **Automatic Session Management** - Sessions expire after 30 minutes of inactivity
✅ **CSRF Protection** - Cross-Site Request Forgery protection for all forms and AJAX requests
✅ **Brute Force Protection** - Rate limiting prevents password guessing attacks
✅ **Security Logging** - All authentication events are logged for audit trail
✅ **Seamless Redirects** - Return URLs preserve user navigation after login
✅ **AJAX Support** - Graceful handling of auth errors in AJAX requests

## Architecture

### Core Components

1. **auth_guard.php** - Main authentication middleware
   - Validates sessions and tokens on every page load
   - Automatically redirects unauthenticated users to login
   - Provides helper functions for token management

2. **login.php** - Secure login page
   - Rate limiting (5 attempts per 15 minutes)
   - Support for both hashed and plain-text passwords (legacy)
   - Security event logging
   - Return URL support

3. **logout.php** - Secure logout handler
   - Destroys authentication tokens
   - Clears all session data
   - Removes session cookies
   - Logs logout events

4. **auth_helper.js** - Client-side authentication utilities
   - CSRF token management
   - Authenticated AJAX requests
   - Automatic token injection in forms
   - Session expiration handling

## How It Works

### Authentication Flow

```
1. User visits admin page (e.g., home.php)
   ↓
2. auth_guard.php checks if authenticated
   ↓
3. If NOT authenticated:
   - Redirect to login.php with return URL
   - Show login form
   ↓
4. User enters credentials
   ↓
5. login.php validates credentials
   - Check rate limit
   - Verify password
   - Generate auth token
   - Initialize session
   ↓
6. Redirect to original page (or home.php)
   ↓
7. auth_guard.php validates token
   - Check session variables
   - Check token exists
   - Check inactivity timeout (30 min)
   - Update last activity timestamp
   ↓
8. User accesses admin page successfully
```

### Session Timeout

- **Inactivity Timeout**: 30 minutes (1800 seconds)
- **Last Activity Tracking**: Updated on every page load
- **Automatic Expiration**: Session destroyed after timeout
- **User Notification**: Redirect to login with message

### Token Management

- **Token Generation**: 64-character hexadecimal (256-bit security)
- **Token Storage**: PHP session variables
- **Token Validation**: Checked on every page load
- **Token Expiration**: On logout or session timeout
- **CSRF Tokens**: Separate 64-character tokens for forms

## Implementation

### Securing Admin Pages

All admin pages now include authentication guard at the top:

```php
<?php
// Require authentication - automatically redirects to login if not authenticated
require_once 'auth_guard.php';

include 'connect_db.php';
// ... rest of page code
?>
```

### Secured Pages

The following admin pages are now protected:

**Main Dashboard Pages:**
- home.php
- officeManagement.php
- floorPlan.php
- systemSettings.php
- visitorFeedback.php
- geofence_admin_dashboard.php

**Admin Tools:**
- generate_qrcodes.php
- generate_panorama_qrs.php
- panorama_qr_manager.php
- migrate_geofence_enabled.php
- update_panorama_qr_urls.php
- regenerate_all_panorama_qr.php
- animated_hotspot_manager.php
- panorama_tour_manager.php
- video_hotspot_manager.php

### CSRF Protection

#### Server-Side (PHP)

All AJAX endpoints should validate CSRF tokens:

```php
<?php
require_once 'auth_guard.php';

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    // Process request...
}
?>
```

#### Client-Side (JavaScript)

Use AdminAuth.ajax() for authenticated AJAX requests:

```javascript
// Traditional AJAX call
$.ajax({
    url: 'endpoint.php',
    method: 'POST',
    data: { action: 'saveData', value: 123 },
    success: function(response) { ... }
});

// Replace with authenticated AJAX
AdminAuth.ajax({
    url: 'endpoint.php',
    method: 'POST',
    data: { action: 'saveData', value: 123 },
    success: function(response) { ... }
});
// CSRF token is automatically added
// Auth errors are automatically handled
```

#### Adding CSRF to HTML Forms

**Option 1: PHP Helper Function**
```php
<form method="POST" action="save.php">
    <?php echo csrfTokenField(); ?>
    <input type="text" name="data" />
    <button type="submit">Save</button>
</form>
```

**Option 2: Manual Meta Tag**
```html
<head>
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
</head>
```

**Option 3: JavaScript Auto-Injection**
```html
<script src="auth_helper.js"></script>
<!-- Automatically adds CSRF token to all forms -->
```

## Security Features

### Rate Limiting

**Login Attempts:**
- Maximum: 5 failed attempts
- Time Window: 15 minutes
- Lockout: User must wait until window expires
- Tracking: Per-session attempt counter

**Implementation:**
```php
// In login.php
function checkRateLimit($username) {
    $maxAttempts = 5;
    $timeWindow = 900; // 15 minutes
    
    // Check attempt count
    if (count($_SESSION['login_attempts']) >= $maxAttempts) {
        return ['allowed' => false, 'wait_time' => $minutes];
    }
    
    return ['allowed' => true];
}
```

### Security Logging

All authentication events are logged to `logs/security.log`:

**Logged Events:**
- Successful logins
- Failed login attempts
- Rate limit violations
- Session expirations
- Logout events
- Page access (optional)

**Log Format:**
```
[2025-10-22 14:30:45] [info] [admin@192.168.1.100] Successful login for user: admin
[2025-10-22 14:35:12] [warning] [192.168.1.100] Failed login attempt for username: admin (incorrect password)
[2025-10-22 15:00:30] [info] [admin@192.168.1.100] User logged out
```

**Enable Page Access Logging:**
```php
// In any admin page
define('AUTH_LOGGING', true);
require_once 'auth_guard.php';
// Now all page accesses are logged
```

### Session Security

**Secure Cookie Configuration:**
```php
ini_set('session.cookie_httponly', 1);    // Prevent JavaScript access
ini_set('session.use_only_cookies', 1);   // No URL parameters
ini_set('session.cookie_secure', 0);      // Set to 1 for HTTPS
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.gc_maxlifetime', 1800);  // 30-minute timeout
```

**Session Regeneration:**
- On login: Prevents session fixation attacks
- Every 15 minutes: Rotating session IDs
- Implementation in auth_guard.php

## Helper Functions

### PHP Functions (auth_guard.php)

```php
// Check if user is authenticated
isAuthenticated() → bool

// Initialize auth session (on login)
initAuthSession($adminId, $username) → string (token)

// Destroy auth session (on logout)
destroyAuthSession() → void

// Generate CSRF token
generateCSRFToken() → string

// Validate CSRF token
validateCSRFToken($token) → bool

// Get CSRF token HTML field
csrfTokenField() → string (HTML)

// Get CSRF token value
csrfToken() → string

// Get current admin info
getAuthAdmin() → array

// Log security event
logSecurityEvent($event, $level) → void

// Redirect to login
redirectToLogin($message) → void (exits)

// Send JSON auth error
sendAuthErrorJSON($message, $code) → void (exits)
```

### JavaScript Functions (auth_helper.js)

```javascript
// Make authenticated AJAX request
AdminAuth.ajax(options) → jqXHR

// Get CSRF token
AdminAuth.getToken() → string

// Refresh CSRF token
AdminAuth.refreshToken(callback) → void

// Logout user
AdminAuth.logout() → void
```

## Testing the System

### Test Cases

1. **Unauthenticated Access**
   - Visit any admin page without logging in
   - Expected: Redirect to login.php with return URL

2. **Successful Login**
   - Enter valid credentials
   - Expected: Redirect to home.php or return URL
   - Session variables set correctly

3. **Failed Login**
   - Enter invalid credentials
   - Expected: Error message displayed
   - Attempt counter incremented

4. **Rate Limiting**
   - Make 5 failed login attempts
   - Expected: Locked out for 15 minutes
   - Error message shows wait time

5. **Session Timeout**
   - Login successfully
   - Wait 30+ minutes without activity
   - Try to access admin page
   - Expected: Redirect to login with "session expired" message

6. **Logout**
   - Click logout button
   - Expected: Redirect to login.php
   - All session data cleared
   - Cannot access admin pages without re-login

7. **AJAX Authentication**
   - Make AJAX request without CSRF token
   - Expected: 403 Forbidden error
   - Make AJAX request with expired session
   - Expected: 401 Unauthorized + auto-redirect

### Manual Testing Steps

```bash
# 1. Test unauthenticated access
# Open browser in incognito mode
http://localhost/FinalDev/home.php
# Should redirect to login.php

# 2. Test successful login
# Enter: admin_user / your_password
# Should redirect to home.php

# 3. Test session timeout
# Login, then wait 31 minutes
# Refresh page - should redirect to login

# 4. Test logout
# Click logout button
# Should redirect to login
# Try to access home.php - should redirect again

# 5. Check security logs
cat logs/security.log
# Should see login/logout events
```

## Troubleshooting

### Issue: Infinite redirect loop

**Cause:** auth_guard.php or login.php are misconfigured

**Solution:**
```php
// In login.php, ensure this code exists:
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['auth_token'])) {
    header("Location: home.php");
    exit;
}
```

### Issue: "Session expired" on every page

**Cause:** Session variables not persisting

**Solution:**
1. Check PHP session configuration
2. Ensure session_start() is called
3. Check file permissions on session directory
4. Verify session.gc_maxlifetime setting

### Issue: CSRF token validation fails

**Cause:** Token mismatch or missing

**Solution:**
1. Ensure auth_helper.js is included
2. Check CSRF token is in form/AJAX data
3. Verify validateCSRFToken() is called correctly
4. Check if session expired (token regenerated)

### Issue: Unable to login

**Cause:** Database connection or password hash issue

**Solution:**
1. Check connect_db.php is working
2. Verify admin table exists with correct schema
3. Check password hash: `password_verify()` for hashed, direct comparison for plain-text
4. Check rate limiting - may be temporarily locked out

## Security Best Practices

### For Production Deployment

1. **Enable HTTPS**
   ```php
   // In auth_guard.php, change:
   ini_set('session.cookie_secure', 1); // Enable for HTTPS
   ```

2. **Update Password Hashes**
   ```php
   // Run updatePassHash.php to convert plain-text to hashed passwords
   // After conversion, remove legacy password check in login.php
   ```

3. **Set Strong Session Timeout**
   ```php
   // For high-security environments:
   ini_set('session.gc_maxlifetime', 900); // 15 minutes
   ```

4. **Enable Access Logging**
   ```php
   // In critical admin pages:
   define('AUTH_LOGGING', true);
   require_once 'auth_guard.php';
   ```

5. **Regular Security Audits**
   ```bash
   # Review security logs weekly
   tail -n 100 logs/security.log
   
   # Check for suspicious activity:
   grep "Failed login" logs/security.log | wc -l
   grep "Rate limit" logs/security.log
   ```

6. **Database Security**
   ```php
   // Ensure admin table has proper constraints
   // Use prepared statements (already implemented)
   // Limit admin accounts to minimum necessary
   ```

## Maintenance

### Log Rotation

Security logs should be rotated regularly:

```php
// Create log_rotate.php
<?php
$logFile = __DIR__ . '/logs/security.log';
$archiveFile = __DIR__ . '/logs/security_' . date('Y-m-d') . '.log';

if (file_exists($logFile) && filesize($logFile) > 10485760) { // 10MB
    rename($logFile, $archiveFile);
    touch($logFile);
}
?>
```

### Token Refresh

For long-running sessions, refresh CSRF tokens:

```javascript
// Refresh token every 25 minutes (before 30-min timeout)
setInterval(function() {
    AdminAuth.refreshToken(function(success) {
        if (!success) {
            console.warn('Failed to refresh CSRF token');
        }
    });
}, 1500000); // 25 minutes
```

## Migration Notes

### Upgrading from Old System

The old system used only `$_SESSION['logged_in']` without tokens. New system adds:

- `$_SESSION['auth_token']` - Cryptographic authentication token
- `$_SESSION['csrf_token']` - CSRF protection token
- `$_SESSION['admin_id']` - Admin user ID (replaces 'id')
- `$_SESSION['admin_username']` - Admin username (replaces 'username')
- `$_SESSION['last_activity']` - Inactivity tracking
- `$_SESSION['last_regeneration']` - Session regeneration tracking

**Backward Compatibility:** Sessions from old system will be invalidated (logged out automatically).

### Database Changes

No database schema changes required. Authentication uses session storage only.

Optional: Add security_logs table for permanent audit trail:

```sql
CREATE TABLE security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    level VARCHAR(20),
    username VARCHAR(100),
    ip_address VARCHAR(45),
    event TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Support

### Files to Review

- `auth_guard.php` - Main authentication logic
- `login.php` - Login page with rate limiting
- `logout.php` - Logout handler
- `auth_helper.js` - Client-side utilities
- `logs/security.log` - Security event log

### Common Issues

- Session not persisting: Check PHP session configuration
- CSRF validation failing: Ensure token is included in requests
- Infinite redirects: Check login.php conditional logic
- Rate limiting too aggressive: Adjust maxAttempts/timeWindow

---

**Version:** 1.0  
**Last Updated:** October 22, 2025  
**Author:** GABAY Development Team
