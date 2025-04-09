<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get data from POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Debug: Log received data
error_log("Received data: " . print_r($data, true));

// Validate input data
if (!$data) {
    echo json_encode([
        'success' => false, 
        'message' => 'داتای نادروست',
        'debug' => [
            'received_data' => $json_data,
            'json_error' => json_last_error_msg()
        ]
    ]);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Handle different receipt types
    $receipt_id = null;
    
    if ($data['receipt_type'] === 'selling') {
        // Validate selling data
        if (empty($data['customer_id']) || empty($data['products'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'کڕیار و کاڵاکان پێویستن',
                'debug' => [
                    'customer_id' => $data['customer_id'] ?? 'missing',
                    'products' => $data['products'] ?? 'missing'
                ]
            ]);
            exit;
        }
        
        // Format products data for the stored procedure
        $products_json = [];
        foreach ($data['products'] as $item) {
            $products_json[] = [
                'product_id' => (int)$item['product_id'],
                'quantity' => (int)$item['quantity'],
                'unit_type' => $item['unit_type'],
                'unit_price' => floatval($item['unit_price'])
            ];
        }
        
        $products_json_string = json_encode($products_json);
        
        // Debug: Log the prepared data
        error_log("Prepared sale data: " . print_r([
            'invoice_number' => $data['invoice_number'],
            'customer_id' => $data['customer_id'],
            'date' => $data['date'],
            'payment_type' => $data['payment_type'],
            'discount' => floatval($data['discount']),
            'paid_amount' => floatval($data['paid_amount']),
            'price_type' => $data['price_type'],
            'shipping_cost' => floatval($data['shipping_cost']),
            'other_costs' => floatval($data['other_costs']),
            'notes' => $data['notes'],
            'products' => $products_json
        ], true));
        
        // Call the stored procedure to add sale
        $stmt = $conn->prepare("CALL add_sale(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $data['invoice_number'], PDO::PARAM_STR);
        $stmt->bindParam(2, $data['customer_id'], PDO::PARAM_INT);
        $stmt->bindParam(3, $data['date'], PDO::PARAM_STR);
        $stmt->bindParam(4, $data['payment_type'], PDO::PARAM_STR);
        $stmt->bindParam(5, $data['discount'], PDO::PARAM_STR);
        $stmt->bindParam(6, $data['paid_amount'], PDO::PARAM_STR);
        $stmt->bindParam(7, $data['price_type'], PDO::PARAM_STR);
        $stmt->bindParam(8, $data['shipping_cost'], PDO::PARAM_STR);
        $stmt->bindParam(9, $data['other_costs'], PDO::PARAM_STR);
        $stmt->bindParam(10, $data['notes'], PDO::PARAM_STR);
        $created_by = 1; // Replace with actual user ID when authentication is implemented
        $stmt->bindParam(11, $created_by, PDO::PARAM_INT);
        $stmt->bindParam(12, $products_json_string, PDO::PARAM_STR);
        
        $stmt->execute();
        
        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $receipt_id = $result['result'];
        
        // Close the cursor to prevent "Cannot execute queries while there are pending result sets" error
        $stmt->closeCursor();
        
    } elseif ($data['receipt_type'] === 'buying') {
        // Validate buying data
        if (empty($data['supplier_id']) || empty($data['products'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'فرۆشیار و کاڵاکان پێویستن',
                'debug' => [
                    'supplier_id' => $data['supplier_id'] ?? 'missing',
                    'products' => $data['products'] ?? 'missing'
                ]
            ]);
            exit;
        }
        
        // Format products data for the stored procedure
        $products_json = [];
        foreach ($data['products'] as $item) {
            $products_json[] = [
                'product_id' => (int)$item['product_id'],
                'quantity' => (int)$item['quantity'],
                'unit_price' => floatval($item['unit_price'])
            ];
        }
        
        $products_json_string = json_encode($products_json);
        
        // Debug: Log the prepared data
        error_log("Prepared purchase data: " . print_r([
            'invoice_number' => $data['invoice_number'],
            'supplier_id' => $data['supplier_id'],
            'date' => $data['date'],
            'payment_type' => $data['payment_type'],
            'discount' => floatval($data['discount']),
            'paid_amount' => floatval($data['paid_amount']),
            'notes' => $data['notes'],
            'products' => $products_json
        ], true));
        
        // Call the stored procedure to add purchase
        $stmt = $conn->prepare("CALL add_purchase(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $data['invoice_number'], PDO::PARAM_STR);
        $stmt->bindParam(2, $data['supplier_id'], PDO::PARAM_INT);
        $stmt->bindParam(3, $data['date'], PDO::PARAM_STR);
        $stmt->bindParam(4, $data['payment_type'], PDO::PARAM_STR);
        $stmt->bindParam(5, $data['discount'], PDO::PARAM_STR);
        $stmt->bindParam(6, $data['paid_amount'], PDO::PARAM_STR);
        $stmt->bindParam(7, $data['notes'], PDO::PARAM_STR);
        $created_by = 1; // Replace with actual user ID when authentication is implemented
        $stmt->bindParam(8, $created_by, PDO::PARAM_INT);
        $stmt->bindParam(9, $products_json_string, PDO::PARAM_STR);
        
        $stmt->execute();
        
        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $receipt_id = $result['result'];
        
        // Close the cursor to prevent "Cannot execute queries while there are pending result sets" error
        $stmt->closeCursor();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'receipt_id' => $receipt_id,
        'message' => 'پسووڵە بە سەرکەوتوویی پاشەکەوت کرا'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error if it's active
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی پاشەکەوتکردن',
        'debug' => [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'sql_state' => $e->errorInfo[0] ?? null,
            'driver_code' => $e->errorInfo[1] ?? null,
            'driver_message' => $e->errorInfo[2] ?? null
        ]
    ]);
}
?> 