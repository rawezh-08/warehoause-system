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
                        <button class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#addDebtModal">
                            <i class="fas fa-plus-circle"></i> زیادکردنی قەرز
                        </button>
                        <button class="btn btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="card mt-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">لیستی قەرزەکان</h5>
                        <div class="filters">
                            <div class="row">
                                <div class="col-md-4 col-sm-12 mb-2">
                                    <input type="text" class="form-control" id="searchBox" placeholder="گەڕان...">
                                </div>
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <input type="date" class="form-control" id="startDate" placeholder="بەرواری دەستپێک">
                                </div>
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <input type="date" class="form-control" id="endDate" placeholder="بەرواری کۆتایی">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover dt-responsive nowrap" id="debtsTable" width="100%">
                            <thead>
                                <tr>
                                    <th>ناوی کڕیار</th>
                                    <th>ڕەقەم</th>
                                    <th>بەروار</th>
                                    <th>بڕی پارە</th>
                                    <th>بڕی پارەی دراو</th>
                                    <th>بڕی پارەی ماوە</th>
                                    <th>سنوری قەرز</th>
                                    <th>بەرواری دانەوە</th>
                                    <th>دۆخ</th>
                                    <th>کردار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample data -->
                                <tr>
                                    <td>ئەحمەد محەمەد</td>
                                    <td>D-1001</td>
                                    <td>2023-10-15</td>
                                    <td>1,500,000 د.ع</td>
                                    <td>500,000 د.ع</td>
                                    <td>1,000,000 د.ع</td>
                                    <td>2,000,000 د.ع</td>
                                    <td>2024-01-15</td>
                                    <td><span class="badge bg-warning">دواکەوتوو</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-info view-debt" data-id="1001" title="بینین">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success pay-debt" data-id="1001" title="پارەدان">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary edit-debt" data-id="1001" title="دەستکاریکردن">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>کارزان عومەر</td>
                                    <td>D-1002</td>
                                    <td>2023-11-20</td>
                                    <td>2,200,000 د.ع</td>
                                    <td>1,200,000 د.ع</td>
                                    <td>1,000,000 د.ع</td>
                                    <td>3,000,000 د.ع</td>
                                    <td>2024-02-20</td>
                                    <td><span class="badge bg-success">چالاک</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-info view-debt" data-id="1002" title="بینین">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success pay-debt" data-id="1002" title="پارەدان">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary edit-debt" data-id="1002" title="دەستکاریکردن">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>دلێر ڕەسوڵ</td>
                                    <td>D-1003</td>
                                    <td>2023-12-05</td>
                                    <td>3,500,000 د.ع</td>
                                    <td>2,500,000 د.ع</td>
                                    <td>1,000,000 د.ع</td>
                                    <td>5,000,000 د.ع</td>
                                    <td>2024-03-05</td>
                                    <td><span class="badge bg-success">چالاک</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-info view-debt" data-id="1003" title="بینین">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success pay-debt" data-id="1003" title="پارەدان">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary edit-debt" data-id="1003" title="دەستکاریکردن">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>سۆران ئەمین</td>
                                    <td>D-1004</td>
                                    <td>2024-01-10</td>
                                    <td>4,000,000 د.ع</td>
                                    <td>1,000,000 د.ع</td>
                                    <td>3,000,000 د.ع</td>
                                    <td>4,000,000 د.ع</td>
                                    <td>2024-04-10</td>
                                    <td><span class="badge bg-danger">سنور تێپەڕیوە</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-info view-debt" data-id="1004" title="بینین">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success pay-debt" data-id="1004" title="پارەدان">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary edit-debt" data-id="1004" title="دەستکاریکردن">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>نەوزاد عەلی</td>
                                    <td>D-1005</td>
                                    <td>2024-02-18</td>
                                    <td>1,800,000 د.ع</td>
                                    <td>800,000 د.ع</td>
                                    <td>1,000,000 د.ع</td>
                                    <td>3,000,000 د.ع</td>
                                    <td>2024-05-18</td>
                                    <td><span class="badge bg-success">چالاک</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-info view-debt" data-id="1005" title="بینین">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success pay-debt" data-id="1005" title="پارەدان">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary edit-debt" data-id="1005" title="دەستکاریکردن">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h6>کۆی گشتی قەرز</h6>
                                <p class="total-amount">7,000,000 د.ع</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h6>قەرزی دواکەوتوو</h6>
                                <p class="late-amount">1,000,000 د.ع</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h6>قەرزی سنور تێپەڕیوو</h6>
                                <p class="overdue-amount">3,000,000 د.ع</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Debt Modal -->
    <div class="modal fade" id="addDebtModal" tabindex="-1" aria-labelledby="addDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDebtModalLabel">زیادکردنی قەرز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDebtForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customerSelect" class="form-label">کڕیار</label>
                                <select class="form-select" id="customerSelect" required>
                                    <option value="" selected disabled>کڕیار هەڵبژێرە</option>
                                    <option value="1">ئەحمەد محەمەد</option>
                                    <option value="2">کارزان عومەر</option>
                                    <option value="3">دلێر ڕەسوڵ</option>
                                    <option value="4">سۆران ئەمین</option>
                                    <option value="5">نەوزاد عەلی</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="receiptNumber" class="form-label">ژمارەی پسوڵە</label>
                                <input type="text" class="form-control" id="receiptNumber" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="debtDate" class="form-label">بەرواری قەرز</label>
                                <input type="date" class="form-control" id="debtDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="repaymentDate" class="form-label">بەرواری دانەوە</label>
                                <input type="date" class="form-control" id="repaymentDate" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="totalAmount" class="form-label">بڕی پارە</label>
                                <input type="number" class="form-control" id="totalAmount" required>
                            </div>
                            <div class="col-md-6">
                                <label for="paidAmount" class="form-label">بڕی پارەی دراو</label>
                                <input type="number" class="form-control" id="paidAmount" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="creditLimit" class="form-label">سنوری قەرز</label>
                                <input type="number" class="form-control" id="creditLimit" required>
                            </div>
                            <div class="col-md-6">
                                <label for="remainingAmount" class="form-label">بڕی پارەی ماوە</label>
                                <input type="number" class="form-control" id="remainingAmount" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="debtNotes" class="form-label">تێبینیەکان</label>
                            <textarea class="form-control" id="debtNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveDebtBtn">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Debt Modal -->
    <div class="modal fade" id="viewDebtModal" tabindex="-1" aria-labelledby="viewDebtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDebtModalLabel">وردەکاری قەرز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>ناوی کڕیار:</h6>
                            <p id="viewCustomerName">ئەحمەد محەمەد</p>
                        </div>
                        <div class="col-md-6">
                            <h6>ژمارەی پسوڵە:</h6>
                            <p id="viewReceiptNumber">D-1001</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>بەرواری قەرز:</h6>
                            <p id="viewDebtDate">2023-10-15</p>
                        </div>
                        <div class="col-md-6">
                            <h6>بەرواری دانەوە:</h6>
                            <p id="viewRepaymentDate">2024-01-15</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <h6>بڕی پارە:</h6>
                            <p id="viewTotalAmount">1,500,000 د.ع</p>
                        </div>
                        <div class="col-md-4">
                            <h6>بڕی پارەی دراو:</h6>
                            <p id="viewPaidAmount">500,000 د.ع</p>
                        </div>
                        <div class="col-md-4">
                            <h6>بڕی پارەی ماوە:</h6>
                            <p id="viewRemainingAmount">1,000,000 د.ع</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>سنوری قەرز:</h6>
                            <p id="viewCreditLimit">2,000,000 د.ع</p>
                        </div>
                        <div class="col-md-6">
                            <h6>دۆخ:</h6>
                            <p id="viewStatus"><span class="badge bg-warning">دواکەوتوو</span></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>تێبینیەکان:</h6>
                        <p id="viewNotes">ئەم قەرزە پەیوەندی بە پسوڵەی ژمارە R-2023/156 ەوە هەیە.</p>
                    </div>

                    <h6 class="border-top pt-3">مێژووی پارەدان</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>بەروار</th>
                                    <th>بڕی پارە</th>
                                    <th>شێوازی پارەدان</th>
                                    <th>تێبینی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2023-10-15</td>
                                    <td>500,000 د.ع</td>
                                    <td>نەختینە</td>
                                    <td>پارەی سەرەتا</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="makePaymentBtn">پارەدان</button>
                    <button type="button" class="btn btn-primary" id="printDebtDetailsBtn">چاپکردن</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">زیادکردنی پارەدان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <div class="mb-3">
                            <label for="paymentDate" class="form-label">بەروار</label>
                            <input type="date" class="form-control" id="paymentDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">بڕی پارە</label>
                            <input type="number" class="form-control" id="paymentAmount" required>
                        </div>
                        <div class="mb-3">
                            <label for="paymentMethod" class="form-label">شێوازی پارەدان</label>
                            <select class="form-select" id="paymentMethod" required>
                                <option value="cash">نەختینە</option>
                                <option value="bank">بانک</option>
                                <option value="check">چەک</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paymentNotes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="paymentNotes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
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
    <script src="js/include-components.js"></script>
    <script src="js/customerDebts.js"></script>
</body>
</html> 