/**
 * SweetAlert Fixes
 * This file contains fixes for SweetAlert to prevent issues with form submission
 * and to ensure proper display of the SweetAlert dialog.
 */

// Fix for SweetAlert popup not showing properly or closing immediately
(function() {
    // Store the original Swal.fire function
    const originalSwalFire = Swal.fire;
    
    // Override Swal.fire to fix immediate closing issue
    Swal.fire = function() {
        // Save the current keyboard and focus handlers
        const previousWindowKeyDown = window.onkeydown;
        const previousWindowOnFocus = window.onfocus;
        
        // Call the original Swal.fire with all the arguments
        const result = originalSwalFire.apply(this, arguments);
        
        // When the SweetAlert is closed, reset the handlers
        result.then(function() {
            window.onkeydown = previousWindowKeyDown;
            window.onfocus = previousWindowOnFocus;
        });
        
        return result;
    };
    
    // Also fix swal.close to reset handlers
    const originalSwalClose = Swal.close;
    Swal.close = function() {
        const result = originalSwalClose.apply(this, arguments);
        window.onkeydown = null;
        window.onfocus = null;
        return result;
    };
})();

// Fix for return-sale functionality
document.addEventListener('DOMContentLoaded', function() {
    const returnSaleButtons = document.querySelectorAll('.return-sale');
    
    if (returnSaleButtons.length > 0) {
        returnSaleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const saleId = this.getAttribute('data-id');
                const invoiceNumber = this.getAttribute('data-invoice');
                
                // Show loading message
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'زانیارییەکان وەردەگیرێن',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Fetch sale items with AJAX
                fetch('../../ajax/get_sale_items.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'sale_id=' + saleId
                })
                .then(response => response.json())
                .then(response => {
                    Swal.close();
                    
                    if (response.success) {
                        // Check if sale has returns
                        if (response.has_returns) {
                            Swal.fire({
                                title: 'ئاگاداری!',
                                text: 'ئەم پسووڵە پێشتر گەڕێنراوەتەوە',
                                icon: 'warning',
                                confirmButtonText: 'باشە'
                            });
                            return;
                        }
                        
                        // Check if sale has payments
                        if (response.has_payments) {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: 'ناتوانرێت ئەم پسووڵە بگەڕێتەوە چونکە پارەدانەوەی لەسەر تۆمار کراوە',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                            return;
                        }
                        
                        // Create return form HTML
                        let itemsHtml = '<form id="returnSaleForm">';
                        itemsHtml += '<input type="hidden" name="sale_id" value="' + saleId + '">';
                        itemsHtml += '<input type="hidden" name="receipt_type" value="selling">';
                        
                        // Add introduction text
                        itemsHtml += '<div class="alert alert-info mb-3">';
                        itemsHtml += '<i class="fas fa-info-circle me-2"></i> ';
                        itemsHtml += 'تکایە ئاگاداربە کە دەتوانیت ئەو بڕەی گەڕێنیتەوە کە پێشتر نەگەڕێنراوەتەوە. ';
                        itemsHtml += '</div>';
                        
                        // Add reason selection
                        itemsHtml += '<div class="form-group mb-3">';
                        itemsHtml += '<label for="returnReason">هۆکاری گەڕاندنەوە:</label>';
                        itemsHtml += '<select class="form-select" id="returnReason" name="reason">';
                        itemsHtml += '<option value="damage">زیانلێکەوتوو</option>';
                        itemsHtml += '<option value="wrong-product">کاڵای هەڵە</option>';
                        itemsHtml += '<option value="expired">بەسەرچوو</option>';
                        itemsHtml += '<option value="other" selected>هۆکاری تر</option>';
                        itemsHtml += '</select>';
                        itemsHtml += '</div>';
                        
                        // Add notes field
                        itemsHtml += '<div class="form-group mb-3">';
                        itemsHtml += '<label for="returnNotes">تێبینی:</label>';
                        itemsHtml += '<textarea class="form-control" id="returnNotes" name="notes" rows="2"></textarea>';
                        itemsHtml += '</div>';
                        
                        // Create table for items
                        itemsHtml += '<div class="table-responsive"><table class="table table-bordered">';
                        itemsHtml += '<thead><tr>';
                        itemsHtml += '<th>ناوی کاڵا</th>';
                        itemsHtml += '<th>بڕی کڕین</th>';
                        itemsHtml += '<th>گەڕاوە پێشتر</th>';
                        itemsHtml += '<th>بەردەست بۆ گەڕاندنەوە</th>';
                        itemsHtml += '<th>بڕی گەڕاندنەوە</th>';
                        itemsHtml += '</tr></thead>';
                        itemsHtml += '<tbody>';
                        
                        // Add each item to the table
                        response.items.forEach(function(item) {
                            const remainingQuantity = parseFloat(item.quantity) - parseFloat(item.returned_quantity || 0);
                            const displayUnit = item.unit_type === 'piece' ? 'دانە' : 
                                               (item.unit_type === 'box' ? 'کارتۆن' : 'سێت');
                            
                            if (remainingQuantity > 0) {
                                itemsHtml += '<tr>';
                                itemsHtml += '<td>' + item.product_name + '</td>';
                                itemsHtml += '<td>' + item.quantity + ' ' + displayUnit + '</td>';
                                itemsHtml += '<td>' + (item.returned_quantity || 0) + ' ' + displayUnit + '</td>';
                                itemsHtml += '<td>' + remainingQuantity + ' ' + displayUnit + '</td>';
                                itemsHtml += '<td><input type="number" class="form-control return-quantity" name="return_quantities[' + 
                                    item.id + ']" min="0" max="' + remainingQuantity + '" step="0.01" value="0"></td>';
                                itemsHtml += '</tr>';
                            }
                        });
                        
                        itemsHtml += '</tbody></table></div>';
                        itemsHtml += '</form>';
                        
                        // Show the return form dialog
                        Swal.fire({
                            title: 'گەڕاندنەوەی کاڵا - پسووڵە ' + invoiceNumber,
                            html: itemsHtml,
                            width: '800px',
                            showCancelButton: true,
                            confirmButtonText: 'گەڕاندنەوە',
                            cancelButtonText: 'هەڵوەشاندنەوە',
                            showLoaderOnConfirm: true,
                            preConfirm: () => {
                                // Validate form
                                let hasReturns = false;
                                document.querySelectorAll('.return-quantity').forEach(input => {
                                    if (parseFloat(input.value) > 0) {
                                        hasReturns = true;
                                    }
                                });
                                
                                if (!hasReturns) {
                                    Swal.showValidationMessage('تکایە لانی کەم یەک کاڵا هەڵبژێرە بۆ گەڕاندنەوە');
                                    return false;
                                }
                                
                                // Prepare form data
                                const formData = new FormData(document.getElementById('returnSaleForm'));
                                
                                // Submit form with fetch API
                                return fetch('../../ajax/return_sale.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data.success) {
                                        throw new Error(data.message || 'هەڵەیەک ڕوویدا لە کاتی گەڕاندنەوەی کاڵاکان');
                                    }
                                    return data;
                                })
                                .catch(error => {
                                    Swal.showValidationMessage('هەڵە: ' + error.message);
                                });
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'سەرکەوتوو!',
                                    text: 'کاڵاکان بە سەرکەوتوویی گەڕێنرانەوە',
                                    icon: 'success',
                                    confirmButtonText: 'باشە'
                                }).then(() => {
                                    // Reload page to show updated data
                                    location.reload();
                                });
                            }
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            title: 'هەڵە!',
                            text: response.message || 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                })
                .catch(error => {
                    // Handle fetch errors
                    Swal.close();
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                });
            });
        });
    }
}); 