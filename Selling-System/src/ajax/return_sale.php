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
    $saleQuery = "SELECT s.*, 
                  (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as total_amount,
                  c.debit_on_business as customer_debt
                  FROM sales s 
                  JOIN customers c ON s.customer_id = c.id 
                  WHERE s.id = ?";
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
    
    // Get previous returns count
    $returnCountQuery = "SELECT COUNT(*) as count FROM product_returns WHERE receipt_id = ? AND receipt_type = 'selling'";
    $returnCountStmt = $conn->prepare($returnCountQuery);
    $returnCountStmt->execute([$sale_id]);
    $returnCount = $returnCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate total return amount and items
    $totalReturnAmount = 0;
    $returnedItems = [];
    $remainingItems = [];
    
    foreach ($return_quantities as $item_id => $quantity) {
        if ($quantity > 0) {
            $itemQuery = "SELECT si.*, p.name as product_name 
                         FROM sale_items si 
                         JOIN products p ON si.product_id = p.id 
                         WHERE si.id = ?";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->execute([$item_id]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if return quantity is greater than original quantity
            if ($quantity > $item['quantity']) {
                throw new Exception("بڕی گەڕانەوەی کاڵای {$item['product_name']} ناتوانێت لە {$item['quantity']} زیاتر بێت");
            }
            
            $returnAmount = $item['unit_price'] * $quantity;
            $totalReturnAmount += $returnAmount;
            
            // Add to returned items
            $returnedItems[] = [
                'product_name' => $item['product_name'],
                'original_quantity' => $item['quantity'],
                'returned_quantity' => $quantity,
                'unit_price' => $item['unit_price'],
                'total_price' => $returnAmount
            ];
            
            // Add to remaining items
            $remainingItems[] = [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'] - $quantity,
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'] - $returnAmount
            ];
            
            // Update product quantity
            $updateProductQuery = "UPDATE products 
                                 SET current_quantity = current_quantity + ? 
                                 WHERE id = ?";
            $updateProductStmt = $conn->prepare($updateProductQuery);
            $updateProductStmt->execute([$quantity, $item['product_id']]);
        }
    }
    
    // Calculate remaining amount
    $remainingAmount = $sale['total_amount'] - $totalReturnAmount;
    
    // Update sale remaining amount
    $updateSaleQuery = "UPDATE sales SET remaining_amount = ? WHERE id = ?";
    $updateSaleStmt = $conn->prepare($updateSaleQuery);
    $updateSaleStmt->execute([$remainingAmount, $sale_id]);
    
    // Update customer debt
    $newDebt = $sale['customer_debt'];
    if ($sale['payment_type'] == 'credit') {
        $newDebt -= $totalReturnAmount;
        $updateCustomerQuery = "UPDATE customers 
                              SET debit_on_business = ? 
                              WHERE id = ?";
        $updateCustomerStmt = $conn->prepare($updateCustomerQuery);
        $updateCustomerStmt->execute([$newDebt, $sale['customer_id']]);
    }
    
    // Record the return
    $returnQuery = "INSERT INTO product_returns 
                   (receipt_id, receipt_type, return_date, total_amount, notes, created_at) 
                   VALUES (?, 'selling', NOW(), ?, ?, NOW())";
    $returnStmt = $conn->prepare($returnQuery);
    $returnStmt->execute([$sale_id, $totalReturnAmount, $notes]);
    $return_id = $conn->lastInsertId();
    
    // Record return items
    foreach ($return_quantities as $item_id => $quantity) {
        if ($quantity > 0) {
            $itemQuery = "SELECT si.*, p.name as product_name 
                         FROM sale_items si 
                         JOIN products p ON si.product_id = p.id 
                         WHERE si.id = ?";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->execute([$item_id]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            $returnItemQuery = "INSERT INTO return_items 
                              (return_id, product_id, quantity, unit_price, total_price, unit_type, original_unit_type, original_quantity) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $returnItemStmt = $conn->prepare($returnItemQuery);
            $returnItemStmt->execute([
                $return_id,
                $item['product_id'],
                $quantity,
                $item['unit_price'],
                $item['unit_price'] * $quantity,
                $item['unit_type'],
                $item['unit_type'],
                $item['quantity']
            ]);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Prepare summary for response
    $summary = [
        'original_total' => $sale['total_amount'],
        'returned_amount' => $totalReturnAmount,
        'remaining_amount' => $remainingAmount,
        'returned_items' => $returnedItems,
        'remaining_items' => $remainingItems,
        'new_debt' => $newDebt,
        'return_count' => $returnCount + 1 // Add 1 for current return
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'کاڵاکان بە سەرکەوتوویی گەڕایەوە',
        'summary' => $summary
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