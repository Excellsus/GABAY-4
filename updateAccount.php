<?php
include("connect_db.php");

$email = $_POST['email'];
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];

if ($password !== $confirmPassword) {
    echo "<script>
        alert('Passwords do not match.');
        window.location.href = 'systemSettings.php';
    </script>";
    exit;
}

try {
    $updateQuery = "UPDATE admin SET email = :email, password = :password WHERE username = 'admin_user'";
    $stmt = $connect->prepare($updateQuery);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);

    if ($stmt->execute()) {
        echo "<script>
            alert('Account updated successfully!');
            window.location.href = 'systemSettings.php';
        </script>";
    } else {
        echo "<script>
            alert('Failed to update account.');
            window.location.href = 'systemSettings.php';
        </script>";
    }
} catch (PDOException $e) {
    echo "<script>
        alert('Error: " . addslashes($e->getMessage()) . "');
        window.location.href = 'systemSettings.php';
    </script>";
}
?>
