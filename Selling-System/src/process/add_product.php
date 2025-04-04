<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';

header('Content-Type: application/json');

try {
    // پشتڕاستکردنەوەی داتاکان
    if (empty($_POST['name']) || empty($_POST['category_id']) || empty($_POST['unit_id'])) {
        throw new Exception('تکایە هەموو خانە پێویستەکان پڕبکەوە');
    }

    // دروستکردنی مۆدێلی کاڵا
    $productModel = new Product($conn);

    // پاککردنەوەی کۆما لە نرخەکان
    $purchasePrice = isset($_POST['purchase_price']) ? str_replace(',', '', $_POST['purchase_price']) : null;
    $sellingPriceSingle = isset($_POST['selling_price_single']) ? str_replace(',', '', $_POST['selling_price_single']) : null;
    $sellingPriceWholesale = isset($_POST['selling_price_wholesale']) ? str_replace(',', '', $_POST['selling_price_wholesale']) : null;
    $minQuantity = isset($_POST['min_quantity']) ? str_replace(',', '', $_POST['min_quantity']) : 0;
    $piecesPerBox = isset($_POST['pieces_per_box']) ? str_replace(',', '', $_POST['pieces_per_box']) : null;
    $boxesPerSet = isset($_POST['boxes_per_set']) ? str_replace(',', '', $_POST['boxes_per_set']) : null;
    
    // کۆکردنەوەی داتاکان
    $data = [
        'name' => $_POST['name'],
        'code' => $_POST['code'],
        'barcode' => $_POST['barcode'],
        'category_id' => $_POST['category_id'],
        'unit_id' => $_POST['unit_id'],
        'pieces_per_box' => $piecesPerBox,
        'boxes_per_set' => $boxesPerSet,
        'purchase_price' => $purchasePrice,
        'selling_price_single' => $sellingPriceSingle,
        'selling_price_wholesale' => $sellingPriceWholesale,
        'min_quantity' => $minQuantity,
        'shelf' => $_POST['shelf'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'image' => $_FILES['image'] ?? null
    ];

    // زیادکردنی کاڵا
    try {
        $result = $productModel->add($data);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'کاڵاکە بە سەرکەوتوویی زیاد کرا'
            ]);
        } else {
            throw new Exception('هەڵە لە زیادکردنی کاڵا');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 