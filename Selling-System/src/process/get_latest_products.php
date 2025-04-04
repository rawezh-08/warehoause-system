<?php
require_once '../config/database.php';
require_once '../models/Product.php';

header('Content-Type: application/json');

try {
    $productModel = new Product($conn);
    $latestProducts = $productModel->getLatest(5);
    
    // Format dates and clean up data for JSON response
    $formattedProducts = array_map(function($product) {
        return [
            'id' => $product['id'],
            'name' => htmlspecialchars_decode($product['name']),
            'code' => htmlspecialchars_decode($product['code']),
            'image' => $product['image'] ? htmlspecialchars_decode($product['image']) : null,
            'created_at' => $product['created_at']
        ];
    }, $latestProducts);
    
    echo json_encode($formattedProducts);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error fetching latest products: ' . $e->getMessage()
    ]);
} 