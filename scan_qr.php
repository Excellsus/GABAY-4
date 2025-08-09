<?php
include 'connect_db.php';  // Your database connection file

// Check if QR code ID is provided via POST
if (isset($_POST['qr_code_id'])) {
    $qrCodeId = $_POST['qr_code_id'];

    // Get office_id from qrcode_info table using qr_code_id
    $stmt = $connect->prepare("SELECT office_id FROM qrcode_info WHERE id = ?");
    $stmt->execute([$qrCodeId]);
    $qrInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($qrInfo) {
        $officeId = $qrInfo['office_id'];

        // Insert scan log into qr_scan_logs
        $insertStmt = $connect->prepare("
            INSERT INTO qr_scan_logs (office_id, qr_code_id, check_in_time)
            VALUES (?, ?, NOW())
        ");
        $insertStmt->execute([$officeId, $qrCodeId]);

        echo "Scan successfully recorded for Office ID: $officeId";
    } else {
        echo "QR Code not found.";
    }
} else {
    echo "QR Code ID not provided.";
}
?>
