<?php
// Turn off all error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get data from POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Debug: Log received data
error_log("Raw POST data: " . print_r($_POST, true));
error_log("Raw JSON data: " . $json_data);
error_log("Decoded data: " . print_r($data, true));

// Validate input data
if (!$data) {
    $error_message = json_last_error_msg();
    error_log("JSON decode error: " . $error_message);
    echo json_encode([
        'success' => false, 
        'message' => 'داتای نادروست',
        'debug' => [
            'received_data' => $json_data,
            'json_error' => $error_message,
            'post_data' => $_POST
        ]
    ]);
    exit;
}

// Validate required fields
$required_fields = ['receipt_type', 'invoice_number', 'date', 'payment_type'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    error_log("Missing required fields: " . implode(', ', $missing_fields));
    echo json_encode([
        'success' => false,
        'message' => 'زانیاری پێویست کەمە: ' . implode(', ', $missing_fields),
        'debug' => [
            'missing_fields' => $missing_fields,
            'received_data' => $data
        ]
    ]);
    exit;
}

// Check if this is a draft receipt
$is_draft = isset($data['is_draft']) ? $data['is_draft'] : false;

// Validate receipt type specific fields
if ($data['receipt_type'] === 'selling') {
    if (empty($data['customer_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'کڕیار پێویستە',
            'debug' => [
                'customer_id' => $data['customer_id'] ?? 'missing'
            ]
        ]);
        exit;
    }
} elseif ($data['receipt_type'] === 'buying') {
    if (empty($data['supplier_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'فرۆشیار پێویستە',
            'debug' => [
                'supplier_id' => $data['supplier_id'] ?? 'missing'
            ]
        ]);
        exit;
    }
}

// Validate products
if (empty($data['products']) || !is_array($data['products'])) {
    echo json_encode([
        'success' => false,
        'message' => 'کاڵاکان پێویستن',
        'debug' => [
            'products' => $data['products'] ?? 'missing'
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
        
        // If this is a draft receipt, handle it differently
        if ($is_draft) {
            // For draft sales, directly insert into database without calling stored procedure
            // This approach doesn't modify inventory counts
            $stmt = $conn->prepare("INSERT INTO sales (
                invoice_number, customer_id, date, payment_type, 
                discount, paid_amount, price_type, shipping_cost, other_costs,
                notes, created_by, is_draft
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1
            )");
            
            $stmt->bindParam(1, $data['invoice_number'], PDO::PARAM_STR);
            $stmt->bindParam(2, $data['customer_id'], PDO::PARAM_INT);
            $stmt->bindParam(3, $data['date'], PDO::PARAM_STR);
            $stmt->bindParam(4, $data['payment_type'], PDO::PARAM_STR);
            $stmt->bindParam(5, $data['discount'], PDO::PARAM_STR);
            $stmt->bindParam(6, $data['paid_amount'], PDO::PARAM_STR);
            $stmt->bindParam(7, $data['price_type'], PDO::PARAM_STR);
            $stmt->bindParam(8, $data['shipping_cost'], PDO::PARAM_STR);
            $stmt->bindParam(9, $data['other_cost'], PDO::PARAM_STR);
            $stmt->bindParam(10, $data['notes'], PDO::PARAM_STR);
            $created_by = 1; // Replace with actual user ID when authentication is implemented
            $stmt->bindParam(11, $created_by, PDO::PARAM_INT);
            
            $stmt->execute();
            $receipt_id = $conn->lastInsertId();
            
            // Add draft sale items without updating inventory
            $subtotal = 0;
            foreach ($data['products'] as $item) {
                $unit_price = floatval($item['unit_price']);
                $quantity = (int)$item['quantity'];
                $total_price = $unit_price * $quantity;
                $subtotal += $total_price;
                
                // Calculate pieces count based on unit type
                $product_info = $conn->prepare("SELECT pieces_per_box, boxes_per_set FROM products WHERE id = ?");
                $product_info->execute([(int)$item['product_id']]);
                $product = $product_info->fetch(PDO::FETCH_ASSOC);
                
                $pieces_count = $quantity; // Default for 'piece'
                if ($item['unit_type'] === 'box' && !empty($product['pieces_per_box'])) {
                    $pieces_count = $quantity * $product['pieces_per_box'];
                } elseif ($item['unit_type'] === 'set' && !empty($product['pieces_per_box']) && !empty($product['boxes_per_set'])) {
                    $pieces_count = $quantity * $product['pieces_per_box'] * $product['boxes_per_set'];
                }
                
                $stmt = $conn->prepare("INSERT INTO sale_items (
                    sale_id, product_id, quantity, unit_type, pieces_count,
                    unit_price, total_price
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                )");
                
                $stmt->execute([
                    $receipt_id,
                    (int)$item['product_id'],
                    $quantity,
                    $item['unit_type'],
                    $pieces_count,
                    $unit_price,
                    $total_price
                ]);
            }
            
            // Update total amounts
            $total_amount = $subtotal + floatval($data['shipping_cost']) + floatval($data['other_cost']) - floatval($data['discount']);
            $remaining_amount = $data['payment_type'] === 'credit' ? ($total_amount - floatval($data['paid_amount'])) : 0;
            
            $stmt = $conn->prepare("UPDATE sales SET 
                remaining_amount = ?, 
                paid_amount = ? 
                WHERE id = ?");
            
            // For draft receipts, don't record any debt transactions
            $stmt->execute([
                $remaining_amount,
                $data['payment_type'] === 'credit' ? floatval($data['paid_amount']) : $total_amount,
                $receipt_id
            ]);
        } else {
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
                'other_cost' => floatval($data['other_cost']),
                'notes' => $data['notes'],
                'products' => $products_json
            ], true));
            
            // For regular (non-draft) sales, call the stored procedure
            $stmt = $conn->prepare("CALL add_sale(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bindParam(1, $data['invoice_number'], PDO::PARAM_STR);
            $stmt->bindParam(2, $data['customer_id'], PDO::PARAM_INT);
            $stmt->bindParam(3, $data['date'], PDO::PARAM_STR);
            $stmt->bindParam(4, $data['payment_type'], PDO::PARAM_STR);
            $stmt->bindParam(5, $data['discount'], PDO::PARAM_STR);
            $stmt->bindParam(6, $data['paid_amount'], PDO::PARAM_STR);
            $stmt->bindParam(7, $data['price_type'], PDO::PARAM_STR);
            $stmt->bindParam(8, $data['shipping_cost'], PDO::PARAM_STR);
            $stmt->bindParam(9, $data['other_cost'], PDO::PARAM_STR);
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
        }
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
                'unit_type' => $item['unit_type'],
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
            'shipping_cost' => floatval($data['shipping_cost']),
            'other_cost' => floatval($data['other_cost']),
            'notes' => $data['notes'],
            'products' => $products_json
        ], true));
        
        // Call the stored procedure to add purchase
        $stmt = $conn->prepare("CALL add_purchase(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $data['invoice_number'], PDO::PARAM_STR);
        $stmt->bindParam(2, $data['supplier_id'], PDO::PARAM_INT);
        $stmt->bindParam(3, $data['date'], PDO::PARAM_STR);
        $stmt->bindParam(4, $data['payment_type'], PDO::PARAM_STR);
        $stmt->bindParam(5, $data['discount'], PDO::PARAM_STR);
        $stmt->bindParam(6, $data['paid_amount'], PDO::PARAM_STR);
        $stmt->bindParam(7, $data['shipping_cost'], PDO::PARAM_STR);
        $stmt->bindParam(8, $data['other_cost'], PDO::PARAM_STR);
        $stmt->bindParam(9, $data['notes'], PDO::PARAM_STR);
        $created_by = 1; // Replace with actual user ID when authentication is implemented
        $stmt->bindParam(10, $created_by, PDO::PARAM_INT);
        $stmt->bindParam(11, $products_json_string, PDO::PARAM_STR);
        
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
        'is_draft' => $is_draft,
        'message' => $is_draft ? 'ڕەشنووسی پسووڵە بە سەرکەوتوویی پاشەکەوت کرا' : 'پسووڵە بە سەرکەوتوویی پاشەکەوت کرا'
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