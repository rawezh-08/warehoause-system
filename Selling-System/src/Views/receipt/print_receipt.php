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
        die("پسوڵەی داواکراو نەدۆزرایەوە (پسووڵەی ژمارە: " . htmlspecialchars($sale_id) . ")");
    }
    
    // Fetch sale items with correct column names
    $stmt = $conn->prepare("
        SELECT si.*, 
               p.name as product_name, 
               p.code as product_code, 
               p.image as product_image,
               p.pieces_per_box,
               p.boxes_per_set,
               u.name as unit_name,
               u.is_piece, u.is_box, u.is_set
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        LEFT JOIN units u ON p.unit_id = u.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$sale['id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set receipt data
    $invoice_number = $sale['invoice_number'];
    $customer_name = $sale['customer_name'];
    $subtotal = 0;
    
    // Calculate subtotal from sale items
    foreach ($products as $product) {
        $subtotal += floatval($product['total_price']);
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
    $previous_balance = floatval($customer['debit_on_business']) - $remaining_balance;
    $previous_balance = $previous_balance < 0 ? 0 : $previous_balance;
    
    $remaining_amount = $remaining_balance;
    $grand_total = $previous_balance + $remaining_balance;
} else {
  
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی فرۆشتن - <?php echo $invoice_number; ?></title>
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
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
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
    </style>

</head>
<body>
    <div class="receipt-container">
        <header class="receipt-header">
            <div class="logo-section">
                <img src="../../assets/images/company-logo.svg" alt="کۆگای ئەشکان" class="company-logo">
                <div class="company-info">
                    <h1>کۆگای احمد و ئەشکان</h1>
                    <p>ناونیشان: سلێمانی - کۆگاکانی غرفة تجارة - کۆگای 288</p>
                    <p>ژمارە تەلەفۆن: 5678 123 0770</p>
                </div>
            </div>
            <div class="invoice-details">
                <div class="invoice-number">ژمارەی پسووڵە: <?php echo $invoice_number; ?></div>
                <div class="invoice-date">بەروار: <?php echo isset($sale['date']) ? date('Y-m-d', strtotime($sale['date'])) : date('Y-m-d'); ?></div>
            </div>
        </header>
<center>
        <section class="customer-info">
            <div class="info-group">
                <div class="info-label">کڕیار</div>
                <div class="info-value"><?php echo isset($customer_name) ? $customer_name : 'هیچ'; ?></div>
            </div>
            <?php if (isset($sale['customer_phone']) && !empty($sale['customer_phone'])): ?>
            <div class="info-group">
                <div class="info-label">ژمارەی مۆبایل</div>
                <div class="info-value"><?php echo $sale['customer_phone']; ?></div>
            </div>
            <?php endif; ?>
            <div class="info-group">
                <div class="info-label">کات</div>
                <div class="info-value"><?php echo date('H:i', strtotime($sale['date'] ?? 'now')); ?></div>
            </div>
        </section>
        </center>

        <section class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>وێنە</th>
                        <th>کۆد</th>
                        <th>ناوی کاڵا</th>
                        <th>بڕ</th>
                        <th>دانە</th>
                        <th>کۆی دانەکان</th>
                        <th>نرخی یەکە</th>
                        <th>کۆی گشتی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    $total_items = 0;
                    foreach ($products as $product): 
                        $count++;
                        $total_items += $product['quantity'];

                        // Determine the unit type based on the sale_items unit_type
                        $unit_type = isset($product['unit_type']) ? $product['unit_type'] : 'piece';

                        // Calculate pieces per box and total pieces
                        $pieces_per_box = isset($product['pieces_per_box']) ? $product['pieces_per_box'] : 1;
                        $total_pieces = $product['quantity']; // Default for single pieces
                        
                        if ($unit_type == 'box' && $pieces_per_box > 0) {
                            $total_pieces = $product['quantity'] * $pieces_per_box;
                        } elseif ($unit_type == 'set' && isset($product['boxes_per_set']) && $product['boxes_per_set'] > 0) {
                            $total_pieces = $product['quantity'] * $product['boxes_per_set'] * $pieces_per_box;
                        }
                    ?>
                    <tr>
                        <td><?php echo $count; ?></td>
                        <td class="product-image">
                            <?php 
                            $image_name = !empty($product['product_image']) ? $product['product_image'] : 'pro-1.png';
                            $image_url = get_correct_image_path($image_name);
                            $product_name = isset($product['product_name']) ? $product['product_name'] : 'Product';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($product_name); ?>" 
                                 class="product-thumb"
                                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars(get_correct_image_path('pro-1.png')); ?>';">
                        </td>
                        <td><?php echo isset($product['product_code']) ? $product['product_code'] : $product['code']; ?></td>
                        <td class="product-name-cell">
                            <div class="product-details">
                                <span class="product-name">
                                    <?php echo isset($product['product_name']) ? $product['product_name'] : $product['name']; ?>
                                </span>
                            </div>
                        </td>
                        <td class="quantity-cell">
                            <?php 
                            if ($unit_type == 'piece') {
                                echo $product['quantity'] . ' دانە';
                            } else {
                                echo $product['quantity'] . ' ';
                                switch($unit_type) {
                                    case 'box':
                                        echo '<span class="unit-type">کارتۆن</span>';
                                        break;
                                    case 'set':
                                        echo '<span class="unit-type">سێت</span>';
                                        break;
                                }
                            }
                            ?>
                        </td>
                        <td class="pieces-per-box-cell">
                            <?php 
                            if ($unit_type == 'piece') {
                                echo '-';
                            } elseif ($unit_type == 'box') {
                                echo $pieces_per_box . ' دانە';
                            } else { // set
                                echo $product['boxes_per_set'] . ' کارتۆن';
                            }
                            ?>
                        </td>
                        <td class="total-pieces-cell">
                            <?php 
                            if ($unit_type == 'piece') {
                                echo $product['quantity'] . ' دانە';
                            } else {
                                echo $total_pieces . ' دانە';
                            }
                            ?>
                        </td>
                        <td><?php echo number_format(isset($product['unit_price']) ? $product['unit_price'] : $product['price'], 0) . ' د.ع'; ?></td>
                        <td><?php echo number_format(isset($product['total_price']) ? $product['total_price'] : $product['total'], 0) . ' د.ع'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section id="summary-section" class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">کۆی پارەی کاڵاکان:</td>
                    <td class="summary-value"><?php echo number_format($total_amount, 0) . ' د.ع'; ?></td>
                </tr>
                <?php if ($discount > 0): ?>
                <tr>
                    <td class="summary-label">داشکاندن:</td>
                    <td class="summary-value"><?php echo number_format($discount , 0)  ?> د.ع</td>
                </tr>
                <tr>
                    <td class="summary-label">دوای داشکاندن:</td>
                    <td class="summary-value"><?php echo number_format($after_discount, 0) . ' د.ع'; ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="summary-label">پارەی دراو:</td>
                    <td class="summary-value"><?php echo number_format($paid_amount, 0) . ' د.ع'; ?></td>
                </tr>
                <?php if ($remaining_balance > 0): ?>
                <tr>
                    <td class="summary-label">پارەی ماوە:</td>
                    <td class="summary-value"><?php echo number_format($remaining_balance, 0) . ' د.ع'; ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($previous_balance > 0): ?>
                <tr>
                    <td class="summary-label">قەرزی پێشوو:</td>
                    <td class="summary-value"><?php echo number_format($previous_balance, 0) . ' د.ع'; ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="summary-label">کۆی گشتی:</td>
                    <td class="summary-value"><?php echo number_format($grand_total, 0) . ' د.ع'; ?></td>
                </tr>
            </table>
        </section>

        <footer class="receipt-footer">
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">واژۆی کڕیار</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">واژۆی فرۆشیار</div>
                </div>
            </div>
            
            <div class="thank-you">
                سوپاس بۆ کڕینتان
            </div>
            
         
        </footer>
    </div>

    <button class="print-button" onclick="window.print()">چاپکردن</button>
        <script>
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