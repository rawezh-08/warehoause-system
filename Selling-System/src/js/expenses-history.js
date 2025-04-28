// Initialize default values for pagination
let employeeCurrentPage = 1;
let employeeRecordsPerPage = 10;
let employeeAllData = [];

let withdrawalCurrentPage = 1;
let withdrawalRecordsPerPage = 10;
let withdrawalAllData = [];

// jQuery Modal Plugin for Bootstrap 5
// This adds back the jQuery modal API that was removed in Bootstrap 5
(function($) {
    if (typeof bootstrap !== 'undefined') {
        $.fn.modal = function(options) {
            return this.each(function() {
                var $this = $(this);
                var modal = bootstrap.Modal.getInstance(this);
                
                if (options === 'show') {
                    if (!modal) modal = new bootstrap.Modal(this, { backdrop: true, keyboard: true });
                    modal.show();
                } else if (options === 'hide') {
                    if (modal) modal.hide();
                } else if (options === 'toggle') {
                    if (!modal) modal = new bootstrap.Modal(this, { backdrop: true, keyboard: true });
                    modal.toggle();
                }
            });
        };
    }
})(jQuery);

// Function to get default dates
function getDefaultDates() {
    const currentDate = new Date();
    const monthStart = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    return { today: currentDate, firstDay: monthStart };
}

// Wait for the DOM to be ready
$(document).ready(function() {
    const { today: defaultToday, firstDay: defaultFirstDay } = getDefaultDates();
    
    // Set default dates for employee payments
    if (!$('#employeePaymentStartDate').val()) {
        $('#employeePaymentStartDate').val(formatDate(defaultFirstDay));
    }
    if (!$('#employeePaymentEndDate').val()) {
        $('#employeePaymentEndDate').val(formatDate(defaultToday));
    }
    
    // Set default dates for withdrawals
    if (!$('#withdrawalStartDate').val()) {
        $('#withdrawalStartDate').val(formatDate(defaultFirstDay));
    }
    if (!$('#withdrawalEndDate').val()) {
        $('#withdrawalEndDate').val(formatDate(defaultToday));
    }

    // Apply filters on change
    $('.auto-filter').on('change', function() {
        if ($(this).closest('form').attr('id') === 'employeePaymentFilterForm') {
            applyEmployeePaymentFilter();
        } else if ($(this).closest('form').attr('id') === 'withdrawalFilterForm') {
            applyWithdrawalFilter();
        }
    });

    // Reset filters
    $('#employeePaymentResetFilter').on('click', function() {
        resetEmployeePaymentFilter();
    });

    $('#withdrawalResetFilter').on('click', function() {
        resetWithdrawalFilter();
    });

    // Table search functionality
    $('#employeeTableSearch').on('input', function() {
        const searchValue = $(this).val().toLowerCase();
        $('#employeeHistoryTable tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchValue) > -1);
        });
        updatePaginationInfo();
    });

    $('#withdrawalTableSearch').on('input', function() {
        const searchValue = $(this).val().toLowerCase();
        $('#withdrawalHistoryTable tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchValue) > -1);
        });
        updatePaginationInfo();
    });

    // Records per page change handlers
    $('#employeeRecordsPerPage').on('change', function() {
        employeeRecordsPerPage = parseInt($(this).val());
        employeeCurrentPage = 1; // Reset to first page when changing records per page
        displayEmployeeData();
    });

    $('#withdrawalRecordsPerPage').on('change', function() {
        withdrawalRecordsPerPage = parseInt($(this).val());
        withdrawalCurrentPage = 1; // Reset to first page when changing records per page
        displayWithdrawalData();
    });

    // Handle edit button click for employee payments
    $(document).on('click', '#employeeHistoryTable .edit-btn', function(e) {
        e.preventDefault();
        console.log('Edit button clicked'); // Debug log
        
        const paymentId = $(this).data('id');
        console.log('Payment ID:', paymentId); // Debug log
        
        // Show loading in modal
        $('#editEmployeePaymentForm').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">چاوەڕێ بکە...</p></div>');
        
        // Check if modal is already open
        const modalElement = document.getElementById('editEmployeePaymentModal');
        console.log('Modal element:', modalElement); // Debug log
        
        let modal;
        if (modalElement) {
            // Check if there's already an instance of the modal
            modal = bootstrap.Modal.getInstance(modalElement);
            
            if (!modal) {
                try {
                    // Create new modal instance if none exists
                    modal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    console.log('Modal instance created:', modal); // Debug log
                } catch (error) {
                    console.error('Error creating modal:', error); // Error log
                }
            }
            
            // Show the modal
            if (modal) {
                modal.show();
                console.log('Modal show called'); // Debug log
            }
        } else {
            console.error('Modal element not found'); // Error log
        }
        
        // Fetch payment data
        $.ajax({
            url: '../../process/get_employee_payment.php',
            method: 'GET',
            data: { id: paymentId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const payment = response.payment;
                    
                    // Restore form content first
                    restoreEmployeePaymentForm();
                    
                    // Fetch employees to populate the dropdown
                    $.ajax({
                        url: '../../process/get_employees.php',
                        method: 'GET',
                        dataType: 'json',
                        success: function(empResponse) {
                            if (empResponse.success) {
                                // Populate employee dropdown
                                const employeeSelect = $('#editEmployeePaymentName');
                                employeeSelect.empty();
                                employeeSelect.append('<option value="" disabled>کارمەند هەڵبژێرە</option>');
                                
                                empResponse.employees.forEach(function(employee) {
                                    employeeSelect.append(`<option value="${employee.id}">${employee.name}</option>`);
                                });
                                
                                // Set the selected employee
                                $('#editEmployeePaymentName').val(payment.employee_id);
                            }
                        },
                        error: function() {
                            console.error('Error fetching employees');
                        }
                    });
                    
                    // Fill the form with payment data
                    $('#editEmployeePaymentId').val(payment.id);
                    $('#editEmployeePaymentDate').val(payment.payment_date);
                    
                    // Format the amount with commas
                    const formattedAmount = parseFloat(payment.amount).toLocaleString('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    });
                    $('#editEmployeePaymentAmount').val(formattedAmount);
                    
                    $('#editEmployeePaymentType').val(payment.payment_type);
                    $('#editEmployeePaymentNotes').val(payment.notes);
                } else {
                    // Show error message
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیارییەکان',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Hide the modal after error message
                        closeModal('editEmployeePaymentModal');
                    });
                }
            },
            error: function() {
                // Show error message
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    // Hide the modal after error message
                    closeModal('editEmployeePaymentModal');
                });
            }
        });
    });
    
    // Handle edit button click for withdrawals
    $(document).on('click', '#withdrawalHistoryTable .edit-btn', function(e) {
        e.preventDefault();
        console.log('Withdrawal edit button clicked'); // Debug log
        
        const withdrawalId = $(this).data('id');
        console.log('Withdrawal ID:', withdrawalId); // Debug log
        
        // Show loading in modal
        $('#editWithdrawalForm').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">چاوەڕێ بکە...</p></div>');
        
        // Check if modal is already open
        const modalElement = document.getElementById('editWithdrawalModal');
        console.log('Withdrawal modal element:', modalElement); // Debug log
        
        let modal;
        if (modalElement) {
            // Check if there's already an instance of the modal
            modal = bootstrap.Modal.getInstance(modalElement);
            
            if (!modal) {
                try {
                    // Create new modal instance if none exists
                    modal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    console.log('Withdrawal modal instance created:', modal); // Debug log
                } catch (error) {
                    console.error('Error creating withdrawal modal:', error); // Error log
                }
            }
            
            // Show the modal
            if (modal) {
                modal.show();
                console.log('Withdrawal modal show called'); // Debug log
            }
        } else {
            console.error('Withdrawal modal element not found'); // Error log
        }
        
        // Fetch withdrawal data
        $.ajax({
            url: '../../process/get_expense.php',
            method: 'GET',
            data: { id: withdrawalId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const withdrawal = response.expense;
                    
                    // Restore form content first
                    restoreWithdrawalForm();
                    
                    // Fill the form with withdrawal data
                    $('#editWithdrawalId').val(withdrawal.id);
                    $('#editWithdrawalDate').val(withdrawal.expense_date);
                    
                    // Format the amount with commas
                    const formattedAmount = parseFloat(withdrawal.amount).toLocaleString('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    });
                    $('#editWithdrawalAmount').val(formattedAmount);
                    
                    $('#editWithdrawalNotes').val(withdrawal.notes);
                } else {
                    // Show error message
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیارییەکان',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Hide the modal after error message
                        closeModal('editWithdrawalModal');
                    });
                }
            },
            error: function() {
                // Show error message
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    // Hide the modal after error message
                    closeModal('editWithdrawalModal');
                });
            }
        });
    });

    // Next page button handler
    $('#employeeNextPageBtn').on('click', function() {
        if (employeeCurrentPage < Math.ceil(employeeAllData.length / employeeRecordsPerPage)) {
            employeeCurrentPage++;
            displayEmployeeData();
        }
    });
    
    $('#withdrawalNextPageBtn').on('click', function() {
        if (withdrawalCurrentPage < Math.ceil(withdrawalAllData.length / withdrawalRecordsPerPage)) {
            withdrawalCurrentPage++;
            displayWithdrawalData();
        }
    });
    
    // Previous page button handler
    $('#employeePrevPageBtn').on('click', function() {
        if (employeeCurrentPage > 1) {
            employeeCurrentPage--;
            displayEmployeeData();
        }
    });
    
    $('#withdrawalPrevPageBtn').on('click', function() {
        if (withdrawalCurrentPage > 1) {
            withdrawalCurrentPage--;
            displayWithdrawalData();
        }
    });

    // Save employee payment edit
    $('#saveEmployeePaymentEdit').on('click', function() {
        const paymentId = $('#editEmployeePaymentId').val();
        const employeeId = $('#editEmployeePaymentName').val();
        const paymentDate = $('#editEmployeePaymentDate').val();
        const amount = $('#editEmployeePaymentAmount').val().replace(/,/g, ''); // Remove commas
        const paymentType = $('#editEmployeePaymentType').val();
        const notes = $('#editEmployeePaymentNotes').val();
        
        // Validate form
        if (!employeeId || !paymentDate || !amount || !paymentType) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'تکایە هەموو خانە پێویستەکان پڕ بکەوە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            text: 'نوێکردنەوەی زانیاری',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Send update request
        $.ajax({
            url: '../../process/update_employee_payment.php',
            method: 'POST',
            data: {
                id: paymentId,
                employee_id: employeeId,
                payment_date: paymentDate,
                amount: amount,
                payment_type: paymentType,
                notes: notes
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close modal first
                    closeModal('editEmployeePaymentModal');
                        
                    // Refresh table with success message
                    applyEmployeePaymentFilter(true);
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاری',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
    
    // Save withdrawal edit
    $('#saveWithdrawalEdit').on('click', function() {
        const withdrawalId = $('#editWithdrawalId').val();
        const expenseDate = $('#editWithdrawalDate').val();
        const amount = $('#editWithdrawalAmount').val().replace(/,/g, ''); // Remove commas
        const notes = $('#editWithdrawalNotes').val();
        
        // Validate form
        if (!expenseDate || !amount) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'تکایە هەموو خانە پێویستەکان پڕ بکەوە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            text: 'نوێکردنەوەی زانیاری',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Send update request
        $.ajax({
            url: '../../process/update_expense.php',
            method: 'POST',
            data: {
                id: withdrawalId,
                name: 'Expense', // Use default value as we don't have name input
                expense_date: expenseDate,
                amount: amount,
                category: 'expense', // Use default value as we don't have category input
                notes: notes
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close modal first
                    closeModal('editWithdrawalModal');
                        
                    // Refresh table with success message
                    applyWithdrawalFilter(true);
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاری',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });

    // Delete employee payment
    $(document).on('click', '#employeeHistoryTable .delete-btn', function() {
        const paymentId = $(this).data('id');
        
        Swal.fire({
            title: 'دڵنیای؟',
            text: "ئەم کردارە ناگەڕێتەوە!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'بەڵێ، بیسڕەوە!',
            cancelButtonText: 'نەخێر، پاشگەزبوونەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX request to delete the payment
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: {
                        action: 'delete_employee_payment',
                        id: paymentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سڕایەوە!',
                                text: 'پارەدانەکە بە سەرکەوتوویی سڕایەوە.',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            });
                            
                            // Refresh the employee payments table
                            applyEmployeePaymentFilter();
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            }
        });
    });
    
    // Delete withdrawal
    $(document).on('click', '#withdrawalHistoryTable .delete-btn', function() {
        const withdrawalId = $(this).data('id');
        
        Swal.fire({
            title: 'دڵنیای؟',
            text: "ئەم کردارە ناگەڕێتەوە، دەرکردنی پارە دەسڕێتەوە!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'بەڵێ، بیسڕەوە!',
            cancelButtonText: 'نەخێر، پاشگەزبوونەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX request to delete the withdrawal
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: {
                        action: 'delete_withdrawal',
                        id: withdrawalId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سڕایەوە!',
                                text: 'دەرکردنی پارە بە سەرکەوتوویی سڕایەوە.',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            });
                            
                            // Refresh the withdrawals table
                            applyWithdrawalFilter();
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەدا',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            }
        });
    });

    // Set default dates and apply initial filter
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#employeePaymentStartDate').val(formatDate(firstDay));
    $('#employeePaymentEndDate').val(formatDate(today));
    
    // Apply initial filter to show all records
    applyEmployeePaymentFilter();
    
    // Apply initial filter for withdrawals as well
    $('#withdrawalStartDate').val(formatDate(firstDay));
    $('#withdrawalEndDate').val(formatDate(today));
    applyWithdrawalFilter();

    // Initialize pagination on page load
    displayEmployeeData();
    displayWithdrawalData();
});

// Function to display employee data with pagination
function displayEmployeeData() {
    const startIndex = (employeeCurrentPage - 1) * employeeRecordsPerPage;
    const endIndex = startIndex + employeeRecordsPerPage;
    const dataToShow = employeeAllData.slice(startIndex, endIndex);
    
    updateEmployeePaymentsTable(dataToShow);
    updatePaginationInfo('employee', employeeAllData.length, employeeCurrentPage, employeeRecordsPerPage, employeeAllData.length);
    
    // Update pagination controls
    $('#employeePrevPageBtn').prop('disabled', employeeCurrentPage === 1);
    $('#employeeNextPageBtn').prop('disabled', endIndex >= employeeAllData.length);
    
    // Generate pagination numbers
    generatePaginationNumbers('employee', employeeAllData.length, employeeCurrentPage, employeeRecordsPerPage);
}

// Function to display withdrawal data with pagination
function displayWithdrawalData() {
    const startIndex = (withdrawalCurrentPage - 1) * withdrawalRecordsPerPage;
    const endIndex = startIndex + withdrawalRecordsPerPage;
    const dataToShow = withdrawalAllData.slice(startIndex, endIndex);
    
    updateWithdrawalsTable(dataToShow);
    updatePaginationInfo('withdrawal', withdrawalAllData.length, withdrawalCurrentPage, withdrawalRecordsPerPage, withdrawalAllData.length);
    
    // Update pagination controls
    $('#withdrawalPrevPageBtn').prop('disabled', withdrawalCurrentPage === 1);
    $('#withdrawalNextPageBtn').prop('disabled', endIndex >= withdrawalAllData.length);
    
    // Generate pagination numbers
    generatePaginationNumbers('withdrawal', withdrawalAllData.length, withdrawalCurrentPage, withdrawalRecordsPerPage);
}

// Generate pagination numbers
function generatePaginationNumbers(prefix, totalRecords, currentPage, recordsPerPage) {
    const totalPages = Math.ceil(totalRecords / recordsPerPage);
    let html = '';
    
    // Show max 5 page numbers
    const maxPagesToShow = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = startPage + maxPagesToShow - 1;
    
    if (endPage > totalPages) {
        endPage = totalPages;
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2 page-number" data-page="${i}">${i}</button>`;
    }
    
    $(`#${prefix}PaginationNumbers`).html(html);
    
    // Add event listeners to new pagination buttons
    $(`.page-number`).on('click', function() {
        const page = parseInt($(this).data('page'));
        if ($(this).closest(`#${prefix}PaginationNumbers`).length) {
            if (prefix === 'employee') {
                employeeCurrentPage = page;
                displayEmployeeData();
            } else if (prefix === 'withdrawal') {
                withdrawalCurrentPage = page;
                displayWithdrawalData();
            }
        }
    });
}

// Update employee payments table
function updateEmployeePaymentsTable(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="7" class="text-center">هیچ پارەدانێک نەدۆزرایەوە</td></tr>';
    } else {
        data.forEach(function(payment, index) {
            const paymentTypeClass = payment.payment_type === 'salary' ? 'bg-success' : 
                                   (payment.payment_type === 'bonus' ? 'bg-warning' : 'bg-info');
            
            const paymentTypeText = payment.payment_type === 'salary' ? 'مووچە' : 
                                  (payment.payment_type === 'bonus' ? 'پاداشت' : 'کاتژمێری زیادە');
            
            // Format date to Y/m/d
            const dateObj = new Date(payment.payment_date);
            const formattedDate = dateObj.getFullYear() + '/' + 
                                 String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                 String(dateObj.getDate()).padStart(2, '0');
            
            // Calculate the actual row number based on the current page and records per page
            const actualIndex = ((employeeCurrentPage - 1) * employeeRecordsPerPage) + index + 1;
            
            html += `
                <tr data-id="${payment.id}">
                    <td>${actualIndex}</td>
                    <td>${payment.employee_name || 'N/A'}</td>
                    <td>${formattedDate}</td>
                    <td>${payment.amount ? new Intl.NumberFormat().format(payment.amount) + ' د.ع' : '0 د.ع'}</td>
                    <td>
                        <span class="badge rounded-pill ${paymentTypeClass}">
                            ${paymentTypeText}
                        </span>
                    </td>
                    <td>${payment.notes || ''}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${payment.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${payment.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#employeeHistoryTable tbody').html(html);
}

// Update withdrawals table
function updateWithdrawalsTable(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="5" class="text-center">هیچ دەرکردنێکی پارە نەدۆزرایەوە</td></tr>';
    } else {
        data.forEach(function(withdrawal, index) {
            // Format date to Y/m/d
            const dateObj = new Date(withdrawal.expense_date);
            const formattedDate = dateObj.getFullYear() + '/' + 
                                 String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                 String(dateObj.getDate()).padStart(2, '0');
            
            // Calculate the actual row number based on the current page and records per page
            const actualIndex = ((withdrawalCurrentPage - 1) * withdrawalRecordsPerPage) + index + 1;
            
            html += `
                <tr data-id="${withdrawal.id}">
                    <td>${actualIndex}</td>
                    <td>${formattedDate}</td>
                    <td>${withdrawal.amount ? new Intl.NumberFormat().format(withdrawal.amount) + ' د.ع' : '0 د.ع'}</td>
                    <td>${withdrawal.notes || ''}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${withdrawal.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${withdrawal.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#withdrawalHistoryTable tbody').html(html);
}

// Apply filter for employee payments
function applyEmployeePaymentFilter(showSuccessMessage = false) {
    const startDate = $('#employeePaymentStartDate').val();
    const endDate = $('#employeePaymentEndDate').val();
    const employeeName = $('#employeePaymentName').val();
    
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: {
            action: 'filter',
            type: 'employee_payments',
            start_date: startDate,
            end_date: endDate,
            employee_name: employeeName || '' // Send empty string if no employee selected
        },
        dataType: 'json',
        beforeSend: function() {
            // Show loading state
            $('#employeeHistoryTable tbody').html('<tr><td colspan="7" class="text-center">جاوەڕێ بکە...</td></tr>');
        },
        success: function(response) {
            if (response.success) {
                // Update global data array
                employeeAllData = response.data;
                employeeCurrentPage = 1; // Reset to first page
                
                // Display data with pagination
                displayEmployeeData();
                
                // Show success message if requested
                if (showSuccessMessage) {
                    Swal.fire({
                        title: 'سەرکەوتوو بوو!',
                        text: 'پارەدان بە کارمەند بە سەرکەوتوویی نوێ کرایەوە',
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    });
                }
            } else {
                Swal.fire({
                    title: 'هەڵە!',
                    text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", xhr.responseText);
            Swal.fire({
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        }
    });
}

// Apply filter for withdrawals
function applyWithdrawalFilter(showSuccessMessage = false) {
    const startDate = $('#withdrawalStartDate').val();
    const endDate = $('#withdrawalEndDate').val();
    
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: {
            action: 'filter',
            type: 'withdrawals',
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        beforeSend: function() {
            // Show loading state
            $('#withdrawalHistoryTable tbody').html('<tr><td colspan="5" class="text-center">جاوەڕێ بکە...</td></tr>');
        },
        success: function(response) {
            if (response.success) {
                // Update global data array
                withdrawalAllData = response.data;
                withdrawalCurrentPage = 1; // Reset to first page
                
                // Display data with pagination
                displayWithdrawalData();
                
                // Show success message if requested
                if (showSuccessMessage) {
                    Swal.fire({
                        title: 'سەرکەوتوو بوو!',
                        text: 'دەرکردنی پارە بە سەرکەوتوویی نوێ کرایەوە',
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    });
                }
            } else {
                Swal.fire({
                    title: 'هەڵە!',
                    text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", xhr.responseText);
            Swal.fire({
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        }
    });
}

// Reset employee payment filter
function resetEmployeePaymentFilter() {
    // Reset form inputs
    const { today: defaultToday, firstDay: defaultFirstDay } = getDefaultDates();
    
    $('#employeePaymentStartDate').val(formatDate(defaultFirstDay));
    $('#employeePaymentEndDate').val(formatDate(defaultToday));
    $('#employeePaymentName').val(''); // Reset to empty value
    
    // Apply filter with reset values
    applyEmployeePaymentFilter();
}

// Reset withdrawal filter
function resetWithdrawalFilter() {
    // Reset form inputs
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#withdrawalStartDate').val(formatDate(firstDay));
    $('#withdrawalEndDate').val(formatDate(today));
    
    // Apply filter with reset values
    applyWithdrawalFilter();
}

// Helper function to format date as YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Update pagination info
function updatePaginationInfo(prefix, totalRecords, currentPage, recordsPerPage, filteredRecords) {
    const startRecord = totalRecords > 0 ? (currentPage - 1) * recordsPerPage + 1 : 0;
    const endRecord = Math.min(startRecord + recordsPerPage - 1, filteredRecords);
    
    $(`#${prefix}StartRecord`).text(startRecord);
    $(`#${prefix}EndRecord`).text(endRecord);
    $(`#${prefix}TotalRecords`).text(filteredRecords);
}

// Function to safely close a Bootstrap modal
function closeModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
            // Ensure the backdrop is removed
            setTimeout(function() {
                if ($('.modal-backdrop').length > 0) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                }
            }, 150); // Small delay to allow the modal to hide first
        }
    }
}

// Restore employee payment form content
function restoreEmployeePaymentForm() {
    $('#editEmployeePaymentForm').html(`
        <input type="hidden" id="editEmployeePaymentId">
        <div class="mb-3">
            <label for="editEmployeePaymentName" class="form-label">ناوی کارمەند</label>
            <select id="editEmployeePaymentName" class="form-select" required>
                <option value="" selected disabled>کارمەند هەڵبژێرە</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="editEmployeePaymentDate" class="form-label">بەروار</label>
            <input type="date" id="editEmployeePaymentDate" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="editEmployeePaymentAmount" class="form-label">بڕی پارە</label>
            <div class="input-group">
                <input type="text" id="editEmployeePaymentAmount" class="form-control number-format" required>
                <span class="input-group-text">د.ع</span>
            </div>
        </div>
        <div class="mb-3">
            <label for="editEmployeePaymentType" class="form-label">جۆری پارەدان</label>
            <select id="editEmployeePaymentType" class="form-select" required>
                <option value="" selected disabled>جۆری پارەدان</option>
                <option value="salary">مووچە</option>
                <option value="bonus">پاداشت</option>
                <option value="overtime">کاتژمێری زیادە</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="editEmployeePaymentNotes" class="form-label">تێبینی</label>
            <textarea id="editEmployeePaymentNotes" class="form-control" rows="3"></textarea>
        </div>
    `);
    
    // Initialize number formatting
    initNumberFormat();
}

// Restore withdrawal form content
function restoreWithdrawalForm() {
    $('#editWithdrawalForm').html(`
        <input type="hidden" id="editWithdrawalId">
        <div class="mb-3">
            <label for="editWithdrawalDate" class="form-label">بەروار</label>
            <input type="date" id="editWithdrawalDate" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="editWithdrawalAmount" class="form-label">بڕی پارە</label>
            <div class="input-group">
                <input type="text" id="editWithdrawalAmount" class="form-control number-format" required>
                <span class="input-group-text">د.ع</span>
            </div>
        </div>
        <div class="mb-3">
            <label for="editWithdrawalNotes" class="form-label">تێبینی</label>
            <textarea id="editWithdrawalNotes" class="form-control" rows="3"></textarea>
        </div>
    `);
    
    // Initialize number formatting
    initNumberFormat();
}

// Function to initialize number formatting for inputs
function initNumberFormat() {
    const formatNumberInputs = document.querySelectorAll('.number-format');
    
    formatNumberInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            // Remove non-numeric characters except decimal point
            let value = this.value.replace(/[^\d.]/g, '');
            
            // Ensure only one decimal point
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Add thousand separators
            if (value) {
                const decimalParts = value.split('.');
                decimalParts[0] = decimalParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                value = decimalParts.join('.');
            }
            
            this.value = value;
        });
    });
} 