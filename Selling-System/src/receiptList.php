<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی پسوڵەکان</title>
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
    <link rel="stylesheet" href="css/receiptList.css">
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
                    <h4 class="mb-0">لیستی پسوڵەکان</h4>
                    <div class="d-flex">
                        <a href="addReceipt.php" class="btn btn-primary ms-2">
                            <i class="fas fa-plus-circle"></i> زیادکردنی پسوڵە
                        </a>
                        <button class="btn btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mt-4" id="receiptTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="selling-tab" data-bs-toggle="tab" data-bs-target="#selling" type="button" role="tab" aria-controls="selling" aria-selected="true">
                        فرۆشتن
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="buying-tab" data-bs-toggle="tab" data-bs-target="#buying" type="button" role="tab" aria-controls="buying" aria-selected="false">
                        کڕین
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="wasting-tab" data-bs-toggle="tab" data-bs-target="#wasting" type="button" role="tab" aria-controls="wasting" aria-selected="false">
                        ڕێکخستنەوە
                    </button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content mt-3" id="receiptTabsContent">
                <!-- Selling Receipts Tab -->
                <div class="tab-pane fade show active" id="selling" role="tabpanel" aria-labelledby="selling-tab">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">پسوڵەکانی فرۆشتن</h5>
                                <div class="filters">
                                    <div class="row">
                                        <div class="col-md-4 col-sm-12 mb-2">
                                            <input type="text" class="form-control" id="sellingSearchBox" placeholder="گەڕان...">
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <input type="date" class="form-control" id="sellingStartDate" placeholder="بەرواری دەستپێک">
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <input type="date" class="form-control" id="sellingEndDate" placeholder="بەرواری کۆتایی">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover dt-responsive nowrap" id="sellingTable" width="100%">
                                    <thead>
                                        <tr>
                                            <th>ژمارەی پسوڵە</th>
                                            <th>ناونیشان</th>
                                            <th>کڕیار</th>
                                            <th>بەروار</th>
                                            <th>کۆی گشتی</th>
                                            <th>دۆخ</th>
                                            <th>کردار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buying Receipts Tab -->
                <div class="tab-pane fade" id="buying" role="tabpanel" aria-labelledby="buying-tab">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">پسوڵەکانی کڕین</h5>
                                <div class="filters">
                                    <div class="row">
                                        <div class="col-md-4 col-sm-12 mb-2">
                                            <input type="text" class="form-control" id="buyingSearchBox" placeholder="گەڕان...">
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <input type="date" class="form-control" id="buyingStartDate" placeholder="بەرواری دەستپێک">
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <input type="date" class="form-control" id="buyingEndDate" placeholder="بەرواری کۆتایی">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover dt-responsive nowrap" id="buyingTable" width="100%">
                                    <thead>
                                        <tr>
                                            <th>ژمارەی پسوڵە</th>
                                            <th>ناونیشان</th>
                                            <th>فرۆشیار</th>
                                            <th>بەروار</th>
                                            <th>ژمارەی پسوڵەی فرۆشیار</th>
                                            <th>کۆی گشتی</th>
                                            <th>دۆخ</th>
                                            <th>کردار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wasting/Adjustment Receipts Tab -->
                <div class="tab-pane fade" id="wasting" role="tabpanel" aria-labelledby="wasting-tab">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">پسوڵەکانی ڕێکخستنەوە</h5>
                                <div class="filters">
                                    <div class="row">
                                        <div class="col-md-4 col-sm-12 mb-2">
                                            <input type="text" class="form-control" id="wastingSearchBox" placeholder="گەڕان...">
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <input type="date" class="form-control" id="wastingStartDate" placeholder="بەرواری دەستپێک">
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <input type="date" class="form-control" id="wastingEndDate" placeholder="بەرواری کۆتایی">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover dt-responsive nowrap" id="wastingTable" width="100%">
                                    <thead>
                                        <tr>
                                            <th>ژمارەی پسوڵە</th>
                                            <th>ناونیشان</th>
                                            <th>بەرپرسیار</th>
                                            <th>بەروار</th>
                                            <th>هۆکار</th>
                                            <th>کۆی زیان</th>
                                            <th>دۆخ</th>
                                            <th>کردار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Receipt Details -->
    <div class="modal fade" id="receiptDetailsModal" tabindex="-1" aria-labelledby="receiptDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptDetailsModalLabel">پسوڵەی ژمارە: <span id="modalReceiptNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="receipt-details-container">
                        <!-- Receipt Header Info -->
                        <div class="row mb-3">
                            <div class="col-md-4 col-sm-6">
                                <p><strong>ناونیشان:</strong> <span id="modalReceiptTitle"></span></p>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <p><strong>بەروار:</strong> <span id="modalReceiptDate"></span></p>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <p><strong>کڕیار/فرۆشیار:</strong> <span id="modalReceiptParty"></span></p>
                            </div>
                        </div>

                        <!-- Receipt Items -->
                        <h6>کاڵاکانی پسوڵە</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="modalReceiptItems">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>کاڵا</th>
                                        <th>نرخی یەکە</th>
                                        <th>بڕی یەکە</th>
                                        <th>کۆی گشتی</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Items will be loaded dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-start"><strong>کۆی گشتی:</strong></td>
                                        <td id="modalReceiptTotal"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Receipt Notes -->
                        <div class="mt-3">
                            <h6>تێبینیەکان</h6>
                            <p id="modalReceiptNotes"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" id="printReceiptBtn">چاپکردن</button>
                    <button type="button" class="btn btn-outline-secondary" id="editReceiptBtn">دەستکاریکردن</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Receipt Deletion Confirmation -->
    <div class="modal fade" id="deleteReceiptModal" tabindex="-1" aria-labelledby="deleteReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteReceiptModalLabel">سڕینەوەی پسوڵە</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>ئایا دڵنیایت لە سڕینەوەی پسوڵەی ژمارە: <span id="deleteReceiptNumber"></span>؟</p>
                    <p class="text-danger">ئاگادار بە: ئەم کردارە ناتوانرێت پاشگەز بکرێتەوە!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">سڕینەوە</button>
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
    <script src="js/receiptList.js"></script>
</body>
</html> 