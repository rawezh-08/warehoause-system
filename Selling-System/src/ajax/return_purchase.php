<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    if (empty($_POST['purchase_id']) || empty($_POST['receipt_type'])) {
        throw new Exception('Missing required fields');
    }

    $purchase_id = intval($_POST['purchase_id']);
    $receipt_type = $_POST['receipt_type'];
    $reason = $_POST['reason'] ?? 'other';
    $notes = $_POST['notes'] ?? '';
    $return_quantities = $_POST['return_quantities'] ?? [];

    if (empty($return_quantities)) {
        throw new Exception('No items selected for return');
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
        ':receipt_id' => $purchase_id,
        ':receipt_type' => 'buying',
        ':reason' => $reason,
        ':notes' => $notes
    ]);

    $return_id = $conn->lastInsertId();
    $total_return_amount = 0;
    $returned_items = [];

    // Process each returned item
    foreach ($return_quantities as $item_id => $quantity) {
        if (floatval($quantity) <= 0) continue;

        // Get original purchase item details
        $stmt = $conn->prepare("
            SELECT pi.*, p.name as product_name, p.pieces_per_box, p.boxes_per_set
            FROM purchase_items pi
            JOIN products p ON pi.product_id = p.id
            WHERE pi.id = ? AND pi.purchase_id = ?
        ");
        $stmt->execute([$item_id, $purchase_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception('Invalid item ID');
        }

        // Calculate actual pieces count based on unit type
        $pieces_count = $quantity;
        if ($item['unit_type'] === 'box' && $item['pieces_per_box']) {
            $pieces_count *= $item['pieces_per_box'];
        } elseif ($item['unit_type'] === 'set' && $item['pieces_per_box'] && $item['boxes_per_set']) {
            $pieces_count *= ($item['pieces_per_box'] * $item['boxes_per_set']);
        }

        // Insert return item record
        $stmt = $conn->prepare("
            INSERT INTO return_items (
                return_id,
                product_id,
                quantity,
                unit_price,
                unit_type,
                original_unit_type,
                original_quantity,
                reason,
                total_price
            ) VALUES (
                :return_id,
                :product_id,
                :quantity,
                :unit_price,
                :unit_type,
                :original_unit_type,
                :original_quantity,
                :reason,
                :total_price
            )
        ");

        $total_price = $quantity * $item['unit_price'];
        $total_return_amount += $total_price;

        $stmt->execute([
            ':return_id' => $return_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $quantity,
            ':unit_price' => $item['unit_price'],
            ':unit_type' => $item['unit_type'],
            ':original_unit_type' => $item['unit_type'],
            ':original_quantity' => $quantity,
            ':reason' => $reason,
            ':total_price' => $total_price
        ]);

        // Update returned quantity in purchase items
        $stmt = $conn->prepare("
            UPDATE purchase_items 
            SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity 
            WHERE id = :item_id
        ");

        $stmt->execute([
            ':quantity' => $quantity,
            ':item_id' => $item_id
        ]);

        // Update product quantity
        $stmt = $conn->prepare("
            UPDATE products 
            SET current_quantity = current_quantity - :quantity 
            WHERE id = :product_id
        ");

        $stmt->execute([
            ':quantity' => $pieces_count,
            ':product_id' => $item['product_id']
        ]);

        // Record in inventory
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
                :reference_id,
                :notes
            )
        ");

        $stmt->execute([
            ':product_id' => $item['product_id'],
            ':quantity' => -$pieces_count, // Negative because items are going out
            ':reference_id' => $return_id,
            ':notes' => "گەڕاندنەوە: {$quantity} {$item['unit_type']} (ئەسڵی: {$quantity} {$item['unit_type']})"
        ]);

        // Add to returned items array for response
        $returned_items[] = [
            'product_name' => $item['product_name'],
            'returned_quantity' => $quantity,
            'unit_price' => $item['unit_price'],
            'total_price' => $total_price
        ];
    }

    // Update purchase total and remaining amounts
    $stmt = $conn->prepare("
        SELECT p.*, 
               COALESCE(p.paid_amount, 0) as paid_amount,
               COALESCE(p.remaining_amount, 0) as remaining_amount,
              (SELECT SUM((quantity - COALESCE(returned_quantity, 0)) * unit_price) 
               FROM purchase_items 
               WHERE purchase_id = p.id) as subtotal
        FROM purchases p 
        WHERE p.id = ?
    ");
    $stmt->execute([$purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($purchase) {
        // Calculate new total
        $newSubtotal = floatval($purchase['subtotal']);
        $newTotalAmount = $newSubtotal + 
                         floatval($purchase['shipping_cost'] ?? 0) + 
                         floatval($purchase['other_cost'] ?? 0) - 
                         floatval($purchase['discount'] ?? 0);

        // Calculate new remaining amount
        $paidAmount = floatval($purchase['paid_amount'] ?? 0);
        $newRemainingAmount = 0;

        if ($purchase['payment_type'] === 'credit') {
            $newRemainingAmount = max(0, $newTotalAmount - $paidAmount);
        }

        // Update purchase record
        $stmt = $conn->prepare("
            UPDATE purchases 
            SET remaining_amount = :remaining_amount,
                updated_at = NOW()
            WHERE id = :purchase_id
        ");

        $stmt->execute([
            ':remaining_amount' => $newRemainingAmount,
            ':purchase_id' => $purchase_id
        ]);

        // Update supplier debt if credit purchase
        if ($purchase['payment_type'] === 'credit' && $purchase['supplier_id']) {
            $oldRemainingAmount = floatval($purchase['remaining_amount'] ?? 0);
            $debtAdjustment = $oldRemainingAmount - $newRemainingAmount;

            if ($debtAdjustment > 0) {
                // Update supplier debt
                $stmt = $conn->prepare("
                    UPDATE suppliers 
                    SET debt_on_myself = GREATEST(0, debt_on_myself - :adjustment),
                        updated_at = NOW()
                    WHERE id = :supplier_id
                ");

                $stmt->execute([
                    ':adjustment' => $debtAdjustment,
                    ':supplier_id' => $purchase['supplier_id']
                ]);

                // Record debt transaction
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
                    ':supplier_id' => $purchase['supplier_id'],
                    ':amount' => -$debtAdjustment,
                    ':return_id' => $return_id,
                    ':notes' => "گەڕاندنەوەی کاڵا - " . $notes
                ]);
            }
        }
    }

    // Update total_amount in product_returns
    $stmt = $conn->prepare("
        UPDATE product_returns 
        SET total_amount = :total_amount 
        WHERE id = :return_id
    ");

    $stmt->execute([
        ':total_amount' => $total_return_amount,
        ':return_id' => $return_id
    ]);

    $conn->commit();

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'کاڵاکان بە سەرکەوتوویی گەڕێنرانەوە',
        'summary' => [
            'original_total' => floatval($purchase['remaining_amount']),
            'return_count' => count($returned_items),
            'returned_amount' => $total_return_amount,
            'returned_items' => $returned_items
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    if ($conn) {
        $conn->rollBack();
    }

    error_log("Error in return_purchase.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 