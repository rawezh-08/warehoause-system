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

    <!-- Customer Profile Modal -->
    <div class="modal fade" id="customerProfileModal" tabindex="-1" aria-labelledby="customerProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerProfileModalLabel">پڕۆفایلی قەرزار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Customer Info Section -->
                    <div class="customer-profile-header mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="customer-info">
                                    <h4 id="customerName">ناوی قەرزار</h4>
                                    <p><i class="fas fa-phone me-2"></i><span id="customerPhone">ژمارە تەلەفۆن</span></p>
                                    <p><i class="fas fa-map-marker-alt me-2"></i><span id="customerAddress">ناونیشان</span></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="debt-summary">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="debt-stat">
                                                <h6>بڕی پارەی گشتی</h6>
                                                <h4 id="totalAmount">0 $</h4>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="debt-stat">
                                                <h6>پارەی دراو</h6>
                                                <h4 id="paidAmount">0 $</h4>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="debt-stat">
                                                <h6>بڕی پارەی ماوە</h6>
                                                <h4 id="remainingAmount">0 $</h4>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="debt-stat">
                                                <h6>سنوری قەرز</h6>
                                                <h4 id="debtLimit">0 $</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <p><strong>بەرواری دانەوە:</strong> <span id="paymentDueDate">--/--/----</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="customerProfileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab">هەموو کڕینەکان</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">مێژووی پارەدانەکان</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="repayments-tab" data-bs-toggle="tab" data-bs-target="#repayments" type="button" role="tab">پارەدانەوە</button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content mt-3" id="customerProfileTabsContent">
                        <!-- Purchases Tab -->
                        <div class="tab-pane fade show active" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="purchasesTable">
                                    <thead>
                                        <tr>
                                            <th>ژمارەی وەسڵ</th>
                                            <th>بەروار</th>
                                            <th>کۆی گشتی</th>
                                            <th>باری پارەدان</th>
                                            <th>کردار</th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchasesTableBody">
                                        <!-- Purchases will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment History Tab -->
                        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="paymentsTable">
                                    <thead>
                                        <tr>
                                            <th>ژمارە</th>
                                            <th>بەروار</th>
                                            <th>بڕی پارە</th>
                                            <th>شێوازی پارەدان</th>
                                            <th>تێبینی</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentsTableBody">
                                        <!-- Payment history will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Repayments Tab -->
                        <div class="tab-pane fade" id="repayments" role="tabpanel" aria-labelledby="repayments-tab">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">زیادکردنی پارەدانەوە</h6>
                                    <form id="repaymentForm">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="repaymentAmount" class="form-label">بڕی پارە</label>
                                                <input type="number" class="form-control" id="repaymentAmount" min="0" step="0.01" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="repaymentDate" class="form-label">بەروار</label>
                                                <input type="date" class="form-control" id="repaymentDate" required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="repaymentMethod" class="form-label">شێوازی پارەدان</label>
                                                <select class="form-select" id="repaymentMethod" required>
                                                    <option value="cash">نەقد</option>
                                                    <option value="bank_transfer">گواستنەوەی بانکی</option>
                                                    <option value="check">چەک</option>
                                                    <option value="other">شێوازی تر</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="repaymentNote" class="form-label">تێبینی</label>
                                                <input type="text" class="form-control" id="repaymentNote">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">پاشەکەوتکردن</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                </div>
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