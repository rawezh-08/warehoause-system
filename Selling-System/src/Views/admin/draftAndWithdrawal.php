<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../controllers/receipts/SaleReceiptsController.php';
require_once '../controllers/receipts/PurchaseReceiptsController.php';
require_once '../controllers/receipts/WastingReceiptsController.php';
require_once '../controllers/receipts/DraftReceiptsController.php';

// Custom number formatting function for Iraqi Dinar
function numberFormat($number)
{
    return number_format($number, 0, '.', ',') . ' د.ع';
}

// Initialize controllers
$saleReceiptsController = new SaleReceiptsController($conn);
$purchaseReceiptsController = new PurchaseReceiptsController($conn);
$wastingReceiptsController = new WastingReceiptsController($conn);
$draftReceiptsController = new DraftReceiptsController($conn);

// Get initial data for page load
$today = date('Y-m-d');
$startOfMonth = date('Y-m-01');

$defaultFilters = [
    'start_date' => $startOfMonth,
    'end_date' => $today
];

// Get data using controllers
$draftData = $draftReceiptsController->getDraftReceipts(0, 0, $defaultFilters);
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
    <link rel="stylesheet" href="../../assets/css/custom.css">
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
                                <button class="nav-link active" id="draft-tab" data-bs-toggle="tab"
                                    data-bs-target="#draft-content" type="button" role="tab"
                                    aria-controls="draft-content" aria-selected="true">
                                    <i class="fas fa-file-alt me-2"></i> ڕەشنووسەکان
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="withdrawal-tab" data-bs-toggle="tab"
                                    data-bs-target="#withdrawal-content" type="button" role="tab"
                                    aria-controls="withdrawal-content" aria-selected="false">
                                    <i class="fas fa-money-bill-wave me-2"></i> بەفیڕۆچوو
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tabs content -->
                <div class="tab-content" id="expensesTabsContent">
                    <!-- Draft Receipts Tab -->
                    <div class="tab-pane fade show active" id="draft-content" role="tabpanel" aria-labelledby="draft-tab">
                        <!-- Date Filter for Drafts -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی بەروار و ناو</h5>
                                        <form id="draftFilterForm" class="row g-3">
                                            <div class="col-md-3">
                                                <label for="draftStartDate" class="form-label">بەرواری دەستپێک</label>
                                                <input type="date" class="form-control auto-filter" id="draftStartDate">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="draftEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="draftEndDate">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="draftCustomer" class="form-label">کڕیار</label>
                                                <select class="form-select auto-filter" id="draftCustomer">
                                                    <option value="">هەموو کڕیارەکان</option>
                                                    <?php
                                                    // Get all customers from database
                                                    $stmt = $conn->query("SELECT DISTINCT name FROM customers ORDER BY name");
                                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100"
                                                    id="draftResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Draft History Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div
                                        class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">ڕەشنووسی پسووڵەکان</h5>
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
                                                                <select id="draftRecordsPerPage"
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
                                                                <input type="text" id="draftTableSearch"
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
                                                <table id="draftHistoryTable"
                                                    class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ژمارەی پسووڵە</th>
                                                            <th>کڕیار</th>
                                                            <th>بەروار</th>
                                                            <th>جۆری پارەدان</th>
                                                            <th>کۆی گشتی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        // Get draft receipts
                                                        $stmt = $conn->prepare("
                                                            SELECT s.*, c.name as customer_name,
                                                            COALESCE(SUM(si.total_price), 0) as subtotal,
                                                            (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
                                                            FROM sales s
                                                            LEFT JOIN customers c ON s.customer_id = c.id
                                                            LEFT JOIN sale_items si ON s.id = si.sale_id
                                                            WHERE s.is_draft = 1
                                                            GROUP BY s.id
                                                            ORDER BY s.created_at DESC
                                                        ");
                                                        $stmt->execute();
                                                        $draft_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                        
                                                        if (count($draft_receipts) > 0) {
                                                            foreach ($draft_receipts as $index => $receipt) {
                                                                echo '<tr>';
                                                                echo '<td>' . ($index + 1) . '</td>';
                                                                echo '<td>' . htmlspecialchars($receipt['invoice_number']) . ' <span class="draft-badge">ڕەشنووس</span></td>';
                                                                echo '<td>' . htmlspecialchars($receipt['customer_name']) . '</td>';
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
                                                            echo '<tr><td colspan="7" class="text-center">هیچ ڕەشنووسێک نەدۆزرایەوە</td></tr>';
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
                                                            نیشاندانی <span id="draftStartRecord">1</span> تا <span
                                                                id="draftEndRecord">3</span> لە کۆی <span
                                                                id="draftTotalRecords">3</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="draftPrevPageBtn"
                                                                class="btn btn-sm btn-outline-primary rounded-circle me-2"
                                                                disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="draftPaginationNumbers"
                                                                class="pagination-numbers d-flex">
                                                                <!-- Pagination numbers will be generated by JavaScript -->
                                                                <button
                                                                    class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="draftNextPageBtn"
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
                    
                    <!-- Money Withdrawal Tab -->
                    <div class="tab-pane fade" id="withdrawal-content" role="tabpanel" aria-labelledby="withdrawal-tab">
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

        // Function to delete a draft receipt
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
                    logDebug('هەوڵی سڕینەوەی ڕەشنووس: ID=' + receiptId);
                    
                    // First, verify the draft exists
                    $.ajax({
                        url: '../../api/receipts/verify_draft.php',
                        method: 'POST',
                        data: { receipt_id: receiptId },
                        success: function(response) {
                            logDebug('پشکنینی ڕەشنووس: ' + JSON.stringify(response));
                            
                            if (response.exists) {
                                // If draft exists, proceed with deletion
                                $.ajax({
                                    url: '../../api/receipts/delete_draft.php',
                                    method: 'POST',
                                    data: { receipt_id: receiptId },
                                    success: function(response) {
                                        logDebug('ئەنجامی سڕینەوە: ' + JSON.stringify(response));
                                        
                                        if (response.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'سەرکەوتوو',
                                                text: 'ڕەشنووسەکە بە سەرکەوتوویی سڕایەوە'
                                            }).then(() => {
                                                if (typeof loadDraftReceipts === 'function') {
                                                    loadDraftReceipts();
                                                } else {
                                                    location.reload();
                                                }
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'هەڵە',
                                                text: response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی ڕەشنووس'
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
                                    text: 'ڕەشنووسەکە نەدۆزرایەوە'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            logDebug('هەڵەی پشکنین: ' + xhr.responseText);
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

        // Handle tab switching to load relevant data
        $('#expensesTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).attr("data-bs-target");
            if (target === '#draft-content') {
                // Load draft data if not already loaded
                if ($('#draftHistoryTable tbody tr').length <= 1) {
                    if (typeof loadDraftReceipts === 'function') {
                        loadDraftReceipts();
                    }
                }
            } else if (target === '#withdrawal-content') {
                // Load wasting data if not already loaded
                if ($('#withdrawalHistoryTable tbody tr').length <= 1) {
                    if (typeof loadWastingData === 'function') {
                        loadWastingData();
                    }
                }
            }
        });
        
        // Initialize product hover functionality for all tables
        if (typeof initProductsListHover === 'function') {
            initProductsListHover();
        }

        // Remove any existing event handlers first
        $(document).off('click', '#draft-content .delete-btn');
        $(document).off('click', '#withdrawal-content .delete-btn');

        // Assign specific click handlers for delete buttons in different tabs
        $(document).on('click', '#draft-content .delete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const receiptId = $(this).data('id');
            handleDeleteDraft(receiptId);
        });

        $(document).on('click', '#withdrawal-content .delete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const wastingId = $(this).data('id');
            handleDeleteWasting(wastingId);
        });
    });
    </script>

    <!-- Load optional module scripts after the main initialization -->
    <script src="../../js/receiptList/tabs/common-receipt-functions.js"></script>
    <script src="../../js/receiptList/tabs/draft-receipts.js"></script>
    <script src="../../js/receiptList/tabs/wasting-receipts.js"></script>
</body>

</html> 