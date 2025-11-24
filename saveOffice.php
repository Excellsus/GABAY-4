<?php
// Require authentication
require_once 'auth_guard.php';

include 'connect_db.php';

// Check if this is a swap rooms action
$action = $_POST['action'] ?? '';

if ($action === 'swap_rooms') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $office1_id = $_POST['office1_id'] ?? null;
    $office2_id = $_POST['office2_id'] ?? null;
    
    if (!$office1_id || !$office2_id) {
        echo json_encode(['success' => false, 'error' => 'Both office IDs are required']);
        exit;
    }
    
    if ($office1_id === $office2_id) {
        echo json_encode(['success' => false, 'error' => 'Cannot swap an office with itself']);
        exit;
    }
    
    try {
        // Start transaction
        $connect->beginTransaction();
        
        // Get current locations
        $stmt = $connect->prepare("SELECT id, location FROM offices WHERE id IN (?, ?)");
        $stmt->execute([$office1_id, $office2_id]);
        $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($offices) !== 2) {
            throw new Exception('One or both offices not found');
        }
        
        $office1_location = null;
        $office2_location = null;
        
        foreach ($offices as $office) {
            if ($office['id'] == $office1_id) {
                $office1_location = $office['location'];
            } elseif ($office['id'] == $office2_id) {
                $office2_location = $office['location'];
            }
        }
        
        // Swap locations
        $stmt = $connect->prepare("UPDATE offices SET location = ? WHERE id = ?");
        $stmt->execute([$office2_location, $office1_id]);
        $stmt->execute([$office1_location, $office2_id]);
        
        // Commit transaction
        $connect->commit();
        
        echo json_encode(['success' => true, 'message' => 'Rooms swapped successfully']);
    } catch (Exception $e) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Original save office functionality
$office_id = $_POST['office_id'] ?? null;
$name = $_POST['office_name'];
$details = $_POST['details'];
$contact = $_POST['contact'];
$location = $_POST['location'];

if ($office_id) {
  // Update
  $stmt = $connect->prepare("UPDATE offices SET name=?, details=?, contact=?, location=? WHERE id=?");
  $stmt->execute([$name, $details, $contact, $location, $office_id]);
} else {
  // Insert new
  $stmt = $connect->prepare("INSERT INTO offices (name, details, contact, location) VALUES (?, ?, ?, ?)");
  $stmt->execute([$name, $details, $contact, $location]);
}

header("Location: officeManagement.php");
exit;
