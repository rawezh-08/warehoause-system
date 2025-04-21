/**
 * Supplier Advance Payment Handling
 * This file contains functions for managing supplier advance payments
 */

// Function to save supplier advance payment
function saveSupplierAdvancePayment(formData, callback) {
    $.ajax({
        url: '../../ajax/save_supplier_advance.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            let result;
            try {
                result = JSON.parse(response);
            } catch (e) {
                result = {
                    success: false,
                    message: 'هەڵەیەک ڕوویدا: ' + response
                };
            }
            
            callback(result);
        },
        error: function(xhr, status, error) {
            callback({
                success: false,
                message: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن: ' + error
            });
        }
    });
}

// Function to check if a supplier has advance payment
function checkSupplierAdvancePayment(supplierId, callback) {
    if (!supplierId) {
        callback({ success: false, message: 'No supplier selected' });
        return;
    }
    
    // Make AJAX request to get supplier's advance payment status
    $.ajax({
        url: '../../api/suppliers.php',
        type: 'GET',
        data: { id: supplierId },
        success: function(response) {
            if (response.success && response.supplier) {
                const supplier = response.supplier;
                // Check if we have advance payment to this supplier
                if (supplier.debt_on_supplier > 0) {
                    // Return the available advance payment amount
                    callback({
                        success: true,
                        hasAdvance: true,
                        advanceAmount: supplier.debt_on_supplier,
                        supplier: supplier
                    });
                } else {
                    // Supplier has no advance payment
                    callback({
                        success: true,
                        hasAdvance: false,
                        advanceAmount: 0,
                        supplier: supplier
                    });
                }
            } else {
                callback({ 
                    success: false, 
                    message: response.message || 'Error fetching supplier data' 
                });
            }
        },
        error: function(xhr, status, error) {
            callback({ 
                success: false, 
                message: 'Error connecting to server: ' + error 
            });
        }
    });
}

// Function to use supplier advance payment for a purchase
function useSupplierAdvancePayment(purchaseData, callback) {
    // Check if supplier has advance payment
    checkSupplierAdvancePayment(purchaseData.supplierId, function(result) {
        if (!result.success || !result.hasAdvance) {
            callback(result);
            return;
        }
        
        // If supplier has advance payment and we need to use it
        if (purchaseData.paymentType === 'credit' && purchaseData.remainingAmount > 0) {
            // Determine how much to use from advance payment (up to the remaining amount)
            const amountToUse = Math.min(result.advanceAmount, purchaseData.remainingAmount);
            
            // If there's advance payment to use, make an AJAX call to use it
            if (amountToUse > 0) {
                $.ajax({
                    url: '../../ajax/use_supplier_advance.php',
                    type: 'POST',
                    data: {
                        supplier_id: purchaseData.supplierId,
                        purchase_id: purchaseData.purchaseId,
                        amount: amountToUse,
                        invoice_number: purchaseData.invoiceNumber
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        callback({
                            success: data.success,
                            message: data.message,
                            advanceUsed: data.success ? data.data.amount_used : 0,
                            remainingAdvance: data.success ? data.data.remaining_advance : result.advanceAmount,
                            newRemainingAmount: data.success ? data.data.new_remaining_amount : purchaseData.remainingAmount
                        });
                    },
                    error: function(xhr, status, error) {
                        callback({ 
                            success: false, 
                            message: 'Error connecting to server: ' + error 
                        });
                    }
                });
            } else {
                // No advance payment used
                callback({
                    success: true,
                    advanceUsed: 0,
                    message: 'No advance payment used'
                });
            }
        } else {
            // No need to use advance payment
            callback({
                success: true,
                advanceUsed: 0,
                message: 'No advance payment needed'
            });
        }
    });
}

// Initialize the supplier advance payment functionality
$(document).ready(function() {
    // Save advance payment button click
    $('#saveSupplierAdvancePaymentBtn').on('click', function() {
        const form = $('#supplierAdvancePaymentForm');
        const formData = form.serialize();
        
        saveSupplierAdvancePayment(formData, function(result) {
            if (result.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: result.message,
                    confirmButtonText: 'باشە'
                }).then((swalResult) => {
                    // Show print option
                    if (swalResult.isConfirmed && result.transaction_id) {
                        Swal.fire({
                            icon: 'question',
                            title: 'چاپکردنی پسووڵە',
                            text: 'دەتەویت پسووڵەی پارەی پێشەکی چاپ بکەیت؟',
                            showCancelButton: true,
                            confirmButtonText: 'بەڵێ، چاپی بکە',
                            cancelButtonText: 'نەخێر'
                        }).then((printResult) => {
                            if (printResult.isConfirmed) {
                                // Open receipt in new tab for printing
                                window.open('../../views/receipt/supplier_advance_receipt.php?transaction_id=' + result.transaction_id + '&print=true', '_blank');
                            }
                            
                            // Refresh the page
                            location.reload();
                        });
                    } else {
                        // Refresh the page
                        location.reload();
                    }
                });
                
                // Reset form
                form[0].reset();
            } else {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: result.message,
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
    
    // Refresh advance payment history button click
    $('.refresh-advance-btn').on('click', function() {
        location.reload();
    });
}); 