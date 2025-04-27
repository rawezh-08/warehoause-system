<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

try {
    // Check if purchase_id is provided
    if (!isset($_POST['purchase_id'])) {
        throw new Exception('Purchase ID is required');
    }

    $purchaseId = intval($_POST['purchase_id']);
    
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get purchase items with return information
    $query = "SELECT 
                pi.id,
                pi.product_id,
                p.name as product_name,
                pi.quantity,
                pi.unit_price,
                pi.total_price,
                pi.unit_type,
                COALESCE(pr.returned_quantity, 0) as returned_quantity
              FROM purchase_items pi
              JOIN products p ON pi.product_id = p.id
              LEFT JOIN (
                  SELECT 
                      purchase_item_id,
                      SUM(quantity) as returned_quantity
                  FROM product_returns
                  WHERE receipt_id = :purchase_id 
                  AND receipt_type = 'buying'
                  GROUP BY purchase_item_id
              ) pr ON pi.id = pr.purchase_item_id
              WHERE pi.purchase_id = :purchase_id";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':purchase_id', $purchaseId);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        throw new Exception('No items found for this purchase');
    }
    
    // Prepare response data
    $response['status'] = 'success';
    $response['data'] = [
        'items' => $items
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 