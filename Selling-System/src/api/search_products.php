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
                p.code, 
                p.barcode, 
                p.image,
                p.purchase_price,
                p.selling_price_single,
                p.selling_price_wholesale,
                p.current_quantity,
                p.pieces_per_box,
                p.boxes_per_set,
                p.notes,
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
        $formattedProducts[] = [
            'id' => $product['id'],
            'text' => $product['name'],
            'barcode' => $product['barcode'],
            'code' => $product['code'],
            'purchase_price' => $product['purchase_price'],
            'retail_price' => $product['selling_price_single'],
            'wholesale_price' => $product['selling_price_wholesale'],
            'current_quantity' => $product['current_quantity'],
            'pieces_per_box' => $product['pieces_per_box'],
            'boxes_per_set' => $product['boxes_per_set'],
            'image' => !empty($product['image']) ? 
                '../../uploads/products/' . basename($product['image']) : 
                '../../assets/images/no-image.png',
            'category' => $product['category_name'],
            'unit' => $product['unit_name']
        ];
    }
    
    echo json_encode($formattedProducts);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 