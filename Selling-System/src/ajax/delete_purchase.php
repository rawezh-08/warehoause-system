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