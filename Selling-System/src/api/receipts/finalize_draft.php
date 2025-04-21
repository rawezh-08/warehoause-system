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

    // First, verify the receipt exists and is a draft
    $stmt = $conn->prepare("SELECT id, type FROM receipts WHERE id = ? AND status = 'draft'");
    $stmt->execute([$receipt_id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receipt) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Draft receipt not found']);
        exit;
    }

    // Get receipt items to update product quantities
    $stmt = $conn->prepare("SELECT product_id, quantity FROM receipt_items WHERE receipt_id = ?");
    $stmt->execute([$receipt_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update product quantities based on receipt type
    foreach ($items as $item) {
        if ($receipt['type'] === 'sale') {
            // For sales, reduce product quantity
            $update_stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
            $update_stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
        } else {
            // For purchases, increase product quantity
            $update_stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $update_stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        if ($update_stmt->rowCount() === 0) {
            throw new Exception("Insufficient quantity for product ID: " . $item['product_id']);
        }
    }

    // Update receipt status to finalized
    $stmt = $conn->prepare("UPDATE receipts SET status = 'finalized', finalized_at = NOW() WHERE id = ?");
    $stmt->execute([$receipt_id]);
    $affected_rows = $stmt->rowCount();

    if ($affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Draft receipt finalized successfully']);
    } else {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to finalize draft receipt']);
    }

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error finalizing draft receipt: ' . $e->getMessage()]);
}
?> 