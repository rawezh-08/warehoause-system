<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

try {
    // Check if required parameters are provided
    if (!isset($_POST['purchase_id']) || !isset($_POST['invoice_number']) || !isset($_POST['return_quantities'])) {
        throw new Exception('Missing required parameters');
    }

    $purchaseId = intval($_POST['purchase_id']);
    $invoiceNumber = $_POST['invoice_number'];
    $returnQuantities = $_POST['return_quantities'];
    
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Get purchase details
        $purchaseQuery = "SELECT * FROM purchases WHERE id = :purchase_id";
        $purchaseStmt = $conn->prepare($purchaseQuery);
        $purchaseStmt->bindParam(':purchase_id', $purchaseId);
        $purchaseStmt->execute();
        $purchase = $purchaseStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchase) {
            throw new Exception('Purchase not found');
        }
        
        // Check if purchase has any payments
        $paymentQuery = "SELECT COUNT(*) as count FROM supplier_debt_transactions 
                        WHERE reference_id = :purchase_id 
                        AND transaction_type = 'payment'";
        $paymentStmt = $conn->prepare($paymentQuery);
        $paymentStmt->bindParam(':purchase_id', $purchaseId);
        $paymentStmt->execute();
        $paymentCount = $paymentStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($paymentCount > 0) {
            throw new Exception('Cannot return items from a purchase that has payments');
        }
        
        // Process each returned item
        foreach ($returnQuantities as $itemId => $quantity) {
            if ($quantity <= 0) continue;
            
            // Get purchase item details
            $itemQuery = "SELECT * FROM purchase_items WHERE id = :item_id AND purchase_id = :purchase_id";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->bindParam(':item_id', $itemId);
            $itemStmt->bindParam(':purchase_id', $purchaseId);
            $itemStmt->execute();
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception('Invalid purchase item');
            }
            
            // Check if return quantity is valid
            $returnedQuery = "SELECT COALESCE(SUM(quantity), 0) as returned_quantity 
                            FROM product_returns 
                            WHERE purchase_item_id = :item_id";
            $returnedStmt = $conn->prepare($returnedQuery);
            $returnedStmt->bindParam(':item_id', $itemId);
            $returnedStmt->execute();
            $returnedQuantity = $returnedStmt->fetch(PDO::FETCH_ASSOC)['returned_quantity'];
            
            if ($quantity > ($item['quantity'] - $returnedQuantity)) {
                throw new Exception('Return quantity exceeds available quantity');
            }
            
            // Insert return record
            $returnQuery = "INSERT INTO product_returns (
                            receipt_id, 
                            receipt_type, 
                            purchase_item_id, 
                            product_id, 
                            quantity, 
                            unit_price, 
                            total_price, 
                            return_date
                        ) VALUES (
                            :receipt_id,
                            'buying',
                            :purchase_item_id,
                            :product_id,
                            :quantity,
                            :unit_price,
                            :total_price,
                            NOW()
                        )";
            
            $returnStmt = $conn->prepare($returnQuery);
            $returnStmt->bindParam(':receipt_id', $purchaseId);
            $returnStmt->bindParam(':purchase_item_id', $itemId);
            $returnStmt->bindParam(':product_id', $item['product_id']);
            $returnStmt->bindParam(':quantity', $quantity);
            $returnStmt->bindParam(':unit_price', $item['unit_price']);
            
            // Calculate total price and store in variable
            $totalPrice = $item['unit_price'] * $quantity;
            $returnStmt->bindParam(':total_price', $totalPrice);
            
            $returnStmt->execute();
            
            // Update product stock
            $stockQuery = "UPDATE products 
                          SET stock = stock - :quantity 
                          WHERE id = :product_id";
            $stockStmt = $conn->prepare($stockQuery);
            $stockStmt->bindParam(':quantity', $quantity);
            $stockStmt->bindParam(':product_id', $item['product_id']);
            $stockStmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'Items returned successfully';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 