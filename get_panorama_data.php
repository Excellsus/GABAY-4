<?php
/**
 * API endpoint to get fresh panorama data for dashboard refresh with time filtering
 */

header('Content-Type: application/json');
include 'connect_db.php';

try {
    // Get time filter parameter
    $timeFilter = $_GET['filter'] ?? 'all';
    
    // Build WHERE clause for time filtering
    $timeWhereClause = "";
    $params = [];
    
    switch ($timeFilter) {
        case 'today':
            $timeWhereClause = " AND DATE(ps.scan_timestamp) = CURDATE()";
            break;
        case 'week':
            $timeWhereClause = " AND WEEK(ps.scan_timestamp) = WEEK(CURDATE()) AND YEAR(ps.scan_timestamp) = YEAR(CURDATE())";
            break;
        case 'month':
            $timeWhereClause = " AND MONTH(ps.scan_timestamp) = MONTH(CURDATE()) AND YEAR(ps.scan_timestamp) = YEAR(CURDATE())";
            break;
        case 'all':
        default:
            // No time filter
            break;
    }
    
    // Get panorama locations with coordinates and scan counts for map display
    $stmt = $connect->prepare("
        SELECT 
            pi.floor_number,
            pi.path_id,
            pi.point_index,
            pi.point_x,
            pi.point_y,
            pi.title,
            COALESCE(COUNT(ps.id), 0) as scan_count
        FROM panorama_image pi 
        LEFT JOIN panorama_qrcodes pq ON (
            pi.path_id = pq.path_id AND 
            pi.point_index = pq.point_index AND 
            pi.floor_number = pq.floor_number
        )
        LEFT JOIN panorama_qr_scans ps ON pq.id = ps.qr_id
        WHERE pi.is_active = 1 $timeWhereClause
        GROUP BY pi.id
        ORDER BY pi.floor_number, scan_count DESC
    ");
    
    $stmt->execute($params);
    $panoramaData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'panorama_data' => $panoramaData,
        'total_panoramas' => count($panoramaData),
        'filter_applied' => $timeFilter,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>