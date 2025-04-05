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
    
    // First, verify that the receipt exists
    $checkQuery = "SELECT id FROM receipts WHERE id = :id AND type = :type";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->bindParam(':id', $receiptId, PDO::PARAM_INT);
    $checkStmt->bindParam(':type', $receiptType, PDO::PARAM_STR);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'پسوڵە نەدۆزرایەوە'
        ]);
        exit;
    }
    
    // Before deleting the receipt, handle inventory updates based on receipt type
    if ($receiptType === 'selling' || $receiptType === 'buying' || $receiptType === 'wasting') {
        // Get all items in the receipt to adjust inventory accordingly
        $itemsQuery = "SELECT product_id, quantity FROM receipt_items WHERE receipt_id = :receipt_id";
        $itemsStmt = $pdo->prepare($itemsQuery);
        $itemsStmt->bindParam(':receipt_id', $receiptId, PDO::PARAM_INT);
        $itemsStmt->execute();
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update inventory for each product
        foreach ($items as $item) {
            // The inventory update logic depends on the receipt type
            if ($receiptType === 'selling') {
                // For selling receipts, add back to inventory
                $updateQuery = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id";
            } elseif ($receiptType === 'buying') {
                // For buying receipts, reduce from inventory
                $updateQuery = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
            } elseif ($receiptType === 'wasting') {
                // For wasting receipts, add back to inventory (assuming wasting reduces inventory)
                $updateQuery = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id";
            }
            
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $updateStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $updateStmt->execute();
        }
    }
    
    // Delete all receipt items
    $deleteItemsQuery = "DELETE FROM receipt_items WHERE receipt_id = :receipt_id";
    $deleteItemsStmt = $pdo->prepare($deleteItemsQuery);
    $deleteItemsStmt->bindParam(':receipt_id', $receiptId, PDO::PARAM_INT);
    $deleteItemsStmt->execute();
    
    // Delete the receipt
    $deleteReceiptQuery = "DELETE FROM receipts WHERE id = :id";
    $deleteReceiptStmt = $pdo->prepare($deleteReceiptQuery);
    $deleteReceiptStmt->bindParam(':id', $receiptId, PDO::PARAM_INT);
    $deleteReceiptStmt->execute();
    
    // Commit the transaction
    $pdo->commit();
    
    // Return success message
    echo json_encode([
        'success' => true,
        'message' => 'پسوڵە بە سەرکەوتوویی سڕایەوە'
    ]);
    
} catch (PDOException $e) {
    // Rollback the transaction
    $pdo->rollBack();
    
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => 'خەتا لە کاتی سڕینەوەی پسوڵە: ' . $e->getMessage()
    ]);
} 