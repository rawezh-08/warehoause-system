<?php
// Include database connection
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if we're in print mode (no filters shown)
$printMode = isset($_GET['print']) && $_GET['print'] == 'true';

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
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مێژووی کڕیار - <?php echo htmlspecialchars($customer['name']); ?></title>
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
    </style>
</head>
<body>
    <?php if (!$printMode): ?>
    <!-- Date Filter Form -->
    <div class="filter-container">
        <div class="filter-title">فلتەرکردنی مێژووی کڕیار بە پێی بەروار</div>
        <form id="dateFilterForm" class="filter-form">
            <!-- Preserve customer_id or transaction_id -->
            <?php if ($customerId > 0): ?>
            <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
            <?php elseif ($transactionId > 0): ?>
            <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="start_date" class="form-label">لە بەرواری</label>
                <input type="date" id="start_date" name="start_date" class="form-control date-filter" value="<?php echo $startDate; ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date" class="form-label">هەتا بەرواری</label>
                <input type="date" id="end_date" name="end_date" class="form-control date-filter" value="<?php echo $endDate; ?>">
            </div>
            
            <div class="form-group" style="flex: 0 0 auto;">
                <a href="?<?php echo $customerId > 0 ? 'customer_id=' . $customerId : 'transaction_id=' . $transactionId; ?>" class="btn-reset">
                    <i class="fas fa-redo"></i> ڕیسێت
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="receipt-container">
        <header class="receipt-header">
            <div class="logo-section">
                <img src="../../assets/images/company-logo.svg" alt="کۆگای ئەشکان" class="company-logo">
                <div class="company-info">
                    <h1>کۆگای ئەشکان</h1>
                    <p>ناونیشان: سلێمانی - کۆگاکانی غرفة تجارة - کۆگای 288</p>
                    <p>ژمارە تەلەفۆن: 5678 123 0770</p>
                </div>
            </div>
            <div class="invoice-details">
                <div class="receipt-title">مێژووی مامەڵەکانی کڕیار</div>
                <div class="receipt-date">بەروار: <?php echo date('Y-m-d'); ?></div>
            </div>
        </header>

        <section class="customer-info">
            <div class="info-group">
                <div class="info-label">ناوی کڕیار</div>
                <div class="info-value"><?php echo htmlspecialchars($customer['name']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">ژمارەی مۆبایل</div>
                <div class="info-value"><?php echo htmlspecialchars($customer['phone1']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">ناونیشان</div>
                <div class="info-value"><?php echo htmlspecialchars($customer['address'] ?: 'نادیار'); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">بارودۆخی قەرز</div>
                <div class="info-value">
                    <?php if ($customer['debit_on_business'] > 0): ?>
                        <span style="color: var(--danger-color);"><?php echo number_format($customer['debit_on_business']); ?> دینار قەرزدار</span>
                    <?php elseif ($customer['debit_on_business'] < 0): ?>
                        <span style="color: var(--success-color);"><?php echo number_format(abs($customer['debit_on_business'])); ?> دینار پێشەکی</span>
                    <?php else: ?>
                        <span style="color: var(--info-color);">هاوسەنگ</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="items-section">
            <h2 class="section-title">مێژووی کڕینەکان</h2>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ژمارەی پسووڵە</th>
                            <th>بەروار</th>
                            <th>هەژمار کاڵاکان</th>
                            <th>کۆی بڕ</th>
                            <th>کۆی نرخ</th>
                            <th>جۆری پارەدان</th>
                            <th>پارەی دراو</th>
                            <th>پارەی ماوە</th>
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
                                            <span class="badge badge-success">نەقد</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">قەرز</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($sale['paid_amount']); ?> د.ع</td>
                                    <td><?php echo number_format($sale['remaining_amount']); ?> د.ع</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">هیچ کڕینێک نەدۆزرایەوە</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title">مێژووی گەڕاندنەوەی قەرز</h2>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>بەروار</th>
                            <th>بڕی گەڕاوە</th>
                            <th>شێوازی پارەدان</th>
                            <th>تێبینی</th>
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
                                                echo '<span class="badge badge-success">نەقد</span>';
                                                break;
                                            case 'transfer':
                                                echo '<span class="badge badge-info">FIB یان FastPay</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">هی تر</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['payment_notes'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">هیچ گەڕاندنەوەیەکی قەرز نەدۆزرایەوە</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">کۆی کڕینەکان:</td>
                    <td class="summary-value"><?php echo number_format($totalSales); ?> د.ع</td>
                </tr>
                <tr>
                    <td class="summary-label">کۆی گەڕاندنەوەی قەرز:</td>
                    <td class="summary-value"><?php echo number_format($totalPaid); ?> د.ع</td>
                </tr>
                <tr>
                    <td class="summary-label">دۆخی قەرز:</td>
                    <td class="summary-value">
                        <?php if ($totalDebt > 0): ?>
                            <span style="color: var(--danger-color);"><?php echo number_format($totalDebt); ?> د.ع قەرزدار</span>
                        <?php elseif ($totalDebt < 0): ?>
                            <span style="color: var(--success-color);"><?php echo number_format(abs($totalDebt)); ?> د.ع پێشەکی</span>
                        <?php else: ?>
                            <span style="color: var(--info-color);">هاوسەنگ</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </section>

        <footer class="receipt-footer">
            <div class="footer-notes">
                ئەم وەسڵە تەنها بۆ مەبەستی بەدواداچوونە و بە وەسڵی فەرمی دانانرێت
            </div>
            
            <p class="mt-3">© کۆگای ئەشکان - <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <?php if (!$printMode): ?>
    <button class="print-button" onclick="printWithFilters()">چاپکردن</button>
    
    <script>
        function printWithFilters() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('print', 'true');
            
            // Open in new tab and print
            const printWindow = window.open(currentUrl.toString(), '_blank');
            if (printWindow) {
                printWindow.addEventListener('load', function() {
                    printWindow.print();
                });
            }
        }
        
        // Auto open print dialog when page loads in print mode
        <?php if ($printMode): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000); // Short delay to ensure everything is loaded
        };
        <?php endif; ?>
    </script>
    <?php endif; ?>

    <!-- Add JavaScript for auto-filtering -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto filter when date changes
        const dateInputs = document.querySelectorAll('.date-filter');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
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
            });
        });
    });
    </script>
</body>
</html> 