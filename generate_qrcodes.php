<?php
require 'phpqrcode/qrlib.php'; // Path to the QR library
require 'connect_db.php'; // Uses $connect

// --- Configuration ---
// Determine base URL dynamically when possible so QR codes work across devices/networks.
// If running via webserver, derive protocol/host and build the path to the mobileScreen folder.
// If running from CLI or server vars are unavailable, fall back to a sensible default.
$baseUrl = '';
if (!empty($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // dirname($_SERVER['SCRIPT_NAME']) gives the directory of this script (e.g., /FinalDev)
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // Ensure we end up with a trailing slash and point to the mobileScreen folder
    $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $scriptDir . '/mobileScreen/';
    // Normalize double slashes (except after http(s):)
    $baseUrl = preg_replace('#([^:])/+#', '$1/', $baseUrl);
} else {
    // Fallback: common local dev path — adjust if your environment differs
    $baseUrl = "http://localhost/FinalDev/mobileScreen/";
}

// Function to create a safe filename from a string (like an office name)
function sanitize_filename($string) {
    // Remove any character that is not a letter, number, space, hyphen, or underscore.
    // \pL matches any kind of letter from any language. \pN matches any kind of number.
    $string = preg_replace('/[^\pL\pN\s\-_]/u', '', $string);
    // Replace multiple spaces, underscores, or hyphens with a single underscore.
    $string = preg_replace('/[\s_]+/', '_', $string);
    // Trim underscores from the beginning and end of the string.
    $string = trim($string, '_');
    // If the string is empty after sanitization, default to 'office'
    if (empty($string)) {
        return 'office';
    }
    return $string;
}
// Folder to store QR code images
$qrDir = 'qrcodes/';
if (!file_exists($qrDir)) {
    mkdir($qrDir, 0777, true);
}

// Fetch all offices
$stmt = $connect->query("SELECT * FROM offices");
$offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($offices as $office) {
    $officeId = $office['id'];
    $officeName = $office['name']; // Original office name

    // Construct the URL for the QR code. Add a `from_qr=1` flag so the server can
    // distinguish true QR scans from normal navigation (clicks or manual refreshes).
    $qrData = $baseUrl . "explore.php?office_id=" . $officeId . "&from_qr=1";

    // Sanitize the office name for the filename and append office ID for uniqueness
    $sanitizedOfficeName = sanitize_filename($officeName);
    $filename = $qrDir . $sanitizedOfficeName . "_" . $officeId . ".png";

    // Generate QR code image (this will overwrite existing images if any)
    QRcode::png($qrData, $filename, QR_ECLEVEL_L, 4);

    // Prepare QR info for the database
    $qrImage = basename($filename); // e.g., Governors_Office_1.png

    // Check if an entry for this office_id already exists in qrcode_info
    $check = $connect->prepare("SELECT id FROM qrcode_info WHERE office_id = ?");
    $check->execute([$officeId]);
    $existingQrInfo = $check->fetch(PDO::FETCH_ASSOC);

    if ($existingQrInfo) {
        // Update existing entry
        $updateStmt = $connect->prepare("UPDATE qrcode_info SET qr_code_data = ?, qr_code_image = ? WHERE office_id = ?");
        $updateStmt->execute([$qrData, $qrImage, $officeId]);
        echo "🔄 QR code updated for office ID $officeId. Filename: $qrImage, URL: $qrData<br>";
    } else {
        // Insert new entry
        $insertStmt = $connect->prepare("INSERT INTO qrcode_info (office_id, qr_code_data, qr_code_image) VALUES (?, ?, ?)");
        $insertStmt->execute([$officeId, $qrData, $qrImage]);
        echo "✅ QR code generated and info saved for office ID $officeId. Filename: $qrImage, URL: $qrData<br>";
    }
}
?>
