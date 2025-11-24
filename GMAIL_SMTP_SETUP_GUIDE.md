# Gmail SMTP Setup Guide for GABAY Password Reset

## 📧 Why PHPMailer with Gmail SMTP?

InfinityFree (and most free hosting providers) block the PHP `mail()` function for security reasons. PHPMailer with Gmail SMTP is a reliable alternative that works on any hosting.

---

## 🚀 Quick Setup (5 Steps)

### Step 1: Enable 2-Step Verification in Gmail

1. Go to your Google Account: https://myaccount.google.com/security
2. Under "Signing in to Google", click **"2-Step Verification"**
3. Click **"Get Started"** and follow the setup process
4. Use your phone number to receive verification codes

### Step 2: Generate App Password

1. After enabling 2-Step Verification, go to: https://myaccount.google.com/apppasswords
2. You may need to sign in again
3. Under "Select app", choose **"Mail"**
4. Under "Select device", choose **"Other (Custom name)"**
5. Type: **"GABAY System"**
6. Click **"Generate"**
7. **COPY THE 16-CHARACTER PASSWORD** (it looks like: `abcd efgh ijkl mnop`)
8. Keep this safe - you won't see it again!

### Step 3: Update Email Configuration

Open `email_config.php` and update these lines:

```php
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop'); // The 16-character App Password from Step 2
define('SMTP_FROM_EMAIL', 'your-email@gmail.com'); // Same as username
```

**Example:**
```php
define('SMTP_USERNAME', 'gabay.admin@gmail.com');
define('SMTP_PASSWORD', 'xyzw abcd efgh ijkl'); // DO NOT use your regular Gmail password!
define('SMTP_FROM_EMAIL', 'gabay.admin@gmail.com');
```

### Step 4: Upload Files to InfinityFree

Upload these files/folders to your `gabay/` directory:
- `email_config.php`
- `send_email.php`
- `phpmailer/` (entire folder with subfolders)
- `forgot_password.php` (updated version)
- `reset_password.php`
- `create_password_resets_table.sql`

### Step 5: Create Database Table

1. Log into InfinityFree phpMyAdmin
2. Select your database (`if0_40224155_XXX`)
3. Click "SQL" tab
4. Copy contents of `create_password_resets_table.sql`
5. Paste and click "Go"

---

## ✅ Testing

1. Make sure admin email is set in System Settings
2. Go to: https://localhost/gabay/forgot_password.php
3. Enter admin email
4. Click "Send Reset Link"
5. Check your Gmail inbox (and spam folder!)
6. Click the reset link in the email
7. Set new password

---

## 🔧 Troubleshooting

### "Failed to send email" error

**Check:**
1. ✅ Did you use the **App Password** (16 characters), NOT your regular Gmail password?
2. ✅ Is 2-Step Verification enabled in your Google Account?
3. ✅ Did you update `email_config.php` with correct credentials?
4. ✅ Is the `phpmailer` folder uploaded to your server?

### Email not arriving

1. Check your **spam/junk folder** in Gmail
2. Wait 2-5 minutes (sometimes there's a delay)
3. Check if Gmail sent a security alert to your phone
4. Try using a different email address to test

### "Access denied" error

- Make sure you're using the **App Password**, not your regular password
- Regenerate a new App Password if needed

### Gmail blocking sign-in attempts

1. Check your Gmail for security alerts
2. Go to: https://myaccount.google.com/notifications
3. Allow access for "Less secure apps" (if prompted)
4. Try generating a new App Password

---

## 🔒 Security Notes

- **NEVER commit or share your App Password publicly**
- **NEVER use your regular Gmail password in the code**
- App Passwords can be revoked anytime at: https://myaccount.google.com/apppasswords
- Each App Password is specific to one application

---

## 🆓 Alternative: Free SMTP Services (Recommended for Production)

If you want better deliverability and more professional setup:

### **SendGrid** (Recommended)
- **Free tier:** 100 emails/day
- More reliable than Gmail
- Setup: https://sendgrid.com/

Update `email_config.php`:
```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'apikey'); // literal string "apikey"
define('SMTP_PASSWORD', 'YOUR_SENDGRID_API_KEY');
```

### **Mailgun**
- **Free tier:** 100 emails/day
- Good API and SMTP support
- Setup: https://mailgun.com/

### **Amazon SES**
- Very cheap ($0.10 per 1000 emails)
- Requires AWS account
- Setup: https://aws.amazon.com/ses/

---

## 📝 Files Overview

```
gabay/
├── email_config.php          # SMTP credentials (UPDATE THIS!)
├── send_email.php            # Email sending helper
├── forgot_password.php       # Password reset request page (updated)
├── reset_password.php        # Password reset form
├── phpmailer/                # PHPMailer library
│   ├── src/
│   │   ├── PHPMailer.php
│   │   ├── SMTP.php
│   │   └── Exception.php
│   └── ...
└── create_password_resets_table.sql  # Database schema
```

---

## 🎯 Quick Test Command

After setup, test if emails are working:

1. Go to forgot password page
2. Enter your admin email
3. Submit form
4. Check for success message
5. Check Gmail inbox/spam

If it works: ✅ You're done!  
If not: 📧 Check troubleshooting section above.

---

## 💡 Tips

- Use a **dedicated Gmail account** for sending system emails (not your personal account)
- Add `noreply@localhost` to Gmail's "From" name for professionalism
- Test password reset at least once before deploying to production
- Keep your App Password secure - treat it like a password!

---

Need help? Check the error logs in your hosting control panel or contact support.
