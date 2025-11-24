<?php
require_once 'auth_guard.php';
include 'connect_db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'generate_report':
            generateReport($connect);
            break;
        case 'export_csv':
            exportCSV($connect);
            break;
        case 'export_excel':
            exportExcel($connect);
            break;
        case 'export_pdf':
            exportPDF($connect);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function generateReport($connect) {
    $reportType = $_GET['report_type'] ?? 'all'; // 'office', 'panorama', 'all'
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $groupBy = $_GET['group_by'] ?? 'day'; // 'day', 'week', 'month'
    
    $data = [
        'summary' => getSummaryStats($connect, $reportType, $dateFrom, $dateTo),
        'timeline' => getTimelineData($connect, $reportType, $dateFrom, $dateTo, $groupBy),
        'top_offices' => getTopOffices($connect, $dateFrom, $dateTo),
        'top_panoramas' => getTopPanoramas($connect, $dateFrom, $dateTo),
        'hourly_distribution' => getHourlyDistribution($connect, $reportType, $dateFrom, $dateTo),
        'daily_comparison' => getDailyComparison($connect, $reportType, $dateFrom, $dateTo)
    ];
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function getSummaryStats($connect, $reportType, $dateFrom, $dateTo) {
    $stats = ['office' => 0, 'panorama' => 0, 'total' => 0];
    
    $dateFilter = buildDateFilter($dateFrom, $dateTo);
    
    if ($reportType === 'office' || $reportType === 'all') {
        $stmt = $connect->prepare("SELECT COUNT(*) FROM qr_scan_logs WHERE 1=1 {$dateFilter['office']}");
        $stmt->execute($dateFilter['params_office']);
        $stats['office'] = $stmt->fetchColumn();
    }
    
    if ($reportType === 'panorama' || $reportType === 'all') {
        $stmt = $connect->prepare("SELECT COUNT(*) FROM panorama_qr_scans WHERE 1=1 {$dateFilter['panorama']}");
        $stmt->execute($dateFilter['params_panorama']);
        $stats['panorama'] = $stmt->fetchColumn();
    }
    
    $stats['total'] = $stats['office'] + $stats['panorama'];
    
    return $stats;
}

function getTimelineData($connect, $reportType, $dateFrom, $dateTo, $groupBy) {
    $dateFormat = match($groupBy) {
        'day' => '%Y-%m-%d',
        'week' => '%Y-%u',
        'month' => '%Y-%m',
        default => '%Y-%m-%d'
    };
    
    $dateFilter = buildDateFilter($dateFrom, $dateTo);
    $timeline = [];
    
    if ($reportType === 'office' || $reportType === 'all') {
        $stmt = $connect->prepare("
            SELECT DATE_FORMAT(check_in_time, '{$dateFormat}') as period, 
                   COUNT(*) as count
            FROM qr_scan_logs 
            WHERE 1=1 {$dateFilter['office']}
            GROUP BY period
            ORDER BY period
        ");
        $stmt->execute($dateFilter['params_office']);
        $officeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($officeData as $row) {
            $timeline[$row['period']]['office'] = (int)$row['count'];
        }
    }
    
    if ($reportType === 'panorama' || $reportType === 'all') {
        $stmt = $connect->prepare("
            SELECT DATE_FORMAT(scan_timestamp, '{$dateFormat}') as period, 
                   COUNT(*) as count
            FROM panorama_qr_scans 
            WHERE 1=1 {$dateFilter['panorama']}
            GROUP BY period
            ORDER BY period
        ");
        $stmt->execute($dateFilter['params_panorama']);
        $panoramaData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($panoramaData as $row) {
            $timeline[$row['period']]['panorama'] = (int)$row['count'];
        }
    }
    
    // Format timeline data
    $formattedTimeline = [];
    foreach ($timeline as $period => $counts) {
        $formattedTimeline[] = [
            'period' => $period,
            'office' => $counts['office'] ?? 0,
            'panorama' => $counts['panorama'] ?? 0,
            'total' => ($counts['office'] ?? 0) + ($counts['panorama'] ?? 0)
        ];
    }
    
    return $formattedTimeline;
}

function getTopOffices($connect, $dateFrom, $dateTo, $limit = 10) {
    $dateFilter = buildDateFilter($dateFrom, $dateTo);
    
    $stmt = $connect->prepare("
        SELECT o.name, COALESCE(NULLIF(o.details, ''), o.location) as title, COUNT(l.id) as scan_count
        FROM qr_scan_logs l
        JOIN offices o ON o.id = l.office_id
        WHERE 1=1 {$dateFilter['office']}
        GROUP BY o.id
        ORDER BY scan_count DESC
        LIMIT {$limit}
    ");
    $stmt->execute($dateFilter['params_office']);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopPanoramas($connect, $dateFrom, $dateTo, $limit = 10) {
    $dateFilter = buildDateFilter($dateFrom, $dateTo);
    
    $stmt = $connect->prepare("
        SELECT 
            CONCAT('Floor ', pq.floor_number, ' - ', pq.path_id, ' Point ', pq.point_index) as location_name,
            pq.floor_number,
            pq.path_id,
            pq.point_index,
            COUNT(ps.id) as view_count
        FROM panorama_qr_scans ps
        JOIN panorama_qrcodes pq ON pq.id = ps.qr_id
        WHERE 1=1 {$dateFilter['panorama']}
        GROUP BY pq.id
        ORDER BY view_count DESC
        LIMIT {$limit}
    ");
    $stmt->execute($dateFilter['params_panorama']);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getHourlyDistribution($connect, $reportType, $dateFrom, $dateTo) {
    $dateFilter = buildDateFilter($dateFrom, $dateTo);
    $distribution = array_fill(0, 24, ['office' => 0, 'panorama' => 0]);
    
    if ($reportType === 'office' || $reportType === 'all') {
        $stmt = $connect->prepare("
            SELECT HOUR(check_in_time) as hour, COUNT(*) as count
            FROM qr_scan_logs
            WHERE 1=1 {$dateFilter['office']}
            GROUP BY hour
        ");
        $stmt->execute($dateFilter['params_office']);
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $distribution[(int)$row['hour']]['office'] = (int)$row['count'];
        }
    }
    
    if ($reportType === 'panorama' || $reportType === 'all') {
        $stmt = $connect->prepare("
            SELECT HOUR(scan_timestamp) as hour, COUNT(*) as count
            FROM panorama_qr_scans
            WHERE 1=1 {$dateFilter['panorama']}
            GROUP BY hour
        ");
        $stmt->execute($dateFilter['params_panorama']);
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $distribution[(int)$row['hour']]['panorama'] = (int)$row['count'];
        }
    }
    
    return array_map(function($hour, $data) {
        return [
            'hour' => sprintf('%02d:00', $hour),
            'office' => $data['office'],
            'panorama' => $data['panorama'],
            'total' => $data['office'] + $data['panorama']
        ];
    }, array_keys($distribution), $distribution);
}

function getDailyComparison($connect, $reportType, $dateFrom, $dateTo) {
    $dateFilter = buildDateFilter($dateFrom, $dateTo);
    $comparison = [];
    
    $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    foreach ($daysOfWeek as $index => $day) {
        $comparison[$day] = ['office' => 0, 'panorama' => 0];
    }
    
    if ($reportType === 'office' || $reportType === 'all') {
        $stmt = $connect->prepare("
            SELECT DAYOFWEEK(check_in_time) - 1 as day_index, COUNT(*) as count
            FROM qr_scan_logs
            WHERE 1=1 {$dateFilter['office']}
            GROUP BY day_index
        ");
        $stmt->execute($dateFilter['params_office']);
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $comparison[$daysOfWeek[(int)$row['day_index']]]['office'] = (int)$row['count'];
        }
    }
    
    if ($reportType === 'panorama' || $reportType === 'all') {
        $stmt = $connect->prepare("
            SELECT DAYOFWEEK(scan_timestamp) - 1 as day_index, COUNT(*) as count
            FROM panorama_qr_scans
            WHERE 1=1 {$dateFilter['panorama']}
            GROUP BY day_index
        ");
        $stmt->execute($dateFilter['params_panorama']);
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $comparison[$daysOfWeek[(int)$row['day_index']]]['panorama'] = (int)$row['count'];
        }
    }
    
    return array_map(function($day, $data) {
        return [
            'day' => $day,
            'office' => $data['office'],
            'panorama' => $data['panorama'],
            'total' => $data['office'] + $data['panorama']
        ];
    }, array_keys($comparison), $comparison);
}

function buildDateFilter($dateFrom, $dateTo) {
    $officeFilter = '';
    $panoramaFilter = '';
    $paramsOffice = [];
    $paramsPanorama = [];
    
    if ($dateFrom) {
        $officeFilter .= " AND DATE(check_in_time) >= :date_from";
        $panoramaFilter .= " AND DATE(scan_timestamp) >= :date_from";
        $paramsOffice[':date_from'] = $dateFrom;
        $paramsPanorama[':date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $officeFilter .= " AND DATE(check_in_time) <= :date_to";
        $panoramaFilter .= " AND DATE(scan_timestamp) <= :date_to";
        $paramsOffice[':date_to'] = $dateTo;
        $paramsPanorama[':date_to'] = $dateTo;
    }
    
    return [
        'office' => $officeFilter,
        'panorama' => $panoramaFilter,
        'params_office' => $paramsOffice,
        'params_panorama' => $paramsPanorama
    ];
}

function exportCSV($connect) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token');
    }
    
    $reportType = $_POST['report_type'] ?? 'all';
    $dateFrom = $_POST['date_from'] ?? null;
    $dateTo = $_POST['date_to'] ?? null;
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="qr_scan_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Summary section
    fputcsv($output, ['QR Scan Report', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Report Type', ucfirst($reportType)]);
    fputcsv($output, ['Date Range', ($dateFrom ?: 'All') . ' to ' . ($dateTo ?: 'Now')]);
    fputcsv($output, []);
    
    $summary = getSummaryStats($connect, $reportType, $dateFrom, $dateTo);
    fputcsv($output, ['Summary']);
    fputcsv($output, ['Office QR Scans', $summary['office']]);
    fputcsv($output, ['Panorama QR Scans', $summary['panorama']]);
    fputcsv($output, ['Total Scans', $summary['total']]);
    fputcsv($output, []);
    
    // Top offices
    if ($reportType === 'office' || $reportType === 'all') {
        fputcsv($output, ['Top Offices']);
        fputcsv($output, ['Office Name', 'Title', 'Scan Count']);
        
        foreach (getTopOffices($connect, $dateFrom, $dateTo, 20) as $office) {
            fputcsv($output, [$office['name'], $office['title'], $office['scan_count']]);
        }
        fputcsv($output, []);
    }
    
    // Top panoramas
    if ($reportType === 'panorama' || $reportType === 'all') {
        fputcsv($output, ['Top Panorama Locations']);
        fputcsv($output, ['Location', 'Floor', 'Path ID', 'Point Index', 'View Count']);
        
        foreach (getTopPanoramas($connect, $dateFrom, $dateTo, 20) as $panorama) {
            fputcsv($output, [
                $panorama['location_name'],
                $panorama['floor_number'],
                $panorama['path_id'],
                $panorama['point_index'],
                $panorama['view_count']
            ]);
        }
        fputcsv($output, []);
    }
    
    // Timeline data
    fputcsv($output, ['Timeline Data']);
    fputcsv($output, ['Period', 'Office Scans', 'Panorama Scans', 'Total Scans']);
    
    foreach (getTimelineData($connect, $reportType, $dateFrom, $dateTo, 'day') as $row) {
        fputcsv($output, [$row['period'], $row['office'], $row['panorama'], $row['total']]);
    }
    
    fclose($output);
    exit;
}

function exportExcel($connect) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token');
    }
    
    $reportType = $_POST['report_type'] ?? 'all';
    $dateFrom = $_POST['date_from'] ?? null;
    $dateTo = $_POST['date_to'] ?? null;
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="qr_scan_report_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start HTML table format (Excel can read this)
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
    echo '<x:Name>QR Scan Report</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions>';
    echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body>';
    
    // Title and metadata
    echo '<table><tr><td colspan="4"><h2>QR Scan Report</h2></td></tr>';
    echo '<tr><td><b>Generated:</b></td><td>' . date('Y-m-d H:i:s') . '</td></tr>';
    echo '<tr><td><b>Report Type:</b></td><td>' . ucfirst($reportType) . '</td></tr>';
    echo '<tr><td><b>Date Range:</b></td><td>' . ($dateFrom ?: 'All') . ' to ' . ($dateTo ?: 'Now') . '</td></tr>';
    echo '<tr><td>&nbsp;</td></tr></table>';
    
    // Summary statistics
    $summary = getSummaryStats($connect, $reportType, $dateFrom, $dateTo);
    echo '<table border="1"><tr><th colspan="2" bgcolor="#2e7d32" style="color:white;">Summary Statistics</th></tr>';
    echo '<tr><td><b>Office QR Scans</b></td><td>' . $summary['office'] . '</td></tr>';
    echo '<tr><td><b>Panorama QR Scans</b></td><td>' . $summary['panorama'] . '</td></tr>';
    echo '<tr><td><b>Total Scans</b></td><td>' . $summary['total'] . '</td></tr>';
    echo '</table><br>';
    
    // Top offices
    if ($reportType === 'office' || $reportType === 'all') {
        echo '<table border="1">';
        echo '<tr><th colspan="4" bgcolor="#2e7d32" style="color:white;">Most Visited Offices</th></tr>';
        echo '<tr bgcolor="#f0f0f0"><th>Rank</th><th>Office Name</th><th>Title</th><th>Scan Count</th></tr>';
        
        $offices = getTopOffices($connect, $dateFrom, $dateTo, 20);
        $rank = 1;
        foreach ($offices as $office) {
            echo '<tr>';
            echo '<td>' . $rank++ . '</td>';
            echo '<td>' . htmlspecialchars($office['name']) . '</td>';
            echo '<td>' . htmlspecialchars($office['title']) . '</td>';
            echo '<td>' . $office['scan_count'] . '</td>';
            echo '</tr>';
        }
        echo '</table><br>';
    }
    
    // Top panoramas
    if ($reportType === 'panorama' || $reportType === 'all') {
        echo '<table border="1">';
        echo '<tr><th colspan="4" bgcolor="#2e7d32" style="color:white;">Most Viewed Panorama Locations</th></tr>';
        echo '<tr bgcolor="#f0f0f0"><th>Rank</th><th>Location</th><th>Floor</th><th>View Count</th></tr>';
        
        $panoramas = getTopPanoramas($connect, $dateFrom, $dateTo, 20);
        $rank = 1;
        foreach ($panoramas as $panorama) {
            echo '<tr>';
            echo '<td>' . $rank++ . '</td>';
            echo '<td>' . htmlspecialchars($panorama['location_name']) . '</td>';
            echo '<td>Floor ' . $panorama['floor_number'] . '</td>';
            echo '<td>' . $panorama['view_count'] . '</td>';
            echo '</tr>';
        }
        echo '</table><br>';
    }
    
    // Timeline data
    echo '<table border="1">';
    echo '<tr><th colspan="4" bgcolor="#2e7d32" style="color:white;">Timeline Data</th></tr>';
    echo '<tr bgcolor="#f0f0f0"><th>Period</th><th>Office Scans</th><th>Panorama Scans</th><th>Total Scans</th></tr>';
    
    $timeline = getTimelineData($connect, $reportType, $dateFrom, $dateTo, 'day');
    foreach ($timeline as $row) {
        echo '<tr>';
        echo '<td>' . $row['period'] . '</td>';
        echo '<td>' . $row['office'] . '</td>';
        echo '<td>' . $row['panorama'] . '</td>';
        echo '<td>' . $row['total'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</body></html>';
    exit;
}

function exportPDF($connect) {
    // Debug logging
    error_log('PDF Export called');
    error_log('POST data: ' . print_r($_POST, true));
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        error_log('CSRF token validation failed');
        echo '<html><body><h1>Error: Invalid CSRF token</h1><p>Please close this window and try again.</p></body></html>';
        exit;
    }
    
    $reportType = $_POST['report_type'] ?? 'all';
    $dateFrom = $_POST['date_from'] ?? null;
    $dateTo = $_POST['date_to'] ?? null;
    
    error_log("Generating PDF for: type=$reportType, from=$dateFrom, to=$dateTo");
    
    // Generate HTML page optimized for PDF printing
    // The browser's print dialog will allow saving as PDF
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>QR Scan Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1, h2 { color: #2e7d32; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
            th { background: #2e7d32; color: white; }
            tr:nth-child(even) { background: #f8f8f8; }
            .summary-box { background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0; }
            .summary-item { display: inline-block; margin: 10px 20px; }
            .summary-value { font-size: 24px; font-weight: bold; color: #2e7d32; }
        </style>
    </head>
    <body onload="window.print(); window.close();">
        <h1>QR Scan Report</h1>
        <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Report Type:</strong> <?php echo ucfirst($reportType); ?></p>
        <p><strong>Date Range:</strong> <?php echo ($dateFrom ?: 'All') . ' to ' . ($dateTo ?: 'Now'); ?></p>
        
        <div class="summary-box">
            <h2>Summary Statistics</h2>
            <?php
            $summary = getSummaryStats($connect, $reportType, $dateFrom, $dateTo);
            ?>
            <div class="summary-item">
                <div>Office QR Scans</div>
                <div class="summary-value"><?php echo number_format($summary['office']); ?></div>
            </div>
            <div class="summary-item">
                <div>Panorama QR Scans</div>
                <div class="summary-value"><?php echo number_format($summary['panorama']); ?></div>
            </div>
            <div class="summary-item">
                <div>Total Scans</div>
                <div class="summary-value"><?php echo number_format($summary['total']); ?></div>
            </div>
        </div>
        
        <?php if ($reportType === 'office' || $reportType === 'all'): ?>
        <h2>Most Visited Offices</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Office Name</th>
                    <th>Title</th>
                    <th>Scan Count</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $offices = getTopOffices($connect, $dateFrom, $dateTo, 20);
                $rank = 1;
                foreach ($offices as $office):
                ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($office['name']); ?></td>
                    <td><?php echo htmlspecialchars($office['title']); ?></td>
                    <td><?php echo $office['scan_count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <?php if ($reportType === 'panorama' || $reportType === 'all'): ?>
        <h2>Most Viewed Panorama Locations</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Location</th>
                    <th>Floor</th>
                    <th>View Count</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $panoramas = getTopPanoramas($connect, $dateFrom, $dateTo, 20);
                $rank = 1;
                foreach ($panoramas as $panorama):
                ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($panorama['location_name']); ?></td>
                    <td>Floor <?php echo $panorama['floor_number']; ?></td>
                    <td><?php echo $panorama['view_count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}
