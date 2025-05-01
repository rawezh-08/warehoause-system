<?php
// Include database connection
require_once '../../config/database.php';

// Function to get correct image path without duplication
function get_correct_image_path($image_name) {
    // Remove any existing paths to avoid duplication
    $image_name = basename($image_name);
    
    // Check if the image exists in common locations
    $possible_locations = [
        '/uploads/products/',
        '/Selling-System/uploads/products/',
        '/warehouse-system/uploads/products/'
    ];
    
    $base_url = '';
    // Try to determine base URL from current script
    if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_NAME'])) {
        $base_path = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                   '://' . $_SERVER['HTTP_HOST'] . ($base_path == '/' ? '' : $base_path);
    }
    
    foreach ($possible_locations as $location) {
        $image_path = $base_url . $location . $image_name;
        // We can't check if file exists with URL, so just return the most likely path
        return $image_path;
    }
    
    // If all else fails, return a path relative to current file
    return '../../uploads/products/' . $image_name;
}

// Initialize variables with default values to prevent undefined variable errors
$products = [];
$invoice_number = '';
$supplier_name = '';
$subtotal = 0;
$discount = 0;
$shipping_cost = 0;
$other_cost = 0;
$total_amount = 0;
$after_discount = 0;
$paid_amount = 0;
$remaining_balance = 0;
$previous_balance = 0;
$remaining_amount = 0;
$grand_total = 0;
$purchase = [
    'date' => date('Y-m-d'),
    'time' => date('H:i'),
    'payment_type' => '',
    'supplier_name' => '',
    'supplier_phone' => ''
];

// Check if purchase_id is provided
if (isset($_GET['purchase_id']) && !empty($_GET['purchase_id'])) {
    $purchase_id = $_GET['purchase_id']; // Don't convert to int to preserve string format
    
    // First try to find directly by purchase ID
    $stmt = $conn->prepare("
        SELECT p.*, s.name as supplier_name, s.phone1 as supplier_phone
        FROM purchases p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        WHERE p.id = ?
    ");
    $stmt->execute([intval($purchase_id)]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found, check if this is a supplier_debt_transaction ID and get the reference_id
    if (!$purchase) {
        $stmt = $conn->prepare("
            SELECT p.*, s.name as supplier_name, s.phone1 as supplier_phone
            FROM supplier_debt_transactions sdt
            JOIN purchases p ON sdt.reference_id = p.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE sdt.id = ? AND sdt.transaction_type = 'purchase'
        ");
        $stmt->execute([intval($purchase_id)]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$purchase) {
        echo "<div style='text-align:center; padding:20px; color:red;'>پسوڵەی داواکراو نەدۆزرایەوە (پسووڵەی ژمارە: " . htmlspecialchars($purchase_id) . ")</div>";
    } else {
        // Fetch purchase items with correct column names
        $stmt = $conn->prepare("
            SELECT pi.*, 
                p.name as product_name, 
                p.code as product_code, 
                p.image as product_image,
                p.pieces_per_box,
                p.boxes_per_set,
                u.name as unit_name,
                u.is_piece, u.is_box, u.is_set,
                COALESCE(pi.returned_quantity, 0) as returned_quantity
            FROM purchase_items pi
            JOIN products p ON pi.product_id = p.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE pi.purchase_id = ?
            ORDER BY p.id, pi.unit_type
        ");
        $stmt->execute([$purchase['id']]);
        $purchase_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group purchase items by product_id to combine different unit types
        $grouped_products = [];
        foreach ($purchase_items as $item) {
            $product_id = $item['product_id'];
            
            if (!isset($grouped_products[$product_id])) {
                // Initialize the product with base data
                $grouped_products[$product_id] = [
                    'product_id' => $product_id,
                    'product_name' => $item['product_name'],
                    'product_code' => $item['product_code'],
                    'product_image' => $item['product_image'],
                    'pieces_per_box' => $item['pieces_per_box'],
                    'boxes_per_set' => $item['boxes_per_set'],
                    'unit_name' => $item['unit_name'],
                    'units' => [],
                    'total_price' => 0,
                    'returned_quantity' => 0
                ];
            }
            
            // Add this unit type to the product's units array
            $actual_quantity = $item['quantity'] - $item['returned_quantity'];
            if ($actual_quantity > 0) {
                $grouped_products[$product_id]['units'][] = [
                    'unit_type' => $item['unit_type'],
                    'quantity' => $actual_quantity,
                    'unit_price' => $item['unit_price']
                ];
                
                // Add to total price
                $grouped_products[$product_id]['total_price'] += $actual_quantity * $item['unit_price'];
            }
            
            // Track returned items
            $grouped_products[$product_id]['returned_quantity'] += $item['returned_quantity'];
        }
        
        // Convert grouped products to array for easier iteration
        $products = array_values($grouped_products);
        
        // Set receipt data
        $invoice_number = $purchase['invoice_number'];
        $supplier_name = $purchase['supplier_name'];
        $subtotal = 0;
        
        // Calculate subtotal from purchase items (considering returns)
        foreach ($purchase_items as $product) {
            $actual_quantity = $product['quantity'] - $product['returned_quantity'];
            $subtotal += floatval($product['unit_price'] * $actual_quantity);
        }
        
        $discount = floatval($purchase['discount']);
        $shipping_cost = floatval($purchase['shipping_cost']);
        $other_cost = floatval($purchase['other_cost']);
        
        // Calculate total before discount
        $total_amount = $subtotal + $shipping_cost + $other_cost;
        
        // Calculate amount after discount
        $after_discount = $total_amount - $discount;
        
        // Get paid and remaining amounts directly from the database
        $paid_amount = floatval($purchase['paid_amount']);
        $remaining_balance = floatval($purchase['remaining_amount']);
        
        // Get previous balance (all previous debt except this purchase)
        $stmt = $conn->prepare("
            SELECT debt_on_myself 
            FROM suppliers 
            WHERE id = ?
        ");
        $stmt->execute([$purchase['supplier_id']]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        $previous_balance = isset($supplier['debt_on_myself']) ? 
            (floatval($supplier['debt_on_myself']) - $remaining_balance) : 0;
        $previous_balance = $previous_balance < 0 ? 0 : $previous_balance;
        
        $remaining_amount = $remaining_balance;
        $grand_total = $previous_balance + $remaining_balance;
    }
} else {
    echo "<div style='text-align:center; padding:20px; color:red;'>هیچ پسووڵەیەک دیاری نەکراوە</div>";
}

// Add language translations
$translations = [
    'ku' => [
        'receipt_title' => 'پسووڵەی کڕین',
        'company_name' => 'کۆگای احمد و ئەشکان',
        'address' => 'ناونیشان: سلێمانی - کۆگاکانی غرفة تجارة - کۆگای 288',
        'phone' => 'ژمارە تەلەفۆن: 5678 123 0770',
        'receipt_number' => 'ژمارەی پسووڵە',
        'date' => 'بەروار',
        'supplier' => 'دابینکەر',
        'mobile' => 'ژمارەی مۆبایل',
        'time' => 'کات',
        'image' => 'وێنە',
        'code' => 'کۆد',
        'product_name' => 'ناوی کاڵا',
        'quantity' => 'بڕ',
        'pieces' => 'دانە',
        'total_pieces' => 'کۆی دانەکان',
        'unit_price' => 'نرخی یەکە',
        'total' => 'کۆی گشتی',
        'total_amount' => 'کۆی پارەی کاڵاکان',
        'discount' => 'داشکاندن',
        'after_discount' => 'دوای داشکاندن',
        'paid_amount' => 'پارەی دراو',
        'remaining' => 'پارەی ماوە',
        'previous_balance' => 'قەرزی پێشوو',
        'grand_total' => 'کۆی گشتی',
        'supplier_signature' => 'واژۆی دابینکەر',
        'buyer_signature' => 'واژۆی کڕیار',
        'thank_you' => 'سوپاس',
        'print' => 'چاپکردن',
        'box' => 'کارتۆن',
        'set' => 'سێت',
        'returned' => 'گەڕێنراوەتەوە',
        'shipping_cost' => 'کرێی گواستنەوە',
        'other_costs' => 'خەرجی تر'
    ],
    'ar' => [
        'receipt_title' => 'فاتورة الشراء',
        'company_name' => 'مخزن أحمد و أشكان',
        'address' => 'العنوان: السليمانية - مخازن الغرفة التجارية - مخزن 288',
        'phone' => 'رقم الهاتف: 5678 123 0770',
        'receipt_number' => 'رقم الفاتورة',
        'date' => 'التاريخ',
        'supplier' => 'المورد',
        'mobile' => 'رقم الموبايل',
        'time' => 'الوقت',
        'image' => 'الصورة',
        'code' => 'الرمز',
        'product_name' => 'اسم المنتج',
        'quantity' => 'الكمية',
        'pieces' => 'قطعة',
        'total_pieces' => 'مجموع القطع',
        'unit_price' => 'سعر الوحدة',
        'total' => 'المجموع',
        'total_amount' => 'المبلغ الإجمالي',
        'discount' => 'الخصم',
        'after_discount' => 'بعد الخصم',
        'paid_amount' => 'المبلغ المدفوع',
        'remaining' => 'المبلغ المتبقي',
        'previous_balance' => 'الرصيد السابق',
        'grand_total' => 'المجموع الكلي',
        'supplier_signature' => 'توقيع المورد',
        'buyer_signature' => 'توقيع المشتري',
        'thank_you' => 'شكرا لكم',
        'print' => 'طباعة',
        'box' => 'كرتون',
        'set' => 'سيت',
        'returned' => 'مرتجع',
        'shipping_cost' => 'تكلفة الشحن',
        'other_costs' => 'تكاليف أخرى'
    ]
];

// Default language is Kurdish
$lang = 'ku';
$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['receipt_title'] . ' #' . $invoice_number; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page {
                size: 80mm 297mm; /* Receipt width (80mm) and auto height */
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
                width: 80mm; /* Match page width */
            }
            .no-print {
                display: none !important;
            }
            .receipt-container {
                width: 100%;
                max-width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 5mm;
            }
            .receipt-header, .receipt-footer {
                text-align: center;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            width: 80mm;
            background: white;
            margin: 0 auto;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .receipt-header, .receipt-footer {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .receipt-info {
            border-top: 1px dashed #ccc;
            border-bottom: 1px dashed #ccc;
            padding: 8px 0;
            margin: 5px 0;
            font-size: 12px;
        }
        
        .receipt-info p {
            margin: 2px 0;
        }
        
        .items-table {
            width: 100%;
            font-size: 12px;
            border-collapse: collapse;
            margin: 5px 0;
        }
        
        .items-table th, .items-table td {
            padding: 4px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            background: #f9f9f9;
        }
        
        .summary {
            margin-top: 10px;
            font-size: 12px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }
        
        .signature-area {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }
        
        .signature-box {
            text-align: center;
            flex: 1;
        }
        
        .signature-line {
            border-top: 1px solid #999;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        .thank-you {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .print-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
        }
        
        .product-image {
            max-width: 40px;
            max-height: 40px;
        }
        
        .total-row {
            font-weight: bold;
            border-top: 1px solid #999;
            border-bottom: 1px solid #999;
        }
        
        /* Table header alignment */
        .items-table th:nth-child(1), /* Code */
        .items-table td:nth-child(1) {
            text-align: right;
        }
        
        .items-table th:nth-child(3), /* Quantity */
        .items-table td:nth-child(3),
        .items-table th:nth-child(4), /* Unit Price */
        .items-table td:nth-child(4),
        .items-table th:nth-child(5), /* Total */
        .items-table td:nth-child(5) {
            text-align: center;
        }
    </style>
</head>
<body>
    <?php if(isset($purchase['id'])): ?>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-title"><?php echo $t['receipt_title']; ?></div>
            <div class="company-info"><?php echo $t['company_name']; ?></div>
            <div class="company-info"><?php echo $t['address']; ?></div>
            <div class="company-info"><?php echo $t['phone']; ?></div>
        </div>
        
        <div class="receipt-info">
            <p><strong><?php echo $t['receipt_number']; ?>:</strong> <?php echo $invoice_number; ?></p>
            <p><strong><?php echo $t['date']; ?>:</strong> <?php echo date('Y-m-d', strtotime($purchase['date'])); ?></p>
            <p><strong><?php echo $t['time']; ?>:</strong> <?php echo date('H:i', strtotime($purchase['date'])); ?></p>
            <p><strong><?php echo $t['supplier']; ?>:</strong> <?php echo $supplier_name; ?></p>
        </div>
        
        <!-- Products Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th><?php echo $t['code']; ?></th>
                    <th><?php echo $t['product_name']; ?></th>
                    <th><?php echo $t['quantity']; ?></th>
                    <th><?php echo $t['unit_price']; ?></th>
                    <th><?php echo $t['total']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $total_items = 0; ?>
                <?php foreach($products as $i => $product): ?>
                    <?php foreach($product['units'] as $unit): ?>
                        <?php $total_items++; ?>
                        <tr>
                            <td><?php echo $product['product_code'] ?? '-'; ?></td>
                            <td><?php echo $product['product_name']; ?></td>
                            <td>
                                <?php echo $unit['quantity']; ?> 
                                <?php
                                    if($unit['unit_type'] == 'piece') echo $t['pieces'];
                                    elseif($unit['unit_type'] == 'box') echo $t['box'];
                                    elseif($unit['unit_type'] == 'set') echo $t['set'];
                                ?>
                            </td>
                            <td><?php echo number_format($unit['unit_price'],0); ?></td>
                            <td><?php echo number_format($unit['quantity'] * $unit['unit_price'],0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if($product['returned_quantity'] > 0): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color: #d9534f;">
                                <?php echo $product['returned_quantity'] . ' ' . $t['pieces'] . ' ' . $t['returned']; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2"><?php echo $total_items . ' ' . $t['total_pieces']; ?></td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Summary Section -->
        <div class="summary">
            <div class="summary-row">
                <div><?php echo $t['total_amount']; ?>:</div>
                <div><?php echo number_format($subtotal,0); ?></div>
            </div>
            
            <?php if($shipping_cost > 0): ?>
            <div class="summary-row">
                <div><?php echo $t['shipping_cost']; ?>:</div>
                <div><?php echo number_format($shipping_cost,0); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if($other_cost > 0): ?>
            <div class="summary-row">
                <div><?php echo $t['other_costs']; ?>:</div>
                <div><?php echo number_format($other_cost,0); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if($discount > 0): ?>
            <div class="summary-row">
                <div><?php echo $t['discount']; ?>:</div>
                <div><?php echo number_format($discount,0); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="summary-row" style="font-weight: bold;">
                <div><?php echo $t['after_discount']; ?>:</div>
                <div><?php echo number_format($after_discount,0); ?></div>
            </div>
            
            <div class="summary-row">
                <div><?php echo $t['paid_amount']; ?>:</div>
                <div><?php echo number_format($paid_amount,0); ?></div>
            </div>
            
            <div class="summary-row">
                <div><?php echo $t['remaining']; ?>:</div>
                <div><?php echo number_format($remaining_amount,0); ?></div>
            </div>
            
            <?php if($previous_balance > 0): ?>
            <div class="summary-row">
                <div><?php echo $t['previous_balance']; ?>:</div>
                <div><?php echo number_format($previous_balance,0); ?></div>
            </div>
            
            <div class="summary-row" style="font-weight: bold;">
                <div><?php echo $t['grand_total']; ?>:</div>
                <div><?php echo number_format($grand_total,0); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Signature Area -->
        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><?php echo $t['supplier_signature']; ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><?php echo $t['buyer_signature']; ?></div>
            </div>
        </div>
        
        <div class="thank-you"><?php echo $t['thank_you']; ?></div>
    </div>
    
    <button class="btn btn-primary print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> <?php echo $t['print']; ?>
    </button>
    <?php endif; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 