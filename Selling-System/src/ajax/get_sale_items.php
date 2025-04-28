<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get sale ID
    if (!isset($_POST['sale_id']) || empty($_POST['sale_id'])) {
        throw new Exception('Sale ID is required');
    }
    
    $sale_id = $_POST['sale_id'];
    
    // Get sale items with previously returned quantities
    $query = "SELECT si.*, 
              p.name as product_name,
              p.code as product_code,
              COALESCE((
                  SELECT SUM(ri.quantity) 
                  FROM return_items ri 
                  JOIN product_returns pr ON ri.return_id = pr.id 
                  WHERE pr.receipt_id = si.sale_id AND pr.receipt_type = 'selling' 
                  AND ri.product_id = si.product_id
              ), 0) as returned_quantity
              FROM sale_items si 
              JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        throw new Exception('لا يوجد بنود للبيع المحدد - هیچ کاڵایەک نەدۆزرایەوە');
    }
    
    // For each item, check if there's any quantity left to return
    $returnable_items = array_filter($items, function($item) {
        return ($item['quantity'] - $item['returned_quantity']) > 0;
    });
    
    if (empty($returnable_items)) {
        throw new Exception('هەموو کاڵاکان پێشتر گەڕاونەتەوە');
    }
    
    echo json_encode([
        'success' => true,
        'items' => $returnable_items
    ]);
    
} catch (Exception $e) {
    error_log('get_sale_items.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 