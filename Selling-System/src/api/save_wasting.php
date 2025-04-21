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
error_log("Raw JSON data: " . $json_data);
error_log("Decoded data: " . print_r($data, true));

// Validate input data
if (!$data) {
    echo json_encode([
        'success' => false, 
        'message' => 'داتای نادروست',
        'debug' => [
            'received_data' => $json_data,
            'json_error' => json_last_error_msg(),
            'post_data' => $_POST
        ]
    ]);
    exit;
}

// Validate required fields
if (empty($data['date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'بەروار پێویستە',
        'debug' => [
            'received_date' => $data['date'] ?? null
        ]
    ]);
    exit;
}

// Validate products
if (empty($data['products']) || !is_array($data['products'])) {
    echo json_encode([
        'success' => false,
        'message' => 'کاڵاکان پێویستن',
        'debug' => [
            'received_products' => $data['products'] ?? null
        ]
    ]);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Insert wasting record
    $stmt = $conn->prepare("INSERT INTO wastings (
        date, notes, created_by
    ) VALUES (
        ?, ?, ?
    )");

    $stmt->bindParam(1, $data['date'], PDO::PARAM_STR);
    $stmt->bindParam(2, $data['notes'], PDO::PARAM_STR);
    $created_by = 1; // Replace with actual user ID when authentication is implemented
    $stmt->bindParam(3, $created_by, PDO::PARAM_INT);

    $stmt->execute();
    $wasting_id = $conn->lastInsertId();

    // Process each wasted item
    foreach ($data['products'] as $item) {
        // Validate product data
        if (empty($item['product_id']) || empty($item['adjusted_quantity'])) {
            throw new Exception('داتای کاڵاکان ناتەواوە');
        }

        // Calculate pieces count based on unit type
        $product_info = $conn->prepare("SELECT pieces_per_box, boxes_per_set FROM products WHERE id = ?");
        $product_info->execute([(int)$item['product_id']]);
        $product = $product_info->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('کاڵای داواکراو نەدۆزرایەوە');
        }

        // Calculate pieces_count for inventory update
        $pieces_count = $item['adjusted_quantity']; // Default for 'piece'
        if ($item['unit_type'] === 'box' && !empty($product['pieces_per_box'])) {
            $pieces_count = $item['adjusted_quantity'] * $product['pieces_per_box'];
        } elseif ($item['unit_type'] === 'set' && !empty($product['pieces_per_box']) && !empty($product['boxes_per_set'])) {
            $pieces_count = $item['adjusted_quantity'] * $product['pieces_per_box'] * $product['boxes_per_set'];
        }

        // Calculate total price based on adjusted quantity (not pieces_count)
        $total_price = $item['adjusted_quantity'] * floatval($item['unit_price']);

        // Insert wasting item
        $stmt = $conn->prepare("INSERT INTO wasting_items (
            wasting_id, product_id, quantity, unit_type, pieces_count,
            unit_price, total_price
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?
        )");

        $stmt->execute([
            $wasting_id,
            (int)$item['product_id'],
            $item['adjusted_quantity'],
            $item['unit_type'],
            $pieces_count,
            floatval($item['unit_price']),
            $total_price
        ]);

        // Update product current quantity
        $update_stmt = $conn->prepare("UPDATE products SET current_quantity = current_quantity - ? WHERE id = ?");
        $update_stmt->execute([$pieces_count, (int)$item['product_id']]);

        // Record in inventory table
        $inventory_stmt = $conn->prepare("INSERT INTO inventory (
            product_id, quantity, reference_type, reference_id
        ) VALUES (
            ?, ?, 'adjustment', ?
        )");
        $inventory_stmt->execute([
            (int)$item['product_id'],
            -$pieces_count,
            $wasting_id
        ]);
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'wasting_id' => $wasting_id,
        'message' => 'پسووڵەی بەفیڕۆچوو بە سەرکەوتوویی پاشەکەوت کرا'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Error in save_wasting.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 