<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ناسنامەی کڕیار و دابینکەر نادروستە');
    }
    
    $partnerId = (int)$data['id'];
    $customerId = isset($data['customer_id']) ? (int)$data['customer_id'] : null;
    $supplierId = isset($data['supplier_id']) ? (int)$data['supplier_id'] : null;
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Check for customer transactions if customer ID is provided
    if ($customerId) {
        // Check for customer debt
        $checkCustomerDebtStmt = $conn->prepare("SELECT debit_on_business FROM customers WHERE id = ?");
        $checkCustomerDebtStmt->execute([$customerId]);
        $customerDebt = $checkCustomerDebtStmt->fetchColumn();
        
        if ($customerDebt > 0) {
            throw new Exception('ناتوانیت ئەم کڕیارە بسڕیتەوە چونکە قەرزی هەیە');
        }
        
        // Check for sales
        $checkSalesStmt = $conn->prepare("SELECT id FROM sales WHERE customer_id = ? LIMIT 1");
        $checkSalesStmt->execute([$customerId]);
        
        if ($checkSalesStmt->rowCount() > 0) {
            throw new Exception('ناتوانیت ئەم کڕیارە بسڕیتەوە چونکە فرۆشتنی بۆ تۆمارکراوە');
        }
        
        // Check for payments - using debt_transactions table instead of non-existent payments table
        $checkPaymentsStmt = $conn->prepare("SELECT id FROM debt_transactions WHERE customer_id = ? AND transaction_type = 'payment' LIMIT 1");
        $checkPaymentsStmt->execute([$customerId]);
        
        if ($checkPaymentsStmt->rowCount() > 0) {
            throw new Exception('ناتوانیت ئەم کڕیارە بسڕیتەوە چونکە پارەدانی بۆ تۆمارکراوە');
        }
    }
    
    // Check for supplier transactions if supplier ID is provided
    if ($supplierId) {
        // Check for supplier debt
        $checkSupplierDebtStmt = $conn->prepare("SELECT debt_on_myself FROM suppliers WHERE id = ?");
        $checkSupplierDebtStmt->execute([$supplierId]);
        $supplierDebt = $checkSupplierDebtStmt->fetchColumn();
        
        if ($supplierDebt > 0) {
            throw new Exception('ناتوانیت ئەم دابینکەرە بسڕیتەوە چونکە قەرزمان هەیە لەسەری');
        }
        
        // Check for purchases
        $checkPurchasesStmt = $conn->prepare("SELECT id FROM purchases WHERE supplier_id = ? LIMIT 1");
        $checkPurchasesStmt->execute([$supplierId]);
        
        if ($checkPurchasesStmt->rowCount() > 0) {
            throw new Exception('ناتوانیت ئەم دابینکەرە بسڕیتەوە چونکە کڕینی لێکراوە');
        }
        
        // Check for supplier payments - using supplier_debt_transactions table instead of non-existent supplier_payments table
        $checkSupplierPaymentsStmt = $conn->prepare("SELECT id FROM supplier_debt_transactions WHERE supplier_id = ? AND transaction_type IN ('payment', 'supplier_payment') LIMIT 1");
        $checkSupplierPaymentsStmt->execute([$supplierId]);
        
        if ($checkSupplierPaymentsStmt->rowCount() > 0) {
            throw new Exception('ناتوانیت ئەم دابینکەرە بسڕیتەوە چونکە پارەدانی بۆ تۆمارکراوە');
        }
    }
    
    // If we have both customer and supplier, need to update the is_business_partner flag to 0
    if ($customerId && $supplierId) {
        // Update customer and supplier to not be business partners
        $updateCustomerStmt = $conn->prepare("UPDATE customers SET is_business_partner = 0, supplier_id = NULL WHERE id = ?");
        $updateCustomerResult = $updateCustomerStmt->execute([$customerId]);
        
        $updateSupplierStmt = $conn->prepare("UPDATE suppliers SET is_business_partner = 0 WHERE id = ?");
        $updateSupplierResult = $updateSupplierStmt->execute([$supplierId]);
        
        if (!$updateCustomerResult || !$updateSupplierResult) {
            throw new Exception('هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاریەکان');
        }
    } elseif ($customerId) {
        // If only customer, delete customer
        $deleteCustomerStmt = $conn->prepare("DELETE FROM customers WHERE id = ? AND is_business_partner = 1");
        $deleteCustomerResult = $deleteCustomerStmt->execute([$customerId]);
        
        if (!$deleteCustomerResult) {
            throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی کڕیار');
        }
    } elseif ($supplierId) {
        // If only supplier, delete supplier
        $deleteSupplierStmt = $conn->prepare("DELETE FROM suppliers WHERE id = ? AND is_business_partner = 1");
        $deleteSupplierResult = $deleteSupplierStmt->execute([$supplierId]);
        
        if (!$deleteSupplierResult) {
            throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی دابینکەر');
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'کڕیار و دابینکەر بە سەرکەوتوویی سڕایەوە'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if an error occurred
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 