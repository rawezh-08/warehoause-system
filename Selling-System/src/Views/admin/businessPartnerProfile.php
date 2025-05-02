<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if partner IDs are provided
$customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$supplierId = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

// If neither customer_id nor supplier_id is provided, redirect to business partners list
if (!$customerId && !$supplierId) {
    header("Location: business_partners.php");
    exit;
}

// Initialize variables
$customer = null;
$supplier = null;
$sales = [];
$purchases = [];
$customerDebtTransactions = [];
$supplierDebtTransactions = [];
$partnerName = "";

// Get customer data if customer_id is provided
if ($customerId) {
    $customerQuery = "SELECT * FROM customers WHERE id = :id";
    $customerStmt = $conn->prepare($customerQuery);
    $customerStmt->bindParam(':id', $customerId);
    $customerStmt->execute();
    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        header("Location: business_partners.php");
        exit;
    }
    
    // If this customer is linked to a supplier, get the supplier data too
    if (!empty($customer['supplier_id'])) {
        $supplierId = $customer['supplier_id'];
    }
    
    $partnerName = $customer['name'];
    
    // Get customer sales data
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
    
    // Get customer debt transactions
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
    $customerDebtTransactions = $debtStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get supplier data if supplier_id is provided
if ($supplierId) {
    $supplierQuery = "SELECT * FROM suppliers WHERE id = :id";
    $supplierStmt = $conn->prepare($supplierQuery);
    $supplierStmt->bindParam(':id', $supplierId);
    $supplierStmt->execute();
    $supplier = $supplierStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        if (!$customer) { // Only redirect if we don't have a customer record either
            header("Location: business_partners.php");
            exit;
        }
    } else {
        if (empty($partnerName)) {
            $partnerName = $supplier['name'];
        }
        
        // Get supplier purchases data
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
        
        // Get supplier debt transactions
        $supplierDebtQuery = "SELECT sdt.*, 
                 CASE 
                    WHEN sdt.transaction_type = 'purchase' THEN (SELECT invoice_number FROM purchases WHERE id = sdt.reference_id)
                    ELSE '' 
                 END as invoice_number
                 FROM supplier_debt_transactions sdt
                 WHERE sdt.supplier_id = :supplier_id 
                 ORDER BY sdt.created_at DESC";
        $supplierDebtStmt = $conn->prepare($supplierDebtQuery);
        $supplierDebtStmt->bindParam(':supplier_id', $supplierId);
        $supplierDebtStmt->execute();
        $supplierDebtTransactions = $supplierDebtStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Calculate summary metrics
$totalSales = 0;
$monthlySales = 0;
$totalPurchases = 0;
$monthlyPurchases = 0;
$totalReturns = 0;

foreach ($sales as $sale) {
    $totalSales += $sale['sale_total'];
    if (date('Y-m', strtotime($sale['date'])) === date('Y-m')) {
        $monthlySales += $sale['sale_total'];
    }
}

foreach ($purchases as $purchase) {
    $totalPurchases += $purchase['total_amount'];
    if (date('Y-m', strtotime($purchase['date'])) === date('Y-m')) {
        $monthlyPurchases += $purchase['total_amount'];
    }
}

// Get the active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'basic';
$tabs = [
    'basic' => 'زانیاری بنەڕەتی',
    'sales' => 'فرۆشتنەکان',
    'purchases' => 'کڕینەکان',
    'customer_debt' => 'قەرزەکان (کڕیار)',
    'supplier_debt' => 'قەرزەکان (دابینکەر)',
    'activity' => 'چالاکییەکان'
];
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی گشتگیری کڕیار و دابینکەر/<?php echo htmlspecialchars($partnerName); ?> - سیستەمی بەڕێوەبردنی کۆگا</title>
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

        /* Partner info card */
        .partner-info-card {
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .partner-info-item {
            margin-bottom: 10px;
        }

        .partner-info-label {
            font-weight: bold;
            color: #6c757d;
        }

        .partner-info-value {
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

        /* Dual role badge */
        .dual-role-badge {
            background: linear-gradient(45deg, #0d6efd 50%, #198754 50%);
            color: white;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
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
                        <h3 class="page-title">
                            پڕۆفایلی گشتگیری کڕیار و دابینکەر/<?php echo htmlspecialchars($partnerName); ?>
                            <?php if ($customer && $supplier): ?>
                            <span class="dual-role-badge ms-2"><i class="fas fa-exchange-alt me-1"></i> کڕیار و دابینکەر</span>
                            <?php elseif ($customer): ?>
                            <span class="badge bg-primary ms-2"><i class="fas fa-user me-1"></i> کڕیار</span>
                            <?php elseif ($supplier): ?>
                            <span class="badge bg-success ms-2"><i class="fas fa-truck me-1"></i> دابینکەر</span>
                            <?php endif; ?>
                        </h3>
                        <div>
                            <a href="addStaff.php?tab=business_partner" class="btn btn-primary me-2">
                                <i class="fas fa-plus me-2"></i> زیادکردنی کڕیار و دابینکەر
                            </a>
                            <a href="business_partners.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-right me-2"></i> گەڕانەوە بۆ لیستی کڕیار و دابینکەرەکان
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <?php if ($customer): ?>
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-primary me-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کۆی فرۆشتن</h5>
                                    <p class="card-value"><?php echo number_format($totalSales); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-warning me-3">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">فرۆشتنی مانگانە</h5>
                                    <p class="card-value"><?php echo number_format($monthlySales); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($supplier): ?>
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-success me-3">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کۆی کڕین</h5>
                                    <p class="card-value"><?php echo number_format($totalPurchases); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-info me-3">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کڕینی مانگانە</h5>
                                    <p class="card-value"><?php echo number_format($monthlyPurchases); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Partner Profile Content -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <!-- Tabs Navigation -->
                            <div class="card-header bg-transparent p-0">
                                <ul class="nav nav-tabs custom-tabs" id="partnerTabs" role="tablist">
                                    <?php foreach ($tabs as $tabKey => $tabName): ?>
                                        <?php 
                                        // Skip tabs that aren't relevant based on what partner type we have
                                        if (($tabKey == 'sales' || $tabKey == 'customer_debt') && !$customer) continue;
                                        if (($tabKey == 'purchases' || $tabKey == 'supplier_debt') && !$supplier) continue;
                                        ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?php echo $activeTab == $tabKey ? 'active' : ''; ?>" 
                                                    id="<?php echo $tabKey; ?>-tab" 
                                                    data-bs-toggle="tab" 
                                                    data-bs-target="#<?php echo $tabKey; ?>-content" 
                                                    type="button" 
                                                    role="tab" 
                                                    aria-controls="<?php echo $tabKey; ?>-content" 
                                                    aria-selected="<?php echo $activeTab == $tabKey ? 'true' : 'false'; ?>">
                                                <?php echo $tabName; ?>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <!-- Tabs Content -->
                            <div class="card-body">
                                <div class="tab-content" id="partnerTabsContent">
                                    <!-- Basic Information Tab -->
                                    <div class="tab-pane fade <?php echo $activeTab == 'basic' ? 'show active' : ''; ?>" id="basic-content" role="tabpanel" aria-labelledby="basic-tab">
                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <?php if ($customer): ?>
                                                <!-- Customer Information Section -->
                                                <div class="card partner-info-card p-4 mb-4">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h5 class="card-title mb-0">
                                                            <i class="fas fa-user me-2 text-primary"></i>
                                                            زانیاری کڕیار
                                                        </h5>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ناوی کڕیار</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($customer['name']); ?></p>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ژمارەی مۆبایل</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($customer['phone1']); ?></p>
                                                        </div>
                                                        
                                                        <?php if (!empty($customer['phone2'])): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ژمارەی مۆبایل ٢</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($customer['phone2']); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($customer['address'])): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ناونیشان</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($customer['address']); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($customer['guarantor_name'])): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ناوی کەفیل</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($customer['guarantor_name']); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($customer['guarantor_phone'])): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ژمارەی مۆبایلی کەفیل</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($customer['guarantor_phone']); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Debt Information Card -->
                                                    <div class="mt-3">
                                                        <h6 class="mb-3">بارودۆخی دارایی کڕیار</h6>
                                                        <div class="d-flex gap-3 mb-3">
                                                            <div class="card border-0 bg-light p-3 flex-grow-1">
                                                                <p class="small text-muted mb-1">قەرزی کڕیار لە ئێمە</p>
                                                                <p class="h5 mb-0 <?php echo $customer['debit_on_business'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                                                    <?php echo number_format($customer['debit_on_business']); ?> دینار
                                                                </p>
                                                            </div>
                                                            <div class="card border-0 bg-light p-3 flex-grow-1">
                                                                <p class="small text-muted mb-1">قەرزی ئێمە لە کڕیار</p>
                                                                <p class="h5 mb-0 <?php echo $customer['debt_on_customer'] > 0 ? 'text-warning' : 'text-success'; ?>">
                                                                    <?php echo number_format($customer['debt_on_customer']); ?> دینار
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($supplier): ?>
                                                <!-- Supplier Information Section -->
                                                <div class="card partner-info-card p-4">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h5 class="card-title mb-0">
                                                            <i class="fas fa-truck me-2 text-success"></i>
                                                            زانیاری دابینکەر
                                                        </h5>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ناوی دابینکەر</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($supplier['name']); ?></p>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ژمارەی مۆبایل</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($supplier['phone1']); ?></p>
                                                        </div>
                                                        
                                                        <?php if (!empty($supplier['phone2'])): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <p class="partner-info-label">ژمارەی مۆبایل ٢</p>
                                                            <p class="partner-info-value"><?php echo htmlspecialchars($supplier['phone2']); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Debt Information Card -->
                                                    <div class="mt-3">
                                                        <h6 class="mb-3">بارودۆخی دارایی دابینکەر</h6>
                                                        <div class="d-flex gap-3 mb-3">
                                                            <div class="card border-0 bg-light p-3 flex-grow-1">
                                                                <p class="small text-muted mb-1">قەرزی ئێمە لە دابینکەر</p>
                                                                <p class="h5 mb-0 <?php echo $supplier['debt_on_myself'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                                                    <?php echo number_format($supplier['debt_on_myself']); ?> دینار
                                                                </p>
                                                            </div>
                                                            <div class="card border-0 bg-light p-3 flex-grow-1">
                                                                <p class="small text-muted mb-1">قەرزی دابینکەر لە ئێمە</p>
                                                                <p class="h5 mb-0 <?php echo $supplier['debt_on_supplier'] > 0 ? 'text-warning' : 'text-success'; ?>">
                                                                    <?php echo number_format($supplier['debt_on_supplier']); ?> دینار
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Notes and Actions Section -->
                                            <div class="col-md-6 mb-4">
                                                <div class="card p-4 h-100">
                                                    <h5 class="card-title mb-3">
                                                        <i class="fas fa-sticky-note me-2 text-warning"></i>
                                                        تێبینییەکان
                                                    </h5>
                                                    <div class="bg-light p-3 rounded mb-4" style="min-height: 120px;">
                                                        <?php if ($customer && !empty($customer['notes'])): ?>
                                                            <p class="mb-3">
                                                                <strong class="text-primary"><i class="fas fa-user me-1"></i> تێبینی کڕیار:</strong><br>
                                                                <?php echo nl2br(htmlspecialchars($customer['notes'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($supplier && !empty($supplier['notes'])): ?>
                                                            <p class="mb-0">
                                                                <strong class="text-success"><i class="fas fa-truck me-1"></i> تێبینی دابینکەر:</strong><br>
                                                                <?php echo nl2br(htmlspecialchars($supplier['notes'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ((!$customer || empty($customer['notes'])) && (!$supplier || empty($supplier['notes']))): ?>
                                                            <p class="text-muted mb-0">هیچ تێبینییەک نییە</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <h5 class="card-title mb-3">
                                                        <i class="fas fa-tools me-2 text-secondary"></i>
                                                        کردارەکان
                                                    </h5>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php if ($customer): ?>
                                                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                                            <i class="fas fa-money-bill-wave me-1"></i> پارەدان بۆ کڕیار
                                                        </a>
                                                        <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#collectDebtModal">
                                                            <i class="fas fa-hand-holding-usd me-1"></i> وەرگرتنی قەرز لە کڕیار
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($supplier): ?>
                                                        <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#supplierPaymentModal">
                                                            <i class="fas fa-money-bill-wave me-1"></i> پارەدان بۆ دابینکەر
                                                        </a>
                                                        <a href="#" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#collectSupplierDebtModal">
                                                            <i class="fas fa-hand-holding-usd me-1"></i> وەرگرتنی قەرز لە دابینکەر
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($customer): ?>
                                                        <a href="../../Views/receipt/customer_history_receipt.php?customer_id=<?php echo $customerId; ?>" class="btn btn-outline-primary" target="_blank">
                                                            <i class="fas fa-history me-1"></i> بینینی مێژووی کڕیار
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($supplier): ?>
                                                        <a href="../../Views/receipt/supplier_history_receipt.php?supplier_id=<?php echo $supplierId; ?>" class="btn btn-outline-success" target="_blank">
                                                            <i class="fas fa-history me-1"></i> بینینی مێژووی دابینکەر
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPartnerModal">
                                                            <i class="fas fa-edit me-1"></i> دەستکاری زانیاری
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($customer): ?>
                                    <div class="tab-pane fade <?php echo $activeTab == 'sales' ? 'show active' : ''; ?>" id="sales-content" role="tabpanel" aria-labelledby="sales-tab">
                                        <h5 class="mb-3">فرۆشتنەکان بۆ <?php echo htmlspecialchars($customer['name']); ?></h5>
                                        
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
                                        
                                        <!-- Sales Table -->
                                        <div class="table-responsive">
                                            <table id="salesTable" class="table table-bordered table-hover custom-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>ژمارەی وەصل</th>
                                                        <th>بەروار</th>
                                                        <th>کۆی گشتی</th>
                                                        <th>شێوازی پارەدان</th>
                                                        <th>کەش کراو</th>
                                                        <th>قەرز</th>
                                                        <th>کردارەکان</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($sales) > 0): 
                                                        // Group sales by sale ID
                                                        $groupedSales = [];
                                                        foreach ($sales as $saleItem) {
                                                            $saleId = $saleItem['id'];
                                                            if (!isset($groupedSales[$saleId])) {
                                                                $groupedSales[$saleId] = $saleItem;
                                                            }
                                                        }
                                                        
                                                        $counter = 1;
                                                        foreach ($groupedSales as $sale): 
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                                            <td><?php echo date('Y-m-d', strtotime($sale['date'])); ?></td>
                                                            <td><?php echo number_format($sale['invoice_total']); ?> دینار</td>
                                                            <td>
                                                                <?php if ($sale['payment_type'] == 'cash'): ?>
                                                                    <span class="badge bg-success">کاش</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">قەرز</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo number_format($sale['paid_amount']); ?> دینار</td>
                                                            <td>
                                                                <?php 
                                                                $debt = $sale['invoice_total'] - $sale['paid_amount'];
                                                                echo number_format($debt) . ' دینار';
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <a href="../../Views/receipt/sale_receipt.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" target="_blank" title="بینینی وەصل">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach;
                                                    else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">هیچ فرۆشتنێک نەدۆزرایەوە</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Sales Table Pagination -->
                                        <div class="table-pagination mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-6 mb-2 mb-md-0">
                                                    <div class="pagination-info">
                                                        نیشاندانی <span id="salesStartRecord">1</span> تا <span id="salesEndRecord">10</span> لە کۆی <span id="salesTotalRecords"><?php echo count($groupedSales ?? []); ?></span> تۆمار
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
                                    <div class="tab-pane fade <?php echo $activeTab == 'customer_debt' ? 'show active' : ''; ?>" id="customer_debt-content" role="tabpanel" aria-labelledby="customer_debt-tab">
                                        <h5 class="mb-3">قەرزەکانی کڕیار - <?php echo htmlspecialchars($customer['name']); ?></h5>
                                        
                                        <!-- Debt Summary Cards -->
                                        <div class="row mb-4">
                                            <div class="col-md-4">
                                                <div class="card summary-card bg-white border-0">
                                                    <div class="card-body d-flex align-items-center">
                                                        <div class="icon-bg bg-danger me-3">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title">قەرزی کڕیار لە ئێمە</h5>
                                                            <p class="card-value"><?php echo number_format($customer['debit_on_business']); ?> دینار</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card summary-card bg-white border-0">
                                                    <div class="card-body d-flex align-items-center">
                                                        <div class="icon-bg bg-warning me-3">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title">قەرزی ئێمە لە کڕیار</h5>
                                                            <p class="card-value"><?php echo number_format($customer['debt_on_customer']); ?> دینار</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Table Controls -->
                                        <div class="table-controls mb-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                                    <div class="records-per-page">
                                                        <label class="me-2">نیشاندان:</label>
                                                        <div class="custom-select-wrapper">
                                                            <select id="customerDebtRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                            <input type="text" id="customerDebtTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                            <span class="input-group-text rounded-pill-end bg-light">
                                                                <i class="fas fa-search"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Customer Debt Transactions Table -->
                                        <div class="table-responsive">
                                            <table id="customerDebtTable" class="table table-bordered table-hover custom-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>بەروار</th>
                                                        <th>جۆری مامەڵە</th>
                                                        <th>بڕی پارە</th>
                                                        <th>ژمارەی وەصل</th>
                                                        <th>تێبینی</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($customerDebtTransactions) > 0): 
                                                        $counter = 1;
                                                        foreach ($customerDebtTransactions as $transaction): 
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?></td>
                                                            <td>
                                                                <?php 
                                                                switch ($transaction['transaction_type']) {
                                                                    case 'sale':
                                                                        echo '<span class="badge bg-primary">فرۆشتن</span>';
                                                                        break;
                                                                    case 'payment':
                                                                        echo '<span class="badge bg-success">پارەدان</span>';
                                                                        break;
                                                                    case 'collection':
                                                                        echo '<span class="badge bg-warning">وەرگرتنی قەرز</span>';
                                                                        break;
                                                                    default:
                                                                        echo '<span class="badge bg-secondary">' . $transaction['transaction_type'] . '</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php echo number_format($transaction['amount']); ?> دینار
                                                            </td>
                                                            <td>
                                                                <?php echo !empty($transaction['invoice_number']) ? htmlspecialchars($transaction['invoice_number']) : '-'; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-'; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach;
                                                    else: ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">هیچ مامەڵەیەک نەدۆزرایەوە</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Customer Debt Table Pagination -->
                                        <div class="table-pagination mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-6 mb-2 mb-md-0">
                                                    <div class="pagination-info">
                                                        نیشاندانی <span id="customerDebtStartRecord">1</span> تا <span id="customerDebtEndRecord">10</span> لە کۆی <span id="customerDebtTotalRecords"><?php echo count($customerDebtTransactions); ?></span> تۆمار
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="pagination-controls d-flex justify-content-md-end">
                                                        <button id="customerDebtPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                            <i class="fas fa-chevron-right"></i>
                                                        </button>
                                                        <div id="customerDebtPaginationNumbers" class="pagination-numbers d-flex">
                                                            <!-- Will be populated by JavaScript -->
                                                        </div>
                                                        <button id="customerDebtNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($supplier): ?>
                                    <div class="tab-pane fade <?php echo $activeTab == 'purchases' ? 'show active' : ''; ?>" id="purchases-content" role="tabpanel" aria-labelledby="purchases-tab">
                                        <h5 class="mb-3">کڕینەکان لە <?php echo htmlspecialchars($supplier['name']); ?></h5>
                                        
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
                                        
                                        <!-- Purchases Table -->
                                        <div class="table-responsive">
                                            <table id="purchasesTable" class="table table-bordered table-hover custom-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>ژمارەی وەصل</th>
                                                        <th>بەروار</th>
                                                        <th>کۆی گشتی</th>
                                                        <th>شێوازی پارەدان</th>
                                                        <th>پێشەکی دراو</th>
                                                        <th>قەرز</th>
                                                        <th>کردارەکان</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($purchases) > 0): 
                                                        // Group purchases by purchase ID
                                                        $groupedPurchases = [];
                                                        foreach ($purchases as $purchaseItem) {
                                                            $purchaseId = $purchaseItem['id'];
                                                            if (!isset($groupedPurchases[$purchaseId])) {
                                                                $groupedPurchases[$purchaseId] = $purchaseItem;
                                                            }
                                                        }
                                                        
                                                        $counter = 1;
                                                        foreach ($groupedPurchases as $purchase): 
                                                            $totalAmount = $purchase['total_amount'] + $purchase['shipping_cost'] + $purchase['other_cost'] - $purchase['discount'];
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                                                            <td><?php echo date('Y-m-d', strtotime($purchase['date'])); ?></td>
                                                            <td><?php echo number_format($totalAmount); ?> دینار</td>
                                                            <td>
                                                                <?php if ($purchase['payment_type'] == 'cash'): ?>
                                                                    <span class="badge bg-success">کاش</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">قەرز</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo number_format($purchase['paid_amount']); ?> دینار</td>
                                                            <td>
                                                                <?php 
                                                                $debt = $totalAmount - $purchase['paid_amount'];
                                                                echo number_format($debt) . ' دینار';
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <a href="../../Views/receipt/purchase_receipt.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" target="_blank" title="بینینی وەصل">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach;
                                                    else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">هیچ کڕینێک نەدۆزرایەوە</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Purchases Table Pagination -->
                                        <div class="table-pagination mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-6 mb-2 mb-md-0">
                                                    <div class="pagination-info">
                                                        نیشاندانی <span id="purchasesStartRecord">1</span> تا <span id="purchasesEndRecord">10</span> لە کۆی <span id="purchasesTotalRecords"><?php echo count($groupedPurchases ?? []); ?></span> تۆمار
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
                                    <div class="tab-pane fade <?php echo $activeTab == 'supplier_debt' ? 'show active' : ''; ?>" id="supplier_debt-content" role="tabpanel" aria-labelledby="supplier_debt-tab">
                                        <h5 class="mb-3">قەرزەکانی دابینکەر - <?php echo htmlspecialchars($supplier['name']); ?></h5>
                                        
                                        <!-- Debt Summary Cards -->
                                        <div class="row mb-4">
                                            <div class="col-md-4">
                                                <div class="card summary-card bg-white border-0">
                                                    <div class="card-body d-flex align-items-center">
                                                        <div class="icon-bg bg-danger me-3">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title">قەرزی ئێمە لە دابینکەر</h5>
                                                            <p class="card-value"><?php echo number_format($supplier['debt_on_myself']); ?> دینار</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card summary-card bg-white border-0">
                                                    <div class="card-body d-flex align-items-center">
                                                        <div class="icon-bg bg-warning me-3">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title">قەرزی دابینکەر لە ئێمە</h5>
                                                            <p class="card-value"><?php echo number_format($supplier['debt_on_supplier']); ?> دینار</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Table Controls -->
                                        <div class="table-controls mb-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                                    <div class="records-per-page">
                                                        <label class="me-2">نیشاندان:</label>
                                                        <div class="custom-select-wrapper">
                                                            <select id="supplierDebtRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                            <input type="text" id="supplierDebtTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                            <span class="input-group-text rounded-pill-end bg-light">
                                                                <i class="fas fa-search"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Supplier Debt Transactions Table -->
                                        <div class="table-responsive">
                                            <table id="supplierDebtTable" class="table table-bordered table-hover custom-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>بەروار</th>
                                                        <th>جۆری مامەڵە</th>
                                                        <th>بڕی پارە</th>
                                                        <th>ژمارەی وەصل</th>
                                                        <th>تێبینی</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($supplierDebtTransactions) > 0): 
                                                        $counter = 1;
                                                        foreach ($supplierDebtTransactions as $transaction): 
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?></td>
                                                            <td>
                                                                <?php 
                                                                switch ($transaction['transaction_type']) {
                                                                    case 'purchase':
                                                                        echo '<span class="badge bg-primary">کڕین</span>';
                                                                        break;
                                                                    case 'payment':
                                                                        echo '<span class="badge bg-success">پارەدان</span>';
                                                                        break;
                                                                    case 'return':
                                                                        echo '<span class="badge bg-danger">گەڕاندنەوە</span>';
                                                                        break;
                                                                    case 'supplier_payment':
                                                                        echo '<span class="badge bg-info">پارەدانی دابینکەر</span>';
                                                                        break;
                                                                    case 'supplier_return':
                                                                        echo '<span class="badge bg-warning">گەڕاندنەوەی دابینکەر</span>';
                                                                        break;
                                                                    default:
                                                                        echo '<span class="badge bg-secondary">' . $transaction['transaction_type'] . '</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php echo number_format($transaction['amount']); ?> دینار
                                                            </td>
                                                            <td>
                                                                <?php echo !empty($transaction['invoice_number']) ? htmlspecialchars($transaction['invoice_number']) : '-'; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-'; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach;
                                                    else: ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">هیچ مامەڵەیەک نەدۆزرایەوە</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Supplier Debt Table Pagination -->
                                        <div class="table-pagination mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-6 mb-2 mb-md-0">
                                                    <div class="pagination-info">
                                                        نیشاندانی <span id="supplierDebtStartRecord">1</span> تا <span id="supplierDebtEndRecord">10</span> لە کۆی <span id="supplierDebtTotalRecords"><?php echo count($supplierDebtTransactions); ?></span> تۆمار
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="pagination-controls d-flex justify-content-md-end">
                                                        <button id="supplierDebtPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                            <i class="fas fa-chevron-right"></i>
                                                        </button>
                                                        <div id="supplierDebtPaginationNumbers" class="pagination-numbers d-flex">
                                                            <!-- Will be populated by JavaScript -->
                                                        </div>
                                                        <button id="supplierDebtNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Activity Tab -->
                                    <div class="tab-pane fade <?php echo $activeTab == 'activity' ? 'show active' : ''; ?>" id="activity-content" role="tabpanel" aria-labelledby="activity-tab">
                                        <h5 class="mb-4">چالاکییەکانی کڕیار و دابینکەر - <?php echo htmlspecialchars($partnerName); ?></h5>
                                        
                                        <!-- Combined Activity Timeline -->
                                        <div class="activity-timeline">
                                            <?php
                                            // Combine all transactions for timeline
                                            $allActivities = [];
                                            
                                            // Add customer sales
                                            if (!empty($sales)) {
                                                foreach ($sales as $sale) {
                                                    $allActivities[] = [
                                                        'type' => 'sale',
                                                        'date' => $sale['date'],
                                                        'amount' => $sale['invoice_total'] ?? $sale['sale_total'],
                                                        'details' => 'فرۆشتن - ژمارەی وەصل: ' . $sale['invoice_number'],
                                                        'id' => $sale['id']
                                                    ];
                                                }
                                            }
                                            
                                            // Add supplier purchases
                                            if (!empty($purchases)) {
                                                foreach ($purchases as $purchase) {
                                                    $totalAmount = $purchase['total_amount'] + $purchase['shipping_cost'] + $purchase['other_cost'] - $purchase['discount'];
                                                    $allActivities[] = [
                                                        'type' => 'purchase',
                                                        'date' => $purchase['date'],
                                                        'amount' => $totalAmount,
                                                        'details' => 'کڕین - ژمارەی وەصل: ' . $purchase['invoice_number'],
                                                        'id' => $purchase['id']
                                                    ];
                                                }
                                            }
                                            
                                            // Add customer debt transactions
                                            if (!empty($customerDebtTransactions)) {
                                                foreach ($customerDebtTransactions as $transaction) {
                                                    if ($transaction['transaction_type'] == 'payment' || $transaction['transaction_type'] == 'collection') {
                                                        $allActivities[] = [
                                                            'type' => $transaction['transaction_type'],
                                                            'date' => $transaction['created_at'],
                                                            'amount' => $transaction['amount'],
                                                            'details' => $transaction['transaction_type'] == 'payment' ? 
                                                                'پارەدان بۆ کڕیار' : 'وەرگرتنی قەرز لە کڕیار',
                                                            'id' => $transaction['id'],
                                                            'notes' => $transaction['notes']
                                                        ];
                                                    }
                                                }
                                            }
                                            
                                            // Add supplier debt transactions
                                            if (!empty($supplierDebtTransactions)) {
                                                foreach ($supplierDebtTransactions as $transaction) {
                                                    if ($transaction['transaction_type'] == 'payment' || 
                                                        $transaction['transaction_type'] == 'supplier_payment' || 
                                                        $transaction['transaction_type'] == 'return' || 
                                                        $transaction['transaction_type'] == 'supplier_return') {
                                                        
                                                        $activityType = '';
                                                        switch ($transaction['transaction_type']) {
                                                            case 'payment':
                                                                $activityType = 'پارەدان بۆ دابینکەر';
                                                                break;
                                                            case 'supplier_payment':
                                                                $activityType = 'پارەدانی دابینکەر';
                                                                break;
                                                            case 'return':
                                                                $activityType = 'گەڕاندنەوە';
                                                                break;
                                                            case 'supplier_return':
                                                                $activityType = 'گەڕاندنەوەی دابینکەر';
                                                                break;
                                                        }
                                                        
                                                        $allActivities[] = [
                                                            'type' => $transaction['transaction_type'],
                                                            'date' => $transaction['created_at'],
                                                            'amount' => $transaction['amount'],
                                                            'details' => $activityType,
                                                            'id' => $transaction['id'],
                                                            'notes' => $transaction['notes']
                                                        ];
                                                    }
                                                }
                                            }
                                            
                                            // Sort activities by date (newest first)
                                            usort($allActivities, function($a, $b) {
                                                return strtotime($b['date']) - strtotime($a['date']);
                                            });
                                            
                                            // Display timeline
                                            if (!empty($allActivities)):
                                                foreach ($allActivities as $index => $activity):
                                                    $activityClass = '';
                                                    $activityIcon = '';
                                                    
                                                    switch ($activity['type']) {
                                                        case 'sale':
                                                            $activityClass = 'primary';
                                                            $activityIcon = 'shopping-cart';
                                                            break;
                                                        case 'purchase':
                                                            $activityClass = 'success';
                                                            $activityIcon = 'truck';
                                                            break;
                                                        case 'payment':
                                                            $activityClass = 'warning';
                                                            $activityIcon = 'money-bill-wave';
                                                            break;
                                                        case 'collection':
                                                            $activityClass = 'danger';
                                                            $activityIcon = 'hand-holding-usd';
                                                            break;
                                                        case 'supplier_payment':
                                                            $activityClass = 'info';
                                                            $activityIcon = 'money-check-alt';
                                                            break;
                                                        case 'return':
                                                        case 'supplier_return':
                                                            $activityClass = 'secondary';
                                                            $activityIcon = 'undo-alt';
                                                            break;
                                                        default:
                                                            $activityClass = 'secondary';
                                                            $activityIcon = 'circle';
                                                    }
                                            ?>
                                            <div class="card mb-3 border-0 shadow-sm">
                                                <div class="card-body">
                                                    <div class="d-flex">
                                                        <div class="me-3">
                                                            <div class="bg-<?php echo $activityClass; ?> text-white rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="fas fa-<?php echo $activityIcon; ?>"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <h6 class="mb-0"><?php echo $activity['details']; ?></h6>
                                                                <small class="text-muted"><?php echo date('Y-m-d', strtotime($activity['date'])); ?></small>
                                                            </div>
                                                            <p class="mb-1">
                                                                <strong>بڕی پارە:</strong> <?php echo number_format($activity['amount']); ?> دینار
                                                            </p>
                                                            <?php if (!empty($activity['notes'])): ?>
                                                            <p class="mb-0 small text-muted">
                                                                <strong>تێبینی:</strong> <?php echo htmlspecialchars($activity['notes']); ?>
                                                            </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i> هیچ چالاکییەک نەدۆزرایەوە
                                            </div>
                                            <?php endif; ?>
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
    
    <!-- Modals for payment actions will be implemented here -->
    
    <!-- Customer Payment Modal -->
    <?php if ($customer): ?>
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">پارەدان بۆ کڕیار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="customerPaymentForm">
                        <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                        <div class="mb-3">
                            <label for="amount" class="form-label">بڕی پارە</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveCustomerPayment">پاشەکەوت</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Collect Customer Debt Modal -->
    <div class="modal fade" id="collectDebtModal" tabindex="-1" aria-labelledby="collectDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="collectDebtModalLabel">وەرگرتنی قەرز لە کڕیار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="collectCustomerDebtForm">
                        <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                        <div class="mb-3">
                            <label for="collection_amount" class="form-label">بڕی پارە</label>
                            <input type="number" class="form-control" id="collection_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="collection_notes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="collection_notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-warning" id="saveCustomerCollection">پاشەکەوت</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Supplier Payment Modal -->
    <?php if ($supplier): ?>
    <div class="modal fade" id="supplierPaymentModal" tabindex="-1" aria-labelledby="supplierPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierPaymentModalLabel">پارەدان بۆ دابینکەر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="supplierPaymentForm">
                        <input type="hidden" name="supplier_id" value="<?php echo $supplierId; ?>">
                        <div class="mb-3">
                            <label for="supplier_amount" class="form-label">بڕی پارە</label>
                            <input type="number" class="form-control" id="supplier_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="supplier_notes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="supplier_notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-success" id="saveSupplierPayment">پاشەکەوت</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Collect Supplier Debt Modal -->
    <div class="modal fade" id="collectSupplierDebtModal" tabindex="-1" aria-labelledby="collectSupplierDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="collectSupplierDebtModalLabel">وەرگرتنی قەرز لە دابینکەر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="collectSupplierDebtForm">
                        <input type="hidden" name="supplier_id" value="<?php echo $supplierId; ?>">
                        <div class="mb-3">
                            <label for="supplier_collection_amount" class="form-label">بڕی پارە</label>
                            <input type="number" class="form-control" id="supplier_collection_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="supplier_collection_notes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="supplier_collection_notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-info" id="saveSupplierCollection">پاشەکەوت</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Partner Modal -->
    <div class="modal fade" id="editPartnerModal" tabindex="-1" aria-labelledby="editPartnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPartnerModalLabel">دەستکاری زانیاری <?php echo htmlspecialchars($partnerName); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPartnerForm">
                        <div class="row">
                            <?php if ($customer): ?>
                            <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                            <?php endif; ?>
                            
                            <?php if ($supplier): ?>
                            <input type="hidden" name="supplier_id" value="<?php echo $supplierId; ?>">
                            <?php endif; ?>
                            
                            <div class="col-md-6 mb-3">
                                <label for="partner_name" class="form-label">ناو</label>
                                <input type="text" class="form-control" id="partner_name" name="name" value="<?php echo htmlspecialchars($partnerName); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="partner_phone1" class="form-label">ژمارەی مۆبایل</label>
                                <input type="text" class="form-control" id="partner_phone1" name="phone1" value="<?php echo $customer ? htmlspecialchars($customer['phone1']) : htmlspecialchars($supplier['phone1']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="partner_phone2" class="form-label">ژمارەی مۆبایل ٢</label>
                                <input type="text" class="form-control" id="partner_phone2" name="phone2" value="<?php echo $customer ? htmlspecialchars($customer['phone2'] ?? '') : htmlspecialchars($supplier['phone2'] ?? ''); ?>">
                            </div>
                            
                            <?php if ($customer): ?>
                            <div class="col-md-6 mb-3">
                                <label for="partner_address" class="form-label">ناونیشان</label>
                                <input type="text" class="form-control" id="partner_address" name="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="guarantor_name" class="form-label">ناوی کەفیل</label>
                                <input type="text" class="form-control" id="guarantor_name" name="guarantor_name" value="<?php echo htmlspecialchars($customer['guarantor_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="guarantor_phone" class="form-label">ژمارەی مۆبایلی کەفیل</label>
                                <input type="text" class="form-control" id="guarantor_phone" name="guarantor_phone" value="<?php echo htmlspecialchars($customer['guarantor_phone'] ?? ''); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-12 mb-3">
                                <label for="partner_notes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="partner_notes" name="notes" rows="3"><?php echo $customer ? htmlspecialchars($customer['notes'] ?? '') : htmlspecialchars($supplier['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="savePartnerChanges">پاشەکەوت</button>
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
    
    <script>
        $(document).ready(function() {
            // Handle tab navigation with URL parameter
            const tabs = $('#partnerTabs .nav-link');
            tabs.on('click', function() {
                const tabId = $(this).attr('id').replace('-tab', '');
                // Update URL without reloading page
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            });
            
            // Table pagination functionality - sales
            initTablePagination('sales');
            
            // Table pagination functionality - purchases
            initTablePagination('purchases');
            
            // Table pagination functionality - customer debt
            initTablePagination('customerDebt');
            
            // Table pagination functionality - supplier debt
            initTablePagination('supplierDebt');
            
            // Initialize pagination function
            function initTablePagination(tablePrefix) {
                let currentPage = 1;
                const recordsPerPageSelect = $(`#${tablePrefix}RecordsPerPage`);
                let recordsPerPage = parseInt(recordsPerPageSelect.val());
                const tableRows = $(`#${tablePrefix}Table tbody tr`);
                const totalRecords = tableRows.length;
                
                // Update records per page when select changes
                recordsPerPageSelect.on('change', function() {
                    recordsPerPage = parseInt($(this).val());
                    currentPage = 1; // Reset to first page
                    updateTable();
                });
                
                // Update the table display based on current page and records per page
                function updateTable() {
                    const startIndex = (currentPage - 1) * recordsPerPage;
                    const endIndex = startIndex + recordsPerPage;
                    
                    // Hide all rows
                    tableRows.hide();
                    
                    // Show only rows for current page
                    tableRows.slice(startIndex, endIndex).show();
                    
                    // Update pagination info
                    $(`#${tablePrefix}StartRecord`).text(totalRecords > 0 ? startIndex + 1 : 0);
                    $(`#${tablePrefix}EndRecord`).text(Math.min(endIndex, totalRecords));
                    $(`#${tablePrefix}TotalRecords`).text(totalRecords);
                    
                    // Enable/disable pagination buttons
                    $(`#${tablePrefix}PrevPageBtn`).prop('disabled', currentPage === 1);
                    $(`#${tablePrefix}NextPageBtn`).prop('disabled', endIndex >= totalRecords);
                    
                    // Update pagination numbers
                    updatePaginationNumbers();
                }
                
                // Create pagination number buttons
                function updatePaginationNumbers() {
                    const totalPages = Math.ceil(totalRecords / recordsPerPage);
                    const paginationNumbersContainer = $(`#${tablePrefix}PaginationNumbers`);
                    
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
                }
                
                // Handle pagination button clicks
                $(document).on('click', `#${tablePrefix}PaginationNumbers .page-number`, function() {
                    currentPage = parseInt($(this).data('page'));
                    updateTable();
                });
                
                // Next page button
                $(`#${tablePrefix}NextPageBtn`).on('click', function() {
                    if (currentPage < Math.ceil(totalRecords / recordsPerPage)) {
                        currentPage++;
                        updateTable();
                    }
                });
                
                // Previous page button
                $(`#${tablePrefix}PrevPageBtn`).on('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        updateTable();
                    }
                });
                
                // Search functionality
                $(`#${tablePrefix}TableSearch`).on('keyup', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    
                    // If search term is empty, reset table
                    if (searchTerm === '') {
                        tableRows.show();
                        updateTable();
                        return;
                    }
                    
                    // Filter rows based on search term
                    tableRows.hide();
                    const filteredRows = tableRows.filter(function() {
                        const rowText = $(this).text().toLowerCase();
                        return rowText.includes(searchTerm);
                    });
                    
                    filteredRows.show();
                    
                    // Update pagination information
                    $(`#${tablePrefix}StartRecord`).text(filteredRows.length > 0 ? 1 : 0);
                    $(`#${tablePrefix}EndRecord`).text(filteredRows.length);
                    $(`#${tablePrefix}TotalRecords`).text(filteredRows.length);
                    
                    // Disable pagination when searching
                    $(`#${tablePrefix}PrevPageBtn, #${tablePrefix}NextPageBtn`).prop('disabled', true);
                    $(`#${tablePrefix}PaginationNumbers`).empty();
                });
                
                // Initialize table on page load
                updateTable();
            }

            // Handle customer payment form submission
            $('#saveCustomerPayment').on('click', function() {
                const formData = $('#customerPaymentForm').serialize();
                
                $.ajax({
                    url: '../../process/add_customer_payment.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            }).then(function() {
                                location.reload(); // Reload page to update data
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message,
                                icon: 'error',
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی داواکاری',
                            icon: 'error',
                            customClass: {
                                container: 'toast-container-rtl'
                            }
                        });
                    }
                });
                
                $('#paymentModal').modal('hide');
            });

            // Handle collect customer debt form submission
            $('#saveCustomerCollection').on('click', function() {
                const formData = $('#collectCustomerDebtForm').serialize();
                
                $.ajax({
                    url: '../../process/collect_customer_debt.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            }).then(function() {
                                location.reload(); // Reload page to update data
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message,
                                icon: 'error',
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی داواکاری',
                            icon: 'error',
                            customClass: {
                                container: 'toast-container-rtl'
                            }
                        });
                    }
                });
                
                $('#collectDebtModal').modal('hide');
            });

            // Handle supplier payment form submission
            $('#saveSupplierPayment').on('click', function() {
                const formData = $('#supplierPaymentForm').serialize();
                
                $.ajax({
                    url: '../../process/add_supplier_payment.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            }).then(function() {
                                location.reload(); // Reload page to update data
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message,
                                icon: 'error',
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی داواکاری',
                            icon: 'error',
                            customClass: {
                                container: 'toast-container-rtl'
                            }
                        });
                    }
                });
                
                $('#supplierPaymentModal').modal('hide');
            });

            // Handle collect supplier debt form submission
            $('#saveSupplierCollection').on('click', function() {
                const formData = $('#collectSupplierDebtForm').serialize();
                
                $.ajax({
                    url: '../../process/collect_supplier_debt.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            }).then(function() {
                                location.reload(); // Reload page to update data
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message,
                                icon: 'error',
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی داواکاری',
                            icon: 'error',
                            customClass: {
                                container: 'toast-container-rtl'
                            }
                        });
                    }
                });
                
                $('#collectSupplierDebtModal').modal('hide');
            });

            // Handle edit partner form submission
            $('#savePartnerChanges').on('click', function() {
                const formData = $('#editPartnerForm').serialize();
                
                $.ajax({
                    url: '../../process/update_business_partner.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            }).then(function() {
                                location.reload(); // Reload page to update data
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message,
                                icon: 'error',
                                customClass: {
                                    container: 'toast-container-rtl'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی داواکاری',
                            icon: 'error',
                            customClass: {
                                container: 'toast-container-rtl'
                            }
                        });
                    }
                });
                
                $('#editPartnerModal').modal('hide');
            });

            // Clear form fields when modals are closed
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
            });
        });
    </script>
</body>
</html> 