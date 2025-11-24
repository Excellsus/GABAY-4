# 🚀 GABAY Production Deployment - Quick Start

## What Changed for Production

### ✅ All Updated Files (No Manual Changes Needed)

The following files now automatically detect the environment and use production URLs:

1. **connect_db.php** - Database configuration with environment detection
2. **auth_guard.php** - HTTPS auto-detection for secure cookies
3. **panorama_api.php** - Dynamic URL detection for panorama QR codes
4. **panorama_qr_api.php** - Enhanced IP detection with production fallback
5. **door_qr_api.php** - Dynamic door QR code URL generation
6. **generate_qrcodes.php** - Office QR codes use production URLs
7. **forgot_password.php** - Password reset links use current domain

### 🔧 What You Need to Do

#### 1. Update Database Credentials (REQUIRED)

Edit `connect_db.php` line 14-18:

```php
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    // Production settings - UPDATE THESE
    $db_host = "sql12345.infinityfreeapp.com"; // Your MySQL host from InfinityFree
    $db_name = "epiz_12345678_gabay"; // Your database name
    $db_username = "epiz_12345678"; // Your database username
    $db_password = "your_secure_password_here"; // Your database password
}
```

Get these credentials from InfinityFree cPanel → MySQL Databases

#### 2. Upload Files

**Simple Method (File Manager):**
1. InfinityFree cPanel → File Manager
2. Go to `htdocs/`
3. Upload entire `gabay/` folder
4. Done!

**FTP Method:**
1. Use FileZilla or similar
2. Connect to your InfinityFree FTP
3. Upload `gabay/` folder to `htdocs/`

#### 3. Import Database

1. InfinityFree cPanel → phpMyAdmin
2. Select your database
3. Click Import tab
4. Upload `admin (14).sql`
5. Click Go

#### 4. Set Permissions

In File Manager, right-click these folders and set to 755:
- `gabay/QR/`
- `gabay/Pano/`
- `gabay/logs/` (create if doesn't exist)
- `gabay/animated_hotspot_icons/`

#### 5. Regenerate QR Codes

Access these URLs once (they auto-run):
```
https://localhost/gabay/generate_qrcodes.php
https://localhost/gabay/regenerate_all_panorama_qr.php
```

This updates all QR codes from localhost to production URLs.

#### 6. Login and Test

Go to: `https://localhost/gabay/login.php`

**Default credentials:**
- Username: `admin_user`
- Password: `password`

**⚠️ Change this password immediately in System Settings!**

## Quick Test Checklist

After deployment, verify these work:

- [ ] Can login to admin panel
- [ ] Dashboard loads
- [ ] Can access System Settings and change password
- [ ] Mobile interface loads: `/mobileScreen/explore.php`
- [ ] Office search works
- [ ] QR code scan opens correct page
- [ ] Password reset sends email with correct link

## Troubleshooting

**"Connection failed"**
→ Check database credentials in `connect_db.php`

**"Session expired immediately"**
→ Clear browser cookies and try again

**QR codes show localhost**
→ Run the regenerate scripts (step 5 above)

**Images not loading**
→ Check folder permissions are 755

**500 Error**
→ Check PHP error logs in cPanel

## Files to NEVER Upload

Don't upload these to production:
- `*.bak` (backup files)
- `*.log` (log files)
- `check_*.php` (debug scripts)
- `test_*.php` (test files)
- `.git/` (version control)
- Local database exports (only import fresh one)

## Security After Deployment

**IMMEDIATELY do these:**

1. **Change Admin Password**
   - System Settings → Change Password
   - Use strong password (16+ characters)

2. **Update Database Password**
   - Use strong password in `connect_db.php`
   - Never use "root" or empty password in production

3. **Verify HTTPS Works**
   - All pages should use https://
   - Check green padlock in browser

4. **Test Access Control**
   - Try accessing: `https://localhost/gabay/connect_db.php`
   - Should get 403 Forbidden (this is correct!)
   - `.htaccess` protects sensitive files

## Getting Help

**Read the full guides:**
- `PRODUCTION_DEPLOYMENT_GUIDE.md` - Complete step-by-step guide
- `DEPLOYMENT_CHECKLIST.md` - Detailed testing checklist

**Common Issues:**
- InfinityFree limits: Check your account limits
- File upload size: Max 10MB per file on free plan
- PHP timeout: Scripts limited to 30 seconds
- Database size: Check your storage quota

**InfinityFree Support:**
- Forum: https://forum.infinityfree.com/
- Knowledge Base: Check cPanel help section

## Production URL Structure

Your site will be accessible at:

```
Admin Panel:
https://localhost/gabay/login.php
https://localhost/gabay/home.php
https://localhost/gabay/officeManagement.php
https://localhost/gabay/floorPlan.php
https://localhost/gabay/systemSettings.php

Mobile Interface:
https://localhost/gabay/mobileScreen/explore.php
https://localhost/gabay/mobileScreen/rooms.php
https://localhost/gabay/mobileScreen/feedback.php

QR Code Generators:
https://localhost/gabay/generate_qrcodes.php
https://localhost/gabay/regenerate_all_panorama_qr.php
```

## Next Steps After Successful Deployment

1. **Print QR Codes:**
   - Download from `/QR/` folder
   - Print on durable material
   - Place at office doors and panorama points

2. **Train Staff:**
   - Show admin panel features
   - Demonstrate mobile interface
   - Explain QR code scanning

3. **Monitor System:**
   - Check error logs daily first week
   - Monitor user feedback
   - Track QR scan analytics

4. **Setup Backups:**
   - Weekly database export
   - Monthly file backup
   - Store backups securely off-site

5. **Optimize:**
   - Monitor page load times
   - Optimize large images if needed
   - Review and act on user feedback

---

**Production URL:** https://localhost/gabay  
**Status:** Ready for Deployment ✅

**Estimated Deployment Time:** 30-60 minutes  
**Difficulty:** Intermediate  
**Prerequisites:** InfinityFree account, database access, basic FTP knowledge
