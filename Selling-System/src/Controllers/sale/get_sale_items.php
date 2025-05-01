<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if sale_id is provided
if (!isset($_GET['sale_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

$saleId = $_GET['sale_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get sale items with product details
    $query = "SELECT si.*, p.name as product_name, p.code as product_code 
              FROM sale_items si 
              LEFT JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = :sale_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 