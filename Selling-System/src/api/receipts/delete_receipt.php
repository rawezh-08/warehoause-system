<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_receipt_errors.log');

// Log the start of the script
error_log("Starting delete_receipt.php script");

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
    
    if (!isset($_POST['receipt_type']) || empty($_POST['receipt_type'])) {
        error_log("Receipt type is missing from POST data");
        throw new Exception('Receipt type is required');
    }
    
    $receipt_id = intval($_POST['receipt_id']);
    $receipt_type = $_POST['receipt_type'];
    
    error_log("Processing receipt_id: " . $receipt_id . ", type: " . $receipt_type);
    
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('Database connection failed');
    }
    
    error_log("Database connection established successfully");
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        switch ($receipt_type) {
            case 'sale':
                // Check if sale exists
                $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
                $stmt->execute([$receipt_id]);
                $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$receipt) {
                    throw new Exception('پسووڵەی فرۆشتن نەدۆزرایەوە - ID: ' . $receipt_id);
                }
                
                // Check for returns
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_returns WHERE receipt_id = ? AND receipt_type = 'sale'");
                $stmt->execute([$receipt_id]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                    throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
                }
                
                // Check for payments if credit sale
                if ($receipt['payment_type'] === 'credit') {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM debt_transactions WHERE reference_id = ? AND transaction_type IN ('payment', 'return')");
                    $stmt->execute([$receipt_id]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                        throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە پارەدانی لەسەر تۆمارکراوە');
                    }
                }
                
                // Get sale items
                $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
                $stmt->execute([$receipt_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Update product quantities
                foreach ($items as $item) {
                    $stmt = $conn->prepare("UPDATE products SET current_quantity = current_quantity + ? WHERE id = ?");
                    $stmt->execute([$item['pieces_count'], $item['product_id']]);
                }
                
                // If it's a credit sale, update customer debt
                if ($receipt['payment_type'] === 'credit') {
                    // Calculate total amount
                    $totalAmount = $receipt['total_amount'];
                    
                    // Update customer debt
                    $stmt = $conn->prepare("
                        UPDATE customers 
                        SET debit_on_business = GREATEST(0, debit_on_business - :amount)
                        WHERE id = :customer_id
                    ");
                    $stmt->execute([
                        ':amount' => $totalAmount,
                        ':customer_id' => $receipt['customer_id']
                    ]);
                }
                
                // Delete related records
                $stmt = $conn->prepare("DELETE FROM inventory WHERE reference_type = 'sale' AND reference_id IN (SELECT id FROM sale_items WHERE sale_id = ?)");
                $stmt->execute([$receipt_id]);
                
                $stmt = $conn->prepare("DELETE FROM debt_transactions WHERE reference_id = ? AND transaction_type = 'sale'");
                $stmt->execute([$receipt_id]);
                
                $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
                $stmt->execute([$receipt_id]);
                
                $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
                $stmt->execute([$receipt_id]);
                break;
                
            case 'purchase':
                // Check if purchase exists
                $stmt = $conn->prepare("SELECT * FROM purchases WHERE id = ?");
                $stmt->execute([$receipt_id]);
                $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$receipt) {
                    throw new Exception('پسووڵەی کڕین نەدۆزرایەوە - ID: ' . $receipt_id);
                }
                
                // Check for returns
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_returns WHERE receipt_id = ? AND receipt_type = 'purchase'");
                $stmt->execute([$receipt_id]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                    throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
                }
                
                // Check for payments if credit purchase
                if ($receipt['payment_type'] === 'credit') {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM supplier_debt_transactions WHERE reference_id = ? AND transaction_type IN ('payment', 'return')");
                    $stmt->execute([$receipt_id]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                        throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە پارەدانی لەسەر تۆمارکراوە');
                    }
                }
                
                // Get purchase items
                $stmt = $conn->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
                $stmt->execute([$receipt_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Update product quantities
                foreach ($items as $item) {
                    $stmt = $conn->prepare("UPDATE products SET current_quantity = current_quantity - ? WHERE id = ?");
                    $stmt->execute([$item['pieces_count'], $item['product_id']]);
                }
                
                // If it's a credit purchase, update supplier debt
                if ($receipt['payment_type'] === 'credit') {
                    // Calculate total amount
                    $totalAmount = $receipt['total_amount'];
                    
                    // Update supplier debt
                    $stmt = $conn->prepare("
                        UPDATE suppliers 
                        SET debt_on_myself = GREATEST(0, debt_on_myself - :amount)
                        WHERE id = :supplier_id
                    ");
                    $stmt->execute([
                        ':amount' => $totalAmount,
                        ':supplier_id' => $receipt['supplier_id']
                    ]);
                }
                
                // Delete related records
                $stmt = $conn->prepare("DELETE FROM inventory WHERE reference_type = 'purchase' AND reference_id IN (SELECT id FROM purchase_items WHERE purchase_id = ?)");
                $stmt->execute([$receipt_id]);
                
                $stmt = $conn->prepare("DELETE FROM supplier_debt_transactions WHERE reference_id = ? AND transaction_type = 'purchase'");
                $stmt->execute([$receipt_id]);
                
                $stmt = $conn->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
                $stmt->execute([$receipt_id]);
                
                $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
                $stmt->execute([$receipt_id]);
                break;
                
            case 'draft':
                // Check if draft exists
                $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ? AND is_draft = 1");
                $stmt->execute([$receipt_id]);
                $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$receipt) {
                    throw new Exception('ڕەشنووسی پسووڵە نەدۆزرایەوە - ID: ' . $receipt_id);
                }
                
                // Delete related records
                $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
                $stmt->execute([$receipt_id]);
                
                $stmt = $conn->prepare("DELETE FROM sales WHERE id = ? AND is_draft = 1");
                $stmt->execute([$receipt_id]);
                break;
                
            default:
                throw new Exception('جۆری پسووڵە نادروستە');
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'پسووڵە بە سەرکەوتوویی سڕایەوە'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete_receipt.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ]
    ]);
} 