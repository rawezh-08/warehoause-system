/**
 * Sale receipts tab functionality
 */

// Initialize the module once DOM is fully loaded
$(document).ready(function() {
    // Global variables
    let currentSaleId = null;
    let salesTable = $('#employeeHistoryTable');
    
    // Initialize
    init();
    
    function init() {
        initializeEventHandlers();
        initializeFilterHandlers();
        loadSalesData();
    }
    
    // Initialize date pickers with current month
    initDateRangePicker('employeePaymentStartDate', 'employeePaymentEndDate');
    
    // Initialize product hover functionality
    initProductsListHover();
    
    // Load initial data
    loadSalesData();
    
    // Event handlers
    $('#employeePaymentFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadSalesData();
    });
    
    $('.auto-filter').on('change', function() {
        loadSalesData();
    });
    
    $('#employeePaymentResetFilter').on('click', function() {
        $('#employeePaymentFilterForm')[0].reset();
        initDateRangePicker('employeePaymentStartDate', 'employeePaymentEndDate');
        loadSalesData();
    });
    
    // Handle Edit button click
    $(document).on('click', '#employeeHistoryTable .edit-btn', function() {
        const saleId = $(this).data('id');
        editSale(saleId);
    });
    
    // Handle View button click
    $(document).on('click', '#employeeHistoryTable .view-btn', function() {
        const saleId = $(this).data('id');
        viewSale(saleId);
    });
    
    // Handle Print button click
    $(document).on('click', '#employeeHistoryTable .print-btn', function() {
        const saleId = $(this).data('id');
        printSaleReceipt(saleId);
    });
    
    // Handle Return button click
    $(document).on('click', '#employeeHistoryTable .return-btn', function() {
        const saleId = $(this).data('id');
        returnSaleItems(saleId);
    });
    
    // Handle Delete button click
    $(document).on('click', '#employeeHistoryTable .delete-btn', function() {
        const saleId = $(this).data('id');
        deleteSale(saleId);
    });
    
    // Handle save sale edit
    $('#saveSaleEdit').on('click', function() {
        saveSaleChanges();
    });
});

/**
 * Load sales data and display in the table
 * @param {Object} filters Optional filters for sales data
 */
function loadSalesData(filters = {}) {
    // Show loading
    showLoading('Loading sales data...');
    
    $.ajax({
        url: '../../api/receipts/filter_sales.php',
        type: 'POST',
        data: filters,
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                displaySalesData(response.data);
                showNotification('success', 'Sales data loaded successfully');
            } else {
                showNotification('error', response.message || 'Failed to load sales data');
                // Clear table if there's an error
                $('#sales-table tbody').html('<tr><td colspan="8" class="text-center">No data available</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showNotification('error', 'Network error occurred');
            console.error('AJAX error:', error);
            // Clear table if there's an error
            $('#sales-table tbody').html('<tr><td colspan="8" class="text-center">No data available</td></tr>');
        }
    });
}

/**
 * Display sales data in the table
 * @param {Array} sales Sales data to display
 */
function displaySalesData(sales) {
    const tbody = $('#sales-table tbody');
    tbody.empty();
    
    if (sales.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center">No sales found</td></tr>');
        return;
    }
    
    sales.forEach(function(sale) {
        const row = $('<tr>');
        
        // Format date
        const date = new Date(sale.sale_date);
        const formattedDate = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Build row content
        row.append(`<td>${sale.invoice_number}</td>`);
        row.append(`<td>${formattedDate}</td>`);
        row.append(`<td>${sale.customer_name || 'Walk-in Customer'}</td>`);
        row.append(`<td>${parseFloat(sale.total_amount).toFixed(2)}</td>`);
        row.append(`<td>${parseFloat(sale.paid_amount).toFixed(2)}</td>`);
        row.append(`<td>${parseFloat(sale.due_amount).toFixed(2)}</td>`);
        row.append(`<td>${sale.payment_status}</td>`);
        
        // Action buttons
        const actions = $('<td class="text-center">');
        actions.append(`<button class="btn btn-sm btn-info me-1 btn-view-sale" data-id="${sale.id}" title="View"><i class="fas fa-eye"></i></button>`);
        actions.append(`<button class="btn btn-sm btn-primary me-1 btn-edit-sale" data-id="${sale.id}" title="Edit"><i class="fas fa-edit"></i></button>`);
        actions.append(`<button class="btn btn-sm btn-success me-1 btn-print-sale" data-id="${sale.id}" title="Print"><i class="fas fa-print"></i></button>`);
        actions.append(`<button class="btn btn-sm btn-warning me-1 btn-return-sale" data-id="${sale.id}" title="Return"><i class="fas fa-undo"></i></button>`);
        actions.append(`<button class="btn btn-sm btn-danger btn-delete-sale" data-id="${sale.id}" title="Delete"><i class="fas fa-trash"></i></button>`);
        row.append(actions);
        
        tbody.append(row);
    });
}

/**
 * Edit a sale receipt
 * @param {number} saleId - ID of the sale to edit
 */
function editSale(saleId) {
    showLoading('جاری وەرگرتنی زانیاری...');
    
    $.ajax({
        url: '../../api/receipts/get_sale.php',
        type: 'POST',
        data: { id: saleId },
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                // Populate form with sale data
                $('#editSaleId').val(response.data.id);
                $('#editSaleInvoiceNumber').val(response.data.invoice_number);
                $('#editSaleCustomer').val(response.data.customer_id);
                $('#editSaleDate').val(formatDateForInput(response.data.date));
                $('#editSaleShippingCost').val(response.data.shipping_cost);
                $('#editSaleOtherCosts').val(response.data.other_costs);
                $('#editSaleDiscount').val(response.data.discount);
                $('#editSalePaymentType').val(response.data.payment_type);
                $('#editSaleNotes').val(response.data.notes);
                
                // Show modal
                $('#editSaleModal').modal('show');
            } else {
                showError(response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری');
            }
        },
        error: function() {
            hideLoading();
            showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر');
        }
    });
}

/**
 * Save changes to edited sale
 */
function saveSaleChanges() {
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
    
    showLoading('جاری پاشەکەوتکردنی گۆڕانکارییەکان...');
    
    $.ajax({
        url: '../../api/receipts/update_sale.php',
        type: 'POST',
        data: saleData,
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                // Close modal
                $('#editSaleModal').modal('hide');
                
                // Show success message
                showSuccess('گۆڕانکارییەکان بە سەرکەوتوویی پاشەکەوت کران');
                
                // Reload data
                loadSalesData();
            } else {
                showError(response.message || 'هەڵەیەک ڕوویدا لە پاشەکەوتکردنی گۆڕانکارییەکان');
            }
        },
        error: function() {
            hideLoading();
            showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر');
        }
    });
}

/**
 * View sale receipt details
 * @param {number} saleId - ID of the sale to view
 */
function viewSale(saleId) {
    showLoading('جاری وەرگرتنی زانیاری...');
    
    $.ajax({
        url: '../../api/receipts/get_sale.php',
        type: 'POST',
        data: { id: saleId },
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                showSaleDetails(response.data);
            } else {
                showError(response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری');
            }
        },
        error: function() {
            hideLoading();
            showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر');
        }
    });
}

/**
 * Show sale details in a modal
 * @param {Object} data - Sale data object
 */
function showSaleDetails(data) {
    let itemsHtml = '';
    let totalQuantity = 0;
    let subtotal = 0;
    
    data.items.forEach((item, index) => {
        totalQuantity += parseInt(item.quantity);
        const itemTotal = item.quantity * item.price;
        subtotal += itemTotal;
        
        itemsHtml += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.product_name}</td>
                <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                <td>${formatCurrency(item.price)}</td>
                <td>${item.quantity}</td>
                <td>${formatCurrency(itemTotal)}</td>
            </tr>
        `;
    });
    
    const totalAmount = subtotal + parseFloat(data.shipping_cost) + parseFloat(data.other_costs) - parseFloat(data.discount);
    
    const detailsHtml = `
        <div class="receipt-details">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>کڕیار:</strong> ${data.customer_name || 'N/A'}</p>
                    <p><strong>بەروار:</strong> ${formatDate(data.date)}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>ژمارەی پسووڵە:</strong> ${data.invoice_number}</p>
                    <p><strong>جۆری پارەدان:</strong> ${data.payment_type === 'cash' ? 'نەقد' : (data.payment_type === 'credit' ? 'قەرز' : 'چەک')}</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>کاڵا</th>
                            <th>یەکە</th>
                            <th>نرخی یەکە</th>
                            <th>بڕ</th>
                            <th>کۆی گشتی</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">کۆی گشتی:</th>
                            <th>${totalQuantity}</th>
                            <th>${formatCurrency(subtotal)}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <p><strong>تێبینی:</strong> ${data.notes || 'هیچ تێبینییەک نیە'}</p>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td>کۆی نرخی کاڵاکان:</td>
                            <td>${formatCurrency(subtotal)}</td>
                        </tr>
                        <tr>
                            <td>کرێی گواستنەوە:</td>
                            <td>${formatCurrency(data.shipping_cost)}</td>
                        </tr>
                        <tr>
                            <td>خەرجی تر:</td>
                            <td>${formatCurrency(data.other_costs)}</td>
                        </tr>
                        <tr>
                            <td>داشکاندن:</td>
                            <td>${formatCurrency(data.discount)}</td>
                        </tr>
                        <tr class="table-primary">
                            <th>کۆی گشتی:</th>
                            <th>${formatCurrency(totalAmount)}</th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    Swal.fire({
        title: `پسووڵەی ژمارە: ${data.invoice_number}`,
        html: detailsHtml,
        width: 800,
        showCloseButton: true,
        customClass: {
            container: 'swal-rtl',
            popup: 'swal-wide'
        }
    });
}

/**
 * Print sale receipt
 * @param {number} saleId - ID of the sale to print
 */
function printSaleReceipt(saleId) {
    // Open print receipt page in new window
    window.open(`../../views/receipt/print_receipt.php?sale_id=${saleId}`, '_blank');
}

/**
 * Show return items form for a sale
 * @param {number} saleId - ID of the sale for item return
 */
function returnSaleItems(saleId) {
    showLoading('جاری وەرگرتنی زانیاری...');
    
    $.ajax({
        url: '../../api/receipts/get_sale_items.php',
        type: 'POST',
        data: { sale_id: saleId },
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                showReturnItemsModal(saleId, response.data);
            } else {
                showError(response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری');
            }
        },
        error: function() {
            hideLoading();
            showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر');
        }
    });
}

/**
 * Show modal for returning items
 * @param {number} saleId - ID of the sale
 * @param {Array} items - Sale items data
 */
function showReturnItemsModal(saleId, items) {
    $('#returnReceiptId').val(saleId);
    $('#returnReceiptType').val('sale');
    
    const itemsList = $('#returnItemsList');
    itemsList.empty();
    
    items.forEach(item => {
        const remainingQty = item.quantity - (item.returned_quantity || 0);
        
        if (remainingQty > 0) {
            itemsList.append(`
                <tr data-item-id="${item.id}">
                    <td>${item.product_name}</td>
                    <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                    <td>${item.quantity}</td>
                    <td>${item.returned_quantity || 0}</td>
                    <td>
                        <input type="number" class="form-control return-qty" 
                            min="0" max="${remainingQty}" value="0" 
                            data-item-id="${item.id}" data-max="${remainingQty}">
                    </td>
                </tr>
            `);
        }
    });
    
    if (itemsList.children().length === 0) {
        itemsList.append('<tr><td colspan="5" class="text-center">هەموو کاڵاکان گەڕێنراونەتەوە</td></tr>');
        $('#saveReturn').prop('disabled', true);
    } else {
        $('#saveReturn').prop('disabled', false);
    }
    
    $('#returnModal').modal('show');
}

/**
 * Delete a sale receipt
 * @param {number} saleId - ID of the sale to delete
 */
function deleteSale(saleId) {
    confirmAction(
        'دڵنیایت؟',
        'ئایا دڵنیایت کە دەتەوێت ئەم پسووڵەیە بسڕیتەوە؟ ئەم کردارە ناتوانرێت پاشگەز بکرێتەوە.',
        'بەڵێ، بیسڕەوە',
        'نەخێر، پاشگەز بوومەوە',
        function() {
            showLoading('جاری سڕینەوەی پسووڵە...');
            
            $.ajax({
                url: '../../api/receipts/delete_sale.php',
                type: 'POST',
                data: { id: saleId },
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        showSuccess('پسووڵەکە بە سەرکەوتوویی سڕایەوە');
                        loadSalesData();
                    } else {
                        showError(response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی پسووڵە');
                    }
                },
                error: function() {
                    hideLoading();
                    showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر');
                }
            });
        }
    );
}

/**
 * Initialize event handlers for all buttons
 */
function initializeEventHandlers() {
    // Action buttons
    $(document).on('click', '.btn-view-sale', function() {
        const saleId = $(this).data('id');
        viewSale(saleId);
    });
    
    $(document).on('click', '.btn-edit-sale', function() {
        const saleId = $(this).data('id');
        editSale(saleId);
    });
    
    $(document).on('click', '.btn-print-sale', function() {
        const saleId = $(this).data('id');
        printSaleReceipt(saleId);
    });
    
    $(document).on('click', '.btn-return-sale', function() {
        const saleId = $(this).data('id');
        returnSaleItems(saleId);
    });
    
    $(document).on('click', '.btn-delete-sale', function() {
        const saleId = $(this).data('id');
        deleteSale(saleId);
    });
    
    // Save edited sale
    $('#save-edit-sale-btn').on('click', function() {
        saveSaleChanges();
    });
    
    // Close modals
    $('.btn-close-modal').on('click', function() {
        $(this).closest('.modal').modal('hide');
    });
}

/**
 * Initialize event handlers for the filter form
 */
function initializeFilterHandlers() {
    // Filter button click handler
    $('#filter-sales-btn').on('click', function() {
        const filters = {
            invoice_number: $('#filter-invoice').val(),
            customer_name: $('#filter-customer').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            payment_status: $('#filter-payment-status').val()
        };
        
        // Remove empty filters
        Object.keys(filters).forEach(key => {
            if (!filters[key]) delete filters[key];
        });
        
        loadSalesData(filters);
    });
    
    // Reset filter button click handler
    $('#reset-filter-btn').on('click', function() {
        // Clear all filter inputs
        $('#filter-invoice').val('');
        $('#filter-customer').val('');
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        $('#filter-payment-status').val('');
        
        // Reload with no filters
        loadSalesData();
    });
    
    // Enter key on filter inputs
    $('.filter-input').on('keyup', function(e) {
        if (e.key === 'Enter') {
            $('#filter-sales-btn').click();
        }
    });
}

/**
 * Show loading message
 * @param {string} message Loading message to display
 */
function showLoading(message = 'Loading...') {
    $('#loading-overlay').removeClass('d-none');
    $('#loading-message').text(message);
}

/**
 * Hide loading message
 */
function hideLoading() {
    $('#loading-overlay').addClass('d-none');
}

/**
 * Show notification
 * @param {string} type Notification type (success, error, warning, info)
 * @param {string} message Message to display
 */
function showNotification(type, message) {
    const toast = $(`
        <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `);
    
    $('.toast-container').append(toast);
    const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
    bsToast.show();
    
    // Remove toast from DOM after it's hidden
    toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
} 