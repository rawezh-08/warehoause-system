$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const supplierId = urlParams.get('id');
    
    // Table variables
    let transactionsData = [];
    let filteredTransactionsData = [];
    let currentPage = 1;
    let recordsPerPage = 10;
    let totalPages = 1;
    
    // Initialize table
    initializeTransactionsTable();
    
    // Load initial data
    loadSupplierDetails();
    loadSupplierTransactions();
    
    // Refresh button
    $('#refreshBtn').on('click', function() {
        loadSupplierDetails();
        loadSupplierTransactions();
    });
    
    // Filter button
    $('#filterBtn').on('click', function() {
        loadSupplierTransactions();
    });

    // Pagination controls
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        const action = $(this).data('action');
        
        if (action === 'prev' && currentPage > 1) {
            currentPage--;
        } else if (action === 'next' && currentPage < totalPages) {
            currentPage++;
        } else if (action === 'page') {
            const page = parseInt($(this).text());
            if (!isNaN(page)) {
                currentPage = page;
            }
        }
        
        renderTransactionsTable();
    });
    
    function initializeTransactionsTable() {
        // Setup table structure
        const table = $('#transactionsTable');
        if (!table.find('tbody').length) {
            table.append('<tbody></tbody>');
        }
        if (!table.find('tfoot').length) {
            table.append(`
                <tfoot>
                    <tr>
                        <td colspan="7">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="pagination-info"></div>
                                <ul class="pagination mb-0"></ul>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            `);
        }
    }
    
    function renderTransactionsTable() {
        const tableBody = $('#transactionsTable tbody');
        tableBody.empty();
        
        // Calculate pagination
        updatePaginationInfo();
        
        // Get current page data
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = Math.min(startIndex + recordsPerPage, filteredTransactionsData.length);
        const currentPageData = filteredTransactionsData.slice(startIndex, endIndex);
        
        // No results message
        if (currentPageData.length === 0) {
            tableBody.append('<tr><td colspan="7" class="text-center">هیچ مامەڵەیەک نەدۆزرایەوە</td></tr>');
        } else {
            // Add rows to table
            currentPageData.forEach((transaction, index) => {
                const actualIndex = startIndex + index + 1;
                
                // Format date
                const date = new Date(transaction.created_at);
                const formattedDate = date.toLocaleDateString('ku-IQ') + ' ' + 
                                    date.toLocaleTimeString('ku-IQ', { hour: '2-digit', minute: '2-digit' });
                
                tableBody.append(`
                    <tr data-id="${transaction.id}">
                        <td>${actualIndex}</td>
                        <td>${formattedDate}</td>
                        <td><span class="badge rounded-pill ${getBadgeClass(transaction.transaction_type)}">${getTransactionTypeText(transaction.transaction_type)}</span></td>
                        <td><span class="${getAmountClass(transaction.amount)}">${formatCurrency(transaction.amount)} دینار</span></td>
                        <td><span class="badge rounded-pill ${getEffectBadgeClass(transaction.effect_on_balance)}">${getEffectText(transaction.effect_on_balance)}</span></td>
                        <td>${transaction.notes || '-'}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-info rounded-circle view-transaction-btn" 
                                        title="بینین" data-id="${transaction.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-circle delete-transaction-btn" 
                                        title="سڕینەوە" data-id="${transaction.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
            });
        }
        
        // Update pagination controls
        updatePaginationControls();
    }
    
    function updatePaginationInfo() {
        totalPages = Math.ceil(filteredTransactionsData.length / recordsPerPage);
        const startIndex = (currentPage - 1) * recordsPerPage + 1;
        const endIndex = Math.min(startIndex + recordsPerPage - 1, filteredTransactionsData.length);
        
        $('.pagination-info').text(
            `پیشاندانی ${startIndex} تا ${endIndex} لە کۆی ${filteredTransactionsData.length} مامەڵە`
        );
    }
    
    function updatePaginationControls() {
        const pagination = $('.pagination');
        pagination.empty();
        
        // Previous button
        pagination.append(`
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-action="prev">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `);
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            pagination.append(`
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" data-action="page">${i}</a>
                </li>
            `);
        }
        
        // Next button
        pagination.append(`
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-action="next">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `);
    }
    
    if (!supplierId) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'دابینکەر نەدۆزرایەوە',
            confirmButtonText: 'باشە'
        }).then(() => {
            window.location.href = 'bank.php';
        });
        return;
    }
    
    // Load supplier details
    loadSupplierDetails();
    
    // Load supplier transactions
    loadSupplierTransactions();
    
    // Add payment button
    $('#add-payment-btn').on('click', function() {
        const supplierName = $('#supplier-name-display span').text();
        const netBalanceText = $('#net-balance span').text();
        const debtOnMyself = $('#debt-on-myself span').text();
        const debtOnSupplier = $('#debt-on-supplier span').text();
        
        // Convert net balance text to number
        const netBalanceValue = parseFloat(netBalanceText.replace(/[^\d.-]/g, ''));
        
        // Populate modal fields
        $('#supplierId').val(supplierId);
        $('#supplierName').val(supplierName);
        
        // Format balance display with appropriate text
        if (netBalanceValue < 0) {
            // We owe them money
            $('#currentBalance').val(formatCurrency(Math.abs(netBalanceValue)) + ' - پارەمان لای ئەوانە');
        } else if (netBalanceValue > 0) {
            // They owe us money
            $('#currentBalance').val(formatCurrency(netBalanceValue) + ' - پارەیان لای ئێمەیە');
        } else {
            // Zero balance
            $('#currentBalance').val('0 دینار - هیچ پارەیەک نییە');
        }
        
        // Call updatePaymentDirectionOptions for proper setup of the select
        updatePaymentDirectionOptions(
            parseFloat(debtOnMyself.replace(/[^\d.-]/g, '')),
            parseFloat(debtOnSupplier.replace(/[^\d.-]/g, ''))
        );
        
        // Open modal
        $('#addPaymentModal').modal('show');
    });
    
    // Update payment direction options based on balance situation
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
        
        // Send AJAX request to process the payment
        $.ajax({
            url: apiEndpoint,
            type: 'POST',
            data: paymentData,
            success: function(response) {
                if (response.success) {
                    $('#addPaymentModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو',
                        text: 'پارەدان بە سەرکەوتوویی تۆمارکرا',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Reload data
                        loadSupplierDetails();
                        loadSupplierTransactions();
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
    
    // Function to load supplier details
    function loadSupplierDetails() {
        $.ajax({
            url: '../../api/get_supplier_details.php',
            type: 'GET',
            data: { 
                supplier_id: supplierId 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const supplier = response.supplier;
                    
                    // Update page title
                    $('#supplier-name').text(supplier.name);
                    $('#supplier-title').text('زانیاری دابینکەر: ' + supplier.name);
                    document.title = 'زانیاری دابینکەر: ' + supplier.name;
                    
                    // Update supplier information
                    $('#supplier-name-display span').text(supplier.name);
                    $('#supplier-phone1 span').text(supplier.phone1 || '-');
                    $('#supplier-phone2 span').text(supplier.phone2 || '-');
                    $('#supplier-notes span').text(supplier.notes || '-');
                    
                    // Calculate balances
                    const debtOnMyself = parseFloat(supplier.debt_on_myself) || 0;
                    const debtOnSupplier = parseFloat(supplier.debt_on_supplier) || 0;
                    const netBalance = debtOnSupplier - debtOnMyself;
                    
                    // Update balance information
                    $('#debt-on-myself span').text(formatCurrency(debtOnMyself));
                    $('#debt-on-supplier span').text(formatCurrency(debtOnSupplier));
                    
                    // Set net balance with appropriate class
                    const $netBalance = $('#net-balance span');
                    $netBalance.text(formatCurrency(netBalance));
                    $netBalance.removeClass('positive-balance negative-balance');
                    
                    if (netBalance > 0) {
                        $netBalance.addClass('positive-balance');
                    } else if (netBalance < 0) {
                        $netBalance.addClass('negative-balance');
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا لە هێنانی زانیاریەکانی دابینکەر',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        window.location.href = 'bank.php';
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    window.location.href = 'bank.php';
                });
            }
        });
    }
    
    // Function to load supplier transactions
    function loadSupplierTransactions() {
        // Get filter values
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const transactionType = $('#transactionType').val();
        
        // Check if date range is valid
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'بەرواری کۆتایی دەبێت دوای بەرواری سەرەتا بێت',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        $.ajax({
            url: '../../api/get_supplier_transactions.php',
            type: 'GET',
            data: { 
                supplier_id: supplierId,
                start_date: startDate,
                end_date: endDate,
                transaction_type: transactionType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update data arrays
                    transactionsData = response.transactions;
                    filteredTransactionsData = [...transactionsData];
                    
                    // Reset to first page
                    currentPage = 1;
                    
                    // Render table
                    renderTransactionsTable();
                    
                    // Show message if no transactions
                    if (response.transactions.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'زانیاری',
                            text: 'هیچ مامەڵەیەک نەدۆزرایەوە بۆ ئەم فلتەرە',
                            confirmButtonText: 'باشە'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا لە هێنانی مامەڵەکانی دابینکەر',
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
    }
    
    // Helper function to get transaction type text in Kurdish
    function getTransactionTypeText(type) {
        switch(type) {
            case 'purchase':
                return 'کڕین';
            case 'payment':
                return 'پارەدان بە فرۆشیار';
            case 'return':
                return 'گەڕاندنەوە بۆ فرۆشیار';
            case 'supplier_payment':
                return 'وەرگرتنی پارە لە فرۆشیار';
            case 'supplier_return':
                return 'گەڕاندنەوە لەلایەن فرۆشیار';
            case 'manual_adjustment':
                return 'دەستکاری دەستی';
            default:
                return type;
        }
    }
    
    // Helper function to get effect text
    function getEffectText(effect) {
        switch(effect) {
            case 'increase_debt_on_myself':
                return '<span class="badge bg-danger">زیادبوونی قەرزی ئێمە</span>';
            case 'decrease_debt_on_myself':
                return '<span class="badge bg-secondary">کەمبوونەوەی قەرزی ئێمە</span>';
            case 'increase_debt_on_supplier':
                return '<span class="badge bg-success">زیادبوونی قەرزی دابینکەر</span>';
            case 'decrease_debt_on_supplier':
                return '<span class="badge bg-secondary">کەمبوونەوەی قەرزی دابینکەر</span>';
            default:
                return effect;
        }
    }
    
    // Helper function to format currency
    function formatCurrency(amount) {
        // Convert to number in case it's a string
        amount = parseFloat(amount) || 0;
        
        // Format with comma separator
        return amount.toLocaleString() + ' دینار';
    }

    // Add these helper functions if they don't exist
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
}); 