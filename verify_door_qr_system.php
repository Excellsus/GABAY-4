<?php
/**
 * Door QR System Verification Script
 * 
 * Tests all components of the door QR code system
 */

require_once 'connect_db.php';

echo "🔍 DOOR QR CODE SYSTEM VERIFICATION\n";
echo str_repeat("=", 50) . "\n\n";

$allPassed = true;

// Test 1: Database Table
echo "TEST 1: Database Table Structure\n";
try {
    $stmt = $connect->query("DESCRIBE door_qrcodes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'office_id', 'door_index', 'room_id', 'qr_code_data', 'qr_code_image', 'is_active'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "✅ PASS: door_qrcodes table exists with all required columns\n";
    } else {
        echo "❌ FAIL: Missing columns: " . implode(', ', $missingColumns) . "\n";
        $allPassed = false;
    }
} catch (PDOException $e) {
    echo "❌ FAIL: Table doesn't exist or cannot be accessed\n";
    $allPassed = false;
}

// Test 2: Foreign Key Constraint
echo "\nTEST 2: Foreign Key Constraint\n";
try {
    $stmt = $connect->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'door_qrcodes' 
        AND REFERENCED_TABLE_NAME = 'offices'
    ");
    if ($stmt->rowCount() > 0) {
        echo "✅ PASS: Foreign key constraint to offices table exists\n";
    } else {
        echo "⚠️  WARNING: Foreign key constraint not found\n";
    }
} catch (PDOException $e) {
    echo "⚠️  WARNING: Could not check foreign key\n";
}

// Test 3: API File Exists
echo "\nTEST 3: API Endpoint\n";
if (file_exists(__DIR__ . '/door_qr_api.php')) {
    echo "✅ PASS: door_qr_api.php exists\n";
} else {
    echo "❌ FAIL: door_qr_api.php not found\n";
    $allPassed = false;
}

// Test 4: QR Storage Directory
echo "\nTEST 4: QR Code Storage\n";
$qrDir = __DIR__ . '/qrcodes/doors/';
if (!file_exists($qrDir)) {
    mkdir($qrDir, 0777, true);
    echo "✅ CREATED: qrcodes/doors/ directory\n";
} else {
    echo "✅ PASS: qrcodes/doors/ directory exists\n";
}

if (is_writable($qrDir)) {
    echo "✅ PASS: Directory is writable\n";
} else {
    echo "❌ FAIL: Directory is not writable\n";
    $allPassed = false;
}

// Test 5: PHPQRCode Library
echo "\nTEST 5: QR Code Library\n";
if (file_exists(__DIR__ . '/phpqrcode/qrlib.php')) {
    echo "✅ PASS: PHPQRCode library found\n";
} else {
    echo "❌ FAIL: PHPQRCode library not found\n";
    $allPassed = false;
}

// Test 6: Floor Graph Files
echo "\nTEST 6: Floor Graph Files\n";
$graphFiles = [
    'floor_graph.json',
    'floor_graph_2.json',
    'floor_graph_3.json'
];

foreach ($graphFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ PASS: $file exists\n";
    } else {
        echo "⚠️  WARNING: $file not found\n";
    }
}

// Test 7: Sample Office Check
echo "\nTEST 7: Sample Office with Location\n";
try {
    $stmt = $connect->query("SELECT id, name, location FROM offices WHERE location IS NOT NULL AND location != '' LIMIT 1");
    $office = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($office) {
        echo "✅ PASS: Found office with location\n";
        echo "   ID: {$office['id']}, Name: {$office['name']}, Location: {$office['location']}\n";
        
        // Check if this location has entry points in floor graph
        $roomId = $office['location'];
        $floorNumber = 1;
        if (preg_match('/room-\d+-(\d+)/', $roomId, $matches)) {
            $floorNumber = (int)$matches[1];
        }
        
        $graphFile = __DIR__ . '/floor_graph' . ($floorNumber > 1 ? "_$floorNumber" : '') . '.json';
        if (file_exists($graphFile)) {
            $graphData = json_decode(file_get_contents($graphFile), true);
            if (isset($graphData['rooms'][$roomId]['doorPoints'])) {
                $entryCount = count($graphData['rooms'][$roomId]['doorPoints']);
                echo "   ✅ Room has $entryCount door point(s) in floor graph\n";
            } else {
                echo "   ⚠️  WARNING: No door points found for this room\n";
            }
        }
    } else {
        echo "⚠️  WARNING: No offices with locations found\n";
    }
} catch (PDOException $e) {
    echo "❌ FAIL: Could not query offices\n";
    $allPassed = false;
}

// Test 8: Mobile Integration
echo "\nTEST 8: Mobile Integration\n";
$exploreFile = __DIR__ . '/mobileScreen/explore.php';
if (file_exists($exploreFile)) {
    $content = file_get_contents($exploreFile);
    if (strpos($content, 'door_qr') !== false && strpos($content, 'door_index') !== false) {
        echo "✅ PASS: explore.php contains door QR handling code\n";
    } else {
        echo "❌ FAIL: explore.php missing door QR code\n";
        $allPassed = false;
    }
} else {
    echo "❌ FAIL: explore.php not found\n";
    $allPassed = false;
}

// Test 9: Admin Integration
echo "\nTEST 9: Admin Integration\n";
$officeManageFile = __DIR__ . '/officeManagement.php';
if (file_exists($officeManageFile)) {
    $content = file_get_contents($officeManageFile);
    // Check for unified modal with tabs
    $hasDownloadModal = strpos($content, 'downloadModal') !== false;
    $hasDoorTab = strpos($content, 'data-tab="doors"') !== false;
    $hasDoorQrList = strpos($content, 'door-qr-list') !== false;
    $hasLoadDoorQrFunction = strpos($content, 'loadDoorQrData') !== false;
    
    if ($hasDownloadModal && $hasDoorTab && $hasDoorQrList && $hasLoadDoorQrFunction) {
        echo "✅ PASS: officeManagement.php contains unified door QR UI\n";
        echo "   ✅ Download modal with tabs\n";
        echo "   ✅ Door QR tab\n";
        echo "   ✅ Door QR list container\n";
        echo "   ✅ Door QR loading function\n";
    } else {
        echo "❌ FAIL: officeManagement.php missing door QR UI components\n";
        if (!$hasDownloadModal) echo "   ❌ Missing: downloadModal\n";
        if (!$hasDoorTab) echo "   ❌ Missing: door tab\n";
        if (!$hasDoorQrList) echo "   ❌ Missing: door-qr-list\n";
        if (!$hasLoadDoorQrFunction) echo "   ❌ Missing: loadDoorQrData function\n";
        $allPassed = false;
    }
} else {
    echo "❌ FAIL: officeManagement.php not found\n";
    $allPassed = false;
}

// Final Summary
echo "\n" . str_repeat("=", 50) . "\n";
if ($allPassed) {
    echo "✅ ALL TESTS PASSED - System Ready!\n";
    echo "\nNext Steps:\n";
    echo "1. Go to officeManagement.php\n";
    echo "2. Click the QR code icon next to any office\n";
    echo "3. Switch to the 'Door QR Codes' tab\n";
    echo "4. Click 'Generate All Door QR Codes' button\n";
    echo "5. Download and test scanning with your phone\n";
    echo "\nNote: The door QR management is now integrated into\n";
    echo "the main QR download modal with tabbed interface.\n";
} else {
    echo "❌ SOME TESTS FAILED - Review errors above\n";
}
echo str_repeat("=", 50) . "\n";
?>
