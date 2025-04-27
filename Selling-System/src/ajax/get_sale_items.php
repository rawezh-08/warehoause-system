<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get sale ID
    $sale_id = $_POST['sale_id'];
    
    // Get sale items
    $query = "SELECT si.*, p.name as product_name 
              FROM sale_items si 
              JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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