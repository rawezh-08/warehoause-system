<?php
// Include database connection
require_once('../config/database.php');

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

// Get posted JSON data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    // Fallback to regular POST data if JSON parsing fails
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
} else {
    // Use JSON data
    $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : 0;
    $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
    $notes = isset($data['notes']) ? $data['notes'] : '';
}

// Debug logging
error_log("Received business payment request - supplier_id: $supplier_id, amount: $amount");

// Validate data
if ($supplier_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid supplier ID'
    ]);
    exit;
}

if ($amount <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Amount must be greater than zero'
    ]);
    exit;
}

try {
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8mb4");
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Check if supplier exists and get current balances
    $stmt = $conn->prepare("SELECT id, debt_on_myself, debt_on_supplier FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        throw new Exception("Supplier not found");
    }
    
    // Log initial balances
    error_log("Supplier initial balances - debt_on_myself: " . $supplier['debt_on_myself'] . 
             ", debt_on_supplier: " . $supplier['debt_on_supplier']);
    
    // Get current user ID (this should come from your authentication system)
    $created_by = 1; // Placeholder - replace with actual logged-in user ID
    
    try {
        // Try to use stored procedure first
        $stmt = $conn->prepare("CALL business_pay_supplier(?, ?, ?, ?)");
        $success = $stmt->execute([$supplier_id, $amount, $notes, $created_by]);
        
        // Close the cursor to release the connection for further queries
        $stmt->closeCursor();
        
        // Debug output
        error_log("Stored procedure execution result: " . ($success ? 'true' : 'false'));
    } catch (Exception $procError) {
        error_log("Error executing procedure: " . $procError->getMessage());
        error_log("Falling back to direct database update");
        
        // Fallback: Insert the transaction directly
        $stmt = $conn->prepare("INSERT INTO supplier_debt_transactions 
                              (supplier_id, amount, transaction_type, notes, created_by) 
                              VALUES (?, ?, 'business_payment', ?, ?)");
        $stmt->execute([$supplier_id, $amount, $notes, $created_by]);
        
        // Update supplier balance - DECREASE debt_on_myself when we pay the supplier
        // because we are paying off our debt to them
        $stmt = $conn->prepare("UPDATE suppliers 
                              SET debt_on_myself = debt_on_myself - ? 
                              WHERE id = ?");
        $stmt->execute([$amount, $supplier_id]);
    }
    
    // Verify the balance was updated
    $stmt = $conn->prepare("SELECT debt_on_myself, debt_on_supplier FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $updated_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($updated_supplier) {
        error_log("Supplier updated balances - debt_on_myself: " . $updated_supplier['debt_on_myself'] . 
                 ", debt_on_supplier: " . $updated_supplier['debt_on_supplier']);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'پارەدان بۆ دابینکەر بە سەرکەوتوویی تۆمارکرا',
        'supplier_id' => $supplier_id,
        'amount' => $amount,
        'old_balance' => [
            'debt_on_myself' => $supplier['debt_on_myself'],
            'debt_on_supplier' => $supplier['debt_on_supplier']
        ],
        'new_balance' => [
            'debt_on_myself' => $updated_supplier['debt_on_myself'],
            'debt_on_supplier' => $updated_supplier['debt_on_supplier']
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        try {
            $conn->rollBack();
        } catch (Exception $rollbackError) {
            error_log("Error during rollback: " . $rollbackError->getMessage());
        }
    }
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close connection
if (isset($conn)) {
    $conn = null;
}
?> 