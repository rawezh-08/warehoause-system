<?php
// Include database connection
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get common input data
    $name = $_POST['partnerName'];
    $phone1 = $_POST['partnerPhone'];
    $phone2 = isset($_POST['partnerPhone2']) ? $_POST['partnerPhone2'] : '';
    $notes = isset($_POST['partnerNotes']) ? $_POST['partnerNotes'] : '';
    $address = isset($_POST['partnerAddress']) ? $_POST['partnerAddress'] : '';

    // Customer specific data
    $guarantorName = isset($_POST['guarantorName']) ? $_POST['guarantorName'] : '';
    $guarantorPhone = isset($_POST['guarantorPhone']) ? $_POST['guarantorPhone'] : '';
    $debitOnBusiness = isset($_POST['debitOnBusiness']) && !empty($_POST['debitOnBusiness']) ? 
                       (float) str_replace(',', '', $_POST['debitOnBusiness']) : 0;
    $debtOnCustomer = isset($_POST['debt_on_customer']) && !empty($_POST['debt_on_customer']) ? 
                     (float) str_replace(',', '', $_POST['debt_on_customer']) : 0;

    // Supplier specific data
    $debtOnMyself = isset($_POST['debt_on_myself']) && !empty($_POST['debt_on_myself']) ? 
                   (float) str_replace(',', '', $_POST['debt_on_myself']) : 0;
    $debtOnSupplier = isset($_POST['debt_on_supplier']) && !empty($_POST['debt_on_supplier']) ? 
                     (float) str_replace(',', '', $_POST['debt_on_supplier']) : 0;

    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Step 1: Insert into customers table
        $customerQuery = "INSERT INTO customers (businessMan, phone1, phone2, guarantorName, guarantorPhone, debitOnBusiness, debt_on_customer, notes, address, is_business_partner) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $customerStmt = $conn->prepare($customerQuery);
        $customerStmt->execute([
            $name, $phone1, $phone2, $guarantorName, $guarantorPhone, 
            $debitOnBusiness, $debtOnCustomer, $notes, $address
        ]);
        
        $customerId = $conn->lastInsertId();
        
        // Step 2: Insert into suppliers table
        $supplierQuery = "INSERT INTO suppliers (name, phone1, phone2, debt_on_myself, debt_on_supplier, notes, is_business_partner, customer_id) 
                         VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
        
        $supplierStmt = $conn->prepare($supplierQuery);
        $supplierStmt->execute([
            $name, $phone1, $phone2, $debtOnMyself, $debtOnSupplier, $notes, $customerId
        ]);
        
        $supplierId = $conn->lastInsertId();
        
        // Step 3: Update the customer record with the supplier ID for cross-reference
        $updateCustomerQuery = "UPDATE customers SET supplier_id = ? WHERE id = ?";
        
        $updateStmt = $conn->prepare($updateCustomerQuery);
        $updateStmt->execute([$supplierId, $customerId]);
        
        // Step 4: Add debt transactions if needed
        if ($debitOnBusiness > 0) {
            $debtQuery = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, transaction_date, notes) 
                         VALUES (?, ?, 'initial', NOW(), 'قەرزی سەرەتایی')";
            
            $debtStmt = $conn->prepare($debtQuery);
            $debtStmt->execute([$customerId, $debitOnBusiness]);
        }
        
        if ($debtOnCustomer > 0) {
            $debtQuery = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, transaction_date, notes) 
                         VALUES (?, ?, 'payment', NOW(), 'پێشەکی سەرەتایی')";
            
            $debtStmt = $conn->prepare($debtQuery);
            $negativeAmount = -1 * $debtOnCustomer; // Negative amount for payment
            $debtStmt->execute([$customerId, $negativeAmount]);
        }
        
        if ($debtOnMyself > 0) {
            $supplierDebtQuery = "INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, transaction_date, notes) 
                                 VALUES (?, ?, 'initial', NOW(), 'قەرزی سەرەتایی')";
            
            $supplierDebtStmt = $conn->prepare($supplierDebtQuery);
            $supplierDebtStmt->execute([$supplierId, $debtOnMyself]);
        }
        
        if ($debtOnSupplier > 0) {
            $supplierDebtQuery = "INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, transaction_date, notes) 
                                 VALUES (?, ?, 'payment', NOW(), 'پێشەکی سەرەتایی')";
            
            $supplierDebtStmt = $conn->prepare($supplierDebtQuery);
            $negativeAmount = -1 * $debtOnSupplier; // Negative amount for payment
            $supplierDebtStmt->execute([$supplierId, $negativeAmount]);
        }
        
        // If we got here, commit the transaction
        $conn->commit();
        
        // Return success JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'کەسی دووفاقە بە سەرکەوتوویی زیادکرا']);
        exit();
        
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Return error JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} else {
    // If not a POST request, redirect back to the form
    header("Location: ../views/admin/addStaff.php?tab=business-partner");
    exit();
} 