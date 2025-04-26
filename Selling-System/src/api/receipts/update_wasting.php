<?php

try {
    // Get wasting ID from POST data
    $wasting_id = isset($_POST['wasting_id']) ? intval($_POST['wasting_id']) : 0;
    
    if ($wasting_id <= 0) {
        throw new Exception('IDی بەفیڕۆچوو نادروستە');
    }
    
    // Debug: Log the wasting ID
    error_log("Updating wasting ID: " . $wasting_id);
    
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
    
    if ($result['count'] == 0) {
        throw new Exception('بەفیڕۆچوو نەدۆزرایەوە');
    }
    
    // Get updated data from POST
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Debug: Log the updated data
    error_log("Updated data - Quantity: " . $quantity . ", Notes: " . $notes);
    
    // Update wasting
    $stmt = $conn->prepare("
        UPDATE wastings 
        SET quantity = ?, notes = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$quantity, $notes, $wasting_id]);
    
    // Debug: Log the update result
    error_log("Update result: " . $stmt->rowCount() . " rows affected");
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'بەفیڕۆچوو بە سەرکەوتوویی نوێکرایەوە'
    ]);
    
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Error in update_wasting.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 