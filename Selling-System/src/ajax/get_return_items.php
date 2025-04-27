<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if return ID is provided
    if (!isset($_POST['return_id']) || empty($_POST['return_id'])) {
        throw new Exception('هیچ ناسنامەیەکی گەڕاندنەوە نەدراوە');
    }
    
    $returnId = intval($_POST['return_id']);
    
    // Get return items with product information
    $itemsQuery = "SELECT ri.*, p.name as product_name 
                  FROM return_items ri 
                  JOIN products p ON ri.product_id = p.id 
                  WHERE ri.return_id = :return_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':return_id', $returnId);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 