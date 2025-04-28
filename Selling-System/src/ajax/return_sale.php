<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    if (empty($_POST['sale_id']) || empty($_POST['receipt_type'])) {
        throw new Exception('Missing required fields');
    }

    $sale_id = intval($_POST['sale_id']);
    $receipt_type = 'selling';
    $reason = $_POST['reason'] ?? 'other';
    $notes = $_POST['notes'] ?? '';
    $return_quantities = $_POST['return_quantities'] ?? [];

    if (empty($return_quantities)) {
        throw new Exception('No items selected for return');
    }

    $database = new Database();
    $conn = $database->getConnection();
    
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
        ':receipt_id' => $sale_id,
        ':receipt_type' => $receipt_type,
        ':reason' => $reason,
        ':notes' => $notes
    ]);

    $return_id = $conn->lastInsertId();
    $total_return_amount = 0;
    $returned_items = [];

    // Process each returned item
    foreach ($return_quantities as $item_id => $quantity) {
        if (floatval($quantity) <= 0) continue;

        // Get original sale item details
        $stmt = $conn->prepare("
            SELECT si.*, p.name as product_name, p.pieces_per_box, p.boxes_per_set
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.id = ? AND si.sale_id = ?
        ");
        $stmt->execute([$item_id, $sale_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception('Invalid item ID');
        }

        // Check if return quantity is valid (not exceeding original sale minus already returned)
        $originalQuantity = floatval($item['quantity']);
        $alreadyReturned = floatval($item['returned_quantity'] ?? 0);
        $maxReturnable = $originalQuantity - $alreadyReturned;
        
        if (floatval($quantity) > $maxReturnable) {
            throw new Exception('بڕی داواکراو بۆ گەڕاندنەوە زیاترە لە بڕی بەردەست. بۆ ' . 
                htmlspecialchars($item['product_name']) . 
                '، تەنها ' . $maxReturnable . ' ' . $item['unit_type'] . ' بەردەستە بۆ گەڕاندنەوە');
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

        // Update returned quantity in sale items
        $stmt = $conn->prepare("
            UPDATE sale_items 
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
            SET current_quantity = current_quantity + :quantity 
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
            ':quantity' => $pieces_count, // Positive because items are coming back
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

    // Update sale total and remaining amounts
    $stmt = $conn->prepare("
        SELECT s.*, 
               COALESCE(s.paid_amount, 0) as paid_amount,
               COALESCE(s.remaining_amount, 0) as remaining_amount,
              (SELECT SUM((quantity - COALESCE(returned_quantity, 0)) * unit_price) 
               FROM sale_items 
               WHERE sale_id = s.id) as subtotal
        FROM sales s 
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sale) {
        // Calculate new total
        $newSubtotal = floatval($sale['subtotal']);
        $newTotalAmount = $newSubtotal + 
                         floatval($sale['shipping_cost'] ?? 0) + 
                         floatval($sale['other_costs'] ?? 0) - 
                         floatval($sale['discount'] ?? 0);

        // Calculate new remaining amount
        $paidAmount = floatval($sale['paid_amount'] ?? 0);
        $newRemainingAmount = 0;

        if ($sale['payment_type'] === 'credit') {
            $newRemainingAmount = max(0, $newTotalAmount - $paidAmount);
        }

        // Update sale record
        $stmt = $conn->prepare("
            UPDATE sales 
            SET remaining_amount = :remaining_amount,
                updated_at = NOW()
            WHERE id = :sale_id
        ");

        $stmt->execute([
            ':remaining_amount' => $newRemainingAmount,
            ':sale_id' => $sale_id
        ]);

        // Update customer debt if credit sale
        if ($sale['payment_type'] === 'credit' && $sale['customer_id']) {
            $oldRemainingAmount = floatval($sale['remaining_amount'] ?? 0);
            $debtAdjustment = $oldRemainingAmount - $newRemainingAmount;

            if ($debtAdjustment > 0) {
                // Update customer debt
                $stmt = $conn->prepare("
                    UPDATE customers 
                    SET debit_on_business = GREATEST(0, debit_on_business - :adjustment),
                        updated_at = NOW()
                    WHERE id = :customer_id
                ");

                $stmt->execute([
                    ':adjustment' => $debtAdjustment,
                    ':customer_id' => $sale['customer_id']
                ]);

                // Record debt transaction
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
                    ':customer_id' => $sale['customer_id'],
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
            'original_total' => floatval($sale['remaining_amount']),
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

    error_log("Error in return_sale.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 