<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['receipt_id']) || !isset($_POST['receipt_type']) || !isset($_POST['items'])) {
        throw new Exception('داواکاری نادروستە');
    }

    $receipt_id = intval($_POST['receipt_id']);
    $receipt_type = $_POST['receipt_type'];
    $items = json_decode($_POST['items'], true);
    $notes = $_POST['notes'] ?? '';

    if (!is_array($items) || empty($items)) {
        throw new Exception('هیچ کاڵایەک دیاری نەکراوە');
    }

    if (!in_array($receipt_type, ['sale', 'purchase'])) {
        throw new Exception('جۆری پسووڵە نادروستە');
    }

    // Start transaction
    $conn->beginTransaction();

    // Create return record
    $stmt = $conn->prepare("
        INSERT INTO product_returns (receipt_id, receipt_type, return_date, notes)
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$receipt_id, $receipt_type, $notes]);
    $return_id = $conn->lastInsertId();

    foreach ($items as $item) {
        // Get product details
        $stmt = $conn->prepare("
            SELECT pieces_per_box, boxes_per_set 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate actual pieces count based on unit type
        $pieces_count = $item['quantity'];
        if ($item['unit_type'] === 'box') {
            if (empty($product['pieces_per_box'])) {
                throw new Exception('هەڵەیەک هەیە لە ژمارەی دانەکان لە کارتۆن بۆ ئەم کاڵایە');
            }
            $pieces_count *= $product['pieces_per_box'];
        } elseif ($item['unit_type'] === 'set') {
            if (empty($product['pieces_per_box']) || empty($product['boxes_per_set'])) {
                throw new Exception('هەڵەیەک هەیە لە ژمارەی دانەکان/کارتۆنەکان بۆ ئەم کاڵایە');
            }
            $pieces_count *= ($product['pieces_per_box'] * $product['boxes_per_set']);
        }

        // Add return items record
        $stmt = $conn->prepare("
            INSERT INTO return_items (return_id, product_id, quantity, unit_type, unit_price)
            VALUES (?, ?, ?, ?, (
                SELECT " . ($receipt_type === 'sale' ? 'unit_price' : 'unit_price') . "
                FROM " . ($receipt_type === 'sale' ? 'sale_items' : 'purchase_items') . "
                WHERE " . ($receipt_type === 'sale' ? 'sale_id' : 'purchase_id') . " = ?
                AND product_id = ?
                LIMIT 1
            ))
        ");
        $stmt->execute([$return_id, $item['product_id'], $item['quantity'], $item['unit_type'], $receipt_id, $item['product_id']]);

        // Update returned quantity in original receipt items
        if ($receipt_type === 'sale') {
            $stmt = $conn->prepare("
                UPDATE sale_items 
                SET returned_quantity = IFNULL(returned_quantity, 0) + ?
                WHERE sale_id = ? AND product_id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE purchase_items 
                SET returned_quantity = IFNULL(returned_quantity, 0) + ?
                WHERE purchase_id = ? AND product_id = ?
            ");
        }
        $stmt->execute([$item['quantity'], $receipt_id, $item['product_id']]);

        // Update product quantity
        if ($receipt_type === 'sale') {
            // For sales returns, subtract from inventory (items coming back)
            $stmt = $conn->prepare("
                UPDATE products 
                SET current_quantity = current_quantity - ?
                WHERE id = ?
            ");
        } else {
            // For purchase returns, add to inventory (items going out)
            $stmt = $conn->prepare("
                UPDATE products 
                SET current_quantity = current_quantity + ?
                WHERE id = ?
            ");
        }
        $stmt->execute([$pieces_count, $item['product_id']]);

        // Record in inventory
        $stmt = $conn->prepare("
            INSERT INTO inventory (product_id, quantity, reference_type, reference_id)
            VALUES (?, ?, 'return', ?)
        ");
        $stmt->execute([
            $item['product_id'],
            $receipt_type === 'sale' ? -$pieces_count : $pieces_count,
            $return_id
        ]);
    }

    // Update debt/payment records if needed
    if ($receipt_type === 'sale') {
        // Get sale details
        $stmt = $conn->prepare("
            SELECT customer_id, payment_type
            FROM sales
            WHERE id = ?
        ");
        $stmt->execute([$receipt_id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale['payment_type'] === 'credit') {
            // Calculate total return amount
            $stmt = $conn->prepare("
                SELECT SUM(quantity * unit_price) as total_amount
                FROM return_items
                WHERE return_id = ?
            ");
            $stmt->execute([$return_id]);
            $return_amount = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'];

            // Add debt transaction
            $stmt = $conn->prepare("
                INSERT INTO debt_transactions (customer_id, amount, transaction_type, reference_id, notes)
                VALUES (?, ?, 'return', ?, ?)
            ");
            $stmt->execute([$sale['customer_id'], -$return_amount, $return_id, $notes]);

            // Update customer debt
            $stmt = $conn->prepare("
                UPDATE customers
                SET debit_on_business = debit_on_business - ?
                WHERE id = ?
            ");
            $stmt->execute([$return_amount, $sale['customer_id']]);
        }
    } else {
        // Similar process for purchase returns
        $stmt = $conn->prepare("
            SELECT supplier_id, payment_type
            FROM purchases
            WHERE id = ?
        ");
        $stmt->execute([$receipt_id]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($purchase['payment_type'] === 'credit') {
            // Calculate total return amount
            $stmt = $conn->prepare("
                SELECT SUM(quantity * unit_price) as total_amount
                FROM return_items
                WHERE return_id = ?
            ");
            $stmt->execute([$return_id]);
            $return_amount = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'];

            // Add supplier debt transaction
            $stmt = $conn->prepare("
                INSERT INTO supplier_debt_transactions (supplier_id, amount, transaction_type, reference_id, notes)
                VALUES (?, ?, 'return', ?, ?)
            ");
            $stmt->execute([$purchase['supplier_id'], -$return_amount, $return_id, $notes]);

            // Update supplier debt
            $stmt = $conn->prepare("
                UPDATE suppliers
                SET debt_on_myself = debt_on_myself - ?
                WHERE id = ?
            ");
            $stmt->execute([$return_amount, $purchase['supplier_id']]);
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'کاڵاکان بە سەرکەوتوویی گەڕێنرانەوە'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 