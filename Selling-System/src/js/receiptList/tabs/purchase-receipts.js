// Purchase Receipts Management
$(document).ready(function() {
    // Helper function for number formatting
    function numberFormat(number) {
        return number.toLocaleString('en-US') + ' د.ع';
    }

    // Handle purchase receipt view
    $(document).on('click', '#shippingHistoryTable .view-btn', function() {
        const purchaseId = $(this).data('id');
        viewPurchaseDetails(purchaseId);
    });

    // Handle delete button
    $(document).on('click', '.delete-btn', function() {
        const receiptId = $(this).data('id');
        
        Swal.fire({
            title: 'دڵنیای؟',
            text: 'ئەم پسووڵەیە بە تەواوی دەسڕدرێتەوە',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بیسڕەوە',
            cancelButtonText: 'نەخێر، پەشیمان بوومەوە',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../api/receipts/delete_purchase.php',
                    method: 'POST',
                    data: { receipt_id: receiptId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو!',
                                text: 'پسووڵە بە سەرکەوتوویی سڕایەوە',
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا',
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });

    // Handle purchase receipt return
    $(document).on('click', '#shippingHistoryTable .return-btn', function() {
        const purchaseId = $(this).data('id');
        
        console.log("Return button clicked for purchase ID:", purchaseId);
        
        if (!purchaseId) {
            console.error("No purchase ID found on return button");
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'ناسنامەی کڕین نادروستە'
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

        // Fetch purchase items
        $.ajax({
            url: '../../api/receipts/get_purchase_items.php',
            type: 'POST',
            data: { purchase_id: purchaseId },
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
                    showReturnForm(purchaseId, 'purchase', response.items);
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
                
                let errorMessage = 'Unknown error occurred';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || error;
                } catch (e) {
                    errorMessage = error || 'Could not parse error response';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    html: `<div dir="ltr" style="text-align: left;">
                        <p>هەڵەیەک ڕوویدا لە کاتی پەیوەندی کردن بە سێرڤەر:</p>
                        <p><strong>Status:</strong> ${status}</p>
                        <p><strong>Error:</strong> ${errorMessage}</p>
                    </div>`
                });
            }
        });
    });

    // Handle date filter changes
    $('#shippingStartDate, #shippingEndDate, #shippingProvider, #shippingInvoiceNumber').on('change', function() {
        const startDate = $('#shippingStartDate').val();
        const endDate = $('#shippingEndDate').val();
        const provider = $('#shippingProvider').val();
        const invoiceNumber = $('#shippingInvoiceNumber').val();
        
        if (startDate && endDate) {
            $.ajax({
                url: '../../api/receipts/filter_purchases.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    provider: provider,
                    invoice_number: invoiceNumber
                },
                success: function(response) {
                    if (response.success) {
                        // Update table with new data
                        updatePurchasesTable(response.data);
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: response.message || 'هەڵەیەک ڕوویدا',
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                        icon: 'error'
                    });
                }
            });
        }
    });

    // Handle reset filter
    $('#shippingResetFilter').on('click', function() {
        $('#shippingStartDate').val('');
        $('#shippingEndDate').val('');
        $('#shippingProvider').val('');
        $('#shippingInvoiceNumber').val('');
        location.reload();
    });

    /**
     * Update the purchases table with filtered data
     * @param {Array} purchasesData - Array of purchases data
     */
    function updatePurchasesTable(purchasesData) {
        const tbody = $('#shippingHistoryTable tbody');
        tbody.empty();
        
        if (purchasesData.length === 0) {
            tbody.html('<tr class="no-records"><td colspan="15" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
            return;
        }
        
        purchasesData.forEach((purchase, index) => {
            const row = `
                <tr data-id="${purchase.id}">
                    <td>${index + 1}</td>
                    <td>${purchase.invoice_number || 'N/A'}</td>
                    <td>${purchase.supplier_name || 'N/A'}</td>
                    <td>${formatDate(purchase.date)}</td>
                    <td class="products-list-cell" data-products="${purchase.products_list || ''}">
                        ${purchase.products_list || ''}
                        <div class="products-popup"></div>
                    </td>
                    <td>${numberFormat(purchase.subtotal)}</td>
                    <td>${numberFormat(purchase.shipping_cost)}</td>
                    <td>${numberFormat(purchase.other_cost)}</td>
                    <td>${numberFormat(purchase.discount)}</td>
                    <td>${numberFormat(purchase.total_amount)}</td>
                    <td>${numberFormat(purchase.paid_amount)}</td>
                    <td>${numberFormat(purchase.remaining_amount)}</td>
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
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-circle return-btn" data-id="${purchase.id}">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${purchase.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
        
        // Initialize pagination
        initTablePagination(
            'shippingHistoryTable',
            'shippingRecordsPerPage',
            'shippingPrevPageBtn',
            'shippingNextPageBtn',
            'shippingPaginationNumbers',
            'shippingStartRecord',
            'shippingEndRecord',
            'shippingTotalRecords'
        );
    }

    // Initialize DataTable
    const purchaseTable = $('#shippingHistoryTable').DataTable({
        // ... your existing DataTable configuration
    });

    // Load initial data
    loadPurchasesData();
}); 