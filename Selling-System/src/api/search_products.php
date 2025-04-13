<?php
// Include database connection
require_once '../config/database.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? $_GET['q'] : '';

try {
    // Build search query
    $sql = "SELECT 
                p.id, 
                p.name, 
                p.barcode, 
                p.code,
                p.selling_price_single as retail_price,
                p.selling_price_wholesale as wholesale_price,
                p.current_quantity,
                p.image_path,
                c.name as category_name,
                u.name as unit_name,
                u.is_piece,
                u.is_box,
                u.is_set
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE p.name LIKE :query OR p.barcode LIKE :query OR p.code LIKE :query
            ORDER BY p.name ASC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format products for Select2
    $formattedProducts = [];
    foreach ($products as $product) {
        $image = !empty($product['image_path']) ? 
            '../../uploads/products/' . $product['image_path'] : 
            '../../assets/images/no-image.png';
        
        $formattedProducts[] = [
            'id' => $product['id'],
            'text' => $product['name'],
            'barcode' => $product['barcode'],
            'code' => $product['code'],
            'retail_price' => $product['retail_price'],
            'wholesale_price' => $product['wholesale_price'],
            'current_quantity' => $product['current_quantity'],
            'image' => $image,
            'category' => $product['category_name'],
            'unit_name' => $product['unit_name'],
            'is_piece' => $product['is_piece'],
            'is_box' => $product['is_box'],
            'is_set' => $product['is_set']
        ];
    }
    
    echo json_encode($formattedProducts);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 