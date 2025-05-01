<?php
// Include necessary files
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get the latest receipt number from the database
    $stmt = $conn->query("
        SELECT invoice_number 
        FROM receipts 
        WHERE invoice_number LIKE 'A-%' 
        ORDER BY CAST(SUBSTRING(invoice_number, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    $lastNumber = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastNumber && !empty($lastNumber['invoice_number'])) {
        // Extract the number part
        $parts = explode('-', $lastNumber['invoice_number']);
        if (count($parts) == 2 && is_numeric($parts[1])) {
            // Increment the number
            $nextNumber = intval($parts[1]) + 1;
            $nextReceiptNumber = 'A-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        } else {
            // If format is unexpected, start from A-0001
            $nextReceiptNumber = 'A-0001';
        }
    } else {
        // If no receipt numbers exist, start from A-0001
        $nextReceiptNumber = 'A-0001';
    }
    
    // Also check for drafts (they might have higher numbers)
    $stmt = $conn->query("
        SELECT invoice_number 
        FROM receipt_drafts 
        WHERE invoice_number LIKE 'A-%' 
        ORDER BY CAST(SUBSTRING(invoice_number, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    $lastDraftNumber = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastDraftNumber && !empty($lastDraftNumber['invoice_number'])) {
        // Extract the number part
        $parts = explode('-', $lastDraftNumber['invoice_number']);
        if (count($parts) == 2 && is_numeric($parts[1])) {
            // Check if draft number is higher than regular receipt number
            $draftNumber = intval($parts[1]) + 1;
            $draftReceiptNumber = 'A-' . str_pad($draftNumber, 4, '0', STR_PAD_LEFT);
            
            // Use the higher number
            if ($draftNumber > intval(substr($nextReceiptNumber, 2))) {
                $nextReceiptNumber = $draftReceiptNumber;
            }
        }
    }
    
    // Return the next receipt number
    echo json_encode([
        'success' => true,
        'next_number' => $nextReceiptNumber
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 