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
    // Get product details and current inventory directly from products table
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
    
    // Get inventory quantities by unit type from sales, purchases and inventory
    // First, get available quantity in piece, box, and set
    $result = [
        'piece_quantity' => 0,
        'box_quantity' => 0,
        'set_quantity' => 0
    ];
    
    // Try simpler approach - get current_quantity directly from products table
    $current_qty = intval($product['current_quantity'] ?? 0);
    $pieces_per_box = intval($product['pieces_per_box'] ?? 0) ?: 1; // Default to 1 if not set
    $boxes_per_set = intval($product['boxes_per_set'] ?? 0) ?: 1;   // Default to 1 if not set
    
    // First, try to get actual inventory from the tables
    // If all is 0, try using the inventory table as a fallback
    $stmt = $conn->prepare("
        SELECT SUM(quantity) as total_qty 
        FROM inventory 
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id]);
    $inventory_qty = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_pieces = intval($inventory_qty['total_qty'] ?? 0);
    
    // If still 0, use the current_quantity field
    if ($total_pieces <= 0 && $current_qty > 0) {
        $total_pieces = $current_qty;
    }

    // If still 0, try to calculate from purchases, sales, wastings and returns
    if ($total_pieces <= 0) {
        // Get purchased quantities (purchase_items table)
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN pi.unit_type = 'piece' THEN pi.quantity - pi.returned_quantity ELSE 0 END) as piece_qty,
                SUM(CASE WHEN pi.unit_type = 'box' THEN (pi.quantity - pi.returned_quantity) * ? ELSE 0 END) as box_pieces,
                SUM(CASE WHEN pi.unit_type = 'set' THEN (pi.quantity - pi.returned_quantity) * ? * ? ELSE 0 END) as set_pieces
            FROM purchase_items pi
            JOIN purchases p ON pi.purchase_id = p.id
            WHERE pi.product_id = ?
        ");
        $stmt->execute([$pieces_per_box, $boxes_per_set, $pieces_per_box, $product_id]);
        $purchase_quantities = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get sold quantities (sale_items table)
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN si.unit_type = 'piece' THEN si.quantity - si.returned_quantity ELSE 0 END) as piece_qty,
                SUM(CASE WHEN si.unit_type = 'box' THEN (si.quantity - si.returned_quantity) * ? ELSE 0 END) as box_pieces,
                SUM(CASE WHEN si.unit_type = 'set' THEN (si.quantity - si.returned_quantity) * ? * ? ELSE 0 END) as set_pieces
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE si.product_id = ?
        ");
        $stmt->execute([$pieces_per_box, $boxes_per_set, $pieces_per_box, $product_id]);
        $sale_quantities = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get wastage quantities (wasting_items table)
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN wi.unit_type = 'piece' THEN wi.quantity ELSE 0 END) as piece_qty,
                SUM(CASE WHEN wi.unit_type = 'box' THEN wi.quantity * ? ELSE 0 END) as box_pieces,
                SUM(CASE WHEN wi.unit_type = 'set' THEN wi.quantity * ? * ? ELSE 0 END) as set_pieces
            FROM wasting_items wi
            JOIN wastings w ON wi.wasting_id = w.id
            WHERE wi.product_id = ?
        ");
        $stmt->execute([$pieces_per_box, $boxes_per_set, $pieces_per_box, $product_id]);
        $wastage_quantities = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get product returns that increase inventory
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN ri.unit_type = 'piece' THEN ri.quantity ELSE 0 END) as piece_qty,
                SUM(CASE WHEN ri.unit_type = 'box' THEN ri.quantity * ? ELSE 0 END) as box_pieces,
                SUM(CASE WHEN ri.unit_type = 'set' THEN ri.quantity * ? * ? ELSE 0 END) as set_pieces
            FROM return_items ri
            JOIN product_returns pr ON ri.return_id = pr.id
            WHERE ri.product_id = ?
        ");
        $stmt->execute([$pieces_per_box, $boxes_per_set, $pieces_per_box, $product_id]);
        $returned_quantities = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate total pieces
        $purchased_pieces = intval($purchase_quantities['piece_qty'] ?? 0) + 
                           intval($purchase_quantities['box_pieces'] ?? 0) + 
                           intval($purchase_quantities['set_pieces'] ?? 0);
                           
        $sold_pieces = intval($sale_quantities['piece_qty'] ?? 0) + 
                      intval($sale_quantities['box_pieces'] ?? 0) + 
                      intval($sale_quantities['set_pieces'] ?? 0);
                      
        $wasted_pieces = intval($wastage_quantities['piece_qty'] ?? 0) + 
                        intval($wastage_quantities['box_pieces'] ?? 0) + 
                        intval($wastage_quantities['set_pieces'] ?? 0);
                        
        $returned_pieces = intval($returned_quantities['piece_qty'] ?? 0) + 
                          intval($returned_quantities['box_pieces'] ?? 0) + 
                          intval($returned_quantities['set_pieces'] ?? 0);
        
        $total_pieces = max(0, $purchased_pieces - $sold_pieces - $wasted_pieces + $returned_pieces);
    }
    
    // Now that we have total pieces, distribute them to units (piece, box, set)
    // First distribute to sets
    if ($pieces_per_box > 0 && $boxes_per_set > 0) {
        $pieces_per_set = $pieces_per_box * $boxes_per_set;
        $result['set_quantity'] = floor($total_pieces / $pieces_per_set);
        $remaining_pieces = $total_pieces % $pieces_per_set;
        
        // Then distribute to boxes
        if ($pieces_per_box > 0) {
            $result['box_quantity'] = floor($remaining_pieces / $pieces_per_box);
            $remaining_pieces = $remaining_pieces % $pieces_per_box;
        }
        
        // Remaining are individual pieces
        $result['piece_quantity'] = $remaining_pieces;
    } else if ($pieces_per_box > 0) {
        // No sets, just boxes and pieces
        $result['box_quantity'] = floor($total_pieces / $pieces_per_box);
        $result['piece_quantity'] = $total_pieces % $pieces_per_box;
    } else {
        // Only pieces
        $result['piece_quantity'] = $total_pieces;
    }

    // Debug information
    error_log("Product ID: " . $product_id);
    error_log("Product details: " . print_r($product, true));
    error_log("Total pieces: " . $total_pieces);
    error_log("pieces_per_box: " . $pieces_per_box);
    error_log("boxes_per_set: " . $boxes_per_set);
    error_log("Final result: " . print_r($result, true));
    
    // Respond with product and inventory info
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'unit_name' => $product['unit_name'],
            'pieces_per_box' => $pieces_per_box,
            'boxes_per_set' => $boxes_per_set,
            'piece_quantity' => $result['piece_quantity'],
            'box_quantity' => $result['box_quantity'],
            'set_quantity' => $result['set_quantity'],
            'total_pieces' => $total_pieces
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