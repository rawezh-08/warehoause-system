<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Customer.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Customer ID is required");
    }
    
    $customerId = (int)$_GET['id'];
    
    // Create Customer model
    $customerModel = new Customer($conn);
    
    // Check if customer exists
    $customer = $customerModel->getById($customerId);
    
    if (!$customer) {
        throw new Exception("Customer not found");
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // First delete related debt transactions
        $deleteDebtTransactions = "DELETE FROM debt_transactions WHERE customer_id = :customer_id";
        $stmt = $conn->prepare($deleteDebtTransactions);
        if (!$stmt) {
            throw new Exception("Error preparing debt transactions delete statement");
        }
        
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            throw new Exception("Error executing debt transactions delete");
        }
        
        // Then delete the customer
        $result = $customerModel->delete($customerId);
        
        if (!$result) {
            throw new Exception("Error deleting customer");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => "Customer deleted successfully"
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error in delete_customer.php: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete_customer.php: " . $e->getMessage());
    
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 