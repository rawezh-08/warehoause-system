<?php
header('Content-Type: application/json');
require_once '../../../config/database.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the receipt ID from the POST data
$receipt_id = isset($_POST['receipt_id']) ? intval($_POST['receipt_id']) : 0;

if ($receipt_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid receipt ID']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // First, get the receipt items to update product quantities
    $stmt = $conn->prepare("SELECT product_id, quantity FROM receipt_items WHERE receipt_id = ?");
    $stmt->execute([$receipt_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update product quantities
    foreach ($items as $item) {
        $update_stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $update_stmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Delete receipt items
    $stmt = $conn->prepare("DELETE FROM receipt_items WHERE receipt_id = ?");
    $stmt->execute([$receipt_id]);

    // Delete the receipt
    $stmt = $conn->prepare("DELETE FROM receipts WHERE id = ? AND status = 'draft'");
    $stmt->execute([$receipt_id]);
    $affected_rows = $stmt->rowCount();

    if ($affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Draft receipt deleted successfully']);
    } else {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Receipt not found or not a draft']);
    }

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error deleting draft receipt: ' . $e->getMessage()]);
}
?> 