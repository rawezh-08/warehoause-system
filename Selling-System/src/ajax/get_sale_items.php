<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    if (empty($_GET['sale_id'])) {
        throw new Exception('Sale ID is required');
    }

    $sale_id = intval($_GET['sale_id']);

    $database = new Database();
    $conn = $database->getConnection();
    
    // Get the sale items with product details
    $stmt = $conn->prepare("
        SELECT si.*, p.name as product_name, p.code as product_code, p.pieces_per_box, p.boxes_per_set
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
        ORDER BY si.id
    ");
    
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (Exception $e) {
    error_log("Error in get_sale_items.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 