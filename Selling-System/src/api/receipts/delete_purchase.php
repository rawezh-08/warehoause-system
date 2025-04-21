<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_purchase_errors.log');

// Log the start of the script
error_log("Starting delete_purchase.php script");

require_once '../../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Log POST data
error_log("POST data received: " . print_r($_POST, true));

try {
    // Validate input
    if (!isset($_POST['receipt_id']) || empty($_POST['receipt_id'])) {
        error_log("Receipt ID is missing from POST data");
        throw new Exception('Receipt ID is required');
    }
    
    $receipt_id = intval($_POST['receipt_id']);
    error_log("Processing receipt_id: " . $receipt_id);
    
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('Database connection failed');
    }
    
    error_log("Database connection established successfully");
    
    // First, check if the purchase exists
    $stmt = $conn->prepare("SELECT * FROM purchases WHERE id = ?");
    if (!$stmt) {
        error_log("Failed to prepare statement for purchase check: " . print_r($conn->errorInfo(), true));
        throw new Exception('Database query preparation failed');
    }
    
    $stmt->execute([$receipt_id]);
    $purchaseInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Purchase info found: " . print_r($purchaseInfo, true));
    
    if (!$purchaseInfo) {
        error_log("Purchase not found with ID: " . $receipt_id);
        throw new Exception('پسووڵەی کڕین نەدۆزرایەوە - ID: ' . $receipt_id);
    }
    
    // Check if purchase has any returns
    $stmt = $conn->prepare("
        SELECT COUNT(*) as return_count 
        FROM product_returns 
        WHERE receipt_id = ? AND receipt_type = 'buying'
    ");
    $stmt->execute([$receipt_id]);
    $hasReturns = $stmt->fetch(PDO::FETCH_ASSOC)['return_count'] > 0;
    
    error_log("Has returns check result: " . ($hasReturns ? 'Yes' : 'No'));
    
    if ($hasReturns) {
        error_log("Cannot delete: Purchase has returns");
        throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
    }
    
    // Check if purchase has any payments (for credit purchases)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as payment_count 
        FROM supplier_debt_transactions 
        WHERE reference_id = ? AND transaction_type IN ('payment', 'return')
    ");
    $stmt->execute([$receipt_id]);
    $hasPayments = $stmt->fetch(PDO::FETCH_ASSOC)['payment_count'] > 0;
    
    error_log("Has payments check result: " . ($hasPayments ? 'Yes' : 'No'));
    
    if ($purchaseInfo['payment_type'] === 'credit' && $hasPayments) {
        error_log("Cannot delete: Credit purchase has payments");
        throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە پارەدانی لەسەر تۆمارکراوە');
    }
    
    // Get purchase items
    $stmt = $conn->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$receipt_id]);
    $purchaseItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Purchase items found: " . print_r($purchaseItems, true));
    
    if (empty($purchaseItems)) {
        error_log("No items found for purchase ID: " . $receipt_id);
        throw new Exception('هیچ کاڵایەک لە پسووڵەی کڕین نەدۆزرایەوە - ID: ' . $receipt_id);
    }
    
    // Check if any products from this purchase have been sold
    foreach ($purchaseItems as $item) {
        error_log("Checking sales for product ID: " . $item['product_id']);
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as sales_count
            FROM sale_items si 
            JOIN sales s ON si.sale_id = s.id
            WHERE si.product_id = ? 
            AND s.date > ?
        ");
        $stmt->execute([$item['product_id'], $purchaseInfo['date']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Sales count for product: " . $result['sales_count']);
        
        if ($result['sales_count'] > 0) {
            // Get product name for error message
            $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $productName = $stmt->fetch(PDO::FETCH_ASSOC)['name'];
            
            error_log("Cannot delete: Product has been sold: " . $productName);
            throw new Exception("ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە بەشێک لە کاڵاکان فرۆشراون: " . $productName);
        }
    }
    
    error_log("Starting transaction for deletion");
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Remove from inventory
        foreach ($purchaseItems as $item) {
            error_log("Processing item: " . print_r($item, true));
            
            // Get pieces count
            $pieces_count = $item['quantity'];
            if ($item['unit_type'] === 'box') {
                // Get pieces per box
                $stmt = $conn->prepare("SELECT pieces_per_box FROM products WHERE id = ?");
                $stmt->execute([$item['product_id']]);
                $piecesPerBox = $stmt->fetch(PDO::FETCH_ASSOC)['pieces_per_box'];
                $pieces_count = $item['quantity'] * $piecesPerBox;
            } elseif ($item['unit_type'] === 'set') {
                // Get pieces per box and boxes per set
                $stmt = $conn->prepare("
                    SELECT pieces_per_box, boxes_per_set 
                    FROM products 
                    WHERE id = ?
                ");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                $pieces_count = $item['quantity'] * $product['boxes_per_set'] * $product['pieces_per_box'];
            }
            
            error_log("Calculated pieces count: " . $pieces_count);
            
            // Update product quantity
            $stmt = $conn->prepare("
                UPDATE products 
                SET current_quantity = current_quantity - ?
                WHERE id = ?
            ");
            $stmt->execute([$pieces_count, $item['product_id']]);
            
            error_log("Updated product quantity");
            
            // Delete from inventory
            $stmt = $conn->prepare("
                DELETE FROM inventory 
                WHERE product_id = ? 
                AND reference_type = 'purchase' 
                AND reference_id = ?
            ");
            $stmt->execute([$item['product_id'], $item['id']]);
            
            error_log("Deleted from inventory");
        }
        
        // Delete supplier debt transactions
        if ($purchaseInfo['payment_type'] === 'credit') {
            error_log("Deleting supplier debt transactions");
            
            $stmt = $conn->prepare("
                DELETE FROM supplier_debt_transactions 
                WHERE reference_id = ? AND transaction_type = 'purchase'
            ");
            $stmt->execute([$receipt_id]);
        }
        
        error_log("Deleting purchase items");
        
        // Delete purchase items
        $stmt = $conn->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
        $stmt->execute([$receipt_id]);
        
        error_log("Deleting purchase record");
        
        // Delete purchase
        $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
        $stmt->execute([$receipt_id]);
        
        // Commit transaction
        $conn->commit();
        
        error_log("Transaction committed successfully");
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'پسووڵە بە سەرکەوتوویی سڕایەوە'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Transaction error in delete_purchase.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw new Exception('هەڵە لە سڕینەوەی پسووڵە: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete_purchase.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
