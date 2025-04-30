<?php
// Include authentication check
require_once '../includes/auth.php';
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check if invoice number is provided
if (!isset($_POST['invoice_number']) || empty($_POST['invoice_number'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invoice number is required']);
    exit;
}

$invoiceNumber = $_POST['invoice_number'];

// Connect to database
$db = new Database();
$conn = $db->getConnection();

try {
    // Get sale ID from invoice number
    $saleQuery = "SELECT id FROM sales WHERE invoice_number = :invoice_number";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':invoice_number', $invoiceNumber);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
        exit;
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
    
    // Return success response with items
    echo json_encode([
        'status' => 'success',
        'items' => $items
    ]);
    
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 