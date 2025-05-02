<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Start transaction
        $conn->beginTransaction();
        
        // Get form data
        $name = $_POST['partnerName'];
        $phone1 = $_POST['partnerPhone1'];
        $phone2 = !empty($_POST['partnerPhone2']) ? $_POST['partnerPhone2'] : null;
        $address = !empty($_POST['partnerAddress']) ? $_POST['partnerAddress'] : null;
        $notes = !empty($_POST['partnerNotes']) ? $_POST['partnerNotes'] : null;
        
        // Customer specific data
        $guarantorName = !empty($_POST['guarantorName']) ? $_POST['guarantorName'] : null;
        $guarantorPhone = !empty($_POST['guarantorPhone']) ? $_POST['guarantorPhone'] : null;
        
        // Handle empty or invalid numeric values
        $debitOnBusiness = !empty($_POST['debitOnBusiness']) ? str_replace(',', '', $_POST['debitOnBusiness']) : 0;
        $debtOnCustomer = !empty($_POST['debt_on_customer']) ? str_replace(',', '', $_POST['debt_on_customer']) : 0;
        $debtOnMyself = !empty($_POST['debt_on_myself']) ? str_replace(',', '', $_POST['debt_on_myself']) : 0;
        $debtOnSupplier = !empty($_POST['debt_on_supplier']) ? str_replace(',', '', $_POST['debt_on_supplier']) : 0;
        
        // Convert to float to ensure valid decimal values
        $debitOnBusiness = floatval($debitOnBusiness);
        $debtOnCustomer = floatval($debtOnCustomer);
        $debtOnMyself = floatval($debtOnMyself);
        $debtOnSupplier = floatval($debtOnSupplier);
        
        // Insert into customers table
        $customerQuery = "INSERT INTO customers (name, phone1, phone2, address, guarantor_name, guarantor_phone, 
                        debit_on_business, debt_on_customer, notes, is_business_partner) 
                        VALUES (:name, :phone1, :phone2, :address, :guarantor_name, :guarantor_phone, 
                        :debit_on_business, :debt_on_customer, :notes, 1)";
        
        $customerStmt = $conn->prepare($customerQuery);
        $customerStmt->execute([
            ':name' => $name,
            ':phone1' => $phone1,
            ':phone2' => $phone2,
            ':address' => $address,
            ':guarantor_name' => $guarantorName,
            ':guarantor_phone' => $guarantorPhone,
            ':debit_on_business' => $debitOnBusiness,
            ':debt_on_customer' => $debtOnCustomer,
            ':notes' => $notes
        ]);
        
        $customerId = $conn->lastInsertId();
        
        // Insert into suppliers table
        $supplierQuery = "INSERT INTO suppliers (name, phone1, phone2, debt_on_myself, debt_on_supplier, notes, is_business_partner) 
                        VALUES (:name, :phone1, :phone2, :debt_on_myself, :debt_on_supplier, :notes, 1)";
        
        $supplierStmt = $conn->prepare($supplierQuery);
        $supplierStmt->execute([
            ':name' => $name,
            ':phone1' => $phone1,
            ':phone2' => $phone2,
            ':debt_on_myself' => $debtOnMyself,
            ':debt_on_supplier' => $debtOnSupplier,
            ':notes' => $notes
        ]);
        
        $supplierId = $conn->lastInsertId();
        
        // Update customer with supplier_id
        $updateCustomerQuery = "UPDATE customers SET supplier_id = :supplier_id WHERE id = :customer_id";
        $updateCustomerStmt = $conn->prepare($updateCustomerQuery);
        $updateCustomerStmt->execute([
            ':supplier_id' => $supplierId,
            ':customer_id' => $customerId
        ]);
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'کڕیار و دابینکەر بە سەرکەوتوویی زیادکرا',
            'customer_id' => $customerId,
            'supplier_id' => $supplierId
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Return error response
        echo json_encode([
            'status' => 'error',
            'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
        ]);
    }
} else {
    // Return error for non-POST requests
    echo json_encode([
        'status' => 'error',
        'message' => 'تەنها داواکاری POST قبوڵ دەکرێت'
    ]);
} 