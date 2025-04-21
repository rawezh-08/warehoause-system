<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_sale_errors.log');

// Log the start of the script
error_log("Starting delete_sale.php script");

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
    
    // First, check if the sale exists
    $stmt = $conn->prepare("
        SELECT s.*, c.name as customer_name 
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$receipt_id]);
    $saleInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Sale info found: " . print_r($saleInfo, true));
    
    if (!$saleInfo) {
        error_log("Sale not found with ID: " . $receipt_id);
        throw new Exception('پسووڵەی فرۆشتن نەدۆزرایەوە - ID: ' . $receipt_id);
    }
    
    // Check if sale has any returns
    $stmt = $conn->prepare("
        SELECT COUNT(*) as return_count 
        FROM product_returns 
        WHERE receipt_id = ? AND receipt_type = 'selling'
    ");
    $stmt->execute([$receipt_id]);
    $hasReturns = $stmt->fetch(PDO::FETCH_ASSOC)['return_count'] > 0;
    
    error_log("Has returns check result: " . ($hasReturns ? 'Yes' : 'No'));
    
    if ($hasReturns) {
        error_log("Cannot delete: Sale has returns");
        throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
    }
    
    // Check if sale has any payments (for credit sales)
    if ($saleInfo['payment_type'] === 'credit') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as payment_count 
            FROM debt_transactions 
            WHERE reference_id = ? AND transaction_type = 'payment'
        ");
        $stmt->execute([$receipt_id]);
        $hasPayments = $stmt->fetch(PDO::FETCH_ASSOC)['payment_count'] > 0;
        
        error_log("Has payments check result: " . ($hasPayments ? 'Yes' : 'No'));
        
        if ($hasPayments) {
            error_log("Cannot delete: Credit sale has payments");
            throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە پارەدانی لەسەر تۆمارکراوە');
        }
    }
    
    // Get sale items
    $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$receipt_id]);
    $saleItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Sale items found: " . print_r($saleItems, true));
    
    if (empty($saleItems)) {
        error_log("No items found for sale ID: " . $receipt_id);
        throw new Exception('هیچ کاڵایەک لە پسووڵەی فرۆشتن نەدۆزرایەوە - ID: ' . $receipt_id);
    }
    
    error_log("Starting transaction for deletion");
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Update product quantities
        foreach ($saleItems as $item) {
            error_log("Processing item: " . print_r($item, true));
            
            // Get pieces count
            $pieces_count = $item['pieces_count'];
            
            error_log("Returning " . $pieces_count . " pieces to inventory for product ID: " . $item['product_id']);
            
            // Update product quantity (add back to inventory)
            $stmt = $conn->prepare("
                UPDATE products 
                SET current_quantity = current_quantity + ?
                WHERE id = ?
            ");
            $stmt->execute([$pieces_count, $item['product_id']]);
            
            error_log("Updated product quantity");
            
            // Delete from inventory
            $stmt = $conn->prepare("
                DELETE FROM inventory 
                WHERE product_id = ? 
                AND reference_type = 'sale' 
                AND reference_id = ?
            ");
            $stmt->execute([$item['product_id'], $item['id']]);
            
            error_log("Deleted from inventory");
        }
        
        // Delete debt transactions if it was a credit sale
        if ($saleInfo['payment_type'] === 'credit') {
            error_log("Deleting debt transactions");
            
            // Update customer's debt
            $stmt = $conn->prepare("
                UPDATE customers 
                SET debit_on_business = debit_on_business - ? 
                WHERE id = ?
            ");
            $stmt->execute([$saleInfo['remaining_amount'], $saleInfo['customer_id']]);
            
            // Delete debt transactions
            $stmt = $conn->prepare("
                DELETE FROM debt_transactions 
                WHERE reference_id = ? AND transaction_type = 'sale'
            ");
            $stmt->execute([$receipt_id]);
        }
        
        error_log("Deleting sale items");
        
        // Delete sale items
        $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$receipt_id]);
        
        error_log("Deleting sale record");
        
        // Delete sale
        $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
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
        error_log("Transaction error in delete_sale.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw new Exception('هەڵە لە سڕینەوەی پسووڵە: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete_sale.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 