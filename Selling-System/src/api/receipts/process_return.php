<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
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
            ':unit_price' => $unit_price,
            ':unit_type' => $unit_type,
            ':original_unit_type' => $unit_type,
            ':original_quantity' => $quantity,
            ':reason' => $reason,
            ':total_price' => $total_price
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
                :reference_id,
                :notes
            )
        ");

        $inventory_quantity = $data['receipt_type'] === 'sale' ? $quantity : -$quantity;
        $notes = "گەڕاندنەوە: {$quantity} {$unit_type} (ئەسڵی: {$quantity} {$unit_type})";

        $stmt->execute([
            ':product_id' => $product_id,
            ':quantity' => $inventory_quantity,
            ':reference_id' => $return_id,
            ':notes' => $notes
        ]);
    }

    // If it's a sale return, update customer debt
    if ($data['receipt_type'] === 'sale') {
        // Get sale information
        $stmt = $conn->prepare("
            SELECT customer_id, payment_type 
            FROM sales 
            WHERE id = :sale_id
        ");
        $stmt->execute([':sale_id' => $data['receipt_id']]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale && $sale['payment_type'] === 'credit') {
            // Calculate total return amount
            $total_return_amount = 0;
            foreach ($data['items'] as $item) {
                $total_return_amount += floatval($item['quantity']) * floatval($item['unit_price'] ?? 0);
            }

            // Add debt transaction if applicable
            if ($total_return_amount > 0) {
                // Add debt transaction
                $stmt = $conn->prepare("
                    INSERT INTO debt_transactions (
                        customer_id,
                        amount,
                        transaction_type,
                        reference_id,
                        notes,
                        created_by
                    ) VALUES (
                        :customer_id,
                        :amount,
                        'return',
                        :reference_id,
                        :notes,
                        :created_by
                    )
                ");

                $stmt->execute([
                    ':customer_id' => $sale['customer_id'],
                    ':amount' => -$total_return_amount, // Negative amount to reduce debt
                    ':reference_id' => $return_id,
                    ':notes' => 'گەڕاندنەوەی کاڵا - ' . ($data['notes'] ?? ''),
                    ':created_by' => null // You might want to add proper user authentication
                ]);

                // Update customer debt
                $stmt = $conn->prepare("
                    UPDATE customers 
                    SET debit_on_business = debit_on_business - :amount 
                    WHERE id = :customer_id
                ");

                $stmt->execute([
                    ':amount' => $total_return_amount,
                    ':customer_id' => $sale['customer_id']
                ]);
            }
        }
    }

    $conn->commit();
    error_log("Return processed successfully. Return ID: $return_id");
    echo json_encode(['success' => true, 'message' => 'Return processed successfully']);

} catch (Exception $e) {
    error_log("Error in process_return.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} 