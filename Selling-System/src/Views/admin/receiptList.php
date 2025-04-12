<?php
require_once '../../config/database.php';

// Function to get sales data with customer info
function getSalesData($limit = 0, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get sales with joined customer info and calculated total amount
    $sql = "SELECT 
                s.*, 
                c.name as customer_name,
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(p.name, ' (', si.quantity, ' ', 
                            CASE si.unit_type 
                                WHEN 'piece' THEN 'دانە'
                                WHEN 'box' THEN 'کارتۆن'
                                WHEN 'set' THEN 'سێت'
                            END,
                            ')'
                        ) SEPARATOR ', '
                    )
                    FROM sale_items si 
                    LEFT JOIN products p ON si.product_id = p.id 
                    WHERE si.sale_id = s.id
                ) as products_list,
                SUM(si.total_price) as subtotal,
                s.shipping_cost + s.other_costs as additional_costs,
                SUM(si.total_price) + s.shipping_cost + s.other_costs - s.discount as total_amount
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            LEFT JOIN sale_items si ON s.id = si.sale_id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(s.date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(s.date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['customer_name'])) {
        $sql .= " AND c.name LIKE :customer_name";
        $params[':customer_name'] = '%' . $filters['customer_name'] . '%';
    }
    if (!empty($filters['invoice_number'])) {
        $sql .= " AND s.invoice_number LIKE :invoice_number";
        $params[':invoice_number'] = '%' . $filters['invoice_number'] . '%';
    }
    
    $sql .= " GROUP BY s.id ORDER BY s.date DESC";
    
    // Only apply limit if it's greater than 0
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if (($key == ':offset' || $key == ':limit') && $limit > 0) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Function to get purchases data with supplier info
function getPurchasesData($limit = 0, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get purchases with joined supplier info and calculated total amount
    $sql = "SELECT 
                p.*, 
                s.name as supplier_name,
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(pr.name, ' (', pi.quantity, ' دانە)') 
                        SEPARATOR ', '
                    )
                    FROM purchase_items pi 
                    LEFT JOIN products pr ON pi.product_id = pr.id 
                    WHERE pi.purchase_id = p.id
                ) as products_list,
                SUM(pi.total_price) as subtotal,
                SUM(pi.total_price) - p.discount as total_amount
            FROM purchases p 
            LEFT JOIN suppliers s ON p.supplier_id = s.id 
            LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
            LEFT JOIN products pr ON pi.product_id = pr.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(p.date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(p.date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['supplier_name'])) {
        $sql .= " AND s.name LIKE :supplier_name";
        $params[':supplier_name'] = '%' . $filters['supplier_name'] . '%';
    }
    
    $sql .= " GROUP BY p.id ORDER BY p.date DESC";
    
    // Only apply limit if it's greater than 0
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if (($key == ':offset' || $key == ':limit') && $limit > 0) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Default filter values
$today = date('Y-m-d');
$startOfMonth = date('Y-m-01');

// Get initial data
$defaultFilters = [
    'start_date' => $startOfMonth,
    'end_date' => $today
];

// Get data with default filters - retrieve all records
$salesData = getSalesData(0, 0, $defaultFilters);
$purchasesData = getPurchasesData(0, 0, $defaultFilters);

// Handle AJAX filter requests
if (isset($_POST['action']) && $_POST['action'] == 'filter') {
    header('Content-Type: application/json');
    
    $filters = [
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? null
    ];
    
    if ($_POST['type'] == 'sales') {
        if (!empty($_POST['customer_name'])) {
            $filters['customer_name'] = $_POST['customer_name'];
        }
        if (!empty($_POST['invoice_number'])) {
            $filters['invoice_number'] = $_POST['invoice_number'];
        }
        $salesData = getSalesData(0, 0, $filters);
        echo json_encode(['success' => true, 'data' => $salesData]);
        exit;
    } else if ($_POST['type'] == 'purchases') {
        if (!empty($_POST['supplier_name'])) {
            $filters['supplier_name'] = $_POST['supplier_name'];
        }
        if (!empty($_POST['invoice_number'])) {
            $filters['invoice_number'] = $_POST['invoice_number'];
        }
        $purchasesData = getPurchasesData(0, 0, $filters);
        echo json_encode(['success' => true, 'data' => $purchasesData]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> پسووڵەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
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

        #employeeHistoryTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #shippingHistoryTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #withdrawalHistoryTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #employeeHistoryTable th {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #shippingHistoryTable th {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #withdrawalHistoryTable th {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Products list column style */
        .products-list-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            position: relative;
        }

        /* Products list popup style */
        .products-popup {
            display: none;
            position: absolute;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            z-index: 1000;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            max-width: 350px;
            min-width: 250px;
            white-space: normal;
            word-wrap: break-word;
            right: 0;
            left: auto;
            max-height: 250px;
            overflow-y: auto;
        }

        /* Product item style */
        .product-item {
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }
        
        .product-item:last-child {
            border-bottom: none;
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
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
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
                        <h3 class="page-title">پسووڵەکان</h3>
                    </div>
                </div>

                <!-- Tabs navigation -->
                <div class="row mb-4">
                    <div class="col-12">
                        <ul class="nav nav-tabs expenses-tabs" id="expensesTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="employee-payment-tab" data-bs-toggle="tab" data-bs-target="#employee-payment-content" type="button" role="tab" aria-controls="employee-payment-content" aria-selected="true">
                                    <i class="fas fa-user-tie me-2"></i> پسووڵەکانی فرۆشتن 
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping-content" type="button" role="tab" aria-controls="shipping-content" aria-selected="false">
                                    <i class="fas fa-truck me-2"></i> پسووڵەکانی کڕین
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="withdrawal-tab" data-bs-toggle="tab" data-bs-target="#withdrawal-content" type="button" role="tab" aria-controls="withdrawal-content" aria-selected="false">
                                    <i class="fas fa-money-bill-wave me-2"></i>  بەفیڕۆچوو
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tabs content -->
                <div class="tab-content" id="expensesTabsContent">
                    <!-- Employee Payment Tab -->
                    <div class="tab-pane fade show active" id="employee-payment-content" role="tabpanel" aria-labelledby="employee-payment-tab">
                        <!-- Date Filter for Employee Payments -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی بەروار و ناو</h5>
                                        <form id="employeePaymentFilterForm" class="row g-3">
                                            <div class="col-md-3">
                                                <label for="employeePaymentStartDate" class="form-label">بەرواری دەستپێک</label>
                                                <input type="date" class="form-control auto-filter" id="employeePaymentStartDate">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="employeePaymentEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="employeePaymentEndDate">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="employeePaymentName" class="form-label">ناوی کڕیار</label>
                                                <select class="form-select auto-filter" id="employeePaymentName">
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
                                            <div class="col-md-2">
                                                <label for="invoiceNumber" class="form-label">ژمارەی پسووڵە</label>
                                                <input type="text" class="form-control auto-filter" id="invoiceNumber" placeholder="ژمارەی پسووڵە">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100" id="employeePaymentResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">لیستی پسووڵە کڕدراوەکان</h5>
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
                                                                <select id="employeeRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                                <input type="text" id="employeeTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                                <table id="employeeHistoryTable" class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ژمارەی پسووڵە</th>
                                                            <th>ناوی کڕیار</th>
                                                            <th>بەروار</th>
                                                            <th>کاڵاکان</th>
                                                            <th>کۆی نرخی کاڵاکان</th>
                                                            <th>کرێی گواستنەوە</th>
                                                            <th>خەرجی تر</th>
                                                            <th>داشکاندن</th>
                                                            <th>کۆی گشتی</th>
                                                            <th>جۆری پارەدان</th>
                                                            <th>تێبینی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($salesData as $index => $sale): ?>
                                                        <tr data-id="<?php echo $sale['id']; ?>">
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                                            <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></td>
                                                            <td><?php echo date('Y/m/d', strtotime($sale['date'])); ?></td>
                                                            <td class="products-list-cell" data-products="<?php echo htmlspecialchars($sale['products_list'] ?? ''); ?>">
                                                                <?php echo htmlspecialchars($sale['products_list'] ?? ''); ?>
                                                                <div class="products-popup"></div>
                                                            </td>
                                                            <td><?php echo number_format($sale['subtotal']) . ' د.ع'; ?></td>
                                                            <td><?php echo number_format($sale['shipping_cost']) . ' د.ع'; ?></td>
                                                            <td><?php echo number_format($sale['other_costs']) . ' د.ع'; ?></td>
                                                            <td><?php echo number_format($sale['discount']) . ' د.ع'; ?></td>
                                                            <td><?php echo number_format($sale['total_amount']) . ' د.ع'; ?></td>
                                                            <td>
                                                                <span class="badge rounded-pill <?php 
                                                                    echo $sale['payment_type'] == 'cash' ? 'bg-success' : 
                                                                        ($sale['payment_type'] == 'credit' ? 'bg-warning' : 'bg-info'); 
                                                                ?>">
                                                                    <?php 
                                                                    echo $sale['payment_type'] == 'cash' ? 'نەقد' : 
                                                                        ($sale['payment_type'] == 'credit' ? 'قەرز' : 'چەک'); 
                                                                    ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($sale['notes'] ?? ''); ?></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="<?php echo $sale['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="<?php echo $sale['id']; ?>">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="<?php echo $sale['id']; ?>">
                                                                        <i class="fas fa-print"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($salesData)): ?>
                                                        <tr>
                                                            <td colspan="13" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td>
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
                                                            نیشاندانی <span id="employeeStartRecord">1</span> تا <span id="employeeEndRecord">3</span> لە کۆی <span id="employeeTotalRecords">3</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="employeePrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="employeePaginationNumbers" class="pagination-numbers d-flex">
                                                                <!-- Pagination numbers will be generated by JavaScript -->
                                                                <button class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="employeeNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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
                    
                    <!-- Shipping Cost Tab -->
                    <div class="tab-pane fade" id="shipping-content" role="tabpanel" aria-labelledby="shipping-tab">
                        <!-- Date Filter for Shipping Costs -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">فلتەر بەپێی بەروار و ناو</h5>
                                        <form id="shippingFilterForm" class="row g-3">
                                            <div class="col-md-3">
                                                <label for="shippingStartDate" class="form-label">بەرواری دەستپێک</label>
                                                <input type="date" class="form-control auto-filter" id="shippingStartDate">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="shippingEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="shippingEndDate">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="shippingProvider" class="form-label">دابینکەر</label>
                                                <select class="form-select auto-filter" id="shippingProvider">
                                                    <option value="">هەموو دابینکەرەکان</option>
                                                    <?php
                                                    // Get all suppliers from database
                                                    $stmt = $conn->query("SELECT DISTINCT name FROM suppliers ORDER BY name");
                                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="shippingInvoiceNumber" class="form-label">ژمارەی پسووڵە</label>
                                                <input type="text" class="form-control auto-filter" id="shippingInvoiceNumber" placeholder="ژمارەی پسووڵە">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100" id="shippingResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Cost History Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">مێژووی کرێی بار</h5>
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
                                                                <select id="shippingRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                                <input type="text" id="shippingTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                                <table id="shippingHistoryTable" class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ژمارەی پسووڵە</th>
                                                            <th>ناوی دابینکەر</th>
                                                            <th>بەروار</th>
                                                            <th>کاڵاکان</th>
                                                            <th>کۆی نرخی کاڵاکان</th>
                                                            <th>داشکاندن</th>
                                                            <th>کۆی گشتی</th>
                                                            <th>جۆری پارەدان</th>
                                                            <th>تێبینی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($purchasesData as $index => $purchase): ?>
                                                        <tr data-id="<?php echo $purchase['id']; ?>">
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                                                            <td><?php echo htmlspecialchars($purchase['supplier_name'] ?? 'N/A'); ?></td>
                                                            <td><?php echo date('Y/m/d', strtotime($purchase['date'])); ?></td>
                                                            <td class="products-list-cell" data-products="<?php echo htmlspecialchars($purchase['products_list'] ?? ''); ?>">
                                                                <?php echo htmlspecialchars($purchase['products_list'] ?? ''); ?>
                                                                <div class="products-popup"></div>
                                                            </td>
                                                            <td><?php echo number_format($purchase['subtotal']) . ' د.ع'; ?></td>
                                                            <td><?php echo number_format($purchase['discount']) . ' د.ع'; ?></td>
                                                            <td><?php echo number_format($purchase['total_amount']) . ' د.ع'; ?></td>
                                                            <td>
                                                                <span class="badge rounded-pill <?php 
                                                                    echo $purchase['payment_type'] == 'cash' ? 'bg-success' : 
                                                                        ($purchase['payment_type'] == 'credit' ? 'bg-warning' : 'bg-info'); 
                                                                ?>">
                                                                    <?php 
                                                                    echo $purchase['payment_type'] == 'cash' ? 'نەقد' : 
                                                                        ($purchase['payment_type'] == 'credit' ? 'قەرز' : 'چەک'); 
                                                                    ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($purchase['notes'] ?? ''); ?></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="<?php echo $purchase['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="<?php echo $purchase['id']; ?>">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="<?php echo $purchase['id']; ?>">
                                                                        <i class="fas fa-print"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($purchasesData)): ?>
                                                        <tr>
                                                            <td colspan="11" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td>
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
                                                            نیشاندانی <span id="shippingStartRecord">1</span> تا <span id="shippingEndRecord">2</span> لە کۆی <span id="shippingTotalRecords">2</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="shippingPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="shippingPaginationNumbers" class="pagination-numbers d-flex">
                                                                <!-- Pagination numbers will be generated by JavaScript -->
                                                                <button class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="shippingNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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
                                        <h5 class="card-title mb-4">فلتەر بەپێی بەروار و ناو</h5>
                                        <form id="withdrawalFilterForm" class="row g-3">
                                            <div class="col-md-3">
                                                <label for="withdrawalStartDate" class="form-label">بەرواری دەستپێک</label>
                                                <input type="date" class="form-control auto-filter" id="withdrawalStartDate">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="withdrawalEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="withdrawalEndDate">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="withdrawalName" class="form-label">ناو</label>
                                                <select class="form-select auto-filter" id="withdrawalName">
                                                    <option value="">هەموو ناوەکان</option>
                                                    <option value="کارزان عومەر">کارزان عومەر</option>
                                                    <option value="ئاکۆ سەعید">ئاکۆ سەعید</option>
                                                    <option value="هێڤیدار ئەحمەد">هێڤیدار ئەحمەد</option>
                                                    <option value="ئارام مستەفا">ئارام مستەفا</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100" id="withdrawalResetFilter">
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
                                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">مێژووی دەرکردنی پارە</h5>
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
                                                                <select id="withdrawalRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                                <input type="text" id="withdrawalTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                                <table id="withdrawalHistoryTable" class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ناو</th>
                                                            <th>بەروار</th>
                                                            <th>بڕی پارە</th>
                                                            <th>جۆری دەرکردن</th>
                                                            <th>تێبینی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Sample data - will be replaced with real data from database -->
                                                        <tr data-id="1">
                                                            <td>1</td>
                                                            <td>کارزان عومەر</td>
                                                            <td>2023/04/05</td>
                                                            <td>$150</td>
                                                            <td><span class="badge rounded-pill bg-warning text-dark">خەرجی ڕۆژانە</span></td>
                                                            <td>کڕینی پێداویستی بۆ ئۆفیس</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="1" data-bs-toggle="modal" data-bs-target="#editWithdrawalModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="1">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="1">
                                                                        <i class="fas fa-print"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr data-id="2">
                                                            <td>2</td>
                                                            <td>ئاکۆ سەعید</td>
                                                            <td>2023/04/10</td>
                                                            <td>$500</td>
                                                            <td><span class="badge rounded-pill bg-danger">کرێ</span></td>
                                                            <td>کرێی مانگانەی ئۆفیس</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="2" data-bs-toggle="modal" data-bs-target="#editWithdrawalModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="2">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="2">
                                                                        <i class="fas fa-print"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr data-id="3">
                                                            <td>3</td>
                                                            <td>هێڤیدار ئەحمەد</td>
                                                            <td>2023/04/12</td>
                                                            <td>$75</td>
                                                            <td><span class="badge rounded-pill bg-info">خزمەتگوزاری</span></td>
                                                            <td>پارەی کارەبا</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="3" data-bs-toggle="modal" data-bs-target="#editWithdrawalModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="3">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="3">
                                                                        <i class="fas fa-print"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Table Pagination -->
                                            <div class="table-pagination mt-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6 mb-2 mb-md-0">
                                                        <div class="pagination-info">
                                                            نیشاندانی <span id="withdrawalStartRecord">1</span> تا <span id="withdrawalEndRecord">3</span> لە کۆی <span id="withdrawalTotalRecords">3</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="withdrawalPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="withdrawalPaginationNumbers" class="pagination-numbers d-flex">
                                                                <!-- Pagination numbers will be generated by JavaScript -->
                                                                <button class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="withdrawalNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/receiptList/receipt.js"></script>
    
    <script>
        // Function to handle products list display
        $(document).ready(function() {
            // Products list popup functionality
            $('.products-list-cell').hover(
                function() {
                    const products = $(this).data('products');
                    if (products && products.trim() !== '') {
                        const $popup = $(this).find('.products-popup');
                        // Format products list for better readability
                        const productItems = products.split(', ').map(item => {
                            return `<div class="product-item">${item}</div>`;
                        }).join('');
                        
                        $popup.html(productItems);
                        $popup.show();
                    }
                },
                function() {
                    $(this).find('.products-popup').hide();
                }
            );
            
            // Click event to keep popup open
            $('.products-list-cell').click(function() {
                const products = $(this).data('products');
                if (products && products.trim() !== '') {
                    // Use SweetAlert2 for better display on click
                    Swal.fire({
                        title: 'کاڵاکان',
                        html: products.split(', ').map(item => {
                            return `<div style="text-align: right; padding: 5px 0; border-bottom: 1px solid #eee;">${item}</div>`;
                        }).join(''),
                        confirmButtonText: 'داخستن',
                        customClass: {
                            container: 'rtl-swal',
                            popup: 'rtl-swal-popup',
                            title: 'rtl-swal-title',
                            htmlContainer: 'rtl-swal-html',
                            confirmButton: 'rtl-swal-confirm'
                        }
                    });
                }
            });

            // Handle filter changes for sales
            $('.auto-filter').on('change input', function() {
                if ($('#employee-payment-content').hasClass('show')) {
                    filterSalesData();
                } else if ($('#shipping-content').hasClass('show')) {
                    filterPurchasesData();
                }
            });

            // Reset filter button for sales
            $('#employeePaymentResetFilter').click(function() {
                $('#employeePaymentStartDate').val('');
                $('#employeePaymentEndDate').val('');
                $('#employeePaymentName').val('');
                $('#invoiceNumber').val('');
                filterSalesData();
            });

            // Reset filter button for purchases
            $('#shippingResetFilter').click(function() {
                $('#shippingStartDate').val('');
                $('#shippingEndDate').val('');
                $('#shippingProvider').val('');
                $('#shippingInvoiceNumber').val('');
                filterPurchasesData();
            });

            // Records per page functionality for sales
            let currentSalesPage = 1;
            const salesRecordsPerPageSelect = $('#employeeRecordsPerPage');
            let salesRecordsPerPage = parseInt(salesRecordsPerPageSelect.val());
            
            // Update records per page when select changes for sales
            salesRecordsPerPageSelect.on('change', function() {
                salesRecordsPerPage = parseInt($(this).val());
                currentSalesPage = 1; // Reset to first page
                updateSalesDisplayedRows();
            });
            
            // Records per page functionality for purchases
            let currentPurchasesPage = 1;
            const purchasesRecordsPerPageSelect = $('#shippingRecordsPerPage');
            let purchasesRecordsPerPage = parseInt(purchasesRecordsPerPageSelect.val());
            
            // Update records per page when select changes for purchases
            purchasesRecordsPerPageSelect.on('change', function() {
                purchasesRecordsPerPage = parseInt($(this).val());
                currentPurchasesPage = 1; // Reset to first page
                updatePurchasesDisplayedRows();
            });
            
            // Records per page functionality for waste
            let currentWastePage = 1;
            const wasteRecordsPerPageSelect = $('#withdrawalRecordsPerPage');
            let wasteRecordsPerPage = parseInt(wasteRecordsPerPageSelect.val());
            
            // Update records per page when select changes for waste
            wasteRecordsPerPageSelect.on('change', function() {
                wasteRecordsPerPage = parseInt($(this).val());
                currentWastePage = 1; // Reset to first page
                updateWasteDisplayedRows();
            });
            
            // Pagination navigation for sales
            $('#employeePrevPageBtn').on('click', function() {
                if (!$(this).prop('disabled')) {
                    currentSalesPage--;
                    updateSalesDisplayedRows();
                }
            });
            
            $('#employeeNextPageBtn').on('click', function() {
                if (!$(this).prop('disabled')) {
                    currentSalesPage++;
                    updateSalesDisplayedRows();
                }
            });
            
            // Pagination navigation for purchases
            $('#shippingPrevPageBtn').on('click', function() {
                if (!$(this).prop('disabled')) {
                    currentPurchasesPage--;
                    updatePurchasesDisplayedRows();
                }
            });
            
            $('#shippingNextPageBtn').on('click', function() {
                if (!$(this).prop('disabled')) {
                    currentPurchasesPage++;
                    updatePurchasesDisplayedRows();
                }
            });
            
            // Pagination navigation for waste
            $('#withdrawalPrevPageBtn').on('click', function() {
                if (!$(this).prop('disabled')) {
                    currentWastePage--;
                    updateWasteDisplayedRows();
                }
            });
            
            $('#withdrawalNextPageBtn').on('click', function() {
                if (!$(this).prop('disabled')) {
                    currentWastePage++;
                    updateWasteDisplayedRows();
                }
            });
            
            // Function to update sales table displayed rows
            function updateSalesDisplayedRows() {
                const tableRows = $('#employeeHistoryTable tbody tr');
                const totalRecords = tableRows.length;
                
                if (totalRecords === 0) return;
                
                const startIndex = (currentSalesPage - 1) * salesRecordsPerPage;
                const endIndex = startIndex + salesRecordsPerPage;
                
                // Hide all rows
                tableRows.hide();
                
                // Show only rows for current page
                tableRows.slice(startIndex, endIndex).show();
                
                // Update pagination info
                $('#employeeStartRecord').text(totalRecords > 0 ? startIndex + 1 : 0);
                $('#employeeEndRecord').text(Math.min(endIndex, totalRecords));
                $('#employeeTotalRecords').text(totalRecords);
                
                // Enable/disable pagination buttons
                $('#employeePrevPageBtn').prop('disabled', currentSalesPage === 1);
                $('#employeeNextPageBtn').prop('disabled', endIndex >= totalRecords);
                
                // Update pagination numbers
                updateSalesPaginationNumbers();
            }
            
            // Function to update purchases table displayed rows
            function updatePurchasesDisplayedRows() {
                const tableRows = $('#shippingHistoryTable tbody tr');
                const totalRecords = tableRows.length;
                
                if (totalRecords === 0) return;
                
                const startIndex = (currentPurchasesPage - 1) * purchasesRecordsPerPage;
                const endIndex = startIndex + purchasesRecordsPerPage;
                
                // Hide all rows
                tableRows.hide();
                
                // Show only rows for current page
                tableRows.slice(startIndex, endIndex).show();
                
                // Update pagination info
                $('#shippingStartRecord').text(totalRecords > 0 ? startIndex + 1 : 0);
                $('#shippingEndRecord').text(Math.min(endIndex, totalRecords));
                $('#shippingTotalRecords').text(totalRecords);
                
                // Enable/disable pagination buttons
                $('#shippingPrevPageBtn').prop('disabled', currentPurchasesPage === 1);
                $('#shippingNextPageBtn').prop('disabled', endIndex >= totalRecords);
                
                // Update pagination numbers
                updatePurchasesPaginationNumbers();
            }
            
            // Function to update waste table displayed rows
            function updateWasteDisplayedRows() {
                const tableRows = $('#withdrawalHistoryTable tbody tr');
                const totalRecords = tableRows.length;
                
                if (totalRecords === 0) return;
                
                const startIndex = (currentWastePage - 1) * wasteRecordsPerPage;
                const endIndex = startIndex + wasteRecordsPerPage;
                
                // Hide all rows
                tableRows.hide();
                
                // Show only rows for current page
                tableRows.slice(startIndex, endIndex).show();
                
                // Update pagination info
                $('#withdrawalStartRecord').text(totalRecords > 0 ? startIndex + 1 : 0);
                $('#withdrawalEndRecord').text(Math.min(endIndex, totalRecords));
                $('#withdrawalTotalRecords').text(totalRecords);
                
                // Enable/disable pagination buttons
                $('#withdrawalPrevPageBtn').prop('disabled', currentWastePage === 1);
                $('#withdrawalNextPageBtn').prop('disabled', endIndex >= totalRecords);
                
                // Update pagination numbers
                updateWastePaginationNumbers();
            }
            
            // Function to update sales pagination number buttons
            function updateSalesPaginationNumbers() {
                const totalRecords = $('#employeeHistoryTable tbody tr').length;
                const totalPages = Math.ceil(totalRecords / salesRecordsPerPage);
                
                let paginationHTML = '';
                
                // Determine range of page numbers to show
                let startPage = Math.max(1, currentSalesPage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                
                // Ensure we always show 5 page numbers if possible
                if (endPage - startPage < 4 && totalPages > 4) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `<button class="btn btn-sm ${i === currentSalesPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" data-page="${i}">${i}</button>`;
                }
                
                $('#employeePaginationNumbers').html(paginationHTML);
                
                // Add click event for pagination numbers
                $('#employeePaginationNumbers button').on('click', function() {
                    currentSalesPage = parseInt($(this).data('page'));
                    updateSalesDisplayedRows();
                });
            }
            
            // Function to update purchases pagination number buttons
            function updatePurchasesPaginationNumbers() {
                const totalRecords = $('#shippingHistoryTable tbody tr').length;
                const totalPages = Math.ceil(totalRecords / purchasesRecordsPerPage);
                
                let paginationHTML = '';
                
                // Determine range of page numbers to show
                let startPage = Math.max(1, currentPurchasesPage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                
                // Ensure we always show 5 page numbers if possible
                if (endPage - startPage < 4 && totalPages > 4) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `<button class="btn btn-sm ${i === currentPurchasesPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" data-page="${i}">${i}</button>`;
                }
                
                $('#shippingPaginationNumbers').html(paginationHTML);
                
                // Add click event for pagination numbers
                $('#shippingPaginationNumbers button').on('click', function() {
                    currentPurchasesPage = parseInt($(this).data('page'));
                    updatePurchasesDisplayedRows();
                });
            }
            
            // Function to update waste pagination number buttons
            function updateWastePaginationNumbers() {
                const totalRecords = $('#withdrawalHistoryTable tbody tr').length;
                const totalPages = Math.ceil(totalRecords / wasteRecordsPerPage);
                
                let paginationHTML = '';
                
                // Determine range of page numbers to show
                let startPage = Math.max(1, currentWastePage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                
                // Ensure we always show 5 page numbers if possible
                if (endPage - startPage < 4 && totalPages > 4) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `<button class="btn btn-sm ${i === currentWastePage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" data-page="${i}">${i}</button>`;
                }
                
                $('#withdrawalPaginationNumbers').html(paginationHTML);
                
                // Add click event for pagination numbers
                $('#withdrawalPaginationNumbers button').on('click', function() {
                    currentWastePage = parseInt($(this).data('page'));
                    updateWasteDisplayedRows();
                });
            }

            // Table search functionality for sales
            $('#employeeTableSearch').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                $('#employeeHistoryTable tbody tr').each(function() {
                    const rowText = $(this).text().toLowerCase();
                    $(this).toggle(rowText.indexOf(searchText) > -1);
                });
                
                // Reset pagination after search
                currentSalesPage = 1;
                updateSalesDisplayedRows();
            });

            // Table search functionality for purchases
            $('#shippingTableSearch').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                $('#shippingHistoryTable tbody tr').each(function() {
                    const rowText = $(this).text().toLowerCase();
                    $(this).toggle(rowText.indexOf(searchText) > -1);
                });
                
                // Reset pagination after search
                currentPurchasesPage = 1;
                updatePurchasesDisplayedRows();
            });

            // Function to filter sales data
            function filterSalesData() {
                const filters = {
                    start_date: $('#employeePaymentStartDate').val(),
                    end_date: $('#employeePaymentEndDate').val(),
                    customer_name: $('#employeePaymentName').val(),
                    invoice_number: $('#invoiceNumber').val()
                };

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        action: 'filter',
                        type: 'sales',
                        ...filters
                    },
                    success: function(response) {
                        if (response.success) {
                            updateSalesTable(response.data);
                        }
                    }
                });
            }

            // Function to filter purchases data
            function filterPurchasesData() {
                const filters = {
                    start_date: $('#shippingStartDate').val(),
                    end_date: $('#shippingEndDate').val(),
                    supplier_name: $('#shippingProvider').val(),
                    invoice_number: $('#shippingInvoiceNumber').val()
                };

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        action: 'filter',
                        type: 'purchases',
                        ...filters
                    },
                    success: function(response) {
                        if (response.success) {
                            updatePurchasesTable(response.data);
                        }
                    }
                });
            }

            // Function to update sales table
            function updateSalesTable(data) {
                const tbody = $('#employeeHistoryTable tbody');
                tbody.empty();

                if (data.length === 0) {
                    tbody.append('<tr><td colspan="13" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
                    return;
                }

                data.forEach((sale, index) => {
                    const row = `
                        <tr data-id="${sale.id}">
                            <td>${index + 1}</td>
                            <td>${sale.invoice_number}</td>
                            <td>${sale.customer_name || 'N/A'}</td>
                            <td>${new Date(sale.date).toLocaleDateString('en-US')}</td>
                            <td class="products-list-cell" data-products="${sale.products_list || ''}">
                                ${sale.products_list || ''}
                                <div class="products-popup"></div>
                            </td>
                            <td>${sale.subtotal.toLocaleString()} د.ع</td>
                            <td>${sale.shipping_cost.toLocaleString()} د.ع</td>
                            <td>${sale.other_costs.toLocaleString()} د.ع</td>
                            <td>${sale.discount.toLocaleString()} د.ع</td>
                            <td>${sale.total_amount.toLocaleString()} د.ع</td>
                            <td>
                                <span class="badge rounded-pill ${sale.payment_type === 'cash' ? 'bg-success' : (sale.payment_type === 'credit' ? 'bg-warning' : 'bg-info')}">
                                    ${sale.payment_type === 'cash' ? 'نەقد' : (sale.payment_type === 'credit' ? 'قەرز' : 'چەک')}
                                </span>
                            </td>
                            <td>${sale.notes || ''}</td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${sale.id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${sale.id}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${sale.id}">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                
                // Initialize product popups for new rows
                initializeProductListPopups();
                
                // Reset pagination to first page when loading new data
                currentSalesPage = 1;
                updateSalesDisplayedRows();
            }

            // Function to update purchases table
            function updatePurchasesTable(data) {
                const tbody = $('#shippingHistoryTable tbody');
                tbody.empty();

                if (data.length === 0) {
                    tbody.append('<tr><td colspan="11" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
                    return;
                }

                data.forEach((purchase, index) => {
                    const row = `
                        <tr data-id="${purchase.id}">
                            <td>${index + 1}</td>
                            <td>${purchase.invoice_number}</td>
                            <td>${purchase.supplier_name || 'N/A'}</td>
                            <td>${new Date(purchase.date).toLocaleDateString('en-US')}</td>
                            <td class="products-list-cell" data-products="${purchase.products_list || ''}">
                                ${purchase.products_list || ''}
                                <div class="products-popup"></div>
                            </td>
                            <td>${purchase.subtotal.toLocaleString()} د.ع</td>
                            <td>${purchase.discount.toLocaleString()} د.ع</td>
                            <td>${purchase.total_amount.toLocaleString()} د.ع</td>
                            <td>
                                <span class="badge rounded-pill ${purchase.payment_type === 'cash' ? 'bg-success' : (purchase.payment_type === 'credit' ? 'bg-warning' : 'bg-info')}">
                                    ${purchase.payment_type === 'cash' ? 'نەقد' : (purchase.payment_type === 'credit' ? 'قەرز' : 'چەک')}
                                </span>
                            </td>
                            <td>${purchase.notes || ''}</td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${purchase.id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${purchase.id}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${purchase.id}">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                
                // Initialize product popups for new rows
                initializeProductListPopups();
                
                // Reset pagination to first page when loading new data
                currentPurchasesPage = 1;
                updatePurchasesDisplayedRows();
            }

            // Initialize date inputs with current month
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            $('#employeePaymentStartDate').val(firstDay.toISOString().split('T')[0]);
            $('#employeePaymentEndDate').val(today.toISOString().split('T')[0]);
            $('#shippingStartDate').val(firstDay.toISOString().split('T')[0]);
            $('#shippingEndDate').val(today.toISOString().split('T')[0]);

            // Load initial data
            filterSalesData();
            filterPurchasesData();

            // Initialize table pagination on page load
            updateSalesDisplayedRows();
            updatePurchasesDisplayedRows();
            updateWasteDisplayedRows();
        });

        // Handle edit button click for sales
        $(document).on('click', '#employeeHistoryTable .edit-btn', function() {
            const saleId = $(this).data('id');
            // Get sale data from the row
            const row = $(this).closest('tr');
            const saleData = {
                id: saleId,
                invoice_number: row.find('td:eq(1)').text(),
                customer_name: row.find('td:eq(2)').text(),
                date: row.find('td:eq(3)').text(),
                shipping_cost: parseFloat(row.find('td:eq(6)').text().replace(/[^0-9.-]+/g, '')),
                other_costs: parseFloat(row.find('td:eq(7)').text().replace(/[^0-9.-]+/g, '')),
                discount: parseFloat(row.find('td:eq(8)').text().replace(/[^0-9.-]+/g, '')),
                payment_type: row.find('td:eq(10) .badge').text().trim(),
                notes: row.find('td:eq(11)').text()
            };

            // Fill the form with sale data
            $('#editSaleId').val(saleData.id);
            $('#editSaleInvoiceNumber').val(saleData.invoice_number);
            
            // Set customer selection
            const customerSelect = $('#editSaleCustomer');
            customerSelect.find('option').each(function() {
                if ($(this).text().trim() === saleData.customer_name.trim()) {
                    customerSelect.val($(this).val());
                    return false;
                }
            });
            
            // Set payment type
            const paymentTypeMap = {
                'نەقد': 'cash',
                'قەرز': 'credit',
                'چەک': 'check'
            };
            const paymentTypeValue = paymentTypeMap[saleData.payment_type];
            if (paymentTypeValue) {
                $('#editSalePaymentType').val(paymentTypeValue);
            }
            
            $('#editSaleDate').val(new Date(saleData.date).toISOString().split('T')[0]);
            $('#editSaleShippingCost').val(saleData.shipping_cost);
            $('#editSaleOtherCosts').val(saleData.other_costs);
            $('#editSaleDiscount').val(saleData.discount);
            $('#editSaleNotes').val(saleData.notes);

            // Show the modal
            $('#editSaleModal').modal('show');
        });

        // Handle edit button click for purchases
        $(document).on('click', '#shippingHistoryTable .edit-btn', function() {
            const purchaseId = $(this).data('id');
            // Get purchase data from the row
            const row = $(this).closest('tr');
            const purchaseData = {
                id: purchaseId,
                invoice_number: row.find('td:eq(1)').text(),
                supplier_name: row.find('td:eq(2)').text(),
                date: row.find('td:eq(3)').text(),
                discount: parseFloat(row.find('td:eq(6)').text().replace(/[^0-9.-]+/g, '')),
                payment_type: row.find('td:eq(8) .badge').text().trim(),
                notes: row.find('td:eq(9)').text()
            };

            // Fill the form with purchase data
            $('#editPurchaseId').val(purchaseData.id);
            $('#editPurchaseInvoiceNumber').val(purchaseData.invoice_number);
            $('#editPurchaseSupplier').val(purchaseData.supplier_name);
            $('#editPurchaseDate').val(new Date(purchaseData.date).toISOString().split('T')[0]);
            $('#editPurchaseDiscount').val(purchaseData.discount);
            $('#editPurchasePaymentType').val(purchaseData.payment_type);
            $('#editPurchaseNotes').val(purchaseData.notes);

            // Show the modal
            $('#editPurchaseModal').modal('show');
        });

        // Handle save sale edit
        $('#saveSaleEdit').click(function() {
            const saleData = {
                id: $('#editSaleId').val(),
                invoice_number: $('#editSaleInvoiceNumber').val(),
                customer_id: $('#editSaleCustomer').val(),
                date: $('#editSaleDate').val(),
                shipping_cost: $('#editSaleShippingCost').val(),
                other_costs: $('#editSaleOtherCosts').val(),
                discount: $('#editSaleDiscount').val(),
                payment_type: $('#editSalePaymentType').val(),
                notes: $('#editSaleNotes').val()
            };

            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    action: 'update_sale',
                    ...saleData
                },
                success: function(response) {
                    if (response.success) {
                        $('#editSaleModal').modal('hide');
                        // Refresh the table without using DataTable
                        filterSalesData();
                        Swal.fire({
                            title: 'سەرکەوتوو',
                            text: 'پسووڵە بە سەرکەوتوویی نوێکرایەوە',
                            icon: 'success',
                            confirmButtonText: 'باشە'
                        });
                    } else {
                        Swal.fire({
                            title: 'هەڵە',
                            text: response.message || 'هەڵەیەک ڕوویدا',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                }
            });
        });

        // Handle save purchase edit
        $('#savePurchaseEdit').click(function() {
            const purchaseData = {
                id: $('#editPurchaseId').val(),
                invoice_number: $('#editPurchaseInvoiceNumber').val(),
                supplier_id: $('#editPurchaseSupplier').val(),
                date: $('#editPurchaseDate').val(),
                discount: $('#editPurchaseDiscount').val(),
                payment_type: $('#editPurchasePaymentType').val(),
                notes: $('#editPurchaseNotes').val()
            };

            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    action: 'update_purchase',
                    ...purchaseData
                },
                success: function(response) {
                    if (response.success) {
                        $('#editPurchaseModal').modal('hide');
                        filterPurchasesData();
                        Swal.fire({
                            title: 'سەرکەوتوو',
                            text: 'پسووڵە بە سەرکەوتوویی نوێکرایەوە',
                            icon: 'success',
                            confirmButtonText: 'باشە'
                        });
                    } else {
                        Swal.fire({
                            title: 'هەڵە',
                            text: response.message || 'هەڵەیەک ڕوویدا',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                }
            });
        });
    </script>

    <!-- Employee Payment Edit Modal -->
    <div class="modal fade" id="editEmployeePaymentModal" tabindex="-1" aria-labelledby="editEmployeePaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeePaymentModalLabel">دەستکاری پارەدان بە کارمەند</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editEmployeePaymentForm">
                        <input type="hidden" id="editEmployeePaymentId">
                        <div class="mb-3">
                            <label for="editEmployeePaymentName" class="form-label">ناوی کارمەند</label>
                            <select id="editEmployeePaymentName" class="form-select" required>
                                <option value="" selected disabled>کارمەند هەڵبژێرە</option>
                                <option value="ئاری محمد">ئاری محمد</option>
                                <option value="شیلان عمر">شیلان عمر</option>
                                <option value="هاوڕێ ئەحمەد">هاوڕێ ئەحمەد</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePaymentDate" class="form-label">بەروار</label>
                            <input type="date" id="editEmployeePaymentDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePaymentAmount" class="form-label">بڕی پارە</label>
                            <div class="input-group">
                                <input type="number" id="editEmployeePaymentAmount" class="form-control" required>
                                <span class="input-group-text">$</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePaymentType" class="form-label">جۆری پارەدان</label>
                            <select id="editEmployeePaymentType" class="form-select" required>
                                <option value="" selected disabled>جۆری پارەدان</option>
                                <option value="salary">مووچە</option>
                                <option value="bonus">پاداشت</option>
                                <option value="advance">پێشەکی</option>
                                <option value="other">جۆری تر</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePaymentNotes" class="form-label">تێبینی</label>
                            <textarea id="editEmployeePaymentNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveEmployeePaymentEdit">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping Cost Edit Modal -->
    <div class="modal fade" id="editShippingModal" tabindex="-1" aria-labelledby="editShippingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editShippingModalLabel">دەستکاری کرێی بار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editShippingForm">
                        <input type="hidden" id="editShippingId">
                        <div class="mb-3">
                            <label for="editShippingProvider" class="form-label">دابینکەر</label>
                            <input type="text" id="editShippingProvider" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editShippingDate" class="form-label">بەروار</label>
                            <input type="date" id="editShippingDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editShippingAmount" class="form-label">بڕی پارە</label>
                            <div class="input-group">
                                <input type="number" id="editShippingAmount" class="form-control" required>
                                <span class="input-group-text">$</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editShippingType" class="form-label">جۆری بار</label>
                            <select id="editShippingType" class="form-select" required>
                                <option value="" selected disabled>جۆری بار</option>
                                <option value="land">وشکانی</option>
                                <option value="sea">دەریایی</option>
                                <option value="air">ئاسمانی</option>
                                <option value="other">جۆری تر</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editShippingNotes" class="form-label">تێبینی</label>
                            <textarea id="editShippingNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveShippingEdit">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Edit Modal -->
    <div class="modal fade" id="editWithdrawalModal" tabindex="-1" aria-labelledby="editWithdrawalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editWithdrawalModalLabel">دەستکاری دەرکردنی پارە</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editWithdrawalForm">
                        <input type="hidden" id="editWithdrawalId">
                        <div class="mb-3">
                            <label for="editWithdrawalName" class="form-label">ناو</label>
                            <input type="text" id="editWithdrawalName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editWithdrawalDate" class="form-label">بەروار</label>
                            <input type="date" id="editWithdrawalDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editWithdrawalAmount" class="form-label">بڕی پارە</label>
                            <div class="input-group">
                                <input type="number" id="editWithdrawalAmount" class="form-control" required>
                                <span class="input-group-text">$</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editWithdrawalCategory" class="form-label">جۆری دەرکردن</label>
                            <select id="editWithdrawalCategory" class="form-select" required>
                                <option value="" selected disabled>جۆر هەڵبژێرە</option>
                                <option value="expense">خەرجی ڕۆژانە</option>
                                <option value="supplies">پێداویستی</option>
                                <option value="rent">کرێ</option>
                                <option value="utility">خزمەتگوزاری</option>
                                <option value="other">جۆری تر</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editWithdrawalNotes" class="form-label">تێبینی</label>
                            <textarea id="editWithdrawalNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveWithdrawalEdit">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Sale Modal -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSaleModalLabel">دەستکاری پسووڵەی فرۆشتن</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSaleForm">
                        <input type="hidden" id="editSaleId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editSaleInvoiceNumber" class="form-label">ژمارەی پسووڵە</label>
                                <input type="text" class="form-control" id="editSaleInvoiceNumber" required>
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

    <!-- Edit Purchase Modal -->
    <div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-labelledby="editPurchaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPurchaseModalLabel">دەستکاری پسووڵەی کڕین</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                    <option value="check">چەک</option>
                                </select>
                            </div>
                            <div class="col-md-6">
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
</body>
</html> 