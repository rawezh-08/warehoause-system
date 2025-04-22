<?php

// Include database connection
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get latest 5 products 
    $query = "SELECT p.id, p.name, p.code, p.image, p.created_at 
              FROM products p 
              ORDER BY p.created_at DESC 
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return products as JSON
    echo json_encode($products);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 