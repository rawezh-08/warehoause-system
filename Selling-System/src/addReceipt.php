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
    <!-- Custom CSS -->
         <!-- Page CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/addReceipt.css">
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
                    <h5 class="mb-0 ms-2">جۆری پسوڵە:</h5>
                    <div class="btn-group receipt-types" role="group">
                        <button class="btn receipt-type-btn active" data-type="selling">فرۆشتن</button>
                        <button class="btn receipt-type-btn" data-type="buying">کڕین</button>
                        <button class="btn receipt-type-btn" data-type="wasting">ڕێکخستنەوە</button>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="showCanceled" class="form-check-input">
                    <label for="showCanceled">کارەکانی هەڵوەشاوە</label>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
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
        <div class="tab-content" id="receiptTabsContent">
            <!-- SELLING RECEIPT TEMPLATE -->
            <div class="tab-pane fade show active" id="selling-1" role="tabpanel" data-receipt-type="selling">
                <div class="receipt-container">
                    <!-- Header Section -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">ژمارە</label>
                            <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ناونیشانی پسوڵە</label>
                            <input type="text" class="form-control receipt-title" placeholder="ناونیشانی پسوڵە بنووسە">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">کڕیار</label>
                            <input type="text" class="form-control receipt-customer" placeholder="ناوی کڕیار">
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">بەروار</label>
                            <input type="date" class="form-control start-date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ڕەوانەکردن</label>
                            <input type="date" class="form-control delivery-date">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="btn btn-outline-primary refresh-btn">
                            <i class="fas fa-sync"></i>
                            نوێکردنەوە
                        </button>
                        <button class="btn btn-outline-primary export-btn">
                            <i class="fas fa-file-export"></i>
                            ناردنە دەرەوە
                        </button>
                        <button class="btn btn-outline-primary print-btn">
                            <i class="fas fa-print"></i>
                            چاپکردن
                        </button>
                        <button class="btn btn-outline-primary add-new-btn">
                            <i class="fas fa-plus"></i>
                            زیادکردنی نوێ
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 50px">#</th>
                                    <th>کاڵا</th>
                                    <th>نرخی یەکە</th>
                                    <th>بڕی یەکە</th>
                                    <th>وەسفکردن</th>
                                    <th>کۆی گشتی</th>
                                    <th style="width: 100px">کردار</th>
                                </tr>
                            </thead>
                            <tbody class="items-list">
                                <tr>
                                    <td>1</td>
                                    <td><input type="text" class="form-control" placeholder="ناوی کاڵا"></td>
                                    <td><input type="number" class="form-control price" step="0.01"></td>
                                    <td><input type="number" class="form-control quantity"></td>
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
                        <i class="fas fa-plus"></i> زیادکردنی ڕیز
                    </button>

                    <!-- Totals Section -->
                    <div class="total-section">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="total-label">کۆ</label>
                                <input type="number" class="form-control subtotal" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="total-label">باج</label>
                                <input type="number" class="form-control tax" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="total-label">داشکاندن</label>
                                <input type="number" class="form-control discount" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="total-label">کۆی گشتی</label>
                                <input type="number" class="form-control grand-total" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-4 text-start">
                        <button type="submit" class="btn btn-primary save-btn">
                            <i class="fas fa-save"></i> پاشەکەوتکردن
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template for Buying Receipt Tab (Hidden) -->
    <div id="buying-template" class="d-none">
        <div class="receipt-container">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">ژمارە</label>
                    <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ناونیشانی پسوڵە</label>
                    <input type="text" class="form-control receipt-title" placeholder="ناونیشانی پسوڵە بنووسە">
                </div>
                <div class="col-md-4">
                    <label class="form-label">فرۆشیار</label>
                    <input type="text" class="form-control receipt-vendor" placeholder="ناوی فرۆشیار">
                </div>
            </div>

            <!-- Date and Vendor Info -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">بەروار</label>
                    <input type="date" class="form-control purchase-date">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ڕەوانەکردن</label>
                    <input type="date" class="form-control delivery-date">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ژمارەی پسوڵەی فرۆشیار</label>
                    <input type="text" class="form-control vendor-invoice" placeholder="ژمارەی پسوڵەی فرۆشیار">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-outline-primary refresh-btn">
                    <i class="fas fa-sync"></i>
                    نوێکردنەوە
                </button>
                <button class="btn btn-outline-primary export-btn">
                    <i class="fas fa-file-export"></i>
                    ناردنە دەرەوە
                </button>
                <button class="btn btn-outline-primary print-btn">
                    <i class="fas fa-print"></i>
                    چاپکردن
                </button>
                <button class="btn btn-outline-primary add-new-btn">
                    <i class="fas fa-plus"></i>
                    زیادکردنی نوێ
                </button>
            </div>

            <!-- Items Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>کاڵا</th>
                            <th>نرخی کڕین</th>
                            <th>بڕی یەکە</th>
                            <th>وەسفکردن</th>
                            <th>کۆی گشتی</th>
                            <th style="width: 100px">کردار</th>
                        </tr>
                    </thead>
                    <tbody class="items-list">
                        <tr>
                            <td>1</td>
                            <td><input type="text" class="form-control" placeholder="ناوی "></td>
                            <td><input type="number" class="form-control price" step="0.01"></td>
                            <td><input type="number" class="form-control quantity"></td>
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
                <i class="fas fa-plus"></i> زیادکردنی ڕیز
            </button>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="total-label">کۆ</label>
                        <input type="number" class="form-control subtotal" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">باج</label>
                        <input type="number" class="form-control tax" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">تێچووی گواستنەوە</label>
                        <input type="number" class="form-control shipping-cost" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">کۆی گشتی</label>
                        <input type="number" class="form-control grand-total" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start">
                <button type="submit" class="btn btn-primary save-btn">
                    <i class="fas fa-save"></i> پاشەکەوتکردن
                </button>
            </div>
        </div>
    </div>

    <!-- Template for Wasting Receipt Tab (Hidden) -->
    <div id="wasting-template" class="d-none">
        <div class="receipt-container">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">ژمارە</label>
                    <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ناونیشانی پسوڵە</label>
                    <input type="text" class="form-control receipt-title" placeholder="ناونیشانی پسوڵە بنووسە">
                </div>
                <div class="col-md-4">
                    <label class="form-label">بەرپرسیار</label>
                    <input type="text" class="form-control responsible-person" placeholder="ناوی بەرپرسیار">
                </div>
            </div>

            <!-- Date and Reason Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">بەروار</label>
                    <input type="date" class="form-control adjustment-date">
                </div>
                <div class="col-md-6">
                    <label class="form-label">هۆکاری ڕێکخستنەوە</label>
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
            <div class="action-buttons">
                <button class="btn btn-outline-primary refresh-btn">
                    <i class="fas fa-sync"></i>
                    نوێکردنەوە
                </button>
                <button class="btn btn-outline-primary export-btn">
                    <i class="fas fa-file-export"></i>
                    ناردنە دەرەوە
                </button>
                <button class="btn btn-outline-primary print-btn">
                    <i class="fas fa-print"></i>
                    چاپکردن
                </button>
                <button class="btn btn-outline-primary add-new-btn">
                    <i class="fas fa-plus"></i>
                    زیادکردنی نوێ
                </button>
            </div>

            <!-- Items Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>کاڵا</th>
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
                            <td><input type="text" class="form-control" placeholder="ناوی کاڵا"></td>
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
                <i class="fas fa-plus"></i> زیادکردنی ڕیز
            </button>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-12">
                        <label class="total-label">کۆی گشتی زیان</label>
                        <input type="number" class="form-control grand-total" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start">
                <button type="submit" class="btn btn-primary save-btn">
                    <i class="fas fa-save"></i> پاشەکەوتکردن
                </button>
            </div>
        </div>
    </div>

    <!-- Template for Selling Receipt Tab (Hidden) -->
    <div id="selling-template" class="d-none">
        <div class="receipt-container">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">ژمارە</label>
                    <input type="text" class="form-control receipt-number" placeholder="ژمارەی پسوڵە">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ناونیشانی پسوڵە</label>
                    <input type="text" class="form-control receipt-title" placeholder="ناونیشانی پسوڵە بنووسە">
                </div>
                <div class="col-md-4">
                    <label class="form-label">کڕیار</label>
                    <input type="text" class="form-control receipt-customer" placeholder="ناوی کڕیار">
                </div>
            </div>

            <!-- Date Range -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">بەروار</label>
                    <input type="date" class="form-control start-date">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ڕەوانەکردن</label>
                    <input type="date" class="form-control delivery-date">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-outline-primary refresh-btn">
                    <i class="fas fa-sync"></i>
                    نوێکردنەوە
                </button>
                <button class="btn btn-outline-primary export-btn">
                    <i class="fas fa-file-export"></i>
                    ناردنە دەرەوە
                </button>
                <button class="btn btn-outline-primary print-btn">
                    <i class="fas fa-print"></i>
                    چاپکردن
                </button>
                <button class="btn btn-outline-primary add-new-btn">
                    <i class="fas fa-plus"></i>
                    زیادکردنی نوێ
                </button>
            </div>

            <!-- Items Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>کاڵا</th>
                            <th>نرخی یەکە</th>
                            <th>بڕی یەکە</th>
                            <th>وەسفکردن</th>
                            <th>کۆی گشتی</th>
                            <th style="width: 100px">کردار</th>
                        </tr>
                    </thead>
                    <tbody class="items-list">
                        <tr>
                            <td>1</td>
                            <td><input type="text" class="form-control" placeholder="ناوی کاڵا"></td>
                            <td><input type="number" class="form-control price" step="0.01"></td>
                            <td><input type="number" class="form-control quantity"></td>
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
                <i class="fas fa-plus"></i> زیادکردنی ڕیز
            </button>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="row">
                    <div class="col-md-3">
                        <label class="total-label">کۆ</label>
                        <input type="number" class="form-control subtotal" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">باج</label>
                        <input type="number" class="form-control tax" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">داشکاندن</label>
                        <input type="number" class="form-control discount" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="total-label">کۆی گشتی</label>
                        <input type="number" class="form-control grand-total" readonly>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4 text-start">
                <button type="submit" class="btn btn-primary save-btn">
                    <i class="fas fa-save"></i> پاشەکەوتکردن
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/addReceipt.js"></script>
    <script src="js/include-components.js"></script>
</body>
</html> 