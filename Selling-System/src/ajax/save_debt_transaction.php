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
    $referenceId = isset($_POST['reference_id']) ? intval($_POST['reference_id']) : null;
    
    // Validate data
    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کڕیار نادروستە.']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر.']);
        exit;
    }
    
    if (!in_array($transactionType, ['payment', 'collection', 'advance_payment', 'prepayment_used'])) {
        echo json_encode(['success' => false, 'message' => 'جۆری مامەڵە نادروستە.']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get current customer data to check balances
        $customerQuery = "SELECT debit_on_business FROM customers WHERE id = :customer_id";
        $customerStmt = $conn->prepare($customerQuery);
        $customerStmt->bindParam(':customer_id', $customerId);
        $customerStmt->execute();
        $customerData = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customerData) {
            throw new PDOException('کڕیار نەدۆزرایەوە.');
        }
        
        $currentDebt = $customerData['debit_on_business'];
        
        // Additional validation: Check if customer has debt when trying to make advance payment
        if ($transactionType === 'advance_payment' && $currentDebt > 0) {
            throw new PDOException('ناتوانیت پارەی پێشەکی زیاد بکەیت، چونکە ئەم کڕیارە قەرزی لەسەرە. تکایە سەرەتا قەرزەکەی وەربگرەوە.');
        }
        
        // Insert debt transaction
        $sql = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, reference_id, notes, created_by) 
                VALUES (:customer_id, :amount, :transaction_type, :reference_id, :notes, :created_by)";
        $stmt = $conn->prepare($sql);
        $createdBy = 1; // Replace with actual user ID from session
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':transaction_type', $transactionType);
        $stmt->bindParam(':reference_id', $referenceId);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $createdBy);
        $stmt->execute();
        
        // Update customer debt based on transaction type
        if ($transactionType === 'payment') {
            // Payment increases customer debt
            $sql = "UPDATE customers SET debit_on_business = debit_on_business + :amount WHERE id = :customer_id";
        } else if ($transactionType === 'collection') { 
            // Collection decreases customer debt
            $sql = "UPDATE customers SET debit_on_business = debit_on_business - :amount WHERE id = :customer_id";
        } else if ($transactionType === 'advance_payment') {
            // Advance payment makes the debt negative (customer has credit)
            $sql = "UPDATE customers SET debit_on_business = debit_on_business - :amount WHERE id = :customer_id";
        } else if ($transactionType === 'prepayment_used') {
            // When customer uses their advance payment for a purchase
            // This increases their debt (reduces the negative balance)
            $sql = "UPDATE customers SET debit_on_business = debit_on_business + :amount WHERE id = :customer_id";
            
            // Validate that customer has enough advance payment
            if ($currentDebt >= 0) {
                throw new PDOException('کڕیار هیچ پارەی پێشەکی نییە.');
            }
            
            if (abs($currentDebt) < $amount) {
                throw new PDOException('بڕی پارەی پێشەکی کافی نییە.');
            }
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Prepare appropriate success message based on transaction type
        $successMessage = '';
        switch ($transactionType) {
            case 'payment':
                $successMessage = 'قەرز بە سەرکەوتوویی تۆمارکرا.';
                break;
            case 'collection':
                $successMessage = 'گەڕاندنەوەی قەرز بە سەرکەوتوویی تۆمارکرا.';
                break;
            case 'advance_payment':
                $successMessage = 'پارەی پێشەکی بە سەرکەوتوویی تۆمارکرا.';
                break;
            case 'prepayment_used':
                $successMessage = 'بەکارهێنانی پارەی پێشەکی بە سەرکەوتوویی تۆمارکرا.';
                break;
            default:
                $successMessage = 'مامەڵەی قەرز بە سەرکەوتوویی تۆمارکرا.';
        }
        
        echo json_encode(['success' => true, 'message' => $successMessage]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'داواکاری نادروستە.']);
} 