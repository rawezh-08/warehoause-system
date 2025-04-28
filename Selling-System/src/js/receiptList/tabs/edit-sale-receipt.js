// Handle sale receipt editing
$(document).ready(function() {
    // Handle edit button click
    $(document).on('click', '#employeeHistoryTable .edit-btn', function() {
        const saleId = $(this).data('id');
        loadSaleForEditing(saleId);
    });

    // Handle save button click
    $('#saveSaleEdit').on('click', function() {
        saveSaleChanges();
    });
});

// Load sale data for editing
function loadSaleForEditing(saleId) {
    if (!saleId) return;

    // Show loading
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        text: 'زانیارییەکان وەردەگیرێن',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch sale details
    $.ajax({
        url: '../../api/receipts/get_sale.php',
        type: 'POST',
        data: { id: saleId },
        success: function(response) {
            Swal.close();

            if (response.success) {
                populateSaleEditForm(response.data);
                $('#editSaleModal').modal('show');
            } else {
                showError(response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیارییەکان');
            }
        },
        error: function() {
            Swal.close();
            showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە');
        }
    });
}

// Populate edit form with sale data
function populateSaleEditForm(saleData) {
    $('#editSaleId').val(saleData.id);
    $('#editSaleInvoiceNumber').val(saleData.invoice_number);
    $('#editSaleCustomer').val(saleData.customer_id);
    $('#editSaleDate').val(formatDateForInput(saleData.date));
    $('#editSalePaymentType').val(saleData.payment_type);
    $('#editSaleShippingCost').val(saleData.shipping_cost || 0);
    $('#editSaleOtherCosts').val(saleData.other_costs || 0);
    $('#editSaleDiscount').val(saleData.discount || 0);
    $('#editSaleNotes').val(saleData.notes || '');
    
    // Disable payment type field if sale has returns or payments
    if (saleData.has_returns || saleData.has_payments) {
        $('#editSalePaymentType').prop('disabled', true);
        
        // Add a note about why the field is disabled
        if (saleData.has_returns && saleData.has_payments) {
            $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵا و پارەدانی لەسەر تۆمارکراوە</small>').insertAfter('#editSalePaymentType');
        } else if (saleData.has_returns) {
            $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە گەڕاندنەوەی کاڵای لەسەر تۆمارکراوە</small>').insertAfter('#editSalePaymentType');
        } else if (saleData.has_payments) {
            $('<small class="text-danger d-block mt-1">ناتوانرێت جۆری پارەدان بگۆڕدرێت چونکە پسووڵەکە پارەدانی لەسەر تۆمارکراوە</small>').insertAfter('#editSalePaymentType');
        }
    } else {
        $('#editSalePaymentType').prop('disabled', false);
        // Remove any existing note
        $('#editSalePaymentType').next('small.text-danger').remove();
    }
}

// Save sale changes
function saveSaleChanges() {
    // Validate form
    if (!validateSaleEditForm()) {
        return;
    }

    // Get form data
    const saleData = {
        id: $('#editSaleId').val(),
        invoice_number: $('#editSaleInvoiceNumber').val(),
        customer_id: $('#editSaleCustomer').val(),
        date: $('#editSaleDate').val(),
        payment_type: $('#editSalePaymentType').val(),
        shipping_cost: $('#editSaleShippingCost').val() || 0,
        other_costs: $('#editSaleOtherCosts').val() || 0,
        discount: $('#editSaleDiscount').val() || 0,
        notes: $('#editSaleNotes').val()
    };

    // Show confirmation dialog
    Swal.fire({
        title: 'دڵنیای؟',
        text: 'ئایا دڵنیای لە تازەکردنەوەی ئەم پسووڵەیە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ، تازەی بکەوە',
        cancelButtonText: 'نەخێر، پەشیمان بوومەوە',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            updateSale(saleData);
        }
    });
}

// Validate sale edit form
function validateSaleEditForm() {
    // Required fields
    if (!$('#editSaleInvoiceNumber').val()) {
        showError('تکایە ژمارەی پسووڵە بنووسە');
        return false;
    }
    if (!$('#editSaleCustomer').val()) {
        showError('تکایە کڕیار هەڵبژێرە');
        return false;
    }
    if (!$('#editSaleDate').val()) {
        showError('تکایە بەروار هەڵبژێرە');
        return false;
    }

    // Numeric fields should be non-negative
    if ($('#editSaleShippingCost').val() < 0) {
        showError('کرێی گواستنەوە ناتوانێت کەمتر بێت لە سفر');
        return false;
    }
    if ($('#editSaleOtherCosts').val() < 0) {
        showError('خەرجی تر ناتوانێت کەمتر بێت لە سفر');
        return false;
    }
    if ($('#editSaleDiscount').val() < 0) {
        showError('داشکاندن ناتوانێت کەمتر بێت لە سفر');
        return false;
    }

    return true;
}

// Update sale in database
function updateSale(saleData) {
    // Show loading
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        text: 'گۆڕانکارییەکان پاشەکەوت دەکرێن',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send update request
    $.ajax({
        url: '../../api/receipts/update_sale.php',
        type: 'POST',
        data: saleData,
        success: function(response) {
            Swal.close();

            if (response.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو!',
                    text: response.message,
                    confirmButtonText: 'باشە'
                }).then(() => {
                    // Close modal and reload data
                    $('#editSaleModal').modal('hide');
                    loadSalesData(); // This function should be defined in the main sales tab file
                });
            } else {
                showError(response.message || 'هەڵەیەک ڕوویدا لە پاشەکەوتکردنی گۆڕانکارییەکان');
            }
        },
        error: function() {
            Swal.close();
            showError('هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە');
        }
    });
}

// Helper function to format date for input
function formatDateForInput(dateString) {
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
}

// Helper function to show error messages
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'هەڵە!',
        text: message,
        confirmButtonText: 'باشە'
    });
} 