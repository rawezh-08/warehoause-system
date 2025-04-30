<?php
// Include authentication check
require_once '../includes/auth.php';
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['invoice_number']) || empty($_POST['invoice_number'])) {
        throw new Exception('Invoice number is required');
    }

    $invoiceNumber = $_POST['invoice_number'];

    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();

    // Get sale ID from invoice number
    $saleQuery = "SELECT id FROM sales WHERE invoice_number = :invoice_number";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':invoice_number', $invoiceNumber);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Invoice not found');
    }

    $saleId = $sale['id'];

    // Get sale items
    $itemsQuery = "SELECT si.*, p.name as product_name, p.code as product_code 
                  FROM sale_items si 
                  LEFT JOIN products p ON si.product_id = p.id 
                  WHERE si.sale_id = :sale_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':sale_id', $saleId);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $response = [
        'success' => true,
        'data' => [
            'invoice_number' => $invoiceNumber,
            'sale_id' => $saleId,
            'items' => $items
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 