<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Get search term, default to empty string
$search = isset($_GET['term']) ? $_GET['term'] : '';
$show_initial = isset($_GET['show_initial']) && $_GET['show_initial'] == 1;

// Determine which query to use based on whether we need initial products
if (empty($search) && $show_initial) {
    // Query to get recently added or popular products when no search term
    $query = "SELECT id, name, code, barcode, selling_price_single, purchase_price 
              FROM products 
              ORDER BY created_at DESC 
              LIMIT 15";  // Get the most recent 15 products
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
} else {
    // Query to get products matching the search term
    $query = "SELECT id, name, code, barcode, selling_price_single, purchase_price 
              FROM products 
              WHERE name LIKE ? OR code LIKE ? OR barcode LIKE ? 
              ORDER BY name ASC 
              LIMIT 15";
    
    $stmt = $conn->prepare($query);
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
}

try {
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the products for the response
    $formattedProducts = array_map(function($product) {
        return [
            'id' => $product['id'],
            'text' => $product['name'] . ' - ' . $product['code'],
            'name' => $product['name'],
            'code' => $product['code'],
            'barcode' => $product['barcode'],
            'selling_price' => $product['selling_price_single'],
            'purchase_price' => $product['purchase_price']
        ];
    }, $products);
    
    echo json_encode([
        'results' => $formattedProducts
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error searching products: ' . $e->getMessage()
    ]);
} 