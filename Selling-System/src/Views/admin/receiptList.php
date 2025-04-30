<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all sales with details
$salesQuery = "SELECT s.*, 
               c.name as customer_name, 
               c.phone1 as customer_phone,
               SUM(si.total_price) as total_amount
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id
               LEFT JOIN sale_items si ON s.id = si.sale_id 
               GROUP BY s.id
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to calculate the total for a sale
function calculateSaleTotal($saleId, $conn) {
    $query = "SELECT SUM(total_price) as total FROM sale_items WHERE sale_id = :sale_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

// Format date for display
function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی پسووڵەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/reports.css">

    <link rel="stylesheet" href="../../test/main.css">
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
    <div id="main-container"\>
<div id="navbar-container"></div>

<!-- Sidebar container - populated by JavaScript -->
<div id="sidebar-container"></div>

    <div class="container-fluid mt-3">
        <h2 class="text-center mb-4"><i class="fas fa-receipt"></i> لیستی پسووڵەکان</h2>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs custom-tabs mb-4" id="receiptTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab" aria-controls="sales" aria-selected="true">
                    <i class="fas fa-shopping-cart"></i> پسووڵەکانی فرۆشتن
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab" aria-controls="purchases" aria-selected="false">
                    <i class="fas fa-truck"></i> پسووڵەکانی کڕین
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab" aria-controls="returns" aria-selected="false">
                    <i class="fas fa-undo"></i> پسووڵەکانی گەڕانەوە
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="receiptTabsContent">
            <!-- Sales Tab -->
            <div class="tab-pane fade show active" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> پسووڵەکانی فرۆشتن</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" id="salesSearchInput" class="form-control table-search-input" placeholder="گەڕان بۆ پسووڵە...">
                                    <span class="input-group-text bg-primary text-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table custom-table table-hover mb-0" id="salesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>ژمارەی پسووڵە</th>
                                        <th>ناوی کڕیار</th>
                                        <th>ژمارەی پەیوەندی</th>
                                        <th>بەروار</th>
                                        <th>جۆری پارەدان</th>
                                        <th>کۆی گشتی</th>
                                        <th>دۆخ</th>
                                        <th>کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($sales)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">هیچ پسووڵەیەک نەدۆزرایەوە</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($sales as $index => $sale): ?>
                                            <?php 
                                                $total = $sale['total_amount'] ?? 0;
                                                $paymentStatus = 'unpaid';
                                                if ($sale['payment_type'] == 'cash' || $sale['paid_amount'] >= $total) {
                                                    $paymentStatus = 'paid';
                                                } elseif ($sale['paid_amount'] > 0) {
                                                    $paymentStatus = 'partial';
                                                }
                                            ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($sale['invoice_number']) ?></td>
                                                <td><?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($sale['customer_phone'] ?? 'N/A') ?></td>
                                                <td><?= formatDate($sale['date']) ?></td>
                                                <td>
                                                    <?php if($sale['payment_type'] == 'cash'): ?>
                                                        <span class="badge bg-success">نەقد</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">قەرز</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= number_format($total, 0, '.', ',') ?> IQD</td>
                                                <td>
                                                    <?php if($paymentStatus == 'paid'): ?>
                                                        <span class="badge bg-success">پارەدراوە</span>
                                                    <?php elseif($paymentStatus == 'partial'): ?>
                                                        <span class="badge bg-warning">بەشێکی دراوە</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">پارە نەدراوە</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <a href="saleDetails.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info view-receipt-btn" data-receipt-id="<?= $sale['id'] ?>" data-receipt-type="sale">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="../../ Views/receipt/printReceipt.php?id=<?= $sale['id'] ?>&type=sale" class="btn btn-sm btn-primary print-receipt-btn" target="_blank" data-receipt-id="<?= $sale['id'] ?>" data-receipt-type="sale">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="pagination-wrapper">
                            <div class="pagination-info">
                                <span id="salesShowing">نیشاندانی <span id="salesFrom">1</span> بۆ <span id="salesTo">10</span> لە <span id="salesTotalItems">0</span> پسووڵە</span>
                            </div>
                            <div class="pagination-controls">
                                <button class="btn btn-sm btn-outline-secondary" id="salesPrevPage" disabled>
                                    <i class="fas fa-chevron-right"></i> پێشوو
                                </button>
                                <div class="pagination-numbers" id="salesPagination"></div>
                                <button class="btn btn-sm btn-outline-secondary" id="salesNextPage">
                                    دواتر <i class="fas fa-chevron-left"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchases Tab (Placeholder for future implementation) -->
            <div class="tab-pane fade" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <h4 class="text-muted"><i class="fas fa-truck mb-3 fa-3x"></i></h4>
                        <h5>پسووڵەکانی کڕین</h5>
                        <p class="text-muted">ئەم بەشە بەمزوانە چالاک دەکرێت</p>
                    </div>
                </div>
            </div>

            <!-- Returns Tab (Placeholder for future implementation) -->
            <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <h4 class="text-muted"><i class="fas fa-undo mb-3 fa-3x"></i></h4>
                        <h5>پسووڵەکانی گەڕانەوە</h5>
                        <p class="text-muted">ئەم بەشە بەمزوانە چالاک دەکرێت</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Page Script -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/receiptList.js"></script>
</body>

</html> 