<?php

try {
    // Get wasting products with product information
    $stmt = $conn->prepare("
        SELECT wp.*, p.name as product_name, p.barcode
        FROM wasting_products wp
        JOIN products p ON wp.product_id = p.id
        WHERE wp.wasting_id = :wasting_id
    ");
    $stmt->bindParam(':wasting_id', $wasting_id);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the wasting ID and number of products found
    error_log("Getting products for wasting ID: " . $wasting_id);
    error_log("Number of products found: " . count($products));
    
    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
    
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Error in get_wasting_products.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 