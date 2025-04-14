<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['supplier_id']) || !isset($data['amount'])) {
        throw new Exception('داتای پێویست نەنێردراوە');
    }
    
    $supplierId = $data['supplier_id'];
    $amount = floatval($data['amount']);
    $notes = $data['notes'] ?? '';
    
    // Validate amount
    if ($amount <= 0) {
        throw new Exception('بڕی پارە دەبێت گەورەتر بێت لە سفر');
    }
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Get current supplier debt
        $query = "SELECT debt_on_myself FROM suppliers WHERE id = :supplier_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->execute();
        $currentDebt = $stmt->fetchColumn();
        $stmt->closeCursor(); // Close the cursor to avoid PDO issues
        
        // Calculate payment breakdown
        $paymentAmount = 0;
        $advanceAmount = 0;
        
        if ($amount > $currentDebt) {
            $paymentAmount = $currentDebt;
            $advanceAmount = $amount - $currentDebt;
        } else {
            $paymentAmount = $amount;
            $advanceAmount = 0;
        }
        
        // Process debt payment if there is any
        if ($paymentAmount > 0) {
            // 1. Record in transactions
            $query = "INSERT INTO supplier_debt_transactions (
                            supplier_id, amount, transaction_type, notes, created_by
                        ) VALUES (
                            :supplier_id, :amount, 'payment', :notes, :created_by
                        )";
            $stmt = $db->prepare($query);
            $debtNotes = $notes . ($advanceAmount > 0 ? ' (پارەدانی قەرز)' : '');
            $createdBy = 1; // TODO: Get from session
            
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->bindParam(':amount', $paymentAmount);
            $stmt->bindParam(':notes', $debtNotes);
            $stmt->bindParam(':created_by', $createdBy);
            $stmt->execute();
            $stmt->closeCursor();
            
            // 2. Update supplier debt
            $query = "UPDATE suppliers 
                      SET debt_on_myself = debt_on_myself - :payment_amount
                      WHERE id = :supplier_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':payment_amount', $paymentAmount);
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->execute();
            $stmt->closeCursor();
        }
        
        // Process advance payment if there is any
        if ($advanceAmount > 0) {
            // 1. Record in transactions
            $query = "INSERT INTO supplier_debt_transactions (
                            supplier_id, amount, transaction_type, notes, created_by
                        ) VALUES (
                            :supplier_id, :amount, 'advance_payment', :notes, :created_by
                        )";
            $stmt = $db->prepare($query);
            $advanceNotes = $notes . ' (پارەی پێشەکی)';
            $createdBy = 1; // TODO: Get from session
            
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->bindParam(':amount', $advanceAmount);
            $stmt->bindParam(':notes', $advanceNotes);
            $stmt->bindParam(':created_by', $createdBy);
            $stmt->execute();
            $stmt->closeCursor();
            
            // 2. Update supplier advance balance
            $query = "UPDATE suppliers 
                      SET debt_on_supplier = debt_on_supplier + :advance_amount
                      WHERE id = :supplier_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':advance_amount', $advanceAmount);
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->execute();
            $stmt->closeCursor();
        }
        
        // Commit transaction
        $db->commit();
        
        // Prepare success message
        $message = '';
        if ($paymentAmount > 0) {
            $message .= sprintf('پارەدانی قەرز: %s دینار', number_format($paymentAmount));
        }
        if ($advanceAmount > 0) {
            if ($message) $message .= "\n";
            $message .= sprintf('پارەی پێشەکی: %s دینار', number_format($advanceAmount));
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => $message ?: 'پارەدان بە سەرکەوتوویی تۆمارکرا',
            'data' => [
                'debt_payment' => $paymentAmount,
                'advance_payment' => $advanceAmount
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 