<?php
// You can add PHP logic here if needed
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مێژووی مەسروفات - سیستەمی بەڕێوەبردنی کۆگا</title>
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
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="page-title">مێژووی مەسروفات</h3>
                    </div>
                </div>

                <!-- Tabs navigation -->
                <div class="row mb-4">
                    <div class="col-12">
                        <ul class="nav nav-tabs expenses-tabs" id="expensesTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="employee-payment-tab" data-bs-toggle="tab" data-bs-target="#employee-payment-content" type="button" role="tab" aria-controls="employee-payment-content" aria-selected="true">
                                    <i class="fas fa-user-tie me-2"></i>پارەدان بە کارمەند
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping-content" type="button" role="tab" aria-controls="shipping-content" aria-selected="false">
                                    <i class="fas fa-truck me-2"></i>کرێی بار
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="withdrawal-tab" data-bs-toggle="tab" data-bs-target="#withdrawal-content" type="button" role="tab" aria-controls="withdrawal-content" aria-selected="false">
                                    <i class="fas fa-money-bill-wave me-2"></i>دەرکردنی پارە
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
                                            <div class="col-md-4">
                                                <label for="employeePaymentName" class="form-label">ناوی کارمەند</label>
                                                <select class="form-select auto-filter" id="employeePaymentName">
                                                    <option value="">هەموو کارمەندەکان</option>
                                                    <option value="ئاری محمد">ئاری محمد</option>
                                                    <option value="شیلان عمر">شیلان عمر</option>
                                                    <option value="هاوڕێ ئەحمەد">هاوڕێ ئەحمەد</option>
                                                    <option value="سارا عەلی">سارا عەلی</option>
                                                    <option value="کارزان محمود">کارزان محمود</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
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
                                        <h5 class="card-title mb-0">مێژووی پارەدان بە کارمەند</h5>
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
                                                            <th>ناوی کارمەند</th>
                                                            <th>بەروار</th>
                                                            <th>بڕی پارە</th>
                                                            <th>جۆری پارەدان</th>
                                                            <th>تێبینی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Sample data - will be replaced with real data from database -->
                                                        <tr data-id="1">
                                                            <td>1</td>
                                                            <td>ئاری محمد</td>
                                                            <td>2023/04/15</td>
                                                            <td>$500</td>
                                                            <td><span class="badge rounded-pill bg-success">مووچە</span></td>
                                                            <td>مووچەی مانگی نیسان</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="1" data-bs-toggle="modal" data-bs-target="#editEmployeePaymentModal">
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
                                                            <td>شیلان عمر</td>
                                                            <td>2023/04/15</td>
                                                            <td>$450</td>
                                                            <td><span class="badge rounded-pill bg-success">مووچە</span></td>
                                                            <td>مووچەی مانگی نیسان</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="2" data-bs-toggle="modal" data-bs-target="#editEmployeePaymentModal">
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
                                                            <td>هاوڕێ ئەحمەد</td>
                                                            <td>2023/04/17</td>
                                                            <td>$100</td>
                                                            <td><span class="badge rounded-pill bg-info">پێشەکی</span></td>
                                                            <td>پێشەکی پارە</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="3" data-bs-toggle="modal" data-bs-target="#editEmployeePaymentModal">
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
                                        <h5 class="card-title mb-4">فلتەر بەپێی بەروار و ناو</h5>
                                        <form id="shippingFilterForm" class="row g-3">
                                            <div class="col-md-3">
                                                <label for="shippingStartDate" class="form-label">بەرواری دەستپێک</label>
                                                <input type="date" class="form-control auto-filter" id="shippingStartDate">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="shippingEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="shippingEndDate">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="shippingProvider" class="form-label">دابینکەر</label>
                                                <select class="form-select auto-filter" id="shippingProvider">
                                                    <option value="">هەموو دابینکەرەکان</option>
                                                    <option value="کۆمپانیای تیوان">کۆمپانیای تیوان</option>
                                                    <option value="کۆمپانیای ئارەزوو">کۆمپانیای ئارەزوو</option>
                                                    <option value="کۆمپانیای هێمن">کۆمپانیای هێمن</option>
                                                    <option value="کۆمپانیای سارا">کۆمپانیای سارا</option>
                                                </select>
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
                                                            <th>دابینکەر</th>
                                                            <th>بەروار</th>
                                                            <th>بڕی پارە</th>
                                                            <th>جۆری بار</th>
                                                            <th>تێبینی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Sample data - will be replaced with real data from database -->
                                                        <tr data-id="1">
                                                            <td>1</td>
                                                            <td>کۆمپانیای تیوان</td>
                                                            <td>2023/04/10</td>
                                                            <td>$800</td>
                                                            <td><span class="badge rounded-pill bg-info">وشکانی</span></td>
                                                            <td>بار لە تورکیاوە</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="1" data-bs-toggle="modal" data-bs-target="#editShippingModal">
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
                                                            <td>کۆمپانیای ئارەزوو</td>
                                                            <td>2023/04/15</td>
                                                            <td>$1200</td>
                                                            <td><span class="badge rounded-pill bg-success">دەریایی</span></td>
                                                            <td>بار لە چینەوە</td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="2" data-bs-toggle="modal" data-bs-target="#editShippingModal">
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
    <script src="js/include-components.js"></script>
    <script src="js/expensesHistory/script.js"></script>

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
</body>
</html> 