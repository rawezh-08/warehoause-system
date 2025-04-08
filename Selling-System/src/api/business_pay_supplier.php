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

// Get posted data
$supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

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
    
    // Check if supplier exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier_count = $stmt->fetchColumn();
    
    if ($supplier_count == 0) {
        throw new Exception("Supplier not found");
    }
    
    // Get current user ID (this should come from your authentication system)
    $created_by = 1; // Placeholder - replace with actual logged-in user ID
    
    try {
        // Execute stored procedure to register business payment to supplier
        $stmt = $conn->prepare("CALL business_pay_supplier(?, ?, ?, ?)");
        $success = $stmt->execute([$supplier_id, $amount, $notes, $created_by]);
        
        // Close the cursor to release the connection for further queries
        $stmt->closeCursor();
        
        // Debug output
        error_log("Stored procedure execution result: " . ($success ? 'true' : 'false'));
        
        if (!$success) {
            throw new Exception("Failed to process payment");
        }
    } catch (Exception $procError) {
        error_log("Error executing procedure: " . $procError->getMessage());
        throw $procError;
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully'
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