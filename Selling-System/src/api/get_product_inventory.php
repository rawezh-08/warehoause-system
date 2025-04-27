<?php
// Include database connection
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set content type
header('Content-Type: application/json');

// Get product ID from request
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Validate product ID
if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ناسنامەی کاڵا دروست نییە'
    ]);
    exit;
}

try {
    // Get product details
    $stmt = $conn->prepare("
        SELECT p.*, u.name as unit_name
        FROM products p
        LEFT JOIN units u ON p.unit_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'کاڵاکە نەدۆزرایەوە'
        ]);
        exit;
    }
    
    // Get inventory quantity by unit type
    $inventory = [
        'piece_quantity' => 0,
        'box_quantity' => 0,
        'set_quantity' => 0
    ];
    
    // Calculate actual inventory based on purchase_items, sale_items, and wasting_items
    $stmt = $conn->prepare("
        WITH inventory_movements AS (
            -- Purchases (increase inventory)
            SELECT 
                pi.unit_type,
                pi.quantity,
                'purchase' as movement_type
            FROM purchase_items pi
            JOIN purchases p ON pi.purchase_id = p.id
            WHERE pi.product_id = ? AND p.status = 'completed'
            
            UNION ALL
            
            -- Sales (decrease inventory)
            SELECT 
                si.unit_type,
                -1 * (si.quantity - COALESCE(si.returned_quantity, 0)) as quantity,
                'sale' as movement_type
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE si.product_id = ? AND s.status = 'completed'
            
            UNION ALL
            
            -- Wastage (decrease inventory)
            SELECT 
                wi.unit_type,
                -1 * wi.quantity as quantity,
                'wastage' as movement_type
            FROM wasting_items wi
            JOIN wastings w ON wi.wasting_id = w.id
            WHERE wi.product_id = ?
            
            UNION ALL
            
            -- Returns (increase inventory)
            SELECT 
                ri.unit_type,
                ri.quantity,
                'return' as movement_type
            FROM return_items ri
            JOIN product_returns pr ON ri.return_id = pr.id
            WHERE ri.product_id = ? AND pr.status = 'completed'
        )
        SELECT 
            COALESCE(SUM(CASE WHEN unit_type = 'piece' THEN quantity ELSE 0 END), 0) as piece_qty,
            COALESCE(SUM(CASE WHEN unit_type = 'box' THEN quantity ELSE 0 END), 0) as box_qty,
            COALESCE(SUM(CASE WHEN unit_type = 'set' THEN quantity ELSE 0 END), 0) as set_qty
        FROM inventory_movements
    ");
    
    $stmt->execute([$product_id, $product_id, $product_id, $product_id]);
    $inventory_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($inventory_result) {
        $inventory['piece_quantity'] = max(0, intval($inventory_result['piece_qty'] ?? 0));
        $inventory['box_quantity'] = max(0, intval($inventory_result['box_qty'] ?? 0));
        $inventory['set_quantity'] = max(0, intval($inventory_result['set_qty'] ?? 0));
    }
    
    // Debug information
    error_log("Product ID: " . $product_id);
    error_log("Inventory Result: " . print_r($inventory_result, true));
    error_log("Final Inventory: " . print_r($inventory, true));
    
    // Respond with product and inventory info
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'unit_name' => $product['unit_name'],
            'pieces_per_box' => $product['pieces_per_box'],
            'boxes_per_set' => $product['boxes_per_set'],
            'piece_quantity' => $inventory['piece_quantity'],
            'box_quantity' => $inventory['box_quantity'],
            'set_quantity' => $inventory['set_quantity']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_product_inventory.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
}
?> 