<?php


require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    try {
        // First check if this product is used in purchases or sales
        $checkPurchases = $conn->prepare("SELECT COUNT(*) FROM purchase_items WHERE product_id = ?");
        $checkPurchases->execute([$id]);
        $purchaseCount = $checkPurchases->fetchColumn();
        
        $checkSales = $conn->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
        $checkSales->execute([$id]);
        $saleCount = $checkSales->fetchColumn();
        
        $checkInventory = $conn->prepare("SELECT COUNT(*) FROM inventory WHERE product_id = ?");
        $checkInventory->execute([$id]);
        $inventoryCount = $checkInventory->fetchColumn();
        
        // If product is used in other tables, return error
        if ($purchaseCount > 0 || $saleCount > 0 || $inventoryCount > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'ناتوانرێت ئەم کاڵایە بسڕدرێتەوە چونکە بەکارهاتووە لە پسووڵەی کڕین، فرۆشتن یان ئینڤێنتۆری. تکایە یەکەم جار پسووڵەکان بسڕەوە پێش سڕینەوەی کاڵاکە.'
            ]);
            exit;
        }
        
        // If product is not used, delete it
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'کاڵاکە نەدۆزرایەوە']);
        }
    } catch (PDOException $e) {
        // Log the full error for debugging
        error_log("Product deletion error: " . $e->getMessage());
        
        // Return more specific error message
        if ($e->getCode() == '23000') { // Integrity constraint violation
            echo json_encode([
                'success' => false, 
                'message' => 'ناتوانرێت کاڵاکە بسڕدرێتەوە چونکە بەکارهاتووە لە پسووڵەی کڕین، فرۆشتن یان کارەکانی تر'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'کێشەیەک ڕوویدا لە سڕینەوەی کاڵاکە: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'داوایەکی نادروست']);
} 