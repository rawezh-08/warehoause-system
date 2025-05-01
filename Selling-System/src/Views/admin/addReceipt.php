<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسوڵەکان</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
   <!-- Global CSS -->
   <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/addReceipt.css">
    <link rel="stylesheet" href="../../css/input.css">
</head>
<body>
    <!-- Top Navigation -->
    <div class="main-content">
   
        <div id="navbar-container"></div>

 
       
<!-- Sidebar container - will be populated by JavaScript -->
<div id="sidebar-container"></div>
    
<div class="d-flex justify-content-between align-items-center mt-5" style="background-color: transparent;">
                <?php include 'receipt_type_section.php'; ?>
            </div>
  

    <div class="container-fluid px-2">
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="receiptTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active receipt-tab" id="tab-selling-1" data-bs-toggle="tab" data-bs-target="#selling-1" type="button" role="tab">
                    فرۆشتن #1
                    <span class="close-tab"><i class="fas fa-times"></i></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="add-tab-btn" id="addNewTab" title="زیادکردنی پسوڵەی نوێ">
                    <i class="fas fa-plus-circle"></i>
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content " id="receiptTabsContent" >
            <!-- SELLING RECEIPT TEMPLATE -->
            <div class="tab-pane fade show active" id="selling-1" role="tabpanel" data-receipt-type="selling">
                <div class="receipt-container card-qiuck-style">
                    <!-- Header Section -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">ژمارەی پسوڵە</label>
                            <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە" readonly>
                            <div class="invalid-feedback">ئەم ژمارەی پسووڵەیە پێشتر بەکارهاتووە</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">کڕیار</label>
                            <select class="form-select customer-select">
                                <option value="">کڕیار هەڵبژێرە (ئارەزوومەندانە)</option>
                            </select>
                            <div class="customer-advance-info mt-2" style="display: none;">
                                <small class="text-muted">پارەی پێشەکی: <span class="advance-amount">0</span> دینار</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">جۆری پارەدان</label>
                            <select class="form-select payment-type">
                                <option value="cash">نەقد</option>
                                <option value="credit">قەرز</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="draft-indicator" style="display: none;">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-file-alt"></i> ئەم پسوڵە تەنیا بۆ نیشاندان و عەرز کردنە
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table - Moved to top -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 50px" class="tbl-header">#</th>
                                    <th class="tbl-header">کاڵا</th>
                                    <th class="tbl-header">وێنە</th>
                                    <th class="tbl-header">جۆری یەکە</th>
                                    <th class="tbl-header">نرخی یەکە</th>
                                    <th class="tbl-header">بڕی یەکە</th>
                                    <th class="tbl-header">کۆی گشتی</th>
                                    <th class="tbl-header" style="width: 100px">کردار</th>
                                </tr>
                            </thead>
                            <tbody class="items-list">
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <div class="d-flex">
                                            <select class="form-control product-select" style="width: 100%"></select>
                                            <button type="button" class="btn btn-sm btn-primary ms-2 quick-add-product" title="زیادکردنی کاڵای نوێ">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="product-image-cell"></td>
                                    <td>
                                        <select class="form-control unit-type">
                                            <option value="piece">دانە</option>
                                            <option value="box">کارتۆن</option>
                                            <option value="set">سێت</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control unit-price" step="1"></td>
                                    <td><input type="number" class="form-control quantity" min="1" step="1" data-check-stock="true"></td>
                                    <td><input type="number" class="form-control total" step="1" readonly></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-row">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm show-inventory-info" title="زانیاری کۆگا">
                                            <i class="fas fa-boxes"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Add Row Button -->
                    <button type="button" class="btn btn-link text-primary add-row-btn mb-4">
                        <i class="fas fa-plus"></i> زیادکردنی ڕیز
                    </button>

                    <!-- Credit Payment Fields (initially hidden) -->
                    <div class="row mb-4 credit-payment-fields" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label">بڕی پارەی دراو</label>
                            <input type="number" class="form-control paid-amount" value="0" min="0" step="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بڕی ماوە</label>
                            <input type="number" class="form-control remaining-amount" value="0" step="1" readonly>
                        </div>
                    </div>

                    <!-- Additional Sale Fields -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">جۆری نرخ</label>
                            <select class="form-select price-type">
                                <option value="single">دانە</option>
                                <option value="wholesale">کۆ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تێچووی گواستنەوە</label>
                            <input type="number" class="form-control shipping-cost" value="0" step="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تێچووی تر</label>
                            <input type="number" class="form-control other-cost" value="0" step="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">بەروار</label>
                            <input type="date" class="form-control sale-date">
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea class="form-control notes" rows="2" placeholder="تێبینی"></textarea>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                

                    <!-- Totals Section -->
                    <div class="total-section">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="total-label">کۆ</label>
                                <input type="number" class="form-control subtotal" step="1" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="total-label">داشکاندن</label>
                                <input type="number" class="form-control discount" value="0" step="1">
                            </div>
                            <div class="col-md-3">
                                <label class="total-label">تێچووی گواستنەوە</label>
                                <input type="number" class="form-control shipping-cost-total" step="1" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="total-label">کۆی گشتی</label>
                                <div class="input-group">
                                    <input type="number" class="form-control grand-total" step="1" readonly>
                                    <button type="button" class="btn btn-outline-primary round-total" title="خڕکردنەوەی بڕی پارە">
                                        <i class="fas fa-calculator"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-4 text-start d-flex justify-content-start align-items-center">
                        <button type="button" class="btn btn-outline-primary draft-btn">
                            <i class="fas fa-file-alt"></i> ڕەشنووس
                        </button>
                        <button type="button" class="btn btn-outline-success delivery-btn ms-2">
                            <i class="fas fa-truck"></i> پسووڵەی گەیاندن
                        </button>
                     
                        
                        <button type="button" class="btn btn-primary save-btn cta-btn ms-2">
                            پاشەکەوتکردن <i class="fas fa-save"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template for Buying Receipt Tab (Hidden) -->
    <div id="buying-template" class="d-none">
        <div class="receipt-container card-qiuck-style">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ژمارەی پسوڵە</label>
                    <input type="text" class="form-control receipt-number" readonly placeholder="ژمارەی پسوڵە">
                </div>
                <div class="col-md-4">
                    <label class="form-label">فرۆشیار</label>
                    <select class="form-select supplier-select" style="width: 100%">
                        <option value="" selected disabled>فرۆشیار هەڵبژێرە</option>
                    </select>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">جۆری پارەدان</label>
                    <select class="form-select payment-type">
                        <option value="cash">نەقد</option>
                        <option value="credit">قەرز</option>
                    </select>
                </div>
            </div>

            <!-- Items Table - Moved to top -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px" class="tbl-header">#</th>
                            <th class="tbl-header">کاڵا</th>
                            <th class="tbl-header">وێنە</th>
                            <th class="tbl-header">جۆری یەکە</th>
                            <th class="tbl-header">نرخی یەکە</th>
                            <th class="tbl-header">بڕی یەکە</th>
                            <th class="tbl-header">کۆی گشتی</th>
                            <th class="tbl-header" style="width: 100px">کردار</th>
                        </tr>
                    </thead>
                    <tbody class="items-list">
                        <tr>
                            <td>1</td>
                            <td><select class="form-control product-select" style="width: 100%"></select></td>
                            <td class="product-image-cell"></td>
                            <td>
                                <select class="form-control unit-type">
                                    <option value="piece">دانە</option>
                                    <option value="box">کارتۆن</option>
                                    <option value="set">سێت</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control unit-price" min="0" step="1"></td>
                            <td><input type="number" class="form-control quantity" min="0" step="1"></td>
                            <td><input type="number" class="form-control total" step="1" readonly></td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm show-inventory-info" title="زانیاری کۆگا">
                                    <i class="fas fa-boxes"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add Row Button -->
            <button type="button" class="btn btn-link text-primary add-row-btn mb-4">
                <i class="fas fa-plus"></i> زیادکردنی ڕیز
            </button>

            <!-- Credit Payment Fields (initially hidden) -->
            <div class="row mb-4 credit-payment-fields" style="display: none;">
                <div class="col-md-6">
                    <label class="form-label">بڕی پارەی دراو</label>
                    <input type="number" class="form-control paid-amount" value="0" min="0" step="1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">بڕی ماوە</label>
                    <input type="number" class="form-control remaining-amount" value="0" step="1" readonly>
                </div>
            </div>

            <!-- Additional Purchase Fields -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">کرێی بار</label>
                    <div class="input-group">
                        <input type="number" class="form-control shipping-cost" value="0" min="0" step="1">
                        <span class="input-group-text">دینار</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">تێچووی تر</label>
                    <div class="input-group">
                        <input type="number" class="form-control other-cost" value="0" min="0" step="1">
                        <span class="input-group-text">دینار</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">بەروار</label>
                    <input type="date" class="form-control purchase-date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">تێبینی</label>
                    <textarea class="form-control notes" rows="1" placeholder="تێبینی"></textarea>
                </div>
            </div>

           
            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="total-label">کۆ</label>
                        <input type="number" class="form-control subtotal" step="1" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">داشکاندن</label>
                        <input type="number" class="form-control discount" value="0" min="0" step="1">
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">کرێی بار</label>
                        <input type="number" class="form-control shipping-cost-total" step="1" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">کۆی گشتی</label>
                        <input type="number" class="form-control grand-total" step="1" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button --> 
            <div class="mt-4 text-star  d-flex justify-content-start align-items-center">
                <button type="button" class="btn btn-primary save-btn cta-btn">
                    پاشەکەوتکردن   <i class="fas fa-save"></i> 
                </button>
            </div>
        </div>
    </div>

    <!-- Template for Wasting Receipt Tab (Hidden) -->
    <div id="wasting-template" class="d-none">
        <div class="receipt-container card-qiuck-style">
            <!-- Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>کاڵا</th>
                            <th>وێنە</th>
                            <th>جۆری یەکە</th>
                            <th>بڕی بەردەست</th>
                            <th>بڕی بەفیڕۆچوو</th>
                            <th>نرخی یەکە</th>
                            <th>کۆی گشتی</th>
                            <th style="width: 100px">کردار</th>
                        </tr>
                    </thead>
                    <tbody class="items-list">
                        <tr>
                            <td>1</td>
                            <td><select class="form-control product-select" style="width: 100%"></select></td>
                            <td class="product-image-cell"></td>
                            <td>
                                <select class="form-control unit-type">
                                    <option value="piece">دانە</option>
                                    <option value="box">کارتۆن</option>
                                    <option value="set">سێت</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control current-quantity" step="1" readonly></td>
                            <td><input type="number" class="form-control adjusted-quantity" step="1"></td>
                            <td><input type="number" class="form-control price" step="1"></td>
                            <td><input type="number" class="form-control total" step="1" readonly></td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add Row Button -->
            <button type="button" class="btn btn-link text-primary add-row-btn mb-4">
                <i class="fas fa-plus"></i> زیادکردنی ڕیز
            </button>

            <!-- Date Info -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label">بەروار</label>
                    <input type="date" class="form-control adjustment-date">
                </div>
            </div>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-12">
                        <label class="total-label">کۆی گشتی زیان</label>
                        <input type="number" class="form-control grand-total" step="1" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start  d-flex justify-content-start align-items-center">
                <button type="button" class="btn btn-primary save-btn">
                    <i class="fas fa-save"></i> پاشەکەوتکردن
                </button>
            </div>
        </div>
    </div>

    <!-- Template for Selling Receipt Tab (Hidden) -->
    <div id="selling-template" class="d-none">
        <div class="receipt-container card-qiuck-style">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ژمارەی پسوڵە</label>
                    <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە" readonly>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">کڕیار هەڵبژێرە (ئارەزوومەندانە)</label>
                    <select class="form-select customer-select">
                        <option value="" selected disabled>کڕیار هەڵبژێرە (ئارەزوومەندانە)</option>
                    </select>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">جۆری پارەدان</label>
                    <select class="form-select payment-type">
                        <option value="cash">پارە</option>
                        <option value="credit">قەرز</option>
                    </select>
                </div>
            </div>

            <!-- Credit Payment Fields (initially hidden) -->
            <div class="row mb-4 credit-payment-fields" style="display: none;">
                <div class="col-md-6">
                    <label class="form-label">بڕی پارەی دراو</label>
                    <input type="number" class="form-control paid-amount" value="0" min="0" step="1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">بڕی ماوە</label>
                    <input type="number" class="form-control remaining-amount" value="0" step="1" readonly>
                </div>
            </div>

            <!-- Items Table - Moved to top -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>کاڵا</th>
                            <th>وێنە</th>
                            <th>جۆری یەکە</th>
                            <th>نرخی یەکە</th>
                            <th>بڕی یەکە</th>
                            <th>کۆی گشتی</th>
                            <th style="width: 100px">کردار</th>
                        </tr>
                    </thead>
                    <tbody class="items-list">
                        <tr>
                            <td>1</td>
                            <td><select class="form-control product-select" style="width: 100%"></select></td>
                            <td class="product-image-cell"></td>
                            <td>
                                <select class="form-control unit-type">
                                    <option value="piece">دانە</option>
                                    <option value="box">کارتۆن</option>
                                    <option value="set">سێت</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control unit-price" step="1"></td>
                            <td><input type="number" class="form-control quantity" min="1" step="1" data-check-stock="true"></td>
                            <td><input type="number" class="form-control total" step="1" readonly></td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm show-inventory-info" title="زانیاری کۆگا">
                                    <i class="fas fa-boxes"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add Row Button -->
            <button type="button" class="btn btn-link text-primary add-row-btn mb-4">
                <i class="fas fa-plus"></i> زیادکردنی ڕیز
            </button>

            <!-- Additional Sale Fields -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">جۆری نرخ</label>
                    <select class="form-select price-type">
                        <option value="single">دانە</option>
                        <option value="wholesale">کۆمەڵ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">تێچووی گواستنەوە</label>
                    <input type="number" class="form-control shipping-cost" value="0" step="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">تێچووی تر</label>
                    <input type="number" class="form-control other-cost" value="0" step="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">بەروار</label>
                    <input type="date" class="form-control sale-date">
                </div>
            </div>

            <!-- Notes Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <label class="form-label">تێبینی</label>
                    <textarea class="form-control notes" rows="2" placeholder="تێبینی"></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
      

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="total-label">کۆ</label>
                        <input type="number" class="form-control subtotal" step="1" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">داشکاندن</label>
                        <input type="number" class="form-control discount" value="0" step="1">
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">تێچووی گواستنەوە</label>
                        <input type="number" class="form-control shipping-cost-total" step="1" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">کۆی گشتی</label>
                        <input type="number" class="form-control grand-total" step="1" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start  d-flex justify-content-start align-items-center">
                <button type="button" class="btn btn-outline-primary draft-btn">
                    <i class="fas fa-file-alt"></i> ڕەشنووس
                </button>
                <button type="button" class="btn btn-primary save-btn">
                    <i class="fas fa-save"></i> پاشەکەوتکردن
                </button>
            </div>
        </div>
    </div>

    <!-- Add this modal after the main content div -->
    <div class="modal fade" id="quickAddProductModal" tabindex="-1" role="dialog" aria-labelledby="quickAddProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddProductModalLabel">زیادکردنی کاڵای نوێ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickAddProductForm">
                        <div class="mb-3">
                            <label for="productName" class="form-label">ناوی کاڵا</label>
                            <input type="text" class="form-control" id="productName" required>
                        </div>
                        <div class="mb-3">
                            <label for="productCategory" class="form-label">جۆری کاڵا</label>
                            <select class="form-select" id="productCategory" required>
                                <?php
                                $stmt = $conn->query("SELECT id, name FROM categories");
                                while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . $category['id'] . "'>" . $category['name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="productUnit" class="form-label">یەکەی کاڵا</label>
                            <select class="form-select" id="productUnit" required>
                                <?php
                                $stmt = $conn->query("SELECT id, name FROM units");
                                while ($unit = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . $unit['id'] . "'>" . $unit['name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="productQuantity" class="form-label">بڕی بەردەست</label>
                            <input type="number" class="form-control" id="productQuantity" min="1" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="productPurchasePrice" class="form-label">نرخی کڕین</label>
                            <input type="number" class="form-control" id="productPurchasePrice" required>
                        </div>
                        <div class="mb-3">
                            <label for="productSellingPrice" class="form-label">نرخی فرۆشتن</label>
                            <input type="number" class="form-control" id="productSellingPrice" required>
                        </div>
                        <div class="mb-3">
                            <label for="productWholesalePrice" class="form-label">نرخی کۆ</label>
                            <input type="number" class="form-control" id="productWholesalePrice" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveQuickProduct">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Receipt Modal -->
    <div class="modal fade" id="deliveryReceiptModal" tabindex="-1" role="dialog" aria-labelledby="deliveryReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deliveryReceiptModalLabel">پسووڵەی گەیاندن</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deliveryReceiptForm">
                        <div class="mb-3">
                            <label for="deliveryAddress" class="form-label">ناونیشانی گەیاندن</label>
                            <textarea class="form-control" id="deliveryAddress" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="phoneNumber" class="form-label">ژمارەی مۆبایل</label>
                            <input type="text" class="form-control" id="phoneNumber" required placeholder="07XX XXX XXXX">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-success" id="saveDeliveryReceipt">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Info Modal -->
    <div class="modal fade" id="inventoryInfoModal" tabindex="-1" role="dialog" aria-labelledby="inventoryInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inventoryInfoModalLabel">زانیاری بەردەستی کاڵا</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3 product-name-display fw-bold"></div>
                    <div class="inventory-cards">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><i class="fas fa-box"></i> دانە</h5>
                                        <h3 class="piece-count">0</h3>
                                        <p class="card-text text-muted piece-info">دانە بەردەستە</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><i class="fas fa-boxes"></i> کارتۆن</h5>
                                        <h3 class="box-count">0</h3>
                                        <p class="card-text text-muted box-info">کارتۆن بەردەستە</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><i class="fas fa-pallet"></i> سێت</h5>
                                        <h3 class="set-count">0</h3>
                                        <p class="card-text text-muted set-info">سێت بەردەستە</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">زانیاری زیاتر:</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>دانە لە کارتۆنێکدا:</span>
                                            <span class="pieces-per-box fw-bold">0</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>کارتۆن لە سێتێکدا:</span>
                                            <span class="boxes-per-set fw-bold">0</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>کۆی گشتی دانەکان:</span>
                                            <span class="total-pieces fw-bold">0</span>
                                        </li>
                                    </ul>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Inventory Info Script -->
    <script>
        $(document).ready(function() {
            // Handle show inventory info button click
            $(document).on('click', '.show-inventory-info', function() {
                const $row = $(this).closest('tr');
                const productId = $row.find('.product-select').val();
                const productName = $row.find('.product-select').text();
                
                if (!productId) {
                    Swal.fire({
                        title: 'تکایە کاڵا هەڵبژێرە',
                        text: 'هیچ کاڵایەک دیاری نەکراوە',
                        icon: 'warning',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }
                
                // Show loading in modal
                $('#inventoryInfoModal').modal('show');
                $('.product-name-display').text('چاوەڕێ بکە...');
                $('.piece-count, .box-count, .set-count, .pieces-per-box, .boxes-per-set, .total-pieces').text('...');
                
                // Get inventory info via AJAX
                $.ajax({
                    url: '../../api/get_product_inventory.php',
                    type: 'GET',
                    data: { product_id: productId },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            
                            // Update product name
                            $('.product-name-display').text(data.name);
                            
                            // Update inventory counts
                            $('.piece-count').text(data.piece_quantity || 0);
                            $('.box-count').text(data.box_quantity || 0);
                            $('.set-count').text(data.set_quantity || 0);
                            
                            // Update additional info
                            $('.pieces-per-box').text(data.pieces_per_box || 0);
                            $('.boxes-per-set').text(data.boxes_per_set || 0);
                            
                            // Calculate and display total pieces
                            const totalPieces = 
                                (data.piece_quantity || 0) + 
                                (data.box_quantity || 0) * (data.pieces_per_box || 0) + 
                                (data.set_quantity || 0) * (data.boxes_per_set || 0) * (data.pieces_per_box || 0);
                            
                            $('.total-pieces').text(totalPieces);
                            
                            // Update info text
                            $('.piece-info').text(data.piece_quantity == 1 ? 'دانە بەردەستە' : 'دانە بەردەستە');
                            $('.box-info').text(data.box_quantity == 1 ? 'کارتۆن بەردەستە' : 'کارتۆن بەردەستە');
                            $('.set-info').text(data.set_quantity == 1 ? 'سێت بەردەستە' : 'سێت بەردەستە');
                            
                        } else {
                            // Show error message in modal
                            $('.product-name-display').text('هەڵە ڕوویدا');
                            $('.piece-count, .box-count, .set-count, .pieces-per-box, .boxes-per-set, .total-pieces').text('0');
                        }
                    },
                    error: function() {
                        // Show error message in modal
                        $('.product-name-display').text('هەڵە ڕوویدا لە پەیوەندی بە سێرڤەرەوە');
                        $('.piece-count, .box-count, .set-count, .pieces-per-box, .boxes-per-set, .total-pieces').text('0');
                    }
                });
            });
        });
    </script>

    <!-- Test Script -->
    <script>
        $(document).ready(function() {
            // Function to generate receipt number
            function generateReceiptNumber() {
                // Get the highest receipt number from the server and increment it
                return $.ajax({
                    url: '../../api/get_next_receipt_number.php',
                    type: 'GET',
                    dataType: 'json',
                    async: false
                }).then(function(response) {
                    if (response && response.success && response.next_number) {
                        return response.next_number;
                    } else {
                        // Fallback to A-0001 if the API fails
                        return 'A-0001';
                    }
                }).catch(function() {
                    // Fallback to A-0001 if the API fails
                    return 'A-0001';
                });
            }

            // Function to check if receipt number exists
            function checkReceiptNumber(number) {
                return $.ajax({
                    url: '../../api/check_receipt_number.php',
                    type: 'POST',
                    data: { receipt_number: number },
                    dataType: 'json'
                });
            }

            // Function to set receipt number
            async function setReceiptNumber() {
                try {
                    const receiptNumber = await generateReceiptNumber();
                    $('.receipt-number').val(receiptNumber);
                } catch (error) {
                    console.error('Error generating receipt number:', error);
                    $('.receipt-number').val('A-0001'); // Fallback default
                    
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'نەتوانرا ژمارەی پسووڵەیەکی نوێ دروست بکرێت',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            }

            // Set receipt number when page loads
            setReceiptNumber();

            // Handle save button click
            $('.save-btn').on('click', function(e) {
                e.preventDefault();
                
                const receiptNumber = $('.receipt-number').val();
                if (!receiptNumber) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'ژمارەی پسووڵە پێویستە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Continue with saving the receipt...
            });

            // Handle draft button click
            $('.draft-btn').on('click', function(e) {
                e.preventDefault();
                
                // Prevent multiple submissions
                const saveButton = $(this);
                if (saveButton.prop('disabled')) {
                    return;
                }
                
                // Get current receipt data
                const currentTab = $('.tab-pane.active');
                const receiptData = {
                    receipt_type: 'selling',
                    is_draft: true,
                    invoice_number: currentTab.find('.receipt-number').val(),
                    customer_id: currentTab.find('.customer-select').val(),
                    date: currentTab.find('.sale-date').val(),
                    payment_type: currentTab.find('.payment-type').val(),
                    discount: currentTab.find('.discount').val() || 0,
                    paid_amount: currentTab.find('.paid-amount').val() || 0,
                    price_type: currentTab.find('.price-type').val(),
                    shipping_cost: currentTab.find('.shipping-cost').val() || 0,
                    other_cost: currentTab.find('.other-cost').val() || 0,
                    notes: currentTab.find('.notes').val() || '',
                    products: []
                };

                // Get products data
                currentTab.find('.items-list tr').each(function() {
                    const $row = $(this);
                    const productId = $row.find('.product-select').val();
                    if (productId) {
                        receiptData.products.push({
                            product_id: productId,
                            quantity: $row.find('.quantity').val(),
                            unit_type: $row.find('.unit-type').val(),
                            unit_price: $row.find('.unit-price').val()
                        });
                    }
                });

                // Show loading state
                saveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> چاوەڕێ بکە...');

                // Save draft receipt via AJAX
                $.ajax({
                    url: '../../api/save_receipt.php',
                    type: 'POST',
                    data: JSON.stringify(receiptData),
                    contentType: 'application/json',
                    success: function(response) {
                        console.log('Server response:', response);
                        if (response.success) {
                            // Show success message
                            Swal.fire({
                                title: 'سەرکەوتوو بوو',
                                text: 'ڕەشنووسی پسووڵە بە سەرکەوتوویی پاشەکەوتکرا',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Fix the URL path to point to the correct location
                                    window.location.href = '../../views/admin/receiptList.php?tab=drafts';
                                }
                            });
                        } else {
                            // Handle specific error cases
                            if (response.message === 'ئەم ڕەشنووسە پێشتر هەیە') {
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: 'ئەم ڕەشنووسە پێشتر هەیە. دەتەوێت بیبینیت؟',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'بەڵێ',
                                    cancelButtonText: 'نەخێر'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Fix the URL path to point to the correct location
                                        window.location.href = '../../views/admin/receiptList.php?tab=drafts';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: response.message || 'هەڵەیەک ڕوویدا',
                                    icon: 'error',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەرەوە',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    },
                    complete: function() {
                        // Reset button state
                        saveButton.prop('disabled', false).html('<i class="fas fa-file-alt"></i> ڕەشنووس');
                    }
                });
            });

            // Handle quick add product button click
            $('.quick-add-product').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#quickAddProductModal').modal('show');
            });

            // Handle save product button click
            $('#saveQuickProduct').on('click', function(e) {
                e.preventDefault();
                console.log('Save button clicked');

                // Get form data
                const productData = {
                    name: $('#productName').val(),
                    category_id: $('#productCategory').val(),
                    unit_id: $('#productUnit').val(),
                    quantity: $('#productQuantity').val(),
                    purchase_price: $('#productPurchasePrice').val(),
                    selling_price_single: $('#productSellingPrice').val(),
                    selling_price_wholesale: $('#productWholesalePrice').val() || $('#productSellingPrice').val(),
                    code: 'Q' + Math.floor(Math.random() * 1000000),
                    barcode: Date.now().toString()
                };

                console.log('Product data:', productData);

                // Validate form
                if (!$('#quickAddProductForm')[0].checkValidity()) {
                    console.log('Form validation failed');
                    $('#quickAddProductForm')[0].reportValidity();
                    return;
                }

                // Show loading state
                const saveButton = $(this);
                saveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> چاوەڕێ بکە...');

                // Save product via AJAX
            $.ajax({
                    url: '../../api/quick_add_product.php',
                    type: 'POST',
                    data: productData,
                success: function(response) {
                        console.log('API Response:', response);
                        
                    if (response.success) {
                            // Add the new product to the select2 dropdown
                            const newOption = new Option(productData.name, response.product_id, true, true);
                            const $currentSelect = $('.product-select').last();
                            
                            $currentSelect.append(newOption).trigger('change');

                            // Set the unit price based on price type
                            const priceType = $('.price-type').val();
                            const unitPriceInput = $currentSelect.closest('tr').find('.unit-price');
                            
                            if (priceType === 'wholesale') {
                                unitPriceInput.val(productData.selling_price_wholesale);
                            } else {
                                unitPriceInput.val(productData.selling_price_single);
                            }

                            // Close modal and reset form
                            $('#quickAddProductModal').modal('hide');
                            $('#quickAddProductForm')[0].reset();

                            // Show success message
                            Swal.fire({
                                title: 'سەرکەوتوو بوو',
                                text: 'کاڵاکە بە سەرکەوتوویی زیادکرا',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەرەوە',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    },
                    complete: function() {
                        // Reset button state
                        saveButton.prop('disabled', false).html('پاشەکەوتکردن');
                    }
                });
            });

            // Reset form when modal is closed
            $('#quickAddProductModal').on('hidden.bs.modal', function() {
                $('#quickAddProductForm')[0].reset();
            });

            // Handle delivery receipt button click
            $('.delivery-btn').on('click', function(e) {
                e.preventDefault();
                $('#deliveryReceiptModal').modal('show');
            });

            // Handle save delivery receipt button click
            $('#saveDeliveryReceipt').on('click', function(e) {
                e.preventDefault();
                
                // Get form data
                const deliveryAddress = $('#deliveryAddress').val();
                const phoneNumber = $('#phoneNumber').val();
                
                if (!deliveryAddress) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە ناونیشانی گەیاندن بنووسە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                if (!phoneNumber) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە ژمارەی مۆبایل بنووسە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Get current receipt data
                const currentTab = $('.tab-pane.active');
                const receiptData = {
                    receipt_type: 'selling',
                    is_delivery: true,
                    delivery_address: deliveryAddress,
                    phone_number: phoneNumber,
                    invoice_number: currentTab.find('.receipt-number').val(),
                    customer_id: currentTab.find('.customer-select').val() || null,
                    date: currentTab.find('.sale-date').val(),
                    payment_type: currentTab.find('.payment-type').val(),
                    discount: currentTab.find('.discount').val() || 0,
                    paid_amount: currentTab.find('.paid-amount').val() || 0,
                    price_type: currentTab.find('.price-type').val(),
                    shipping_cost: currentTab.find('.shipping-cost').val() || 0,
                    other_cost: currentTab.find('.other-cost').val() || 0,
                    notes: currentTab.find('.notes').val() || '',
                    products: []
                };

                // Get products data
                currentTab.find('.items-list tr').each(function() {
                    const $row = $(this);
                    const productId = $row.find('.product-select').val();
                    if (productId) {
                        receiptData.products.push({
                            product_id: productId,
                            quantity: $row.find('.quantity').val(),
                            unit_type: $row.find('.unit-type').val(),
                            unit_price: $row.find('.unit-price').val()
                        });
                    }
                });

                // Check if products are selected
                if (receiptData.products.length === 0) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە لانیکەم یەک کاڵا هەڵبژێرە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Debug: Log the data being sent
                console.log('Sending receipt data:', receiptData);

                // Show loading state
                const saveButton = $(this);
                saveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> چاوەڕێ بکە...');

                // Save receipt via AJAX
                $.ajax({
                    url: '../../api/save_receipt.php',
                    type: 'POST',
                    data: JSON.stringify(receiptData),
                    contentType: 'application/json',
                    success: function(response) {
                        console.log('Server response:', response);
                        if (response.success) {
                            // Close modal and reset form
                            $('#deliveryReceiptModal').modal('hide');
                            $('#deliveryReceiptForm')[0].reset();

                            // Show success message
                            Swal.fire({
                                title: 'سەرکەوتوو بوو',
                                text: 'پسووڵەی گەیاندن بە سەرکەوتوویی پاشەکەوتکرا',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = `../receipt/delivery_receipt.php?id=${response.receipt_id}`;
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەرەوە',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });

            // Reset form when modal is closed
            $('#deliveryReceiptModal').on('hidden.bs.modal', function() {
                $('#deliveryReceiptForm')[0].reset();
            });

            // Handle print button click
            $('.print-btn').on('click', function(e) {
                e.preventDefault();
                
                // Get the receipt ID
                const receiptId = $(this).data('id');
                
                // Open print window
                const printWindow = window.open(`/warehoause-system/Selling-System/src/Views/receipt/print_receipt.php?sale_id=${receiptId}`, '_blank');
                
                // Add event listener for when the print window is closed
                printWindow.onbeforeunload = function() {
                    // Reload the current page
                    window.location.reload();
                };
            });

            // Function to round down to nearest appropriate value
            function roundDownToNearest(number) {
                // Define valid Iraqi Dinar denominations
                const denominations = [250, 500, 750, 1000];
                
                // Find the largest denomination that's less than or equal to the number
                let result = 0;
                for (let i = denominations.length - 1; i >= 0; i--) {
                    const quotient = Math.floor(number / denominations[i]);
                    if (quotient > 0) {
                        result = quotient * denominations[i];
                        const remainder = number - result;
                        
                        // If there's a remainder, check if we can add smaller denominations
                        if (remainder > 0) {
                            for (let j = i - 1; j >= 0; j--) {
                                if (remainder >= denominations[j]) {
                                    result += denominations[j];
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
                
                return result;
            }

            // Handle round total button click
            $(document).on('click', '.round-total', function() {
                const $container = $(this).closest('.receipt-container');
                const currentTotal = parseFloat($container.find('.grand-total').val()) || 0;
                const roundedTotal = roundDownToNearest(currentTotal);
                const difference = currentTotal - roundedTotal;
                
                if (difference > 0) {
                    // Get current discount
                    const currentDiscount = parseFloat($container.find('.discount').val()) || 0;
                    
                    // Update discount with rounded difference
                    const newDiscount = currentDiscount + difference;
                    $container.find('.discount').val(newDiscount.toFixed(0)).trigger('change');
                    
                    // Show notification
                    Swal.fire({
                        title: 'خڕکردنەوەی بڕی پارە',
                        html: `
                            <p>بڕی پارە: ${currentTotal.toFixed(0)} دینار</p>
                            <p>بڕی خڕکراوە: ${roundedTotal.toFixed(0)} دینار</p>
                            <p>بڕی داشکاندن: ${difference.toFixed(0)} دینار</p>
                        `,
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    });
                }
            });
        });
    </script>

    <!-- Your other scripts -->
    <script src="../../js/debug_selects.js"></script>
    <script src="../../js/addReceipt.js"></script>
    <script src="../../js/advance-payment.js"></script>
    <script src="../../js/include-components.js"></script>
</body>
</html> 