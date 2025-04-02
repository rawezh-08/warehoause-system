<?php
// You can add PHP logic here if needed
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پارەدان بە کارمەند - سیستەمی بەڕێوەبردنی کۆگا</title>
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
</head>
<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>
            
        <!-- Main content -->
        <div class="main-content p-3" id="main-content">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="page-title">مەسروفات</h3>
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
                        <div class="row">
                            <!-- Left column - Payment form -->
                            <div class="col-lg-8 col-md-12 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">زیادکردنی پارەدان</h5>
                                        
                                        <form id="addEmployeePaymentForm">
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="employeeName" class="form-label">ناوی کارمەند</label>
                                                    <select id="employeeName" class="form-select" required>
                                                        <option value="" selected disabled>کارمەند هەڵبژێرە</option>
                                                        <!-- Options will be loaded dynamically from database -->
                                                        <option value="1">ئاری محمد</option>
                                                        <option value="2">شیلان عمر</option>
                                                        <option value="3">هاوڕێ ئەحمەد</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="paymentDate" class="form-label">بەروار</label>
                                                    <input type="date" id="paymentDate" class="form-control" required>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="paymentAmount" class="form-label">بڕی پارە</label>
                                                    <div class="input-group">
                                                        <input type="number" id="paymentAmount" class="form-control" placeholder="بڕی پارە" required>
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="paymentType" class="form-label">جۆری پارەدان</label>
                                                    <select id="paymentType" class="form-select">
                                                        <option value="" selected disabled>جۆری پارەدان</option>
                                                        <option value="salary">مووچە</option>
                                                        <option value="bonus">پاداشت</option>
                                                        <option value="advance">پێشەکی</option>
                                                        <option value="other">جۆری تر</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-12 mb-3">
                                                    <label for="paymentNotes" class="form-label">تێبینی</label>
                                                    <textarea id="paymentNotes" class="form-control" rows="3" placeholder="تێبینی لێرە بنووسە..."></textarea>
                                                </div>
                                            </div>
                                            
                                            <hr class="my-4">
                                            
                                            <div class="d-flex justify-content-end">
                                                <button type="submit" id="submitBtn" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> زیادکردنی پارەدان
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right column - Instructions -->
                            <div class="col-lg-4 col-md-12">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">ڕێنماییەکان</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="instructions">
                                            <div class="instruction-item mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>1</small>
                                                    </div>
                                                    <h6 class="mb-0">ناوی کارمەند</h6>
                                                </div>
                                                <p class="text-muted small mb-0">کارمەندی دیاریکراو هەڵبژێرە بۆ پارەدان.</p>
                                            </div>
                                            <div class="instruction-item mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>2</small>
                                                    </div>
                                                    <h6 class="mb-0">بەروار و بڕ</h6>
                                                </div>
                                                <p class="text-muted small mb-0">بەرواری پارەدان و بڕی پارە دیاری بکە.</p>
                                            </div>
                                            <div class="instruction-item">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>3</small>
                                                    </div>
                                                    <h6 class="mb-0">جۆر و تێبینی</h6>
                                                </div>
                                                <p class="text-muted small mb-0">جۆری پارەدان دیاری بکە و تێبینی پێویست بنووسە.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Cost Tab -->
                    <div class="tab-pane fade" id="shipping-content" role="tabpanel" aria-labelledby="shipping-tab">
                        <div class="row">
                            <!-- Left column - Shipping form -->
                            <div class="col-lg-8 col-md-12 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">زیادکردنی کرێی بار</h5>
                                        
                                        <form id="addShippingCostForm">
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="provider" class="form-label">دابینکەر</label>
                                                    <input type="text" id="provider" class="form-control" placeholder="ناوی دابینکەر" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="shippingDate" class="form-label">بەروار</label>
                                                    <input type="date" id="shippingDate" class="form-control" required>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="shippingAmount" class="form-label">بڕی پارە</label>
                                                    <div class="input-group">
                                                        <input type="number" id="shippingAmount" class="form-control" placeholder="بڕی پارە" required>
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="shippingType" class="form-label">جۆری بار</label>
                                                    <select id="shippingType" class="form-select">
                                                        <option value="" selected disabled>جۆری بار</option>
                                                        <option value="land">وشکانی</option>
                                                        <option value="sea">دەریایی</option>
                                                        <option value="air">ئاسمانی</option>
                                                        <option value="other">جۆری تر</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-12 mb-3">
                                                    <label for="shippingNotes" class="form-label">تێبینی</label>
                                                    <textarea id="shippingNotes" class="form-control" rows="3" placeholder="تێبینی لێرە بنووسە..."></textarea>
                                                </div>
                                            </div>
                                            
                                            <hr class="my-4">
                                            
                                            <div class="d-flex justify-content-end">
                                                <button type="submit" id="submitShippingBtn" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> زیادکردنی کرێی بار
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right column - Instructions -->
                            <div class="col-lg-4 col-md-12">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">ڕێنماییەکان</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="instructions">
                                            <div class="instruction-item mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>1</small>
                                                    </div>
                                                    <h6 class="mb-0">دابینکەر</h6>
                                                </div>
                                                <p class="text-muted small mb-0">ناوی دابینکەری بار دیاری بکە.</p>
                                            </div>
                                            <div class="instruction-item mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>2</small>
                                                    </div>
                                                    <h6 class="mb-0">بەروار و بڕ</h6>
                                                </div>
                                                <p class="text-muted small mb-0">بەرواری پارەدان و بڕی پارە دیاری بکە.</p>
                                            </div>
                                            <div class="instruction-item">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>3</small>
                                                    </div>
                                                    <h6 class="mb-0">جۆر و تێبینی</h6>
                                                </div>
                                                <p class="text-muted small mb-0">جۆری بار دیاری بکە و تێبینی پێویست بنووسە.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Money Withdrawal Tab -->
                    <div class="tab-pane fade" id="withdrawal-content" role="tabpanel" aria-labelledby="withdrawal-tab">
                        <div class="row">
                            <!-- Left column - Withdrawal form -->
                            <div class="col-lg-8 col-md-12 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">زیادکردنی دەرکردنی پارە</h5>
                                        
                                        <form id="addWithdrawalForm">
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="withdrawalName" class="form-label">ناو</label>
                                                    <input type="text" id="withdrawalName" class="form-control" placeholder="ناوی وەرگر" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="withdrawalDate" class="form-label">بەروار</label>
                                                    <input type="date" id="withdrawalDate" class="form-control" required>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="withdrawalAmount" class="form-label">بڕی پارە</label>
                                                    <div class="input-group">
                                                        <input type="number" id="withdrawalAmount" class="form-control" placeholder="بڕی پارە" required>
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="withdrawalCategory" class="form-label">جۆری دەرکردن</label>
                                                    <select id="withdrawalCategory" class="form-select">
                                                        <option value="" selected disabled>جۆر هەڵبژێرە</option>
                                                        <option value="expense">خەرجی ڕۆژانە</option>
                                                        <option value="supplies">پێداویستی</option>
                                                        <option value="rent">کرێ</option>
                                                        <option value="utility">خزمەتگوزاری</option>
                                                        <option value="other">جۆری تر</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-12 mb-3">
                                                    <label for="withdrawalNotes" class="form-label">تێبینی</label>
                                                    <textarea id="withdrawalNotes" class="form-control" rows="3" placeholder="تێبینی لێرە بنووسە..."></textarea>
                                                </div>
                                            </div>
                                            
                                            <hr class="my-4">
                                            
                                            <div class="d-flex justify-content-end">
                                                <button type="submit" id="submitWithdrawalBtn" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> زیادکردنی دەرکردنی پارە
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right column - Instructions -->
                            <div class="col-lg-4 col-md-12">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">ڕێنماییەکان</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="instructions">
                                            <div class="instruction-item mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>1</small>
                                                    </div>
                                                    <h6 class="mb-0">ناو</h6>
                                                </div>
                                                <p class="text-muted small mb-0">ناوی وەرگری پارە دیاری بکە.</p>
                                            </div>
                                            <div class="instruction-item mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>2</small>
                                                    </div>
                                                    <h6 class="mb-0">بەروار و بڕ</h6>
                                                </div>
                                                <p class="text-muted small mb-0">بەرواری پارەدان و بڕی پارە دیاری بکە.</p>
                                            </div>
                                            <div class="instruction-item">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <small>3</small>
                                                    </div>
                                                    <h6 class="mb-0">جۆر و تێبینی</h6>
                                                </div>
                                                <p class="text-muted small mb-0">جۆری دەرکردنی پارە دیاری بکە و تێبینی پێویست بنووسە.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Withdrawal History Table -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">مێژووی دەرکردنی پارە</h5>
                                        <button class="btn btn-sm btn-outline-primary refresh-btn">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
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
                                                                <input type="text" id="withdrawalTableSearch" class="form-control rounded-pill-start" placeholder="گەڕان لە تەیبڵدا...">
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
                                                <table id="withdrawalsTable" class="table custom-table table-hover">
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
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="1">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="1">
                                                                        <i class="fas fa-trash"></i>
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
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="2">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="2">
                                                                        <i class="fas fa-trash"></i>
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
                                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="3">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="3">
                                                                        <i class="fas fa-trash"></i>
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
    <!-- Custom JavaScript -->
    <script src="js/include-components.js"></script>
    <script src="js/employeePayment/script.js"></script>
</body>
</html> 