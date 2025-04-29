<?php
// Include authentication check


require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/image_upload_error.log');

// Log function for debugging
function log_debug($message, $data = null) {
    $log_message = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $log_message .= ' - ' . json_encode($data);
    }
    error_log($log_message);
}

try {
    log_debug('Starting product addition process');
    
    // Validate required fields
    $required_fields = [
        'name' => 'ناوی کاڵا',
        'category_id' => 'جۆری کاڵا',
        'unit_id' => 'یەکە',
        'purchase_price' => 'نرخی کڕین',
        'selling_price_single' => 'نرخی فرۆشتن'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $label;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception('تکایە ئەم خانانە پڕبکەوە: ' . implode('، ', $missing_fields));
    }

    // Clean number inputs
    $purchasePrice = isset($_POST['purchase_price']) ? str_replace(',', '', $_POST['purchase_price']) : null;
    $sellingPriceSingle = isset($_POST['selling_price_single']) ? str_replace(',', '', $_POST['selling_price_single']) : null;
    $sellingPriceWholesale = isset($_POST['selling_price_wholesale']) ? str_replace(',', '', $_POST['selling_price_wholesale']) : null;
    $minQuantity = isset($_POST['min_quantity']) ? str_replace(',', '', $_POST['min_quantity']) : 0;
    $currentQuantity = isset($_POST['current_quantity']) ? str_replace(',', '', $_POST['current_quantity']) : 0;
    $piecesPerBox = isset($_POST['pieces_per_box']) && $_POST['pieces_per_box'] !== '' ? 
        (int)str_replace(',', '', $_POST['pieces_per_box']) : null;
    $boxesPerSet = isset($_POST['boxes_per_set']) && $_POST['boxes_per_set'] !== '' ? 
        (int)str_replace(',', '', $_POST['boxes_per_set']) : null;

    // Generate code if not provided
    if (empty($_POST['code'])) {
        $_POST['code'] = uniqid('P');
    }

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        log_debug('Processing image upload', $_FILES['image']);
        $image = $_FILES['image'];
        
        // Log PHP settings
        log_debug('PHP Upload Settings', [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit')
        ]);
        
        // Check if file is actually an image using getimagesize
        $imageInfo = @getimagesize($image['tmp_name']);
        if ($imageInfo === false) {
            log_debug('File is not a valid image based on getimagesize');
            // Try to check MIME type as fallback
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $image['tmp_name']);
            finfo_close($finfo);
            
            log_debug('MIME type check result', $mimeType);
            
            if (!str_starts_with($mimeType, 'image/')) {
                throw new Exception('تەنها فایلی وێنە قبوڵ دەکرێت');
            }
        }
        
        // Upload directory
        $uploadDir = __DIR__ . '/../uploads/products/';
        log_debug('Upload directory', $uploadDir);
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            log_debug('Creating upload directory');
            if (!mkdir($uploadDir, 0777, true)) {
                log_debug('Failed to create upload directory');
                throw new Exception('نەتوانرا فۆڵدەری وێنەکان دروست بکرێت');
            }
        }
        
        // Check directory permissions
        log_debug('Directory permissions', [
            'exists' => file_exists($uploadDir),
            'is_dir' => is_dir($uploadDir),
            'is_writable' => is_writable($uploadDir)
        ]);
        
        // Generate unique filename with original extension
        $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        log_debug('File details', [
            'extension' => $extension,
            'filename' => $filename,
            'filepath' => $filepath,
            'original_size' => filesize($image['tmp_name']) / (1024 * 1024) . ' MB'
        ]);
        
        // Check GD library
        log_debug('GD Library', [
            'installed' => function_exists('gd_info'),
            'info' => function_exists('gd_info') ? gd_info() : 'Not installed'
        ]);
        
        // Always resize the image to ensure consistent size and quality
        try {
            log_debug('Image dimensions', $imageInfo);
            list($width, $height) = $imageInfo;
            
            // More aggressive optimization for faster upload and smaller file size
            // Calculate new dimensions (max 600px width or height while maintaining aspect ratio)
            $maxDimension = 600; // Reduced from 800px to 600px
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = intval($height * $maxDimension / $width);
            } else {
                $newHeight = $maxDimension;
                $newWidth = intval($width * $maxDimension / $height);
            }
            
            log_debug('New dimensions', ['width' => $newWidth, 'height' => $newHeight]);
            
            // Create image resource based on file type
            $sourceImage = null;
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    $sourceImage = @imagecreatefromjpeg($image['tmp_name']);
                    break;
                case 'png':
                    $sourceImage = @imagecreatefrompng($image['tmp_name']);
                    break;
                case 'gif':
                    $sourceImage = @imagecreatefromgif($image['tmp_name']);
                    break;
                default:
                    log_debug('Unsupported image format, trying to move directly');
                    // For unsupported file types, try to move the file directly
                    if (!move_uploaded_file($image['tmp_name'], $filepath)) {
                        log_debug('Failed to move uploaded file');
                        throw new Exception('هەڵەیەک ڕوویدا لە کاتی هەڵگرتنی وێنەکە');
                    }
                    $imagePath = 'uploads/products/' . $filename;
                    log_debug('File moved successfully', $imagePath);
                    break;
            }
            
            if ($sourceImage) {
                log_debug('Source image created successfully');
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
                
                // Save the resized image with lower quality for smaller file size
                $saveResult = false;
                switch ($extension) {
                    case 'jpeg':
                    case 'jpg':
                        $saveResult = imagejpeg($destinationImage, $filepath, 60); // Reduced quality from 80% to 60%
                        break;
                    case 'png':
                        // Convert PNG to JPG for smaller file size if it's not transparent
                        $hasTransparency = false;
                        if (function_exists('imagecolortransparent') && function_exists('imagealpha')) {
                            for ($x = 0; $x < $width; $x++) {
                                for ($y = 0; $y < $height; $y++) {
                                    $color = imagecolorat($sourceImage, $x, $y);
                                    $alpha = ($color >> 24) & 0x7F;
                                    if ($alpha > 0) { // If any pixel has transparency
                                        $hasTransparency = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                        
                        if (!$hasTransparency) {
                            // Convert to JPG if no transparency
                            $jpgFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
                            $jpgFilepath = $uploadDir . $jpgFilename;
                            $saveResult = imagejpeg($destinationImage, $jpgFilepath, 60);
                            $filename = $jpgFilename;
                            $filepath = $jpgFilepath;
                            log_debug('Converted PNG to JPG for smaller file size');
                        } else {
                            // Keep as PNG but with higher compression
                            $saveResult = imagepng($destinationImage, $filepath, 9); // Increased compression from 8 to 9 (max)
                        }
                        break;
                    case 'gif':
                        // Convert GIF to JPG if not animated
                        $isAnimated = false;
                        if (function_exists('imagecreatefromgif')) {
                            $fileContent = file_get_contents($image['tmp_name']);
                            $count = preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $fileContent, $matches);
                            $isAnimated = $count > 1;
                        }
                        
                        if (!$isAnimated) {
                            // Convert to JPG if not animated
                            $jpgFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
                            $jpgFilepath = $uploadDir . $jpgFilename;
                            $saveResult = imagejpeg($destinationImage, $jpgFilepath, 60);
                            $filename = $jpgFilename;
                            $filepath = $jpgFilepath;
                            log_debug('Converted GIF to JPG for smaller file size');
                        } else {
                            $saveResult = imagegif($destinationImage, $filepath);
                        }
                        break;
                }
                
                log_debug('Image save result', $saveResult);
                
                // Free up memory
                imagedestroy($sourceImage);
                imagedestroy($destinationImage);
                
                // Check if the file size exceeds 2MB even after optimization
                if (file_exists($filepath) && filesize($filepath) > 2 * 1024 * 1024) {
                    log_debug('File still too large after initial optimization, compressing further');
                    
                    // Further compress the image to ensure it's under 2MB
                    if (in_array($extension, ['jpg', 'jpeg']) || 
                        pathinfo($filepath, PATHINFO_EXTENSION) == 'jpg') {
                        // Re-compress the JPEG with even lower quality
                        $source = imagecreatefromjpeg($filepath);
                        if ($source) {
                            // Try with progressively lower quality until file size is under 1.9MB
                            $quality = 50; // Start with 50% quality
                            $fileSizeOk = false;
                            
                            while (!$fileSizeOk && $quality > 10) {
                                imagejpeg($source, $filepath, $quality);
                                if (filesize($filepath) <= 1.9 * 1024 * 1024) {
                                    $fileSizeOk = true;
                                } else {
                                    $quality -= 10;
                                }
                            }
                            
                            imagedestroy($source);
                            log_debug('Final image quality', $quality);
                        }
                    }
                }
                
                // Make sure the file was created
                if (!$saveResult || !file_exists($filepath)) {
                    log_debug('Failed to save resized image');
                    throw new Exception('هەڵەیەک ڕوویدا لە کاتی هەڵگرتنی وێنەکە');
                }
                
                $imagePath = 'uploads/products/' . $filename;
                log_debug('Image path saved', [
                    'path' => $imagePath,
                    'final_size' => file_exists($filepath) ? (filesize($filepath) / (1024 * 1024)) . ' MB' : 'unknown'
                ]);
            } else {
                log_debug('Failed to create source image');
                // As a fallback, move and resize the file directly using standard PHP
                if (move_uploaded_file($image['tmp_name'], $filepath)) {
                    // Check if the file is over 2MB, if so we'll try to compress it further
                    if (filesize($filepath) > 2 * 1024 * 1024 && function_exists('imagecreatefromstring')) {
                        $imgString = file_get_contents($filepath);
                        $source = @imagecreatefromstring($imgString);
                        
                        if ($source) {
                            // Try to compress into a JPEG
                            $compressedFilepath = $uploadDir . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
                            imagejpeg($source, $compressedFilepath, 60);
                            imagedestroy($source);
                            
                            // If compressed version is smaller, use it instead
                            if (file_exists($compressedFilepath) && 
                                filesize($compressedFilepath) < filesize($filepath)) {
                                unlink($filepath); // Delete original
                                $filepath = $compressedFilepath;
                                $filename = pathinfo($compressedFilepath, PATHINFO_BASENAME);
                                log_debug('Used compressed version instead', [
                                    'new_filename' => $filename,
                                    'new_size' => filesize($filepath) / (1024 * 1024) . ' MB'
                                ]);
                            }
                        }
                    }
                    
                    $imagePath = 'uploads/products/' . $filename;
                    log_debug('Fallback: Moved file directly', [
                        'path' => $imagePath,
                        'size' => filesize($filepath) / (1024 * 1024) . ' MB'
                    ]);
                } else {
                    log_debug('Fallback failed: Could not move file');
                }
            }
        } catch (Exception $e) {
            log_debug('Error in image processing', $e->getMessage());
            // If image processing fails, try direct upload as a last resort
            if (move_uploaded_file($image['tmp_name'], $filepath)) {
                $imagePath = 'uploads/products/' . $filename;
                log_debug('Error recovery: Moved file directly', [
                    'path' => $imagePath,
                    'size' => filesize($filepath) / (1024 * 1024) . ' MB'
                ]);
            }
        }
    } else if (isset($_FILES['image'])) {
        log_debug('Image upload error', [
            'error_code' => $_FILES['image']['error'],
            'error_message' => [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ][$_FILES['image']['error']]
        ]);
    }

    // Insert product into database
    $query = "INSERT INTO products (
        name, code, barcode, category_id, unit_id, 
        pieces_per_box, boxes_per_set, purchase_price, 
        selling_price_single, selling_price_wholesale, 
        min_quantity, current_quantity, notes, image
    ) VALUES (
        :name, :code, :barcode, :category_id, :unit_id, 
        :pieces_per_box, :boxes_per_set, :purchase_price, 
        :selling_price_single, :selling_price_wholesale, 
        :min_quantity, :current_quantity, :notes, :image
    )";
    
    $stmt = $conn->prepare($query);
    // Fix reference issues by creating variables
    $name = $_POST['name'];
    $code = $_POST['code'];
    $barcode = isset($_POST['barcode']) ? $_POST['barcode'] : '';
    $categoryId = $_POST['category_id'];
    $unitId = $_POST['unit_id'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':barcode', $barcode);
    $stmt->bindParam(':category_id', $categoryId);
    $stmt->bindParam(':unit_id', $unitId);
    $stmt->bindParam(':pieces_per_box', $piecesPerBox);
    $stmt->bindParam(':boxes_per_set', $boxesPerSet);
    $stmt->bindParam(':purchase_price', $purchasePrice);
    $stmt->bindParam(':selling_price_single', $sellingPriceSingle);
    $stmt->bindParam(':selling_price_wholesale', $sellingPriceWholesale);
    $stmt->bindParam(':min_quantity', $minQuantity);
    $stmt->bindParam(':current_quantity', $currentQuantity);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':image', $imagePath);
    
    log_debug('Database insert parameters', [
        'name' => $name,
        'code' => $code,
        'image_path' => $imagePath
    ]);
    
    if ($stmt->execute()) {
        log_debug('Product added successfully');
        echo json_encode([
            'success' => true,
            'message' => 'کاڵاکە بە سەرکەوتوویی زیاد کرا',
            'image_path' => $imagePath
        ]);
    } else {
        log_debug('Database insert failed', $stmt->errorInfo());
        throw new Exception('هەڵەیەک ڕوویدا لە کاتی زیادکردنی کاڵا');
    }

} catch (Exception $e) {
    log_debug('Exception occurred', $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 