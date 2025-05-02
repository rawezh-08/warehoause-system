<?php
require_once '../config/database.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => 'Unknown error occurred'
);

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get form data
        $supplierId = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
        $amount = isset($_POST['amount']) ? str_replace(',', '', $_POST['amount']) : 0; // Remove commas
        $amount = floatval($amount);
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // Validate data
        if ($supplierId <= 0) {
            throw new Exception("Invalid supplier ID.");
        }
        
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero.");
        }
        
        // Get supplier information
        $supplierQuery = "SELECT * FROM suppliers WHERE id = :supplier_id";
        $supplierStmt = $conn->prepare($supplierQuery);
        $supplierStmt->bindParam(':supplier_id', $supplierId);
        $supplierStmt->execute();
        $supplier = $supplierStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$supplier) {
            throw new Exception("Supplier not found.");
        }
        
        // Insert payment record in supplier_transactions table
        $transactionType = 'payment';
        $transactionQuery = "INSERT INTO supplier_transactions (supplier_id, amount, transaction_type, notes, created_at) 
                            VALUES (:supplier_id, :amount, :transaction_type, :notes, NOW())";
        $transactionStmt = $conn->prepare($transactionQuery);
        $transactionStmt->bindParam(':supplier_id', $supplierId);
        $transactionStmt->bindParam(':amount', $amount);
        $transactionStmt->bindParam(':transaction_type', $transactionType);
        $transactionStmt->bindParam(':notes', $notes);
        $transactionStmt->execute();
        
        // Update supplier's debt_to_supplier balance
        $newDebtToSupplier = $supplier['debt_to_supplier'] - $amount;
        $updateQuery = "UPDATE suppliers SET debt_to_supplier = :debt_to_supplier WHERE id = :supplier_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':debt_to_supplier', $newDebtToSupplier);
        $updateStmt->bindParam(':supplier_id', $supplierId);
        $updateStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = "Payment of " . number_format($amount) . " successfully added to supplier.";
        
    } catch (Exception $e) {
        // Rollback transaction if error occurs
        $conn->rollBack();
        $response['message'] = $e->getMessage();
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit; 