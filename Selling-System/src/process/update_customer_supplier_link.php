<?php
// Include database connection
require_once '../config/database.php';

// Set response content type
header('Content-Type: application/json');

try {
    // Check if required fields are provided
    if (!isset($_POST['id']) || !isset($_POST['supplier_id'])) {
        throw new Exception('زانیاری پێویست دابین نەکراوە');
    }
    
    // Sanitize input
    $customerId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $supplierId = filter_var($_POST['supplier_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Update customer record to link with supplier
    $stmt = $conn->prepare("
        UPDATE customers 
        SET supplier_id = :supplier_id,
            is_business_partner = 1
        WHERE id = :customer_id
    ");
    
    $stmt->bindParam(':supplier_id', $supplierId, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
    
    // Execute query
    if (!$stmt->execute()) {
        throw new Exception('هەڵە لە نوێکردنەوەی پەیوەندی کڕیار-دابینکەر');
    }
    
    // Check if any row was affected
    if ($stmt->rowCount() === 0) {
        throw new Exception('کڕیار نەدۆزرایەوە یان نوێکردنەوە پێویست نەبوو');
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'پەیوەندی کڕیار-دابینکەر بە سەرکەوتوویی نوێ کرایەوە'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error
    error_log("Error updating customer-supplier link: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 