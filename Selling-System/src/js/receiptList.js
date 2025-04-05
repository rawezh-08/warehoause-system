$(document).ready(function() {
    // Initialize DataTables
    const sellingTable = initializeDataTable('#sellingTable');
    const buyingTable = initializeDataTable('#buyingTable');
    const wastingTable = initializeDataTable('#wastingTable');

    // Load initial data
    loadSellingReceipts();
    
    // Handle tab switching to load relevant data
    $('#receiptTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href");
        if (target === '#selling') {
            loadSellingReceipts();
        } else if (target === '#buying') {
            loadBuyingReceipts();
        } else if (target === '#wasting') {
            loadWastingReceipts();
        }
    });

    // Refresh button click handler
    $('#refreshBtn').on('click', function() {
        const activeTab = $('.nav-tabs .active').attr('id');
        if (activeTab === 'selling-tab') {
            loadSellingReceipts();
        } else if (activeTab === 'buying-tab') {
            loadBuyingReceipts();
        } else if (activeTab === 'wasting-tab') {
            loadWastingReceipts();
        }
    });

    // Filter handlers for selling receipts
    $('#sellingSearchBox').on('keyup', function() {
        sellingTable.search($(this).val()).draw();
    });

    $('#sellingStartDate, #sellingEndDate').on('change', function() {
        applyDateFilter(sellingTable);
    });

    // Filter handlers for buying receipts
    $('#buyingSearchBox').on('keyup', function() {
        buyingTable.search($(this).val()).draw();
    });

    $('#buyingStartDate, #buyingEndDate').on('change', function() {
        applyDateFilter(buyingTable);
    });

    // Filter handlers for wasting receipts
    $('#wastingSearchBox').on('keyup', function() {
        wastingTable.search($(this).val()).draw();
    });

    $('#wastingStartDate, #wastingEndDate').on('change', function() {
        applyDateFilter(wastingTable);
    });

    // View receipt details
    $(document).on('click', '.view-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        // Load receipt details
        loadReceiptDetails(receiptId, receiptType);
    });

    // Edit receipt
    $(document).on('click', '.edit-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        // Redirect to edit page
        window.location.href = `addReceipt.php?id=${receiptId}&type=${receiptType}&edit=true`;
    });

    // Edit button in modal
    $('#editReceiptBtn').on('click', function() {
        const receiptId = $('#modalReceiptNumber').text();
        const receiptType = $('#receiptDetailsModal').data('receiptType');
        
        // Redirect to edit page
        window.location.href = `addReceipt.php?id=${receiptId}&type=${receiptType}&edit=true`;
    });

    // Print receipt
    $(document).on('click', '.print-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        printReceipt(receiptId, receiptType);
    });

    // Print button in modal
    $('#printReceiptBtn').on('click', function() {
        const receiptId = $('#modalReceiptNumber').text();
        const receiptType = $('#receiptDetailsModal').data('receiptType');
        
        printReceipt(receiptId, receiptType);
    });

    // Delete receipt confirmation
    $(document).on('click', '.delete-receipt', function() {
        const receiptId = $(this).data('id');
        const receiptType = $(this).data('type');
        
        // Set values in the delete confirmation modal
        $('#deleteReceiptNumber').text(receiptId);
        $('#deleteReceiptModal').data('receiptId', receiptId);
        $('#deleteReceiptModal').data('receiptType', receiptType);
        
        // Show the delete confirmation modal
        $('#deleteReceiptModal').modal('show');
    });

    // Delete receipt confirm button
    $('#confirmDeleteBtn').on('click', function() {
        const receiptId = $('#deleteReceiptModal').data('receiptId');
        const receiptType = $('#deleteReceiptModal').data('receiptType');
        
        // Delete the receipt
        deleteReceipt(receiptId, receiptType);
    });
});

// Function to initialize DataTables
function initializeDataTable(tableId) {
    return $(tableId).DataTable({
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
            { responsivePriority: 1, targets: [0, 1, 6] },
            { responsivePriority: 2, targets: [2, 4] }
        ]
    });
}

// Function to load selling receipts
function loadSellingReceipts() {
    // Show loading spinner
    showLoadingSpinner('#sellingTable');
    
    // AJAX request to get selling receipts
    $.ajax({
        url: 'process/get_receipts.php',
        type: 'POST',
        data: { type: 'selling' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateSellingTable(response.data);
            } else {
                showErrorAlert(response.message);
            }
        },
        error: function(xhr, status, error) {
            showErrorAlert('خەتا ڕوویدا: ' + error);
        },
        complete: function() {
            hideLoadingSpinner('#sellingTable');
        }
    });
}

// Function to load buying receipts
function loadBuyingReceipts() {
    // Show loading spinner
    showLoadingSpinner('#buyingTable');
    
    // AJAX request to get buying receipts
    $.ajax({
        url: 'process/get_receipts.php',
        type: 'POST',
        data: { type: 'buying' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateBuyingTable(response.data);
            } else {
                showErrorAlert(response.message);
            }
        },
        error: function(xhr, status, error) {
            showErrorAlert('خەتا ڕوویدا: ' + error);
        },
        complete: function() {
            hideLoadingSpinner('#buyingTable');
        }
    });
}

// Function to load wasting receipts
function loadWastingReceipts() {
    // Show loading spinner
    showLoadingSpinner('#wastingTable');
    
    // AJAX request to get wasting receipts
    $.ajax({
        url: 'process/get_receipts.php',
        type: 'POST',
        data: { type: 'wasting' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateWastingTable(response.data);
            } else {
                showErrorAlert(response.message);
            }
        },
        error: function(xhr, status, error) {
            showErrorAlert('خەتا ڕوویدا: ' + error);
        },
        complete: function() {
            hideLoadingSpinner('#wastingTable');
        }
    });
}

// Function to update selling receipts table
function updateSellingTable(data) {
    const table = $('#sellingTable').DataTable();
    
    // Clear the table
    table.clear();
    
    // Add rows to the table
    data.forEach(function(receipt) {
        const statusBadge = getStatusBadge(receipt.status);
        const actionButtons = `
            <div class="action-buttons">
                <button class="btn btn-sm btn-outline-info view-receipt" data-id="${receipt.id}" data-type="selling" title="بینین">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-success edit-receipt" data-id="${receipt.id}" data-type="selling" title="دەستکاریکردن">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary print-receipt" data-id="${receipt.id}" data-type="selling" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-receipt" data-id="${receipt.id}" data-type="selling" title="سڕینەوە">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        table.row.add([
            receipt.id,
            receipt.title,
            receipt.customer,
            formatDate(receipt.date),
            formatCurrency(receipt.total),
            statusBadge,
            actionButtons
        ]);
    });
    
    // Draw the table
    table.draw();
}

// Function to update buying receipts table
function updateBuyingTable(data) {
    const table = $('#buyingTable').DataTable();
    
    // Clear the table
    table.clear();
    
    // Add rows to the table
    data.forEach(function(receipt) {
        const statusBadge = getStatusBadge(receipt.status);
        const actionButtons = `
            <div class="action-buttons">
                <button class="btn btn-sm btn-outline-info view-receipt" data-id="${receipt.id}" data-type="buying" title="بینین">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-success edit-receipt" data-id="${receipt.id}" data-type="buying" title="دەستکاریکردن">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary print-receipt" data-id="${receipt.id}" data-type="buying" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-receipt" data-id="${receipt.id}" data-type="buying" title="سڕینەوە">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        table.row.add([
            receipt.id,
            receipt.title,
            receipt.vendor,
            formatDate(receipt.date),
            receipt.vendor_invoice,
            formatCurrency(receipt.total),
            statusBadge,
            actionButtons
        ]);
    });
    
    // Draw the table
    table.draw();
}

// Function to update wasting receipts table
function updateWastingTable(data) {
    const table = $('#wastingTable').DataTable();
    
    // Clear the table
    table.clear();
    
    // Add rows to the table
    data.forEach(function(receipt) {
        const statusBadge = getStatusBadge(receipt.status);
        const actionButtons = `
            <div class="action-buttons">
                <button class="btn btn-sm btn-outline-info view-receipt" data-id="${receipt.id}" data-type="wasting" title="بینین">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-success edit-receipt" data-id="${receipt.id}" data-type="wasting" title="دەستکاریکردن">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary print-receipt" data-id="${receipt.id}" data-type="wasting" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-receipt" data-id="${receipt.id}" data-type="wasting" title="سڕینەوە">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        table.row.add([
            receipt.id,
            receipt.title,
            receipt.responsible,
            formatDate(receipt.date),
            translateReason(receipt.reason),
            formatCurrency(receipt.total),
            statusBadge,
            actionButtons
        ]);
    });
    
    // Draw the table
    table.draw();
}

// Function to load receipt details
function loadReceiptDetails(receiptId, receiptType) {
    // Show loading spinner
    Swal.fire({
        title: 'تکایە چاوەڕوان بە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // AJAX request to get receipt details
    $.ajax({
        url: 'process/get_receipt_details.php',
        type: 'POST',
        data: { 
            id: receiptId,
            type: receiptType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.close();
                showReceiptDetails(response.data, receiptType);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خەتا',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'خەتا',
                text: 'خەتا ڕوویدا: ' + error
            });
        }
    });
}

// Function to show receipt details in modal
function showReceiptDetails(data, receiptType) {
    // Set modal title and content
    $('#modalReceiptNumber').text(data.id);
    $('#modalReceiptTitle').text(data.title);
    $('#modalReceiptDate').text(formatDate(data.date));
    
    // Set party name based on receipt type
    if (receiptType === 'selling') {
        $('#modalReceiptParty').text(data.customer);
    } else if (receiptType === 'buying') {
        $('#modalReceiptParty').text(data.vendor);
    } else if (receiptType === 'wasting') {
        $('#modalReceiptParty').text(data.responsible);
    }
    
    // Clear the items table
    $('#modalReceiptItems tbody').empty();
    
    // Add items to the table
    data.items.forEach(function(item, index) {
        $('#modalReceiptItems tbody').append(`
            <tr>
                <td>${index + 1}</td>
                <td>${item.product_name}</td>
                <td>${formatCurrency(item.price)}</td>
                <td>${item.quantity}</td>
                <td>${formatCurrency(item.total)}</td>
            </tr>
        `);
    });
    
    // Set the total
    $('#modalReceiptTotal').text(formatCurrency(data.total));
    
    // Set notes
    $('#modalReceiptNotes').text(data.notes || 'هیچ تێبینیەک نییە');
    
    // Store the receipt type in the modal
    $('#receiptDetailsModal').data('receiptType', receiptType);
    
    // Show the modal
    $('#receiptDetailsModal').modal('show');
}

// Function to delete receipt
function deleteReceipt(receiptId, receiptType) {
    // Close the confirmation modal
    $('#deleteReceiptModal').modal('hide');
    
    // Show loading spinner
    Swal.fire({
        title: 'تکایە چاوەڕوان بە...',
        text: 'سڕینەوەی پسوڵە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // AJAX request to delete receipt
    $.ajax({
        url: 'process/delete_receipt.php',
        type: 'POST',
        data: { 
            id: receiptId,
            type: receiptType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: 'پسوڵە بە سەرکەوتوویی سڕایەوە'
                }).then(() => {
                    // Reload the receipts list
                    if (receiptType === 'selling') {
                        loadSellingReceipts();
                    } else if (receiptType === 'buying') {
                        loadBuyingReceipts();
                    } else if (receiptType === 'wasting') {
                        loadWastingReceipts();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خەتا',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'خەتا',
                text: 'خەتا ڕوویدا: ' + error
            });
        }
    });
}

// Function to print receipt
function printReceipt(receiptId, receiptType) {
    // Open the print window
    window.open(`process/print_receipt.php?id=${receiptId}&type=${receiptType}`, '_blank');
}

// Function to apply date filter
function applyDateFilter(table) {
    // Custom search function for date range
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        let startDate, endDate, dateColumn;
        
        // Determine which table and get the relevant dates
        if (settings.nTable.id === 'sellingTable') {
            startDate = $('#sellingStartDate').val();
            endDate = $('#sellingEndDate').val();
            dateColumn = 3; // Date column index
        } else if (settings.nTable.id === 'buyingTable') {
            startDate = $('#buyingStartDate').val();
            endDate = $('#buyingEndDate').val();
            dateColumn = 3; // Date column index
        } else if (settings.nTable.id === 'wastingTable') {
            startDate = $('#wastingStartDate').val();
            endDate = $('#wastingEndDate').val();
            dateColumn = 3; // Date column index
        } else {
            return true; // Not a table we're interested in
        }
        
        // If no date filter is set, show all rows
        if (!startDate && !endDate) {
            return true;
        }
        
        // Parse the dates from the table
        const rowDate = parseDate(data[dateColumn]);
        
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
    table.draw();
    
    // Remove the custom search function after it's done
    $.fn.dataTable.ext.search.pop();
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ku-IQ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Helper function to parse date from formatted string
function parseDate(dateString) {
    // Split the date string into parts
    const parts = dateString.split('/');
    
    // Create a new date object (format depends on the display format)
    return new Date(parts[2], parts[1] - 1, parts[0]);
}

// Helper function to format currency
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2) + ' $';
}

// Helper function to get status badge HTML
function getStatusBadge(status) {
    switch (status) {
        case 'active':
        case 'completed':
            return '<span class="badge bg-success">تەواوکراو</span>';
        case 'pending':
            return '<span class="badge bg-warning">چاوەڕوان</span>';
        case 'canceled':
            return '<span class="badge bg-danger">هەڵوەشاوەتەوە</span>';
        default:
            return '<span class="badge bg-secondary">نادیار</span>';
    }
}

// Helper function to translate reason codes
function translateReason(reason) {
    switch (reason) {
        case 'damaged':
            return 'کاڵای زیانمەند';
        case 'expired':
            return 'کاڵای بەسەرچوو';
        case 'inventory_correction':
            return 'ڕاستکردنەوەی ئینڤێنتۆری';
        case 'other':
            return 'هۆکاری تر';
        default:
            return reason;
    }
}

// Helper function to show loading spinner
function showLoadingSpinner(tableId) {
    $(tableId).closest('.card-body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
}

// Helper function to hide loading spinner
function hideLoadingSpinner(tableId) {
    $(tableId).closest('.card-body').find('.loading-overlay').remove();
}

// Helper function to show error alert
function showErrorAlert(message) {
    Swal.fire({
        icon: 'error',
        title: 'خەتا',
        text: message
    });
} 