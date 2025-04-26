<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Get wasting ID from POST data
    $wasting_id = isset($_POST['wasting_id']) ? intval($_POST['wasting_id']) : 0;
    
    if ($wasting_id <= 0) {
        throw new Exception('IDی بەفیڕۆچوو نادروستە');
    }
    
    // Get wasting header information
    $header_stmt = $conn->prepare("
        SELECT w.*, 
               GROUP_CONCAT(
                   CONCAT(p.name, ' (', wi.quantity, ' ', 
                   CASE wi.unit_type 
                       WHEN 'piece' THEN 'دانە'
                       WHEN 'box' THEN 'کارتۆن'
                       WHEN 'set' THEN 'سێت'
                   END, ')')
               SEPARATOR ', ') as products_list,
               SUM(wi.total_price) as total_amount
        FROM wastings w
        LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
        LEFT JOIN products p ON wi.product_id = p.id
        WHERE w.id = ?
        GROUP BY w.id
    ");
    
    $header_stmt->execute([$wasting_id]);
    $header = $header_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$header) {
        throw new Exception('بەفیڕۆچووەکە نەدۆزرایەوە');
    }
    
    // Get wasting items
    $items_stmt = $conn->prepare("
        SELECT wi.*, 
               p.name as product_name,
               p.code as product_code,
               p.image as product_image
        FROM wasting_items wi
        LEFT JOIN products p ON wi.product_id = p.id
        WHERE wi.wasting_id = ?
    ");
    
    $items_stmt->execute([$wasting_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'status' => 'success',
        'wasting' => [
            'header' => $header,
            'items' => $items
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 