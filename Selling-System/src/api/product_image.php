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
    // Output the image file
    readfile($fullPath);
} else {
    // Return a placeholder image if file doesn't exist
    readfile('../assets/images/placeholder.jpg');
} 