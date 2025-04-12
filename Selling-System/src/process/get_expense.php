<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ناسنامەی خەرجی پێویستە');
    }
    
    $expenseId = (int)$_GET['id'];
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Fetch expense details
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$expenseId]);
    
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$expense) {
        throw new Exception('خەرجی نەدۆزرایەوە');
    }
    
    // Return expense data as JSON
    echo json_encode([
        'success' => true,
        'expense' => $expense
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 