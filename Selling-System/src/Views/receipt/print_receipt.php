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
$customer_name = '';
$subtotal = 0;
$discount = 0;
$shipping_cost = 0;
$other_costs = 0;
$total_amount = 0;
$after_discount = 0;
$paid_amount = 0;
$remaining_balance = 0;
$previous_balance = 0;
$remaining_amount = 0;
$grand_total = 0;
$sale = [
    'date' => date('Y-m-d'),
    'time' => date('H:i'),
    'payment_type' => '',
    'customer_name' => '',
    'customer_phone' => ''
];

// Check if sale_id is provided
if (isset($_GET['sale_id']) && !empty($_GET['sale_id'])) {
    $sale_id = $_GET['sale_id']; // Don't convert to int to preserve string format
    
    // First try to find directly by sale ID
    $stmt = $conn->prepare("
        SELECT s.*, c.name as customer_name, c.phone1 as customer_phone
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([intval($sale_id)]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found, check if this is a debt_transaction ID and get the reference_id
    if (!$sale) {
        $stmt = $conn->prepare("
            SELECT s.*, c.name as customer_name, c.phone1 as customer_phone
            FROM debt_transactions dt
            JOIN sales s ON dt.reference_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE dt.id = ? AND dt.transaction_type = 'sale'
        ");
        $stmt->execute([intval($sale_id)]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$sale) {
        echo "<div style='text-align:center; padding:20px; color:red;'>پسوڵەی داواکراو نەدۆزرایەوە (پسووڵەی ژمارە: " . htmlspecialchars($sale_id) . ")</div>";
    } else {
        // Fetch sale items with correct column names
        $stmt = $conn->prepare("
            SELECT si.*, 
                p.name as product_name, 
                p.code as product_code, 
                p.image as product_image,
                p.pieces_per_box,
                p.boxes_per_set,
                u.name as unit_name,
                u.is_piece, u.is_box, u.is_set,
                COALESCE(si.returned_quantity, 0) as returned_quantity
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE si.sale_id = ?
            ORDER BY p.id, si.unit_type
        ");
        $stmt->execute([$sale['id']]);
        $sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group sale items by product_id to combine different unit types
        $grouped_products = [];
        foreach ($sale_items as $item) {
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
        $invoice_number = $sale['invoice_number'];
        $customer_name = $sale['customer_name'];
        $subtotal = 0;
        
        // Calculate subtotal from sale items (considering returns)
        foreach ($sale_items as $product) {
            $actual_quantity = $product['quantity'] - $product['returned_quantity'];
            $subtotal += floatval($product['unit_price'] * $actual_quantity);
        }
        
        $discount = floatval($sale['discount']);
        $shipping_cost = floatval($sale['shipping_cost']);
        $other_costs = floatval($sale['other_costs']);
        
        // Calculate total before discount
        $total_amount = $subtotal + $shipping_cost + $other_costs;
        
        // Calculate amount after discount
        $after_discount = $total_amount - $discount;
        
        // Get paid and remaining amounts directly from the database
        $paid_amount = floatval($sale['paid_amount']);
        $remaining_balance = floatval($sale['remaining_amount']);
        
        // Get previous balance (all previous debt except this sale)
        $stmt = $conn->prepare("
            SELECT debit_on_business 
            FROM customers 
            WHERE id = ?
        ");
        $stmt->execute([$sale['customer_id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $previous_balance = isset($customer['debit_on_business']) ? 
            (floatval($customer['debit_on_business']) - $remaining_balance) : 0;
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
        'receipt_title' => 'پسووڵەی فرۆشتن',
        'company_name' => 'کۆگای احمد و ئەشکان',
        'address' => 'ناونیشان: سلێمانی - کۆگاکانی غرفة تجارة - کۆگای 288',
        'phone' => 'ژمارە تەلەفۆن: 5678 123 0770',
        'receipt_number' => 'ژمارەی پسووڵە',
        'date' => 'بەروار',
        'customer' => 'کڕیار',
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
        'customer_signature' => 'واژۆی کڕیار',
        'seller_signature' => 'واژۆی فرۆشیار',
        'thank_you' => 'سوپاس بۆ کڕینتان',
        'print' => 'چاپکردن',
        'box' => 'کارتۆن',
        'set' => 'سێت',
        'returned' => 'گەڕێنراوەتەوە',
        'draft_notice' => 'ئەم پسوڵە تەنیا بۆ نیشاندان و عەرز کردنە',
        'and' => 'و'
    ],
    'ar' => [
        'receipt_title' => 'فاتورة المبيعات',
        'company_name' => 'مخزن أحمد و أشكان',
        'address' => 'العنوان: السليمانية - مخازن الغرفة التجارية - مخزن 288',
        'phone' => 'رقم الهاتف: 5678 123 0770',
        'receipt_number' => 'رقم الفاتورة',
        'date' => 'التاريخ',
        'customer' => 'الزبون',
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
        'customer_signature' => 'توقيع الزبون',
        'seller_signature' => 'توقيع البائع',
        'thank_you' => 'شكراً لتسوقكم',
        'print' => 'طباعة',
        'box' => 'كارتون',
        'set' => 'طقم',
        'returned' => 'مرتجع',
        'draft_notice' => 'هذه الفاتورة للعرض فقط',
        'and' => 'و'
    ]
];

// Get current language (default to Kurdish)
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ku';
$t = $translations[$lang];

// Update HTML direction based on language
$dir = $lang === 'ar' ? 'rtl' : 'rtl';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['receipt_title']; ?> - <?php echo $invoice_number; ?></title>
    <style>
        @font-face {
            font-family: 'Rabar';
            src: url('../../assets/fonts/Rabar_021.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --primary-color: #7380ec;
            --primary-light: rgba(115, 128, 236, 0.1);
            --primary-hover: #5b6be0;
            --danger-color: #ff7782;
            --success-color: #41f1b6;
            --warning-color: #ffbb55;
            --info-color: #7380ec;
            --dark-color: #363949; 
            --text-color: #363949;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --bg-light: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rabar', sans-serif;
            background: #f0f0f0;
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
        }

        .receipt-container {
            max-width: 21cm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header design */
        .receipt-header {
            background: var(--primary-color);
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .company-info h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .company-info p {
            font-size: 14px;
            opacity: 0.9;
        }

        .invoice-details {
            text-align: left;
            padding: 10px 20px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
        }

        .invoice-number {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .invoice-date {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Customer info */
        .customer-info {
            padding: 20px 25px;
            background: var(--bg-light);
            border-bottom: 2px dashed var(--border-color);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-group {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: bold;
            color: var(--dark-color);
        }

        /* Table styles */
        .items-section {
            padding: 20px 25px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border-color);
        }

        .items-table th {
            background: var(--primary-color);
            color: white;
            padding: 12px 15px;
            font-size: 14px;
            font-weight: normal;
            text-align: center;
            white-space: nowrap;
        }

        .items-table td {
            padding: 2px 2px;
            border: 1px solid var(--border-color);
            text-align: center;
            font-size: 14px;
        }

        .items-table tr:nth-child(even) {
            background-color: var(--primary-light);
        }

        .product-image {
            text-align: center;
        }

        .product-thumb {
            max-width: 50px;
            max-height: 50px;
            object-fit: contain;
            border-radius: 4px;
        }

        .product-name-cell {
            text-align: right;
        }

        .quantity-cell {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pieces-per-box-cell {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .total-pieces-cell {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Summary section */
        .summary-section {
            padding: 20px 25px;
            display: flex;
            justify-content: flex-end;
        }

        .summary-table {
            width: 350px;
            border-collapse: collapse;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .summary-table td {
            padding: 10px 15px;
            border: none;
        }

        .summary-table tr:not(:last-child) td {
            border-bottom: 1px solid #eee;
        }

        .summary-table tr:last-child {
            background: var(--primary-color);
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .summary-label {
            text-align: right;
        }

        .summary-value {
            text-align: left;
            font-weight: bold;
        }

        /* Footer */
        .receipt-footer {
            padding: 25px;
            background: var(--bg-light);
            border-top: 2px dashed var(--border-color);
            margin-top: auto;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .signature-line {
            width: 100%;
            height: 1px;
            background: var(--border-color);
            margin: 10px 0;
        }

        .signature-label {
            font-size: 14px;
            color: var(--text-muted);
        }

        .footer-notes {
            text-align: center;
            margin-top: 20px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .thank-you {
            text-align: center;
            margin-top: 20px;
            font-size: 18px;
            color: var(--primary-color);
        }

        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .print-button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }
            .receipt-container {
                box-shadow: none;
                margin: 0;
                max-width: none;
                min-height: 100vh;
            }
            .print-button {
                display: none;
            }
            @page {
                size: A4;
                margin: 0;
            }
            
            /* Force page break before summary section if needed */
            .page-break-summary {
                page-break-before: always;
            }
            
            /* Ensure header appears on all pages */
            .receipt-header {
                display: flex !important;
            }
            
            /* Ensure table headers repeat on new pages */
            thead {
                display: table-header-group;
            }
            
            /* Keep table rows together where possible */
            tr {
                page-break-inside: avoid;
            }

            /* Ensure footer stays at bottom */
            .receipt-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                margin-top: auto;
            }

            /* Add margin to content to prevent overlap with footer */
            .items-section, .summary-section {
                margin-bottom: 150px; /* Adjust based on footer height */
            }
        }

        .draft-indicator {
            background: #ffc107;
            color: #000;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .draft-indicator i {
            margin-left: 5px;
        }

        .language-button {
            position: fixed;
            bottom: 20px;
            left: 20px;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            font-family: 'Rabar', sans-serif;
        }

        .language-button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        @media print {
            .language-button {
                display: none;
            }
        }
    </style>

</head>
<body>
    <div class="receipt-container">
        <header class="receipt-header">
            <div class="logo-section">
          
                <div class="company-info">
                    <h1><?php echo $t['company_name']; ?></h1>
                    <p><?php echo $t['address']; ?></p>
                    <p>0771 225 5656</p>
                    <p>0750 147 8786</p>
                </div>
            </div>
            <div class="invoice-details">
                <div class="invoice-number"><?php echo $t['receipt_number']; ?>: <?php echo $invoice_number; ?></div>
                <div class="invoice-date"><?php echo $t['date']; ?>: <?php echo isset($sale['date']) ? date('Y-m-d', strtotime($sale['date'])) : date('Y-m-d'); ?></div>
            </div>
        </header>
        <?php if (isset($sale['is_draft']) && $sale['is_draft']): ?>
        <div class="draft-indicator">
            <i class="fas fa-file-alt"></i> <?php echo $t['draft_notice']; ?>
        </div>
        <?php endif; ?>
        <center>
        <section class="customer-info">
            <div class="info-group">
                <div class="info-label"><?php echo $t['customer']; ?></div>
                <div class="info-value"><?php echo isset($customer_name) ? $customer_name : 'هیچ'; ?></div>
            </div>
            <?php if (isset($sale['customer_phone']) && !empty($sale['customer_phone'])): ?>
            <div class="info-group">
                <div class="info-label"><?php echo $t['mobile']; ?></div>
                <div class="info-value"><?php echo $sale['customer_phone']; ?></div>
            </div>
            <?php endif; ?>
            <div class="info-group">
                <div class="info-label"><?php echo $t['time']; ?></div>
                <div class="info-value"><?php echo date('H:i', strtotime($sale['date'] ?? 'now')); ?></div>
            </div>
        </section>
        </center>

        <section class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo $t['image']; ?></th>
                        <th><?php echo $t['code']; ?></th>
                        <th><?php echo $t['product_name']; ?></th>
                        <th><?php echo $t['quantity']; ?></th>
                        <th><?php echo $t['total_pieces']; ?></th>
                        <th><?php echo $t['unit_price']; ?></th>
                        <th><?php echo $t['total']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    $total_items = 0;
                    foreach ($products as $product): 
                        // Skip products with no valid units
                        if (empty($product['units'])) continue;
                        
                        $count++;
                        
                        // Calculate total pieces and prepare display text
                        $total_pieces = 0;
                        $quantity_text = '';
                        $unit_counts = [
                            'set' => 0,
                            'box' => 0,
                            'piece' => 0
                        ];
                        
                        // Count quantities by unit type
                        foreach ($product['units'] as $unit) {
                            $unit_counts[$unit['unit_type']] += $unit['quantity'];
                        }
                        
                        // Build quantity text with all unit types
                        $parts = [];
                        if ($unit_counts['set'] > 0) {
                            $parts[] = $unit_counts['set'] . ' ' . $t['set'];
                            $total_pieces += $unit_counts['set'] * $product['boxes_per_set'] * $product['pieces_per_box'];
                        }
                        if ($unit_counts['box'] > 0) {
                            $parts[] = $unit_counts['box'] . ' ' . $t['box'];
                            $total_pieces += $unit_counts['box'] * $product['pieces_per_box'];
                        }
                        if ($unit_counts['piece'] > 0) {
                            $parts[] = $unit_counts['piece'] . ' ' . $t['pieces'];
                            $total_pieces += $unit_counts['piece'];
                        }
                        
                        // Join parts with "and"
                        if (count($parts) > 1) {
                            $last_part = array_pop($parts);
                            $quantity_text = implode('، ', $parts) . ' ' . $t['and'] . ' ' . $last_part;
                        } else {
                            $quantity_text = $parts[0];
                        }
                        
                        $image_name = !empty($product['product_image']) ? $product['product_image'] : 'pro-1.png';
                        $image_url = get_correct_image_path($image_name);
                    ?>
                    <tr>
                        <td><?php echo $count; ?></td>
                        <td class="product-image">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="product-thumb"
                                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars(get_correct_image_path('pro-1.png')); ?>';">
                        </td>
                        <td><?php echo $product['product_code']; ?></td>
                        <td class="product-name-cell">
                            <div class="product-details">
                                <span class="product-name">
                                    <?php echo $product['product_name']; ?>
                                </span>
                                <?php if ($product['returned_quantity'] > 0): ?>
                                <br>
                                <span class="returned-info" style="color: red; font-size: 0.9em;">
                                    (<?php echo $product['returned_quantity']; ?> <?php echo $t['returned']; ?>)
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="quantity-cell">
                            <?php echo $quantity_text; ?>
                        </td>
                        <td class="total-pieces-cell">
                            <?php echo $total_pieces . ' ' . $t['pieces']; ?>
                        </td>
                        <td>
                            <?php 
                            // Show unit prices if multiple units with different prices
                            if (count($product['units']) > 1) {
                                $price_texts = [];
                                foreach ($product['units'] as $unit) {
                                    $price_texts[] = number_format($unit['unit_price'], 0) . ' د.ع';
                                }
                                echo implode(' / ', $price_texts);
                            } else {
                                echo number_format($product['units'][0]['unit_price'], 0) . ' د.ع';
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($product['total_price'], 0) . ' د.ع'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section id="summary-section" class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="summary-label"><?php echo $t['total_amount']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($total_amount, 0) . ' د.ع'; ?></td>
                </tr>
                <?php if ($discount > 0): ?>
                <tr>
                    <td class="summary-label"><?php echo $t['discount']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($discount , 0)  ?> د.ع</td>
                </tr>
                <tr>
                    <td class="summary-label"><?php echo $t['after_discount']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($after_discount, 0) . ' د.ع'; ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="summary-label"><?php echo $t['paid_amount']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($paid_amount, 0) . ' د.ع'; ?></td>
                </tr>
                <?php if ($remaining_balance > 0): ?>
                <tr>
                    <td class="summary-label"><?php echo $t['remaining']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($remaining_balance, 0) . ' د.ع'; ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($previous_balance > 0): ?>
                <tr>
                    <td class="summary-label"><?php echo $t['previous_balance']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($previous_balance, 0) . ' د.ع'; ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="summary-label"><?php echo $t['grand_total']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($grand_total, 0) . ' د.ع'; ?></td>
                </tr>
            </table>
        </section>

        <footer class="receipt-footer">
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label"><?php echo $t['customer_signature']; ?></div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label"><?php echo $t['seller_signature']; ?></div>
                </div>
            </div>
            
            <div class="thank-you">
                <?php echo $t['thank_you']; ?>
            </div>
            
         
        </footer>
    </div>

    <button class="print-button" onclick="window.print()"><?php echo $t['print']; ?></button>
    <button class="language-button" onclick="toggleLanguage()"><?php echo $lang === 'ku' ? 'العربية' : 'کوردی'; ?></button>

    <script>
    function toggleLanguage() {
        const currentLang = '<?php echo $lang; ?>';
        const newLang = currentLang === 'ku' ? 'ar' : 'ku';
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('lang', newLang);
        window.location.href = currentUrl.toString();
    }

    // Auto open print dialog when page loads
    window.onload = function() {
        setTimeout(function() {
            window.print();
            checkSummaryPosition();
            // Recalculate on window resize
            window.addEventListener('resize', checkSummaryPosition);
        }, 1000); // Short delay to ensure everything is loaded
    };
    
    function checkSummaryPosition() {
        const summarySection = document.getElementById('summary-section');
        const itemsSection = document.querySelector('.items-section');
        
        if (!summarySection || !itemsSection) return;
        
        // Get elements positions
        const itemsRect = itemsSection.getBoundingClientRect();
        const summaryRect = summarySection.getBoundingClientRect();
        
        // If items table takes up most of the page, force summary to next page
        const pageHeight = window.innerHeight;
        const itemsHeight = itemsRect.height;
        
        if (itemsHeight > pageHeight * 0.7) { // If items take more than 70% of the page
            summarySection.classList.add('page-break-summary');
        } else {
            summarySection.classList.remove('page-break-summary');
        }
    }
    </script>
</body>
</html> 