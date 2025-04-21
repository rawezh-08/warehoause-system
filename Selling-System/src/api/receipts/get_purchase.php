<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception('داواکاری نادروستە');
    }

    $id = intval($_POST['id']);

    // Get purchase details
    $stmt = $conn->prepare("
        SELECT p.*, s.name as supplier_name
        FROM purchases p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('پسووڵەی داواکراو نەدۆزرایەوە');
    }

    // Get purchase items
    $stmt = $conn->prepare("
        SELECT pi.*, p.name as product_name
        FROM purchase_items pi
        LEFT JOIN products p ON pi.product_id = p.id
        WHERE pi.purchase_id = ?
    ");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $purchase['id'],
            'invoice_number' => $purchase['invoice_number'],
            'supplier_id' => $purchase['supplier_id'],
            'supplier_name' => $purchase['supplier_name'],
            'date' => $purchase['date'],
            'payment_type' => $purchase['payment_type'],
            'shipping_cost' => $purchase['shipping_cost'],
            'other_cost' => $purchase['other_cost'],
            'discount' => $purchase['discount'],
            'notes' => $purchase['notes'],
            'items' => $items
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 