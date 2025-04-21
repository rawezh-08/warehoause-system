<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get all products
    $query = "SELECT id, name, price, cost, stock_quantity FROM products ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $products
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