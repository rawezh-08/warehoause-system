<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('پێویستە سەرەتا بچیتە ژوورەوە');
    }
    
    // Check if wasting_id is provided
    if (!isset($_POST['wasting_id'])) {
        throw new Exception('ID ی بەفیڕۆچوو پێویستە');
    }

    $wasting_id = intval($_POST['wasting_id']);
    
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // First, get wasting items to update inventory
    $stmt = $conn->prepare("
        SELECT wi.product_id, wi.pieces_count
        FROM wasting_items wi
        WHERE wi.wasting_id = ?
    ");
    $stmt->execute([$wasting_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return products to inventory
    foreach ($items as $item) {
        $stmt = $conn->prepare("
            UPDATE products
            SET current_quantity = current_quantity + :pieces_count
            WHERE id = :product_id
        ");
        $stmt->execute([
            ':pieces_count' => $item['pieces_count'],
            ':product_id' => $item['product_id']
        ]);
    }
    
    // Delete wasting items
    $stmt = $conn->prepare("DELETE FROM wasting_items WHERE wasting_id = ?");
    $stmt->execute([$wasting_id]);
    
    // Delete wasting record
    $stmt = $conn->prepare("DELETE FROM wastings WHERE id = ?");
    $stmt->execute([$wasting_id]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'بەفیڕۆچوو بە سەرکەوتوویی سڕایەوە'
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 