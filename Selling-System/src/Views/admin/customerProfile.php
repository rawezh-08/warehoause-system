<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if customer ID is provided
$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get customer details
$customerQuery = "SELECT * FROM customers WHERE id = :id";
$customerStmt = $conn->prepare($customerQuery);
$customerStmt->bindParam(':id', $customerId);
$customerStmt->execute();
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

// If customer not found, redirect to customers list page
if (!$customer) {
    header("Location: customers.php");
    exit;
}

// Get all sales for this customer with detailed product information
$salesQuery = "SELECT s.*, 
               si.quantity, si.unit_type, si.pieces_count, si.unit_price, si.total_price,
               p.name as product_name, p.code as product_code,
               SUM(si.total_price) as sale_total,
               (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as invoice_total
               FROM sales s 
               JOIN sale_items si ON s.id = si.sale_id 
               JOIN products p ON si.product_id = p.id
               WHERE s.customer_id = :customer_id 
               GROUP BY s.id, si.id, p.id
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->bindParam(':customer_id', $customerId);
$salesStmt->execute();
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all debt transactions for this customer (credit sales and payments)
$debtQuery = "SELECT dt.*, 
             CASE 
                WHEN dt.transaction_type = 'sale' THEN (SELECT invoice_number FROM sales WHERE id = dt.reference_id)
                ELSE '' 
             END as invoice_number,
             s.payment_type
             FROM debt_transactions dt
             LEFT JOIN sales s ON dt.reference_id = s.id AND dt.transaction_type = 'sale'
             WHERE dt.customer_id = :customer_id 
             ORDER BY dt.created_at DESC";
$debtStmt = $conn->prepare($debtQuery);
$debtStmt->bindParam(':customer_id', $customerId);
$debtStmt->execute();
$debtTransactions = $debtStmt->fetchAll(PDO::FETCH_ASSOC);

// Filter to get only credit transactions
$creditTransactions = array_filter($debtTransactions, function ($transaction) {
    // Include only sales with payment_type = 'credit' or manual payments/collections
    return ($transaction['transaction_type'] == 'sale' && $transaction['payment_type'] == 'credit')
        || $transaction['transaction_type'] == 'payment'
        || $transaction['transaction_type'] == 'collection';
});

// Calculate additional metrics
$totalReturns = 0;
$totalSales = 0;
$monthlySales = 0;

foreach ($sales as $sale) {
    $totalSales += $sale['sale_total'];
    if (date('Y-m', strtotime($sale['date'])) === date('Y-m')) {
        $monthlySales += $sale['sale_total'];
    }
}

foreach ($debtTransactions as $debtTransaction) {
    if ($debtTransaction['transaction_type'] === 'collection') {
        $totalReturns += $debtTransaction['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی کڕیار/<?php echo htmlspecialchars($customer['name']); ?> - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/staff.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Custom styles for this page -->
    <style>
        /* Transparent search input */
        .table-search-input {
            background-color: transparent !important;
            border: 1px solid #dee2e6;
        }

        .custom-table td,
        th {
            white-space: normal;
            word-wrap: break-word;
            vertical-align: middle;
            padding: 0.75rem;
        }

        .custom-table td {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .custom-table th {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Adjust pagination display for many pages */
        .pagination-numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            max-width: 300px;
            overflow: hidden;
        }

        .pagination-numbers .btn {
            min-width: 35px;
            height: 35px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: #6c757d;
        }

        /* RTL Toast Container Styles */
        .toast-container-rtl {
            right: 0 !important;
            left: auto !important;
        }

        .toast-container-rtl .swal2-toast {
            margin-right: 1em !important;
            margin-left: 0 !important;
        }

        .toast-container-rtl .swal2-toast .swal2-title {
            text-align: right !important;
        }

        .toast-container-rtl .swal2-toast .swal2-icon {
            margin-right: 0 !important;
            margin-left: 0.5em !important;
        }

        /* Customer info card */
        .customer-info-card {
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .customer-info-item {
            margin-bottom: 10px;
        }

        .customer-info-label {
            font-weight: bold;
            color: #6c757d;
        }

        .customer-info-value {
            font-weight: normal;
        }

        .debt-badge {
            font-size: 1.2rem;
            padding: 8px 15px;
        }

        /* Summary Cards Styles */
        .summary-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: default;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .summary-card .card-body {
            padding: 1.5rem;
        }

        .summary-card .card-title {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .summary-card .card-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }

        .summary-card .icon-bg {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            margin-right: 1rem;
        }

        /* Custom Tabs Styling */
        .custom-tabs {
            border-bottom: 1px solid #dee2e6;
            background-color: #fff;
            padding: 0 1rem;
        }

        .custom-tabs .nav-item {
            margin-bottom: -1px;
        }

        .custom-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            background-color: transparent;
            transition: all 0.2s ease;
            position: relative;
            margin-right: 5px;
            border-radius: 0;
            padding: 0.75rem 1.25rem;
        }

        .custom-tabs .nav-link:hover {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.04);
            border: none;
        }

        .custom-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            font-weight: 600;
            border: none;
            box-shadow: none;
        }

        .custom-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #0d6efd;
        }

        .custom-tabs .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 5px;
        }

        /* Tab Content Styling */
        .tab-content {
            background-color: transparent;
        }

        .tab-pane {
            padding: 0;
        }

        .card-header.bg-transparent {
            background-color: #fff !important;
        }

        /* Remove any translucency/blurriness */
        .nav-tabs .nav-link {
            -webkit-backdrop-filter: none;
            backdrop-filter: none;
        }

        .card {
            -webkit-backdrop-filter: none;
            backdrop-filter: none;
        }

        /* Improve the card header containing tabs */
        .card-header {
            padding: 0;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
        }

        /* Add consistent shadow */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        /* Reset button animation */
        .fa-spin {
            animation: fa-spin 0.5s linear;
        }

        #resetFilters:hover {
            background-color: #e2e6ea;
        }

        #resetFilters:active {
            transform: scale(0.95);
        }

        /* Draft Receipts Styles */
        .draft-badge {
            background-color: #FFC107;
            color: #333;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }

        .action-buttons .btn {
            margin: 0 2px;
        }

        .action-buttons .btn i {
            font-size: 0.875rem;
        }

        .table td {
            vertical-align: middle;
        }

        /* Responsive Pagination */
        @media (max-width: 768px) {
            .pagination-numbers {
                max-width: 200px;
            }

            .pagination-info {
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main content -->
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3 class="page-title">پڕۆفایلی کڕیار/<?php echo htmlspecialchars($customer['name']); ?></h3>
                        <div>
                            <a href="addStaff.php?tab=customer" class="btn btn-primary me-2">
                                <i class="fas fa-plus me-2"></i> زیادکردنی کڕیاری نوێ
                            </a>
                            <a href="customers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-right me-2"></i> گەڕانەوە بۆ لیستی کڕیارەکان
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-danger me-3">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">کۆی قەرز</h5>
                                    <p class="card-value mb-0">
                                        <?php echo number_format($customer['debit_on_business']); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-success me-3">
                                    <i class="fas fa-undo"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">کۆی گەڕانەوە</h5>
                                    <p class="card-value mb-0"><?php echo number_format($totalReturns); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-primary me-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">کۆی فرۆشتن</h5>
                                    <p class="card-value mb-0"><?php echo number_format($totalSales); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-warning me-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">کۆی فرۆشتن لەم مانگە</h5>
                                    <p class="card-value mb-0"><?php echo number_format($monthlySales); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-title mb-0">فلتەرکردن</h6>
                                    <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-undo me-1"></i> ڕیسێت
                                    </button>
                                </div>
                                <form id="salesFilterForm" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="paymentTypeFilter" class="form-label">جۆری پارەدان</label>
                                        <select class="form-select auto-filter" id="paymentTypeFilter">
                                            <option value="">هەموو جۆرەکان</option>
                                            <option value="cash">نەقد</option>
                                            <option value="credit">قەرز</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="startDate" class="form-label">لە بەرواری</label>
                                        <input type="date" class="form-control auto-filter" id="startDate">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="endDate" class="form-label">بۆ بەرواری</label>
                                        <input type="date" class="form-control auto-filter" id="endDate">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card with Tabs and Content -->
            <div class="card shadow-sm">
                <!-- Tabs Navigation -->
                <div class="card-header bg-transparent p-0 border-bottom-0">
                    <ul class="nav nav-tabs custom-tabs border-0" id="customerTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="sales-tab" data-bs-toggle="tab"
                                data-bs-target="#sales-content" type="button" role="tab" aria-controls="sales-content"
                                aria-selected="true">
                                <i class="fas fa-shopping-cart"></i>کڕینەکان
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="debt-tab" data-bs-toggle="tab" data-bs-target="#debt-content"
                                type="button" role="tab" aria-controls="debt-content" aria-selected="false">
                                <i class="fas fa-money-bill-wave"></i>مامەڵەکانی قەرز
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="debt-return-tab" data-bs-toggle="tab"
                                data-bs-target="#debt-return-content" type="button" role="tab"
                                aria-controls="debt-return-content" aria-selected="false">
                                <i class="fas fa-hand-holding-dollar"></i>وەرگرتنەوەی قەرز
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="debt-history-tab" data-bs-toggle="tab"
                                data-bs-target="#debt-history-content" type="button" role="tab"
                                aria-controls="debt-history-content" aria-selected="false">
                                <i class="fas fa-history"></i>مێژووی گەڕاندنەوەی قەرز
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="advance-payment-tab" data-bs-toggle="tab"
                                data-bs-target="#advance-payment-content" type="button" role="tab"
                                aria-controls="advance-payment-content" aria-selected="false">
                                <i class="fas fa-coins"></i>پارەی پێشەکی
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="advance-payment-history-tab" data-bs-toggle="tab"
                                data-bs-target="#advance-payment-history-content" type="button" role="tab"
                                aria-controls="advance-payment-history-content" aria-selected="false">
                                <i class="fas fa-history"></i>مێژووی پارەی پێشەکی
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="draft-receipts-tab" data-bs-toggle="tab" data-bs-target="#draft-receipts-content" type="button" role="tab" aria-controls="draft-receipts-content" aria-selected="false">
                                <i class="fas fa-file-alt"></i>ڕەشنووسەکان
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Sales History Tab -->
                        <div class="tab-pane fade show active" id="sales-content" role="tabpanel"
                            aria-labelledby="sales-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">مێژووی کڕینەکان</h5>
                                <button class="btn btn-sm btn-outline-primary refresh-btn">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>

                            <div class="table-container">
                                <!-- Table Controls -->
                                <div class="table-controls mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page">
                                                <label class="me-2">نیشاندان:</label>
                                                <div class="custom-select-wrapper">
                                                    <select id="salesRecordsPerPage"
                                                        class="form-select form-select-sm rounded-pill">
                                                        <option value="5">5</option>
                                                        <option value="10" selected>10</option>
                                                        <option value="25">25</option>
                                                        <option value="50">50</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="salesTableSearch"
                                                        class="form-control rounded-pill-start table-search-input"
                                                        placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table Content -->
                                <div class="table-responsive">
                                    <table id="salesHistoryTable" class="table table-bordered custom-table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>ژمارەی پسووڵە</th>
                                                <th>بەروار</th>
                                                <th>ناوی کاڵا</th>
                                                <th>کۆدی کاڵا</th>
                                                <th>بڕ</th>
                                                <th>یەکە</th>
                                                <th>نرخی تاک</th>
                                                <th>نرخی گشتی</th>
                                                <th>کرێی گواستنەوە</th>
                                                <th>خەرجی تر</th>
                                                <th>داشکاندن</th>
                                                <th>جۆری پارەدان</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($sales) > 0): ?>
                                                <?php 
                                                $counter = 1;
                                                $processedInvoices = array();
                                                foreach ($sales as $sale): 
                                                    if (!in_array($sale['invoice_number'], $processedInvoices)):
                                                        $processedInvoices[] = $sale['invoice_number'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                                        <td><?php echo date('Y/m/d', strtotime($sale['date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($sale['product_code']); ?></td>
                                                        <td><?php echo number_format($sale['quantity']); ?></td>
                                                        <td>
                                                            <?php
                                                            switch ($sale['unit_type']) {
                                                                case 'piece':
                                                                    echo 'دانە';
                                                                    break;
                                                                case 'box':
                                                                    echo 'کارتۆن';
                                                                    break;
                                                                case 'set':
                                                                    echo 'سێت';
                                                                    break;
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo number_format($sale['unit_price']); ?> دینار</td>
                                                        <td><?php echo number_format($sale['total_price']); ?> دینار</td>
                                                        <td><?php echo number_format($sale['shipping_cost'] ?? 0); ?> دینار</td>
                                                        <td><?php echo number_format($sale['other_costs'] ?? 0); ?> دینار</td>
                                                        <td><?php echo number_format($sale['discount'] ?? 0); ?> دینار</td>
                                                        <td>
                                                            <?php if ($sale['payment_type'] == 'cash'): ?>
                                                                <span class="badge bg-success">نەقد</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">قەرز</span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (isset($sale['is_delivery']) && $sale['is_delivery'] == 1): ?>
                                                                <span class="badge bg-info ms-1">گەیاندن</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="<?php echo (isset($sale['is_delivery']) && $sale['is_delivery'] == 1) ? 
                                                                    '../../Views/receipt/delivery_receipt.php?sale_id=' . $sale['id'] : 
                                                                    '../../Views/receipt/print_receipt.php?sale_id=' . $sale['id']; ?>"
                                                                    class="btn btn-sm btn-outline-success rounded-circle"
                                                                    title="چاپکردن">
                                                                    <i class="fas fa-print"></i>
                                                                </a>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                    data-id="<?php echo $sale['id']; ?>"
                                                                    title="دەستکاری پسووڵە">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-invoice-items"
                                                                    data-invoice="<?php echo $sale['invoice_number']; ?>"
                                                                    title="بینینی هەموو کاڵاکان">
                                                                    <i class="fas fa-list"></i>
                                                                </button>
                                                                <!-- Add return button -->
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-warning rounded-circle return-btn"
                                                                    data-id="<?php echo $sale['id']; ?>"
                                                                    title="گەڕاندنەوەی کاڵا">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                                <?php
                                                                // Check if sale can be returned (no payments made)
                                                                $canReturn = true;
                                                                $canDelete = true;
                                                                
                                                                // Check for debt transactions
                                                                foreach ($debtTransactions as $transaction) {
                                                                    if ($transaction['reference_id'] == $sale['id'] && 
                                                                        ($transaction['transaction_type'] == 'collection' || 
                                                                         $transaction['transaction_type'] == 'payment')) {
                                                                        $canReturn = false;
                                                                        $canDelete = false;
                                                                        break;
                                                                    }
                                                                }
                                                                
                                                                // Check for product returns
                                                                $returnQuery = "SELECT COUNT(*) as count FROM product_returns WHERE receipt_id = :sale_id AND receipt_type = 'selling'";
                                                                $returnStmt = $conn->prepare($returnQuery);
                                                                $returnStmt->bindParam(':sale_id', $sale['id']);
                                                                $returnStmt->execute();
                                                                $returnCount = $returnStmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                
                                                                if ($returnCount > 0) {
                                                                    $canDelete = false;
                                                                }
                                                                
                                                                if ($canDelete): ?>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger rounded-circle delete-sale"
                                                                    data-id="<?php echo $sale['id']; ?>"
                                                                    data-invoice="<?php echo $sale['invoice_number']; ?>"
                                                                    title="سڕینەوە">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="11" class="text-center">هیچ کڕینێک نەدۆزرایەوە</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Table Pagination -->
                                <div class="table-pagination mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6 mb-2 mb-md-0">
                                            <div class="pagination-info">
                                                نیشاندانی <span id="salesStartRecord">1</span> تا <span
                                                    id="salesEndRecord">10</span> لە کۆی <span
                                                    id="salesTotalRecords"><?php echo count($sales); ?></span> تۆمار
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="pagination-controls d-flex justify-content-md-end">
                                                <button id="salesPrevPageBtn"
                                                    class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                                <div id="salesPaginationNumbers" class="pagination-numbers d-flex">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                                <button id="salesNextPageBtn"
                                                    class="btn btn-sm btn-outline-primary rounded-circle">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Debt Transactions Tab -->
                        <div class="tab-pane fade" id="debt-content" role="tabpanel" aria-labelledby="debt-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">مامەڵەکانی قەرز</h5>
                                <button class="btn btn-sm btn-outline-primary refresh-btn">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>

                            <div class="table-container">
                                <!-- Table Controls -->
                                <div class="table-controls mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page">
                                                <label class="me-2">نیشاندان:</label>
                                                <div class="custom-select-wrapper">
                                                    <select id="debtRecordsPerPage"
                                                        class="form-select form-select-sm rounded-pill">
                                                        <option value="5">5</option>
                                                        <option value="10" selected>10</option>
                                                        <option value="25">25</option>
                                                        <option value="50">50</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="debtTableSearch"
                                                        class="form-control rounded-pill-start table-search-input"
                                                        placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table Content -->
                                <div class="table-responsive">
                                    <table id="debtHistoryTable" class="table table-bordered custom-table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>بەروار</th>
                                                <th>ژمارەی پسووڵە</th>
                                                <th>جۆری مامەڵە</th>
                                                <th>بڕی پارە</th>
                                                <th>تێبینی</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($creditTransactions) > 0): ?>
                                                <?php 
                                                $counter = 1;
                                                $processedInvoices = array();
                                                foreach ($creditTransactions as $transaction): 
                                                    if ($transaction['transaction_type'] === 'sale' && !in_array($transaction['invoice_number'], $processedInvoices)):
                                                        $processedInvoices[] = $transaction['invoice_number'];
                                                ?>
                                                    <tr data-id="<?php echo $transaction['id']; ?>">
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo date('Y/m/d', strtotime($transaction['created_at'])); ?></td>
                                                        <td><?php echo !empty($transaction['invoice_number']) ? htmlspecialchars($transaction['invoice_number']) : '-'; ?></td>
                                                        <td>
                                                            <span class="badge bg-warning">فرۆشتن بە قەرز</span>
                                                        </td>
                                                        <td>
                                                                <span class="text-danger">
                                                                    <?php echo number_format($transaction['amount']); ?> دینار
                                                                </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            // Check if the notes are in JSON format
                                                            $notesObj = json_decode($transaction['notes'], true);
                                                            if (is_array($notesObj) && isset($notesObj['notes'])) {
                                                                // If it's a structured JSON with a notes field, display just that
                                                                echo !empty($notesObj['notes']) ? htmlspecialchars($notesObj['notes']) : '-';
                                                            } else {
                                                                // Otherwise display the original notes
                                                                echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-invoice-items"
                                                                    data-invoice="<?php echo $transaction['invoice_number']; ?>"
                                                                    title="بینینی هەموو کاڵاکان">
                                                                    <i class="fas fa-list"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">هیچ مامەڵەیەکی قەرز نەدۆزرایەوە</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Table Pagination -->
                                <div class="table-pagination mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6 mb-2 mb-md-0">
                                            <div class="pagination-info">
                                                نیشاندانی <span id="debtStartRecord">1</span> تا <span
                                                    id="debtEndRecord">10</span> لە کۆی <span
                                                    id="debtTotalRecords"><?php echo count($creditTransactions); ?></span>
                                                تۆمار
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="pagination-controls d-flex justify-content-md-end">
                                                <button id="debtPrevPageBtn"
                                                    class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                                <div id="debtPaginationNumbers" class="pagination-numbers d-flex">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                                <button id="debtNextPageBtn"
                                                    class="btn btn-sm btn-outline-primary rounded-circle">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Debt Return Tab -->
                        <div class="tab-pane fade" id="debt-return-content" role="tabpanel"
                            aria-labelledby="debt-return-tab">
                            <div class="row">
                                <!-- Debt Information Section -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">زانیاری قەرز</h5>
                                    </div>
                                    <div class="card border-0 bg-light p-3 mb-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">ناوی کڕیار</h6>
                                                <p class="h5"><?php echo htmlspecialchars($customer['name']); ?></p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">ژمارەی مۆبایل</h6>
                                                <p class="h5"><?php echo htmlspecialchars($customer['phone1']); ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">کۆی قەرز</h6>
                                                <p
                                                    class="h4 text-<?php echo $customer['debit_on_business'] > 0 ? 'danger' : 'success'; ?>">
                                                    <?php echo number_format($customer['debit_on_business']); ?> دینار
                                                </p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">کۆی گەڕاندنەوە</h6>
                                                <p class="h4 text-success"><?php echo number_format($totalReturns); ?>
                                                    دینار</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Debt Return Form Section -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">وەرگرتنی پارە</h5>
                                    </div>
                                    <form id="debtReturnForm">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        <input type="hidden" name="transaction_type" value="collection">

                                        <div class="mb-3">
                                            <label for="returnAmount" class="form-label">بڕی پارەی گەڕاوە</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="returnAmount"
                                                    name="amount" min="1"
                                                    max="<?php echo $customer['debit_on_business']; ?>"
                                                    oninput="validateReturnAmount(this)" required>
                                                <span class="input-group-text">دینار</span>
                                            </div>
                                            <div class="form-text text-danger" id="amountError" style="display: none;">
                                                بڕی پارەی گەڕاوە ناتوانێت لە
                                                <?php echo number_format($customer['debit_on_business']); ?> دینار زیاتر
                                                بێت
                                            </div>
                                            <div class="form-text">
                                                زۆرترین بڕی نوێ:
                                                <?php echo number_format($customer['debit_on_business']); ?> دینار
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="returnDate" class="form-label">بەرواری گەڕانەوە</label>
                                            <input type="date" class="form-control" id="returnDate" name="return_date"
                                                value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="returnNotes" class="form-label">تێبینی</label>
                                            <textarea class="form-control" id="returnNotes" name="notes"
                                                rows="3"></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="paymentMethod" class="form-label">شێوازی پارەدان</label>
                                            <select class="form-select" id="paymentMethod" name="payment_method">
                                                <option value="cash">نەقد</option>
                                                <option value="transfer">FIB یان FastPay</option>
                                            </select>
                                        </div>

                                        <div class="text-end">
                                            <button type="reset" class="btn btn-outline-secondary me-2">
                                                <i class="fas fa-undo me-2"></i> ڕیسێت
                                            </button>
                                            <button type="button" id="saveDebtReturnBtn" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> تۆمارکردن
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Debt History Tab -->
                        <div class="tab-pane fade" id="debt-history-content" role="tabpanel"
                            aria-labelledby="debt-history-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">مێژووی گەڕاندنەوەی قەرز</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-success me-2" id="printDebtHistory">
                                        <i class="fas fa-print me-2"></i> چاپکردن
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary refresh-btn">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-container">
                                <!-- Table Controls -->
                                <div class="table-controls mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page">
                                                <label class="me-2">نیشاندان:</label>
                                                <div class="custom-select-wrapper">
                                                    <select id="debtHistoryRecordsPerPage"
                                                        class="form-select form-select-sm rounded-pill">
                                                        <option value="5">5</option>
                                                        <option value="10" selected>10</option>
                                                        <option value="25">25</option>
                                                        <option value="50">50</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="debtHistoryTableSearch"
                                                        class="form-control rounded-pill-start table-search-input"
                                                        placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table Content -->
                                <div class="table-responsive">
                                    <table id="debtHistoryTable" class="table table-bordered custom-table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>بەروار</th>
                                                <th>بڕی پارە</th>
                                                <th>شێوازی پارەدان</th>
                                                <th>تێبینی</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($debtTransactions) > 0): ?>
                                                <?php 
                                                $counter = 1;
                                                foreach ($debtTransactions as $transaction): 
                                                    if ($transaction['transaction_type'] === 'collection'):
                                                ?>
                                                    <tr data-id="<?php echo $transaction['id']; ?>">
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo date('Y/m/d', strtotime($transaction['created_at'])); ?></td>
                                                        <td class="text-success"><?php echo number_format($transaction['amount']); ?> دینار</td>
                                                        <td>
                                                            <?php
                                                            $notesObj = json_decode($transaction['notes'], true);
                                                            $paymentMethod = is_array($notesObj) && isset($notesObj['payment_method']) ? 
                                                                $notesObj['payment_method'] : 'cash';
                                                            echo $paymentMethod === 'cash' ? 'نەقد' : 'FIB یان FastPay';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            if (is_array($notesObj) && isset($notesObj['notes'])) {
                                                                echo !empty($notesObj['notes']) ? htmlspecialchars($notesObj['notes']) : '-';
                                                            } else {
                                                                echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary rounded-circle edit-debt-return"
                                                                    data-id="<?php echo $transaction['id']; ?>"
                                                                    data-amount="<?php echo $transaction['amount']; ?>"
                                                                    data-date="<?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?>"
                                                                    data-payment-method="<?php echo $paymentMethod; ?>"
                                                                    data-notes="<?php echo is_array($notesObj) && isset($notesObj['notes']) ? 
                                                                        htmlspecialchars($notesObj['notes']) : 
                                                                        htmlspecialchars($transaction['notes']); ?>"
                                                                    title="دەستکاری">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">هیچ گەڕاندنەوەیەکی قەرز نەدۆزرایەوە</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Table Pagination -->
                                <div class="table-pagination mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6 mb-2 mb-md-0">
                                            <div class="pagination-info">
                                                نیشاندانی <span id="debtHistoryStartRecord">1</span> تا <span
                                                    id="debtHistoryEndRecord">10</span> لە کۆی <span
                                                    id="debtHistoryTotalRecords"><?php echo count($debtTransactions); ?></span>
                                                تۆمار
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="pagination-controls d-flex justify-content-md-end">
                                                <button id="debtHistoryPrevPageBtn"
                                                    class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                                <div id="debtHistoryPaginationNumbers" class="pagination-numbers d-flex">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                                <button id="debtHistoryNextPageBtn"
                                                    class="btn btn-sm btn-outline-primary rounded-circle">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advance Payment Tab -->
                        <div class="tab-pane fade" id="advance-payment-content" role="tabpanel"
                            aria-labelledby="advance-payment-tab">
                            <div class="row">
                                <!-- Customer Information Section -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">زانیاری کڕیار</h5>
                                    </div>
                                    <div class="card border-0 bg-light p-3 mb-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">ناوی کڕیار</h6>
                                                <p class="h5"><?php echo htmlspecialchars($customer['name']); ?></p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">ژمارەی مۆبایل</h6>
                                                <p class="h5"><?php echo htmlspecialchars($customer['phone1']); ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">دۆخی ئێستا</h6>
                                                <?php if ($customer['debit_on_business'] > 0): ?>
                                                <p class="h4 text-danger">
                                                    <?php echo number_format($customer['debit_on_business']); ?> دینار قەرز
                                                </p>
                                                <?php elseif ($customer['debit_on_business'] < 0): ?>
                                                <p class="h4 text-success">
                                                    <?php echo number_format(abs($customer['debit_on_business'])); ?> دینار پێشەکی
                                                </p>
                                                <?php else: ?>
                                                <p class="h4 text-secondary">
                                                    هاوسەنگ
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-muted mb-2">کۆی گەڕاندنەوە</h6>
                                                <p class="h4 text-success"><?php echo number_format($totalReturns); ?>
                                                    دینار</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advance Payment Form Section -->
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">وەرگرتنی پارەی پێشەکی</h5>
                                    </div>
                                    
                                    <?php if ($customer['debit_on_business'] > 0): ?>
                                    <!-- Warning message for customers with debt -->
                                    <div class="alert alert-warning mb-4">
                                        <h5><i class="fas fa-exclamation-triangle me-2"></i> ئاگاداری</h5>
                                        <p class="mb-0">ئەم کڕیارە <?php echo number_format($customer['debit_on_business']); ?> دینار قەرزی هەیە لەسەر. پێویستە سەرەتا قەرزەکەی بدات پێش ئەوەی پارەی پێشەکی وەربگیرێت.</p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form id="advancePaymentForm">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        <input type="hidden" name="transaction_type" value="advance_payment">

                                        <div class="mb-3">
                                            <label for="advanceAmount" class="form-label">بڕی پارەی پێشەکی</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="advanceAmount"
                                                    name="amount" min="1" required <?php echo $customer['debit_on_business'] > 0 ? 'disabled' : ''; ?>>
                                                <span class="input-group-text">دینار</span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="advanceDate" class="form-label">بەرواری وەرگرتن</label>
                                            <input type="date" class="form-control" id="advanceDate" name="advance_date"
                                                value="<?php echo date('Y-m-d'); ?>" required <?php echo $customer['debit_on_business'] > 0 ? 'disabled' : ''; ?>>
                                        </div>

                                        <div class="mb-3">
                                            <label for="advanceNotes" class="form-label">تێبینی</label>
                                            <textarea class="form-control" id="advanceNotes" name="notes"
                                                rows="3" <?php echo $customer['debit_on_business'] > 0 ? 'disabled' : ''; ?>></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="advancePaymentMethod" class="form-label">شێوازی پارەدان</label>
                                            <select class="form-select" id="advancePaymentMethod" name="payment_method" <?php echo $customer['debit_on_business'] > 0 ? 'disabled' : ''; ?>>
                                                <option value="cash">نەقد</option>
                                                <option value="transfer">FIB یان FastPay</option>
                                            </select>
                                        </div>

                                        <div class="text-end">
                                            <button type="reset" class="btn btn-outline-secondary me-2" <?php echo $customer['debit_on_business'] > 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-undo me-2"></i> ڕیسێت
                                            </button>
                                            <button type="button" id="saveAdvancePaymentBtn" class="btn btn-primary" <?php echo $customer['debit_on_business'] > 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-save me-2"></i> تۆمارکردن
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Advance Payment History Tab -->
                        <div class="tab-pane fade" id="advance-payment-history-content" role="tabpanel"
                            aria-labelledby="advance-payment-history-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">مێژووی پارەی پێشەکی</h5>
                                <button class="btn btn-sm btn-outline-primary refresh-btn">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table id="advancePaymentTable" class="table table-bordered custom-table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>بەروار</th>
                                            <th>بڕی پارە</th>
                                            <th>تێبینی</th>
                                            <th>شێوازی پارەدان</th>
                                            <th>کردارەکان</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $advanceTransactions = array_filter($debtTransactions, function ($transaction) {
                                            return $transaction['transaction_type'] == 'advance_payment';
                                        });

                                        if (count($advanceTransactions) > 0):
                                            $counter = 1;
                                            foreach ($advanceTransactions as $transaction):
                                                $notesData = json_decode($transaction['notes'], true);
                                                $paymentMethod = isset($notesData['payment_method']) ? $notesData['payment_method'] : 'cash';
                                                $displayNotes = isset($notesData['notes']) ? $notesData['notes'] : $transaction['notes'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><?php echo date('Y/m/d', strtotime($transaction['created_at'])); ?>
                                                    </td>
                                                    <td class="text-primary">
                                                        <?php echo number_format($transaction['amount']); ?> دینار</td>
                                                    <td><?php echo !empty($displayNotes) ? htmlspecialchars($displayNotes) : '-'; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        switch ($paymentMethod) {
                                                            case 'cash':
                                                                echo '<span class="badge bg-success">نەقد</span>';
                                                                break;
                                                            case 'transfer':
                                                                echo '<span class="badge bg-info">FIB یان FastPay</span>';
                                                                break;
                                                            default:
                                                                echo '<span class="badge bg-secondary">هی تر</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="../../Views/receipt/customer_advance_receipt.php?transaction_id=<?php echo $transaction['id']; ?>"
                                                                class="btn btn-sm btn-outline-warning rounded-circle"
                                                                target="_blank"
                                                                title="بینینی مێژوو">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            <a href="#"
                                                                class="btn btn-sm btn-outline-info rounded-circle print-receipt-btn"
                                                                data-id="<?php echo $transaction['id']; ?>" title="چاپکردن">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">هیچ پارەی پێشەکی تۆمار نەکراوە
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Draft Receipts Tab -->
                        <div class="tab-pane fade" id="draft-receipts-content" role="tabpanel" aria-labelledby="draft-receipts-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">ڕەشنووسی پسووڵەکان</h5>
                                <button class="btn btn-sm btn-outline-primary refresh-btn">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>

                            <div class="table-container">
                                <!-- Table Controls -->
                                <div class="table-controls mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page">
                                                <label class="me-2">نیشاندان:</label>
                                                <div class="custom-select-wrapper">
                                                    <select id="draftRecordsPerPage" class="form-select form-select-sm rounded-pill">
                                                        <option value="5">5</option>
                                                        <option value="10" selected>10</option>
                                                        <option value="25">25</option>
                                                        <option value="50">50</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="draftTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Table Content -->
                                <div class="table-responsive">
                                    <table id="draftHistoryTable" class="table table-bordered custom-table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>ژمارەی پسووڵە</th>
                                                <th>بەروار</th>
                                                <th>جۆری پارەدان</th>
                                                <th>کۆی گشتی</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Get draft receipts for this customer
                                            $stmt = $conn->prepare("
                                                SELECT s.*, 
                                                       COALESCE(SUM(si.total_price), 0) as subtotal,
                                                       (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
                                                FROM sales s
                                                LEFT JOIN sale_items si ON s.id = si.sale_id
                                                WHERE s.is_draft = 1 AND s.customer_id = :customer_id
                                                GROUP BY s.id
                                                ORDER BY s.created_at DESC
                                            ");
                                            $stmt->bindParam(':customer_id', $customerId);
                                            $stmt->execute();
                                            $draft_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (count($draft_receipts) > 0) {
                                                foreach ($draft_receipts as $index => $receipt) {
                                                    echo '<tr>';
                                                    echo '<td>' . ($index + 1) . '</td>';
                                                    echo '<td>' . htmlspecialchars($receipt['invoice_number']) . ' <span class="draft-badge">ڕەشنووس</span></td>';
                                                    echo '<td>' . date('Y-m-d', strtotime($receipt['date'])) . '</td>';
                                                    echo '<td>' . ($receipt['payment_type'] == 'cash' ? 'نەقد' : 'قەرز') . '</td>';
                                                    echo '<td>' . number_format($receipt['total_amount']) . ' دینار</td>';
                                                    echo '<td>
                                                        <div class="action-buttons">
                                                            <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="' . $receipt['id'] . '">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="' . $receipt['id'] . '">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-success rounded-circle finalize-btn" data-id="' . $receipt['id'] . '">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="' . $receipt['id'] . '">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">هیچ ڕەشنووسێک نەدۆزرایەوە</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Table Pagination -->
                                <div class="table-pagination mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6 mb-2 mb-md-0">
                                            <div class="pagination-info">
                                                نیشاندانی <span id="draftStartRecord">1</span> تا <span id="draftEndRecord">10</span> لە کۆی <span id="draftTotalRecords"><?php echo count($draft_receipts); ?></span> تۆمار
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="pagination-controls d-flex justify-content-md-end">
                                                <button id="draftPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                                <div id="draftPaginationNumbers" class="pagination-numbers d-flex">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                                <button id="draftNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">تۆمارکردنی پارەدان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPaymentForm">
                        <input type="hidden" id="customerId" name="customer_id" value="<?php echo $customer['id']; ?>">

                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">بڕی پارە</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="paymentAmount" name="amount" required
                                    min="1">
                                <span class="input-group-text">دینار</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="paymentType" class="form-label">جۆری مامەڵە</label>
                            <select class="form-select" id="paymentType" name="transaction_type" required>
                                <option value="payment">پارەدان (دەبێتە قەرز)</option>
                                <option value="collection">وەرگرتنی پارە (کەمبوونەوەی قەرز)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="paymentNotes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="paymentNotes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="savePaymentBtn">تۆمارکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Sale Modal -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" role="dialog" aria-labelledby="editSaleModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSaleModalLabel">دەستکاری پسووڵەی فرۆشتن</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="داخستن"></button>
                </div>
                <div class="modal-body">
                    <form id="editSaleForm">
                        <input type="hidden" id="editSaleId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editSaleInvoiceNumber" class="form-label">ژمارەی پسووڵە</label>
                                <input type="text" class="form-control" id="editSaleInvoiceNumber" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="editSaleCustomer" class="form-label">کڕیار</label>
                                <select class="form-select" id="editSaleCustomer" required>
                                    <option value="">کڕیار هەڵبژێرە</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, name FROM customers ORDER BY name");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editSaleDate" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="editSaleDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editSalePaymentType" class="form-label">جۆری پارەدان</label>
                                <select class="form-select" id="editSalePaymentType" required>
                                    <option value="cash">نەقد</option>
                                    <option value="credit">قەرز</option>
                                    <option value="check">چەک</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editSaleShippingCost" class="form-label">کرێی گواستنەوە</label>
                                <input type="number" class="form-control" id="editSaleShippingCost" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editSaleOtherCosts" class="form-label">خەرجی تر</label>
                                <input type="number" class="form-control" id="editSaleOtherCosts" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editSaleDiscount" class="form-label">داشکاندن</label>
                                <input type="number" class="form-control" id="editSaleDiscount" required>
                            </div>
                            <div class="col-12">
                                <label for="editSaleNotes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="editSaleNotes" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveSaleEdit">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Global AJAX Configuration -->
    <script src="../../js/ajax-config.js"></script>
    <!-- Custom Scripts -->
    <script src="../../js/include-components.js"></script>
    <!-- Page Specific Script -->
    <script>
        $(document).ready(function () {
            // Initialize pagination for tables
            initBasicTablePagination('sales');
            initBasicTablePagination('debt');
            initBasicTablePagination('debtHistory');

            // Table search functionality
            $('#salesTableSearch, #debtTableSearch, #debtHistoryTableSearch').on('keyup', function () {
                const tableId = $(this).attr('id').replace('TableSearch', '');
                const value = $(this).val().toLowerCase();
                $(`#${tableId}HistoryTable tbody tr, #${tableId}ReturnTable tbody tr`).filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updatePagination(tableId);
            });

            // Records per page change handlers
            $('#salesRecordsPerPage, #debtRecordsPerPage, #debtHistoryRecordsPerPage').on('change', function () {
                const tableId = $(this).attr('id').replace('RecordsPerPage', '');
                showAllRows(tableId);  // Show all rows first
                updatePagination(tableId);
            });

            function showAllRows(tableId) {
                const tableSelector = tableId === 'debtHistory' ? `#${tableId}ReturnTable` : `#${tableId}HistoryTable`;
                $(`${tableSelector} tbody tr`).show();
            }

            function initBasicTablePagination(tableId) {
                showAllRows(tableId);  // Show all rows initially
                updatePagination(tableId);

                // Pagination button handlers
                $(`#${tableId}PrevPageBtn`).off('click').on('click', function () {
                    const currentPage = parseInt($(`#${tableId}PaginationNumbers .active`).text());
                    if (currentPage > 1) {
                        goToPage(tableId, currentPage - 1);
                    }
                });

                $(`#${tableId}NextPageBtn`).off('click').on('click', function () {
                    const currentPage = parseInt($(`#${tableId}PaginationNumbers .active`).text());
                    const totalPages = Math.ceil($(`#${tableId}TotalRecords`).text() / $(`#${tableId}RecordsPerPage`).val());
                    if (currentPage < totalPages) {
                        goToPage(tableId, currentPage + 1);
                    }
                });

                // Add click handlers for pagination numbers using event delegation
                $(`#${tableId}PaginationNumbers`).off('click').on('click', 'button', function () {
                    const page = parseInt($(this).data('page'));
                    goToPage(tableId, page);
                });
            }

            function updatePagination(tableId) {
                const recordsPerPage = parseInt($(`#${tableId}RecordsPerPage`).val());
                const tableSelector = tableId === 'debtHistory' ? `#${tableId}ReturnTable` : `#${tableId}HistoryTable`;
                const allRows = $(`${tableSelector} tbody tr`);
                const totalRows = allRows.length;
                const totalPages = Math.ceil(totalRows / recordsPerPage);

                // Generate pagination numbers
                let paginationHtml = '';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `<button class="btn btn-sm ${i === 1 ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2 ${i === 1 ? 'active' : ''}" data-page="${i}">${i}</button>`;
                }
                $(`#${tableId}PaginationNumbers`).html(paginationHtml);

                // Show first page
                goToPage(tableId, 1);
            }

            function goToPage(tableId, page) {
                const recordsPerPage = parseInt($(`#${tableId}RecordsPerPage`).val());
                const tableSelector = tableId === 'debtHistory' ? `#${tableId}ReturnTable` : `#${tableId}HistoryTable`;
                const allRows = $(`${tableSelector} tbody tr`);

                // First show all rows to ensure we can access them all
                allRows.show();

                // Calculate start and end indices
                const startIndex = (page - 1) * recordsPerPage;
                const endIndex = startIndex + recordsPerPage;

                // Hide all rows first
                allRows.hide();

                // Show only the rows for current page
                allRows.slice(startIndex, endIndex).show();

                // Debug information
                console.log('Page Change Debug for ' + tableId + ':');
                console.log('Going to page:', page);
                console.log('Total rows:', allRows.length);
                console.log('Start Index:', startIndex);
                console.log('End Index:', endIndex);
                console.log('Rows shown:', allRows.slice(startIndex, endIndex).length);

                // Update debug div
                $(`#debug-${tableId}`).html(`
                    <strong>Debug Info for ${tableId}:</strong><br>
                    Table: ${tableSelector}<br>
                    Records Per Page: ${recordsPerPage}<br>
                    Total Rows: ${allRows.length}<br>
                    Total Pages: ${Math.ceil(allRows.length / recordsPerPage)}<br>
                    Current Page: ${page}<br>
                    Showing rows ${startIndex + 1} to ${Math.min(endIndex, allRows.length)}<br>
                    Rows shown on this page: ${allRows.slice(startIndex, endIndex).length}
                `);

                // Update pagination UI
                $(`#${tableId}PaginationNumbers button`).removeClass('btn-primary active').addClass('btn-outline-primary');
                $(`#${tableId}PaginationNumbers button[data-page="${page}"]`).removeClass('btn-outline-primary').addClass('btn-primary active');

                // Update pagination info
                const startRecord = allRows.length > 0 ? startIndex + 1 : 0;
                const endRecord = Math.min(endIndex, allRows.length);
                $(`#${tableId}StartRecord`).text(startRecord);
                $(`#${tableId}EndRecord`).text(endRecord);
                $(`#${tableId}TotalRecords`).text(allRows.length);

                // Update prev/next buttons
                const totalPages = Math.ceil(allRows.length / recordsPerPage);
                $(`#${tableId}PrevPageBtn`).prop('disabled', page === 1);
                $(`#${tableId}NextPageBtn`).prop('disabled', page === totalPages);
            }

            // Add tab change handler to reinitialize pagination
            $('#customerTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                const targetTab = $(e.target).attr('id');
                let tableId;
                
                switch(targetTab) {
                    case 'sales-tab':
                        tableId = 'sales';
                        showAllRows(tableId);
                        updatePagination(tableId);
                        break;
                    case 'debt-tab':
                        tableId = 'debt';
                        showAllRows(tableId);
                        updatePagination(tableId);
                        break;
                    case 'debt-history-tab':
                        tableId = 'debtHistory';
                        showAllRows(tableId);
                        updatePagination(tableId);
                        break;
                    case 'draft-receipts-tab':
                        // Re-initialize advanced pagination for draft receipts
                        initAdvancedTablePagination({
                            tableId: 'draftHistoryTable',
                            recordsPerPageId: 'draftRecordsPerPage',
                            paginationNumbersId: 'draftPaginationNumbers',
                            prevBtnId: 'draftPrevPageBtn',
                            nextBtnId: 'draftNextPageBtn',
                            startRecordId: 'draftStartRecord',
                            endRecordId: 'draftEndRecord',
                            totalRecordsId: 'draftTotalRecords',
                            searchInputId: 'draftTableSearch'
                        });
                        break;
                }
            });

            // Save payment button handler
            $('#savePaymentBtn').on('click', function () {
                const formData = new FormData($('#addPaymentForm')[0]);

                $.ajax({
                    url: '../../ajax/save_debt_transaction.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Save debt return button handler
            $('#saveDebtReturnBtn').on('click', function () {
                const returnAmount = parseInt($('#returnAmount').val());
                const maxDebt = <?php echo $customer['debit_on_business']; ?>;

                // Validate form
                if (!returnAmount || returnAmount <= 0) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە بڕی پارەی گەڕاوە بەدروستی داخل بکە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Check if amount exceeds total debt
                if (returnAmount > maxDebt) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'بڕی پارەی گەڕاوە ناتوانێت لە ' + maxDebt.toLocaleString() + ' دینار زیاتر بێت',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Prepare form data
                const formData = new FormData();
                formData.append('customer_id', <?php echo $customer['id']; ?>);
                formData.append('transaction_type', 'collection');
                formData.append('amount', returnAmount);

                // Create JSON notes to store additional information
                const notesObj = {
                    payment_method: $('#paymentMethod').val(),
                    reference_number: $('#referenceNumber').val(),
                    notes: $('#returnNotes').val(),
                    return_date: $('#returnDate').val()
                };

                formData.append('notes', JSON.stringify(notesObj));

                // Send AJAX request
                $.ajax({
                    url: '../../ajax/save_debt_transaction.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: 'گەڕانەوەی قەرز بەسەرکەوتوویی تۆمارکرا',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: data.message || 'هەڵەیەک ڕوویدا لە تۆمارکردنی گەڕانەوەی قەرز',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Add this new function for real-time validation
            function validateReturnAmount(input) {
                const returnAmount = parseInt(input.value);
                const maxDebt = <?php echo $customer['debit_on_business']; ?>;
                const errorDiv = document.getElementById('amountError');
                const submitBtn = document.getElementById('saveDebtReturnBtn');

                if (returnAmount > maxDebt) {
                    errorDiv.style.display = 'block';
                    input.classList.add('is-invalid');
                    submitBtn.disabled = true;
                } else {
                    errorDiv.style.display = 'none';
                    input.classList.remove('is-invalid');
                    submitBtn.disabled = false;
                }
            }

            // Add the validateReturnAmount function to the window object
            window.validateReturnAmount = validateReturnAmount;

            // Print customer receipt handler for debt return
            $(document).on('click', '.print-receipt-btn', function(e) {
                e.preventDefault(); // Prevent default action
                const transactionId = $(this).data('id');
                const printWindow = window.open(`../../Views/receipt/customer_history_receipt.php?transaction_id=${transactionId}`, '_blank');
                
                if (printWindow) {
                    printWindow.addEventListener('load', function() {
                        printWindow.print();
                    });
                } else {
                    Swal.fire({
                        title: 'ئاگاداری',
                        text: 'تکایە ڕێگە بدە بە کردنەوەی پەنجەرەی نوێ بۆ چاپکردن',
                        icon: 'warning',
                        confirmButtonText: 'باشە'
                    });
                }
            });

            // Handle show invoice items button click
            $(document).on('click', '.show-invoice-items', function() {
                const invoiceNumber = $(this).data('invoice');
                const invoiceItems = <?php echo json_encode($sales); ?>;
                const items = invoiceItems.filter(item => item.invoice_number === invoiceNumber);
                
                let itemsHtml = '<div class="table-responsive"><table class="table table-bordered">';
                itemsHtml += '<thead><tr><th>ناوی کاڵا</th><th>کۆدی کاڵا</th><th>بڕ</th><th>یەکە</th><th>نرخی تاک</th><th>نرخی گشتی</th></tr></thead>';
                itemsHtml += '<tbody>';
                
                items.forEach(item => {
                    itemsHtml += `<tr>
                        <td>${item.product_name}</td>
                        <td>${item.product_code}</td>
                        <td>${item.quantity}</td>
                        <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                        <td>${item.unit_price.toLocaleString()} دینار</td>
                        <td>${item.total_price.toLocaleString()} دینار</td>
                    </tr>`;
                });
                
                itemsHtml += '</tbody></table></div>';
                
                Swal.fire({
                    title: `کاڵاکانی پسووڵە ${invoiceNumber}`,
                    html: itemsHtml,
                    width: '80%',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'swal2-popup-custom'
                    }
                });
            });

            // Add custom CSS for the popup
            const style = document.createElement('style');
            style.textContent = `
                .swal2-popup-custom {
                    max-width: 90%;
                    width: 80%;
                }
                .swal2-popup-custom .table {
                    margin-bottom: 0;
                }
                .swal2-popup-custom .table th,
                .swal2-popup-custom .table td {
                    padding: 0.5rem;
                    text-align: center;
                }
                .swal2-popup-custom .table thead th {
                    background-color: #f8f9fa;
                    border-bottom: 2px solid #dee2e6;
                }
            `;
            document.head.appendChild(style);

            // Helper functions
            function filterTableByDate(tableId) {
                const startDate = $(`#${tableId}StartDate`).val();
                const endDate = $(`#${tableId}EndDate`).val();
                const typeFilter = tableId === 'debt' ? $(`#debtType`).val() : '';

                $(`#${tableId}HistoryTable tbody tr`).each(function () {
                    let show = true;

                    // Date filtering
                    if (startDate || endDate) {
                        const rowDate = new Date($(this).find('td:nth-child(2)').text().split('/').join('-'));

                        if (startDate && new Date(startDate) > rowDate) {
                            show = false;
                        }

                        if (endDate && new Date(endDate) < rowDate) {
                            show = false;
                        }
                    }

                    // Type filtering for debt transactions
                    if (tableId === 'debt' && typeFilter) {
                        const rowText = $(this).find('td:nth-child(4)').text().trim();
                        let matchesType = false;

                        switch (typeFilter) {
                            case 'sale':
                                matchesType = rowText.includes('فرۆشتن');
                                break;
                            case 'purchase':
                                matchesType = rowText.includes('کڕین');
                                break;
                            case 'payment':
                                matchesType = rowText.includes('پارەدان');
                                break;
                            case 'collection':
                                matchesType = rowText.includes('وەرگرتنی پارە');
                                break;
                        }

                        if (!matchesType) {
                            show = false;
                        }
                    }

                    $(this).toggle(show);
                });

                updatePagination(tableId);
            }

            function resetTableFilter(tableId) {
                $(`#${tableId}HistoryTable tbody tr`).show();
                updatePagination(tableId);
            }

            // Auto filter functionality
            $('.auto-filter').on('change', function () {
                filterSalesTable();
            });

            function filterSalesTable() {
                const productId = $('#productFilter').val();
                const unitType = $('#unitFilter').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                $('#salesHistoryTable tbody tr').each(function () {
                    let show = true;
                    const row = $(this);

                    // Product filter
                    if (productId) {
                        const productCell = row.find('td:nth-child(4)'); // Product name column
                        if (productCell.data('product-id') != productId) {
                            show = false;
                        }
                    }

                    // Unit type filter
                    if (unitType) {
                        const unitCell = row.find('td:nth-child(7)'); // Unit type column
                        const unitText = unitCell.text().trim();
                        let matches = false;

                        switch (unitType) {
                            case 'piece':
                                matches = unitText === 'دانە';
                                break;
                            case 'box':
                                matches = unitText === 'کارتۆن';
                                break;
                            case 'set':
                                matches = unitText === 'سێت';
                                break;
                        }

                        if (!matches) {
                            show = false;
                        }
                    }

                    // Date range filter
                    if (startDate || endDate) {
                        const dateCell = row.find('td:nth-child(3)'); // Date column
                        const rowDate = new Date(dateCell.text().split('/').reverse().join('-'));

                        if (startDate && new Date(startDate) > rowDate) {
                            show = false;
                        }

                        if (endDate && new Date(endDate) < rowDate) {
                            show = false;
                        }
                    }

                    row.toggle(show);
                });

                // Update pagination after filtering
                updatePagination('sales');
            }

            // Reset filters button handler
            $('#resetFilters').on('click', function () {
                // Reset all filter values
                $('#productFilter').val('');
                $('#unitFilter').val('');
                $('#startDate').val('');
                $('#endDate').val('');

                // Show all rows
                $('#salesHistoryTable tbody tr').show();

                // Reset pagination
                updatePagination('sales');

                // Add animation to the reset button
                const $icon = $(this).find('i');
                $icon.addClass('fa-spin');
                setTimeout(() => {
                    $icon.removeClass('fa-spin');
                }, 500);
            });

            // Add error handling
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                console.error('Ajax Error:', thrownError);
                console.error('Status:', jqxhr.status);
                console.error('Response:', jqxhr.responseText);
            });

            // Add global error handling
            window.onerror = function(msg, url, lineNo, columnNo, error) {
                console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
                return false;
            };

            // Save advance payment button handler
            $('#saveAdvancePaymentBtn').on('click', function () {
                const advanceAmount = parseInt($('#advanceAmount').val());
                const customerDebt = <?php echo $customer['debit_on_business']; ?>;

                // Check if customer has debt
                if (customerDebt > 0) {
                    Swal.fire({
                        title: 'ناتوانیت پارەی پێشەکی زیاد بکەیت!',
                        text: 'ئەم کڕیارە قەرزی هەیە لەسەر. پێویستە سەرەتا قەرزەکەی بدات پێش ئەوەی پارەی پێشەکی وەربگیرێت.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Validate form
                if (!advanceAmount || advanceAmount <= 0) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە بڕی پارەی پێشەکی بەدروستی داخل بکە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Prepare form data
                const formData = new FormData();
                formData.append('customer_id', <?php echo $customer['id']; ?>);
                formData.append('transaction_type', 'advance_payment');
                formData.append('amount', advanceAmount);

                // Create JSON notes to store additional information
                const notesObj = {
                    payment_method: $('#advancePaymentMethod').val(),
                    notes: $('#advanceNotes').val(),
                    advance_date: $('#advanceDate').val()
                };

                formData.append('notes', JSON.stringify(notesObj));

                // Send AJAX request
                $.ajax({
                    url: '../../ajax/save_debt_transaction.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: 'پارەی پێشەکی بەسەرکەوتوویی تۆمارکرا',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: data.message || 'هەڵەیەک ڕوویدا لە تۆمارکردنی پارەی پێشەکی',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Print advance payment receipt handler
            $(document).on('click', '.print-receipt-btn', function(e) {
                e.preventDefault(); // Prevent default action
                const transactionId = $(this).data('id');
                const printWindow = window.open(`../../Views/receipt/customer_advance_receipt.php?transaction_id=${transactionId}`, '_blank');
                
                if (printWindow) {
                    printWindow.addEventListener('load', function() {
                        printWindow.print();
                    });
                } else {
                    Swal.fire({
                        title: 'ئاگاداری',
                        text: 'تکایە ڕێگە بدە بە کردنەوەی پەنجەرەی نوێ بۆ چاپکردن',
                        icon: 'warning',
                        confirmButtonText: 'باشە'
                    });
                }
            });

            // Delete sale button handler
            $(document).on('click', '.delete-sale', function() {
                const saleId = $(this).data('id');
                const invoiceNumber = $(this).data('invoice');
                
                Swal.fire({
                    title: 'دڵنیای لە سڕینەوە؟',
                    text: `ئایا دڵنیای لە سڕینەوەی پسووڵە ${invoiceNumber}؟`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ، بسڕەوە',
                    cancelButtonText: 'نەخێر',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../ajax/delete_sale.php',
                            type: 'POST',
                            data: {
                                sale_id: saleId
                            },
                            success: function(response) {
                                const data = JSON.parse(response);
                                if (data.success) {
                                    Swal.fire({
                                        title: 'سەرکەوتوو بوو!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonText: 'باشە'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'هەڵە!',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                                    icon: 'error',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        });
                    }
                });
            });

            // Return sale button handler
            $(document).on('click', '.return-sale', function() {
                const saleId = $(this).data('id');
                const invoiceNumber = $(this).data('invoice');
                
                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'زانیارییەکان وەردەگیرێن',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Get sale items
                $.ajax({
                    url: '../../ajax/get_sale_items.php',
                    type: 'POST',
                    data: {
                        sale_id: saleId
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            // Check if sale has payments
                            if (response.has_payments) {
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: 'ناتوانرێت ئەم پسووڵە بگەڕێتەوە چونکە پارەدانەوەی لەسەر تۆمار کراوە',
                                    icon: 'error',
                                    confirmButtonText: 'باشە'
                                });
                                return;
                            }
                            
                            // Create return form
                            let itemsHtml = '<form id="returnSaleForm">';
                            itemsHtml += '<input type="hidden" name="sale_id" value="' + saleId + '">';
                            itemsHtml += '<input type="hidden" name="receipt_type" value="selling" data-type="selling">';
                            itemsHtml += '<div class="table-responsive"><table class="table table-bordered">';
                            itemsHtml += '<thead><tr><th>ناوی کاڵا</th><th>بڕی کڕین</th><th>بڕی گەڕانەوە</th></tr></thead>';
                            itemsHtml += '<tbody>';
                            
                            response.items.forEach(item => {
                                // Calculate max returnable amount (total quantity - already returned quantity)
                                const maxReturnable = item.quantity - (item.returned_quantity || 0);
                                
                                if (maxReturnable > 0) {
                                    itemsHtml += `<tr>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity} (${item.returned_quantity || 0} گەڕاوە پێشتر)</td>
                                        <td>
                                            <input type="number" class="form-control return-quantity" 
                                                name="return_quantities[${item.id}]" 
                                                min="0" max="${maxReturnable}" value="0">
                                        </td>
                                    </tr>`;
                                }
                            });
                            
                            itemsHtml += '</tbody></table></div>';
                            itemsHtml += '<div class="mb-3">';
                            itemsHtml += '<label for="returnReason" class="form-label">هۆکاری گەڕانەوە</label>';
                            itemsHtml += '<select class="form-select" name="reason" id="returnReason">';
                            itemsHtml += '<option value="damaged">شکاو/خراپ</option>';
                            itemsHtml += '<option value="wrong_product">کاڵای هەڵە</option>';
                            itemsHtml += '<option value="other">هۆکاری تر</option>';
                            itemsHtml += '</select>';
                            itemsHtml += '</div>';
                            itemsHtml += '<div class="mb-3">';
                            itemsHtml += '<label for="returnNotes" class="form-label">تێبینی</label>';
                            itemsHtml += '<textarea class="form-control" id="returnNotes" name="notes" rows="3"></textarea>';
                            itemsHtml += '</div>';
                            itemsHtml += '</form>';
                            
                            Swal.fire({
                                title: `گەڕاندنەوەی کاڵا - پسووڵە ${invoiceNumber}`,
                                html: itemsHtml,
                                showCancelButton: true,
                                confirmButtonText: 'گەڕاندنەوە',
                                cancelButtonText: 'هەڵوەشاندنەوە',
                                showLoaderOnConfirm: true,
                                preConfirm: () => {
                                    const formData = new FormData(document.getElementById('returnSaleForm'));
                                    // Log form data for debugging
                                    for (let pair of formData.entries()) {
                                        console.log(pair[0] + ': ' + pair[1]);
                                    }
                                    return $.ajax({
                                        url: '../../ajax/return_sale.php',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        dataType: 'json'
                                    }).catch(function(jqXHR, textStatus, errorThrown) {
                                        console.error('AJAX Error:', textStatus);
                                        console.error('Status:', jqXHR.status);
                                        console.error('Response:', jqXHR.responseText);
                                        
                                        // Try to parse the error response
                                        let errorMessage = 'هەڵەیەک ڕوویدا';
                                        try {
                                            const response = JSON.parse(jqXHR.responseText);
                                            if (response.message) {
                                                errorMessage = response.message;
                                            }
                                            if (response.debug_info) {
                                                console.error('Debug Info:', response.debug_info);
                                            }
                                        } catch (e) {
                                            console.error('Error parsing response:', e);
                                        }
                                        
                                        throw new Error(errorMessage);
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const response = result.value;
                                    if (response.success) {
                                        // Create summary HTML
                                        let summaryHtml = '<div class="return-summary mt-3">';
                                        summaryHtml += '<h5 class="mb-3">کورتەی گەڕانەوە</h5>';
                                        
                                        // Original total
                                        summaryHtml += `<div class="mb-2">
                                            <strong>کۆی گشتی پسووڵە:</strong> 
                                            ${response.summary.original_total.toLocaleString()} دینار
                                        </div>`;
                                        
                                        // Return count
                                        summaryHtml += `<div class="mb-2">
                                            <strong>ژمارەی گەڕانەوەکان:</strong> 
                                            ${response.summary.return_count}
                                        </div>`;
                                        
                                        // Returned amount
                                        summaryHtml += `<div class="mb-2">
                                            <strong>کۆی گشتی گەڕاوە:</strong> 
                                            ${response.summary.returned_amount.toLocaleString()} دینار
                                        </div>`;
                                        
                                        // Remaining items
                                        summaryHtml += '<div class="mb-2"><strong>کاڵاکانی گەڕاوە:</strong></div>';
                                        summaryHtml += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                                        summaryHtml += '<thead><tr><th>ناوی کاڵا</th><th>بڕی گەڕانەوە</th><th>نرخی تاک</th><th>نرخی گشتی</th></tr></thead>';
                                        summaryHtml += '<tbody>';
                                        
                                        response.summary.returned_items.forEach(item => {
                                            summaryHtml += `<tr>
                                                <td>${item.product_name}</td>
                                                <td>${item.returned_quantity}</td>
                                                <td>${item.unit_price.toLocaleString()} دینار</td>
                                                <td>${item.total_price.toLocaleString()} دینار</td>
                                            </tr>`;
                                        });
                                        
                                        summaryHtml += '</tbody></table></div>';
                                        
                                        // Remaining items
                                        summaryHtml += '<div class="mb-2"><strong>کاڵاکانی ماوە:</strong></div>';
                                        summaryHtml += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                                        summaryHtml += '<thead><tr><th>ناوی کاڵا</th><th>بڕی ماوە</th><th>نرخی تاک</th><th>نرخی گشتی</th></tr></thead>';
                                        summaryHtml += '<tbody>';
                                        
                                        response.summary.remaining_items.forEach(item => {
                                            summaryHtml += `<tr>
                                                <td>${item.product_name}</td>
                                                <td>${item.quantity}</td>
                                                <td>${item.unit_price.toLocaleString()} دینار</td>
                                                <td>${item.total_price.toLocaleString()} دینار</td>
                                            </tr>`;
                                        });
                                        
                                        summaryHtml += '</tbody></table></div>';
                                        summaryHtml += '</div>';
                                        
                                        Swal.fire({
                                            title: 'سەرکەوتوو بوو!',
                                            html: response.message + summaryHtml,
                                            icon: 'success',
                                            confirmButtonText: 'باشە'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'هەڵە!',
                                            text: response.message,
                                            icon: 'error',
                                            confirmButtonText: 'باشە'
                                        });
                                    }
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', error);
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Handle edit button click
            $(document).on('click', '.edit-btn', function() {
                const saleId = $(this).data('id');
                loadSaleForEditing(saleId);
            });

            // Handle save button click
            $('#saveSaleEdit').on('click', function() {
                saveSaleChanges();
            });

            // Draft Receipts Tab Functionality
            function handleDeleteDraft(receiptId) {
                Swal.fire({
                    title: 'دڵنیای',
                    text: 'دڵنیای لە سڕینەوەی ئەم ڕەشنووسە؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../api/receipts/verify_draft.php',
                            method: 'POST',
                            data: { receipt_id: receiptId },
                            success: function(response) {
                                if (response.exists) {
                                    $.ajax({
                                        url: '../../api/receipts/delete_draft.php',
                                        method: 'POST',
                                        data: { receipt_id: receiptId },
                                        success: function(response) {
                                            if (response.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'سەرکەوتوو',
                                                    text: 'ڕەشنووسەکە بە سەرکەوتوویی سڕایەوە'
                                                }).then(() => {
                                                    location.reload();
                                                });
                                            } else {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'هەڵە',
                                                    text: response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی ڕەشنووس'
                                                });
                                            }
                                        },
                                        error: function() {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'هەڵە',
                                                text: 'هەڵەیەک ڕوویدا لە پەیوەندی بە سێرڤەرەوە'
                                            });
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە',
                                        text: 'ڕەشنووسەکە نەدۆزرایەوە'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: 'هەڵەیەک ڕوویدا لە پشکنینی ڕەشنووس'
                                });
                            }
                        });
                    }
                });
            }

            // Handle draft receipt actions
            $(document).on('click', '#draft-receipts-content .view-btn', function() {
                const receiptId = $(this).data('id');
                window.location.href = `viewReceipt.php?id=${receiptId}&type=draft`;
            });

            $(document).on('click', '#draft-receipts-content .edit-btn', function() {
                const receiptId = $(this).data('id');
                window.location.href = `editReceipt.php?id=${receiptId}&type=draft`;
            });

            $(document).on('click', '#draft-receipts-content .finalize-btn', function() {
                const receiptId = $(this).data('id');
                Swal.fire({
                    title: 'دڵنیای',
                    text: 'دڵنیای لە تەواوکردنی ئەم ڕەشنووسە؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../api/receipts/finalize_draft.php',
                            method: 'POST',
                            data: { receipt_id: receiptId },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'سەرکەوتوو',
                                        text: 'ڕەشنووسەکە بە سەرکەوتوویی تەواوکرا'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە',
                                        text: response.message || 'هەڵەیەک ڕوویدا لە تەواوکردنی ڕەشنووس'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: 'هەڵەیەک ڕوویدا لە پەیوەندی بە سێرڤەرەوە'
                                });
                            }
                        });
                    }
                });
            });

            $(document).on('click', '#draft-receipts-content .delete-btn', function() {
                const receiptId = $(this).data('id');
                handleDeleteDraft(receiptId);
            });

            // Initialize draft receipts table pagination
            initAdvancedTablePagination({
                tableId: 'draftHistoryTable',
                recordsPerPageId: 'draftRecordsPerPage',
                paginationNumbersId: 'draftPaginationNumbers',
                prevBtnId: 'draftPrevPageBtn',
                nextBtnId: 'draftNextPageBtn',
                startRecordId: 'draftStartRecord',
                endRecordId: 'draftEndRecord',
                totalRecordsId: 'draftTotalRecords',
                searchInputId: 'draftTableSearch'
            });

            // Advanced table pagination function that handles options object
            function initAdvancedTablePagination(options) {
                if (typeof options !== 'object' || options === null) {
                    console.error('Invalid options for table pagination');
                    return;
                }
                
                const tableId = options.tableId || '';
                const recordsPerPageId = options.recordsPerPageId || '';
                const paginationNumbersId = options.paginationNumbersId || '';
                const prevBtnId = options.prevBtnId || '';
                const nextBtnId = options.nextBtnId || '';
                const startRecordId = options.startRecordId || '';
                const endRecordId = options.endRecordId || '';
                const totalRecordsId = options.totalRecordsId || '';
                const searchInputId = options.searchInputId || '';
                
                // Ensure all elements exist before proceeding
                if (!$('#' + tableId).length) {
                    console.error('Table not found: ' + tableId);
                    return;
                }
                
                const table = $('#' + tableId);
                const tbody = table.find('tbody');
                const rows = tbody.find('tr').not('.no-records');
                
                let currentPage = 1;
                let recordsPerPage = parseInt($('#' + recordsPerPageId).val()) || 10;
                
                // Initialize
                updatePagination();
                
                // Records per page change event
                $('#' + recordsPerPageId).on('change', function() {
                    recordsPerPage = parseInt($(this).val());
                    currentPage = 1;
                    updatePagination();
                });
                
                // Previous page button
                $('#' + prevBtnId).on('click', function() {
                    if (!$(this).prop('disabled')) {
                        currentPage--;
                        updatePagination();
                    }
                });
                
                // Next page button
                $('#' + nextBtnId).on('click', function() {
                    if (!$(this).prop('disabled')) {
                        currentPage++;
                        updatePagination();
                    }
                });
                
                // Search functionality
                $('#' + searchInputId).on('keyup', function() {
                    currentPage = 1;
                    updatePagination();
                });
                
                // Function to update pagination
                function updatePagination() {
                    const searchTerm = $('#' + searchInputId).val().toLowerCase();
                    
                    // Filter rows based on search term
                    const filteredRows = rows.filter(function() {
                        const rowText = $(this).text().toLowerCase();
                        return searchTerm === '' || rowText.indexOf(searchTerm) > -1;
                    });
                    
                    const totalRecords = filteredRows.length;
                    const totalPages = Math.ceil(totalRecords / recordsPerPage);
                    
                    // Update pagination info
                    $('#' + totalRecordsId).text(totalRecords);
                    
                    // Ensure current page is valid
                    currentPage = Math.max(1, Math.min(currentPage, totalPages || 1));
                    
                    // Calculate start and end indices
                    const startIndex = (currentPage - 1) * recordsPerPage;
                    const endIndex = Math.min(startIndex + recordsPerPage - 1, totalRecords - 1);
                    
                    // Update pagination info
                    $('#' + startRecordId).text(totalRecords > 0 ? startIndex + 1 : 0);
                    $('#' + endRecordId).text(totalRecords > 0 ? endIndex + 1 : 0);
                    
                    // Show/hide rows based on current page
                    tbody.find('tr').hide();
                    filteredRows.each(function(index) {
                        if (index >= startIndex && index <= endIndex) {
                            $(this).show();
                        }
                    });
                    
                    // No records message
                    if (totalRecords === 0) {
                        if (tbody.find('tr.no-records').length === 0) {
                            const colCount = table.find('thead th').length;
                            tbody.append(`<tr class="no-records"><td colspan="${colCount}" class="text-center">هیچ تۆمارێک نەدۆزرایەوە</td></tr>`);
                        }
                        tbody.find('tr.no-records').show();
                    } else {
                        tbody.find('tr.no-records').remove();
                    }
                    
                    // Update pagination controls
                    $('#' + prevBtnId).prop('disabled', currentPage === 1);
                    $('#' + nextBtnId).prop('disabled', currentPage === totalPages || totalPages === 0);
                    
                    // Update pagination numbers
                    updatePaginationNumbers();
                }
                
                // Function to update pagination number buttons
                function updatePaginationNumbers() {
                    const totalRecords = rows.filter(function() {
                        const rowText = $(this).text().toLowerCase();
                        const searchTerm = $('#' + searchInputId).val().toLowerCase();
                        return searchTerm === '' || rowText.indexOf(searchTerm) > -1;
                    }).length;
                    
                    const totalPages = Math.ceil(totalRecords / recordsPerPage);
                    const paginationNumbersContainer = $('#' + paginationNumbersId);
                    
                    // Clear existing pagination numbers
                    paginationNumbersContainer.empty();
                    
                    // Calculate which page numbers to show
                    let startPage = Math.max(1, currentPage - 2);
                    let endPage = Math.min(totalPages, startPage + 4);
                    
                    if (endPage - startPage < 4 && startPage > 1) {
                        startPage = Math.max(1, endPage - 4);
                    }
                    
                    // Add first page button if needed
                    if (startPage > 1) {
                        paginationNumbersContainer.append(`
                            <button class="btn btn-sm btn-outline-primary rounded-pill me-1 page-number" data-page="1">1</button>
                        `);
                        
                        if (startPage > 2) {
                            paginationNumbersContainer.append(`
                                <span class="d-flex align-items-center mx-1">...</span>
                            `);
                        }
                    }
                    
                    // Add page number buttons
                    for (let i = startPage; i <= endPage; i++) {
                        const activeClass = i === currentPage ? 'btn-primary text-white' : 'btn-outline-primary';
                        paginationNumbersContainer.append(`
                            <button class="btn btn-sm ${activeClass} rounded-pill me-1 page-number" data-page="${i}">${i}</button>
                        `);
                    }
                    
                    // Add last page button if needed
                    if (endPage < totalPages) {
                        if (endPage < totalPages - 1) {
                            paginationNumbersContainer.append(`
                                <span class="d-flex align-items-center mx-1">...</span>
                            `);
                        }
                        
                        paginationNumbersContainer.append(`
                            <button class="btn btn-sm btn-outline-primary rounded-pill me-1 page-number" data-page="${totalPages}">${totalPages}</button>
                        `);
                    }
                    
                    // Handle pagination button clicks
                    paginationNumbersContainer.find('.page-number').on('click', function() {
                        currentPage = parseInt($(this).data('page'));
                        updatePagination();
                    });
                }
            }
        });

        // Load sale data for editing
        function loadSaleForEditing(saleId) {
            if (!saleId) return;

            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زانیارییەکان وەردەگیرێن',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch sale details
            $.ajax({
                url: '../../api/receipts/get_sale.php',
                type: 'POST',
                data: { id: saleId },
                success: function(response) {
                    Swal.close();

                    if (response.success) {
                        populateSaleEditForm(response.data);
                        $('#editSaleModal').modal('show');
                    } else {
                        showError(response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیارییەکان');
                    }
                },
                error: function() {
                    Swal.close();
                    showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە');
                }
            });
        }

        // Populate edit form with sale data
        function populateSaleEditForm(saleData) {
            $('#editSaleId').val(saleData.id);
            $('#editSaleInvoiceNumber').val(saleData.invoice_number);
            $('#editSaleCustomer').val(saleData.customer_id);
            $('#editSaleDate').val(formatDateForInput(saleData.date));
            $('#editSalePaymentType').val(saleData.payment_type);
            $('#editSaleShippingCost').val(saleData.shipping_cost || 0);
            $('#editSaleOtherCosts').val(saleData.other_costs || 0);
            $('#editSaleDiscount').val(saleData.discount || 0);
            $('#editSaleNotes').val(saleData.notes || '');
            
            // Disable payment type field if sale has returns or payments
            if (saleData.has_returns || saleData.has_payments) {
                $('#editSalePaymentType').prop('disabled', true);
                
                // Add a note about why the field is disabled
                if (saleData.has_returns && saleData.has_payments) {
                    $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵا و پارەدانی لەسەر تۆمارکراوە</small>').insertAfter('#editSalePaymentType');
                } else if (saleData.has_returns) {
                    $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە</small>').insertAfter('#editSalePaymentType');
                } else if (saleData.has_payments) {
                    $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە پارەدانی لەسەر تۆمارکراوە</small>').insertAfter('#editSalePaymentType');
                }
            } else {
                $('#editSalePaymentType').prop('disabled', false);
                // Remove any existing note
                $('#editSalePaymentType').next('small.text-danger').remove();
            }
        }

        // Save sale changes
        function saveSaleChanges() {
            // Validate form
            if (!validateSaleEditForm()) {
                return;
            }

            // Get form data
            const saleData = {
                id: $('#editSaleId').val(),
                invoice_number: $('#editSaleInvoiceNumber').val(),
                customer_id: $('#editSaleCustomer').val(),
                date: $('#editSaleDate').val(),
                payment_type: $('#editSalePaymentType').val(),
                shipping_cost: $('#editSaleShippingCost').val() || 0,
                other_costs: $('#editSaleOtherCosts').val() || 0,
                discount: $('#editSaleDiscount').val() || 0,
                notes: $('#editSaleNotes').val()
            };

            // Show confirmation dialog
            Swal.fire({
                title: 'دڵنیای؟',
                text: 'ئایا دڵنیای لە تازەکردنەوەی ئەم پسووڵەیە؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بەڵێ، تازەی بکەوە',
                cancelButtonText: 'نەخێر، پەشیمان بوومەوە',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    updateSale(saleData);
                }
            });
        }

        // Validate sale edit form
        function validateSaleEditForm() {
            // Required fields
            if (!$('#editSaleInvoiceNumber').val()) {
                showError('تکایە ژمارەی پسووڵە بنووسە');
                return false;
            }
            if (!$('#editSaleCustomer').val()) {
                showError('تکایە کڕیار هەڵبژێرە');
                return false;
            }
            if (!$('#editSaleDate').val()) {
                showError('تکایە بەروار هەڵبژێرە');
                return false;
            }

            // Numeric fields should be non-negative
            if ($('#editSaleShippingCost').val() < 0) {
                showError('کرێی گواستنەوە ناتوانێت کەمتر بێت لە سفر');
                return false;
            }
            if ($('#editSaleOtherCosts').val() < 0) {
                showError('خەرجی تر ناتوانێت کەمتر بێت لە سفر');
                return false;
            }
            if ($('#editSaleDiscount').val() < 0) {
                showError('داشکاندن ناتوانێت کەمتر بێت لە سفر');
                return false;
            }

            return true;
        }

        // Update sale in database
        function updateSale(saleData) {
            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'گۆڕانکارییەکان پاشەکەوت دەکرێن',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send update request
            $.ajax({
                url: '../../api/receipts/update_sale.php',
                type: 'POST',
                data: saleData,
                success: function(response) {
                    Swal.close();

                    if (response.success) {
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو بوو!',
                            text: response.message,
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            // Close modal and reload page
                            $('#editSaleModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        showError(response.message || 'هەڵەیەک ڕوویدا لە پاشەکەوتکردنی گۆڕانکارییەکان');
                    }
                },
                error: function() {
                    Swal.close();
                    showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە');
                }
            });
        }

        // Helper function to format date for input
        function formatDateForInput(dateString) {
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        }

        // Helper function to show error messages
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: message,
                confirmButtonText: 'باشە'
            });
        }

        // Custom JavaScript for search and filter functionality
        $(document).ready(function() {
            // Search functionality for sales history table
            $("#salesTableSearch").on("keyup", function() {
                const value = $(this).val().toLowerCase();
                $("#salesHistoryTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updateSalesPagination();
            });
            
            // Search functionality for debt history table
            $("#debtTableSearch").on("keyup", function() {
                const value = $(this).val().toLowerCase();
                $("#debtHistoryTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updateDebtPagination();
            });
            
            // Search functionality for debt return history table
            $("#debtHistoryTableSearch").on("keyup", function() {
                const value = $(this).val().toLowerCase();
                $("#debtHistoryReturnTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updateDebtHistoryPagination();
            });
            
            // Search functionality for draft receipts table
            $("#draftTableSearch").on("keyup", function() {
                const value = $(this).val().toLowerCase();
                $("#draftHistoryTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updateDraftPagination();
            });
            
            // Payment type filter functionality
            $("#paymentTypeFilter").on("change", function() {
                const value = $(this).val().toLowerCase();
                if (value === "") {
                    // Show all rows if no filter selected
                    $("#salesHistoryTable tbody tr").show();
                } else {
                    // Filter rows based on payment type
                    $("#salesHistoryTable tbody tr").each(function() {
                        const paymentTypeCell = $(this).find("td:nth-child(13)").text().toLowerCase();
                        const isMatch = paymentTypeCell.includes(value === "cash" ? "نەقد" : "قەرز");
                        $(this).toggle(isMatch);
                    });
                }
                updateSalesPagination();
            });
            
            // Date range filter functionality
            $("#startDate, #endDate").on("change", function() {
                const startDate = $("#startDate").val();
                const endDate = $("#endDate").val();
                
                if (startDate || endDate) {
                    $("#salesHistoryTable tbody tr").each(function() {
                        const dateCell = $(this).find("td:nth-child(3)").text();
                        const rowDate = new Date(dateCell.split('/').reverse().join('-'));
                        
                        let showRow = true;
                        
                        if (startDate && new Date(startDate) > rowDate) {
                            showRow = false;
                        }
                        
                        if (endDate && new Date(endDate) < rowDate) {
                            showRow = false;
                        }
                        
                        $(this).toggle(showRow);
                    });
                } else {
                    $("#salesHistoryTable tbody tr").show();
                }
                updateSalesPagination();
            });
            
            // Reset filters button
            $("#resetFilters").on("click", function() {
                // Reset all filter inputs
                $("#paymentTypeFilter").val("");
                $("#startDate").val("");
                $("#endDate").val("");
                
                // Show all rows
                $("#salesHistoryTable tbody tr").show();
                updateSalesPagination();
                
                // Add animation to reset button
                $(this).find("i").addClass("fa-spin");
                setTimeout(() => {
                    $(this).find("i").removeClass("fa-spin");
                }, 500);
            });
            
            // Update pagination functions
            function updateSalesPagination() {
                // Add your pagination update logic here
                // This is a basic example that updates the pagination counters
                const visibleRows = $("#salesHistoryTable tbody tr:visible").length;
                $("#salesTotalRecords").text(visibleRows);
                
                const pageSize = parseInt($("#salesRecordsPerPage").val());
                const maxPage = Math.ceil(visibleRows / pageSize);
                
                if (maxPage <= 1) {
                    $("#salesNextPageBtn").prop("disabled", true);
                } else {
                    $("#salesNextPageBtn").prop("disabled", false);
                }
                
                $("#salesEndRecord").text(Math.min(pageSize, visibleRows));
            }
            
            function updateDebtPagination() {
                const visibleRows = $("#debtHistoryTable tbody tr:visible").length;
                $("#debtTotalRecords").text(visibleRows);
                
                const pageSize = parseInt($("#debtRecordsPerPage").val());
                const maxPage = Math.ceil(visibleRows / pageSize);
                
                if (maxPage <= 1) {
                    $("#debtNextPageBtn").prop("disabled", true);
                } else {
                    $("#debtNextPageBtn").prop("disabled", false);
                }
                
                $("#debtEndRecord").text(Math.min(pageSize, visibleRows));
            }
            
            function updateDebtHistoryPagination() {
                const visibleRows = $("#debtHistoryReturnTable tbody tr:visible").length;
                $("#debtHistoryTotalRecords").text(visibleRows);
                
                const pageSize = parseInt($("#debtHistoryRecordsPerPage").val());
                const maxPage = Math.ceil(visibleRows / pageSize);
                
                if (maxPage <= 1) {
                    $("#debtHistoryNextPageBtn").prop("disabled", true);
                } else {
                    $("#debtHistoryNextPageBtn").prop("disabled", false);
                }
                
                $("#debtHistoryEndRecord").text(Math.min(pageSize, visibleRows));
            }
            
            function updateDraftPagination() {
                const visibleRows = $("#draftHistoryTable tbody tr:visible").length;
                $("#draftTotalRecords").text(visibleRows);
                
                const pageSize = parseInt($("#draftRecordsPerPage").val());
                const maxPage = Math.ceil(visibleRows / pageSize);
                
                if (maxPage <= 1) {
                    $("#draftNextPageBtn").prop("disabled", true);
                } else {
                    $("#draftNextPageBtn").prop("disabled", false);
                }
                
                $("#draftEndRecord").text(Math.min(pageSize, visibleRows));
            }
            
            // Initialize pagination
            updateSalesPagination();
            updateDebtPagination();
            updateDebtHistoryPagination();
            updateDraftPagination();
        });
    </script>

    <!-- Edit Debt Return Modal -->
    <div class="modal fade" id="editDebtReturnModal" tabindex="-1" aria-labelledby="editDebtReturnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDebtReturnModalLabel">دەستکاریکردنی گەڕاندنەوەی قەرز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDebtReturnForm">
                        <input type="hidden" id="editDebtReturnId" name="id">
                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                        <input type="hidden" name="transaction_type" value="collection">

                        <div class="mb-3">
                            <label for="editReturnAmount" class="form-label">بڕی پارەی گەڕاوە</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editReturnAmount"
                                    name="amount" min="1" required>
                                <span class="input-group-text">دینار</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editReturnDate" class="form-label">بەرواری گەڕانەوە</label>
                            <input type="date" class="form-control" id="editReturnDate" name="return_date" required>
                        </div>

                        <div class="mb-3">
                            <label for="editReturnNotes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="editReturnNotes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editPaymentMethod" class="form-label">شێوازی پارەدان</label>
                            <select class="form-select" id="editPaymentMethod" name="payment_method">
                                <option value="cash">نەقد</option>
                                <option value="transfer">FIB یان FastPay</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveEditDebtReturn">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add JavaScript for handling the new functionality -->
    <script>
    // Print debt history
    document.getElementById('printDebtHistory').addEventListener('click', function() {
        window.print();
    });

    // Edit debt return
    document.querySelectorAll('.edit-debt-return').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const amount = this.dataset.amount;
            const date = this.dataset.date;
            const paymentMethod = this.dataset.paymentMethod;
            const notes = this.dataset.notes;

            document.getElementById('editDebtReturnId').value = id;
            document.getElementById('editReturnAmount').value = amount;
            document.getElementById('editReturnDate').value = date;
            document.getElementById('editPaymentMethod').value = paymentMethod;
            document.getElementById('editReturnNotes').value = notes;

            const modal = new bootstrap.Modal(document.getElementById('editDebtReturnModal'));
            modal.show();
        });
    });

    // Save edited debt return
    document.getElementById('saveEditDebtReturn').addEventListener('click', function() {
        const form = document.getElementById('editDebtReturnForm');
        const formData = new FormData(form);

        // Show loading state
        const saveButton = this;
        const originalText = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> پاشەکەوتکردن...';

        fetch('../../api/customers/update_debt_return.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: 'گەڕاندنەوەی قەرز بە سەرکەوتوویی دەستکاری کرا'
                }).then(() => {
                    location.reload();
                });
            } else {
                throw new Error(data.message || 'هەڵەیەک ڕوویدا لە کاتی دەستکاریکردن');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە. تکایە دووبارە هەوڵ بدەوە.'
            });
        })
        .finally(() => {
            // Reset button state
            saveButton.disabled = false;
            saveButton.innerHTML = originalText;
        });
    });
    </script>

    <!-- Pagination Functions -->
    <script>
    // ... existing JavaScript code ...

    // Pagination Functions
    function updatePagination(tableId, totalRecords, currentPage, recordsPerPage) {
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        const paginationContainer = document.getElementById(`${tableId}PaginationNumbers`);
        const prevButton = document.getElementById(`${tableId}PrevPageBtn`);
        const nextButton = document.getElementById(`${tableId}NextPageBtn`);
        const startRecord = document.getElementById(`${tableId}StartRecord`);
        const endRecord = document.getElementById(`${tableId}EndRecord`);
        const totalRecordsSpan = document.getElementById(`${tableId}TotalRecords`);

        // Update record info
        startRecord.textContent = totalRecords === 0 ? 0 : (currentPage - 1) * recordsPerPage + 1;
        endRecord.textContent = Math.min(currentPage * recordsPerPage, totalRecords);
        totalRecordsSpan.textContent = totalRecords;

        // Update pagination buttons
        prevButton.disabled = currentPage === 1;
        nextButton.disabled = currentPage === totalPages;

        // Clear existing pagination numbers
        paginationContainer.innerHTML = '';

        // Calculate which page numbers to show
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        // Add first page button if needed
        if (startPage > 1) {
            const firstPageBtn = document.createElement('button');
            firstPageBtn.className = 'btn btn-sm btn-outline-primary rounded-circle';
            firstPageBtn.textContent = '1';
            firstPageBtn.onclick = () => updateTable(tableId, 1);
            paginationContainer.appendChild(firstPageBtn);

            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'mx-2';
                ellipsis.textContent = '...';
                paginationContainer.appendChild(ellipsis);
            }
        }

        // Add page numbers
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => updateTable(tableId, i);
            paginationContainer.appendChild(pageBtn);
        }

        // Add last page button if needed
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'mx-2';
                ellipsis.textContent = '...';
                paginationContainer.appendChild(ellipsis);
            }

            const lastPageBtn = document.createElement('button');
            lastPageBtn.className = 'btn btn-sm btn-outline-primary rounded-circle';
            lastPageBtn.textContent = totalPages;
            lastPageBtn.onclick = () => updateTable(tableId, totalPages);
            paginationContainer.appendChild(lastPageBtn);
        }
    }

    function updateTable(tableId, page) {
        const recordsPerPage = parseInt(document.getElementById(`${tableId}RecordsPerPage`).value);
        const searchTerm = document.getElementById(`${tableId}TableSearch`).value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const paymentType = document.getElementById('paymentTypeFilter').value;

        // Update the table based on the tableId
        switch(tableId) {
            case 'sales':
                loadSalesData(page, recordsPerPage, searchTerm, startDate, endDate, paymentType);
                break;
            case 'debt':
                loadDebtData(page, recordsPerPage, searchTerm, startDate, endDate);
                break;
            case 'debtHistory':
                loadDebtHistoryData(page, recordsPerPage, searchTerm, startDate, endDate);
                break;
            // Add other cases as needed
        }
    }

    // Initialize pagination for all tables
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize each table's pagination
        ['sales', 'debt', 'debtHistory'].forEach(tableId => {
            const totalRecords = parseInt(document.getElementById(`${tableId}TotalRecords`).textContent);
            const recordsPerPage = parseInt(document.getElementById(`${tableId}RecordsPerPage`).value);
            updatePagination(tableId, totalRecords, 1, recordsPerPage);

            // Add event listener for records per page change
            document.getElementById(`${tableId}RecordsPerPage`).addEventListener('change', function() {
                updateTable(tableId, 1);
            });
        });
    });
    </script>

    <!-- Core JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Common utilities & components -->
    <script src="../../js/navbar.js"></script>
    <script src="../../js/sidebar.js"></script>
    <script src="../../js/datapicker.js"></script>
    <script src="../../js/utilities.js"></script>
    
    <!-- Return functionality -->
    <script src="../../js/receiptList/tabs/common-receipt-functions.js"></script>
    
    <!-- Page-specific JS -->
    <script src="../../js/customerProfile.js"></script>
    
    <!-- Return button handler -->
    <script>
        $(document).ready(function() {
            // Handle sale receipt return
            $(document).on('click', '.return-btn', function() {
                const saleId = $(this).data('id');
                
                console.log("Return button clicked for sale ID:", saleId);
                
                if (!saleId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'ناسنامەی فرۆشتن نادروستە'
                    });
                    return;
                }
                
                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Fetch sale items
                $.ajax({
                    url: '../../api/receipts/get_sale_items.php',
                    type: 'POST',
                    data: { sale_id: saleId },
                    dataType: 'json',
                    success: function(response) {
                        console.log("API Response:", response);
                        
                        Swal.close();
                        
                        if (response.status === 'success' && response.items) {
                            // Show return form
                            showReturnForm(saleId, 'sale', response.items);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message || 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", {xhr, status, error});
                        Swal.close();
                        
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                            icon: 'error'
                        });
                    }
                });
            });
            
            // Add validation function to window scope (needed for the return form)
            window.validateReturnQuantity = function(input) {
                const value = parseFloat(input.value) || 0;
                const maxReturn = parseFloat(input.dataset.maxReturn) || 0;
                
                if (value > maxReturn) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: `بڕی گەڕاندنەوە نابێت لە ${maxReturn} زیاتر بێت`
                    });
                    input.value = maxReturn;
                }
            };
            
            // Process return form data
            window.processReturn = function(returnData) {
                console.log('Processing return data:', returnData);
                
                if (!returnData || !returnData.items || returnData.items.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هیچ کاڵایەک بۆ گەڕاندنەوە دیاری نەکراوە'
                    });
                    return;
                }
                
                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'گەڕاندنەوەی کاڵا بەڕێوەیە',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Prepare data for submission
                const formData = new FormData();
                formData.append('sale_id', returnData.receipt_id);
                formData.append('receipt_type', returnData.receipt_type);
                formData.append('notes', returnData.notes || '');
                formData.append('reason', returnData.reason || 'damaged');
                
                // Add return quantities for each item
                returnData.items.forEach((item, index) => {
                    formData.append(`return_quantities[${item.id}]`, item.quantity);
                });
                
                // Submit the return data
                $.ajax({
                    url: '../../ajax/return_sale.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.close();
                        
                        try {
                            console.log('Return response:', response);
                            const data = typeof response === 'string' ? JSON.parse(response) : response;
                            
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'سەرکەوتوو',
                                    text: 'گەڕاندنەوەی کاڵا بە سەرکەوتوویی ئەنجام درا',
                                    confirmButtonText: 'باشە'
                                }).then(() => {
                                    // Reload the page to show updated data
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: data.message || 'هەڵەیەک ڕوویدا لە کاتی گەڕاندنەوەی کاڵا'
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e, response);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: 'هەڵەیەک ڕوویدا لە کاتی پڕۆسەکردنی وەڵامی سێرڤەر'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr, status, error);
                        Swal.close();
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی کردن بە سێرڤەرەوە'
                        });
                    }
                });
            };
        });
    </script>
</body>

</html>