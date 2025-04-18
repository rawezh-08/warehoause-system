<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !isset($_POST['type'])) {
        throw new Exception('داواکاری نادروستە');
    }

    $id = intval($_POST['id']);
    $type = $_POST['type'];

    if (!in_array($type, ['sale', 'purchase'])) {
        throw new Exception('جۆری پسووڵە نادروستە');
    }

    // First check if the receipt exists in the specified type's table
    // If not, check if it exists in the other table and suggest correction
    $actualType = null;
    
    // Check if exists in sales table
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    $salesExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    // Check if exists in purchases table
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchases WHERE id = ?");
    $stmt->execute([$id]);
    $purchasesExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    // Determine the actual type of receipt
    if ($salesExists) {
        $actualType = 'sale';
    } elseif ($purchasesExists) {
        $actualType = 'purchase';
    } else {
        throw new Exception('پسووڵەکە لە هیچ خشتەیەکدا نەدۆزرایەوە - ID: ' . $id);
    }
    
    // If there's a mismatch between specified type and actual type
    if ($actualType !== $type) {
        throw new Exception('جۆری پسووڵە هەڵەیە. ئەم پسووڵەیە لە خشتەی ' . 
            ($actualType === 'sale' ? 'فرۆشتن' : 'کڕین') . ' دایە، نەك ' . 
            ($type === 'sale' ? 'فرۆشتن' : 'کڕین') . '. تکایە جۆرەکەی ڕاست بکەوە.');
    }

    // Start transaction
    $conn->beginTransaction();

    if ($type === 'sale') {
        try {
            // First check if it's a draft
            $stmt = $conn->prepare("SELECT is_draft FROM sales WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception('پسووڵەکە نەدۆزرایەوە');
            }
            
            $isDraft = $result['is_draft'] ?? 0;

            if ($isDraft) {
                // For drafts, we can simply delete without additional checks
                $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
                $stmt->execute([$id]);

                $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                // Check if sale has any returns
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as return_count 
                    FROM product_returns 
                    WHERE receipt_id = ? AND receipt_type = 'selling'
                ");
                $stmt->execute([$id]);
                $hasReturns = $stmt->fetch(PDO::FETCH_ASSOC)['return_count'] > 0;

                // Check if sale has any payments (for credit sales)
                $stmt = $conn->prepare("
                    SELECT s.payment_type, s.remaining_amount,
                           (SELECT COUNT(*) FROM debt_transactions 
                            WHERE reference_id = s.id AND transaction_type = 'payment') as payment_count
                    FROM sales s
                    WHERE s.id = ?
                ");
                $stmt->execute([$id]);
                $saleInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($hasReturns) {
                    throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
                }

                if ($saleInfo['payment_type'] === 'credit' && $saleInfo['payment_count'] > 0) {
                    throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە پارەدانی لەسەر تۆمارکراوە');
                }

                // Get sale items to update inventory
                $stmt = $conn->prepare("SELECT product_id, pieces_count FROM sale_items WHERE sale_id = ?");
                $stmt->execute([$id]);
                $saleItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Update product quantities
                foreach ($saleItems as $item) {
                    $stmt = $conn->prepare("UPDATE products SET current_quantity = current_quantity + ? WHERE id = ?");
                    $stmt->execute([$item['pieces_count'], $item['product_id']]);
                }

                // Delete related records
                $stmt = $conn->prepare("DELETE FROM inventory WHERE reference_type = 'sale' AND reference_id IN (SELECT id FROM sale_items WHERE sale_id = ?)");
                $stmt->execute([$id]);

                $stmt = $conn->prepare("DELETE FROM debt_transactions WHERE reference_id = ? AND transaction_type = 'sale'");
                $stmt->execute([$id]);

                $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
                $stmt->execute([$id]);

                $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
                $stmt->execute([$id]);
            }
        } catch (PDOException $e) {
            throw new Exception('هەڵە لە سڕینەوەی پسووڵەی فرۆشتن: ' . $e->getMessage());
        }
    } else { // purchase
        try {
            // First, verify the purchase exists
            $stmt = $conn->prepare("SELECT * FROM purchases WHERE id = ?");
            $stmt->execute([$id]);
            $purchaseInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchaseInfo) {
                throw new Exception('پسووڵەی کڕین نەدۆزرایەوە - ID: ' . $id);
            }

            // Check if purchase has any returns
            $stmt = $conn->prepare("
                SELECT COUNT(*) as return_count 
                FROM product_returns 
                WHERE receipt_id = ? AND receipt_type = 'buying'
            ");
            $stmt->execute([$id]);
            $hasReturns = $stmt->fetch(PDO::FETCH_ASSOC)['return_count'] > 0;

            if ($hasReturns) {
                throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە');
            }

            // Check if purchase has any payments (for credit purchases)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as payment_count 
                FROM supplier_debt_transactions 
                WHERE reference_id = ? AND transaction_type IN ('payment', 'return')
            ");
            $stmt->execute([$id]);
            $hasPayments = $stmt->fetch(PDO::FETCH_ASSOC)['payment_count'] > 0;

            if ($purchaseInfo['payment_type'] === 'credit' && $hasPayments) {
                throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە پارەدانی لەسەر تۆمارکراوە');
            }

            // Get purchase items to check for sales
            $stmt = $conn->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
            $stmt->execute([$id]);
            $purchaseItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($purchaseItems)) {
                throw new Exception('هیچ کاڵایەک لە پسووڵەی کڕین نەدۆزرایەوە - ID: ' . $id);
            }

            // Check if any products from this purchase have been sold
            $anyProductsSold = false;
            $soldProductId = null;
            
            foreach ($purchaseItems as $item) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as sales_count
                    FROM sale_items si 
                    JOIN sales s ON si.sale_id = s.id
                    WHERE si.product_id = ? 
                    AND s.date > ?
                ");
                $stmt->execute([$item['product_id'], $purchaseInfo['date']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['sales_count'] > 0) {
                    $anyProductsSold = true;
                    $soldProductId = $item['product_id'];
                    break;
                }
            }

            if ($anyProductsSold) {
                throw new Exception('ناتوانرێت ئەم پسووڵەیە بسڕدرێتەوە چونکە بەشێک لە کاڵاکانی فرۆشراون (کاڵا ID: ' . $soldProductId . ')');
            }

            // Get purchase items to update inventory
            $stmt = $conn->prepare("
                SELECT pi.product_id, pi.quantity, pi.unit_type, p.pieces_per_box, p.boxes_per_set 
                FROM purchase_items pi 
                JOIN products p ON pi.product_id = p.id 
                WHERE pi.purchase_id = ?
            ");
            $stmt->execute([$id]);
            $purchaseItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Update product quantities
            foreach ($purchaseItems as $item) {
                // Calculate pieces count based on unit type
                $pieces_count = $item['quantity'];
                if ($item['unit_type'] === 'box') {
                    $pieces_count *= $item['pieces_per_box'];
                } elseif ($item['unit_type'] === 'set') {
                    $pieces_count *= ($item['pieces_per_box'] * $item['boxes_per_set']);
                }

                $stmt = $conn->prepare("UPDATE products SET current_quantity = current_quantity - ? WHERE id = ?");
                $stmt->execute([$pieces_count, $item['product_id']]);
            }

            // Delete related records
            $stmt = $conn->prepare("DELETE FROM inventory WHERE reference_type = 'purchase' AND reference_id IN (SELECT id FROM purchase_items WHERE purchase_id = ?)");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM supplier_debt_transactions WHERE reference_id = ? AND transaction_type = 'purchase'");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
            $stmt->execute([$id]);

            $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            throw new Exception('هەڵە لە سڕینەوەی پسووڵەی کڕین: ' . $errorMessage . ' (Code: ' . $errorCode . ')');
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'پسووڵەکە بە سەرکەوتوویی سڕایەوە'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'id' => $id ?? null,
            'type' => $type ?? null,
            'error_trace' => $e->getTraceAsString(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
} 