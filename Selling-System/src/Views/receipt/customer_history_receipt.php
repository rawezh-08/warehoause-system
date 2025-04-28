<?php
// Include database connection
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if we're in print mode (no filters shown)
$printMode = isset($_GET['print']) && $_GET['print'] == 'true';

// Get language preference (default to Kurdish)
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ku';

// Language translations
$translations = [
    'ku' => [
        'title' => 'مێژووی کڕیار',
        'company_name' => 'کۆگای احمد و ئەشکان',
        'company_address' => 'ناونیشان: سلێمانی - کۆگاکانی غرفة تجارة - کۆگای 288',
        'company_phone' => 'ژمارە تەلەفۆن: 5678 123 0770',
        'customer_history' => 'مێژووی مامەڵەکانی کڕیار',
        'date' => 'بەروار',
        'customer_name' => 'ناوی کڕیار',
        'phone' => 'ژمارەی مۆبایل',
        'address' => 'ناونیشان',
        'debt_status' => 'بارودۆخی قەرز',
        'unknown' => 'نادیار',
        'debt_amount' => 'دینار قەرزدار',
        'advance_amount' => 'دینار پێشەکی',
        'balanced' => 'هاوسەنگ',
        'purchase_history' => 'مێژووی کڕینەکان',
        'invoice_number' => 'ژمارەی پسووڵە',
        'items_count' => 'هەژمار کاڵاکان',
        'total_quantity' => 'کۆی بڕ',
        'total_price' => 'کۆی نرخ',
        'payment_type' => 'جۆری پارەدان',
        'cash' => 'نەقد',
        'credit' => 'قەرز',
        'paid_amount' => 'پارەی دراو',
        'remaining_amount' => 'پارەی ماوە',
        'debt_return_history' => 'مێژووی گەڕاندنەوەی قەرز',
        'returned_amount' => 'بڕی گەڕاوە',
        'payment_method' => 'شێوازی پارەدان',
        'notes' => 'تێبینی',
        'total_purchases' => 'کۆی کڕینەکان',
        'total_debt_returns' => 'کۆی گەڕاندنەوەی قەرز',
        'debt_status_title' => 'دۆخی قەرز',
        'debtor' => 'قەرزدار',
        'advance' => 'پێشەکی',
        'footer_note' => 'ئەم وەسڵە تەنها بۆ مەبەستی بەدواداچوونە و بە وەسڵی فەرمی دانانرێت',
        'print' => 'چاپکردن',
        'filter_title' => 'فلتەرکردنی مێژووی کڕیار بە پێی بەروار',
        'from_date' => 'لە بەرواری',
        'to_date' => 'هەتا بەرواری',
        'reset' => 'ڕیسێت',
        'credit_only' => 'تەنها پسووڵەکانی قەرز نیشان بدە',
        'no_purchases' => 'هیچ کڕینێک نەدۆزرایەوە',
        'no_debt_returns' => 'هیچ گەڕاندنەوەیەکی قەرز نەدۆزرایەوە',
        'transfer' => 'FIB یان FastPay',
        'other' => 'هی تر'
    ],
    'ar' => [
        'title' => 'سجل العميل',
        'company_name' => 'مخزن احمد و أشكان',
        'company_address' => 'العنوان: السليمانية - مخازن الغرفة التجارية - مخزن 288',
        'company_phone' => 'رقم الهاتف: 5678 123 0770',
        'customer_history' => 'سجل معاملات العميل',
        'date' => 'التاريخ',
        'customer_name' => 'اسم العميل',
        'phone' => 'رقم الهاتف',
        'address' => 'العنوان',
        'debt_status' => 'حالة الدين',
        'unknown' => 'غير معروف',
        'debt_amount' => 'دينار مدين',
        'advance_amount' => 'دينار مقدم',
        'balanced' => 'متوازن',
        'purchase_history' => 'سجل المشتريات',
        'invoice_number' => 'رقم الفاتورة',
        'items_count' => 'عدد المواد',
        'total_quantity' => 'الكمية الإجمالية',
        'total_price' => 'السعر الإجمالي',
        'payment_type' => 'نوع الدفع',
        'cash' => 'نقداً',
        'credit' => 'دين',
        'paid_amount' => 'المبلغ المدفوع',
        'remaining_amount' => 'المبلغ المتبقي',
        'debt_return_history' => 'سجل تسديد الديون',
        'returned_amount' => 'المبلغ المسدد',
        'payment_method' => 'طريقة الدفع',
        'notes' => 'ملاحظات',
        'total_purchases' => 'إجمالي المشتريات',
        'total_debt_returns' => 'إجمالي تسديد الديون',
        'debt_status_title' => 'حالة الدين',
        'debtor' => 'مدين',
        'advance' => 'مقدم',
        'footer_note' => 'هذا الإيصال للمتابعة فقط ولا يعتبر إيصالاً رسمياً',
        'print' => 'طباعة',
        'filter_title' => 'تصفية سجل العميل حسب التاريخ',
        'from_date' => 'من تاريخ',
        'to_date' => 'إلى تاريخ',
        'reset' => 'إعادة تعيين',
        'credit_only' => 'عرض الفواتير الآجلة فقط',
        'no_purchases' => 'لم يتم العثور على مشتريات',
        'no_debt_returns' => 'لم يتم العثور على تسديدات للديون',
        'transfer' => 'FIB أو FastPay',
        'other' => 'أخرى'
    ]
];

// Get the translations for the current language
$t = $translations[$lang];

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

// Get start and end dates from URL parameters if provided
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Check if customer_id or transaction_id is provided
$customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$transactionId = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;

// If transaction_id is provided, get the customer_id from it
if ($transactionId > 0 && $customerId == 0) {
    $stmt = $conn->prepare("SELECT customer_id FROM debt_transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        $customerId = $transaction['customer_id'];
    }
}

if ($customerId == 0) {
    die("کڕیار نەدۆزرایەوە");
}

// Get customer details
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die("کڕیار نەدۆزرایەوە");
}

// Apply date filters if provided
$dateFilterCondition = "";
$dateFilterParams = [$customerId];

if (!empty($startDate)) {
    $dateFilterCondition .= " AND s.date >= ?";
    $dateFilterParams[] = $startDate;
}

if (!empty($endDate)) {
    $dateFilterCondition .= " AND s.date <= ?";
    $dateFilterParams[] = $endDate;
}

// Check if credit only filter is applied
$creditOnly = isset($_GET['credit_only']) && $_GET['credit_only'] == '1';
if ($creditOnly) {
    $dateFilterCondition .= " AND s.payment_type = 'credit'";
}

// Get all sales for this customer
$salesQuery = "SELECT s.*, 
               COUNT(si.id) as items_count,
               SUM(si.quantity) as total_quantity,
               SUM(si.total_price) as invoice_total
               FROM sales s 
               JOIN sale_items si ON s.id = si.sale_id 
               WHERE s.customer_id = ? $dateFilterCondition
               GROUP BY s.id
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute($dateFilterParams);
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Apply date filters to payments query
$paymentDateFilterCondition = "";
$paymentDateFilterParams = [$customerId];

if (!empty($startDate)) {
    $paymentDateFilterCondition .= " AND dt.created_at >= ?";
    $paymentDateFilterParams[] = $startDate;
}

if (!empty($endDate)) {
    $paymentDateFilterCondition .= " AND dt.created_at <= ?";
    $paymentDateFilterParams[] = $endDate;
}

// Get all debt collections (payments) for this customer
$paymentsQuery = "SELECT dt.*, 
                  JSON_UNQUOTE(JSON_EXTRACT(dt.notes, '$.payment_method')) as payment_method,
                  JSON_UNQUOTE(JSON_EXTRACT(dt.notes, '$.notes')) as payment_notes
                  FROM debt_transactions dt
                  WHERE dt.customer_id = ? AND dt.transaction_type = 'collection' $paymentDateFilterCondition
                  ORDER BY dt.created_at DESC";
$paymentsStmt = $conn->prepare($paymentsQuery);
$paymentsStmt->execute($paymentDateFilterParams);
$payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalSales = 0;
$totalPaid = 0;
$totalDebt = $customer['debit_on_business'];

foreach ($sales as $sale) {
    $totalSales += $sale['invoice_total'];
}

foreach ($payments as $payment) {
    $totalPaid += $payment['amount'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'rtl'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - <?php echo htmlspecialchars($customer['name']); ?></title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .receipt-title {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .receipt-date {
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

        .section-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
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
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            text-align: center;
            font-size: 14px;
        }

        .items-table tr:nth-child(even) {
            background-color: var(--primary-light);
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .badge-success {
            background-color: var(--success-color);
        }

        .badge-warning {
            background-color: var(--warning-color);
        }

        .badge-danger {
            background-color: var(--danger-color);
        }

        .badge-info {
            background-color: var(--info-color);
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
            text-align: center;
        }

        .footer-notes {
            margin-top: 20px;
            color: var(--text-muted);
            font-size: 14px;
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
            }
            .print-button {
                display: none;
            }
            @page {
                size: A4;
                margin: 0;
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
        }

        /* Filter form styles */
        .filter-container {
            max-width: 21cm;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filter-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: 'Rabar', sans-serif;
        }
        
        .btn-reset {
            background-color: var(--bg-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-reset:hover {
            background-color: #e9ecef;
        }
        
        /* Custom checkbox style */
        .form-check {
            padding: 10px 30px 10px 0;
            background-color: rgba(255, 187, 85, 0.1);
            border-radius: 5px;
            border: 1px solid var(--warning-color);
            cursor: pointer;
        }
        
        .form-check-input {
            margin-left: 10px;
        }
        
        .form-check-label {
            cursor: pointer;
            font-weight: bold;
            color: var(--warning-color);
        }

        /* Language selector styles */
        .language-selector {
            position: fixed;
            top: 20px;
            <?php echo $lang === 'ar' ? 'left' : 'right'; ?>: 80px;
            z-index: 1000;
        }
        
        .language-btn {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            margin-<?php echo $lang === 'ar' ? 'right' : 'left'; ?>: 10px;
        }
        
        .language-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .language-btn.active {
            background: var(--success-color);
        }
    </style>
</head>
<body>
    <?php if (!$printMode): ?>
    <!-- Language Selector -->
    <div class="language-selector">
        <button onclick="changeLanguage('ku')" class="language-btn <?php echo $lang === 'ku' ? 'active' : ''; ?>">کوردی</button>
        <button onclick="changeLanguage('ar')" class="language-btn <?php echo $lang === 'ar' ? 'active' : ''; ?>">عربي</button>
    </div>
    
    <!-- Date Filter Form -->
    <div class="filter-container">
        <div class="filter-title"><?php echo $t['filter_title']; ?></div>
        <form id="dateFilterForm" class="filter-form">
            <!-- Preserve customer_id or transaction_id -->
            <?php if ($customerId > 0): ?>
            <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
            <?php elseif ($transactionId > 0): ?>
            <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
            <?php endif; ?>
            
            <!-- Preserve language selection -->
            <input type="hidden" name="lang" value="<?php echo $lang; ?>">
            
            <div class="form-group">
                <label for="start_date" class="form-label"><?php echo $t['from_date']; ?></label>
                <input type="date" id="start_date" name="start_date" class="form-control date-filter" value="<?php echo $startDate; ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date" class="form-label"><?php echo $t['to_date']; ?></label>
                <input type="date" id="end_date" name="end_date" class="form-control date-filter" value="<?php echo $endDate; ?>">
            </div>
            
            <div class="form-group">
                <label for="credit_only" class="form-label d-block">&nbsp;</label>
                <div class="form-check">
                    <input type="checkbox" id="credit_only" name="credit_only" value="1" class="form-check-input credit-filter" <?php echo $creditOnly ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="credit_only"><?php echo $t['credit_only']; ?></label>
                </div>
            </div>
            
            <div class="form-group" style="flex: 0 0 auto;">
                <a href="?<?php echo $customerId > 0 ? 'customer_id=' . $customerId : 'transaction_id=' . $transactionId; ?>&lang=<?php echo $lang; ?>" class="btn-reset">
                    <i class="fas fa-redo"></i> <?php echo $t['reset']; ?>
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="receipt-container">
        <header class="receipt-header">
            <div class="logo-section">
                <div class="company-info">
                    <h1><?php echo $t['company_name']; ?></h1>
                    <p><?php echo $t['company_address']; ?></p>
                    <p><?php echo $t['company_phone']; ?></p>
                </div>
            </div>
            <div class="invoice-details">
                <div class="receipt-title"><?php echo $t['customer_history']; ?></div>
                <div class="receipt-date"><?php echo $t['date']; ?>: <?php echo date('Y-m-d'); ?></div>
            </div>
        </header>

        <section class="customer-info">
            <div class="info-group">
                <div class="info-label"><?php echo $t['customer_name']; ?></div>
                <div class="info-value"><?php echo htmlspecialchars($customer['name']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label"><?php echo $t['phone']; ?></div>
                <div class="info-value"><?php echo htmlspecialchars($customer['phone1']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label"><?php echo $t['address']; ?></div>
                <div class="info-value"><?php echo htmlspecialchars($customer['address'] ?: $t['unknown']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label"><?php echo $t['debt_status']; ?></div>
                <div class="info-value">
                    <?php if ($customer['debit_on_business'] > 0): ?>
                        <span style="color: var(--danger-color);"><?php echo number_format($customer['debit_on_business']); ?> <?php echo $t['debt_amount']; ?></span>
                    <?php elseif ($customer['debit_on_business'] < 0): ?>
                        <span style="color: var(--success-color);"><?php echo number_format(abs($customer['debit_on_business'])); ?> <?php echo $t['advance_amount']; ?></span>
                    <?php else: ?>
                        <span style="color: var(--info-color);"><?php echo $t['balanced']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="items-section">
            <h2 class="section-title">
                <?php echo $t['purchase_history']; ?>
                <?php if ($creditOnly): ?>
                <span class="badge badge-warning" style="font-size: 14px; vertical-align: middle; margin-right: 10px;"><?php echo $t['credit_only']; ?></span>
                <?php endif; ?>
            </h2>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo $t['invoice_number']; ?></th>
                            <th><?php echo $t['date']; ?></th>
                            <th><?php echo $t['items_count']; ?></th>
                            <th><?php echo $t['total_quantity']; ?></th>
                            <th><?php echo $t['total_price']; ?></th>
                            <th><?php echo $t['payment_type']; ?></th>
                            <th><?php echo $t['paid_amount']; ?></th>
                            <th><?php echo $t['remaining_amount']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sales) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                    <td><?php echo date('Y/m/d', strtotime($sale['date'])); ?></td>
                                    <td><?php echo $sale['items_count']; ?></td>
                                    <td><?php echo $sale['total_quantity']; ?></td>
                                    <td><?php echo number_format($sale['invoice_total']); ?> د.ع</td>
                                    <td>
                                        <?php if ($sale['payment_type'] == 'cash'): ?>
                                            <span class="badge badge-success"><?php echo $t['cash']; ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?php echo $t['credit']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($sale['paid_amount']); ?> د.ع</td>
                                    <td><?php echo number_format($sale['remaining_amount']); ?> د.ع</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;"><?php echo $t['no_purchases']; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title"><?php echo $t['debt_return_history']; ?></h2>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo $t['date']; ?></th>
                            <th><?php echo $t['returned_amount']; ?></th>
                            <th><?php echo $t['payment_method']; ?></th>
                            <th><?php echo $t['notes']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payments) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo date('Y/m/d', strtotime($payment['created_at'])); ?></td>
                                    <td style="color: var(--success-color);"><?php echo number_format($payment['amount']); ?> د.ع</td>
                                    <td>
                                        <?php
                                        switch ($payment['payment_method']) {
                                            case 'cash':
                                                echo '<span class="badge badge-success">' . $t['cash'] . '</span>';
                                                break;
                                            case 'transfer':
                                                echo '<span class="badge badge-info">' . $t['transfer'] . '</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">' . $t['other'] . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['payment_notes'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;"><?php echo $t['no_debt_returns']; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="summary-label"><?php echo $t['total_purchases']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($totalSales); ?> د.ع</td>
                </tr>
                <tr>
                    <td class="summary-label"><?php echo $t['total_debt_returns']; ?>:</td>
                    <td class="summary-value"><?php echo number_format($totalPaid); ?> د.ع</td>
                </tr>
                <tr>
                    <td class="summary-label"><?php echo $t['debt_status_title']; ?>:</td>
                    <td class="summary-value">
                        <?php if ($totalDebt > 0): ?>
                            <span style="color: var(--danger-color);"><?php echo number_format($totalDebt); ?> د.ع <?php echo $t['debtor']; ?></span>
                        <?php elseif ($totalDebt < 0): ?>
                            <span style="color: var(--success-color);"><?php echo number_format(abs($totalDebt)); ?> د.ع <?php echo $t['advance']; ?></span>
                        <?php else: ?>
                            <span style="color: var(--info-color);"><?php echo $t['balanced']; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </section>

        <footer class="receipt-footer">
            <div class="footer-notes">
                <?php echo $t['footer_note']; ?>
            </div>
            
            <p class="mt-3">© <?php echo $t['company_name']; ?> - <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <?php if (!$printMode): ?>
    <button class="print-button" onclick="printWithFilters()"><?php echo $t['print']; ?></button>
    
    <script>
        function changeLanguage(newLang) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('lang', newLang);
            window.location.href = currentUrl.toString();
        }
        
        function printWithFilters() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('print', 'true');
            
            // Preserve credit_only filter if it's checked
            const creditOnlyCheck = document.getElementById('credit_only');
            if (creditOnlyCheck && creditOnlyCheck.checked) {
                currentUrl.searchParams.set('credit_only', '1');
            }
            
            // Preserve language selection
            currentUrl.searchParams.set('lang', '<?php echo $lang; ?>');
            
            // Open in new tab and print
            const printWindow = window.open(currentUrl.toString(), '_blank');
            if (printWindow) {
                printWindow.addEventListener('load', function() {
                    printWindow.print();
                });
            }
        }
        
        // Auto filter when date changes
        const dateInputs = document.querySelectorAll('.date-filter');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                submitFilterForm();
            });
        });
        
        // Auto filter when credit checkbox changes
        const creditFilter = document.getElementById('credit_only');
        if (creditFilter) {
            creditFilter.addEventListener('change', function() {
                submitFilterForm();
            });
        }
        
        // Function to submit the filter form
        function submitFilterForm() {
            const form = document.getElementById('dateFilterForm');
            
            // Create a FormData object from our form
            const formData = new FormData(form);
            
            // Build the new URL with parameters
            let url = '?';
            for (let pair of formData.entries()) {
                url += `${pair[0]}=${pair[1]}&`;
            }
            
            // Remove trailing & and redirect
            url = url.slice(0, -1);
            window.location.href = url;
        }
    </script>
    <?php endif; ?>

    <!-- Add JavaScript for auto-filtering -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto filter when date changes
        const dateInputs = document.querySelectorAll('.date-filter');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                submitFilterForm();
            });
        });
        
        // Auto filter when credit checkbox changes
        const creditFilter = document.getElementById('credit_only');
        if (creditFilter) {
            creditFilter.addEventListener('change', function() {
                submitFilterForm();
            });
        }
        
        // Function to submit the filter form
        function submitFilterForm() {
            const form = document.getElementById('dateFilterForm');
            
            // Create a FormData object from our form
            const formData = new FormData(form);
            
            // Build the new URL with parameters
            let url = '?';
            for (let pair of formData.entries()) {
                url += `${pair[0]}=${pair[1]}&`;
            }
            
            // Remove trailing & and redirect
            url = url.slice(0, -1);
            window.location.href = url;
        }
    });
    </script>
</body>
</html> 