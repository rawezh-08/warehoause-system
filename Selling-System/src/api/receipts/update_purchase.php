<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    // Validate required fields
    if (!isset($_POST['id']) || !isset($_POST['invoice_number']) || !isset($_POST['supplier_id']) || 
        !isset($_POST['date']) || !isset($_POST['payment_type'])) {
        throw new Exception('هەموو خانەکان پێویستن');
    }

    $id = intval($_POST['id']);
    $invoice_number = $_POST['invoice_number'];
    $supplier_id = intval($_POST['supplier_id']);
    $date = $_POST['date'];
    $payment_type = $_POST['payment_type'];
    $shipping_cost = isset($_POST['shipping_cost']) ? floatval($_POST['shipping_cost']) : 0;
    $other_cost = isset($_POST['other_cost']) ? floatval($_POST['other_cost']) : 0;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    // Start transaction
    $conn->beginTransaction();

    // First check if purchase exists and get current values
    $stmt = $conn->prepare("SELECT * FROM purchases WHERE id = ?");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('پسووڵەی داواکراو نەدۆزرایەوە');
    }

    // Check if purchase has any returns
    $stmt = $conn->prepare("
        SELECT COUNT(*) as return_count 
        FROM product_returns 
        WHERE receipt_id = ? AND receipt_type = 'buying'
    ");
    $stmt->execute([$id]);
    $hasReturns = $stmt->fetch(PDO::FETCH_ASSOC)['return_count'] > 0;

    if ($hasReturns) {
        throw new Exception('ناتوانرێت ئەم پسووڵەیە دەستکاری بکرێت چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
    }

    // Check if purchase has any payments (for credit purchases)
    if ($purchase['payment_type'] === 'credit') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as payment_count 
            FROM supplier_debt_transactions 
            WHERE reference_id = ? AND transaction_type IN ('payment', 'return')
        ");
        $stmt->execute([$id]);
        $hasPayments = $stmt->fetch(PDO::FETCH_ASSOC)['payment_count'] > 0;

        if ($hasPayments) {
            throw new Exception('ناتوانرێت ئەم پسووڵەیە دەستکاری بکرێت چونکە پارەدانی لەسەر تۆمارکراوە');
        }
    }

    // Update purchase
    $stmt = $conn->prepare("
        UPDATE purchases 
        SET invoice_number = ?,
            supplier_id = ?,
            date = ?,
            payment_type = ?,
            shipping_cost = ?,
            other_cost = ?,
            discount = ?,
            notes = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $invoice_number,
        $supplier_id,
        $date,
        $payment_type,
        $shipping_cost,
        $other_cost,
        $discount,
        $notes,
        $id
    ]);

    // If payment type changed from credit to cash, remove debt records
    if ($purchase['payment_type'] === 'credit' && $payment_type === 'cash') {
        $stmt = $conn->prepare("
            DELETE FROM supplier_debt_transactions 
            WHERE reference_id = ? AND transaction_type = 'purchase'
        ");
        $stmt->execute([$id]);

        // Update supplier debt
        $stmt = $conn->prepare("
            UPDATE suppliers 
            SET debt_on_myself = debt_on_myself - ? 
            WHERE id = ?
        ");
        $stmt->execute([$purchase['remaining_amount'], $supplier_id]);
    }

    // If payment type changed from cash to credit, add debt record
    if ($purchase['payment_type'] === 'cash' && $payment_type === 'credit') {
        // Calculate total amount
        $stmt = $conn->prepare("
            SELECT SUM(total_price) as total 
            FROM purchase_items 
            WHERE purchase_id = ?
        ");
        $stmt->execute([$id]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total += $shipping_cost + $other_cost - $discount;

        // Add debt transaction
        $stmt = $conn->prepare("
            INSERT INTO supplier_debt_transactions (
                supplier_id, amount, transaction_type, reference_id, notes
            ) VALUES (?, ?, 'purchase', ?, ?)
        ");
        $stmt->execute([
            $supplier_id,
            $total,
            $id,
            $notes
        ]);

        // Update remaining amount in purchases table
        $stmt = $conn->prepare("
            UPDATE purchases 
            SET remaining_amount = ?, 
                paid_amount = 0
            WHERE id = ?
        ");
        $stmt->execute([$total, $id]);

        // Update supplier debt
        $stmt = $conn->prepare("
            UPDATE suppliers 
            SET debt_on_myself = debt_on_myself + ? 
            WHERE id = ?
        ");
        $stmt->execute([$total, $supplier_id]);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'پسووڵەکە بە سەرکەوتوویی نوێ کرایەوە'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 