<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    if (empty($_GET['return_id'])) {
        throw new Exception('Return ID is required');
    }

    $return_id = intval($_GET['return_id']);

    $database = new Database();
    $conn = $database->getConnection();
    
    // Get return details
    $stmt = $conn->prepare("
        SELECT pr.*, s.invoice_number 
        FROM product_returns pr
        JOIN sales s ON pr.receipt_id = s.id
        WHERE pr.id = ? AND pr.receipt_type = 'selling'
    ");
    
    $stmt->execute([$return_id]);
    $return = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$return) {
        throw new Exception('Return not found');
    }

    // Get return items
    $stmt = $conn->prepare("
        SELECT ri.*, p.name as product_name
        FROM return_items ri
        JOIN products p ON ri.product_id = p.id
        WHERE ri.return_id = ?
        ORDER BY ri.id
    ");
    
    $stmt->execute([$return_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += floatval($item['total_price']);
    }

    // Update data object with items and total
    $return['items'] = $items;
    $return['total_amount'] = $total_amount;

    echo json_encode([
        'success' => true,
        'data' => $return
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 