<?php
// Include database connection
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all customers
$query = "SELECT * FROM customers ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$totalCustomers = count($customers);
$totalDebt = 0;
$customersWithDebt = 0;

foreach ($customers as $customer) {
    if ($customer['debit_on_business'] > 0) {
        $totalDebt += $customer['debit_on_business'];
        $customersWithDebt++;
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کڕیارەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <!-- Page CSS -->
   <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/employeePayment/style.css">
    <link rel="stylesheet" href="css/staff.css">
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

        #customersTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #customersTable th {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .summary-card .card-value {
            font-size: 1.75rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        
        .summary-card .icon-bg {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
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
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3 class="page-title">لیستی کڕیارەکان</h3>
                        <a href="addStaff.php?tab=customer" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> زیادکردنی کڕیاری نوێ
                        </a>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-primary me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کۆی کڕیارەکان</h5>
                                    <p class="card-value"><?php echo number_format($totalCustomers); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-danger me-3">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کۆی قەرز</h5>
                                    <p class="card-value"><?php echo number_format($totalDebt); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-warning me-3">
                                    <i class="fas fa-user-tag"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کڕیارەکانی قەرزدار</h5>
                                    <p class="card-value"><?php echo number_format($customersWithDebt); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customers Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">هەموو کڕیارەکان</h5>
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
                                                        <select id="customersRecordsPerPage" class="form-select form-select-sm rounded-pill">
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
                                                        <input type="text" id="customersTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                        <table id="customersTable" class="table table-bordered custom-table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>ناوی کڕیار</th>
                                                    <th>ژمارەی مۆبایل</th>
                                                    <th>ناونیشان</th>
                                                    <th>دۆخی قەرز</th>
                                                    <th>بڕی قەرز</th>
                                                    <th>کردارەکان</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($customers) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($customers as $customer): ?>
                                                    <tr data-id="<?php echo $customer['id']; ?>">
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($customer['phone1']); ?></td>
                                                        <td><?php echo htmlspecialchars($customer['address'] ?? 'نادیار'); ?></td>
                                                        <td>
                                                            <?php if ($customer['debit_on_business'] > 0): ?>
                                                            <span class="badge rounded-pill bg-danger">قەرزدار</span>
                                                            <?php elseif ($customer['debit_on_business'] < 0): ?>
                                                            <span class="badge rounded-pill bg-success">پێشەکی</span>
                                                            <?php else: ?>
                                                            <span class="badge rounded-pill bg-secondary">هاوسەنگ</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($customer['debit_on_business'] > 0): ?>
                                                            <span class="text-danger"><?php echo number_format($customer['debit_on_business']); ?> دینار</span>
                                                            <?php elseif ($customer['debit_on_business'] < 0): ?>
                                                            <span class="text-success"><?php echo number_format(abs($customer['debit_on_business'])); ?> دینار پێشەکی</span>
                                                            <?php else: ?>
                                                            <span class="text-muted">0 دینار</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="customerProfile.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle">
                                                                    <i class="fas fa-user-circle"></i>
                                                                </a>
                                                                <a href="editCustomer.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-warning rounded-circle">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="<?php echo $customer['id']; ?>">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">هیچ کڕیارێک نەدۆزرایەوە</td>
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
                                                    نیشاندانی <span id="customersStartRecord">1</span> تا <span id="customersEndRecord"><?php echo min(count($customers), 10); ?></span> لە کۆی <span id="customersTotalRecords"><?php echo count($customers); ?></span> تۆمار
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pagination-controls d-flex justify-content-md-end">
                                                    <button id="customersPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="customersPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Pagination numbers will be generated by JavaScript -->
                                                        <button class="btn btn-sm btn-primary rounded-circle me-2 active">1</button>
                                                    </div>
                                                    <button id="customersNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
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
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCustomerModalLabel">دڵنیاکردنەوەی سڕینەوە</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>ئایا دڵنیایت لە سڕینەوەی ئەم کڕیارە؟</p>
                    <p class="text-danger"><strong>ئاگاداری:</strong> هەموو مامەڵەکانی ئەم کڕیارە بەتەواوی دەسڕێنەوە.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">سڕینەوە</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Custom Scripts -->
    <script src="js/include-components.js"></script>
    <!-- Page Specific Script -->
    <script>
        $(document).ready(function() {
            // Initialize pagination
            initTablePagination();
            
            // Table search functionality
            $('#customersTableSearch').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('#customersTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                updatePagination();
            });
            
            // Records per page change handler
            $('#customersRecordsPerPage').on('change', function() {
                updatePagination();
            });
            
            // Delete customer button handler
            $('.delete-btn').on('click', function() {
                const customerId = $(this).data('id');
                $('#confirmDeleteBtn').data('id', customerId);
                $('#deleteCustomerModal').modal('show');
            });
            
            // Confirm delete button handler
            $('#confirmDeleteBtn').on('click', function() {
                const customerId = $(this).data('id');
                
                $.ajax({
                    url: 'ajax/delete_customer.php',
                    type: 'POST',
                    data: { id: customerId },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            $('#deleteCustomerModal').modal('hide');
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
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
            
            // Refresh button handler
            $('.refresh-btn').on('click', function() {
                location.reload();
            });
            
            // Helper functions
            function initTablePagination() {
                updatePagination();
                
                // Pagination button handlers
                $('#customersPrevPageBtn').on('click', function() {
                    const currentPage = parseInt($('#customersPaginationNumbers .active').text());
                    if (currentPage > 1) {
                        goToPage(currentPage - 1);
                    }
                });
                
                $('#customersNextPageBtn').on('click', function() {
                    const currentPage = parseInt($('#customersPaginationNumbers .active').text());
                    const totalPages = Math.ceil($('#customersTotalRecords').text() / $('#customersRecordsPerPage').val());
                    if (currentPage < totalPages) {
                        goToPage(currentPage + 1);
                    }
                });
            }
            
            function updatePagination() {
                const recordsPerPage = parseInt($('#customersRecordsPerPage').val());
                const visibleRows = $('#customersTable tbody tr:visible').length;
                const totalPages = Math.ceil(visibleRows / recordsPerPage);
                
                // Update total records
                $('#customersTotalRecords').text(visibleRows);
                
                // Generate pagination numbers
                let paginationHtml = '';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `<button class="btn btn-sm ${i === 1 ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2 ${i === 1 ? 'active' : ''}" data-page="${i}">${i}</button>`;
                }
                $('#customersPaginationNumbers').html(paginationHtml);
                
                // Add click handlers for pagination numbers
                $('#customersPaginationNumbers button').on('click', function() {
                    const page = parseInt($(this).data('page'));
                    goToPage(page);
                });
                
                // Show only first page
                goToPage(1);
            }
            
            function goToPage(page) {
                const recordsPerPage = parseInt($('#customersRecordsPerPage').val());
                const visibleRows = $('#customersTable tbody tr:visible');
                
                // Hide all rows
                visibleRows.hide();
                
                // Show rows for current page
                const startIndex = (page - 1) * recordsPerPage;
                const endIndex = startIndex + recordsPerPage;
                
                visibleRows.slice(startIndex, endIndex).show();
                
                // Update pagination UI
                $('#customersPaginationNumbers button').removeClass('btn-primary active').addClass('btn-outline-primary');
                $('#customersPaginationNumbers button[data-page="' + page + '"]').removeClass('btn-outline-primary').addClass('btn-primary active');
                
                // Update pagination info
                const startRecord = visibleRows.length > 0 ? startIndex + 1 : 0;
                const endRecord = Math.min(endIndex, visibleRows.length);
                $('#customersStartRecord').text(startRecord);
                $('#customersEndRecord').text(endRecord);
                
                // Update prev/next buttons
                $('#customersPrevPageBtn').prop('disabled', page === 1);
                $('#customersNextPageBtn').prop('disabled', page === Math.ceil(visibleRows.length / recordsPerPage));
            }
        });
    </script>
</body>
</html> 