<?php
// Include database connection
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $partnerName = sanitizeInput($_POST['partnerName']);
    $partnerPhone1 = sanitizeInput($_POST['partnerPhone1']);
    $partnerPhone2 = isset($_POST['partnerPhone2']) ? sanitizeInput($_POST['partnerPhone2']) : '';
    $partnerAddress = isset($_POST['partnerAddress']) ? sanitizeInput($_POST['partnerAddress']) : '';
    
    // Customer-related information
    $guarantorName = isset($_POST['partnerGuarantorName']) ? sanitizeInput($_POST['partnerGuarantorName']) : '';
    $guarantorPhone = isset($_POST['partnerGuarantorPhone']) ? sanitizeInput($_POST['partnerGuarantorPhone']) : '';
    $debitOnBusiness = isset($_POST['partnerDebitOnBusiness']) ? cleanNumber($_POST['partnerDebitOnBusiness']) : 0;
    $debtOnCustomer = isset($_POST['partnerDebtOnCustomer']) ? cleanNumber($_POST['partnerDebtOnCustomer']) : 0;
    
    // Supplier-related information
    $debtOnMyself = isset($_POST['partnerDebtOnMyself']) ? cleanNumber($_POST['partnerDebtOnMyself']) : 0;
    $debtOnSupplier = isset($_POST['partnerDebtOnSupplier']) ? cleanNumber($_POST['partnerDebtOnSupplier']) : 0;
    
    $notes = isset($_POST['partnerNotes']) ? sanitizeInput($_POST['partnerNotes']) : '';
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // 1. First, insert into customers table with is_business_partner flag
        $customerSql = "INSERT INTO customers (name, phone1, phone2, guarantor_name, guarantor_phone, address, 
                        debit_on_business, debt_on_customer, notes, is_business_partner) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $customerStmt = $pdo->prepare($customerSql);
        $customerStmt->execute([
            $partnerName, 
            $partnerPhone1, 
            $partnerPhone2, 
            $guarantorName, 
            $guarantorPhone, 
            $partnerAddress, 
            $debitOnBusiness, 
            $debtOnCustomer, 
            $notes
        ]);
        
        $customerId = $pdo->lastInsertId();
        
        // 2. Insert into suppliers table with is_business_partner flag and customer_id
        $supplierSql = "INSERT INTO suppliers (name, phone1, phone2, debt_on_myself, debt_on_supplier, notes, 
                        is_business_partner, customer_id) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
        
        $supplierStmt = $pdo->prepare($supplierSql);
        $supplierStmt->execute([
            $partnerName, 
            $partnerPhone1, 
            $partnerPhone2, 
            $debtOnMyself, 
            $debtOnSupplier, 
            $notes, 
            $customerId
        ]);
        
        $supplierId = $pdo->lastInsertId();
        
        // 3. Update the customer record with the supplier_id to establish the link
        $updateCustomerSql = "UPDATE customers SET supplier_id = ? WHERE id = ?";
        $updateCustomerStmt = $pdo->prepare($updateCustomerSql);
        $updateCustomerStmt->execute([$supplierId, $customerId]);
        
        // 4. Record initial debt transactions if there are any
        
        // Customer debt transaction (if any)
        if ($debitOnBusiness > 0) {
            $debtSql = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, notes) 
                        VALUES (?, ?, 'manual_adjustment', ?)";
            $debtStmt = $pdo->prepare($debtSql);
            $debtStmt->execute([$customerId, $debitOnBusiness, 'Initial debt for business partner: ' . $partnerName]);
        }
        
        if ($debtOnCustomer > 0) {
            $debtSql = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, notes) 
                        VALUES (?, ?, 'advance_payment', ?)";
            $debtStmt = $pdo->prepare($debtSql);
            $debtStmt->execute([$customerId, -$debtOnCustomer, 'Initial advance payment from business partner: ' . $partnerName]);
        }
        
        // Supplier debt transaction (if any)
        if ($debtOnMyself > 0) {
            $supplierDebtSql = "INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, notes) 
                                VALUES (?, ?, 'manual_adjustment', ?)";
            $supplierDebtStmt = $pdo->prepare($supplierDebtSql);
            $supplierDebtStmt->execute([$supplierId, $debtOnMyself, 'Initial debt to business partner: ' . $partnerName]);
        }
        
        if ($debtOnSupplier > 0) {
            $supplierDebtSql = "INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, notes) 
                                VALUES (?, ?, 'supplier_payment', ?)";
            $supplierDebtStmt = $pdo->prepare($supplierDebtSql);
            $supplierDebtStmt->execute([$supplierId, -$debtOnSupplier, 'Initial prepayment to business partner: ' . $partnerName]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect with success message
        $_SESSION['success_message'] = "هاوبەشی بازرگانی بە سەرکەوتوویی زیاد کرا";
        header("Location: ../views/admin/addStaff.php?tab=partner");
        exit();
        
    } catch (PDOException $e) {
        // Roll back the transaction if something failed
        $pdo->rollBack();
        $_SESSION['error_message'] = "هەڵە ڕوویدا: " . $e->getMessage();
        header("Location: ../views/admin/addStaff.php?tab=partner");
        exit();
    }
}

// Function to clean number inputs (remove commas, etc.)
function cleanNumber($input) {
    // Remove any non-numeric characters except for decimal point
    $input = preg_replace('/[^0-9.]/', '', $input);
    
    // If empty, return 0
    if (empty($input)) {
        return 0;
    }
    
    return (float) $input;
}

// Redirect if accessed directly without form submission
header("Location: ../views/admin/addStaff.php");
exit(); 