<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامە و جۆری پسوڵە پێویستە'
    ]);
    exit;
}

$receipt_id = intval($_POST['id']);
$receipt_type = $_POST['type'];

// Validate receipt type
if ($receipt_type !== 'selling') {
    echo json_encode([
        'success' => false,
        'message' => 'ئەم جۆرە پسوڵەیە پشتگیری ناکرێت بۆ پەسەندکردن'
    ]);
    exit;
}

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // First check if the draft exists
    $check_stmt = $conn->prepare("SELECT EXISTS(SELECT 1 FROM sales WHERE id = ? AND is_draft = 1) as draft_exists");
    $check_stmt->execute([$receipt_id]);
    $draft_exists = $check_stmt->fetchColumn();
    
    if (!$draft_exists) {
        echo json_encode([
            'success' => false,
            'message' => 'ڕەشنووسی پسوڵە نەدۆزرایەوە یان پێشتر پەسەند کراوە'
        ]);
        exit;
    }
    
    // Get receipt information
    $created_by = isset($_POST['created_by']) ? intval($_POST['created_by']) : 1;
    
    $info_stmt = $conn->prepare("
        SELECT s.customer_id, s.payment_type, 
            (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $info_stmt->execute([$receipt_id]);
    $receipt_info = $info_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt_info) {
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکانی پسوڵە'
        ]);
        $conn->rollBack();
        exit;
    }
    
    // Update the draft to finalize it
    $update_stmt = $conn->prepare("
        UPDATE sales 
        SET is_draft = 0, 
            created_by = ?, 
            updated_at = NOW()
        WHERE id = ?
    ");
    $update_stmt->execute([$created_by, $receipt_id]);
    
    // If payment type is credit, update customer debt
    if ($receipt_info['payment_type'] === 'credit') {
        // Insert debt transaction record
        $debt_stmt = $conn->prepare("
            INSERT INTO debt_transactions (
                customer_id, 
                amount, 
                transaction_type, 
                reference_id, 
                created_by, 
                created_at
            ) VALUES (?, ?, 'sale', ?, ?, NOW())
        ");
        $debt_stmt->execute([
            $receipt_info['customer_id'],
            $receipt_info['total_amount'],
            $receipt_id,
            $created_by
        ]);
        
        // Update customer debit_on_business (not debt_on_business)
        $customer_stmt = $conn->prepare("
            UPDATE customers 
            SET debit_on_business = debit_on_business + ?
            WHERE id = ?
        ");
        $customer_stmt->execute([
            $receipt_info['total_amount'],
            $receipt_info['customer_id']
        ]);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ڕەشنووسی پسوڵە بە سەرکەوتوویی پەسەند کرا'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی پەسەندکردنی ڕەشنووس: ' . $e->getMessage(),
        'debug' => [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage()
        ]
    ]);
}
?> 