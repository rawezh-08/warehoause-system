<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['sale_id'])) {
        throw new Exception('Sale ID is required');
    }

    $sale_id = $_POST['sale_id'];
    
    // Log the request for debugging
    error_log("get_sale_items.php called with sale_id: " . $sale_id);

    // Check if database connection is valid
    if (!$conn) {
        throw new Exception('Database connection error');
    }

    // Get sale items with product information
    $stmt = $conn->prepare("
        SELECT 
            si.*,
            p.name as product_name,
            p.current_quantity as available_quantity,
            p.code as product_code
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = :sale_id
    ");

    $stmt->execute([':sale_id' => $sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log result
    error_log("get_sale_items.php found " . count($items) . " items for sale_id: " . $sale_id);

    // Process items to ensure correct format for returned_quantity
    foreach ($items as &$item) {
        // Ensure returned_quantity is set
        if (!isset($item['returned_quantity'])) {
            $item['returned_quantity'] = 0;
        }
        
        // Convert to float/number
        $item['quantity'] = floatval($item['quantity']);
        $item['returned_quantity'] = floatval($item['returned_quantity']);
        $item['unit_price'] = floatval($item['unit_price']);
        
        // Calculate remaining
        $item['remaining_quantity'] = $item['quantity'] - $item['returned_quantity'];
        
        // Ensure unit_type is set
        if (!isset($item['unit_type']) || empty($item['unit_type'])) {
            $item['unit_type'] = 'piece';
        }
        
        error_log("Item: " . $item['product_name'] . ", Quantity: " . $item['quantity'] . 
                 ", Returned: " . $item['returned_quantity'] . ", Remaining: " . $item['remaining_quantity']);
    }
    unset($item); // Release reference

    echo json_encode([
        'status' => 'success',
        'items' => $items,
        'count' => count($items)
    ]);

} catch (Exception $e) {
    error_log("Error in get_sale_items.php: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} 