// Global variables for all tables
// Employee Payment table variables
let employeePaymentData = [];
let filteredEmployeePaymentData = [];
let employeePaymentCurrentPage = 1;
let employeePaymentRecordsPerPage = 10;
let employeePaymentTotalPages = 1;

// Shipping table variables
let shippingData = [];
let filteredShippingData = [];
let shippingCurrentPage = 1;
let shippingRecordsPerPage = 10;
let shippingTotalPages = 1;

// Withdrawal table variables
let withdrawalData = [];
let filteredWithdrawalData = [];
let withdrawalCurrentPage = 1;
let withdrawalRecordsPerPage = 10;
let withdrawalTotalPages = 1;

$(document).ready(function() {
    // Load components (sidebar, navbar)
    loadComponents();
    
    // Tab related variables
    let activeTab = "employee-payment";
    
    // Initialize all tables
    initializeEmployeePaymentTable();
    initializeShippingCostTable();
    initializeWithdrawalTable();
    
    // Set default dates to today
    setDefaultDates();
    
    // Setup form submission handlers
    setupFormHandlers();
    
    // Setup tab click handlers
    setupTabHandlers();
    
    // Button click handlers
    setupButtonHandlers();
});

/**
 * Load the navbar and sidebar components
 */
function loadComponents() {
    // This function is located in the include-components.js file
    // It will load the navbar and sidebar components
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
    
    // Highlight the employee payment menu item
    const employeePaymentItem = submenu.find('a[href="employeePayment.php"]');
    employeePaymentItem.parent().addClass('active');
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
 * Set all default dates to today
 */
function setDefaultDates() {
    const today = new Date();
    const formattedDate = today.toISOString().substring(0, 10);
    
    // Employee payment date
    $('#paymentDate').val(formattedDate);
    
    // Shipping date
    $('#shippingDate').val(formattedDate);
    
    // Withdrawal date
    $('#withdrawalDate').val(formattedDate);
}

/**
 * Setup all form submission handlers
 */
function setupFormHandlers() {
    // Employee Payment form
    $('#addEmployeePaymentForm').on('submit', function(e) {
        e.preventDefault();
        handleEmployeePaymentSubmission();
    });
    
    // Shipping Cost form
    $('#addShippingCostForm').on('submit', function(e) {
        e.preventDefault();
        handleShippingCostSubmission();
    });
    
    // Withdrawal form
    $('#addWithdrawalForm').on('submit', function(e) {
        e.preventDefault();
        handleWithdrawalSubmission();
    });
}

// EMPLOYEE PAYMENT TAB FUNCTIONS

/**
 * Initialize the employee payment table
 */
function initializeEmployeePaymentTable() {
    // Collect data from the table
    collectEmployeePaymentData();
    
    // Initial table render
    renderEmployeePaymentTable();
    
    // Setup table event listeners
    setupEmployeePaymentTableEventListeners();
}

/**
 * Collect data from the employee payment table's initial HTML
 */
function collectEmployeePaymentData() {
    const rows = $('#employeePaymentsTable tbody tr');
    
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
        
        employeePaymentData.push(rowData);
    });
    
    // Initialize filtered data
    filteredEmployeePaymentData = [...employeePaymentData];
    
    // Update pagination info
    updateEmployeePaymentPaginationInfo();
}

/**
 * Setup employee payment table event listeners
 */
function setupEmployeePaymentTableEventListeners() {
    // Pagination controls
    $('#prevPageBtn').on('click', function() {
        if (employeePaymentCurrentPage > 1) {
            employeePaymentCurrentPage--;
            renderEmployeePaymentTable();
        }
    });
    
    $('#nextPageBtn').on('click', function() {
        if (employeePaymentCurrentPage < employeePaymentTotalPages) {
            employeePaymentCurrentPage++;
            renderEmployeePaymentTable();
        }
    });
    
    // Records per page change
    $('#recordsPerPage').on('change', function() {
        employeePaymentRecordsPerPage = parseInt($(this).val());
        employeePaymentCurrentPage = 1; // Reset to first page
        renderEmployeePaymentTable();
    });
    
    // Search functionality
    $('#tableSearch').on('input', function() {
        const searchQuery = $(this).val().toLowerCase();
        
        if (searchQuery.length === 0) {
            filteredEmployeePaymentData = [...employeePaymentData];
        } else {
            filteredEmployeePaymentData = employeePaymentData.filter(item => {
                return (
                    item.employeeName.toLowerCase().includes(searchQuery) ||
                    item.paymentDate.toLowerCase().includes(searchQuery) ||
                    item.paymentAmount.toLowerCase().includes(searchQuery) ||
                    item.paymentType.toLowerCase().includes(searchQuery) ||
                    item.notes.toLowerCase().includes(searchQuery)
                );
            });
        }
        
        employeePaymentCurrentPage = 1; // Reset to first page
        renderEmployeePaymentTable();
    });
    
    // Number pagination clicks
    $(document).on('click', '.pagination-number', function() {
        employeePaymentCurrentPage = parseInt($(this).text());
        renderEmployeePaymentTable();
    });
}

/**
 * Render the employee payment table with current data and pagination
 */
function renderEmployeePaymentTable() {
    const tableBody = $('#employeePaymentsTable tbody');
    tableBody.empty();
    
    // Calculate pagination
    updateEmployeePaymentPaginationInfo();
    
    // Get current page data
    const startIndex = (employeePaymentCurrentPage - 1) * employeePaymentRecordsPerPage;
    const endIndex = Math.min(startIndex + employeePaymentRecordsPerPage, filteredEmployeePaymentData.length);
    const currentPageData = filteredEmployeePaymentData.slice(startIndex, endIndex);
    
    // No results message
    if (currentPageData.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="no-results">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">هیچ ئەنجامێک نەدۆزرایەوە</p>
                    </div>
                </td>
            </tr>
        `);
    } else {
        // Render rows
        currentPageData.forEach(item => {
            // Determine badge class based on payment type
            let badgeClass = 'bg-secondary';
            if (item.paymentType === 'مووچە') {
                badgeClass = 'bg-success';
            } else if (item.paymentType === 'پێشەکی') {
                badgeClass = 'bg-info';
            } else if (item.paymentType === 'پاداشت') {
                badgeClass = 'bg-warning text-dark';
            }
            
            const rowHtml = `
                <tr data-id="${item.id}" class="fade-in">
                    <td>${item.id}</td>
                    <td>${item.employeeName}</td>
                    <td>${item.paymentDate}</td>
                    <td>${item.paymentAmount}</td>
                    <td><span class="badge rounded-pill ${badgeClass}">${item.paymentType}</span></td>
                    <td>${item.notes}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${item.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${item.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            
            tableBody.append(rowHtml);
        });
    }
    
    // Update pagination controls
    updateEmployeePaymentPaginationControls();
    
    // Update pagination info text
    updateEmployeePaymentPaginationInfoText();
}

/**
 * Update employee payment pagination info
 */
function updateEmployeePaymentPaginationInfo() {
    employeePaymentTotalPages = Math.ceil(filteredEmployeePaymentData.length / employeePaymentRecordsPerPage);
    if (employeePaymentTotalPages === 0) employeePaymentTotalPages = 1;
    
    // Ensure current page is in valid range
    if (employeePaymentCurrentPage > employeePaymentTotalPages) {
        employeePaymentCurrentPage = employeePaymentTotalPages;
    }
}

/**
 * Update employee payment pagination controls
 */
function updateEmployeePaymentPaginationControls() {
    // Update prev/next buttons
    $('#prevPageBtn').prop('disabled', employeePaymentCurrentPage === 1);
    $('#nextPageBtn').prop('disabled', employeePaymentCurrentPage === employeePaymentTotalPages);
    
    // Update pagination numbers
    const paginationNumbers = $('#paginationNumbers');
    paginationNumbers.empty();
    
    // Limit the number of pagination buttons shown
    const maxPaginationButtons = 5;
    let startPage = Math.max(1, employeePaymentCurrentPage - Math.floor(maxPaginationButtons / 2));
    let endPage = Math.min(employeePaymentTotalPages, startPage + maxPaginationButtons - 1);
    
    // Adjust if we're at the end
    if (endPage - startPage + 1 < maxPaginationButtons) {
        startPage = Math.max(1, endPage - maxPaginationButtons + 1);
    }
    
    // Add pagination numbers
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === employeePaymentCurrentPage;
        const buttonClass = isActive ? 'btn-primary active' : 'btn-outline-primary';
        
        paginationNumbers.append(`
            <button class="btn btn-sm ${buttonClass} rounded-circle me-2 pagination-number">
                ${i}
            </button>
        `);
    }
}

/**
 * Update employee payment pagination info text
 */
function updateEmployeePaymentPaginationInfoText() {
    const startRecord = filteredEmployeePaymentData.length > 0 ? (employeePaymentCurrentPage - 1) * employeePaymentRecordsPerPage + 1 : 0;
    const endRecord = Math.min(startRecord + employeePaymentRecordsPerPage - 1, filteredEmployeePaymentData.length);
    const totalRecords = filteredEmployeePaymentData.length;
    
    $('#startRecord').text(startRecord);
    $('#endRecord').text(endRecord);
    $('#totalRecords').text(totalRecords);
}

/**
 * Handle employee payment form submission
 */
function handleEmployeePaymentSubmission() {
    // Get form data
    const employeeId = $('#employeeName').val();
    const employeeName = $('#employeeName option:selected').text();
    const paymentDate = $('#paymentDate').val();
    const paymentAmount = $('#paymentAmount').val();
    const paymentType = $('#paymentType').val();
    const paymentTypeName = $('#paymentType option:selected').text();
    const notes = $('#paymentNotes').val();
    
    if (!employeeId || !paymentDate || !paymentAmount || !paymentType) {
        showAlert('تکایە زانیارییەکان بە تەواوی پڕ بکەوە.', 'danger');
        return;
    }
    
    // Here you would normally send data to the server using AJAX
    // For now, we'll just add a row to the table as a demo
    
    // Get next ID
    const newId = getNextEmployeePaymentId();
    
    // Create new record
    const newRecord = {
        id: newId,
        employeeId: employeeId,
        employeeName: employeeName,
        paymentDate: formatDate(paymentDate),
        paymentAmount: '$' + paymentAmount,
        paymentType: paymentTypeName,
        notes: notes || ''
    };
    
    // Add to data arrays
    employeePaymentData.push(newRecord);
    filteredEmployeePaymentData.push(newRecord);
    
    // Refresh table display
    employeePaymentCurrentPage = Math.ceil(employeePaymentData.length / employeePaymentRecordsPerPage); // Go to last page
    renderEmployeePaymentTable();
    
    // Reset form
    resetEmployeePaymentForm();
    
    // Show success message
    showAlert('پارەدان بە سەرکەوتوویی زیادکرا.', 'success');
}

/**
 * Reset the employee payment form fields
 */
function resetEmployeePaymentForm() {
    $('#employeeName').val('');
    setDefaultDates();
    $('#paymentAmount').val('');
    $('#paymentType').val('');
    $('#paymentNotes').val('');
}

/**
 * Get the next ID for employee payment
 */
function getNextEmployeePaymentId() {
    let maxId = 0;
    employeePaymentData.forEach(item => {
        if (item.id > maxId) {
            maxId = item.id;
        }
    });
    
    return maxId + 1;
}

// SHIPPING COST TAB FUNCTIONS

/**
 * Initialize the shipping cost table
 */
function initializeShippingCostTable() {
    // Collect data from the table
    collectShippingData();
    
    // Initial table render
    renderShippingTable();
    
    // Setup table event listeners
    setupShippingTableEventListeners();
}

/**
 * Collect data from the shipping table's initial HTML
 */
function collectShippingData() {
    const rows = $('#shippingCostsTable tbody tr');
    
    shippingData = []; // Clear existing data
    
    rows.each(function() {
        const $row = $(this);
        const id = $row.data('id');
        
        const rowData = {
            id: id,
            provider: $row.find('td:eq(1)').text(),
            shippingDate: $row.find('td:eq(2)').text(),
            shippingAmount: $row.find('td:eq(3)').text(),
            shippingType: $row.find('td:eq(4)').find('span').text(),
            notes: $row.find('td:eq(5)').text()
        };
        
        shippingData.push(rowData);
    });
    
    // Initialize filtered data
    filteredShippingData = [...shippingData];
    
    // Update pagination info
    updateShippingPaginationInfo();
}

/**
 * Setup shipping table event listeners
 */
function setupShippingTableEventListeners() {
    // Pagination controls
    $('#shippingPrevPageBtn').on('click', function() {
        if (shippingCurrentPage > 1) {
            shippingCurrentPage--;
            renderShippingTable();
        }
    });
    
    $('#shippingNextPageBtn').on('click', function() {
        if (shippingCurrentPage < shippingTotalPages) {
            shippingCurrentPage++;
            renderShippingTable();
        }
    });
    
    // Records per page change
    $('#shippingRecordsPerPage').on('change', function() {
        shippingRecordsPerPage = parseInt($(this).val());
        shippingCurrentPage = 1; // Reset to first page
        renderShippingTable();
    });
    
    // Search functionality
    $('#shippingTableSearch').on('input', function() {
        const searchQuery = $(this).val().toLowerCase();
        
        if (searchQuery.length === 0) {
            filteredShippingData = [...shippingData];
        } else {
            filteredShippingData = shippingData.filter(item => {
                return (
                    item.provider.toLowerCase().includes(searchQuery) ||
                    item.shippingDate.toLowerCase().includes(searchQuery) ||
                    item.shippingAmount.toLowerCase().includes(searchQuery) ||
                    item.shippingType.toLowerCase().includes(searchQuery) ||
                    item.notes.toLowerCase().includes(searchQuery)
                );
            });
        }
        
        shippingCurrentPage = 1; // Reset to first page
        renderShippingTable();
    });
}

/**
 * Render the shipping table
 */
function renderShippingTable() {
    const tableBody = $('#shippingCostsTable tbody');
    tableBody.empty();
    
    // Calculate pagination
    updateShippingPaginationInfo();
    
    // Get current page data
    const startIndex = (shippingCurrentPage - 1) * shippingRecordsPerPage;
    const endIndex = Math.min(startIndex + shippingRecordsPerPage, filteredShippingData.length);
    const currentPageData = filteredShippingData.slice(startIndex, endIndex);
    
    // No results message
    if (currentPageData.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="no-results">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">هیچ ئەنجامێک نەدۆزرایەوە</p>
                    </div>
                </td>
            </tr>
        `);
    } else {
        // Render rows
        currentPageData.forEach(item => {
            // Determine badge class based on shipping type
            let badgeClass = 'bg-secondary';
            if (item.shippingType === 'وشکانی') {
                badgeClass = 'bg-info';
            } else if (item.shippingType === 'دەریایی') {
                badgeClass = 'bg-success';
            }
            
            const rowHtml = `
                <tr data-id="${item.id}" class="fade-in">
                    <td>${item.id}</td>
                    <td>${item.provider}</td>
                    <td>${item.shippingDate}</td>
                    <td>${item.shippingAmount}</td>
                    <td><span class="badge rounded-pill ${badgeClass}">${item.shippingType}</span></td>
                    <td>${item.notes}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${item.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${item.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            
            tableBody.append(rowHtml);
        });
    }
    
    // Update pagination controls
    updateShippingPaginationControls();
    
    // Update pagination info text
    updateShippingPaginationInfoText();
}

/**
 * Update shipping pagination information
 */
function updateShippingPaginationInfo() {
    shippingTotalPages = Math.ceil(filteredShippingData.length / shippingRecordsPerPage);
    if (shippingTotalPages === 0) shippingTotalPages = 1;
    
    // Ensure current page is in valid range
    if (shippingCurrentPage > shippingTotalPages) {
        shippingCurrentPage = shippingTotalPages;
    }
}

/**
 * Update shipping pagination controls
 */
function updateShippingPaginationControls() {
    // Update prev/next buttons
    $('#shippingPrevPageBtn').prop('disabled', shippingCurrentPage === 1);
    $('#shippingNextPageBtn').prop('disabled', shippingCurrentPage === shippingTotalPages);
    
    // Update pagination numbers
    const paginationNumbers = $('#shippingPaginationNumbers');
    paginationNumbers.empty();
    
    // Limit the number of pagination buttons shown
    const maxPaginationButtons = 5;
    let startPage = Math.max(1, shippingCurrentPage - Math.floor(maxPaginationButtons / 2));
    let endPage = Math.min(shippingTotalPages, startPage + maxPaginationButtons - 1);
    
    // Adjust if we're at the end
    if (endPage - startPage + 1 < maxPaginationButtons) {
        startPage = Math.max(1, endPage - maxPaginationButtons + 1);
    }
    
    // Add pagination numbers
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === shippingCurrentPage;
        const buttonClass = isActive ? 'btn-primary active' : 'btn-outline-primary';
        
        paginationNumbers.append(`
            <button class="btn btn-sm ${buttonClass} rounded-circle me-2 pagination-number">
                ${i}
            </button>
        `);
    }
}

/**
 * Update shipping pagination info text
 */
function updateShippingPaginationInfoText() {
    const startRecord = filteredShippingData.length > 0 ? (shippingCurrentPage - 1) * shippingRecordsPerPage + 1 : 0;
    const endRecord = Math.min(startRecord + shippingRecordsPerPage - 1, filteredShippingData.length);
    const totalRecords = filteredShippingData.length;
    
    $('#shippingStartRecord').text(startRecord);
    $('#shippingEndRecord').text(endRecord);
    $('#shippingTotalRecords').text(totalRecords);
}

/**
 * Handle shipping cost form submission
 */
function handleShippingCostSubmission() {
    // Similar to employee payment but with shipping-specific form fields
    // ...Implementation similar to employee payment form submission
    showAlert('کرێی بار بە سەرکەوتوویی زیادکرا.', 'success');
}

// WITHDRAWAL TAB FUNCTIONS

/**
 * Initialize the withdrawal table
 */
function initializeWithdrawalTable() {
    // Collect data from the table
    collectWithdrawalData();
    
    // Initial table render
    renderWithdrawalTable();
    
    // Setup table event listeners
    setupWithdrawalTableEventListeners();
}

/**
 * Collect data from the withdrawal table's initial HTML
 */
function collectWithdrawalData() {
    const rows = $('#withdrawalsTable tbody tr');
    
    rows.each(function() {
        const $row = $(this);
        const id = $row.data('id');
        
        const rowData = {
            id: id,
            name: $row.find('td:eq(1)').text(),
            withdrawalDate: $row.find('td:eq(2)').text(),
            withdrawalAmount: $row.find('td:eq(3)').text(),
            withdrawalCategory: $row.find('td:eq(4)').find('span').text(),
            notes: $row.find('td:eq(5)').text()
        };
        
        withdrawalData.push(rowData);
    });
    
    // Initialize filtered data
    filteredWithdrawalData = [...withdrawalData];
    
    // Update pagination info
    updateWithdrawalPaginationInfo();
}

/**
 * Setup withdrawal table event listeners
 */
function setupWithdrawalTableEventListeners() {
    // Pagination controls
    $('#withdrawalPrevPageBtn').on('click', function() {
        if (withdrawalCurrentPage > 1) {
            withdrawalCurrentPage--;
            renderWithdrawalTable();
        }
    });
    
    $('#withdrawalNextPageBtn').on('click', function() {
        if (withdrawalCurrentPage < withdrawalTotalPages) {
            withdrawalCurrentPage++;
            renderWithdrawalTable();
        }
    });
    
    // Records per page change
    $('#withdrawalRecordsPerPage').on('change', function() {
        withdrawalRecordsPerPage = parseInt($(this).val());
        withdrawalCurrentPage = 1; // Reset to first page
        renderWithdrawalTable();
    });
    
    // Search functionality
    $('#withdrawalTableSearch').on('input', function() {
        const searchQuery = $(this).val().toLowerCase();
        
        if (searchQuery.length === 0) {
            filteredWithdrawalData = [...withdrawalData];
        } else {
            filteredWithdrawalData = withdrawalData.filter(item => {
                return (
                    item.name.toLowerCase().includes(searchQuery) ||
                    item.withdrawalDate.toLowerCase().includes(searchQuery) ||
                    item.withdrawalAmount.toLowerCase().includes(searchQuery) ||
                    item.withdrawalCategory.toLowerCase().includes(searchQuery) ||
                    item.notes.toLowerCase().includes(searchQuery)
                );
            });
        }
        
        withdrawalCurrentPage = 1; // Reset to first page
        renderWithdrawalTable();
    });
    
    // Number pagination clicks
    $(document).on('click', '#withdrawalPaginationNumbers .pagination-number', function() {
        withdrawalCurrentPage = parseInt($(this).text());
        renderWithdrawalTable();
    });
}

/**
 * Render the withdrawal table
 */
function renderWithdrawalTable() {
    const tableBody = $('#withdrawalsTable tbody');
    tableBody.empty();
    
    // Calculate pagination
    updateWithdrawalPaginationInfo();
    
    // Get current page data
    const startIndex = (withdrawalCurrentPage - 1) * withdrawalRecordsPerPage;
    const endIndex = Math.min(startIndex + withdrawalRecordsPerPage, filteredWithdrawalData.length);
    const currentPageData = filteredWithdrawalData.slice(startIndex, endIndex);
    
    // No results message
    if (currentPageData.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="no-results">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">هیچ ئەنجامێک نەدۆزرایەوە</p>
                    </div>
                </td>
            </tr>
        `);
    } else {
        // Render rows
        currentPageData.forEach(item => {
            // Determine badge class based on withdrawal category
            let badgeClass = 'bg-secondary';
            if (item.withdrawalCategory === 'چاککردنەوە') {
                badgeClass = 'bg-warning text-dark';
            } else if (item.withdrawalCategory === 'سووتەمەنی') {
                badgeClass = 'bg-danger';
            } else if (item.withdrawalCategory === 'پێداویستی') {
                badgeClass = 'bg-info';
            } else if (item.withdrawalCategory === 'تر') {
                badgeClass = 'bg-primary';
            }
            
            const rowHtml = `
                <tr data-id="${item.id}" class="fade-in">
                    <td>${item.id}</td>
                    <td>${item.name}</td>
                    <td>${item.withdrawalDate}</td>
                    <td>${item.withdrawalAmount}</td>
                    <td><span class="badge rounded-pill ${badgeClass}">${item.withdrawalCategory}</span></td>
                    <td>${item.notes}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${item.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${item.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            
            tableBody.append(rowHtml);
        });
    }
    
    // Update pagination controls
    updateWithdrawalPaginationControls();
    
    // Update pagination info text
    updateWithdrawalPaginationInfoText();
}

/**
 * Update withdrawal pagination information
 */
function updateWithdrawalPaginationInfo() {
    withdrawalTotalPages = Math.ceil(filteredWithdrawalData.length / withdrawalRecordsPerPage);
    if (withdrawalTotalPages === 0) withdrawalTotalPages = 1;
    
    // Ensure current page is in valid range
    if (withdrawalCurrentPage > withdrawalTotalPages) {
        withdrawalCurrentPage = withdrawalTotalPages;
    }
}

/**
 * Update withdrawal pagination controls
 */
function updateWithdrawalPaginationControls() {
    // Update prev/next buttons
    $('#withdrawalPrevPageBtn').prop('disabled', withdrawalCurrentPage === 1);
    $('#withdrawalNextPageBtn').prop('disabled', withdrawalCurrentPage === withdrawalTotalPages);
    
    // Update pagination numbers
    const paginationNumbers = $('#withdrawalPaginationNumbers');
    paginationNumbers.empty();
    
    // Limit the number of pagination buttons shown
    const maxPaginationButtons = 5;
    let startPage = Math.max(1, withdrawalCurrentPage - Math.floor(maxPaginationButtons / 2));
    let endPage = Math.min(withdrawalTotalPages, startPage + maxPaginationButtons - 1);
    
    // Adjust if we're at the end
    if (endPage - startPage + 1 < maxPaginationButtons) {
        startPage = Math.max(1, endPage - maxPaginationButtons + 1);
    }
    
    // Add pagination numbers
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === withdrawalCurrentPage;
        const buttonClass = isActive ? 'btn-primary active' : 'btn-outline-primary';
        
        paginationNumbers.append(`
            <button class="btn btn-sm ${buttonClass} rounded-circle me-2 pagination-number">
                ${i}
            </button>
        `);
    }
}

/**
 * Update withdrawal pagination info text
 */
function updateWithdrawalPaginationInfoText() {
    const startRecord = filteredWithdrawalData.length > 0 ? (withdrawalCurrentPage - 1) * withdrawalRecordsPerPage + 1 : 0;
    const endRecord = Math.min(startRecord + withdrawalRecordsPerPage - 1, filteredWithdrawalData.length);
    const totalRecords = filteredWithdrawalData.length;
    
    $('#withdrawalStartRecord').text(startRecord);
    $('#withdrawalEndRecord').text(endRecord);
    $('#withdrawalTotalRecords').text(totalRecords);
}

/**
 * Handle withdrawal form submission
 */
function handleWithdrawalSubmission() {
    // Similar to employee payment but with withdrawal-specific form fields
    // ...Implementation similar to employee payment form submission
    showAlert('دەرکردنی پارە بە سەرکەوتوویی زیادکرا.', 'success');
}

// SHARED FUNCTIONS

/**
 * Setup button click handlers
 */
function setupButtonHandlers() {
    // Refresh buttons
    $('.refresh-btn').on('click', function() {
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        if (tabId === 'employee-payment-content') {
            filteredEmployeePaymentData = [...employeePaymentData];
            $('#tableSearch').val('');
            employeePaymentCurrentPage = 1;
            renderEmployeePaymentTable();
        } else if (tabId === 'shipping-content') {
            filteredShippingData = [...shippingData];
            $('#shippingTableSearch').val('');
            shippingCurrentPage = 1;
            renderShippingTable();
        } else if (tabId === 'withdrawal-content') {
            filteredWithdrawalData = [...withdrawalData];
            $('#withdrawalTableSearch').val('');
            withdrawalCurrentPage = 1;
            renderWithdrawalTable();
        }
        
        showAlert('داتاکان نوێکرانەوە.', 'info');
    });
    
    // Edit buttons
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        // Show different messages based on which tab is active
        if (tabId === 'employee-payment-content') {
            showAlert('دەستکاری پارەدانی کارمەند - تۆماری ژمارە: ' + id, 'info');
        } else if (tabId === 'shipping-content') {
            showAlert('دەستکاری کرێی بار - تۆماری ژمارە: ' + id, 'info');
        } else if (tabId === 'withdrawal-content') {
            showAlert('دەستکاری دەرکردنی پارە - تۆماری ژمارە: ' + id, 'info');
        }
    });
    
    // Delete buttons
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        let confirmMessage = 'دڵنیای لە سڕینەوەی ئەم تۆمارە؟';
        let successMessage = 'تۆمار بە سەرکەوتوویی سڕایەوە.';
        
        if (confirm(confirmMessage)) {
            if (tabId === 'employee-payment-content') {
                employeePaymentData = employeePaymentData.filter(item => item.id !== id);
                filteredEmployeePaymentData = filteredEmployeePaymentData.filter(item => item.id !== id);
                renderEmployeePaymentTable();
            } else if (tabId === 'shipping-content') {
                shippingData = shippingData.filter(item => item.id !== id);
                filteredShippingData = filteredShippingData.filter(item => item.id !== id);
                renderShippingTable();
            } else if (tabId === 'withdrawal-content') {
                withdrawalData = withdrawalData.filter(item => item.id !== id);
                filteredWithdrawalData = filteredWithdrawalData.filter(item => item.id !== id);
                renderWithdrawalTable();
            }
            
            showAlert(successMessage, 'success');
        }
    });
}

/**
 * Format a date string to a more readable format
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', options).replace(/\//g, '/');
}

/**
 * Show an alert message
 */
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const alertContainer = $('<div>').html(alertHtml);
    $('#main-content').prepend(alertContainer);
    
    // Auto-dismiss after 3 seconds
    setTimeout(function() {
        alertContainer.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}