<?php
include 'connect_db .php';

$office_id = $_POST['office_id'] ?? null;
$name = $_POST['office_name'];
$details = $_POST['details'];
$contact = $_POST['contact'];
$location = $_POST['location'];

if ($office_id) {
  // Update
  $stmt = $connect->prepare("UPDATE offices SET office_name=?, details=?, contact=?, location=? WHERE id=?");
  $stmt->execute([$name, $details, $contact, $location, $office_id]);
} else {
  // Insert new
  $stmt = $connect->prepare("INSERT INTO offices (office_name, details, contact, location) VALUES (?, ?, ?, ?)");
  $stmt->execute([$name, $details, $contact, $location]);
}

header("Location: officeManagement.php");
exit;
