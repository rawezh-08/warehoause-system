<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID ی بەرهەم پێویستە']);
    exit;
}

$product_id = $_GET['product_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            p.*,
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

    echo json_encode([
        'success' => true,
        'data' => $product
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا']);
}
?> 