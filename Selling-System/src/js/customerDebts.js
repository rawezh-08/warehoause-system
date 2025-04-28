$(document).ready(function() {
    // Load sample data
    loadSampleCustomers();
    
    // Event handler for search button
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        filterCustomers(searchTerm);
    });
    
    // Event handler for search input
    $('#searchInput').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = $(this).val().toLowerCase();
            filterCustomers(searchTerm);
        }
    });
    
    // Event handler for sort select
    $('#sortSelect').on('change', function() {
        const sortOption = $(this).val();
        sortCustomers(sortOption);
    });
    
    // Event handler for refresh button
    $('#refreshBtn').on('click', function() {
        loadSampleCustomers();
    });
    
    // Event handler for add debt button
    $('#addDebtBtn').on('click', function() {
        $('#addDebtForm')[0].reset();
        $('#addDebtModal').modal('show');
    });
    
    // Event handler for save debt button
    $('#saveDebtBtn').on('click', function() {
        saveNewDebt();
    });
    
    // Event handler for repayment form submission
    $('#repaymentForm').on('submit', function(e) {
        e.preventDefault();
        saveRepayment();
    });
    
    // Event handler for opening customer profile
    $(document).on('click', '.customer-card', function() {
        const customerId = $(this).data('customer-id');
        // Redirect to customer profile page instead of opening modal
        window.location.href = `customerProfile.php?id=${customerId}`;
    });
    
    // Event handler for view purchase details
    $(document).on('click', '.view-purchase-btn', function(e) {
        e.stopPropagation();
        const purchaseId = $(this).data('purchase-id');
        openPurchaseDetails(purchaseId);
    });
});

// Function to load sample customer data
function loadSampleCustomers() {
    const sampleCustomers = [
        {
            id: 1,
            name: 'ئەحمەد محەمەد',
            phone: '07501234567',
            address: 'ھەولێر، شەقامی ١٠٠ مەتری',
            totalDebt: 1500.00,
            paidAmount: 500.00,
            remainingAmount: 1000.00,
            debtLimit: 2000.00,
            dueDate: '2023-12-30',
            isOverdue: false
        },
        {
            id: 2,
            name: 'سارا عەلی',
            phone: '07707654321',
            address: 'سلێمانی، گەڕەکی بەختیاری',
            totalDebt: 3000.00,
            paidAmount: 1000.00,
            remainingAmount: 2000.00,
            debtLimit: 5000.00,
            dueDate: '2023-11-15',
            isOverdue: true
        },
        {
            id: 3,
            name: 'کارزان عومەر',
            phone: '07501122334',
            address: 'دھۆک، شەقامی نەورۆز',
            totalDebt: 750.00,
            paidAmount: 250.00,
            remainingAmount: 500.00,
            debtLimit: 1000.00,
            dueDate: '2023-12-25',
            isOverdue: false
        },
        {
            id: 4,
            name: 'هێڤی ڕەزا',
            phone: '07705566778',
            address: 'هەولێر، شاری نوێ',
            totalDebt: 2200.00,
            paidAmount: 700.00,
            remainingAmount: 1500.00,
            debtLimit: 3000.00,
            dueDate: '2023-10-30',
            isOverdue: true
        },
        {
            id: 5,
            name: 'دلێر ڕەسوڵ',
            phone: '07508877665',
            address: 'سلێمانی، سالم سترێت',
            totalDebt: 900.00,
            paidAmount: 900.00,
            remainingAmount: 0.00,
            debtLimit: 2000.00,
            dueDate: '2023-11-20',
            isOverdue: false
        },
        {
            id: 6,
            name: 'شادی حسێن',
            phone: '07701234567',
            address: 'هەولێر، گەڕەکی ئازادی',
            totalDebt: 1800.00,
            paidAmount: 800.00,
            remainingAmount: 1000.00,
            debtLimit: 2500.00,
            dueDate: '2023-12-15',
            isOverdue: false
        }
    ];
    
    renderCustomerCards(sampleCustomers);
}

// Function to render customer cards
function renderCustomerCards(customers) {
    const container = $('#customerCardsContainer');
    container.empty();
    
    customers.forEach(customer => {
        const paymentStatus = customer.remainingAmount <= 0 ? 
            '<span class="badge badge-paid">پارەدراوە</span>' : 
            (customer.isOverdue ? 
                '<span class="badge badge-unpaid">دواکەوتووە</span>' : 
                '<span class="badge badge-partial">بەشێکی دراوە</span>');
                
        const dueDateClass = customer.isOverdue ? 'overdue' : '';
        
        const card = `
            <div class="customer-card" data-customer-id="${customer.id}">
                <div class="card-header">
                    <h5 class="customer-name">${customer.name}</h5>
                    <div class="customer-info">
                        <p><i class="fas fa-phone"></i> ${customer.phone}</p>
                        <p><i class="fas fa-map-marker-alt"></i> ${customer.address}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="debt-amount">$${customer.remainingAmount.toFixed(2)}</div>
                    <div class="due-date ${dueDateClass}">
                        <strong>بەرواری دانەوە:</strong> ${formatDate(customer.dueDate)}
                    </div>
                    <div class="debt-progress-container">
                        <div class="progress debt-progress">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: ${(customer.paidAmount / customer.totalDebt) * 100}%" 
                                aria-valuenow="${customer.paidAmount}" 
                                aria-valuemin="0" 
                                aria-valuemax="${customer.totalDebt}">
                            </div>
                        </div>
                        <div class="progress-label">
                            <span>$${customer.paidAmount.toFixed(2)}</span>
                            <span>$${customer.totalDebt.toFixed(2)}</span>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        ${paymentStatus}
                    </div>
                </div>
            </div>
        `;
        
        container.append(card);
    });
}

// Function to filter customers based on search term
function filterCustomers(searchTerm) {
    $('.customer-card').each(function() {
        const customerName = $(this).find('.customer-name').text().toLowerCase();
        const customerPhone = $(this).find('.customer-info p:first').text().toLowerCase();
        const customerAddress = $(this).find('.customer-info p:last').text().toLowerCase();
        
        if (customerName.includes(searchTerm) || 
            customerPhone.includes(searchTerm) || 
            customerAddress.includes(searchTerm)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Function to sort customers
function sortCustomers(sortOption) {
    const container = $('#customerCardsContainer');
    const cards = container.find('.customer-card').get();
    
    cards.sort(function(a, b) {
        if (sortOption === 'name') {
            const nameA = $(a).find('.customer-name').text().toLowerCase();
            const nameB = $(b).find('.customer-name').text().toLowerCase();
            return nameA.localeCompare(nameB);
        } else if (sortOption === 'debt-high') {
            const debtA = parseFloat($(a).find('.debt-amount').text().replace('$', ''));
            const debtB = parseFloat($(b).find('.debt-amount').text().replace('$', ''));
            return debtB - debtA;
        } else if (sortOption === 'debt-low') {
            const debtA = parseFloat($(a).find('.debt-amount').text().replace('$', ''));
            const debtB = parseFloat($(b).find('.debt-amount').text().replace('$', ''));
            return debtA - debtB;
        } else if (sortOption === 'date') {
            const dateA = new Date($(a).find('.due-date').text().split(':')[1].trim());
            const dateB = new Date($(b).find('.due-date').text().split(':')[1].trim());
            return dateA - dateB;
        }
        return 0;
    });
    
    $.each(cards, function(i, card) {
        container.append(card);
    });
}

// Function to open customer profile - keep this function for backward compatibility
// but change its implementation to redirect instead of opening a modal
function openCustomerProfile(customerId) {
    // Redirect to customer profile page
    window.location.href = `customerProfile.php?id=${customerId}`;
}

// Function to load sample purchase data
function loadSamplePurchases(customerId) {
    const purchases = [
        {
            id: 101,
            receiptNumber: 'INV-2023-001',
            date: '2023-10-15',
            total: 500.00,
            paymentStatus: 'partial',
            remainingAmount: 200.00
        },
        {
            id: 102,
            receiptNumber: 'INV-2023-005',
            date: '2023-10-22',
            total: 750.00,
            paymentStatus: 'unpaid',
            remainingAmount: 750.00
        },
        {
            id: 103,
            receiptNumber: 'INV-2023-008',
            date: '2023-11-05',
            total: 250.00,
            paymentStatus: 'paid',
            remainingAmount: 0.00
        }
    ];
    
    const tbody = $('#purchasesTableBody');
    tbody.empty();
    
    purchases.forEach(purchase => {
        const paymentStatusBadge = purchase.paymentStatus === 'paid' ? 
            '<span class="badge bg-success">پارەدراوە</span>' : 
            (purchase.paymentStatus === 'partial' ? 
                '<span class="badge bg-warning">بەشێکی دراوە</span>' : 
                '<span class="badge bg-danger">نەدراوە</span>');
        
        const row = `
            <tr>
                <td>${purchase.receiptNumber}</td>
                <td>${formatDate(purchase.date)}</td>
                <td>${purchase.total.toFixed(2)} $</td>
                <td>${paymentStatusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info view-purchase-btn" data-purchase-id="${purchase.id}">
                        <i class="fas fa-eye"></i> بینین
                    </button>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

// Function to load sample payment history
function loadSamplePaymentHistory(customerId) {
    const payments = [
        {
            id: 201,
            date: '2023-10-18',
            amount: 300.00,
            method: 'cash',
            note: 'پارەدانی سەرەتایی'
        },
        {
            id: 202,
            date: '2023-11-01',
            amount: 200.00,
            method: 'bank_transfer',
            note: 'گواستنەوە لە ڕێگەی بانکی کوردستان'
        },
        {
            id: 203,
            date: '2023-11-15',
            amount: 250.00,
            method: 'check',
            note: 'چەکی ژمارە 12345'
        }
    ];
    
    const tbody = $('#paymentsTableBody');
    tbody.empty();
    
    payments.forEach((payment, index) => {
        const methodTranslated = payment.method === 'cash' ? 'نەقد' : 
                              (payment.method === 'bank_transfer' ? 'گواستنەوەی بانکی' : 
                              (payment.method === 'check' ? 'چەک' : 'شێوازی تر'));
        
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${formatDate(payment.date)}</td>
                <td>${payment.amount.toFixed(2)} $</td>
                <td>${methodTranslated}</td>
                <td>${payment.note}</td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

// Function to open purchase details
function openPurchaseDetails(purchaseId) {
    // Sample receipt items data
    const receiptItems = [
        {
            id: 1001,
            product: 'تەلەفزیۆنی سامسونگ ٥٥ ئینچ',
            unitPrice: 350.00,
            quantity: 1,
            total: 350.00
        },
        {
            id: 1002,
            product: 'یاری پلەیستەیشن ٥',
            unitPrice: 80.00,
            quantity: 1,
            total: 80.00
        },
        {
            id: 1003,
            product: 'دەسکی یاری پلەیستەیشن',
            unitPrice: 35.00,
            quantity: 2,
            total: 70.00
        }
    ];
    
    // Set receipt details
    $('#receiptNumber').text('INV-2023-001');
    $('#receiptDate').text(formatDate('2023-10-15'));
    $('#receiptTotal').text('500.00 $');
    $('#receiptPaymentStatus').html('<span class="badge bg-warning">بەشێکی دراوە</span>');
    $('#receiptRemainingAmount').text('200.00 $');
    
    // Load receipt items
    const tbody = $('#receiptItemsTableBody');
    tbody.empty();
    
    receiptItems.forEach((item, index) => {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${item.product}</td>
                <td>${item.unitPrice.toFixed(2)} $</td>
                <td>${item.quantity}</td>
                <td>${item.total.toFixed(2)} $</td>
            </tr>
        `;
        
        tbody.append(row);
    });
    
    // Show the modal
    $('#purchaseDetailsModal').modal('show');
}

// Function to save new debt
function saveNewDebt() {
    const name = $('#debtCustomerName').val();
    const phone = $('#debtCustomerPhone').val();
    const address = $('#debtCustomerAddress').val();
    const amount = parseFloat($('#debtAmount').val());
    const dueDate = $('#debtDueDate').val();
    const note = $('#debtNote').val();
    
    if (!name || !phone || !amount || !dueDate) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە هەموو خانەکان پڕ بکەرەوە'
        });
        return;
    }
    
    // In a real app, this would send data to the server
    // For demonstration, we'll just show a success message
    
    Swal.fire({
        icon: 'success',
        title: 'سەرکەوتوو',
        text: 'قەرز بە سەرکەوتوویی زیادکرا'
    }).then(() => {
        $('#addDebtModal').modal('hide');
        
        // Add a new card for the new customer
        const newCustomer = {
            id: 7, // This would be generated by the server in a real app
            name: name,
            phone: phone,
            address: address || 'ناونیشان نییە',
            totalDebt: amount,
            paidAmount: 0,
            remainingAmount: amount,
            debtLimit: amount * 1.5, // Just for demonstration
            dueDate: dueDate,
            isOverdue: false
        };
        
        const customers = [newCustomer];
        renderCustomerCards(customers);
    });
}

// Function to save repayment
function saveRepayment() {
    const amount = parseFloat($('#repaymentAmount').val());
    const date = $('#repaymentDate').val();
    const method = $('#repaymentMethod').val();
    const note = $('#repaymentNote').val();
    
    if (!amount || !date || !method) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە هەموو خانەکان پڕ بکەرەوە'
        });
        return;
    }
    
    // In a real app, this would send data to the server
    // For demonstration, we'll just show a success message and update the payment history
    
    Swal.fire({
        icon: 'success',
        title: 'سەرکەوتوو',
        text: 'پارەدانەوە بە سەرکەوتوویی تۆمارکرا'
    }).then(() => {
        // Update payment history by adding a new row
        const methodTranslated = method === 'cash' ? 'نەقد' : 
                            (method === 'bank_transfer' ? 'گواستنەوەی بانکی' : 
                            (method === 'check' ? 'چەک' : 'شێوازی تر'));
                            
        const newPaymentRow = `
            <tr>
                <td>${$('#paymentsTableBody tr').length + 1}</td>
                <td>${formatDate(date)}</td>
                <td>${amount.toFixed(2)} $</td>
                <td>${methodTranslated}</td>
                <td>${note || ''}</td>
            </tr>
        `;
        
        $('#paymentsTableBody').append(newPaymentRow);
        
        // Update the customer summary
        const currentPaid = parseFloat($('#paidAmount').text().replace('$', '').trim());
        const currentRemaining = parseFloat($('#remainingAmount').text().replace('$', '').trim());
        
        const newPaid = currentPaid + amount;
        const newRemaining = Math.max(0, currentRemaining - amount);
        
        $('#paidAmount').text(newPaid.toFixed(2) + ' $');
        $('#remainingAmount').text(newRemaining.toFixed(2) + ' $');
        
        // Reset form
        $('#repaymentForm')[0].reset();
        $('#repaymentDate').val(new Date().toISOString().split('T')[0]);
    });
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