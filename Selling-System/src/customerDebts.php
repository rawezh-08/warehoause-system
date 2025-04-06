<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قەرزی ئێمە لای خەڵک</title>
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
    <link rel="stylesheet" href="css/customerDebts.css">
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
                    <h4 class="mb-0">قەرزی ئێمە لای خەڵک</h4>
                    <div class="d-flex">
                        <button class="btn btn-primary ms-2" id="addDebtBtn">
                            <i class="fas fa-plus-circle"></i> زیادکردنی قەرز
                        </button>
                        <button class="btn btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" placeholder="گەڕان بە ناو، ژمارە تەلەفۆن...">
                                <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                    <i class="fas fa-search"></i> گەڕان
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="sortSelect">
                                <option value="name">ڕیزکردن بە ناو</option>
                                <option value="debt-high">زۆرترین قەرز</option>
                                <option value="debt-low">کەمترین قەرز</option>
                                <option value="date">بەروار</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Debt Cards Section -->
            <div class="customer-cards-container" id="customerCardsContainer">
                <!-- Cards will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Add Debt Modal -->
    <div class="modal fade" id="addDebtModal" tabindex="-1" aria-labelledby="addDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDebtModalLabel">زیادکردنی قەرز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDebtForm">
                        <div class="mb-3">
                            <label for="debtCustomerName" class="form-label">ناوی قەرزار</label>
                            <input type="text" class="form-control" id="debtCustomerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="debtCustomerPhone" class="form-label">ژمارە تەلەفۆن</label>
                            <input type="tel" class="form-control" id="debtCustomerPhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="debtCustomerAddress" class="form-label">ناونیشان</label>
                            <input type="text" class="form-control" id="debtCustomerAddress">
                        </div>
                        <div class="mb-3">
                            <label for="debtAmount" class="form-label">بڕی قەرز</label>
                            <input type="number" class="form-control" id="debtAmount" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="debtDueDate" class="form-label">بەرواری دانەوە</label>
                            <input type="date" class="form-control" id="debtDueDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="debtNote" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="debtNote" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="button" class="btn btn-primary" id="saveDebtBtn">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Details Modal -->
    <div class="modal fade" id="purchaseDetailsModal" tabindex="-1" aria-labelledby="purchaseDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchaseDetailsModalLabel">وردەکاری وەسڵی ژمارە: <span id="receiptNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="receipt-details mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>بەروار:</strong> <span id="receiptDate"></span></p>
                                <p><strong>کۆی گشتی:</strong> <span id="receiptTotal"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>باری پارەدان:</strong> <span id="receiptPaymentStatus"></span></p>
                                <p><strong>بڕی ماوە:</strong> <span id="receiptRemainingAmount"></span></p>
                            </div>
                        </div>
                    </div>
                    <h6>کاڵاکانی ناو وەسڵ</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="receiptItemsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>کاڵا</th>
                                    <th>نرخی یەکە</th>
                                    <th>بڕ</th>
                                    <th>کۆی گشتی</th>
                                </tr>
                            </thead>
                            <tbody id="receiptItemsTableBody">
                                <!-- Receipt items will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
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
    <script src="js/include-components.js"></script>
    <script src="js/customerDebts.js"></script>
</body>
</html> 