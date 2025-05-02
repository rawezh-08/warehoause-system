<?php
// Include necessary files
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // First, get the last used receipt number from sales table
    $stmt = $conn->query("
        SELECT invoice_number 
        FROM sales 
        WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$'
        ORDER BY CAST(SUBSTRING(invoice_number, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    $lastSale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Then get the last used receipt number from receipt_drafts table
    $stmt = $conn->query("
        SELECT invoice_number 
        FROM receipt_drafts 
        WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$'
        ORDER BY CAST(SUBSTRING(invoice_number, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    $lastDraft = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Initialize variables
    $maxNumber = 0;
    
    // Check sales table
    if ($lastSale && !empty($lastSale['invoice_number'])) {
        $number = intval(substr($lastSale['invoice_number'], 2));
        $maxNumber = max($maxNumber, $number);
    }
    
    // Check receipt_drafts table
    if ($lastDraft && !empty($lastDraft['invoice_number'])) {
        $number = intval(substr($lastDraft['invoice_number'], 2));
        $maxNumber = max($maxNumber, $number);
    }
    
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