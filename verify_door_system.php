<?php
/**
 * Door Management System — Installation Verification Script
 * 
 * This script verifies that all components of the door management system
 * are properly installed and configured.
 */

require_once 'connect_db.php';

echo "=== DOOR MANAGEMENT SYSTEM — INSTALLATION CHECK ===\n\n";

$checks = [];
$errors = [];

// Check 1: Database table exists
echo "1. Checking database table 'door_status'... ";
try {
    $stmt = $connect->query("SHOW TABLES LIKE 'door_status'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ PASS\n";
        $checks[] = 'Database table exists';
        
        // Check table structure
        echo "   └─ Checking table structure... ";
        $stmt = $connect->query("DESCRIBE door_status");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'office_id', 'door_id', 'is_active', 'created_at', 'updated_at'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "✅ PASS\n";
            $checks[] = 'Table structure correct';
        } else {
            echo "❌ FAIL (Missing columns: " . implode(', ', $missingColumns) . ")\n";
            $errors[] = 'Table structure incomplete';
        }
    } else {
        echo "❌ FAIL\n";
        $errors[] = 'Database table does not exist — run create_door_status_table.php';
    }
} catch (PDOException $e) {
    echo "❌ FAIL (" . $e->getMessage() . ")\n";
    $errors[] = 'Database connection error';
}

// Check 2: API file exists
echo "\n2. Checking API file 'door_status_api.php'... ";
if (file_exists(__DIR__ . '/door_status_api.php')) {
    echo "✅ PASS\n";
    $checks[] = 'API file exists';
    
    // Check API includes auth guard
    echo "   └─ Checking auth guard inclusion... ";
    $apiContent = file_get_contents(__DIR__ . '/door_status_api.php');
    if (strpos($apiContent, 'auth_guard.php') !== false) {
        echo "✅ PASS\n";
        $checks[] = 'API has auth protection';
    } else {
        echo "⚠️  WARNING (No auth guard found)\n";
        $errors[] = 'API may not be protected';
    }
    
    // Check CSRF validation
    echo "   └─ Checking CSRF token validation... ";
    if (strpos($apiContent, 'validateCSRFToken') !== false) {
        echo "✅ PASS\n";
        $checks[] = 'CSRF validation present';
    } else {
        echo "⚠️  WARNING (No CSRF validation found)\n";
        $errors[] = 'API may lack CSRF protection';
    }
} else {
    echo "❌ FAIL\n";
    $errors[] = 'API file missing';
}

// Check 3: floorPlan.php has door management code
echo "\n3. Checking floorPlan.php modifications... ";
if (file_exists(__DIR__ . '/floorPlan.php')) {
    $floorPlanContent = file_get_contents(__DIR__ . '/floorPlan.php');
    
    // Check for door functions
    $hasDoorControls = strpos($floorPlanContent, 'loadDoorControls') !== false;
    $hasRenderFunction = strpos($floorPlanContent, 'renderDoorControls') !== false;
    $hasToggleHandler = strpos($floorPlanContent, 'handleDoorToggle') !== false;
    $hasLoadAllFunction = strpos($floorPlanContent, 'loadAndApplyAllDoorStatuses') !== false;
    
    if ($hasDoorControls && $hasRenderFunction && $hasToggleHandler && $hasLoadAllFunction) {
        echo "✅ PASS\n";
        $checks[] = 'All door management functions present';
    } else {
        echo "❌ FAIL\n";
        $errors[] = 'Missing door management functions in floorPlan.php';
        if (!$hasDoorControls) echo "   └─ Missing: loadDoorControls()\n";
        if (!$hasRenderFunction) echo "   └─ Missing: renderDoorControls()\n";
        if (!$hasToggleHandler) echo "   └─ Missing: handleDoorToggle()\n";
        if (!$hasLoadAllFunction) echo "   └─ Missing: loadAndApplyAllDoorStatuses()\n";
    }
    
    // Check for door status section in modal
    echo "   └─ Checking door status section in modal... ";
    if (strpos($floorPlanContent, 'door-status-section') !== false) {
        echo "✅ PASS\n";
        $checks[] = 'Door status section in modal';
    } else {
        echo "❌ FAIL\n";
        $errors[] = 'Door status section not found in office modal';
    }
    
    // Check for CSRF token in FormData
    echo "   └─ Checking CSRF token in door API calls... ";
    if (strpos($floorPlanContent, "formData.append('csrf_token'") !== false) {
        echo "✅ PASS\n";
        $checks[] = 'CSRF token sent with door updates';
    } else {
        echo "❌ FAIL\n";
        $errors[] = 'CSRF token not appended to door API calls';
    }
} else {
    echo "❌ FAIL\n";
    $errors[] = 'floorPlan.php not found';
}

// Check 4: CSS styles exist
echo "\n4. Checking CSS styles in floorPlan.css... ";
if (file_exists(__DIR__ . '/floorPlan.css')) {
    $cssContent = file_get_contents(__DIR__ . '/floorPlan.css');
    
    $hasMarkerStyle = strpos($cssContent, '.entry-point-marker') !== false;
    $hasInactiveStyle = strpos($cssContent, '.entry-point-marker.inactive') !== false;
    $hasControlStyle = strpos($cssContent, '.door-control-item') !== false;
    
    if ($hasMarkerStyle && $hasInactiveStyle && $hasControlStyle) {
        echo "✅ PASS\n";
        $checks[] = 'All door CSS styles present';
    } else {
        echo "❌ FAIL\n";
        $errors[] = 'Missing CSS styles';
        if (!$hasMarkerStyle) echo "   └─ Missing: .entry-point-marker\n";
        if (!$hasInactiveStyle) echo "   └─ Missing: .entry-point-marker.inactive\n";
        if (!$hasControlStyle) echo "   └─ Missing: .door-control-item\n";
    }
} else {
    echo "❌ FAIL\n";
    $errors[] = 'floorPlan.css not found';
}

// Check 5: Floor graph structure
echo "\n5. Checking floor graph files... ";
$floorGraphs = ['floor_graph.json', 'floor_graph_2.json', 'floor_graph_3.json'];
$missingGraphs = [];
$graphsWithEntryPoints = 0;

foreach ($floorGraphs as $graphFile) {
    if (file_exists(__DIR__ . '/' . $graphFile)) {
        $graphContent = json_decode(file_get_contents(__DIR__ . '/' . $graphFile), true);
        
        // Check if any rooms have doorPoints (the actual property used in floor graphs)
        if (isset($graphContent['rooms'])) {
            foreach ($graphContent['rooms'] as $room) {
                if (isset($room['doorPoints']) && !empty($room['doorPoints'])) {
                    $graphsWithEntryPoints++;
                    break;
                }
            }
        }
    } else {
        $missingGraphs[] = $graphFile;
    }
}

if (empty($missingGraphs)) {
    echo "✅ PASS\n";
    $checks[] = 'All floor graph files exist';
    
    echo "   └─ Checking for entry points... ";
    if ($graphsWithEntryPoints > 0) {
        echo "✅ PASS ($graphsWithEntryPoints floor(s) have entry points)\n";
        $checks[] = 'Entry points configured';
    } else {
        echo "⚠️  WARNING (No entry points found — doors won't be visible)\n";
        $errors[] = 'No entry points configured in floor graphs';
    }
} else {
    echo "❌ FAIL (Missing: " . implode(', ', $missingGraphs) . ")\n";
    $errors[] = 'Floor graph files missing';
}

// Summary
echo "\n========================================\n";
echo "INSTALLATION CHECK SUMMARY\n";
echo "========================================\n\n";

echo "✅ Passed checks: " . count($checks) . "\n";
foreach ($checks as $check) {
    echo "   • $check\n";
}

if (!empty($errors)) {
    echo "\n❌ Issues found: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
    echo "\n⚠️  System may not function correctly. Please review the issues above.\n";
} else {
    echo "\n🎉 All checks passed! Door management system is ready to use.\n";
    echo "\nNext steps:\n";
    echo "1. Log in to floorPlan.php\n";
    echo "2. Click on any office with entry points\n";
    echo "3. Find 'Door Status' section in the office details modal\n";
    echo "4. Toggle doors on/off to test functionality\n";
}

echo "\n========================================\n";
?>
