<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get product ID from request
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Validate product ID
if (!$product_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

try {
    // Query to get product information
    $query = "
        SELECT p.*, c.name as category_name, u.name as unit_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN units u ON p.unit_id = u.id
        WHERE p.id = :product_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    // Format image path if exists
    if ($product['image']) {
        $product['image_url'] = $product['image'];
    }
    
    // Return product information
    echo json_encode([
        'success' => true,
        'data' => $product
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database error in get_product_info.php: " . $e->getMessage());
    
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving product information',
        'error' => $e->getMessage()
    ]);
}
?> 