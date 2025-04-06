<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Get invoice number and receipt type from POST data
$invoice_number = $_POST['invoice_number'] ?? '';
$receipt_type = $_POST['receipt_type'] ?? '';

if (empty($invoice_number) || empty($receipt_type)) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Check in the appropriate table based on receipt type
    $table = '';
    switch ($receipt_type) {
        case 'selling':
            $table = 'sales';
            break;
        case 'buying':
            $table = 'purchases';
            break;
        default:
            echo json_encode(['error' => 'Invalid receipt type']);
            exit;
    }
    
    // Check if invoice number exists
    $query = "SELECT COUNT(*) as count FROM $table WHERE invoice_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$invoice_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'exists' => $result['count'] > 0
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 