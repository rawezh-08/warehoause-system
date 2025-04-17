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
    // Start a transaction
    $conn->beginTransaction();
    
    // Check if the receipt exists and is a draft
    $check_stmt = $conn->prepare("SELECT * FROM sales WHERE id = ? AND is_draft = 1");
    $check_stmt->execute([$receipt_id]);
    $draft_receipt = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$draft_receipt) {
        echo json_encode([
            'success' => false,
            'message' => 'ڕەشنووسی پسوڵە نەدۆزرایەوە یان پێشتر پەسەند کراوە'
        ]);
        $conn->rollBack();
        exit;
    }
    
    // Get sale items
    $items_stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
    $items_stmt->execute([$receipt_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode([
            'success' => false,
            'message' => 'هیچ کاڵایەک لەناو ڕەشنووسەکە نییە'
        ]);
        $conn->rollBack();
        exit;
    }
    
    // Check inventory for each item
    foreach ($items as $item) {
        // Get product stock
        $stock_stmt = $conn->prepare("SELECT current_quantity FROM products WHERE id = ?");
        $stock_stmt->execute([$item['product_id']]);
        $product = $stock_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if we have enough stock
        if ($product['current_quantity'] < $item['pieces_count']) {
            echo json_encode([
                'success' => false,
                'message' => "بڕی پێویست لە کۆگا بەردەست نییە بۆ کاڵای بە ناسنامەی " . $item['product_id']
            ]);
            $conn->rollBack();
            exit;
        }
        
        // Update product stock
        $update_stock_stmt = $conn->prepare("UPDATE products SET current_quantity = current_quantity - ? WHERE id = ?");
        $update_stock_stmt->execute([$item['pieces_count'], $item['product_id']]);
        
        // Record in inventory table
        $inventory_stmt = $conn->prepare("INSERT INTO inventory (product_id, quantity, reference_type, reference_id) VALUES (?, ?, 'sale', ?)");
        $inventory_stmt->execute([$item['product_id'], -$item['pieces_count'], $item['id']]);
    }
    
    // Mark the receipt as no longer a draft
    $update_stmt = $conn->prepare("UPDATE sales SET is_draft = 0 WHERE id = ?");
    $update_stmt->execute([$receipt_id]);
    
    // If payment type is credit and there's a remaining amount, create debt transaction
    if ($draft_receipt['payment_type'] === 'credit' && $draft_receipt['remaining_amount'] > 0) {
        // Create debt transaction using stored procedure
        $debt_stmt = $conn->prepare("CALL add_debt_transaction(?, ?, 'sale', ?, ?, ?)");
        $debt_stmt->bindParam(1, $draft_receipt['customer_id'], PDO::PARAM_INT);
        $debt_stmt->bindParam(2, $draft_receipt['remaining_amount'], PDO::PARAM_STR);
        $debt_stmt->bindParam(3, $receipt_id, PDO::PARAM_INT);
        $debt_stmt->bindParam(4, $draft_receipt['notes'], PDO::PARAM_STR);
        $created_by = $draft_receipt['created_by'] ?? 1;
        $debt_stmt->bindParam(5, $created_by, PDO::PARAM_INT);
        $debt_stmt->execute();
        
        // Close the cursor
        $debt_stmt->closeCursor();
    }
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ڕەشنووسی پسوڵە بە سەرکەوتوویی پەسەند کرا'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی پەسەندکردنی ڕەشنووس',
        'debug' => [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage()
        ]
    ]);
}
?> 