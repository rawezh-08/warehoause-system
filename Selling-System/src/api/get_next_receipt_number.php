<?php
// Include necessary files
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get the highest receipt number from both sales and receipt_drafts tables
    $stmt = $conn->query("
        SELECT MAX(CAST(SUBSTRING(invoice_number, 3) AS UNSIGNED)) as max_number 
        FROM (
            SELECT invoice_number FROM sales WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$'
            UNION
            SELECT invoice_number FROM receipt_drafts WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$'
        ) AS combined_receipts
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $maxNumber = $result['max_number'] ?? 0;
    
    // Increment the number
    $nextNumber = $maxNumber + 1;
    
    // Format the next receipt number
    $nextReceiptNumber = 'A-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    
    // Make a final check to ensure this invoice number doesn't already exist
    $checkStmt = $conn->prepare("
        SELECT 1 
        FROM (
            SELECT invoice_number FROM sales
            UNION
            SELECT invoice_number FROM receipt_drafts
        ) AS all_receipts
        WHERE invoice_number = ?
    ");
    $checkStmt->execute([$nextReceiptNumber]);
    
    // If this receipt number already exists, find the next available one
    if ($checkStmt->rowCount() > 0) {
        $found = false;
        $attempts = 0;
        $maxAttempts = 1000; // Safety limit
        
        while (!$found && $attempts < $maxAttempts) {
            $nextNumber++;
            $candidateNumber = 'A-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            $checkStmt->execute([$candidateNumber]);
            if ($checkStmt->rowCount() === 0) {
                $nextReceiptNumber = $candidateNumber;
                $found = true;
            }
            
            $attempts++;
        }
        
        // If we couldn't find a unique number after many attempts, return an error
        if (!$found) {
            throw new Exception('هەڵە: نەتوانرا ژمارەیەکی یەکتای پسووڵە بدۆزرێتەوە دوای ' . $maxAttempts . ' هەوڵدان');
        }
    }
    
    // Return the next receipt number
    echo json_encode([
        'success' => true,
        'next_number' => $nextReceiptNumber
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 