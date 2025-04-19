$(document).ready(function() {
    // Initialize variables to store totals
    let totalBalance = 0;
    let totalTheyOweUs = 0;
    let totalWeOweThem = 0;
    let totalAdvancePayments = 0;

    // Function to format currency with English numbers
    function formatCurrency(amount) {
        // Handle undefined, null, or invalid values
        if (amount === undefined || amount === null) {
            amount = 0;
        }
        // Convert to number if it's a string
        const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
        return new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(numAmount) + ' دینار';
    }

    // Function to parse currency string to number
    function parseCurrency(currencyString) {
        // Handle undefined, null, or invalid values
        if (currencyString === undefined || currencyString === null) {
            return 0;
        }
        // If it's already a number, return it
        if (typeof currencyString === 'number') {
            return currencyString;
        }
        // If it's a string, remove non-numeric characters (except decimal point and minus)
        return parseFloat(currencyString.toString().replace(/[^\d.-]/g, '')) || 0;
    }

    // Function to load customer data
    function loadCustomerData() {
        $.ajax({
            url: '../../api/customers/getCustomers.php',
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    // Reset totals before calculating
                    totalBalance = 0;
                    totalTheyOweUs = 0;
                    totalWeOweThem = 0;
                    totalAdvancePayments = 0;
                    
                    // Process the data
                    const customers = response.data.map(customer => {
                        // Ensure debit_on_business is a number
                        customer.debit_on_business = parseCurrency(customer.debit_on_business);
                        return customer;
                    });
                    
                    displayCustomerCards(customers);
                    updateTotalBalances(customers);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'کێشەیەک هەیە لە هێنانی زانیارییەکان',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'کێشەیەک هەیە لە پەیوەندیکردن بە سێرڤەرەوە',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }

    // Function to display customer cards
    function displayCustomerCards(customers) {
        const container = $('#customerCardsContainer');
        container.empty();

        customers.forEach(customer => {
            // Ensure we have valid numbers for balances
            const balance = parseCurrency(customer.debit_on_business || 0);
            const advancePayment = parseCurrency(customer.debt_on_customer || 0);
            
            let balanceClass, balanceIcon, balanceText, balanceAmount;
            
            // Determine card appearance based on balance and advance payment
            if (balance > 0) {
                // Customer owes us money
                balanceClass = 'balance-positive';
                balanceIcon = 'fa-arrow-up';
                balanceText = 'قەرزی کڕیار لە ئێمە';
                balanceAmount = balance;
            } else if (balance < 0) {
                // We owe customer money
                balanceClass = 'balance-negative';
                balanceIcon = 'fa-arrow-down';
                balanceText = 'قەرزی ئێمە لە کڕیار';
                balanceAmount = Math.abs(balance);
            } else if (advancePayment > 0) {
                // Customer has advance payment
                balanceClass = 'balance-positive';
                balanceIcon = 'fa-wallet';
                balanceText = 'پارەی پێشەکی';
                balanceAmount = advancePayment;
            } else {
                // No balance
                balanceClass = 'balance-zero';
                balanceIcon = 'fa-equals';
                balanceText = 'هیچ قەرزێک نییە';
                balanceAmount = 0;
            }
            
            const card = `
                <div class="customer-card" data-id="${customer.id}">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-user"></i>
                            <span>${customer.name || ''}</span>
                        </div>
                        <div class="contact-info">
                            <i class="fas fa-phone"></i>
                            <span>${customer.phone1 || ''}</span>
                            ${customer.phone2 ? `<span class="ms-2">${customer.phone2}</span>` : ''}
                        </div>
                        ${customer.guarantor_name ? `
                        <div class="contact-info mt-1">
                            <i class="fas fa-user-shield"></i>
                            <span>${customer.guarantor_name}</span>
                            ${customer.guarantor_phone ? `
                            <span class="ms-2">
                                <i class="fas fa-phone-square"></i>
                                ${customer.guarantor_phone}
                            </span>` : ''}
                        </div>` : ''}
                    </div>
                    <div class="card-body">
                        <div class="balance-section ${balanceClass}">
                            <div class="balance-amount">
                                <i class="fas ${balanceIcon}"></i>
                                <span>${formatCurrency(balanceAmount)}</span>
                            </div>
                            <div class="balance-label">
                                ${balanceText}
                            </div>
                            ${advancePayment > 0 && balance !== 0 ? `
                            <div class="advance-payment mt-2">
                                <i class="fas fa-wallet"></i>
                                <span>پارەی پێشەکی: ${formatCurrency(advancePayment)}</span>
                            </div>` : ''}
                        </div>
                        <div class="card-actions">
                            <button type="button" class="btn btn-primary" onclick="openPaymentModal('${customer.id}')">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>زیادکردنی پارەدان</span>
                            </button>
                            <button type="button" class="btn btn-secondary view-history-btn" data-id="${customer.id}">
                                <i class="fas fa-history"></i>
                                <span>مێژووی پارەدان</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });
    }

    // Function to update total balances
    function updateTotalBalances(customers) {
        // Reset totals
        totalBalance = 0;
        totalTheyOweUs = 0;
        totalWeOweThem = 0;
        totalAdvancePayments = 0;

        customers.forEach(customer => {
            const balance = parseCurrency(customer.debit_on_business || 0);
            const advancePayment = parseCurrency(customer.debt_on_customer || 0);
            
            if (balance > 0) {
                totalTheyOweUs += balance;
            } else if (balance < 0) {
                totalWeOweThem += Math.abs(balance);
            }
            
            if (advancePayment > 0) {
                totalAdvancePayments += advancePayment;
            }
            
            totalBalance += balance;
        });

        // Update the display
        $('#totalBalance').text(formatCurrency(totalBalance));
        $('#totalTheyOweUs').text(formatCurrency(totalTheyOweUs));
        $('#totalWeOweThem').text(formatCurrency(totalWeOweThem));
        $('#totalAdvancePayments').text(formatCurrency(totalAdvancePayments));

        // Add animation class to show the update
        $('.balance-amount').addClass('animate');
        setTimeout(() => {
            $('.balance-amount').removeClass('animate');
        }, 500);
    }

    // Function to handle payment submission
    function handlePaymentSubmission(formData) {
        $.ajax({
            url: '../../api/customers/addPayment.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو',
                        text: 'پارەدان بە سەرکەوتوویی تۆمارکرا',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        $('#addPaymentModal').modal('hide');
                        loadCustomerData(); // Reload data
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'کێشەیەک هەیە لە تۆمارکردنی پارەدان',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'کێشەیەک هەیە لە پەیوەندیکردن بە سێرڤەرەوە',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }

    // Initialize page
    loadCustomerData();

    // Event Handlers
    $('#refreshBtn').click(function() {
        loadCustomerData();
    });

    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.customer-card').each(function() {
            const customerName = $(this).find('.card-title span').text().toLowerCase();
            const customerPhone = $(this).find('.contact-info span').text().toLowerCase();
            if (customerName.includes(searchTerm) || customerPhone.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('#sortSelect').change(function() {
        const sortValue = $(this).val();
        const container = $('#customerCardsContainer');
        const cards = container.find('.customer-card').get();

        cards.sort(function(a, b) {
            const aBalance = parseFloat($(a).find('.balance-amount span').text().replace(/[^\d.-]/g, ''));
            const bBalance = parseFloat($(b).find('.balance-amount span').text().replace(/[^\d.-]/g, ''));
            const aName = $(a).find('.card-title span').text();
            const bName = $(b).find('.card-title span').text();

            switch(sortValue) {
                case 'balance-high':
                    return bBalance - aBalance;
                case 'balance-low':
                    return aBalance - bBalance;
                case 'they-owe-us':
                    return bBalance - aBalance;
                case 'we-owe-them':
                    return aBalance - bBalance;
                default: // name
                    return aName.localeCompare(bName);
            }
        });

        container.empty().append(cards);
    });

    $('#resetFilterBtn').click(function() {
        $('#searchInput').val('');
        $('#sortSelect').val('name');
        loadCustomerData();
    });

    // Save payment button click handler
    $('#savePaymentBtn').click(function() {
        const form = $('#addPaymentForm')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = {
            customerId: $('#customerId').val(),
            amount: $('#paymentAmount').val(),
            direction: $('#paymentDirection').val(),
            notes: $('#paymentNote').val()
        };

        handlePaymentSubmission(formData);
    });
});

// Global functions
function openPaymentModal(customerId) {
    $.ajax({
        url: `../../api/customers/getCustomer.php?id=${customerId}`,
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const customer = response.data;
                $('#customerId').val(customer.id);
                $('#customerName').val(customer.name);
                $('#currentBalance').val(customer.debit_on_business);
                $('#currentAdvancePayment').val(customer.debt_on_customer);
                
                // Update payment direction options based on current balances
                const paymentDirectionSelect = $('#paymentDirection');
                paymentDirectionSelect.empty();
                
                // Always allow receiving payment from customer
                paymentDirectionSelect.append(`
                    <option value="from_customer">وەرگرتنی پارە لە کڕیار (پارەدان یان پێشەکی)</option>
                `);
                
                // If we have debt or customer has advance payment, show payment to customer option
                if (customer.debit_on_business < 0 || customer.debt_on_customer > 0) {
                    paymentDirectionSelect.append(`
                        <option value="to_customer">پارەدان بە کڕیار (کەمکردنەوەی قەرزی ئێمە یان گەڕاندنەوەی پێشەکی)</option>
                    `);
                }
                
                // Always allow balance adjustment
                paymentDirectionSelect.append(`
                    <option value="adjust_balance">ڕێکخستنی باڵانس (ڕاستکردنەوەی هەڵە)</option>
                `);
                
                $('#addPaymentModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'کێشەیەک هەیە لە هێنانی زانیارییەکانی کڕیار',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'کێشەیەک هەیە لە پەیوەندیکردن بە سێرڤەرەوە',
                confirmButtonText: 'باشە'
            });
        }
    });
}

function viewHistory(customerId) {
    window.location.href = `customer_detail.php?id=${customerId}`;
} 