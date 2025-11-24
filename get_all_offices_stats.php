<?php
// Require authentication
require_once 'auth_guard.php';

header('Content-Type: application/json');

include 'connect_db.php';

try {
    // Get month and year filter from query parameters (matching home.php filter logic)
    $selectedMonth = $_GET['month'] ?? 'all';
    $selectedYear = $_GET['year'] ?? 'all';
    
    // Build WHERE clause based on month/year filters (same as home.php)
    $whereClause = "";
    $params = [];
    
    if ($selectedMonth !== 'all') {
        $whereClause .= " AND MONTH(l.check_in_time) = :month";
        $params[':month'] = $selectedMonth;
    }
    if ($selectedYear !== 'all') {
        $whereClause .= " AND YEAR(l.check_in_time) = :year";
        $params[':year'] = $selectedYear;
    }
    
    // Fetch all offices with their scan counts
    $stmt = $connect->prepare("
        SELECT 
            o.id,
            o.name,
            o.status,
            o.location,
            COALESCE(COUNT(l.id), 0) as scan_count,
            CASE 
                WHEN o.location LIKE '%-1' THEN '1'
                WHEN o.location LIKE '%-2' THEN '2'
                WHEN o.location LIKE '%-3' THEN '3'
                ELSE NULL
            END as floor
        FROM offices o
        LEFT JOIN qr_scan_logs l ON o.id = l.office_id
        WHERE 1=1 $whereClause
        GROUP BY o.id, o.name, o.status, o.location
        ORDER BY scan_count DESC, o.name ASC
    ");
    
    $stmt->execute($params);
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'offices' => $offices,
        'month' => $selectedMonth,
        'year' => $selectedYear,
        'total_count' => count($offices)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_all_offices_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
