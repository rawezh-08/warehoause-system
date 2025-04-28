<?php
require_once '../config/database.php';

try {
    // Add new columns to return_items table
    $alterQueries = [
        "ALTER TABLE return_items 
         ADD COLUMN original_unit_type ENUM('piece', 'box', 'set') DEFAULT 'piece' AFTER unit_type,
         ADD COLUMN original_quantity DECIMAL(10,2) DEFAULT 0 AFTER original_unit_type,
         ADD COLUMN reason ENUM('damaged', 'wrong_product', 'customer_request', 'other') DEFAULT 'other' AFTER original_quantity"
    ];

    foreach ($alterQueries as $query) {
        $conn->exec($query);
    }

    echo "Table return_items has been updated successfully!";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 