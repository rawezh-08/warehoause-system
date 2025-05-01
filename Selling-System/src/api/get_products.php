<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get all products
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name,
            p.code,
            p.barcode,
            p.image,
            p.pieces_per_box,
            p.boxes_per_set,
            p.purchase_price,
            p.selling_price_single,
            p.selling_price_wholesale,
            p.current_quantity,
            p.min_quantity,
            c.name as category_name,
            u.name as unit_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN units u ON p.unit_id = u.id
        ORDER BY p.name ASC
    ");

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Error in get_products.php: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 