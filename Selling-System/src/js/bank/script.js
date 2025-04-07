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
        const currentBalance = $(this).data('balance');
        
        // Populate modal fields
        $('#supplierId').val(supplierId);
        $('#supplierName').val(supplierName);
        $('#currentBalance').val(formatCurrency(currentBalance));
        
        // Set default payment direction based on balance
        if (currentBalance < 0) {
            // We owe them money, default to paying them
            $('#paymentDirection').val('to_supplier');
        } else {
            // They owe us money, default to receiving payment
            $('#paymentDirection').val('from_supplier');
        }
        
        // Open modal
        $('#addPaymentModal').modal('show');
    });
    
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
        
        // Send AJAX request to process the payment
        $.ajax({
            url: 'api/process_supplier_payment.php',
            type: 'POST',
            data: {
                supplier_id: supplierId,
                amount: paymentAmount,
                direction: paymentDirection,
                notes: paymentNote
            },
            success: function(response) {
                if (response.success) {
                    $('#addPaymentModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو',
                        text: 'پارەدان بە سەرکەوتوویی تۆمارکرا',
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
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
    
    // Function to load supplier balances
    function loadSupplierBalances() {
        // Sample data instead of AJAX call
        const sampleData = {
            success: true,
            suppliers: [
                {
                    id: 1,
                    name: "کۆمپانیای مەحمود",
                    phone1: "0750 123 4567",
                    phone2: "0770 123 4567",
                    debt_on_myself: -500000, // We owe them
                    notes: "کۆمپانیای گەورەی هێنانی کەلوپەل"
                },
                {
                    id: 2,
                    name: "فرۆشگای ئەحمەد",
                    phone1: "0751 234 5678",
                    phone2: "",
                    debt_on_myself: 750000, // They owe us (positive when inverted)
                    notes: "وەکیلی هێنانی کەلوپەلی ناوماڵ"
                },
                {
                    id: 3,
                    name: "کۆمپانیای هەولێر",
                    phone1: "0772 345 6789",
                    phone2: "0773 345 6789",
                    debt_on_myself: -1200000, // We owe them
                    notes: ""
                },
                {
                    id: 4,
                    name: "بازرگانی مەهدی",
                    phone1: "0773 456 7890",
                    phone2: "",
                    debt_on_myself: 0, // No debt
                    notes: "فرۆشیاری کەلوپەلی کارەبایی"
                },
                {
                    id: 5,
                    name: "فرۆشگای فەریق",
                    phone1: "0774 567 8901",
                    phone2: "0775 567 8901",
                    debt_on_myself: 300000, // They owe us
                    notes: "فرۆشیاری کەلوپەلی خواردەمەنی"
                }
            ]
        };
            
        // Clear table body and cards container
        $('#balanceTableBody').empty();
        $('#supplierCardsContainer').empty();
        
        let totalPositiveBalance = 0;
        let totalNegativeBalance = 0;
        let totalBalance = 0;
        
        // Add supplier rows and cards
        sampleData.suppliers.forEach(function(supplier, index) {
            const balance = parseFloat(supplier.debt_on_myself) * -1; // Invert since debt_on_myself means we owe them
            totalBalance += balance;
            
            if (balance > 0) {
                totalPositiveBalance += balance;
            } else if (balance < 0) {
                totalNegativeBalance += Math.abs(balance);
            }
            
            // Table row classes and text
            const rowClass = balance < 0 ? 'table-danger' : (balance > 0 ? 'table-success' : '');
            const balanceStatusText = balance < 0 ? 'ئێمە قەرزدارین' : (balance > 0 ? 'ئەوان قەرزدارن' : 'یەکسانە');
            const balanceClass = balance < 0 ? 'negative-balance' : (balance > 0 ? 'positive-balance' : '');
            
            // Create table row
            const row = `
                <tr class="${rowClass}">
                    <td>${index + 1}</td>
                    <td>${supplier.name}</td>
                    <td>${supplier.phone1}</td>
                    <td class="${balanceClass}">${formatCurrency(balance)}</td>
                    <td>${balanceStatusText}</td>
                    <td>
                        <button class="btn btn-primary btn-sm add-payment-btn" 
                            data-id="${supplier.id}" 
                            data-name="${supplier.name}" 
                            data-balance="${balance}">
                            <i class="fas fa-money-bill"></i> پارەدان
                        </button>
                        <button class="btn btn-info btn-sm view-history-btn" data-id="${supplier.id}">
                            <i class="fas fa-history"></i> مێژوو
                        </button>
                    </td>
                </tr>
            `;
            
            $('#balanceTableBody').append(row);
            
            // Card status classes
            const cardClass = balance < 0 ? 'negative' : (balance > 0 ? 'positive' : 'zero');
            const badgeClass = balance < 0 ? 'bg-danger' : (balance > 0 ? 'bg-success' : 'bg-secondary');
            const badgeText = balance < 0 ? 'ئێمە قەرزدارین' : (balance > 0 ? 'ئەوان قەرزدارن' : 'یەکسانە');
            
            // Create card for supplier
            const card = `
                <div class="supplier-card ${cardClass}" data-id="${supplier.id}" data-balance="${balance}">
                    <div class="card-header">
                        <h5 class="card-title">${supplier.name}</h5>
                        <span class="badge ${badgeClass} card-badge">${badgeText}</span>
                    </div>
                    <div class="card-body">
                        <div class="card-text">
                            <i class="fas fa-phone card-icons"></i>
                            <span>${supplier.phone1}</span>
                        </div>
                        ${supplier.phone2 ? `
                        <div class="card-text">
                            <i class="fas fa-phone-alt card-icons"></i>
                            <span>${supplier.phone2}</span>
                        </div>` : ''}
                        <div class="card-balance ${balanceClass}">
                            ${formatCurrency(balance)}
                        </div>
                        ${supplier.notes ? `
                        <div class="card-text">
                            <i class="fas fa-sticky-note card-icons"></i>
                            <span>${supplier.notes}</span>
                        </div>` : ''}
                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm add-payment-btn" 
                                data-id="${supplier.id}" 
                                data-name="${supplier.name}" 
                                data-balance="${balance}">
                                <i class="fas fa-money-bill"></i> پارەدان
                            </button>
                            <button class="btn btn-info btn-sm view-history-btn" data-id="${supplier.id}">
                                <i class="fas fa-history"></i> مێژوو
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#supplierCardsContainer').append(card);
        });
        
        // Update summary cards
        $('#totalBalance').text(formatCurrency(totalBalance));
        $('#totalBalance').removeClass('positive-balance negative-balance');
        if (totalBalance > 0) {
            $('#totalBalance').addClass('positive-balance');
            $('#balanceDescription').text('ئەوان قەرزداری ئێمەن');
        } else if (totalBalance < 0) {
            $('#totalBalance').addClass('negative-balance');
            $('#balanceDescription').text('ئێمە قەرزداری ئەوانین');
        } else {
            $('#balanceDescription').text('باڵانس یەکسانە');
        }
        
        $('#totalTheyOweUs').text(formatCurrency(totalPositiveBalance));
        $('#totalWeOweThem').text(formatCurrency(totalNegativeBalance));
        
        // Refresh DataTable
        balanceTable.clear().rows.add($('#balanceTableBody tr')).draw();
    }
    
    // Helper function to format currency
    function formatCurrency(amount) {
        // Format with commas
        return parseFloat(amount).toLocaleString('en-US') + ' دینار';
    }
});