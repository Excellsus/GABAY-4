<?php
require 'connect_db.php';

// Check the table structure
echo "=== PASSWORD_RESETS TABLE STRUCTURE ===\n\n";
$stmt = $connect->query("DESCRIBE password_resets");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "Column: " . $col['Field'] . "\n";
    echo "  Type: " . $col['Type'] . "\n";
    echo "  Null: " . $col['Null'] . "\n";
    echo "  Key: " . $col['Key'] . "\n";
    echo "  Default: " . ($col['Default'] ?? 'NULL') . "\n";
    echo "  Extra: " . $col['Extra'] . "\n\n";
}

// Check all tokens
echo "\n=== ALL TOKENS IN DATABASE ===\n\n";
$connect->exec("SET time_zone = '+08:00'");
$stmt = $connect->query("
    SELECT pr.*, a.username, 
           pr.expiry > NOW() as is_valid, 
           NOW() as db_now
    FROM password_resets pr 
    JOIN admin a ON pr.admin_id = a.id 
    ORDER BY pr.created_at DESC 
    LIMIT 5
");
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tokens as $t) {
    echo "Token: " . substr($t['token'], 0, 20) . "...\n";
    echo "Username: " . $t['username'] . "\n";
    echo "Used: " . $t['used'] . "\n";
    echo "Is Valid: " . $t['is_valid'] . "\n";
    echo "Expiry: " . $t['expiry'] . "\n";
    echo "DB Now: " . $t['db_now'] . "\n";
    echo "Created: " . $t['created_at'] . "\n";
    echo "---\n";
}
