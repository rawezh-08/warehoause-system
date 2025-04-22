<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../process/expensesHistory_logic.php';
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
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/expensesHistory.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
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
                                                <input type="date" class="form-control auto-filter" id="employeePaymentStartDate" value="<?php echo $startOfMonth; ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="employeePaymentEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="employeePaymentEndDate" value="<?php echo $today; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="employeePaymentName" class="form-label">ناوی کارمەند</label>
                                                <select class="form-select auto-filter" id="employeePaymentName">
                                                    <option value="">هەموو کارمەندەکان</option>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <option value="<?php echo htmlspecialchars($employee['name']); ?>">
                                                            <?php echo htmlspecialchars($employee['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
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
                        
                        <!-- Employee Payments Summary -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">پوختەی پارەدان لەم ماوەیەدا</h5>
                                        <div class="row">
                                            <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                                                <div class="card bg-light h-100">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-subtitle mb-2 text-muted">کۆی پارەدان</h6>
                                                        <h3 class="card-title"><?php echo number_format($employeePaymentsStats['total_amount'] ?? 0); ?> د.ع</h3>
                                                        <p class="card-text"><?php echo $employeePaymentsStats['total_payments'] ?? 0; ?> پارەدان</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                                                <div class="card bg-light h-100">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-subtitle mb-2 text-muted">مووچە</h6>
                                                        <h3 class="card-title"><?php echo number_format($employeePaymentsStats['total_salary'] ?? 0); ?> د.ع</h3>
                                                        <p class="card-text"><span class="badge bg-success">مووچە</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                                                <div class="card bg-light h-100">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-subtitle mb-2 text-muted">پاداشت</h6>
                                                        <h3 class="card-title"><?php echo number_format($employeePaymentsStats['total_bonus'] ?? 0); ?> د.ع</h3>
                                                        <p class="card-text"><span class="badge bg-warning">پاداشت</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                                                <div class="card bg-light h-100">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-subtitle mb-2 text-muted">کاتژمێری زیادە</h6>
                                                        <h3 class="card-title"><?php echo number_format($employeePaymentsStats['total_overtime'] ?? 0); ?> د.ع</h3>
                                                        <p class="card-text"><span class="badge bg-info">کاتژمێری زیادە</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                                                        <?php foreach ($employeePaymentsData as $index => $payment): ?>
                                                        <tr data-id="<?php echo $payment['id']; ?>">
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($payment['employee_name'] ?? 'N/A'); ?></td>
                                                            <td><?php echo date('Y/m/d', strtotime($payment['payment_date'])); ?></td>
                                                            <td><?php echo number_format($payment['amount']) . ' د.ع'; ?></td>
                                                            <td>
                                                                <span class="badge rounded-pill <?php 
                                                                    echo $payment['payment_type'] == 'salary' ? 'bg-success' : 
                                                                        ($payment['payment_type'] == 'bonus' ? 'bg-warning' : 
                                                                        ($payment['payment_type'] == 'overtime' ? 'bg-info' : 'bg-secondary')); 
                                                                ?>">
                                                                    <?php 
                                                                    echo $payment['payment_type'] == 'salary' ? 'مووچە' : 
                                                                        ($payment['payment_type'] == 'bonus' ? 'پاداشت' : 
                                                                        ($payment['payment_type'] == 'overtime' ? 'کاتژمێری زیادە' : 'جۆری تر')); 
                                                                    ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="<?php echo $payment['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="<?php echo $payment['id']; ?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($employeePaymentsData)): ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center">هیچ پارەدانێک نەدۆزرایەوە</td>
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
                  
                    
                    <!-- Money Withdrawal Tab -->
                    <div class="tab-pane fade" id="withdrawal-content" role="tabpanel" aria-labelledby="withdrawal-tab">
                        <!-- Date Filter for Withdrawals -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">فلتەر بەپێی بەروار و ناو</h5>
                                        <form id="withdrawalFilterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="withdrawalStartDate" class="form-label">بەرواری دەستپێک</label>
                                                <input type="date" class="form-control auto-filter" id="withdrawalStartDate">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="withdrawalEndDate" class="form-label">بەرواری کۆتایی</label>
                                                <input type="date" class="form-control auto-filter" id="withdrawalEndDate">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
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
                                                            <th>بەروار</th>
                                                            <th>بڕی پارە</th>
                                                            <th>تێبینی</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($withdrawalsData as $index => $withdrawal): ?>
                                                        <tr data-id="<?php echo $withdrawal['id']; ?>">
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo date('Y/m/d', strtotime($withdrawal['expense_date'])); ?></td>
                                                            <td><?php echo number_format($withdrawal['amount']) . ' د.ع'; ?></td>
                                                            <td><?php echo htmlspecialchars($withdrawal['notes'] ?? ''); ?></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="<?php echo $withdrawal['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="<?php echo $withdrawal['id']; ?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($withdrawalsData)): ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center">هیچ دەرکردنێکی پارە نەدۆزرایەوە</td>
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
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Global AJAX Configuration -->
    <script src="../../js/ajax-config.js"></script>
    <!-- Custom JavaScript -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/expenses-history.js"></script>

    <!-- Edit Employee Payment Modal -->
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
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo htmlspecialchars($employee['id']); ?>">
                                        <?php echo htmlspecialchars($employee['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePaymentDate" class="form-label">بەروار</label>
                            <input type="date" id="editEmployeePaymentDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePaymentAmount" class="form-label">بڕی پارە</label>
                            <div class="input-group">
                                <input type="text" id="editEmployeePaymentAmount" class="form-control number-format" required>
                                <span class="input-group-text">د.ع</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePaymentType" class="form-label">جۆری پارەدان</label>
                            <select id="editEmployeePaymentType" class="form-select" required>
                                <option value="" selected disabled>جۆری پارەدان</option>
                                <option value="salary">مووچە</option>
                                <option value="bonus">پاداشت</option>
                                <option value="overtime">کاتژمێری زیادە</option>
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

    <!-- Edit Withdrawal Modal -->
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
                            <label for="editWithdrawalDate" class="form-label">بەروار</label>
                            <input type="date" id="editWithdrawalDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editWithdrawalAmount" class="form-label">بڕی پارە</label>
                            <div class="input-group">
                                <input type="text" id="editWithdrawalAmount" class="form-control number-format" required>
                                <span class="input-group-text">د.ع</span>
                            </div>
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