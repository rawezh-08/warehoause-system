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
        ':reason' => isset($data['items'][0]['reason']) ? $data['items'][0]['reason'] : 'other',
        ':notes' => $data['notes'] ?? ''
    ]);

    $return_id = $conn->lastInsertId();
    $total_return_amount = 0;

    // Get receipt details
    if ($data['receipt_type'] === 'sale') {
        $stmt = $conn->prepare("
            SELECT id, customer_id, payment_type 
            FROM sales 
            WHERE id = ?
        ");
        $stmt->execute([$data['receipt_id']]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        $customer_id = $receipt['customer_id'] ?? null;
    } else {
        $stmt = $conn->prepare("
            SELECT id, supplier_id, payment_type 
            FROM purchases 
            WHERE id = ?
        ");
        $stmt->execute([$data['receipt_id']]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        $supplier_id = $receipt['supplier_id'] ?? null;
    }

    if (!$receipt) {
        throw new Exception('پسووڵەی داواکراو نەدۆزرایەوە');
        }

    // Process each returned item
    foreach ($data['items'] as $item) {
        // Validate item data
        if (empty($item['product_id']) || empty($item['quantity'])) {
            throw new Exception('Missing product_id or quantity in item data');
        }
        
        $product_id = $item['product_id'];
        $quantity = floatval($item['quantity']);
        $unit_type = $item['unit_type'] ?? 'piece';
        $unit_price = floatval($item['unit_price'] ?? 0);
        $reason = $item['reason'] ?? 'other';

        // Get original receipt item details
        if ($data['receipt_type'] === 'sale') {
            $stmt = $conn->prepare("
                SELECT quantity, returned_quantity, unit_price, unit_type
                FROM sale_items 
                WHERE sale_id = ? AND product_id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT quantity, returned_quantity, unit_price, unit_type
                FROM purchase_items 
                WHERE purchase_id = ? AND product_id = ?
            ");
        }
        $stmt->execute([$data['receipt_id'], $product_id]);
        $originalItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$originalItem) {
            throw new Exception('کاڵای داواکراو لە پسووڵەکەدا نییە');
        }

        // Get product details for unit conversion
        $stmt = $conn->prepare("
            SELECT pieces_per_box, boxes_per_set
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$product_id]);
        $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productDetails) {
            throw new Exception('کاڵای داواکراو نەدۆزرایەوە');
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
        $newReturnPieces = $convertToPieces($quantity, $unit_type);

        // Validate return quantity
        $availableToReturn = $originalPieces - $returnedPieces;
        if ($newReturnPieces > $availableToReturn) {
            throw new Exception('بڕی گەڕاندنەوە زیاترە لە بڕی بەردەست');
        }

        // Calculate return amount using original unit price
        $itemReturnAmount = $quantity * $originalItem['unit_price'];
        $total_return_amount += $itemReturnAmount;

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

        $total_price = $quantity * $unit_price;

        $stmt->execute([
            ':return_id' => $return_id,
            ':product_id' => $product_id,
            ':quantity' => $quantity,
            ':unit_price' => $originalItem['unit_price'], // Use original unit price
            ':unit_type' => $unit_type,
            ':original_unit_type' => $originalItem['unit_type'],
            ':original_quantity' => $quantity,
            ':reason' => $reason,
            ':total_price' => $itemReturnAmount
        ]);

        // Update returned quantity in original receipt items
        if ($data['receipt_type'] === 'sale') {
            $stmt = $conn->prepare("
                UPDATE sale_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity 
                WHERE sale_id = :receipt_id AND product_id = :product_id
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE purchase_items 
                SET returned_quantity = COALESCE(returned_quantity, 0) + :quantity 
                WHERE purchase_id = :receipt_id AND product_id = :product_id
            ");
        }

        $stmt->execute([
            ':quantity' => $quantity,
            ':receipt_id' => $data['receipt_id'],
            ':product_id' => $product_id
        ]);

        // Update product quantity
        if ($data['receipt_type'] === 'sale') {
            // For sales returns, add back to inventory
        $stmt = $conn->prepare("
            UPDATE products 
            SET current_quantity = current_quantity + :quantity
            WHERE id = :product_id
        ");
        } else {
            // For purchase returns, subtract from inventory
        $stmt = $conn->prepare("
                UPDATE products 
                SET current_quantity = current_quantity - :quantity 
                WHERE id = :product_id
            ");
        }

        $stmt->execute([
            ':quantity' => $quantity,
            ':product_id' => $product_id
        ]);
    }

    // Update receipt total and remaining amounts
    if ($data['receipt_type'] === 'sale') {
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
        
            // Update sales record
            $stmt = $conn->prepare("
                UPDATE sales 
                SET total_amount = :total_amount,
                    remaining_amount = CASE 
                        WHEN payment_type = 'credit' THEN GREATEST(0, :total_amount - paid_amount)
                        ELSE 0
                    END,
                    updated_at = NOW()
                WHERE id = :receipt_id
            ");
            
            $stmt->execute([
                ':total_amount' => $newTotalAmount,
                ':receipt_id' => $data['receipt_id']
            ]);
            
            // Update customer debt if credit sale
            if ($receiptDetails['payment_type'] === 'credit' && $customer_id) {
                // Get current customer debt
                $stmt = $conn->prepare("
                    SELECT debit_on_business FROM customers WHERE id = ?
            ");
                $stmt->execute([$customer_id]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);
            
                if ($customerData) {
                    $oldRemainingAmount = floatval($receiptDetails['remaining_amount']);
                    $newRemainingAmount = max(0, $newTotalAmount - floatval($receiptDetails['paid_amount']));
                    $debtAdjustment = $oldRemainingAmount - $newRemainingAmount;
                    
                    // Only update if there's an actual change
                    if ($debtAdjustment != 0) {
            // Update customer debt
            $stmt = $conn->prepare("
                UPDATE customers 
                            SET debit_on_business = GREATEST(0, debit_on_business - :adjustment),
                    updated_at = NOW()
                WHERE id = :customer_id
            ");
                        
            $stmt->execute([
                            ':adjustment' => $debtAdjustment,
                            ':customer_id' => $customer_id
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
                            ':customer_id' => $customer_id,
                            ':amount' => -$debtAdjustment, // Negative to reduce debt
                            ':return_id' => $return_id,
                            ':notes' => "گەڕاندنەوەی کاڵا - " . ($data['notes'] ?? '')
                        ]);
                    }
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
        
            // Update purchase record
            $stmt = $conn->prepare("
                UPDATE purchases 
                SET total_amount = :total_amount,
                    remaining_amount = CASE 
                        WHEN payment_type = 'credit' THEN GREATEST(0, :total_amount - paid_amount)
                        ELSE 0
                    END,
                    updated_at = NOW()
                WHERE id = :receipt_id
            ");
            
            $stmt->execute([
                ':total_amount' => $newTotalAmount,
                ':receipt_id' => $data['receipt_id']
            ]);
            
            // Update supplier debt if credit purchase
            if ($receiptDetails['payment_type'] === 'credit' && $supplier_id) {
                // Get current supplier debt
                $stmt = $conn->prepare("
                    SELECT debt_on_myself FROM suppliers WHERE id = ?
            ");
                $stmt->execute([$supplier_id]);
            $supplierData = $stmt->fetch(PDO::FETCH_ASSOC);
            
                if ($supplierData) {
                    $oldRemainingAmount = floatval($receiptDetails['remaining_amount']);
                    $newRemainingAmount = max(0, $newTotalAmount - floatval($receiptDetails['paid_amount']));
                    $debtAdjustment = $oldRemainingAmount - $newRemainingAmount;
                    
                    // Direct approach - always reduce debt by total_return_amount if payment_type is credit
                    if ($receiptDetails['payment_type'] === 'credit') {
                        // Update supplier debt directly using total_return_amount
                        $stmt = $conn->prepare("
                            UPDATE suppliers 
                            SET debt_on_myself = GREATEST(0, debt_on_myself - :adjustment),
                                updated_at = NOW()
                            WHERE id = :supplier_id
                        ");
                        
                        $stmt->execute([
                            ':adjustment' => $total_return_amount,
                            ':supplier_id' => $supplier_id
                        ]);
                        
                        // Log debug info
                        error_log("Supplier debt update - supplier_id: $supplier_id, old debt: " . $supplierData['debt_on_myself'] . 
                                 ", adjustment: $total_return_amount, new debt: " . 
                                 max(0, $supplierData['debt_on_myself'] - $total_return_amount));
                        
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
                            ':supplier_id' => $supplier_id,
                            ':amount' => -$total_return_amount, // Negative amount to reduce debt
                            ':return_id' => $return_id,
                            ':notes' => "گەڕاندنەوەی کاڵا - " . ($data['notes'] ?? '')
                        ]);
                    }
                }
            }
        }
    }

    // Directly update supplier debt with a direct SQL statement
    if ($data['receipt_type'] === 'purchase' && $supplier_id) {
        try {
            // Direct SQL approach to update the supplier's debt
            $stmt = $conn->prepare("
                UPDATE suppliers 
                SET 
                    debt_on_myself = GREATEST(0, debt_on_myself - :return_amount),
                    updated_at = NOW()
                WHERE id = :supplier_id
            ");
            
            $stmt->execute([
                ':return_amount' => $total_return_amount,
                ':supplier_id' => $supplier_id
            ]);

            $rowsAffected = $stmt->rowCount();
            error_log("Direct supplier debt update - supplier_id: $supplier_id, amount: $total_return_amount, rows affected: $rowsAffected");
            
            // Insert a record in supplier_debt_transactions
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
                ':supplier_id' => $supplier_id,
                ':amount' => -$total_return_amount, // Negative to reduce debt
                ':return_id' => $return_id,
                ':notes' => "گەڕاندنەوەی کاڵا - ڕاستەوخۆ (Direct Update)"
            ]);
        } catch (Exception $e) {
            error_log("Error in direct supplier debt update: " . $e->getMessage());
            // Continue execution, don't throw the exception to allow the rest of the process to complete
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