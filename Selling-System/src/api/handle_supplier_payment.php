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
error_log("Received payment from supplier request - supplier_id: $supplier_id, amount: $amount");

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
    // Enable PDO error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Disable emulated prepared statements
    $conn->exec("set names utf8mb4");
    
    error_log("Database connection established successfully");
    
    // Begin transaction
    $conn->beginTransaction();
    error_log("Transaction started");
    
    // Check if supplier exists and get current balances
    error_log("Attempting to fetch supplier with ID: " . $supplier_id);
    $stmt = $conn->prepare("SELECT id, debt_on_myself, debt_on_supplier FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    if (!$supplier) {
        error_log("Supplier not found with ID: " . $supplier_id);
        throw new Exception("Supplier not found");
    }
    
    // Log initial balances
    error_log("Supplier initial balances - debt_on_myself: " . $supplier['debt_on_myself'] . 
             ", debt_on_supplier: " . $supplier['debt_on_supplier']);
    
    // Get current user ID (this should come from your authentication system)
    $created_by = 1; // Placeholder - replace with actual logged-in user ID
    
    // Insert transaction record directly
    error_log("Attempting to insert transaction record - Amount: " . $amount);
    $stmt = $conn->prepare("INSERT INTO supplier_debt_transactions 
                          (supplier_id, amount, transaction_type, notes, created_by) 
                          VALUES (?, ?, 'supplier_payment', ?, ?)");
    try {
        $result = $stmt->execute([$supplier_id, $amount, $notes, $created_by]);
        error_log("Transaction record inserted successfully");
    } catch (PDOException $e) {
        error_log("Error inserting transaction: " . $e->getMessage());
        throw $e;
    }
    $stmt->closeCursor();
    
    if (!$result) {
        error_log("Failed to insert transaction record");
        throw new Exception("Failed to record transaction");
    }
    
    // Update supplier balance
    error_log("Attempting to update supplier balance");
    $stmt = $conn->prepare("UPDATE suppliers 
                          SET debt_on_supplier = debt_on_supplier - ? 
                          WHERE id = ?");
    try {
        $result = $stmt->execute([$amount, $supplier_id]);
        error_log("Supplier balance updated successfully");
    } catch (PDOException $e) {
        error_log("Error updating supplier balance: " . $e->getMessage());
        throw $e;
    }
    $stmt->closeCursor();
    
    if (!$result) {
        error_log("Failed to update supplier balance");
        throw new Exception("Failed to update supplier balance");
    }
    
    // Verify the balance was updated
    error_log("Verifying updated balance");
    $stmt = $conn->prepare("SELECT debt_on_myself, debt_on_supplier FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $updated_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    if ($updated_supplier) {
        error_log("Supplier updated balances - debt_on_myself: " . $updated_supplier['debt_on_myself'] . 
                 ", debt_on_supplier: " . $updated_supplier['debt_on_supplier']);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment from supplier recorded successfully',
        'transaction_type' => 'supplier_payment',
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
            error_log("Transaction rolled back due to error: " . $e->getMessage());
        } catch (Exception $rollbackError) {
            error_log("Error during rollback: " . $rollbackError->getMessage());
        }
    }
    
    // Log the full error details
    error_log("Error details: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    // Return error response with more details
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => [
            'code' => $e instanceof PDOException ? $e->getCode() : 0,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

// Close connection
if (isset($conn)) {
    $conn = null;
}
?> 