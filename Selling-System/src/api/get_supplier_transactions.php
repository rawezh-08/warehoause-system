<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Check if supplier_id is provided
if (!isset($_GET['supplier_id']) || empty($_GET['supplier_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامەی دابینکەر پێویستە'
    ]);
    exit;
}

$supplierId = $_GET['supplier_id'];
$transactionType = isset($_GET['transaction_type']) && !empty($_GET['transaction_type']) ? $_GET['transaction_type'] : null;

// Optional date range filter
$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : null;

try {
    // Connect to database using the global $conn from config
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
    
    // Base query
    $query = "
        SELECT 
            id,
            supplier_id,
            amount,
            transaction_type,
            reference_id,
            notes,
            created_by,
            created_at,
            CASE 
                WHEN transaction_type IN ('purchase', 'manual_adjustment') AND amount >= 0 THEN 'increase_debt_on_myself'
                WHEN transaction_type IN ('payment', 'return') THEN 'decrease_debt_on_myself'
                WHEN transaction_type IN ('supplier_payment', 'manual_adjustment') AND amount < 0 THEN 'increase_debt_on_supplier'
                WHEN transaction_type = 'supplier_return' THEN 'decrease_debt_on_supplier'
                ELSE 'unknown'
            END as effect_on_balance
        FROM 
            supplier_debt_transactions
        WHERE 
            supplier_id = :supplier_id
    ";
    
    // Add transaction type filter if provided
    if ($transactionType) {
        $query .= " AND transaction_type = :transaction_type";
    }
    
    // Add date filters if provided
    if ($startDate) {
        $query .= " AND DATE(created_at) >= :start_date";
    }
    
    if ($endDate) {
        $query .= " AND DATE(created_at) <= :end_date";
    }
    
    // Add sorting
    $query .= " ORDER BY created_at DESC, id DESC";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':supplier_id', $supplierId, PDO::PARAM_INT);
    
    if ($transactionType) {
        $stmt->bindParam(':transaction_type', $transactionType, PDO::PARAM_STR);
    }
    
    if ($startDate) {
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
    }
    
    if ($endDate) {
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} finally {
    // Close connection
    if ($conn) {
        $conn = null;
    }
} 