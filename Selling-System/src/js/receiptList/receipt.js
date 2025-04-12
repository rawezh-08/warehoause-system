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
        url: 'process/get_receipts.php',
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
        url: 'process/get_receipts.php',
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
        url: 'process/get_receipts.php',
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
        url: 'process/get_receipt_details.php',
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
        url: 'process/delete_receipt.php',
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
    window.open(`process/print_receipt.php?id=${receiptId}&type=${receiptType}`, '_blank');
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