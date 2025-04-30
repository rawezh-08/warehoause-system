<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Get sale ID from POST data
$sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;

try {
    // Get sale details with customer information
    $stmt = $conn->prepare("
        SELECT s.*, c.name as customer_name, c.phone1 as customer_phone
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ? AND s.is_draft = 0
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode([
            'success' => false,
            'message' => 'پسووڵەکە نەدۆزرایەوە'
        ]);
        exit;
    }

    // Get sale items with product information
    $stmt = $conn->prepare("
        SELECT si.*, p.name as product_name, p.code as product_code
        FROM sale_items si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $sale,
        'items' => $items
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری پسووڵە'
    ]);
} 