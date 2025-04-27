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
    
    // Handle Print button click
    $(document).on('click', '#employeeHistoryTable .print-btn', function() {
        const saleId = $(this).data('id');
        printSaleReceipt(saleId);
    });
    
    // Handle date filter changes
    $('#employeePaymentStartDate, #employeePaymentEndDate, #employeePaymentName').on('change', function() {
        loadSalesData();
    });
    
    // Handle sale receipt return
    $(document).on('click', '#employeeHistoryTable .return-btn', function() {
        const saleId = $(this).data('id');
        
        console.log("Return button clicked for sale ID:", saleId);
        
        if (!saleId) {
            console.error("No sale ID found on return button");
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'ناسنامەی فرۆشتن نادروستە'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch sale items
        $.ajax({
            url: '../../api/receipts/get_sale_items.php',
            type: 'POST',
            data: { sale_id: saleId },
            dataType: 'json',
            success: function(response) {
                console.log("API Response:", response);
                
                Swal.close();
                
                if (response.status === 'success' && response.items) {
                    console.log("Items found:", response.items);
                    console.log("Items length:", response.items.length);
                    
                    // Show detailed info for each item
                    if (response.items && response.items.length > 0) {
                        response.items.forEach((item, index) => {
                            console.log(`Item ${index + 1}:`, item);
                            console.log(`  - product_id: ${item.product_id}`);
                            console.log(`  - product_name: ${item.product_name}`);
                            console.log(`  - quantity: ${item.quantity}`);
                            console.log(`  - returned_quantity: ${item.returned_quantity}`);
                            console.log(`  - remaining: ${item.quantity - (parseFloat(item.returned_quantity) || 0)}`);
                        });
                    }
                    
                    // Show return form
                    showReturnForm(saleId, 'sale', response.items);
                } else {
                    console.error('API Response Error:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        html: `<div dir="ltr" style="text-align: left;">
                            <p>هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان:</p>
                            <pre style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow: auto;">${response.message || 'No error message'}</pre>
                        </div>`
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", {xhr, status, error});
                Swal.close();
                
                let errorMessage = '';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    } else if (response.debug_info && response.debug_info.error_message) {
                        errorMessage = response.debug_info.error_message;
                    } else {
                        errorMessage = error || 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە';
                    }
                    
                    // Add debug info if available
                    if (response.debug_info) {
                        console.log('Debug Info:', response.debug_info);
                        errorMessage += '\n\nزانیاری زیاتر:';
                        if (response.debug_info.error_file) {
                            errorMessage += `\nفایل: ${response.debug_info.error_file}`;
                        }
                        if (response.debug_info.error_line) {
                            errorMessage += `\nهێڵ: ${response.debug_info.error_line}`;
                        }
                    }
                } catch (e) {
                    console.error('Error parsing server response:', e);
                    errorMessage = xhr.responseText || error || 'هەڵەیەکی نەناسراو ڕوویدا';
                }
                
                Swal.fire({
                    title: 'هەڵە!',
                    text: errorMessage,
                    icon: 'error',
                    customClass: {
                        popup: 'swal-rtl'
                    }
                });
            }
        });
    });
    
    // Handle save sale edit
    $('#saveSaleEdit').on('click', function() {
        saveSaleChanges();
    });
});

/**
 * Load sales data and display in the table
 * @param {Object} customFilters Optional custom filters for sales data
 */
function loadSalesData(customFilters = {}) {
    // Show loading indicator
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Collect filter values from form fields
    const startDate = $('#employeePaymentStartDate').val();
    const endDate = $('#employeePaymentEndDate').val();
    const customerName = $('#employeePaymentName').val();
    const searchTerm = $('#employeeTableSearch').val();
    
    // Combine form filters with any custom filters
    const filters = {
        ...customFilters,
        start_date: startDate,
        end_date: endDate,
        customer_name: customerName,
        search: searchTerm
    };
    
    // Remove empty filters
    Object.keys(filters).forEach(key => {
        if (!filters[key]) {
            delete filters[key];
        }
    });
    
    // Make AJAX request
    $.ajax({
        url: '../../api/receipts/filter_sales.php',
        type: 'POST',
        data: filters,
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success || response.status === 'success') {
                // Get actual data array
                const salesData = response.data || response.sales || [];
                
                // Update table with data
                updateSalesTable(salesData);
            } else {
                console.error('API Error:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر'
            });
        }
    });
}

/**
 * Update the sales table with filtered data
 * @param {Array} salesData - Array of sales data
 */
function updateSalesTable(salesData) {
    const tbody = $('#employeeHistoryTable tbody');
    tbody.empty();
    
    if (salesData.length === 0) {
        tbody.html('<tr class="no-records"><td colspan="13" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
        return;
    }
    
    salesData.forEach((sale, index) => {
        const row = `
            <tr data-id="${sale.id}">
                <td>${index + 1}</td>
                <td>${sale.invoice_number || 'N/A'}</td>
                <td>${sale.customer_name || 'N/A'}</td>
                <td>${formatDate(sale.date)}</td>
                <td class="products-list-cell" data-products="${sale.products_list || ''}">
                    ${sale.products_list || ''}
                    <div class="products-popup"></div>
                </td>
                <td>${numberFormat(sale.subtotal)}</td>
                <td>${numberFormat(sale.shipping_cost)}</td>
                <td>${numberFormat(sale.other_costs)}</td>
                <td>${numberFormat(sale.discount)}</td>
                <td>${numberFormat(sale.total_amount)}</td>
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
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" title="پرینت" data-id="${sale.id}">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Initialize pagination
    initTablePagination(
        'employeeHistoryTable',
        'employeeRecordsPerPage',
        'employeePrevPageBtn',
        'employeeNextPageBtn',
        'employeePaginationNumbers',
        'employeeStartRecord',
        'employeeEndRecord',
        'employeeTotalRecords'
    );
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).replace(/(\d+)\/(\d+)\/(\d+)/, '$3/$1/$2'); // Convert MM/DD/YYYY to YYYY/MM/DD
}

// Helper function to format numbers
function numberFormat(number) {
    return Number(number).toLocaleString() + ' د.ع';
}

// Update pagination information
function updatePaginationInfo(totalRecords) {
    $('#employeeTotalRecords').text(totalRecords);
    $('#employeeEndRecord').text(Math.min(totalRecords, 10));
}

// Add search functionality
$(document).ready(function() {
    // Table search functionality
    $('#employeeTableSearch').on('keyup', function() {
        loadSalesData();
    });
});

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
    window.open(`../../Views/receipt/print_receipt.php?sale_id=${saleId}`, '_blank');
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

/**
 * Initialize event handlers for all buttons
 */
function initializeEventHandlers() {
    // Action buttons
    $(document).on('click', '.btn-edit-sale', function() {
        const saleId = $(this).data('id');
        editSale(saleId);
    });
    
    $(document).on('click', '.btn-print-sale', function() {
        const saleId = $(this).data('id');
        printSaleReceipt(saleId);
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