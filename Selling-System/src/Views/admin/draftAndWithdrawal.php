<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '/var/www/html/warehoause-system/Selling-System/src/Controllers/receipts/PurchaseReceiptsController.php';
require_once '/var/www/html/warehoause-system/Selling-System/src/Controllers/receipts/WastingReceiptsController.php';

// Custom number formatting function for Iraqi Dinar
function numberFormat($number)
{
    return number_format($number, 0, '.', ',') . ' د.ع';
}

// Initialize controllers
$purchaseReceiptsController = new PurchaseReceiptsController($conn);
$wastingReceiptsController = new WastingReceiptsController($conn);

// Get initial data for page load
$today = date('Y-m-d');
$startOfMonth = date('Y-m-01');

$defaultFilters = [
    'start_date' => $startOfMonth,
    'end_date' => $today
];

// Get data using controllers
$withdrawalData = $wastingReceiptsController->getWastingData(0, 0, $defaultFilters);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕەشنووسەکان و بەفیڕۆچوو - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Global CSS -->

    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/receiptList.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <style>
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
                    <div class="col-12">
                        <h3 class="page-title">ڕەشنووسەکان و بەفیڕۆچوو</h3>
                    </div>
                </div>

                <!-- Tabs navigation -->
                <div class="row mb-4">
                    <div class="col-12">
                        <ul class="nav nav-tabs expenses-tabs" id="expensesTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="withdrawal-tab" data-bs-toggle="tab"
                                    data-bs-target="#withdrawal-content" type="button" role="tab"
                                    aria-controls="withdrawal-content" aria-selected="true">
                                    <i class="fas fa-money-bill-wave me-2"></i> بەفیڕۆچوو
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sales-tab" data-bs-toggle="tab"
                                    data-bs-target="#sales-content" type="button" role="tab"
                                    aria-controls="sales-content" aria-selected="false">
                                    <i class="fas fa-file-invoice me-2"></i> پسووڵەکان
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tabs content -->
                <div class="tab-content" id="expensesTabsContent">
                    <!-- Money Withdrawal Tab -->
                    <div class="tab-pane fade show active" id="withdrawal-content" role="tabpanel" aria-labelledby="withdrawal-tab">
                        <!-- Date Filter for Withdrawals -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی بەروار</h5>
                                        <form id="withdrawalFilterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="withdrawalStartDate" class="form-label">بەرواری
                                                    دەستپێک</label>
                                                <input type="date" class="form-control auto-filter"
                                                    id="withdrawalStartDate">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="withdrawalEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter"
                                                    id="withdrawalEndDate">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100"
                                                    id="withdrawalResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Withdrawal History Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div
                                        class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">مێژووی بەفیڕۆچوو</h5>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary refresh-btn me-2">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-container">
                                            <!-- Table Controls -->
                                            <div class="table-controls mb-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                                        <div class="records-per-page">
                                                            <label class="me-2">نیشاندان:</label>
                                                            <div class="custom-select-wrapper">
                                                                <select id="withdrawalRecordsPerPage"
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
                                                                <input type="text" id="withdrawalTableSearch"
                                                                    class="form-control rounded-pill-start table-search-input"
                                                                    placeholder="گەڕان لە تەیبڵدا...">
                                                                <span
                                                                    class="input-group-text rounded-pill-end bg-light">
                                                                    <i class="fas fa-search"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Table Content -->
                                            <div class="table-responsive">
                                                <table id="withdrawalHistoryTable"
                                                    class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>بەروار</th>
                                                            <th>کاڵاکان</th>
                                                            <th>کۆی گشتی</th>
                                                            <th>تێبینی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        // Get wasting data with related items
                                                        $stmt = $conn->query("
                                                            SELECT w.*, 
                                                                   GROUP_CONCAT(
                                                                       CONCAT(p.name, ' (', wi.quantity, ' ', 
                                                                       CASE wi.unit_type 
                                                                           WHEN 'piece' THEN 'دانە'
                                                                           WHEN 'box' THEN 'کارتۆن'
                                                                           WHEN 'set' THEN 'سێت'
                                                                       END, ')')
                                                                   SEPARATOR ', ') as products_list,
                                                                   SUM(wi.total_price) as total_amount
                                                            FROM wastings w
                                                            LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
                                                            LEFT JOIN products p ON wi.product_id = p.id
                                                            GROUP BY w.id
                                                            ORDER BY w.date DESC
                                                        ");
                                                        $wastings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                        if (count($wastings) > 0) {
                                                            foreach ($wastings as $index => $wasting) {
                                                                echo '<tr data-id="' . $wasting['id'] . '">';
                                                                echo '<td>' . ($index + 1) . '</td>';
                                                                echo '<td>' . date('Y/m/d', strtotime($wasting['date'])) . '</td>';
                                                                echo '<td class="products-list-cell" data-products="' . htmlspecialchars($wasting['products_list']) . '">';
                                                                echo htmlspecialchars($wasting['products_list']);
                                                                echo '<div class="products-popup"></div>';
                                                                echo '</td>';
                                                                echo '<td>' . number_format($wasting['total_amount']) . ' د.ع</td>';
                                                                echo '<td>' . htmlspecialchars($wasting['notes'] ?? '') . '</td>';
                                                                echo '<td>
                                                                <div class="action-buttons">
                                                                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="' . $wasting['id'] . '">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="' . $wasting['id'] . '">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                                </td>';
                                                                echo '</tr>';
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="6" class="text-center">هیچ بەفیڕۆچوویەک نەدۆزرایەوە</td></tr>';
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
                                                            نیشاندانی <span id="withdrawalStartRecord">1</span> تا <span
                                                                id="withdrawalEndRecord">3</span> لە کۆی <span
                                                                id="withdrawalTotalRecords">3</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="withdrawalPrevPageBtn"
                                                                class="btn btn-sm btn-outline-primary rounded-circle me-2"
                                                                disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="withdrawalPaginationNumbers"
                                                                class="pagination-numbers d-flex">
                                                                <!-- Pagination numbers will be generated by JavaScript -->
                                                                <button
                                                                    class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="withdrawalNextPageBtn"
                                                                class="btn btn-sm btn-outline-primary rounded-circle">
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

                    <!-- Sales Receipts Tab -->
                    <div class="tab-pane fade" id="sales-content" role="tabpanel" aria-labelledby="sales-tab">
                        <!-- Date Filter for Sales -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی بەروار</h5>
                                        <form id="salesFilterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="salesStartDate" class="form-label">بەرواری دەستپێک</label>
                                                <input type="date" class="form-control auto-filter" id="salesStartDate">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="salesEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="salesEndDate">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100" id="salesResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales History Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">مێژووی پسووڵەکان</h5>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary refresh-btn me-2">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
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
                                                            <th>کڕیار</th>
                                                            <th>جۆری پارەدان</th>
                                                            <th>جۆری نرخ</th>
                                                            <th>بڕی پارەدراو</th>
                                                            <th>بڕی ماوە</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        // Get sales data with customer information
                                                        $stmt = $conn->query("
                                                            SELECT s.*, c.name as customer_name,
                                                                   (s.paid_amount + s.remaining_amount) as total_amount
                                                            FROM sales s
                                                            LEFT JOIN customers c ON s.customer_id = c.id
                                                            WHERE s.is_draft = 0
                                                            ORDER BY s.date DESC
                                                        ");
                                                        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                        if (count($sales) > 0) {
                                                            foreach ($sales as $index => $sale) {
                                                                echo '<tr data-id="' . $sale['id'] . '">';
                                                                echo '<td>' . ($index + 1) . '</td>';
                                                                echo '<td>' . htmlspecialchars($sale['invoice_number']) . '</td>';
                                                                echo '<td>' . date('Y/m/d', strtotime($sale['date'])) . '</td>';
                                                                echo '<td>' . htmlspecialchars($sale['customer_name']) . '</td>';
                                                                echo '<td>' . ($sale['payment_type'] == 'cash' ? 'نقد' : 'قەرز') . '</td>';
                                                                echo '<td>' . ($sale['price_type'] == 'single' ? 'تاک' : 'کۆمەڵ') . '</td>';
                                                                echo '<td>' . number_format($sale['paid_amount']) . ' د.ع</td>';
                                                                echo '<td>' . number_format($sale['remaining_amount']) . ' د.ع</td>';
                                                                echo '<td>
                                                                    <div class="action-buttons">
                                                                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="' . $sale['id'] . '">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="' . $sale['id'] . '">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>';
                                                                echo '</tr>';
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="9" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>';
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
                                                            نیشاندانی <span id="salesStartRecord">1</span> تا <span id="salesEndRecord">3</span> لە کۆی <span id="salesTotalRecords">3</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="salesPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="salesPaginationNumbers" class="pagination-numbers d-flex">
                                                                <!-- Pagination numbers will be generated by JavaScript -->
                                                                <button class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load dependencies first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <!-- Then load your custom JavaScript -->
    <script src="../../js/include-components.js"></script>

    <!-- Initialize everything after all scripts are loaded -->
    <script>
    $(document).ready(function() {
        // Function to update debug info (visible only in development)
        function logDebug(message) {
            console.log(message);
        }

        // Function to delete a wasting record
        function handleDeleteWasting(wastingId) {
            Swal.fire({
                title: 'دڵنیای',
                text: 'دڵنیای لە سڕینەوەی ئەم بەفیڕۆچووە؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بەڵێ',
                cancelButtonText: 'نەخێر'
            }).then((result) => {
                if (result.isConfirmed) {
                    logDebug('هەوڵی سڕینەوەی بەفیڕۆچوو: ID=' + wastingId);
                    
                    // First, verify the wasting record exists
                    $.ajax({
                        url: '../../api/receipts/verify_wasting.php',
                        method: 'POST',
                        data: { wasting_id: wastingId },
                        success: function(response) {
                            logDebug('پشکنینی بەفیڕۆچوو: ' + JSON.stringify(response));
                            
                            if (response.exists) {
                                // If wasting record exists, proceed with deletion
                                $.ajax({
                                    url: '../../api/receipts/delete_wasting.php',
                                    method: 'POST',
                                    data: { wasting_id: wastingId },
                                    success: function(response) {
                                        logDebug('ئەنجامی سڕینەوە: ' + JSON.stringify(response));
                                        
                                        if (response.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'سەرکەوتوو',
                                                text: 'بەفیڕۆچووەکە بە سەرکەوتوویی سڕایەوە'
                                            }).then(() => {
                                                if (typeof loadWastingData === 'function') {
                                                    loadWastingData();
                                                } else {
                                                    location.reload();
                                                }
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'هەڵە',
                                                text: response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی بەفیڕۆچوو'
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        logDebug('هەڵەی AJAX: ' + xhr.responseText);
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
                                    text: 'بەفیڕۆچووەکە نەدۆزرایەوە'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            logDebug('هەڵەی پشکنین: ' + xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: 'هەڵەیەک ڕوویدا لە پشکنینی بەفیڕۆچوو'
                            });
                        }
                    });
                }
            });
        }

        // Function to delete a sale record
        function handleDeleteSale(saleId) {
            Swal.fire({
                title: 'دڵنیای',
                text: 'دڵنیای لە سڕینەوەی ئەم پسووڵەیە؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بەڵێ',
                cancelButtonText: 'نەخێر'
            }).then((result) => {
                if (result.isConfirmed) {
                    logDebug('هەوڵی سڕینەوەی پسووڵە: ID=' + saleId);
                    
                    $.ajax({
                        url: '../../api/receipts/delete_sale.php',
                        method: 'POST',
                        data: { sale_id: saleId },
                        success: function(response) {
                            logDebug('ئەنجامی سڕینەوە: ' + JSON.stringify(response));
                            
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'سەرکەوتوو',
                                    text: 'پسووڵەکە بە سەرکەوتوویی سڕایەوە'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی پسووڵە'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            logDebug('هەڵەی AJAX: ' + xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: 'هەڵەیەک ڕوویدا لە پەیوەندی بە سێرڤەرەوە'
                            });
                        }
                    });
                }
            });
        }

        // Handle tab switching to load relevant data
        $('#expensesTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).attr("data-bs-target");
            if (target === '#withdrawal-content') {
                // Load wasting data if not already loaded
                if ($('#withdrawalHistoryTable tbody tr').length <= 1) {
                    if (typeof loadWastingData === 'function') {
                        loadWastingData();
                    }
                }
            } else if (target === '#sales-content') {
                // Load sales data if not already loaded
                if ($('#salesHistoryTable tbody tr').length <= 1) {
                    if (typeof loadSalesData === 'function') {
                        loadSalesData();
                    }
                }
            }
        });
        
        // Initialize product hover functionality for all tables
        if (typeof initProductsListHover === 'function') {
            initProductsListHover();
        }

        // Remove any existing event handlers first
        $(document).off('click', '#withdrawal-content .delete-btn');
        $(document).off('click', '#sales-content .delete-btn');

        // Assign specific click handlers for delete buttons in different tabs
        $(document).on('click', '#withdrawal-content .delete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const wastingId = $(this).data('id');
            handleDeleteWasting(wastingId);
        });

        $(document).on('click', '#sales-content .delete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const saleId = $(this).data('id');
            handleDeleteSale(saleId);
        });

        // Handle view button clicks
        $(document).on('click', '.view-btn', function() {
            const id = $(this).data('id');
            const tabId = $(this).closest('.tab-pane').attr('id');
            
            if (tabId === 'withdrawal-content') {
                // Handle withdrawal view
                window.location.href = `viewWasting.php?id=${id}`;
            } else if (tabId === 'sales-content') {
                // Handle sales view with SweetAlert2
                $.ajax({
                    url: '../../api/receipts/get_sale_details.php',
                    method: 'POST',
                    data: { sale_id: id },
                    success: function(response) {
                        if (response.success) {
                            const sale = response.data;
                            const items = response.items;
                            
                            // Format items list
                            let itemsHtml = '<div class="table-responsive"><table class="table table-bordered">';
                            itemsHtml += '<thead><tr><th>#</th><th>کۆدی کاڵا</th><th>ناوی کاڵا</th><th>بڕ</th><th>جۆری یەکە</th><th>نرخی تاک</th><th>کۆی گشتی</th></tr></thead><tbody>';
                            
                            items.forEach((item, index) => {
                                const unitType = {
                                    'piece': 'دانە',
                                    'box': 'کارتۆن',
                                    'set': 'سێت'
                                }[item.unit_type] || item.unit_type;
                                
                                itemsHtml += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.product_code}</td>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity}</td>
                                        <td>${unitType}</td>
                                        <td>${numberFormat(item.unit_price)}</td>
                                        <td>${numberFormat(item.total_price)}</td>
                                    </tr>
                                `;
                            });
                            
                            itemsHtml += '</tbody></table></div>';
                            
                            // Show sale details in SweetAlert2
                            Swal.fire({
                                title: 'زانیاری پسووڵە',
                                html: `
                                    <div class="text-start">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <p><strong>ژمارەی پسووڵە:</strong> ${sale.invoice_number}</p>
                                                <p><strong>بەروار:</strong> ${formatDate(sale.date)}</p>
                                                <p><strong>کڕیار:</strong> ${sale.customer_name || 'کڕیاری نەناسراو'}</p>
                                                <p><strong>ژمارەی مۆبایل:</strong> ${sale.customer_phone || '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>جۆری پارەدان:</strong> ${sale.payment_type === 'cash' ? 'نقد' : 'قەرز'}</p>
                                                <p><strong>جۆری نرخ:</strong> ${sale.price_type === 'single' ? 'تاک' : 'کۆمەڵ'}</p>
                                                <p><strong>بڕی پارەدراو:</strong> ${numberFormat(sale.paid_amount)}</p>
                                                <p><strong>بڕی ماوە:</strong> ${numberFormat(sale.remaining_amount)}</p>
                                            </div>
                                        </div>
                                        <h5 class="mb-3">کاڵاکان</h5>
                                        ${itemsHtml}
                                    </div>
                                `,
                                width: '80%',
                                customClass: {
                                    container: 'sale-details-modal'
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری پسووڵە'
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

        // Helper function to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ku-IQ');
        }

        // Initialize date filters
        const today = new Date();
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        // Format dates for input fields
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        // Set default dates for both tabs
        $('#withdrawalStartDate').val(formatDate(startOfMonth));
        $('#withdrawalEndDate').val(formatDate(today));
        $('#salesStartDate').val(formatDate(startOfMonth));
        $('#salesEndDate').val(formatDate(today));

        // Handle date filter changes
        $('.auto-filter').on('change', function() {
            const tabId = $(this).closest('.tab-pane').attr('id');
            if (tabId === 'withdrawal-content' && typeof loadWastingData === 'function') {
                loadWastingData();
            } else if (tabId === 'sales-content' && typeof loadSalesData === 'function') {
                loadSalesData();
            }
        });

        // Handle reset filter buttons
        $('#withdrawalResetFilter').on('click', function() {
            $('#withdrawalStartDate').val(formatDate(startOfMonth));
            $('#withdrawalEndDate').val(formatDate(today));
            if (typeof loadWastingData === 'function') {
                loadWastingData();
            }
        });

        $('#salesResetFilter').on('click', function() {
            $('#salesStartDate').val(formatDate(startOfMonth));
            $('#salesEndDate').val(formatDate(today));
            if (typeof loadSalesData === 'function') {
                loadSalesData();
            }
        });
    });
    </script>

    <!-- Load optional module scripts after the main initialization -->
    <script src="../../js/receiptList/tabs/common-receipt-functions.js"></script>
    <script src="../../js/receiptList/tabs/wasting-receipts.js"></script>
</body>

</html> 