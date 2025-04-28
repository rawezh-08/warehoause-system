<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }

    // Get receipt number from POST data
    $receiptNumber = $_POST['receipt_number'] ?? null;
    
    if (!$receiptNumber) {
        throw new Exception('ژمارەی پسووڵە پێویستە');
    }

    // Check if receipt number exists in sales table
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sales WHERE invoice_number = ?");
    $stmt->execute([$receiptNumber]);
    $salesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Check if receipt number exists in purchases table
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchases WHERE invoice_number = ?");
    $stmt->execute([$receiptNumber]);
    $purchasesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Check if receipt number exists in wastings table
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wastings WHERE invoice_number = ?");
    $stmt->execute([$receiptNumber]);
    $wastingsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Return response
    echo json_encode([
        'success' => true,
        'exists' => ($salesCount > 0 || $purchasesCount > 0 || $wastingsCount > 0)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 