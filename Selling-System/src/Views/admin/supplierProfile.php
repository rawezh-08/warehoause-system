<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if supplier ID is provided
$supplierId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get supplier details
$supplierQuery = "SELECT * FROM suppliers WHERE id = :id";
$supplierStmt = $conn->prepare($supplierQuery);
$supplierStmt->bindParam(':id', $supplierId);
$supplierStmt->execute();
$supplier = $supplierStmt->fetch(PDO::FETCH_ASSOC);

// If supplier not found, redirect to suppliers list page
if (!$supplier) {
    header("Location: suppliers.php");
    exit;
}

// Get all purchases for this supplier
$purchasesQuery = "SELECT p.*, 
               pi.quantity, pi.unit_price, pi.total_price,
               pr.name as product_name, pr.code as product_code,
               SUM(pi.total_price) as total_amount,
               (SELECT SUM(total_price) FROM purchase_items WHERE purchase_id = p.id) as invoice_number
               FROM purchases p 
               JOIN purchase_items pi ON p.id = pi.purchase_id 
               JOIN products pr ON pi.product_id = pr.id
               WHERE p.supplier_id = :supplier_id 
               GROUP BY p.id, pi.id
               ORDER BY p.date DESC";
$purchasesStmt = $conn->prepare($purchasesQuery);
$purchasesStmt->bindParam(':supplier_id', $supplierId);
$purchasesStmt->execute();
$purchases = $purchasesStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate additional metrics
$totalPurchases = 0;
$monthlyPurchases = 0;

foreach ($purchases as $purchase) {
    $totalPurchases += $purchase['total_amount'];
    if (date('Y-m', strtotime($purchase['date'])) === date('Y-m')) {
        $monthlyPurchases += $purchase['total_amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی دابینکەر - سیستەمی بەڕێوەبردنی کۆگا</title>
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
        
        /* Supplier info card */
        .supplier-info-card {
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .supplier-info-item {
            margin-bottom: 10px;
        }
        
        .supplier-info-label {
            font-weight: bold;
            color: #6c757d;
        }
        
        .supplier-info-value {
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
            background-color: transparent;
            border: none;
            font-weight: 600;
        }

        .custom-tabs .nav-link.active::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #0d6efd;
        }

        .custom-tabs .nav-link i {
            margin-left: 5px;
        }

        /* Card Header with Tabs */
        .card-header-with-tabs {
            padding: 0;
            background-color: #fff;
            border-bottom: none;
        }

        /* Custom tab styling */
        .custom-tabs .nav-link {
            color: #6c757d;
            padding: 0.75rem 1.25rem;
            border: none;
            font-weight: 500;
            border-radius: 0;
            transition: all 0.2s ease;
        }
        .custom-tabs .nav-link:hover {
            color: #495057;
            background-color: rgba(13, 110, 253, 0.04);
        }
        .custom-tabs .nav-link.active {
            color: #0d6efd;
            background-color: transparent;
            border-bottom: 2px solid #0d6efd;
            font-weight: 600;
        }
        .custom-tabs .nav-link i {
            margin-left: 8px;
        }

        /* Card styling */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }
        .card-header {
            background-color: transparent;
            padding: 1rem 1.25rem;
        }

        /* Tables */
        .custom-table th, .custom-table td {
            vertical-align: middle;
        }
        .pagination-numbers .page-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            margin: 0 0.25rem;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .pagination-numbers .page-number:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }
        .pagination-numbers .page-number.active {
            background-color: #0d6efd;
            color: white;
        }

        /* Summary cards */
        .summary-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .icon-bg {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            color: white;
        }
        .card-value {
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Filter control styles */
        .form-select, .form-control {
            border-radius: 0.375rem;
        }
        .rounded-pill-start {
            border-top-left-radius: 50rem;
            border-bottom-left-radius: 50rem;
        }
        .rounded-pill-end {
            border-top-right-radius: 50rem;
            border-bottom-right-radius: 50rem;
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
                        <h3 class="page-title">پڕۆفایلی دابینکەر</h3>
                        <div>
                            <a href="addStaff.php?tab=supplier" class="btn btn-primary me-2">
                                <i class="fas fa-plus me-2"></i> زیادکردنی دابینکەری نوێ
                            </a>
                            <a href="suppliers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-right me-2"></i> گەڕانەوە بۆ لیستی دابینکەرەکان
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
                                    <p class="card-value mb-0"><?php echo number_format($supplier['debt_on_myself']); ?> دینار</p>
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
                                    <p class="card-value mb-0">0 دینار</p>
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
                                    <h5 class="card-title mb-0">کۆی کڕینەکان</h5>
                                    <p class="card-value mb-0"><?php echo number_format($totalPurchases); ?> دینار</p>
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
                                    <h5 class="card-title mb-0">کۆی کڕین لەم مانگە</h5>
                                    <p class="card-value mb-0"><?php echo number_format($monthlyPurchases); ?> دینار</p>
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
                                <form id="purchasesFilterForm" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="productFilter" class="form-label">ناوی کاڵا</label>
                                        <select class="form-select auto-filter" id="productFilter">
                                            <option value="">هەموو کاڵاکان</option>
                                            <?php
                                            $productQuery = "SELECT DISTINCT pr.id, pr.name 
                                                           FROM products pr 
                                                           JOIN purchase_items pi ON pr.id = pi.product_id 
                                                           JOIN purchases p ON pi.purchase_id = p.id 
                                                           WHERE p.supplier_id = :supplier_id 
                                                           ORDER BY pr.name";
                                            $productStmt = $conn->prepare($productQuery);
                                            $productStmt->bindParam(':supplier_id', $supplierId);
                                            $productStmt->execute();
                                            $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($products as $product) {
                                                echo '<option value="' . $product['id'] . '">' . htmlspecialchars($product['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
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

                <!-- Main Card with Tabs and Content -->
                <div class="card shadow-sm">
                    <!-- Tabs Navigation -->
                    <div class="card-header bg-transparent p-0 border-bottom-0">
                        <ul class="nav nav-tabs custom-tabs border-0" id="supplierTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-content" type="button" role="tab" aria-controls="purchases-content" aria-selected="true">
                                    <i class="fas fa-shopping-cart"></i>کڕینەکان
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="credit-tab" data-bs-toggle="tab" data-bs-target="#credit-content" type="button" role="tab" aria-controls="credit-content" aria-selected="false">
                                    <i class="fas fa-money-bill-wave"></i>کڕینە قەرزەکان
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="debt-payment-tab" data-bs-toggle="tab" data-bs-target="#debt-payment-content" type="button" role="tab" aria-controls="debt-payment-content" aria-selected="false">
                                    <i class="fas fa-hand-holding-dollar"></i>قەرزدانەوە
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="advance-payment-tab" data-bs-toggle="tab" data-bs-target="#advance-payment-content" type="button" role="tab" aria-controls="advance-payment-content" aria-selected="false">
                                    <i class="fas fa-coins"></i>پارەی پێشەکی
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="advance-payment-history-tab" data-bs-toggle="tab" data-bs-target="#advance-payment-history-content" type="button" role="tab" aria-controls="advance-payment-history-content" aria-selected="false">
                                    <i class="fas fa-history"></i>مێژووی پارەی پێشەکی
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Purchases History Tab -->
                            <div class="tab-pane fade show active" id="purchases-content" role="tabpanel" aria-labelledby="purchases-tab">
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
                                                        <select id="purchasesRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                        <input type="text" id="purchasesTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                        <table id="purchasesTable" class="table table-bordered custom-table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>ژمارەی پسووڵە</th>
                                                    <th>بەروار</th>
                                                    <th>ناوی کاڵا</th>
                                                    <th>کۆدی کاڵا</th>
                                                    <th>بڕ</th>
                                                    <th>نرخی یەکە</th>
                                                    <th>کۆی نرخ</th>
                                                    <th>جۆری پارەدان</th>
                                                    <th>کردارەکان</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($purchases) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($purchases as $purchase): ?>
                                                    <tr>
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                                                        <td><?php echo date('Y/m/d', strtotime($purchase['date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($purchase['product_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($purchase['product_code']); ?></td>
                                                        <td><?php echo number_format($purchase['quantity']); ?></td>
                                                        <td><?php echo number_format($purchase['unit_price']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['total_amount']); ?> دینار</td>
                                                        <td>
                                                            <?php if ($purchase['payment_type'] == 'cash'): ?>
                                                                <span class="badge bg-success">نەقد</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">قەرز</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="viewPurchase.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="بینین">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center">هیچ کڕینێک نەدۆزرایەوە</td>
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
                                                    نیشاندانی <span id="purchasesStartRecord">1</span> تا <span id="purchasesEndRecord">10</span> لە کۆی <span id="purchasesTotalRecords"><?php echo count($purchases); ?></span> تۆمار
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pagination-controls d-flex justify-content-md-end">
                                                    <button id="purchasesPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="purchasesPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Will be populated by JavaScript -->
                                                    </div>
                                                    <button id="purchasesNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Credit Purchases Tab -->
                            <div class="tab-pane fade" id="credit-content" role="tabpanel" aria-labelledby="credit-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">کڕینە قەرزەکان</h5>
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
                                                        <select id="creditRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                        <input type="text" id="creditTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                        <span class="input-group-text rounded-pill-end bg-light">
                                                            <i class="fas fa-search"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Filter Credit Purchases -->
                                    <?php 
                                    $creditPurchases = array_filter($purchases, function($purchase) {
                                        return $purchase['payment_type'] == 'credit';
                                    });
                                    ?>
                                    
                                    <!-- Table Content -->
                                    <div class="table-responsive">
                                        <table id="creditTable" class="table table-bordered custom-table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>ژمارەی پسووڵە</th>
                                                    <th>بەروار</th>
                                                    <th>ناوی کاڵا</th>
                                                    <th>کۆدی کاڵا</th>
                                                    <th>بڕ</th>
                                                    <th>نرخی یەکە</th>
                                                    <th>کۆی نرخ</th>
                                                    <th>کردارەکان</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($creditPurchases) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($creditPurchases as $purchase): ?>
                                                    <tr>
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                                                        <td><?php echo date('Y/m/d', strtotime($purchase['date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($purchase['product_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($purchase['product_code']); ?></td>
                                                        <td><?php echo number_format($purchase['quantity']); ?></td>
                                                        <td><?php echo number_format($purchase['unit_price']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['total_amount']); ?> دینار</td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="viewPurchase.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="بینین">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center">هیچ کڕینی قەرز نەدۆزرایەوە</td>
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
                                                    نیشاندانی <span id="creditStartRecord">1</span> تا <span id="creditEndRecord">10</span> لە کۆی <span id="creditTotalRecords"><?php echo count($creditPurchases); ?></span> تۆمار
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pagination-controls d-flex justify-content-md-end">
                                                    <button id="creditPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="creditPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Will be populated by JavaScript -->
                                                    </div>
                                                    <button id="creditNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Debt Payment Tab -->
                            <div class="tab-pane fade" id="debt-payment-content" role="tabpanel" aria-labelledby="debt-payment-tab">
                                <div class="row">
                                    <!-- Debt Information Section -->
                                    <div class="col-md-6 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">زانیاری قەرز</h5>
                                        </div>
                                        <div class="card border-0 bg-light p-3 mb-4">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">ناوی دابینکەر</h6>
                                                    <p class="h5"><?php echo htmlspecialchars($supplier['name']); ?></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">ژمارەی تەلەفۆن</h6>
                                                    <p class="h5"><?php echo htmlspecialchars($supplier['phone1']); ?></p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">کۆی قەرز</h6>
                                                    <p class="h4 text-<?php echo $supplier['debt_on_myself'] > 0 ? 'danger' : 'success'; ?>">
                                                        <?php echo number_format($supplier['debt_on_myself']); ?> دینار
                                                    </p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">کۆی پارەدان</h6>
                                                    <p class="h4 text-success">0 دینار</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Debt Payment Form Section -->
                                    <div class="col-md-6 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">پارەدانی قەرز</h5>
                                        </div>
                                        <form id="debtPaymentForm">
                                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="paymentAmount" class="form-label">بڕی پارەی پێدراو</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="paymentAmount" name="amount" min="1" required>
                                                    <span class="input-group-text">دینار</span>
                                                </div>
                                                <div class="form-text">زۆرترین بڕی نوێ: <?php echo number_format($supplier['debt_on_myself']); ?> دینار</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="paymentDate" class="form-label">بەرواری پارەدان</label>
                                                <input type="date" class="form-control" id="paymentDate" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="paymentNotes" class="form-label">تێبینی</label>
                                                <textarea class="form-control" id="paymentNotes" name="notes" rows="3"></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="paymentMethod" class="form-label">شێوازی پارەدان</label>
                                                <select class="form-select" id="paymentMethod" name="payment_method">
                                                    <option value="cash">نەقد</option>
                                                    <option value="transfer">FIB یان FastPay</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="referenceNumber" class="form-label">ژمارەی مەرجەع</label>
                                                <input type="text" class="form-control" id="referenceNumber" name="reference_number">
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="reset" class="btn btn-outline-secondary me-2">
                                                    <i class="fas fa-undo me-2"></i> ڕیسێت
                                                </button>
                                                <button type="button" id="saveDebtPaymentBtn" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> تۆمارکردن
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Recent Debt Payments -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">دوایین پارەدانەکانی قەرز</h5>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="debtPaymentTable" class="table table-bordered custom-table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>بەروار</th>
                                                        <th>بڕی پارە</th>
                                                        <th>تێبینی</th>
                                                        <th>شێوازی پارەدان</th>
                                                        <th>ژمارەی مەرجەع</th>
                                                        <th>کردارەکان</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Get supplier debt payment transactions
                                                    $debtPaymentQuery = "SELECT * FROM supplier_debt_transactions 
                                                                      WHERE supplier_id = :supplier_id 
                                                                      AND transaction_type = 'payment'
                                                                      ORDER BY created_at DESC";
                                                    $debtPaymentStmt = $conn->prepare($debtPaymentQuery);
                                                    $debtPaymentStmt->bindParam(':supplier_id', $supplierId);
                                                    $debtPaymentStmt->execute();
                                                    $debtPayments = $debtPaymentStmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    if (count($debtPayments) > 0):
                                                        $counter = 1;
                                                        foreach ($debtPayments as $payment):
                                                            $notesData = json_decode($payment['notes'], true);
                                                            $paymentMethod = isset($notesData['paymentMethod']) ? $notesData['paymentMethod'] : 'cash';
                                                            $referenceNumber = isset($notesData['referenceNumber']) ? $notesData['referenceNumber'] : '-';
                                                            $displayNotes = isset($notesData['originalNotes']) ? $notesData['originalNotes'] : $payment['notes'];
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $counter++; ?></td>
                                                                <td><?php echo date('Y/m/d', strtotime($payment['created_at'])); ?></td>
                                                                <td class="text-success">
                                                                    <?php echo number_format($payment['amount']); ?> دینار</td>
                                                                <td><?php echo !empty($displayNotes) ? htmlspecialchars($displayNotes) : '-'; ?></td>
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
                                                                <td><?php echo htmlspecialchars($referenceNumber); ?></td>
                                                                <td>
                                                                    <div class="action-buttons">
                                                                        <a href="../../views/receipt/supplier_payment_receipt.php?transaction_id=<?php echo $payment['id']; ?>"
                                                                           class="btn btn-sm btn-outline-info rounded-circle print-receipt-btn"
                                                                           target="_blank"
                                                                           title="چاپکردن">
                                                                            <i class="fas fa-print"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center">هیچ پارەدانێک نەدۆزرایەوە</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Advance Payment Tab -->
                            <div class="tab-pane fade" id="advance-payment-content" role="tabpanel" aria-labelledby="advance-payment-tab">
                                <div class="row">
                                    <!-- Supplier Information Section -->
                                    <div class="col-md-6 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">زانیاری دابینکەر</h5>
                                        </div>
                                        <div class="card border-0 bg-light p-3 mb-4">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">ناوی دابینکەر</h6>
                                                    <p class="h5"><?php echo htmlspecialchars($supplier['name']); ?></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">ژمارەی مۆبایل</h6>
                                                    <p class="h5"><?php echo htmlspecialchars($supplier['phone1']); ?></p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">دۆخی ئێستا</h6>
                                                    <?php if ($supplier['debt_on_myself'] > 0): ?>
                                                    <p class="h4 text-danger">
                                                        <?php echo number_format($supplier['debt_on_myself']); ?> دینار قەرزمانە
                                                    </p>
                                                    <?php elseif ($supplier['debt_on_supplier'] > 0): ?>
                                                    <p class="h4 text-success">
                                                        <?php echo number_format($supplier['debt_on_supplier']); ?> دینار پێشەکیمان داوە
                                                    </p>
                                                    <?php else: ?>
                                                    <p class="h4 text-secondary">
                                                        هاوسەنگ
                                                    </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-2">کۆی کڕینەکان</h6>
                                                    <p class="h4 text-primary"><?php echo number_format($totalPurchases); ?>
                                                        دینار</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Advance Payment Form Section -->
                                    <div class="col-md-6 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">پێدانی پارەی پێشەکی</h5>
                                        </div>
                                        <form id="supplierAdvancePaymentForm">
                                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                            <input type="hidden" name="transaction_type" value="supplier_advance">

                                            <div class="mb-3">
                                                <label for="advanceAmount" class="form-label">بڕی پارەی پێشەکی</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="advanceAmount"
                                                        name="amount" min="1" required>
                                                    <span class="input-group-text">دینار</span>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="advanceDate" class="form-label">بەرواری پێدان</label>
                                                <input type="date" class="form-control" id="advanceDate" name="advance_date"
                                                    value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="advanceNotes" class="form-label">تێبینی</label>
                                                <textarea class="form-control" id="advanceNotes" name="notes"
                                                    rows="3"></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="advancePaymentMethod" class="form-label">شێوازی پارەدان</label>
                                                <select class="form-select" id="advancePaymentMethod" name="payment_method">
                                                    <option value="cash">نەقد</option>
                                                    <option value="transfer">FIB یان FastPay</option>
                                                </select>
                                            </div>

                                            <div class="text-end">
                                                <button type="reset" class="btn btn-outline-secondary me-2">
                                                    <i class="fas fa-undo me-2"></i> ڕیسێت
                                                </button>
                                                <button type="button" id="saveSupplierAdvancePaymentBtn" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> تۆمارکردن
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Advance Payment History Tab -->
                            <div class="tab-pane fade" id="advance-payment-history-content" role="tabpanel" aria-labelledby="advance-payment-history-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">مێژووی پارەی پێشەکی</h5>
                                    <button class="btn btn-sm btn-outline-primary refresh-advance-btn">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table id="supplierAdvancePaymentTable" class="table table-bordered custom-table table-hover">
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
                                            // Get supplier advance payment transactions
                                            $advanceQuery = "SELECT * FROM supplier_debt_transactions 
                                                          WHERE supplier_id = :supplier_id 
                                                          AND transaction_type = 'supplier_advance'
                                                          ORDER BY created_at DESC";
                                            $advanceStmt = $conn->prepare($advanceQuery);
                                            $advanceStmt->bindParam(':supplier_id', $supplierId);
                                            $advanceStmt->execute();
                                            $advanceTransactions = $advanceStmt->fetchAll(PDO::FETCH_ASSOC);
                                            
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
                                                                <a href="../../views/receipt/supplier_advance_receipt.php?transaction_id=<?php echo $transaction['id']; ?>"
                                                                   class="btn btn-sm btn-outline-info rounded-circle print-receipt-btn"
                                                                   target="_blank"
                                                                   title="چاپکردن">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Global AJAX Configuration -->
    <script src="../../js/ajax-config.js"></script>
    <!-- Initialize common elements -->
    <script src="../../js/include-components.js"></script>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include Custom JS -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/supplier-advance-payment.js"></script>
    <!-- Custom JS for this page -->
    <script>
        $(document).ready(function() {
            // Table pagination functionality for Purchases table
            let currentPurchasesPage = 1;
            const purchasesRecordsPerPageSelect = $('#purchasesRecordsPerPage');
            let purchasesRecordsPerPage = parseInt(purchasesRecordsPerPageSelect.val());
            const purchasesTableRows = $('#purchasesTable tbody tr');
            const purchasesTotalRecords = purchasesTableRows.length;
            
            // Update records per page when select changes
            purchasesRecordsPerPageSelect.on('change', function() {
                purchasesRecordsPerPage = parseInt($(this).val());
                currentPurchasesPage = 1; // Reset to first page
                updatePurchasesTable();
            });
            
            // Update the table display based on current page and records per page
            function updatePurchasesTable() {
                const startIndex = (currentPurchasesPage - 1) * purchasesRecordsPerPage;
                const endIndex = startIndex + purchasesRecordsPerPage;
                
                // Hide all rows
                purchasesTableRows.hide();
                
                // Show only rows for current page
                purchasesTableRows.slice(startIndex, endIndex).show();
                
                // Update pagination info
                $('#purchasesStartRecord').text(purchasesTotalRecords > 0 ? startIndex + 1 : 0);
                $('#purchasesEndRecord').text(Math.min(endIndex, purchasesTotalRecords));
                $('#purchasesTotalRecords').text(purchasesTotalRecords);
                
                // Enable/disable pagination buttons
                $('#purchasesPrevPageBtn').prop('disabled', currentPurchasesPage === 1);
                $('#purchasesNextPageBtn').prop('disabled', endIndex >= purchasesTotalRecords);
                
                // Update pagination numbers
                updatePurchasesPaginationNumbers();
            }
            
            // Create pagination number buttons
            function updatePurchasesPaginationNumbers() {
                const totalPages = Math.ceil(purchasesTotalRecords / purchasesRecordsPerPage);
                const paginationNumbersContainer = $('#purchasesPaginationNumbers');
                
                // Clear existing pagination numbers
                paginationNumbersContainer.empty();
                
                // Calculate which page numbers to show
                let startPage = Math.max(1, currentPurchasesPage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                
                if (endPage - startPage < 4 && startPage > 1) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                // Add first page button if needed
                if (startPage > 1) {
                    paginationNumbersContainer.append(`
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1 purchase-page-number" data-page="1">1</button>
                    `);
                    
                    if (startPage > 2) {
                        paginationNumbersContainer.append(`
                            <span class="d-flex align-items-center mx-1">...</span>
                        `);
                    }
                }
                
                // Add page number buttons
                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === currentPurchasesPage ? 'btn-primary text-white' : 'btn-outline-primary';
                    paginationNumbersContainer.append(`
                        <button class="btn btn-sm ${activeClass} rounded-pill me-1 purchase-page-number" data-page="${i}">${i}</button>
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
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1 purchase-page-number" data-page="${totalPages}">${totalPages}</button>
                    `);
                }
            }
            
            // Purchases Tab Table Pagination
            initTablePagination({
                tableId: 'purchasesTable',
                recordsPerPageId: 'purchasesRecordsPerPage',
                paginationNumbersId: 'purchasesPaginationNumbers',
                prevBtnId: 'purchasesPrevPageBtn',
                nextBtnId: 'purchasesNextPageBtn',
                startRecordId: 'purchasesStartRecord',
                endRecordId: 'purchasesEndRecord',
                totalRecordsId: 'purchasesTotalRecords',
                searchInputId: 'purchasesTableSearch'
            });

            // Credit Purchases Tab Table Pagination
            initTablePagination({
                tableId: 'creditTable',
                recordsPerPageId: 'creditRecordsPerPage',
                paginationNumbersId: 'creditPaginationNumbers',
                prevBtnId: 'creditPrevPageBtn',
                nextBtnId: 'creditNextPageBtn',
                startRecordId: 'creditStartRecord',
                endRecordId: 'creditEndRecord',
                totalRecordsId: 'creditTotalRecords',
                searchInputId: 'creditTableSearch'
            });

            // Auto filter functionality
            $('.auto-filter').on('change', function() {
                filterPurchasesTable();
            });

            // Reset filters button
            $('#resetFilters').on('click', function() {
                $('#purchasesFilterForm')[0].reset();
                $('.custom-select-wrapper select').val('').trigger('change');
                filterPurchasesTable();
            });

            // Debt Payment Form Validation and Submission
            $('#saveDebtPaymentBtn').on('click', function() {
                const form = $('#debtPaymentForm');

                // Basic validation
                if (!form[0].checkValidity()) {
                    form.addClass('was-validated');
                    return;
                }

                // Get form data
                const supplierId = form.find('input[name="supplier_id"]').val();
                const amount = form.find('input[name="amount"]').val();
                const paymentDate = form.find('input[name="payment_date"]').val();
                const notes = form.find('textarea[name="notes"]').val();
                const paymentMethod = form.find('select[name="payment_method"]').val();
                const referenceNumber = form.find('input[name="reference_number"]').val();

                // Check if amount is greater than total debt
                const totalDebt = <?php echo $supplier['debt_on_myself']; ?>;
                if (parseFloat(amount) > totalDebt) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'بڕی پارەی پێدراو نابێت لە کۆی قەرز زیاتر بێت',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Prepare metadata as JSON for storing in notes field
                const metadata = {
                    paymentMethod: paymentMethod,
                    referenceNumber: referenceNumber || '',
                    originalNotes: notes || ''
                };

                // Prepare request data
                const requestData = {
                    supplier_id: supplierId,
                    amount: amount,
                    transaction_date: paymentDate,
                    transaction_type: 'payment', // This is for supplier debt payment
                    notes: JSON.stringify(metadata) // Store metadata as JSON
                };

                // Submit via AJAX
                $.ajax({
                    url: '../../ajax/save_supplier_debt_transaction.php',
                    method: 'POST',
                    data: requestData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: 'پارەدانی قەرز بە سەرکەوتوویی تۆمارکرا',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                // Reset form
                                form[0].reset();
                                form.removeClass('was-validated');
                                
                                // Refresh the page to update all data
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message || 'هەڵەیەک ڕوویدا لە کاتی تۆمارکردنی پارەدانی قەرز',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error details:', {xhr, status, error});
                        let errorMessage = 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەر';
                        
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: errorMessage,
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Function to initialize table pagination
            function initTablePagination(options) {
                const table = $(`#${options.tableId}`);
                const tbody = table.find('tbody');
                const rows = tbody.find('tr').not('.no-records');
                
                let currentPage = 1;
                let recordsPerPage = parseInt($(`#${options.recordsPerPageId}`).val()) || 10;
                
                // Initialize
                updatePagination();
                
                // Records per page change event
                $(`#${options.recordsPerPageId}`).on('change', function() {
                    recordsPerPage = parseInt($(this).val());
                    currentPage = 1;
                    updatePagination();
                });
                
                // Previous page button
                $(`#${options.prevBtnId}`).on('click', function() {
                    if (!$(this).prop('disabled')) {
                        currentPage--;
                        updatePagination();
                    }
                });
                
                // Next page button
                $(`#${options.nextBtnId}`).on('click', function() {
                    if (!$(this).prop('disabled')) {
                        currentPage++;
                        updatePagination();
                    }
                });
                
                // Search functionality
                $(`#${options.searchInputId}`).on('keyup', function() {
                    currentPage = 1;
                    updatePagination();
                });
                
                // Function to update pagination
                function updatePagination() {
                    const searchTerm = $(`#${options.searchInputId}`).val().toLowerCase();
                    
                    // Filter rows based on search term
                    const filteredRows = rows.filter(function() {
                        const rowText = $(this).text().toLowerCase();
                        return searchTerm === '' || rowText.indexOf(searchTerm) > -1;
                    });
                    
                    const totalRecords = filteredRows.length;
                    const totalPages = Math.ceil(totalRecords / recordsPerPage);
                    
                    // Update pagination info
                    $(`#${options.totalRecordsId}`).text(totalRecords);
                    
                    // Ensure current page is valid
                    currentPage = Math.max(1, Math.min(currentPage, totalPages || 1));
                    
                    // Calculate start and end indices
                    const startIndex = (currentPage - 1) * recordsPerPage;
                    const endIndex = Math.min(startIndex + recordsPerPage - 1, totalRecords - 1);
                    
                    // Update pagination info
                    $(`#${options.startRecordId}`).text(totalRecords > 0 ? startIndex + 1 : 0);
                    $(`#${options.endRecordId}`).text(totalRecords > 0 ? endIndex + 1 : 0);
                    
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
                    $(`#${options.prevBtnId}`).prop('disabled', currentPage === 1);
                    $(`#${options.nextBtnId}`).prop('disabled', currentPage === totalPages || totalPages === 0);
                    
                    // Generate page numbers
                    const paginationNumbers = $(`#${options.paginationNumbersId}`);
                    paginationNumbers.empty();
                    
                    if (totalPages <= 5) {
                        for (let i = 1; i <= totalPages; i++) {
                            paginationNumbers.append(createPageNumber(i));
                        }
                    } else {
                        // Always show first page
                        paginationNumbers.append(createPageNumber(1));
                        
                        // Determine start and end of middle pages
                        let startMiddle = Math.max(2, currentPage - 1);
                        let endMiddle = Math.min(totalPages - 1, currentPage + 1);
                        
                        // Add ellipsis if needed
                        if (startMiddle > 2) {
                            paginationNumbers.append('<span class="mx-1">...</span>');
                        }
                        
                        // Add middle pages
                        for (let i = startMiddle; i <= endMiddle; i++) {
                            paginationNumbers.append(createPageNumber(i));
                        }
                        
                        // Add ellipsis if needed
                        if (endMiddle < totalPages - 1) {
                            paginationNumbers.append('<span class="mx-1">...</span>');
                        }
                        
                        // Always show last page
                        paginationNumbers.append(createPageNumber(totalPages));
                    }
                }
                
                // Function to create a page number element
                function createPageNumber(pageNum) {
                    const element = $('<div></div>')
                        .addClass('page-number')
                        .text(pageNum);
                    
                    if (pageNum === currentPage) {
                        element.addClass('active');
                    } else {
                        element.on('click', function() {
                            currentPage = pageNum;
                            updatePagination();
                        });
                    }
                    
                    return element;
                }
            }
            
            // Function to filter purchases table based on filter form values
            function filterPurchasesTable() {
                const productId = $('#productFilter').val();
                const paymentType = $('#paymentTypeFilter').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                
                const table = $('#purchasesTable');
                const rows = table.find('tbody tr');
                
                rows.each(function() {
                    const row = $(this);
                    let shouldShow = true;
                    
                    // Filter by product
                    if (productId && row.data('product-id') !== productId) {
                        shouldShow = false;
                    }
                    
                    // Filter by payment type
                    if (paymentType) {
                        const rowPaymentType = row.find('td:nth-child(9)').text().trim().indexOf('نەقد') > -1 ? 'cash' : 'credit';
                        if (rowPaymentType !== paymentType) {
                            shouldShow = false;
                        }
                    }
                    
                    // Filter by date range
                    if (startDate || endDate) {
                        const rowDateText = row.find('td:nth-child(3)').text().trim();
                        const rowDateParts = rowDateText.split('/');
                        const rowDate = new Date(rowDateParts[0], rowDateParts[1] - 1, rowDateParts[2]);
                        
                        if (startDate) {
                            const filterStartDate = new Date(startDate);
                            if (rowDate < filterStartDate) {
                                shouldShow = false;
                            }
                        }
                        
                        if (endDate) {
                            const filterEndDate = new Date(endDate);
                            if (rowDate > filterEndDate) {
                                shouldShow = false;
                            }
                        }
                    }
                    
                    row.toggle(shouldShow);
                });
                
                // Update "no records" message
                const visibleRows = rows.filter(':visible');
                if (visibleRows.length === 0) {
                    const colCount = table.find('thead th').length;
                    const noRecordsRow = `<tr class="no-records"><td colspan="${colCount}" class="text-center">هیچ تۆمارێک نەدۆزرایەوە</td></tr>`;
                    
                    if (table.find('tbody tr.no-records').length === 0) {
                        table.find('tbody').append(noRecordsRow);
                    } else {
                        table.find('tbody tr.no-records').show();
                    }
                } else {
                    table.find('tbody tr.no-records').remove();
                }
            }
        });
    </script>
</body>
</html>