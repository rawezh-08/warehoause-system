<?php

// Include database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $transactionId = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Validate data
    if ($transactionId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی مامەڵە نادروستە.']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر.']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get current transaction details
        $sql = "SELECT * FROM debt_transactions WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $transactionId);
        $stmt->execute();
        $oldTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldTransaction) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'مامەڵەی قەرز نەدۆزرایەوە.']);
            exit;
        }
        
        // Calculate the difference for customer debt update
        $amountDiff = $amount - $oldTransaction['amount'];
        
        // Update the transaction
        $sql = "UPDATE debt_transactions SET amount = :amount, notes = :notes WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $transactionId);
        $stmt->execute();
        
        // Update customer debt if amount changed
        if ($amountDiff != 0) {
            if ($oldTransaction['transaction_type'] === 'payment' || $oldTransaction['transaction_type'] === 'purchase') {
                // For payment/purchase, an increase in amount increases debt
                $sql = "UPDATE customers SET debit_on_business = debit_on_business + :diff WHERE id = :customer_id";
            } else { // collection or sale
                // For collection/sale, an increase in amount decreases debt
                $sql = "UPDATE customers SET debit_on_business = debit_on_business - :diff WHERE id = :customer_id";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':diff', $amountDiff);
            $stmt->bindParam(':customer_id', $oldTransaction['customer_id']);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'مامەڵەی قەرز بە سەرکەوتوویی نوێکرایەوە.']);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'داواکاری نادروستە.']);
} 