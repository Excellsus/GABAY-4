# GABAY Admin Authentication System - Testing Guide

## Quick Start Testing

### Prerequisites
1. Ensure XAMPP is running (Apache + MySQL)
2. Database connection working (connect_db.php configured)
3. Admin user exists in `admin` table

### Test 1: Unauthenticated Access Protection

**Purpose:** Verify that unauthenticated users cannot access admin pages

**Steps:**
1. Open browser in incognito/private mode
2. Visit: `http://localhost/FinalDev/home.php`
3. **Expected Result:** Automatically redirected to `login.php`
4. URL should show: `login.php?return=/FinalDev/home.php`

**Try with other admin pages:**
- `http://localhost/FinalDev/officeManagement.php`
- `http://localhost/FinalDev/floorPlan.php`
- `http://localhost/FinalDev/systemSettings.php`
- `http://localhost/FinalDev/generate_qrcodes.php`

**Success Criteria:** All pages redirect to login

---

### Test 2: Successful Login

**Purpose:** Verify login works and session is created properly

**Steps:**
1. On login page, enter credentials:
   - Username: `admin_user` (or your admin username)
   - Password: [your password]
2. Click "Sign In"
3. **Expected Result:** Redirected to `home.php`
4. Should see admin dashboard with your data

**Verify Session:**
1. Open browser developer tools (F12)
2. Go to Application → Cookies
3. Look for `PHPSESSID` cookie
4. Should be present and have a value

**Success Criteria:** 
- Login successful
- Dashboard loads
- Cookie created

---

### Test 3: Failed Login & Rate Limiting

**Purpose:** Verify incorrect credentials are rejected and rate limiting works

**Steps:**
1. Logout (or use incognito window)
2. Try login with wrong password 3 times
3. **Expected Result:** Error message "Invalid username or password"
4. Try 2 more times (total 5 failed attempts)
5. **Expected Result:** Error message "Too many failed login attempts. Please try again in X minutes."
6. Wait 1 minute and try again
7. **Expected Result:** Still locked out (15-minute window)

**Verify Security Log:**
```bash
# On Windows with XAMPP:
type "C:\Program Files\xampp\htdocs\FinalDev\logs\security.log"

# Should show:
[timestamp] [warning] [IP] Failed login attempt for username: admin_user (incorrect password)
[timestamp] [warning] [IP] Rate limit exceeded for username: admin_user
```

**Success Criteria:**
- Failed attempts logged
- Rate limiting activates after 5 attempts
- User locked out for 15 minutes

---

### Test 4: Session Timeout

**Purpose:** Verify sessions expire after 30 minutes of inactivity

**Steps:**
1. Login successfully
2. Note the current time
3. **Important:** Don't click anything or reload for 31 minutes
4. After 31 minutes, try to navigate to another admin page
5. **Expected Result:** Redirected to login with message "Your session has expired. Please log in again."

**Quick Test (for development):**
1. Temporarily modify timeout in `auth_guard.php`:
```php
// Change line ~112 from 1800 to 60 (1 minute)
if ($inactive > 60) { // 1 minute for testing
```
2. Login
3. Wait 61 seconds
4. Reload page
5. Should redirect to login
6. **Remember to change back to 1800**

**Success Criteria:**
- Session expires after inactivity period
- User redirected to login
- Informative message displayed

---

### Test 5: Logout Functionality

**Purpose:** Verify logout clears all authentication data

**Steps:**
1. Login successfully
2. Navigate to any admin page
3. Click logout button in sidebar
4. **Expected Result:** Redirected to login.php with message "You have been logged out successfully."
5. Try to access `home.php` directly
6. **Expected Result:** Redirected to login again

**Verify Session Destroyed:**
1. Open browser developer tools (F12)
2. Go to Application → Cookies
3. `PHPSESSID` cookie should be deleted or have expired timestamp

**Verify Security Log:**
```bash
type "C:\Program Files\xampp\htdocs\FinalDev\logs\security.log"

# Should show:
[timestamp] [info] [admin@IP] User logged out
```

**Success Criteria:**
- Logout redirects to login
- Session cookie deleted
- Cannot access admin pages without re-login
- Logout event logged

---

### Test 6: Return URL Functionality

**Purpose:** Verify users are redirected to intended page after login

**Steps:**
1. Logout completely
2. Try to access: `http://localhost/FinalDev/officeManagement.php`
3. **Expected Result:** Redirected to `login.php?return=/FinalDev/officeManagement.php`
4. Login with correct credentials
5. **Expected Result:** Automatically redirected to `officeManagement.php` (not home.php)

**Success Criteria:**
- Return URL preserved in login redirect
- After login, user returns to originally requested page

---

### Test 7: CSRF Token Protection

**Purpose:** Verify CSRF tokens protect against form replay attacks

**Test A: Missing CSRF Token**
1. Login successfully
2. Open browser developer tools (F12) → Console
3. Try to make AJAX request without CSRF token:
```javascript
$.ajax({
    url: 'systemSettings.php',
    method: 'POST',
    data: { ajax: 'updateAccount', email: 'test@test.com' },
    success: function(r) { console.log(r); },
    error: function(r) { console.log(r); }
});
```
4. **Expected Result:** 403 Forbidden error
5. Response: `{"success":false,"error":"Invalid CSRF token"}`

**Test B: Valid CSRF Token (using AdminAuth)**
1. Try same request using authenticated AJAX:
```javascript
AdminAuth.ajax({
    url: 'systemSettings.php',
    method: 'POST',
    data: { ajax: 'updateAccount', email: 'test@test.com', password: '', confirm_password: '' },
    success: function(r) { console.log('Success:', r); },
    error: function(r) { console.log('Error:', r); }
});
```
2. **Expected Result:** Request succeeds (or proper validation error)
3. CSRF token automatically included

**Success Criteria:**
- Requests without CSRF token are rejected
- Requests with valid CSRF token are accepted
- AdminAuth.ajax() automatically adds token

---

### Test 8: Token Persistence Across Pages

**Purpose:** Verify authentication tokens persist when navigating between pages

**Steps:**
1. Login successfully
2. Navigate to `home.php` → verify access granted
3. Click to `officeManagement.php` → verify access granted
4. Click to `floorPlan.php` → verify access granted
5. Click to `systemSettings.php` → verify access granted
6. Refresh any page → verify still authenticated

**Success Criteria:**
- No re-login required when navigating
- Session persists across page loads
- Last activity timestamp updates with each navigation

---

### Test 9: Concurrent Session Handling

**Purpose:** Verify behavior with multiple browser sessions

**Steps:**
1. Login in Chrome
2. Open Firefox (or another browser)
3. Login with same credentials in Firefox
4. **Expected Result:** Both sessions active (not mutually exclusive)
5. Logout in Chrome
6. Check Firefox → still logged in (separate session)

**Success Criteria:**
- Multiple sessions allowed per user
- Logout in one browser doesn't affect other sessions
- Each session has independent token

---

### Test 10: XSS Protection in Login Messages

**Purpose:** Verify user input is properly escaped

**Steps:**
1. Logout
2. Try to login with username: `<script>alert('XSS')</script>`
3. **Expected Result:** Error message displayed, but no alert popup
4. Check HTML source of login page
5. Username should be escaped: `&lt;script&gt;alert('XSS')&lt;/script&gt;`

**Success Criteria:**
- No script execution
- HTML properly escaped
- XSS attempt blocked

---

## Advanced Testing

### Test 11: Session Regeneration

**Purpose:** Verify session IDs are regenerated to prevent fixation attacks

**Steps:**
1. Login successfully
2. Note the `PHPSESSID` cookie value in dev tools
3. Wait 16 minutes (session regenerates every 15 minutes)
4. Reload page
5. Check `PHPSESSID` again
6. **Expected Result:** Cookie value changed (new session ID)

**Success Criteria:**
- Session ID changes every 15 minutes
- User remains authenticated through regeneration

---

### Test 12: Security Logging

**Purpose:** Verify all security events are logged

**View Security Log:**
```bash
# Windows
type "C:\Program Files\xampp\htdocs\FinalDev\logs\security.log"

# Linux/Mac
cat /var/www/html/FinalDev/logs/security.log
```

**Check for Events:**
- `[info] Successful login for user: username`
- `[warning] Failed login attempt for username: username (incorrect password)`
- `[warning] Rate limit exceeded for username: username`
- `[info] [username@IP] User logged out`

**Success Criteria:**
- All login attempts logged
- All logout events logged
- Timestamps and IP addresses included

---

### Test 13: Direct Tool Access

**Purpose:** Verify admin tools require authentication

**Steps:**
1. Logout completely
2. Try to access these URLs directly:
   - `http://localhost/FinalDev/generate_qrcodes.php`
   - `http://localhost/FinalDev/panorama_qr_manager.php`
   - `http://localhost/FinalDev/migrate_geofence_enabled.php`
   - `http://localhost/FinalDev/animated_hotspot_manager.php`
3. **Expected Result:** All redirect to login

**Success Criteria:**
- No admin tool accessible without authentication
- All tools redirect to login when accessed directly

---

### Test 14: AJAX Session Expiration Handling

**Purpose:** Verify AJAX requests handle expired sessions gracefully

**Steps:**
1. Login successfully
2. Open `systemSettings.php`
3. Open browser console (F12)
4. Manually destroy session:
```javascript
// In another tab, logout
// Or wait for session to expire naturally
```
5. Try to make AJAX request from original tab:
```javascript
AdminAuth.ajax({
    url: 'systemSettings.php',
    method: 'POST',
    data: { ajax: 'updateAccount', email: 'test@test.com' },
    success: function(r) { console.log('Success:', r); }
});
```
6. **Expected Result:** 
   - Alert: "Your session has expired. Please log in again."
   - Automatic redirect to login after 1.5 seconds

**Success Criteria:**
- AJAX requests detect expired sessions
- User notified clearly
- Automatic redirect to login

---

## Troubleshooting Common Issues

### Issue 1: Infinite Redirect Loop

**Symptoms:** Login → home → login → home (loop)

**Check:**
1. Ensure `auth_guard.php` is NOT included in `login.php`
2. Verify login.php has proper session check:
```php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['auth_token'])) {
    header("Location: home.php");
    exit;
}
```

**Fix:** Remove `require_once 'auth_guard.php';` from `login.php` if present

---

### Issue 2: "Session expired" on Every Page Load

**Symptoms:** Can login but immediately logged out on next page

**Check:**
1. PHP session configuration:
```php
// In php.ini
session.gc_maxlifetime = 1800
session.cookie_lifetime = 0
```
2. File permissions on session directory
3. Ensure `session_start()` called before any output

**Fix:**
```bash
# Check session directory permissions
# Windows: C:\xampp\tmp
# Linux: /var/lib/php/sessions
```

---

### Issue 3: CSRF Token Validation Always Fails

**Symptoms:** All AJAX requests return "Invalid CSRF token"

**Check:**
1. Ensure meta tag is present in HTML:
```html
<meta name="csrf-token" content="<?php echo csrfToken(); ?>">
```
2. Ensure `auth_helper.js` is loaded:
```html
<script src="auth_helper.js"></script>
```
3. Using `AdminAuth.ajax()` not plain `$.ajax()`

**Fix:**
1. Add meta tag to all admin pages
2. Include auth_helper.js after jQuery
3. Use AdminAuth.ajax() for all authenticated requests

---

### Issue 4: Can't Login with Correct Password

**Symptoms:** Valid credentials rejected

**Check:**
1. Database connection working
2. Admin user exists:
```sql
SELECT * FROM admin WHERE username = 'admin_user';
```
3. Password hash format:
```sql
-- Should start with $2y$ for bcrypt
-- Length should be 60 characters
```

**Fix:**
1. Reset password using `updatePassHash.php`
2. Or manually:
```php
<?php
$password = 'your_password';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
// Copy this hash to database
?>
```

---

### Issue 5: Security Log Not Created

**Symptoms:** No `logs/security.log` file

**Check:**
1. Directory permissions
2. PHP write permissions

**Fix:**
```bash
# Windows (in command prompt as admin)
mkdir "C:\Program Files\xampp\htdocs\FinalDev\logs"
icacls "C:\Program Files\xampp\htdocs\FinalDev\logs" /grant Users:F

# Linux/Mac
mkdir -p /var/www/html/FinalDev/logs
chmod 755 /var/www/html/FinalDev/logs
```

---

## Automated Testing Script

Save this as `test_auth.php` in your project root:

```php
<?php
/**
 * Automated Authentication Testing Script
 * Run from command line: php test_auth.php
 */

echo "GABAY Authentication System - Automated Tests\n";
echo str_repeat("=", 50) . "\n\n";

$tests_passed = 0;
$tests_failed = 0;

// Test 1: auth_guard.php exists
echo "Test 1: Check auth_guard.php exists... ";
if (file_exists('auth_guard.php')) {
    echo "✓ PASS\n";
    $tests_passed++;
} else {
    echo "✗ FAIL\n";
    $tests_failed++;
}

// Test 2: logout.php exists
echo "Test 2: Check logout.php exists... ";
if (file_exists('logout.php')) {
    echo "✓ PASS\n";
    $tests_passed++;
} else {
    echo "✗ FAIL\n";
    $tests_failed++;
}

// Test 3: auth_helper.js exists
echo "Test 3: Check auth_helper.js exists... ";
if (file_exists('auth_helper.js')) {
    echo "✓ PASS\n";
    $tests_passed++;
} else {
    echo "✗ FAIL\n";
    $tests_failed++;
}

// Test 4: logs directory writable
echo "Test 4: Check logs directory writable... ";
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}
if (is_writable($logsDir)) {
    echo "✓ PASS\n";
    $tests_passed++;
} else {
    echo "✗ FAIL (not writable)\n";
    $tests_failed++;
}

// Test 5: Database connection
echo "Test 5: Check database connection... ";
try {
    require_once 'connect_db.php';
    if (isset($connect) && $connect) {
        echo "✓ PASS\n";
        $tests_passed++;
        
        // Test 6: Admin table exists
        echo "Test 6: Check admin table exists... ";
        $stmt = $connect->query("SHOW TABLES LIKE 'admin'");
        if ($stmt->rowCount() > 0) {
            echo "✓ PASS\n";
            $tests_passed++;
            
            // Test 7: Admin user exists
            echo "Test 7: Check admin user exists... ";
            $stmt = $connect->query("SELECT COUNT(*) as count FROM admin");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                echo "✓ PASS\n";
                $tests_passed++;
            } else {
                echo "✗ FAIL (no admin users)\n";
                $tests_failed++;
            }
        } else {
            echo "✗ FAIL (table doesn't exist)\n";
            $tests_failed++;
            $tests_failed++; // Skip test 7
        }
    } else {
        echo "✗ FAIL\n";
        $tests_failed++;
        $tests_failed += 2; // Skip tests 6 & 7
    }
} catch (Exception $e) {
    echo "✗ FAIL (" . $e->getMessage() . ")\n";
    $tests_failed += 3; // Fail tests 5, 6, 7
}

// Test 8: Admin pages have auth guard
echo "Test 8: Check home.php has auth guard... ";
$homeContent = file_get_contents('home.php');
if (strpos($homeContent, "require_once 'auth_guard.php'") !== false) {
    echo "✓ PASS\n";
    $tests_passed++;
} else {
    echo "✗ FAIL\n";
    $tests_failed++;
}

// Test 9: Login page does NOT have auth guard
echo "Test 9: Check login.php doesn't have auth guard... ";
$loginContent = file_get_contents('login.php');
if (strpos($loginContent, "require_once 'auth_guard.php'") === false) {
    echo "✓ PASS\n";
    $tests_passed++;
} else {
    echo "✗ FAIL (would cause redirect loop)\n";
    $tests_failed++;
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Tests Passed: $tests_passed\n";
echo "Tests Failed: $tests_failed\n";

if ($tests_failed === 0) {
    echo "\n✓ ALL TESTS PASSED! System ready for use.\n";
} else {
    echo "\n✗ SOME TESTS FAILED. Please review and fix issues.\n";
}
?>
```

**Run:**
```bash
cd "C:\Program Files\xampp\htdocs\FinalDev"
php test_auth.php
```

---

## Success Checklist

Use this checklist to verify complete implementation:

- [ ] ✓ auth_guard.php created and working
- [ ] ✓ login.php updated with token generation
- [ ] ✓ logout.php created and working
- [ ] ✓ auth_helper.js created and included in pages
- [ ] ✓ home.php secured with auth guard
- [ ] ✓ officeManagement.php secured
- [ ] ✓ floorPlan.php secured
- [ ] ✓ systemSettings.php secured
- [ ] ✓ visitorFeedback.php secured
- [ ] ✓ All admin tools secured (generate_qrcodes.php, etc.)
- [ ] ✓ CSRF tokens added to AJAX endpoints
- [ ] ✓ CSRF tokens in HTML meta tags
- [ ] ✓ AdminAuth.ajax() used for authenticated requests
- [ ] ✓ Session timeout works (30 minutes)
- [ ] ✓ Logout clears session completely
- [ ] ✓ Rate limiting works (5 attempts / 15 minutes)
- [ ] ✓ Security logging works
- [ ] ✓ Return URLs work correctly
- [ ] ✓ No unauthenticated access possible
- [ ] ✓ Documentation reviewed

---

**Testing Complete:** If all tests pass, your authentication system is production-ready!

**Last Updated:** October 22, 2025
