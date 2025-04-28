<?php
// Include database connection
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Check if invoice_number parameter is provided
if (!isset($_GET['invoice_number']) || empty($_GET['invoice_number'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ژمارەی پسووڵە ناتوانرێت بدۆزرێتەوە'
    ]);
    exit;
}

$invoiceNumber = $_GET['invoice_number'];

// Get items from sale with product details
try {
    $query = "SELECT si.*, p.name as product_name, p.code as product_code
              FROM sales s 
              JOIN sale_items si ON s.id = si.sale_id
              JOIN products p ON si.product_id = p.id
              WHERE s.invoice_number = :invoice_number";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':invoice_number', $invoiceNumber);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) > 0) {
        echo json_encode([
            'status' => 'success',
            'data' => $items
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