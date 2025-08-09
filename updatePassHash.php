<?php
// Include database connection
require_once 'connect_db.php';

// Admin details
$username = "admin"; // Change to the username you want to update
$new_password = "admin123"; // Change to the desired password

// Hash the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Update password
    $stmt = $connect->prepare("UPDATE users SET password = :password WHERE username = :username");
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':username', $username);
    
    if ($stmt->execute()) {
        echo "Password updated successfully!";
    } else {
        echo "Error updating password.";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}