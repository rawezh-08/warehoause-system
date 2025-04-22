<?php

// Include database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $supplierId = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $advanceDate = isset($_POST['advance_date']) ? $_POST['advance_date'] : date('Y-m-d');
    
    // Validate data
    if ($supplierId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی دابینکەر نادروستە.']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر.']);
        exit;
    }
    
    // Prepare notes as JSON to include payment method
    $notesData = [
        'payment_method' => $paymentMethod,
        'notes' => $notes,
        'advance_date' => $advanceDate
    ];
    $jsonNotes = json_encode($notesData);
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Add the supplier advance payment transaction
        $sql = "INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, notes, created_by) 
                VALUES (:supplier_id, :amount, 'supplier_advance', :notes, :created_by)";
        $stmt = $conn->prepare($sql);
        $createdBy = 1; // Replace with actual user ID from session
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':notes', $jsonNotes);
        $stmt->bindParam(':created_by', $createdBy);
        $stmt->execute();
        
        $transactionId = $conn->lastInsertId();
        
        // Update supplier's debt_on_supplier (increase since we're giving them money)
        $sql = "UPDATE suppliers SET debt_on_supplier = debt_on_supplier + :amount WHERE id = :supplier_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'پارەی پێشەکی بە سەرکەوتوویی تۆمارکرا.',
            'transaction_id' => $transactionId
        ]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'داواکاری نادروستە.']);
} 