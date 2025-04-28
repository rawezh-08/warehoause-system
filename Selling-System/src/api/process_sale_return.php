<?php
header('Content-Type: application/json');
require_once '../includes/auth.php'; 
require_once '../config/database.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get data from POST request
$sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
$items_to_return = isset($_POST['items']) ? $_POST['items'] : [];
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : 'other';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
$user_id = $_SESSION['user_id'] ?? null; // Get user ID from session

// --- Basic Validation ---
if ($sale_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ژمارەی پسووڵە نادروستە.']);
    exit;
}

if (empty($items_to_return)) {
    echo json_encode(['success' => false, 'message' => 'هیچ کاڵایەک بۆ گەڕاندنەوە دیاری نەکراوە.']);
    exit;
}

if ($user_id === null) {
     echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
     exit;
}

// Filter out items with 0 quantity
$items_to_return = array_filter($items_to_return, function($item) {
    return isset($item['quantity']) && intval($item['quantity']) > 0;
});

if (empty($items_to_return)) {
    echo json_encode(['success' => false, 'message' => 'بڕی گەڕاوە بۆ هەموو کاڵاکان سفرە.']);
    exit;
}

$total_return_amount = 0;
$return_details_for_db = [];

$conn->beginTransaction();

try {
    // 1. Fetch Sale and Customer Info
    $saleStmt = $conn->prepare("SELECT customer_id, date FROM sales WHERE id = :sale_id");
    $saleStmt->bindParam(':sale_id', $sale_id);
    $saleStmt->execute();
    $saleInfo = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$saleInfo) {
        throw new Exception('پسووڵەی فرۆشتن نەدۆزرایەوە.');
    }
    $customer_id = $saleInfo['customer_id'];

    // 2. Validate each item
    foreach ($items_to_return as $sale_item_id => $item) {
        $return_quantity = intval($item['quantity']);
        $return_unit_type = $item['unit_type'];
        $product_id = intval($item['product_id']);
        $unit_price = floatval($item['unit_price']); // Price at the time of sale
        $original_sale_quantity = intval($item['original_quantity']);
        $original_sale_unit_type = $item['original_unit_type'];

        if ($return_quantity <= 0) continue; // Should be filtered already, but double check

        // Fetch current sale item details and product details
        $itemStmt = $conn->prepare("
            SELECT 
                si.quantity as sold_quantity, 
                si.returned_quantity as currently_returned,
                p.pieces_per_box, 
                p.boxes_per_set,
                p.current_quantity as product_stock
            FROM sale_items si 
            JOIN products p ON si.product_id = p.id
            WHERE si.id = :sale_item_id AND si.sale_id = :sale_id AND si.product_id = :product_id
        ");
        $itemStmt->bindParam(':sale_item_id', $sale_item_id);
        $itemStmt->bindParam(':sale_id', $sale_id);
         $itemStmt->bindParam(':product_id', $product_id);
        $itemStmt->execute();
        $dbItemInfo = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$dbItemInfo) {
            throw new Exception("کاڵای ژمارە {$product_id} لە پسووڵەکەدا نەدۆزرایەوە.");
        }

        // Calculate return quantity in pieces
        $pieces_per_box = $dbItemInfo['pieces_per_box'] ?? 1;
        $boxes_per_set = $dbItemInfo['boxes_per_set'] ?? 1;
        $return_pieces = 0;
        $max_returnable_pieces = 0;

        if ($return_unit_type === 'piece') {
            $return_pieces = $return_quantity;
        } elseif ($return_unit_type === 'box') {
            if ($pieces_per_box <= 0) throw new Exception("Pieces per box not set correctly for product ID {$product_id}");
            $return_pieces = $return_quantity * $pieces_per_box;
        } elseif ($return_unit_type === 'set') {
            if ($pieces_per_box <= 0 || $boxes_per_set <= 0) throw new Exception("Unit configuration error for product ID {$product_id}");
            $return_pieces = $return_quantity * $pieces_per_box * $boxes_per_set;
        }
        
        // Calculate max returnable quantity in selected unit
        $available_to_return_pieces = $dbItemInfo['sold_quantity'] - $dbItemInfo['currently_returned'];
         if ($return_pieces > $available_to_return_pieces) {
            throw new Exception("بڕی گەڕاوە بۆ کاڵای '{$item['product_name']}' ({$return_pieces}) لە بڕی ماوە ({$available_to_return_pieces}) زیاترە.");
        }
        
        // Calculate amount for this item
        $item_return_amount = $return_quantity * $unit_price; // Based on returned unit quantity and price
        $total_return_amount += $item_return_amount;

        $return_details_for_db[] = [
            'sale_item_id' => $sale_item_id,
            'product_id' => $product_id,
            'return_quantity' => $return_quantity, // Quantity in the selected unit
            'return_unit_type' => $return_unit_type,
             'original_sale_quantity' => $original_sale_quantity,
            'original_sale_unit_type' => $original_sale_unit_type,
            'return_pieces' => $return_pieces, // Quantity in pieces
            'unit_price' => $unit_price, // Price per returned unit
            'total_price' => $item_return_amount
        ];
    }

    if (empty($return_details_for_db)) {
        throw new Exception("هیچ کاڵایەکی دروست بۆ گەڕاندنەوە نەدۆزرایەوە.");
    }

    // 3. Create main return record
    $insertReturnStmt = $conn->prepare("
        INSERT INTO product_returns (receipt_id, receipt_type, return_date, total_amount, reason, notes, created_at)
        VALUES (:receipt_id, :receipt_type, NOW(), :total_amount, :reason, :notes, NOW())
    ");
    $receipt_type = 'selling'; // Since it's a sale return
    $insertReturnStmt->bindParam(':receipt_id', $sale_id);
    $insertReturnStmt->bindParam(':receipt_type', $receipt_type);
    $insertReturnStmt->bindParam(':total_amount', $total_return_amount);
    $insertReturnStmt->bindParam(':reason', $reason);
    $insertReturnStmt->bindParam(':notes', $notes);
    $insertReturnStmt->execute();
    $return_id = $conn->lastInsertId();

    // 4. Process each item: update sale_items, products, inventory, return_items
    $insertReturnItemStmt = $conn->prepare("
        INSERT INTO return_items (return_id, product_id, quantity, unit_price, unit_type, original_quantity, original_unit_type, reason, notes, created_at, total_price)
        VALUES (:return_id, :product_id, :quantity, :unit_price, :unit_type, :original_quantity, :original_unit_type, :reason, :notes, NOW(), :total_price)
    ");
    $updateSaleItemStmt = $conn->prepare("
        UPDATE sale_items 
        SET returned_quantity = returned_quantity + :return_pieces 
        WHERE id = :sale_item_id
    ");
    $updateProductStmt = $conn->prepare("
        UPDATE products 
        SET current_quantity = current_quantity + :return_pieces 
        WHERE id = :product_id
    ");
    $insertInventoryStmt = $conn->prepare("
        INSERT INTO inventory (product_id, quantity, reference_type, reference_id, created_at, notes)
        VALUES (:product_id, :quantity, :reference_type, :reference_id, NOW(), :notes)
    ");

    foreach ($return_details_for_db as $detail) {
        // Insert into return_items
        $return_item_note = "گەڕاندنەوە: {$detail['return_quantity']} {$detail['return_unit_type']} (ئەسڵی: {$detail['original_sale_quantity']} {$detail['original_sale_unit_type']})";
        $insertReturnItemStmt->bindParam(':return_id', $return_id);
        $insertReturnItemStmt->bindParam(':product_id', $detail['product_id']);
        $insertReturnItemStmt->bindParam(':quantity', $detail['return_quantity']);
        $insertReturnItemStmt->bindParam(':unit_price', $detail['unit_price']);
        $insertReturnItemStmt->bindParam(':unit_type', $detail['return_unit_type']);
        $insertReturnItemStmt->bindParam(':original_quantity', $detail['original_sale_quantity']);
        $insertReturnItemStmt->bindParam(':original_unit_type', $detail['original_sale_unit_type']);
        $insertReturnItemStmt->bindParam(':reason', $reason);
        $insertReturnItemStmt->bindParam(':notes', $notes);
        $insertReturnItemStmt->bindParam(':total_price', $detail['total_price']);
        $insertReturnItemStmt->execute();

        // Update sale_items (use return_pieces)
        $updateSaleItemStmt->bindParam(':return_pieces', $detail['return_pieces']);
        $updateSaleItemStmt->bindParam(':sale_item_id', $detail['sale_item_id']);
        $updateSaleItemStmt->execute();

        // Update products stock (use return_pieces)
        $updateProductStmt->bindParam(':return_pieces', $detail['return_pieces']);
        $updateProductStmt->bindParam(':product_id', $detail['product_id']);
        $updateProductStmt->execute();

        // Insert into inventory (use return_pieces)
        $inv_ref_type = 'return';
        $inv_notes = "گەڕاندنەوەی کڕیار - پسووڵە: " . $sale_id . " - " . $notes;
        $insertInventoryStmt->bindParam(':product_id', $detail['product_id']);
        $insertInventoryStmt->bindParam(':quantity', $detail['return_pieces']); // Positive quantity for inventory increase
        $insertInventoryStmt->bindParam(':reference_type', $inv_ref_type);
        $insertInventoryStmt->bindParam(':reference_id', $return_id); // Link to the product_returns record
        $insertInventoryStmt->bindParam(':notes', $inv_notes);
        $insertInventoryStmt->execute();
    }

    // 5. Update Customer Debt using the stored procedure
    if ($total_return_amount > 0) {
         $debtProcStmt = $conn->prepare("CALL add_debt_transaction(:p_customer_id, :p_amount, :p_transaction_type, :p_reference_id, :p_notes, :p_created_by)");
         $transaction_type = 'collection'; // Use 'collection' to decrease customer debt
         $debt_notes = "گەڕاندنەوەی کاڵا - " . $notes; // Combine notes
         $debt_amount = $total_return_amount; // The amount to decrease debt by
         
         $debtProcStmt->bindParam(':p_customer_id', $customer_id, PDO::PARAM_INT);
         $debtProcStmt->bindParam(':p_amount', $debt_amount); // Amount is positive, procedure handles direction
         $debtProcStmt->bindParam(':p_transaction_type', $transaction_type);
         $debtProcStmt->bindParam(':p_reference_id', $return_id); // Reference the product_returns ID
         $debtProcStmt->bindParam(':p_notes', $debt_notes);
         $debtProcStmt->bindParam(':p_created_by', $user_id, PDO::PARAM_INT);
         $debtProcStmt->execute();
         $debtProcStmt->closeCursor(); // Important when calling procedures
    }
    
     // Optionally: Update sales record remaining amount (if needed)
     $updateSaleTotalStmt = $conn->prepare("UPDATE sales SET remaining_amount = remaining_amount - :returned_total WHERE id = :sale_id AND payment_type = 'credit'");
     $updateSaleTotalStmt->bindParam(':returned_total', $total_return_amount);
     $updateSaleTotalStmt->bindParam(':sale_id', $sale_id);
     $updateSaleTotalStmt->execute();

    // If all successful, commit
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'گەڕاندنەوەکە بە سەرکەوتوویی تۆمارکرا.']);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Database Error during return processing: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک لە داتابەیس ڕوویدا: ' . $e->getMessage()]);
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error during return processing: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?> 