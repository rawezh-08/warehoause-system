$(document).ready(function() {
    // Toggle view buttons
    $('#cardViewBtn').on('click', function() {
        $(this).addClass('active');
        $('#tableViewBtn').removeClass('active');
        $('#supplierCardsView').show();
        $('#supplierTableView').hide();
    });
    
    $('#tableViewBtn').on('click', function() {
        $(this).addClass('active');
        $('#cardViewBtn').removeClass('active');
        $('#supplierTableView').show();
        $('#supplierCardsView').hide();
    });
    
    // Modal accessibility handlers
    $('#addPaymentModal').on('shown.bs.modal', function() {
        // Set focus to the first input when modal opens
        $('#paymentAmount').trigger('focus');
    });
    
    $('#addPaymentModal').on('hidden.bs.modal', function() {
        // Clear form values when modal is closed
        $('#addPaymentForm')[0].reset();
        // Return focus to the body to avoid aria-hidden issues
        $('body').attr('tabindex', '-1').focus().removeAttr('tabindex');
    });
    
    // Load suppliers and their balances
    loadSupplierBalances();
    
    // Initialize DataTable
    const balanceTable = $('#balanceTable').DataTable({
        responsive: true,
        language: {
            search: "گەڕان:",
            lengthMenu: "پیشاندانی _MENU_ تۆمار",
            info: "پیشاندانی _START_ تا _END_ لە کۆی _TOTAL_ تۆمار",
            infoEmpty: "هیچ تۆمارێک نییە",
            infoFiltered: "(پاڵێوراو لە کۆی _MAX_ تۆمار)",
            zeroRecords: "هیچ تۆمارێک نەدۆزرایەوە",
            paginate: {
                first: "یەکەم",
                last: "کۆتایی",
                next: "دواتر",
                previous: "پێشتر"
            }
        }
    });
    
    // Search functionality
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#searchInput').val();
        
        // For table view
        balanceTable.search(searchTerm).draw();
        
        // For card view, filter cards
        filterCards(searchTerm);
    });
    
    // Filter cards by search term
    function filterCards(searchTerm) {
        if (!searchTerm) {
            $('.supplier-card').show();
            return;
        }
        
        searchTerm = searchTerm.toLowerCase();
        
        $('.supplier-card').each(function() {
            const cardContent = $(this).text().toLowerCase();
            if (cardContent.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    // Sort functionality
    $('#sortSelect').on('change', function() {
        const sortValue = $(this).val();
        
        // For table view
        balanceTable.order.neutral();
        
        // Apply sorting based on selection
        switch(sortValue) {
            case 'name':
                balanceTable.order([1, 'asc']).draw();
                sortCards('name', 'asc');
                break;
            case 'balance-high':
                balanceTable.order([3, 'desc']).draw();
                sortCards('balance', 'desc');
                break;
            case 'balance-low':
                balanceTable.order([3, 'asc']).draw();
                sortCards('balance', 'asc');
                break;
            case 'they-owe-us':
                // Custom filtering for positive balances
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    const balance = parseFloat(data[3].replace(/[^\d.-]/g, ''));
                    return balance > 0;
                });
                balanceTable.draw();
                $.fn.dataTable.ext.search.pop(); // Remove this filter
                
                // For card view
                $('.supplier-card').hide();
                $('.supplier-card.positive').show();
                break;
            case 'we-owe-them':
                // Custom filtering for negative balances
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    const balance = parseFloat(data[3].replace(/[^\d.-]/g, ''));
                    return balance < 0;
                });
                balanceTable.draw();
                $.fn.dataTable.ext.search.pop(); // Remove this filter
                
                // For card view
                $('.supplier-card').hide();
                $('.supplier-card.negative').show();
                break;
        }
    });
    
    // Sort cards function
    function sortCards(property, order) {
        const $container = $('#supplierCardsContainer');
        const $cards = $container.children('.supplier-card').get();
        
        $cards.sort(function(a, b) {
            let valueA, valueB;
            
            if (property === 'name') {
                valueA = $(a).find('.card-title').text().trim();
                valueB = $(b).find('.card-title').text().trim();
                return order === 'asc' ? valueA.localeCompare(valueB) : valueB.localeCompare(valueA);
            } else if (property === 'balance') {
                valueA = parseFloat($(a).data('balance'));
                valueB = parseFloat($(b).data('balance'));
                return order === 'asc' ? valueA - valueB : valueB - valueA;
            }
            
            return 0;
        });
        
        $.each($cards, function(_, card) {
            $container.append(card);
        });
    }
    
    // Refresh button
    $('#refreshBtn').on('click', function() {
        loadSupplierBalances();
    });
    
    // Add payment modal handlers
    $(document).on('click', '.add-payment-btn', function() {
        const supplierId = $(this).data('id');
        const supplierName = $(this).data('name');
        const netBalance = $(this).data('balance');
        const debtOnMyself = $(this).data('debt-on-myself');
        const debtOnSupplier = $(this).data('debt-on-supplier');
        
        // Populate modal fields
        $('#supplierId').val(supplierId);
        $('#supplierName').val(supplierName);
        
        // Check if we owe them money (negative net balance)
        if (netBalance < 0) {
            // Display how much we owe them as a positive number with the right text
            $('#currentBalance').val(formatCurrency(Math.abs(netBalance)) + ' - پارەمان لای ئەوانە');
        } else if (netBalance > 0) {
            // Display how much they owe us as a positive number with the right text
            $('#currentBalance').val(formatCurrency(netBalance) + ' - پارەیان لای ئێمەیە');
        } else {
            // Zero balance
            $('#currentBalance').val('0 دینار - هیچ پارەیەک نییە');
        }
        
        // Update payment direction options based on the two-way balance
        updatePaymentDirectionOptions(debtOnMyself, debtOnSupplier);
        
        // Open modal
        $('#addPaymentModal').modal('show');
    });
    
    // Update payment direction options
    function updatePaymentDirectionOptions(debtOnMyself, debtOnSupplier) {
        const $paymentDirection = $('#paymentDirection');
        $paymentDirection.empty();
        
        // We always show both options, but we'll set the default based on the balance situation
        $paymentDirection.append(`
            <option value="to_supplier">پارەدان بۆ فرۆشیار (ئێمە دەدەین بەوان)</option>
            <option value="from_supplier">وەرگرتنی پارە لە فرۆشیار (ئەوان دەدەن بە ئێمە)</option>
            <option value="adjust_balance">ڕێکخستنی باڵانس (دەستکاری دەستی)</option>
        `);
        
        // Set default based on overall situation
        if (debtOnMyself > debtOnSupplier) {
            // We owe them more, default to paying them
            $paymentDirection.val('to_supplier');
        } else if (debtOnSupplier > debtOnMyself) {
            // They owe us more, default to receiving payment
            $paymentDirection.val('from_supplier');
        } else {
            // Balanced or both zero, default to manual adjustment
            $paymentDirection.val('adjust_balance');
        }
    }
    
    // Save payment button
    $('#savePaymentBtn').on('click', function() {
        const supplierId = $('#supplierId').val();
        const paymentAmount = $('#paymentAmount').val();
        const paymentDirection = $('#paymentDirection').val();
        const paymentNote = $('#paymentNote').val();
        
        if (!paymentAmount || paymentAmount <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'تکایە بڕی پارەدان داخڵ بکە',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        // Determine which API endpoint to call based on payment direction
        let apiEndpoint;
        let paymentData = {
            supplier_id: supplierId,
            amount: paymentAmount,
            notes: paymentNote
        };
        
        if (paymentDirection === 'to_supplier') {
            // When we pay supplier (reduces our debt to them)
            apiEndpoint = '../../api/pay_supplier.php';
        } else if (paymentDirection === 'from_supplier') {
            // When supplier pays us (reduces their debt to us)
            apiEndpoint = '../../api/supplier_pays_business.php';
        } else if (paymentDirection === 'adjust_balance') {
            apiEndpoint = '../../api/adjust_supplier_balance.php';
        }
        
        console.log('Sending payment request:', {
            endpoint: apiEndpoint,
            direction: paymentDirection,
            data: paymentData
        });
        
        // Send AJAX request to process the payment
        $.ajax({
            url: apiEndpoint,
            type: 'POST',
            data: JSON.stringify(paymentData),
            contentType: 'application/json',
            success: function(response) {
                console.log('Payment response:', response);
                
                if (response.success) {
                    // Show success message
                    let successMessage = '';
                    if (paymentDirection === 'to_supplier') {
                        successMessage = 'پارەدان بە دابینکەر بە سەرکەوتوویی تۆمارکرا';
                    } else if (paymentDirection === 'from_supplier') {
                        successMessage = 'وەرگرتنی پارە لە دابینکەر بە سەرکەوتوویی تۆمارکرا';
                    } else {
                        successMessage = 'باڵانس بە سەرکەوتوویی ڕێکخرایەوە';
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: successMessage,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Close modal
                        $('#addPaymentModal').modal('hide');
                        
                        // Reset form
                        $('#addPaymentForm')[0].reset();
                        
                        // Reload supplier balances
                        loadSupplierBalances();
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا لە کاتی پارەدان',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'پەیوەندی بە سێرڤەرەوە شکستی هێنا',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
    
    // Load supplier balances
    loadSupplierBalances();
    
    // Setup search with auto filter (no button needed)
    $('#searchInput').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        filterSuppliers();
    });
    
    // Setup supplier filter dropdown
    $('#supplierFilter').on('change', function() {
        filterSuppliers();
    });
    
    // Setup sort select
    $('#sortSelect').on('change', function() {
        sortSuppliers($(this).val());
    });
    
    // Reset all filters
    $('#resetFilterBtn').on('click', function() {
        $('#searchInput').val('');
        $('#supplierFilter').val('');
        $('#sortSelect').val('name');
        filterSuppliers();
        sortSuppliers('name');
    });
    
    // Refresh button click
    $('#refreshBtn').on('click', function() {
        loadSupplierBalances();
    });
});

// Filter suppliers based on search input and filter dropdown
function filterSuppliers() {
    const searchText = $('#searchInput').val().toLowerCase();
    const selectedSupplier = $('#supplierFilter').val();
    
    $('.supplier-card').each(function() {
        const supplierName = $(this).find('.card-title').text().toLowerCase();
        const supplierPhone = $(this).find('.supplier-phone').text().toLowerCase();
        const supplierId = $(this).data('id').toString();
        
        // Check if it matches the search text (name or phone)
        const matchesSearch = supplierName.includes(searchText) || 
                              supplierPhone.includes(searchText);
        
        // Check if it matches the selected supplier
        const matchesFilter = !selectedSupplier || supplierId === selectedSupplier;
        
        // Show/hide based on combined filters
        if (matchesSearch && matchesFilter) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Load supplier data and populate the filter dropdown
function loadSupplierBalances() {
    $.ajax({
        url: '../../api/get_suppliers.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const suppliers = response.data;
                let totalDebtOnMyself = 0;
                let totalDebtOnSupplier = 0;
                let totalInitialPayments = 0;
                
                // Clear existing cards
                $('#supplierCardsContainer').empty();
                
                suppliers.forEach(supplier => {
                    // Calculate net balance
                    const debtOnMyself = parseFloat(supplier.debt_on_myself) || 0;  // ئێمە قەرزداری ئەوانین
                    const debtOnSupplier = parseFloat(supplier.debt_on_supplier) || 0;  // ئەوان قەرزداری ئێمەن
                    
                    // Add to totals
                    totalDebtOnMyself += debtOnMyself;
                    totalDebtOnSupplier += debtOnSupplier;
                    
                    // Calculate initial payments (positive balances)
                    if (debtOnSupplier > debtOnMyself) {
                        totalInitialPayments += (debtOnSupplier - debtOnMyself);
                    }
                    
                    // Create and append supplier card
                    createSupplierCard(supplier, debtOnMyself, debtOnSupplier);
                });
                
                // Update summary cards
                $('#totalWeOweThem').text(formatCurrency(totalDebtOnMyself) + ' دینار');  // ئێمە قەرزداری ئەوانین
                $('#totalTheyOweUs').text(formatCurrency(totalDebtOnSupplier) + ' دینار');  // ئەوان قەرزداری ئێمەن
                $('#totalInitialPayments').text(formatCurrency(totalInitialPayments) + ' دینار');  // کۆی پارەی پێشەکی
                
                // Calculate and display net balance
                const netBalance = totalDebtOnSupplier - totalDebtOnMyself;  // ئەگەر موجەب بێت ئەوان قەرزدارن، ئەگەر سالب بێت ئێمە قەرزدارین
                $('#totalBalance').text(formatCurrency(Math.abs(netBalance)) + ' دینار');
                
                // Update balance description
                if (netBalance > 0) {
                    $('#balanceDescription').text('پارەیان لای ئێمەیە');
                    $('#totalBalance').removeClass('negative-balance').addClass('positive-balance');
                } else if (netBalance < 0) {
                    $('#balanceDescription').text('پارەمان لای ئەوانە');
                    $('#totalBalance').removeClass('positive-balance').addClass('negative-balance');
                } else {
                    $('#balanceDescription').text('هیچ پارەیەک نییە');
                    $('#totalBalance').removeClass('positive-balance negative-balance');
                }
                
                // Populate supplier filter
                populateSupplierFilter(suppliers);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading supplier balances:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک ڕوویدا لە کاتی بارکردنی زانیارییەکان',
                confirmButtonText: 'باشە'
            });
        }
    });
}

// Populate the supplier filter dropdown
function populateSupplierFilter(suppliers) {
    const $filterSelect = $('#supplierFilter');
    // Keep the first option (All Suppliers)
    $filterSelect.find('option:not(:first)').remove();
    
    // Sort suppliers by name for the dropdown
    suppliers.sort((a, b) => a.name.localeCompare(b.name));
    
    // Add each supplier to the dropdown
    suppliers.forEach(supplier => {
        $filterSelect.append(`<option value="${supplier.id}">${supplier.name}</option>`);
    });
}

function createSupplierCard(supplier, debtOnMyself, debtOnSupplier) {
    const netBalance = debtOnSupplier - debtOnMyself;
    let balanceClass = netBalance > 0 ? 'positive' : (netBalance < 0 ? 'negative' : 'zero');
    
    const card = `
        <div class="card supplier-card ${balanceClass}" data-id="${supplier.id}" 
             data-debt-on-myself="${debtOnMyself}" data-debt-on-supplier="${debtOnSupplier}">
            <div class="card-header">
                <h5 class="card-title">${supplier.name}</h5>
                <span class="card-badge ${balanceClass === 'positive' ? 'bg-success' : 
                    (balanceClass === 'negative' ? 'bg-danger' : 'bg-secondary')} text-white">
                    ${balanceClass === 'positive' ? 'پارەی پێشەکی' : 
                      (balanceClass === 'negative' ? 'قەرزی ئێمە لە دابینکەر' : 'هیچ پارەیەک نییە')}
                </span>
            </div>
            <div class="card-body">
                <div class="balance-section ${balanceClass === 'positive' ? 'balance-positive' : 
                    (balanceClass === 'negative' ? 'balance-negative' : 'balance-zero')}">
                    <div class="balance-amount">
                        <i class="fas ${balanceClass === 'positive' ? 'fa-arrow-down' : 
                            (balanceClass === 'negative' ? 'fa-arrow-up' : 'fa-balance-scale')}"></i>
                        <span>${formatCurrency(Math.abs(netBalance))}</span>
                    </div>
                    <div class="balance-label">
                        ${balanceClass === 'positive' ? 'پارەی پێشەکی' : 
                          (balanceClass === 'negative' ? 'قەرزی ئێمە لە دابینکەر' : 'هیچ پارەیەک نییە')}
                    </div>
                </div>
                <div class="card-actions">
                    <button class="btn btn-primary add-payment-btn" data-id="${supplier.id}" 
                            data-name="${supplier.name}" data-balance="${netBalance}"
                            data-debt-on-myself="${debtOnMyself}" 
                            data-debt-on-supplier="${debtOnSupplier}">
                        <i class="fas fa-money-bill-wave"></i> پارەدان
                    </button>
                    <button class="btn btn-info view-history-btn" data-id="${supplier.id}">
                        <i class="fas fa-history"></i> مێژوو
                    </button>
                </div>
            </div>
        </div>
    `;
    
    $('#supplierCardsContainer').append(card);
}

// Helper function to format currency
function formatCurrency(amount) {
    // Convert to number in case it's a string
    amount = parseFloat(amount) || 0;
    
    // Format with comma separator
    return amount.toLocaleString() + ' دینار';
}

// Sort suppliers based on selected sort option
function sortSuppliers(sortOption) {
    const $container = $('#supplierCardsContainer');
    const $cards = $container.children('.supplier-card').get();
    
    $cards.sort(function(a, b) {
        let valueA, valueB;
        
        if (sortOption === 'name') {
            valueA = $(a).find('.card-title').text().trim();
            valueB = $(b).find('.card-title').text().trim();
            return valueA.localeCompare(valueB);
        } else if (sortOption === 'balance-high') {
            valueA = parseFloat($(a).data('balance'));
            valueB = parseFloat($(b).data('balance'));
            return valueB - valueA;
        } else if (sortOption === 'balance-low') {
            valueA = parseFloat($(a).data('balance'));
            valueB = parseFloat($(b).data('balance'));
            return valueA - valueB;
        } else if (sortOption === 'they-owe-us') {
            valueA = parseFloat($(a).data('balance'));
            valueB = parseFloat($(b).data('balance'));
            return valueB - valueA;
        } else if (sortOption === 'we-owe-them') {
            valueA = parseFloat($(a).data('balance'));
            valueB = parseFloat($(b).data('balance'));
            return valueA - valueB;
        }
        
        return 0;
    });
    
    $.each($cards, function(_, card) {
        $container.append(card);
    });
}

// Add these helper functions
function getBadgeClass(transactionType) {
    switch(transactionType) {
        case 'purchase':
            return 'bg-primary';
        case 'payment':
            return 'bg-success';
        case 'return':
            return 'bg-warning text-dark';
        case 'supplier_payment':
            return 'bg-info';
        case 'supplier_return':
            return 'bg-secondary';
        case 'manual_adjustment':
            return 'bg-dark';
        default:
            return 'bg-secondary';
    }
}

function getAmountClass(amount) {
    return amount >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
}

function getEffectBadgeClass(effect) {
    switch(effect) {
        case 'increase_debt_on_myself':
            return 'bg-danger';
        case 'decrease_debt_on_myself':
            return 'bg-success';
        case 'increase_debt_on_supplier':
            return 'bg-primary';
        case 'decrease_debt_on_supplier':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}