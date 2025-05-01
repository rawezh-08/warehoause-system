<?php
// Include necessary files
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get the latest receipt number from the database
    $stmt = $conn->query("
        SELECT invoice_number 
        FROM receipts 
        WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$' 
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
    
    // Also check for drafts (they might have higher numbers)
    $stmt = $conn->query("
        SELECT invoice_number 
        FROM receipt_drafts 
        WHERE invoice_number REGEXP '^[A-Z]-[0-9]+$' 
        ORDER BY LEFT(invoice_number, 1), CAST(SUBSTRING(invoice_number, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    
    $lastDraftNumber = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastDraftNumber && !empty($lastDraftNumber['invoice_number'])) {
        // Extract the parts (letter and number)
        $parts = explode('-', $lastDraftNumber['invoice_number']);
        
        if (count($parts) == 2 && strlen($parts[0]) == 1 && is_numeric($parts[1])) {
            $draftLetter = $parts[0];
            $draftNumber = intval($parts[1]);
            
            // Increment the number
            $nextDraftNumber = $draftNumber + 1;
            
            // Check if we need to move to the next letter
            if ($nextDraftNumber > 9999) {
                $nextDraftLetter = chr(ord($draftLetter) + 1);
                // If we go beyond 'Z', wrap around to 'A'
                if (ord($nextDraftLetter) > ord('Z')) {
                    $nextDraftLetter = 'A';
                }
                $draftReceiptNumber = $nextDraftLetter . '-0001';
            } else {
                $draftReceiptNumber = $draftLetter . '-' . str_pad($nextDraftNumber, 4, '0', STR_PAD_LEFT);
            }
            
            // Compare the two receipt numbers to find the higher one
            $receiptParts = explode('-', $nextReceiptNumber);
            $draftParts = explode('-', $draftReceiptNumber);
            
            $receiptLetter = $receiptParts[0];
            $draftLetter = $draftParts[0];
            
            // Compare letters first
            if (ord($draftLetter) > ord($receiptLetter)) {
                $nextReceiptNumber = $draftReceiptNumber;
            } 
            // If letters are the same, compare numbers
            elseif ($draftLetter == $receiptLetter) {
                $receiptNumber = intval($receiptParts[1]);
                $draftNumber = intval($draftParts[1]);
                
                if ($draftNumber > $receiptNumber) {
                    $nextReceiptNumber = $draftReceiptNumber;
                }
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