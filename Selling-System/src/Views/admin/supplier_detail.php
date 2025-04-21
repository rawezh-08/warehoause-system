<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زانیاری دابینکەر</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
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
        
        #transactionsTable td,
        th {
            white-space: normal;
            word-wrap: break-word;
            vertical-align: middle;
            padding: 0.75rem;
        }

        #transactionsTable td {

            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        #transactionsTable th {

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

        /* Table styles */
        .custom-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .custom-table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #4a5568;
            text-align: center;
            padding: 1rem;
            border: none;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        
        .custom-table tbody td {
            padding: 1rem;
            text-align: center;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
            vertical-align: middle;
            word-wrap: break-word;
            word-break: break-word;
            max-width: 200px;
        }
        
        .custom-table tbody tr:hover {
            background-color: #f7fafc;
            transition: all 0.2s ease;
        }
        
        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .action-buttons .btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-buttons .btn i {
            font-size: 0.875rem;
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .badge-payment {
            background-color: #3b82f6;
            color: white;
        }
        
        .badge-debt {
            background-color: #ef4444;
            color: white;
        }
        
        .badge-adjustment {
            background-color: #f59e0b;
            color: white;
        }
        
        .badge-effect-increase {
            background-color: #10b981;
            color: white;
        }
        
        .badge-effect-decrease {
            background-color: #ef4444;
            color: white;
        }
        
        .amount-positive {
            color: #10b981;
            font-weight: 600;
        }
        
        .amount-negative {
            color: #ef4444;
            font-weight: 600;
        }
        
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.375rem;
        }
        
        .pagination .page-item .page-link {
            position: relative;
            display: block;
            padding: 0.5rem 0.75rem;
            margin-left: -1px;
            line-height: 1.25;
            color: #3b82f6;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            font-size: 0.875rem;
        }
        
        .pagination .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #9ca3af;
            pointer-events: none;
            background-color: #fff;
            border-color: #e2e8f0;
        }
        
        .pagination-info {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        /* Table responsive styles */
        @media (max-width: 768px) {
            .custom-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .custom-table thead th {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .custom-table tbody td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }
        
        /* Fix for table header and data alignment */
        #transactionsTable th,
        #transactionsTable td {
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        #transactionsTable td:nth-child(6) {
            max-width: 200px;
            text-align: right;
        }
        
        /* Card and table container styles */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            background: #fff;
            overflow: hidden;
        }
        
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content" style="margin-top: 60px;">
        <div class="container">
            <!-- Navbar -->
            <div id="navbar-container"></div>

            <!-- Page Header -->
            <div class="top-nav">
                <!-- Sidebar container -->
                <div id="sidebar-container"></div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="bank.php">باڵانسی فرۆشیارەکان</a></li>
                                <li class="breadcrumb-item active" aria-current="page" id="supplier-name">زانیاری دابینکەر</li>
                            </ol>
                        </nav>
                        <h4 class="mb-0" id="supplier-title">زانیاری دابینکەر</h4>
                    </div>
                    <div class="d-flex">
                        <a href="bank.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button class="btn btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                </div>
            </div>

            <!-- Supplier Information -->
            <div class="card mb-4" id="supplier-info-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title">زانیاری دابینکەر</h5>
                            <div class="mb-2" id="supplier-name-display">
                                <strong>ناو:</strong> <span></span>
                            </div>
                            <div class="mb-2" id="supplier-phone1">
                                <strong>ژمارەی مۆبایل:</strong> <span></span>
                            </div>
                            <div class="mb-2" id="supplier-phone2">
                                <strong>ژمارەی مۆبایلی دووەم:</strong> <span></span>
                            </div>
                            <div class="mb-2" id="supplier-notes">
                                <strong>تێبینی:</strong> <span></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">باڵانسەکان</h5>
                            <div class="mb-2" id="debt-on-myself">
                                <strong>قەرزی ئێمە لایان:</strong> <span class="negative-balance"></span>
                            </div>
                            <div class="mb-2" id="debt-on-supplier">
                                <strong>قەرزی ئەوان لە ئێمە:</strong> <span class="positive-balance"></span>
                            </div>
                            <div class="mb-2" id="net-balance">
                                <strong>باڵانسی کۆتایی:</strong> <span></span>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-primary add-payment-btn" id="add-payment-btn">
                                    <i class="fas fa-money-bill-wave"></i> پارەدان
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label for="startDate" class="form-label">لە بەرواری</label>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="endDate" class="form-label">بۆ بەرواری</label>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="transactionType" class="form-label">جۆری مامەڵە</label>
                            <select class="form-select" id="transactionType">
                                <option value="">هەموو</option>
                                <option value="purchase">کڕین</option>
                                <option value="payment">پارەدان بە فرۆشیار</option>
                                <option value="return">گەڕاندنەوە بۆ فرۆشیار</option>
                                <option value="supplier_payment">وەرگرتنی پارە لە فرۆشیار</option>
                                <option value="supplier_return">گەڕاندنەوە لەلایەن فرۆشیار</option>
                                <option value="manual_adjustment">دەستکاری دەستی</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <button class="btn btn-primary w-100" id="filterBtn">
                                <i class="fas fa-filter"></i> فلتەرکردن
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Section -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">مێژووی مامەڵەکان</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary refresh-btn me-2">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="transactionsTable" class="table table-bordered custom-table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>بەروار</th>
                                    <th>جۆری مامەڵە</th>
                                    <th>بڕی پارە</th>
                                    <th>کاریگەری</th>
                                    <th>تێبینی</th>
                                    <th>کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Transactions will be loaded here -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="pagination-info"></div>
                                            <ul class="pagination mb-0"></ul>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">زیادکردنی پارەدان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPaymentForm">
                        <input type="hidden" id="supplierId">
                        <div class="mb-3">
                            <label for="supplierName" class="form-label">ناوی فرۆشیار</label>
                            <input type="text" class="form-control" id="supplierName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="currentBalance" class="form-label">باڵانسی ئێستا</label>
                            <input type="text" class="form-control" id="currentBalance" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">بڕی پارەدان</label>
                            <input type="number" class="form-control" id="paymentAmount" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="paymentDirection" class="form-label">جۆری پارەدان</label>
                            <select class="form-select" id="paymentDirection" required>
                                <option value="to_supplier">پارەدان بۆ فرۆشیار (ئێمە دەدەین بەوان)</option>
                                <option value="from_supplier">وەرگرتنی پارە لە فرۆشیار (ئەوان دەدەن بە ئێمە)</option>
                                <option value="adjust_balance">ڕێکخستنی باڵانس (دەستکاری دەستی)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paymentNote" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="paymentNote" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="button" class="btn btn-primary" id="savePaymentBtn">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../js/include-components.js"></script>
    <script src="../../js/supplier_detail.js"></script>
</body>
</html> 