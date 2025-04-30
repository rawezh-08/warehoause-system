<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

try {
    // Start transaction
    $conn->beginTransaction();

    // First create the customer record
    $customer_sql = "INSERT INTO customers (
        name, 
        phone1, 
        phone2, 
        address, 
        debit_on_business, 
        debt_on_customer,
        notes,
        is_business_partner
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";

    $customer_stmt = $conn->prepare($customer_sql);
    $customer_stmt->execute([
        $_POST['partnerName'],
        $_POST['partnerPhone1'],
        $_POST['partnerPhone2'],
        $_POST['partnerAddress'],
        $_POST['partnerDebitOnBusiness'],
        $_POST['partnerDebtOnCustomer'],
        $_POST['partnerNotes']
    ]);
    $customer_id = $conn->lastInsertId();

    // Then create the supplier record
    $supplier_sql = "INSERT INTO suppliers (
        name, 
        phone1, 
        phone2, 
        debt_on_myself,
        debt_on_supplier,
        notes,
        is_business_partner,
        customer_id
    ) VALUES (?, ?, ?, ?, ?, ?, 1, ?)";

    $supplier_stmt = $conn->prepare($supplier_sql);
    $supplier_stmt->execute([
        $_POST['partnerName'],
        $_POST['partnerPhone1'],
        $_POST['partnerPhone2'],
        $_POST['partnerDebtOnMyself'],
        $_POST['partnerDebtOnSupplier'],
        $_POST['partnerNotes'],
        $customer_id
    ]);
    $supplier_id = $conn->lastInsertId();

    // Update the customer record with the supplier_id
    $update_customer_sql = "UPDATE customers SET supplier_id = ? WHERE id = ?";
    $update_customer_stmt = $conn->prepare($update_customer_sql);
    $update_customer_stmt->execute([$supplier_id, $customer_id]);

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'هاوکار بە سەرکەوتوویی زیادکرا',
        'customer_id' => $customer_id,
        'supplier_id' => $supplier_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی هاوکار: ' . $e->getMessage()
    ]);
}
?> 