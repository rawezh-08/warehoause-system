<?php

try {
    // Get product details
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name, 
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
        echo json_encode([
            'success' => false,
            'message' => 'بەرهەم نەدۆزرایەوە'
        ]);
        exit;
    }
    
    // Determine base price based on price type
    $base_price = ($price_type === 'wholesale') ? 
        floatval($product['selling_price_wholesale']) : 
        floatval($product['selling_price_single']);
    
    // Adjust price based on unit type
    $final_price = $base_price;
    
    if ($unit_type === 'piece' && !empty($product['pieces_per_box'])) {
        // If unit type is piece, divide box price by pieces per box
        $final_price = $base_price / intval($product['pieces_per_box']);
    } else if ($unit_type === 'set' && !empty($product['pieces_per_box']) && !empty($product['boxes_per_set'])) {
        // If unit type is set, multiply box price by boxes per set
        $final_price = $base_price * intval($product['boxes_per_set']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'price' => $final_price,
            'product_id' => $product_id,
            'unit_type' => $unit_type,
            'price_type' => $price_type,
            'pieces_per_box' => $product['pieces_per_box'],
            'boxes_per_set' => $product['boxes_per_set']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا',
        'error' => $e->getMessage()
    ]);
} 