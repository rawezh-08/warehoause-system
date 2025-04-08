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
            apiEndpoint = 'api/business_pay_supplier.php';
        } else if (paymentDirection === 'from_supplier') {
            apiEndpoint = 'api/handle_supplier_payment.php';
        } else if (paymentDirection === 'adjust_balance') {
            apiEndpoint = 'api/adjust_supplier_balance.php';
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
            data: paymentData,
            success: function(response) {
                console.log('Payment response:', response);
                
                if (response.success) {
                    $('#addPaymentModal').modal('hide');
                    
                    // Reset form
                    $('#addPaymentForm')[0].reset();
                    
                    let successMessage = '';
                    if (paymentDirection === 'to_supplier') {
                        successMessage = 'پارەدان بۆ فرۆشیار بە سەرکەوتوویی تۆمارکرا';
                    } else if (paymentDirection === 'from_supplier') {
                        successMessage = 'وەرگرتنی پارە لە فرۆشیار بە سەرکەوتوویی تۆمارکرا';
                    } else {
                        successMessage = 'ڕێکخستنی باڵانس بە سەرکەوتوویی تۆمارکرا';
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو',
                        text: successMessage,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Reload balances
                        loadSupplierBalances();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا لە کاتی پرۆسەکردنی پارەدان',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                let errorMsg = 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر';
                
                // Try to get more detailed error from response
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        errorMsg = response.message;
                    }
                } catch(e) {
                    // If parsing fails, use the raw response text if it exists
                    if (xhr.responseText) {
                        errorMsg += '<br><br>وردەکاری: ' + xhr.responseText;
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    html: errorMsg,
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
    
    // Function to load supplier balances
    function loadSupplierBalances() {
        // Clear existing content
        $('#supplierCardsContainer').empty();
        
        // Show loading indicator
        $('#supplierCardsContainer').html('<div class="text-center my-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        // Use AJAX to fetch real data from our API
        $.ajax({
            url: 'api/get_suppliers_with_balance.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Clear loading indicator
                    $('#supplierCardsContainer').empty();
                    
                    // Calculate total balances
                    let totalNetBalance = 0;
                    let totalTheyOweUs = 0;
                    let totalWeOweThem = 0;
                    
                    // Process each supplier
                    response.suppliers.forEach(function(supplier) {
                        // Calculate net balance
                        const debtOnMyself = parseFloat(supplier.debt_on_myself) || 0;
                        const debtOnSupplier = parseFloat(supplier.debt_on_supplier) || 0;
                        const netBalance = debtOnSupplier - debtOnMyself;
                        
                        console.log('Supplier:', supplier.name, 
                            'debt_on_myself:', debtOnMyself, 
                            'debt_on_supplier:', debtOnSupplier, 
                            'netBalance:', netBalance);
                        
                        // Add to totals
                        if (netBalance > 0) {
                            totalTheyOweUs += netBalance;
                        } else if (netBalance < 0) {
                            totalWeOweThem += Math.abs(netBalance);
                        }
                        
                        totalNetBalance += netBalance;
                        
                        // Determine card class based on balance
                        let cardClass = 'zero';
                        let balanceStatus = 'balanced';
                        let badgeClass = 'bg-secondary';
                        
                        if (netBalance > 0) {
                            cardClass = 'positive';
                            balanceStatus = 'they_owe_us';
                            badgeClass = 'bg-success';
                        } else if (netBalance < 0) {
                            cardClass = 'negative';
                            balanceStatus = 'we_owe_them';
                            badgeClass = 'bg-danger';
                        }
                        
                        // Create supplier card
                        const card = `
                            <div class="card supplier-card ${cardClass}" data-id="${supplier.id}" data-balance="${netBalance}" data-debt-on-myself="${debtOnMyself}" data-debt-on-supplier="${debtOnSupplier}">
                                <div class="card-header">
                                    <h5 class="card-title">${supplier.name}</h5>
                                    <span class="badge ${badgeClass} card-badge">
                                        ${balanceStatus === 'they_owe_us' ? 'قەرزداری ئێمەن' : 
                                          balanceStatus === 'we_owe_them' ? 'قەرزمان لایانە' : 'باڵانس سفرە'}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <i class="fas fa-phone card-icons"></i>
                                        ${supplier.phone1}
                                    </p>
                                    ${supplier.phone2 ? `
                                    <p class="card-text">
                                        <i class="fas fa-phone card-icons"></i>
                                        ${supplier.phone2}
                                    </p>
                                    ` : ''}
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>قەرزی ئێمە لایان:</span>
                                        <span class="negative-balance">${formatCurrency(debtOnMyself)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>قەرزی ئەوان لە ئێمە:</span>
                                        <span class="positive-balance">${formatCurrency(debtOnSupplier)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span><strong>باڵانسی کۆتایی:</strong></span>
                                        <span class="card-balance ${netBalance > 0 ? 'positive-balance' : netBalance < 0 ? 'negative-balance' : ''}">
                                            ${formatCurrency(netBalance)}
                                        </span>
                                    </div>
                                    <div class="card-actions">
                                        <button class="btn btn-primary add-payment-btn" data-id="${supplier.id}" data-name="${supplier.name}" data-balance="${netBalance}" data-debt-on-myself="${debtOnMyself}" data-debt-on-supplier="${debtOnSupplier}">
                                            <i class="fas fa-money-bill-wave"></i> پارەدان
                                        </button>
                                        <button class="btn btn-outline-secondary view-history-btn" data-id="${supplier.id}">
                                            <i class="fas fa-history"></i> مێژوو
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('#supplierCardsContainer').append(card);
                    });
                    
                    // Update summary cards
                    $('#totalBalance').text(formatCurrency(totalNetBalance));
                    $('#totalTheyOweUs').text(formatCurrency(totalTheyOweUs));
                    $('#totalWeOweThem').text(formatCurrency(totalWeOweThem));
                    
                    // Set balance description
                    if (totalNetBalance > 0) {
                        $('#balanceDescription').text('کۆی گشتی فرۆشیارەکان قەرزداری ئێمەن');
                        $('#balanceDescription').removeClass('negative-balance').addClass('positive-balance');
                        $('#totalBalance').removeClass('negative-balance').addClass('positive-balance');
                    } else if (totalNetBalance < 0) {
                        $('#balanceDescription').text('کۆی گشتی ئێمە قەرزداری فرۆشیارەکانین');
                        $('#balanceDescription').removeClass('positive-balance').addClass('negative-balance');
                        $('#totalBalance').removeClass('positive-balance').addClass('negative-balance');
                    } else {
                        $('#balanceDescription').text('قەرزەکان هاوسەنگن');
                        $('#balanceDescription').removeClass('positive-balance negative-balance');
                        $('#totalBalance').removeClass('positive-balance negative-balance');
                    }
                    
                } else {
                    // Show error
                    $('#supplierCardsContainer').html('<div class="alert alert-danger">هەڵەیەک ڕوویدا لە هێنانی زانیاریەکان</div>');
                }
            },
            error: function() {
                // Show error
                $('#supplierCardsContainer').html('<div class="alert alert-danger">هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر</div>');
            }
        });
    }
    
    // Helper function to format currency
    function formatCurrency(amount) {
        // Convert to number in case it's a string
        amount = parseFloat(amount) || 0;
        
        // Format with comma separator
        return amount.toLocaleString() + ' دینار';
    }
});