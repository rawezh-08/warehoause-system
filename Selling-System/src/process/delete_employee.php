<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ناسنامەی کارمەند نادروستە');
    }
    
    $employeeId = (int)$data['id'];
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if employee exists
    $checkStmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
    $checkStmt->execute([$employeeId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('کارمەندەکە نەدۆزرایەوە');
    }
    
    // Check if employee has associated payments
    $checkPaymentsStmt = $conn->prepare("SELECT id FROM employee_payments WHERE employee_id = ? LIMIT 1");
    $checkPaymentsStmt->execute([$employeeId]);
    
    if ($checkPaymentsStmt->rowCount() > 0) {
        throw new Exception('ناتوانرێت ئەم کارمەندە بسڕێتەوە چونکە پارەدانی بۆ تۆمارکراوە');
    }
    
    // Delete employee
    $deleteStmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $result = $deleteStmt->execute([$employeeId]);
    
    if (!$result) {
        throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی کارمەند');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'کارمەند بە سەرکەوتوویی سڕایەوە'
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