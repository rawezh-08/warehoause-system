<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ناسنامەی کڕیار نادروستە');
    }
    
    $customerId = (int)$data['id'];
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if customer exists
    $checkStmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
    $checkStmt->execute([$customerId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('کڕیار نەدۆزرایەوە');
    }
    
    // Check if customer has associated debts
    $checkDebtStmt = $conn->prepare("SELECT id FROM debt_transactions WHERE customer_id = ? LIMIT 1");
    $checkDebtStmt->execute([$customerId]);
    
    if ($checkDebtStmt->rowCount() > 0) {
        throw new Exception('ناتوانرێت ئەم کڕیارە بسڕێتەوە چونکە قەرزی هەیە');
    }
    
    // Check if customer has any sales
    $checkSalesStmt = $conn->prepare("SELECT id FROM sales WHERE customer_id = ? LIMIT 1");
    $checkSalesStmt->execute([$customerId]);
    
    if ($checkSalesStmt->rowCount() > 0) {
        throw new Exception('ناتوانرێت ئەم کڕیارە بسڕێتەوە چونکە کڕینی تۆمارکراوە، پێویستە سەرەتا کڕینەکانی بسڕیتەوە');
    }
    
    // Delete customer
    $deleteStmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $result = $deleteStmt->execute([$customerId]);
    
    if (!$result) {
        throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی کڕیار');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'کڕیار بە سەرکەوتوویی سڕایەوە'
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