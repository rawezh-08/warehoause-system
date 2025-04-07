<?php
// Include database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $transactionType = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Validate data
    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کڕیار نادروستە.']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر.']);
        exit;
    }
    
    if (!in_array($transactionType, ['payment', 'collection'])) {
        echo json_encode(['success' => false, 'message' => 'جۆری مامەڵە نادروستە.']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Insert debt transaction
        $sql = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, notes, created_by) 
                VALUES (:customer_id, :amount, :transaction_type, :notes, :created_by)";
        $stmt = $conn->prepare($sql);
        $createdBy = 1; // Replace with actual user ID from session
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':transaction_type', $transactionType);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $createdBy);
        $stmt->execute();
        
        // Update customer debt
        if ($transactionType === 'payment') {
            // Payment increases customer debt
            $sql = "UPDATE customers SET debit_on_business = debit_on_business + :amount WHERE id = :customer_id";
        } else { // collection
            // Collection decreases customer debt
            $sql = "UPDATE customers SET debit_on_business = debit_on_business - :amount WHERE id = :customer_id";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'مامەڵەی قەرز بە سەرکەوتوویی تۆمارکرا.']);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'داواکاری نادروستە.']);
} 