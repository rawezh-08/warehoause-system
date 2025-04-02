$(document).ready(function() {
    // Load components (sidebar, navbar)
    loadComponents();
    
    // Tab related variables
    let activeTab = "employee-payment";
    
    // Initialize all tables
    initializeEmployeeHistoryTable();
    initializeShippingHistoryTable();
    initializeWithdrawalHistoryTable();
    
    // Set default dates to one month range
    setDefaultDateRanges();
    
    // Setup auto filter handlers
    setupAutoFilterHandlers();
    
    // Setup tab click handlers
    setupTabHandlers();
    
    // Setup action button handlers
    setupActionButtons();
});

/**
 * Safe toast function to show notifications
 */
function showToast(message, type) {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Configure toast based on type
        const config = {
            icon: type,
            title: message,
            rtl: true,
            customClass: {
                container: 'toast-container-rtl'
            }
        };

        // Show the toast
        Toast.fire(config);
    } else {
        console.log(type + ': ' + message);
    }
}

/**
 * Load the navbar and sidebar components
 */
function loadComponents() {
    // This function is located in the include-components.js file
    try {
        $("#navbar-container").load("components/navbar.php");
        $("#sidebar-container").load("components/sidebar.php", function() {
            // Activate the current menu item
            activateSidebarItem();
        });
    } catch (error) {
        console.error("Error loading components:", error);
    }
}

/**
 * Activate the current sidebar menu item
 */
function activateSidebarItem() {
    // Find and activate the expenses menu item
    const expensesMenuItem = $('[href="#expensesSubmenu"]');
    expensesMenuItem.parent().addClass('active');
    expensesMenuItem.attr('aria-expanded', 'true');
    
    // Show the submenu
    const submenu = $('#expensesSubmenu');
    submenu.addClass('show');
    
    // Highlight the expenses history menu item (assuming it exists)
    const expensesHistoryItem = submenu.find('a[href="expensesHistory.php"]');
    expensesHistoryItem.parent().addClass('active');
}

/**
 * Set default date ranges to last 30 days for all filters
 */
function setDefaultDateRanges() {
    const today = new Date();
    const lastMonth = new Date();
    lastMonth.setDate(today.getDate() - 30);
    
    const todayFormatted = today.toISOString().substring(0, 10);
    const lastMonthFormatted = lastMonth.toISOString().substring(0, 10);
    
    // Set employee payment date range
    $('#employeePaymentStartDate').val(lastMonthFormatted);
    $('#employeePaymentEndDate').val(todayFormatted);
    
    // Set shipping date range
    $('#shippingStartDate').val(lastMonthFormatted);
    $('#shippingEndDate').val(todayFormatted);
    
    // Set withdrawal date range
    $('#withdrawalStartDate').val(lastMonthFormatted);
    $('#withdrawalEndDate').val(todayFormatted);
}

/**
 * Setup tab click handlers
 */
function setupTabHandlers() {
    // Tab click event
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tabId = $(e.target).attr('id');
        activeTab = tabId.replace('-tab', '');
    });
}

/**
 * Setup automatic filter handlers
 */
function setupAutoFilterHandlers() {
    // Employee payment auto filters
    $('#employeePaymentStartDate, #employeePaymentEndDate, #employeePaymentName').on('change input', function() {
        applyEmployeePaymentFilter();
    });
    
    // Shipping auto filters
    $('#shippingStartDate, #shippingEndDate, #shippingProvider').on('change input', function() {
        applyShippingFilter();
    });
    
    // Withdrawal auto filters
    $('#withdrawalStartDate, #withdrawalEndDate, #withdrawalName').on('change input', function() {
        applyWithdrawalFilter();
    });
    
    // Reset filter buttons
    $('#employeePaymentResetFilter').on('click', function() {
        resetEmployeePaymentFilter();
    });
    
    $('#shippingResetFilter').on('click', function() {
        resetShippingFilter();
    });
    
    $('#withdrawalResetFilter').on('click', function() {
        resetWithdrawalFilter();
    });
}

/**
 * Apply employee payment filter
 */
function applyEmployeePaymentFilter() {
    const startDate = $('#employeePaymentStartDate').val();
    const endDate = $('#employeePaymentEndDate').val();
    const name = $('#employeePaymentName').val();
    
    if (!startDate && !endDate && !name) {
        // No filters, reset to original data
        filteredEmployeeHistoryData = [...employeeHistoryData];
    } else {
        // Apply filters
        filteredEmployeeHistoryData = employeeHistoryData.filter(item => {
            // Date filtering
            let dateMatches = true;
            if (startDate && endDate) {
                const itemDate = new Date(item.paymentDate.replace(/(\d{4})\/(\d{2})\/(\d{2})/, '$1-$2-$3'));
                const filterStartDate = new Date(startDate);
                const filterEndDate = new Date(endDate);
                dateMatches = itemDate >= filterStartDate && itemDate <= filterEndDate;
            }
            
            // Name filtering
            let nameMatches = true;
            if (name) {
                nameMatches = item.employeeName === name;
            }
            
            return dateMatches && nameMatches;
        });
    }
    
    // Reset to first page and render
    employeeHistoryCurrentPage = 1;
    renderEmployeeHistoryTable();
    
    // Show toast notification
    showToast('داتاکان فلتەر کران', 'info');
}

/**
 * Apply shipping filter
 */
function applyShippingFilter() {
    const startDate = $('#shippingStartDate').val();
    const endDate = $('#shippingEndDate').val();
    const provider = $('#shippingProvider').val();
    
    // Similar implementation to employee payment filter but for shipping data
    if (!startDate && !endDate && !provider) {
        // No filters, reset to original data
        filteredShippingData = [...shippingData];
    } else {
        // Apply filters
        filteredShippingData = shippingData.filter(item => {
            // Date filtering
            let dateMatches = true;
            if (startDate && endDate) {
                const itemDate = new Date(item.shippingDate.replace(/(\d{4})\/(\d{2})\/(\d{2})/, '$1-$2-$3'));
                const filterStartDate = new Date(startDate);
                const filterEndDate = new Date(endDate);
                dateMatches = itemDate >= filterStartDate && itemDate <= filterEndDate;
            }
            
            // Provider filtering
            let providerMatches = true;
            if (provider) {
                providerMatches = item.provider === provider;
            }
            
            return dateMatches && providerMatches;
        });
    }
    
    // Reset to first page and render
    shippingCurrentPage = 1;
    renderShippingTable();
    
    // Show toast notification
    showToast('داتاکان فلتەر کران', 'info');
}

/**
 * Apply withdrawal filter
 */
function applyWithdrawalFilter() {
    const startDate = $('#withdrawalStartDate').val();
    const endDate = $('#withdrawalEndDate').val();
    const name = $('#withdrawalName').val();
    
    // Similar implementation to employee payment filter but for withdrawal data
    if (!startDate && !endDate && !name) {
        // No filters, reset to original data
        filteredWithdrawalData = [...withdrawalData];
    } else {
        // Apply filters
        filteredWithdrawalData = withdrawalData.filter(item => {
            // Date filtering
            let dateMatches = true;
            if (startDate && endDate) {
                const itemDate = new Date(item.withdrawalDate.replace(/(\d{4})\/(\d{2})\/(\d{2})/, '$1-$2-$3'));
                const filterStartDate = new Date(startDate);
                const filterEndDate = new Date(endDate);
                dateMatches = itemDate >= filterStartDate && itemDate <= filterEndDate;
            }
            
            // Name filtering
            let nameMatches = true;
            if (name) {
                nameMatches = item.name === name;
            }
            
            return dateMatches && nameMatches;
        });
    }
    
    // Reset to first page and render
    withdrawalCurrentPage = 1;
    renderWithdrawalTable();
    
    // Show toast notification
    showToast('داتاکان فلتەر کران', 'info');
}

/**
 * Reset employee payment filter
 */
function resetEmployeePaymentFilter() {
    // Reset date inputs to default range
    const today = new Date();
    const lastMonth = new Date();
    lastMonth.setDate(today.getDate() - 30);
    
    const todayFormatted = today.toISOString().substring(0, 10);
    const lastMonthFormatted = lastMonth.toISOString().substring(0, 10);
    
    $('#employeePaymentStartDate').val(lastMonthFormatted);
    $('#employeePaymentEndDate').val(todayFormatted);
    $('#employeePaymentName').val('');
    
    // Reset data to original
    filteredEmployeeHistoryData = [...employeeHistoryData];
    employeeHistoryCurrentPage = 1;
    renderEmployeeHistoryTable();
    
    // Show toast notification
    showToast('فلتەرەکان ڕیسێت کران', 'success');
}

/**
 * Reset shipping filter
 */
function resetShippingFilter() {
    // Reset date inputs to default range
    const today = new Date();
    const lastMonth = new Date();
    lastMonth.setDate(today.getDate() - 30);
    
    const todayFormatted = today.toISOString().substring(0, 10);
    const lastMonthFormatted = lastMonth.toISOString().substring(0, 10);
    
    $('#shippingStartDate').val(lastMonthFormatted);
    $('#shippingEndDate').val(todayFormatted);
    $('#shippingProvider').val('');
    
    // For demo purposes, show a toast notification
    showToast('فلتەرەکان ڕیسێت کران', 'success');
}

/**
 * Reset withdrawal filter
 */
function resetWithdrawalFilter() {
    // Reset date inputs to default range
    const today = new Date();
    const lastMonth = new Date();
    lastMonth.setDate(today.getDate() - 30);
    
    const todayFormatted = today.toISOString().substring(0, 10);
    const lastMonthFormatted = lastMonth.toISOString().substring(0, 10);
    
    $('#withdrawalStartDate').val(lastMonthFormatted);
    $('#withdrawalEndDate').val(todayFormatted);
    $('#withdrawalName').val('');
    
    // For demo purposes, show a toast notification
    showToast('فلتەرەکان ڕیسێت کران', 'success');
}

// EMPLOYEE PAYMENT HISTORY FUNCTIONS

/**
 * Employee Payment history table variables
 */
let employeeHistoryData = [];
let filteredEmployeeHistoryData = [];
let employeeHistoryCurrentPage = 1;
let employeeHistoryRecordsPerPage = 10;
let employeeHistoryTotalPages = 1;

/**
 * Initialize the employee payment history table
 */
function initializeEmployeeHistoryTable() {
    // Collect data from the table
    collectEmployeeHistoryData();
    
    // Initial table render
    renderEmployeeHistoryTable();
    
    // Setup table event listeners
    setupEmployeeHistoryTableEventListeners();
}

/**
 * Collect data from the employee payment history table's initial HTML
 */
function collectEmployeeHistoryData() {
    const rows = $('#employeeHistoryTable tbody tr');
    
    rows.each(function() {
        const $row = $(this);
        const id = $row.data('id');
        
        const rowData = {
            id: id,
            employeeId: id,
            employeeName: $row.find('td:eq(1)').text(),
            paymentDate: $row.find('td:eq(2)').text(),
            paymentAmount: $row.find('td:eq(3)').text(),
            paymentType: $row.find('td:eq(4)').find('span').text(),
            notes: $row.find('td:eq(5)').text()
        };
        
        employeeHistoryData.push(rowData);
    });
    
    // Initialize filtered data
    filteredEmployeeHistoryData = [...employeeHistoryData];
    
    // Update pagination info
    updateEmployeeHistoryPaginationInfo();
}

/**
 * Setup employee payment history table event listeners
 */
function setupEmployeeHistoryTableEventListeners() {
    // Pagination controls
    $('#employeePrevPageBtn').on('click', function() {
        if (employeeHistoryCurrentPage > 1) {
            employeeHistoryCurrentPage--;
            renderEmployeeHistoryTable();
        }
    });
    
    $('#employeeNextPageBtn').on('click', function() {
        if (employeeHistoryCurrentPage < employeeHistoryTotalPages) {
            employeeHistoryCurrentPage++;
            renderEmployeeHistoryTable();
        }
    });
    
    // Records per page change
    $('#employeeRecordsPerPage').on('change', function() {
        employeeHistoryRecordsPerPage = parseInt($(this).val());
        employeeHistoryCurrentPage = 1; // Reset to first page
        renderEmployeeHistoryTable();
    });
    
    // Search functionality
    $('#employeeTableSearch').on('input', function() {
        const searchQuery = $(this).val().toLowerCase();
        
        if (searchQuery.length === 0) {
            filteredEmployeeHistoryData = [...employeeHistoryData];
        } else {
            filteredEmployeeHistoryData = employeeHistoryData.filter(item => {
                return (
                    item.employeeName.toLowerCase().includes(searchQuery) ||
                    item.paymentDate.toLowerCase().includes(searchQuery) ||
                    item.paymentAmount.toLowerCase().includes(searchQuery) ||
                    item.paymentType.toLowerCase().includes(searchQuery) ||
                    item.notes.toLowerCase().includes(searchQuery)
                );
            });
        }
        
        employeeHistoryCurrentPage = 1; // Reset to first page
        renderEmployeeHistoryTable();
    });
    
    // Number pagination clicks
    $(document).on('click', '.pagination-number', function() {
        employeeHistoryCurrentPage = parseInt($(this).text());
        renderEmployeeHistoryTable();
    });
}

/**
 * Render the employee payment history table with current data and pagination
 */
function renderEmployeeHistoryTable() {
    const tableBody = $('#employeeHistoryTable tbody');
    tableBody.empty();
    
    // Calculate pagination
    updateEmployeeHistoryPaginationInfo();
    
    // Get current page data
    const startIndex = (employeeHistoryCurrentPage - 1) * employeeHistoryRecordsPerPage;
    const endIndex = Math.min(startIndex + employeeHistoryRecordsPerPage, filteredEmployeeHistoryData.length);
    const currentPageData = filteredEmployeeHistoryData.slice(startIndex, endIndex);
    
    // No results message
    if (currentPageData.length === 0) {
        tableBody.append('<tr><td colspan="7" class="text-center">هیچ داتایەک نەدۆزرایەوە</td></tr>');
    } else {
        // Add rows to table
        currentPageData.forEach(item => {
            tableBody.append(`
                <tr data-id="${item.id}">
                    <td>${item.id}</td>
                    <td>${item.employeeName}</td>
                    <td>${item.paymentDate}</td>
                    <td>${item.paymentAmount}</td>
                    <td><span class="badge rounded-pill ${getBadgeClass(item.paymentType)}">${item.paymentType}</span></td>
                    <td>${item.notes}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle view-btn" data-id="${item.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${item.id}">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `);
        });
    }
    
    // Update pagination controls
    updateEmployeeHistoryPaginationControls();
    
    // Update pagination info text
    updateEmployeeHistoryPaginationText();
}

/**
 * Update employee payment history pagination info
 */
function updateEmployeeHistoryPaginationInfo() {
    employeeHistoryTotalPages = Math.ceil(filteredEmployeeHistoryData.length / employeeHistoryRecordsPerPage);
    if (employeeHistoryTotalPages === 0) employeeHistoryTotalPages = 1;
    
    // Ensure current page is in valid range
    if (employeeHistoryCurrentPage > employeeHistoryTotalPages) {
        employeeHistoryCurrentPage = employeeHistoryTotalPages;
    }
}

/**
 * Update employee payment history pagination info text
 */
function updateEmployeeHistoryPaginationText() {
    const startRecord = filteredEmployeeHistoryData.length > 0 ? (employeeHistoryCurrentPage - 1) * employeeHistoryRecordsPerPage + 1 : 0;
    const endRecord = Math.min(startRecord + employeeHistoryRecordsPerPage - 1, filteredEmployeeHistoryData.length);
    const totalRecords = filteredEmployeeHistoryData.length;
    
    $('#employeeStartRecord').text(startRecord);
    $('#employeeEndRecord').text(endRecord);
    $('#employeeTotalRecords').text(totalRecords);
}

/**
 * Update employee payment history pagination controls with limits
 */
function updateEmployeeHistoryPaginationControls() {
    // Update prev/next buttons
    $('#employeePrevPageBtn').prop('disabled', employeeHistoryCurrentPage === 1);
    $('#employeeNextPageBtn').prop('disabled', employeeHistoryCurrentPage === employeeHistoryTotalPages);
    
    // Update pagination numbers
    const paginationContainer = $('#employeePaginationNumbers');
    paginationContainer.empty();
    
    // Limit the number of pagination buttons shown
    const maxPaginationButtons = 5;
    let startPage = Math.max(1, employeeHistoryCurrentPage - Math.floor(maxPaginationButtons / 2));
    let endPage = Math.min(employeeHistoryTotalPages, startPage + maxPaginationButtons - 1);
    
    // Adjust if we're at the end
    if (endPage - startPage + 1 < maxPaginationButtons && startPage > 1) {
        startPage = Math.max(1, endPage - maxPaginationButtons + 1);
    }
    
    // Add first page button if not visible
    if (startPage > 1) {
        paginationContainer.append(`
            <button class="btn btn-sm btn-outline-primary rounded-circle me-1 pagination-number">1</button>
        `);
        
        // Add ellipsis if needed
        if (startPage > 2) {
            paginationContainer.append('<span class="pagination-ellipsis me-1">...</span>');
        }
    }
    
    // Add pagination numbers
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === employeeHistoryCurrentPage;
        const buttonClass = isActive ? 'btn-primary active' : 'btn-outline-primary';
        paginationContainer.append(`
            <button class="btn btn-sm ${buttonClass} rounded-circle me-1 pagination-number">${i}</button>
        `);
    }
    
    // Add last page button if not visible
    if (endPage < employeeHistoryTotalPages) {
        // Add ellipsis if needed
        if (endPage < employeeHistoryTotalPages - 1) {
            paginationContainer.append('<span class="pagination-ellipsis me-1">...</span>');
        }
        
        paginationContainer.append(`
            <button class="btn btn-sm btn-outline-primary rounded-circle me-1 pagination-number">${employeeHistoryTotalPages}</button>
        `);
    }
}

/**
 * Get badge class based on payment type
 */
function getBadgeClass(paymentType) {
    switch (paymentType) {
        case 'مووچە':
            return 'bg-success';
        case 'پێشەکی':
            return 'bg-info';
        case 'وشکانی':
            return 'bg-info';
        case 'دەریایی':
            return 'bg-success';
        case 'خەرجی ڕۆژانە':
            return 'bg-warning text-dark';
        case 'کرێ':
            return 'bg-danger';
        case 'خزمەتگوزاری':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// SHIPPING COST HISTORY FUNCTIONS
// Similar implementations to employee payment history functions but for shipping costs
function initializeShippingHistoryTable() {
    // Implementation similar to employee payment history table initialization
}

// WITHDRAWAL HISTORY FUNCTIONS
// Similar implementations to employee payment history functions but for withdrawals
function initializeWithdrawalHistoryTable() {
    // Implementation similar to employee payment history table initialization
}

/**
 * Setup action buttons (view, print, etc.)
 */
function setupActionButtons() {
    // View button click handler
    $(document).on('click', '.view-btn', function() {
        const id = $(this).data('id');
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        // Here you would normally fetch details from the server and show them in a modal
        // For demo purposes, we'll just show a toast notification
        let message = '';
        
        if (tabId === 'employee-payment-content') {
            message = 'پیشاندانی پارەدانی کارمەند - تۆماری ژمارە: ' + id;
        } else if (tabId === 'shipping-content') {
            message = 'پیشاندانی کرێی بار - تۆماری ژمارە: ' + id;
        } else if (tabId === 'withdrawal-content') {
            message = 'پیشاندانی دەرکردنی پارە - تۆماری ژمارە: ' + id;
        }
        
        showToast(message, 'info');
    });
    
    // Print button click handler
    $(document).on('click', '.print-btn', function() {
        const id = $(this).data('id');
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        // Here you would normally generate a printable view and open it
        // For demo purposes, we'll just show a toast notification
        let message = '';
        
        if (tabId === 'employee-payment-content') {
            message = 'چاپکردنی پارەدانی کارمەند - تۆماری ژمارە: ' + id;
        } else if (tabId === 'shipping-content') {
            message = 'چاپکردنی کرێی بار - تۆماری ژمارە: ' + id;
        } else if (tabId === 'withdrawal-content') {
            message = 'چاپکردنی دەرکردنی پارە - تۆماری ژمارە: ' + id;
        }
        
        showToast(message, 'success');
    });
    
    // Refresh button click handler
    $('.refresh-btn').on('click', function() {
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        // Here you would normally reload data from the server
        // For demo purposes, we'll just show a toast notification
        let message = 'داتاکان نوێکرانەوە';
        
        showToast(message, 'info');
    });
} 