<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// For debugging
function debug_log($message) {
    error_log(print_r($message, true));
}

// Check if receipt_id is provided
if (!isset($_POST['receipt_id']) || empty($_POST['receipt_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامەی پسوڵە پێویستە'
    ]);
    exit;
}

$receiptId = $_POST['receipt_id'];
debug_log("Processing receipt ID: " . $receiptId);

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Get draft receipt details
    $stmt = $conn->prepare("
        SELECT s.*, 
               si.product_id,
               si.quantity,
               si.unit_type,
               si.unit_price,
               si.total_price
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE s.id = :receipt_id AND s.is_draft = 1
    ");
    $stmt->bindParam(':receipt_id', $receiptId);
    $stmt->execute();
    $draftData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($draftData)) {
        throw new Exception('پسوڵەکە نەدۆزرایەوە یان پێشتر تەواوکراوە');
    }
    
    debug_log("Draft data:");
    debug_log($draftData);
    
    // Get current customer debt
    $stmt = $conn->prepare("SELECT debit_on_business FROM customers WHERE id = ?");
    $stmt->execute([$draftData[0]['customer_id']]);
    $oldDebt = $stmt->fetchColumn();
    debug_log("Current customer debt: " . $oldDebt);
    
    // Prepare products JSON for add_sale procedure
    $products = [];
    foreach ($draftData as $item) {
        if (isset($item['product_id'])) {
            $products[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_type' => $item['unit_type'],
                'unit_price' => $item['unit_price']
            ];
        }
    }
    
    // Convert products array to JSON
    $productsJson = json_encode($products);
    debug_log("Products JSON:");
    debug_log($productsJson);
    
    // First, mark the draft as deleted
    $stmt = $conn->prepare("UPDATE sales SET is_draft = 2 WHERE id = :receipt_id");
    $stmt->bindParam(':receipt_id', $receiptId);
    $stmt->execute();
    
    // Call add_sale stored procedure
    $stmt = $conn->prepare("CALL add_sale(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $saleData = $draftData[0]; // Get the sale data from first row
    $createdBy = 1; // Replace with actual user ID from session
    
    debug_log("Calling add_sale with params:");
    debug_log([
        'invoice_number' => $saleData['invoice_number'],
        'customer_id' => $saleData['customer_id'],
        'date' => $saleData['date'],
        'payment_type' => $saleData['payment_type'],
        'discount' => $saleData['discount'],
        'paid_amount' => $saleData['paid_amount'],
        'price_type' => $saleData['price_type'],
        'shipping_cost' => $saleData['shipping_cost'],
        'other_costs' => $saleData['other_costs'],
        'notes' => $saleData['notes'],
        'created_by' => $createdBy,
        'products' => $productsJson
    ]);
    
    $stmt->execute([
        $saleData['invoice_number'],
        $saleData['customer_id'],
        $saleData['date'],
        $saleData['payment_type'],
        $saleData['discount'],
        $saleData['paid_amount'],
        $saleData['price_type'],
        $saleData['shipping_cost'],
        $saleData['other_costs'],
        $saleData['notes'],
        $createdBy,
        $productsJson
    ]);
    
    // Get the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    debug_log("Stored procedure result:");
    debug_log($result);
    
    // Get new customer debt
    $stmt = $conn->prepare("SELECT debit_on_business FROM customers WHERE id = ?");
    $stmt->execute([$saleData['customer_id']]);
    $newDebt = $stmt->fetchColumn();
    debug_log("New customer debt: " . $newDebt);
    debug_log("Debt difference: " . ($newDebt - $oldDebt));
    
    // Delete the draft receipt and its items
    $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = :receipt_id");
    $stmt->bindParam(':receipt_id', $receiptId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM sales WHERE id = :receipt_id");
    $stmt->bindParam(':receipt_id', $receiptId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    debug_log("Transaction committed successfully");
    
    echo json_encode([
        'success' => true,
        'message' => 'پسوڵە بە سەرکەوتوویی تەواوکرا',
        'old_debt' => $oldDebt,
        'new_debt' => $newDebt,
        'difference' => $newDebt - $oldDebt
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    debug_log("Error occurred:");
    debug_log($e->getMessage());
    debug_log($e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 