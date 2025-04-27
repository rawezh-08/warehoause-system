<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    // Check if required data is provided
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('ناسنامەی پسووڵەی کڕین دیاری نەکراوە');
    }
    
    // Get form data
    $purchaseId = intval($_POST['id']);
    $invoiceNumber = $_POST['invoice_number'] ?? '';
    $supplierId = $_POST['supplier_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $paymentType = $_POST['payment_type'] ?? '';
    $shippingCost = floatval($_POST['shipping_cost'] ?? 0);
    $otherCost = floatval($_POST['other_cost'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (empty($invoiceNumber) || empty($supplierId) || empty($date) || empty($paymentType)) {
        throw new Exception('تکایە هەموو خانە پێویستەکان پڕ بکەوە');
    }

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Start transaction
    $conn->beginTransaction();

    // Check if purchase exists
    $checkQuery = "SELECT id, payment_type FROM purchases WHERE id = :id";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bindParam(':id', $purchaseId);
    $stmt->execute();
    $existingPurchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingPurchase) {
        throw new Exception('پسووڵەی کڕین نەدۆزرایەوە');
    }

    // Check if payment type can be changed
    if ($existingPurchase['payment_type'] !== $paymentType) {
    // Check if purchase has any returns
        $returnsQuery = "SELECT COUNT(*) as count FROM product_returns WHERE receipt_id = :purchase_id AND receipt_type = 'buying'";
        $stmt = $conn->prepare($returnsQuery);
        $stmt->bindParam(':purchase_id', $purchaseId);
        $stmt->execute();
        $hasReturns = ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0);

        // Check if purchase has any payments
        $paymentsQuery = "SELECT COUNT(*) as count FROM supplier_debt_transactions 
                        WHERE reference_id = :purchase_id AND transaction_type = 'payment'";
        $stmt = $conn->prepare($paymentsQuery);
        $stmt->bindParam(':purchase_id', $purchaseId);
        $stmt->execute();
        $hasPayments = ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0);

        if ($hasReturns || $hasPayments) {
            throw new Exception('ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵا یان پارەدانی لەسەر تۆمارکراوە');
        }
    }

    // Update purchase
    $updateQuery = "UPDATE purchases SET 
                    invoice_number = :invoice_number,
                    supplier_id = :supplier_id,
                    date = :date,
                    payment_type = :payment_type,
                    shipping_cost = :shipping_cost,
                    other_cost = :other_cost,
                    discount = :discount,
                    notes = :notes,
                    updated_at = NOW()
                    WHERE id = :id";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bindParam(':id', $purchaseId);
    $stmt->bindParam(':invoice_number', $invoiceNumber);
    $stmt->bindParam(':supplier_id', $supplierId);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':payment_type', $paymentType);
    $stmt->bindParam(':shipping_cost', $shippingCost);
    $stmt->bindParam(':other_cost', $otherCost);
    $stmt->bindParam(':discount', $discount);
    $stmt->bindParam(':notes', $notes);
    $stmt->execute();
    
    // Update supplier debt if payment type changed
    if ($existingPurchase['payment_type'] !== $paymentType) {
        // Get total amount of purchase
        $totalQuery = "SELECT SUM(total_price) as total FROM purchase_items WHERE purchase_id = :purchase_id";
        $stmt = $conn->prepare($totalQuery);
        $stmt->bindParam(':purchase_id', $purchaseId);
        $stmt->execute();
        $totalAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculate final amount after adjustments
        $finalAmount = $totalAmount + $shippingCost + $otherCost - $discount;

        // Adjust supplier debt based on payment type change
        if ($existingPurchase['payment_type'] === 'credit' && $paymentType === 'cash') {
            // Changed from credit to cash - reduce debt
            $updateDebtQuery = "UPDATE suppliers SET 
                              debt_on_myself = debt_on_myself - :amount 
                              WHERE id = :supplier_id";
            $stmt = $conn->prepare($updateDebtQuery);
            $stmt->bindParam(':amount', $finalAmount);
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->execute();
        } elseif ($existingPurchase['payment_type'] === 'cash' && $paymentType === 'credit') {
            // Changed from cash to credit - increase debt
            $updateDebtQuery = "UPDATE suppliers SET 
                              debt_on_myself = debt_on_myself + :amount 
                              WHERE id = :supplier_id";
            $stmt = $conn->prepare($updateDebtQuery);
            $stmt->bindParam(':amount', $finalAmount);
            $stmt->bindParam(':supplier_id', $supplierId);
            $stmt->execute();
        }
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'پسووڵەی کڕین بە سەرکەوتوویی نوێ کرایەوە'
    ]);

} catch (Exception $e) {
    // Rollback transaction if an error occurred
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 