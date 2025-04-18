<?php
// Include database connection
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
                <button type="button" class="btn btn-outline-primary draft-btn">
                    <i class="fas fa-file-alt"></i> ڕەشنووس
                </button>
                <button type="button" class="btn btn-primary save-btn cta-btn">
                    پاشەکەوتکردن   <i class="fas fa-save"></i> 
                </button>
            </div>
        </div>
    </div>

    <!-- Template for Wasting Receipt Tab (Hidden) -->
    <div id="wasting-template" class="d-none">
        <div class="receipt-container card-qiuck-style">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ژمارە</label>
                    <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ناونیشانی پسوڵە</label>
                    <input type="text" class="form-control receipt-title" placeholder="ناونیشانی پسوڵە بنووسە">
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">بەرپرسیار</label>
                    <select class="form-select responsible-select">
                        <option value="" selected disabled>بەرپرسیار هەڵبژێرە</option>
                        <option value="1">هێمن عەبدوڵا</option>
                        <option value="2">نەوزاد عەلی</option>
                        <option value="3">کامەران حەسەن</option>
                        <option value="new">بەرپرسیاری نوێ زیاد بکە...</option>
                    </select>
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
                            <th>بڕی بەردەست</th>
                            <th>بڕی بەفیڕۆچوو</th>
                            <th>نرخی یەکە</th>
                            <th>وەسفکردن</th>
                            <th>کۆی گشتی</th>
                            <th style="width: 100px">کردار</th>
                        </tr>
                    </thead>
                    <tbody class="items-list">
                        <tr>
                            <td>1</td>
                            <td><select class="form-control product-select" style="width: 100%"></select></td>
                            <td class="product-image-cell"></td>
                            <td><input type="number" class="form-control current-quantity" step="1" readonly></td>
                            <td><input type="number" class="form-control adjusted-quantity" step="1"></td>
                            <td><input type="number" class="form-control price" step="1"></td>
                            <td><input type="text" class="form-control" placeholder="وەسفکردن"></td>
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

            <!-- Date and Reason Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">بەروار</label>
                    <input type="date" class="form-control adjustment-date">
                </div>
                <div class="col-md-6">
                    <label class="form-label">هۆکاری زیان</label>
                    <select class="form-select adjustment-reason">
                        <option value="damaged">کاڵای زیانمەند</option>
                        <option value="expired">کاڵای بەسەرچوو</option>
                        <option value="inventory_correction">ڕاستکردنەوەی ئینڤێنتۆری</option>
                        <option value="other">هۆکاری تر</option>
                    </select>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <label class="form-label">تێبینی</label>
                    <textarea class="form-control adjustment-notes" rows="3" placeholder="تێبینی لەسەر ڕێکخستنەوە"></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
        

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
                <button type="button" class="btn btn-outline-primary draft-btn">
                    <i class="fas fa-file-alt"></i> ڕەشنووس
                </button>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../js/debug_selects.js"></script>
    <script src="../../js/addReceipt.js"></script>
    <script src="../../js/include-components.js"></script>
    
    <script>
        // Get the last receipt number
        <?php
        // Get the last receipt number from the database
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(receipt_number, 3) AS UNSIGNED)) as max_number FROM sales");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNumber = ($result['max_number'] ?? 0) + 1;
        $receiptNumber = 'A-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        ?>
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set the receipt number
            const receiptFields = document.querySelectorAll('.receipt-number');
            receiptFields.forEach(field => {
                field.value = '<?php echo $receiptNumber; ?>';
            });

            // Initialize Select2 for all select elements
            $('.customer-select, .supplier-select, .product-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            $('.sale-date, .purchase-date, .adjustment-date').val(today);

            // Initialize receipt type buttons
            const buttons = document.querySelectorAll('.receipt-type-btn');
            
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    buttons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                });
            });

            // Initialize the first tab
            updateReceiptType('selling');

            // Initialize Select2 for customer select with advance payment info
            $('.customer-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                ajax: {
                    url: '../../api/customers.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.customers,
                            pagination: {
                                more: (params.page * 10) < data.total_count
                            }
                        };
                    },
                    cache: true
                },
                templateResult: formatCustomer,
                templateSelection: formatCustomerSelection
            });
        });

        // Function to update receipt type
        function updateReceiptType(type) {
            // Hide all tab panes
            $('.tab-pane').removeClass('show active');
            
            // Show the active tab pane
            $(`#${type}-1`).addClass('show active');
            
            // Update the tab button
            $(`.receipt-tab[data-bs-target="#${type}-1"]`).addClass('active');
            
            // Update the receipt number based on type
            $.ajax({
                url: '../../api/get_next_invoice.php',
                type: 'GET',
                data: { type: type },
                success: function(response) {
                    if (response.success) {
                        $(`#${type}-1 .receipt-number`).val(response.invoice_number);
                    }
                }
            });
        }

        // Function to check product stock
        function checkProductStock(quantityInput) {
            const row = quantityInput.closest('tr');
            const productSelect = row.querySelector('.product-select');
            const unitTypeSelect = row.querySelector('.unit-type');
            
            if (!productSelect || !productSelect.value || !quantityInput.value) {
                return;
            }
            
            const productId = productSelect.value;
            const quantity = parseInt(quantityInput.value);
            const unitType = unitTypeSelect ? unitTypeSelect.value : 'piece';
            
            // AJAX call to check stock
            $.ajax({
                url: '../../api/check_product_stock.php',
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity,
                    unit_type: unitType
                },
                success: function(response) {
                    if (!response.success) {
                        // Show warning if insufficient stock
                        Swal.fire({
                            title: 'ئاگاداری!',
                            text: `بڕی پێویست لە کۆگادا بەردەست نیە! بڕی بەردەست: ${response.available_quantity} ${getUnitName(unitType)}`,
                            icon: 'warning',
                            confirmButtonText: 'باشە'
                        });
                        // Reset quantity to 1 or the maximum available
                        quantityInput.value = Math.min(1, response.available_quantity);
                        // Recalculate total
                        calculateRowTotal(row);
                    }
                },
                error: function() {
                    console.error('Error checking product stock');
                }
            });
        }

        // Helper function to get unit name in Kurdish
        function getUnitName(unitType) {
            switch(unitType) {
                case 'box': return 'کارتۆن';
                case 'set': return 'سێت';
                default: return 'دانە';
            }
        }

        // Format customer display in dropdown
        function formatCustomer(customer) {
            if (!customer.id) return customer.text;
            
            let advanceInfo = '';
            if (customer.debt_on_customer > 0) {
                advanceInfo = `<div class="text-success" style="font-size: 0.9em;">
                    <i class="fas fa-money-bill-wave"></i> پارەی پێشەکی: ${customer.debt_on_customer.toLocaleString()} دینار
                </div>`;
            }
            
            let debtInfo = '';
            if (customer.debit_on_business > 0) {
                debtInfo = `<div class="text-danger" style="font-size: 0.9em;">
                    <i class="fas fa-file-invoice-dollar"></i> قەرز: ${customer.debit_on_business.toLocaleString()} دینار
                </div>`;
            }
            
            return $(`
                <div class="customer-option">
                    <div class="customer-name" style="font-weight: bold;">${customer.text}</div>
                    <div class="customer-phone text-muted" style="font-size: 0.9em;">
                        <i class="fas fa-phone"></i> ${customer.phone1}
                    </div>
                    ${advanceInfo}
                    ${debtInfo}
                </div>
            `);
        }

        // Format selected customer
        function formatCustomerSelection(customer) {
            if (!customer.id) return customer.text;
            
            // Show/hide advance payment info
            const advanceInfoDiv = $('.customer-advance-info');
            const advanceAmountSpan = $('.advance-amount');
            
            if (customer.debt_on_customer > 0) {
                advanceInfoDiv.show();
                advanceAmountSpan.text(customer.debt_on_customer.toLocaleString());
            } else {
                advanceInfoDiv.hide();
            }
            
            return customer.text;
        }

        // Add CSS styles for the dropdown
        const style = `
        <style>
            .customer-option {
                padding: 8px;
                border-bottom: 1px solid #eee;
            }
            .customer-option:last-child {
                border-bottom: none;
            }
            .customer-name {
                margin-bottom: 2px;
            }
            .select2-results__option {
                padding: 0 !important;
            }
            .select2-results__option--highlighted .customer-option {
                background-color: #f0f0f0;
            }
            .customer-advance-info {
                margin-top: 5px;
                padding: 5px;
                background-color: #f8f9fa;
                border-radius: 4px;
                font-size: 0.9em;
            }
        </style>
        `;
        $('head').append(style);

        // Handle customer selection change
        $('.customer-select').on('change', function() {
            const selectedCustomer = $(this).select2('data')[0];
            if (selectedCustomer && selectedCustomer.debt_on_customer > 0) {
                // Show warning if customer has advance payment
                Swal.fire({
                    title: 'ئاگاداری',
                    text: `ئەم کڕیارە ${selectedCustomer.debt_on_customer.toLocaleString()} دینار پارەی پێشەکی هەیە. ئەگەر کاڵا بە قەرز بکڕێت، لەم پارە پێشەکییە دەبڕدرێتەوە.`,
                    icon: 'info',
                    confirmButtonText: 'باشە'
                });
            }
        });
    </script>
</body>
</html> 