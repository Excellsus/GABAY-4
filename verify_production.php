<?php
/**
 * GABAY Production Readiness Verification Script
 * 
 * Run this script AFTER deployment to verify all configurations are correct.
 * Access: https://localhost/gabay/verify_production.php
 * 
 * ⚠️ DELETE THIS FILE AFTER VERIFICATION for security!
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GABAY Production Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pass {
            color: #27ae60;
            font-weight: bold;
        }
        .fail {
            color: #e74c3c;
            font-weight: bold;
        }
        .warn {
            color: #f39c12;
            font-weight: bold;
        }
        .check-item {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #ecf0f1;
        }
        .check-item.pass {
            border-left-color: #27ae60;
            background: #eafaf1;
        }
        .check-item.fail {
            border-left-color: #e74c3c;
            background: #fadbd8;
        }
        .check-item.warn {
            border-left-color: #f39c12;
            background: #fef5e7;
        }
        code {
            background: #ecf0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .delete-warning {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>🚀 GABAY Production Readiness Check</h1>
    
    <?php
    $allPassed = true;
    $warnings = [];
    
    // Check 1: Database Connection
    echo '<div class="section">';
    echo '<h2>1. Database Connection</h2>';
    
    try {
        include 'connect_db.php';
        
        if (isset($connect) && $connect instanceof PDO) {
            echo '<div class="check-item pass">✓ Database connection successful</div>';
            
            // Check for admin table
            $stmt = $connect->query("SHOW TABLES LIKE 'admin'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="check-item pass">✓ Admin table exists</div>';
            } else {
                echo '<div class="check-item fail">✗ Admin table NOT found - Database may not be imported</div>';
                $allPassed = false;
            }
            
            // Check for offices table
            $stmt = $connect->query("SHOW TABLES LIKE 'offices'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="check-item pass">✓ Offices table exists</div>';
                
                // Count offices
                $stmt = $connect->query("SELECT COUNT(*) as count FROM offices");
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                echo '<div class="check-item pass">✓ Found ' . $count['count'] . ' offices in database</div>';
            } else {
                echo '<div class="check-item fail">✗ Offices table NOT found</div>';
                $allPassed = false;
            }
            
        } else {
            echo '<div class="check-item fail">✗ Database connection object not created</div>';
            $allPassed = false;
        }
    } catch (Exception $e) {
        echo '<div class="check-item fail">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<div class="check-item warn">⚠ Check credentials in <code>connect_db.php</code></div>';
        $allPassed = false;
    }
    
    echo '</div>';
    
    // Check 2: Environment Detection
    echo '<div class="section">';
    echo '<h2>2. Environment Detection</h2>';
    
    $currentHost = $_SERVER['HTTP_HOST'] ?? 'unknown';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    
    echo '<div class="check-item pass">✓ Current Host: <code>' . htmlspecialchars($currentHost) . '</code></div>';
    echo '<div class="check-item ' . ($protocol === 'https' ? 'pass' : 'warn') . '">';
    echo ($protocol === 'https' ? '✓' : '⚠') . ' Protocol: <code>' . $protocol . '</code>';
    if ($protocol !== 'https') {
        echo ' - HTTPS recommended for production';
        $warnings[] = 'Not using HTTPS';
    }
    echo '</div>';
    
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    echo '<div class="check-item pass">✓ Script Directory: <code>' . htmlspecialchars($scriptDir) . '</code></div>';
    
    echo '</div>';
    
    // Check 3: File Permissions
    echo '<div class="section">';
    echo '<h2>3. Directory Permissions</h2>';
    
    $dirs = [
        'QR' => 'QR codes storage',
        'Pano' => 'Panorama images',
        'logs' => 'Error and security logs',
        'animated_hotspot_icons' => 'Hotspot icons'
    ];
    
    foreach ($dirs as $dir => $description) {
        $path = __DIR__ . '/' . $dir;
        if (file_exists($path)) {
            if (is_writable($path)) {
                echo '<div class="check-item pass">✓ <code>' . $dir . '/</code> is writable (' . $description . ')</div>';
            } else {
                echo '<div class="check-item fail">✗ <code>' . $dir . '/</code> is NOT writable - Set to 755</div>';
                $allPassed = false;
            }
        } else {
            if ($dir === 'logs') {
                echo '<div class="check-item warn">⚠ <code>' . $dir . '/</code> does not exist - Will be created automatically</div>';
                $warnings[] = 'logs/ directory missing';
            } else {
                echo '<div class="check-item fail">✗ <code>' . $dir . '/</code> does not exist</div>';
                $allPassed = false;
            }
        }
    }
    
    echo '</div>';
    
    // Check 4: Critical Files
    echo '<div class="section">';
    echo '<h2>4. Critical Files</h2>';
    
    $files = [
        'auth_guard.php' => 'Authentication system',
        'connect_db.php' => 'Database connection',
        'generate_qrcodes.php' => 'Office QR generator',
        'panorama_api.php' => 'Panorama API',
        'forgot_password.php' => 'Password reset',
        'mobileScreen/explore.php' => 'Mobile interface'
    ];
    
    foreach ($files as $file => $description) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo '<div class="check-item pass">✓ <code>' . $file . '</code> exists (' . $description . ')</div>';
        } else {
            echo '<div class="check-item fail">✗ <code>' . $file . '</code> NOT found</div>';
            $allPassed = false;
        }
    }
    
    echo '</div>';
    
    // Check 5: URL Generation
    echo '<div class="section">';
    echo '<h2>5. Dynamic URL Generation</h2>';
    
    // Test panorama URL function
    if (function_exists('getPanoramaBaseUrl')) {
        $panoramaUrl = getPanoramaBaseUrl();
        echo '<div class="check-item pass">✓ Panorama Base URL: <code>' . htmlspecialchars($panoramaUrl) . '</code></div>';
        
        if (strpos($panoramaUrl, 'localhost') !== false || strpos($panoramaUrl, '192.168') !== false) {
            echo '<div class="check-item warn">⚠ URL still contains localhost/IP - Should be production domain</div>';
            $warnings[] = 'Panorama URL not production-ready';
        }
    } else {
        echo '<div class="check-item warn">⚠ getPanoramaBaseUrl() function not available in this context</div>';
    }
    
    // Test door QR URL function
    if (file_exists(__DIR__ . '/door_qr_api.php')) {
        include_once __DIR__ . '/door_qr_api.php';
        if (function_exists('getDoorQRBaseUrl')) {
            $doorUrl = getDoorQRBaseUrl();
            echo '<div class="check-item pass">✓ Door QR Base URL: <code>' . htmlspecialchars($doorUrl) . '</code></div>';
            
            if (strpos($doorUrl, 'localhost') !== false || strpos($doorUrl, '192.168') !== false) {
                echo '<div class="check-item warn">⚠ URL still contains localhost/IP</div>';
                $warnings[] = 'Door QR URL not production-ready';
            }
        }
    }
    
    echo '</div>';
    
    // Check 6: PHP Configuration
    echo '<div class="section">';
    echo '<h2>6. PHP Configuration</h2>';
    
    $phpVersion = phpversion();
    echo '<div class="check-item ' . (version_compare($phpVersion, '7.0', '>=') ? 'pass' : 'warn') . '">';
    echo (version_compare($phpVersion, '7.0', '>=') ? '✓' : '⚠') . ' PHP Version: <code>' . $phpVersion . '</code>';
    if (version_compare($phpVersion, '7.0', '<')) {
        echo ' - PHP 7.0+ recommended';
        $warnings[] = 'Old PHP version';
    }
    echo '</div>';
    
    $extensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json'];
    foreach ($extensions as $ext) {
        $loaded = extension_loaded($ext);
        echo '<div class="check-item ' . ($loaded ? 'pass' : 'fail') . '">';
        echo ($loaded ? '✓' : '✗') . ' Extension <code>' . $ext . '</code>: ';
        echo ($loaded ? 'Loaded' : 'NOT loaded');
        if (!$loaded) {
            $allPassed = false;
        }
        echo '</div>';
    }
    
    $uploadMaxSize = ini_get('upload_max_filesize');
    echo '<div class="check-item pass">✓ Upload Max Size: <code>' . $uploadMaxSize . '</code></div>';
    
    $postMaxSize = ini_get('post_max_size');
    echo '<div class="check-item pass">✓ Post Max Size: <code>' . $postMaxSize . '</code></div>';
    
    echo '</div>';
    
    // Check 7: Security
    echo '<div class="section">';
    echo '<h2>7. Security Configuration</h2>';
    
    // Check .htaccess
    if (file_exists(__DIR__ . '/.htaccess')) {
        echo '<div class="check-item pass">✓ <code>.htaccess</code> file exists</div>';
    } else {
        echo '<div class="check-item warn">⚠ <code>.htaccess</code> file NOT found - Security rules missing</div>';
        $warnings[] = '.htaccess file missing';
    }
    
    // Check if error display is off
    $displayErrors = ini_get('display_errors');
    echo '<div class="check-item ' . ($displayErrors ? 'warn' : 'pass') . '">';
    echo ($displayErrors ? '⚠' : '✓') . ' Display Errors: <code>' . ($displayErrors ? 'On' : 'Off') . '</code>';
    if ($displayErrors) {
        echo ' - Should be OFF in production';
        $warnings[] = 'Error display enabled';
    }
    echo '</div>';
    
    // Check session settings
    $sessionHttpOnly = ini_get('session.cookie_httponly');
    echo '<div class="check-item ' . ($sessionHttpOnly ? 'pass' : 'warn') . '">';
    echo ($sessionHttpOnly ? '✓' : '⚠') . ' Session HttpOnly: <code>' . ($sessionHttpOnly ? 'On' : 'Off') . '</code>';
    if (!$sessionHttpOnly) {
        $warnings[] = 'Session HttpOnly not enabled';
    }
    echo '</div>';
    
    echo '</div>';
    
    // Final Summary
    echo '<div class="section">';
    echo '<h2>📊 Summary</h2>';
    
    if ($allPassed && count($warnings) === 0) {
        echo '<div class="check-item pass">';
        echo '<h3>✓ All checks passed! System is production-ready.</h3>';
        echo '<p>You can now:</p>';
        echo '<ul>';
        echo '<li>Test admin login</li>';
        echo '<li>Regenerate QR codes</li>';
        echo '<li>Test mobile interface</li>';
        echo '<li>Delete this verification script</li>';
        echo '</ul>';
        echo '</div>';
    } elseif ($allPassed && count($warnings) > 0) {
        echo '<div class="check-item warn">';
        echo '<h3>⚠ System functional with ' . count($warnings) . ' warning(s)</h3>';
        echo '<p>Warnings:</p><ul>';
        foreach ($warnings as $warning) {
            echo '<li>' . htmlspecialchars($warning) . '</li>';
        }
        echo '</ul>';
        echo '<p>System will work but consider addressing warnings for optimal security/performance.</p>';
        echo '</div>';
    } else {
        echo '<div class="check-item fail">';
        echo '<h3>✗ Critical issues found</h3>';
        echo '<p>Please fix the failed checks before going live.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    ?>
    
    <div class="delete-warning">
        ⚠️ IMPORTANT: Delete this file (<code>verify_production.php</code>) after verification for security reasons!
    </div>
    
    <div class="section">
        <h2>Next Steps</h2>
        <ol>
            <li><strong>If all checks passed:</strong>
                <ul>
                    <li>Delete this verification file</li>
                    <li>Login to admin panel: <code>/login.php</code></li>
                    <li>Change default password in System Settings</li>
                    <li>Regenerate QR codes: <code>/generate_qrcodes.php</code></li>
                    <li>Test mobile interface: <code>/mobileScreen/explore.php</code></li>
                </ul>
            </li>
            <li><strong>If issues found:</strong>
                <ul>
                    <li>Review <code>PRODUCTION_DEPLOYMENT_GUIDE.md</code></li>
                    <li>Check database credentials in <code>connect_db.php</code></li>
                    <li>Verify file permissions (755 for folders, 644 for files)</li>
                    <li>Check PHP error logs for details</li>
                </ul>
            </li>
        </ol>
    </div>
</body>
</html>
