<?php
require_once '../include/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the required data
$purchase_id = isset($_POST['purchase_id']) ? $_POST['purchase_id'] : null;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$items = isset($_POST['items']) ? $_POST['items'] : [];

// Validate input
if (empty($purchase_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Purchase ID is required'
    ]);
    exit;
}

if (empty($items)) {
    echo json_encode([
        'success' => false,
        'message' => 'No items selected for return'
    ]);
    exit;
}

try {
    // Begin transaction
    $conn->beginTransaction();

    // Get purchase details
    $purchaseQuery = "SELECT * FROM purchases WHERE id = :purchase_id";
    $purchaseStmt = $conn->prepare($purchaseQuery);
    $purchaseStmt->bindParam(':purchase_id', $purchase_id);
    $purchaseStmt->execute();
    $purchase = $purchaseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('Purchase not found');
    }

    $user_id = $_SESSION['user_id'];
    $timestamp = date('Y-m-d H:i:s');
    $return_date = date('Y-m-d');
    $total_return_amount = 0;

    // Process each returned item
    foreach ($items as $item) {
        $item_id = $item['item_id'];
        $return_quantity = $item['return_quantity'];
        
        if ($return_quantity <= 0) {
            continue; // Skip items with no return quantity
        }

        // Verify item exists in the purchase
        $itemQuery = "SELECT * FROM purchase_items WHERE id = :item_id AND purchase_id = :purchase_id";
        $itemStmt = $conn->prepare($itemQuery);
        $itemStmt->bindParam(':item_id', $item_id);
        $itemStmt->bindParam(':purchase_id', $purchase_id);
        $itemStmt->execute();
        $purchaseItem = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$purchaseItem) {
            throw new Exception('Item not found in purchase');
        }

        // Get already returned quantity
        $returnedQuery = "SELECT COALESCE(SUM(quantity), 0) as returned_qty FROM product_returns 
                         WHERE receipt_id = :purchase_id AND receipt_type = 'buying' AND item_id = :item_id";
        $returnedStmt = $conn->prepare($returnedQuery);
        $returnedStmt->bindParam(':purchase_id', $purchase_id);
        $returnedStmt->bindParam(':item_id', $item_id);
        $returnedStmt->execute();
        $returnedQty = $returnedStmt->fetchColumn();

        // Check if return quantity is valid
        $available_for_return = $purchaseItem['quantity'] - $returnedQty;
        if ($return_quantity > $available_for_return) {
            throw new Exception('Return quantity exceeds available quantity for item ' . $purchaseItem['product_id']);
        }

        // Calculate return amount for this item
        $unit_price = $purchaseItem['unit_price'];
        $return_amount = $return_quantity * $unit_price;
        $total_return_amount += $return_amount;

        // Insert into product_returns table
        $insertReturnQuery = "INSERT INTO product_returns 
                             (receipt_id, receipt_type, item_id, product_id, quantity, unit_price, 
                              total_price, reason, date, created_by, created_at) 
                             VALUES 
                             (:receipt_id, 'buying', :item_id, :product_id, :quantity, :unit_price, 
                              :total_price, :reason, :date, :created_by, :created_at)";
        
        $insertStmt = $conn->prepare($insertReturnQuery);
        $insertStmt->bindParam(':receipt_id', $purchase_id);
        $insertStmt->bindParam(':item_id', $item_id);
        $insertStmt->bindParam(':product_id', $purchaseItem['product_id']);
        $insertStmt->bindParam(':quantity', $return_quantity);
        $insertStmt->bindParam(':unit_price', $unit_price);
        $insertStmt->bindParam(':total_price', $return_amount);
        $insertStmt->bindParam(':reason', $reason);
        $insertStmt->bindParam(':date', $return_date);
        $insertStmt->bindParam(':created_by', $user_id);
        $insertStmt->bindParam(':created_at', $timestamp);
        $insertStmt->execute();

        // Update stock (increase stock for returned items)
        $updateStockQuery = "UPDATE product_inventory 
                            SET stock_quantity = stock_quantity + :return_quantity 
                            WHERE product_id = :product_id";
        $updateStockStmt = $conn->prepare($updateStockQuery);
        $updateStockStmt->bindParam(':return_quantity', $return_quantity);
        $updateStockStmt->bindParam(':product_id', $purchaseItem['product_id']);
        $updateStockStmt->execute();
    }

    // Add supplier transaction for the return (debt or payment)
    if ($total_return_amount > 0) {
        $transactionType = 'return';
        $insertTransQuery = "INSERT INTO supplier_debt_transactions 
                            (supplier_id, reference_id, transaction_type, amount, date, created_by, created_at) 
                            VALUES 
                            (:supplier_id, :reference_id, :transaction_type, :amount, :date, :created_by, :created_at)";
        
        $insertTransStmt = $conn->prepare($insertTransQuery);
        $insertTransStmt->bindParam(':supplier_id', $purchase['supplier_id']);
        $insertTransStmt->bindParam(':reference_id', $purchase_id);
        $insertTransStmt->bindParam(':transaction_type', $transactionType);
        $insertTransStmt->bindParam(':amount', $total_return_amount);
        $insertTransStmt->bindParam(':date', $return_date);
        $insertTransStmt->bindParam(':created_by', $user_id);
        $insertTransStmt->bindParam(':created_at', $timestamp);
        $insertTransStmt->execute();
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Purchase return processed successfully',
        'total_amount' => $total_return_amount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 