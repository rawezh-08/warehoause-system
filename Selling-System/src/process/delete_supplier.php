<?php
require_once '../config/database.php';

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ناسنامەی دابینکەر نادروستە');
    }
    
    $supplierId = (int)$data['id'];
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if supplier exists
    $checkStmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
    $checkStmt->execute([$supplierId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('دابینکەر نەدۆزرایەوە');
    }
    
    // Check if supplier has associated purchases or debt
    $checkDebtStmt = $conn->prepare("SELECT id FROM supplier_debt_transactions WHERE supplier_id = ? LIMIT 1");
    $checkDebtStmt->execute([$supplierId]);
    
    if ($checkDebtStmt->rowCount() > 0) {
        throw new Exception('ناتوانرێت ئەم دابینکەرە بسڕێتەوە چونکە قەرزی هەیە یان کڕینی لێکراوە');
    }
    
    // Delete supplier
    $deleteStmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $result = $deleteStmt->execute([$supplierId]);
    
    if (!$result) {
        throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی دابینکەر');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'دابینکەر بە سەرکەوتوویی سڕایەوە'
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