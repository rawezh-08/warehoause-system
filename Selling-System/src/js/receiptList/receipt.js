$(document).ready(function() {
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
                        <td>${sale.payment_type === 'cash' ? 'نەقد' : 'قەرز'}</td>
                        <td>${sale.total_amount} دینار</td>
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
        } else {
            tbody.html('<tr><td colspan="7" class="text-center">هیچ پسووڵەیەک نەدۆزرایەوە</td></tr>');
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

    // Function to view receipt details
    function viewReceipt(id, type) {
        $.ajax({
            url: '../../api/get_receipt_details.php',
            type: 'POST',
            data: { id: id, type: type },
            success: function(response) {
                if (response.success) {
                    showReceiptDetails(response.data);
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

    // Function to show receipt details
    function showReceiptDetails(data) {
        // Implementation depends on your receipt details modal structure
        // This is just a placeholder
        Swal.fire({
            title: 'زانیاری پسووڵە',
            html: `<div class="receipt-details">...</div>`,
            width: '800px',
            showCloseButton: true,
            showConfirmButton: false
        });
    }
});