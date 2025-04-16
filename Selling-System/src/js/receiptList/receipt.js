$(document).ready(function() {
    // Initialize DataTables
    const sellingTable = initializeDataTable('#sellingTable');
    const buyingTable = initializeDataTable('#buyingTable');
    const wastingTable = initializeDataTable('#wastingTable');

    // Load initial data
    loadSellingReceipts();
    
    // Handle tab switching to load relevant data
    $('#receiptTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href");
        if (target === '#selling') {
            loadSellingReceipts();
        } else if (target === '#buying') {
            loadBuyingReceipts();
        } else if (target === '#wasting') {
            loadWastingReceipts();
        }
    });

    // Refresh button click handler
    $('#refreshBtn').on('click', function() {
        const activeTab = $('.nav-tabs .active').attr('id');
        if (activeTab === 'selling-tab') {
            loadSellingReceipts();
        } else if (activeTab === 'buying-tab') {
            loadBuyingReceipts();
        } else if (activeTab === 'wasting-tab') {
            loadWastingReceipts();
        }
    });

    // Filter handlers for selling receipts
    $('#sellingSearchBox').on('keyup', function() {
        sellingTable.search($(this).val()).draw();
    });

    $('#sellingStartDate, #sellingEndDate').on('change', function() {
        applyDateFilter(sellingTable);
    });

    // Filter handlers for buying receipts
    $('#buyingSearchBox').on('keyup', function() {
        buyingTable.search($(this).val()).draw();
    });

    $('#buyingStartDate, #buyingEndDate').on('change', function() {
        applyDateFilter(buyingTable);
    });

    // Filter handlers for wasting receipts
    $('#wastingSearchBox').on('keyup', function() {
        wastingTable.search($(this).val()).draw();
    });

    $('#wastingStartDate, #wastingEndDate').on('change', function() {
        applyDateFilter(wastingTable);
    });

    // View receipt details
    $(document).on('click', '.view-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        // Load receipt details
        loadReceiptDetails(receiptId, receiptType);
    });

    // Edit receipt
    $(document).on('click', '.edit-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        // Redirect to edit page
        window.location.href = `addReceipt.php?id=${receiptId}&type=${receiptType}&edit=true`;
    });

    // Edit button in modal
    $('#editReceiptBtn').on('click', function() {
        const receiptId = $('#modalReceiptNumber').text();
        const receiptType = $('#receiptDetailsModal').data('receiptType');
        
        // Redirect to edit page
        window.location.href = `addReceipt.php?id=${receiptId}&type=${receiptType}&edit=true`;
    });

    // Print receipt
    $(document).on('click', '.print-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        printReceipt(receiptId, receiptType);
    });

    // Print button in modal
    $('#printReceiptBtn').on('click', function() {
        const receiptId = $('#modalReceiptNumber').text();
        const receiptType = $('#receiptDetailsModal').data('receiptType');
        
        printReceipt(receiptId, receiptType);
    });

    // Delete receipt confirmation
    $(document).on('click', '.delete-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        // Set values in the delete confirmation modal
        $('#deleteReceiptNumber').text(receiptId);
        $('#deleteReceiptModal').data('receiptId', receiptId);
        $('#deleteReceiptModal').data('receiptType', receiptType);
        
        // Show the delete confirmation modal
        $('#deleteReceiptModal').modal('show');
    });

    // Delete receipt confirm button
    $('#confirmDeleteBtn').on('click', function() {
        const receiptId = $('#deleteReceiptModal').data('receiptId');
        const receiptType = $('#deleteReceiptModal').data('receiptType');
        
        // Delete the receipt
        deleteReceipt(receiptId, receiptType);
    });

    // Initialize date inputs with default values
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    if (!$('#employeePaymentStartDate').val()) {
        $('#employeePaymentStartDate').val(formatDate(firstDay));
    }
    
    if (!$('#employeePaymentEndDate').val()) {
        $('#employeePaymentEndDate').val(formatDate(today));
    }
    
    if (!$('#shippingStartDate').val()) {
        $('#shippingStartDate').val(formatDate(firstDay));
    }
    
    if (!$('#shippingEndDate').val()) {
        $('#shippingEndDate').val(formatDate(today));
    }
    
    if (!$('#withdrawalStartDate').val()) {
        $('#withdrawalStartDate').val(formatDate(firstDay));
    }
    
    if (!$('#withdrawalEndDate').val()) {
        $('#withdrawalEndDate').val(formatDate(today));
    }
    
    // Helper function to format date as YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Apply filter for sales data
    function applySalesFilter() {
        const startDate = $('#employeePaymentStartDate').val();
        const endDate = $('#employeePaymentEndDate').val();
        const customerName = $('#employeePaymentName').val();
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'filter',
                type: 'sales',
                start_date: startDate,
                end_date: endDate,
                customer_name: customerName
            },
            dataType: 'json',
            beforeSend: function() {
                // Show loading state
                $('#employeeHistoryTable tbody').html('<tr><td colspan="13" class="text-center">جاوەڕێ بکە...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    // Update table with filtered data
                    updateSalesTable(response.data);
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }
    
    // Update sales table
    function updateSalesTable(data) {
        let html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="13" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>';
        } else {
            data.forEach(function(sale, index) {
                const paymentTypeClass = sale.payment_type === 'cash' ? 'bg-success' : 
                                        (sale.payment_type === 'credit' ? 'bg-warning' : 'bg-info');
                
                const paymentTypeText = sale.payment_type === 'cash' ? 'نەقد' : 
                                       (sale.payment_type === 'credit' ? 'قەرز' : 'چەک');
                
                // Format date to Y/m/d
                const dateObj = new Date(sale.date);
                const formattedDate = dateObj.getFullYear() + '/' + 
                                     String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                     String(dateObj.getDate()).padStart(2, '0');
                
                html += `
                    <tr data-id="${sale.id}">
                        <td>${index + 1}</td>
                        <td>${sale.invoice_number || 'N/A'}</td>
                        <td>${sale.customer_name || 'N/A'}</td>
                        <td>${formattedDate}</td>
                        <td class="products-list-cell" data-products="${sale.products_list || ''}">
                            ${sale.products_list || ''}
                            <div class="products-popup"></div>
                        </td>
                        <td>${sale.subtotal ? new Intl.NumberFormat().format(sale.subtotal) + ' د.ع' : '0 د.ع'}</td>
                        <td>${sale.shipping_cost ? new Intl.NumberFormat().format(sale.shipping_cost) + ' د.ع' : '0 د.ع'}</td>
                        <td>${sale.other_costs ? new Intl.NumberFormat().format(sale.other_costs) + ' د.ع' : '0 د.ع'}</td>
                        <td>${sale.discount ? new Intl.NumberFormat().format(sale.discount) + ' د.ع' : '0 د.ع'}</td>
                        <td>${sale.total_amount ? new Intl.NumberFormat().format(sale.total_amount) + ' د.ع' : '0 د.ع'}</td>
                        <td>
                            <span class="badge rounded-pill ${paymentTypeClass}">
                                ${paymentTypeText}
                            </span>
                        </td>
                        <td>${sale.notes || ''}</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${sale.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${sale.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${sale.id}">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        $('#employeeHistoryTable tbody').html(html);
        
        // Initialize product list popups
        initializeProductListPopups();
        
        // Update pagination info
        updatePaginationInfo('employee', data.length, 1, data.length, data.length);
    }
    
    // Apply filter for purchases data
    function applyPurchasesFilter() {
        const startDate = $('#shippingStartDate').val();
        const endDate = $('#shippingEndDate').val();
        const supplierName = $('#shippingProvider').val();
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'filter',
                type: 'purchases',
                start_date: startDate,
                end_date: endDate,
                supplier_name: supplierName
            },
            dataType: 'json',
            beforeSend: function() {
                // Show loading state
                $('#shippingHistoryTable tbody').html('<tr><td colspan="11" class="text-center">جاوەڕێ بکە...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    // Update table with filtered data
                    updatePurchasesTable(response.data);
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }
    
    // Update purchases table 
    function updatePurchasesTable(data) {
        let html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="11" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>';
        } else {
            data.forEach(function(purchase, index) {
                const paymentTypeClass = purchase.payment_type === 'cash' ? 'bg-success' : 
                                        (purchase.payment_type === 'credit' ? 'bg-warning' : 'bg-info');
                
                const paymentTypeText = purchase.payment_type === 'cash' ? 'نەقد' : 
                                       (purchase.payment_type === 'credit' ? 'قەرز' : 'چەک');
                
                // Format date to Y/m/d
                const dateObj = new Date(purchase.date);
                const formattedDate = dateObj.getFullYear() + '/' + 
                                     String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                     String(dateObj.getDate()).padStart(2, '0');
                
                html += `
                    <tr data-id="${purchase.id}">
                        <td>${index + 1}</td>
                        <td>${purchase.invoice_number || 'N/A'}</td>
                        <td>${purchase.supplier_name || 'N/A'}</td>
                        <td>${formattedDate}</td>
                        <td class="products-list-cell" data-products="${purchase.products_list || ''}">
                            ${purchase.products_list || ''}
                            <div class="products-popup"></div>
                        </td>
                        <td>${purchase.subtotal ? new Intl.NumberFormat().format(purchase.subtotal) + ' د.ع' : '0 د.ع'}</td>
                        <td>${purchase.discount ? new Intl.NumberFormat().format(purchase.discount) + ' د.ع' : '0 د.ع'}</td>
                        <td>${purchase.total_amount ? new Intl.NumberFormat().format(purchase.total_amount) + ' د.ع' : '0 د.ع'}</td>
                        <td>
                            <span class="badge rounded-pill ${paymentTypeClass}">
                                ${paymentTypeText}
                            </span>
                        </td>
                        <td>${purchase.notes || ''}</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${purchase.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${purchase.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${purchase.id}">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        $('#shippingHistoryTable tbody').html(html);
        
        // Initialize product list popups
        initializeProductListPopups();
        
        // Update pagination info
        updatePaginationInfo('shipping', data.length, 1, data.length, data.length);
    }
    
    // Initialize product list popups
    function initializeProductListPopups() {
        // Products list popup functionality
        $('.products-list-cell').hover(
            function() {
                const products = $(this).data('products');
                if (products && products.trim() !== '') {
                    const $popup = $(this).find('.products-popup');
                    // Format products list for better readability
                    const productItems = products.split(', ').map(item => {
                        return `<div class="product-item">${item}</div>`;
                    }).join('');
                    
                    $popup.html(productItems);
                    $popup.show();
                }
            },
            function() {
                $(this).find('.products-popup').hide();
            }
        );
        
        // Click event to keep popup open
        $('.products-list-cell').click(function() {
            const products = $(this).data('products');
            if (products && products.trim() !== '') {
                // Use SweetAlert2 for better display on click
                Swal.fire({
                    title: 'کاڵاکان',
                    html: products.split(', ').map(item => {
                        return `<div style="text-align: right; padding: 5px 0; border-bottom: 1px solid #eee;">${item}</div>`;
                    }).join(''),
                    confirmButtonText: 'داخستن',
                    customClass: {
                        container: 'rtl-swal',
                        popup: 'rtl-swal-popup',
                        title: 'rtl-swal-title',
                        htmlContainer: 'rtl-swal-html',
                        confirmButton: 'rtl-swal-confirm'
                    }
                });
            }
        });
    }
    
    // Helper function to update pagination info
    function updatePaginationInfo(prefix, totalRecords, currentPage, recordsPerPage, filteredRecords) {
        const startRecord = filteredRecords > 0 ? (currentPage - 1) * recordsPerPage + 1 : 0;
        const endRecord = Math.min(startRecord + recordsPerPage - 1, filteredRecords);
        
        $(`#${prefix}StartRecord`).text(startRecord);
        $(`#${prefix}EndRecord`).text(endRecord);
        $(`#${prefix}TotalRecords`).text(filteredRecords);
    }
    
    // Event listeners for filters
    $('.auto-filter').on('change', function() {
        // Determine which tab is active
        const activeTab = $('.expenses-tabs .nav-link.active').attr('id');
        
        if (activeTab === 'employee-payment-tab') {
            applySalesFilter();
        } else if (activeTab === 'shipping-tab') {
            applyPurchasesFilter();
        } else if (activeTab === 'withdrawal-tab') {
            // Handle for waste items if needed
        }
    });
    
    // Reset filters
    $('#employeePaymentResetFilter').on('click', function() {
        $('#employeePaymentStartDate').val(formatDate(firstDay));
        $('#employeePaymentEndDate').val(formatDate(today));
        $('#employeePaymentName').val('').trigger('change');
        
        // Apply default filters
        applySalesFilter();
    });
    
    $('#shippingResetFilter').on('click', function() {
        $('#shippingStartDate').val(formatDate(firstDay));
        $('#shippingEndDate').val(formatDate(today));
        $('#shippingProvider').val('').trigger('change');
        
        // Apply default filters
        applyPurchasesFilter();
    });
    
    $('#withdrawalResetFilter').on('click', function() {
        $('#withdrawalStartDate').val(formatDate(firstDay));
        $('#withdrawalEndDate').val(formatDate(today));
        
        // Apply default filters for waste items if needed
    });
    
    // Tab change event
    $('#expensesTabs button').on('shown.bs.tab', function (e) {
        const targetId = $(e.target).attr('id');
        
        // If switching to sales tab, refresh data
        if (targetId === 'employee-payment-tab') {
            applySalesFilter();
        } 
        // If switching to purchases tab, refresh data
        else if (targetId === 'shipping-tab') {
            applyPurchasesFilter();
        }
        // If switching to waste tab, refresh data
        else if (targetId === 'withdrawal-tab') {
            // Handle for waste items if needed
        }
    });
    
    // Refresh button click events
    $('.refresh-btn').on('click', function() {
        const activeTab = $('.expenses-tabs .nav-link.active').attr('id');
        
        if (activeTab === 'employee-payment-tab') {
            applySalesFilter();
        } else if (activeTab === 'shipping-tab') {
            applyPurchasesFilter();
        } else if (activeTab === 'withdrawal-tab') {
            // Handle for waste items if needed
        }
    });

    // Initial load of data
    applySalesFilter();
});

// Function to initialize DataTables
function initializeDataTable(tableId) {
    return $(tableId).DataTable({
        responsive: true,
        dom: '<"top"fl>rt<"bottom"ip>',
        language: {
            search: "گەڕان:",
            lengthMenu: "پیشاندانی _MENU_ تۆماری",
            info: "پیشاندانی _START_ بۆ _END_ لە _TOTAL_ تۆماری",
            paginate: {
                first: "یەکەم",
                last: "کۆتایی",
                next: "دواتر",
                previous: "پێشتر"
            }
        },
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 6] },
            { responsivePriority: 2, targets: [2, 4] }
        ]
    });
}

// Function to load selling receipts
function loadSellingReceipts() {
    // Show loading spinner
    showLoadingSpinner('#sellingTable');
    
    // AJAX request to get selling receipts
    $.ajax({
        url: '../../process/get_receipts.php',
        type: 'POST',
        data: { type: 'selling' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateSellingTable(response.data);
            } else {
                showErrorAlert(response.message);
            }
        },
        error: function(xhr, status, error) {
            showErrorAlert('خەتا ڕوویدا: ' + error);
        },
        complete: function() {
            hideLoadingSpinner('#sellingTable');
        }
    });
}

// Function to load buying receipts
function loadBuyingReceipts() {
    // Show loading spinner
    showLoadingSpinner('#buyingTable');
    
    // AJAX request to get buying receipts
    $.ajax({
        url: '../../process/get_receipts.php',
        type: 'POST',
        data: { type: 'buying' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateBuyingTable(response.data);
            } else {
                showErrorAlert(response.message);
            }
        },
        error: function(xhr, status, error) {
            showErrorAlert('خەتا ڕوویدا: ' + error);
        },
        complete: function() {
            hideLoadingSpinner('#buyingTable');
        }
    });
}

// Function to load wasting receipts
function loadWastingReceipts() {
    // Show loading spinner
    showLoadingSpinner('#wastingTable');
    
    // AJAX request to get wasting receipts
    $.ajax({
        url: '../../process/get_receipts.php',
        type: 'POST',
        data: { type: 'wasting' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateWastingTable(response.data);
            } else {
                showErrorAlert(response.message);
            }
        },
        error: function(xhr, status, error) {
            showErrorAlert('خەتا ڕوویدا: ' + error);
        },
        complete: function() {
            hideLoadingSpinner('#wastingTable');
        }
    });
}

// Function to update selling receipts table
function updateSellingTable(data) {
    const table = $('#sellingTable').DataTable();
    
    // Clear the table
    table.clear();
    
    // Add rows to the table
    data.forEach(function(receipt) {
        const statusBadge = getStatusBadge(receipt.status);
        const actionButtons = `
            <div class="action-buttons">
                <button class="btn btn-sm btn-outline-info view-receipt" data-id="${receipt.id}" data-type="selling" title="بینین">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-success edit-receipt" data-id="${receipt.id}" data-type="selling" title="دەستکاریکردن">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary print-receipt" data-id="${receipt.id}" data-type="selling" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-receipt" data-id="${receipt.id}" data-type="selling" title="سڕینەوە">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        table.row.add([
            receipt.id,
            receipt.title,
            receipt.customer,
            formatDate(receipt.date),
            formatCurrency(receipt.total),
            statusBadge,
            actionButtons
        ]);
    });
    
    // Draw the table
    table.draw();
}

// Function to update buying receipts table
function updateBuyingTable(data) {
    const table = $('#buyingTable').DataTable();
    
    // Clear the table
    table.clear();
    
    // Add rows to the table
    data.forEach(function(receipt) {
        const statusBadge = getStatusBadge(receipt.status);
        const actionButtons = `
            <div class="action-buttons">
                <button class="btn btn-sm btn-outline-info view-receipt" data-id="${receipt.id}" data-type="buying" title="بینین">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-success edit-receipt" data-id="${receipt.id}" data-type="buying" title="دەستکاریکردن">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary print-receipt" data-id="${receipt.id}" data-type="buying" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-receipt" data-id="${receipt.id}" data-type="buying" title="سڕینەوە">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        table.row.add([
            receipt.id,
            receipt.title,
            receipt.vendor,
            formatDate(receipt.date),
            receipt.vendor_invoice,
            formatCurrency(receipt.total),
            statusBadge,
            actionButtons
        ]);
    });
    
    // Draw the table
    table.draw();
}

// Function to update wasting receipts table
function updateWastingTable(data) {
    const table = $('#wastingTable').DataTable();
    
    // Clear the table
    table.clear();
    
    // Add rows to the table
    data.forEach(function(receipt) {
        const statusBadge = getStatusBadge(receipt.status);
        const actionButtons = `
            <div class="action-buttons">
                <button class="btn btn-sm btn-outline-info view-receipt" data-id="${receipt.id}" data-type="wasting" title="بینین">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-success edit-receipt" data-id="${receipt.id}" data-type="wasting" title="دەستکاریکردن">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary print-receipt" data-id="${receipt.id}" data-type="wasting" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-receipt" data-id="${receipt.id}" data-type="wasting" title="سڕینەوە">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        table.row.add([
            receipt.id,
            receipt.title,
            receipt.responsible,
            formatDate(receipt.date),
            translateReason(receipt.reason),
            formatCurrency(receipt.total),
            statusBadge,
            actionButtons
        ]);
    });
    
    // Draw the table
    table.draw();
}

// Function to load receipt details
function loadReceiptDetails(receiptId, receiptType) {
    // Show loading spinner
    Swal.fire({
        title: 'تکایە چاوەڕوان بە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // AJAX request to get receipt details
    $.ajax({
        url: '../../process/get_receipt_details.php',
        type: 'POST',
        data: { 
            id: receiptId,
            type: receiptType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.close();
                showReceiptDetails(response.data, receiptType);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خەتا',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'خەتا',
                text: 'خەتا ڕوویدا: ' + error
            });
        }
    });
}

// Function to show receipt details in modal
function showReceiptDetails(data, receiptType) {
    // Set modal title and content
    $('#modalReceiptNumber').text(data.id);
    $('#modalReceiptTitle').text(data.title);
    $('#modalReceiptDate').text(formatDate(data.date));
    
    // Set party name based on receipt type
    if (receiptType === 'selling') {
        $('#modalReceiptParty').text(data.customer);
    } else if (receiptType === 'buying') {
        $('#modalReceiptParty').text(data.vendor);
    } else if (receiptType === 'wasting') {
        $('#modalReceiptParty').text(data.responsible);
    }
    
    // Clear the items table
    $('#modalReceiptItems tbody').empty();
    
    // Add items to the table
    data.items.forEach(function(item, index) {
        $('#modalReceiptItems tbody').append(`
            <tr>
                <td>${index + 1}</td>
                <td>${item.product_name}</td>
                <td>${formatCurrency(item.price)}</td>
                <td>${item.quantity}</td>
                <td>${formatCurrency(item.total)}</td>
            </tr>
        `);
    });
    
    // Set the total
    $('#modalReceiptTotal').text(formatCurrency(data.total));
    
    // Set notes
    $('#modalReceiptNotes').text(data.notes || 'هیچ تێبینیەک نییە');
    
    // Store the receipt type in the modal
    $('#receiptDetailsModal').data('receiptType', receiptType);
    
    // Show the modal
    $('#receiptDetailsModal').modal('show');
}

// Function to delete receipt
function deleteReceipt(receiptId, receiptType) {
    // Close the confirmation modal
    $('#deleteReceiptModal').modal('hide');
    
    // Show loading spinner
    Swal.fire({
        title: 'تکایە چاوەڕوان بە...',
        text: 'سڕینەوەی پسوڵە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // AJAX request to delete receipt
    $.ajax({
        url: '../../process/delete_receipt.php',
        type: 'POST',
        data: { 
            id: receiptId,
            type: receiptType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: 'پسوڵە بە سەرکەوتوویی سڕایەوە'
                }).then(() => {
                    // Reload the receipts list
                    if (receiptType === 'selling') {
                        loadSellingReceipts();
                    } else if (receiptType === 'buying') {
                        loadBuyingReceipts();
                    } else if (receiptType === 'wasting') {
                        loadWastingReceipts();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خەتا',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'خەتا',
                text: 'خەتا ڕوویدا: ' + error
            });
        }
    });
}

// Function to print receipt
function printReceipt(receiptId, receiptType) {
    // Open the print window
    window.open(`../../process/print_receipt.php?id=${receiptId}&type=${receiptType}`, '_blank');
}

// Function to apply date filter
function applyDateFilter(table) {
    // Custom search function for date range
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        let startDate, endDate, dateColumn;
        
        // Determine which table and get the relevant dates
        if (settings.nTable.id === 'sellingTable') {
            startDate = $('#sellingStartDate').val();
            endDate = $('#sellingEndDate').val();
            dateColumn = 3; // Date column index
        } else if (settings.nTable.id === 'buyingTable') {
            startDate = $('#buyingStartDate').val();
            endDate = $('#buyingEndDate').val();
            dateColumn = 3; // Date column index
        } else if (settings.nTable.id === 'wastingTable') {
            startDate = $('#wastingStartDate').val();
            endDate = $('#wastingEndDate').val();
            dateColumn = 3; // Date column index
        } else {
            return true; // Not a table we're interested in
        }
        
        // If no date filter is set, show all rows
        if (!startDate && !endDate) {
            return true;
        }
        
        // Parse the dates from the table
        const rowDate = parseDate(data[dateColumn]);
        
        // Check if the date is within the range
        if (startDate && !endDate) {
            return rowDate >= new Date(startDate);
        } else if (!startDate && endDate) {
            return rowDate <= new Date(endDate);
        } else {
            return rowDate >= new Date(startDate) && rowDate <= new Date(endDate);
        }
    });
    
    // Redraw the table
    table.draw();
    
    // Remove the custom search function after it's done
    $.fn.dataTable.ext.search.pop();
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ku-IQ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Helper function to parse date from formatted string
function parseDate(dateString) {
    // Split the date string into parts
    const parts = dateString.split('/');
    
    // Create a new date object (format depends on the display format)
    return new Date(parts[2], parts[1] - 1, parts[0]);
}

// Helper function to format currency
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2) + ' $';
}

// Helper function to get status badge HTML
function getStatusBadge(status) {
    switch (status) {
        case 'active':
        case 'completed':
            return '<span class="badge bg-success">تەواوکراو</span>';
        case 'pending':
            return '<span class="badge bg-warning">چاوەڕوان</span>';
        case 'canceled':
            return '<span class="badge bg-danger">هەڵوەشاوەتەوە</span>';
        default:
            return '<span class="badge bg-secondary">نادیار</span>';
    }
}

// Helper function to translate reason codes
function translateReason(reason) {
    switch (reason) {
        case 'damaged':
            return 'کاڵای زیانمەند';
        case 'expired':
            return 'کاڵای بەسەرچوو';
        case 'inventory_correction':
            return 'ڕاستکردنەوەی ئینڤێنتۆری';
        case 'other':
            return 'هۆکاری تر';
        default:
            return reason;
    }
}

// Helper function to show loading spinner
function showLoadingSpinner(tableId) {
    $(tableId).closest('.card-body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
}

// Helper function to hide loading spinner
function hideLoadingSpinner(tableId) {
    $(tableId).closest('.card-body').find('.loading-overlay').remove();
}

// Helper function to show error alert
function showErrorAlert(message) {
    Swal.fire({
        icon: 'error',
        title: 'خەتا',
        text: message
    });
} 

     // Function to handle products list display
     $(document).ready(function() {
        // Products list popup functionality
        $('.products-list-cell').hover(
            function() {
                const products = $(this).data('products');
                if (products && products.trim() !== '') {
                    const $popup = $(this).find('.products-popup');
                    // Format products list for better readability
                    const productItems = products.split(', ').map(item => {
                        return `<div class="product-item">${item}</div>`;
                    }).join('');
                    
                    $popup.html(productItems);
                    $popup.show();
                }
            },
            function() {
                $(this).find('.products-popup').hide();
            }
        );
        
        // Click event to keep popup open
        $('.products-list-cell').click(function() {
            const products = $(this).data('products');
            if (products && products.trim() !== '') {
                // Use SweetAlert2 for better display on click
                Swal.fire({
                    title: 'کاڵاکان',
                    html: products.split(', ').map(item => {
                        return `<div style="text-align: right; padding: 5px 0; border-bottom: 1px solid #eee;">${item}</div>`;
                    }).join(''),
                    confirmButtonText: 'داخستن',
                    customClass: {
                        container: 'rtl-swal',
                        popup: 'rtl-swal-popup',
                        title: 'rtl-swal-title',
                        htmlContainer: 'rtl-swal-html',
                        confirmButton: 'rtl-swal-confirm'
                    }
                });
            }
        });

        // Handle filter changes for sales
        $('.auto-filter').on('change input', function() {
            if ($('#employee-payment-content').hasClass('show')) {
                filterSalesData();
            } else if ($('#shipping-content').hasClass('show')) {
                filterPurchasesData();
            }
        });

        // Reset filter button for sales
        $('#employeePaymentResetFilter').click(function() {
            $('#employeePaymentStartDate').val('');
            $('#employeePaymentEndDate').val('');
            $('#employeePaymentName').val('');
            $('#invoiceNumber').val('');
            filterSalesData();
        });

        // Reset filter button for purchases
        $('#shippingResetFilter').click(function() {
            $('#shippingStartDate').val('');
            $('#shippingEndDate').val('');
            $('#shippingProvider').val('');
            $('#shippingInvoiceNumber').val('');
            filterPurchasesData();
        });

        // Records per page functionality for sales
        let currentSalesPage = 1;
        const salesRecordsPerPageSelect = $('#employeeRecordsPerPage');
        let salesRecordsPerPage = parseInt(salesRecordsPerPageSelect.val());
        
        // Update records per page when select changes for sales
        salesRecordsPerPageSelect.on('change', function() {
            salesRecordsPerPage = parseInt($(this).val());
            currentSalesPage = 1; // Reset to first page
            updateSalesDisplayedRows();
        });
        
        // Records per page functionality for purchases
        let currentPurchasesPage = 1;
        const purchasesRecordsPerPageSelect = $('#shippingRecordsPerPage');
        let purchasesRecordsPerPage = parseInt(purchasesRecordsPerPageSelect.val());
        
        // Update records per page when select changes for purchases
        purchasesRecordsPerPageSelect.on('change', function() {
            purchasesRecordsPerPage = parseInt($(this).val());
            currentPurchasesPage = 1; // Reset to first page
            updatePurchasesDisplayedRows();
        });
        
        // Records per page functionality for waste
        let currentWastePage = 1;
        const wasteRecordsPerPageSelect = $('#withdrawalRecordsPerPage');
        let wasteRecordsPerPage = parseInt(wasteRecordsPerPageSelect.val());
        
        // Update records per page when select changes for waste
        wasteRecordsPerPageSelect.on('change', function() {
            wasteRecordsPerPage = parseInt($(this).val());
            currentWastePage = 1; // Reset to first page
            updateWasteDisplayedRows();
        });
        
        // Pagination navigation for sales
        $('#employeePrevPageBtn').on('click', function() {
            if (!$(this).prop('disabled')) {
                currentSalesPage--;
                updateSalesDisplayedRows();
            }
        });
        
        $('#employeeNextPageBtn').on('click', function() {
            if (!$(this).prop('disabled')) {
                currentSalesPage++;
                updateSalesDisplayedRows();
            }
        });
        
        // Pagination navigation for purchases
        $('#shippingPrevPageBtn').on('click', function() {
            if (!$(this).prop('disabled')) {
                currentPurchasesPage--;
                updatePurchasesDisplayedRows();
            }
        });
        
        $('#shippingNextPageBtn').on('click', function() {
            if (!$(this).prop('disabled')) {
                currentPurchasesPage++;
                updatePurchasesDisplayedRows();
            }
        });
        
        // Pagination navigation for waste
        $('#withdrawalPrevPageBtn').on('click', function() {
            if (!$(this).prop('disabled')) {
                currentWastePage--;
                updateWasteDisplayedRows();
            }
        });
        
        $('#withdrawalNextPageBtn').on('click', function() {
            if (!$(this).prop('disabled')) {
                currentWastePage++;
                updateWasteDisplayedRows();
            }
        });
        
        // Function to update sales table displayed rows
        function updateSalesDisplayedRows() {
            const tableRows = $('#employeeHistoryTable tbody tr');
            const totalRecords = tableRows.length;
            
            if (totalRecords === 0) return;
            
            const startIndex = (currentSalesPage - 1) * salesRecordsPerPage;
            const endIndex = startIndex + salesRecordsPerPage;
            
            // Hide all rows
            tableRows.hide();
            
            // Show only rows for current page
            tableRows.slice(startIndex, endIndex).show();
            
            // Update pagination info
            $('#employeeStartRecord').text(totalRecords > 0 ? startIndex + 1 : 0);
            $('#employeeEndRecord').text(Math.min(endIndex, totalRecords));
            $('#employeeTotalRecords').text(totalRecords);
            
            // Enable/disable pagination buttons
            $('#employeePrevPageBtn').prop('disabled', currentSalesPage === 1);
            $('#employeeNextPageBtn').prop('disabled', endIndex >= totalRecords);
            
            // Update pagination numbers
            updateSalesPaginationNumbers();
        }
        
        // Function to update purchases table displayed rows
        function updatePurchasesDisplayedRows() {
            const tableRows = $('#shippingHistoryTable tbody tr');
            const totalRecords = tableRows.length;
            
            if (totalRecords === 0) return;
            
            const startIndex = (currentPurchasesPage - 1) * purchasesRecordsPerPage;
            const endIndex = startIndex + purchasesRecordsPerPage;
            
            // Hide all rows
            tableRows.hide();
            
            // Show only rows for current page
            tableRows.slice(startIndex, endIndex).show();
            
            // Update pagination info
            $('#shippingStartRecord').text(totalRecords > 0 ? startIndex + 1 : 0);
            $('#shippingEndRecord').text(Math.min(endIndex, totalRecords));
            $('#shippingTotalRecords').text(totalRecords);
            
            // Enable/disable pagination buttons
            $('#shippingPrevPageBtn').prop('disabled', currentPurchasesPage === 1);
            $('#shippingNextPageBtn').prop('disabled', endIndex >= totalRecords);
            
            // Update pagination numbers
            updatePurchasesPaginationNumbers();
        }
        
        // Function to update waste table displayed rows
        function updateWasteDisplayedRows() {
            const tableRows = $('#withdrawalHistoryTable tbody tr');
            const totalRecords = tableRows.length;
            
            if (totalRecords === 0) return;
            
            const startIndex = (currentWastePage - 1) * wasteRecordsPerPage;
            const endIndex = startIndex + wasteRecordsPerPage;
            
            // Hide all rows
            tableRows.hide();
            
            // Show only rows for current page
            tableRows.slice(startIndex, endIndex).show();
            
            // Update pagination info
            $('#withdrawalStartRecord').text(totalRecords > 0 ? startIndex + 1 : 0);
            $('#withdrawalEndRecord').text(Math.min(endIndex, totalRecords));
            $('#withdrawalTotalRecords').text(totalRecords);
            
            // Enable/disable pagination buttons
            $('#withdrawalPrevPageBtn').prop('disabled', currentWastePage === 1);
            $('#withdrawalNextPageBtn').prop('disabled', endIndex >= totalRecords);
            
            // Update pagination numbers
            updateWastePaginationNumbers();
        }
        
        // Function to update sales pagination number buttons
        function updateSalesPaginationNumbers() {
            const totalRecords = $('#employeeHistoryTable tbody tr').length;
            const totalPages = Math.ceil(totalRecords / salesRecordsPerPage);
            
            let paginationHTML = '';
            
            // Determine range of page numbers to show
            let startPage = Math.max(1, currentSalesPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            // Ensure we always show 5 page numbers if possible
            if (endPage - startPage < 4 && totalPages > 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `<button class="btn btn-sm ${i === currentSalesPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" data-page="${i}">${i}</button>`;
            }
            
            $('#employeePaginationNumbers').html(paginationHTML);
            
            // Add click event for pagination numbers
            $('#employeePaginationNumbers button').on('click', function() {
                currentSalesPage = parseInt($(this).data('page'));
                updateSalesDisplayedRows();
            });
        }
        
        // Function to update purchases pagination number buttons
        function updatePurchasesPaginationNumbers() {
            const totalRecords = $('#shippingHistoryTable tbody tr').length;
            const totalPages = Math.ceil(totalRecords / purchasesRecordsPerPage);
            
            let paginationHTML = '';
            
            // Determine range of page numbers to show
            let startPage = Math.max(1, currentPurchasesPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            // Ensure we always show 5 page numbers if possible
            if (endPage - startPage < 4 && totalPages > 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `<button class="btn btn-sm ${i === currentPurchasesPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" data-page="${i}">${i}</button>`;
            }
            
            $('#shippingPaginationNumbers').html(paginationHTML);
            
            // Add click event for pagination numbers
            $('#shippingPaginationNumbers button').on('click', function() {
                currentPurchasesPage = parseInt($(this).data('page'));
                updatePurchasesDisplayedRows();
            });
        }
        
        // Function to update waste pagination number buttons
        function updateWastePaginationNumbers() {
            const totalRecords = $('#withdrawalHistoryTable tbody tr').length;
            const totalPages = Math.ceil(totalRecords / wasteRecordsPerPage);
            
            let paginationHTML = '';
            
            // Determine range of page numbers to show
            let startPage = Math.max(1, currentWastePage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            // Ensure we always show 5 page numbers if possible
            if (endPage - startPage < 4 && totalPages > 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `<button class="btn btn-sm ${i === currentWastePage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" data-page="${i}">${i}</button>`;
            }
            
            $('#withdrawalPaginationNumbers').html(paginationHTML);
            
            // Add click event for pagination numbers
            $('#withdrawalPaginationNumbers button').on('click', function() {
                currentWastePage = parseInt($(this).data('page'));
                updateWasteDisplayedRows();
            });
        }

        // Table search functionality for sales
        $('#employeeTableSearch').on('input', function() {
            const searchText = $(this).val().toLowerCase();
            $('#employeeHistoryTable tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchText) > -1);
            });
            
            // Reset pagination after search
            currentSalesPage = 1;
            updateSalesDisplayedRows();
        });

        // Table search functionality for purchases
        $('#shippingTableSearch').on('input', function() {
            const searchText = $(this).val().toLowerCase();
            $('#shippingHistoryTable tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchText) > -1);
            });
            
            // Reset pagination after search
            currentPurchasesPage = 1;
            updatePurchasesDisplayedRows();
        });

        // Function to filter sales data
        function filterSalesData() {
            const filters = {
                start_date: $('#employeePaymentStartDate').val(),
                end_date: $('#employeePaymentEndDate').val(),
                customer_name: $('#employeePaymentName').val(),
                invoice_number: $('#invoiceNumber').val()
            };

            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    action: 'filter',
                    type: 'sales',
                    ...filters
                },
                success: function(response) {
                    if (response.success) {
                        updateSalesTable(response.data);
                    }
                }
            });
        }

        // Function to filter purchases data
        function filterPurchasesData() {
            const filters = {
                start_date: $('#shippingStartDate').val(),
                end_date: $('#shippingEndDate').val(),
                supplier_name: $('#shippingProvider').val(),
                invoice_number: $('#shippingInvoiceNumber').val()
            };

            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    action: 'filter',
                    type: 'purchases',
                    ...filters
                },
                success: function(response) {
                    if (response.success) {
                        updatePurchasesTable(response.data);
                    }
                }
            });
        }

        // Function to update sales table
        function updateSalesTable(data) {
            const tbody = $('#employeeHistoryTable tbody');
            tbody.empty();

            if (data.length === 0) {
                tbody.append('<tr><td colspan="13" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
                return;
            }

            data.forEach((sale, index) => {
                const row = `
                    <tr data-id="${sale.id}">
                        <td>${index + 1}</td>
                        <td>${sale.invoice_number}</td>
                        <td>${sale.customer_name || 'N/A'}</td>
                        <td>${new Date(sale.date).toLocaleDateString('en-US')}</td>
                        <td class="products-list-cell" data-products="${sale.products_list || ''}">
                            ${sale.products_list || ''}
                            <div class="products-popup"></div>
                        </td>
                        <td>${sale.subtotal.toLocaleString()} د.ع</td>
                        <td>${sale.shipping_cost.toLocaleString()} د.ع</td>
                        <td>${sale.other_costs.toLocaleString()} د.ع</td>
                        <td>${sale.discount.toLocaleString()} د.ع</td>
                        <td>${sale.total_amount.toLocaleString()} د.ع</td>
                        <td>
                            <span class="badge rounded-pill ${sale.payment_type === 'cash' ? 'bg-success' : (sale.payment_type === 'credit' ? 'bg-warning' : 'bg-info')}">
                                ${sale.payment_type === 'cash' ? 'نەقد' : (sale.payment_type === 'credit' ? 'قەرز' : 'چەک')}
                            </span>
                        </td>
                        <td>${sale.notes || ''}</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${sale.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${sale.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${sale.id}">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
            
            // Initialize product popups for new rows
            initializeProductListPopups();
            
            // Reset pagination to first page when loading new data
            currentSalesPage = 1;
            updateSalesDisplayedRows();
        }

        // Function to update purchases table
        function updatePurchasesTable(data) {
            const tbody = $('#shippingHistoryTable tbody');
            tbody.empty();

            if (data.length === 0) {
                tbody.append('<tr><td colspan="11" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
                return;
            }

            data.forEach((purchase, index) => {
                const row = `
                    <tr data-id="${purchase.id}">
                        <td>${index + 1}</td>
                        <td>${purchase.invoice_number}</td>
                        <td>${purchase.supplier_name || 'N/A'}</td>
                        <td>${new Date(purchase.date).toLocaleDateString('en-US')}</td>
                        <td class="products-list-cell" data-products="${purchase.products_list || ''}">
                            ${purchase.products_list || ''}
                            <div class="products-popup"></div>
                        </td>
                        <td>${purchase.subtotal.toLocaleString()} د.ع</td>
                        <td>${purchase.discount.toLocaleString()} د.ع</td>
                        <td>${purchase.total_amount.toLocaleString()} د.ع</td>
                        <td>
                            <span class="badge rounded-pill ${purchase.payment_type === 'cash' ? 'bg-success' : (purchase.payment_type === 'credit' ? 'bg-warning' : 'bg-info')}">
                                ${purchase.payment_type === 'cash' ? 'نەقد' : (purchase.payment_type === 'credit' ? 'قەرز' : 'چەک')}
                            </span>
                        </td>
                        <td>${purchase.notes || ''}</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${purchase.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${purchase.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${purchase.id}">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
            
            // Initialize product popups for new rows
            initializeProductListPopups();
            
            // Reset pagination to first page when loading new data
            currentPurchasesPage = 1;
            updatePurchasesDisplayedRows();
        }

        // Initialize date inputs with current month
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        $('#employeePaymentStartDate').val(firstDay.toISOString().split('T')[0]);
        $('#employeePaymentEndDate').val(today.toISOString().split('T')[0]);
        $('#shippingStartDate').val(firstDay.toISOString().split('T')[0]);
        $('#shippingEndDate').val(today.toISOString().split('T')[0]);

        // Load initial data
        filterSalesData();
        filterPurchasesData();

        // Initialize table pagination on page load
        updateSalesDisplayedRows();
        updatePurchasesDisplayedRows();
        updateWasteDisplayedRows();
    });

    // Handle edit button click for sales
    $(document).on('click', '#employeeHistoryTable .edit-btn', function() {
        const saleId = $(this).data('id');
        // Get sale data from the row
        const row = $(this).closest('tr');
        const saleData = {
            id: saleId,
            invoice_number: row.find('td:eq(1)').text(),
            customer_name: row.find('td:eq(2)').text(),
            date: row.find('td:eq(3)').text(),
            shipping_cost: parseFloat(row.find('td:eq(6)').text().replace(/[^0-9.-]+/g, '')),
            other_costs: parseFloat(row.find('td:eq(7)').text().replace(/[^0-9.-]+/g, '')),
            discount: parseFloat(row.find('td:eq(8)').text().replace(/[^0-9.-]+/g, '')),
            payment_type: row.find('td:eq(10) .badge').text().trim(),
            notes: row.find('td:eq(11)').text()
        };

        // Fill the form with sale data
        $('#editSaleId').val(saleData.id);
        $('#editSaleInvoiceNumber').val(saleData.invoice_number);
        
        // Set customer selection
        const customerSelect = $('#editSaleCustomer');
        customerSelect.find('option').each(function() {
            if ($(this).text().trim() === saleData.customer_name.trim()) {
                customerSelect.val($(this).val());
                return false;
            }
        });
        
        // Set payment type
        const paymentTypeMap = {
            'نەقد': 'cash',
            'قەرز': 'credit',
            'چەک': 'check'
        };
        const paymentTypeValue = paymentTypeMap[saleData.payment_type];
        if (paymentTypeValue) {
            $('#editSalePaymentType').val(paymentTypeValue);
        }
        
        $('#editSaleDate').val(new Date(saleData.date).toISOString().split('T')[0]);
        $('#editSaleShippingCost').val(saleData.shipping_cost);
        $('#editSaleOtherCosts').val(saleData.other_costs);
        $('#editSaleDiscount').val(saleData.discount);
        $('#editSaleNotes').val(saleData.notes);

        // Show the modal
        $('#editSaleModal').modal('show');
    });

    // Handle edit button click for purchases
    $(document).on('click', '#shippingHistoryTable .edit-btn', function() {
        const purchaseId = $(this).data('id');
        // Get purchase data from the row
        const row = $(this).closest('tr');
        const purchaseData = {
            id: purchaseId,
            invoice_number: row.find('td:eq(1)').text(),
            supplier_name: row.find('td:eq(2)').text(),
            date: row.find('td:eq(3)').text(),
            discount: parseFloat(row.find('td:eq(6)').text().replace(/[^0-9.-]+/g, '')),
            payment_type: row.find('td:eq(8) .badge').text().trim(),
            notes: row.find('td:eq(9)').text()
        };

        // Fill the form with purchase data
        $('#editPurchaseId').val(purchaseData.id);
        $('#editPurchaseInvoiceNumber').val(purchaseData.invoice_number);
        $('#editPurchaseSupplier').val(purchaseData.supplier_name);
        $('#editPurchaseDate').val(new Date(purchaseData.date).toISOString().split('T')[0]);
        $('#editPurchaseDiscount').val(purchaseData.discount);
        $('#editPurchasePaymentType').val(purchaseData.payment_type);
        $('#editPurchaseNotes').val(purchaseData.notes);

        // Show the modal
        $('#editPurchaseModal').modal('show');
    });

    // Handle save sale edit
    $('#saveSaleEdit').click(function() {
        const saleData = {
            id: $('#editSaleId').val(),
            invoice_number: $('#editSaleInvoiceNumber').val(),
            customer_id: $('#editSaleCustomer').val(),
            date: $('#editSaleDate').val(),
            shipping_cost: $('#editSaleShippingCost').val(),
            other_costs: $('#editSaleOtherCosts').val(),
            discount: $('#editSaleDiscount').val(),
            payment_type: $('#editSalePaymentType').val(),
            notes: $('#editSaleNotes').val()
        };

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'update_sale',
                ...saleData
            },
            success: function(response) {
                if (response.success) {
                    $('#editSaleModal').modal('hide');
                    // Refresh the table without using DataTable
                    filterSalesData();
                    Swal.fire({
                        title: 'سەرکەوتوو',
                        text: 'پسووڵە بە سەرکەوتوویی نوێکرایەوە',
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    });
                } else {
                    Swal.fire({
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            }
        });
    });

    // Handle save purchase edit
    $('#savePurchaseEdit').click(function() {
        const purchaseData = {
            id: $('#editPurchaseId').val(),
            invoice_number: $('#editPurchaseInvoiceNumber').val(),
            supplier_id: $('#editPurchaseSupplier').val(),
            date: $('#editPurchaseDate').val(),
            discount: $('#editPurchaseDiscount').val(),
            payment_type: $('#editPurchasePaymentType').val(),
            notes: $('#editPurchaseNotes').val()
        };

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'update_purchase',
                ...purchaseData
            },
            success: function(response) {
                if (response.success) {
                    $('#editPurchaseModal').modal('hide');
                    filterPurchasesData();
                    Swal.fire({
                        title: 'سەرکەوتوو',
                        text: 'پسووڵە بە سەرکەوتوویی نوێکرایەوە',
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    });
                } else {
                    Swal.fire({
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            }
        });
    });

       // Custom number formatting function for Iraqi Dinar (JavaScript version)
       function formatCurrency(number) {
        return number.toLocaleString('en-US') + ' د.ع';
    }

    // Function to load and filter sales data
    function filterSalesData() {
        $.ajax({
            url: '../../process/get_receipts.php',
            type: 'POST',
            data: {
                type: 'selling',
                startDate: $('#employeePaymentStartDate').val(),
                endDate: $('#employeePaymentEndDate').val(),
                customerName: $('#employeePaymentName').val(),
                invoiceNumber: $('#invoiceNumber').val()
            },
            success: function(response) {
                if (response.success) {
                    // Clear the table
                    $('#employeeHistoryTable tbody').empty();
                    
                    // Add new data
                    response.data.forEach(function(sale, index) {
                        let row = `
                            <tr data-id="${sale.id}">
                                <td>${index + 1}</td>
                                <td>${sale.title}</td>
                                <td>${sale.customer || 'N/A'}</td>
                                <td>${new Date(sale.date).toLocaleDateString('en-US')}</td>
                                <td class="products-list-cell" data-products="${sale.products || ''}">
                                    ${sale.products || 'N/A'}
                                    <div class="products-popup"></div>
                                </td>
                                <td>${formatCurrency(parseFloat(sale.total))}</td>
                                <td>
                                    <span class="badge rounded-pill ${sale.status === 'قەرز' ? 'bg-warning' : 'bg-success'}">
                                        ${sale.status}
                                    </span>
                                </td>
                                <td>${sale.notes || ''}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${sale.id}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${sale.id}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${sale.id}">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#employeeHistoryTable tbody').append(row);
                    });
                    
                    // Reinitialize any necessary event handlers
                    initializeProductListPopups();
                } else {
                    Swal.fire({
                        title: 'هەڵە',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە کاتی داواکردنی زانیارییەکان',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }

    // Initialize DataTable
    $(document).ready(function() {
        // Initial load of sales data
        filterSalesData();
        
        // Add event listeners for filter inputs
        $('.auto-filter').on('change', filterSalesData);
        
        // Reset filter button
        $('#employeePaymentResetFilter').on('click', function() {
            // Clear all filter inputs
            $('#employeePaymentStartDate').val('');
            $('#employeePaymentEndDate').val('');
            $('#employeePaymentName').val('');
            $('#invoiceNumber').val('');
            
            // Reload data
            filterSalesData();
        });
        
        // Initialize product list popups
        initializeProductListPopups();
    });

    // Initialize product list popups functionality
    function initializeProductListPopups() {
        $('.view-btn').off('click').on('click', function() {
            const row = $(this).closest('tr');
            const productsCell = row.find('.products-list-cell');
            const products = productsCell.data('products');
            
            // Format products list with HTML for better display
            const formattedProducts = products.split(',').map((product, index) => {
                return `
                    <div class="product-item" style="
                        padding: 10px;
                        margin: 5px 0;
                        background-color: #f8f9fa;
                        border-radius: 8px;
                        border: 1px solid #dee2e6;
                        text-align: right;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <span class="product-number" style="
                            background-color: #0d6efd;
                            color: white;
                            width: 24px;
                            height: 24px;
                            border-radius: 50%;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            margin-left: 10px;
                            font-size: 0.9em;
                        ">${index + 1}</span>
                        <span class="product-name" style="flex-grow: 1;">${product.trim()}</span>
                    </div>
                `;
            }).join('');

            const htmlContent = `
                <div style="
                    max-height: 400px;
                    overflow-y: auto;
                    padding: 10px;
                    direction: rtl;
                ">
                    <div style="
                        background-color: #e9ecef;
                        padding: 10px;
                        margin-bottom: 15px;
                        border-radius: 8px;
                        text-align: center;
                        font-weight: bold;
                    ">
                        کۆی کاڵاکان: ${products.split(',').length}
                    </div>
                    ${formattedProducts}
                </div>
            `;

            Swal.fire({
                title: 'کاڵاکانی پسووڵە',
                html: htmlContent,
                icon: 'info',
                confirmButtonText: 'باشە',
                width: '600px',
                customClass: {
                    container: 'swal-rtl',
                    title: 'swal-title-rtl',
                    htmlContainer: 'swal-html-rtl'
                }
            });
        });
    }

    // Error handling for AJAX requests
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        console.error('AJAX Error:', {
            status: jqXHR.status,
            statusText: jqXHR.statusText,
            responseText: jqXHR.responseText,
            error: error
        });
        
        // Show error in a more user-friendly way
        Swal.fire({
            title: 'هەڵەیەک ڕوویدا',
            html: `<div dir="rtl">
                <p>وردەکاری هەڵە:</p>
                <pre style="text-align: left; direction: ltr; background: #f8f9fa; padding: 10px; border-radius: 5px;">${jqXHR.responseText}</pre>
            </div>`,
            icon: 'error',
            confirmButtonText: 'باشە'
        });
    });

    // Catch general JavaScript errors
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        console.error('JavaScript Error:', {
            message: msg,
            url: url,
            lineNo: lineNo,
            columnNo: columnNo,
            error: error
        });
        
        // Show error in a more user-friendly way
        Swal.fire({
            title: 'هەڵەیەک ڕوویدا',
            html: `<div dir="rtl">
                <p>وردەکاری هەڵە:</p>
                <pre style="text-align: left; direction: ltr; background: #f8f9fa; padding: 10px; border-radius: 5px;">${msg}\nلە هێڵی: ${lineNo}</pre>
            </div>`,
            icon: 'error',
            confirmButtonText: 'باشە'
        });
        
        return false;
    };