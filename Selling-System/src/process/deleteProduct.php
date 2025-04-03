<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // First, delete any related records (if any exist)
        // For example, if you have sales records or other related data
        // Add those DELETE queries here
        
        // Delete the product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        // Commit transaction
        $conn->commit();
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
        }
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 