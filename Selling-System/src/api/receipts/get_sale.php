<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../controllers/receipts/SaleReceiptsController.php';

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('هیچ پسووڵەیەک دیاری نەکراوە');
    }
    
    $saleId = $_POST['id'];
    
    // Log that this API was called
    error_log("API get_sale.php was called for sale_id: $saleId");
    
    $salesController = new SaleReceiptsController($conn);
    
    // Get sale details
    $saleQuery = "SELECT s.*, 
                       c.name as customer_name,
                       c.phone as customer_phone
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                WHERE s.id = :sale_id";
    
    $stmt = $conn->prepare($saleQuery);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    $saleData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$saleData) {
        throw new Exception('پسووڵە نەدۆزرایەوە');
    }
    
    // Get sale items
    $itemsQuery = "SELECT si.*, 
                      p.name as product_name,
                      p.code as product_code,
                      p.image as product_image,
                      si.unit_price as price
                  FROM sale_items si
                  LEFT JOIN products p ON si.product_id = p.id
                  WHERE si.sale_id = :sale_id";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine data
    $result = [
        'id' => $saleData['id'],
        'invoice_number' => $saleData['invoice_number'],
        'customer_id' => $saleData['customer_id'],
        'customer_name' => $saleData['customer_name'],
        'date' => $saleData['date'],
        'shipping_cost' => $saleData['shipping_cost'] ?? 0,
        'other_costs' => $saleData['other_costs'] ?? 0,
        'discount' => $saleData['discount'] ?? 0,
        'payment_type' => $saleData['payment_type'],
        'notes' => $saleData['notes'],
        'items' => $itemsData
    ];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    // Log detailed error
    error_log("Error in get_sale.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 