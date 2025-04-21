/**
 * Advance Payment Handling for Sales
 * This file contains functions to check for and use customer advance payments during the sales process
 */

// Function to check if a customer has advance payment
function checkCustomerAdvancePayment(customerId, callback) {
    if (!customerId) {
        callback({ success: false, message: 'No customer selected' });
        return;
    }
    
    // Make AJAX request to get customer's advance payment status
    $.ajax({
        url: '../../api/customers.php',
        type: 'GET',
        data: { id: customerId },
        success: function(response) {
            if (response.success && response.customer) {
                const customer = response.customer;
                // Check if customer has advance payment (negative debit_on_business)
                if (customer.debit_on_business < 0) {
                    // Return the available advance payment amount (as a positive number)
                    callback({
                        success: true,
                        hasAdvance: true,
                        advanceAmount: Math.abs(customer.debit_on_business),
                        customer: customer
                    });
                } else {
                    // Customer has no advance payment
                    callback({
                        success: true,
                        hasAdvance: false,
                        advanceAmount: 0,
                        customer: customer
                    });
                }
            } else {
                callback({ 
                    success: false, 
                    message: response.message || 'Error fetching customer data' 
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

// Function to use customer advance payment for a sale
function useAdvancePayment(saleData, callback) {
    // Check if customer has advance payment
    checkCustomerAdvancePayment(saleData.customerId, function(result) {
        if (!result.success || !result.hasAdvance) {
            callback(result);
            return;
        }
        
        // If customer has advance payment and we need to use it
        if (saleData.paymentType === 'credit' && saleData.remainingAmount > 0) {
            // Determine how much to use from advance payment (up to the remaining amount)
            const amountToUse = Math.min(result.advanceAmount, saleData.remainingAmount);
            
            // If there's advance payment to use, make an AJAX call to use it
            if (amountToUse > 0) {
                $.ajax({
                    url: '../../ajax/use_advance_payment.php',
                    type: 'POST',
                    data: {
                        customer_id: saleData.customerId,
                        sale_id: saleData.saleId,
                        amount: amountToUse,
                        invoice_number: saleData.invoiceNumber
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        callback({
                            success: data.success,
                            message: data.message,
                            advanceUsed: data.success ? data.data.amount_used : 0,
                            remainingAdvance: data.success ? data.data.remaining_advance : result.advanceAmount,
                            newRemainingAmount: data.success ? data.data.new_remaining_amount : saleData.remainingAmount
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

// Function to handle advance payment UI
function setupAdvancePaymentUI() {
    // Listen for customer selection change
    $('.customer-select').on('change', function() {
        const customerId = $(this).val();
        const receiptContainer = $(this).closest('.receipt-container');
        const customerAdvanceInfo = receiptContainer.find('.customer-advance-info');
        const advanceAmount = receiptContainer.find('.advance-amount');
        
        if (!customerId) {
            customerAdvanceInfo.hide();
            return;
        }
        
        // Check if customer has advance payment
        checkCustomerAdvancePayment(customerId, function(result) {
            if (result.success && result.hasAdvance) {
                // Update UI to show advance payment
                customerAdvanceInfo.show();
                advanceAmount.text(result.advanceAmount.toLocaleString());
                
                // Add a button to use advance payment if payment type is credit
                if (receiptContainer.find('.payment-type').val() === 'credit') {
                    // Only add the button if it doesn't exist
                    if (customerAdvanceInfo.find('.use-advance-btn').length === 0) {
                        const useButton = $('<button>', {
                            type: 'button',
                            class: 'btn btn-sm btn-outline-success use-advance-btn ms-2',
                            html: '<i class="fas fa-coins"></i> بەکارهێنان',
                            click: function() {
                                // This button will be used when manually applying advance payment
                                Swal.fire({
                                    title: 'بەکارهێنانی پارەی پێشەکی',
                                    text: `ئایا دەتەوێت ${result.advanceAmount.toLocaleString()} دینار پارەی پێشەکی بۆ ئەم پسووڵەیە بەکاربهێنیت؟`,
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonText: 'بەڵێ',
                                    cancelButtonText: 'نەخێر'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Logic to use advance payment will go here
                                        // This is a placeholder for manual application
                                        alert('This functionality will be implemented during actual sale submission');
                                    }
                                });
                            }
                        });
                        customerAdvanceInfo.append(useButton);
                    }
                } else {
                    // Remove the button if payment type is not credit
                    customerAdvanceInfo.find('.use-advance-btn').remove();
                }
            } else {
                customerAdvanceInfo.hide();
            }
        });
    });
    
    // Listen for payment type change
    $('.payment-type').on('change', function() {
        const receiptContainer = $(this).closest('.receipt-container');
        const customerId = receiptContainer.find('.customer-select').val();
        const customerAdvanceInfo = receiptContainer.find('.customer-advance-info');
        
        if ($(this).val() === 'credit' && customerId) {
            // Check if customer has advance payment
            checkCustomerAdvancePayment(customerId, function(result) {
                if (result.success && result.hasAdvance) {
                    // Show the use button if needed
                    if (customerAdvanceInfo.find('.use-advance-btn').length === 0) {
                        const useButton = $('<button>', {
                            type: 'button',
                            class: 'btn btn-sm btn-outline-success use-advance-btn ms-2',
                            html: '<i class="fas fa-coins"></i> بەکارهێنان',
                            click: function() {
                                Swal.fire({
                                    title: 'بەکارهێنانی پارەی پێشەکی',
                                    text: `ئایا دەتەوێت ${result.advanceAmount.toLocaleString()} دینار پارەی پێشەکی بۆ ئەم پسووڵەیە بەکاربهێنیت؟`,
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonText: 'بەڵێ',
                                    cancelButtonText: 'نەخێر'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Logic to use advance payment will go here
                                        alert('This functionality will be implemented during actual sale submission');
                                    }
                                });
                            }
                        });
                        customerAdvanceInfo.append(useButton);
                    }
                }
            });
        } else {
            // Remove the button if payment type is not credit
            customerAdvanceInfo.find('.use-advance-btn').remove();
        }
    });
}

// Initialize when the document is ready
$(document).ready(function() {
    setupAdvancePaymentUI();
});

// Export functions for use in other scripts
window.checkCustomerAdvancePayment = checkCustomerAdvancePayment;
window.useAdvancePayment = useAdvancePayment; 