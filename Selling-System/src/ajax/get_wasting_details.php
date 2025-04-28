<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Check if wasting_id is provided
    if (!isset($_POST['wasting_id'])) {
        throw new Exception('ID ی بەفیڕۆچوو پێویستە');
    }

    $wasting_id = intval($_POST['wasting_id']);
    
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get wasting details
    $stmt = $conn->prepare("
        SELECT w.*
        FROM wastings w
        WHERE w.id = ?
    ");
    
    $stmt->execute([$wasting_id]);
    $wasting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wasting) {
        throw new Exception('بەفیڕۆچووەکە نەدۆزرایەوە');
    }
    
    // Get wasting items
    $stmt = $conn->prepare("
        SELECT wi.*, p.name as product_name
        FROM wasting_items wi
        JOIN products p ON wi.product_id = p.id
        WHERE wi.wasting_id = ?
    ");
    
    $stmt->execute([$wasting_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total amount
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += floatval($item['total_price']);
    }
    
    // Add items and total to wasting response
    $wasting['items'] = $items;
    $wasting['total_amount'] = $total_amount;
    
    echo json_encode([
        'status' => 'success',
        'wasting' => $wasting
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 