// Wait for the DOM to be ready
$(document).ready(function() {
    // Initialize date inputs with default values
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    if (!$('#employeePaymentStartDate').val()) {
        $('#employeePaymentStartDate').val(formatDate(firstDay));
    }
    
    if (!$('#employeePaymentEndDate').val()) {
        $('#employeePaymentEndDate').val(formatDate(today));
    }
    
    if (!$('#withdrawalStartDate').val()) {
        $('#withdrawalStartDate').val(formatDate(firstDay));
    }
    
    if (!$('#withdrawalEndDate').val()) {
        $('#withdrawalEndDate').val(formatDate(today));
    }
    
    // Helper function to format date as YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Apply filter for employee payments
    function applyEmployeePaymentFilter() {
        const startDate = $('#employeePaymentStartDate').val();
        const endDate = $('#employeePaymentEndDate').val();
        const employeeName = $('#employeePaymentName').val();
        
        $.ajax({
            url: 'expensesHistory.php',
            method: 'POST',
            data: {
                action: 'filter',
                type: 'employee_payments',
                start_date: startDate,
                end_date: endDate,
                employee_name: employeeName
            },
            dataType: 'json',
            beforeSend: function() {
                // Show loading state
                $('#employeeHistoryTable tbody').html('<tr><td colspan="7" class="text-center">جاوەڕێ بکە...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    // Update table with filtered data
                    updateEmployeePaymentsTable(response.data);
                    
                    // Update statistics
                    updateEmployeePaymentsStats(response.stats);
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
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
                                        (payment.payment_type === 'bonus' ? 'bg-warning' : 
                                        (payment.payment_type === 'overtime' ? 'bg-info' : 'bg-secondary'));
                
                const paymentTypeText = payment.payment_type === 'salary' ? 'مووچە' : 
                                       (payment.payment_type === 'bonus' ? 'پاداشت' : 
                                       (payment.payment_type === 'overtime' ? 'کاتژمێری زیادە' : 'جۆری تر'));
                
                // Format date to Y/m/d
                const dateObj = new Date(payment.payment_date);
                const formattedDate = dateObj.getFullYear() + '/' + 
                                     String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                     String(dateObj.getDate()).padStart(2, '0');
                
                // Format amount with thousand separators
                const formattedAmount = new Intl.NumberFormat().format(payment.amount) + ' د.ع';
                
                html += `
                    <tr data-id="${payment.id}" data-employee-id="${payment.employee_id}">
                        <td>${index + 1}</td>
                        <td>${payment.employee_name || 'N/A'}</td>
                        <td>${formattedDate}</td>
                        <td>${formattedAmount}</td>
                        <td>
                            <span class="badge rounded-pill ${paymentTypeClass}">
                                ${paymentTypeText}
                            </span>
                        </td>
                        <td>${payment.notes || ''}</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${payment.id}" data-bs-toggle="modal" data-bs-target="#editEmployeePaymentModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${payment.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${payment.id}">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        $('#employeeHistoryTable tbody').html(html);
        
        // Update pagination info
        updatePaginationInfo('employee', data.length, 1, data.length, data.length);
    }
    
    // Update employee payments statistics
    function updateEmployeePaymentsStats(stats) {
        // Format numbers with thousand separators
        const totalAmount = new Intl.NumberFormat().format(stats.total_amount || 0) + ' د.ع';
        const totalSalary = new Intl.NumberFormat().format(stats.total_salary || 0) + ' د.ع';
        const totalBonus = new Intl.NumberFormat().format(stats.total_bonus || 0) + ' د.ع';
        const totalOvertime = new Intl.NumberFormat().format(stats.total_overtime || 0) + ' د.ع';
        
        // Update statistics in the UI
        $('.card-title:contains("کۆی پارەدان")').text(totalAmount);
        $('.card-text:contains("پارەدان")').text(stats.total_payments + ' پارەدان');
        
        $('.card-title:contains("مووچە")').text(totalSalary);
        $('.card-title:contains("پاداشت")').text(totalBonus);
        $('.card-title:contains("کاتژمێری زیادە")').text(totalOvertime);
    }
    
    // Apply filter for withdrawals
    function applyWithdrawalFilter() {
        const startDate = $('#withdrawalStartDate').val();
        const endDate = $('#withdrawalEndDate').val();
        const name = $('#withdrawalName').val();
        
        $.ajax({
            url: 'expensesHistory.php',
            method: 'POST',
            data: {
                action: 'filter',
                type: 'withdrawals',
                start_date: startDate,
                end_date: endDate,
                name: name
            },
            dataType: 'json',
            beforeSend: function() {
                // Show loading state
                $('#withdrawalHistoryTable tbody').html('<tr><td colspan="7" class="text-center">جاوەڕێ بکە...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    // Update table with filtered data
                    updateWithdrawalsTable(response.data);
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی فلتەرکردندا',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }
    
    // Update withdrawals table
    function updateWithdrawalsTable(data) {
        let html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="5" class="text-center">هیچ دەرکردنێکی پارە نەدۆزرایەوە</td></tr>';
        } else {
            data.forEach(function(expense, index) {
                // Format date to Y/m/d
                const dateObj = new Date(expense.expense_date);
                const formattedDate = dateObj.getFullYear() + '/' + 
                                     String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + 
                                     String(dateObj.getDate()).padStart(2, '0');
                
                // Format amount with thousand separators
                const formattedAmount = new Intl.NumberFormat().format(expense.amount) + ' د.ع';
                
                html += `
                    <tr data-id="${expense.id}">
                        <td>${index + 1}</td>
                        <td>${formattedDate}</td>
                        <td>${formattedAmount}</td>
                        <td>${expense.notes || ''}</td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${expense.id}" data-bs-toggle="modal" data-bs-target="#editWithdrawalModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${expense.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle print-btn" data-id="${expense.id}">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        $('#withdrawalHistoryTable tbody').html(html);
        
        // Update pagination info
        updatePaginationInfo('withdrawal', data.length, 1, data.length, data.length);
    }
    
    // Helper function to update pagination info
    function updatePaginationInfo(prefix, totalRecords, currentPage, recordsPerPage, filteredRecords) {
        const startRecord = filteredRecords > 0 ? (currentPage - 1) * recordsPerPage + 1 : 0;
        const endRecord = Math.min(startRecord + recordsPerPage - 1, filteredRecords);
        
        $(`#${prefix}StartRecord`).text(startRecord);
        $(`#${prefix}EndRecord`).text(endRecord);
        $(`#${prefix}TotalRecords`).text(filteredRecords);
    }
    
    // Event listeners for filters
    $('.auto-filter').on('change', function() {
        // Determine which tab is active
        const activeTab = $('.expenses-tabs .nav-link.active').attr('id');
        
        if (activeTab === 'employee-payment-tab') {
            applyEmployeePaymentFilter();
        } else if (activeTab === 'withdrawal-tab') {
            applyWithdrawalFilter();
        }
    });
    
    // Reset filters
    $('#employeePaymentResetFilter').on('click', function() {
        $('#employeePaymentStartDate').val(formatDate(firstDay));
        $('#employeePaymentEndDate').val(formatDate(today));
        $('#employeePaymentName').val('').trigger('change');
        
        // Apply default filters
        applyEmployeePaymentFilter();
    });
    
    $('#withdrawalResetFilter').on('click', function() {
        $('#withdrawalStartDate').val(formatDate(firstDay));
        $('#withdrawalEndDate').val(formatDate(today));
        $('#withdrawalName').val('').trigger('change');
        
        // Apply default filters
        applyWithdrawalFilter();
    });
    
    // Tab change event
    $('#expensesTabs button').on('shown.bs.tab', function (e) {
        const targetId = $(e.target).attr('id');
        
        // If switching to employee payments tab, refresh data
        if (targetId === 'employee-payment-tab') {
            applyEmployeePaymentFilter();
        } 
        // If switching to withdrawals tab, refresh data
        else if (targetId === 'withdrawal-tab') {
            applyWithdrawalFilter();
        }
    });
    
    // Refresh button click events
    $('.refresh-btn').on('click', function() {
        const activeTab = $('.expenses-tabs .nav-link.active').attr('id');
        
        if (activeTab === 'employee-payment-tab') {
            applyEmployeePaymentFilter();
        } else if (activeTab === 'withdrawal-tab') {
            applyWithdrawalFilter();
        }
    });

    // Handle edit button click for employee payments
    $(document).on('click', '#employeeHistoryTable .edit-btn', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        // Get data from row
        const employeeId = row.data('employee-id');
        const employeeName = row.find('td:eq(1)').text().trim();
        const paymentDate = row.find('td:eq(2)').text().trim();
        const amount = row.find('td:eq(3)').text().trim().replace(/[^\d]/g, '');
        const paymentType = row.find('td:eq(4) .badge').text().trim();
        const notes = row.find('td:eq(5)').text().trim();
        
        // Populate modal fields
        $('#editEmployeePaymentId').val(id);
        $('#editEmployeePaymentName').val(employeeId);
        
        // Convert date from Y/m/d to Y-m-d format
        const dateParts = paymentDate.split('/');
        const formattedDate = `${dateParts[0]}-${dateParts[1].padStart(2, '0')}-${dateParts[2].padStart(2, '0')}`;
        $('#editEmployeePaymentDate').val(formattedDate);
        
        $('#editEmployeePaymentAmount').val(amount);
        
        // Set payment type based on the badge text
        let paymentTypeValue = '';
        if (paymentType === 'مووچە') paymentTypeValue = 'salary';
        else if (paymentType === 'پاداشت') paymentTypeValue = 'bonus';
        else if (paymentType === 'کاتژمێری زیادە') paymentTypeValue = 'overtime';
        $('#editEmployeePaymentType').val(paymentTypeValue);
        
        $('#editEmployeePaymentNotes').val(notes);
    });
    
    // Handle save button click for employee payment edit
    $('#saveEmployeePaymentEdit').on('click', function() {
        // Get form data
        const id = $('#editEmployeePaymentId').val();
        const employeeId = $('#editEmployeePaymentName').val();
        const paymentDate = $('#editEmployeePaymentDate').val();
        const amount = $('#editEmployeePaymentAmount').val();
        const paymentType = $('#editEmployeePaymentType').val();
        const notes = $('#editEmployeePaymentNotes').val();
        
        // Validate form
        if (!employeeId || !paymentDate || !amount || !paymentType) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'تکایە هەموو خانە پێویستەکان پڕبکەوە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        // Send AJAX request to update payment
        $.ajax({
            url: 'expensesHistory.php',
            method: 'POST',
            data: {
                action: 'update_employee_payment',
                id: id,
                employee_id: employeeId,
                payment_date: paymentDate,
                amount: amount,
                payment_type: paymentType,
                notes: notes
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'سەرکەوتوو بوو!',
                        text: 'زانیاری پارەدان نوێکرایەوە',
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Close the modal
                        $('#editEmployeePaymentModal').modal('hide');
                        
                        // Refresh the table
                        applyEmployeePaymentFilter();
                    });
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەدا',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەدا',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
}); 