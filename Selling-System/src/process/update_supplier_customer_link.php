<?php
// Include database connection
require_once '../config/database.php';

// Set response content type
header('Content-Type: application/json');

try {
    // Check if required fields are provided
    if (!isset($_POST['id']) || !isset($_POST['customer_id'])) {
        throw new Exception('زانیاری پێویست دابین نەکراوە');
    }
    
    // Sanitize input
    $supplierId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $customerId = filter_var($_POST['customer_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Update supplier record to link with customer
    $stmt = $conn->prepare("
        UPDATE suppliers 
        SET customer_id = :customer_id,
            is_business_partner = 1
        WHERE id = :supplier_id
    ");
    
    $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
    $stmt->bindParam(':supplier_id', $supplierId, PDO::PARAM_INT);
    
    // Execute query
    if (!$stmt->execute()) {
        throw new Exception('هەڵە لە نوێکردنەوەی پەیوەندی دابینکەر-کڕیار');
    }
    
    // Check if any row was affected
    if ($stmt->rowCount() === 0) {
        throw new Exception('دابینکەر نەدۆزرایەوە یان نوێکردنەوە پێویست نەبوو');
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'پەیوەندی دابینکەر-کڕیار بە سەرکەوتوویی نوێ کرایەوە'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error
    error_log("Error updating supplier-customer link: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 