<?php
require 'connect_db.php';

$connect->exec("SET time_zone = '+08:00'");

$token = '77d69e7ee759fc6fcd7a16c2c2ee7d4d42f1928cb9dcbf40630079c6749ee532';

$stmt = $connect->prepare("
    SELECT pr.*, a.username, 
           pr.expiry > NOW() as is_valid, 
           NOW() as db_now 
    FROM password_resets pr 
    JOIN admin a ON pr.admin_id = a.id 
    WHERE pr.token = ?
");
$stmt->execute([$token]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "Token Found: YES\n";
    echo "Username: " . $result['username'] . "\n";
    echo "Expiry: " . $result['expiry'] . "\n";
    echo "Used: " . $result['used'] . "\n";
    echo "Is Valid (not expired): " . $result['is_valid'] . "\n";
    echo "DB Now: " . $result['db_now'] . "\n";
    echo "Created At: " . $result['created_at'] . "\n";
} else {
    echo "Token NOT FOUND in database!\n";
}
