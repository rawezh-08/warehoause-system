<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامە و جۆری پسوڵە پێویستە'
    ]);
    exit;
}

$receipt_id = intval($_POST['id']);
$receipt_type = $_POST['type'];

// Validate receipt type
if ($receipt_type !== 'selling') {
    echo json_encode([
        'success' => false,
        'message' => 'ئەم جۆرە پسوڵەیە پشتگیری ناکرێت'
    ]);
    exit;
}

try {
    // Get receipt header information
    if ($receipt_type === 'selling') {
        $header_stmt = $conn->prepare("
            SELECT s.*, c.name as customer_name, c.phone1 as customer_phone
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ?
        ");
        $header_stmt->execute([$receipt_id]);
        $header = $header_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$header) {
            echo json_encode([
                'success' => false,
                'message' => 'پسوڵە نەدۆزرایەوە'
            ]);
            exit;
        }
        
        // Get receipt items
        $items_stmt = $conn->prepare("
            SELECT si.*, p.name as product_name, p.code as product_code, p.image as product_image
            FROM sale_items si
            LEFT JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ");
        $items_stmt->execute([$receipt_id]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['total_price'];
        }
        
        $shipping_cost = $header['shipping_cost'] ?? 0;
        $other_costs = $header['other_costs'] ?? 0;
        $discount = $header['discount'] ?? 0;
        
        $grand_total = $subtotal + $shipping_cost + $other_costs - $discount;
        
        // Format the response
        $response = [
            'success' => true,
            'data' => [
                'header' => $header,
                'items' => $items,
                'totals' => [
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shipping_cost,
                    'other_costs' => $other_costs,
                    'discount' => $discount,
                    'grand_total' => $grand_total
                ]
            ]
        ];
        
        echo json_encode($response);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیاری پسوڵە',
        'debug' => [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage()
        ]
    ]);
}
?> 