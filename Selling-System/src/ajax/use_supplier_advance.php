<?php
// Include database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $supplierId = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $purchaseId = isset($_POST['purchase_id']) ? intval($_POST['purchase_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $invoiceNumber = isset($_POST['invoice_number']) ? $_POST['invoice_number'] : '';
    
    // Validate data
    if ($supplierId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی دابینکەر نادروستە.']);
        exit;
    }
    
    if ($purchaseId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کڕین نادروستە.']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر.']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get supplier data to check advance payment balance
        $supplierQuery = "SELECT debt_on_supplier FROM suppliers WHERE id = :supplier_id";
        $supplierStmt = $conn->prepare($supplierQuery);
        $supplierStmt->bindParam(':supplier_id', $supplierId);
        $supplierStmt->execute();
        $supplier = $supplierStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$supplier) {
            throw new PDOException('دابینکەر نەدۆزرایەوە.');
        }
        
        // Check if we have advance payment to this supplier
        if ($supplier['debt_on_supplier'] <= 0) {
            throw new PDOException('هیچ پارەی پێشەکی بۆ ئەم دابینکەرە نییە.');
        }
        
        // Calculate available advance payment
        $availableAdvance = $supplier['debt_on_supplier'];
        
        // Determine how much to use
        $amountToUse = min($availableAdvance, $amount);
        
        // Insert transaction record for using the advance payment
        $notes = 'بەکارهێنانی پارەی پێشەکی بۆ کڕینی ژمارە ' . $invoiceNumber;
        $transactionType = 'advance_used';
        $createdBy = 1; // Replace with actual user ID from session
        
        $sql = "INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, reference_id, notes, created_by) 
                VALUES (:supplier_id, :amount, :transaction_type, :reference_id, :notes, :created_by)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->bindParam(':amount', $amountToUse);
        $stmt->bindParam(':transaction_type', $transactionType);
        $stmt->bindParam(':reference_id', $purchaseId);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $createdBy);
        $stmt->execute();
        
        // Update supplier's advance payment balance (decrease debt_on_supplier by the amount used)
        $sql = "UPDATE suppliers SET debt_on_supplier = debt_on_supplier - :amount WHERE id = :supplier_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':amount', $amountToUse);
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->execute();
        
        // Update purchase's remaining amount and paid amount
        $sql = "SELECT remaining_amount, paid_amount FROM purchases WHERE id = :purchase_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':purchase_id', $purchaseId);
        $stmt->execute();
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchase) {
            throw new PDOException('کڕین نەدۆزرایەوە.');
        }
        
        // Calculate new remaining amount and paid amount
        $newRemainingAmount = max(0, $purchase['remaining_amount'] - $amountToUse);
        $newPaidAmount = $purchase['paid_amount'] + $amountToUse;
        
        // Update the purchase
        $sql = "UPDATE purchases SET remaining_amount = :remaining_amount, paid_amount = :paid_amount WHERE id = :purchase_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':remaining_amount', $newRemainingAmount);
        $stmt->bindParam(':paid_amount', $newPaidAmount);
        $stmt->bindParam(':purchase_id', $purchaseId);
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