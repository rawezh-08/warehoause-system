// Purchase Receipts Management
$(document).ready(function() {
    // Helper function for number formatting
    function numberFormat(number) {
        return number.toLocaleString('en-US') + ' د.ع';
    }

    // Handle purchase receipt deletion
    $(document).on('click', '.delete-btn', function() {
        const receiptId = $(this).data('id');
        if (!receiptId) return; // Skip if no ID (empty row)
        
        Swal.fire({
            title: 'دڵنیای لە سڕینەوەی پسووڵە؟',
            text: 'پاش سڕینەوە ناتوانیت گەڕایەوە',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'نەخێر، هەڵوەشانەوە'
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

    // Handle purchase receipt view
    $(document).on('click', '.view-btn', function() {
        const receiptId = $(this).data('id');
        if (!receiptId) return; // Skip if no ID (empty row)
        window.location.href = `../../views/admin/view_purchase.php?id=${receiptId}`;
    });

    // Handle purchase receipt edit
    $(document).on('click', '.edit-btn', function() {
        const receiptId = $(this).data('id');
        if (!receiptId) return; // Skip if no ID (empty row)
        window.location.href = `../../views/admin/edit_purchase.php?id=${receiptId}`;
    });

    // Handle purchase receipt return
    $(document).on('click', '.return-btn', function() {
        const receiptId = $(this).data('id');
        if (!receiptId) return; // Skip if no ID (empty row)
        window.location.href = `../../views/admin/return_purchase.php?id=${receiptId}`;
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
                        updatePurchaseTable(response.data);
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

    // Function to update purchase table with new data
    function updatePurchaseTable(data) {
        const tbody = $('#shippingHistoryTable tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`<tr><td colspan="15" class="text-center">هیچ داتایەک نەدۆزرایەوە</td></tr>`);
            return;
        }

        data.forEach((purchase, index) => {
            const row = `<tr>
                <td>${index + 1}</td>
                <td>${purchase.invoice_number}</td>
                <td>${purchase.supplier_name || 'N/A'}</td>
                <td>${purchase.date}</td>
                <td class="products-list-cell" data-products="${purchase.products_list}">
                    ${purchase.products_list}
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
                    <span class="badge rounded-pill ${purchase.payment_type == 'cash' ? 'bg-success' : (purchase.payment_type == 'credit' ? 'bg-warning' : 'bg-info')}">
                        ${purchase.payment_type == 'cash' ? 'نەقد' : (purchase.payment_type == 'credit' ? 'قەرز' : 'چەک')}
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
            </tr>`;
            tbody.append(row);
        });

        // Reinitialize product hover functionality
        initProductsListHover();
    }
}); 