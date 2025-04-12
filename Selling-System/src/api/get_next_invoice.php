<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get the receipt type from the request
    $receipt_type = isset($_GET['type']) ? $_GET['type'] : 'selling';
    
    // Set the table and prefix based on receipt type
    if ($receipt_type === 'selling') {
        $table = 'sales';
        $prefix = 'A';
    } else if ($receipt_type === 'buying') {
        $table = 'purchases';
        $prefix = 'B';
    } else if ($receipt_type === 'wasting') {
        $table = 'waste_items';
        $prefix = 'W';
    } else {
        throw new Exception('Invalid receipt type');
    }
    
    // Get the last invoice number for this type
    $sql = "SELECT invoice_number FROM $table ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If there's no previous invoice number, start with prefix-0001
    if (!$result) {
        $next_number = $prefix . '-0001';
    } else {
        // Extract the number part and increment it
        $last_number = $result['invoice_number'];
        if (preg_match('/' . $prefix . '-(\d+)/', $last_number, $matches)) {
            $num = intval($matches[1]);
            $next_num = $num + 1;
            $next_number = $prefix . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        } else {
            // If the format doesn't match, start with prefix-0001
            $next_number = $prefix . '-0001';
        }
    }
    
    echo json_encode([
        'success' => true,
        'invoice_number' => $next_number
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 