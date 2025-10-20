<?php
// notify_inactive_panorama_qrs.php
// Finds panorama_qrcodes with no recent scans and notifies admin via email.

require __DIR__ . '/../connect_db.php';

$adminEmails = ['admin@example.gov']; // TODO: set real admin emails
$thresholdDays = 30; // notify if not scanned within this many days
$fromEmail = 'noreply@example.gov';
$siteName = 'GABAY Panorama QR Monitor';
$logFile = __DIR__ . '/notify_inactive_panorama_qrs.log';

try {
    if (!isset($connect) || !$connect) throw new Exception('DB connection not available');

    // Query for panorama qrcodes where last_scanned_at is null or older than threshold
    $sql = "
    SELECT pq.id as qr_id, pi.path_id, pi.point_index, pi.floor_number, pi.title, pq.last_scanned_at, pq.qr_code_data
    FROM panorama_qrcodes pq
    LEFT JOIN panorama_image pi ON pi.path_id = pq.path_id AND pi.point_index = pq.point_index AND pi.floor_number = pq.floor_number
    WHERE pq.last_scanned_at IS NULL OR pq.last_scanned_at <= (NOW() - INTERVAL :days DAY)
    ORDER BY pq.last_scanned_at ASC
    ";

    $stmt = $connect->prepare($sql);
    $stmt->bindValue(':days', (int)$thresholdDays, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        file_put_contents($logFile, "[".date('c')."] No inactive panorama QR codes (threshold {$thresholdDays} days)\n", FILE_APPEND);
        exit(0);
    }

    $body = "The following panorama QR codes have not been scanned in the last {$thresholdDays} days:\n\n";
    foreach ($rows as $r) {
        $body .= sprintf("QR ID: %s; Path: %s; Point: %s; Floor: %s; Title: %s; Last scanned: %s; URL: %s\n",
            $r['qr_id'], $r['path_id'], $r['point_index'], $r['floor_number'], $r['title'] ?? '(untitled)', $r['last_scanned_at'] ?? 'never', $r['qr_code_data'] ?? '');
    }

    $subject = "Inactive Panorama QR Codes (not scanned in {$thresholdDays} days)";
    $headers = "From: {$fromEmail}\r\nReply-To: {$fromEmail}\r\n";

    foreach ($adminEmails as $to) {
        $ok = mail($to, $subject, $body, $headers);
        file_put_contents($logFile, "[".date('c')."] Sent to {$to}: " . ($ok ? 'OK' : 'FAILED') . "\n", FILE_APPEND);
    }

    echo "Done. Found " . count($rows) . " inactive panorama QR codes.\n";
} catch (Exception $e) {
    file_put_contents($logFile, "[".date('c')."] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

