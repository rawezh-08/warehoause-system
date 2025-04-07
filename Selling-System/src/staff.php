<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Customer.php';
require_once __DIR__ . '/models/Supplier.php';

// Create a database connection
$db = new Database();
// Ensure we get a valid PDO connection
$conn = $db->getConnection();

// Create Customer model instance
$customerModel = new Customer($conn);

// Create Supplier model instance
$supplierModel = new Supplier($conn);

// Get all customers
$customers = $customerModel->getAll();

// Get all suppliers
$suppliers = $supplierModel->getAll();

// You can add PHP logic here if needed
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی هاوکارەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/employeePayment/style.css">
    <link rel="stylesheet" href="css/staff.css">
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
                        <h3 class="page-title">لیستی هەژمارەکان</h3>
                    </div>
                </div>

                <!-- Tabs navigation -->
                <div class="row mb-4">
                    <div class="col-12">
                        <ul class="nav nav-tabs expenses-tabs" id="staffTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="employee-tab" data-bs-toggle="tab"
                                    data-bs-target="#employee-content" type="button" role="tab"
                                    aria-controls="employee-content" aria-selected="true">
                                    <i class="fas fa-user-tie me-2"></i>کارمەندەکان
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="customer-tab" data-bs-toggle="tab"
                                    data-bs-target="#customer-content" type="button" role="tab"
                                    aria-controls="customer-content" aria-selected="false">
                                    <i class="fas fa-user me-2"></i>کڕیارەکان
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="supplier-tab" data-bs-toggle="tab"
                                    data-bs-target="#supplier-content" type="button" role="tab"
                                    aria-controls="supplier-content" aria-selected="false">
                                    <i class="fas fa-truck me-2"></i>دابینکەرەکان
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tabs content -->
                <div class="tab-content" id="staffTabsContent">
                    <!-- Employees Tab -->
                    <div class="tab-pane fade show active" id="employee-content" role="tabpanel"
                        aria-labelledby="employee-tab">
                        <!-- Employees Filter -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی ناو</h5>
                                        <form id="employeeFilterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="employeeName" class="form-label">ناوی کارمەند</label>
                                                <input type="text" class="form-control auto-filter" id="employeeName">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="employeePosition" class="form-label">پۆست</label>
                                                <select class="form-select auto-filter" id="employeePosition">
                                                    <option value="">هەموو پۆستەکان</option>
                                                    <option value="فرۆشیار">فرۆشیار</option>
                                                    <option value="بەڕێوەبەر">بەڕێوەبەر</option>
                                                    <option value="ژمێریار">ژمێریار</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100"
                                                    id="employeeResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <a href="addStaff.php" class="btn btn-primary w-100">
                                                    <i class="fas fa-plus me-2"></i> زیادکردن
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div
                                        class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">لیستی کارمەندەکان</h5>
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
                                                                <select id="employeeRecordsPerPage"
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
                                                                <input type="text" id="employeeTableSearch"
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
                                                <table id="employeeTable"
                                                    class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ناوی کارمەند</th>
                                                            <th>ژمارەی مۆبایل</th>
                                                            <th>پۆست</th>
                                                            <th>مووچە</th>
                                                            <th>ناونیشان</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Sample data - will be replaced with real data from database -->
                                                        <tr data-id="1">
                                                            <td>1</td>
                                                            <td>ئاری محمد</td>
                                                            <td>0750 123 4567</td>
                                                            <td>فرۆشیار</td>
                                                            <td>$500</td>
                                                            <td>هەولێر، شەقامی 60 مەتری</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="1" data-bs-toggle="modal"
                                                                        data-bs-target="#editEmployeeModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-info rounded-circle view-btn"
                                                                        data-id="1">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-danger rounded-circle delete-btn"
                                                                        data-id="1">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr data-id="2">
                                                            <td>2</td>
                                                            <td>شیلان عمر</td>
                                                            <td>0750 876 5432</td>
                                                            <td>ژمێریار</td>
                                                            <td>$600</td>
                                                            <td>سلێمانی، شەقامی سالم</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="2" data-bs-toggle="modal"
                                                                        data-bs-target="#editEmployeeModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-info rounded-circle view-btn"
                                                                        data-id="2">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-danger rounded-circle delete-btn"
                                                                        data-id="2">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr data-id="3">
                                                            <td>3</td>
                                                            <td>هاوڕێ ئەحمەد</td>
                                                            <td>0750 555 7777</td>
                                                            <td>بەڕێوەبەر</td>
                                                            <td>$1200</td>
                                                            <td>هەولێر، شەقامی گوڵان</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="3" data-bs-toggle="modal"
                                                                        data-bs-target="#editEmployeeModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-info rounded-circle view-btn"
                                                                        data-id="3">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-danger rounded-circle delete-btn"
                                                                        data-id="3">
                                                                        <i class="fas fa-trash-alt"></i>
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
                                                            نیشاندانی <span id="employeeStartRecord">1</span> تا <span
                                                                id="employeeEndRecord">3</span> لە کۆی <span
                                                                id="employeeTotalRecords">3</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="employeePrevPageBtn"
                                                                class="btn btn-sm btn-outline-primary rounded-circle me-2"
                                                                disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="employeePaginationNumbers"
                                                                class="pagination-numbers d-flex">
                                                                <!-- Pagination numbers will be generated by JavaScript -->
                                                                <button
                                                                    class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="employeeNextPageBtn"
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

                    <!-- Customers Tab -->
                    <div class="tab-pane fade" id="customer-content" role="tabpanel" aria-labelledby="customer-tab">
                        <!-- Customers Filter -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی ناو</h5>
                                        <form id="customerFilterForm" class="row g-3">
                                            <div class="col-md-3">
                                                <label for="customerName" class="form-label">ناوی کڕیار</label>
                                                <input type="text" class="form-control auto-filter" id="customerName">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="customerPhone" class="form-label">ژمارەی مۆبایل</label>
                                                <input type="text" class="form-control auto-filter" id="customerPhone">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="customerAddress" class="form-label">ناونیشان</label>
                                                <input type="text" class="form-control auto-filter" id="customerAddress">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100" id="customerResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <a href="addStaff.php?tab=customer" class="btn btn-primary w-100">
                                                    <i class="fas fa-plus me-2"></i> زیادکردن
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div
                                        class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">لیستی کڕیارەکان</h5>
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
                                                                <select id="customerRecordsPerPage"
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
                                                                <input type="text" id="customerTableSearch"
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
                                                <table id="customerTable"
                                                    class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ناوی کڕیار</th>
                                                            <th>ژمارەی مۆبایل</th>
                                                            <th>ژمارەی مۆبایلی دووەم</th>
                                                            <th>ناوی کەفیل</th>
                                                            <th>ژمارەی مۆبایلی کەفیل</th>
                                                            <th>ناونیشان</th>
                                                            <th>قەرز بەسەر کڕیار</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($customers as $index => $customer): ?>
                                                        <tr data-id="<?php echo $customer['id']; ?>">
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($customer['phone1']); ?></td>
                                                            <td><?php echo htmlspecialchars($customer['phone2'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($customer['guarantor_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($customer['guarantor_phone'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($customer['address'] ?? ''); ?></td>
                                                            <td><?php echo number_format($customer['debit_on_business'], 0); ?></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="<?php echo $customer['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                  
                                                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-circle notes-btn" 
                                                                            data-notes="<?php echo htmlspecialchars($customer['notes'] ?? ''); ?>"
                                                                            data-customer-name="<?php echo htmlspecialchars($customer['name']); ?>">
                                                                        <i class="fas fa-sticky-note"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="<?php echo $customer['id']; ?>">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Table Pagination -->
                                            <div class="table-pagination mt-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6 mb-2 mb-md-0">
                                                        <div class="pagination-info">
                                                            نیشاندانی <span id="customerStartRecord">1</span> تا <span id="customerEndRecord"><?php echo count($customers); ?></span> لە کۆی <span id="customerTotalRecords"><?php echo count($customers); ?></span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="customerPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="customerPaginationNumbers" class="pagination-numbers d-flex">
                                                                <button class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="customerNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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

                    <!-- Suppliers Tab -->
                    <div class="tab-pane fade" id="supplier-content" role="tabpanel" aria-labelledby="supplier-tab">
                        <!-- Suppliers Filter -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی ناو</h5>
                                        <form id="supplierFilterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="supplierName" class="form-label">ناوی دابینکەر</label>
                                                <input type="text" class="form-control auto-filter" id="supplierName">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="supplierPhone" class="form-label">ژمارەی پەیوەندی</label>
                                                <input type="text" class="form-control auto-filter" id="supplierPhone">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100"
                                                    id="supplierResetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <a href="addStaff.php?tab=supplier" class="btn btn-primary w-100">
                                                    <i class="fas fa-plus me-2"></i> زیادکردن
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div
                                        class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">لیستی دابینکەرەکان</h5>
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
                                                                <select id="supplierRecordsPerPage"
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
                                                                <input type="text" id="supplierTableSearch"
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
                                                <table id="supplierTable"
                                                    class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ناوی دابینکەر</th>
                                                            <th>ژمارەی پەیوەندی</th>
                                                            <th>ژمارەی پەیوەندی ٢</th>
                                                            <th>قەرز لەسەر خۆم</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($suppliers)): ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">هیچ دابینکەرێک نەدۆزرایەوە</td>
                                                        </tr>
                                                        <?php else: ?>
                                                            <?php foreach ($suppliers as $index => $supplier): ?>
                                                            <tr data-id="<?php echo $supplier['id']; ?>">
                                                                <td><?php echo $index + 1; ?></td>
                                                                <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($supplier['phone1']); ?></td>
                                                                <td><?php echo htmlspecialchars($supplier['phone2'] ? $supplier['phone2'] : '-'); ?></td>
                                                                <td><?php echo number_format($supplier['debt_on_myself'], 0, '.', ','); ?> دینار</td>
                                                                <td>
                                                                    <div class="action-buttons">
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                            data-id="<?php echo $supplier['id']; ?>" data-bs-toggle="modal"
                                                                            data-bs-target="#editSupplierModal">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-info rounded-circle view-btn"
                                                                            data-id="<?php echo $supplier['id']; ?>">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-warning rounded-circle notes-btn"
                                                                            data-notes="<?php echo htmlspecialchars($supplier['notes'] ?? ''); ?>"
                                                                            data-supplier-name="<?php echo htmlspecialchars($supplier['name']); ?>">
                                                                            <i class="fas fa-sticky-note"></i>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-danger rounded-circle delete-btn"
                                                                            data-id="<?php echo $supplier['id']; ?>">
                                                                            <i class="fas fa-trash-alt"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Table Pagination -->
                                            <div class="table-pagination mt-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6 mb-2 mb-md-0">
                                                        <div class="pagination-info">
                                                            نیشاندانی <span id="supplierStartRecord">1</span> تا <span id="supplierEndRecord"><?php echo count($suppliers); ?></span> لە کۆی <span id="supplierTotalRecords"><?php echo count($suppliers); ?></span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="supplierPrevPageBtn"
                                                                class="btn btn-sm btn-outline-primary rounded-circle me-2"
                                                                disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="supplierPaginationNumbers"
                                                                class="pagination-numbers d-flex">
                                                                <button
                                                                    class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="supplierNextPageBtn"
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

    <!-- Employee Edit Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">دەستکاری زانیاری کارمەند</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editEmployeeForm">
                        <input type="hidden" id="editEmployeeId">
                        <div class="mb-3">
                            <label for="editEmployeeName" class="form-label">ناوی کارمەند</label>
                            <input type="text" class="form-control" id="editEmployeeName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePhone" class="form-label">ژمارەی مۆبایل</label>
                            <input type="tel" class="form-control" id="editEmployeePhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePosition" class="form-label">پۆست</label>
                            <input type="text" class="form-control" id="editEmployeePosition">
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeeSalary" class="form-label">مووچە</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editEmployeeSalary">
                                <span class="input-group-text">$</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeeAddress" class="form-label">ناونیشان</label>
                            <textarea class="form-control" id="editEmployeeAddress" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveEmployeeEdit">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Edit Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">دەستکاری زانیاری کڕیار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCustomerForm">
                        <input type="hidden" id="editCustomerId" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editCustomerName" class="form-label">ناوی کڕیار</label>
                                <input type="text" class="form-control" id="editCustomerName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCustomerPhone" class="form-label">ژمارەی مۆبایل</label>
                                <input type="tel" class="form-control" id="editCustomerPhone" name="phone1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editCustomerPhone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                                <input type="tel" class="form-control" id="editCustomerPhone2" name="phone2">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCustomerAddress" class="form-label">ناونیشان</label>
                                <input type="text" class="form-control" id="editCustomerAddress" name="address">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editGuarantorName" class="form-label">ناوی کەفیل</label>
                                <input type="text" class="form-control" id="editGuarantorName" name="guarantor_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editGuarantorPhone" class="form-label">ژمارەی مۆبایلی کەفیل</label>
                                <input type="tel" class="form-control" id="editGuarantorPhone" name="guarantor_phone">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editDebitOnBusiness" class="form-label">قەرز بەسەر کڕیار</label>
                                <input type="number" class="form-control" id="editDebitOnBusiness" name="debit_on_business" step="0.01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editCustomerNotes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="editCustomerNotes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveCustomerEdit">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Edit Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSupplierModalLabel">دەستکاری زانیاری دابینکەر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSupplierForm">
                        <input type="hidden" id="editSupplierId">
                        <div class="mb-3">
                            <label for="editSupplierName" class="form-label">ناوی دابینکەر</label>
                            <input type="text" class="form-control" id="editSupplierName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierPhone1" class="form-label">ژمارەی پەیوەندی</label>
                            <input type="tel" class="form-control" id="editSupplierPhone1" required pattern="[0-9]{4} [0-9]{3} [0-9]{4}">
                          
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierPhone2" class="form-label">ژمارەی پەیوەندی ٢</label>
                            <input type="tel" class="form-control" id="editSupplierPhone2" pattern="[0-9]{4} [0-9]{3} [0-9]{4}">
                           
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierDebt" class="form-label">قەرز لەسەر خۆم</label>
                            <div class="input-group">
                                <input type="text" class="form-control number-format" id="editSupplierDebt" value="0">
                                <span class="input-group-text">دینار</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierNotes" class="form-label">تێبینییەکان</label>
                            <textarea class="form-control" id="editSupplierNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveSupplierEdit">پاشەکەوتکردن</button>
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
    <script src="./js/include-components.js"></script>

</body>

</html>