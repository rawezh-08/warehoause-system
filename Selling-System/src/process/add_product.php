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

    // کۆکردنەوەی داتاکان
    $data = [
        'name' => $_POST['name'],
        'code' => $_POST['code'],
        'barcode' => $_POST['barcode'],
        'category_id' => $_POST['category_id'],
        'unit_id' => $_POST['unit_id'],
        'pieces_per_box' => $_POST['pieces_per_box'] ?? null,
        'boxes_per_set' => $_POST['boxes_per_set'] ?? null,
        'purchase_price' => $_POST['purchase_price'],
        'selling_price_single' => $_POST['selling_price_single'],
        'selling_price_wholesale' => $_POST['selling_price_wholesale'] ?? null,
        'current_quantity' => $_POST['current_quantity'] ?? 0,
        'min_quantity' => $_POST['min_quantity'] ?? 0,
        'shelf' => $_POST['shelf'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'image' => $_FILES['image'] ?? null
    ];

    // زیادکردنی کاڵا
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