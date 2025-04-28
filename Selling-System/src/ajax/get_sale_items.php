<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!isset($_POST['sale_id'])) {
        throw new Exception('Sale ID is required');
    }

    $sale_id = intval($_POST['sale_id']);
    
    // Get sale items with product details and return status
    $stmt = $conn->prepare("
        SELECT 
            si.id,
            si.product_id,
            si.quantity,
            si.unit_price,
            si.unit_type,
            si.returned_quantity,
            p.name as product_name,
            p.code as product_code,
            p.pieces_per_box,
            p.boxes_per_set
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        throw new Exception('No items found for this sale');
    }
    
    // Get sale details to check payment status
    $stmt = $conn->prepare("
        SELECT s.*, 
               COALESCE(s.paid_amount, 0) as paid_amount,
               COALESCE(s.remaining_amount, 0) as remaining_amount
        FROM sales s 
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        throw new Exception('Sale not found');
    }
    
    // Check if sale has any payments
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM debt_transactions 
        WHERE reference_id = ? 
        AND transaction_type IN ('payment', 'collection')
    ");
    $stmt->execute([$sale_id]);
    $hasPayments = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    // Check if sale has any returns
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM product_returns 
        WHERE receipt_id = ? 
        AND receipt_type = 'selling'
    ");
    $stmt->execute([$sale_id]);
    $hasReturns = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    // Process items to ensure correct format for returned_quantity
    foreach ($items as &$item) {
        // Ensure returned_quantity is set
        if (!isset($item['returned_quantity'])) {
            $item['returned_quantity'] = 0;
        }
        
        // Convert to float/number
        $item['quantity'] = floatval($item['quantity']);
        $item['returned_quantity'] = floatval($item['returned_quantity']);
        $item['unit_price'] = floatval($item['unit_price']);
        
        // Calculate remaining
        $item['remaining_quantity'] = $item['quantity'] - $item['returned_quantity'];
        
        // Ensure unit_type is set
        if (!isset($item['unit_type']) || empty($item['unit_type'])) {
            $item['unit_type'] = 'piece';
        }
    }
    unset($item); // Release reference
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'sale' => $sale,
        'has_payments' => $hasPayments,
        'has_returns' => $hasReturns
    ]);

} catch (Exception $e) {
    error_log("Error in get_sale_items.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 