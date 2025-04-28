<?php
// Include database connection
require_once '../../config/database.php';

// Set response headers
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
    exit;
}

// Check if receipt_id is provided
if (!isset($_POST['receipt_id']) || empty($_POST['receipt_id'])) {
    echo json_encode([
        'success' => false,
        'exists' => false,
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
    
    // Check if the draft receipt exists
    $stmt = $conn->prepare("SELECT id FROM sales WHERE id = :receipt_id AND is_draft = 1");
    $stmt->bindParam(':receipt_id', $receiptId);
    $stmt->execute();
    
    $exists = $stmt->rowCount() > 0;
    
    // Return response
    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'message' => $exists ? 'Draft receipt exists.' : 'Draft receipt not found.'
    ]);
    
} catch (PDOException $e) {
    // Handle database errors
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?> 