<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$saleId = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
$reason = isset($_POST['reason']) ? $_POST['reason'] : '';
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Validate input
if ($saleId <= 0 || $productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Get sale details
    $saleQuery = "SELECT * FROM sales WHERE id = :sale_id";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':sale_id', $saleId);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        throw new Exception('Sale not found');
    }
    
    // Get sale item details
    $itemQuery = "SELECT * FROM sale_items WHERE sale_id = :sale_id AND product_id = :product_id";
    $itemStmt = $conn->prepare($itemQuery);
    $itemStmt->bindParam(':sale_id', $saleId);
    $itemStmt->bindParam(':product_id', $productId);
    $itemStmt->execute();
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Sale item not found');
    }
    
    // Check if return quantity is valid
    if ($quantity > ($item['quantity'] - $item['returned_quantity'])) {
        throw new Exception('Return quantity exceeds available quantity');
    }
    
    // Calculate total amount
    $totalAmount = $item['unit_price'] * $quantity;
    
    // Create product return record
    $returnQuery = "INSERT INTO product_returns (receipt_id, receipt_type, return_date, total_amount, reason, notes) 
                   VALUES (:receipt_id, 'selling', NOW(), :total_amount, :reason, :notes)";
    $returnStmt = $conn->prepare($returnQuery);
    $returnStmt->bindParam(':receipt_id', $saleId);
    $returnStmt->bindParam(':total_amount', $totalAmount);
    $returnStmt->bindParam(':reason', $reason);
    $returnStmt->bindParam(':notes', $notes);
    $returnStmt->execute();
    $returnId = $conn->lastInsertId();
    
    // Create return items record
    $returnItemQuery = "INSERT INTO return_items (return_id, product_id, quantity, unit_price, unit_type, 
                      original_unit_type, original_quantity, reason, notes, total_price) 
                      VALUES (:return_id, :product_id, :quantity, :unit_price, :unit_type, 
                      :original_unit_type, :original_quantity, :reason, :notes, :total_price)";
    $returnItemStmt = $conn->prepare($returnItemQuery);
    $returnItemStmt->bindParam(':return_id', $returnId);
    $returnItemStmt->bindParam(':product_id', $productId);
    $returnItemStmt->bindParam(':quantity', $quantity);
    $returnItemStmt->bindParam(':unit_price', $item['unit_price']);
    $returnItemStmt->bindParam(':unit_type', $item['unit_type']);
    $returnItemStmt->bindParam(':original_unit_type', $item['unit_type']);
    $returnItemStmt->bindParam(':original_quantity', $item['quantity']);
    $returnItemStmt->bindParam(':reason', $reason);
    $returnItemStmt->bindParam(':notes', $notes);
    $returnItemStmt->bindParam(':total_price', $totalAmount);
    $returnItemStmt->execute();
    
    // Update sale item returned quantity
    $updateQuery = "UPDATE sale_items SET returned_quantity = returned_quantity + :quantity 
                   WHERE sale_id = :sale_id AND product_id = :product_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':quantity', $quantity);
    $updateStmt->bindParam(':sale_id', $saleId);
    $updateStmt->bindParam(':product_id', $productId);
    $updateStmt->execute();
    
    // Update inventory
    $inventoryQuery = "UPDATE inventory SET quantity = quantity + :quantity 
                      WHERE product_id = :product_id";
    $inventoryStmt = $conn->prepare($inventoryQuery);
    $inventoryStmt->bindParam(':quantity', $quantity);
    $inventoryStmt->bindParam(':product_id', $productId);
    $inventoryStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Product returned successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 