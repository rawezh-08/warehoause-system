<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set header for JSON response
header('Content-Type: application/json');

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
        // Use empty string instead of null for optional fields
        $phone2 = !empty($_POST['partnerPhone2']) ? $_POST['partnerPhone2'] : '';
        $address = !empty($_POST['partnerAddress']) ? $_POST['partnerAddress'] : '';
        $notes = !empty($_POST['partnerNotes']) ? $_POST['partnerNotes'] : '';
        
        // Check if phone1 already exists in customers or suppliers table
        $checkPhoneQuery = "SELECT c.id AS customer_id, s.id AS supplier_id 
                           FROM customers c 
                           LEFT JOIN suppliers s ON s.phone1 = :phone OR s.phone2 = :phone 
                           WHERE c.phone1 = :phone OR c.phone2 = :phone 
                           LIMIT 1";
        
        $checkPhoneStmt = $conn->prepare($checkPhoneQuery);
        $checkPhoneStmt->execute([':phone' => $phone1]);
        
        if ($checkPhoneStmt->rowCount() > 0) {
            // Phone number already exists, throw an error
            throw new Exception('ژمارەی مۆبایل پێشتر بەکارهێنراوە');
        }
        
        // Customer specific data
        $guarantorName = !empty($_POST['guarantorName']) ? $_POST['guarantorName'] : '';
        $guarantorPhone = !empty($_POST['guarantorPhone']) ? $_POST['guarantorPhone'] : '';
        
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
        
        // Return success response with proper encoding
        $response = [
            'status' => 'success',
            'message' => 'کڕیار و دابینکەر بە سەرکەوتوویی زیادکرا',
            'customer_id' => $customerId,
            'supplier_id' => $supplierId
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Return error response
        $response = [
            'status' => 'error',
            'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
} else {
    // Return error for non-POST requests
    $response = [
        'status' => 'error',
        'message' => 'تەنها داواکاری POST قبوڵ دەکرێت'
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} 