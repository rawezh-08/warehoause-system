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

try {
    // Get POST data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    if (!isset($data['supplier_id']) || !isset($data['amount']) || !isset($data['notes'])) {
        throw new Exception('پارامیتەرەکان ناتەواون');
    }
    
    $supplier_id = intval($data['supplier_id']);
    $amount = floatval($data['amount']);
    $notes = $data['notes'];
    
    // Debug logging
    error_log("Received adjust balance request - supplier_id: $supplier_id, amount: $amount");
    
    // Validate amount
    if ($amount <= 0) {
        throw new Exception('بڕی پارە نابێت کەمتر بێت لە 0');
    }
    
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8mb4");
    
    // Start transaction
    $conn->beginTransaction();
    
    // Get current balances
    $stmt = $conn->prepare("SELECT debt_on_myself, debt_on_supplier FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $current_balances = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_balances) {
        throw new Exception('فرۆشیار نەدۆزرایەوە');
    }
    
    // Insert adjustment transaction
    $stmt = $conn->prepare("
        INSERT INTO supplier_debt_transactions 
        (supplier_id, amount, transaction_type, notes) 
        VALUES (?, ?, 'adjust_balance', ?)
    ");
    $stmt->execute([$supplier_id, $amount, $notes]);
    
    // Update supplier balances
    $stmt = $conn->prepare("
        UPDATE suppliers 
        SET debt_on_supplier = debt_on_supplier + ?,
            debt_on_myself = debt_on_myself + ?
        WHERE id = ?
    ");
    $stmt->execute([$amount, $amount, $supplier_id]);
    
    // Commit transaction
    $conn->commit();
    
    // Get updated balances
    $stmt = $conn->prepare("SELECT debt_on_myself, debt_on_supplier FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $updated_balances = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'باڵانس بە سەرکەوتوویی ڕێکخرایەوە',
        'old_balance' => $current_balances,
        'new_balance' => $updated_balances
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if started
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
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