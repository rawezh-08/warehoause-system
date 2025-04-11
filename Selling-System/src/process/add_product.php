<?php
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
    $piecesPerBox = isset($_POST['pieces_per_box']) ? str_replace(',', '', $_POST['pieces_per_box']) : null;
    $boxesPerSet = isset($_POST['boxes_per_set']) ? str_replace(',', '', $_POST['boxes_per_set']) : null;

    // Generate code if not provided
    if (empty($_POST['code'])) {
        $_POST['code'] = uniqid('P');
    }

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($image['type'], $allowedTypes)) {
            throw new Exception('تەنها فایلی وێنە (JPG, PNG, GIF) قبوڵ دەکرێت');
        }
        
        // Validate file size (max 5MB)
        if ($image['size'] > 5 * 1024 * 1024) {
            throw new Exception('قەبارەی وێنە دەبێت کەمتر بێت لە 5 مێگابایت');
        }
        
        // Upload directory
        $uploadDir = __DIR__ . '/../uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($image['tmp_name'], $filepath)) {
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