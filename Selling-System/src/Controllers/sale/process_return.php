<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if sale_id is provided
if (!isset($_POST['sale_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

$saleId = $_POST['sale_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();

    // Get sale items
    $query = "SELECT * FROM sale_items WHERE sale_id = :sale_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    $saleItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update product quantities
    foreach ($saleItems as $item) {
        // Add quantity back to product stock
        $updateQuery = "UPDATE products 
                       SET quantity = quantity + :quantity 
                       WHERE id = :product_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':quantity', $item['quantity']);
        $updateStmt->bindParam(':product_id', $item['product_id']);
        $updateStmt->execute();
    }

    // Mark sale as returned
    $updateSaleQuery = "UPDATE sales 
                       SET status = 'returned', 
                           return_date = CURRENT_TIMESTAMP 
                       WHERE id = :sale_id";
    $updateSaleStmt = $conn->prepare($updateSaleQuery);
    $updateSaleStmt->bindParam(':sale_id', $saleId);
    $updateSaleStmt->execute();

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Items returned successfully'
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 