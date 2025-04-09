<?php
// Include database connection
require_once('../config/database.php');

header('Content-Type: application/json');

try {
    // Create database connection
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8mb4");
    
    // Get all suppliers with their balances
    $query = "SELECT s.id, s.name, s.phone1, s.phone2, 
                     s.debt_on_myself, s.debt_on_supplier, s.notes,
                     (SELECT COUNT(*) FROM supplier_debt_transactions 
                      WHERE supplier_id = s.id) as transaction_count
              FROM suppliers s 
              ORDER BY s.name";
    
    $stmt = $conn->query($query);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Get recent transactions for each supplier
    foreach ($suppliers as &$supplier) {
        $transactionQuery = "SELECT id, amount, transaction_type, created_at 
                            FROM supplier_debt_transactions 
                            WHERE supplier_id = ? 
                            ORDER BY created_at DESC LIMIT 5";
        $transStmt = $conn->prepare($transactionQuery);
        $transStmt->execute([$supplier['id']]);
        $supplier['recent_transactions'] = $transStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Return success response with suppliers data
    echo json_encode([
        'status' => 'success',
        'suppliers' => $suppliers
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close connection
if (isset($conn)) {
    $conn = null;
}
?> 