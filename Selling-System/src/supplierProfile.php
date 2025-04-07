<?php
// Include database connection
require_once 'config/database.php';
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
               SUM(si.total_price) as total_amount,
               (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as invoice_total
               FROM sales s 
               JOIN sale_items si ON s.id = si.sale_id 
               JOIN products p ON si.product_id = p.id
               WHERE s.customer_id = :customer_id 
               GROUP BY s.id, si.id
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
$creditTransactions = array_filter($debtTransactions, function($transaction) {
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
    $totalSales += $sale['total_amount'];
    if (date('Y-m', strtotime($sale['date'])) === date('Y-m')) {
        $monthlySales += $sale['total_amount'];
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
    <title>پڕۆفایلی کڕیار - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/employeePayment/style.css">
    <link rel="stylesheet" href="css/staff.css">
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
            flex-wrap: wrap;
            max-width: 300px;
            overflow: hidden;
        }
        
        .pagination-numbers .btn {
            margin-bottom: 5px;
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
                        <h3 class="page-title">پڕۆفایلی کڕیار</h3>
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
                                    <p class="card-value mb-0"><?php echo number_format($customer['debit_on_business']); ?> دینار</p>
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
                                        <label for="productFilter" class="form-label">ناوی کاڵا</label>
                                        <select class="form-select auto-filter" id="productFilter">
                                            <option value="">هەموو کاڵاکان</option>
                                            <?php
                                            $productQuery = "SELECT DISTINCT p.id, p.name 
                                                           FROM products p 
                                                           JOIN sale_items si ON p.id = si.product_id 
                                                           JOIN sales s ON si.sale_id = s.id 
                                                           WHERE s.customer_id = :customer_id 
                                                           ORDER BY p.name";
                                            $productStmt = $conn->prepare($productQuery);
                                            $productStmt->bindParam(':customer_id', $customerId);
                                            $productStmt->execute();
                                            $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($products as $product) {
                                                echo '<option value="' . $product['id'] . '">' . htmlspecialchars($product['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                </div>
                                    <div class="col-md-3">
                                        <label for="unitFilter" class="form-label">یەکە</label>
                                        <select class="form-select auto-filter" id="unitFilter">
                                            <option value="">هەموو یەکەکان</option>
                                            <option value="piece">دانە</option>
                                            <option value="box">کارتۆن</option>
                                            <option value="set">سێت</option>
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
                                <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales-content" type="button" role="tab" aria-controls="sales-content" aria-selected="true">
                                    <i class="fas fa-shopping-cart"></i>کڕینەکان
                                </button>
                </li>
                <li class="nav-item" role="presentation">
                                <button class="nav-link" id="debt-tab" data-bs-toggle="tab" data-bs-target="#debt-content" type="button" role="tab" aria-controls="debt-content" aria-selected="false">
                                    <i class="fas fa-money-bill-wave"></i>مامەڵەکانی قەرز
                                </button>
                </li>
                <li class="nav-item" role="presentation">
                                <button class="nav-link" id="debt-return-tab" data-bs-toggle="tab" data-bs-target="#debt-return-content" type="button" role="tab" aria-controls="debt-return-content" aria-selected="false">
                                    <i class="fas fa-hand-holding-dollar"></i>وەرگرتنەوەی قەرز
                                </button>
                </li>
            </ul>
                    </div>

                    <!-- Tab Content -->
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Sales History Tab -->
                            <div class="tab-pane fade show active" id="sales-content" role="tabpanel" aria-labelledby="sales-tab">
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
                                                        <select id="salesRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                        <input type="text" id="salesTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                                    <th>جۆری پارەدان</th>
                                                    <th>کردارەکان</th>
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
                                                        <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($sale['product_code']); ?></td>
                                                        <td><?php echo number_format($sale['quantity']); ?></td>
                                                        <td>
                                                            <?php
                                                            switch($sale['unit_type']) {
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
                                                        <td>
                                                            <?php if ($sale['payment_type'] == 'cash'): ?>
                                                                <span class="badge bg-success">نەقد</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">قەرز</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="viewSale.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="بینین">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="printInvoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-outline-success rounded-circle" title="چاپکردن">
                                                                    <i class="fas fa-print"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
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
                                                    نیشاندانی <span id="salesStartRecord">1</span> تا <span id="salesEndRecord">10</span> لە کۆی <span id="salesTotalRecords"><?php echo count($sales); ?></span> تۆمار
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pagination-controls d-flex justify-content-md-end">
                                                    <button id="salesPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="salesPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Will be populated by JavaScript -->
                                                    </div>
                                                    <button id="salesNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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
                                                        <select id="debtRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                        <input type="text" id="debtTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($creditTransactions as $transaction): ?>
                                                    <tr data-id="<?php echo $transaction['id']; ?>">
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo date('Y/m/d', strtotime($transaction['created_at'])); ?></td>
                                                        <td><?php echo !empty($transaction['invoice_number']) ? htmlspecialchars($transaction['invoice_number']) : '-'; ?></td>
                                                        <td>
                                                            <?php
                                                            switch($transaction['transaction_type']) {
                                                                case 'sale':
                                                                    echo '<span class="badge bg-warning">فرۆشتن بە قەرز</span>';
                                                                    break;
                                                                case 'payment':
                                                                    echo '<span class="badge bg-danger">پارەدان (زیادبوونی قەرز)</span>';
                                                                    break;
                                                                case 'collection':
                                                                    echo '<span class="badge bg-success">وەرگرتنی پارە (کەمبوونەوەی قەرز)</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge bg-secondary">نادیار</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($transaction['transaction_type'] == 'collection'): ?>
                                                                <span class="text-success">
                                                                    <?php echo number_format($transaction['amount']); ?> دینار
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-danger">
                                                                    <?php echo number_format($transaction['amount']); ?> دینار
                                                                </span>
                                                            <?php endif; ?>
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
                                                                <?php if ($transaction['transaction_type'] != 'sale'): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-circle edit-debt-btn" data-id="<?php echo $transaction['id']; ?>" title="دەستکاری">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php else: ?>
                                                                <a href="viewSale.php?id=<?php echo $transaction['reference_id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="بینین">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
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
                                                    نیشاندانی <span id="debtStartRecord">1</span> تا <span id="debtEndRecord">10</span> لە کۆی <span id="debtTotalRecords"><?php echo count($creditTransactions); ?></span> تۆمار
                                                </div>
                                    </div>
                                    <div class="col-md-6">
                                                <div class="pagination-controls d-flex justify-content-md-end">
                                                    <button id="debtPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="debtPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                                    <button id="debtNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                </div>
                                    </div>
                                    </div>
                                </div>
                        </div>
                    </div>

                            <!-- Debt Return Tab -->
                            <div class="tab-pane fade" id="debt-return-content" role="tabpanel" aria-labelledby="debt-return-tab">
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
                                                    <p class="h4 text-<?php echo $customer['debit_on_business'] > 0 ? 'danger' : 'success'; ?>">
                                                        <?php echo number_format($customer['debit_on_business']); ?> دینار
                                                    </p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">کۆی گەڕاندنەوە</h6>
                                                    <p class="h4 text-success"><?php echo number_format($totalReturns); ?> دینار</p>
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
                                                    <input type="number" class="form-control" id="returnAmount" name="amount" min="1" max="<?php echo $customer['debit_on_business']; ?>" required>
                                                    <span class="input-group-text">دینار</span>
                            </div>
                                                <div class="form-text">زۆرترین بڕی نوێ: <?php echo number_format($customer['debit_on_business']); ?> دینار</div>
                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="returnDate" class="form-label">بەرواری گەڕانەوە</label>
                                                <input type="date" class="form-control" id="returnDate" name="return_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                                            
                                            <div class="mb-3">
                                                <label for="returnNotes" class="form-label">تێبینی</label>
                                                <textarea class="form-control" id="returnNotes" name="notes" rows="3"></textarea>
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
                                
                                <!-- Recent Debt Returns -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">دوایین گەڕانەوەکانی قەرز</h5>
                                        </div>
                    <div class="table-responsive">
                                            <table id="debtReturnTable" class="table table-bordered custom-table table-hover">
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
                                                    $returnTransactions = array_filter($debtTransactions, function($transaction) {
                                                        return $transaction['transaction_type'] == 'collection';
                                                    });
                                                    
                                                    if (count($returnTransactions) > 0): 
                                                        $counter = 1;
                                                        foreach ($returnTransactions as $transaction):
                                                            $notesData = json_decode($transaction['notes'], true);
                                                            $paymentMethod = isset($notesData['payment_method']) ? $notesData['payment_method'] : 'cash';
                                                            $displayNotes = isset($notesData['notes']) ? $notesData['notes'] : $transaction['notes'];
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo date('Y/m/d', strtotime($transaction['created_at'])); ?></td>
                                                        <td class="text-success"><?php echo number_format($transaction['amount']); ?> دینار</td>
                                                        <td><?php echo !empty($displayNotes) ? htmlspecialchars($displayNotes) : '-'; ?></td>
                                                        <td>
                                                            <?php 
                                                            switch($paymentMethod) {
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
                                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-circle edit-debt-btn" data-id="<?php echo $transaction['id']; ?>" title="دەستکاری">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <a href="#" class="btn btn-sm btn-outline-info rounded-circle print-receipt-btn" data-id="<?php echo $transaction['id']; ?>" title="چاپکردن">
                                                                    <i class="fas fa-print"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">هیچ گەڕانەوەیەکی قەرز نەدۆزرایەوە</td>
                                                    </tr>
                                                    <?php endif; ?>
                            </tbody>
                        </table>
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
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
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
                                <input type="number" class="form-control" id="paymentAmount" name="amount" required min="1">
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

    <!-- Edit Debt Transaction Modal -->
    <div class="modal fade" id="editDebtModal" tabindex="-1" aria-labelledby="editDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDebtModalLabel">دەستکاری مامەڵەی قەرز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDebtForm">
                        <input type="hidden" id="debtTransactionId" name="transaction_id">
                        
                        <div class="mb-3">
                            <label for="editDebtAmount" class="form-label">بڕی پارە</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editDebtAmount" name="amount" required min="1">
                                <span class="input-group-text">دینار</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDebtNotes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="editDebtNotes" name="notes" rows="3"></textarea>
                    </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="updateDebtBtn">نوێکردنەوە</button>
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
    <!-- Custom Scripts -->
    <script src="js/include-components.js"></script>
    <!-- Page Specific Script -->
    <script>
        $(document).ready(function() {
            // Initialize pagination for tables
            initTablePagination('sales');
            initTablePagination('debt');
            
            // Table search functionality
            $('#salesTableSearch').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('#salesHistoryTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updatePagination('sales');
            });
            
            $('#debtTableSearch').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('#debtHistoryTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updatePagination('debt');
            });
            
            // Records per page change handlers
            $('#salesRecordsPerPage, #debtRecordsPerPage').on('change', function() {
                const tableId = $(this).attr('id').replace('RecordsPerPage', '');
                updatePagination(tableId);
            });
            
            // Date filter handlers
            $('.auto-filter').on('change', function() {
                if ($(this).closest('form').attr('id') === 'salesFilterForm') {
                    filterTableByDate('sales');
                } else if ($(this).closest('form').attr('id') === 'debtFilterForm') {
                    filterTableByDate('debt');
                }
            });
            
            // Reset filter buttons
            $('#salesResetFilter').on('click', function() {
                $('#salesFilterForm')[0].reset();
                resetTableFilter('sales');
            });
            
            $('#debtResetFilter').on('click', function() {
                $('#debtFilterForm')[0].reset();
                resetTableFilter('debt');
            });
            
            // Save payment button handler
            $('#savePaymentBtn').on('click', function() {
                const formData = new FormData($('#addPaymentForm')[0]);
                
                $.ajax({
                    url: 'ajax/save_debt_transaction.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
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
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
            
            // Edit debt transaction button handler
            $(document).on('click', '.edit-debt-btn', function() {
                const transactionId = $(this).data('id');
                
                // Get transaction details
                $.ajax({
                    url: 'ajax/get_debt_transaction.php',
                    type: 'GET',
                    data: { id: transactionId },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            $('#debtTransactionId').val(data.transaction.id);
                            $('#editDebtAmount').val(data.transaction.amount);
                            
                            // Check if notes are in JSON format and extract just the notes field
                            try {
                                const notesObj = JSON.parse(data.transaction.notes);
                                if (notesObj && typeof notesObj === 'object' && 'notes' in notesObj) {
                                    $('#editDebtNotes').val(notesObj.notes);
                                } else {
                                    $('#editDebtNotes').val(data.transaction.notes);
                                }
                            } catch (e) {
                                // If not valid JSON, use the notes as is
                                $('#editDebtNotes').val(data.transaction.notes);
                            }
                            
                            $('#editDebtModal').modal('show');
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
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
            
            // Update debt transaction button handler
            $('#updateDebtBtn').on('click', function() {
                const formData = new FormData($('#editDebtForm')[0]);
                
                // Get the current transaction data
                const transactionId = $('#debtTransactionId').val();
                
                $.ajax({
                    url: 'ajax/get_debt_transaction.php',
                    type: 'GET',
                    data: { id: transactionId },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const updateFormData = new FormData();
                            updateFormData.append('transaction_id', transactionId);
                            updateFormData.append('amount', $('#editDebtAmount').val());
                            
                            // Check if the original notes were in JSON format
                            try {
                                const originalNotesObj = JSON.parse(data.transaction.notes);
                                if (originalNotesObj && typeof originalNotesObj === 'object') {
                                    // If it was JSON, maintain the same structure but update the notes field
                                    const updatedNotesObj = {...originalNotesObj, notes: $('#editDebtNotes').val()};
                                    updateFormData.append('notes', JSON.stringify(updatedNotesObj));
                                } else {
                                    // If it wasn't JSON originally, just use the plain text
                                    updateFormData.append('notes', $('#editDebtNotes').val());
                                }
                            } catch (e) {
                                // If not valid JSON, use the notes as is
                                updateFormData.append('notes', $('#editDebtNotes').val());
                            }
                            
                            // Send the update request
                            $.ajax({
                                url: 'ajax/update_debt_transaction.php',
                                type: 'POST',
                                data: updateFormData,
                                processData: false,
                                contentType: false,
                                success: function(updateResponse) {
                                    const updateData = JSON.parse(updateResponse);
                                    if (updateData.success) {
                                        Swal.fire({
                                            title: 'سەرکەوتوو بوو!',
                                            text: updateData.message,
                                            icon: 'success',
                                            confirmButtonText: 'باشە'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'هەڵە!',
                                            text: updateData.message,
                                            icon: 'error',
                                            confirmButtonText: 'باشە'
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        title: 'هەڵە!',
                                        text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر.',
                                        icon: 'error',
                                        confirmButtonText: 'باشە'
                                    });
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
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
            
            // Helper functions
            function initTablePagination(tableId) {
                updatePagination(tableId);
                
                // Pagination button handlers
                $(`#${tableId}PrevPageBtn`).on('click', function() {
                    const currentPage = parseInt($(`#${tableId}PaginationNumbers .active`).text());
                    if (currentPage > 1) {
                        goToPage(tableId, currentPage - 1);
                    }
                });
                
                $(`#${tableId}NextPageBtn`).on('click', function() {
                    const currentPage = parseInt($(`#${tableId}PaginationNumbers .active`).text());
                    const totalPages = Math.ceil($(`#${tableId}TotalRecords`).text() / $(`#${tableId}RecordsPerPage`).val());
                    if (currentPage < totalPages) {
                        goToPage(tableId, currentPage + 1);
                    }
                });
            }
            
            function updatePagination(tableId) {
                const recordsPerPage = parseInt($(`#${tableId}RecordsPerPage`).val());
                const visibleRows = $(`#${tableId}HistoryTable tbody tr:visible`).length;
                const totalPages = Math.ceil(visibleRows / recordsPerPage);
                
                // Update total records
                $(`#${tableId}TotalRecords`).text(visibleRows);
                
                // Generate pagination numbers
                let paginationHtml = '';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `<button class="btn btn-sm ${i === 1 ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2 ${i === 1 ? 'active' : ''}" data-page="${i}">${i}</button>`;
                }
                $(`#${tableId}PaginationNumbers`).html(paginationHtml);
                
                // Add click handlers for pagination numbers
                $(`#${tableId}PaginationNumbers button`).on('click', function() {
                    const page = parseInt($(this).data('page'));
                    goToPage(tableId, page);
                });
                
                // Show only first page
                goToPage(tableId, 1);
            }
            
            function goToPage(tableId, page) {
                const recordsPerPage = parseInt($(`#${tableId}RecordsPerPage`).val());
                const visibleRows = $(`#${tableId}HistoryTable tbody tr:visible`);
                
                // Hide all rows
                visibleRows.hide();
                
                // Show rows for current page
                const startIndex = (page - 1) * recordsPerPage;
                const endIndex = startIndex + recordsPerPage;
                
                visibleRows.slice(startIndex, endIndex).show();
                
                // Update pagination UI
                $(`#${tableId}PaginationNumbers button`).removeClass('btn-primary active').addClass('btn-outline-primary');
                $(`#${tableId}PaginationNumbers button[data-page="${page}"]`).removeClass('btn-outline-primary').addClass('btn-primary active');
                
                // Update pagination info
                const startRecord = visibleRows.length > 0 ? startIndex + 1 : 0;
                const endRecord = Math.min(endIndex, visibleRows.length);
                $(`#${tableId}StartRecord`).text(startRecord);
                $(`#${tableId}EndRecord`).text(endRecord);
                
                // Update prev/next buttons
                $(`#${tableId}PrevPageBtn`).prop('disabled', page === 1);
                $(`#${tableId}NextPageBtn`).prop('disabled', page === Math.ceil(visibleRows.length / recordsPerPage));
            }
            
            function filterTableByDate(tableId) {
                const startDate = $(`#${tableId}StartDate`).val();
                const endDate = $(`#${tableId}EndDate`).val();
                const typeFilter = tableId === 'debt' ? $(`#debtType`).val() : '';
                
                $(`#${tableId}HistoryTable tbody tr`).each(function() {
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
            $('.auto-filter').on('change', function() {
                filterSalesTable();
            });

            function filterSalesTable() {
                const productId = $('#productFilter').val();
                const unitType = $('#unitFilter').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                $('#salesHistoryTable tbody tr').each(function() {
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
            $('#resetFilters').on('click', function() {
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

            // Save debt return button handler
            $('#saveDebtReturnBtn').on('click', function() {
                // Validate form
                if (!$('#returnAmount').val() || parseInt($('#returnAmount').val()) <= 0) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە بڕی پارەی گەڕاوە بەدروستی داخل بکە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Check if amount exceeds total debt
                const returnAmount = parseInt($('#returnAmount').val());
                const totalDebt = <?php echo $customer['debit_on_business']; ?>;
                
                if (returnAmount > totalDebt) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'بڕی پارەی گەڕاوە ناتوانێت لە کۆی قەرز زیاتر بێت. کۆی قەرز: ' + totalDebt.toLocaleString() + ' دینار',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Prepare form data
                const formData = new FormData();
                formData.append('customer_id', <?php echo $customer['id']; ?>);
                formData.append('transaction_type', 'collection');
                formData.append('amount', $('#returnAmount').val());
                
                // Create JSON notes to store additional information
                const notesObj = {
                    payment_method: $('#paymentMethod').val(),
                    reference_number: $('#referenceNumber').val() || '',
                    notes: $('#returnNotes').val(),
                    return_date: $('#returnDate').val()
                };
                
                formData.append('notes', JSON.stringify(notesObj));
                
                // Send AJAX request
                $.ajax({
                    url: 'ajax/save_debt_transaction.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
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
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Print receipt button click handler
            $(document).on('click', '.print-receipt-btn', function(e) {
                e.preventDefault();
                const transactionId = $(this).data('id');
                // Redirect to receipt print page
                window.open('printDebtReceipt.php?id=' + transactionId, '_blank');
            });
        });
    </script>
</body>
</html> 