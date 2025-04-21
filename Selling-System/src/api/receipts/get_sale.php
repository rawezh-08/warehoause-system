<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception('داواکاری نادروستە');
    }

    $id = intval($_POST['id']);

    // Get sale details
    $stmt = $conn->prepare("
        SELECT s.*, c.name as customer_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('پسووڵەی داواکراو نەدۆزرایەوە');
    }

    // Get sale items
    $stmt = $conn->prepare("
        SELECT si.*, p.name as product_name
        FROM sale_items si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $sale['id'],
            'invoice_number' => $sale['invoice_number'],
            'customer_id' => $sale['customer_id'],
            'customer_name' => $sale['customer_name'],
            'date' => $sale['date'],
            'payment_type' => $sale['payment_type'],
            'shipping_cost' => $sale['shipping_cost'],
            'other_costs' => $sale['other_costs'],
            'discount' => $sale['discount'],
            'notes' => $sale['notes'],
            'items' => $items
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 