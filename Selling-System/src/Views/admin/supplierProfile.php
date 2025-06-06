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
               pi.quantity, pi.unit_price, pi.total_price, pi.unit_type,
               pr.name as product_name, pr.code as product_code,
               SUM(pi.total_price) as total_amount,
               p.shipping_cost, p.other_cost, p.discount
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

// Get supplier tabs content
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'basic';
$tabs = [
    'basic' => 'زانیاری بنەڕەتی',
    'purchases' => 'کڕینەکان',
    'advance_payments' => 'پارەی پێشەکی',
    'debt_payments' => 'پارەدانەکانی قەرز', // Add new tab for debt payments
    'returns' => 'گەڕاندنەوەکان',
    'activity' => 'چالاکییەکان'
];
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی دابینکەر: <?php echo htmlspecialchars($supplier['name']); ?> - سیستەمی بەڕێوەبردنی کۆگا</title>
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
        
        /* Page specific styling */
        .section-title {
            position: relative;
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: #007bff;
        }
        
        /* Pagination Styles */
        .table-pagination {
            font-size: 0.85rem;
        }
        
        .pagination-numbers {
            display: flex;
            align-items: center;
        }
        
        .page-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            margin: 0 3px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .page-number:hover {
            background-color: #e9ecef;
        }
        
        .page-number.active {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
        }
        
        .records-per-page {
            display: flex;
            align-items: center;
        }
        
        .custom-select-wrapper {
            min-width: 70px;
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
                        <h3 class="page-title">پڕۆفایلی دابینکەر: <?php echo htmlspecialchars($supplier['name']); ?></h3>
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
                                <button class="nav-link" id="debt-history-tab" data-bs-toggle="tab" data-bs-target="#debt-history-content" type="button" role="tab" aria-controls="debt-history-content" aria-selected="false">
                                    <i class="fas fa-history"></i>مێژووی قەرزدانەوە
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
                                                    <th>کرێی گواستنەوە</th>
                                                    <th>خەرجی تر</th>
                                                    <th>داشکاندن</th>
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
                                                        <td>
                                                            <?php echo number_format($purchase['quantity']); ?>
                                                            <?php
                                                                switch($purchase['unit_type']) {
                                                                    case 'box':
                                                                        echo ' <span class="badge bg-info">کارتۆن</span>';
                                                                        break;
                                                                    case 'piece':
                                                                        echo ' <span class="badge bg-secondary">دانە</span>';
                                                                        break;
                                                                    case 'set':
                                                                        echo ' <span class="badge bg-warning">سێت</span>';
                                                                        break;
                                                                    default:
                                                                        echo ' <span class="badge bg-secondary">دانە</span>';
                                                                }
                                                            ?>
                                                        </td>
                                                        <td><?php echo number_format($purchase['unit_price']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['total_amount']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['shipping_cost']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['other_cost']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['discount']); ?> دینار</td>
                                                        <td>
                                                            <?php if ($purchase['payment_type'] == 'cash'): ?>
                                                                <span class="badge bg-success">نەقد</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">قەرز</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-purchase" 
                                                                    data-id="<?php echo $purchase['id']; ?>"
                                                                    title="دەستکاری">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-purchase-details" 
                                                                    data-id="<?php echo $purchase['id']; ?>"
                                                                    data-invoice="<?php echo $purchase['invoice_number']; ?>"
                                                                    title="بینین">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <?php
                                                                // Check if purchase can be deleted (no returns or payments)
                                                                $canDelete = true;
                                                                
                                                                // Check for debt transactions (payments)
                                                                $paymentQuery = "SELECT COUNT(*) as count FROM supplier_debt_transactions 
                                                                                WHERE reference_id = :purchase_id 
                                                                                AND transaction_type = 'payment'";
                                                                $paymentStmt = $conn->prepare($paymentQuery);
                                                                $paymentStmt->bindParam(':purchase_id', $purchase['id']);
                                                                $paymentStmt->execute();
                                                                $paymentCount = $paymentStmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                
                                                                if ($paymentCount > 0) {
                                                                    $canDelete = false;
                                                                }
                                                                
                                                                // Check for product returns
                                                                $returnQuery = "SELECT COUNT(*) as count FROM product_returns 
                                                                               WHERE receipt_id = :purchase_id AND receipt_type = 'buying'";
                                                                $returnStmt = $conn->prepare($returnQuery);
                                                                $returnStmt->bindParam(':purchase_id', $purchase['id']);
                                                                $returnStmt->execute();
                                                                $returnCount = $returnStmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                
                                                                if ($returnCount > 0) {
                                                                    $canDelete = false;
                                                                }
                                                                
                                                                // Add return button
                                                                ?>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-warning rounded-circle return-purchase"
                                                                    data-id="<?php echo $purchase['id']; ?>"
                                                                    data-invoice="<?php echo $purchase['invoice_number']; ?>"
                                                                    title="گەڕاندنەوەی کاڵا">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                                <?php if ($canDelete): ?>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger rounded-circle delete-purchase"
                                                                    data-id="<?php echo $purchase['id']; ?>"
                                                                    data-invoice="<?php echo $purchase['invoice_number']; ?>"
                                                                    title="سڕینەوە">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="13" class="text-center">هیچ کڕینێک نەدۆزرایەوە</td>
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
                                                    <th>کرێی گواستنەوە</th>
                                                    <th>خەرجی تر</th>
                                                    <th>داشکاندن</th>
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
                                                        <td>
                                                            <?php echo number_format($purchase['quantity']); ?>
                                                            <?php
                                                                switch($purchase['unit_type']) {
                                                                    case 'box':
                                                                        echo ' <span class="badge bg-info">کارتۆن</span>';
                                                                        break;
                                                                    case 'piece':
                                                                        echo ' <span class="badge bg-secondary">دانە</span>';
                                                                        break;
                                                                    case 'set':
                                                                        echo ' <span class="badge bg-warning">سێت</span>';
                                                                        break;
                                                                    default:
                                                                        echo ' <span class="badge bg-secondary">دانە</span>';
                                                                }
                                                            ?>
                                                        </td>
                                                        <td><?php echo number_format($purchase['unit_price']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['total_amount']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['shipping_cost']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['other_cost']); ?> دینار</td>
                                                        <td><?php echo number_format($purchase['discount']); ?> دینار</td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-purchase" 
                                                                    data-id="<?php echo $purchase['id']; ?>"
                                                                    title="دەستکاری">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-purchase-details" 
                                                                    data-id="<?php echo $purchase['id']; ?>"
                                                                    data-invoice="<?php echo $purchase['invoice_number']; ?>"
                                                                    title="بینین">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="12" class="text-center">هیچ کڕینی قەرز نەدۆزرایەوە</td>
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
                                                    <h6 class="text-muted mb-2">کۆی کڕینە قەرزەکان</h6>
                                                    <p class="h4 text-primary"><?php echo number_format($creditPurchases['total']); ?> دینار</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Payment Form Section -->
                                    <div class="col-md-6 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">قەرزدانەوە</h5>
                                        </div>
                                        <form id="supplierPaymentForm">
                                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                            
                                            <div class="alert alert-info mb-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                دابینکەر: <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="paymentAmount" class="form-label">بڕی پارە</label>
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
                                            
                                            <input type="hidden" name="payment_strategy" value="fifo">
                                            
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
                            </div>

                            <!-- Debt History Tab -->
                            <div class="tab-pane fade" id="debt-history-content" role="tabpanel" aria-labelledby="debt-history-tab">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">مێژووی پارەدانەکانی قەرز</h5>
                                            <button class="btn btn-sm btn-outline-primary refresh-debt-history-btn">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Summary Cards for Debt History -->
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <div class="card summary-card bg-white border-0">
                                                    <div class="card-body d-flex align-items-center">
                                                        <div class="icon-bg bg-danger me-3">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title mb-0">کۆی قەرزی ئێمە</h5>
                                                            <p class="card-value mb-0"><?php echo number_format($supplier['debt_on_myself']); ?> دینار</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card summary-card bg-white border-0">
                                                    <div class="card-body d-flex align-items-center">
                                                        <div class="icon-bg bg-success me-3">
                                                            <i class="fas fa-hand-holding-dollar"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title mb-0">کۆی گەڕاندنەوەی قەرزەکان</h5>
                                                            <?php
                                                            // Calculate total debt payments
                                                            $totalDebtPaymentsQuery = "SELECT SUM(amount) as total FROM supplier_debt_transactions 
                                                                                WHERE supplier_id = :supplier_id 
                                                                                AND transaction_type = 'payment'";
                                                            $totalDebtPaymentsStmt = $conn->prepare($totalDebtPaymentsQuery);
                                                            $totalDebtPaymentsStmt->bindParam(':supplier_id', $supplierId);
                                                            $totalDebtPaymentsStmt->execute();
                                                            $totalDebtPayments = $totalDebtPaymentsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                                                            ?>
                                                            <p class="card-value mb-0"><?php echo number_format($totalDebtPayments); ?> دینار</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
                                                            $paymentMethod = isset($notesData['payment_method']) ? $notesData['payment_method'] : 'cash';
                                                            $displayNotes = isset($notesData['notes']) && !empty($notesData['notes']) ? $notesData['notes'] : '-';
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $counter++; ?></td>
                                                                <td><?php echo date('Y/m/d', strtotime($payment['created_at'])); ?></td>
                                                                <td class="text-success">
                                                                    <?php echo number_format($payment['amount']); ?> دینار</td>
                                                                <td><?php echo htmlspecialchars($displayNotes); ?></td>
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
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center">هیچ پارەدانێک نەدۆزرایەوە</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Table Pagination for Debt History -->
                                        <div class="table-pagination mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                                    <div class="records-per-page">
                                                        <label class="me-2">نیشاندان:</label>
                                                        <div class="custom-select-wrapper">
                                                            <select id="debtHistoryRecordsPerPage" class="form-select form-select-sm rounded-pill">
                                                                <option value="5">5</option>
                                                                <option value="10" selected>10</option>
                                                                <option value="25">25</option>
                                                                <option value="50">50</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-sm-6">
                                                    <div class="d-flex justify-content-md-end">
                                                        <div class="pagination-info me-3">
                                                            نیشاندانی <span id="debtHistoryStartRecord">1</span> تا <span id="debtHistoryEndRecord">10</span> لە کۆی <span id="debtHistoryTotalRecords"><?php echo count($debtPayments); ?></span> تۆمار
                                                        </div>
                                                        <div class="pagination-controls d-flex">
                                                            <button id="debtHistoryPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="debtHistoryPaginationNumbers" class="pagination-numbers d-flex">
                                                                <!-- Will be populated by JavaScript -->
                                                            </div>
                                                            <button id="debtHistoryNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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
                                                <div class="col-md-12 mb-3">
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
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Advance Payment Form Section -->
                                    <div class="col-md-6 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">پێدانی پارەی پێشەکی</h5>
                                        </div>
                                        <?php if ($supplier['debt_on_myself'] > 0): ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                ناتوانرێت پارەی پێشەکی بدەیت چونکە قەرزمان لەسەرە بە بڕی <?php echo number_format($supplier['debt_on_myself']); ?> دینار
                                            </div>
                                        <?php else: ?>
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
                                        <?php endif; ?>
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
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">هیچ پارەی پێشەکی تۆمار نەکراوە
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Table Pagination for Advance Payment History -->
                                <div class="table-pagination mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page">
                                                <label class="me-2">نیشاندان:</label>
                                                <div class="custom-select-wrapper">
                                                    <select id="advanceHistoryRecordsPerPage" class="form-select form-select-sm rounded-pill">
                                                        <option value="5">5</option>
                                                        <option value="10" selected>10</option>
                                                        <option value="25">25</option>
                                                        <option value="50">50</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="d-flex justify-content-md-end">
                                                <div class="pagination-info me-3">
                                                    نیشاندانی <span id="advanceHistoryStartRecord">1</span> تا <span id="advanceHistoryEndRecord">10</span> لە کۆی <span id="advanceHistoryTotalRecords"><?php echo count($advanceTransactions); ?></span> تۆمار
                                                </div>
                                                <div class="pagination-controls d-flex">
                                                    <button id="advanceHistoryPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="advanceHistoryPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Will be populated by JavaScript -->
                                                    </div>
                                                    <button id="advanceHistoryNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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
            
            // Delete purchase button handler
            $(document).on('click', '.delete-purchase', function() {
                const purchaseId = $(this).data('id');
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
                            url: '../../ajax/delete_purchase.php',
                            type: 'POST',
                            data: {
                                purchase_id: purchaseId
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
                const form = $('#supplierPaymentForm');

                // Basic validation
                const amount = $('#paymentAmount').val();
                if (!amount || isNaN(amount) || Number(amount) <= 0) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە بڕی پارەی دروست داخل بکە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Get payment strategy
                const paymentStrategy = 'fifo'; // Always use FIFO
                
                // Get form data
                const formData = {
                    supplier_id: form.find('input[name="supplier_id"]').val(),
                    amount: amount,
                    notes: JSON.stringify({
                        notes: $('#paymentNotes').val(),
                        payment_method: $('#paymentMethod').val()
                    }),
                    payment_date: $('#paymentDate').val(),
                    payment_method: $('#paymentMethod').val()
                };

                // Use FIFO payment API
                let apiEndpoint = '../../api/pay_supplier_debt_fifo.php';
                
                // Show loading indicator
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit form data
                $.ajax({
                    url: apiEndpoint,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                // Reset form
                                form[0].reset();
                                // Reload page to update debt information
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
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                        console.error(xhr, status, error);
                    }
                });
            });

            // Refresh debt history button click
            $('.refresh-debt-history-btn').on('click', function() {
                location.reload();
            });

            // Initialize Debt History Pagination
            initTablePagination({
                tableId: 'debtPaymentTable',
                recordsPerPageId: 'debtHistoryRecordsPerPage',
                paginationNumbersId: 'debtHistoryPaginationNumbers',
                prevBtnId: 'debtHistoryPrevPageBtn',
                nextBtnId: 'debtHistoryNextPageBtn',
                startRecordId: 'debtHistoryStartRecord',
                endRecordId: 'debtHistoryEndRecord',
                totalRecordsId: 'debtHistoryTotalRecords',
                searchInputId: null // No search input for this table
            });

            // Initialize Advance Payment History Pagination
            initTablePagination({
                tableId: 'supplierAdvancePaymentTable',
                recordsPerPageId: 'advanceHistoryRecordsPerPage',
                paginationNumbersId: 'advanceHistoryPaginationNumbers',
                prevBtnId: 'advanceHistoryPrevPageBtn',
                nextBtnId: 'advanceHistoryNextPageBtn',
                startRecordId: 'advanceHistoryStartRecord',
                endRecordId: 'advanceHistoryEndRecord',
                totalRecordsId: 'advanceHistoryTotalRecords',
                searchInputId: null // No search input for this table
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
                
                // Search functionality (if search input is provided)
                if (options.searchInputId) {
                    $(`#${options.searchInputId}`).on('keyup', function() {
                        currentPage = 1;
                        updatePagination();
                    });
                }
                
                // Function to update pagination
                function updatePagination() {
                    let filteredRows = rows;
                    
                    // Apply search filter if search input exists
                    if (options.searchInputId) {
                        const searchTerm = $(`#${options.searchInputId}`).val().toLowerCase();
                        filteredRows = rows.filter(function() {
                            const rowText = $(this).text().toLowerCase();
                            return searchTerm === '' || rowText.indexOf(searchTerm) > -1;
                        });
                    }
                    
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

            // Custom JS for purchase details modal
            $(document).ready(function() {
                // Function to handle viewing purchase details
                $(document).on('click', '.view-purchase-details', function() {
                    const purchaseId = $(this).data('id');
                    const invoiceNumber = $(this).data('invoice');
                    
                    // Show loading
                    Swal.fire({
                        title: 'تکایە چاوەڕێ بە...',
                        html: 'زانیاریەکانی پسووڵە دەهێنرێن',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Fetch purchase details via AJAX
                    $.ajax({
                        url: '../../ajax/get_purchase_items.php',
                        type: 'POST',
                        data: {
                            purchase_id: purchaseId
                        },
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                
                                if (data.success) {
                                    // Format items for display
                                    let itemsHtml = `
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>ناوی کاڵا</th>
                                                        <th>بڕ</th>
                                                        <th>نرخی یەکە</th>
                                                        <th>کۆی نرخ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                    `;
                                    
                                    let counter = 1;
                                    data.items.forEach(item => {
                                        let unitTypeBadge = '';
                                        switch(item.unit_type) {
                                            case 'box':
                                                unitTypeBadge = '<span class="badge bg-info ms-1">کارتۆن</span>';
                                                break;
                                            case 'piece':
                                                unitTypeBadge = '<span class="badge bg-secondary ms-1">دانە</span>';
                                                break;
                                            case 'set':
                                                unitTypeBadge = '<span class="badge bg-warning ms-1">سێت</span>';
                                                break;
                                            default:
                                                unitTypeBadge = '<span class="badge bg-secondary ms-1">دانە</span>';
                                        }
                                        
                                        itemsHtml += `
                                            <tr>
                                                <td>${counter++}</td>
                                                <td>${item.product_name}</td>
                                                <td>${item.quantity} ${unitTypeBadge}</td>
                                                <td>${Number(item.unit_price).toLocaleString()} دینار</td>
                                                <td>${Number(item.total_price).toLocaleString()} دینار</td>
                                            </tr>
                                        `;
                                    });
                                    
                                    itemsHtml += `
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="4" class="text-start fw-bold">کۆی گشتی</td>
                                                <td class="fw-bold">${data.total_amount.toLocaleString()} دینار</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            `;
                                    
                                    // Display the modal with purchase items
                                    Swal.fire({
                                        title: `پسووڵەی ژمارە ${invoiceNumber}`,
                                        html: itemsHtml,
                                        width: '800px',
                                        confirmButtonText: 'داخستن',
                                        customClass: {
                                            container: 'swal-rtl',
                                            title: 'text-right',
                                            htmlContainer: 'text-right'
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە',
                                        text: data.message || 'هەڵەیەک ڕوویدا',
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            } catch (error) {
                                console.error('Error parsing JSON:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: 'هەڵەیەک ڕوویدا لە جێبەجێکردنی داواکاریەکە',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                                confirmButtonText: 'باشە'
                            });
                        }
                    });
                });
            });

            // Handle edit purchase button
            $(document).on('click', '.edit-purchase', function() {
                const purchaseId = $(this).data('id');
                loadPurchaseForEditing(purchaseId);
            });
            
            // Handle purchase edit save button
            $('#savePurchaseEdit').on('click', function() {
                savePurchaseChanges();
            });
            
            // Function to load purchase data for editing
            function loadPurchaseForEditing(purchaseId) {
                if (!purchaseId) return;
                
                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'زانیارییەکان وەردەگیرێن',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Fetch purchase details
                $.ajax({
                    url: '../../api/receipts/get_purchase.php',
                    type: 'POST',
                    data: { id: purchaseId },
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            populatePurchaseEditForm(response.data);
                            $('#editPurchaseModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیارییەکان',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            }
            
            // Populate edit form with purchase data
            function populatePurchaseEditForm(purchaseData) {
                $('#editPurchaseId').val(purchaseData.id);
                $('#editPurchaseInvoiceNumber').val(purchaseData.invoice_number);
                $('#editPurchaseSupplier').val(purchaseData.supplier_id);
                $('#editPurchaseDate').val(formatDateForInput(purchaseData.date));
                $('#editPurchasePaymentType').val(purchaseData.payment_type);
                $('#editPurchaseShippingCost').val(purchaseData.shipping_cost || 0);
                $('#editPurchaseOtherCost').val(purchaseData.other_cost || 0);
                $('#editPurchaseDiscount').val(purchaseData.discount || 0);
                $('#editPurchaseNotes').val(purchaseData.notes || '');
                
                // Disable payment type field if purchase has returns or payments
                if (purchaseData.has_returns || purchaseData.has_payments) {
                    $('#editPurchasePaymentType').prop('disabled', true);
                    
                    // Add a note about why the field is disabled
                    if (purchaseData.has_returns && purchaseData.has_payments) {
                        $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵا و پارەدانی لەسەر تۆمارکراوە</small>').insertAfter('#editPurchasePaymentType');
                    } else if (purchaseData.has_returns) {
                        $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە</small>').insertAfter('#editPurchasePaymentType');
                    } else if (purchaseData.has_payments) {
                        $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە پارەدانی لەسەر تۆمارکراوە</small>').insertAfter('#editPurchasePaymentType');
                    }
                } else {
                    $('#editPurchasePaymentType').prop('disabled', false);
                    // Remove any existing note
                    $('#editPurchasePaymentType').next('small.text-danger').remove();
                }
            }
            
            // Save purchase changes
            function savePurchaseChanges() {
                // Validate form
                if (!validatePurchaseEditForm()) {
                    return;
                }
                
                // Get form data
                const purchaseData = {
                    id: $('#editPurchaseId').val(),
                    invoice_number: $('#editPurchaseInvoiceNumber').val(),
                    supplier_id: $('#editPurchaseSupplier').val(),
                    date: $('#editPurchaseDate').val(),
                    payment_type: $('#editPurchasePaymentType').val(),
                    shipping_cost: $('#editPurchaseShippingCost').val() || 0,
                    other_cost: $('#editPurchaseOtherCost').val() || 0,
                    discount: $('#editPurchaseDiscount').val() || 0,
                    notes: $('#editPurchaseNotes').val()
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
                        updatePurchase(purchaseData);
                    }
                });
            }
            
            // Validate purchase edit form
            function validatePurchaseEditForm() {
                // Required fields
                if (!$('#editPurchaseInvoiceNumber').val()) {
                    showError('تکایە ژمارەی پسووڵە بنووسە');
                    return false;
                }
                if (!$('#editPurchaseSupplier').val()) {
                    showError('تکایە دابینکەر هەڵبژێرە');
                    return false;
                }
                if (!$('#editPurchaseDate').val()) {
                    showError('تکایە بەروار هەڵبژێرە');
                    return false;
                }
                
                // Numeric fields should be non-negative
                if ($('#editPurchaseShippingCost').val() < 0) {
                    showError('کرێی گواستنەوە ناتوانێت کەمتر بێت لە سفر');
                    return false;
                }
                if ($('#editPurchaseOtherCost').val() < 0) {
                    showError('خەرجی تر ناتوانێت کەمتر بێت لە سفر');
                    return false;
                }
                if ($('#editPurchaseDiscount').val() < 0) {
                    showError('داشکاندن ناتوانێت کەمتر بێت لە سفر');
                    return false;
                }
                
                return true;
            }
            
            // Update purchase in database
            function updatePurchase(purchaseData) {
                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'پسووڵەکە نوێ دەکرێتەوە',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send update request
                $.ajax({
                    url: '../../api/receipts/update_purchase.php',
                    type: 'POST',
                    data: purchaseData,
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            // Close modal
                            $('#editPurchaseModal').modal('hide');
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: 'پسووڵەکە بە سەرکەوتوویی نوێ کرایەوە',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                // Reload the page to show updated data
                                location.reload();
                            });
                        } else {
                            showError(response.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی پسووڵەکە');
                        }
                    },
                    error: function() {
                        Swal.close();
                        showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە');
                    }
                });
            }
            
            // Helper function to show error messages
            function showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: message,
                    confirmButtonText: 'باشە'
                });
            }
            
            // Helper function to format date for input field
            function formatDateForInput(dateString) {
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            }

            // Return purchase button handler
            $(document).on('click', '.return-purchase', function() {
                const purchaseId = $(this).data('id');
                const invoiceNumber = $(this).data('invoice');
                
                // Get purchase items
                $.ajax({
                    url: '../../ajax/get_purchase_items.php',
                    type: 'POST',
                    data: {
                        purchase_id: purchaseId
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            // Create return form
                            let itemsHtml = '<form id="returnPurchaseForm">';
                            itemsHtml += '<input type="hidden" name="purchase_id" value="' + purchaseId + '">';
                            
                            // Add introduction text explaining return limits
                            itemsHtml += '<div class="alert alert-info mb-3">';
                            itemsHtml += '<i class="fas fa-info-circle me-2"></i> ';
                            itemsHtml += 'تکایە ئاگاداربە کە ناتوانیت لە بڕی ئەسڵی کەمتر بڕێک بگەڕێنیتەوە. ';
                            itemsHtml += 'هەروەها ناتوانیت کاڵایەک دووبارە بگەڕێنیتەوە کە پێشتر گەڕێنراوەتەوە.';
                            itemsHtml += '</div>';
                            
                            itemsHtml += '<div class="table-responsive"><table class="table table-bordered">';
                            itemsHtml += '<thead><tr>';
                            itemsHtml += '<th>ناوی کاڵا</th>';
                            itemsHtml += '<th>بڕی کڕین</th>';
                            itemsHtml += '<th>گەڕاوە پێشتر</th>';
                            itemsHtml += '<th>بەردەست بۆ گەڕاندنەوە</th>';
                            itemsHtml += '<th>بڕی گەڕاندنەوە</th>';
                            itemsHtml += '</tr></thead>';
                            itemsHtml += '<tbody>';
                            
                            data.items.forEach(item => {
                                // Calculate max returnable amount (total quantity - already returned quantity)
                                const originalQty = parseFloat(item.quantity);
                                const returnedQty = parseFloat(item.returned_quantity || 0);
                                const maxReturnable = originalQty - returnedQty;
                                
                                // Skip if nothing left to return
                                if (maxReturnable <= 0) {
                                    itemsHtml += `<tr class="table-secondary">
                                        <td>${item.product_name}</td>
                                        <td>${originalQty} ${item.unit_type}</td>
                                        <td>${returnedQty} ${item.unit_type}</td>
                                        <td>0 ${item.unit_type}</td>
                                        <td><span class="badge bg-secondary">هەمووی گەڕاوەتەوە</span></td>
                                    </tr>`;
                                } else {
                                    itemsHtml += `<tr>
                                        <td>${item.product_name}</td>
                                        <td>${originalQty} ${item.unit_type}</td>
                                        <td>${returnedQty} ${item.unit_type}</td>
                                        <td><strong class="text-success">${maxReturnable} ${item.unit_type}</strong></td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" class="form-control return-quantity" 
                                                    name="return_quantities[${item.id}]" 
                                                    min="0" max="${maxReturnable}" value="0"
                                                    step="0.001">
                                                <span class="input-group-text">${item.unit_type}</span>
                                            </div>
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
                                width: '800px',
                                showCancelButton: true,
                                confirmButtonText: 'گەڕاندنەوە',
                                cancelButtonText: 'هەڵوەشاندنەوە',
                                showLoaderOnConfirm: true,
                                preConfirm: () => {
                                    // Validate that at least one item has been selected for return
                                    let hasReturns = false;
                                    document.querySelectorAll('.return-quantity').forEach(input => {
                                        if (parseFloat(input.value) > 0) {
                                            hasReturns = true;
                                        }
                                    });
                                    
                                    if (!hasReturns) {
                                        Swal.showValidationMessage('تکایە لانی کەم یەک کاڵا هەڵبژێرە بۆ گەڕاندنەوە');
                                        return false;
                                    }
                                    
                                    const formData = new FormData(document.getElementById('returnPurchaseForm'));
                                    // Add receipt_type parameter to indicate this is a purchase (buying) return
                                    formData.append('receipt_type', 'buying');
                                    
                                    return $.ajax({
                                        url: '../../ajax/return_purchase.php',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        dataType: 'json'
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
                                text: data.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی کاڵاکان',
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
        });
    </script>

    <!-- Edit Purchase Modal -->
    <div class="modal fade" id="editPurchaseModal" tabindex="-1" role="dialog" aria-labelledby="editPurchaseModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPurchaseModalLabel">دەستکاری پسووڵەی کڕین</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="داخستن"></button>
                </div>
                <div class="modal-body">
                    <form id="editPurchaseForm">
                        <input type="hidden" id="editPurchaseId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editPurchaseInvoiceNumber" class="form-label">ژمارەی پسووڵە</label>
                                <input type="text" class="form-control" id="editPurchaseInvoiceNumber" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editPurchaseSupplier" class="form-label">دابینکەر</label>
                                <select class="form-select" id="editPurchaseSupplier" required>
                                    <option value="">دابینکەر هەڵبژێرە</option>
                                    <?php
                                    $stmt = $conn->query("SELECT id, name FROM suppliers ORDER BY name");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editPurchaseDate" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="editPurchaseDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editPurchasePaymentType" class="form-label">جۆری پارەدان</label>
                                <select class="form-select" id="editPurchasePaymentType" required>
                                    <option value="cash">نەقد</option>
                                    <option value="credit">قەرز</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="editPurchaseShippingCost" class="form-label">کرێی گواستنەوە</label>
                                <input type="number" class="form-control" id="editPurchaseShippingCost" required>
                            </div>
                            <div class="col-md-4">
                                <label for="editPurchaseOtherCost" class="form-label">خەرجی تر</label>
                                <input type="number" class="form-control" id="editPurchaseOtherCost" required>
                            </div>
                            <div class="col-md-4">
                                <label for="editPurchaseDiscount" class="form-label">داشکاندن</label>
                                <input type="number" class="form-control" id="editPurchaseDiscount" required>
                            </div>
                            <div class="col-12">
                                <label for="editPurchaseNotes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="editPurchaseNotes" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="savePurchaseEdit">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    
    
    <!-- Custom JS for purchase details modal -->
    <script>
        $(document).ready(function() {
            // Handle edit purchase button
            $(document).on('click', '.edit-purchase', function() {
                const purchaseId = $(this).data('id');
                loadPurchaseForEditing(purchaseId);
            });
            
            // Function to handle viewing purchase details
            $(document).on('click', '.view-purchase-details', function() {
                const purchaseId = $(this).data('id');
                const invoiceNumber = $(this).data('invoice');
                
                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بە...',
                    html: 'زانیاریەکانی پسووڵە دەهێنرێن',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Fetch purchase details via AJAX
                $.ajax({
                    url: '../../ajax/get_purchase_items.php',
                    type: 'POST',
                    data: {
                        purchase_id: purchaseId
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            
                            if (data.success) {
                                // Format items for display
                                let itemsHtml = `
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>ناوی کاڵا</th>
                                                    <th>بڕ</th>
                                                    <th>نرخی یەکە</th>
                                                    <th>کۆی نرخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                `;
                                
                                let counter = 1;
                                data.items.forEach(item => {
                                    let unitTypeBadge = '';
                                    switch(item.unit_type) {
                                        case 'box':
                                            unitTypeBadge = '<span class="badge bg-info ms-1">کارتۆن</span>';
                                            break;
                                        case 'piece':
                                            unitTypeBadge = '<span class="badge bg-secondary ms-1">دانە</span>';
                                            break;
                                        case 'set':
                                            unitTypeBadge = '<span class="badge bg-warning ms-1">سێت</span>';
                                            break;
                                        default:
                                            unitTypeBadge = '<span class="badge bg-secondary ms-1">دانە</span>';
                                    }
                                    
                                    itemsHtml += `
                                        <tr>
                                            <td>${counter++}</td>
                                            <td>${item.product_name}</td>
                                            <td>${item.quantity} ${unitTypeBadge}</td>
                                            <td>${Number(item.unit_price).toLocaleString()} دینار</td>
                                            <td>${Number(item.total_price).toLocaleString()} دینار</td>
                                        </tr>
                                    `;
                                });
                                
                                itemsHtml += `
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <td colspan="4" class="text-start fw-bold">کۆی گشتی</td>
                                            <td class="fw-bold">${data.total_amount.toLocaleString()} دینار</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        `;
                                
                                // Display the modal with purchase items
                                Swal.fire({
                                    title: `پسووڵەی ژمارە ${invoiceNumber}`,
                                    html: itemsHtml,
                                    width: '800px',
                                    confirmButtonText: 'داخستن',
                                    customClass: {
                                        container: 'swal-rtl',
                                        title: 'text-right',
                                        htmlContainer: 'text-right'
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: data.message || 'هەڵەیەک ڕوویدا',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        } catch (error) {
                            console.error('Error parsing JSON:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: 'هەڵەیەک ڕوویدا لە جێبەجێکردنی داواکاریەکە',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>