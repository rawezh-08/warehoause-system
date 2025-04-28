<?php


require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get data from request body
    $data = $_POST;
    
    // Validate required fields
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ناسنامەی خەرجی پێویستە');
    }
    
    if (!isset($data['expense_date']) || empty($data['expense_date'])) {
        throw new Exception('بەرواری خەرجی پێویستە');
    }
    
    if (!isset($data['amount']) || empty($data['amount'])) {
        throw new Exception('بڕی پارە پێویستە');
    }
    
    $expenseId = (int)$data['id'];
    $expenseDate = $data['expense_date'];
    $amount = (float)$data['amount'];
    $notes = $data['notes'] ?? '';
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if expense exists
    $checkStmt = $conn->prepare("SELECT id FROM expenses WHERE id = ?");
    $checkStmt->execute([$expenseId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('خەرجی نەدۆزرایەوە');
    }
    
    // Update expense record - note that expenses table only has amount, expense_date, notes fields
    $updateStmt = $conn->prepare("
        UPDATE expenses 
        SET 
            expense_date = ?,
            amount = ?,
            notes = ?
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([
        $expenseDate,
        $amount,
        $notes,
        $expenseId
    ]);
    
    if (!$result) {
        throw new Exception('هەڵەیەک ڕوویدا لە نوێکردنەوەی خەرجی');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'خەرجی بە سەرکەوتوویی نوێ کرایەوە'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 