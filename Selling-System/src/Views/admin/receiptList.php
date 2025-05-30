<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all sales with details
$salesQuery = "SELECT s.id, s.invoice_number, s.date, s.customer_id, 
               s.shipping_cost, s.other_costs, s.discount, s.payment_type, 
               s.paid_amount, s.remaining_amount, s.is_draft, s.is_delivery, 
               c.name as customer_name,
               (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as total_amount
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id
               WHERE s.is_draft = 0 AND s.is_delivery = 0
               GROUP BY s.id
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get delivery receipts
$deliveryQuery = "SELECT s.*, 
                  c.name as customer_name,
                  (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as total_amount
                  FROM sales s 
                  LEFT JOIN customers c ON s.customer_id = c.id
                  WHERE s.is_delivery = 1
                  GROUP BY s.id
                  ORDER BY s.date DESC";
$deliveryStmt = $conn->prepare($deliveryQuery);
$deliveryStmt->execute();
$deliveries = $deliveryStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all delivery items for modal
$deliveryItemsQuery = "SELECT s.*, 
                       p.name as product_name,
                       p.code as product_code,
                       si.quantity,
                       si.unit_type,
                       si.unit_price,
                       si.total_price
                       FROM sales s 
                       LEFT JOIN sale_items si ON s.id = si.sale_id
                       LEFT JOIN products p ON si.product_id = p.id
                       WHERE s.is_delivery = 1";
$deliveryItemsStmt = $conn->prepare($deliveryItemsQuery);
$deliveryItemsStmt->execute();
$deliveryItems = $deliveryItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get draft receipts
$draftQuery = "SELECT s.*, 
               c.name as customer_name,
               (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as total_amount
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id
               WHERE s.is_draft = 1
               GROUP BY s.id
               ORDER BY s.date DESC";
$draftStmt = $conn->prepare($draftQuery);
$draftStmt->execute();
$drafts = $draftStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all draft items for modal
$draftItemsQuery = "SELECT s.*, 
                    p.name as product_name,
                    p.code as product_code,
                    si.quantity,
                    si.unit_type,
                    si.unit_price,
                    si.total_price
                    FROM sales s 
                    LEFT JOIN sale_items si ON s.id = si.sale_id
                    LEFT JOIN products p ON si.product_id = p.id
                    WHERE s.is_draft = 1";
$draftItemsStmt = $conn->prepare($draftItemsQuery);
$draftItemsStmt->execute();
$draftItems = $draftItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all wastings
$wastingsQuery = "SELECT w.id, w.date, w.notes, w.created_at, w.updated_at,
                  COUNT(wi.id) as item_count,
                  SUM(wi.total_price) as total_amount
                  FROM wastings w 
                  LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
                  GROUP BY w.id
                  ORDER BY w.date DESC";
$wastingsStmt = $conn->prepare($wastingsQuery);
$wastingsStmt->execute();
$wastings = $wastingsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all wasting items for modal display
$wastingItemsQuery = "SELECT w.id as wasting_id, w.date,
                      p.name as product_name,
                      p.code as product_code,
                      wi.quantity,
                      wi.unit_type,
                      wi.unit_price,
                      wi.total_price
                      FROM wastings w 
                      LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
                      LEFT JOIN products p ON wi.product_id = p.id
                      ORDER BY w.date DESC";
$wastingItemsStmt = $conn->prepare($wastingItemsQuery);
$wastingItemsStmt->execute();
$wastingItems = $wastingItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all sale items with product details for modal display
$saleItemsQuery = "SELECT s.id as sale_id, s.invoice_number, 
                   p.name as product_name,
                   p.code as product_code,
                   si.quantity,
                   si.unit_type,
                   si.unit_price,
                   si.total_price
                   FROM sales s 
                   JOIN sale_items si ON s.id = si.sale_id
                   JOIN products p ON si.product_id = p.id
                   WHERE s.is_draft = 0 AND s.is_delivery = 0
                   ORDER BY s.date DESC";
$saleItemsStmt = $conn->prepare($saleItemsQuery);
$saleItemsStmt->execute();
$saleItems = $saleItemsStmt->fetchAll(PDO::FETCH_ASSOC);

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

// Function to translate unit type to Kurdish
function translateUnitType($unitType) {
    switch ($unitType) {
        case 'piece':
            return 'دانە';
        case 'box':
            return 'کارتۆن';
        case 'set':
            return 'سێت';
        default:
            return '-';
    }
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
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/products.css">

    <link rel="stylesheet" href="../../test/main.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Custom styles for this page -->
    <style>
        .badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            letter-spacing: 0.3px;
            font-weight: 500;
        }
        
        .badge.rounded-pill {
            border-radius: 50rem;
            padding-right: 12px;
            padding-left: 12px;
        }
        
        .badge.bg-danger {
            background-color: rgba(220, 53, 69, 0.2) !important;
            color: #dc3545 !important;
            border: 1px solid rgba(220, 53, 69, 0.4);
        }
        
        .badge.bg-warning {
            background-color: rgba(255, 193, 7, 0.2) !important;
            color: #e0a800 !important;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }
        
        .badge.bg-success {
            background-color: rgba(25, 135, 84, 0.2) !important;
            color: #198754 !important;
            border: 1px solid rgba(25, 135, 84, 0.4);
        }
        
        /* RTL specific fixes */
        .sidebar-menu .menu-item a i {
            margin-left: 8px;
            margin-right: 0;
        }
        
        .sidebar-menu .menu-item a {
            display: flex;
            align-items: center;
        }
        
        /* Fix for cart icon in sidebar */
        .sidebar .menu-item a span {
            display: inline-block;
        }
        
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
            padding: 0.85rem;
            border: 1px solid #eaeaea;
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
            background-color: #cde1ff;
            border: none !important;
        }

        /* Adjust pagination display */
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .pagination-numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .pagination-numbers .btn {
            min-width: 35px;
            height: 35px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            border-radius: 50%;
            margin: 0 2px;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }

        .action-buttons .btn i {
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
            border-color: transparent;
            color: #0d6efd;
        }

        .custom-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            border-bottom: 3px solid #0d6efd;
            font-weight: 600;
        }

        .custom-tabs .nav-item .nav-link i {
            margin-left: 0.5rem;
        }
        
        /* Fix for buttons and icon display in RTL mode */
        button, .btn {
            direction: rtl !important;
            text-align: right !important;
        }
        
        /* Fix for RTL alignment in sidebar */
        .sidebar .menu-item .item-link {
            display: flex !important;
            align-items: center !important;
            text-align: right !important;
        }
        
        .sidebar .menu-item .item-link span {
            order: 2;
        }
        
        .sidebar .menu-item .item-link .icon-cont {
            order: 1;
            margin-left: 10px;
        }
        
        .sidebar .menu-item .item-link .dropdown-icon {
            order: 3;
            margin-right: auto;
        }
        
        /* Fix for cart icon specifically */
        #psoola-btn {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
        }
        
        #psoola-btn i {
            margin-left: 8px !important;
            margin-right: 0 !important;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 68vh;
            border-radius: 0.25rem;
        }

        /* Filter section styles */
        .filter-section {
            background-color: #f8f9ff;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 24px; 
        }

        /* Custom table styles */
        .custom-table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 16px;
            margin: 0;
            border: none;
        }
        
        .custom-table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        .custom-table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Card styling */
        .card {
            border-radius: 24px !important; 
            border: 1px solid #9ec5ff !important;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: transparent !important;
            border-bottom: 1px solid #e3f2fd !important;
            padding: 1.25rem 1.5rem !important;
        }

        .card-footer {
            background-color: transparent !important;
            border-top: 1px solid #e3f2fd !important;
            padding: 1rem 1.5rem !important;
        }

        /* Main content margin fix */
        .main-content {
            margin-top: 60px !important;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .custom-table td,
            .custom-table th {
                min-width: 120px;
            }
        }

        /* Fix margin for main content */
        #main-content-area {
            margin-top: 100px !important;
            padding-top: 20px !important;
        }
        
        /* Make sure the page title is visible */
        .page-title {
            margin-top: 10px !important;
            margin-bottom: 20px !important;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div id="main-container">
        <div id="navbar-container"></div>

        <!-- Sidebar container - populated by JavaScript -->
        <div id="sidebar-container"></div>

        <div class="main-content p-3" id="main-content-area">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="page-title">لیستی پسووڵەکان</h3>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs custom-tabs mb-4" id="receiptTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab" aria-controls="sales" aria-selected="true">
                            <i class="fas fa-shopping-cart"></i> پسووڵەکانی فرۆشتن
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="delivery-tab" data-bs-toggle="tab" data-bs-target="#delivery" type="button" role="tab" aria-controls="delivery" aria-selected="false">
                            <i class="fas fa-truck"></i> پسووڵەکانی گەیاندن
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts" type="button" role="tab" aria-controls="drafts" aria-selected="false">
                            <i class="fas fa-file-alt"></i> پسووڵە ڕەش نووسەکان
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab" aria-controls="returns" aria-selected="false">
                            <i class="fas fa-undo"></i> بەفیڕۆچوو
                        </button>
                    </li>
                </ul>

                <!-- Filter Section -->
                <div class="card mb-4 filter-section">
                    <div class="card-body p-3">
                        <form id="receiptFiltersForm">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="customerFilter" class="form-label">ناوی کڕیار</label>
                                    <select id="customerFilter" class="form-select customer-select">
                                        <option value="">هەموو کڕیارەکان</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="paymentTypeFilter" class="form-label">جۆری پارەدان</label>
                                    <select id="paymentTypeFilter" class="form-select">
                                        <option value="">هەموو جۆرەکان</option>
                                        <option value="cash">نەقد</option>
                                        <option value="debt">قەرز</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-redo-alt me-1"></i> ڕیسێت
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content" id="receiptTabsContent">
                    <!-- Sales Tab -->
                    <div class="tab-pane fade show active" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                             
                                <div class="table-controls mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page d-flex align-items-center">
                                                <label class="me-2 mb-0">نیشاندان:</label>
                                                <select id="salesRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
                                                    <option value="5">5</option>
                                                    <option value="10" selected>10</option>
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="salesSearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive p-2" style="background-color: #fff; border-radius: 16px;">
                                    <table id="salesHistoryTable" class="table table-bordered custom-table table-hover" style="border-radius: 16px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="background-color: #cde1ff; border: none;">#</th>
                                                <th style="background-color: #cde1ff; border: none;">ژمارەی پسووڵە</th>
                                                <th style="background-color: #cde1ff; border: none;">بەروار</th>
                                                <th style="background-color: #cde1ff; border: none;">ناوی کڕیار</th>
                                                <th style="background-color: #cde1ff; border: none;">نرخی گشتی</th>
                                                <th style="background-color: #cde1ff; border: none;">کرێی گواستنەوە</th>
                                                <th style="background-color: #cde1ff; border: none;">خەرجی تر</th>
                                                <th style="background-color: #cde1ff; border: none;">داشکاندن</th>
                                                <th style="background-color: #cde1ff; border: none;">جۆری پارەدان</th>
                                                <th style="background-color: #cde1ff; border: none;">کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($sales)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-4">هیچ پسووڵەیەک نەدۆزرایەوە</td>
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
                                                        <td><?= formatDate($sale['date']) ?></td>
                                                        <td><?= htmlspecialchars($sale['customer_name'] ?? '-') ?></td>
                                                        <td><?= number_format($total, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($sale['shipping_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($sale['other_costs'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($sale['discount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td>
                                                            <?php if($sale['payment_type'] == 'cash'): ?>
                                                                <span class="badge bg-success">نەقد</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">قەرز</span>
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
                                                                        class="btn btn-sm btn-outline-info rounded-circle show-receipt-items"
                                                                        data-invoice="<?php echo $sale['invoice_number']; ?>"
                                                                        title="بینینی هەموو کاڵاکان">
                                                                        <i class="fas fa-list"></i>
                                                                    </button>
                                                                </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="pagination-wrapper">
                                   
                                    <div class="pagination-controls">
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="salesPrevPage" disabled>
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <div class="pagination-numbers" id="salesPagination"></div>
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="salesNextPage">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Tab -->
                    <div class="tab-pane fade" id="delivery" role="tabpanel" aria-labelledby="delivery-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                        
                                <div class="table-controls mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page d-flex align-items-center">
                                                <label class="me-2 mb-0">نیشاندان:</label>
                                                <select id="deliveryRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
                                                    <option value="5">5</option>
                                                    <option value="10" selected>10</option>
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="deliverySearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive p-2" style="background-color: #fff; border-radius: 16px;">
                                    <table id="deliveryTable" class="table table-bordered custom-table table-hover" style="border-radius: 16px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="background-color: #cde1ff; border: none;">#</th>
                                                <th style="background-color: #cde1ff; border: none;">ژمارەی پسووڵە</th>
                                                <th style="background-color: #cde1ff; border: none;">بەروار</th>
                                                <th style="background-color: #cde1ff; border: none;">ناوی کڕیار</th>
                                                <th style="background-color: #cde1ff; border: none;">نرخی گشتی</th>
                                                <th style="background-color: #cde1ff; border: none;">کرێی گواستنەوە</th>
                                                <th style="background-color: #cde1ff; border: none;">خەرجی تر</th>
                                                <th style="background-color: #cde1ff; border: none;">داشکاندن</th>
                                                <th style="background-color: #cde1ff; border: none;">جۆری پارەدان</th>
                                                <th style="background-color: #cde1ff; border: none;">کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($deliveries)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-4">هیچ پسووڵەیەکی گەیاندن نەدۆزرایەوە</td>
                                            </tr>
                                            <?php else: ?>
                                                <?php foreach($deliveries as $index => $delivery): ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($delivery['invoice_number']) ?></td>
                                                        <td><?= formatDate($delivery['date']) ?></td>
                                                        <td><?= htmlspecialchars($delivery['customer_name'] ?? '-') ?></td>
                                                        <td><?= number_format($delivery['total_amount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($delivery['shipping_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($delivery['other_costs'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($delivery['discount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td>
                                                            <?php if($delivery['payment_type'] == 'cash'): ?>
                                                                <span class="badge bg-success">نەقد</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">قەرز</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="../../Views/receipt/delivery_receipt.php?sale_id=<?= $delivery['id'] ?>"
                                                                    class="btn btn-sm btn-outline-success rounded-circle"
                                                                    title="چاپکردن">
                                                                    <i class="fas fa-print"></i>
                                                                </a>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-receipt-items"
                                                                    data-invoice="<?php echo $delivery['invoice_number']; ?>"
                                                                    title="بینینی هەموو کاڵاکان">
                                                                    <i class="fas fa-list"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="pagination-wrapper">
                                    <div class="pagination-controls">
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="deliveryPrevPage" disabled>
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <div class="pagination-numbers" id="deliveryPagination"></div>
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="deliveryNextPage">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Drafts Tab -->
                    <div class="tab-pane fade" id="drafts" role="tabpanel" aria-labelledby="drafts-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                        
                                <div class="table-controls mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page d-flex align-items-center">
                                                <label class="me-2 mb-0">نیشاندان:</label>
                                                <select id="draftsRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
                                                    <option value="5">5</option>
                                                    <option value="10" selected>10</option>
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="draftsSearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive p-2" style="background-color: #fff; border-radius: 16px;">
                                    <table id="draftsTable" class="table table-bordered custom-table table-hover" style="border-radius: 16px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="background-color: #cde1ff; border: none;">#</th>
                                                <th style="background-color: #cde1ff; border: none;">ژمارەی پسووڵە</th>
                                                <th style="background-color: #cde1ff; border: none;">بەروار</th>
                                                <th style="background-color: #cde1ff; border: none;">ناوی کڕیار</th>
                                                <th style="background-color: #cde1ff; border: none;">نرخی گشتی</th>
                                                <th style="background-color: #cde1ff; border: none;">کرێی گواستنەوە</th>
                                                <th style="background-color: #cde1ff; border: none;">خەرجی تر</th>
                                                <th style="background-color: #cde1ff; border: none;">داشکاندن</th>
                                                <th style="background-color: #cde1ff; border: none;">جۆری پارەدان</th>
                                                <th style="background-color: #cde1ff; border: none;">کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($drafts)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-4">هیچ پسووڵەیەکی ڕەش نووس نەدۆزرایەوە</td>
                                            </tr>
                                            <?php else: ?>
                                                <?php foreach($drafts as $index => $draft): ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($draft['invoice_number']) ?></td>
                                                        <td><?= formatDate($draft['date']) ?></td>
                                                        <td><?= htmlspecialchars($draft['customer_name'] ?? '-') ?></td>
                                                        <td><?= number_format($draft['total_amount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($draft['shipping_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($draft['other_costs'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td><?= number_format($draft['discount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td>
                                                            <?php if($draft['payment_type'] == 'cash'): ?>
                                                                <span class="badge bg-success">نەقد</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">قەرز</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="<?php echo (isset($draft['is_delivery']) && $draft['is_delivery'] == 1) ? 
                                                                    '../../Views/receipt/delivery_receipt.php?sale_id=' . $draft['id'] : 
                                                                    '../../Views/receipt/print_receipt.php?sale_id=' . $draft['id']; ?>"
                                                                    class="btn btn-sm btn-outline-success rounded-circle"
                                                                    title="چاپکردن">
                                                                    <i class="fas fa-print"></i>
                                                                </a>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-receipt-items"
                                                                    data-invoice="<?php echo $draft['invoice_number']; ?>"
                                                                    title="بینینی هەموو کاڵاکان">
                                                                    <i class="fas fa-list"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="pagination-wrapper">
                                    <div class="pagination-controls">
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="draftsPrevPage" disabled>
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <div class="pagination-numbers" id="draftsPagination"></div>
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="draftsNextPage">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Returns Tab -->
                    <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                              
                                <div class="table-controls mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page d-flex align-items-center">
                                                <label class="me-2 mb-0">نیشاندان:</label>
                                                <select id="returnsRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
                                                    <option value="5">5</option>
                                                    <option value="10" selected>10</option>
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="returnsSearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive p-2" style="background-color: #fff; border-radius: 16px;">
                                    <table id="returnsTable" class="table table-bordered custom-table table-hover" style="border-radius: 16px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="background-color: #cde1ff; border: none;">#</th>
                                                <th style="background-color: #cde1ff; border: none;">بەروار</th>
                                                <th style="background-color: #cde1ff; border: none;">ژمارەی کاڵاکان</th>
                                                <th style="background-color: #cde1ff; border: none;">کۆی گشتی</th>
                                                <th style="background-color: #cde1ff; border: none;">کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($wastings)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">هیچ پسووڵەیەکی بەفیڕۆچوو نەدۆزرایەوە</td>
                                            </tr>
                                            <?php else: ?>
                                                <?php foreach($wastings as $index => $wasting): ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><?= formatDate($wasting['date']) ?></td>
                                                        <td><?= htmlspecialchars($wasting['item_count'] ?? '0') ?></td>
                                                        <td><?= number_format($wasting['total_amount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="../../Views/receipt/print_wasting.php?wasting_id=<?= $wasting['id'] ?>"
                                                                    class="btn btn-sm btn-outline-success rounded-circle"
                                                                    title="چاپکردن">
                                                                    <i class="fas fa-print"></i>
                                                                </a>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-receipt-items"
                                                                    data-wasting-id="<?php echo $wasting['id']; ?>"
                                                                    title="بینینی هەموو کاڵاکان">
                                                                    <i class="fas fa-list"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="pagination-wrapper">
                                    <div class="pagination-controls">
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="returnsPrevPage" disabled>
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <div class="pagination-numbers" id="returnsPagination"></div>
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="returnsNextPage">
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Page Script -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/ajax-config.js"></script>
    <script src="../../js/receipt-filters.js"></script>
    <script>
    $(document).ready(function() {
        // Map of table identifiers to actual table IDs
        const tableMap = {
            'sales': 'salesHistoryTable',
            'delivery': 'deliveryTable',
            'drafts': 'draftsTable',
            'returns': 'returnsTable'
        };
        
        // Variables used by all tables
        let currentPageMap = {
            'sales': 1,
            'delivery': 1,
            'drafts': 1,
            'returns': 1
        };
        
        let itemsPerPageMap = {
            'sales': 10,
            'delivery': 10,
            'drafts': 10,
            'returns': 10
        };
        
        // Global functions to allow access from tab switching handler
        function globalShowPage(tableId, page) {
            const actualTableId = tableMap[tableId];
            const tableBody = $(`#${actualTableId} tbody`);
            const allRows = tableBody.find('tr');
            
            // Important: First remove any existing no-records-row
            tableBody.find('.no-records-row').remove();
            
            // Reset visibility - make all rows visible before filtering
            allRows.show();
            
            // Apply current search filter
            applySearchFilter(tableId);
            
            // Get truly visible rows (not hidden by search/filter)
            const visibleRows = tableBody.find('tr:visible');
            
            // If no visible rows, show "no records" message
            if (visibleRows.length === 0) {
                // Add a "no records" message row
                const colCount = $(`#${actualTableId} thead th`).length;
                const noRecordsRow = $(`<tr class="no-records-row"><td colspan="${colCount}" class="text-center py-4">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>`);
                tableBody.append(noRecordsRow);
                
                // Update pagination
                const totalItems = 0;
                const totalPages = 0;
                updatePagination(tableId, totalItems, totalPages);
                return;
            }
            
            // Calculate start and end indexes
            const itemsPerPage = itemsPerPageMap[tableId];
            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            
            // Hide all visible rows first
            visibleRows.hide();
            
            // Show only rows for current page
            visibleRows.slice(startIndex, endIndex).show();
            
            // Update pagination buttons and counts
            const totalItems = visibleRows.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            // Make sure current page is valid
            if (page > totalPages) {
                page = totalPages || 1;
                currentPageMap[tableId] = page;
            }
            
            // Update pagination
            updatePagination(tableId, totalItems, totalPages);
            
            // Update next/prev button states
            $(`#${tableId}PrevPage`).prop('disabled', page === 1);
            $(`#${tableId}NextPage`).prop('disabled', page === totalPages || totalPages === 0);
        }
        
        function applySearchFilter(tableId) {
            const actualTableId = tableMap[tableId];
            const searchTerm = $(`#${tableId}SearchInput`).val().toLowerCase();
            
            // Apply current filters from filter form using the correct table ID
            applyCustomerFilter(actualTableId);
            applyPaymentTypeFilter(actualTableId);
            
            // Skip filtering if search is empty to show all rows
            if (!searchTerm) return; 
            
            const allRows = $(`#${actualTableId} tbody tr`).not('.no-records-row');
            
            allRows.each(function() {
                const rowText = $(this).text().toLowerCase();
                const match = rowText.indexOf(searchTerm) > -1;
                $(this).toggle(match);
            });
        }
        
        function applyCustomerFilter(tableId) {
            const customerFilter = $('#customerFilter').val();
            if (!customerFilter) return; // Skip if no customer selected
            
            // Apply to current table only
            const allRows = $(`#${tableId} tbody tr`).not('.no-records-row');
            
            allRows.each(function() {
                const customerName = $(this).find('td:eq(3)').text().trim();
                const match = customerName === customerFilter;
                $(this).toggle(match);
            });
        }
        
        function applyPaymentTypeFilter(tableId) {
            const paymentTypeFilter = $('#paymentTypeFilter').val();
            if (!paymentTypeFilter) return; // Skip if no payment type selected
            
            // Apply to current table only
            const allRows = $(`#${tableId} tbody tr`).not('.no-records-row');
            
            allRows.each(function() {
                // Payment type is in a badge in column with the "naqd" or "qarz" text
                const paymentType = $(this).text().toLowerCase();
                let match = true;
                
                if (paymentTypeFilter === 'cash' && paymentType.indexOf('نەقد') === -1) {
                    match = false;
                } else if (paymentTypeFilter === 'debt' && paymentType.indexOf('قەرز') === -1) {
                    match = false;
                }
                
                $(this).toggle(match);
            });
        }
        
        function updatePagination(tableId, totalItems, totalPages) {
            const pagination = $(`#${tableId}Pagination`);
            pagination.empty();
            
            // Current page from map
            const currentPage = currentPageMap[tableId];

            // Don't show pagination if there are no pages
            if (totalPages === 0) {
                $(`#${tableId}PrevPage`).prop('disabled', true);
                $(`#${tableId}NextPage`).prop('disabled', true);
                return;
            }
            
            // Limit number of page buttons to display
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

            if (endPage - startPage + 1 < maxPagesToShow && startPage > 1) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            // Add first page button if not visible
            if (startPage > 1) {
                const firstPageBtn = $('<button class="btn btn-sm btn-outline-secondary rounded-circle">١</button>');
                firstPageBtn.on('click', function() {
                    currentPageMap[tableId] = 1;
                    globalShowPage(tableId, 1);
                });
                pagination.append(firstPageBtn);
                
                // Add ellipsis if there's a gap
                if (startPage > 2) {
                    pagination.append('<span class="px-1">...</span>');
                }
            }

            // Add page buttons
            for (let i = startPage; i <= endPage; i++) {
                // Convert to Kurdish numerals
                const kurdishNum = convertToKurdishNumerals(i);
                const pageButton = $('<button class="btn btn-sm ' + (i === currentPage ? 'btn-primary' : 'btn-outline-secondary') + ' rounded-circle">' + kurdishNum + '</button>');
                pageButton.on('click', function() {
                    currentPageMap[tableId] = i;
                    globalShowPage(tableId, i);
                });
                pagination.append(pageButton);
            }

            // Add last page button if not visible
            if (endPage < totalPages) {
                // Add ellipsis if there's a gap
                if (endPage < totalPages - 1) {
                    pagination.append('<span class="px-1">...</span>');
                }
                
                const lastPageBtn = $('<button class="btn btn-sm btn-outline-secondary rounded-circle">' + convertToKurdishNumerals(totalPages) + '</button>');
                lastPageBtn.on('click', function() {
                    currentPageMap[tableId] = totalPages;
                    globalShowPage(tableId, totalPages);
                });
                pagination.append(lastPageBtn);
            }

            $(`#${tableId}PrevPage`).prop('disabled', currentPage === 1);
            $(`#${tableId}NextPage`).prop('disabled', currentPage === totalPages);
        }
        
        // Convert numbers to Kurdish numerals
        function convertToKurdishNumerals(num) {
            const kurdishDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            return num.toString().split('').map(digit => 
                isNaN(parseInt(digit)) ? digit : kurdishDigits[parseInt(digit)]
            ).join('');
        }
        
        // Initialize all tables
        initializeTable('sales');
        initializeTable('drafts');
        initializeTable('delivery');
        initializeTable('returns');
        
        // Handle tab switching to reset pagination
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const targetId = $(e.target).attr('id');
            let tableId;
            
            // Determine which table to reset based on the tab
            if (targetId === 'sales-tab') {
                tableId = 'sales';
            } else if (targetId === 'delivery-tab') {
                tableId = 'delivery';
            } else if (targetId === 'drafts-tab') {
                tableId = 'drafts';
            } else if (targetId === 'returns-tab') {
                tableId = 'returns';
            }
            
            if (tableId) {
                // Clear search input
                $(`#${tableId}SearchInput`).val('');
                
                // Reset to page 1
                currentPageMap[tableId] = 1;
                
                // Update display
                globalShowPage(tableId, 1);
            }
        });

        function initializeTable(tableId) {
            const actualTableId = tableMap[tableId];
            const table = $(`#${actualTableId}`);
            const tableBody = table.find('tbody');
            const rows = tableBody.find('tr');
            
            // Set initial values
            itemsPerPageMap[tableId] = parseInt($(`#${tableId}RecordsPerPage`).val());
            let totalItems = rows.length;
            let totalPages = Math.ceil(totalItems / itemsPerPageMap[tableId]);

            // Initial pagination setup
            updatePagination(tableId, totalItems, totalPages);
            globalShowPage(tableId, 1);

            // Handle records per page change
            $(`#${tableId}RecordsPerPage`).on('change', function() {
                itemsPerPageMap[tableId] = parseInt($(this).val());
                currentPageMap[tableId] = 1;
                
                globalShowPage(tableId, 1);
            });

            // Previous page button
            $(`#${tableId}PrevPage`).on('click', function() {
                if (currentPageMap[tableId] > 1) {
                    currentPageMap[tableId]--;
                    globalShowPage(tableId, currentPageMap[tableId]);
                }
            });

            // Next page button
            $(`#${tableId}NextPage`).on('click', function() {
                const visibleRows = $(`#${actualTableId} tbody tr:visible`).not('.no-records-row');
                const totalItems = visibleRows.length;
                const totalPages = Math.ceil(totalItems / itemsPerPageMap[tableId]);
                
                if (currentPageMap[tableId] < totalPages) {
                    currentPageMap[tableId]++;
                    globalShowPage(tableId, currentPageMap[tableId]);
                }
            });

            // Search functionality with improved handling
            $(`#${tableId}SearchInput`).on('keyup', function() {
                // Reset to first page when searching
                currentPageMap[tableId] = 1;
                
                // Apply filter and show first page
                globalShowPage(tableId, 1);
            });
            
            // Also listen for filter changes in customer and payment type
            $('#customerFilter, #paymentTypeFilter').on('change', function() {
                // This will use our global search function from receipt-filters.js
                // But we need to update pagination afterward
                setTimeout(function() {
                    currentPageMap[tableId] = 1;
                    globalShowPage(tableId, 1);
                }, 100);
            });
            
            // Also connect to the filter reset button
            $('#resetFilters').on('click', function() {
                // Wait a moment for the filters to reset
                setTimeout(function() {
                    currentPageMap[tableId] = 1;
                    globalShowPage(tableId, 1);
                }, 100);
            });
        }

        // Show receipt items in modal (for all tables)
        $(document).on('click', '.show-receipt-items', function() {
            const invoiceNumber = $(this).data('invoice');
            const wastingId = $(this).data('wasting-id');
            
            let items = [];
            let modalTitle = '';
            
            // Special handling for wastings
            if (wastingId) {
                const allWastingItems = <?php echo json_encode($wastingItems); ?>;
                items = allWastingItems.filter(item => item.wasting_id == wastingId);
                modalTitle = `بەفیڕۆچووەکان - ئایدی: ${wastingId}`;
            } else {
                // Handle regular items by invoice number
                const invoiceItems = <?php echo json_encode(array_merge($saleItems ?? [], $draftItems, $deliveryItems)); ?>;
                items = invoiceItems.filter(item => item.invoice_number === invoiceNumber);
                modalTitle = `کاڵاکانی پسووڵە ${invoiceNumber || ''}`;
            }
            
            let itemsHtml = '<div class="table-responsive"><table class="table table-bordered">';
            itemsHtml += '<thead><tr><th>ناوی کاڵا</th><th>کۆدی کاڵا</th><th>بڕ</th><th>یەکە</th><th>نرخی تاک</th><th>نرخی گشتی</th></tr></thead>';
            itemsHtml += '<tbody>';
            
            if (items.length === 0) {
                itemsHtml += '<tr><td colspan="6" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>';
            } else {
                items.forEach(item => {
                    itemsHtml += `<tr>
                        <td>${item.product_name || '-'}</td>
                        <td>${item.product_code || '-'}</td>
                        <td>${item.quantity || '-'}</td>
                        <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                        <td>${parseInt(item.unit_price || 0).toLocaleString()} دینار</td>
                        <td>${parseInt(item.total_price || 0).toLocaleString()} دینار</td>
                    </tr>`;
                });
            }
            
            itemsHtml += '</tbody></table></div>';
            
            Swal.fire({
                title: modalTitle,
                html: itemsHtml,
                width: '80%',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal2-popup-custom'
                }
            });
        });
    });
    </script>
    <script src="../../js/receiptList.js"></script>
    <script src="../../js/debtTransactions.js"></script>
</body>

</html>