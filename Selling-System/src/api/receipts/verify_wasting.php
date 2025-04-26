<?php
require_once '../../includes/auth.php';
require_once '../../controllers/receipts/WastingReceiptsController.php';

header('Content-Type: application/json');

try {
    // Get wasting ID from POST data
    $wasting_id = isset($_POST['wasting_id']) ? intval($_POST['wasting_id']) : 0;
    
    if ($wasting_id <= 0) {
        throw new Exception('IDی بەفیڕۆچوو نادروستە');
    }
    
    // Debug: Log the wasting ID
    error_log("Checking wasting ID: " . $wasting_id);
    
    // Check if wasting exists
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM wastings 
        WHERE id = ?
    ");
    $stmt->execute([$wasting_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Log the query result
    error_log("Query result: " . print_r($result, true));
    
    // Return result
    echo json_encode([
        'success' => true,
        'exists' => $result['count'] > 0,
        'wasting_id' => $wasting_id
    ]);
    
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Error in verify_wasting.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 