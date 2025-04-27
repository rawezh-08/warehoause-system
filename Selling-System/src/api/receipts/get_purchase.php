<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('ناسنامەی پسووڵەی کڕین دیاری نەکراوە');
    }

    $purchaseId = intval($_POST['id']);
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Get purchase details
    $query = "SELECT p.*, s.name as supplier_name
              FROM purchases p
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              WHERE p.id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $purchaseId);
    $stmt->execute();
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('پسووڵەی کڕین نەدۆزرایەوە');
    }

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

    // Add flags to indicate if the purchase has returns or payments
    $purchase['has_returns'] = $hasReturns;
    $purchase['has_payments'] = $hasPayments;

    // Format date for better display
    if (isset($purchase['date'])) {
        $purchase['formatted_date'] = date('Y-m-d', strtotime($purchase['date']));
    }

    // Get purchase items
    $stmt = $conn->prepare("
        SELECT pi.*, p.name as product_name
        FROM purchase_items pi
        LEFT JOIN products p ON pi.product_id = p.id
        WHERE pi.purchase_id = ?
    ");
    $stmt->execute([$purchaseId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $purchase['items'] = $items;

    echo json_encode([
        'success' => true,
        'data' => $purchase
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 