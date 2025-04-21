// Handle purchase receipt editing
$(document).ready(function() {
    // Handle edit button click
    $(document).on('click', '#shippingHistoryTable .edit-btn', function() {
        const purchaseId = $(this).data('id');
        loadPurchaseForEditing(purchaseId);
    });

    // Handle save button click
    $('#savePurchaseEdit').on('click', function() {
        savePurchaseChanges();
    });
});

// Load purchase data for editing
function loadPurchaseForEditing(purchaseId) {
    if (!purchaseId) return;

    // Show loading
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        text: 'زانیارییەکان وەردەگیرێن',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch purchase details
    $.ajax({
        url: '../../api/receipts/get_purchase.php',
        type: 'POST',
        data: { id: purchaseId },
        success: function(response) {
            Swal.close();

            if (response.success) {
                populatePurchaseEditForm(response.data);
                $('#editPurchaseModal').modal('show');
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

// Populate edit form with purchase data
function populatePurchaseEditForm(purchaseData) {
    $('#editPurchaseId').val(purchaseData.id);
    $('#editPurchaseInvoiceNumber').val(purchaseData.invoice_number);
    $('#editPurchaseSupplier').val(purchaseData.supplier_id);
    $('#editPurchaseDate').val(formatDateForInput(purchaseData.date));
    $('#editPurchasePaymentType').val(purchaseData.payment_type);
    $('#editPurchaseShippingCost').val(purchaseData.shipping_cost || 0);
    $('#editPurchaseOtherCost').val(purchaseData.other_cost || 0);
    $('#editPurchaseDiscount').val(purchaseData.discount || 0);
    $('#editPurchaseNotes').val(purchaseData.notes || '');
}

// Save purchase changes
function savePurchaseChanges() {
    // Validate form
    if (!validatePurchaseEditForm()) {
        return;
    }

    // Get form data
    const purchaseData = {
        id: $('#editPurchaseId').val(),
        invoice_number: $('#editPurchaseInvoiceNumber').val(),
        supplier_id: $('#editPurchaseSupplier').val(),
        date: $('#editPurchaseDate').val(),
        payment_type: $('#editPurchasePaymentType').val(),
        shipping_cost: $('#editPurchaseShippingCost').val() || 0,
        other_cost: $('#editPurchaseOtherCost').val() || 0,
        discount: $('#editPurchaseDiscount').val() || 0,
        notes: $('#editPurchaseNotes').val()
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
            updatePurchase(purchaseData);
        }
    });
}

// Validate purchase edit form
function validatePurchaseEditForm() {
    // Required fields
    if (!$('#editPurchaseInvoiceNumber').val()) {
        showError('تکایە ژمارەی پسووڵە بنووسە');
        return false;
    }
    if (!$('#editPurchaseSupplier').val()) {
        showError('تکایە دابینکەر هەڵبژێرە');
        return false;
    }
    if (!$('#editPurchaseDate').val()) {
        showError('تکایە بەروار هەڵبژێرە');
        return false;
    }

    // Numeric fields should be non-negative
    if ($('#editPurchaseShippingCost').val() < 0) {
        showError('کرێی گواستنەوە ناتوانێت کەمتر بێت لە سفر');
        return false;
    }
    if ($('#editPurchaseOtherCost').val() < 0) {
        showError('خەرجی تر ناتوانێت کەمتر بێت لە سفر');
        return false;
    }
    if ($('#editPurchaseDiscount').val() < 0) {
        showError('داشکاندن ناتوانێت کەمتر بێت لە سفر');
        return false;
    }

    return true;
}

// Update purchase in database
function updatePurchase(purchaseData) {
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
        url: '../../api/receipts/update_purchase.php',
        type: 'POST',
        data: purchaseData,
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
                    $('#editPurchaseModal').modal('hide');
                    loadPurchasesData(); // This function should be defined in the main purchases tab file
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