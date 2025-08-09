<?php
header('Content-Type: application/json');
include 'connect_db.php'; // Assumes connect_db.php is in the same directory

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['office_id']) && isset($_POST['status'])) {
        $officeId = filter_var($_POST['office_id'], FILTER_VALIDATE_INT);
        $status = trim($_POST['status']); // Expected 'active' or 'inactive'

        if ($officeId === false || $officeId <= 0) {
            $response['message'] = 'Invalid Office ID.';
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $response['message'] = 'Invalid status value. Must be "active" or "inactive".';
        } else {
            try {
                if (!isset($connect) || !$connect) {
                    throw new Exception("Database connection failed. Check connect_db.php.");
                }

                $stmt = $connect->prepare("UPDATE offices SET status = :status WHERE id = :office_id");
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':office_id', $officeId, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Office status updated successfully.';
                    } else {
                        // Check if the office exists to differentiate "not found" from "status already set"
                        $checkStmt = $connect->prepare("SELECT COUNT(*) FROM offices WHERE id = :office_id");
                        $checkStmt->bindParam(':office_id', $officeId, PDO::PARAM_INT);
                        $checkStmt->execute();
                        if ($checkStmt->fetchColumn() > 0) {
                            $response['success'] = true; // No change needed, but not an error
                            $response['message'] = 'Office status was already set to this value.';
                        } else {
                            $response['message'] = 'Office ID not found.';
                        }
                    }
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $response['message'] = 'Database error during update: ' . ($errorInfo[2] ?? 'Unknown error');
                }
            } catch (PDOException $e) {
                error_log("PDOException in update_office_status.php: " . $e->getMessage());
                $response['message'] = 'Database error: ' . $e->getMessage();
            } catch (Exception $e) {
                error_log("Exception in update_office_status.php: " . $e->getMessage());
                $response['message'] = 'Server error: ' . $e->getMessage();
            }
        }
    } else {
        $response['message'] = 'Missing office_id or status parameters.';
    }
}

echo json_encode($response);
?>