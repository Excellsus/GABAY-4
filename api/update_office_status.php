<?php
header('Content-Type: application/json');

// Adjust the path to your database connection file as necessary
// If 'api' folder is directly inside 'FinalDev', then '../connect_db.php' is correct.
require '../connect_db.php'; 

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['office_id']) && isset($input['status'])) {
        $officeId = filter_var($input['office_id'], FILTER_VALIDATE_INT);
        $status = $input['status']; // Should be 'active' or 'inactive'

        // Validate the status value to ensure it's one of the allowed enum values
        if ($officeId && ($status === 'active' || $status === 'inactive')) {
            try {
                if (!isset($connect) || !$connect) {
                    // This check is important if connect_db.php might not always establish $connect
                    throw new Exception("Database connection is not available.");
                }

                $stmt = $connect->prepare("UPDATE offices SET status = :status WHERE id = :office_id");
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':office_id', $officeId, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Office status updated successfully.';
                    } else {
                        // No rows affected - could mean office_id not found or status was already the same
                        $response['success'] = true; // Or false, depending on how strict you want to be
                        $response['message'] = 'Office not found or status was already set to this value.';
                    }
                } else {
                    $response['message'] = 'Database execution failed.';
                }
            } catch (PDOException $e) {
                error_log("PDOError updating office status: " . $e->getMessage());
                $response['message'] = 'Database error occurred. ' . $e->getMessage();
            } catch (Exception $e) {
                error_log("General error updating office status: " . $e->getMessage());
                $response['message'] = 'Server error occurred. ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Invalid office ID or status value provided.';
        }
    } else {
        $response['message'] = 'Missing office_id or status in request payload.';
    }
}

echo json_encode($response);
?>