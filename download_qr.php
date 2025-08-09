<?php
require 'connect_db.php'; // Include database connection

// Download QR code based on office_id
if (isset($_POST['office_id'])) {
    $officeId = $_POST['office_id'];

    try {
        // Fetch the qr_code_image filename from the qrcode_info table
        $stmt = $connect->prepare("SELECT qr_code_image FROM qrcode_info WHERE office_id = ?");
        $stmt->execute([$officeId]);
        $qrInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($qrInfo && !empty($qrInfo['qr_code_image'])) {
            $qrImageFilename = $qrInfo['qr_code_image'];
            $qrImagePath = "qrcodes/" . $qrImageFilename;

            // Check if the file exists
            if (file_exists($qrImagePath)) {
                // Set headers for download
                header('Content-Type: image/png');
                header('Content-Disposition: attachment; filename="' . basename($qrImageFilename) . '"'); // Use the actual filename for download
                readfile($qrImagePath);
                exit;
            } else {
                echo "QR code image file not found on server for Office ID: $officeId (Expected: $qrImageFilename)";
            }
        } else {
            echo "QR code information not found in database for Office ID: $officeId.";
        }
    } catch (PDOException $e) {
        error_log("Error in download_qr.php: " . $e->getMessage());
        echo "Database error occurred while trying to download QR code.";
    }
} else {
    echo "No office ID provided.";
}
?>
