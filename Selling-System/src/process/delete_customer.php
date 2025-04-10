<?php
require_once '../config/database.php';

// Debug input
file_put_contents('../logs/debug.log', 'Received request: ' . file_get_contents('php://input') . "\n", FILE_APPEND);

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug decoded data
    file_put_contents('../logs/debug.log', 'Decoded data: ' . print_r($data, true) . "\n", FILE_APPEND);
    
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
    
    // Check if customer has associated sales or debt
    $checkDebtStmt = $conn->prepare("SELECT id FROM debt_transactions WHERE customer_id = ? LIMIT 1");
    $checkDebtStmt->execute([$customerId]);
    
    if ($checkDebtStmt->rowCount() > 0) {
        throw new Exception('ناتوانرێت ئەم کڕیارە بسڕێتەوە چونکە قەرزی هەیە یان فرۆشتنی بۆ تۆمارکراوە');
    }
    
    // Delete customer
    $deleteStmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $result = $deleteStmt->execute([$customerId]);
    
    if (!$result) {
        throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی کڕیار');
    }
    
    // Debug success
    file_put_contents('../logs/debug.log', 'Successfully deleted customer ID: ' . $customerId . "\n", FILE_APPEND);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'کڕیار بە سەرکەوتوویی سڕایەوە'
    ]);
    
} catch (Exception $e) {
    // Debug error
    file_put_contents('../logs/debug.log', 'Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 