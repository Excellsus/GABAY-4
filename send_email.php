<?php
/**
 * =====================================================
 * EMAIL SENDING HELPER FOR GABAY
 * =====================================================
 * 
 * Uses PHPMailer to send emails via SMTP.
 * This is more reliable than PHP mail() function, especially on shared hosting.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer exists before requiring
$phpmailerPath = __DIR__ . '/phpmailer/src/';
if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
    error_log('PHPMailer files not found at: ' . $phpmailerPath);
    // Define dummy function to prevent fatal errors
    if (!function_exists('sendEmail')) {
        function sendEmail($to, $subject, $body, $altBody = '') {
            return ['success' => false, 'message' => 'Email system not configured', 'error' => 'PHPMailer not installed'];
        }
    }
    if (!function_exists('sendPasswordResetEmail')) {
        function sendPasswordResetEmail($to, $username, $resetUrl) {
            return ['success' => false, 'message' => 'Email system not configured', 'error' => 'PHPMailer not installed'];
        }
    }
    return; // Stop execution here
}

require $phpmailerPath . 'Exception.php';
require $phpmailerPath . 'PHPMailer.php';
require $phpmailerPath . 'SMTP.php';

// Check if email config exists
if (!file_exists(__DIR__ . '/email_config.php')) {
    error_log('email_config.php not found');
    if (!function_exists('sendEmail')) {
        function sendEmail($to, $subject, $body, $altBody = '') {
            return ['success' => false, 'message' => 'Email configuration not found', 'error' => 'email_config.php missing'];
        }
    }
    if (!function_exists('sendPasswordResetEmail')) {
        function sendPasswordResetEmail($to, $username, $resetUrl) {
            return ['success' => false, 'message' => 'Email configuration not found', 'error' => 'email_config.php missing'];
        }
    }
    return;
}

require_once __DIR__ . '/email_config.php';

/**
 * Send an email using PHPMailer with SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @param string $altBody Plain text alternative body (optional)
 * @return array ['success' => bool, 'message' => string, 'error' => string]
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Disable SSL verification for development (remove in production if possible)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        
        $mail->send();
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return [
            'success' => false,
            'message' => 'Failed to send email',
            'error' => $mail->ErrorInfo
        ];
    }
}

/**
 * Send password reset email
 * 
 * @param string $to Recipient email address
 * @param string $username Admin username
 * @param string $resetUrl Password reset URL
 * @return array ['success' => bool, 'message' => string]
 */
function sendPasswordResetEmail($to, $username, $resetUrl) {
    $subject = "GABAY Admin - Password Reset Request";
    
    $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1a5f3c 0%, #2d8659 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; padding: 12px 30px; background: #1a5f3c; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Password Reset Request</h1>
                    <p>GABAY Navigation System</p>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$username}</strong>,</p>
                    
                    <p>We received a request to reset your password for the GABAY Admin Portal. Click the button below to create a new password:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$resetUrl}' class='button'>Reset Password</a>
                    </div>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='background: #fff; padding: 10px; border: 1px solid #ddd; word-break: break-all;'>{$resetUrl}</p>
                    
                    <div class='warning'>
                        ⚠️ <strong>Important:</strong> This link will expire in 1 hour for security reasons.
                    </div>
                    
                    <p>If you didn't request a password reset, please ignore this email. Your password will remain unchanged.</p>
                    
                    <p>Best regards,<br><strong>GABAY Admin System</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2025 GABAY Navigation System<br>Provincial Government of Negros Occidental</p>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    $altBody = "Hello {$username},\n\n"
             . "We received a request to reset your password for the GABAY Admin Portal.\n\n"
             . "Click this link to reset your password:\n{$resetUrl}\n\n"
             . "This link will expire in 1 hour for security reasons.\n\n"
             . "If you didn't request a password reset, please ignore this email.\n\n"
             . "Best regards,\nGABAY Admin System";
    
    return sendEmail($to, $subject, $body, $altBody);
}
?>
