<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get the receipt type from the request
    $receipt_type = isset($_GET['type']) ? $_GET['type'] : 'selling';
    
    // Get the last invoice number for this type
    $sql = "SELECT invoice_number FROM sales ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If there's no previous invoice number, start with A-0001
    if (!$result) {
        $next_number = 'A-0001';
    } else {
        // Extract the number part and increment it
        $last_number = $result['invoice_number'];
        if (preg_match('/A-(\d+)/', $last_number, $matches)) {
            $num = intval($matches[1]);
            $next_num = $num + 1;
            $next_number = 'A-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        } else {
            // If the format doesn't match, start with A-0001
            $next_number = 'A-0001';
        }
    }
    
    echo json_encode([
        'success' => true,
        'invoice_number' => $next_number
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 