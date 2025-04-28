<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_draft_errors.log');

// Log the start of the script
error_log("Starting delete_draft.php script");

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
    $stmt = $conn->prepare("SELECT id FROM sales WHERE id = :receipt_id AND is_draft = 1");
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
    
    // Delete sale items first (foreign key constraint)
    $deleteItemsStmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = :receipt_id");
    $deleteItemsStmt->bindParam(':receipt_id', $receiptId);
    $deleteItemsStmt->execute();
    
    // Delete the sale record
    $deleteSaleStmt = $conn->prepare("DELETE FROM sales WHERE id = :receipt_id AND is_draft = 1");
    $deleteSaleStmt->bindParam(':receipt_id', $receiptId);
    $result = $deleteSaleStmt->execute();
    
    if ($result) {
        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Draft receipt has been deleted successfully.'
        ]);
    } else {
        // Rollback on failure
        $conn->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete draft receipt.'
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