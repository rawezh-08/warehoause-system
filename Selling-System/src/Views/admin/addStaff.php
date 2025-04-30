<?php
// Include authentication check
require_once '../../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی هەژمارەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Global CSS -->
<link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/input.css">

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
                        <h3 class="page-title">زیادکردنی هەژمارەکان</h3>
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
                                <div class="card shadow-sm" style="border: 1px solid var(--blue-border-color); border-radius: 18px;">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">زانیاری کارمەند</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="employeeForm" action="../../process/add_employee.php" method="POST" class="needs-validation" novalidate>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label for="employeeName" class="form-label">ناوی کارمەند</label>
                                                    <input type="text" class="form-control" id="employeeName" name="employeeName" required>
                                                    <div class="invalid-feedback">
                                                        تکایە ناوی کارمەند داخل بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="employeePhone" class="form-label">ژمارەی مۆبایل</label>
                                                    <input type="tel" class="form-control" id="employeePhone" name="employeePhone" required>
                                                    <div class="invalid-feedback">
                                                        تکایە ژمارەی مۆبایل داخل بکە
                                                    </div>
                                                </div>
                                         
                                                <div class="col-md-4">
                                                    <label for="employeeSalary" class="form-label">مووچە</label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" id="employeeSalary" name="employeeSalary" required>
                                                        <span class="input-group-text">د.ع</span>
                                                        <div class="invalid-feedback">
                                                            تکایە مووچەی کارمەند داخل بکە
                                                        </div>
                                                    </div>
                                                </div>
                                              
                                                <div class="col-12">
                                                    <label for="employeeNotes" class="form-label">تێبینی</label>
                                                    <textarea class="form-control" id="employeeNotes" name="employeeNotes" rows="2"></textarea>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-outline-secondary me-2" id="resetEmployeeForm" style="border-radius: 24px;">
                                                    ڕیسێت <i class="fas fa-redo me-2"></i>
                                                    </button>
                                                    <button type="submit" class="btn btn-primary cta-btn">
                                                    پاشەکەوتکردن  <i class="fas fa-save me-2"></i>
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
                                <div class="card shadow-sm" style="border: 1px solid var(--blue-border-color); border-radius: 18px;">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">زانیاری کڕیار</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="customerForm" action="../../process/add_customer.php" method="POST" class="needs-validation" novalidate>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="businessMan" class="form-label">ناوی کڕیار</label>
                                                    <input type="text" class="form-control" id="businessMan" name="businessMan" placeholder="ناوی کڕیار" required>
                                                    <div class="invalid-feedback">
                                                        ناوی کڕیار داخڵ بکە
                                                    </div>
                                                </div>
                                              
                                                <div class="col-md-6">
                                                    <label for="phone1" class="form-label">ژمارەی مۆبایی یەکەم</label>
                                                    <input type="tel" class="form-control" id="phone1" name="phone1" required placeholder="07xxxxxxxxx" pattern="07[0-9]{9}">
                                                    <div class="invalid-feedback">
                                                        ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="phone2" class="form-label">ژمارەی مۆبایلی دووەم (اختیاری)</label>
                                                    <input type="tel" class="form-control" id="phone2" name="phone2" placeholder="07xxxxxxxxx" pattern="07[0-9]{9}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="guarantorName" class="form-label">ناوی کەفیل</label>
                                                    <input type="text" class="form-control" id="guarantorName" name="guarantorName" placeholder="ناوی کەفیل">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="guarantorPhone" class="form-label">ژمارەی مۆبایلی کەفیل</label>
                                                    <input type="tel" class="form-control" id="guarantorPhone" placeholder="07xxxxxxxxx" name="guarantorPhone" pattern="07[0-9]{9}">
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label for="debitOnBusiness" class="form-label">ئەو بڕەی کڕیار قەرزارە</label>
                                                    <div class="input-group">
                                                        <input type="text" id="debitOnBusiness" name="debitOnBusiness" class="form-control" placeholder="بڕی قەرز لە سەر کڕیار" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label for="debt_on_customer" class="form-label">بری پێشەکی کە کڕیار بە ئێمەی داوە</label>
                                                    <div class="input-group">
                                                        <input type="text" id="debt_on_customer" name="debt_on_customer" class="form-control" placeholder="بڕی پێشەکی" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="isBusinessPartner" name="isBusinessPartner">
                                                        <label class="form-check-label" for="isBusinessPartner">
                                                            ئەم کڕیارە دابینکەریشە
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="customerAddress" class="form-label">ناونیشان</label>
                                                    <textarea class="form-control" id="customerAddress" name="customerAddress" rows="2" placeholder="ناونیشانی کڕیار"></textarea>
                                                </div>
                                               
                                            

                                             
                                                
                                                <div class="col-6">
                                                    <label for="customerNotes" class="form-label">تێبینی</label>
                                                    <textarea class="form-control" id="customerNotes" name="customerNotes" rows="2" placeholder="تێبینی لەسەر کڕیار"></textarea>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-outline-secondary me-2" id="resetCustomerForm">
                                                        <i class="fas fa-redo me-2"></i>ڕیسێت
                                                    </button>
                                                    <button type="submit" class="btn btn-primary cta-btn">
                                                    پاشەکەوتکردن  <i class="fas fa-save me-2"></i>
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
                                <div class="card shadow-sm" style="border: 1px solid var(--blue-border-color); border-radius: 18px;">
                                    <div class="card-header bg-transparent">
                                        <h5 class="card-title mb-0">زانیاری دابینکەر</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="supplierForm" action="../../process/add_supplier.php" method="POST" class="needs-validation" novalidate>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="supplierName" class="form-label">ناوی دابینکەر</label>
                                                    <input type="text" class="form-control" id="supplierName" name="supplierName" placeholder="ناوی دابینکەر" required>
                                                    <div class="invalid-feedback">
                                                        تکایە ناوی دابینکەر داخل بکە
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="supplierPhone" class="form-label">ژمارەی مۆبایلی یەکەم</label>
                                                    <input type="tel" class="form-control" id="supplierPhone" name="supplierPhone" placeholder="07xxxxxxxxx" pattern="07[0-9]{9}" required>
                                                    <div class="invalid-feedback">
                                                        ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="supplierPhone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                                                    <input type="tel" class="form-control" id="supplierPhone2" name="supplierPhone2" placeholder="07xxxxxxxxx" pattern="07[0-9]{9}">
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label for="debt_on_myself" class="form-label">ئەو بڕە پارەی قەرزارم</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="debt_on_myself" name="debt_on_myself" placeholder=" بڕ بنووسە" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label for="debt_on_supplier" class="form-label">پارەی پێشەکی لە ئێمە داومانە بە دابینکەر</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="debt_on_supplier" name="debt_on_supplier" placeholder="بڕی پێشەکی" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="isBusinessPartnerSupplier" name="isBusinessPartnerSupplier">
                                                        <label class="form-check-label" for="isBusinessPartnerSupplier">
                                                            ئەم دابینکەرە کڕیاریشە
                                                        </label>
                                                    </div>
                                                </div>
                                              
                                                <div class="col-12">
                                                    <label for="supplierNotes" class="form-label">تێبینی</label>
                                                    <textarea class="form-control" id="supplierNotes" name="supplierNotes" rows="2" placeholder="تێبینی لەسەر دابینکەر"></textarea>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-outline-secondary me-2" id="resetSupplierForm">
                                                        <i class="fas fa-redo me-2"></i>ڕیسێت
                                                    </button>
                                                    <button type="submit" class="btn btn-primary cta-btn">
                                                    پاشەکەوتکردن  <i class="fas fa-save me-2"></i>
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
    <!-- Global AJAX Configuration -->
    <script src="../../js/ajax-config.js"></script>
    <!-- Custom JavaScript -->
    <script src="../../js/addStaff/script.js"></script>
    <script src="../../js/include-components.js"></script>
    
    <script>
        // Select the appropriate tab based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            // Get tab parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            // If tab parameter exists, activate the corresponding tab
            if (tabParam) {
                // Find the tab button
                const tabButton = document.getElementById(tabParam + '-tab');
                if (tabButton) {
                    // Create a new Bootstrap Tab instance and show it
                    const tab = new bootstrap.Tab(tabButton);
                    tab.show();
                }
            }

            // Handle business partner relationship
            const isBusinessPartnerCheckbox = document.getElementById('isBusinessPartner');
            const isBusinessPartnerSupplierCheckbox = document.getElementById('isBusinessPartnerSupplier');

            // When customer is marked as business partner
            isBusinessPartnerCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Show supplier tab
                    const supplierTab = document.getElementById('supplier-tab');
                    const supplierTabInstance = new bootstrap.Tab(supplierTab);
                    supplierTabInstance.show();
                    
                    // Pre-fill supplier form with customer data
                    const customerName = document.getElementById('businessMan').value;
                    const customerPhone = document.getElementById('phone1').value;
                    const customerPhone2 = document.getElementById('phone2').value;
                    
                    document.getElementById('supplierName').value = customerName;
                    document.getElementById('supplierPhone').value = customerPhone;
                    document.getElementById('supplierPhone2').value = customerPhone2;
                    
                    // Mark supplier as business partner
                    isBusinessPartnerSupplierCheckbox.checked = true;
                }
            });

            // When supplier is marked as business partner
            isBusinessPartnerSupplierCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Show customer tab
                    const customerTab = document.getElementById('customer-tab');
                    const customerTabInstance = new bootstrap.Tab(customerTab);
                    customerTabInstance.show();
                    
                    // Pre-fill customer form with supplier data
                    const supplierName = document.getElementById('supplierName').value;
                    const supplierPhone = document.getElementById('supplierPhone').value;
                    const supplierPhone2 = document.getElementById('supplierPhone2').value;
                    
                    document.getElementById('businessMan').value = supplierName;
                    document.getElementById('phone1').value = supplierPhone;
                    document.getElementById('phone2').value = supplierPhone2;
                    
                    // Mark customer as business partner
                    isBusinessPartnerCheckbox.checked = true;
                }
            });
        });
    </script>
   
</body>
</html> 