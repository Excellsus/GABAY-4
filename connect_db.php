<?php
$db_host = "localhost";
$db_name = "admin"; // Replace with your actual database name
$db_username = "root";
$db_password = "";

try {
    $connect = new PDO("mysql:host=$db_host;dbname=$db_name", $db_username, $db_password);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
