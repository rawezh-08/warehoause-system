// Function to check for supplier advance payments during purchase
function checkSupplierAdvancePayment() {
    const tabPane = $('.tab-pane.active');
    const supplierId = tabPane.find('.supplier-select').val();
    const paymentType = tabPane.find('.payment-type').val();
    const advanceInfoDiv = tabPane.find('.supplier-advance-info');
    
    if (!supplierId || paymentType !== 'credit') {
        advanceInfoDiv.hide();
        return;
    }
    
    // Check if supplier has advance payment
    $.ajax({
        url: '../../api/suppliers.php',
        type: 'GET',
        data: { id: supplierId },
        success: function(response) {
            if (response.success && response.supplier && response.supplier.debt_on_supplier > 0) {
                const advanceAmount = response.supplier.debt_on_supplier;
                
                // Show the advance payment info
                advanceInfoDiv.html(`
                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle me-2"></i>
                                ئەم دابینکەرە <strong>${advanceAmount.toLocaleString()}</strong> دینار پارەی پێشەکی هەیە.
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success use-advance-btn">
                                <i class="fas fa-check me-1"></i> بەکارهێنان
                            </button>
                        </div>
                    </div>
                `).show();
                
                // Handle use advance button click
                tabPane.find('.use-advance-btn').on('click', function() {
                    const remainingAmount = parseFloat(tabPane.find('.remaining-amount').val()) || 0;
                    
                    if (remainingAmount <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'ئاگاداری',
                            text: 'هیچ بڕێکی ماوە نییە بۆ بەکارهێنانی پارەی پێشەکی.',
                            confirmButtonText: 'باشە'
                        });
                        return;
                    }
                    
                    const amountToUse = Math.min(advanceAmount, remainingAmount);
                    
                    Swal.fire({
                        icon: 'question',
                        title: 'بەکارهێنانی پارەی پێشەکی',
                        html: `
                            ئایا دەتەوێت <strong>${amountToUse.toLocaleString()}</strong> دینار 
                            لە پارەی پێشەکی دابینکەر بەکاربهێنیت بۆ ئەم کڕینە؟
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'بەڵێ، بەکاریبهێنە',
                        cancelButtonText: 'نەخێر'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Update UI first
                            const newRemainingAmount = remainingAmount - amountToUse;
                            const currentPaidAmount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
                            const newPaidAmount = currentPaidAmount + amountToUse;
                            
                            tabPane.find('.remaining-amount').val(newRemainingAmount);
                            tabPane.find('.paid-amount').val(newPaidAmount);
                            
                            // Update the advance payment info
                            const newAdvanceAmount = advanceAmount - amountToUse;
                            advanceInfoDiv.html(`
                                <div class="alert alert-success mb-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>${amountToUse.toLocaleString()}</strong> دینار پارەی پێشەکی بەکارهات.
                                    ${newAdvanceAmount > 0 ? 
                                      `<strong>${newAdvanceAmount.toLocaleString()}</strong> دینار ماوەتەوە.` : 
                                      'هەموو پارەی پێشەکی بەکارهات.'}
                                </div>
                            `);
                            
                            // Add a hidden field to track that we used advance payment
                            if (tabPane.find('input[name="used_advance_payment"]').length === 0) {
                                tabPane.append(`
                                    <input type="hidden" name="used_advance_payment" value="${amountToUse}">
                                    <input type="hidden" name="advance_payment_supplier_id" value="${supplierId}">
                                `);
                            } else {
                                tabPane.find('input[name="used_advance_payment"]').val(amountToUse);
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: 'پارەی پێشەکی بە سەرکەوتوویی بەکارهات.',
                                confirmButtonText: 'باشە'
                            });
                        }
                    });
                });
            } else {
                advanceInfoDiv.hide();
            }
        },
        error: function() {
            advanceInfoDiv.hide();
        }
    });
}

// Function to handle advance payment when saving purchase
function handleAdvancePaymentOnSave(purchaseId, formData) {
    const usedAdvancePayment = formData.get('used_advance_payment');
    const supplierId = formData.get('advance_payment_supplier_id');
    const invoiceNumber = formData.get('invoice_number');
    
    if (!usedAdvancePayment || parseFloat(usedAdvancePayment) <= 0 || !supplierId) {
        return Promise.resolve(); // No advance payment used
    }
    
    return new Promise((resolve, reject) => {
        $.ajax({
            url: '../../ajax/use_supplier_advance.php',
            type: 'POST',
            data: {
                supplier_id: supplierId,
                purchase_id: purchaseId,
                amount: usedAdvancePayment,
                invoice_number: invoiceNumber
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        resolve(result);
                    } else {
                        reject(result.message);
                    }
                } catch (e) {
                    reject('هەڵەیەک ڕوویدا: ' + e.message);
                }
            },
            error: function(xhr, status, error) {
                reject('هەڵەی پەیوەندیکردن: ' + error);
            }
        });
    });
}

function savePurchase(tabId, isDraft = false) {
    const tabPane = $('#' + tabId);
    
    // Validate form
    if (!validatePurchaseForm(tabPane)) {
        return;
    }
    
    // Collect form data
    const formData = collectFormData(tabPane);
    
    // Show loading
    Swal.fire({
        title: 'جاوەڕێ بکە...',
        text: 'تۆمارکردنی کڕین',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Submit purchase data to server
    $.ajax({
        url: '../../api/purchases.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.success) {
                // If we used advance payment, process it
                const formDataObj = new FormData(tabPane.find('form')[0]);
                if (formDataObj.has('used_advance_payment') && parseFloat(formDataObj.get('used_advance_payment')) > 0) {
                    handleAdvancePaymentOnSave(response.purchase_id, formDataObj)
                        .then(() => {
                            // Show success message
                            showSuccessMessage(response, isDraft);
                        })
                        .catch(error => {
                            console.error('Error handling advance payment:', error);
                            // Still show success but with a warning
                            Swal.fire({
                                icon: 'warning',
                                title: 'کڕین بە سەرکەوتوویی تۆمارکرا',
                                text: 'بەڵام هەڵەیەک ڕوویدا لە بەکارهێنانی پارەی پێشەکی: ' + error,
                                confirmButtonText: 'باشە'
                            });
                        });
                } else {
                    // No advance payment used, just show success
                    showSuccessMessage(response, isDraft);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.message,
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک ڕوویدا: ' + error,
                confirmButtonText: 'باشە'
            });
        }
    });
}

$(document).ready(function() {
    // Initialize the page
    initPage();
    
    // Listen for supplier selection changes
    $(document).on('change', '.supplier-select', function() {
        // Check if supplier has advance payment
        if ($(this).closest('.tab-pane').find('.payment-type').val() === 'credit') {
            checkSupplierAdvancePayment();
        }
    });
    
    // Listen for payment type changes
    $(document).on('change', '.payment-type', function() {
        const isCredit = $(this).val() === 'credit';
        const tabPane = $(this).closest('.tab-pane');
        
        // Show/hide credit payment fields
        if (isCredit) {
            tabPane.find('.credit-payment-fields').show();
            // Check for supplier advance payment
            checkSupplierAdvancePayment();
        } else {
            tabPane.find('.credit-payment-fields').hide();
            tabPane.find('.supplier-advance-info').hide();
        }
        
        // Update form fields based on payment type
        updatePaymentFields(tabPane);
    });
}); 