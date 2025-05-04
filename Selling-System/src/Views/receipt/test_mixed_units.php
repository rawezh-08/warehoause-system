<?php
// Include database connection
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Check if we need to create a test sale
if (isset($_GET['create_test'])) {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // 1. Get a valid product that has pieces_per_box defined
        $product_stmt = $conn->query("
            SELECT p.*, u.name as unit_name
            FROM products p
            JOIN units u ON p.unit_id = u.id
            WHERE p.pieces_per_box > 0
            LIMIT 1
        ");
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("No suitable product found. Please make sure you have products with pieces_per_box defined.");
        }
        
        // 2. Get a customer
        $customer_stmt = $conn->query("SELECT id FROM customers LIMIT 1");
        $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception("No customer found. Please create a customer first.");
        }
        
        // 3. Create a test sale
        $sale_date = date('Y-m-d H:i:s');
        $invoice_number = 'TEST-' . date('YmdHis');
        
        $sale_stmt = $conn->prepare("
            INSERT INTO sales (
                invoice_number, customer_id, total_amount, discount,
                shipping_cost, other_costs, paid_amount, remaining_amount,
                payment_type, date, notes, is_draft
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?
            )
        ");
        
        // Calculate example values
        $box_price = $product['selling_price_single'] * 0.9; // Slightly discounted for boxes
        $piece_price = $product['selling_price_single'];
        
        $box_quantity = 1;
        $piece_quantity = 4;
        
        $total_box_price = $box_quantity * $box_price * $product['pieces_per_box'];
        $total_piece_price = $piece_quantity * $piece_price;
        $total_amount = $total_box_price + $total_piece_price;
        
        // Insert the sale
        $sale_stmt->execute([
            $invoice_number,
            $customer['id'],
            $total_amount,
            0, // No discount
            0, // No shipping
            0, // No other costs
            $total_amount, // Fully paid
            0, // No remaining amount
            'cash',
            $sale_date,
            'Test sale with mixed units: 1 box and 4 pieces',
            0 // Not a draft
        ]);
        
        $sale_id = $conn->lastInsertId();
        
        // 4. Add sale items - one for box, one for pieces
        // Add box item
        $box_stmt = $conn->prepare("
            INSERT INTO sale_items (
                sale_id, product_id, quantity, unit_price, unit_type
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $box_stmt->execute([
            $sale_id,
            $product['id'],
            $box_quantity,
            $box_price,
            'box'
        ]);
        
        // Add piece item
        $piece_stmt = $conn->prepare("
            INSERT INTO sale_items (
                sale_id, product_id, quantity, unit_price, unit_type
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $piece_stmt->execute([
            $sale_id,
            $product['id'],
            $piece_quantity,
            $piece_price,
            'piece'
        ]);
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to the print receipt page
        header("Location: print_receipt.php?sale_id=" . $sale_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "<div style='color: red; padding: 20px; margin: 20px; border: 1px solid red;'>";
        echo "<h2>Error:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تاقیکردنەوەی یەکەکانی تێکەڵاو</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            padding: 50px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 30px;
            color: #7380ec;
        }
        .card {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>تاقیکردنەوەی یەکەکانی تێکەڵاو</h1>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">دروستکردنی پسوڵەی تاقیکردنەوە</h5>
                <p class="card-text">
                    ئەم لاپەڕەیە پسوڵەیەکی تاقیکردنەوە دروست دەکات کە تێیدا یەک بەرهەم دوو جۆری یەکەی جیاوازی هەیە (کارتۆن و دانە).
                    پاشان، ڕاستەوخۆ دەتباتە لاپەڕەی چاپکردنی پسوڵە بۆ ئەوەی سەیری ئەنجامەکە بکەیت.
                </p>
                <a href="?create_test=1" class="btn btn-primary">دروستکردنی پسوڵەی تاقیکردنەوە</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">ڕێنماییەکان</h5>
                <p class="card-text">
                    ئەم سکریپتە پسوڵەیەکی تاقیکردنەوە دروست دەکات کە تێیدا بەرهەمێک هەیە بەم شێوەیەی خوارەوە:
                </p>
                <ul>
                    <li>1 کارتۆن</li>
                    <li>4 دانە</li>
                </ul>
                <p class="card-text">
                    لە پسوڵەکەدا، ئەم دوو یەکەیە پیشان دەدرێن لە یەک ڕیزدا بەم شێوەیە: <strong>1 کارتۆن و 4 دانە</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 