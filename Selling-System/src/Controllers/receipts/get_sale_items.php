<?php
require_once '../../config/database.php';

// Check if sale_id is provided
if (!isset($_POST['sale_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'پێناسەی پسووڵە نەدۆزرایەوە'
    ]);
    exit;
}

$sale_id = intval($_POST['sale_id']);

try {
    // Get sale items with product details
    $stmt = $conn->prepare("
        SELECT 
            si.*,
            p.name as product_name
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
        ORDER BY si.id ASC
    ");
    
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'items' => $items
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
    ]);
} 