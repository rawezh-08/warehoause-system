<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Get search term and show_initial parameter
$search = isset($_GET['term']) ? $_GET['term'] : '';
$show_initial = isset($_GET['show_initial']) && $_GET['show_initial'] == '1';

try {
    if (empty($search) && $show_initial) {
        // Query to get recently added products when no search term
        $query = "SELECT 
                    p.id, 
                    p.name, 
                    p.code, 
                    p.barcode,
                    p.selling_price_single,
                    c.name as category_name 
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  ORDER BY p.created_at DESC 
                  LIMIT 10";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
    } else {
        // Query to search products
        $query = "SELECT 
                    p.id, 
                    p.name, 
                    p.code, 
                    p.barcode,
                    p.selling_price_single,
                    c.name as category_name 
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?
                  ORDER BY p.name ASC 
                  LIMIT 10";
        
        $stmt = $conn->prepare($query);
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the products for the response
    $formattedProducts = array_map(function($product) {
        return [
            'id' => $product['id'],
            'text' => $product['name'],
            'name' => $product['name'],
            'code' => $product['code'],
            'barcode' => $product['barcode'],
            'category' => $product['category_name'],
            'selling_price' => number_format($product['selling_price_single'], 0) . ' د.ع',
            'html' => '<div class="search-result-item">
                        <div class="product-name">' . htmlspecialchars($product['name']) . '</div>
                        <div class="product-details">
                            <span class="code">' . htmlspecialchars($product['code']) . '</span>
                            <span class="category">' . htmlspecialchars($product['category_name']) . '</span>
                            <span class="price">' . number_format($product['selling_price_single'], 0) . ' د.ع</span>
                        </div>
                      </div>'
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