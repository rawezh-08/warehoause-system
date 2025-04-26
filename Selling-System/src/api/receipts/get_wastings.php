<?php

try {
    // Get all wastings with product details
    $stmt = $conn->prepare("
        SELECT w.*, p.name as product_name, p.barcode
        FROM wastings w
        JOIN products p ON w.product_id = p.id
        ORDER BY w.created_at DESC
    ");
    $stmt->execute();
    $wastings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the number of wastings found
    error_log("Found " . count($wastings) . " wastings");
    
    echo json_encode([
        'success' => true,
        'data' => $wastings
    ]);
    
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Error in get_wastings.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 