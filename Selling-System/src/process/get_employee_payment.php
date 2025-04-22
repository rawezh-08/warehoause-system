<?php


require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ناسنامەی پارەدان پێویستە');
    }
    
    $paymentId = (int)$_GET['id'];
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Fetch payment details
    $stmt = $conn->prepare("SELECT * FROM employee_payments WHERE id = ?");
    $stmt->execute([$paymentId]);
    
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('پارەدان نەدۆزرایەوە');
    }
    
    // Return payment data as JSON
    echo json_encode([
        'success' => true,
        'payment' => $payment
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 