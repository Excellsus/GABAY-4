<?php
/**
 * GABAY Database Migration Script
 * Creates geofences table with enabled toggle functionality
 */

// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

include 'connect_db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GABAY Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #667eea; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #c62828; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <h2>🔄 GABAY Database Migration</h2>
    <p>Setting up geofences table with enabled toggle functionality...</p>

<?php
try {
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection not available. Please check connect_db.php");
    }

    // Step 1: Check if table exists
    echo "<div class='info'>📋 Step 1: Checking if geofences table exists...</div>";
    
    $tableCheck = $connect->query("SHOW TABLES LIKE 'geofences'");
    $tableExists = $tableCheck->rowCount() > 0;

    if (!$tableExists) {
        echo "<div class='info'>⚠️ Table doesn't exist. Creating geofences table...</div>";
        
        // Create the table
        $createTableSQL = "CREATE TABLE `geofences` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether geofencing is enabled (1) or disabled (0)',
            `center_lat` DECIMAL(10, 7) NOT NULL COMMENT 'Center latitude coordinate',
            `center_lng` DECIMAL(10, 7) NOT NULL COMMENT 'Center longitude coordinate',
            `radius1` INT(11) NOT NULL DEFAULT 50 COMMENT 'Zone 1 radius in meters (Main Building)',
            `radius2` INT(11) NOT NULL DEFAULT 100 COMMENT 'Zone 2 radius in meters (Complex)',
            `radius3` INT(11) NOT NULL DEFAULT 150 COMMENT 'Zone 3 radius in meters (Grounds)',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Geofencing configuration and control'";
        
        $connect->exec($createTableSQL);
        echo "<div class='success'>✅ Successfully created geofences table!</div>";
    } else {
        echo "<div class='success'>✓ Table 'geofences' already exists.</div>";
        
        // Step 2: Check if 'enabled' column exists
        echo "<div class='info'>📋 Step 2: Checking if 'enabled' column exists...</div>";
        
        $columnCheck = $connect->query("SHOW COLUMNS FROM geofences LIKE 'enabled'");
        $columnExists = $columnCheck->rowCount() > 0;
        
        if (!$columnExists) {
            echo "<div class='info'>⚠️ Adding 'enabled' column...</div>";
            $connect->exec("ALTER TABLE `geofences` 
                           ADD COLUMN `enabled` TINYINT(1) NOT NULL DEFAULT 1 
                           COMMENT 'Whether geofencing is enabled (1) or disabled (0)'");
            echo "<div class='success'>✅ Successfully added 'enabled' column!</div>";
        } else {
            echo "<div class='success'>✓ Column 'enabled' already exists.</div>";
        }
    }

    // Step 3: Ensure default record exists
    echo "<div class='info'>📋 Step 3: Ensuring default geofence record exists...</div>";
    
    $stmt = $connect->query("SELECT COUNT(*) as count FROM geofences WHERE name = 'default'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $connect->exec("INSERT INTO `geofences` (`name`, `enabled`, `center_lat`, `center_lng`, `radius1`, `radius2`, `radius3`)
                       VALUES ('default', 1, 10.6496000, 122.9619200, 50, 100, 150)");
        echo "<div class='success'>✅ Created default geofence record with these settings:</div>";
        echo "<ul>";
        echo "<li><strong>Latitude:</strong> 10.6496000</li>";
        echo "<li><strong>Longitude:</strong> 122.9619200</li>";
        echo "<li><strong>Zone 1 (Main Building):</strong> 50m radius</li>";
        echo "<li><strong>Zone 2 (Complex):</strong> 100m radius</li>";
        echo "<li><strong>Zone 3 (Grounds):</strong> 150m radius</li>";
        echo "<li><strong>Status:</strong> ENABLED</li>";
        echo "</ul>";
    } else {
        echo "<div class='success'>✓ Default geofence record already exists.</div>";
        
        // Display current settings
        $currentSettings = $connect->query("SELECT * FROM geofences WHERE name = 'default' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($currentSettings) {
            echo "<div class='info'><strong>Current Settings:</strong>";
            echo "<ul>";
            echo "<li><strong>Latitude:</strong> " . $currentSettings['center_lat'] . "</li>";
            echo "<li><strong>Longitude:</strong> " . $currentSettings['center_lng'] . "</li>";
            echo "<li><strong>Zone 1:</strong> " . $currentSettings['radius1'] . "m</li>";
            echo "<li><strong>Zone 2:</strong> " . $currentSettings['radius2'] . "m</li>";
            echo "<li><strong>Zone 3:</strong> " . $currentSettings['radius3'] . "m</li>";
            echo "<li><strong>Status:</strong> " . ($currentSettings['enabled'] ? 'ENABLED' : 'DISABLED') . "</li>";
            echo "</ul></div>";
        }
    }

    echo "<hr>";
    echo "<div class='success' style='font-size: 1.2em; padding: 20px;'>";
    echo "🎉 <strong>Migration completed successfully!</strong><br><br>";
    echo "Your geofencing system is now ready to use.<br>";
    echo "You can now control geofencing from the System Settings page.";
    echo "</div>";
    
    echo "<a href='systemSettings.php' class='btn'>Go to System Settings →</a>";

} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>";
    echo "<p><strong>Manual Migration Instructions:</strong></p>";
    echo "<p>Please copy and paste this SQL into phpMyAdmin:</p>";
    echo "<pre>";
    $sqlContent = file_get_contents('add_geofence_enabled_column.sql');
    echo htmlspecialchars($sqlContent);
    echo "</pre>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

</body>
</html>
