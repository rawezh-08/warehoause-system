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
  

    <div class="container-fluid px-0">
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
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">کڕیار</label>
                            <select class="form-select customer-select">
                                <option value="" selected disabled>کڕیار هەڵبژێرە</option>
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
                                    <td><input type="number" class="form-control quantity" min="1" step="1" onchange="checkProductStock(this)"></td>
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
                                <input type="number" class="form-control grand-total" step="1" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-4 text-start d-flex justify-content-start align-items-center">
                        <button type="button" class="btn btn-outline-primary draft-btn ">
                            <i class="fas fa-file-alt"></i> ڕەشنووس
                        </button>
                        <button type="button" class="btn btn-primary save-btn cta-btn ">
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
                    <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
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
                    <label class="form-label">کڕیار</label>
                    <select class="form-select customer-select">
                        <option value="" selected disabled>کڕیار هەڵبژێرە</option>
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
                            <td><input type="number" class="form-control quantity" min="1" step="1" onchange="checkProductStock(this)"></td>
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
                            <input type="number" class="form-control" id="productWholesalePrice">
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Test Script -->
    <script>
        $(document).ready(function() {
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
        });
    </script>

    <!-- Your other scripts -->
    <script src="../../js/debug_selects.js"></script>
    <script src="../../js/addReceipt.js"></script>
    <script src="../../js/advance-payment.js"></script>
    <script src="../../js/include-components.js"></script>
</body>
</html> 