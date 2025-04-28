<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['id']) || !isset($data['type'])) {
        throw new Exception('اڵتاکانی پێویست کەم دەکات');
    }
    
    // Start a transaction
    $conn->beginTransaction();
    
    $receiptId = $data['id'];
    $receiptType = $data['type'];
    
    // Handle different receipt types
    if ($receiptType === 'selling') {
        // Update sales table
        $updateSaleQuery = "UPDATE sales SET 
            invoice_number = :invoice_number,
            date = :date,
            customer_id = :customer_id,
            payment_type = :payment_type,
            shipping_cost = :shipping_cost,
            other_costs = :other_costs,
            discount = :discount,
            notes = :notes";
            
        // Add paid_amount if payment type is credit
        if ($data['payment_type'] === 'credit') {
            $updateSaleQuery .= ", paid_amount = :paid_amount";
        }
        
        $updateSaleQuery .= " WHERE id = :id";
        
        $stmt = $conn->prepare($updateSaleQuery);
        $stmt->bindParam(':invoice_number', $data['invoice_number']);
        $stmt->bindParam(':date', $data['date']);
        $stmt->bindParam(':customer_id', $data['customer_id']);
        $stmt->bindParam(':payment_type', $data['payment_type']);
        $stmt->bindParam(':shipping_cost', $data['shipping_cost']);
        $stmt->bindParam(':other_costs', $data['other_costs']);
        $stmt->bindParam(':discount', $data['discount']);
        $stmt->bindParam(':notes', $data['notes']);
        
        if ($data['payment_type'] === 'credit') {
            $stmt->bindParam(':paid_amount', $data['paid_amount']);
        }
        
        $stmt->bindParam(':id', $receiptId);
        $stmt->execute();
        
        // Delete existing items
        $deleteItemsQuery = "DELETE FROM sale_items WHERE sale_id = :sale_id";
        $stmt = $conn->prepare($deleteItemsQuery);
        $stmt->bindParam(':sale_id', $receiptId);
        $stmt->execute();
        
        // Insert new items
        $insertItemQuery = "INSERT INTO sale_items (sale_id, product_id, unit_type, unit_price, quantity, total_price) 
                            VALUES (:sale_id, :product_id, :unit_type, :unit_price, :quantity, :total_price)";
        $stmt = $conn->prepare($insertItemQuery);
        
        foreach ($data['items'] as $item) {
            $totalPrice = $item['unit_price'] * $item['quantity'];
            
            $stmt->bindParam(':sale_id', $receiptId);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':unit_type', $item['unit_type']);
            $stmt->bindParam(':unit_price', $item['unit_price']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':total_price', $totalPrice);
            $stmt->execute();
            
            // Update product stock
            updateProductStock($conn, $item['product_id'], $item['quantity'], 'subtract');
        }
    } elseif ($receiptType === 'buying') {
        // Update purchases table
        $updatePurchaseQuery = "UPDATE purchases SET 
            invoice_number = :invoice_number,
            date = :date,
            supplier_id = :supplier_id,
            payment_type = :payment_type,
            shipping_cost = :shipping_cost,
            other_costs = :other_costs,
            discount = :discount,
            notes = :notes";
            
        // Add paid_amount if payment type is credit
        if ($data['payment_type'] === 'credit') {
            $updatePurchaseQuery .= ", paid_amount = :paid_amount";
        }
        
        $updatePurchaseQuery .= " WHERE id = :id";
        
        $stmt = $conn->prepare($updatePurchaseQuery);
        $stmt->bindParam(':invoice_number', $data['invoice_number']);
        $stmt->bindParam(':date', $data['date']);
        $stmt->bindParam(':supplier_id', $data['supplier_id']);
        $stmt->bindParam(':payment_type', $data['payment_type']);
        $stmt->bindParam(':shipping_cost', $data['shipping_cost']);
        $stmt->bindParam(':other_costs', $data['other_costs']);
        $stmt->bindParam(':discount', $data['discount']);
        $stmt->bindParam(':notes', $data['notes']);
        
        if ($data['payment_type'] === 'credit') {
            $stmt->bindParam(':paid_amount', $data['paid_amount']);
        }
        
        $stmt->bindParam(':id', $receiptId);
        $stmt->execute();
        
        // Delete existing items
        $deleteItemsQuery = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
        $stmt = $conn->prepare($deleteItemsQuery);
        $stmt->bindParam(':purchase_id', $receiptId);
        $stmt->execute();
        
        // Insert new items
        $insertItemQuery = "INSERT INTO purchase_items (purchase_id, product_id, unit_type, unit_price, quantity, total_price) 
                           VALUES (:purchase_id, :product_id, :unit_type, :unit_price, :quantity, :total_price)";
        $stmt = $conn->prepare($insertItemQuery);
        
        foreach ($data['items'] as $item) {
            $totalPrice = $item['unit_price'] * $item['quantity'];
            
            $stmt->bindParam(':purchase_id', $receiptId);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':unit_type', $item['unit_type']);
            $stmt->bindParam(':unit_price', $item['unit_price']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':total_price', $totalPrice);
            $stmt->execute();
            
            // Update product stock
            updateProductStock($conn, $item['product_id'], $item['quantity'], 'add');
        }
    } else {
        throw new Exception('جۆری پسووڵە نادروستە');
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'پسووڵەکە بە سەرکەوتوویی نوێ کرایەوە'
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the error
    error_log('Error in update_receipt.php: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
}

// Function to update product stock
function updateProductStock($conn, $productId, $quantity, $operation) {
    // Get current stock
    $stockQuery = "SELECT current_quantity FROM products WHERE id = :product_id";
    $stmt = $conn->prepare($stockQuery);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    $currentStock = $stmt->fetchColumn();
    
    // Calculate new stock
    if ($operation === 'add') {
        $newStock = $currentStock + $quantity;
    } else {
        $newStock = $currentStock - $quantity;
    }
    
    // Update stock
    $updateQuery = "UPDATE products SET current_quantity = :stock_quantity WHERE id = :product_id";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bindParam(':stock_quantity', $newStock);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
} 