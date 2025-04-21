<?php
require_once '../../config/database.php';
require_once '../../controllers/receipts/PurchaseReceiptsController.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize controller
    $controller = new PurchaseReceiptsController($db);
    
    // Get filtered purchases
    $purchases = $controller->getPurchasesData(
        $data['start_date'] ?? null,
        $data['end_date'] ?? null,
        [
            'supplier' => $data['provider'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null
        ]
    );
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $purchases
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 