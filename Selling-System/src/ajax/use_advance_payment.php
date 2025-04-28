<?php


// Include database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $saleId = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $invoiceNumber = isset($_POST['invoice_number']) ? $_POST['invoice_number'] : '';
    
    // Validate data
    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کڕیار نادروستە.']);
        exit;
    }
    
    if ($saleId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی فرۆشتن نادروستە.']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر.']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get customer data to check advance payment balance
        $customerQuery = "SELECT debit_on_business FROM customers WHERE id = :customer_id";
        $customerStmt = $conn->prepare($customerQuery);
        $customerStmt->bindParam(':customer_id', $customerId);
        $customerStmt->execute();
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new PDOException('کڕیار نەدۆزرایەوە.');
        }
        
        // Check if customer has advance payment (negative debit_on_business)
        if ($customer['debit_on_business'] >= 0) {
            throw new PDOException('کڕیار هیچ پارەی پێشەکی نییە.');
        }
        
        // Calculate available advance payment (as a positive number)
        $availableAdvance = abs($customer['debit_on_business']);
        
        // Determine how much to use
        $amountToUse = min($availableAdvance, $amount);
        
        // Insert transaction record for using the advance payment
        $notes = 'بەکارهێنانی پارەی پێشەکی بۆ پسوڵەی ' . $invoiceNumber;
        $transactionType = 'prepayment_used';
        $createdBy = 1; // Replace with actual user ID from session
        
        $sql = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, reference_id, notes, created_by) 
                VALUES (:customer_id, :amount, :transaction_type, :reference_id, :notes, :created_by)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':amount', $amountToUse);
        $stmt->bindParam(':transaction_type', $transactionType);
        $stmt->bindParam(':reference_id', $saleId);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $createdBy);
        $stmt->execute();
        
        // Update customer's balance (increase debit_on_business by the amount used)
        $sql = "UPDATE customers SET debit_on_business = debit_on_business + :amount WHERE id = :customer_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':amount', $amountToUse);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        
        // Update sale's remaining amount and paid amount
        $sql = "SELECT remaining_amount, paid_amount FROM sales WHERE id = :sale_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':sale_id', $saleId);
        $stmt->execute();
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sale) {
            throw new PDOException('فرۆشتن نەدۆزرایەوە.');
        }
        
        // Calculate new remaining amount and paid amount
        $newRemainingAmount = max(0, $sale['remaining_amount'] - $amountToUse);
        $newPaidAmount = $sale['paid_amount'] + $amountToUse;
        
        // Update the sale
        $sql = "UPDATE sales SET remaining_amount = :remaining_amount, paid_amount = :paid_amount WHERE id = :sale_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':remaining_amount', $newRemainingAmount);
        $stmt->bindParam(':paid_amount', $newPaidAmount);
        $stmt->bindParam(':sale_id', $saleId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'پارەی پێشەکی بە سەرکەوتوویی بەکارهێنرا.',
            'data' => [
                'amount_used' => $amountToUse,
                'remaining_advance' => $availableAdvance - $amountToUse,
                'new_remaining_amount' => $newRemainingAmount
            ]
        ]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'داواکاری نادروستە.']);
} 