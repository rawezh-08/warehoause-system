<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    // Validate required fields
    if (!isset($_POST['id']) || !isset($_POST['invoice_number']) || !isset($_POST['customer_id']) || 
        !isset($_POST['date']) || !isset($_POST['payment_type'])) {
        throw new Exception('هەموو خانەکان پێویستن');
    }

    $id = intval($_POST['id']);
    $invoice_number = $_POST['invoice_number'];
    $customer_id = intval($_POST['customer_id']);
    $date = $_POST['date'];
    $payment_type = $_POST['payment_type'];
    $shipping_cost = isset($_POST['shipping_cost']) ? floatval($_POST['shipping_cost']) : 0;
    $other_costs = isset($_POST['other_costs']) ? floatval($_POST['other_costs']) : 0;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    // Start transaction
    $conn->beginTransaction();

    // First check if sale exists and get current values
    $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('پسووڵەی داواکراو نەدۆزرایەوە');
    }

    // Check if sale has any returns
    $stmt = $conn->prepare("
        SELECT COUNT(*) as return_count 
        FROM product_returns 
        WHERE receipt_id = ? AND receipt_type = 'selling'
    ");
    $stmt->execute([$id]);
    $hasReturns = $stmt->fetch(PDO::FETCH_ASSOC)['return_count'] > 0;

    // Check if sale has any payments (for credit sales)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as payment_count 
        FROM debt_transactions 
        WHERE reference_id = ? AND transaction_type = 'payment'
    ");
    $stmt->execute([$id]);
    $hasPayments = $stmt->fetch(PDO::FETCH_ASSOC)['payment_count'] > 0;

    // Prevent changing payment type if there are returns or payments
    if (($hasReturns || $hasPayments) && $sale['payment_type'] !== $payment_type) {
        throw new Exception('ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵا یان پارەدانی لەسەر تۆمارکراوە');
    }

    // If there are returns, don't allow editing the receipt
    if ($hasReturns) {
        throw new Exception('ناتوانرێت ئەم پسووڵەیە دەستکاری بکرێت چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
    }

    // If there are payments, don't allow editing the receipt
    if ($hasPayments) {
        throw new Exception('ناتوانرێت ئەم پسووڵەیە دەستکاری بکرێت چونکە پارەدانی لەسەر تۆمارکراوە');
    }

    // Update sale
    $stmt = $conn->prepare("
        UPDATE sales 
        SET invoice_number = ?,
            customer_id = ?,
            date = ?,
            payment_type = ?,
            shipping_cost = ?,
            other_costs = ?,
            discount = ?,
            notes = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $invoice_number,
        $customer_id,
        $date,
        $payment_type,
        $shipping_cost,
        $other_costs,
        $discount,
        $notes,
        $id
    ]);

    // Calculate total amount of the sale
    $stmt = $conn->prepare("
        SELECT SUM(total_price) as total 
        FROM sale_items 
        WHERE sale_id = ?
    ");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?? 0;
    $total_amount = $total + $shipping_cost + $other_costs - $discount;

    // If payment type changed from credit to cash, remove debt records and update customer debt
    if ($sale['payment_type'] === 'credit' && $payment_type === 'cash') {
        // Delete debt transaction
        $stmt = $conn->prepare("
            DELETE FROM debt_transactions 
            WHERE reference_id = ? AND transaction_type = 'sale'
        ");
        $stmt->execute([$id]);
        
        // Update customer's debt (decrease)
        $stmt = $conn->prepare("
            UPDATE customers 
            SET debit_on_business = debit_on_business - ?
            WHERE id = ?
        ");
        $stmt->execute([$total_amount, $sale['customer_id']]);
        
        // Update remaining_amount and paid_amount
        $stmt = $conn->prepare("
            UPDATE sales 
            SET remaining_amount = 0, 
                paid_amount = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_amount, $id]);
    }

    // If payment type changed from cash to credit, add debt record and update customer debt
    if ($sale['payment_type'] === 'cash' && $payment_type === 'credit') {
        // Add debt transaction
        $stmt = $conn->prepare("
            INSERT INTO debt_transactions (
                customer_id, amount, transaction_type, reference_id, notes, created_at
            ) VALUES (?, ?, 'sale', ?, ?, NOW())
        ");
        $stmt->execute([
            $customer_id,
            $total_amount,
            $id,
            $notes
        ]);

        // Update customer's debt (increase)
        $stmt = $conn->prepare("
            UPDATE customers 
            SET debit_on_business = debit_on_business + ?
            WHERE id = ?
        ");
        $stmt->execute([$total_amount, $customer_id]);

        // Update remaining amount and paid amount in sales table
        $stmt = $conn->prepare("
            UPDATE sales 
            SET remaining_amount = ?, 
                paid_amount = 0
            WHERE id = ?
        ");
        $stmt->execute([$total_amount, $id]);
    }

    // If only customer changed but payment type remains credit, update debt record ownership
    if ($payment_type === 'credit' && $sale['payment_type'] === 'credit' && $customer_id != $sale['customer_id']) {
        // First decrease debt for old customer
        $stmt = $conn->prepare("
            UPDATE customers 
            SET debit_on_business = debit_on_business - ?
            WHERE id = ?
        ");
        $stmt->execute([$total_amount, $sale['customer_id']]);
        
        // Then increase debt for new customer
        $stmt = $conn->prepare("
            UPDATE customers 
            SET debit_on_business = debit_on_business + ?
            WHERE id = ?
        ");
        $stmt->execute([$total_amount, $customer_id]);
        
        // Update debt transaction customer
        $stmt = $conn->prepare("
            UPDATE debt_transactions 
            SET customer_id = ?
            WHERE reference_id = ? AND transaction_type = 'sale'
        ");
        $stmt->execute([$customer_id, $id]);
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