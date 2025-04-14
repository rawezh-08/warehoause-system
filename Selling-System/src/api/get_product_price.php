<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get parameters
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$unit_type = isset($_GET['unit_type']) ? $_GET['unit_type'] : 'piece';
$price_type = isset($_GET['price_type']) ? $_GET['price_type'] : 'single';

// Validate input
if (!$product_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامەی بەرهەم پێویستە'
    ]);
    exit;
}

try {
    // Get product details
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name, 
            p.retail_price, 
            p.wholesale_price,
            p.pieces_per_box,
            p.boxes_per_set
        FROM products p
        WHERE p.id = ?
    ");
    
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'بەرهەم نەدۆزرایەوە'
        ]);
        exit;
    }
    
    // Determine base price based on price type
    $base_price = ($price_type === 'wholesale') ? 
        floatval($product['wholesale_price']) : 
        floatval($product['retail_price']);
    
    // Adjust price based on unit type
    $final_price = $base_price;
    
    if ($unit_type === 'box' && !empty($product['pieces_per_box'])) {
        $final_price = $base_price * intval($product['pieces_per_box']);
    } else if ($unit_type === 'set' && !empty($product['pieces_per_box']) && !empty($product['boxes_per_set'])) {
        $final_price = $base_price * intval($product['pieces_per_box']) * intval($product['boxes_per_set']);
    }
    
    echo json_encode([
        'success' => true,
        'price' => $final_price,
        'product_id' => $product_id,
        'unit_type' => $unit_type,
        'price_type' => $price_type
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا',
        'error' => $e->getMessage()
    ]);
}
?> 