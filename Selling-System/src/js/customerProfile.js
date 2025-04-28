$(document).ready(function() {
    // Get customer ID from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get('id');
    
    // Set the hidden customer ID field
    $('#customerId').val(customerId);
    
    if (!customerId) {
        // No customer ID provided, show error and redirect back
        Swal.fire({
            title: 'هەڵە',
            text: 'ناسنامەی قەرزار نەدۆزرایەوە',
            icon: 'error',
            confirmButtonText: 'گەڕانەوە'
        }).then(() => {
            window.location.href = 'customerDebts.php';
        });
    } else {
        // Load customer data
        loadCustomerData(customerId);
        // Load purchase history
        loadPurchaseHistory(customerId);
        // Load payment history
        loadPaymentHistory(customerId);
        
        // Add global event listener for tab changes to ensure responsive tables work correctly
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
        });
    }
    
    // Set today's date as default for repayment
    const today = new Date().toISOString().split('T')[0];
    $('#repaymentDate').val(today);
    
    // Event handler for repayment form submission
    $('#repaymentForm').on('submit', function(e) {
        e.preventDefault();
        saveRepayment(customerId);
    });
    
    // Event handler for view purchase details
    $(document).on('click', '.view-purchase-btn', function() {
        const purchaseId = $(this).data('purchase-id');
        openPurchaseDetails(purchaseId);
    });
    
    // Event handler for return sale button
    $(document).on('click', '.return-sale', function() {
        const saleId = $(this).data('id');
        const invoiceNumber = $(this).data('invoice');
        returnSaleItems(saleId, invoiceNumber);
    });
});

// Function to load customer data
function loadCustomerData(customerId) {
    // In a real app, this would be an AJAX call to the server
    // For demo purposes, using sample data
    
    const sampleCustomers = {
        1: {
            id: 1,
            name: 'ئەحمەد محەمەد',
            phone: '07501234567',
            address: 'ھەولێر، شەقامی ١٠٠ مەتری',
            totalDebt: 1500.00,
            paidAmount: 500.00,
            remainingAmount: 1000.00,
            debtLimit: 2000.00,
            dueDate: '2023-12-30'
        },
        2: {
            id: 2,
            name: 'سارا عەلی',
            phone: '07707654321',
            address: 'سلێمانی، گەڕەکی بەختیاری',
            totalDebt: 3000.00,
            paidAmount: 1000.00,
            remainingAmount: 2000.00,
            debtLimit: 5000.00,
            dueDate: '2023-11-15'
        },
        3: {
            id: 3,
            name: 'کارزان عومەر',
            phone: '07501122334',
            address: 'دھۆک، شەقامی نەورۆز',
            totalDebt: 750.00,
            paidAmount: 250.00,
            remainingAmount: 500.00,
            debtLimit: 1000.00,
            dueDate: '2023-12-25'
        },
        4: {
            id: 4,
            name: 'هێڤی ڕەزا',
            phone: '07705566778',
            address: 'هەولێر، شاری نوێ',
            totalDebt: 2200.00,
            paidAmount: 700.00,
            remainingAmount: 1500.00,
            debtLimit: 3000.00,
            dueDate: '2023-10-30'
        },
        5: {
            id: 5,
            name: 'دلێر ڕەسوڵ',
            phone: '07508877665',
            address: 'سلێمانی، سالم سترێت',
            totalDebt: 900.00,
            paidAmount: 900.00,
            remainingAmount: 0.00,
            debtLimit: 2000.00,
            dueDate: '2023-11-20'
        },
        6: {
            id: 6,
            name: 'شادی حسێن',
            phone: '07701234567',
            address: 'هەولێر، گەڕەکی ئازادی',
            totalDebt: 1800.00,
            paidAmount: 800.00,
            remainingAmount: 1000.00,
            debtLimit: 2500.00,
            dueDate: '2023-12-15'
        }
    };
    
    const customer = sampleCustomers[customerId];
    
    if (customer) {
        // Update page title
        document.title = `پڕۆفایلی قەرزار - ${customer.name}`;
        $('#pageHeader').text(`پڕۆفایلی قەرزار - ${customer.name}`);
        
        // Update customer info
        $('#customerName').text(customer.name);
        $('#customerPhone').text(customer.phone);
        $('#customerAddress').text(customer.address);
        
        // Update debt info
        $('#totalAmount').text(`${customer.totalDebt.toFixed(2)} $`);
        $('#paidAmount').text(`${customer.paidAmount.toFixed(2)} $`);
        $('#remainingAmount').text(`${customer.remainingAmount.toFixed(2)} $`);
        $('#debtLimit').text(`${customer.debtLimit.toFixed(2)} $`);
        $('#paymentDueDate').text(formatDate(customer.dueDate));
    } else {
        Swal.fire({
            title: 'هەڵە',
            text: 'زانیاری قەرزار نەدۆزرایەوە',
            icon: 'error',
            confirmButtonText: 'گەڕانەوە'
        }).then(() => {
            window.location.href = 'customerDebts.php';
        });
    }
}

// Function to load purchase history
function loadPurchaseHistory(customerId) {
    // In a real app, this would be an AJAX call to the server
    // For demo purposes, using sample data
    const samplePurchases = [
        {
            id: 101,
            invoiceNumber: 'INV-2023-001',
            date: '2023-10-15',
            total: 500.00,
            paid: 200.00,
            remaining: 300.00,
            status: 'partial' // 'paid', 'unpaid', 'partial'
        },
        {
            id: 102,
            invoiceNumber: 'INV-2023-015',
            date: '2023-11-02',
            total: 750.00,
            paid: 300.00,
            remaining: 450.00,
            status: 'partial'
        },
        {
            id: 103,
            invoiceNumber: 'INV-2023-023',
            date: '2023-11-10',
            total: 250.00,
            paid: 0.00,
            remaining: 250.00,
            status: 'unpaid'
        }
    ];
    
    const purchasesTableBody = $('#purchasesTableBody');
    purchasesTableBody.empty();
    
    samplePurchases.forEach(purchase => {
        let statusBadge;
        switch(purchase.status) {
            case 'paid':
                statusBadge = '<span class="badge bg-success">پارەدراوە</span>';
                break;
            case 'unpaid':
                statusBadge = '<span class="badge bg-danger">پارە نەدراوە</span>';
                break;
            case 'partial':
                statusBadge = '<span class="badge bg-warning">بەشێکی دراوە</span>';
                break;
            default:
                statusBadge = '<span class="badge bg-secondary">نادیار</span>';
        }
        
        const row = `
            <tr>
                <td>${purchase.invoiceNumber}</td>
                <td>${formatDate(purchase.date)}</td>
                <td>${purchase.total.toFixed(2)} $</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-info view-purchase-btn" data-purchase-id="${purchase.id}">
                        <i class="fas fa-eye"></i> بینین
                    </button>
                </td>
            </tr>
        `;
        
        purchasesTableBody.append(row);
    });
    
    // Initialize DataTable for purchases
    if ($.fn.DataTable.isDataTable('#purchasesTable')) {
        $('#purchasesTable').DataTable().destroy();
    }
    
    $('#purchasesTable').DataTable({
        responsive: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Kurdish.json'
        },
        // Add RTL support
        direction: 'rtl',
        // Customize column rendering for proper RTL support
        columnDefs: [
            { className: "dt-right", targets: "_all" },
            { className: "dt-center", targets: 4 } // Action column
        ]
    });
    
    // Fix for tab switching issues with responsive tables
    $('#purchases-tab').on('shown.bs.tab', function() {
        $('#purchasesTable').DataTable().columns.adjust().responsive.recalc();
    });
}

// Function to load payment history
function loadPaymentHistory(customerId) {
    // In a real app, this would be an AJAX call to the server
    // For demo purposes, using sample data
    const samplePayments = [
        {
            id: 201,
            date: '2023-10-20',
            amount: 200.00,
            method: 'cash', // 'cash', 'bank_transfer', 'check', 'other'
            note: 'پارەدانی سەرەتایی'
        },
        {
            id: 202,
            date: '2023-11-05',
            amount: 300.00,
            method: 'bank_transfer',
            note: 'گواستنەوە لە ڕێگەی بانکی کوردستانەوە'
        }
    ];
    
    const paymentsTableBody = $('#paymentsTableBody');
    paymentsTableBody.empty();
    
    samplePayments.forEach((payment, index) => {
        let methodText;
        switch(payment.method) {
            case 'cash':
                methodText = 'نەقد';
                break;
            case 'bank_transfer':
                methodText = 'گواستنەوەی بانکی';
                break;
            case 'check':
                methodText = 'چەک';
                break;
            case 'other':
                methodText = 'شێوازی تر';
                break;
            default:
                methodText = payment.method;
        }
        
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${formatDate(payment.date)}</td>
                <td>${payment.amount.toFixed(2)} $</td>
                <td>${methodText}</td>
                <td>${payment.note || '-'}</td>
            </tr>
        `;
        
        paymentsTableBody.append(row);
    });
    
    // Initialize DataTable for payments
    if ($.fn.DataTable.isDataTable('#paymentsTable')) {
        $('#paymentsTable').DataTable().destroy();
    }
    
    $('#paymentsTable').DataTable({
        responsive: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Kurdish.json'
        },
        // Add RTL support
        direction: 'rtl',
        // Customize column rendering for proper RTL support
        columnDefs: [
            { className: "dt-right", targets: "_all" }
        ]
    });
    
    // Fix for tab switching issues with responsive tables
    $('#payments-tab').on('shown.bs.tab', function() {
        $('#paymentsTable').DataTable().columns.adjust().responsive.recalc();
    });
}

// Function to open purchase details
function openPurchaseDetails(purchaseId) {
    // In a real app, this would be an AJAX call to the server
    // For demo purposes, using sample data
    const samplePurchaseDetails = {
        id: purchaseId,
        invoiceNumber: purchaseId === 101 ? 'INV-2023-001' : 
                       (purchaseId === 102 ? 'INV-2023-015' : 'INV-2023-023'),
        date: purchaseId === 101 ? '2023-10-15' : 
              (purchaseId === 102 ? '2023-11-02' : '2023-11-10'),
        total: purchaseId === 101 ? 500.00 : 
               (purchaseId === 102 ? 750.00 : 250.00),
        paid: purchaseId === 101 ? 200.00 : 
              (purchaseId === 102 ? 300.00 : 0.00),
        remaining: purchaseId === 101 ? 300.00 : 
                  (purchaseId === 102 ? 450.00 : 250.00),
        status: purchaseId === 101 || purchaseId === 102 ? 'partial' : 'unpaid',
        items: [
            {
                id: 1,
                name: 'ئامێری تەلەفۆن',
                unitPrice: 250.00,
                quantity: 1,
                total: 250.00
            },
            {
                id: 2,
                name: 'جانتای تەلەفۆن',
                unitPrice: 25.00,
                quantity: 2,
                total: 50.00
            }
        ]
    };
    
    // Update modal with purchase details
    $('#receiptNumber').text(samplePurchaseDetails.invoiceNumber);
    $('#receiptDate').text(formatDate(samplePurchaseDetails.date));
    $('#receiptTotal').text(`${samplePurchaseDetails.total.toFixed(2)} $`);
    
    let statusText;
    switch(samplePurchaseDetails.status) {
        case 'paid':
            statusText = '<span class="badge bg-success">پارەدراوە</span>';
            break;
        case 'unpaid':
            statusText = '<span class="badge bg-danger">پارە نەدراوە</span>';
            break;
        case 'partial':
            statusText = '<span class="badge bg-warning">بەشێکی دراوە</span>';
            break;
        default:
            statusText = '<span class="badge bg-secondary">نادیار</span>';
    }
    
    $('#receiptPaymentStatus').html(statusText);
    $('#receiptRemainingAmount').text(`${samplePurchaseDetails.remaining.toFixed(2)} $`);
    
    // Populate receipt items table
    const receiptItemsTableBody = $('#receiptItemsTableBody');
    receiptItemsTableBody.empty();
    
    samplePurchaseDetails.items.forEach((item, index) => {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${item.name}</td>
                <td>${item.unitPrice.toFixed(2)} $</td>
                <td>${item.quantity}</td>
                <td>${item.total.toFixed(2)} $</td>
            </tr>
        `;
        
        receiptItemsTableBody.append(row);
    });
    
    // Show the modal
    $('#purchaseDetailsModal').modal('show');
}

// Function to save repayment
function saveRepayment(customerId) {
    const amount = parseFloat($('#repaymentAmount').val());
    const date = $('#repaymentDate').val();
    const method = $('#repaymentMethod').val();
    const note = $('#repaymentNote').val();
    
    // Validate amount
    if (isNaN(amount) || amount <= 0) {
        Swal.fire({
            title: 'هەڵە',
            text: 'تکایە بڕی پارەی دروست داخڵ بکە',
            icon: 'error',
            confirmButtonText: 'باشە'
        });
        return;
    }
    
    // In a real app, this would be an AJAX call to save the repayment
    // For demo purposes, just show success message and reload payment history
    
    Swal.fire({
        title: 'سەرکەوتوو',
        text: 'پارەدانەوە بە سەرکەوتوویی تۆمارکرا',
        icon: 'success',
        confirmButtonText: 'باشە'
    }).then(() => {
        // Reset form fields
        $('#repaymentAmount').val('');
        $('#repaymentNote').val('');
        
        // Reload payment history
        loadPaymentHistory(customerId);
        
        // Reload customer data to update debt amounts
        loadCustomerData(customerId);
    });
}

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return '--/--/----';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    
    return date.toLocaleDateString('ku-IQ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Function to handle returning sale items
function returnSaleItems(saleId, invoiceNumber) {
    // Show loading
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        text: 'زانیارییەکان وەردەگیرێن',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Get sale items
    $.ajax({
        url: '../../ajax/get_sale_items.php',
        type: 'POST',
        data: {
            sale_id: saleId
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success) {
                // Check if sale has returns
                if (response.has_returns) {
                    Swal.fire({
                        title: 'ئاگاداری!',
                        text: 'ئەم پسووڵە پێشتر گەڕێنراوەتەوە',
                        icon: 'warning',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }
                
                // Check if sale has payments
                if (response.has_payments) {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'ناتوانرێت ئەم پسووڵە بگەڕێتەوە چونکە پارەدانەوەی لەسەر تۆمار کراوە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }
                
                // Create return form
                let itemsHtml = '<form id="returnSaleForm">';
                itemsHtml += '<input type="hidden" name="sale_id" value="' + saleId + '">';
                itemsHtml += '<input type="hidden" name="receipt_type" value="selling">';
                
                // Add introduction text explaining return limits
                itemsHtml += '<div class="alert alert-info mb-3">';
                itemsHtml += '<i class="fas fa-info-circle me-2"></i> ';
                itemsHtml += 'تکایە ئاگاداربە کە دەتوانیت ئەو بڕەی گەڕێنیتەوە کە پێشتر نەگەڕێنراوەتەوە. ';
                itemsHtml += '</div>';
                
                itemsHtml += '<div class="form-group mb-3">';
                itemsHtml += '<label for="returnReason">هۆکاری گەڕاندنەوە:</label>';
                itemsHtml += '<select class="form-select" id="returnReason" name="reason">';
                itemsHtml += '<option value="damage">زیانلێکەوتوو</option>';
                itemsHtml += '<option value="wrong-product">کاڵای هەڵە</option>';
                itemsHtml += '<option value="expired">بەسەرچوو</option>';
                itemsHtml += '<option value="other" selected>هۆکاری تر</option>';
                itemsHtml += '</select>';
                itemsHtml += '</div>';
                
                itemsHtml += '<div class="form-group mb-3">';
                itemsHtml += '<label for="returnNotes">تێبینی:</label>';
                itemsHtml += '<textarea class="form-control" id="returnNotes" name="notes" rows="2"></textarea>';
                itemsHtml += '</div>';
                
                itemsHtml += '<div class="table-responsive"><table class="table table-bordered">';
                itemsHtml += '<thead><tr>';
                itemsHtml += '<th>ناوی کاڵا</th>';
                itemsHtml += '<th>بڕی کڕین</th>';
                itemsHtml += '<th>گەڕاوە پێشتر</th>';
                itemsHtml += '<th>بەردەست بۆ گەڕاندنەوە</th>';
                itemsHtml += '<th>بڕی گەڕاندنەوە</th>';
                itemsHtml += '</tr></thead>';
                itemsHtml += '<tbody>';
                
                response.items.forEach(function(item) {
                    const remainingQuantity = parseFloat(item.quantity) - parseFloat(item.returned_quantity || 0);
                    const displayUnit = item.unit_type === 'piece' ? 'دانە' : 
                                        (item.unit_type === 'box' ? 'کارتۆن' : 'سێت');
                    
                    if (remainingQuantity > 0) {
                        itemsHtml += '<tr>';
                        itemsHtml += '<td>' + item.product_name + '</td>';
                        itemsHtml += '<td>' + item.quantity + ' ' + displayUnit + '</td>';
                        itemsHtml += '<td>' + (item.returned_quantity || 0) + ' ' + displayUnit + '</td>';
                        itemsHtml += '<td>' + remainingQuantity + ' ' + displayUnit + '</td>';
                        itemsHtml += '<td><input type="number" class="form-control return-quantity" name="return_quantities[' + 
                            item.id + ']" min="0" max="' + remainingQuantity + '" step="0.01" value="0"></td>';
                        itemsHtml += '</tr>';
                    }
                });
                
                itemsHtml += '</tbody></table></div>';
                itemsHtml += '</form>';
                
                Swal.fire({
                    title: `گەڕاندنەوەی کاڵا - پسووڵە ${invoiceNumber}`,
                    html: itemsHtml,
                    width: '800px',
                    showCancelButton: true,
                    confirmButtonText: 'گەڕاندنەوە',
                    cancelButtonText: 'هەڵوەشاندنەوە',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        // Validate that at least one item has been selected for return
                        let hasReturns = false;
                        document.querySelectorAll('.return-quantity').forEach(input => {
                            if (parseFloat(input.value) > 0) {
                                hasReturns = true;
                            }
                        });
                        
                        if (!hasReturns) {
                            Swal.showValidationMessage('تکایە لانی کەم یەک کاڵا هەڵبژێرە بۆ گەڕاندنەوە');
                            return false;
                        }
                        
                        const formData = new FormData(document.getElementById('returnSaleForm'));
                        
                        return fetch('../../ajax/return_sale.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                throw new Error(data.message || 'هەڵەیەک ڕوویدا لە کاتی گەڕاندنەوەی کاڵاکان');
                            }
                            return data;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`هەڵە: ${error.message}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'سەرکەوتوو!',
                            text: 'کاڵاکان بە سەرکەوتوویی گەڕێنرانەوە',
                            icon: 'success',
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            // Reload the page to show updated data
                            location.reload();
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: 'هەڵە!',
                    text: response.message || 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            Swal.fire({
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        }
    });
} 