<?php
// Include database connection
require_once '../config/db_connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the receipt ID and type are provided
if (!isset($_POST['id']) || !isset($_POST['type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامەی پسوڵە یان جۆری پسوڵە دیاری نەکراوە'
    ]);
    exit;
}

// Get the receipt ID and type
$receiptId = $_POST['id'];
$receiptType = $_POST['type'];

// Validate receipt type
if (!in_array($receiptType, ['selling', 'buying', 'wasting'])) {
    echo json_encode([
        'success' => false,
        'message' => 'جۆری پسوڵە نادروستە'
    ]);
    exit;
}

try {
    // Start a transaction
    $pdo->beginTransaction();
    
    // Get receipt header information
    $headerQuery = '';
    
    if ($receiptType === 'selling') {
        $headerQuery = "SELECT r.id, r.title, c.name AS customer, r.date, r.delivery_date, r.subtotal, r.discount, r.total, r.notes, r.status 
                       FROM receipts r 
                       LEFT JOIN customers c ON r.customer_id = c.id 
                       WHERE r.id = :id AND r.type = 'selling'";
    } elseif ($receiptType === 'buying') {
        $headerQuery = "SELECT r.id, r.title, v.name AS vendor, r.date, r.delivery_date, r.vendor_invoice, r.subtotal, r.shipping_cost, r.total, r.notes, r.status 
                       FROM receipts r 
                       LEFT JOIN vendors v ON r.vendor_id = v.id 
                       WHERE r.id = :id AND r.type = 'buying'";
    } elseif ($receiptType === 'wasting') {
        $headerQuery = "SELECT r.id, r.title, s.name AS responsible, r.date, r.reason, r.total, r.notes, r.status 
                       FROM receipts r 
                       LEFT JOIN staff s ON r.responsible_id = s.id 
                       WHERE r.id = :id AND r.type = 'wasting'";
    }
    
    // Execute the header query
    $headerStmt = $pdo->prepare($headerQuery);
    $headerStmt->bindParam(':id', $receiptId, PDO::PARAM_INT);
    $headerStmt->execute();
    
    // Fetch the receipt header
    $receipt = $headerStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if receipt exists
    if (!$receipt) {
        echo json_encode([
            'success' => false,
            'message' => 'پسوڵە نەدۆزرایەوە'
        ]);
        exit;
    }
    
    // Get receipt items
    $itemsQuery = "SELECT ri.id, p.name AS product_name, ri.price, ri.quantity, ri.total, ri.description 
                  FROM receipt_items ri 
                  LEFT JOIN products p ON ri.product_id = p.id 
                  WHERE ri.receipt_id = :receipt_id";
    
    // Execute the items query
    $itemsStmt = $pdo->prepare($itemsQuery);
    $itemsStmt->bindParam(':receipt_id', $receiptId, PDO::PARAM_INT);
    $itemsStmt->execute();
    
    // Fetch all items
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add items to receipt data
    $receipt['items'] = $items;
    
    // Commit the transaction
    $pdo->commit();
    
    // Return the results
    echo json_encode([
        'success' => true,
        'data' => $receipt
    ]);
    
} catch (PDOException $e) {
    // Rollback the transaction
    $pdo->rollBack();
    
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => 'خەتا لە کاتی بەدەستهێنانی زانیاری: ' . $e->getMessage()
    ]);
} 