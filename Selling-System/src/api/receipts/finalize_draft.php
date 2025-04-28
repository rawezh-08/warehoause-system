<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/finalize_draft_errors.log');

// Log the start of the script
error_log("Starting finalize_draft.php script");

// Include database connection
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
    exit;
}

// Check if receipt_id is provided
if (!isset($_POST['receipt_id']) || empty($_POST['receipt_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Receipt ID is required.'
    ]);
    exit;
}

// Sanitize input
$receiptId = intval($_POST['receipt_id']);

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Check if the draft receipt exists
    $stmt = $conn->prepare("SELECT id, customer_id, payment_type FROM sales WHERE id = :receipt_id AND is_draft = 1");
    $stmt->bindParam(':receipt_id', $receiptId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Draft doesn't exist
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Draft receipt not found.'
        ]);
        exit;
    }
    
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update the draft status
    $updateStmt = $conn->prepare("UPDATE sales SET is_draft = 0 WHERE id = :receipt_id");
    $updateStmt->bindParam(':receipt_id', $receiptId);
    $updateResult = $updateStmt->execute();
    
    if ($updateResult) {
        // If it's a credit sale, we need to update customer debt
        if ($sale['payment_type'] == 'credit') {
            // Calculate total sale amount
            $totalStmt = $conn->prepare("
                SELECT SUM(si.total_price) as total_price,
                       s.shipping_cost, s.other_costs, s.discount
                FROM sales s
                LEFT JOIN sale_items si ON s.id = si.sale_id
                WHERE s.id = :receipt_id
                GROUP BY s.id
            ");
            $totalStmt->bindParam(':receipt_id', $receiptId);
            $totalStmt->execute();
            $totalData = $totalStmt->fetch(PDO::FETCH_ASSOC);
            
            $totalAmount = ($totalData['total_price'] ?? 0) + 
                          ($totalData['shipping_cost'] ?? 0) + 
                          ($totalData['other_costs'] ?? 0) - 
                          ($totalData['discount'] ?? 0);
            
            // Add to customer debt
            $debtStmt = $conn->prepare("
                INSERT INTO debt_transactions 
                (customer_id, amount, transaction_type, reference_id, notes, created_by)
                VALUES (:customer_id, :amount, 'sale', :receipt_id, 'Finalized from draft', 1)
            ");
            $debtStmt->bindParam(':customer_id', $sale['customer_id']);
            $debtStmt->bindParam(':amount', $totalAmount);
            $debtStmt->bindParam(':receipt_id', $receiptId);
            $debtStmt->execute();
            
            // Update customer's debt in the customers table
            $updateCustomerStmt = $conn->prepare("
                UPDATE customers
                SET debit_on_business = debit_on_business + :amount
                WHERE id = :customer_id
            ");
            $updateCustomerStmt->bindParam(':amount', $totalAmount);
            $updateCustomerStmt->bindParam(':customer_id', $sale['customer_id']);
            $updateCustomerStmt->execute();
        }
        
        // Update inventory (deduct products from stock)
        $updateInventoryStmt = $conn->prepare("
            UPDATE products p
            JOIN sale_items si ON p.id = si.product_id
            SET p.current_quantity = p.current_quantity - si.pieces_count
            WHERE si.sale_id = :receipt_id
        ");
        $updateInventoryStmt->bindParam(':receipt_id', $receiptId);
        $updateInventoryStmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Draft receipt has been finalized successfully.'
        ]);
    } else {
        // Rollback on failure
        $conn->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to finalize draft receipt.'
        ]);
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Handle database errors
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?> 