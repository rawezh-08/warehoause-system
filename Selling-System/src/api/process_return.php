<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Log incoming data
    error_log("Received POST data: " . print_r($_POST, true));

    // Get return data
    $receiptId = $_POST['receipt_id'] ?? null;
    $receiptType = $_POST['receipt_type'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $items = json_decode($_POST['items'], true);

    // Validate data
    if (!$receiptId) {
        throw new Exception('ژمارەی پسووڵە پێویستە');
    }
    if (!$receiptType) {
        throw new Exception('جۆری پسووڵە پێویستە');
    }
    if (!$reason) {
        throw new Exception('هۆکاری گەڕاندنەوە پێویستە');
    }
    if (!$items || !is_array($items) || empty($items)) {
        throw new Exception('هیچ کاڵایەک دیاری نەکراوە بۆ گەڕاندنەوە');
    }

    // Log decoded items
    error_log("Decoded items: " . print_r($items, true));

    // Validate receipt exists and get its details
    if ($receiptType === 'selling') {
        $stmt = $conn->prepare("SELECT id, payment_type, customer_id FROM sales WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, payment_type, supplier_id FROM purchases WHERE id = ?");
    }
    $stmt->execute([$receiptId]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt) {
        throw new Exception('پسووڵەی داواکراو نەدۆزرایەوە');
    }

    // Start transaction
    $conn->beginTransaction();

    // Create return record
    $stmt = $conn->prepare("
        INSERT INTO product_returns (
            receipt_id, 
            receipt_type, 
            return_date,
            reason,
            notes
        ) VALUES (
            :receipt_id,
            :receipt_type,
            NOW(),
            :reason,
            :notes
        )
    ");

    $stmt->execute([
        ':receipt_id' => $receiptId,
        ':receipt_type' => $receiptType,
        ':reason' => $reason,
        ':notes' => $notes
    ]);

    $returnId = $conn->lastInsertId();
    $totalReturnAmount = 0;

    // Process each returned item
    foreach ($items as $item) {
        // Validate item data
        if (!isset($item['product_id'], $item['quantity'], $item['unit_price'], $item['unit_type'])) {
            throw new Exception('داتای کاڵاکان ناتەواوە');
        }

        // Get product details for unit conversion
        $stmt = $conn->prepare("
            SELECT pieces_per_box, boxes_per_set, current_quantity 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$item['product_id']]);
        $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productDetails) {
            throw new Exception('کاڵای داواکراو نەدۆزرایەوە');
        }

        // Get original receipt item details
        if ($receiptType === 'selling') {
            $stmt = $conn->prepare("
                SELECT quantity, returned_quantity, unit_price, unit_type, total_price
                FROM sale_items 
                WHERE sale_id = ? AND product_id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT quantity, returned_quantity, unit_price, unit_type, total_price
                FROM purchase_items 
                WHERE purchase_id = ? AND product_id = ?
            ");
        }
        $stmt->execute([$receiptId, $item['product_id']]);
        $originalItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$originalItem) {
            throw new Exception('کاڵای داواکراو لە پسووڵەکەدا نییە');
        }

        // Convert all quantities to pieces for comparison
        $convertToPieces = function($quantity, $unitType) use ($productDetails) {
            if ($unitType === 'piece') return $quantity;
            if ($unitType === 'box' && $productDetails['pieces_per_box']) {
                return $quantity * $productDetails['pieces_per_box'];
            }
            if ($unitType === 'set' && $productDetails['pieces_per_box'] && $productDetails['boxes_per_set']) {
                return $quantity * $productDetails['pieces_per_box'] * $productDetails['boxes_per_set'];
            }
            return $quantity;
        };

        // Convert quantities to pieces
        $originalPieces = $convertToPieces($originalItem['quantity'], $originalItem['unit_type']);
        $returnedPieces = $convertToPieces($originalItem['returned_quantity'] ?? 0, $originalItem['unit_type']);
        $newReturnPieces = $convertToPieces($item['quantity'], $item['unit_type']);

        // Validate return quantity
        $availableToReturn = $originalPieces - $returnedPieces;
        if ($newReturnPieces > $availableToReturn) {
            throw new Exception('بڕی گەڕاندنەوە زیاترە لە بڕی بەردەست');
        }

        // Calculate return amount based on original unit price and returned quantity
        $itemReturnAmount = $item['quantity'] * $originalItem['unit_price'];
        $totalReturnAmount += $itemReturnAmount;

        // Calculate the equivalent quantity in the original unit type
        $convertFromPieces = function($pieces, $targetUnitType) use ($productDetails) {
            if ($targetUnitType === 'piece') return $pieces;
            if ($targetUnitType === 'box' && $productDetails['pieces_per_box']) {
                return $pieces / $productDetails['pieces_per_box'];
            }
            if ($targetUnitType === 'set' && $productDetails['pieces_per_box'] && $productDetails['boxes_per_set']) {
                return $pieces / ($productDetails['pieces_per_box'] * $productDetails['boxes_per_set']);
            }
            return $pieces;
        };

        $equivalentQuantity = $convertFromPieces($newReturnPieces, $originalItem['unit_type']);

        // Insert return item record with correct unit price from original sale/purchase
        $stmt = $conn->prepare("
            INSERT INTO return_items (
                return_id,
                product_id,
                quantity,
                unit_price,
                unit_type,
                original_unit_type,
                original_quantity,
                total_price
            ) VALUES (
                :return_id,
                :product_id,
                :quantity,
                :unit_price,
                :unit_type,
                :original_unit_type,
                :original_quantity,
                :total_price
            )
        ");

        $stmt->execute([
            ':return_id' => $returnId,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':unit_price' => $originalItem['unit_price'],  // Use original unit price
            ':unit_type' => $item['unit_type'],
            ':original_unit_type' => $originalItem['unit_type'],
            ':original_quantity' => $equivalentQuantity,
            ':total_price' => $itemReturnAmount
        ]);

        // Update returned quantity in original receipt items
        if ($receiptType === 'selling') {
            $stmt = $conn->prepare("
                UPDATE sale_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity,
                    total_price = (quantity - (COALESCE(returned_quantity, 0) + :quantity)) * unit_price
                WHERE sale_id = :receipt_id AND product_id = :product_id
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE purchase_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity,
                    total_price = (quantity - (COALESCE(returned_quantity, 0) + :quantity)) * unit_price
                WHERE purchase_id = :receipt_id AND product_id = :product_id
            ");
        }

        $stmt->execute([
            ':quantity' => $equivalentQuantity,
            ':receipt_id' => $receiptId,
            ':product_id' => $item['product_id']
        ]);

        // Update product quantity in inventory
        $quantityChange = $receiptType === 'selling' ? $newReturnPieces : -$newReturnPieces;
        
        $stmt = $conn->prepare("
            UPDATE products 
            SET current_quantity = current_quantity + :quantity
            WHERE id = :product_id
        ");

        $stmt->execute([
            ':quantity' => $quantityChange,
            ':product_id' => $item['product_id']
        ]);

        // Record in inventory table
        $stmt = $conn->prepare("
            INSERT INTO inventory (
                product_id,
                quantity,
                reference_type,
                reference_id,
                notes
            ) VALUES (
                :product_id,
                :quantity,
                'return',
                :return_id,
                :notes
            )
        ");

        $stmt->execute([
            ':product_id' => $item['product_id'],
            ':quantity' => $quantityChange,
            ':return_id' => $returnId,
            ':notes' => "گەڕاندنەوە: {$item['quantity']} {$item['unit_type']} (ئەسڵی: {$equivalentQuantity} {$originalItem['unit_type']})"
        ]);
    }

    // Update remaining_amount in sales/purchases based on returned items
    if ($receiptType === 'selling') {
        // Get current sale data for accurate calculations
        $stmt = $conn->prepare("
            SELECT s.*, 
                   COALESCE(s.paid_amount, 0) as paid_amount,
                   COALESCE(s.remaining_amount, 0) as remaining_amount,
                   COALESCE(s.shipping_cost, 0) as shipping_cost,
                   COALESCE(s.other_costs, 0) as other_costs,
                   COALESCE(s.discount, 0) as discount,
                   (SELECT SUM(
                        (si.quantity - COALESCE(si.returned_quantity, 0)) * si.unit_price
                    ) 
                    FROM sale_items si WHERE si.sale_id = s.id) as actual_subtotal
            FROM sales s 
            WHERE s.id = ?
        ");
        $stmt->execute([$receiptId]);
        $saleDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$saleDetails) {
            throw new Exception('سەیڵ نەدۆزرایەوە');
        }
        
        // Calculate the correct subtotal based on items
        $newSubtotal = floatval($saleDetails['actual_subtotal'] ?? 0);
        
        // Calculate the new total amount including other costs
        $newTotalAmount = $newSubtotal + 
                        floatval($saleDetails['shipping_cost'] ?? 0) + 
                        floatval($saleDetails['other_costs'] ?? 0) - 
                        floatval($saleDetails['discount'] ?? 0);
        
        // Calculate new remaining amount for credit sales
        $newRemainingAmount = 0;
        if ($saleDetails['payment_type'] === 'credit') {
            // For credit sales, recalculate the remaining amount
            $newRemainingAmount = max(0, $newTotalAmount - floatval($saleDetails['paid_amount'] ?? 0));
        }
        
        error_log("New subtotal: $newSubtotal, New total: $newTotalAmount, New remaining: $newRemainingAmount");
        
        // Prepare SQL fields to update
        $updateFields = [];
        $params = [];
        
        // Always include remaining_amount and updated_at
        $updateFields[] = "remaining_amount = :remaining_amount";
        $params[':remaining_amount'] = $newRemainingAmount;
        
        // Add fields conditionally based on database structure
        try {
            // Check if subtotal column exists
            $checkStmt = $conn->prepare("SHOW COLUMNS FROM sales LIKE 'subtotal'");
            $checkStmt->execute();
            if ($checkStmt->rowCount() > 0) {
                $updateFields[] = "subtotal = :subtotal";
                $params[':subtotal'] = $newSubtotal;
            }
            
            // Check if total_amount column exists
            $checkStmt = $conn->prepare("SHOW COLUMNS FROM sales LIKE 'total_amount'");
            $checkStmt->execute();
            if ($checkStmt->rowCount() > 0) {
                $updateFields[] = "total_amount = :total_amount";
                $params[':total_amount'] = $newTotalAmount;
            }
            
            // Always include updated_at
            $updateFields[] = "updated_at = NOW()";
        } catch (Exception $e) {
            // If column check fails, just update remaining_amount
            error_log("Error checking columns: " . $e->getMessage());
        }
        
        $params[':receipt_id'] = $receiptId;
        
        // Build and execute update query
        $updateQuery = "UPDATE sales SET " . implode(", ", $updateFields) . " WHERE id = :receipt_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute($params);
        
        // Log the update
        error_log("Sale update query: $updateQuery with params: " . print_r($params, true));

        // Update customer debt if credit sale
        if ($receipt['payment_type'] === 'credit') {
            // Get current customer debt
            $stmt = $conn->prepare("
                SELECT debit_on_business
                FROM customers 
                WHERE id = ?
            ");
            $stmt->execute([$receipt['customer_id']]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate new debt by subtracting the difference between old and new remaining amounts
            $debtAdjustment = floatval($saleDetails['remaining_amount']) - $newRemainingAmount;
            $newCustomerDebt = max(0, floatval($customerData['debit_on_business']) - $debtAdjustment);
            
            // Update customer debt
            $stmt = $conn->prepare("
                UPDATE customers 
                SET debit_on_business = :new_debt,
                    updated_at = NOW()
                WHERE id = :customer_id
            ");
            $stmt->execute([
                ':new_debt' => $newCustomerDebt,
                ':customer_id' => $receipt['customer_id']
            ]);

            // Add debt transaction with correct amount (negative to reduce debt)
            $stmt = $conn->prepare("
                INSERT INTO debt_transactions (
                    customer_id,
                    amount,
                    transaction_type,
                    reference_id,
                    notes,
                    created_at
                ) VALUES (
                    :customer_id,
                    :amount,
                    'return',
                    :return_id,
                    :notes,
                    NOW()
                )
            ");
            $stmt->execute([
                ':customer_id' => $receipt['customer_id'],
                ':amount' => -$debtAdjustment, // Negative amount to reduce debt
                ':return_id' => $returnId,
                ':notes' => "گەڕاندنەوەی کاڵا - " . $notes
            ]);
            
            // Store the debt adjustment for response
            $totalReturnAmount = $debtAdjustment;
        }
    } else {
        // Similar update logic for purchases with appropriate adjustments
        // Get current purchase data for accurate calculations
        $stmt = $conn->prepare("
            SELECT p.*, 
                   COALESCE(p.paid_amount, 0) as paid_amount,
                   COALESCE(p.remaining_amount, 0) as remaining_amount,
                   COALESCE(p.shipping_cost, 0) as shipping_cost,
                   COALESCE(p.other_cost, 0) as other_cost,
                   COALESCE(p.discount, 0) as discount,
                   (SELECT SUM(
                        (pi.quantity - COALESCE(pi.returned_quantity, 0)) * pi.unit_price
                    ) 
                    FROM purchase_items pi WHERE pi.purchase_id = p.id) as actual_subtotal
            FROM purchases p 
            WHERE p.id = ?
        ");
        $stmt->execute([$receiptId]);
        $purchaseDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchaseDetails) {
            throw new Exception('پێرچس نەدۆزرایەوە');
        }
        
        // Calculate the correct subtotal based on items
        $newSubtotal = floatval($purchaseDetails['actual_subtotal'] ?? 0);
        
        // Calculate the new total amount including other costs
        $newTotalAmount = $newSubtotal + 
                       floatval($purchaseDetails['shipping_cost'] ?? 0) + 
                       floatval($purchaseDetails['other_cost'] ?? 0) - 
                       floatval($purchaseDetails['discount'] ?? 0);
        
        // Calculate new remaining amount for credit purchases
        $newRemainingAmount = 0;
        if ($purchaseDetails['payment_type'] === 'credit') {
            // For credit purchases, recalculate the remaining amount
            $newRemainingAmount = max(0, $newTotalAmount - floatval($purchaseDetails['paid_amount'] ?? 0));
        }
        
        error_log("Purchase - New subtotal: $newSubtotal, New total: $newTotalAmount, New remaining: $newRemainingAmount");
        
        // Prepare SQL fields to update
        $updateFields = [];
        $params = [];
        
        // Always include remaining_amount and updated_at
        $updateFields[] = "remaining_amount = :remaining_amount";
        $params[':remaining_amount'] = $newRemainingAmount;
        
        // Add fields conditionally based on database structure
        try {
            // Check if subtotal column exists
            $checkStmt = $conn->prepare("SHOW COLUMNS FROM purchases LIKE 'subtotal'");
            $checkStmt->execute();
            if ($checkStmt->rowCount() > 0) {
                $updateFields[] = "subtotal = :subtotal";
                $params[':subtotal'] = $newSubtotal;
            }
            
            // Check if total_amount column exists
            $checkStmt = $conn->prepare("SHOW COLUMNS FROM purchases LIKE 'total_amount'");
            $checkStmt->execute();
            if ($checkStmt->rowCount() > 0) {
                $updateFields[] = "total_amount = :total_amount";
                $params[':total_amount'] = $newTotalAmount;
            }
            
            // Always include updated_at
            $updateFields[] = "updated_at = NOW()";
        } catch (Exception $e) {
            // If column check fails, just update remaining_amount
            error_log("Error checking columns: " . $e->getMessage());
        }
        
        $params[':receipt_id'] = $receiptId;
        
        // Build and execute update query
        $updateQuery = "UPDATE purchases SET " . implode(", ", $updateFields) . " WHERE id = :receipt_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute($params);
        
        // Log the update
        error_log("Purchase update query: $updateQuery with params: " . print_r($params, true));

        // Update supplier debt if credit purchase
        if ($receipt['payment_type'] === 'credit') {
            // Get current supplier debt
            $stmt = $conn->prepare("
                SELECT debt_on_myself
                FROM suppliers 
                WHERE id = ?
            ");
            $stmt->execute([$receipt['supplier_id']]);
            $supplierData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate new debt by subtracting the difference between old and new remaining amounts
            $debtAdjustment = floatval($purchaseDetails['remaining_amount']) - $newRemainingAmount;
            $newSupplierDebt = max(0, floatval($supplierData['debt_on_myself']) - $debtAdjustment);
            
            // Update supplier debt
            $stmt = $conn->prepare("
                UPDATE suppliers 
                SET debt_on_myself = :new_debt,
                    updated_at = NOW()
                WHERE id = :supplier_id
            ");
            $stmt->execute([
                ':new_debt' => $newSupplierDebt,
                ':supplier_id' => $receipt['supplier_id']
            ]);

            // Add supplier debt transaction with correct amount
            $stmt = $conn->prepare("
                INSERT INTO supplier_debt_transactions (
                    supplier_id,
                    amount,
                    transaction_type,
                    reference_id,
                    notes,
                    created_at
                ) VALUES (
                    :supplier_id,
                    :amount,
                    'return',
                    :return_id,
                    :notes,
                    NOW()
                )
            ");
            $stmt->execute([
                ':supplier_id' => $receipt['supplier_id'],
                ':amount' => -$debtAdjustment, // Negative amount to reduce debt
                ':return_id' => $returnId,
                ':notes' => "گەڕاندنەوەی کاڵا - " . $notes
            ]);
            
            // Store the debt adjustment for response
            $totalReturnAmount = $debtAdjustment;
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'گەڕاندنەوەی کاڵاکان بە سەرکەوتوویی تۆمار کرا',
        'return_amount' => $totalReturnAmount
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Return Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 