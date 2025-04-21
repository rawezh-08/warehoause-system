<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../controllers/receipts/SaleReceiptsController.php';

try {
    // Validate required parameters
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('ID saknas');
    }
    
    // Get all posted data
    $saleData = [
        'id' => $_POST['id'],
        'invoice_number' => $_POST['invoice_number'] ?? '',
        'customer_id' => $_POST['customer_id'] ?? null,
        'date' => $_POST['date'] ?? date('Y-m-d'),
        'shipping_cost' => $_POST['shipping_cost'] ?? 0,
        'other_costs' => $_POST['other_costs'] ?? 0,
        'discount' => $_POST['discount'] ?? 0,
        'payment_type' => $_POST['payment_type'] ?? 'cash',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    // Log the update request
    error_log("Update sale request for ID: " . $saleData['id']);
    error_log("Update data: " . json_encode($saleData));
    
    // Initialize controller
    $salesController = new SaleReceiptsController($conn);
    
    // Update sale
    $result = $salesController->updateSale($saleData);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'پسووڵەکە بە سەرکەوتوویی گۆڕدرا'
        ]);
    } else {
        throw new Exception('هەڵەیەک ڕوویدا لە گۆڕانکاری پسووڵە');
    }
    
} catch (Exception $e) {
    // Log detailed error
    error_log("Error in update_sale.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 