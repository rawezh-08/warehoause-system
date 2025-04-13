<?php
// Include database connection
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسوڵەکان - دیزاینی نوێ</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/input.css">
    <link rel="stylesheet" href="../../css/modernAddReceipt.css">
</head>
<body>
    <!-- Top Navigation -->
    <div class="main-content">
        <div class="container">
            <div id="navbar-container"></div>

            <div class="top-nav">
                <!-- Sidebar container - will be populated by JavaScript -->
                <div id="sidebar-container"></div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div class="receipt-type-container">
                        <h5 class="mb-0 ms-2">جۆری پسوڵە</h5>
                        <div class="receipt-types" role="group">
                            <button class="receipt-type-btn active" data-type="selling">
                                <i class="fas fa-shopping-cart me-2"></i>فرۆشتن
                            </button>
                            <button class="receipt-type-btn" data-type="buying">
                                <i class="fas fa-truck me-2"></i>کڕین
                            </button>
                            <button class="receipt-type-btn" data-type="wasting">
                                <i class="fas fa-trash-alt me-2"></i>بەفیڕۆچوو
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="receiptTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active receipt-tab" id="tab-selling-1" data-bs-toggle="tab" data-bs-target="#selling-1" type="button" role="tab">
                        <i class="fas fa-shopping-cart me-2"></i>فرۆشتن #1
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
            <div class="tab-content" id="receiptTabsContent">
                <!-- SELLING RECEIPT TEMPLATE -->
                <div class="tab-pane fade show active animate-enter" id="selling-1" role="tabpanel" data-receipt-type="selling">
                    <div class="receipt-container card-modern">
                        <!-- Header Section -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">ژمارەی پسوڵە</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                    <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">کڕیار</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <select class="form-select customer-select">
                                        <option value="" selected disabled>کڕیار هەڵبژێرە</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">جۆری پارەدان</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                    <select class="form-select payment-type">
                                        <option value="cash">نەقد</option>
                                        <option value="credit">قەرز</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Credit Payment Fields (initially hidden) -->
                        <div class="row mb-4 credit-payment-fields" style="display: none;">
                            <div class="col-md-6">
                                <label class="form-label">بڕی پارەی دراو</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                    <input type="number" class="form-control paid-amount" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">بڕی ماوە</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                    <input type="number" class="form-control remaining-amount" value="0" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Sale Fields -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label">جۆری نرخ</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <select class="form-select price-type">
                                        <option value="single">دانە</option>
                                        <option value="wholesale">کۆ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">تێچووی گواستنەوە</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                    <input type="number" class="form-control shipping-cost" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">تێچووی تر</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-plus-circle"></i></span>
                                    <input type="number" class="form-control other-costs" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">بەروار</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" class="form-control sale-date">
                                </div>
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">تێبینی</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                                    <textarea class="form-control notes" rows="2" placeholder="تێبینی"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="btn btn-outline-primary refresh-btn">
                                <i class="fas fa-sync me-2"></i>
                                نوێکردنەوە
                            </button>
                            <button class="btn btn-outline-primary print-btn">
                                <i class="fas fa-print me-2"></i>
                                چاپکردن
                            </button>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered table-hover">
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
                                        <td><input type="number" class="form-control unit-price" step="0.01"></td>
                                        <td><input type="number" class="form-control quantity"></td>
                                        <td><input type="number" class="form-control total" readonly></td>
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
                        <button type="button" class="btn btn-link text-primary add-row-btn">
                            <i class="fas fa-plus me-2"></i> زیادکردنی ڕیز
                        </button>

                        <!-- Totals Section -->
                        <div class="total-section">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="total-label">کۆ</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calculator"></i></span>
                                        <input type="number" class="form-control subtotal" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="total-label">داشکاندن</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                        <input type="number" class="form-control discount" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="total-label">تێچووی گواستنەوە</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                        <input type="number" class="form-control shipping-cost-total" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="total-label">کۆی گشتی</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-money-bill-alt"></i></span>
                                        <input type="number" class="form-control grand-total" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-4 text-start">
                            <button type="submit" class="btn btn-primary save-btn">
                                <i class="fas fa-save me-2"></i> پاشەکەوتکردن
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template for Buying Receipt Tab (Hidden) -->
    <div id="buying-template" class="d-none">
        <div class="receipt-container card-modern">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ژمارەی پسوڵە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                        <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">فرۆشیار</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-store"></i></span>
                        <select class="form-select supplier-select" style="width: 100%">
                            <option value="" selected disabled>فرۆشیار هەڵبژێرە</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">جۆری پارەدان</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                        <select class="form-select payment-type">
                            <option value="cash">پارە</option>
                            <option value="credit">قەرز</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Credit Payment Fields (initially hidden) -->
            <div class="row mb-4 credit-payment-fields" style="display: none;">
                <div class="col-md-6">
                    <label class="form-label">بڕی پارەی دراو</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                        <input type="number" class="form-control paid-amount" value="0" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">بڕی ماوە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                        <input type="number" class="form-control remaining-amount" value="0" readonly>
                    </div>
                </div>
            </div>

            <!-- Date and Notes -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">بەروار</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" class="form-control purchase-date">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">تێبینی</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                        <textarea class="form-control notes" rows="2" placeholder="تێبینی"></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-outline-primary refresh-btn">
                    <i class="fas fa-sync me-2"></i>
                    نوێکردنەوە
                </button>
                <button class="btn btn-outline-primary print-btn">
                    <i class="fas fa-print me-2"></i>
                    چاپکردن
                </button>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px" class="tbl-header">#</th>
                            <th class="tbl-header">کاڵا</th>
                            <th class="tbl-header">وێنە</th>
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
                            <td><input type="number" class="form-control unit-price" min="0"></td>
                            <td><input type="number" class="form-control quantity" min="0"></td>
                            <td><input type="number" class="form-control total" readonly></td>
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
            <button type="button" class="btn btn-link text-primary add-row-btn">
                <i class="fas fa-plus me-2"></i> زیادکردنی ڕیز
            </button>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-4">
                        <label class="total-label">کۆ</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calculator"></i></span>
                            <input type="number" class="form-control subtotal" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="total-label">داشکاندن</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-percent"></i></span>
                            <input type="number" class="form-control discount" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="total-label">کۆی گشتی</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-money-bill-alt"></i></span>
                            <input type="number" class="form-control grand-total" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start">
                <button type="submit" class="btn btn-primary save-btn">
                    <i class="fas fa-save me-2"></i> پاشەکەوتکردن
                </button>
            </div>
        </div>
    </div>

    <!-- Template for Wasting Receipt Tab (Hidden) -->
    <div id="wasting-template" class="d-none">
        <div class="receipt-container card-modern">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ژمارە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                        <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ناونیشانی پسوڵە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-heading"></i></span>
                        <input type="text" class="form-control receipt-title" placeholder="ناونیشانی پسوڵە بنووسە">
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">بەرپرسیار</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                        <select class="form-select responsible-select">
                            <option value="" selected disabled>بەرپرسیار هەڵبژێرە</option>
                            <option value="1">هێمن عەبدوڵا</option>
                            <option value="2">نەوزاد عەلی</option>
                            <option value="3">کامەران حەسەن</option>
                            <option value="new">بەرپرسیاری نوێ زیاد بکە...</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Date and Reason Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">بەروار</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" class="form-control adjustment-date">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">هۆکاری ڕێکخستنەوە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-question-circle"></i></span>
                        <select class="form-select adjustment-reason">
                            <option value="damaged">کاڵای زیانمەند</option>
                            <option value="expired">کاڵای بەسەرچوو</option>
                            <option value="inventory_correction">ڕاستکردنەوەی ئینڤێنتۆری</option>
                            <option value="other">هۆکاری تر</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <label class="form-label">تێبینی</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                        <textarea class="form-control adjustment-notes" rows="3" placeholder="تێبینی لەسەر ڕێکخستنەوە"></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-outline-primary refresh-btn">
                    <i class="fas fa-sync me-2"></i>
                    نوێکردنەوە
                </button>
                
                <button class="btn btn-outline-primary print-btn">
                    <i class="fas fa-print me-2"></i>
                    چاپکردن
                </button>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>کاڵا</th>
                            <th>وێنە</th>
                            <th>بڕی بەردەست</th>
                            <th>بڕی ڕێکخراو</th>
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
                            <td><input type="number" class="form-control current-quantity" readonly></td>
                            <td><input type="number" class="form-control adjusted-quantity"></td>
                            <td><input type="number" class="form-control price" step="0.01"></td>
                            <td><input type="text" class="form-control" placeholder="وەسفکردن"></td>
                            <td><input type="number" class="form-control total" readonly></td>
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
            <button type="button" class="btn btn-link text-primary add-row-btn">
                <i class="fas fa-plus me-2"></i> زیادکردنی ڕیز
            </button>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-12">
                        <label class="total-label">کۆی گشتی زیان</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-money-bill-alt"></i></span>
                            <input type="number" class="form-control grand-total" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start">
                <button type="submit" class="btn btn-primary save-btn">
                    <i class="fas fa-save me-2"></i> پاشەکەوتکردن
                </button>
            </div>
        </div>
    </div>

    <!-- Template for Selling Receipt Tab (Hidden) -->
    <div id="selling-template" class="d-none">
        <div class="receipt-container card-modern">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">ژمارەی پسوڵە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                        <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە" readonly>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">کڕیار</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <select class="form-select customer-select">
                            <option value="" selected disabled>کڕیار هەڵبژێرە</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <label class="form-label">جۆری پارەدان</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                        <select class="form-select payment-type">
                            <option value="cash">پارە</option>
                            <option value="credit">قەرز</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Credit Payment Fields (initially hidden) -->
            <div class="row mb-4 credit-payment-fields" style="display: none;">
                <div class="col-md-6">
                    <label class="form-label">بڕی پارەی دراو</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                        <input type="number" class="form-control paid-amount" value="0" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">بڕی ماوە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                        <input type="number" class="form-control remaining-amount" value="0" readonly>
                    </div>
                </div>
            </div>

            <!-- Additional Sale Fields -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">جۆری نرخ</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <select class="form-select price-type">
                            <option value="single">دانە</option>
                            <option value="wholesale">کۆمەڵ</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">تێچووی گواستنەوە</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-truck"></i></span>
                        <input type="number" class="form-control shipping-cost" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">تێچووی تر</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-plus-circle"></i></span>
                        <input type="number" class="form-control other-costs" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">بەروار</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" class="form-control sale-date">
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <label class="form-label">تێبینی</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                        <textarea class="form-control notes" rows="2" placeholder="تێبینی"></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-outline-primary refresh-btn">
                    <i class="fas fa-sync me-2"></i>
                    نوێکردنەوە
                </button>
                <button class="btn btn-outline-primary print-btn">
                    <i class="fas fa-print me-2"></i>
                    چاپکردن
                </button>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-hover">
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
                            <td><input type="number" class="form-control unit-price" step="0.01"></td>
                            <td><input type="number" class="form-control quantity"></td>
                            <td><input type="number" class="form-control total" readonly></td>
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
            <button type="button" class="btn btn-link text-primary add-row-btn">
                <i class="fas fa-plus me-2"></i> زیادکردنی ڕیز
            </button>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="total-label">کۆ</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calculator"></i></span>
                            <input type="number" class="form-control subtotal" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">داشکاندن</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-percent"></i></span>
                            <input type="number" class="form-control discount" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">تێچووی گواستنەوە</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-truck"></i></span>
                            <input type="number" class="form-control shipping-cost-total" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">کۆی گشتی</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-money-bill-alt"></i></span>
                            <input type="number" class="form-control grand-total" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start">
                <button type="submit" class="btn btn-primary save-btn">
                    <i class="fas fa-save me-2"></i> پاشەکەوتکردن
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
    <script src="../../js/include-components.js"></script>
    <script src="../../js/addReceipt.js"></script>

    
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
            $('.customer-select, .supplier-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                ajax: {
                    url: '../../api/search_customers.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                placeholder: 'هەڵبژێرە...',
                minimumInputLength: 1
            });

            $('.product-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                ajax: {
                    url: '../../api/search_products.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                placeholder: 'کاڵا هەڵبژێرە...',
                minimumInputLength: 1
            }).on('select2:select', function (e) {
                const data = e.params.data;
                const row = $(this).closest('tr');
                
                // Set product image if available
                if (data.image) {
                    row.find('.product-image-cell').html(`<img src="${data.image}" alt="${data.text}" class="img-thumbnail">`);
                } else {
                    row.find('.product-image-cell').html('<i class="fas fa-image text-muted"></i>');
                }
                
                // Set product price based on price type
                const priceType = $('.price-type').val();
                const price = priceType === 'wholesale' ? data.wholesale_price : data.retail_price;
                row.find('.unit-price').val(price);
                
                // Update totals
                updateRowTotal(row);
            });

            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            $('.sale-date, .purchase-date, .adjustment-date').val(today);

            // Initialize receipt type buttons
            $('.receipt-type-btn').on('click', function() {
                $('.receipt-type-btn').removeClass('active');
                $(this).addClass('active');
                const type = $(this).data('type');
                updateReceiptType(type);
            });

            // Initialize payment type change
            $(document).on('change', '.payment-type', function() {
                const paymentType = $(this).val();
                const creditFields = $(this).closest('.receipt-container').find('.credit-payment-fields');
                
                if (paymentType === 'credit') {
                    creditFields.slideDown(300);
                } else {
                    creditFields.slideUp(300);
                }
            });

            // Initialize quantity and price change events
            $(document).on('input', '.quantity, .unit-price', function() {
                updateRowTotal($(this).closest('tr'));
            });

            // Initialize discount and shipping cost change events
            $(document).on('input', '.discount, .shipping-cost', function() {
                updateTotals($(this).closest('.receipt-container'));
            });

            // Handle add row button
            $(document).on('click', '.add-row-btn', function() {
                const container = $(this).closest('.receipt-container');
                const tbody = container.find('.items-list');
                const newRowNum = tbody.find('tr').length + 1;
                
                // Clone the first row template based on receipt type
                const newRow = tbody.find('tr:first').clone();
                
                // Reset all inputs and selects in the new row
                newRow.find('input').val('');
                newRow.find('select').val(null).trigger('change');
                newRow.find('.product-image-cell').empty();
                newRow.find('td:first').text(newRowNum);
                
                // Re-initialize select2 for the new row
                newRow.find('.product-select').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    ajax: {
                        url: '../../api/search_products.php',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return { results: data };
                        },
                        cache: true
                    },
                    placeholder: 'کاڵا هەڵبژێرە...',
                    minimumInputLength: 1
                });
                
                // Add the new row
                tbody.append(newRow);
                
                // Apply fancy animation
                newRow.hide().addClass('animate-enter').fadeIn(500);
            });

            // Handle remove row button
            $(document).on('click', '.remove-row', function() {
                const row = $(this).closest('tr');
                const tbody = row.closest('tbody');
                
                // Don't remove if it's the only row
                if (tbody.find('tr').length > 1) {
                    row.fadeOut(300, function() {
                        row.remove();
                        // Update row numbers
                        tbody.find('tr').each(function(index) {
                            $(this).find('td:first').text(index + 1);
                        });
                        // Update totals
                        updateTotals(tbody.closest('.receipt-container'));
                    });
                } else {
                    // Just clear the inputs if it's the only row
                    row.find('input').val('');
                    row.find('select').val(null).trigger('change');
                    row.find('.product-image-cell').empty();
                    updateTotals(tbody.closest('.receipt-container'));
                }
            });

            // Save button click handler
            $(document).on('click', '.save-btn', function() {
                const container = $(this).closest('.receipt-container');
                const receiptType = container.closest('.tab-pane').data('receipt-type');
                
                // Validate form
                if (!validateForm(container, receiptType)) {
                    return;
                }
                
                // Collect form data
                const formData = collectFormData(container, receiptType);
                
                // Show loading state
                const saveBtn = $(this);
                saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> جاری پاشەکەوتکردن...');
                
                // Send data to server
                $.ajax({
                    url: '../../api/save_receipt.php',
                    type: 'POST',
                    data: { 
                        type: receiptType,
                        data: JSON.stringify(formData)
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو بوو!',
                                text: 'پسوڵە بە سەرکەوتووی پاشەکەوت کرا.',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                // Reset the form or redirect
                                resetForm(container, receiptType);
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا لەکاتی پاشەکەوتکردن.',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەکی پەیوەندی ڕوویدا.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    },
                    complete: function() {
                        saveBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> پاشەکەوتکردن');
                    }
                });
            });

            // Initialize the first tab
            updateReceiptType('selling');
            
            // Add new tab button
            $('#addNewTab').on('click', function() {
                const activeType = $('.receipt-type-btn.active').data('type');
                const tabsCount = $('.receipt-tab').length + 1;
                const newTabId = `${activeType}-${tabsCount}`;
                
                // Create new tab
                const newTab = $(`
                    <li class="nav-item" role="presentation">
                        <button class="nav-link receipt-tab" id="tab-${newTabId}" data-bs-toggle="tab" data-bs-target="#${newTabId}" type="button" role="tab">
                            <i class="fas fa-${activeType === 'selling' ? 'shopping-cart' : (activeType === 'buying' ? 'truck' : 'trash-alt')} me-2"></i>${getTypeName(activeType)} #${tabsCount}
                            <span class="close-tab"><i class="fas fa-times"></i></span>
                        </button>
                    </li>
                `);
                
                // Add the tab before the add button
                $(this).parent().before(newTab);
                
                // Get the appropriate template
                const templateHtml = $(`#${activeType}-template`).html();
                
                // Create tab content
                const newTabContent = $(`<div class="tab-pane fade animate-enter" id="${newTabId}" role="tabpanel" data-receipt-type="${activeType}">${templateHtml}</div>`);
                
                // Add it to the tab content container
                $('#receiptTabsContent').append(newTabContent);
                
                // Initialize Select2 for new tab
                newTabContent.find('.customer-select, .supplier-select, .product-select').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
                
                // Set today's date
                newTabContent.find('.sale-date, .purchase-date, .adjustment-date').val(today);
                
                // Get new receipt number and set it
                $.ajax({
                    url: '../../api/get_next_invoice.php',
                    type: 'GET',
                    data: { type: activeType },
                    success: function(response) {
                        if (response.success) {
                            newTabContent.find('.receipt-number').val(response.invoice_number);
                        }
                    }
                });
                
                // Activate the new tab
                newTab.find('button').tab('show');
            });
            
            // Close tab button
            $(document).on('click', '.close-tab', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const tabButton = $(this).closest('button');
                const tabId = tabButton.attr('data-bs-target');
                const tabLi = tabButton.parent();
                
                // Don't close if it's the only tab
                if ($('.receipt-tab').length <= 1) {
                    return;
                }
                
                // Ask for confirmation if the tab has data
                if (hasTabData(tabId)) {
                    Swal.fire({
                        title: 'دڵنیایت؟',
                        text: 'هەموو زانیارییەکانی ناو ئەم پسوڵەیە لەدەست دەدەیت.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'بەڵێ، داخستن',
                        cancelButtonText: 'نەخێر'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            closeTab(tabLi, tabId);
                        }
                    });
                } else {
                    closeTab(tabLi, tabId);
                }
            });
            
            // Refresh button
            $(document).on('click', '.refresh-btn', function() {
                const container = $(this).closest('.receipt-container');
                Swal.fire({
                    title: 'دڵنیایت؟',
                    text: 'هەموو خانەکان پاک دەبنەوە.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ، نوێکردنەوە',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        resetTabContent(container);
                    }
                });
            });
            
            // Print button
            $(document).on('click', '.print-btn', function() {
                const container = $(this).closest('.receipt-container');
                const receiptType = container.closest('.tab-pane').data('receipt-type');
                
                // Validate form
                if (!validateForm(container, receiptType)) {
                    return;
                }
                
                Swal.fire({
                    title: 'چاپکردن',
                    text: 'جۆری چاپکردن هەڵبژێرە',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'چاپکردنی ئاسایی',
                    cancelButtonText: 'هەڵوەشاندنەوە',
                    showDenyButton: true,
                    denyButtonText: 'چاپکردنی وردەکاری'
                }).then((result) => {
                    if (result.isConfirmed || result.isDenied) {
                        const printType = result.isConfirmed ? 'normal' : 'detailed';
                        printReceipt(container, receiptType, printType);
                    }
                });
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
        
        // Update row total
        function updateRowTotal(row) {
            const quantity = parseFloat(row.find('.quantity').val()) || 0;
            const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
            const total = quantity * unitPrice;
            
            row.find('.total').val(total.toFixed(2));
            
            // Update receipt totals
            updateTotals(row.closest('.receipt-container'));
        }
        
        // Update all totals
        function updateTotals(container) {
            let subtotal = 0;
            
            // Sum all row totals
            container.find('.total').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            
            const discount = parseFloat(container.find('.discount').val()) || 0;
            const shippingCost = parseFloat(container.find('.shipping-cost').val()) || 0;
            const otherCosts = parseFloat(container.find('.other-costs').val()) || 0;
            
            // Update subtotal
            container.find('.subtotal').val(subtotal.toFixed(2));
            
            // Update shipping cost total
            container.find('.shipping-cost-total').val(shippingCost.toFixed(2));
            
            // Calculate grand total
            const grandTotal = subtotal - discount + shippingCost + otherCosts;
            container.find('.grand-total').val(grandTotal.toFixed(2));
            
            // Update remaining amount if it's a credit payment
            if (container.find('.payment-type').val() === 'credit') {
                const paidAmount = parseFloat(container.find('.paid-amount').val()) || 0;
                const remainingAmount = grandTotal - paidAmount;
                container.find('.remaining-amount').val(remainingAmount.toFixed(2));
            }
        }
        
        // Form validation
        function validateForm(container, receiptType) {
            // Check if there are any items with product selected
            const hasItems = container.find('.product-select').filter(function() {
                return $(this).val();
            }).length > 0;
            
            if (!hasItems) {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'تکایە بەلایەنی کەم یەک کاڵا زیاد بکە.',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
                return false;
            }
            
            // Validate based on receipt type
            if (receiptType === 'selling' || receiptType === 'buying') {
                const customerOrSupplier = receiptType === 'selling' ? 
                    container.find('.customer-select').val() : 
                    container.find('.supplier-select').val();
                
                if (!customerOrSupplier) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: receiptType === 'selling' ? 'تکایە کڕیار هەڵبژێرە.' : 'تکایە فرۆشیار هەڵبژێرە.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return false;
                }
            }
            
            // Validate credit payment
            if (container.find('.payment-type').val() === 'credit') {
                const paidAmount = parseFloat(container.find('.paid-amount').val()) || 0;
                const grandTotal = parseFloat(container.find('.grand-total').val()) || 0;
                
                if (paidAmount > grandTotal) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'بڕی پارەی دراو ناتوانێت زیاتر بێت لە کۆی گشتی.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return false;
                }
            }
            
            return true;
        }
        
        // Collect form data
        function collectFormData(container, receiptType) {
            const formData = {
                receipt_number: container.find('.receipt-number').val(),
                notes: container.find('.notes').val(),
                payment_type: container.find('.payment-type').val(),
                date: receiptType === 'selling' ? container.find('.sale-date').val() : 
                      (receiptType === 'buying' ? container.find('.purchase-date').val() : 
                       container.find('.adjustment-date').val()),
                items: []
            };
            
            // Add type-specific fields
            if (receiptType === 'selling') {
                formData.customer_id = container.find('.customer-select').val();
                formData.price_type = container.find('.price-type').val();
                formData.shipping_cost = parseFloat(container.find('.shipping-cost').val()) || 0;
                formData.other_costs = parseFloat(container.find('.other-costs').val()) || 0;
            } else if (receiptType === 'buying') {
                formData.supplier_id = container.find('.supplier-select').val();
            } else if (receiptType === 'wasting') {
                formData.title = container.find('.receipt-title').val();
                formData.responsible_id = container.find('.responsible-select').val();
                formData.reason = container.find('.adjustment-reason').val();
            }
            
            // Add payment details
            if (formData.payment_type === 'credit') {
                formData.paid_amount = parseFloat(container.find('.paid-amount').val()) || 0;
                formData.remaining_amount = parseFloat(container.find('.remaining-amount').val()) || 0;
            }
            
            // Add discount
            formData.discount = parseFloat(container.find('.discount').val()) || 0;
            
            // Add totals
            formData.subtotal = parseFloat(container.find('.subtotal').val()) || 0;
            formData.grand_total = parseFloat(container.find('.grand-total').val()) || 0;
            
            // Collect items
            container.find('.items-list tr').each(function() {
                const row = $(this);
                const productId = row.find('.product-select').val();
                
                if (productId) {
                    const item = {
                        product_id: productId,
                        unit_price: parseFloat(row.find('.unit-price').val()) || 0,
                        quantity: parseFloat(row.find('.quantity').val()) || 0,
                        total: parseFloat(row.find('.total').val()) || 0
                    };
                    
                    // Add type-specific item fields
                    if (receiptType === 'selling') {
                        item.unit_type = row.find('.unit-type').val();
                    } else if (receiptType === 'wasting') {
                        item.current_quantity = parseFloat(row.find('.current-quantity').val()) || 0;
                        item.adjusted_quantity = parseFloat(row.find('.adjusted-quantity').val()) || 0;
                        item.description = row.find('input[placeholder="وەسفکردن"]').val();
                    }
                    
                    formData.items.push(item);
                }
            });
            
            return formData;
        }
        
        // Reset form
        function resetForm(container, receiptType) {
            // Reset all inputs except receipt number
            container.find('input:not(.receipt-number)').val('');
            container.find('textarea').val('');
            container.find('select:not(.payment-type, .price-type, .unit-type, .adjustment-reason)').val(null).trigger('change');
            
            // Reset to defaults
            container.find('.payment-type').val('cash').trigger('change');
            container.find('.price-type').val('single');
            container.find('.shipping-cost, .other-costs, .discount').val('0');
            
            // Reset items
            const tbody = container.find('.items-list');
            const firstRow = tbody.find('tr:first');
            
            // Clear first row
            firstRow.find('input').val('');
            firstRow.find('select.product-select').val(null).trigger('change');
            firstRow.find('.product-image-cell').empty();
            
            // Remove other rows
            tbody.find('tr:not(:first)').remove();
            
            // Set today's date
            const today = new Date().toISOString().split('T')[0];
            container.find('.sale-date, .purchase-date, .adjustment-date').val(today);
            
            // Update totals
            updateTotals(container);
            
            // Get new receipt number
            $.ajax({
                url: '../../api/get_next_invoice.php',
                type: 'GET',
                data: { type: receiptType },
                success: function(response) {
                    if (response.success) {
                        container.find('.receipt-number').val(response.invoice_number);
                    }
                }
            });
        }
        
        // Reset tab content
        function resetTabContent(container) {
            const receiptType = container.closest('.tab-pane').data('receipt-type');
            resetForm(container, receiptType);
        }
        
        // Check if tab has data
        function hasTabData(tabId) {
            const container = $(tabId);
            
            // Check if any products are selected
            const hasProducts = container.find('.product-select').filter(function() {
                return $(this).val();
            }).length > 0;
            
            // Check if customer/supplier is selected
            const hasParty = container.find('.customer-select, .supplier-select').filter(function() {
                return $(this).val();
            }).length > 0;
            
            return hasProducts || hasParty;
        }
        
        // Close tab
        function closeTab(tabLi, tabId) {
            // Get the previous tab
            const prevTab = tabLi.prev().find('button');
            
            // Remove the tab and its content
            tabLi.remove();
            $(tabId).remove();
            
            // Activate the previous tab
            prevTab.tab('show');
        }
        
        // Print receipt
        function printReceipt(container, receiptType, printType) {
            const formData = collectFormData(container, receiptType);
            
            // Open print window
            const printWindow = window.open(`../../views/print/print_receipt.php?type=${receiptType}&print_type=${printType}`, '_blank');
            
            // Save data to sessionStorage for the print page to access
            sessionStorage.setItem('receipt_data', JSON.stringify(formData));
        }
        
        // Get receipt type name in Kurdish
        function getTypeName(type) {
            switch(type) {
                case 'selling': return 'فرۆشتن';
                case 'buying': return 'کڕین';
                case 'wasting': return 'بەفیڕۆچوو';
                default: return type;
            }
        }
    </script>
</body>
</html> 