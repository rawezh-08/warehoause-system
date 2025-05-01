<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Default response
$response = [
    'success' => false,
    'data' => null,
    'message' => 'هەڵەیەک ڕوویدا'
];

try {
    // Get product_id from request
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    
    if (!$product_id) {
        throw new Exception('ناسنامەی کاڵا پێویستە');
    }
    
    // Get product details
    $stmt = $conn->prepare("
        SELECT 
            p.id, 
            p.name, 
            p.code, 
            p.barcode, 
            p.purchase_price, 
            p.selling_price_single, 
            p.selling_price_wholesale, 
            p.current_quantity, 
            p.min_quantity, 
            p.pieces_per_box, 
            p.boxes_per_set,
            p.image,
            u.name AS unit_name,
            c.name AS category_name
        FROM 
            products p
        LEFT JOIN 
            units u ON p.unit_id = u.id
        LEFT JOIN 
            categories c ON p.category_id = c.id
        WHERE 
            p.id = ?
    ");
    
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $response = [
            'success' => true,
            'data' => $product,
            'message' => 'زانیاری کاڵا بەدەستهێنرا'
        ];
    } else {
        $response['message'] = 'کاڵاکە نەدۆزرایەوە';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?> 