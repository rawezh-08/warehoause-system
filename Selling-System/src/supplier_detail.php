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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <style>
        .positive-balance {
            color: #198754; /* Bootstrap success green */
            font-weight: bold;
        }
        
        .negative-balance {
            color: #dc3545; /* Bootstrap danger red */
            font-weight: bold;
        }
        
        .transaction-card {
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .transaction-card.purchase,
        .transaction-card.manual-increase-debt {
            border-left: 5px solid #dc3545;
        }
        
        .transaction-card.payment,
        .transaction-card.return,
        .transaction-card.manual-decrease-debt {
            border-left: 5px solid #6c757d;
        }
        
        .transaction-card.supplier-payment,
        .transaction-card.manual-increase-supplier-debt {
            border-left: 5px solid #198754;
        }
        
        .transaction-card.supplier-return,
        .transaction-card.manual-decrease-supplier-debt {
            border-left: 5px solid #6c757d;
        }
        
        .summary-box {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        
        .summary-box h5 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .transaction-detail {
            margin-bottom: 5px;
        }
        
        .transaction-amount {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .transaction-notes {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
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
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">مێژووی مامەڵەکان</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="transactionsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>بەروار</th>
                                    <th>جۆری مامەڵە</th>
                                    <th>بڕی پارە</th>
                                    <th>کاریگەری</th>
                                    <th>تێبینی</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTableBody">
                                <!-- Transactions will be loaded here -->
                            </tbody>
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
    <script src="./js/include-components.js"></script>
    <script src="js/supplier_detail.js"></script>
</body>
</html> 