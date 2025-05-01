<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها پەیامی POST قبوڵ دەکرێت']);
    exit;
}

// Get purchase ID
$purchaseId = isset($_POST['purchase_id']) ? intval($_POST['purchase_id']) : 0;

if ($purchaseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی پسووڵە نادروستە']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Start transaction
    $conn->beginTransaction();

    // Check if purchase exists and get its details
    $purchaseQuery = "SELECT * FROM purchases WHERE id = :id";
    $purchaseStmt = $conn->prepare($purchaseQuery);
    $purchaseStmt->bindParam(':id', $purchaseId);
    $purchaseStmt->execute();
    $purchase = $purchaseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('پسووڵەکە نەدۆزرایەوە');
    }

    // Check if there are any payments for this purchase
    $paymentQuery = "SELECT COUNT(*) as count FROM supplier_debt_transactions 
                    WHERE reference_id = :purchase_id 
                    AND transaction_type = 'payment'";
    $paymentStmt = $conn->prepare($paymentQuery);
    $paymentStmt->bindParam(':purchase_id', $purchaseId);
    $paymentStmt->execute();
    $paymentCount = $paymentStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($paymentCount > 0) {
        throw new Exception('ناتوانرێت پسووڵەکە بسڕدرێتەوە چونکە پارەدانی هەیە');
    }

    // Check if there are any product returns for this purchase
    $returnQuery = "SELECT COUNT(*) as count FROM product_returns 
                   WHERE receipt_id = :purchase_id AND receipt_type = 'buying'";
    $returnStmt = $conn->prepare($returnQuery);
    $returnStmt->bindParam(':purchase_id', $purchaseId);
    $returnStmt->execute();
    $returnCount = $returnStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($returnCount > 0) {
        throw new Exception('ناتوانرێت پسووڵەکە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای هەیە');
    }

    // Get supplier ID and check if this was a credit purchase
    $supplierId = $purchase['supplier_id'];
    $isCredit = ($purchase['payment_type'] == 'credit');

    // If this was a credit purchase, check and get the debt amount
    if ($isCredit) {
        $debtQuery = "SELECT * FROM supplier_debt_transactions 
                    WHERE reference_id = :purchase_id AND transaction_type = 'purchase'";
        $debtStmt = $conn->prepare($debtQuery);
        $debtStmt->bindParam(':purchase_id', $purchaseId);
        $debtStmt->execute();
        $debtTransaction = $debtStmt->fetch(PDO::FETCH_ASSOC);

        if ($debtTransaction) {
            // Update supplier's debt balance
            $debtAmount = $debtTransaction['amount'];
            $updateSupplierQuery = "UPDATE suppliers SET debt_on_myself = debt_on_myself - :amount 
                                  WHERE id = :supplier_id";
            $updateSupplierStmt = $conn->prepare($updateSupplierQuery);
            $updateSupplierStmt->bindParam(':amount', $debtAmount);
            $updateSupplierStmt->bindParam(':supplier_id', $supplierId);
            $updateSupplierStmt->execute();

            // Delete the debt transaction record
            $deleteDebtQuery = "DELETE FROM supplier_debt_transactions 
                              WHERE reference_id = :purchase_id AND transaction_type = 'purchase'";
            $deleteDebtStmt = $conn->prepare($deleteDebtQuery);
            $deleteDebtStmt->bindParam(':purchase_id', $purchaseId);
            $deleteDebtStmt->execute();
        }
    }

    // Get purchase items to update product quantities
    $itemsQuery = "SELECT * FROM purchase_items WHERE purchase_id = :purchase_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':purchase_id', $purchaseId);
    $itemsStmt->execute();
    $purchaseItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Restore product quantities (subtract what was added during the purchase)
    foreach ($purchaseItems as $item) {
        // Calculate pieces count for the correct quantity adjustment
        $productQuery = "SELECT pieces_per_box, boxes_per_set FROM products WHERE id = :product_id";
        $productStmt = $conn->prepare($productQuery);
        $productStmt->bindParam(':product_id', $item['product_id']);
        $productStmt->execute();
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);

        $piecesCount = $item['quantity']; // Default for 'piece' unit_type
        if ($item['unit_type'] == 'box' && !empty($product['pieces_per_box'])) {
            $piecesCount = $item['quantity'] * $product['pieces_per_box'];
        } elseif ($item['unit_type'] == 'set' && !empty($product['pieces_per_box']) && !empty($product['boxes_per_set'])) {
            $piecesCount = $item['quantity'] * $product['pieces_per_box'] * $product['boxes_per_set'];
        }

        $updateProductQuery = "UPDATE products SET current_quantity = current_quantity - :pieces_count 
                             WHERE id = :product_id";
        $updateProductStmt = $conn->prepare($updateProductQuery);
        $updateProductStmt->bindParam(':pieces_count', $piecesCount);
        $updateProductStmt->bindParam(':product_id', $item['product_id']);
        $updateProductStmt->execute();
    }

    // Delete purchase items first
    $deleteItemsQuery = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
    $deleteItemsStmt = $conn->prepare($deleteItemsQuery);
    $deleteItemsStmt->bindParam(':purchase_id', $purchaseId);
    $deleteItemsStmt->execute();

    // Delete the purchase
    $deletePurchaseQuery = "DELETE FROM purchases WHERE id = :id";
    $deletePurchaseStmt = $conn->prepare($deletePurchaseQuery);
    $deletePurchaseStmt->bindParam(':id', $purchaseId);
    $deletePurchaseStmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'پسووڵەکە بەسەرکەوتوویی سڕایەوە'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 