<?php
// Include database connection
require_once '../../config/database.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if wasting_id is provided
if (!isset($_POST['wasting_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID ی بەفیڕۆچوو پێویستە'
    ]);
    exit;
}

$wasting_id = $_POST['wasting_id'];

try {
    // Get wasting details with proper total calculation
    $stmt = $conn->prepare("
        SELECT 
            w.*,
            COALESCE(SUM(wi.total_price), 0) as total_amount,
            GROUP_CONCAT(
                CONCAT(
                    p.name, '|',
                    wi.quantity, '|',
                    wi.unit_type, '|',
                    wi.unit_price, '|',
                    wi.total_price
                )
                SEPARATOR '||'
            ) as items_data
        FROM wastings w
        LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
        LEFT JOIN products p ON wi.product_id = p.id
        WHERE w.id = ?
        GROUP BY w.id
    ");
    
    $stmt->execute([$wasting_id]);
    $wasting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wasting) {
        echo json_encode([
            'status' => 'error',
            'message' => 'بەفیڕۆچووەکە نەدۆزرایەوە'
        ]);
        exit;
    }

    // Parse items data
    $items = [];
    if ($wasting['items_data']) {
        $items_array = explode('||', $wasting['items_data']);
        foreach ($items_array as $item) {
            list($name, $quantity, $unit_type, $unit_price, $total_price) = explode('|', $item);
            $items[] = [
                'product_name' => $name,
                'quantity' => floatval($quantity),
                'unit_type' => $unit_type,
                'unit_price' => floatval($unit_price),
                'total_price' => floatval($total_price)
            ];
        }
    }

    // Remove items_data from wasting array
    unset($wasting['items_data']);
    
    // Add items to wasting array
    $wasting['items'] = $items;
    
    // Ensure total_amount is a number
    $wasting['total_amount'] = floatval($wasting['total_amount']);

    echo json_encode([
        'status' => 'success',
        'wasting' => $wasting
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
    ]);
} 