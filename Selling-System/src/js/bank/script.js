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
        $('#currentBalance').val(formatCurrency(netBalance));
        
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
            apiEndpoint = '../../api/business_pay_supplier.php';
        } else if (paymentDirection === 'from_supplier') {
            apiEndpoint = '../../api/handle_supplier_payment.php';
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
                        successMessage = 'پارەدان بۆ فرۆشیار بە سەرکەوتوویی تۆمارکرا';
                    } else if (paymentDirection === 'from_supplier') {
                        successMessage = 'وەرگرتنی پارە لە فرۆشیار بە سەرکەوتوویی تۆمارکرا';
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
    // Show loading spinner
    $('#supplierCardsContainer').html('<div class="text-center my-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        url: '../../api/get_suppliers_with_balance.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('API response:', response); // Debug log
            
            // Handle both response formats (status or success)
            if (response.status === 'success' || response.success === true) {
                const suppliers = response.suppliers;
                let totalBalance = 0;
                let totalTheyOweUs = 0;
                let totalWeOweThem = 0;
                
                // Clear existing cards
                $('#supplierCardsContainer').empty();
                
                // Process each supplier
                suppliers.forEach(supplier => {
                    const netBalance = supplier.debt_on_supplier - supplier.debt_on_myself;
                    
                    // Update totals
                    if (netBalance > 0) {
                        totalTheyOweUs += netBalance;
                    } else if (netBalance < 0) {
                        totalWeOweThem += Math.abs(netBalance);
                    }
                    totalBalance += netBalance;
                    
                    // Create card
                    const card = createSupplierCard(supplier, netBalance);
                    $('#supplierCardsContainer').append(card);
                });
                
                // Update summary cards
                $('#totalBalance').text(formatCurrency(totalBalance));
                $('#totalTheyOweUs').text(formatCurrency(totalTheyOweUs));
                $('#totalWeOweThem').text(formatCurrency(totalWeOweThem));
                
                // Update balance description
                let balanceDescription = '';
                if (totalBalance > 0) {
                    balanceDescription = 'کۆی قەرزی فرۆشیارەکان لە ئێمە';
                } else if (totalBalance < 0) {
                    balanceDescription = 'کۆی قەرزی ئێمە لە فرۆشیارەکان';
                } else {
                    balanceDescription = 'باڵانسەکان هاوسەنگن';
                }
                $('#balanceDescription').text(balanceDescription);
                
                // Populate the supplier filter dropdown
                populateSupplierFilter(suppliers);
                
                // Apply any existing filters
                filterSuppliers();
                
                // Apply the current sort
                const currentSort = $('#sortSelect').val();
                sortSuppliers(currentSort);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.message || 'هەڵەیەک ڕوویدا لە کاتی بارکردنی زانیاریەکان',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading data:', error); // Debug log
            console.error('XHR Status:', xhr.status); // Debug log
            console.error('XHR Response:', xhr.responseText); // Debug log
            
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'پەیوەندی بە سێرڤەرەوە شکستی هێنا',
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

function createSupplierCard(supplier, netBalance) {
    const cardClass = netBalance > 0 ? 'positive' : (netBalance < 0 ? 'negative' : 'zero');
    const balanceText = formatCurrency(Math.abs(netBalance));
    const balanceType = netBalance > 0 ? 'قەرزی ئەوان لە ئێمە' : (netBalance < 0 ? 'قەرزی ئێمە لە ئەوان' : 'باڵانس هاوسەنگە');
    
    // Ensure numeric types for data attributes
    const safeId = parseInt(supplier.id) || 0;
    const safeDebtOnMyself = parseFloat(supplier.debt_on_myself) || 0;
    const safeDebtOnSupplier = parseFloat(supplier.debt_on_supplier) || 0;
    
    // Log debug information
    console.log('Creating card for supplier:', {
        id: safeId,
        name: supplier.name,
        debt_on_myself: safeDebtOnMyself,
        debt_on_supplier: safeDebtOnSupplier,
        netBalance: netBalance
    });
    
    return `
        <div class="card supplier-card ${cardClass}" data-id="${safeId}" data-balance="${netBalance}">
                    <div class="card-header">
                        <h5 class="card-title">${supplier.name}</h5>
                <span class="badge ${cardClass === 'positive' ? 'bg-success' : (cardClass === 'negative' ? 'bg-danger' : 'bg-secondary')}">${balanceType}</span>
                    </div>
                    <div class="card-body">
                <div class="card-text supplier-phone">
                            <i class="fas fa-phone card-icons"></i>
                    ${supplier.phone1 || 'بێ ژمارە'}
                        </div>
                <div class="debt-info mt-3">
                    <p><strong>قەرزی ئەوان لە ئێمە:</strong> <span class="positive-balance">${formatCurrency(safeDebtOnSupplier)}</span></p>
                    <p><strong>قەرزی ئێمە لە ئەوان:</strong> <span class="negative-balance">${formatCurrency(safeDebtOnMyself)}</span></p>
                    <p><strong>باڵانسی کۆتایی:</strong> <span class="card-balance ${cardClass}">${balanceText}</span></p>
                        </div>
                        <div class="card-actions">
                    <button class="btn btn-primary add-payment-btn" 
                            data-id="${safeId}"
                                data-name="${supplier.name}" 
                            data-balance="${netBalance}"
                            data-debt-on-myself="${safeDebtOnMyself}"
                            data-debt-on-supplier="${safeDebtOnSupplier}">
                        <i class="fas fa-money-bill-wave"></i> پارەدان
                            </button>
                    <button class="btn btn-info view-history-btn" data-id="${safeId}">
                                <i class="fas fa-history"></i> مێژوو
                            </button>
                        </div>
                    </div>
                </div>
            `;
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