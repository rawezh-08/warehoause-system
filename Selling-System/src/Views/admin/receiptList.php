<?php
require_once '../../controllers/receiptController.php';

// Custom number formatting function for Iraqi Dinar
function numberFormat($number) {
    return number_format($number, 0, '.', ',') . ' د.ع';
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
    <link rel="stylesheet" href="../../css/receiptList.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

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
                                        <h5 class="card-title mb-0">ئەو پسوووڵانەی کە تۆ فرۆشتووتن</h5>
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
                                                            <td><?php echo numberFormat($sale['subtotal']); ?></td>
                                                            <td><?php echo numberFormat($sale['shipping_cost']); ?></td>
                                                            <td><?php echo numberFormat($sale['other_costs']); ?></td>
                                                            <td><?php echo numberFormat($sale['discount']); ?></td>
                                                            <td><?php echo numberFormat($sale['total_amount']); ?></td>
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
                                        <h5 class="card-title mb-0">ئەو پسووڵانە کە تۆ کڕیوتن</h5>
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
                                                            <th>کرێی گواستنەوە</th>
                                                            <th>خەرجی تر</th>
                                                            <th>داشکاندن</th>
                                                            <th>کۆی گشتی</th>
                                                            <th>بڕی پارەی دراو</th>
                                                            <th>بڕی پارەی ماوە</th>
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
                                                            <td><?php echo numberFormat($purchase['subtotal']); ?></td>
                                                            <td><?php echo numberFormat($purchase['shipping_cost']); ?></td>
                                                            <td><?php echo numberFormat($purchase['other_cost']); ?></td>
                                                            <td><?php echo numberFormat($purchase['discount']); ?></td>
                                                            <td><?php echo numberFormat($purchase['total_amount']); ?></td>
                                                            <td><?php echo numberFormat($purchase['paid_amount']); ?></td>
                                                            <td><?php echo numberFormat($purchase['remaining_amount']); ?></td>
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
                                                            <td colspan="15" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td>
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/receiptList/receipt.js"></script>
    
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    
    <script>
     
    </script>

    <style>
        .swal-html-rtl {
            direction: rtl !important;
            text-align: right !important;
        }
        
        /* Custom scrollbar for Webkit browsers */
        .swal2-html-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .swal2-html-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .swal2-html-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .swal2-html-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</body>
</html> 