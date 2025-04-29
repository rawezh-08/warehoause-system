<?php
// Simple API to serve product images correctly
header("Content-Type: image/jpeg");
header("Cache-Control: max-age=86400, public");

// Get the filename parameter
$filename = isset($_GET['filename']) ? $_GET['filename'] : '';

// Security check - only allow image files
if (empty($filename) || !preg_match('/\.(jpg|jpeg|png|gif)$/i', $filename)) {
    // Return a blank/placeholder image or error image
    readfile('../assets/images/placeholder.jpg');
    exit;
}

// Base path to product images
$basePath = '../uploads/products/';

// Build the full path
$fullPath = $basePath . basename($filename);

// Check if file exists
if (file_exists($fullPath)) {
    // Get image info
    $imageInfo = getimagesize($fullPath);
    if ($imageInfo === false) {
        // Return placeholder if not a valid image
        readfile('../assets/images/placeholder.jpg');
        exit;
    }
    
    // Check if image needs resizing (if width or height > 800px)
    if ($imageInfo[0] > 800 || $imageInfo[1] > 800) {
        // Calculate new dimensions
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        if ($width > $height) {
            $newWidth = 800;
            $newHeight = intval($height * 800 / $width);
        } else {
            $newHeight = 800;
            $newWidth = intval($width * 800 / $height);
        }
        
        // Create image resource based on file type
        $sourceImage = null;
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $sourceImage = imagecreatefromjpeg($fullPath);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($fullPath);
                break;
            case 'gif':
                $sourceImage = imagecreatefromgif($fullPath);
                break;
        }
        
        if ($sourceImage) {
            // Create a new true color image with new dimensions
            $destinationImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG images
            if ($extension == 'png') {
                imagealphablending($destinationImage, false);
                imagesavealpha($destinationImage, true);
                $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
                imagefilledrectangle($destinationImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Resize the image
            imagecopyresampled(
                $destinationImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $width, $height
            );
            
            // Output the resized image
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    imagejpeg($destinationImage, null, 80); // 80% quality
                    break;
                case 'png':
                    imagepng($destinationImage, null, 8); // Compression level 8 (0-9)
                    break;
                case 'gif':
                    imagegif($destinationImage);
                    break;
            }
            
            // Free up memory
            imagedestroy($sourceImage);
            imagedestroy($destinationImage);
        } else {
            // If resizing fails, output original image
            readfile($fullPath);
        }
    } else {
        // If image is already small enough, output it directly
        readfile($fullPath);
    }
} else {
    // Return a placeholder image if file doesn't exist
    readfile('../assets/images/placeholder.jpg');
} 