<?php
/**
 * TEMPORARY DIAGNOSTIC FOR FORGOT PASSWORD
 * Shows actual error messages
 * DELETE AFTER FIXING!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Forgot Password System</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .pass{color:green;} .fail{color:red;} pre{background:#f4f4f4;padding:10px;border-radius:5px;}</style>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    require_once 'connect_db.php';
    echo "<p class='pass'>✓ Connected</p>";
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    die();
}

// Test 2: Check if password_resets table exists
echo "<h2>2. Check password_resets Table</h2>";
try {
    $stmt = $connect->query("SHOW TABLES LIKE 'password_resets'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='pass'>✓ Table exists</p>";
        
        // Show table structure
        $stmt = $connect->query("DESCRIBE password_resets");
        echo "<pre>";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    } else {
        echo "<p class='fail'>✗ Table does NOT exist</p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check admin table
echo "<h2>3. Check Admin Table</h2>";
try {
    $stmt = $connect->query("SELECT id, username, email FROM admin LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        echo "<p class='pass'>✓ Admin table accessible</p>";
        echo "<pre>";
        echo "Sample admin:\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . ($admin['email'] ?? 'NULL') . "\n";
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Try the actual INSERT query
echo "<h2>4. Test INSERT Query</h2>";
try {
    $testToken = 'test_' . bin2hex(random_bytes(16));
    $testAdminId = 1; // Assuming admin ID 1 exists
    
    echo "<p>Attempting INSERT with:</p>";
    echo "<pre>admin_id: {$testAdminId}\ntoken: {$testToken}</pre>";
    
    $stmt = $connect->prepare("
        INSERT INTO password_resets (admin_id, token, expiry, used, created_at) 
        VALUES (:admin_id, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), 0, NOW())
        ON DUPLICATE KEY UPDATE token = :token, expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR), used = 0, created_at = NOW()
    ");
    
    $result = $stmt->execute([
        ':admin_id' => $testAdminId,
        ':token' => $testToken
    ]);
    
    if ($result) {
        echo "<p class='pass'>✓ INSERT successful</p>";
        
        // Verify it was inserted
        $stmt = $connect->prepare("SELECT * FROM password_resets WHERE token = ?");
        $stmt->execute([$testToken]);
        $inserted = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($inserted);
        echo "</pre>";
        
        // Clean up test data
        $connect->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$testToken]);
        echo "<p>Test data cleaned up.</p>";
    } else {
        echo "<p class='fail'>✗ INSERT failed</p>";
    }
} catch (PDOException $e) {
    echo "<p class='fail'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>Error Code: " . $e->getCode() . "</pre>";
}

// Test 5: Email system
echo "<h2>5. Email System Check</h2>";
if (file_exists('send_email.php')) {
    echo "<p class='pass'>✓ send_email.php exists</p>";
    if (file_exists('email_config.php')) {
        echo "<p class='pass'>✓ email_config.php exists</p>";
    } else {
        echo "<p class='fail'>✗ email_config.php missing</p>";
    }
    if (file_exists('phpmailer/src/PHPMailer.php')) {
        echo "<p class='pass'>✓ PHPMailer installed</p>";
    } else {
        echo "<p class='fail'>✗ PHPMailer missing</p>";
    }
} else {
    echo "<p class='fail'>✗ send_email.php missing</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ DELETE THIS FILE AFTER CHECKING!</strong></p>";
?>
