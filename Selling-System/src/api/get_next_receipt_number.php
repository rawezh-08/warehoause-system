<?php
// Include necessary files
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Use UNION to get receipt numbers from both tables in a single query
    $stmt = $conn->query("
        SELECT invoice_number 
        FROM (
            SELECT invoice_number FROM receipts WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$'
            UNION
            SELECT invoice_number FROM receipt_drafts WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$'
        ) AS combined_receipts
        ORDER BY LEFT(invoice_number, 1), CAST(SUBSTRING(invoice_number, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    $lastNumber = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Initialize with the default first receipt number
    $nextReceiptNumber = 'A-0001';
    
    if ($lastNumber && !empty($lastNumber['invoice_number'])) {
        // Extract the parts (letter and number)
        $parts = explode('-', $lastNumber['invoice_number']);
        
        if (count($parts) == 2 && strlen($parts[0]) == 1 && is_numeric($parts[1])) {
            $currentLetter = $parts[0];
            $currentNumber = intval($parts[1]);
            
            // Increment the number
            $nextNumber = $currentNumber + 1;
            
            // Check if we need to move to the next letter
            if ($nextNumber > 9999) {
                // Move to the next letter
                $nextLetter = chr(ord($currentLetter) + 1);
                // If we go beyond 'Z', wrap around to 'A'
                if (ord($nextLetter) > ord('Z')) {
                    $nextLetter = 'A';
                }
                $nextReceiptNumber = $nextLetter . '-0001';
            } else {
                // Stay with current letter, just increment number
                $nextReceiptNumber = $currentLetter . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        }
    }
    
    // Make a final check to ensure this invoice number doesn't already exist
    $checkStmt = $conn->prepare("
        SELECT 1 
        FROM (
            SELECT invoice_number FROM receipts
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
        $checkLetter = substr($nextReceiptNumber, 0, 1);
        $checkNumber = intval(substr($nextReceiptNumber, 2));
        
        while (!$found && $attempts < $maxAttempts) {
            $checkNumber++;
            
            // Move to next letter if needed
            if ($checkNumber > 9999) {
                $checkLetter = chr(ord($checkLetter) + 1);
                if (ord($checkLetter) > ord('Z')) {
                    $checkLetter = 'A';
                }
                $checkNumber = 1;
            }
            
            $candidateNumber = $checkLetter . '-' . str_pad($checkNumber, 4, '0', STR_PAD_LEFT);
            
            $checkStmt->execute([$candidateNumber]);
            if ($checkStmt->rowCount() === 0) {
                $nextReceiptNumber = $candidateNumber;
                $found = true;
            }
            
            $attempts++;
        }
        
        // If we couldn't find a unique number after many attempts, return an error
        if (!$found) {
            echo json_encode([
                'success' => false,
                'message' => 'هەڵە: نەتوانرا ژمارەیەکی یەکتای پسووڵە بدۆزرێتەوە دوای ' . $maxAttempts . ' هەوڵدان'
            ]);
            exit;
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