<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $code = $_POST['code'];
        $barcode = $_POST['barcode'];
        $shelf = $_POST['shelf'];
        $notes = $_POST['notes'];
        $category_id = $_POST['category_id'];
        $unit_id = $_POST['unit_id'];
        $pieces_per_box = $_POST['pieces_per_box'];
        $boxes_per_set = $_POST['boxes_per_set'];
        $purchase_price = $_POST['purchase_price'];
        $selling_price_single = $_POST['selling_price_single'];
        $selling_price_wholesale = $_POST['selling_price_wholesale'];
        $current_quantity = $_POST['current_quantity'];
        $min_quantity = $_POST['min_quantity'];

        $sql = "UPDATE products SET 
                name = ?, 
                code = ?,
                barcode = ?,
                shelf = ?,
                notes = ?,
                category_id = ?,
                unit_id = ?,
                pieces_per_box = ?,
                boxes_per_set = ?,
                purchase_price = ?,
                selling_price_single = ?,
                selling_price_wholesale = ?,
                current_quantity = ?,
                min_quantity = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $name,
            $code,
            $barcode,
            $shelf,
            $notes,
            $category_id,
            $unit_id,
            $pieces_per_box,
            $boxes_per_set,
            $purchase_price,
            $selling_price_single,
            $selling_price_wholesale,
            $current_quantity,
            $min_quantity,
            $id
        ]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 