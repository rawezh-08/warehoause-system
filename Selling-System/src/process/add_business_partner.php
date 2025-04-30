<?php
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Function to convert formatted number string to float
function convertToNumber($str) {
    if (empty($str)) return 0;
    // Remove any non-numeric characters except decimal point
    $str = preg_replace('/[^0-9.]/', '', $str);
    return floatval($str);
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Get form data
    $partnerName = $_POST['partnerName'];
    $partnerPhone = $_POST['partnerPhone'];
    $partnerPhone2 = $_POST['partnerPhone2'] ?? null;
    $guarantorName = $_POST['guarantorName'] ?? null;
    $guarantorPhone = $_POST['guarantorPhone'] ?? null;
    $partnerAddress = $_POST['partnerAddress'] ?? null;
    $partnerNotes = $_POST['partnerNotes'] ?? null;
    
    // Debt information
    $debitOnBusiness = convertToNumber($_POST['debitOnBusiness'] ?? 0);
    $debtOnCustomer = convertToNumber($_POST['debt_on_customer'] ?? 0);
    $debtOnMyself = convertToNumber($_POST['debt_on_myself'] ?? 0);
    $debtOnSupplier = convertToNumber($_POST['debt_on_supplier'] ?? 0);

    // Insert into customers table
    $customerStmt = $conn->prepare("INSERT INTO customers (business_man, phone1, phone2, guarantor_name, guarantor_phone, address, notes, is_business_partner) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $customerStmt->execute([$partnerName, $partnerPhone, $partnerPhone2, $guarantorName, $guarantorPhone, $partnerAddress, $partnerNotes]);
    $customerId = $conn->lastInsertId();

    // Insert into suppliers table
    $supplierStmt = $conn->prepare("INSERT INTO suppliers (supplier_name, phone1, phone2, address, notes, is_business_partner) VALUES (?, ?, ?, ?, ?, 1)");
    $supplierStmt->execute([$partnerName, $partnerPhone, $partnerPhone2, $partnerAddress, $partnerNotes]);
    $supplierId = $conn->lastInsertId();

    // If there's initial customer debt
    if ($debitOnBusiness > 0) {
        $debtStmt = $conn->prepare("INSERT INTO debt_transactions (customer_id, amount, transaction_type, notes) VALUES (?, ?, 'debit', 'Initial debt')");
        $debtStmt->execute([$customerId, $debitOnBusiness]);
    }

    // If there's initial customer advance payment
    if ($debtOnCustomer > 0) {
        $advanceStmt = $conn->prepare("INSERT INTO debt_transactions (customer_id, amount, transaction_type, notes) VALUES (?, ?, 'credit', 'Initial advance payment')");
        $advanceStmt->execute([$customerId, $debtOnCustomer]);
    }

    // If there's initial supplier debt
    if ($debtOnMyself > 0) {
        $supplierDebtStmt = $conn->prepare("INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, notes) VALUES (?, ?, 'debit', 'Initial debt')");
        $supplierDebtStmt->execute([$supplierId, $debtOnMyself]);
    }

    // If there's initial supplier advance payment
    if ($debtOnSupplier > 0) {
        $supplierAdvanceStmt = $conn->prepare("INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, notes) VALUES (?, ?, 'credit', 'Initial advance payment')");
        $supplierAdvanceStmt->execute([$supplierId, $debtOnSupplier]);
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Business partner added successfully',
        'customer_id' => $customerId,
        'supplier_id' => $supplierId
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Error adding business partner: ' . $e->getMessage()
    ]);
}
?> 