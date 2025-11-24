# 🔒 GABAY Admin Security - Quick Reference Card

## 🚀 Quick Start

### For First Time Use:
1. Login at: `http://localhost/FinalDev/login.php`
2. Use your admin credentials
3. Access dashboard at: `http://localhost/FinalDev/home.php`

### If You Get Locked Out:
- **Reason**: Too many failed login attempts (5 in 15 minutes)
- **Solution**: Wait 15 minutes and try again
- **Prevention**: Use correct password!

---

## 🎯 Common Tasks

### Login
```
URL: login.php
Credentials: admin_user / your_password
Timeout: 30 minutes of inactivity
```

### Logout
```
Click "Logout" button in sidebar
Or visit: logout.php
```

### Access Admin Page
```
All admin pages automatically check authentication
No manual token management needed
Just login and navigate normally
```

---

## 🛠️ For Developers

### Securing a New Admin Page

```php
<?php
// Add this at the very top of your PHP file:
require_once 'auth_guard.php';

// Rest of your code...
include 'connect_db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
    <script src="auth_helper.js"></script>
</head>
<body>
    <!-- Your page content -->
</body>
</html>
```

### Securing AJAX Endpoints

```php
<?php
require_once 'auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    // Your AJAX logic here...
}
?>
```

### Making Authenticated AJAX Requests

```javascript
// OLD WAY (Don't use):
$.ajax({
    url: 'endpoint.php',
    method: 'POST',
    data: { action: 'save', value: 123 }
});

// NEW WAY (Use this):
AdminAuth.ajax({
    url: 'endpoint.php',
    method: 'POST',
    data: { action: 'save', value: 123 },
    success: function(response) {
        console.log('Success!', response);
    }
});
// CSRF token automatically added!
```

---

## 📊 Security Features

| Feature | Status | Details |
|---------|--------|---------|
| **Authentication** | ✅ Active | Token-based, 256-bit security |
| **Session Timeout** | ✅ Active | 30 minutes of inactivity |
| **CSRF Protection** | ✅ Active | All forms and AJAX |
| **Rate Limiting** | ✅ Active | 5 attempts per 15 minutes |
| **Security Logging** | ✅ Active | logs/security.log |
| **XSS Prevention** | ✅ Active | All input escaped |
| **Session Fixation Prevention** | ✅ Active | Regeneration every 15 min |

---

## 🔍 Troubleshooting

### Problem: Can't Access Admin Page
**Check:**
- Are you logged in?
- Has your session expired? (30 min timeout)
- Try logging in again

### Problem: "Invalid CSRF Token" Error
**Fix:**
```javascript
// Use AdminAuth.ajax() instead of $.ajax()
AdminAuth.ajax({ /* your options */ });
```

### Problem: Logged Out Automatically
**Reason:** Session expired after 30 minutes of inactivity
**Solution:** Log in again

### Problem: "Too Many Login Attempts"
**Reason:** Rate limiting (5 failed attempts)
**Solution:** Wait 15 minutes and try again

### Problem: Redirect Loop (login → home → login)
**Fix:** Ensure login.php does NOT have `require_once 'auth_guard.php';`

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `auth_guard.php` | Main authentication system |
| `login.php` | Login page |
| `logout.php` | Logout handler |
| `auth_helper.js` | Client-side security utilities |
| `logs/security.log` | Security event log |

---

## 🧪 Quick Test

### Test 1: Unauthenticated Access
```bash
# In incognito browser:
http://localhost/FinalDev/home.php
# Should redirect to login.php
```

### Test 2: Login Works
```bash
# Login with correct credentials
# Should see admin dashboard
```

### Test 3: Logout Works
```bash
# Click logout button
# Should redirect to login
# Try accessing home.php
# Should redirect to login again
```

### Test 4: Session Timeout
```bash
# Login successfully
# Wait 31 minutes
# Refresh page
# Should redirect to login with "session expired" message
```

---

## 📖 Full Documentation

- **Complete Guide**: ADMIN_AUTH_DOCUMENTATION.md
- **Testing Guide**: TESTING_GUIDE.md
- **Summary**: SECURITY_IMPLEMENTATION_SUMMARY.md

---

## 🎯 Key Points to Remember

1. ✅ **All admin pages are now protected** - No authentication bypass possible
2. ✅ **Sessions expire after 30 minutes** - Automatic logout on inactivity
3. ✅ **Use AdminAuth.ajax() for AJAX** - Automatically handles tokens
4. ✅ **Logout completely clears session** - Requires re-login
5. ✅ **Failed logins are rate limited** - 5 attempts per 15 minutes
6. ✅ **All security events are logged** - Check logs/security.log

---

## 💡 Best Practices

### For Admins:
- ✅ Always logout when finished
- ✅ Use strong passwords
- ✅ Don't share credentials
- ✅ Monitor security logs regularly

### For Developers:
- ✅ Use `require_once 'auth_guard.php';` on all admin pages
- ✅ Use `AdminAuth.ajax()` for all AJAX requests
- ✅ Validate CSRF tokens on all POST endpoints
- ✅ Test authentication before deploying

---

## 🆘 Emergency Contacts

### Can't Login at All?
1. Check database connection (connect_db.php)
2. Verify admin user exists in database
3. Reset password using updatePassHash.php
4. Check logs/security.log for errors

### System Not Working?
1. Run automated tests: `php test_auth.php`
2. Review TESTING_GUIDE.md
3. Check error logs in XAMPP
4. Verify all files exist (auth_guard.php, etc.)

---

## 📞 Support Resources

**Documentation:**
- ADMIN_AUTH_DOCUMENTATION.md - Complete system documentation
- TESTING_GUIDE.md - Testing procedures
- SECURITY_IMPLEMENTATION_SUMMARY.md - Implementation overview

**Quick Commands:**
```bash
# View security log
type "logs\security.log"

# Run automated tests
php test_auth.php

# Check if auth_guard exists
dir auth_guard.php
```

---

**🔒 Security Status: ACTIVE ✅**

*Last Updated: October 22, 2025*
