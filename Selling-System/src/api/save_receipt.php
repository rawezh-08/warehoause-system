<?php
// Include database connection
require_once '../config/db_connection.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get data from POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate input data
if (!$data || !isset($data['receipt_type'])) {
    echo json_encode(['success' => false, 'message' => 'داتای نادروست']);
    exit;
}

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");
    
    // Start transaction
    $conn->beginTransaction();
    
    // Handle different receipt types
    $receipt_id = null;
    
    if ($data['receipt_type'] === 'selling') {
        // Validate selling data
        if (empty($data['customer_id']) || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'کڕیار و کاڵاکان پێویستن']);
            exit;
        }
        
        // Prepare data for stored procedure
        $invoice_number = $data['invoice_number'] ?? '';
        $customer_id = $data['customer_id'];
        $date = !empty($data['date']) ? $data['date'] : date('Y-m-d H:i:s');
        $payment_type = $data['payment_type'] ?? 'cash';
        $discount = floatval($data['discount'] ?? 0);
        $price_type = $data['price_type'] ?? 'single';
        $shipping_cost = floatval($data['shipping_cost'] ?? 0);
        $other_costs = floatval($data['other_costs'] ?? 0);
        $notes = $data['notes'] ?? '';
        $created_by = 1; // Replace with actual user ID when authentication is implemented
        
        // Format products data for the stored procedure
        $products_json = [];
        foreach ($data['items'] as $item) {
            $products_json[] = [
                'product_id' => (int)$item['product_id'],
                'quantity' => (int)$item['quantity'],
                'unit_type' => $item['unit_type'],
                'unit_price' => floatval($item['unit_price'])
            ];
        }
        
        $products_json_string = json_encode($products_json);
        
        // Call the stored procedure to add sale
        $stmt = $conn->prepare("CALL add_sale(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $invoice_number, PDO::PARAM_STR);
        $stmt->bindParam(2, $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $date, PDO::PARAM_STR);
        $stmt->bindParam(4, $payment_type, PDO::PARAM_STR);
        $stmt->bindParam(5, $discount, PDO::PARAM_STR);
        $stmt->bindParam(6, $price_type, PDO::PARAM_STR);
        $stmt->bindParam(7, $shipping_cost, PDO::PARAM_STR);
        $stmt->bindParam(8, $other_costs, PDO::PARAM_STR);
        $stmt->bindParam(9, $notes, PDO::PARAM_STR);
        $stmt->bindParam(10, $created_by, PDO::PARAM_INT);
        $stmt->bindParam(11, $products_json_string, PDO::PARAM_STR);
        $stmt->execute();
        
        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $receipt_id = $result['result'];
        // Close the cursor to release the connection for next query
        $stmt->closeCursor();
        
        // Add debt transaction if payment type is credit
        if ($payment_type === 'credit') {
            // Calculate total amount
            $total_amount = 0;
            foreach ($data['items'] as $item) {
                $total_amount += floatval($item['total_price']);
            }
            $total_amount = $total_amount - $discount + $shipping_cost + $other_costs;
            
            // Add debt transaction
            $stmt2 = $conn->prepare("CALL add_debt_transaction(?, ?, 'sale', ?, ?, ?)");
            $stmt2->bindParam(1, $customer_id, PDO::PARAM_INT);
            $stmt2->bindParam(2, $total_amount, PDO::PARAM_STR);
            $stmt2->bindParam(3, $receipt_id, PDO::PARAM_INT);
            $stmt2->bindParam(4, $notes, PDO::PARAM_STR);
            $stmt2->bindParam(5, $created_by, PDO::PARAM_INT);
            $stmt2->execute();
            // Close this cursor too
            $stmt2->closeCursor();
        }
        
    } elseif ($data['receipt_type'] === 'buying') {
        // Validate buying data
        if (empty($data['supplier_id']) || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'فرۆشیار و کاڵاکان پێویستن']);
            exit;
        }
        
        // Prepare data for stored procedure
        $invoice_number = $data['invoice_number'] ?? '';
        $supplier_id = $data['supplier_id'];
        $date = !empty($data['date']) ? $data['date'] : date('Y-m-d H:i:s');
        $payment_type = $data['payment_type'] ?? 'cash';
        $discount = floatval($data['discount'] ?? 0);
        $notes = $data['notes'] ?? '';
        $created_by = 1; // Replace with actual user ID when authentication is implemented
        
        // Format products data for the stored procedure
        $products_json = [];
        foreach ($data['items'] as $item) {
            $products_json[] = [
                'product_id' => (int)$item['product_id'],
                'quantity' => (int)$item['quantity'],
                'unit_price' => floatval($item['unit_price'])
            ];
        }
        
        $products_json_string = json_encode($products_json);
        
        // Call the stored procedure to add purchase
        $stmt = $conn->prepare("CALL add_purchase(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $invoice_number, PDO::PARAM_STR);
        $stmt->bindParam(2, $supplier_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $date, PDO::PARAM_STR);
        $stmt->bindParam(4, $payment_type, PDO::PARAM_STR);
        $stmt->bindParam(5, $discount, PDO::PARAM_STR);
        $stmt->bindParam(6, $notes, PDO::PARAM_STR);
        $stmt->bindParam(7, $created_by, PDO::PARAM_INT);
        $stmt->bindParam(8, $products_json_string, PDO::PARAM_STR);
        $stmt->execute();
        
        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $receipt_id = $result['result'];
        // Close the cursor to release the connection
        $stmt->closeCursor();
        
    } elseif ($data['receipt_type'] === 'wasting') {
        // Validate inventory adjustment data
        if (empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'کاڵاکان پێویستن']);
            exit;
        }
        
        // Prepare data for stored procedure
        $notes = $data['notes'] ?? '';
        $created_by = 1; // Replace with actual user ID when authentication is implemented
        
        // Format count items data for the stored procedure
        $count_items_json = [];
        foreach ($data['items'] as $item) {
            $count_items_json[] = [
                'product_id' => (int)$item['product_id'],
                'actual_quantity' => (int)$item['actual_quantity']
            ];
        }
        
        $count_items_json_string = json_encode($count_items_json);
        
        // Call the stored procedure to create inventory count
        $stmt = $conn->prepare("CALL create_inventory_count(?, ?, ?)");
        $stmt->bindParam(1, $notes, PDO::PARAM_STR);
        $stmt->bindParam(2, $created_by, PDO::PARAM_INT);
        $stmt->bindParam(3, $count_items_json_string, PDO::PARAM_STR);
        $stmt->execute();
        
        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $receipt_id = $result['result'];
        // Close the cursor to release the connection
        $stmt->closeCursor();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'پسوڵە بەسەرکەوتوویی پاشەکەوتکرا',
        'receipt_id' => $receipt_id
    ]);
    
} catch(PDOException $e) {
    // Rollback transaction in case of error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 