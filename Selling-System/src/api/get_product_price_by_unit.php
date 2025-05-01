<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['product_id']) || !isset($_GET['unit_type'])) {
    echo json_encode(['success' => false, 'message' => 'پارامیتەرەکان پێویستن']);
    exit;
}

$product_id = $_GET['product_id'];
$unit_type = $_GET['unit_type'];

try {
    // وەرگرتنی زانیارییەکانی بەرهەم
    $stmt = $conn->prepare("
        SELECT 
            p.purchase_price,
            p.selling_price_single,
            p.selling_price_wholesale,
            p.pieces_per_box,
            p.boxes_per_set
        FROM products p 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'بەرهەم نەدۆزرایەوە']);
        exit;
    }

    // دیاریکردنی نرخی ڕاست بەپێی یەکە
    $price = 0;
    switch ($unit_type) {
        case 'piece':
            $price = $product['selling_price_single'];
            break;
        case 'box':
            $price = $product['selling_price_wholesale'];
            break;
        case 'set':
            // نرخی سێت = نرخی کارتۆن × ژمارەی کارتۆن لە سێتێکدا
            $price = $product['selling_price_wholesale'] * $product['boxes_per_set'];
            break;
    }

    echo json_encode([
        'success' => true,
        'price' => $price,
        'unit_type' => $unit_type
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا']);
}
?> 