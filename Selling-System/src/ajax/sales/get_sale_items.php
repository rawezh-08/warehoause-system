<?php
// Include database connection
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Check if sale_id parameter is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ژمارەی پسووڵە ناتوانرێت بدۆزرێتەوە'
    ]);
    exit;
}

$saleId = intval($_GET['sale_id']);

// Get items from sale with product details
try {
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