<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Validate required fields
    $required_fields = ['name', 'category_id', 'unit_id', 'purchase_price', 'selling_price_single', 'quantity'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field $field is required");
        }
    }

    // Prepare data
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $unit_id = $_POST['unit_id'];
    $purchase_price = $_POST['purchase_price'];
    $selling_price_single = $_POST['selling_price_single'];
    $selling_price_wholesale = $_POST['selling_price_wholesale'] ?? $selling_price_single;
    $code = $_POST['code'];
    $barcode = $_POST['barcode'];
    $quantity = intval($_POST['quantity']);

    // Insert the product
    $stmt = $conn->prepare("
        INSERT INTO products (
            name, code, barcode, category_id, unit_id,
            purchase_price, selling_price_single, selling_price_wholesale,
            current_quantity
        ) VALUES (
            :name, :code, :barcode, :category_id, :unit_id,
            :purchase_price, :selling_price_single, :selling_price_wholesale,
            :current_quantity
        )
    ");

    $stmt->execute([
        'name' => $name,
        'code' => $code,
        'barcode' => $barcode,
        'category_id' => $category_id,
        'unit_id' => $unit_id,
        'purchase_price' => $purchase_price,
        'selling_price_single' => $selling_price_single,
        'selling_price_wholesale' => $selling_price_wholesale,
        'current_quantity' => $quantity
    ]);

    $product_id = $conn->lastInsertId();

    // Add inventory entry with simpler approach to avoid parameter naming issues
    if ($quantity > 0) {
        try {
            // First try with 'price' column
            $inventory_stmt = $conn->prepare("
                INSERT INTO inventory (
                    product_id, quantity, price, transaction_type, date, description
                ) VALUES (
                    :product_id, :quantity, :price, :transaction_type, :date, :description
                )
            ");
            
            $inventory_stmt->execute([
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $purchase_price,
                'transaction_type' => 'initial',
                'date' => date('Y-m-d H:i:s'),
                'description' => 'زیادکردنی سەرەتایی لە کاتی فرۆشتن'
            ]);
        } catch (Exception $ex) {
            // If first attempt fails, try with basic columns
            try {
                $inventory_stmt = $conn->prepare("
                    INSERT INTO inventory (
                        product_id, quantity
                    ) VALUES (
                        :product_id, :quantity
                    )
                ");
                
                $inventory_stmt->execute([
                    'product_id' => $product_id,
                    'quantity' => $quantity
                ]);
            } catch (Exception $ex2) {
                // Log error but continue - we don't want to fail the whole product creation
                error_log("Failed to create inventory record: " . $ex2->getMessage());
            }
        }
    }

    echo json_encode([
        'success' => true,
        'product_id' => $product_id,
        'message' => 'Product added successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 