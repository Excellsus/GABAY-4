<?php
/**
 * Create a sample animated GIF for testing
 * This creates a simple animated arrow using ImageMagick if available
 */

// Check if we can create a simple animated GIF for testing
function createSampleAnimatedGIF() {
    $sampleDir = 'animated_hotspot_icons/';
    $sampleFile = $sampleDir . 'sample_arrow.gif';
    
    // If the sample already exists, return its path
    if (file_exists($sampleFile)) {
        return $sampleFile;
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($sampleDir)) {
        mkdir($sampleDir, 0755, true);
    }
    
    // Try to create a simple animated GIF using GD (basic approach)
    // This creates a simple pulsing circle as a sample
    
    $frames = [];
    $width = 64;
    $height = 64;
    
    // Create 8 frames with different opacity for pulsing effect
    for ($i = 0; $i < 8; $i++) {
        $frame = imagecreatetruecolor($width, $height);
        
        // Make background transparent
        $transparent = imagecolorallocatealpha($frame, 0, 0, 0, 127);
        imagefill($frame, 0, 0, $transparent);
        imagesavealpha($frame, true);
        
        // Calculate opacity (pulsing from 50 to 255)
        $alpha = 50 + (sin($i / 4 * M_PI) * 100);
        $color = imagecolorallocatealpha($frame, 4, 170, 109, 127 - ($alpha / 2));
        
        // Draw a circle (representing a hotspot)
        imagefilledellipse($frame, $width/2, $height/2, $width-10, $height-10, $color);
        
        // Add an arrow symbol
        $arrowColor = imagecolorallocatealpha($frame, 255, 255, 255, 0);
        
        // Draw simple arrow lines
        $centerX = $width / 2;
        $centerY = $height / 2;
        
        // Arrow body (vertical line)
        imageline($frame, $centerX, $centerY - 15, $centerX, $centerY + 10, $arrowColor);
        
        // Arrow head (two lines)
        imageline($frame, $centerX, $centerY - 15, $centerX - 5, $centerY - 10, $arrowColor);
        imageline($frame, $centerX, $centerY - 15, $centerX + 5, $centerY - 10, $arrowColor);
        
        $frames[] = $frame;
    }
    
    // For this basic implementation, we'll just save the first frame as a static PNG
    // In a real implementation, you'd use ImageMagick or another library to create actual GIF animation
    
    imagepng($frames[0], str_replace('.gif', '_sample.png', $sampleFile));
    
    // Clean up
    foreach ($frames as $frame) {
        imagedestroy($frame);
    }
    
    return str_replace('.gif', '_sample.png', $sampleFile);
}

// Create or return existing sample
$samplePath = createSampleAnimatedGIF();

echo json_encode([
    'success' => true,
    'message' => 'Sample animated icon created',
    'file_path' => $samplePath,
    'note' => 'This is a static PNG sample. Upload your own GIF files for true animation.'
]);
?>