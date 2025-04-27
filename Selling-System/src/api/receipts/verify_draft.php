<?php
require_once '../../includes/auth.php';
require_once '../../controllers/receipts/DraftReceiptsController.php';

header('Content-Type: application/json');

try {
    // Get receipt ID from POST data
    $receipt_id = isset($_POST['receipt_id']) ? intval($_POST['receipt_id']) : 0;
    
    if ($receipt_id <= 0) {
        throw new Exception('IDی ڕەشنووس نادروستە');
    }
    
    // Check if draft exists
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM sales 
        WHERE id = ? AND is_draft = 1
    ");
    $stmt->execute([$receipt_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return result
    echo json_encode([
        'success' => true,
        'exists' => $result['count'] > 0,
        'receipt_id' => $receipt_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 