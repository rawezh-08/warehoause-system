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
        $query = "SELECT debt_on_supplier FROM suppliers WHERE id = :supplier_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->execute();
        
        $currentDebt = $stmt->fetchColumn();
        
        if ($currentDebt < $amount) {
            throw new Exception('بڕی پارەی داواکراو زیاترە لە قەرزی دابینکەر');
        }
        
        // Call the stored procedure to handle the payment
        $query = "CALL handle_supplier_payment(:supplier_id, :amount, :notes, :created_by)";
        $stmt = $db->prepare($query);
        
        $createdBy = 1; // TODO: Get from session
        
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $createdBy);
        
        $stmt->execute();
        $stmt->closeCursor(); // Close the cursor after stored procedure execution
        
        // Commit transaction
        $db->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'پارەدان بە سەرکەوتوویی تۆمارکرا'
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