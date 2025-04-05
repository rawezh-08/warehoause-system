$(document).ready(function() {
    // Initialize DataTable
    const debtsTable = $('#debtsTable').DataTable({
        responsive: true,
        dom: '<"top"fl>rt<"bottom"ip>',
        language: {
            search: "گەڕان:",
            lengthMenu: "پیشاندانی _MENU_ تۆماری",
            info: "پیشاندانی _START_ بۆ _END_ لە _TOTAL_ تۆماری",
            paginate: {
                first: "یەکەم",
                last: "کۆتایی",
                next: "دواتر",
                previous: "پێشتر"
            }
        },
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 5, 9] },
            { responsivePriority: 2, targets: [3, 8] }
        ]
    });

    // Initialize current date for debt date and payment date inputs
    $('#debtDate, #paymentDate').val(new Date().toISOString().split('T')[0]);

    // Search functionality
    $('#searchBox').on('keyup', function() {
        debtsTable.search($(this).val()).draw();
    });

    // Date filter functionality
    $('#startDate, #endDate').on('change', function() {
        applyDateFilter();
    });

    // Refresh button click handler
    $('#refreshBtn').on('click', function() {
        // For demo purposes, just reload the page
        location.reload();
    });

    // View debt details
    $(document).on('click', '.view-debt', function() {
        const debtId = $(this).data('id');
        
        // In a real application, you would fetch the debt details from the server
        // For demo purposes, we'll just show the modal with sample data
        $('#viewDebtModal').modal('show');
    });

    // Add payment functionality
    $(document).on('click', '.pay-debt', function() {
        const debtId = $(this).data('id');
        
        // Store the debt ID for use when saving the payment
        $('#paymentModal').data('debtId', debtId);
        
        // Show the payment modal
        $('#paymentModal').modal('show');
    });

    // Payment from view modal
    $('#makePaymentBtn').on('click', function() {
        $('#viewDebtModal').modal('hide');
        $('#paymentModal').modal('show');
    });

    // Edit debt functionality
    $(document).on('click', '.edit-debt', function() {
        const debtId = $(this).data('id');
        
        // In a real application, you would fetch the debt details and populate the form
        // For demo purposes, we'll just show a message
        Swal.fire({
            title: 'دەستکاریکردنی قەرز',
            text: `دەستکاریکردنی قەرزی ژمارە ${debtId}`,
            icon: 'info',
            confirmButtonText: 'باشە'
        });
    });

    // Calculate remaining amount when total or paid amount changes
    $('#totalAmount, #paidAmount').on('input', function() {
        calculateRemainingAmount();
    });

    // Save debt button click handler
    $('#saveDebtBtn').on('click', function() {
        if (validateDebtForm()) {
            // For demo purposes, just show a success message and close the modal
            $('#addDebtModal').modal('hide');
            
            Swal.fire({
                title: 'سەرکەوتوو',
                text: 'قەرز بە سەرکەوتوویی زیادکرا',
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then(() => {
                // In a real application, you would reload the data from the server
                // For demo purposes, just reload the page
                location.reload();
            });
        }
    });

    // Save payment button click handler
    $('#savePaymentBtn').on('click', function() {
        if (validatePaymentForm()) {
            // Get the debt ID
            const debtId = $('#paymentModal').data('debtId');
            
            // For demo purposes, just show a success message and close the modal
            $('#paymentModal').modal('hide');
            
            Swal.fire({
                title: 'سەرکەوتوو',
                text: 'پارەدان بە سەرکەوتوویی زیادکرا',
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then(() => {
                // In a real application, you would reload the data from the server
                // For demo purposes, just reload the page
                location.reload();
            });
        }
    });

    // Print debt details
    $('#printDebtDetailsBtn').on('click', function() {
        printDebtDetails();
    });
});

// Function to apply date filter
function applyDateFilter() {
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const dateColumn = 2; // Date column index
        
        // If no date filter is set, show all rows
        if (!startDate && !endDate) {
            return true;
        }
        
        // Parse the date from the table
        const rowDate = new Date(data[dateColumn]);
        
        // Check if the date is within the range
        if (startDate && !endDate) {
            return rowDate >= new Date(startDate);
        } else if (!startDate && endDate) {
            return rowDate <= new Date(endDate);
        } else {
            return rowDate >= new Date(startDate) && rowDate <= new Date(endDate);
        }
    });
    
    // Redraw the table
    $('#debtsTable').DataTable().draw();
    
    // Remove the custom search function after it's done
    $.fn.dataTable.ext.search.pop();
}

// Function to calculate remaining amount
function calculateRemainingAmount() {
    const totalAmount = parseFloat($('#totalAmount').val()) || 0;
    const paidAmount = parseFloat($('#paidAmount').val()) || 0;
    const remainingAmount = totalAmount - paidAmount;
    
    $('#remainingAmount').val(remainingAmount >= 0 ? remainingAmount : 0);
}

// Function to validate debt form
function validateDebtForm() {
    // Check if all required fields are filled
    if (!$('#customerSelect').val()) {
        showError('تکایە کڕیار هەڵبژێرە');
        return false;
    }
    
    if (!$('#receiptNumber').val()) {
        showError('تکایە ژمارەی پسوڵە بنووسە');
        return false;
    }
    
    if (!$('#debtDate').val()) {
        showError('تکایە بەرواری قەرز دیاریبکە');
        return false;
    }
    
    if (!$('#repaymentDate').val()) {
        showError('تکایە بەرواری دانەوە دیاریبکە');
        return false;
    }
    
    if (!$('#totalAmount').val() || parseFloat($('#totalAmount').val()) <= 0) {
        showError('تکایە بڕی پارەی دروست بنووسە');
        return false;
    }
    
    if (!$('#paidAmount').val()) {
        showError('تکایە بڕی پارەی دراو بنووسە');
        return false;
    }
    
    if (!$('#creditLimit').val() || parseFloat($('#creditLimit').val()) <= 0) {
        showError('تکایە سنوری قەرز دیاریبکە');
        return false;
    }
    
    // Check if the repayment date is after the debt date
    const debtDate = new Date($('#debtDate').val());
    const repaymentDate = new Date($('#repaymentDate').val());
    
    if (repaymentDate <= debtDate) {
        showError('بەرواری دانەوە دەبێت دوای بەرواری قەرز بێت');
        return false;
    }
    
    // Additional validation can be added here
    
    return true;
}

// Function to validate payment form
function validatePaymentForm() {
    // Check if all required fields are filled
    if (!$('#paymentDate').val()) {
        showError('تکایە بەروار دیاریبکە');
        return false;
    }
    
    if (!$('#paymentAmount').val() || parseFloat($('#paymentAmount').val()) <= 0) {
        showError('تکایە بڕی پارەی دروست بنووسە');
        return false;
    }
    
    if (!$('#paymentMethod').val()) {
        showError('تکایە شێوازی پارەدان هەڵبژێرە');
        return false;
    }
    
    return true;
}

// Function to show error message
function showError(message) {
    Swal.fire({
        title: 'هەڵە',
        text: message,
        icon: 'error',
        confirmButtonText: 'باشە'
    });
}

// Function to print debt details
function printDebtDetails() {
    // Get the debt details
    const customerName = $('#viewCustomerName').text();
    const receiptNumber = $('#viewReceiptNumber').text();
    const debtDate = $('#viewDebtDate').text();
    const repaymentDate = $('#viewRepaymentDate').text();
    const totalAmount = $('#viewTotalAmount').text();
    const paidAmount = $('#viewPaidAmount').text();
    const remainingAmount = $('#viewRemainingAmount').text();
    const creditLimit = $('#viewCreditLimit').text();
    const notes = $('#viewNotes').text();
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Write the HTML content
    printWindow.document.write(`
        <!DOCTYPE html>
        <html dir="rtl">
        <head>
            <title>وردەکاری قەرز</title>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    direction: rtl;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .detail-row {
                    display: flex;
                    margin-bottom: 10px;
                }
                .detail-label {
                    font-weight: bold;
                    width: 150px;
                }
                .detail-value {
                    flex: 1;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #777;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>وردەکاری قەرز</h2>
                <p>بەرواری چاپکردن: ${new Date().toLocaleDateString('ku-IQ')}</p>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">ناوی کڕیار:</div>
                <div class="detail-value">${customerName}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">ژمارەی پسوڵە:</div>
                <div class="detail-value">${receiptNumber}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">بەرواری قەرز:</div>
                <div class="detail-value">${debtDate}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">بەرواری دانەوە:</div>
                <div class="detail-value">${repaymentDate}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">بڕی پارە:</div>
                <div class="detail-value">${totalAmount}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">بڕی پارەی دراو:</div>
                <div class="detail-value">${paidAmount}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">بڕی پارەی ماوە:</div>
                <div class="detail-value">${remainingAmount}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">سنوری قەرز:</div>
                <div class="detail-value">${creditLimit}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">تێبینیەکان:</div>
                <div class="detail-value">${notes}</div>
            </div>
            
            <h3>مێژووی پارەدان</h3>
            <table>
                <thead>
                    <tr>
                        <th>بەروار</th>
                        <th>بڕی پارە</th>
                        <th>شێوازی پارەدان</th>
                        <th>تێبینی</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2023-10-15</td>
                        <td>500,000 د.ع</td>
                        <td>نەختینە</td>
                        <td>پارەی سەرەتا</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="footer">
                <p>سیستەمی فرۆشتن - قەرزی کڕیاران</p>
            </div>
        </body>
        </html>
    `);
    
    // Close the document for writing
    printWindow.document.close();
    
    // Focus and print the window
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
    }, 500);
} 