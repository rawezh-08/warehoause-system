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

        // Calculate return amount for this item
        $itemReturnAmount = $item['quantity'] * $item['unit_price'];
        $totalReturnAmount += $itemReturnAmount;

        // Get product details for unit conversion
        $stmt = $conn->prepare("
            SELECT pieces_per_box, boxes_per_set 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$item['product_id']]);
        $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate actual pieces count based on unit type
        $actualPiecesCount = $item['quantity']; // Default for 'piece'
        if ($item['unit_type'] === 'box' && $productDetails['pieces_per_box']) {
            $actualPiecesCount = $item['quantity'] * $productDetails['pieces_per_box'];
        } elseif ($item['unit_type'] === 'set' && $productDetails['pieces_per_box'] && $productDetails['boxes_per_set']) {
            $actualPiecesCount = $item['quantity'] * $productDetails['pieces_per_box'] * $productDetails['boxes_per_set'];
        }

        // Insert return item record
        $stmt = $conn->prepare("
            INSERT INTO return_items (
                return_id,
                product_id,
                quantity,
                unit_price,
                unit_type
            ) VALUES (
                :return_id,
                :product_id,
                :quantity,
                :unit_price,
                :unit_type
            )
        ");

        $stmt->execute([
            ':return_id' => $returnId,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':unit_price' => $item['unit_price'],
            ':unit_type' => $item['unit_type']
        ]);

        // Update returned quantity in original receipt items
        if ($receiptType === 'selling') {
            // Verify item exists in sale
            $stmt = $conn->prepare("
                SELECT quantity, returned_quantity, total_price, unit_price, unit_type, pieces_count 
                FROM sale_items 
                WHERE sale_id = ? AND product_id = ?
            ");
            $stmt->execute([$receiptId, $item['product_id']]);
            $saleItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$saleItem) {
                throw new Exception('کاڵای داواکراو لە پسووڵەکەدا نییە');
            }

            // Verify unit type matches
            if ($saleItem['unit_type'] !== $item['unit_type']) {
                throw new Exception('یەکەی گەڕاندنەوە دەبێت هەمان یەکەی کڕین بێت');
            }

            $availableToReturn = $saleItem['quantity'] - ($saleItem['returned_quantity'] ?? 0);
            if ($item['quantity'] > $availableToReturn) {
                throw new Exception('بڕی گەڕاندنەوە زیاترە لە بڕی بەردەست');
            }

            // Update sale_items with correct total_price calculation
            $stmt = $conn->prepare("
                UPDATE sale_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity,
                    total_price = (quantity - (COALESCE(returned_quantity, 0) + :quantity)) * unit_price
                WHERE sale_id = :receipt_id AND product_id = :product_id
            ");
        } else {
            // Similar validation for purchase items
            $stmt = $conn->prepare("
                SELECT quantity, returned_quantity, total_price, unit_price, unit_type 
                FROM purchase_items 
                WHERE purchase_id = ? AND product_id = ?
            ");
            $stmt->execute([$receiptId, $item['product_id']]);
            $purchaseItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchaseItem) {
                throw new Exception('کاڵای داواکراو لە پسووڵەکەدا نییە');
            }

            // Verify unit type matches
            if ($purchaseItem['unit_type'] !== $item['unit_type']) {
                throw new Exception('یەکەی گەڕاندنەوە دەبێت هەمان یەکەی کڕین بێت');
            }

            $availableToReturn = $purchaseItem['quantity'] - ($purchaseItem['returned_quantity'] ?? 0);
            if ($item['quantity'] > $availableToReturn) {
                throw new Exception('بڕی گەڕاندنەوە زیاترە لە بڕی بەردەست');
            }

            // Update purchase_items with correct total_price calculation
            $stmt = $conn->prepare("
                UPDATE purchase_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity,
                    total_price = (quantity - (COALESCE(returned_quantity, 0) + :quantity)) * unit_price
                WHERE purchase_id = :receipt_id AND product_id = :product_id
            ");
        }

        $stmt->execute([
            ':quantity' => $item['quantity'],
            ':receipt_id' => $receiptId,
            ':product_id' => $item['product_id']
        ]);

        // Update product quantity in inventory using actual pieces count
        $quantityChange = $receiptType === 'selling' ? $actualPiecesCount : -$actualPiecesCount;
        
        $stmt = $conn->prepare("
            UPDATE products 
            SET current_quantity = current_quantity + :quantity
            WHERE id = :product_id
        ");

        $stmt->execute([
            ':quantity' => $quantityChange,
            ':product_id' => $item['product_id']
        ]);

        // Record in inventory table using actual pieces count
        $stmt = $conn->prepare("
            INSERT INTO inventory (
                product_id,
                quantity,
                reference_type,
                reference_id
            ) VALUES (
                :product_id,
                :quantity,
                'return',
                :return_id
            )
        ");

        $stmt->execute([
            ':product_id' => $item['product_id'],
            ':quantity' => $quantityChange,
            ':return_id' => $returnId
        ]);
    }

    // Update remaining_amount in sales/purchases based on returned items
    if ($receiptType === 'selling') {
        $stmt = $conn->prepare("
            UPDATE sales 
            SET remaining_amount = remaining_amount - :return_amount
            WHERE id = :receipt_id AND payment_type = 'credit'
        ");
        $stmt->execute([
            ':return_amount' => $totalReturnAmount,
            ':receipt_id' => $receiptId
        ]);

        // Update customer debt if credit sale
        if ($receipt['payment_type'] === 'credit') {
            $stmt = $conn->prepare("
                UPDATE customers 
                SET debit_on_business = debit_on_business - :return_amount
                WHERE id = :customer_id
            ");
            $stmt->execute([
                ':return_amount' => $totalReturnAmount,
                ':customer_id' => $receipt['customer_id']
            ]);

            // Add debt transaction
            $stmt = $conn->prepare("
                INSERT INTO debt_transactions (
                    customer_id,
                    amount,
                    transaction_type,
                    reference_id,
                    notes
                ) VALUES (
                    :customer_id,
                    :amount,
                    'return',
                    :return_id,
                    :notes
                )
            ");
            $stmt->execute([
                ':customer_id' => $receipt['customer_id'],
                ':amount' => -$totalReturnAmount,
                ':return_id' => $returnId,
                ':notes' => "گەڕاندنەوەی کاڵا - " . $notes
            ]);
        }
    } else {
        $stmt = $conn->prepare("
            UPDATE purchases 
            SET remaining_amount = remaining_amount - :return_amount
            WHERE id = :receipt_id AND payment_type = 'credit'
        ");
        $stmt->execute([
            ':return_amount' => $totalReturnAmount,
            ':receipt_id' => $receiptId
        ]);

        // Update supplier debt if credit purchase
        if ($receipt['payment_type'] === 'credit') {
            $stmt = $conn->prepare("
                UPDATE suppliers 
                SET debt_on_myself = debt_on_myself - :return_amount
                WHERE id = :supplier_id
            ");
            $stmt->execute([
                ':return_amount' => $totalReturnAmount,
                ':supplier_id' => $receipt['supplier_id']
            ]);

            // Add supplier debt transaction
            $stmt = $conn->prepare("
                INSERT INTO supplier_debt_transactions (
                    supplier_id,
                    amount,
                    transaction_type,
                    reference_id,
                    notes
                ) VALUES (
                    :supplier_id,
                    :amount,
                    'return',
                    :return_id,
                    :notes
                )
            ");
            $stmt->execute([
                ':supplier_id' => $receipt['supplier_id'],
                ':amount' => -$totalReturnAmount,
                ':return_id' => $returnId,
                ':notes' => "گەڕاندنەوەی کاڵا - " . $notes
            ]);
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