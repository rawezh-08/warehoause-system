<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['id', 'name', 'code', 'category_id', 'unit_id'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }

        $id = $_POST['id'];
        $name = $_POST['name'];
        $code = $_POST['code'];
        $barcode = $_POST['barcode'] ?? '';
    
        $notes = $_POST['notes'] ?? '';
        $category_id = $_POST['category_id'];
        $unit_id = $_POST['unit_id'];
        $pieces_per_box = $_POST['pieces_per_box'] ?? null;
        $boxes_per_set = $_POST['boxes_per_set'] ?? null;
        $purchase_price = $_POST['purchase_price'] ?? 0;
        $selling_price_single = $_POST['selling_price_single'] ?? 0;
        $selling_price_wholesale = $_POST['selling_price_wholesale'] ?? null;
        $min_quantity = $_POST['min_quantity'] ?? 0;

        $sql = "UPDATE products SET 
                name = ?, 
                code = ?,
                barcode = ?,
    
                notes = ?,
                category_id = ?,
                unit_id = ?,
                pieces_per_box = ?,
                boxes_per_set = ?,
                purchase_price = ?,
                selling_price_single = ?,
                selling_price_wholesale = ?,
                min_quantity = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $name,
            $code,
            $barcode,

            $notes,
            $category_id,
            $unit_id,
            $pieces_per_box,
            $boxes_per_set,
            $purchase_price,
            $selling_price_single,
            $selling_price_wholesale,
            $min_quantity,
            $id
        ]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 