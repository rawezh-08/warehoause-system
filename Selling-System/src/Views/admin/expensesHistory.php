<?php
require_once __DIR__ . '/../../config/database.php';

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch all employees
try {
    $stmt = $conn->prepare("SELECT id, name FROM employees ORDER BY name ASC");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $employees = [];
}

// Function to get employee payments data
function getEmployeePaymentsData($limit = 10, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get employee payments
    $sql = "SELECT 
                ep.*,
                e.name as employee_name
            FROM employee_payments ep
            LEFT JOIN employees e ON ep.employee_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(ep.payment_date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(ep.payment_date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['employee_name'])) {
        $sql .= " AND e.name LIKE :employee_name";
        $params[':employee_name'] = '%' . $filters['employee_name'] . '%';
    }
    
    $sql .= " ORDER BY ep.payment_date DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key == ':offset' || $key == ':limit') {
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

// Function to get employee payments statistics
function getEmployeePaymentsStats($filters = []) {
    global $conn;
    
    // Base query to get employee payments stats
    $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(ep.amount) as total_amount,
                SUM(CASE WHEN ep.payment_type = 'salary' THEN ep.amount ELSE 0 END) as total_salary,
                SUM(CASE WHEN ep.payment_type = 'bonus' THEN ep.amount ELSE 0 END) as total_bonus,
                SUM(CASE WHEN ep.payment_type = 'overtime' THEN ep.amount ELSE 0 END) as total_overtime
            FROM employee_payments ep
            LEFT JOIN employees e ON ep.employee_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(ep.payment_date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(ep.payment_date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['employee_name'])) {
        $sql .= " AND e.name LIKE :employee_name";
        $params[':employee_name'] = '%' . $filters['employee_name'] . '%';
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [
            'total_payments' => 0,
            'total_amount' => 0,
            'total_salary' => 0,
            'total_bonus' => 0,
            'total_overtime' => 0
        ];
    }
}

// Function to get withdrawal data
function getWithdrawalsData($limit = 10, $offset = 0, $filters = []) {
    global $conn;
    
    // Base query to get expenses
    $sql = "SELECT * FROM expenses WHERE 1=1";
    
    $params = [];
    
    // Apply filters if any
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(expense_date) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(expense_date) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
    }
    
    $sql .= " ORDER BY expense_date DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = (int)$offset;
        $params[':limit'] = (int)$limit;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key == ':offset' || $key == ':limit') {
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

// Get data with default filters
$employeePaymentsData = getEmployeePaymentsData(10, 0, $defaultFilters);
$employeePaymentsStats = getEmployeePaymentsStats($defaultFilters);
$withdrawalsData = getWithdrawalsData(10, 0, $defaultFilters);

// Handle AJAX filter requests
if (isset($_POST['action']) && $_POST['action'] == 'filter') {
    header('Content-Type: application/json');
    
    $filters = [
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? null
    ];
    
    if ($_POST['type'] == 'employee_payments') {
        if (!empty($_POST['employee_name'])) {
            $filters['employee_name'] = $_POST['employee_name'];
        }
        $employeePaymentsData = getEmployeePaymentsData(10, 0, $filters);
        $employeePaymentsStats = getEmployeePaymentsStats($filters);
        echo json_encode([
            'success' => true, 
            'data' => $employeePaymentsData,
            'stats' => $employeePaymentsStats
        ]);
        exit;
    } else if ($_POST['type'] == 'withdrawals') {
        if (!empty($_POST['name'])) {
            $filters['name'] = $_POST['name'];
        }
        $withdrawalsData = getWithdrawalsData(10, 0, $filters);
        echo json_encode(['success' => true, 'data' => $withdrawalsData]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Handle delete requests
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'delete_employee_payment' && isset($_POST['id'])) {
        header('Content-Type: application/json');
        try {
            $stmt = $conn->prepare("DELETE FROM employee_payments WHERE id = :id");
            $stmt->bindParam(':id', $_POST['id']);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا']);
        }
        exit;
    }
    
    if ($_POST['action'] == 'delete_withdrawal' && isset($_POST['id'])) {
        header('Content-Type: application/json');
        try {
            $stmt = $conn->prepare("DELETE FROM expenses WHERE id = :id");
            $stmt->bindParam(':id', $_POST['id']);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا']);
        }
        exit;
    }
}

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
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="<?php echo $payment['id']; ?>" data-bs-toggle="modal" data-bs-target="#editEmployeePaymentModal">
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
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="<?php echo $withdrawal['id']; ?>" data-bs-toggle="modal" data-bs-target="#editWithdrawalModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="<?php echo $withdrawal['id']; ?>">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="<?php echo $withdrawal['id']; ?>">
                                                                        <i class="fas fa-print"></i>
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
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/receiptList/receipt.js"></script>
    <script>
    // Wait for the DOM to be ready
    $(document).ready(function() {
        // Apply filter for employee payments
        $('.auto-filter').on('change', function() {
            if ($(this).closest('form').attr('id') === 'employeePaymentFilterForm') {
                applyEmployeePaymentFilter();
            } else if ($(this).closest('form').attr('id') === 'withdrawalFilterForm') {
                applyWithdrawalFilter();
            }
        });

        // Reset filters for employee payments
        $('#employeePaymentResetFilter').on('click', function() {
            resetEmployeePaymentFilter();
        });

        // Reset filters for withdrawals
        $('#withdrawalResetFilter').on('click', function() {
            resetWithdrawalFilter();
        });

        // Apply filter for employee payments
        function applyEmployeePaymentFilter() {
            const startDate = $('#employeePaymentStartDate').val();
            const endDate = $('#employeePaymentEndDate').val();
            const employeeName = $('#employeePaymentName').val();
            
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    action: 'filter',
                    type: 'employee_payments',
                    start_date: startDate,
                    end_date: endDate,
                    employee_name: employeeName
                },
                dataType: 'json',
                beforeSend: function() {
                    // Show loading state
                    $('#employeeHistoryTable tbody').html('<tr><td colspan="7" class="text-center">جاوەڕێ بکە...</td></tr>');
                },
                success: function(response) {
                    console.log("Response:", response);
                    if (response.success) {
                        // Update table with filtered data
                        updateEmployeePaymentsTable(response.data);
                        updateEmployeePaymentsStats(response.stats);
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", xhr.responseText);
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            });
        }

        // Update employee payments table
        function updateEmployeePaymentsTable(data) {
            let html = '';
            
            if (data.length === 0) {
                html = '<tr><td colspan="7" class="text-center">هیچ پارەدانێک نەدۆزرایەوە</td></tr>';
            } else {
                data.forEach(function(payment, index) {
                    const paymentTypeClass = payment.payment_type === 'salary' ? 'bg-success' : 
                                           (payment.payment_type === 'bonus' ? 'bg-warning' : 'bg-info');
                    
                    const paymentTypeText = payment.payment_type === 'salary' ? 'مووچە' : 
                                          (payment.payment_type === 'bonus' ? 'پاداشت' : 'کاتژمێری زیادە');
                    
                    // Format date to Y/m/d
                    const dateObj = new Date(payment.payment_date);
                    const formattedDate = dateObj.getFullYear() + '/' + 
                                         String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                         String(dateObj.getDate()).padStart(2, '0');
                    
                    html += `
                        <tr data-id="${payment.id}">
                            <td>${index + 1}</td>
                            <td>${payment.employee_name || 'N/A'}</td>
                            <td>${formattedDate}</td>
                            <td>${payment.amount ? new Intl.NumberFormat().format(payment.amount) + ' د.ع' : '0 د.ع'}</td>
                            <td>
                                <span class="badge rounded-pill ${paymentTypeClass}">
                                    ${paymentTypeText}
                                </span>
                            </td>
                            <td>${payment.notes || ''}</td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${payment.id}" data-bs-toggle="modal" data-bs-target="#editEmployeePaymentModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${payment.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }
            
            $('#employeeHistoryTable tbody').html(html);
            
            // Update pagination info
            updatePaginationInfo('employee', data.length, 1, data.length, data.length);
        }

        // Update employee payments stats
        function updateEmployeePaymentsStats(stats) {
            $('.card-title:contains("کۆی پارەدان")').next().text(new Intl.NumberFormat().format(stats.total_amount || 0) + ' د.ع');
            $('.card-title:contains("کۆی پارەدان")').next().next().text((stats.total_payments || 0) + ' پارەدان');
            
            $('.card-title:contains("مووچە")').next().text(new Intl.NumberFormat().format(stats.total_salary || 0) + ' د.ع');
            $('.card-title:contains("پاداشت")').next().text(new Intl.NumberFormat().format(stats.total_bonus || 0) + ' د.ع');
            $('.card-title:contains("کاتژمێری زیادە")').next().text(new Intl.NumberFormat().format(stats.total_overtime || 0) + ' د.ع');
        }

        // Reset employee payment filter
        function resetEmployeePaymentFilter() {
            // Reset form inputs
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            $('#employeePaymentStartDate').val(formatDate(firstDay));
            $('#employeePaymentEndDate').val(formatDate(today));
            $('#employeePaymentName').val('');
            
            // Apply filter with reset values
            applyEmployeePaymentFilter();
        }

        // Apply filter for withdrawals
        function applyWithdrawalFilter() {
            const startDate = $('#withdrawalStartDate').val();
            const endDate = $('#withdrawalEndDate').val();
            
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    action: 'filter',
                    type: 'withdrawals',
                    start_date: startDate,
                    end_date: endDate
                },
                dataType: 'json',
                beforeSend: function() {
                    // Show loading state
                    $('#withdrawalHistoryTable tbody').html('<tr><td colspan="5" class="text-center">جاوەڕێ بکە...</td></tr>');
                },
                success: function(response) {
                    console.log("Withdrawal Response:", response);
                    if (response.success) {
                        // Update table with filtered data
                        updateWithdrawalsTable(response.data);
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", xhr.responseText);
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            });
        }

        // Update withdrawals table
        function updateWithdrawalsTable(data) {
            let html = '';
            
            if (data.length === 0) {
                html = '<tr><td colspan="5" class="text-center">هیچ دەرکردنێکی پارە نەدۆزرایەوە</td></tr>';
            } else {
                data.forEach(function(withdrawal, index) {
                    // Format date to Y/m/d
                    const dateObj = new Date(withdrawal.expense_date);
                    const formattedDate = dateObj.getFullYear() + '/' + 
                                         String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                         String(dateObj.getDate()).padStart(2, '0');
                    
                    html += `
                        <tr data-id="${withdrawal.id}">
                            <td>${index + 1}</td>
                            <td>${formattedDate}</td>
                            <td>${withdrawal.amount ? new Intl.NumberFormat().format(withdrawal.amount) + ' د.ع' : '0 د.ع'}</td>
                            <td>${withdrawal.notes || ''}</td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${withdrawal.id}" data-bs-toggle="modal" data-bs-target="#editWithdrawalModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${withdrawal.id}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${withdrawal.id}">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }
            
            $('#withdrawalHistoryTable tbody').html(html);
            
            // Update pagination info
            updatePaginationInfo('withdrawal', data.length, 1, data.length, data.length);
        }

        // Reset withdrawal filter
        function resetWithdrawalFilter() {
            // Reset form inputs
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            $('#withdrawalStartDate').val(formatDate(firstDay));
            $('#withdrawalEndDate').val(formatDate(today));
            
            // Apply filter with reset values
            applyWithdrawalFilter();
        }

        // Helper function to format date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Update pagination info
        function updatePaginationInfo(prefix, totalRecords, currentPage, recordsPerPage, filteredRecords) {
            const startRecord = totalRecords > 0 ? (currentPage - 1) * recordsPerPage + 1 : 0;
            const endRecord = Math.min(startRecord + recordsPerPage - 1, filteredRecords);
            
            $(`#${prefix}StartRecord`).text(startRecord);
            $(`#${prefix}EndRecord`).text(endRecord);
            $(`#${prefix}TotalRecords`).text(filteredRecords);
        }

        // Delete employee payment
        $(document).on('click', '#employeeHistoryTable .delete-btn', function() {
            const paymentId = $(this).data('id');
            
            Swal.fire({
                title: 'دڵنیای؟',
                text: "ئەم کردارە ناگەڕێتەوە!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'بەڵێ، بیسڕەوە!',
                cancelButtonText: 'نەخێر، پاشگەزبوونەوە'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to delete the payment
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            action: 'delete_employee_payment',
                            id: paymentId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'سڕایەوە!',
                                    text: 'پارەدانەکە بە سەرکەوتوویی سڕایەوە.',
                                    icon: 'success',
                                    confirmButtonText: 'باشە'
                                });
                                
                                // Refresh the employee payments table
                                applyEmployeePaymentFilter();
                            } else {
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: response.message || 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا',
                                    icon: 'error',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    });
                }
            });
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
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
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
                                <input type="number" id="editEmployeePaymentAmount" class="form-control" required>
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