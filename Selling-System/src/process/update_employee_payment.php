<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get JSON data from request body
    $data = $_POST;
    
    // Validate required fields
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ناسنامەی پارەدان پێویستە');
    }
    
    if (!isset($data['employee_id']) || empty($data['employee_id'])) {
        throw new Exception('ناسنامەی کارمەند پێویستە');
    }
    
    if (!isset($data['payment_date']) || empty($data['payment_date'])) {
        throw new Exception('بەرواری پارەدان پێویستە');
    }
    
    if (!isset($data['amount']) || empty($data['amount'])) {
        throw new Exception('بڕی پارە پێویستە');
    }
    
    if (!isset($data['payment_type']) || empty($data['payment_type'])) {
        throw new Exception('جۆری پارەدان پێویستە');
    }
    
    $paymentId = (int)$data['id'];
    $employeeId = (int)$data['employee_id'];
    $paymentDate = $data['payment_date'];
    $amount = (float)$data['amount'];
    $paymentType = $data['payment_type'];
    $notes = $data['notes'] ?? '';
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if payment exists
    $checkStmt = $conn->prepare("SELECT id FROM employee_payments WHERE id = ?");
    $checkStmt->execute([$paymentId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('پارەدان نەدۆزرایەوە');
    }
    
    // Check if employee exists
    $checkEmployeeStmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
    $checkEmployeeStmt->execute([$employeeId]);
    
    if ($checkEmployeeStmt->rowCount() === 0) {
        throw new Exception('کارمەند نەدۆزرایەوە');
    }
    
    // Update payment record
    $updateStmt = $conn->prepare("
        UPDATE employee_payments 
        SET 
            employee_id = ?,
            payment_date = ?,
            amount = ?,
            payment_type = ?,
            notes = ?
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([
        $employeeId,
        $paymentDate,
        $amount,
        $paymentType,
        $notes,
        $paymentId
    ]);
    
    if (!$result) {
        throw new Exception('هەڵەیەک ڕوویدا لە نوێکردنەوەی پارەدان');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'پارەدان بە سەرکەوتوویی نوێ کرایەوە'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 