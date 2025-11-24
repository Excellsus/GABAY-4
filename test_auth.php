<?php
/**
 * =====================================================
 * GABAY Authentication System - Automated Test Suite
 * =====================================================
 * 
 * Validates all authentication system components
 * Run from command line: php test_auth.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
echo "║  GABAY Authentication System - Test Suite     ║\n";
echo "╚════════════════════════════════════════════════╝\n";
echo "\n";

$tests_passed = 0;
$tests_failed = 0;
$tests_total = 0;

function runTest($name, $callable) {
    global $tests_passed, $tests_failed, $tests_total;
    $tests_total++;
    
    echo str_pad("Test {$tests_total}: {$name}", 60, '.');
    
    try {
        $result = $callable();
        if ($result === true) {
            echo " ✓ PASS\n";
            $tests_passed++;
            return true;
        } else {
            echo " ✗ FAIL\n";
            if (is_string($result)) {
                echo "         Reason: {$result}\n";
            }
            $tests_failed++;
            return false;
        }
    } catch (Exception $e) {
        echo " ✗ FAIL\n";
        echo "         Error: " . $e->getMessage() . "\n";
        $tests_failed++;
        return false;
    }
}

// Test 1: Check auth_guard.php exists
runTest("auth_guard.php exists", function() {
    return file_exists('auth_guard.php');
});

// Test 2: Check auth_guard.php is readable
runTest("auth_guard.php is readable", function() {
    return is_readable('auth_guard.php');
});

// Test 3: Check logout.php exists
runTest("logout.php exists", function() {
    return file_exists('logout.php');
});

// Test 4: Check auth_helper.js exists
runTest("auth_helper.js exists", function() {
    return file_exists('auth_helper.js');
});

// Test 5: Check logs directory exists or can be created
runTest("logs directory exists/writable", function() {
    $logsDir = __DIR__ . '/logs';
    if (!file_exists($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }
    return is_writable($logsDir) || is_writable(__DIR__);
});

// Test 6: Check database connection
runTest("Database connection works", function() {
    if (!file_exists('connect_db.php')) {
        return "connect_db.php not found";
    }
    
    require_once 'connect_db.php';
    
    if (!isset($connect) || !$connect) {
        return "Database connection not established";
    }
    
    return true;
});

// Test 7: Check admin table exists
runTest("Admin table exists", function() {
    global $connect;
    if (!isset($connect)) {
        return "Database not connected";
    }
    
    try {
        $stmt = $connect->query("SHOW TABLES LIKE 'admin'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
});

// Test 8: Check admin user exists
runTest("Admin user exists", function() {
    global $connect;
    if (!isset($connect)) {
        return "Database not connected";
    }
    
    try {
        $stmt = $connect->query("SELECT COUNT(*) as count FROM admin");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            return true;
        }
        return "No admin users in database";
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
});

// Test 9: Check admin password is hashed
runTest("Admin passwords are hashed", function() {
    global $connect;
    if (!isset($connect)) {
        return "Database not connected";
    }
    
    try {
        $stmt = $connect->query("SELECT password FROM admin LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && strlen($result['password']) === 60 && substr($result['password'], 0, 4) === '$2y$') {
            return true;
        }
        return "Password not hashed (consider running updatePassHash.php)";
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
});

// Test 10: Check home.php has auth guard
runTest("home.php has auth guard", function() {
    if (!file_exists('home.php')) {
        return "home.php not found";
    }
    $content = file_get_contents('home.php');
    return strpos($content, "require_once 'auth_guard.php'") !== false || 
           strpos($content, 'require_once "auth_guard.php"') !== false;
});

// Test 11: Check officeManagement.php has auth guard
runTest("officeManagement.php has auth guard", function() {
    if (!file_exists('officeManagement.php')) {
        return "officeManagement.php not found";
    }
    $content = file_get_contents('officeManagement.php');
    return strpos($content, "require_once 'auth_guard.php'") !== false || 
           strpos($content, 'require_once "auth_guard.php"') !== false;
});

// Test 12: Check floorPlan.php has auth guard
runTest("floorPlan.php has auth guard", function() {
    if (!file_exists('floorPlan.php')) {
        return "floorPlan.php not found";
    }
    $content = file_get_contents('floorPlan.php');
    return strpos($content, "require_once 'auth_guard.php'") !== false || 
           strpos($content, 'require_once "auth_guard.php"') !== false;
});

// Test 13: Check systemSettings.php has auth guard
runTest("systemSettings.php has auth guard", function() {
    if (!file_exists('systemSettings.php')) {
        return "systemSettings.php not found";
    }
    $content = file_get_contents('systemSettings.php');
    return strpos($content, "require_once 'auth_guard.php'") !== false || 
           strpos($content, 'require_once "auth_guard.php"') !== false;
});

// Test 14: Check login.php does NOT have auth guard (would cause loop)
runTest("login.php doesn't have auth guard", function() {
    if (!file_exists('login.php')) {
        return "login.php not found";
    }
    $content = file_get_contents('login.php');
    $hasAuthGuard = strpos($content, "require_once 'auth_guard.php'") !== false || 
                    strpos($content, 'require_once "auth_guard.php"') !== false;
    if ($hasAuthGuard) {
        return "login.php should NOT include auth_guard.php (causes redirect loop)";
    }
    return true;
});

// Test 15: Check generate_qrcodes.php has auth guard
runTest("generate_qrcodes.php has auth guard", function() {
    if (!file_exists('generate_qrcodes.php')) {
        return "generate_qrcodes.php not found";
    }
    $content = file_get_contents('generate_qrcodes.php');
    return strpos($content, "require_once 'auth_guard.php'") !== false || 
           strpos($content, 'require_once "auth_guard.php"') !== false;
});

// Test 16: Check systemSettings.php has CSRF validation
runTest("systemSettings.php has CSRF validation", function() {
    if (!file_exists('systemSettings.php')) {
        return "systemSettings.php not found";
    }
    $content = file_get_contents('systemSettings.php');
    return strpos($content, 'validateCSRFToken') !== false;
});

// Test 17: Check officeManagement.php has CSRF validation
runTest("officeManagement.php has CSRF validation", function() {
    if (!file_exists('officeManagement.php')) {
        return "officeManagement.php not found";
    }
    $content = file_get_contents('officeManagement.php');
    return strpos($content, 'validateCSRFToken') !== false;
});

// Test 18: Check home.php has CSRF token meta tag
runTest("home.php has CSRF token meta tag", function() {
    if (!file_exists('home.php')) {
        return "home.php not found";
    }
    $content = file_get_contents('home.php');
    return strpos($content, 'csrf-token') !== false && strpos($content, 'csrfToken()') !== false;
});

// Test 19: Check auth_helper.js included in pages
runTest("home.php includes auth_helper.js", function() {
    if (!file_exists('home.php')) {
        return "home.php not found";
    }
    $content = file_get_contents('home.php');
    return strpos($content, 'auth_helper.js') !== false;
});

// Test 20: Check PHP session configuration
runTest("PHP session configuration adequate", function() {
    $maxlifetime = ini_get('session.gc_maxlifetime');
    if ($maxlifetime < 1800) {
        return "session.gc_maxlifetime is {$maxlifetime} (should be >= 1800)";
    }
    return true;
});

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
echo "║                  TEST RESULTS                  ║\n";
echo "╚════════════════════════════════════════════════╝\n";
echo "\n";
echo "  Total Tests:  {$tests_total}\n";
echo "  Passed:       {$tests_passed} ✓\n";
echo "  Failed:       {$tests_failed} ✗\n";
echo "\n";

if ($tests_failed === 0) {
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║          ✓ ALL TESTS PASSED!                  ║\n";
    echo "║     Authentication system is ready to use     ║\n";
    echo "╚════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║          ✗ SOME TESTS FAILED                  ║\n";
    echo "║     Please review and fix issues above        ║\n";
    echo "╚════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Common fixes:\n";
    echo "1. Missing files: Ensure all auth files are uploaded\n";
    echo "2. Database: Check connect_db.php configuration\n";
    echo "3. Permissions: Ensure logs directory is writable\n";
    echo "4. Admin user: Create admin user in database\n";
    echo "\n";
    echo "For detailed help, see TESTING_GUIDE.md\n";
    echo "\n";
    exit(1);
}
