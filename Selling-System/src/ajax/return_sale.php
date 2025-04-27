<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get sale ID and return quantities
    $sale_id = $_POST['sale_id'];
    $return_quantities = $_POST['return_quantities'];
    $notes = $_POST['notes'];
    
    // Start transaction
    $conn->beginTransaction();
    
    // Get sale details
    $saleQuery = "SELECT * FROM sales WHERE id = ?";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->execute([$sale_id]);
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        throw new Exception('پسووڵەکە نەدۆزرایەوە');
    }
    
    // Check if any payments were made
    $paymentQuery = "SELECT COUNT(*) as count FROM debt_transactions 
                    WHERE reference_id = ? AND transaction_type IN ('payment', 'collection')";
    $paymentStmt = $conn->prepare($paymentQuery);
    $paymentStmt->execute([$sale_id]);
    $paymentCount = $paymentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($paymentCount > 0) {
        throw new Exception('ناتوانرێت ئەم پسووڵە بگەڕێتەوە چونکە پارەدانەوەی لەسەر تۆمار کراوە');
    }
    
    // Calculate total return amount
    $totalReturnAmount = 0;
    foreach ($return_quantities as $item_id => $quantity) {
        if ($quantity > 0) {
            $itemQuery = "SELECT * FROM sale_items WHERE id = ?";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->execute([$item_id]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            $totalReturnAmount += $item['unit_price'] * $quantity;
            
            // Update product quantity
            $updateProductQuery = "UPDATE products 
                                 SET current_quantity = current_quantity + ? 
                                 WHERE id = ?";
            $updateProductStmt = $conn->prepare($updateProductQuery);
            $updateProductStmt->execute([$quantity, $item['product_id']]);
        }
    }
    
    // Update customer debt
    if ($sale['payment_type'] == 'credit') {
        $updateCustomerQuery = "UPDATE customers 
                              SET debit_on_business = debit_on_business - ? 
                              WHERE id = ?";
        $updateCustomerStmt = $conn->prepare($updateCustomerQuery);
        $updateCustomerStmt->execute([$totalReturnAmount, $sale['customer_id']]);
    }
    
    // Record the return
    $returnQuery = "INSERT INTO product_returns 
                   (receipt_id, receipt_type, total_amount, notes, created_at) 
                   VALUES (?, 'selling', ?, ?, NOW())";
    $returnStmt = $conn->prepare($returnQuery);
    $returnStmt->execute([$sale_id, $totalReturnAmount, $notes]);
    $return_id = $conn->lastInsertId();
    
    // Record return items
    foreach ($return_quantities as $item_id => $quantity) {
        if ($quantity > 0) {
            $itemQuery = "SELECT * FROM sale_items WHERE id = ?";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->execute([$item_id]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            $returnItemQuery = "INSERT INTO return_items 
                              (return_id, product_id, quantity, unit_price, total_price) 
                              VALUES (?, ?, ?, ?, ?)";
            $returnItemStmt = $conn->prepare($returnItemQuery);
            $returnItemStmt->execute([
                $return_id,
                $item['product_id'],
                $quantity,
                $item['unit_price'],
                $item['unit_price'] * $quantity
            ]);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'کاڵاکان بە سەرکەوتوویی گەڕایەوە'
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 