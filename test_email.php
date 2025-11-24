<?php
/**
 * EMAIL TEST SCRIPT
 * Test if PHPMailer and Gmail SMTP are working correctly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>GABAY Email Configuration Test</h1>";
echo "<hr>";

// Check if PHPMailer exists
echo "<h2>1. Checking PHPMailer Installation</h2>";
if (file_exists('phpmailer/src/PHPMailer.php')) {
    echo "✅ PHPMailer found<br>";
} else {
    echo "❌ PHPMailer NOT found. Please make sure phpmailer folder exists.<br>";
    die();
}

// Load PHPMailer
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

echo "✅ PHPMailer loaded successfully<br>";

// Check email config
echo "<h2>2. Checking Email Configuration</h2>";
if (file_exists('email_config.php')) {
    require_once 'email_config.php';
    echo "✅ Email config file found<br>";
    echo "📧 SMTP Host: " . SMTP_HOST . "<br>";
    echo "📧 SMTP Port: " . SMTP_PORT . "<br>";
    echo "📧 SMTP Username: " . SMTP_USERNAME . "<br>";
    echo "📧 SMTP Password: " . (SMTP_PASSWORD === 'YOUR_APP_PASSWORD_HERE' ? '❌ NOT SET' : '✅ SET (length: ' . strlen(SMTP_PASSWORD) . ')') . "<br>";
    
    if (SMTP_PASSWORD === 'YOUR_APP_PASSWORD_HERE' || SMTP_USERNAME === 'joshuaagawin28@gmail.com') {
        echo "<br>⚠️ <strong style='color: red;'>WARNING: You need to update email_config.php with your actual Gmail App Password!</strong><br>";
        echo "<br><strong>Follow these steps:</strong><br>";
        echo "1. Enable 2-Step Verification: <a href='https://myaccount.google.com/security' target='_blank'>https://myaccount.google.com/security</a><br>";
        echo "2. Generate App Password: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a><br>";
        echo "3. Copy the 16-character password and update email_config.php<br>";
        die();
    }
} else {
    echo "❌ Email config file NOT found<br>";
    die();
}

// Test email sending
echo "<h2>3. Testing Email Send</h2>";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = 2; // 0 = off, 1 = client messages, 2 = client and server messages
    $mail->Debugoutput = 'html';
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    
    // Disable SSL verification for testing
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME); // Send to yourself for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'GABAY Email Test - ' . date('Y-m-d H:i:s');
    $mail->Body    = '<h1>Test Email</h1><p>If you receive this email, PHPMailer is working correctly!</p>';
    $mail->AltBody = 'Test Email - If you receive this email, PHPMailer is working correctly!';
    
    echo "<h3>Sending test email to: " . SMTP_USERNAME . "</h3>";
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #333;'>";
    
    $mail->send();
    
    echo "</div>";
    echo "<h3 style='color: green;'>✅ SUCCESS! Email sent successfully!</h3>";
    echo "<p>Check your inbox (and spam folder) at: <strong>" . SMTP_USERNAME . "</strong></p>";
    
} catch (Exception $e) {
    echo "</div>";
    echo "<h3 style='color: red;'>❌ FAILED! Email could not be sent.</h3>";
    echo "<p><strong>Error:</strong> {$mail->ErrorInfo}</p>";
    
    echo "<h3>Common Issues:</h3>";
    echo "<ul>";
    echo "<li>❌ Using regular Gmail password instead of App Password</li>";
    echo "<li>❌ 2-Step Verification not enabled in Gmail</li>";
    echo "<li>❌ App Password not generated or incorrect</li>";
    echo "<li>❌ Gmail blocking less secure apps (check Gmail security settings)</li>";
    echo "<li>❌ Firewall blocking port 587 or 465</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If test passed, try the forgot password page: <a href='forgot_password.php'>forgot_password.php</a></li>";
echo "<li>Make sure admin has an email set in System Settings</li>";
echo "<li>Make sure password_resets table exists in database</li>";
echo "</ol>";
?>
