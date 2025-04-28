<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $product_id = (int)$_POST['id'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // First, delete all related records from dependent tables
        
        // Delete from inventory
        $delete_inventory = "DELETE FROM inventory WHERE product_id = ?";
        $stmt = $conn->prepare($delete_inventory);
        $stmt->execute([$product_id]);
        
        // Delete from purchase_items
        $delete_purchase_items = "DELETE FROM purchase_items WHERE product_id = ?";
        $stmt = $conn->prepare($delete_purchase_items);
        $stmt->execute([$product_id]);
        
        // Delete from sale_items
        $delete_sale_items = "DELETE FROM sale_items WHERE product_id = ?";
        $stmt = $conn->prepare($delete_sale_items);
        $stmt->execute([$product_id]);
        
        // Delete from inventory_count_items
        $delete_inventory_count_items = "DELETE FROM inventory_count_items WHERE product_id = ?";
        $stmt = $conn->prepare($delete_inventory_count_items);
        $stmt->execute([$product_id]);
        
        // Then delete the product
        $delete_product = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($delete_product);
        $stmt->execute([$product_id]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'کاڵاکە بە سەرکەوتوویی سڕایەوە']);
    } catch (PDOException $e) {
        // Rollback transaction if there's an error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'پارامێتەرەکان نادروستن']);
} 