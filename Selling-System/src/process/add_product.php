<?php
// Include authentication check


require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
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
    if (isset($_FILES['image'])) {
        // Check for upload errors
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = match($_FILES['image']['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'قەبارەی وێنەکە زۆر گەورەیە. تکایە وێنەیەکی بچووکتر هەڵبژێرە.',
                UPLOAD_ERR_PARTIAL => 'تەنها بەشێک لە وێنەکە هەڵگرا. تکایە دووبارە هەوڵ بدەوە.',
                UPLOAD_ERR_NO_FILE => 'هیچ وێنەیەک هەڵنەبژێردراوە.',
                UPLOAD_ERR_NO_TMP_DIR => 'فۆڵدەری کاتییەکە بوونی نییە.',
                UPLOAD_ERR_CANT_WRITE => 'نەتوانرا وێنەکە هەڵبگرێت.',
                UPLOAD_ERR_EXTENSION => 'هەڵەیەک ڕوویدا لە کاتی هەڵگرتنی وێنەکە.',
                default => 'هەڵەیەک ڕوویدا لە کاتی هەڵگرتنی وێنەکە.'
            };
            throw new Exception($errorMessage);
        }

        $image = $_FILES['image'];
        
        // Log original file size
        $originalSize = $image['size'];
        error_log("Original image size: " . round($originalSize / 1024 / 1024, 2) . "MB");
        
        // Check if file is actually an image using getimagesize
        $imageInfo = @getimagesize($image['tmp_name']);
        if ($imageInfo === false) {
            // Try to check MIME type as fallback
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $image['tmp_name']);
            finfo_close($finfo);
            
            if (!str_starts_with($mimeType, 'image/')) {
                throw new Exception('تەنها فایلی وێنە قبوڵ دەکرێت');
            }
        }
        
        // Upload directory
        $uploadDir = __DIR__ . '/../uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('نەتوانرا فۆڵدەری وێنەکان دروست بکرێت');
            }
        }
        
        // Generate unique filename with original extension
        $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Always resize the image to ensure consistent size and quality
        list($width, $height) = $imageInfo;
        
        // Calculate new dimensions (max 600px width or height while maintaining aspect ratio)
        $maxDimension = 600; // Reduced from 800 to 600 for smaller file size
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = intval($height * $maxDimension / $width);
        } else {
            $newHeight = $maxDimension;
            $newWidth = intval($width * $maxDimension / $height);
        }
        
        // Create image resource based on file type
        $sourceImage = null;
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $sourceImage = imagecreatefromjpeg($image['tmp_name']);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($image['tmp_name']);
                break;
            case 'gif':
                $sourceImage = imagecreatefromgif($image['tmp_name']);
                break;
            default:
                // For unsupported file types, try to move the file directly
                if (!move_uploaded_file($image['tmp_name'], $filepath)) {
                    throw new Exception('هەڵەیەک ڕوویدا لە کاتی هەڵگرتنی وێنەکە');
                }
                $imagePath = 'uploads/products/' . $filename;
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
            
            // Resize the image with better quality
            imagecopyresampled(
                $destinationImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $width, $height
            );
            
            // Save the resized image with higher compression
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    imagejpeg($destinationImage, $filepath, 70); // Reduced quality from 80 to 70 for better compression
                    break;
                case 'png':
                    imagepng($destinationImage, $filepath, 9); // Increased compression from 8 to 9 (maximum)
                    break;
                case 'gif':
                    imagegif($destinationImage, $filepath);
                    break;
            }
            
            // Free up memory
            imagedestroy($sourceImage);
            imagedestroy($destinationImage);
            
            // Check the size of the compressed image
            $compressedSize = filesize($filepath);
            error_log("Compressed image size: " . round($compressedSize / 1024 / 1024, 2) . "MB");
            error_log("Compression ratio: " . round(($originalSize - $compressedSize) / $originalSize * 100, 2) . "%");
            
            // if ($compressedSize > 5 * 1024 * 1024) {
            //     // If still too large, delete the file and throw error
            //     unlink($filepath);
            //     throw new Exception('قەبارەی وێنە دەبێت کەمتر بێت لە 5 مێگابایت');
            // }
            
            $imagePath = 'uploads/products/' . $filename;
        }
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
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'کاڵاکە بە سەرکەوتوویی زیاد کرا'
        ]);
    } else {
        throw new Exception('هەڵەیەک ڕوویدا لە کاتی زیادکردنی کاڵا');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 