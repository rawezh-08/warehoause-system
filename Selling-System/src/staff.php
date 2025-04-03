<?php
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

    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/employeePayment/style.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Custom styles for this page -->
    <style>
        /* Transparent search input */
        .table-search-input {
            background-color: transparent !important;
            border: 1px solid #dee2e6;
        }

        /* Word wrapping for table cells */
        .custom-table td,
        th {
            white-space: normal;
            word-wrap: break-word;
            vertical-align: middle;
            padding: 0.75rem;
        }

        #supplierTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #customerTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #employeeTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #supplierTable th {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #customerTable th {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #employeeTable th {

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
        <div class="main-content p-3" id="main-content">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="page-title">لیستی هاوکارەکان</h3>
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
                                            <div class="col-md-4">
                                                <label for="customerName" class="form-label">ناوی کڕیار</label>
                                                <input type="text" class="form-control auto-filter" id="customerName">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="customerType" class="form-label">جۆری کڕیار</label>
                                                <select class="form-select auto-filter" id="customerType">
                                                    <option value="">هەموو جۆرەکان</option>
                                                    <option value="retail">تاک</option>
                                                    <option value="wholesale">کۆ</option>
                                                    <option value="regular">بەردەوام</option>
                                                </select>
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
                                                            <th>ناونیشان</th>
                                                            <th>جۆری کڕیار</th>
                                                            <th>سنووری قەرز</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Sample data - will be replaced with real data from database -->
                                                        <tr data-id="1">
                                                            <td>1</td>
                                                            <td>ئازاد حسین</td>
                                                            <td>0750 222 3333</td>
                                                            <td>دهۆک، شەقامی نەوروز</td>
                                                            <td><span class="badge bg-success">بەردەوام</span></td>
                                                            <td>$2000</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="1" data-bs-toggle="modal"
                                                                        data-bs-target="#editCustomerModal">
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
                                                            <td>بێریڤان عەبدوڵڵا</td>
                                                            <td>0750 444 5555</td>
                                                            <td>هەولێر، گوندی ئینزا</td>
                                                            <td><span class="badge bg-info">تاک</span></td>
                                                            <td>$500</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="2" data-bs-toggle="modal"
                                                                        data-bs-target="#editCustomerModal">
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
                                                            <td>کاروان ڕەشید</td>
                                                            <td>0750 777 8888</td>
                                                            <td>سلێمانی، شەقامی بازاڕ</td>
                                                            <td><span class="badge bg-warning text-dark">کۆ</span></td>
                                                            <td>$5000</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="3" data-bs-toggle="modal"
                                                                        data-bs-target="#editCustomerModal">
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
                                                            نیشاندانی <span id="customerStartRecord">1</span> تا <span
                                                                id="customerEndRecord">3</span> لە کۆی <span
                                                                id="customerTotalRecords">3</span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                                            <button id="customerPrevPageBtn"
                                                                class="btn btn-sm btn-outline-primary rounded-circle me-2"
                                                                disabled>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                            <div id="customerPaginationNumbers"
                                                                class="pagination-numbers d-flex">
                                                                <button
                                                                    class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                            </div>
                                                            <button id="customerNextPageBtn"
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
                                                <label for="supplierType" class="form-label">جۆری دابینکەر</label>
                                                <select class="form-select auto-filter" id="supplierType">
                                                    <option value="">هەموو جۆرەکان</option>
                                                    <option value="manufacturer">بەرهەمهێنەر</option>
                                                    <option value="distributor">دابەشکەر</option>
                                                    <option value="wholesaler">فرۆشیاری کۆ</option>
                                                </select>
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
                                                            <th>کەسی پەیوەندی</th>
                                                            <th>ناونیشان</th>
                                                            <th>جۆری دابینکەر</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Sample data - will be replaced with real data from database -->
                                                        <tr data-id="1">
                                                            <td>1</td>
                                                            <td>کۆمپانیای تیوان</td>
                                                            <td>0750 999 0000</td>
                                                            <td>ئاراس سەعید</td>
                                                            <td>هەولێر، شەقامی ئازادی</td>
                                                            <td><span class="badge bg-primary">بەرهەمهێنەر</span></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="1" data-bs-toggle="modal"
                                                                        data-bs-target="#editSupplierModal">
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
                                                            <td>کۆمپانیای ئارەزوو</td>
                                                            <td>0750 111 2222</td>
                                                            <td>هێڤیدار خالید</td>
                                                            <td>سلێمانی، شەقامی مەولەوی</td>
                                                            <td><span class="badge bg-info">دابەشکەر</span></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="2" data-bs-toggle="modal"
                                                                        data-bs-target="#editSupplierModal">
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
                                                            <td>کۆمپانیای هێمن</td>
                                                            <td>0750 333 4444</td>
                                                            <td>هەڵۆ عەزیز</td>
                                                            <td>دهۆک، شەقامی زانکۆ</td>
                                                            <td><span class="badge bg-warning text-dark">فرۆشیاری
                                                                    کۆ</span></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                        data-id="3" data-bs-toggle="modal"
                                                                        data-bs-target="#editSupplierModal">
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
                                                            نیشاندانی <span id="supplierStartRecord">1</span> تا <span
                                                                id="supplierEndRecord">3</span> لە کۆی <span
                                                                id="supplierTotalRecords">3</span> تۆمار
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">دەستکاری زانیاری کڕیار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCustomerForm">
                        <input type="hidden" id="editCustomerId">
                        <div class="mb-3">
                            <label for="editCustomerName" class="form-label">ناوی کڕیار</label>
                            <input type="text" class="form-control" id="editCustomerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCustomerPhone" class="form-label">ژمارەی مۆبایل</label>
                            <input type="tel" class="form-control" id="editCustomerPhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCustomerAddress" class="form-label">ناونیشان</label>
                            <textarea class="form-control" id="editCustomerAddress" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editGuarantorName" class="form-label">ناوی کەفیل</label>
                            <input type="text" class="form-control" id="editGuarantorName">
                        </div>
                        <div class="mb-3">
                            <label for="editGuarantorPhone" class="form-label">ژمارەی مۆبایلی کەفیل</label>
                            <input type="tel" class="form-control" id="editGuarantorPhone">
                        </div>
                        <div class="mb-3">
                            <label for="editCustomerType" class="form-label">جۆری کڕیار</label>
                            <select class="form-select" id="editCustomerType">
                                <option value="" selected disabled>هەڵبژێرە</option>
                                <option value="retail">تاک</option>
                                <option value="wholesale">کۆ</option>
                                <option value="regular">بەردەوام</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCreditLimit" class="form-label">سنووری قەرز</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editCreditLimit">
                                <span class="input-group-text">$</span>
                            </div>
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
                            <label for="editSupplierPhone" class="form-label">ژمارەی پەیوەندی</label>
                            <input type="tel" class="form-control" id="editSupplierPhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="editContactPerson" class="form-label">کەسی پەیوەندی</label>
                            <input type="text" class="form-control" id="editContactPerson">
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierEmail" class="form-label">ئیمەیل</label>
                            <input type="email" class="form-control" id="editSupplierEmail">
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierAddress" class="form-label">ناونیشان</label>
                            <textarea class="form-control" id="editSupplierAddress" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierType" class="form-label">جۆری دابینکەر</label>
                            <select class="form-select" id="editSupplierType">
                                <option value="" selected disabled>هەڵبژێرە</option>
                                <option value="manufacturer">بەرهەمهێنەر</option>
                                <option value="distributor">دابەشکەر</option>
                                <option value="wholesaler">فرۆشیاری کۆ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPaymentTerms" class="form-label">مەرجەکانی پارەدان</label>
                            <select class="form-select" id="editPaymentTerms">
                                <option value="" selected disabled>هەڵبژێرە</option>
                                <option value="immediate">دەستبەجێ</option>
                                <option value="15days">15 ڕۆژ</option>
                                <option value="30days">30 ڕۆژ</option>
                                <option value="60days">60 ڕۆژ</option>
                            </select>
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
    <!-- Custom JavaScript -->
    <script src="js/include-components.js"></script>
    <script src="js/staff/script.js"></script>
</body>

</html>