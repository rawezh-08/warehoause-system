<?php
// Include database connection
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Start transaction
$conn->beginTransaction();

try {
    // Validate required data
    if (!isset($_POST['sale_id']) || empty($_POST['sale_id']) ||
        !isset($_POST['product_ids']) || empty($_POST['product_ids']) ||
        !isset($_POST['return_quantities']) || empty($_POST['return_quantities']) ||
        !isset($_POST['unit_prices']) || empty($_POST['unit_prices']) ||
        !isset($_POST['unit_types']) || empty($_POST['unit_types']) ||
        !isset($_POST['sale_item_ids']) || empty($_POST['sale_item_ids'])) {
        
        throw new Exception('زانیاری پێویست ناتەواوە');
    }
    
    // Get sale information
    $saleId = intval($_POST['sale_id']);
    $saleQuery = "SELECT s.*, c.id as customer_id, c.name as customer_name 
                  FROM sales s 
                  JOIN customers c ON s.customer_id = c.id 
                  WHERE s.id = :sale_id";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':sale_id', $saleId);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        throw new Exception('پسووڵەی فرۆشتن نەدۆزرایەوە');
    }
    
    // Process return data
    $productIds = $_POST['product_ids'];
    $returnQuantities = $_POST['return_quantities'];
    $unitPrices = $_POST['unit_prices'];
    $unitTypes = $_POST['unit_types'];
    $saleItemIds = $_POST['sale_item_ids'];
    $returnReason = $_POST['reason'] ?? 'other';
    $returnNotes = $_POST['notes'] ?? '';
    
    // Create return record
    $insertReturnQuery = "INSERT INTO product_returns (receipt_id, receipt_type, return_date, reason, notes) 
                          VALUES (:receipt_id, 'selling', NOW(), :reason, :notes)";
    $returnStmt = $conn->prepare($insertReturnQuery);
    $returnStmt->bindParam(':receipt_id', $saleId);
    $returnStmt->bindParam(':reason', $returnReason);
    $returnStmt->bindParam(':notes', $returnNotes);
    $returnStmt->execute();
    
    $returnId = $conn->lastInsertId();
    $totalReturnAmount = 0;
    
    // Process each returned item
    for ($i = 0; $i < count($productIds); $i++) {
        $productId = intval($productIds[$i]);
        $returnQuantity = intval($returnQuantities[$i]);
        $unitPrice = floatval($unitPrices[$i]);
        $unitType = $unitTypes[$i];
        $saleItemId = intval($saleItemIds[$i]);
        
        // Skip if quantity is 0
        if ($returnQuantity <= 0) {
            continue;
        }
        
        // Get original sale item data
        $saleItemQuery = "SELECT si.*, p.pieces_per_box, p.boxes_per_set 
                         FROM sale_items si
                         JOIN products p ON si.product_id = p.id
                         WHERE si.id = :id";
        $saleItemStmt = $conn->prepare($saleItemQuery);
        $saleItemStmt->bindParam(':id', $saleItemId);
        $saleItemStmt->execute();
        $saleItem = $saleItemStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$saleItem) {
            throw new Exception('کاڵای فرۆشراو نەدۆزرایەوە');
        }
        
        // Validate return quantity doesn't exceed available quantity
        $availableQuantity = $saleItem['quantity'] - $saleItem['returned_quantity'];
        
        if ($returnQuantity > $availableQuantity) {
            throw new Exception('بڕی گەڕانەوە لە بڕی فرۆشراو زیاترە');
        }
        
        // Calculate total price for this return item
        $totalPrice = $returnQuantity * $unitPrice;
        $totalReturnAmount += $totalPrice;
        
        // Calculate inventory quantity based on unit type
        $inventoryQuantity = $returnQuantity;
        if ($unitType == 'box' && $saleItem['pieces_per_box']) {
            $inventoryQuantity = $returnQuantity * $saleItem['pieces_per_box'];
        } elseif ($unitType == 'set' && $saleItem['boxes_per_set'] && $saleItem['pieces_per_box']) {
            $inventoryQuantity = $returnQuantity * $saleItem['boxes_per_set'] * $saleItem['pieces_per_box'];
        }
        
        // Insert return item
        $insertReturnItemQuery = "INSERT INTO return_items (
            return_id, product_id, quantity, unit_price, unit_type, 
            original_unit_type, original_quantity, reason, notes, total_price
        ) VALUES (
            :return_id, :product_id, :quantity, :unit_price, :unit_type,
            :original_unit_type, :original_quantity, :reason, :notes, :total_price
        )";
        
        $returnItemStmt = $conn->prepare($insertReturnItemQuery);
        $returnItemStmt->bindParam(':return_id', $returnId);
        $returnItemStmt->bindParam(':product_id', $productId);
        $returnItemStmt->bindParam(':quantity', $returnQuantity);
        $returnItemStmt->bindParam(':unit_price', $unitPrice);
        $returnItemStmt->bindParam(':unit_type', $unitType);
        $returnItemStmt->bindParam(':original_unit_type', $unitType);
        $returnItemStmt->bindParam(':original_quantity', $returnQuantity);
        $returnItemStmt->bindParam(':reason', $returnReason);
        $returnItemStmt->bindParam(':notes', $returnNotes);
        $returnItemStmt->bindParam(':total_price', $totalPrice);
        $returnItemStmt->execute();
        
        // Update sale_items returned_quantity
        $newReturnedQuantity = $saleItem['returned_quantity'] + $returnQuantity;
        $updateSaleItemQuery = "UPDATE sale_items SET returned_quantity = :returned_quantity 
                                WHERE id = :sale_item_id";
        $updateSaleItemStmt = $conn->prepare($updateSaleItemQuery);
        $updateSaleItemStmt->bindParam(':returned_quantity', $newReturnedQuantity);
        $updateSaleItemStmt->bindParam(':sale_item_id', $saleItemId);
        $updateSaleItemStmt->execute();
        
        // Update inventory (add returned items back to stock)
        $inventoryQuery = "UPDATE inventory 
                          SET quantity = quantity + :return_quantity 
                          WHERE product_id = :product_id";
        $inventoryStmt = $conn->prepare($inventoryQuery);
        $inventoryStmt->bindParam(':return_quantity', $inventoryQuantity);
        $inventoryStmt->bindParam(':product_id', $productId);
        $inventoryStmt->execute();
        
        // Update product quantity
        $updateProductQuery = "UPDATE products 
                              SET current_quantity = current_quantity + :return_quantity 
                              WHERE id = :product_id";
        $updateProductStmt = $conn->prepare($updateProductQuery);
        $updateProductStmt->bindParam(':return_quantity', $inventoryQuantity);
        $updateProductStmt->bindParam(':product_id', $productId);
        $updateProductStmt->execute();
    }
    
    // Update the product_return record with total amount
    $updateReturnQuery = "UPDATE product_returns SET total_amount = :total_amount WHERE id = :return_id";
    $updateReturnStmt = $conn->prepare($updateReturnQuery);
    $updateReturnStmt->bindParam(':total_amount', $totalReturnAmount);
    $updateReturnStmt->bindParam(':return_id', $returnId);
    $updateReturnStmt->execute();
    
    // If this was a credit sale, create a debt transaction to adjust customer's debt
    if ($sale['payment_type'] == 'credit' && $totalReturnAmount > 0) {
        // Create a transaction note in JSON format
        $transactionNotes = json_encode([
            'return_id' => $returnId,
            'notes' => $returnNotes
        ]);
        
        // Insert debt transaction (negative amount to reduce debt)
        $debtTransactionQuery = "INSERT INTO debt_transactions (
            customer_id, amount, transaction_type, reference_id, notes, created_at
        ) VALUES (
            :customer_id, :amount, 'collection', :reference_id, :notes, NOW()
        )";
        
        $debtStmt = $conn->prepare($debtTransactionQuery);
        $debtStmt->bindParam(':customer_id', $sale['customer_id']);
        $debtStmt->bindParam(':amount', $totalReturnAmount, PDO::PARAM_STR);
        $debtStmt->bindParam(':reference_id', $returnId);
        $debtStmt->bindParam(':notes', $transactionNotes);
        $debtStmt->execute();
        
        // Update customer's debt
        $updateCustomerQuery = "UPDATE customers 
                               SET debit_on_business = debit_on_business - :amount 
                               WHERE id = :customer_id";
        $customerStmt = $conn->prepare($updateCustomerQuery);
        $customerStmt->bindParam(':amount', $totalReturnAmount, PDO::PARAM_STR);
        $customerStmt->bindParam(':customer_id', $sale['customer_id']);
        $customerStmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success
    echo json_encode([
        'status' => 'success',
        'message' => 'گەڕاندنەوەی کاڵا بە سەرکەوتوویی ئەنجام درا',
        'return_id' => $returnId,
        'total_amount' => $totalReturnAmount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 