<?php
// This API endpoint handles product image requests and provides a fallback image if needed

// Define the base directory for product images
$uploadDir = __DIR__ . '/../uploads/products/';
$defaultImage = '../../assets/img/pro-1.png';

// Get the requested image filename from the query string
if (isset($_GET['filename'])) {
    $filename = basename($_GET['filename']);
    $imagePath = $uploadDir . $filename;
    
    // Check if the image file exists
    if (file_exists($imagePath)) {
        // Get file extension to set appropriate content type
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
        $contentType = 'image/jpeg'; // Default
        
        // Set content type based on file extension
        switch (strtolower($extension)) {
            case 'png':
                $contentType = 'image/png';
                break;
            case 'gif':
                $contentType = 'image/gif';
                break;
            case 'jpg':
            case 'jpeg':
                $contentType = 'image/jpeg';
                break;
        }
        
        // Output the image with appropriate headers
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($imagePath));
        readfile($imagePath);
    } else {
        // Redirect to default image if the requested image doesn't exist
        header('Location: ' . $defaultImage);
    }
} else {
    // No filename provided, redirect to default image
    header('Location: ' . $defaultImage);
}
exit; 