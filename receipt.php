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
    <style>
        body {
            background-color: #f8f9fa;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            color: #0d6efd;
        }
        .form-label {
            font-weight: 600;
            color: #444;
        }
        .receipt-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
            margin-bottom: 20px;
        }
        .receipt-type-btn {
            margin: 0 5px;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .receipt-type-btn.active {
            background-color: #0d6efd;
            color: white;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }
        .action-buttons button {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        .total-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .total-label {
            font-weight: 600;
            color: #444;
        }
        .receipt-tab {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .receipt-tab .close-tab {
            font-size: 0.8rem;
            color: #888;
            margin-right: 5px;
            cursor: pointer;
        }
        .receipt-tab .close-tab:hover {
            color: #dc3545;
        }
        .add-tab-btn {
            background: transparent;
            border: none;
            color: #0d6efd;
            font-size: 1.2rem;
            padding: 0.375rem 0.5rem;
        }
        .receipt-section {
            padding-top: 20px;
        }
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }
        .tab-content {
            padding-top: 20px;
        }
        .receipt-type-container {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="container">
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
            <div class="tab-pane fade show active" id="selling-1" role="tabpanel">
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
                            <label class="form-label">بەکارهێنەر</label>
                            <input type="text" class="form-control receipt-customer" placeholder="ناوی بەکارهێنەر">
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">بەرواری دەستپێکردن</label>
                            <input type="date" class="form-control start-date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بەرواری تەواوبوون</label>
                            <input type="date" class="form-control end-date">
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            $('.start-date, .end-date').val(today);

            // Tab counter for each type
            let tabCounters = {
                'selling': 1,
                'buying': 0,
                'wasting': 0
            };

            // Current active receipt type
            let activeReceiptType = 'selling';

            // Calculate row total
            function calculateRowTotal(row) {
                const price = parseFloat($(row).find('.price').val()) || 0;
                const quantity = parseFloat($(row).find('.quantity').val()) || 0;
                $(row).find('.total').val((price * quantity).toFixed(2));
                
                // Calculate grand total for the current tab
                const tabId = $('.tab-pane.active').attr('id');
                calculateGrandTotal(tabId);
            }

            // Calculate grand total for a specific tab
            function calculateGrandTotal(tabId) {
                const tabPane = $('#' + tabId);
                let subtotal = 0;
                
                tabPane.find('.total').each(function() {
                    subtotal += parseFloat($(this).val()) || 0;
                });
                
                const tax = parseFloat(tabPane.find('.tax').val()) || 0;
                const discount = parseFloat(tabPane.find('.discount').val()) || 0;
                const grandTotal = subtotal + tax - discount;
                
                tabPane.find('.subtotal').val(subtotal.toFixed(2));
                tabPane.find('.grand-total').val(grandTotal.toFixed(2));
            }

            // Add new row to a table
            $(document).on('click', '.add-row-btn', function() {
                const tabPane = $(this).closest('.tab-pane');
                const itemsList = tabPane.find('.items-list');
                const lastRow = itemsList.find('tr:last');
                const newRow = lastRow.clone();
                const rowNumber = itemsList.find('tr').length + 1;
                
                newRow.find('td:first').text(rowNumber);
                newRow.find('input').val('');
                newRow.appendTo(itemsList);
            });

            // Calculate totals on input
            $(document).on('input', '.price, .quantity', function() {
                calculateRowTotal($(this).closest('tr'));
            });

            // Remove row
            $(document).on('click', '.remove-row', function() {
                const itemsList = $(this).closest('.items-list');
                if (itemsList.find('tr').length > 1) {
                    $(this).closest('tr').remove();
                    
                    // Renumber rows
                    itemsList.find('tr').each(function(index) {
                        $(this).find('td:first').text(index + 1);
                    });
                    
                    // Recalculate totals
                    const tabId = $(this).closest('.tab-pane').attr('id');
                    calculateGrandTotal(tabId);
                }
            });

            // Receipt type buttons
            $('.receipt-type-btn').click(function() {
                $('.receipt-type-btn').removeClass('active');
                $(this).addClass('active');
                activeReceiptType = $(this).data('type');
            });

            // Tax and discount updates
            $(document).on('input', '.tax, .discount', function() {
                const tabId = $(this).closest('.tab-pane').attr('id');
                calculateGrandTotal(tabId);
            });

            // Add new tab
            $('#addNewTab').click(function() {
                // Increment counter for current receipt type
                tabCounters[activeReceiptType]++;
                const newTabCount = tabCounters[activeReceiptType];
                
                // Create new tab ID
                const newTabId = activeReceiptType + '-' + newTabCount;
                
                // Get receipt type label
                let receiptTypeLabel;
                switch(activeReceiptType) {
                    case 'selling':
                        receiptTypeLabel = 'فرۆشتن';
                        break;
                    case 'buying':
                        receiptTypeLabel = 'کڕین';
                        break;
                    case 'wasting':
                        receiptTypeLabel = 'ڕێکخستنەوە';
                        break;
                }
                
                // Create new tab
                const newTab = `
                    <li class="nav-item" role="presentation">
                        <button class="nav-link receipt-tab" id="tab-${newTabId}" data-bs-toggle="tab" data-bs-target="#${newTabId}" type="button" role="tab">
                            ${receiptTypeLabel} #${newTabCount}
                            <span class="close-tab"><i class="fas fa-times"></i></span>
                        </button>
                    </li>
                `;
                
                // Insert new tab before the add tab button
                $(this).parent().before(newTab);
                
                // Create new tab content by cloning the first tab
                const firstTabContent = $('#selling-1').html();
                const newTabContent = `
                    <div class="tab-pane fade" id="${newTabId}" role="tabpanel">
                        ${firstTabContent}
                    </div>
                `;
                
                // Append new tab content
                $('#receiptTabsContent').append(newTabContent);
                
                // Clear inputs in the new tab
                $('#' + newTabId).find('input').val('');
                $('#' + newTabId).find('.start-date, .end-date').val(today);
                
                // Activate the new tab
                $(`#tab-${newTabId}`).tab('show');
            });
            
            // Close tab
            $(document).on('click', '.close-tab', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // If there's only one tab, don't close it
                if ($('.nav-tabs .nav-item').length <= 2) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ناتوانیت داخستن!',
                        text: 'لانیکەم یەک پسوڵە پێویستە',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }
                
                // Get the tab ID
                const tabId = $(this).parent().attr('data-bs-target').substring(1);
                
                // If this is the active tab, select another tab
                if ($(this).parent().hasClass('active')) {
                    // Find the previous or next tab
                    const tabToActivate = $(this).closest('.nav-item').prev('.nav-item').length ? 
                        $(this).closest('.nav-item').prev('.nav-item').find('.nav-link') : 
                        $(this).closest('.nav-item').next('.nav-item').next('.nav-item').find('.nav-link');
                    
                    tabToActivate.tab('show');
                }
                
                // Remove tab and content
                $(this).closest('.nav-item').remove();
                $('#' + tabId).remove();
            });
        });
    </script>
</body>
</html> 