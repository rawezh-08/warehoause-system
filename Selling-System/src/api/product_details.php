<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get product ID from request
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize response array
$response = [
    'success' => false,
    'product' => null,
    'message' => ''
];

if ($productId <= 0) {
    $response['message'] = 'ناسنامەی کاڵا دیاری نەکراوە';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Prepare SQL to get product details
    $sql = "SELECT 
                p.id, 
                p.name, 
                p.code, 
                p.barcode, 
                p.image,
                p.purchase_price, 
                p.selling_price_single, 
                p.selling_price_wholesale, 
                p.current_quantity,
                p.pieces_per_box,
                p.boxes_per_set,
                p.unit_id,
                c.name AS category_name,
                u.name AS unit_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE p.id = :product_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch product details
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $response['success'] = true;
        $response['product'] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'code' => $product['code'],
            'barcode' => $product['barcode'],
            'image' => $product['image'],
            'purchase_price' => $product['purchase_price'],
            'selling_price_single' => $product['selling_price_single'],
            'selling_price_wholesale' => $product['selling_price_wholesale'],
            'current_quantity' => $product['current_quantity'],
            'pieces_per_box' => $product['pieces_per_box'],
            'boxes_per_set' => $product['boxes_per_set'],
            'category_name' => $product['category_name'],
            'unit_name' => $product['unit_name'],
            'unit_id' => $product['unit_id']
        ];
    } else {
        $response['message'] = 'کاڵای داواکراو نەدۆزرایەوە';
    }
} catch(PDOException $e) {
    $response['message'] = 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 