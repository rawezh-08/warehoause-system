$(document).ready(function() {
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    $('.start-date, .end-date, .purchase-date, .delivery-date, .adjustment-date').val(today);

    // Tab counter for each type
    let tabCounters = {
        'selling': 1,
        'buying': 0,
        'wasting': 0
    };

    // Current active receipt type
    let activeReceiptType = 'selling';

    // Calculate row total
    function calculateRowTotal(row) {
        const price = parseFloat($(row).find('.price').val()) || 0;
        const quantity = parseFloat($(row).find('.quantity').val()) || 0;
        const adjustedQuantity = parseFloat($(row).find('.adjusted-quantity').val()) || 0;
        
        // For wasting type, use adjusted quantity instead if available
        const qtyToUse = $(row).closest('[data-receipt-type="wasting"]').length ? adjustedQuantity : quantity;
        
        $(row).find('.total').val((price * qtyToUse).toFixed(2));
        
        // Calculate grand total for the current tab
        const tabId = $('.tab-pane.active').attr('id');
        calculateGrandTotal(tabId);
    }

    // Calculate grand total for a specific tab
    function calculateGrandTotal(tabId) {
        const tabPane = $('#' + tabId);
        let subtotal = 0;
        
        tabPane.find('.total').each(function() {
            subtotal += parseFloat($(this).val()) || 0;
        });
        
        const tax = parseFloat(tabPane.find('.tax').val()) || 0;
        const discount = parseFloat(tabPane.find('.discount').val()) || 0;
        const shippingCost = parseFloat(tabPane.find('.shipping-cost').val()) || 0;
        
        // Different calculation based on receipt type
        const receiptType = tabPane.data('receipt-type');
        let grandTotal;
        
        if (receiptType === 'buying') {
            grandTotal = subtotal + tax + shippingCost;
        } else if (receiptType === 'wasting') {
            grandTotal = subtotal; // Just the subtotal for wasting
        } else { // selling
            grandTotal = subtotal + tax - discount;
        }
        
        tabPane.find('.subtotal').val(subtotal.toFixed(2));
        tabPane.find('.grand-total').val(grandTotal.toFixed(2));
    }

    // Add new row to a table
    $(document).on('click', '.add-row-btn', function() {
        const tabPane = $(this).closest('.tab-pane');
        const itemsList = tabPane.find('.items-list');
        const lastRow = itemsList.find('tr:last');
        const newRow = lastRow.clone();
        const rowNumber = itemsList.find('tr').length + 1;
        
        newRow.find('td:first').text(rowNumber);
        newRow.find('input').val('');
        newRow.appendTo(itemsList);
    });

    // Calculate totals on input
    $(document).on('input', '.price, .quantity, .adjusted-quantity', function() {
        calculateRowTotal($(this).closest('tr'));
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        const itemsList = $(this).closest('.items-list');
        if (itemsList.find('tr').length > 1) {
            $(this).closest('tr').remove();
            
            // Renumber rows
            itemsList.find('tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
            
            // Recalculate totals
            const tabId = $(this).closest('.tab-pane').attr('id');
            calculateGrandTotal(tabId);
        }
    });

    // Receipt type buttons
    $('.receipt-type-btn').click(function() {
        $('.receipt-type-btn').removeClass('active');
        $(this).addClass('active');
        activeReceiptType = $(this).data('type');
    });

    // Tax, discount, and shipping cost updates
    $(document).on('input', '.tax, .discount, .shipping-cost', function() {
        const tabId = $(this).closest('.tab-pane').attr('id');
        calculateGrandTotal(tabId);
    });

    // Add new tab
    $('#addNewTab').click(function() {
        // Increment counter for current receipt type
        tabCounters[activeReceiptType]++;
        const newTabCount = tabCounters[activeReceiptType];
        
        // Create new tab ID
        const newTabId = activeReceiptType + '-' + newTabCount;
        
        // Get receipt type label
        let receiptTypeLabel;
        switch(activeReceiptType) {
            case 'selling':
                receiptTypeLabel = 'فرۆشتن';
                break;
            case 'buying':
                receiptTypeLabel = 'کڕین';
                break;
            case 'wasting':
                receiptTypeLabel = 'ڕێکخستنەوە';
                break;
        }
        
        // Create new tab
        const newTab = `
            <li class="nav-item" role="presentation">
                <button class="nav-link receipt-tab" id="tab-${newTabId}" data-bs-toggle="tab" data-bs-target="#${newTabId}" type="button" role="tab">
                    ${receiptTypeLabel} #${newTabCount}
                    <span class="close-tab"><i class="fas fa-times"></i></span>
                </button>
            </li>
        `;
        
        // Insert new tab before the add tab button
        $(this).parent().before(newTab);
        
        // Get appropriate template based on receipt type
        let templateContent;
        if (activeReceiptType === 'buying') {
            templateContent = $('#buying-template').html();
        } else if (activeReceiptType === 'wasting') {
            templateContent = $('#wasting-template').html();
        } else { // selling
            // Clone the selling template from the first tab if available, otherwise use a default
            if ($('#selling-1').length) {
                templateContent = $('#selling-1').html();
            } else {
                templateContent = $('#selling-template').html();
            }
        }
        
        // Create new tab content
        const newTabContent = `
            <div class="tab-pane fade" id="${newTabId}" role="tabpanel" data-receipt-type="${activeReceiptType}">
                ${templateContent}
            </div>
        `;
        
        // Append new tab content
        $('#receiptTabsContent').append(newTabContent);
        
        // Clear inputs in the new tab
        $('#' + newTabId).find('input:not([readonly])').val('');
        
        // Set default dates
        $('#' + newTabId).find('.start-date, .end-date, .purchase-date, .delivery-date, .adjustment-date').val(today);
        
        // Set default values for numeric inputs
        $('#' + newTabId).find('.tax, .discount, .shipping-cost').val('0');
        
        // Activate the new tab
        $(`#tab-${newTabId}`).tab('show');
    });
    
    // Close tab
    $(document).on('click', '.close-tab', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // If there's only one tab, don't close it
        if ($('.nav-tabs .nav-item').length <= 2) {
            Swal.fire({
                icon: 'warning',
                title: 'ناتوانیت داخستن!',
                text: 'لانیکەم یەک پسوڵە پێویستە',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        // Get the tab ID
        const tabId = $(this).parent().attr('data-bs-target').substring(1);
        
        // If this is the active tab, select another tab
        if ($(this).parent().hasClass('active')) {
            // Find the previous or next tab
            const tabToActivate = $(this).closest('.nav-item').prev('.nav-item').length ? 
                $(this).closest('.nav-item').prev('.nav-item').find('.nav-link') : 
                $(this).closest('.nav-item').next('.nav-item').next('.nav-item').find('.nav-link');
            
            tabToActivate.tab('show');
        }
        
        // Remove tab and content
        $(this).closest('.nav-item').remove();
        $('#' + tabId).remove();
    });
});