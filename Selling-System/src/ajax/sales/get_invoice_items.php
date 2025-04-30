<?php
// Include database connection
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Check if invoice_number parameter is provided
if (!isset($_POST['invoice_number']) || empty($_POST['invoice_number'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ژمارەی پسووڵە ناتوانرێت بدۆزرێتەوە'
    ]);
    exit;
}

$invoiceNumber = $_POST['invoice_number'];

// Get items from sale with product details
try {
    // First get the sale ID
    $saleQuery = "SELECT id FROM sales WHERE invoice_number = :invoice_number";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':invoice_number', $invoiceNumber);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        echo json_encode([
            'status' => 'error',
            'message' => 'پسووڵە نەدۆزرایەوە'
        ]);
        exit;
    }
    
    $saleId = $sale['id'];
    
    // Now get the items
    $query = "SELECT si.*, p.name as product_name, p.code as product_code
              FROM sale_items si 
              JOIN products p ON si.product_id = p.id
              WHERE si.sale_id = :sale_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) > 0) {
        echo json_encode([
            'status' => 'success',
            'items' => $items
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'هیچ کاڵایەک بۆ ئەم پسووڵەیە نەدۆزرایەوە'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'هەڵەیەک ڕوویدا لە کاتی هێنانی زانیاریەکان: ' . $e->getMessage()
    ]);
} 