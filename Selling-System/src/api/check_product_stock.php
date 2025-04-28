<?php
// Include database connection
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

// Check if all required parameters are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity']) || !isset($_POST['unit_type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$product_id = intval($_POST['product_id']);
$requested_quantity = intval($_POST['quantity']);
$unit_type = $_POST['unit_type'];

try {
    // Get product information including current quantity and unit conversion factors
    $stmt = $conn->prepare("
        SELECT 
            current_quantity, 
            IFNULL(pieces_per_box, 1) as pieces_per_box, 
            IFNULL(boxes_per_set, 1) as boxes_per_set
        FROM products 
        WHERE id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Calculate the actual pieces required based on unit type
    $pieces_required = $requested_quantity; // Default for 'piece'
    
    if ($unit_type === 'box') {
        $pieces_required = $requested_quantity * $product['pieces_per_box'];
    } else if ($unit_type === 'set') {
        $pieces_required = $requested_quantity * $product['pieces_per_box'] * $product['boxes_per_set'];
    }
    
    // Check if we have enough stock
    $available_quantity = $product['current_quantity'];
    
    // Convert available pieces to requested unit type for display
    $available_in_requested_unit = $available_quantity;
    
    if ($unit_type === 'box' && $product['pieces_per_box'] > 0) {
        $available_in_requested_unit = floor($available_quantity / $product['pieces_per_box']);
    } else if ($unit_type === 'set' && $product['pieces_per_box'] > 0 && $product['boxes_per_set'] > 0) {
        $available_in_requested_unit = floor($available_quantity / ($product['pieces_per_box'] * $product['boxes_per_set']));
    }
    
    if ($pieces_required <= $available_quantity) {
        // We have enough stock
        echo json_encode([
            'success' => true, 
            'message' => 'Stock available',
            'available_quantity' => $available_in_requested_unit,
            'required_quantity' => $requested_quantity
        ]);
    } else {
        // Not enough stock
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient stock',
            'available_quantity' => $available_in_requested_unit,
            'required_quantity' => $requested_quantity
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 