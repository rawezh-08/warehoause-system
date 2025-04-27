<?php
// Include database connection
require_once '../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if product_id is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

$product_id = intval($_GET['product_id']);

try {
    // Get product information
    $stmt = $conn->prepare("
        SELECT p.*, u.name as unit_name, u.is_piece, u.is_box, u.is_set
        FROM products p
        LEFT JOIN units u ON p.unit_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }

    // Get stock information from inventory table
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN unit_type = 'piece' THEN quantity ELSE 0 END) as pieces,
            SUM(CASE WHEN unit_type = 'box' THEN quantity ELSE 0 END) as boxes,
            SUM(CASE WHEN unit_type = 'set' THEN quantity ELSE 0 END) as sets
        FROM inventory
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format the response
    $response = [
        'success' => true,
        'product' => [
            'name' => $product['name'],
            'code' => $product['code'],
            'image' => $product['image'] ? '../uploads/products/' . $product['image'] : null
        ],
        'stock' => [
            'pieces' => intval($stock['pieces'] ?? 0),
            'boxes' => intval($stock['boxes'] ?? 0),
            'sets' => intval($stock['sets'] ?? 0)
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 