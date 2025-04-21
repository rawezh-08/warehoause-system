$(document).ready(function() {
    // Function to load selling receipts
    function loadSellingReceipts() {
        // Use the same filter function with default values
        const startDate = $('#employeePaymentStartDate').val();
        const endDate = $('#employeePaymentEndDate').val();
        const customerName = $('#employeePaymentName').val();
        
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
        
        console.log('Filtering sales data with:', { startDate, endDate, customerName });
        
        // Show loading indicator
        $('.table-responsive').addClass('loading');
        
        $.ajax({
            url: '../../api/filter_receipts.php',
            type: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate,
                customer_name: customerName,
                type: 'sale' // Use 'sale' instead of 'selling'
            },
            success: function(response) {
                // Hide loading indicator
                $('.table-responsive').removeClass('loading');
                
                console.log('Sales filter response:', response);
                
                if (response.success) {
                    updateSalesTable(response.data);
                } else {
                    console.error('Error filtering sales data:', response.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Hide loading indicator
                $('.table-responsive').removeClass('loading');
                
                console.error('AJAX error when filtering sales data:', { xhr, status, error });
                
                // Construct detailed error message
                let errorDetails = '';
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorDetails = errorResponse.message || '';
                        console.error('Server error response:', errorResponse);
                    } catch (e) {
                        errorDetails = xhr.responseText;
                        console.error('Raw server error:', xhr.responseText);
                    }
                }
                
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری: ' + (errorDetails || error || status),
                    footer: '<strong>Status Code:</strong> ' + xhr.status
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
        
        console.log('Filtering purchases data with:', { startDate, endDate, supplierName, invoiceNumber });
        
        // Show loading indicator
        $('#purchasesTableContainer').addClass('loading');
        
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
                // Hide loading indicator
                $('#purchasesTableContainer').removeClass('loading');
                
                console.log('Purchases filter response:', response);
                
                if (response.success) {
                    updatePurchasesTable(response.data);
                } else {
                    console.error('Error filtering purchases data:', response.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Hide loading indicator
                $('#purchasesTableContainer').removeClass('loading');
                
                console.error('AJAX error when filtering purchases data:', { xhr, status, error });
                
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری: ' + (error || status)
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
    $(document).on('click', '#receiptList .view-btn', function() {
        const id = $(this).data('id');
        const type = $(this).closest('.tab-pane').attr('id');
        viewReceipt(id, type);
    });

    // Handle edit button clicks
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
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
        
        // Load receipt data and show edit modal
        loadReceiptForEditing(id, receiptType);
    });

    // Handle print button clicks
    $(document).on('click', '.print-btn', function() {
        const id = $(this).data('id');
        const activeTab = $('.nav-tabs .active').attr('id');
        let type;
        
        // Determine the correct receipt type based on active tab
        switch(activeTab) {
            case 'employee-payment-tab':
                type = 'sale';
                break;
            case 'shipping-tab':
                type = 'purchase';
                break;
            case 'withdrawal-tab':
                type = 'wasting';
                break;
            default:
                console.error('Unknown tab type:', activeTab);
                return;
        }
        
        // Open print receipt page in new window with correct parameters
        if (type === 'sale') {
            window.open(`../../views/receipt/print_receipt.php?sale_id=${id}`, '_blank');
        } else if (type === 'purchase') {
            window.open(`../../views/receipt/print_receipt.php?purchase_id=${id}`, '_blank');
        } else {
            window.open(`../../views/receipt/print_receipt.php?waste_id=${id}`, '_blank');
        }
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
                                    <td style="min-width: 200px; word-break: break-word; overflow-wrap: break-word;">
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
                                               data-unit-type="${item.unit_type}"
                                               data-product-name="${item.product_name}"
                                               onchange="calculateReturnTotals()">
                                    </td>
                                    <td class="item-return-total text-end" style="min-width: 120px;">0 د.ع</td>
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
                                <input type="hidden" id="paymentType" value="${data.header.payment_type || 'cash'}">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
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
                                                <th>کۆی گشتی</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${itemsHtml}
                                        </tbody>
                                    </table>
                                </div>

                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">زانیاری گەڕاندنەوە</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="returnReason" class="form-label fw-bold">هۆکاری گەڕاندنەوە</label>
                                                    <select class="form-select" id="returnReason" required>
                                                        <option value="">هۆکار هەڵبژێرە</option>
                                                        <option value="damaged">کاڵای خراپ</option>
                                                        <option value="wrong_product">کاڵای هەڵە</option>
                                                        <option value="customer_request">داواکاری کڕیار</option>
                                                        <option value="other">هۆکاری تر</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="returnNotes" class="form-label fw-bold">تێبینی</label>
                                                    <textarea class="form-control" id="returnNotes" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="fw-bold">کۆی بەهای گەڕاندنەوە:</span>
                                                        <span id="totalReturnAmount" class="fw-bold fs-5">0 د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="fw-bold">جۆری پارەدان:</span>
                                                        <span id="paymentTypeDisplay" class="fw-bold text-primary">${data.header.payment_type === 'credit' ? 'قەرز' : 'نەقد'}</span>
                                                    </div>
                                                </div>
                                                ${data.header.payment_type === 'credit' ? `
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="fw-bold">قەرزی پێشوو:</span>
                                                        <span id="previousDebt" class="fw-bold text-danger">${data.header.remaining_amount || 0} د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="fw-bold">قەرزی نوێ (دوای گەڕاندنەوە):</span>
                                                        <span id="newDebt" class="fw-bold text-danger">${data.header.remaining_amount || 0} د.ع</span>
                                                    </div>
                                                </div>` : ''}
                                                <div class="alert alert-info mt-3" id="returnSummary">
                                                    تکایە بڕی گەڕاندنەوە دیاری بکە
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                        didOpen: () => {
                            // Define the calculation function in global scope
                            window.calculateReturnTotals = function() {
                                let totalReturn = 0;
                                let returnedItems = 0;
                                let itemsSummary = [];
                                
                                // Calculate each item's return amount
                                $('.return-quantity').each(function() {
                                    const quantity = parseFloat($(this).val()) || 0;
                                    const unitPrice = parseFloat($(this).data('unit-price')) || 0;
                                    const totalPrice = quantity * unitPrice;
                                    
                                    // Update item total in the table
                                    $(this).closest('tr').find('.item-return-total').text(totalPrice.toFixed(0) + ' د.ع');
                                    
                                    // Add to total return amount
                                    totalReturn += totalPrice;
                                    
                                    // Add to summary if returning this item
                                    if (quantity > 0) {
                                        returnedItems++;
                                        itemsSummary.push({
                                            name: $(this).data('product-name'),
                                            quantity: quantity,
                                            total: totalPrice
                                        });
                                    }
                                });
                                
                                // Update UI
                                $('#totalReturnAmount').text(totalReturn.toFixed(0) + ' د.ع');
                                
                                // Calculate new debt if credit payment
                                if ($('#paymentType').val() === 'credit') {
                                    const previousDebt = parseFloat($('#previousDebt').text().replace(' د.ع', '')) || 0;
                                    const newDebt = Math.max(0, previousDebt - totalReturn);
                                    $('#newDebt').text(newDebt.toFixed(0) + ' د.ع');
                                }
                                
                                // Update summary
                                if (returnedItems === 0) {
                                    $('#returnSummary').html('تکایە بڕی گەڕاندنەوە دیاری بکە');
                                } else {
                                    let summaryHtml = `<p>گەڕاندنەوەی ${returnedItems} کاڵا بە بەهای ${totalReturn.toFixed(0)} د.ع</p>`;
                                    
                                    if ($('#paymentType').val() === 'credit') {
                                        summaryHtml += `<p>قەرزی نوێ: ${$('#newDebt').text()}</p>`;
                                    }
                                    
                                    $('#returnSummary').html(summaryHtml);
                                }
                            };
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
                                            text: 'گەڕاندنەوەی کاڵاکان بە سەرکەوتوویی تۆمار کرا',
                                            html: `
                                                <p>گەڕاندنەوەی کاڵاکان بە سەرکەوتوویی تۆمار کرا</p>
                                                <p>بڕی گەڕێندراوە: ${response.return_amount} د.ع</p>
                                            `
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
        const receiptType = $(this).closest('.tab-pane').attr('id') === 'purchase-receipts-content' ? 'purchase' : 'sale';
        
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
                    url: `../../api/receipts/delete_${receiptType}.php`,
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
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
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

    // Function to load receipt data for editing
    function loadReceiptForEditing(id, receiptType) {
        // Show loading modal
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە',
            text: 'بارکردنی زانیاریەکانی پسووڵە...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Fetch receipt details from server
        $.ajax({
            url: '../../api/get_receipt_details.php',
            type: 'POST',
            data: { 
                id: id, 
                type: receiptType 
            },
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    showEditReceiptModal(response.data, receiptType);
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
                Swal.close();
                console.error('AJAX Error:', {xhr, status, error});
                
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر: ' + error
                });
            }
        });
    }
    
    // Function to show the receipt edit modal
    function showEditReceiptModal(data, receiptType) {
        // Create items HTML
        let itemsHtml = '';
        if (data.items && data.items.length > 0) {
            data.items.forEach((item, index) => {
                itemsHtml += `
                    <tr class="item-row" data-product-id="${item.product_id}">
                        <td>${index + 1}</td>
                        <td>${item.product_name}</td>
                        <td>
                            <select class="form-select form-select-sm unit-type">
                                <option value="piece" ${item.unit_type === 'piece' ? 'selected' : ''}>دانە</option>
                                <option value="box" ${item.unit_type === 'box' ? 'selected' : ''}>کارتۆن</option>
                                <option value="set" ${item.unit_type === 'set' ? 'selected' : ''}>سێت</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm unit-price" value="${item.unit_price}" min="0">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm quantity" value="${item.quantity}" min="1">
                        </td>
                        <td class="item-total">${item.total_price} د.ع</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        // Customer/Supplier selection based on receipt type
        let partnerSelectionHtml = '';
        
        if (receiptType === 'selling') {
            partnerSelectionHtml = `
                <div class="mb-3">
                    <label for="customerSelect" class="form-label">کڕیار</label>
                    <select class="form-select" id="customerSelect" required>
                        <option value="">هەڵبژاردنی کڕیار</option>
                        <!-- Customers will be loaded dynamically -->
                    </select>
                </div>
            `;
        } else if (receiptType === 'buying') {
            partnerSelectionHtml = `
                <div class="mb-3">
                    <label for="supplierSelect" class="form-label">فرۆشیار</label>
                    <select class="form-select" id="supplierSelect" required>
                        <option value="">هەڵبژاردنی فرۆشیار</option>
                        <!-- Suppliers will be loaded dynamically -->
                    </select>
                </div>
            `;
        }
        
        // Create and show the modal
        Swal.fire({
            title: 'دەستکاریکردنی پسووڵە',
            html: `
                <form id="editReceiptForm" class="text-start">
                    <input type="hidden" id="receiptId" value="${data.header.id}">
                    <input type="hidden" id="receiptType" value="${receiptType}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="invoiceNumber" class="form-label">ژمارەی پسووڵە</label>
                                <input type="text" class="form-control" id="invoiceNumber" value="${data.header.invoice_number}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="receiptDate" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="receiptDate" value="${formatDateForInput(data.header.date)}" required>
                            </div>
                        </div>
                    </div>
                    
                    ${partnerSelectionHtml}
                    
                    <div class="mb-3">
                        <h5>کاڵاکان</h5>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>کاڵا</th>
                                        <th>یەکە</th>
                                        <th>نرخی یەکە</th>
                                        <th>بڕ</th>
                                        <th>کۆی گشتی</th>
                                        <th>کردار</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    ${itemsHtml}
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addItemBtn">
                            <i class="fas fa-plus"></i> زیادکردنی کاڵا
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="shippingCost" class="form-label">کرێی گواستنەوە</label>
                                <input type="number" class="form-control" id="shippingCost" value="${data.header.shipping_cost || 0}" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="otherCosts" class="form-label">خەرجی تر</label>
                                <input type="number" class="form-control" id="otherCosts" value="${data.header.other_costs || 0}" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="discount" class="form-label">داشکاندن</label>
                                <input type="number" class="form-control" id="discount" value="${data.header.discount || 0}" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="paymentType" class="form-label">جۆری پارەدان</label>
                        <select class="form-select" id="paymentType">
                            <option value="cash" ${data.header.payment_type === 'cash' ? 'selected' : ''}>نەقد</option>
                            <option value="credit" ${data.header.payment_type === 'credit' ? 'selected' : ''}>قەرز</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 paid-amount-container" style="${data.header.payment_type === 'credit' ? '' : 'display: none;'}">
                        <label for="paidAmount" class="form-label">بڕی پارەی دراو</label>
                        <input type="number" class="form-control" id="paidAmount" value="${data.header.paid_amount || 0}" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">تێبینی</label>
                        <textarea class="form-control" id="notes" rows="3">${data.header.notes || ''}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">کۆی کاڵاکان</label>
                                <input type="text" class="form-control" id="subtotal" value="${data.totals.subtotal} د.ع" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">کۆی گشتی</label>
                                <input type="text" class="form-control" id="grandTotal" value="${data.totals.grand_total} د.ع" readonly>
                            </div>
                        </div>
                    </div>
                </form>
            `,
            width: '900px',
            showCancelButton: true,
            confirmButtonText: 'پاشەکەوتکردن',
            cancelButtonText: 'داخستن',
            showLoaderOnConfirm: true,
            customClass: {
                container: 'swal-rtl',
                popup: 'swal-wide'
            },
            didOpen: () => {
                // Load customers/suppliers based on receipt type
                if (receiptType === 'selling') {
                    loadCustomers(data.header.customer_id);
                } else if (receiptType === 'buying') {
                    loadSuppliers(data.header.supplier_id);
                }
                
                // Add event listener for payment type change
                $('#paymentType').on('change', function() {
                    if ($(this).val() === 'credit') {
                        $('.paid-amount-container').show();
                    } else {
                        $('.paid-amount-container').hide();
                    }
                });
                
                // Add event listeners for quantity and price changes
                $('#itemsTableBody').on('input', '.unit-price, .quantity', updateItemTotal);
                
                // Add event listener for remove item button
                $('#itemsTableBody').on('click', '.remove-item', function() {
                    $(this).closest('tr').remove();
                    recalculateTotals();
                });
                
                // Add event listener for add item button
                $('#addItemBtn').on('click', function() {
                    showAddProductModal(receiptType);
                });
                
                // Add event listeners for cost changes
                $('#shippingCost, #otherCosts, #discount').on('input', recalculateTotals);
            },
            preConfirm: () => {
                return collectFormData();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateReceipt(result.value);
            }
        });
    }
    
    // Function to format date for input field
    function formatDateForInput(dateString) {
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    }
    
    // Function to load customers
    function loadCustomers(selectedCustomerId) {
        $.ajax({
            url: '../../api/get_customers.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const customerSelect = $('#customerSelect');
                    customerSelect.find('option:not(:first)').remove();
                    
                    response.data.forEach(customer => {
                        const option = $('<option>')
                            .val(customer.id)
                            .text(customer.name);
                            
                        if (customer.id == selectedCustomerId) {
                            option.prop('selected', true);
                        }
                        
                        customerSelect.append(option);
                    });
                }
            }
        });
    }
    
    // Function to load suppliers
    function loadSuppliers(selectedSupplierId) {
        $.ajax({
            url: '../../api/get_suppliers.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const supplierSelect = $('#supplierSelect');
                    supplierSelect.find('option:not(:first)').remove();
                    
                    response.data.forEach(supplier => {
                        const option = $('<option>')
                            .val(supplier.id)
                            .text(supplier.name);
                            
                        if (supplier.id == selectedSupplierId) {
                            option.prop('selected', true);
                        }
                        
                        supplierSelect.append(option);
                    });
                }
            }
        });
    }
    
    // Function to show add product modal
    function showAddProductModal(receiptType) {
        Swal.fire({
            title: 'زیادکردنی کاڵا',
            html: `
                <form id="addProductForm">
                    <div class="mb-3">
                        <label for="productSelect" class="form-label">کاڵا</label>
                        <select class="form-select" id="productSelect" required>
                            <option value="">هەڵبژاردنی کاڵا</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="productUnitType" class="form-label">یەکە</label>
                                <select class="form-select" id="productUnitType">
                                    <option value="piece">دانە</option>
                                    <option value="box">کارتۆن</option>
                                    <option value="set">سێت</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="productUnitPrice" class="form-label">نرخی یەکە</label>
                                <input type="number" class="form-control" id="productUnitPrice" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="productQuantity" class="form-label">بڕ</label>
                                <input type="number" class="form-control" id="productQuantity" min="1" value="1" required>
                            </div>
                        </div>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'زیادکردن',
            cancelButtonText: 'داخستن',
            customClass: {
                container: 'swal-rtl'
            },
            didOpen: () => {
                // Load products
                loadProducts();
                
                // Calculate total when quantity or price changes
                $('#productUnitPrice, #productQuantity').on('input', function() {
                    calculateProductTotal();
                });
            },
            preConfirm: () => {
                const productId = $('#productSelect').val();
                const productName = $('#productSelect option:selected').text();
                const unitType = $('#productUnitType').val();
                const unitPrice = parseFloat($('#productUnitPrice').val());
                const quantity = parseInt($('#productQuantity').val());
                
                if (!productId) {
                    Swal.showValidationMessage('تکایە کاڵا هەڵبژێرە');
                    return false;
                }
                
                if (isNaN(unitPrice) || unitPrice <= 0) {
                    Swal.showValidationMessage('تکایە نرخی دروست داخل بکە');
                    return false;
                }
                
                if (isNaN(quantity) || quantity <= 0) {
                    Swal.showValidationMessage('تکایە بڕی دروست داخل بکە');
                    return false;
                }
                
                return {
                    id: productId,
                    name: productName,
                    unitType: unitType,
                    unitPrice: unitPrice,
                    quantity: quantity,
                    total: unitPrice * quantity
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                addProductToTable(result.value);
            }
        });
    }
    
    // Function to load products
    function loadProducts() {
        $.ajax({
            url: '../../api/get_products.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const productSelect = $('#productSelect');
                    productSelect.find('option:not(:first)').remove();
                    
                    response.data.forEach(product => {
                        productSelect.append(`<option value="${product.id}" data-price="${product.price}">${product.name}</option>`);
                    });
                    
                    // Set default price when product is selected
                    productSelect.on('change', function() {
                        const selectedOption = $(this).find('option:selected');
                        $('#productUnitPrice').val(selectedOption.data('price') || '');
                        calculateProductTotal();
                    });
                }
            }
        });
    }
    
    // Function to calculate product total
    function calculateProductTotal() {
        const unitPrice = parseFloat($('#productUnitPrice').val()) || 0;
        const quantity = parseInt($('#productQuantity').val()) || 0;
        const total = unitPrice * quantity;
    }
    
    // Function to add product to table
    function addProductToTable(product) {
        const rowCount = $('#itemsTableBody tr').length;
        const newRow = `
            <tr class="item-row" data-product-id="${product.id}">
                <td>${rowCount + 1}</td>
                <td>${product.name}</td>
                <td>
                    <select class="form-select form-select-sm unit-type">
                        <option value="piece" ${product.unitType === 'piece' ? 'selected' : ''}>دانە</option>
                        <option value="box" ${product.unitType === 'box' ? 'selected' : ''}>کارتۆن</option>
                        <option value="set" ${product.unitType === 'set' ? 'selected' : ''}>سێت</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm unit-price" value="${product.unitPrice}" min="0">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm quantity" value="${product.quantity}" min="1">
                </td>
                <td class="item-total">${product.total} د.ع</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#itemsTableBody').append(newRow);
        recalculateTotals();
    }
    
    // Function to update item total
    function updateItemTotal() {
        const row = $(this).closest('tr');
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const quantity = parseInt(row.find('.quantity').val()) || 0;
        const total = unitPrice * quantity;
        
        row.find('.item-total').text(total + ' د.ع');
        recalculateTotals();
    }
    
    // Function to recalculate totals
    function recalculateTotals() {
        let subtotal = 0;
        
        // Sum all item totals
        $('.item-total').each(function() {
            const totalText = $(this).text();
            const total = parseFloat(totalText.replace(' د.ع', '')) || 0;
            subtotal += total;
        });
        
        // Get additional costs
        const shippingCost = parseFloat($('#shippingCost').val()) || 0;
        const otherCosts = parseFloat($('#otherCosts').val()) || 0;
        const discount = parseFloat($('#discount').val()) || 0;
        
        // Calculate grand total
        const grandTotal = subtotal + shippingCost + otherCosts - discount;
        
        // Update the display
        $('#subtotal').val(subtotal + ' د.ع');
        $('#grandTotal').val(grandTotal + ' د.ع');
    }
    
    // Function to collect form data
    function collectFormData() {
        const receiptId = $('#receiptId').val();
        const receiptType = $('#receiptType').val();
        const invoiceNumber = $('#invoiceNumber').val();
        const receiptDate = $('#receiptDate').val();
        const paymentType = $('#paymentType').val();
        const shippingCost = parseFloat($('#shippingCost').val()) || 0;
        const otherCosts = parseFloat($('#otherCosts').val()) || 0;
        const discount = parseFloat($('#discount').val()) || 0;
        const notes = $('#notes').val();
        const paidAmount = parseFloat($('#paidAmount').val()) || 0;
        
        // Get customer or supplier ID
        let partnerId = null;
        if (receiptType === 'selling') {
            partnerId = $('#customerSelect').val();
            if (!partnerId) {
                Swal.showValidationMessage('تکایە کڕیار هەڵبژێرە');
                return false;
            }
        } else if (receiptType === 'buying') {
            partnerId = $('#supplierSelect').val();
            if (!partnerId) {
                Swal.showValidationMessage('تکایە فرۆشیار هەڵبژێرە');
                return false;
            }
        }
        
        // Get items
        const items = [];
        $('.item-row').each(function() {
            const row = $(this);
            const productId = row.data('product-id');
            const unitType = row.find('.unit-type').val();
            const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
            const quantity = parseInt(row.find('.quantity').val()) || 0;
            
            if (productId && unitPrice > 0 && quantity > 0) {
                items.push({
                    product_id: productId,
                    unit_type: unitType,
                    unit_price: unitPrice,
                    quantity: quantity
                });
            }
        });
        
        if (items.length === 0) {
            Swal.showValidationMessage('تکایە لانی کەم یەک کاڵا زیاد بکە');
            return false;
        }
        
        // Build the form data
        const formData = {
            id: receiptId,
            type: receiptType,
            invoice_number: invoiceNumber,
            date: receiptDate,
            payment_type: paymentType,
            shipping_cost: shippingCost,
            other_costs: otherCosts,
            discount: discount,
            notes: notes,
            items: items
        };
        
        // Add partner ID based on receipt type
        if (receiptType === 'selling') {
            formData.customer_id = partnerId;
            if (paymentType === 'credit') {
                formData.paid_amount = paidAmount;
            }
        } else if (receiptType === 'buying') {
            formData.supplier_id = partnerId;
            if (paymentType === 'credit') {
                formData.paid_amount = paidAmount;
            }
        }
        
        return formData;
    }
    
    // Function to update receipt
    function updateReceipt(formData) {
        $.ajax({
            url: '../../api/update_receipt.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو!',
                        text: 'پسووڵەکە بە سەرکەوتوویی نوێ کرایەوە',
                        customClass: {
                            container: 'swal-rtl'
                        }
                    }).then(() => {
                        // Refresh the data
                        if (formData.type === 'selling') {
                            filterSalesData();
                        } else if (formData.type === 'buying') {
                            filterPurchasesData();
                        } else if (formData.type === 'wasting') {
                            filterWasteData();
                        }
                    });
                } else {
                    console.error('Update Error:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی پسووڵە',
                        customClass: {
                            container: 'swal-rtl'
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر: ' + error,
                    customClass: {
                        container: 'swal-rtl'
                    }
                });
            }
        });
    }
});