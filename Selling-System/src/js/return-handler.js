/**
 * Handle product returns
 */

// Process return function
function processReturn(returnData) {
    console.log('Processing return data:', returnData);
    
    // Create FormData with the correct structure
    const formData = new FormData();
    formData.append('sale_id', returnData.receipt_id);
    formData.append('receipt_type', 'selling');
    formData.append('reason', returnData.reason || 'other');
    formData.append('notes', returnData.notes || '');
    
    // Convert return items to the correct format
    const returnQuantities = {};
    returnData.items.forEach(item => {
        if (item.id) {
            returnQuantities[item.id] = item.quantity;
        }
    });
    
    console.log('Return quantities object:', returnQuantities);
    formData.append('return_quantities', JSON.stringify(returnQuantities));
    
    // Send AJAX request
    $.ajax({
        url: '../../ajax/return_sale.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Return response:', response);
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو!',
                    text: 'گەڕاندنەوەی کاڵاکان بە سەرکەوتوویی تۆمار کرا',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    // Reload page to refresh data
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: response.message || 'هەڵەیەک ڕوویدا لە پرۆسەی گەڕاندنەوە',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', xhr, status, error);
            console.log('Status:', xhr.status);
            console.log('Response:', xhr.responseText);
            
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە پرۆسەی گەڕاندنەوە: ' + error,
                confirmButtonText: 'باشە'
            });
        }
    });
}

// Make processReturn function available globally
window.processReturn = processReturn; 