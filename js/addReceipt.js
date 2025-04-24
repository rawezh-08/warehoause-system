// Function to check product stock
function checkProductStock(input) {
    const row = $(input).closest('tr');
    const productId = row.find('.product-select').val();
    const quantity = $(input).val();
    const unitType = row.find('.unit-type').val();

    if (!productId || !quantity) return;

    $.ajax({
        url: '../../api/check_stock.php',
        type: 'POST',
        data: {
            product_id: productId,
            quantity: quantity,
            unit_type: unitType
        },
        success: function(response) {
            if (response.success) {
                if (response.stock_available) {
                    // Stock is available
                    $(input).removeClass('is-invalid').addClass('is-valid');
                } else {
                    // Stock is not available
                    $(input).removeClass('is-valid').addClass('is-invalid');
                    Swal.fire({
                        title: 'ئاگادارکردنەوە',
                        text: 'بڕی داواکراو لە کۆگا بەردەست نییە',
                        icon: 'warning',
                        confirmButtonText: 'باشە'
                    });
                }
            } else {
                console.error('Error checking stock:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Function to save receipt
function saveReceipt(tabId, isDraft = false) {
    console.log(`Saving ${isDraft ? 'draft' : 'regular'} receipt ${tabId}`);
    
    // Get the receipt container
    const $container = $(`#${tabId}`);
    const receiptType = $container.data('receipt-type');
    
    // Collect form data
    const formData = {
        receipt_type: receiptType,
        invoice_number: $container.find('.receipt-number').val(),
        date: $container.find('.sale-date').val(),
        payment_type: $container.find('.payment-type').val(),
        discount: $container.find('.discount').val() || 0,
        shipping_cost: $container.find('.shipping-cost').val() || 0,
        other_cost: $container.find('.other-cost').val() || 0,
        notes: $container.find('.notes').val(),
        is_draft: isDraft,
        price_type: $container.find('.price-type').val()
    };

    // Add customer/supplier ID based on receipt type
    if (receiptType === 'selling') {
        formData.customer_id = $container.find('.customer-select').val();
        formData.paid_amount = $container.find('.paid-amount').val() || 0;
    } else if (receiptType === 'buying') {
        formData.supplier_id = $container.find('.supplier-select').val();
        formData.paid_amount = $container.find('.paid-amount').val() || 0;
    }

    // Collect products data
    formData.products = [];
    $container.find('.items-list tr').each(function() {
        const $row = $(this);
        const productId = $row.find('.product-select').val();
        if (productId) {
            formData.products.push({
                product_id: productId,
                quantity: $row.find('.quantity').val(),
                unit_type: $row.find('.unit-type').val(),
                unit_price: $row.find('.unit-price').val()
            });
        }
    });

    console.log('Form data:', formData);

    // Send AJAX request
    $.ajax({
        url: '../../api/save_receipt.php',
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            console.log('Server response:', response);
            
            if (response.success) {
                Swal.fire({
                    title: 'سەرکەوتوو بوو',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'باشە'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to receipts list
                        window.location.href = 'receipts.php';
                    }
                });
            } else {
                console.error('Save failed:', response);
                Swal.fire({
                    title: 'هەڵە!',
                    text: response.message || 'هەڵەیەک ڕوویدا لە کاتی پاشەکەوتکردن',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            Swal.fire({
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەرەوە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        }
    });
}

$(document).ready(function() {
    // Initialize stock check event listeners
    $(document).on('change', 'input[data-check-stock="true"]', function() {
        checkProductStock(this);
    });

    // ... rest of your existing document.ready code ...
}); 