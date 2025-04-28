<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها پەیامی POST قبوڵ دەکرێت']);
    exit;
}

// Get sale ID
$saleId = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;

if ($saleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی پسووڵە نادروستە']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Start transaction
    $conn->beginTransaction();

    // Check if sale exists and get its details
    $saleQuery = "SELECT * FROM sales WHERE id = :id";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':id', $saleId);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('پسووڵەکە نەدۆزرایەوە');
    }

    // Check if there are any returns or payments for this sale
    $transactionQuery = "SELECT COUNT(*) as count FROM debt_transactions 
                        WHERE reference_id = :sale_id 
                        AND (transaction_type = 'collection' OR transaction_type = 'payment')";
    $transactionStmt = $conn->prepare($transactionQuery);
    $transactionStmt->bindParam(':sale_id', $saleId);
    $transactionStmt->execute();
    $transactionCount = $transactionStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($transactionCount > 0) {
        throw new Exception('ناتوانرێت پسووڵەکە بسڕدرێتەوە چونکە گەڕانەوە یان پارەدانێکی هەیە');
    }

    // Check if there are any product returns for this sale
    $returnQuery = "SELECT COUNT(*) as count FROM product_returns 
                   WHERE receipt_id = :sale_id AND receipt_type = 'selling'";
    $returnStmt = $conn->prepare($returnQuery);
    $returnStmt->bindParam(':sale_id', $saleId);
    $returnStmt->execute();
    $returnCount = $returnStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($returnCount > 0) {
        throw new Exception('ناتوانرێت پسووڵەکە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای لەسەر تۆمار کراوە');
    }

    // Get customer ID and check if this is a credit transaction
    $customerId = $sale['customer_id'];
    $isCredit = ($sale['payment_type'] == 'credit');

    // If this was a credit transaction, check and get the debt amount
    if ($isCredit) {
        $debtQuery = "SELECT * FROM debt_transactions 
                     WHERE reference_id = :sale_id AND transaction_type = 'sale'";
        $debtStmt = $conn->prepare($debtQuery);
        $debtStmt->bindParam(':sale_id', $saleId);
        $debtStmt->execute();
        $debtTransaction = $debtStmt->fetch(PDO::FETCH_ASSOC);

        if ($debtTransaction) {
            // Update customer's debt balance
            $debtAmount = $debtTransaction['amount'];
            $updateCustomerQuery = "UPDATE customers SET debit_on_business = debit_on_business - :amount 
                                   WHERE id = :customer_id";
            $updateCustomerStmt = $conn->prepare($updateCustomerQuery);
            $updateCustomerStmt->bindParam(':amount', $debtAmount);
            $updateCustomerStmt->bindParam(':customer_id', $customerId);
            $updateCustomerStmt->execute();

            // Delete the debt transaction record
            $deleteDebtQuery = "DELETE FROM debt_transactions 
                               WHERE reference_id = :sale_id AND transaction_type = 'sale'";
            $deleteDebtStmt = $conn->prepare($deleteDebtQuery);
            $deleteDebtStmt->bindParam(':sale_id', $saleId);
            $deleteDebtStmt->execute();
        }
    }

    // Get sale items to restore product quantities
    $itemsQuery = "SELECT * FROM sale_items WHERE sale_id = :sale_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':sale_id', $saleId);
    $itemsStmt->execute();
    $saleItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Restore product quantities
    foreach ($saleItems as $item) {
        $updateProductQuery = "UPDATE products SET current_quantity = current_quantity + :pieces_count 
                              WHERE id = :product_id";
        $updateProductStmt = $conn->prepare($updateProductQuery);
        $updateProductStmt->bindParam(':pieces_count', $item['pieces_count']);
        $updateProductStmt->bindParam(':product_id', $item['product_id']);
        $updateProductStmt->execute();
    }

    // Delete sale items first
    $deleteItemsQuery = "DELETE FROM sale_items WHERE sale_id = :sale_id";
    $deleteItemsStmt = $conn->prepare($deleteItemsQuery);
    $deleteItemsStmt->bindParam(':sale_id', $saleId);
    $deleteItemsStmt->execute();

    // Delete the sale
    $deleteSaleQuery = "DELETE FROM sales WHERE id = :id";
    $deleteSaleStmt = $conn->prepare($deleteSaleQuery);
    $deleteSaleStmt->bindParam(':id', $saleId);
    $deleteSaleStmt->execute();

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