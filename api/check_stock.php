<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $unit_type = $_POST['unit_type'] ?? null;

    if (!$product_id || !$quantity || !$unit_type) {
        throw new Exception('Missing required parameters');
    }

    // Get product details
    $stmt = $conn->prepare("SELECT quantity, pieces_per_box, boxes_per_set FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Convert quantity based on unit type
    $total_pieces = 0;
    switch ($unit_type) {
        case 'piece':
            $total_pieces = $quantity;
            break;
        case 'box':
            $total_pieces = $quantity * $product['pieces_per_box'];
            break;
        case 'set':
            $total_pieces = $quantity * $product['pieces_per_box'] * $product['boxes_per_set'];
            break;
        default:
            throw new Exception('Invalid unit type');
    }

    // Check if stock is available
    $stock_available = $product['quantity'] >= $total_pieces;

    echo json_encode([
        'success' => true,
        'stock_available' => $stock_available,
        'current_stock' => $product['quantity'],
        'requested_pieces' => $total_pieces
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 