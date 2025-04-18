$(document).ready(function() {
    // Function to load selling receipts
    function loadSellingReceipts() {
        // Use the same filter function with default values
        const startDate = $('#employeePaymentStartDate').val();
        const endDate = $('#employeePaymentEndDate').val();
        const customerName = $('#employeePaymentName').val();
        const invoiceNumber = $('#invoiceNumber').val();
        
        filterSalesData();
    }

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
    $('.refresh-btn').on('click', function() {
        const activeTab = $('.nav-tabs .active').attr('id');
        if (activeTab === 'employee-payment-tab') {
            filterSalesData();
        } else if (activeTab === 'shipping-tab') {
            filterPurchasesData();
        } else if (activeTab === 'withdrawal-tab') {
            filterWasteData();
        } else if (activeTab === 'draft-tab') {
            filterDrafts();
        }
    });

    // Filter form submissions
    $('#employeePaymentFilterForm').on('submit', function(e) {
        e.preventDefault();
        filterSalesData();
    });

    $('#shippingFilterForm').on('submit', function(e) {
        e.preventDefault();
        filterPurchasesData();
    });

    $('#withdrawalFilterForm').on('submit', function(e) {
        e.preventDefault();
        filterWasteData();
    });

    // Reset filter buttons
    $('#employeePaymentResetFilter').on('click', function() {
        $('#employeePaymentFilterForm')[0].reset();
        filterSalesData();
    });

    $('#shippingResetFilter').on('click', function() {
        $('#shippingFilterForm')[0].reset();
        filterPurchasesData();
    });

    $('#withdrawalResetFilter').on('click', function() {
        $('#withdrawalFilterForm')[0].reset();
        filterWasteData();
    });

    // Initialize date inputs with current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
        $('#employeePaymentStartDate').val(formatDate(firstDay));
        $('#employeePaymentEndDate').val(formatDate(today));
        $('#shippingStartDate').val(formatDate(firstDay));
        $('#shippingEndDate').val(formatDate(today));
        $('#withdrawalStartDate').val(formatDate(firstDay));
        $('#withdrawalEndDate').val(formatDate(today));
    
    // Helper function to format date
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }
    
    // Function to filter sales data
    function filterSalesData() {
        const startDate = $('#employeePaymentStartDate').val();
        const endDate = $('#employeePaymentEndDate').val();
        const customerName = $('#employeePaymentName').val();
        const invoiceNumber = $('#invoiceNumber').val();
        
        $.ajax({
            url: '../../api/filter_receipts.php',
            type: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate,
                customer_name: customerName,
                invoice_number: invoiceNumber,
                type: 'selling'
            },
            success: function(response) {
                if (response.success) {
                    updateSalesTable(response.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
                });
            }
        });
    }
    
    // Function to filter purchases data
    function filterPurchasesData() {
        const startDate = $('#shippingStartDate').val();
        const endDate = $('#shippingEndDate').val();
        const supplierName = $('#shippingProvider').val();
        const invoiceNumber = $('#shippingInvoiceNumber').val();
        
        $.ajax({
            url: '../../api/filter_receipts.php',
            type: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate,
                supplier_name: supplierName,
                invoice_number: invoiceNumber,
                type: 'buying'
            },
            success: function(response) {
                if (response.success) {
                    updatePurchasesTable(response.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                    title: 'هەڵە!',
                    text: response.message
                });
            }
        },
            error: function() {
            Swal.fire({
                icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
            });
        }
    });
}

    // Function to filter waste data
    function filterWasteData() {
        const startDate = $('#withdrawalStartDate').val();
        const endDate = $('#withdrawalEndDate').val();
        const name = $('#withdrawalName').val();

    $.ajax({
            url: '../../api/filter_receipts.php',
        type: 'POST',
        data: { 
                start_date: startDate,
                end_date: endDate,
                name: name,
                type: 'wasting'
            },
        success: function(response) {
            if (response.success) {
                    updateWasteTable(response.data);
            } else {
                Swal.fire({
                    icon: 'error',
                        title: 'هەڵە!',
                    text: response.message
                });
            }
        },
            error: function() {
            Swal.fire({
                icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
                });
                }
            });
        }

        // Function to update sales table
        function updateSalesTable(data) {
            const tbody = $('#employeeHistoryTable tbody');
            tbody.empty();

        if (data.length > 0) {
            data.forEach((sale, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${sale.invoice_number}</td>
                        <td>${sale.customer_name}</td>
                        <td>${sale.date}</td>
                        <td class="products-list-cell" data-products="${sale.products_list}">
                            ${sale.products_list}
                            <div class="products-popup"></div>
                        </td>
                        <td>${sale.subtotal}</td>
                        <td>${sale.shipping_cost}</td>
                        <td>${sale.other_costs}</td>
                        <td>${sale.discount}</td>
                        <td>${sale.total_amount}</td>
                        <td>
                            <span class="badge rounded-pill ${sale.payment_type === 'cash' ? 'bg-success' : 'bg-warning'}">
                                ${sale.payment_type === 'cash' ? 'نەقد' : 'قەرز'}
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
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-circle return-btn" data-id="${sale.id}" data-receipt-type="selling">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${sale.id}">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${sale.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        } else {
            tbody.html('<tr><td colspan="13" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
        }
        }

        // Function to update purchases table
        function updatePurchasesTable(data) {
            const tbody = $('#shippingHistoryTable tbody');
            tbody.empty();

        if (data.length > 0) {
            data.forEach((purchase, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${purchase.invoice_number}</td>
                        <td>${purchase.supplier_name}</td>
                        <td>${purchase.date}</td>
                        <td>${purchase.payment_type === 'cash' ? 'نەقد' : 'قەرز'}</td>
                        <td>${purchase.total_amount} دینار</td>
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
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${purchase.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
                    });
                } else {
            tbody.html('<tr><td colspan="7" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
        }
    }

    // Function to update waste table
    function updateWasteTable(data) {
        const tbody = $('#withdrawalHistoryTable tbody');
        tbody.empty();

        if (data.length > 0) {
            data.forEach((waste, index) => {
                const row = `
                    <tr>
                                <td>${index + 1}</td>
                        <td>${waste.name}</td>
                        <td>${waste.date}</td>
                        <td>${waste.amount} دینار</td>
                        <td>${waste.type}</td>
                        <td>${waste.notes || ''}</td>
                                <td>
                                    <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${waste.id}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${waste.id}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${waste.id}">
                                            <i class="fas fa-print"></i>
                                        </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${waste.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                tbody.append(row);
                    });
                } else {
            tbody.html('<tr><td colspan="7" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
        }
    }

    // Handle view button clicks
    $(document).on('click', '.view-btn', function() {
        const id = $(this).data('id');
        const type = $(this).closest('.tab-pane').attr('id');
        viewReceipt(id, type);
    });

    // Handle edit button clicks
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const type = $(this).closest('.tab-pane').attr('id');
        window.location.href = `editReceipt.php?id=${id}&type=${type}`;
    });

    // Handle print button clicks
    $(document).on('click', '.print-btn', function() {
        const id = $(this).data('id');
        const type = $(this).closest('.tab-pane').attr('id');
        window.open(`print_receipt.php?id=${id}&type=${type}`, '_blank');
    });

    // Add return functionality
    $(document).on('click', '.return-btn', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('receipt-type');
        showReturnModal(receiptId, receiptType);
    });

    // Function to show return modal
    function showReturnModal(receiptId, receiptType) {
        // First get receipt details
        $.ajax({
            url: '../../api/get_receipt_details.php',
            type: 'POST',
            data: { 
                id: receiptId, 
                type: receiptType 
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let itemsHtml = '';
                    
                    data.items.forEach((item, index) => {
                        const maxReturn = item.quantity - (item.returned_quantity || 0);
                        if (maxReturn > 0) {
                            itemsHtml += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td style="min-width: 200px; word-break: break-word;">
                                        ${item.product_name}
                                    </td>
                                    <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                                    <td style="min-width: 120px;">${item.unit_price} د.ع</td>
                                    <td>${item.quantity}</td>
                                    <td>${item.returned_quantity || 0}</td>
                                    <td style="min-width: 150px;">
                                        <input type="number" class="form-control return-quantity" 
                                               min="0" max="${maxReturn}" value="0"
                                               data-product-id="${item.product_id}"
                                               data-unit-price="${item.unit_price}"
                                               data-unit-type="${item.unit_type}">
                                    </td>
                                </tr>
                            `;
                        }
                    });

                    if (!itemsHtml) {
                        Swal.fire({
                            icon: 'info',
                            title: 'ئاگاداری',
                            text: 'هیچ کاڵایەک نەماوە بۆ گەڕاندنەوە لەم پسووڵەیە'
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'گەڕاندنەوەی کاڵا',
                        html: `
                            <form id="returnForm" class="mx-3">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-hover">
                                        <thead class="sticky-top bg-light">
                                            <tr>
                                                <th>#</th>
                                                <th>کاڵا</th>
                                                <th>یەکە</th>
                                                <th>نرخی یەکە</th>
                                                <th>بڕی کڕدراو</th>
                                                <th>بڕی گەڕێندراوە</th>
                                                <th>بڕی گەڕاندنەوە</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${itemsHtml}
                                        </tbody>
                                    </table>
                                </div>
                                <div class="form-group mt-4">
                                    <label for="returnReason" class="form-label fw-bold">هۆکاری گەڕاندنەوە</label>
                                    <select class="form-select" id="returnReason" required>
                                        <option value="">هۆکار هەڵبژێرە</option>
                                        <option value="damaged">کاڵای خراپ</option>
                                        <option value="wrong_product">کاڵای هەڵە</option>
                                        <option value="customer_request">داواکاری کڕیار</option>
                                        <option value="other">هۆکاری تر</option>
                                    </select>
                                </div>
                                <div class="form-group mt-3">
                                    <label for="returnNotes" class="form-label fw-bold">تێبینی</label>
                                    <textarea class="form-control" id="returnNotes" rows="3"></textarea>
                                </div>
                            </form>
                        `,
                        width: '900px',
                        showCancelButton: true,
                        confirmButtonText: 'گەڕاندنەوە',
                        cancelButtonText: 'داخستن',
                        showLoaderOnConfirm: true,
                        customClass: {
                            container: 'swal-rtl',
                            popup: 'swal-wide',
                            content: 'swal-content-large'
                        },
                        preConfirm: () => {
                            const returnItems = [];
                            $('.return-quantity').each(function() {
                                const quantity = parseInt($(this).val());
                                if (quantity > 0) {
                                    returnItems.push({
                                        product_id: $(this).data('product-id'),
                                        quantity: quantity,
                                        unit_price: $(this).data('unit-price'),
                                        unit_type: $(this).data('unit-type')
                                    });
                                }
                            });

                            const reason = $('#returnReason').val();
                            if (!reason) {
                                Swal.showValidationMessage('تکایە هۆکاری گەڕاندنەوە دیاری بکە');
                                return false;
                            }

                            if (returnItems.length === 0) {
                                Swal.showValidationMessage('تکایە بڕی گەڕاندنەوە دیاری بکە');
                                return false;
                            }

                            // Return the data as FormData
                            const formData = new FormData();
                            formData.append('receipt_id', receiptId);
                            formData.append('receipt_type', receiptType);
                            formData.append('reason', reason);
                            formData.append('notes', $('#returnNotes').val());
                            formData.append('items', JSON.stringify(returnItems));

                            return formData;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Submit return request
                            $.ajax({
                                url: '../../api/process_return.php',
                                type: 'POST',
                                data: result.value,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    console.log('Return Response:', response);
                                    if (response.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'سەرکەوتوو بوو!',
                                            text: 'گەڕاندنەوەی کاڵاکان بە سەرکەوتوویی تۆمار کرا'
                                        }).then(() => {
                                            // Refresh the table
                                            if (receiptType === 'selling') {
                                                filterSalesData();
                                            } else {
                                                filterPurchasesData();
                                            }
                                        });
                                    } else {
                                        console.error('Return Error:', response);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'هەڵە!',
                                            text: response.message || 'هەڵەیەک ڕوویدا لە پرۆسەی گەڕاندنەوە'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Return Error:', {
                                        xhr: xhr,
                                        status: status,
                                        error: error,
                                        response: xhr.responseText
                                    });
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە!',
                                        text: 'هەڵەیەک ڕوویدا لە پرۆسەی گەڕاندنەوە: ' + (xhr.responseJSON?.message || error)
                                    });
                                }
                            });
                        }
                    });

                    // Add custom styles
                    $('<style>')
                        .text(`
                            .swal-wide {
                                min-width: 900px !important;
                            }
                            .swal-content-large {
                                max-height: 600px;
                                overflow-y: auto;
                            }
                            .table th {
                                background-color: #f8f9fa;
                                position: sticky;
                                top: 0;
                                z-index: 1;
                            }
                            .table td {
                                vertical-align: middle;
                            }
                            .return-quantity {
                                width: 100px;
                            }
                        `)
                        .appendTo('head');
                }
            },
            error: function(xhr, status, error) {
                console.error('Get Receipt Error:', {xhr, status, error});
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری پسووڵە: ' + error
                });
            }
        });
    }

    // Function to view receipt details
    function viewReceipt(id, type) {
        // Get the correct receipt type based on the active tab
        const activeTab = $('.nav-tabs .active').attr('id');
        let receiptType;
        
        switch(activeTab) {
            case 'employee-payment-tab':
                receiptType = 'selling';
                break;
            case 'shipping-tab':
                receiptType = 'buying';
                break;
            case 'withdrawal-tab':
                receiptType = 'wasting';
                break;
            default:
                console.error('Unknown tab type:', activeTab);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'جۆری پسووڵەکە نەناسراوە'
                });
                return;
        }

        $.ajax({
            url: '../../api/get_receipt_details.php',
            type: 'POST',
            data: { 
                id: id, 
                type: receiptType 
            },
            success: function(response) {
                if (response.success) {
                    showReceiptDetails(response.data);
                } else {
                    console.error('API Error:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری پسووڵە'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر: ' + error
                });
            }
        });
    }

    // Function to show receipt details
    function showReceiptDetails(data) {
        let itemsHtml = '';
        if (data.items && data.items.length > 0) {
            data.items.forEach((item, index) => {
                itemsHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.product_name}</td>
                        <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                        <td>${item.unit_price} د.ع</td>
                        <td>${item.quantity}</td>
                        <td>${item.total_price} د.ع</td>
                    </tr>
                `;
            });
        } else {
            itemsHtml = '<tr><td colspan="6" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>';
        }

        Swal.fire({
            title: `پسووڵەی ژمارە: ${data.header.invoice_number}`,
            html: `
                <div class="receipt-details">
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>ناوی کڕیار/دابینکەر:</strong> ${data.header.customer_name || data.header.supplier_name}
                        </div>
                        <div class="col-6">
                            <strong>بەروار:</strong> ${data.header.date}
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
                                    <th colspan="5">کۆی کاڵاکان:</th>
                                    <td>${data.totals.subtotal} د.ع</td>
                                </tr>
                                <tr>
                                    <th colspan="5">کرێی گواستنەوە:</th>
                                    <td>${data.header.shipping_cost} د.ع</td>
                                </tr>
                                <tr>
                                    <th colspan="5">خەرجی تر:</th>
                                    <td>${data.header.other_costs || data.header.other_cost} د.ع</td>
                                </tr>
                                <tr>
                                    <th colspan="5">داشکاندن:</th>
                                    <td>${data.header.discount} د.ع</td>
                                </tr>
                                <tr>
                                    <th colspan="5">کۆی گشتی:</th>
                                    <td>${data.totals.grand_total} د.ع</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    ${data.header.notes ? `<div class="mt-3"><strong>تێبینی:</strong> ${data.header.notes}</div>` : ''}
                </div>
            `,
            width: '800px',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                container: 'swal-rtl'
            }
        });
    }

    // Add delete functionality
    $(document).on('click', '.delete-btn', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).closest('.tab-pane').attr('id') === 'employee-payment-content' ? 'sale' : 'purchase';
        const row = $(this).closest('tr');
        
        Swal.fire({
            title: 'دڵنیای؟',
            text: 'ئەم پسووڵەیە دەسڕێتەوە و ناتوانرێت بگەڕێنرێتەوە',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بیسڕەوە',
            cancelButtonText: 'نەخێر',
            customClass: {
                popup: 'swal-rtl'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../api/delete_receipt.php',
                    type: 'POST',
                    data: {
                        id: receiptId,
                        type: receiptType
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سڕایەوە!',
                                text: 'پسووڵەکە بە سەرکەوتوویی سڕایەوە',
                                customClass: {
                                    popup: 'swal-rtl'
                                }
                            }).then(() => {
                                row.fadeOut(400, function() {
                                    $(this).remove();
                                });
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: response.message,
                                customClass: {
                                    popup: 'swal-rtl'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە سڕینەوەی پسووڵە',
                            customClass: {
                                popup: 'swal-rtl'
                            }
                        });
                    }
                });
            }
        });
    });
});