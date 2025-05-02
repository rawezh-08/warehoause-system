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
        $customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $amount = isset($_POST['amount']) ? str_replace(',', '', $_POST['amount']) : 0; // Remove commas
        $amount = floatval($amount);
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // Validate data
        if ($customerId <= 0) {
            throw new Exception("Invalid customer ID.");
        }
        
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero.");
        }
        
        // Get customer information
        $customerQuery = "SELECT * FROM customers WHERE id = :customer_id";
        $customerStmt = $conn->prepare($customerQuery);
        $customerStmt->bindParam(':customer_id', $customerId);
        $customerStmt->execute();
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception("Customer not found.");
        }
        
        // Check if collected amount exceeds debt
        if ($amount > $customer['debt_on_customer']) {
            throw new Exception("Collection amount cannot exceed current debt of " . number_format($customer['debt_on_customer']));
        }
        
        // Insert collection record in debt_transactions table
        $transactionType = 'collection';
        $transactionQuery = "INSERT INTO debt_transactions (customer_id, amount, transaction_type, notes, created_at) 
                            VALUES (:customer_id, :amount, :transaction_type, :notes, NOW())";
        $transactionStmt = $conn->prepare($transactionQuery);
        $transactionStmt->bindParam(':customer_id', $customerId);
        $transactionStmt->bindParam(':amount', $amount);
        $transactionStmt->bindParam(':transaction_type', $transactionType);
        $transactionStmt->bindParam(':notes', $notes);
        $transactionStmt->execute();
        
        // Update customer's debt_on_customer balance
        $newDebtOnCustomer = $customer['debt_on_customer'] - $amount;
        $updateQuery = "UPDATE customers SET debt_on_customer = :debt_on_customer WHERE id = :customer_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':debt_on_customer', $newDebtOnCustomer);
        $updateStmt->bindParam(':customer_id', $customerId);
        $updateStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = "Successfully collected " . number_format($amount) . " from customer.";
        
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