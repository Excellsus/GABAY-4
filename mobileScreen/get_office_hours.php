<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Set JSON header
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../connect_db.php';

try {
    // Get office_id from request
    $office_id = isset($_GET['office_id']) ? intval($_GET['office_id']) : null;
    
    if (!$office_id) {
        throw new Exception('Office ID is required');
    }
    
    // Check database connection
    if (!isset($connect) || !$connect) {
        throw new Exception('Database connection failed');
    }
    
    // Fetch all office hours for this office
    $stmt = $connect->prepare("
        SELECT day_of_week, open_time, close_time 
        FROM office_hours 
        WHERE office_id = :office_id
        ORDER BY 
            CASE day_of_week
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
            END
    ");
    
    $stmt->bindParam(':office_id', $office_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response as an associative array with day names as keys
    $formattedHours = [];
    foreach ($hours as $hour) {
        $formattedHours[$hour['day_of_week']] = [
            'open_time' => $hour['open_time'],
            'close_time' => $hour['close_time']
        ];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'hours' => $formattedHours,
        'office_id' => $office_id
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
