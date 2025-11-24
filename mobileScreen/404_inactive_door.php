<?php
// 404 Error Page for Inactive Door QR Codes
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Access Denied</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #e53935 0%, #b71c1c 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 50px 30px;
            animation: fadeIn 0.5s ease-out;
            border: 3px solid #f44336;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .error-icon {
            font-size: 140px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
            filter: drop-shadow(0 10px 20px rgba(244, 67, 54, 0.3));
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 1;
            }
            50% { 
                transform: scale(1.05);
                opacity: 0.9;
            }
        }
        
        .error-code {
            font-size: 80px;
            font-weight: 900;
            color: #f44336;
            margin-bottom: 15px;
            letter-spacing: -3px;
            text-shadow: 2px 2px 10px rgba(244, 67, 54, 0.2);
        }
        
        .error-title {
            font-size: 32px;
            font-weight: bold;
            color: #d32f2f;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .error-message {
            font-size: 18px;
            color: #666;
            line-height: 1.8;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        
        .error-details {
            background: #ffebee;
            border: 2px solid #ffcdd2;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .error-details strong {
            color: #d32f2f;
            display: block;
            margin-bottom: 15px;
            font-size: 18px;
            text-align: center;
        }
        
        .error-details ul {
            list-style: none;
            padding-left: 0;
        }
        
        .error-details li {
            padding: 10px 0;
            color: #555;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .error-details li:before {
            content: "🚫 ";
            font-size: 18px;
            margin-right: 10px;
        }
        
        .warning-box {
            background: #fff3e0;
            border-left: 5px solid #ff9800;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            text-align: left;
        }
        
        .warning-box strong {
            color: #f57c00;
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .warning-box p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
        
        .footer-note {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ffcdd2;
            font-size: 13px;
            color: #999;
            font-style: italic;
        }
        
        .error-code-label {
            display: inline-block;
            background: #ffebee;
            color: #d32f2f;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
            border: 1px solid #ffcdd2;
        }
        
        @media (max-width: 480px) {
            .error-container {
                padding: 40px 20px;
            }
            
            .error-code {
                font-size: 64px;
            }
            
            .error-title {
                font-size: 26px;
            }
            
            .error-icon {
                font-size: 100px;
            }
            
            .error-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚫</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Access Denied</h1>
        <p class="error-message">
            <strong>This door entrance is currently inactive and cannot be accessed.</strong>
        </p>
        
        <div class="error-details">
            <strong>⚠️ Inactive QR Code</strong>
            <ul>
                <li>This entrance has been temporarily disabled by the administrator</li>
                <li>The QR code you scanned is not currently in service</li>
                <li>Access through this door is restricted until further notice</li>
            </ul>
        </div>
        
        <div class="warning-box">
            <strong>⚠️ For Office Access:</strong>
            <p>
                Please try another entrance to this office, or scan the main office QR code. 
                If you need immediate assistance, contact the office directly using their 
                listed contact information.
            </p>
        </div>
        
        <p class="footer-note">
            <span class="error-code-label">ERROR: INACTIVE_DOOR_ENTRANCE</span><br><br>
            This QR code has been deactivated. If you believe this is an error, 
            please contact the system administrator.
        </p>
    </div>
</body>
</html>
