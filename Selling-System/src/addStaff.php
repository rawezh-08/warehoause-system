<?php
// You can add PHP logic here if needed
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی هاوکارەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
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
                        <h3 class="page-title">زیادکردنی هاوکارەکان</h3>
                    </div>
                </div>

                <!-- Tabs navigation -->
                <div class="row mb-4">
                    <div class="col-12">
                        <ul class="nav nav-tabs expenses-tabs" id="staffTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="employee-tab" data-bs-toggle="tab" data-bs-target="#employee-content" type="button" role="tab" aria-controls="employee-content" aria-selected="true">
                                    <i class="fas fa-user-tie me-2"></i>زیادکردنی کارمەند
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer-content" type="button" role="tab" aria-controls="customer-content" aria-selected="false">
                                    <i class="fas fa-user me-2"></i>زیادکردنی کڕیار
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="supplier-tab" data-bs-toggle="tab" data-bs-target="#supplier-content" type="button" role="tab" aria-controls="supplier-content" aria-selected="false">
                                    <i class="fas fa-truck me-2"></i>زیادکردنی دابینکەر
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tabs content -->
                <div class="tab-content" id="staffTabsContent">
                    <!-- Employee Tab -->
                    <div class="tab-pane fade show active" id="employee-content" role="tabpanel" aria-labelledby="employee-tab">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">زانیاری کارمەند</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="employeeForm" class="needs-validation" novalidate>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="employeeName" class="form-label">ناوی کارمەند</label>
                                                    <input type="text" class="form-control" id="employeeName" name="employeeName" required>
                                                    <div class="invalid-feedback">
                                                        تکایە ناوی کارمەند داخل بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="employeePhone" class="form-label">ژمارەی مۆبایل</label>
                                                    <input type="tel" class="form-control" id="employeePhone" name="employeePhone" required>
                                                    <div class="invalid-feedback">
                                                        تکایە ژمارەی مۆبایل داخل بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="employeePosition" class="form-label">پۆست</label>
                                                    <input type="text" class="form-control" id="employeePosition" name="employeePosition">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="employeeSalary" class="form-label">مووچە</label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="employeeSalary" name="employeeSalary">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label for="employeeAddress" class="form-label">ناونیشان</label>
                                                    <textarea class="form-control" id="employeeAddress" name="employeeAddress" rows="2"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="employeeNotes" class="form-label">تێبینی</label>
                                                    <textarea class="form-control" id="employeeNotes" name="employeeNotes" rows="2"></textarea>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-outline-secondary me-2" id="resetEmployeeForm">
                                                        <i class="fas fa-redo me-2"></i>ڕیسێت
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>پاشەکەوتکردن
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Tab -->
                    <div class="tab-pane fade" id="customer-content" role="tabpanel" aria-labelledby="customer-tab">
                        <div class="row">
                            <div class="col-md-10 mx-auto">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">زانیاری بازرگان</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="customerForm" class="needs-validation" novalidate>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="businessMan" class="form-label">ناوی بازرگان</label>
                                                    <input type="text" class="form-control" id="businessMan" name="businessMan" required>
                                                    <div class="invalid-feedback">
                                                        ناوی بازرگان داخڵ بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="businessManType" class="form-label">جۆری بازرگان</label>
                                                    <select class="form-select" id="businessManType" name="businessManType">
                                                        <option value="" selected disabled>هەڵبژێرە</option>
                                                        <option value="customer">کڕیار</option>
                                                        <option value="supplier">فرۆشیار</option>
                                                        <option value="both">هەردوکیان</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="phone1" class="form-label">ژمارەی مۆبایی یەکەم</label>
                                                    <input type="tel" class="form-control" id="phone1" name="phone1" required placeholder="07700000000">
                                                    <div class="invalid-feedback">
                                                        تکایە ژمارەی مۆبایلی یەکەم داخل بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="phone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                                                    <input type="tel" class="form-control" id="phone2" name="phone2" required placeholder="07700000000">
                                                   
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="guarantorName" class="form-label">ناوی کەفیل</label>
                                                    <input type="text" class="form-control" id="guarantorName" name="guarantorName">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="guarantorPhone" class="form-label">ژمارەی مۆبایلی کەفیل</label>
                                                    <input type="tel" class="form-control" id="guarantorPhone" placeholder="07700000000" name="guarantorPhone">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="customerAddress" class="form-label">ناونیشان</label>
                                                    <textarea class="form-control" id="customerAddress" name="customerAddress" rows="2"></textarea>
                                                </div>
                                               
                                               <label for="creditLimit" class="form-label">قەرزەکان</label>
                                               <hr>

                                               <div class="col-md-6 mb-3">
                                                    <label for="debitOnBusiness" class="form-label">قەرز بەسەر ئەو</label>
                                                    <div class="input-group">
                                                        <input type="text" id="debitOnBusiness" name="debitOnBusiness" class="form-control" placeholder="قەرز بەسەر ئەو بازرگانەی کە پارەدانەوە" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="debitOnMyself" class="form-label">قەرز بەسەر من</label>
                                                    <div class="input-group">
                                                        <input type="text" id="debitOnMyself" name="debitOnMyself" class="form-control" placeholder="قەرز بەسەر من" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <label for="customerNotes" class="form-label">تێبینی</label>
                                                    <textarea class="form-control" id="customerNotes" name="customerNotes" rows="2"></textarea>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-outline-secondary me-2" id="resetCustomerForm">
                                                        <i class="fas fa-redo me-2"></i>ڕیسێت
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>پاشەکەوتکردن
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Supplier Tab -->
                    <div class="tab-pane fade" id="supplier-content" role="tabpanel" aria-labelledby="supplier-tab">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">زانیاری دابینکەر</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="supplierForm" class="needs-validation" novalidate>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="supplierName" class="form-label">ناوی دابینکەر</label>
                                                    <input type="text" class="form-control" id="supplierName" name="supplierName" required>
                                                    <div class="invalid-feedback">
                                                        تکایە ناوی دابینکەر داخل بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="supplierPhone" class="form-label">ژمارەی پەیوەندی</label>
                                                    <input type="tel" class="form-control" id="supplierPhone" name="supplierPhone" required>
                                                    <div class="invalid-feedback">
                                                        تکایە ژمارەی پەیوەندی داخل بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="contactPerson" class="form-label">کەسی پەیوەندی</label>
                                                    <input type="text" class="form-control" id="contactPerson" name="contactPerson">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="supplierEmail" class="form-label">ئیمەیل</label>
                                                    <input type="email" class="form-control" id="supplierEmail" name="supplierEmail">
                                                </div>
                                                <div class="col-12">
                                                    <label for="supplierAddress" class="form-label">ناونیشان</label>
                                                    <textarea class="form-control" id="supplierAddress" name="supplierAddress" rows="2"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="supplierType" class="form-label">جۆری دابینکەر</label>
                                                    <select class="form-select" id="supplierType" name="supplierType">
                                                        <option value="" selected disabled>هەڵبژێرە</option>
                                                        <option value="manufacturer">بەرهەمهێنەر</option>
                                                        <option value="distributor">دابەشکەر</option>
                                                        <option value="wholesaler">فرۆشیاری کۆ</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="paymentTerms" class="form-label">مەرجەکانی پارەدان</label>
                                                    <select class="form-select" id="paymentTerms" name="paymentTerms">
                                                        <option value="" selected disabled>هەڵبژێرە</option>
                                                        <option value="immediate">دەستبەجێ</option>
                                                        <option value="15days">15 ڕۆژ</option>
                                                        <option value="30days">30 ڕۆژ</option>
                                                        <option value="60days">60 ڕۆژ</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label for="supplierNotes" class="form-label">تێبینی</label>
                                                    <textarea class="form-control" id="supplierNotes" name="supplierNotes" rows="2"></textarea>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-outline-secondary me-2" id="resetSupplierForm">
                                                        <i class="fas fa-redo me-2"></i>ڕیسێت
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>پاشەکەوتکردن
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
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
    <script src="js/include-components.js"></script>
    <script src="js/addStaff/script.js"></script>
</body>
</html> 