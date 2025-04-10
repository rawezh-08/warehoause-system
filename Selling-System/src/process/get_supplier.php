<?php
require_once '../config/database.php';

try {
    // Get supplier ID from request
    $supplierId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($supplierId <= 0) {
        throw new Exception('ناسنامەی دابینکەر نادروستە');
    }
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplierId]);
    
    // Fetch supplier data
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        throw new Exception('دابینکەر نەدۆزرایەوە');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'supplier' => $supplier
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 