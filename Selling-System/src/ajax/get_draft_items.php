<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_POST['invoice_number'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ژمارەی پسووڵە پێویستە'
    ]);
    exit;
}

$invoiceNumber = $_POST['invoice_number'];
$db = new Database();
$conn = $db->getConnection();

try {
    // Get sale ID from invoice number
    $saleQuery = "SELECT id FROM sales WHERE invoice_number = :invoice_number AND is_draft = 1";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':invoice_number', $invoiceNumber);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        echo json_encode([
            'success' => false,
            'message' => 'پسووڵە نەدۆزرایەوە'
        ]);
        exit;
    }

    // Get sale items
    $itemsQuery = "SELECT si.*, p.name as product_name, p.code as product_code
                  FROM sale_items si
                  LEFT JOIN products p ON si.product_id = p.id
                  WHERE si.sale_id = :sale_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':sale_id', $sale['id']);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
    ]);
} 