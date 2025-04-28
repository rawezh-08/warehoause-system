<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Log the raw input for debugging
    error_log("process_return.php - Raw POST data: " . print_r($_POST, true));

    $data = $_POST;
    if (empty($data['receipt_id']) || empty($data['receipt_type'])) {
        throw new Exception('Missing receipt_id or receipt_type');
    }
    
    if (empty($data['items']) || !is_array($data['items'])) {
        // Check if items is a JSON string
        if (isset($data['items']) && is_string($data['items'])) {
            $data['items'] = json_decode($data['items'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid items format: ' . json_last_error_msg());
            }
    } else {
            throw new Exception('Missing or invalid items data');
        }
    }

    // Check if database connection is valid
    if (!$conn) {
        throw new Exception('Database connection error');
    }

    $conn->beginTransaction();

    // Check receipt type
    if ($data['receipt_type'] === 'selling') {
        // Process sale return
        $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
        $stmt->execute([$data['receipt_id']]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receipt) {
            throw new Exception('Sale receipt not found');
        }
        
        // Get sale items
        $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$data['receipt_id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Process purchase return
        $stmt = $conn->prepare("SELECT * FROM purchases WHERE id = ?");
        $stmt->execute([$data['receipt_id']]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receipt) {
            throw new Exception('Purchase receipt not found');
        }
        
        // Get purchase items
        $stmt = $conn->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
        $stmt->execute([$data['receipt_id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
        ':receipt_id' => $data['receipt_id'],
        ':receipt_type' => $data['receipt_type'],
        ':reason' => $data['reason'],
        ':notes' => $data['notes']
    ]);
    
    $return_id = $conn->lastInsertId();
    
    // Process each returned item
    foreach ($data['items'] as $item) {
        // Validate item data
        if (!isset($item['id']) || !isset($item['quantity'])) {
            continue;
        }
        
        // Get original item details
        if ($data['receipt_type'] === 'selling') {
            $stmt = $conn->prepare("
                SELECT si.*, p.name as product_name 
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                WHERE si.id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT pi.*, p.name as product_name 
                FROM purchase_items pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.id = ?
            ");
        }
        
        $stmt->execute([$item['id']]);
        $original_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$original_item) {
            continue;
        }
        
        // Insert return item record
        $stmt = $conn->prepare("
            INSERT INTO return_items (
                return_id,
                product_id,
                quantity,
                unit_price,
                reason,
                notes
            ) VALUES (
                :return_id,
                :product_id,
                :quantity,
                :unit_price,
                :reason,
                :notes
            )
        ");
        
        $stmt->execute([
            ':return_id' => $return_id,
            ':product_id' => $original_item['product_id'],
            ':quantity' => $item['quantity'],
            ':unit_price' => $original_item['unit_price'],
            ':reason' => $data['reason'],
            ':notes' => $data['notes']
        ]);
        
        // Update inventory
        $inventory_quantity = $data['receipt_type'] === 'selling' ? $item['quantity'] : -$item['quantity'];
        
        $stmt = $conn->prepare("
            UPDATE products 
            SET current_quantity = current_quantity + :quantity 
            WHERE id = :product_id
        ");
        
        $stmt->execute([
            ':quantity' => $inventory_quantity,
            ':product_id' => $original_item['product_id']
        ]);
        
        // Update returned quantity in original receipt
        if ($data['receipt_type'] === 'selling') {
            $stmt = $conn->prepare("
                UPDATE sale_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity 
                WHERE id = :item_id
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE purchase_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity 
                WHERE id = :item_id
            ");
        }
        
        $stmt->execute([
            ':quantity' => $item['quantity'],
            ':item_id' => $item['id']
        ]);
    }

    // Update receipt total and remaining amounts
    if ($data['receipt_type'] === 'selling') {
        // Get current sales data
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
        $stmt->execute([$data['receipt_id']]);
        $receiptDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($receiptDetails) {
            // Calculate new total
            $newSubtotal = floatval($receiptDetails['subtotal']);
            $newTotalAmount = $newSubtotal + 
                             floatval($receiptDetails['shipping_cost'] ?? 0) + 
                             floatval($receiptDetails['other_costs'] ?? 0) - 
                             floatval($receiptDetails['discount'] ?? 0);
            
            // Directly calculate new remaining amount
            $paidAmount = floatval($receiptDetails['paid_amount'] ?? 0);
            $newRemainingAmount = 0;
            
            if ($receiptDetails['payment_type'] === 'credit') {
                $newRemainingAmount = max(0, $newTotalAmount - $paidAmount);
            }
            
            // Log calculation details
            error_log("Sales Return Calculation - ID: {$data['receipt_id']}, Old Total: {$receiptDetails['total_amount']}, New Total: {$newTotalAmount}, Paid: {$paidAmount}, New Remaining: {$newRemainingAmount}");
            
            // Update sales record with pre-calculated values
            $stmt = $conn->prepare("
                UPDATE sales 
                SET total_amount = :total_amount,
                    remaining_amount = :remaining_amount,
                    updated_at = NOW()
                WHERE id = :receipt_id
            ");
            
            $stmt->execute([
                ':total_amount' => $newTotalAmount,
                ':remaining_amount' => $newRemainingAmount,
                ':receipt_id' => $data['receipt_id']
            ]);
            
            // Update customer debt if credit sale
            if ($receiptDetails['payment_type'] === 'credit' && $receipt['customer_id']) {
                // Calculate the difference in remaining amount (this is the actual debt change)
                $oldRemainingAmount = floatval($receiptDetails['remaining_amount'] ?? 0);
                $debtAdjustment = $oldRemainingAmount - $newRemainingAmount;
                
                // Only update if there's an actual change
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
                        ':customer_id' => $receipt['customer_id']
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
                        ':customer_id' => $receipt['customer_id'],
                        ':amount' => -$debtAdjustment, // Negative to reduce debt
                        ':return_id' => $return_id,
                        ':notes' => "گەڕاندنەوەی کاڵا - " . ($data['notes'] ?? '')
                    ]);
                    
                    // Log debug info
                    error_log("Customer debt update - customer_id: {$receipt['customer_id']}, old remaining: $oldRemainingAmount, new remaining: $newRemainingAmount, adjustment: $debtAdjustment");
                }
            }
        }
    } else {
        // Get current purchase data
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
        $stmt->execute([$data['receipt_id']]);
        $receiptDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($receiptDetails) {
            // Calculate new total
            $newSubtotal = floatval($receiptDetails['subtotal']);
            $newTotalAmount = $newSubtotal + 
                            floatval($receiptDetails['shipping_cost'] ?? 0) + 
                            floatval($receiptDetails['other_cost'] ?? 0) - 
                            floatval($receiptDetails['discount'] ?? 0);
            
            // Directly calculate new remaining amount
            $paidAmount = floatval($receiptDetails['paid_amount'] ?? 0);
            $newRemainingAmount = 0;
            
            if ($receiptDetails['payment_type'] === 'credit') {
                $newRemainingAmount = max(0, $newTotalAmount - $paidAmount);
            }
            
            // Log calculation details
            error_log("Purchase Return Calculation - ID: {$data['receipt_id']}, Old Total: {$receiptDetails['total_amount']}, New Total: {$newTotalAmount}, Paid: {$paidAmount}, New Remaining: {$newRemainingAmount}");
            
            // Update purchase record with pre-calculated values
            $stmt = $conn->prepare("
                UPDATE purchases 
                SET total_amount = :total_amount,
                    remaining_amount = :remaining_amount,
                    updated_at = NOW()
                WHERE id = :receipt_id
            ");
            
            $stmt->execute([
                ':total_amount' => $newTotalAmount,
                ':remaining_amount' => $newRemainingAmount,
                ':receipt_id' => $data['receipt_id']
            ]);
            
            // Update supplier debt if credit purchase
            if ($receiptDetails['payment_type'] === 'credit' && $receipt['supplier_id']) {
                // Calculate the difference in remaining amount (this is the actual debt change)
                $oldRemainingAmount = floatval($receiptDetails['remaining_amount'] ?? 0);
                $debtAdjustment = $oldRemainingAmount - $newRemainingAmount;
                
                // Only update if there's an actual change
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
                        ':supplier_id' => $receipt['supplier_id']
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
                        ':supplier_id' => $receipt['supplier_id'],
                        ':amount' => -$debtAdjustment, // Negative amount to reduce debt
                        ':return_id' => $return_id,
                        ':notes' => "گەڕاندنەوەی کاڵا - " . ($data['notes'] ?? '')
                    ]);
                    
                    // Log debug info
                    error_log("Supplier debt update - supplier_id: {$receipt['supplier_id']}, old remaining: $oldRemainingAmount, new remaining: $newRemainingAmount, adjustment: $debtAdjustment");
                }
            }
        }
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'کاڵاکان بە سەرکەوتوویی گەڕێنرانەوە',
        'return_amount' => $total_return_amount
    ]);

} catch (Exception $e) {
    if ($conn) {
        $conn->rollBack();
    }

    error_log("Error in process_return.php: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 