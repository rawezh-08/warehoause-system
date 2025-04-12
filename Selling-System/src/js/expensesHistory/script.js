// Wait for the DOM to be ready
$(document).ready(function() {
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
    
    // Apply filter for sales data (previously employeePaymentFilter)
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
    
    // Update sales table (previously updateEmployeePaymentsTable)
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
    
    // Apply filter for purchases data (previously withdrawalFilter)
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