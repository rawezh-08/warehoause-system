<?php
// save_supplier_debt_transaction.php - AJAX handler for supplier debt payments

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'هەڵەی میتۆدی داواکاری']);
    exit;
}

// Get posted data
$supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$transactionDate = isset($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d');
$transactionType = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : '';
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Validate data
if (!$supplierId) {
    echo json_encode(['success' => false, 'message' => 'ئایدی دابینکەر نادروستە']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'تکایە بڕێکی دروست داخل بکە']);
    exit;
}

if (!in_array($transactionType, ['payment'])) {
    echo json_encode(['success' => false, 'message' => 'جۆری مامەڵە نادروستە']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Get supplier current debt
    $stmt = $conn->prepare("SELECT debt_on_myself FROM suppliers WHERE id = :supplier_id");
    $stmt->bindParam(':supplier_id', $supplierId);
    $stmt->execute();
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        throw new Exception('دابینکەر نەدۆزرایەوە');
    }
    
    $currentDebt = $supplier['debt_on_myself'];
    
    // Check if payment amount is greater than debt
    if ($amount > $currentDebt) {
        throw new Exception('بڕی پارەی پێدراو نابێت لە کۆی قەرز زیاتر بێت');
    }
    
    // Insert debt transaction record
    $stmt = $conn->prepare("
        INSERT INTO supplier_debt_transactions 
        (supplier_id, amount, transaction_type, notes) 
        VALUES 
        (:supplier_id, :amount, :transaction_type, :notes)
    ");
    
    $stmt->bindParam(':supplier_id', $supplierId);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':transaction_type', $transactionType);
    $stmt->bindParam(':notes', $notes);
    
    if (!$stmt->execute()) {
        throw new Exception('هەڵە لە تۆمارکردنی مامەڵەی قەرز');
    }
    
    $transactionId = $conn->lastInsertId();
    
    // Update supplier debt
    $newDebt = $currentDebt - $amount;
    $stmt = $conn->prepare("UPDATE suppliers SET debt_on_myself = :new_debt WHERE id = :supplier_id");
    $stmt->bindParam(':new_debt', $newDebt);
    $stmt->bindParam(':supplier_id', $supplierId);
    
    if (!$stmt->execute()) {
        throw new Exception('هەڵە لە نوێکردنەوەی قەرزی دابینکەر');
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'پارەدانی قەرز بە سەرکەوتوویی تۆمارکرا',
        'transaction_id' => $transactionId,
        'new_debt' => $newDebt
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the error for debugging
    error_log("Error in save_supplier_debt_transaction.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'details' => 'هەڵەیەک ڕوویدا لە کاتی تۆمارکردنی پارەدانی قەرز'
    ]);
} 