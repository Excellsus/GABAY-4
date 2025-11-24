<?php
// Require authentication - this will automatically redirect to login if not authenticated
require_once 'auth_guard.php';

// Bulk Panorama QR Code Generator
include 'connect_db.php';

// Handle URL update request for IP addresses
if ($_POST['action'] ?? '' === 'update_urls_to_ip') {
    try {
        // Get current base URL with IP detection
        function getCurrentBaseUrl() {
            $baseUrl = '';
            if (!empty($_SERVER['HTTP_HOST'])) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/mobileScreen/';
                $baseUrl = preg_replace('#([^:])/+#', '$1/', $baseUrl);
            } else {
                $baseUrl = "https://localhost/gabay/mobileScreen/";
            }
            return $baseUrl;
        }
        
        $newBaseUrl = getCurrentBaseUrl();
        
        // Update all panorama QR URLs with localhost to use IP
        $stmt = $connect->prepare("SELECT id, path_id, point_index, floor_number FROM panorama_qrcodes WHERE mobile_url LIKE '%localhost%'");
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($records as $record) {
            $newUrl = $newBaseUrl . "explore.php?scanned_panorama=path_id:" . $record['path_id'] . "_point:" . $record['point_index'] . "_floor:" . $record['floor_number'];
            
            $updateStmt = $connect->prepare("UPDATE panorama_qrcodes SET mobile_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$newUrl, $record['id']]);
            $updated++;
        }
        
        $message = "Successfully updated $updated panorama QR URLs to use IP addresses instead of localhost!";
        
    } catch (Exception $e) {
        $message = "Error updating URLs: " . $e->getMessage();
    }
}

// Handle bulk generation request
if ($_POST['action'] ?? '' === 'generate_all') {
    try {
        // Get all panoramas
        $stmt = $connect->prepare("SELECT DISTINCT path_id, point_index, floor_number FROM panorama_image ORDER BY floor_number, path_id, point_index");
        $stmt->execute();
        $panoramas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $generated = 0;
        $errors = [];

        foreach ($panoramas as $pano) {
            // Make API call to generate QR
            $postData = http_build_query([
                'action' => 'generate',
                'path_id' => $pano['path_id'],
                'point_index' => $pano['point_index'],
                'floor_number' => $pano['floor_number']
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $postData
                ]
            ]);

            // Use current server for API call
            $apiUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/FinalDev/panorama_qr_api.php';
            $result = file_get_contents($apiUrl, false, $context);
            $data = json_decode($result, true);

            if ($data && $data['success']) {
                $generated++;
            } else {
                $errors[] = "Floor {$pano['floor_number']}, Path {$pano['path_id']}, Point {$pano['point_index']}: " . ($data['error'] ?? 'Unknown error');
            }
        }

        $message = "Generated $generated QR codes successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', $errors);
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get current QR codes
try {
    $stmt = $connect->prepare("
        SELECT 
            pqr.*,
            pi.image_filename,
            pi.title,
            pi.description
        FROM panorama_qrcodes pqr
        LEFT JOIN panorama_image pi ON (
            pqr.path_id = pi.path_id AND 
            pqr.point_index = pi.point_index AND 
            pqr.floor_number = pi.floor_number
        )
        ORDER BY pqr.floor_number, pqr.path_id, pqr.point_index
    ");
    $stmt->execute();
    $qrCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $qrCodes = [];
    $error = "Database error: " . $e->getMessage();
}

// Get panoramas without QR codes
try {
    $stmt = $connect->prepare("
        SELECT DISTINCT 
            pi.path_id, 
            pi.point_index, 
            pi.floor_number,
            pi.image_filename,
            pi.title
        FROM panorama_image pi
        LEFT JOIN panorama_qrcodes pqr ON (
            pi.path_id = pqr.path_id AND 
            pi.point_index = pqr.point_index AND 
            pi.floor_number = pqr.floor_number
        )
        WHERE pqr.id IS NULL
        ORDER BY pi.floor_number, pi.path_id, pi.point_index
    ");
    $stmt->execute();
    $missingQRs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $missingQRs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panorama QR Code Manager - GABAY</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .section {
            background: rgba(255, 255, 255, 0.95);
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .section-header {
            background: #2563eb;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }

        .section-body {
            padding: 30px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .stat-card p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .bulk-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }

        .table tr:hover {
            background: #f9fafb;
        }

        .qr-preview {
            width: 40px;
            height: 40px;
            border-radius: 4px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .message {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .bulk-actions {
                flex-direction: column;
            }
            
            .section-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Panorama QR Code Manager</h1>
            <p>Generate and manage QR codes for 360° panorama views</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="section">
            <div class="section-header">
                <h2>📊 Overview</h2>
            </div>
            <div class="section-body">
                <div class="stats">
                    <div class="stat-card">
                        <h3><?php echo count($qrCodes); ?></h3>
                        <p>QR Codes Generated</p>
                    </div>
                    <div class="stat-card <?php echo count($missingQRs) > 0 ? 'warning' : ''; ?>">
                        <h3><?php echo count($missingQRs); ?></h3>
                        <p>Panoramas Without QR</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count($qrCodes) + count($missingQRs); ?></h3>
                        <p>Total Panoramas</p>
                    </div>
                </div>

                <div class="bulk-actions">
                    <?php if (count($missingQRs) > 0): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="generate_all">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Generate QR codes for all panoramas without QR codes?');">
                            🔗 Generate All Missing QR Codes
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update_urls_to_ip">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Update all existing panorama QR URLs to use IP addresses instead of localhost?');" title="Updates database URLs to use network IP instead of localhost">
                            🌐 Update URLs to IP Address
                        </button>
                    </form>
                    
                    <a href="floorPlan.php" class="btn btn-secondary">
                        🗺️ Back to Floor Plan
                    </a>
                </div>
            </div>
        </div>

        <!-- Missing QR Codes -->
        <?php if (count($missingQRs) > 0): ?>
        <div class="section">
            <div class="section-header">
                <h2>⚠️ Panoramas Missing QR Codes</h2>
                <span class="badge badge-warning"><?php echo count($missingQRs); ?> items</span>
            </div>
            <div class="section-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Floor</th>
                            <th>Path ID</th>
                            <th>Point Index</th>
                            <th>Title</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($missingQRs as $pano): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pano['floor_number']); ?></td>
                            <td><?php echo htmlspecialchars($pano['path_id']); ?></td>
                            <td><?php echo htmlspecialchars($pano['point_index']); ?></td>
                            <td><?php echo htmlspecialchars($pano['title'] ?? 'Untitled'); ?></td>
                            <td>
                                <?php if ($pano['image_filename']): ?>
                                    <img src="Pano/<?php echo htmlspecialchars($pano['image_filename']); ?>" class="qr-preview" alt="Panorama">
                                <?php else: ?>
                                    <span class="text-gray-400">No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-success" onclick="generateSingleQR('<?php echo $pano['path_id']; ?>', <?php echo $pano['point_index']; ?>, <?php echo $pano['floor_number']; ?>)">
                                    🔗 Generate QR
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Existing QR Codes -->
        <div class="section">
            <div class="section-header">
                <h2>✅ Generated QR Codes</h2>
                <span class="badge badge-success"><?php echo count($qrCodes); ?> items</span>
            </div>
            <div class="section-body">
                <?php if (count($qrCodes) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>QR Code</th>
                            <th>Floor</th>
                            <th>Path ID</th>
                            <th>Point Index</th>
                            <th>Title</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($qrCodes as $qr): ?>
                        <tr>
                            <td>
                                <img src="qrcodes/<?php echo htmlspecialchars($qr['qr_filename']); ?>" class="qr-preview" alt="QR Code">
                            </td>
                            <td><?php echo htmlspecialchars($qr['floor_number']); ?></td>
                            <td><?php echo htmlspecialchars($qr['path_id']); ?></td>
                            <td><?php echo htmlspecialchars($qr['point_index']); ?></td>
                            <td><?php echo htmlspecialchars($qr['title'] ?? 'Untitled'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($qr['created_at'])); ?></td>
                            <td>
                                <a href="panorama_qr_api.php?action=download&path_id=<?php echo urlencode($qr['path_id']); ?>&point_index=<?php echo $qr['point_index']; ?>&floor_number=<?php echo $qr['floor_number']; ?>" 
                                   class="btn btn-success" download>
                                    📥 Download
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📱</div>
                    <h3>No QR Codes Generated</h3>
                    <p>Click "Generate All Missing QR Codes" to create QR codes for your panoramas.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function generateSingleQR(pathId, pointIndex, floorNumber) {
            const btn = event.target;
            btn.textContent = 'Generating...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'generate');
            formData.append('path_id', pathId);
            formData.append('point_index', pointIndex);
            formData.append('floor_number', floorNumber);

            fetch('panorama_qr_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('QR code generated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            })
            .finally(() => {
                btn.textContent = '🔗 Generate QR';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>