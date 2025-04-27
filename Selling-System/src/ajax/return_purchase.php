<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get purchase ID and return quantities
    $purchase_id = $_POST['purchase_id'];
    $return_quantities = $_POST['return_quantities'];
    $notes = $_POST['notes'] ?? '';
    $reason = $_POST['reason'] ?? 'other';
    
    // Start transaction
    $conn->beginTransaction();
    
    // Get purchase details
    $purchaseQuery = "SELECT p.*, 
                  (SELECT SUM(total_price) FROM purchase_items WHERE purchase_id = p.id) as total_amount,
                  s.debt_on_myself as supplier_debt
                  FROM purchases p 
                  JOIN suppliers s ON p.supplier_id = s.id 
                  WHERE p.id = ?";
    $purchaseStmt = $conn->prepare($purchaseQuery);
    $purchaseStmt->execute([$purchase_id]);
    $purchase = $purchaseStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase) {
        throw new Exception('پسووڵەکە نەدۆزرایەوە');
    }
    
    // Check if any payments were made
    $paymentQuery = "SELECT COUNT(*) as count FROM supplier_debt_transactions 
                    WHERE reference_id = ? AND transaction_type IN ('payment', 'collection')";
    $paymentStmt = $conn->prepare($paymentQuery);
    $paymentStmt->execute([$purchase_id]);
    $paymentCount = $paymentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // We allow returns even if payments were made
    
    // Get previous returns count
    $returnCountQuery = "SELECT COUNT(*) as count FROM product_returns WHERE receipt_id = ? AND receipt_type = 'buying'";
    $returnCountStmt = $conn->prepare($returnCountQuery);
    $returnCountStmt->execute([$purchase_id]);
    $returnCount = $returnCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate total return amount and items
    $totalReturnAmount = 0;
    $returnedItems = [];
    $remainingItems = [];
    
    foreach ($return_quantities as $item_id => $quantity) {
        if ($quantity > 0) {
            $itemQuery = "SELECT pi.*, p.name as product_name 
                         FROM purchase_items pi 
                         JOIN products p ON pi.product_id = p.id 
                         WHERE pi.id = ?";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->execute([$item_id]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get previously returned quantity
            $prevReturnQuery = "SELECT COALESCE(SUM(ri.quantity), 0) as returned_quantity 
                              FROM return_items ri 
                              JOIN product_returns pr ON ri.return_id = pr.id 
                              WHERE pr.receipt_id = ? AND ri.product_id = ?";
            $prevReturnStmt = $conn->prepare($prevReturnQuery);
            $prevReturnStmt->execute([$purchase_id, $item['product_id']]);
            $prevReturned = $prevReturnStmt->fetch(PDO::FETCH_ASSOC)['returned_quantity'];
            
            // Calculate remaining quantity
            $remainingQuantity = $item['quantity'] - $prevReturned;
            
            // Check if there's any quantity left to return
            if ($remainingQuantity <= 0) {
                throw new Exception("کاڵای {$item['product_name']} پێشتر بە تەواوی گەڕاوەتەوە");
            }
            
            // Check if return quantity is greater than remaining quantity
            if ($quantity > $remainingQuantity) {
                throw new Exception("بڕی گەڕاندنەوەی کاڵای {$item['product_name']} ناتوانێت لە {$remainingQuantity} زیاتر بێت");
            }
            
            $returnAmount = $item['unit_price'] * $quantity;
            $totalReturnAmount += $returnAmount;
            
            // Add to returned items
            $returnedItems[] = [
                'product_name' => $item['product_name'],
                'original_quantity' => $item['quantity'],
                'previously_returned' => $prevReturned,
                'returned_quantity' => $quantity,
                'total_returned_quantity' => $prevReturned + $quantity,
                'remaining_quantity' => $remainingQuantity - $quantity,
                'unit_price' => $item['unit_price'],
                'total_price' => $returnAmount
            ];
            
            // Add to remaining items
            $remainingItems[] = [
                'product_name' => $item['product_name'],
                'quantity' => $remainingQuantity - $quantity,
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'] - $returnAmount
            ];
            
            // Update product quantity (decrease inventory since we're returning to supplier)
            $updateProductQuery = "UPDATE products 
                                 SET current_quantity = current_quantity - ? 
                                 WHERE id = ?";
            $updateProductStmt = $conn->prepare($updateProductQuery);
            $updateProductStmt->execute([$quantity, $item['product_id']]);
        }
    }
    
    // Calculate remaining amount
    $remainingAmount = $purchase['total_amount'] - $totalReturnAmount;
    
    // Update purchase remaining amount
    $updatePurchaseQuery = "UPDATE purchases SET remaining_amount = ? WHERE id = ?";
    $updatePurchaseStmt = $conn->prepare($updatePurchaseQuery);
    $updatePurchaseStmt->execute([$remainingAmount, $purchase_id]);
    
    // Update supplier debt
    $newDebt = $purchase['supplier_debt'];
    if ($purchase['payment_type'] == 'credit') {
        $newDebt -= $totalReturnAmount;
        $updateSupplierQuery = "UPDATE suppliers 
                              SET debt_on_myself = ? 
                              WHERE id = ?";
        $updateSupplierStmt = $conn->prepare($updateSupplierQuery);
        $updateSupplierStmt->execute([$newDebt, $purchase['supplier_id']]);
    }
    
    // Record the return
    $returnQuery = "INSERT INTO product_returns 
                   (receipt_id, receipt_type, return_date, total_amount, reason, notes, created_at) 
                   VALUES (?, 'buying', NOW(), ?, ?, ?, NOW())";
    $returnStmt = $conn->prepare($returnQuery);
    $returnStmt->execute([$purchase_id, $totalReturnAmount, $reason, $notes]);
    $return_id = $conn->lastInsertId();
    
    // Record return items
    foreach ($return_quantities as $item_id => $quantity) {
        if ($quantity > 0) {
            $itemQuery = "SELECT pi.*, p.name as product_name 
                         FROM purchase_items pi 
                         JOIN products p ON pi.product_id = p.id 
                         WHERE pi.id = ?";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->execute([$item_id]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            $returnItemQuery = "INSERT INTO return_items 
                              (return_id, product_id, quantity, unit_price, total_price, unit_type, original_unit_type, original_quantity, reason) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $returnItemStmt = $conn->prepare($returnItemQuery);
            $returnItemStmt->execute([
                $return_id,
                $item['product_id'],
                $quantity,
                $item['unit_price'],
                $item['unit_price'] * $quantity,
                $item['unit_type'],
                $item['unit_type'],
                $item['quantity'],
                $reason
            ]);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Prepare summary for response
    $summary = [
        'original_total' => $purchase['total_amount'],
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