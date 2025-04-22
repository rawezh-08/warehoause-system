<?php
// Include authentication check


require_once '../config/database.php';

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('داتای نادروست');
    }
    
    // Validate required fields
    if (empty($data['id']) || empty($data['name']) || empty($data['phone1'])) {
        throw new Exception('تکایە هەموو خانە پێویستەکان پڕ بکەوە');
    }
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Prepare and execute update query
    $stmt = $conn->prepare("
        UPDATE suppliers 
        SET name = ?, 
            phone1 = ?, 
            phone2 = ?, 
            debt_on_myself = ?, 
            notes = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $data['name'],
        $data['phone1'],
        $data['phone2'] ?? null,
        $data['debt_on_myself'] ?? 0,
        $data['notes'] ?? null,
        $data['id']
    ]);
    
    if (!$result) {
        throw new Exception('هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاریەکان');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'زانیاریەکان بە سەرکەوتوویی نوێ کرانەوە'
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