<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['purchase_id'])) {
        throw new Exception('Purchase ID is required');
    }

    $purchase_id = $_POST['purchase_id'];
    
    // Log the request for debugging
    error_log("get_purchase_items.php called with purchase_id: " . $purchase_id);

    // Check if database connection is valid
    if (!$conn) {
        error_log("Database connection failed");
        throw new Exception('Database connection error');
    }
    error_log("Database connection successful");

    // Get purchase items with product information
    $stmt = $conn->prepare("
        SELECT 
            pi.*,
            p.name as product_name,
            p.current_quantity as available_quantity,
            p.code as product_code
        FROM purchase_items pi
        JOIN products p ON pi.product_id = p.id
        WHERE pi.purchase_id = :purchase_id
    ");

    // Log the SQL query for debugging
    error_log("Executing SQL query with purchase_id: " . $purchase_id);
    
    $stmt->execute([':purchase_id' => $purchase_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log result
    error_log("get_purchase_items.php found " . count($items) . " items for purchase_id: " . $purchase_id);
    if (empty($items)) {
        error_log("No items found in purchase_items table for purchase_id: " . $purchase_id);
        // Let's check if the purchase exists
        $checkStmt = $conn->prepare("SELECT id FROM purchases WHERE id = :purchase_id");
        $checkStmt->execute([':purchase_id' => $purchase_id]);
        if (!$checkStmt->fetch()) {
            error_log("Purchase with ID " . $purchase_id . " does not exist in purchases table");
        }
    }

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
    error_log("Error in get_purchase_items.php: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} 