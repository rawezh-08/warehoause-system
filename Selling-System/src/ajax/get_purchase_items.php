<?php
// Include necessary files
require_once '../config/database.php';

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'items' => [],
    'total_amount' => 0
];

// Check if purchase ID is provided
if (!isset($_POST['purchase_id']) || empty($_POST['purchase_id'])) {
    $response['message'] = 'ناسنامەی پسووڵە دیاری نەکراوە';
    echo json_encode($response);
    exit;
}

$purchaseId = intval($_POST['purchase_id']);

try {
    // Get purchase header information
    $purchaseQuery = "SELECT * FROM purchases WHERE id = :purchase_id";
    $purchaseStmt = $conn->prepare($purchaseQuery);
    $purchaseStmt->bindParam(':purchase_id', $purchaseId);
    $purchaseStmt->execute();
    $purchase = $purchaseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        $response['message'] = 'پسووڵەی داواکراو نەدۆزرایەوە';
        echo json_encode($response);
        exit;
    }

    // Get purchase items
    $itemsQuery = "SELECT pi.*, p.name as product_name, p.code as product_code 
                   FROM purchase_items pi 
                   JOIN products p ON pi.product_id = p.id 
                   WHERE pi.purchase_id = :purchase_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':purchase_id', $purchaseId);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total amount
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += $item['total_price'];
    }

    // Prepare successful response
    $response['success'] = true;
    $response['items'] = $items;
    $response['total_amount'] = $totalAmount;
    $response['purchase'] = $purchase;

} catch (PDOException $e) {
    $response['message'] = 'هەڵەیەک ڕوویدا: ' . $e->getMessage();
}

// Return response as JSON
echo json_encode($response); 