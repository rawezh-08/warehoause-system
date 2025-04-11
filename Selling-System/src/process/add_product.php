<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';

// Add proper namespace use statement
use App\Models\Product;

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

    // Create product model instance - removed $conn parameter as it's handled in constructor
    $productModel = new Product();

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

    // Prepare data
    $data = [
        'name' => trim($_POST['name']),
        'code' => trim($_POST['code']),
        'barcode' => trim($_POST['barcode'] ?? ''),
        'category_id' => $_POST['category_id'],
        'unit_id' => $_POST['unit_id'],
        'pieces_per_box' => $piecesPerBox,
        'boxes_per_set' => $boxesPerSet,
        'purchase_price' => $purchasePrice,
        'selling_price_single' => $sellingPriceSingle,
        'selling_price_wholesale' => $sellingPriceWholesale,
        'min_quantity' => $minQuantity,
        'current_quantity' => $currentQuantity,
        'notes' => $_POST['notes'] ?? null
    ];
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        // Upload directory is handled in the Product class
        $uploadDir = __DIR__ . '/../uploads/products/';
        
        // Generate unique filename
        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($image['tmp_name'], $filepath)) {
            $data['image'] = 'uploads/products/' . $filename;
        }
    }

    // Add product
    if ($productModel->create($data)) {
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