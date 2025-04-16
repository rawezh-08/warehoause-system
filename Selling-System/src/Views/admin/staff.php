<?php
require_once '../../config/database.php';
require_once '../../models/Customer.php';
require_once '../../models/Supplier.php';

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
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/staff.css">
    <link rel="stylesheet" href="../../css/input.css">
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
                                <div class="card shadow-sm card-qiuck-style">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی ناو</h5>
                                        <form id="employeeFilterForm" class="row g-3">
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                        <select class="form-select" id="employeeNameFilter">
                                                            <option value="">هەموو کارمەندان</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                        <input type="text" class="form-control" id="employeePhoneSearch" placeholder="گەڕان بە پێی ژمارەی مۆبایل...">
                                                    </div>
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
                                            </div>
                                       
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm card-qiuck-style">
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
                                                                    <img src="../../assets/icons/search-purple.svg" alt=""> </span>
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
                                                            <th class="tbl-header">#</th>
                                                            <th class="tbl-header">ناوی کارمەند</th>
                                                            <th class="tbl-header">ژمارەی مۆبایل</th>
                                                            <th class="tbl-header">مووچە</th>
                                                           
                                                            <th class="tbl-header">کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Sample data - will be replaced with real data from database -->
                                                        
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
                                <div class="card shadow-sm card-qiuck-style">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی ناو</h5>
                                        <form id="customerFilterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="customerName" class="form-label">ناوی کڕیار</label>
                                                <select class="form-select" id="customerName">
                                                    <option value="">هەموو کڕیارەکان</option>
                                                    <?php foreach ($customers as $customer): ?>
                                                        <option value="<?php echo htmlspecialchars($customer['name']); ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="customerPhone" class="form-label">ژمارەی مۆبایل</label>
                                                <input type="text" class="form-control auto-filter" id="customerPhone">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-secondary w-100"
                                                    id="customerResetFilter">
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
                                <div class="card shadow-sm card-qiuck-style">
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
                                                                    <img src="../../assets/icons/search-purple.svg" alt=""> </span>

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
                                                                <td><?php echo htmlspecialchars($customer['name'] ?? '-'); ?></td>
                                                                <td><?php echo htmlspecialchars($customer['phone1'] ?? '-'); ?></td>
                                                                <td><?php echo htmlspecialchars($customer['phone2'] ? $customer['phone2'] : '-'); ?></td>
                                                                <td><?php echo htmlspecialchars($customer['guarantor_name'] ? $customer['guarantor_name'] : '-'); ?></td>
                                                                <td><?php echo htmlspecialchars($customer['guarantor_phone'] ? $customer['guarantor_phone'] : '-'); ?></td>
                                                                <td><?php echo htmlspecialchars($customer['address'] ? $customer['address'] : '-'); ?></td>
                                                                <td><?php echo $customer['debit_on_business'] ? number_format($customer['debit_on_business'], 0) : '-'; ?></td>
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
                                <div class="card shadow-sm card-qiuck-style">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی ناو</h5>
                                        <form id="supplierFilterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="supplierName" class="form-label">ناوی دابینکەر</label>
                                                <select class="form-select" id="supplierName">
                                                    <option value="">هەموو دابینکەرەکان</option>
                                                    <?php foreach ($suppliers as $supplier): ?>
                                                        <option value="<?php echo htmlspecialchars($supplier['name']); ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
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
                                <div class="card shadow-sm card-qiuck-style">
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
                                                                    <img src="../../assets/icons/search-purple.svg" alt=""> </span>
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
                                                                    <td><?php echo htmlspecialchars($supplier['name'] ?? '-'); ?></td>
                                                                    <td><?php echo htmlspecialchars($supplier['phone1'] ?? '-'); ?></td>
                                                                    <td><?php echo htmlspecialchars($supplier['phone2'] ? $supplier['phone2'] : '-'); ?></td>
                                                                    <td><?php echo $supplier['debt_on_myself'] ? number_format($supplier['debt_on_myself'], 0, '.', ',') . ' دینار' : '-'; ?></td>
                                                                    <td>
                                                                        <div class="action-buttons">
                                                                            <button type="button"
                                                                                class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                                data-id="<?php echo $supplier['id']; ?>">
                                                                                <i class="fas fa-edit"></i>
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
        <div class="modal-dialog">
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
                            <input type="text" class="form-control" id="editEmployeePhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeeSalary" class="form-label">مووچە</label>
                            <input type="text" class="form-control" id="editEmployeeSalary" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeeNotes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="editEmployeeNotes" rows="3"></textarea>
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
    <script src="../../js/include-components.js"></script>

    <script>
        // Function to fetch and display employees
        function fetchEmployees() {
            // Show loading state
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زانیاری کارمەندان بار دەکرێت',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Get filter values
            const nameFilter = document.getElementById('employeeNameFilter').value;
            const phoneSearch = document.getElementById('employeePhoneSearch').value;

            // Build query parameters
            const params = new URLSearchParams();
            if (nameFilter) params.append('name', nameFilter);
            if (phoneSearch) params.append('phone', phoneSearch);

            // Fetch employees from server
            fetch(`../../process/get_employees.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide loading
                        Swal.close();
                        
                        // Get table body
                        const tbody = document.querySelector('#employeeTable tbody');
                        if (!tbody) return;
                        
                        // Clear existing rows
                        tbody.innerHTML = '';
                        
                        // Update name filter options
                        const nameFilter = document.getElementById('employeeNameFilter');
                        nameFilter.innerHTML = '<option value="">هەموو کارمەندان</option>';
                        
                        // Add each employee to table and name filter
                        data.employees.forEach((employee, index) => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${index + 1}</td>
                                <td>${employee.name || '-'}</td>
                                <td>${employee.phone || '-'}</td>
                                <td>${employee.salary ? formatNumberWithCommas(employee.salary) : '-'}</td>
                              
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${employee.id}" data-bs-toggle="modal" data-bs-target="#editEmployeeModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle notes-btn" 
                                            data-notes="${employee.notes || ''}" data-employee-name="${employee.name || ''}">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${employee.id}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(row);
                            
                            // Add to name filter
                            const option = document.createElement('option');
                            option.value = employee.name;
                            option.textContent = employee.name;
                            nameFilter.appendChild(option);
                        });
                        
                        // Add event listeners for edit and delete buttons
                        addEmployeeActionListeners();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: data.message || 'هەڵەیەک ڕوویدا لە کاتی گەڕانەوەی زانیاری کارمەندان',
                            confirmButtonText: 'باشە'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                        confirmButtonText: 'باشە'
                    });
                });
        }

        // Function to add event listeners for employee actions
        function addEmployeeActionListeners() {
            // Edit buttons
            document.querySelectorAll('#employeeTable .edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = this.dataset.id;
                    // Get employee data
                    fetch(`../../process/get_employee.php?id=${employeeId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Fill the edit form with employee data
                                document.getElementById('editEmployeeId').value = data.employee.id;
                                document.getElementById('editEmployeeName').value = data.employee.name;
                                document.getElementById('editEmployeePhone').value = data.employee.phone;
                                document.getElementById('editEmployeeSalary').value = data.employee.salary;
                                document.getElementById('editEmployeeNotes').value = data.employee.notes;
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: data.message || 'هەڵەیەک ڕوویدا لە کاتی گەڕانەوەی زانیاری کارمەند',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                                confirmButtonText: 'باشە'
                            });
                        });
                });
            });
            
            // Delete buttons - ONLY for employee table
            document.querySelectorAll('#employeeTable .delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = this.dataset.id;
                    deleteEmployee(employeeId);
                });
            });
        }

        // Function to delete an employee
        function deleteEmployee(employeeId) {
            Swal.fire({
                title: 'دڵنیای لە سڕینەوەی ئەم کارمەندە؟',
                text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەڵێ، بسڕەوە',
                cancelButtonText: 'نەخێر'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'تکایە چاوەڕێ بکە...',
                        text: 'سڕینەوەی کارمەند بەردەوامە',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send delete request
                    fetch('../../process/delete_employee.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: employeeId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: data.message,
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                // Refresh employee list
                                fetchEmployees();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: data.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                            confirmButtonText: 'باشە'
                        });
                    });
                }
            });
        }

        // Function to format number with commas
        function formatNumberWithCommas(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Function to save employee edit
        function saveEmployeeEdit() {
            const employeeId = document.getElementById('editEmployeeId').value;
            const name = document.getElementById('editEmployeeName').value;
            const phone = document.getElementById('editEmployeePhone').value;
            const salary = document.getElementById('editEmployeeSalary').value;
            const notes = document.getElementById('editEmployeeNotes').value;

            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زانیاری کارمەند تازە دەکرێتەوە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send update request
            fetch('../../process/update_employee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: employeeId,
                    name: name,
                    phone: phone,
                    salary: salary,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو!',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editEmployeeModal'));
                        modal.hide();
                        
                        // Refresh employee list
                        fetchEmployees();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    confirmButtonText: 'باشە'
                });
            });
        }

        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Add notes button click handlers for employees
            document.addEventListener('click', function(e) {
                if (e.target && e.target.closest('#employeeTable .notes-btn')) {
                    const button = e.target.closest('.notes-btn');
                    const notes = button.getAttribute('data-notes');
                    const employeeName = button.getAttribute('data-employee-name');
                    
                    Swal.fire({
                        title: `تێبینیەکانی ${employeeName}`,
                        text: notes || 'هیچ تێبینیەک نییە',
                        icon: 'info',
                        confirmButtonText: 'داخستن'
                    });
                }
            });

            // Add notes button click handlers for customers
            document.querySelectorAll('#customerTable .notes-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const notes = this.getAttribute('data-notes');
                    const customerName = this.getAttribute('data-customer-name');
                    
                    Swal.fire({
                        title: `تێبینیەکانی ${customerName}`,
                        text: notes || 'هیچ تێبینیەک نییە',
                        icon: 'info',
                        confirmButtonText: 'داخستن'
                    });
                });
            });

            // Add notes button click handlers for suppliers
            document.querySelectorAll('#supplierTable .notes-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const notes = this.getAttribute('data-notes');
                    const supplierName = this.getAttribute('data-supplier-name');
                    
                    Swal.fire({
                        title: `تێبینیەکانی ${supplierName}`,
                        text: notes || 'هیچ تێبینیەک نییە',
                        icon: 'info',
                        confirmButtonText: 'داخستن'
                    });
                });
            });

            // Fetch employees when page loads
            fetchEmployees();

            // Add refresh button click handler
            document.querySelector('.refresh-btn').addEventListener('click', function() {
                fetchEmployees();
            });

            // Add save button click handler
            document.getElementById('saveEmployeeEdit').addEventListener('click', saveEmployeeEdit);

            // Add filter change handlers
            document.getElementById('employeeNameFilter').addEventListener('change', fetchEmployees);
            document.getElementById('employeePhoneSearch').addEventListener('input', function() {
                // Add debounce to prevent too many requests
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    fetchEmployees();
                }, 500);
            });

            // Add customer edit button click handlers
            document.querySelectorAll('#customerTable .edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-id');
                    // Show loading
                    Swal.fire({
                        title: 'تکایە چاوەڕێ بکە...',
                        text: 'زانیاری کڕیار بار دەکرێت',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Fetch customer data
                    fetch(`../../process/get_customer.php?id=${customerId}`)
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                // Fill the edit form with customer data
                                document.getElementById('editCustomerId').value = data.customer.id;
                                document.getElementById('editCustomerName').value = data.customer.name;
                                document.getElementById('editCustomerPhone').value = data.customer.phone1;
                                document.getElementById('editCustomerPhone2').value = data.customer.phone2 || '';
                                document.getElementById('editCustomerAddress').value = data.customer.address || '';
                                document.getElementById('editGuarantorName').value = data.customer.guarantor_name || '';
                                document.getElementById('editGuarantorPhone').value = data.customer.guarantor_phone || '';
                                document.getElementById('editDebitOnBusiness').value = data.customer.debit_on_business || 0;
                                document.getElementById('editCustomerNotes').value = data.customer.notes || '';
                                
                                // Open the modal
                                const editCustomerModal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
                                editCustomerModal.show();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: data.message || 'کڕیار نەدۆزرایەوە',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                                confirmButtonText: 'باشە'
                            });
                        });
                });
            });

            // Add save customer edit button click handler
            document.getElementById('saveCustomerEdit').addEventListener('click', function() {
                // Get form data
                const customerId = document.getElementById('editCustomerId').value;
                const formData = {
                    id: customerId,
                    name: document.getElementById('editCustomerName').value,
                    phone1: document.getElementById('editCustomerPhone').value,
                    phone2: document.getElementById('editCustomerPhone2').value,
                    address: document.getElementById('editCustomerAddress').value,
                    guarantor_name: document.getElementById('editGuarantorName').value,
                    guarantor_phone: document.getElementById('editGuarantorPhone').value,
                    debit_on_business: document.getElementById('editDebitOnBusiness').value,
                    notes: document.getElementById('editCustomerNotes').value
                };

                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'زانیاری کڕیار نوێ دەکرێتەوە',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send update request
                fetch('../../process/update_customer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the row in the table directly
                        const row = document.querySelector(`#customerTable tr[data-id="${customerId}"]`);
                        if (row) {
                            row.cells[1].textContent = formData.name;
                            row.cells[2].textContent = formData.phone1;
                            row.cells[3].textContent = formData.phone2 || '-';
                            row.cells[4].textContent = formData.guarantor_name || '-';
                            row.cells[5].textContent = formData.guarantor_phone || '-';
                            row.cells[6].textContent = formData.address || '-';
                            row.cells[7].textContent = formData.debit_on_business ? Number(formData.debit_on_business).toLocaleString() : '-';
                        }

                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'));
                        modal.hide();

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو بوو!',
                            text: data.message || 'زانیاری کڕیار بە سەرکەوتوویی نوێ کرایەوە',
                            confirmButtonText: 'باشە'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: data.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاری کڕیار',
                            confirmButtonText: 'باشە'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                        confirmButtonText: 'باشە'
                    });
                });
            });

            // Add save supplier edit button click handler
            document.getElementById('saveSupplierEdit').addEventListener('click', function() {
                // Get form data
                const supplierId = document.getElementById('editSupplierId').value;
                const formData = {
                    id: supplierId,
                    name: document.getElementById('editSupplierName').value,
                    phone1: document.getElementById('editSupplierPhone1').value,
                    phone2: document.getElementById('editSupplierPhone2').value,
                    debt_on_myself: document.getElementById('editSupplierDebt').value.replace(/,/g, ''),
                    notes: document.getElementById('editSupplierNotes').value
                };

                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'زانیاری دابینکەر نوێ دەکرێتەوە',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send update request
                fetch('../../process/update_supplier.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the row in the table directly
                        const row = document.querySelector(`#supplierTable tr[data-id="${supplierId}"]`);
                        if (row) {
                            row.cells[1].textContent = formData.name;
                            row.cells[2].textContent = formData.phone1;
                            row.cells[3].textContent = formData.phone2 || '-';
                            row.cells[4].textContent = formData.debt_on_myself ? Number(formData.debt_on_myself).toLocaleString() + ' دینار' : '-';
                        }

                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editSupplierModal'));
                        modal.hide();

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو بوو!',
                            text: data.message || 'زانیاری دابینکەر بە سەرکەوتوویی نوێ کرایەوە',
                            confirmButtonText: 'باشە'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: data.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاری دابینکەر',
                            confirmButtonText: 'باشە'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                        confirmButtonText: 'باشە'
                    });
                });
            });

            // Add customer delete button click handlers
            document.querySelectorAll('#customerTable .delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    
                    Swal.fire({
                        title: 'دڵنیای لە سڕینەوە؟',
                        text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'بەڵێ، بیسڕەوە',
                        cancelButtonText: 'نا، هەڵوەشێنەوە'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'تکایە چاوەڕێ بکە...',
                                text: 'سڕینەوەی کڕیار',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            // Send delete request
                            fetch('../../process/delete_customer.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ id: customerId })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove the row from the table
                                    row.remove();
                                    
                                    // Update pagination
                                    applyCustomerPagination(1);
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'سەرکەوتوو بوو!',
                                        text: data.message,
                                        confirmButtonText: 'باشە'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە!',
                                        text: data.message,
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە: ' + error.message,
                                    confirmButtonText: 'باشە'
                                });
                            });
                        }
                    });
                });
            });
            
            // Add supplier delete button click handlers
            document.querySelectorAll('#supplierTable .delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const supplierId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    
                    Swal.fire({
                        title: 'دڵنیای لە سڕینەوە؟',
                        text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'بەڵێ، بیسڕەوە',
                        cancelButtonText: 'نا، هەڵوەشێنەوە'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'تکایە چاوەڕێ بکە...',
                                text: 'سڕینەوەی دابینکەر',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            // Send delete request
                            fetch('../../process/delete_supplier.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ id: supplierId })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove the row from the table
                                    row.remove();
                                    
                                    // Update pagination
                                    applySupplierPagination(1);
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'سەرکەوتوو بوو!',
                                        text: data.message,
                                        confirmButtonText: 'باشە'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە!',
                                        text: data.message,
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە: ' + error.message,
                                    confirmButtonText: 'باشە'
                                });
                            });
                        }
                    });
                });
            });

            // Employee reset filter functionality
            document.getElementById('employeeResetFilter').addEventListener('click', function() {
                // Reset name filter
                document.getElementById('employeeNameFilter').value = '';
                
                // Reset phone filter
                document.getElementById('employeePhoneSearch').value = '';
                
                // Reset table search
                document.getElementById('employeeTableSearch').value = '';
                
                // Refresh employee list
                fetchEmployees();
            });

            // Customer reset filter functionality
            document.getElementById('customerResetFilter').addEventListener('click', function() {
                // Reset name filter
                document.getElementById('customerName').value = '';
                
                // Reset phone filter
                document.getElementById('customerPhone').value = '';
                
                // Reset table search
                document.getElementById('customerTableSearch').value = '';
                
                // Reset all rows to visible
                const customerRows = document.querySelectorAll('#customerTable tbody tr');
                customerRows.forEach(row => {
                    row.dataset.filterMatch = 'true';
                    row.dataset.searchMatch = 'true';
                    row.style.display = '';
                });
                
                // Reapply pagination
                applyCustomerPagination(1);
            });

            // Supplier reset filter functionality
            document.getElementById('supplierResetFilter').addEventListener('click', function() {
                // Reset name filter
                document.getElementById('supplierName').value = '';
                
                // Reset phone filter
                document.getElementById('supplierPhone').value = '';
                
                // Reset table search
                document.getElementById('supplierTableSearch').value = '';
                
                // Reset all rows to visible
                const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
                supplierRows.forEach(row => {
                    row.dataset.filterMatch = 'true';
                    row.dataset.searchMatch = 'true';
                    row.style.display = '';
                });
                
                // Reapply pagination
                applySupplierPagination(1);
            });

            // Add customer table search functionality
            document.getElementById('customerTableSearch').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const customerRows = document.querySelectorAll('#customerTable tbody tr');
                
                customerRows.forEach(row => {
                    let match = false;
                    // Search in all cells except the last one (actions column)
                    for (let i = 1; i < row.cells.length - 1; i++) {
                        const cellText = row.cells[i].textContent.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            match = true;
                            break;
                        }
                    }
                    
                    row.dataset.searchMatch = match ? 'true' : 'false';
                    applyCustomerFilters();
                });
            });

            // Add supplier table search functionality
            document.getElementById('supplierTableSearch').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
                
                supplierRows.forEach(row => {
                    let match = false;
                    // Search in all cells except the last one (actions column)
                    for (let i = 1; i < row.cells.length - 1; i++) {
                        const cellText = row.cells[i].textContent.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            match = true;
                            break;
                        }
                    }
                    
                    row.dataset.searchMatch = match ? 'true' : 'false';
                    applySupplierFilters();
                });
            });

            // Customer name filter functionality
            document.getElementById('customerName').addEventListener('change', function() {
                const selectedName = this.value.toLowerCase();
                const customerRows = document.querySelectorAll('#customerTable tbody tr');
                
                customerRows.forEach(row => {
                    const nameCell = row.cells[1]; // Name is in the second column
                    const name = nameCell.textContent.toLowerCase();
                    
                    // Match if filter is empty or name matches
                    const match = !selectedName || name === selectedName;
                    row.dataset.nameFilterMatch = match ? 'true' : 'false';
                    
                    applyCustomerFilters();
                });
            });

            // Customer phone filter functionality
            document.getElementById('customerPhone').addEventListener('input', function() {
                const phoneFilter = this.value.toLowerCase();
                const customerRows = document.querySelectorAll('#customerTable tbody tr');
                
                customerRows.forEach(row => {
                    const phoneCell = row.cells[2]; // Phone is in the third column
                    const phone = phoneCell.textContent.toLowerCase();
                    
                    // Match if filter is empty or phone includes the filter
                    const match = !phoneFilter || phone.includes(phoneFilter);
                    row.dataset.phoneFilterMatch = match ? 'true' : 'false';
                    
                    applyCustomerFilters();
                });
            });

            // Supplier name filter functionality
            document.getElementById('supplierName').addEventListener('change', function() {
                const selectedName = this.value.toLowerCase();
                const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
                
                supplierRows.forEach(row => {
                    const nameCell = row.cells[1]; // Name is in the second column
                    const name = nameCell.textContent.toLowerCase();
                    
                    // Match if filter is empty or name matches
                    const match = !selectedName || name === selectedName;
                    row.dataset.nameFilterMatch = match ? 'true' : 'false';
                    
                    applySupplierFilters();
                });
            });

            // Supplier phone filter functionality
            document.getElementById('supplierPhone').addEventListener('input', function() {
                const phoneFilter = this.value.toLowerCase();
                const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
                
                supplierRows.forEach(row => {
                    const phoneCell = row.cells[2]; // Phone is in the third column
                    const phone = phoneCell.textContent.toLowerCase();
                    
                    // Match if filter is empty or phone includes the filter
                    const match = !phoneFilter || phone.includes(phoneFilter);
                    row.dataset.phoneFilterMatch = match ? 'true' : 'false';
                    
                    applySupplierFilters();
                });
            });

            // Function to apply all customer filters
            function applyCustomerFilters() {
                const customerRows = document.querySelectorAll('#customerTable tbody tr');
                let visibleCount = 0;
                
                customerRows.forEach(row => {
                    // Default all filters to true if not set
                    const nameMatch = row.dataset.nameFilterMatch !== 'false';
                    const phoneMatch = row.dataset.phoneFilterMatch !== 'false';
                    const searchMatch = row.dataset.searchMatch !== 'false';
                    
                    // Show row only if it matches all filters
                    const isVisible = nameMatch && phoneMatch && searchMatch;
                    row.style.display = isVisible ? '' : 'none';
                    
                    if (isVisible) {
                        visibleCount++;
                    }
                });
                
                // Update pagination after filtering
                applyCustomerPagination(1);
            }

            // Function to apply all supplier filters
            function applySupplierFilters() {
                const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
                let visibleCount = 0;
                
                supplierRows.forEach(row => {
                    // Default all filters to true if not set
                    const nameMatch = row.dataset.nameFilterMatch !== 'false';
                    const phoneMatch = row.dataset.phoneFilterMatch !== 'false';
                    const searchMatch = row.dataset.searchMatch !== 'false';
                    
                    // Show row only if it matches all filters
                    const isVisible = nameMatch && phoneMatch && searchMatch;
                    row.style.display = isVisible ? '' : 'none';
                    
                    if (isVisible) {
                        visibleCount++;
                    }
                });
                
                // Update pagination after filtering
                applySupplierPagination(1);
            }

            // Customer pagination functionality
            function applyCustomerPagination(page) {
                const recordsPerPage = parseInt(document.getElementById('customerRecordsPerPage').value);
                const customerRows = document.querySelectorAll('#customerTable tbody tr');
                const visibleRows = Array.from(customerRows).filter(row => row.style.display !== 'none');
                const totalVisibleRecords = visibleRows.length;
                
                // Calculate total pages
                const totalPages = Math.ceil(totalVisibleRecords / recordsPerPage);
                
                // Ensure page is within bounds
                page = Math.min(Math.max(1, page), totalPages || 1);
                
                // Show only rows for current page
                visibleRows.forEach((row, index) => {
                    const startIndex = (page - 1) * recordsPerPage;
                    const endIndex = startIndex + recordsPerPage - 1;
                    
                    row.style.display = (index >= startIndex && index <= endIndex) ? '' : 'none';
                });
                
                // Update pagination info
                const startRecord = totalVisibleRecords > 0 ? (page - 1) * recordsPerPage + 1 : 0;
                const endRecord = Math.min(page * recordsPerPage, totalVisibleRecords);
                
                document.getElementById('customerStartRecord').textContent = startRecord;
                document.getElementById('customerEndRecord').textContent = endRecord;
                document.getElementById('customerTotalRecords').textContent = totalVisibleRecords;
                
                // Update pagination buttons
                const prevPageBtn = document.getElementById('customerPrevPageBtn');
                const nextPageBtn = document.getElementById('customerNextPageBtn');
                
                prevPageBtn.disabled = page === 1;
                nextPageBtn.disabled = page === totalPages || totalPages === 0;
                
                // Update pagination numbers
                const paginationNumbers = document.getElementById('customerPaginationNumbers');
                paginationNumbers.innerHTML = '';
                
                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.className = `btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2`;
                    pageBtn.textContent = i;
                    pageBtn.addEventListener('click', () => applyCustomerPagination(i));
                    paginationNumbers.appendChild(pageBtn);
                }
                
                return page;
            }

            // Supplier pagination functionality
            function applySupplierPagination(page) {
                const recordsPerPage = parseInt(document.getElementById('supplierRecordsPerPage').value);
                const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
                const visibleRows = Array.from(supplierRows).filter(row => row.style.display !== 'none');
                const totalVisibleRecords = visibleRows.length;
                
                // Calculate total pages
                const totalPages = Math.ceil(totalVisibleRecords / recordsPerPage);
                
                // Ensure page is within bounds
                page = Math.min(Math.max(1, page), totalPages || 1);
                
                // Show only rows for current page
                visibleRows.forEach((row, index) => {
                    const startIndex = (page - 1) * recordsPerPage;
                    const endIndex = startIndex + recordsPerPage - 1;
                    
                    row.style.display = (index >= startIndex && index <= endIndex) ? '' : 'none';
                });
                
                // Update pagination info
                const startRecord = totalVisibleRecords > 0 ? (page - 1) * recordsPerPage + 1 : 0;
                const endRecord = Math.min(page * recordsPerPage, totalVisibleRecords);
                
                document.getElementById('supplierStartRecord').textContent = startRecord;
                document.getElementById('supplierEndRecord').textContent = endRecord;
                document.getElementById('supplierTotalRecords').textContent = totalVisibleRecords;
                
                // Update pagination buttons
                const prevPageBtn = document.getElementById('supplierPrevPageBtn');
                const nextPageBtn = document.getElementById('supplierNextPageBtn');
                
                prevPageBtn.disabled = page === 1;
                nextPageBtn.disabled = page === totalPages || totalPages === 0;
                
                // Update pagination numbers
                const paginationNumbers = document.getElementById('supplierPaginationNumbers');
                paginationNumbers.innerHTML = '';
                
                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.className = `btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2`;
                    pageBtn.textContent = i;
                    pageBtn.addEventListener('click', () => applySupplierPagination(i));
                    paginationNumbers.appendChild(pageBtn);
                }
                
                return page;
            }

            // Add records per page change handlers
            document.getElementById('customerRecordsPerPage').addEventListener('change', function() {
                applyCustomerPagination(1);
            });
            
            document.getElementById('supplierRecordsPerPage').addEventListener('change', function() {
                applySupplierPagination(1);
            });
            
            // Add pagination next/prev button handlers
            document.getElementById('customerPrevPageBtn').addEventListener('click', function() {
                const currentActivePage = document.querySelector('#customerPaginationNumbers .btn-primary');
                if (currentActivePage) {
                    const currentPage = parseInt(currentActivePage.textContent);
                    applyCustomerPagination(currentPage - 1);
                }
            });
            
            document.getElementById('customerNextPageBtn').addEventListener('click', function() {
                const currentActivePage = document.querySelector('#customerPaginationNumbers .btn-primary');
                if (currentActivePage) {
                    const currentPage = parseInt(currentActivePage.textContent);
                    applyCustomerPagination(currentPage + 1);
                }
            });
            
            document.getElementById('supplierPrevPageBtn').addEventListener('click', function() {
                const currentActivePage = document.querySelector('#supplierPaginationNumbers .btn-primary');
                if (currentActivePage) {
                    const currentPage = parseInt(currentActivePage.textContent);
                    applySupplierPagination(currentPage - 1);
                }
            });
            
            document.getElementById('supplierNextPageBtn').addEventListener('click', function() {
                const currentActivePage = document.querySelector('#supplierPaginationNumbers .btn-primary');
                if (currentActivePage) {
                    const currentPage = parseInt(currentActivePage.textContent);
                    applySupplierPagination(currentPage + 1);
                }
            });
            
            // Initialize customer and supplier pagination
            applyCustomerPagination(1);
            applySupplierPagination(1);

            // Add supplier edit button click handlers
            document.querySelectorAll('#supplierTable .edit-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent the default modal opening behavior
                    const supplierId = this.getAttribute('data-id');
                    
                    // Show loading
                    Swal.fire({
                        title: 'تکایە چاوەڕێ بکە...',
                        text: 'زانیاری دابینکەر بار دەکرێت',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Fetch supplier data
                    fetch(`../../process/get_supplier.php?id=${supplierId}`)
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                // Fill the edit form with supplier data
                                document.getElementById('editSupplierId').value = data.supplier.id;
                                document.getElementById('editSupplierName').value = data.supplier.name;
                                document.getElementById('editSupplierPhone1').value = data.supplier.phone1;
                                document.getElementById('editSupplierPhone2').value = data.supplier.phone2 || '';
                                document.getElementById('editSupplierDebt').value = data.supplier.debt_on_myself || 0;
                                document.getElementById('editSupplierNotes').value = data.supplier.notes || '';
                                
                                // Open the modal
                                const editSupplierModal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
                                editSupplierModal.show();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: data.message || 'دابینکەر نەدۆزرایەوە',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                                confirmButtonText: 'باشە'
                            });
                        });
                });
            });
        });
    </script>
</body>

</html>