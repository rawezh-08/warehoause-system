<?php

try {
    // Get wasting ID from GET data
    $wasting_id = isset($_GET['wasting_id']) ? intval($_GET['wasting_id']) : 0;
    
    if ($wasting_id <= 0) {
        throw new Exception('IDی بەفیڕۆچوو نادروستە');
    }
    
    // Debug: Log the wasting ID
    error_log("Getting wasting ID: " . $wasting_id);
    
    // Get wasting details with product information
    $stmt = $conn->prepare("
        SELECT w.*, p.name as product_name, p.barcode
        FROM wastings w
        JOIN products p ON w.product_id = p.id
        WHERE w.id = :id
    ");
    $stmt->bindParam(':id', $wasting_id);
    $stmt->execute();
    $wasting = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Log the wasting ID and result
    error_log("Getting wasting with ID: " . $wasting_id);
    error_log("Query result: " . ($wasting ? "Found" : "Not found"));
    
    if (!$wasting) {
        throw new Exception('بەفیڕۆچوو نەدۆزرایەوە');
    }
    
    // Return success with wasting data
    echo json_encode([
        'success' => true,
        'data' => $wasting
    ]);
    
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Error in get_wasting.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 