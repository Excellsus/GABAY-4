<?php
/**
 * =====================================================
 * EMAIL CONFIGURATION FOR GABAY
 * =====================================================
 * 
 * SMTP settings for sending emails through Gmail.
 * 
 * SETUP INSTRUCTIONS:
 * 1. Enable 2-Step Verification in your Gmail account
 * 2. Generate an App Password: https://myaccount.google.com/apppasswords
 * 3. Update the credentials below
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // or 465 for SSL
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USERNAME', 'excellsusjavier@gmail.com'); // YOUR Gmail address here
define('SMTP_PASSWORD', 'ecokbpkhtswrdgpr'); // YOUR Gmail App Password (NOT your regular password)
define('SMTP_FROM_EMAIL', 'excellsusjavier@gmail.com'); // Same as SMTP_USERNAME
define('SMTP_FROM_NAME', 'GABAY Admin System'); // From name

// Alternative: Use a dedicated SMTP service for better deliverability
// Services like SendGrid, Mailgun, or Amazon SES offer free tiers
// and are more reliable than Gmail SMTP for production use
?>
