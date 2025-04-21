<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../controllers/receipts/SaleReceiptsController.php';

try {
    if (!isset($_POST['sale_id']) || empty($_POST['sale_id'])) {
        throw new Exception('هیچ پسووڵەیەک دیاری نەکراوە');
    }
    
    $saleId = $_POST['sale_id'];
    
    // Get sale items
    $itemsQuery = "SELECT si.*, 
                      p.name as product_name,
                      p.code as product_code,
                      si.unit_price as price
                  FROM sale_items si
                  LEFT JOIN products p ON si.product_id = p.id
                  WHERE si.sale_id = :sale_id";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $itemsData
    ]);
    
} catch (Exception $e) {
    // Log detailed error
    error_log("Error in get_sale_items.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 