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
    
    // Sample product prices (in a real app, these would come from database)
    const productPrices = {
        '1': 499.99, // مۆبایل سامسونگ
        '2': 1299.99, // لاپتۆپ ئەپڵ
        '3': 89.99, // سپیکەر JBL
        '4': 199.99 // مۆنیتەر LG
    };

    // Initialize product select dropdowns
    initProductDropdowns();

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

    // Reset form - used by refresh button
    function resetForm(tabPane) {
        // Reset text inputs and numbers except readonly ones
        tabPane.find('input:not([readonly])').val('');
        
        // Reset selects to first option
        tabPane.find('select').prop('selectedIndex', 0);
        
        // Clear and reload Select2 dropdowns
        tabPane.find('.product-select').val(null).trigger('change');
        
        // Reset date fields to today
        tabPane.find('.start-date, .end-date, .purchase-date, .delivery-date, .adjustment-date').val(today);
        
        // Set default values for numeric inputs
        tabPane.find('.tax, .discount, .shipping-cost').val('0');
        
        // Keep just the first row in the items table and clear its values
        const itemsList = tabPane.find('.items-list');
        const firstRow = itemsList.find('tr:first').clone();
        firstRow.find('input').val('');
        firstRow.find('select').prop('selectedIndex', 0);
        
        // Reinitialize product select in the first row
        const productCell = firstRow.find('td:nth-child(2)');
        productCell.html('<select class="form-control product-select" style="width: 100%"></select>');
        
        itemsList.empty().append(firstRow);
        initProductDropdowns();
        
        // Reset totals
        tabPane.find('.subtotal, .grand-total').val('0.00');
    }

    // Function to initialize product dropdown with Select2
    function initProductDropdowns() {
        $('.product-select').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'ناوی کاڵا',
                    allowClear: true,
                    language: {
                        noResults: function() {
                            return "هیچ ئەنجامێک نەدۆزرایەوە";
                        },
                        searching: function() {
                            return "گەڕان...";
                        }
                    },
                    ajax: {
                        url: 'process/search_products.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term || '',
                                show_initial: params.term ? 0 : 1 // Send flag to show initial products if no search term
                            };
                        },
                        processResults: function(data) {
                            return data;
                        },
                        cache: true
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    },
                    templateResult: formatProduct,
                    templateSelection: formatProductSelection,
                    // Automatically open the dropdown when it's focused
                    // to show initial products
                    minimumInputLength: 0
                });
                
                // When dropdown is opened, send request to get initial products
                $(this).on('select2:open', function() {
                    // Trigger a minimal search to load initial products
                    const select2Instance = $(this).data('select2');
                    if (select2Instance && !select2Instance.isSearching) {
                        const searchField = $('.select2-search__field');
                        if (searchField.length && !searchField.val()) {
                            searchField.trigger('input');
                        }
                    }
                });
            }
        });
    }

    // Format the dropdown item display
    function formatProduct(product) {
        if (product.loading) return product.text;
        if (!product.id) return product.text;
        
        // Format prices with commas for better readability
        const sellingPrice = parseFloat(product.selling_price).toLocaleString();
        const purchasePrice = parseFloat(product.purchase_price).toLocaleString();
        
        // Check which receipt type is active to show appropriate price
        const receiptType = $('.receipt-type-btn.active').data('type');
        const showPurchasePrice = receiptType === 'buying';
        const priceLabel = showPurchasePrice ? 'نرخی کڕین' : 'نرخی فرۆشتن';
        const priceValue = showPurchasePrice ? purchasePrice : sellingPrice;
        
        let markup = `
            <div class="select2-result-product">
                <div class="select2-result-product__title">${product.name}</div>
                <div class="select2-result-product__meta">
                    <div class="select2-result-product__code">کۆد: ${product.code}</div>
                    <div class="select2-result-product__price">${priceLabel}: ${priceValue}</div>
                </div>
            </div>
        `;
        
        return markup;
    }

    // Format the selected item
    function formatProductSelection(product) {
        if (!product.id) return product.text;
        
        // Create a more informative selection display
        return `<span title="${product.name} - ${product.code}">${product.name}</span>`;
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
        
        // Reset the product select
        const productCell = newRow.find('td:nth-child(2)');
        productCell.html('<select class="form-control product-select" style="width: 100%"></select>');
        
        newRow.appendTo(itemsList);
        
        // Initialize the select2 in the new row
        initProductDropdowns();
    });

    // Product selection change - auto-fill price
    $(document).on('change', '.product-select', function() {
        const productData = $(this).select2('data')[0];
        const row = $(this).closest('tr');
        
        if (productData && productData.id) {
            // Set the price from the product data
            const price = productData.selling_price || 0;
            row.find('.price').val(parseFloat(price).toFixed(2));
            
            // If in wasting tab, simulate fetching the current quantity
            if (row.closest('[data-receipt-type="wasting"]').length) {
                // In a real app, this would fetch the actual inventory quantity
                const simulatedCurrentQty = Math.floor(Math.random() * 100) + 1; // Random quantity 1-100
                row.find('.current-quantity').val(simulatedCurrentQty);
            }
            
            // Update the row total
            calculateRowTotal(row);
        }
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

    // Handle dropdown select for adding new customer/vendor/responsible
    $(document).on('change', '.customer-select, .vendor-select, .responsible-select', function() {
        if ($(this).val() === 'new') {
            // Reset to first option
            $(this).prop('selectedIndex', 0);
            
            // Show modal or prompt for adding new entity
            Swal.fire({
                title: 'زیادکردنی نوێ',
                input: 'text',
                inputLabel: 'ناو',
                inputPlaceholder: 'ناوی تەواو بنووسە',
                showCancelButton: true,
                confirmButtonText: 'زیادکردن',
                cancelButtonText: 'پاشگەزبوونەوە',
                inputValidator: (value) => {
                    if (!value) {
                        return 'تکایە ناوێک بنووسە';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Add new option and select it
                    const newOptionValue = Date.now(); // Using timestamp as unique ID
                    const newOption = new Option(result.value, newOptionValue);
                    $(this).append(newOption);
                    $(this).val(newOptionValue);
                }
            });
        }
    });

    // Click handler for refresh button
    $(document).on('click', '.refresh-btn', function() {
        const tabPane = $(this).closest('.tab-pane');
        
        Swal.fire({
            title: 'دڵنیای؟',
            text: 'هەموو داتاکان لە پسوڵەکە دەسڕێنەوە.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                resetForm(tabPane);
            }
        });
    });

    // Dynamically add new tab on adding new receipt button click
    $('#addNewTab').on('click', function() {
        // Get the selected receipt type from the active button
        const receiptType = $('.receipt-type-btn.active').data('type');
        
        // Increment the counter for this type
        tabCounters[receiptType]++;
        
        // Get the appropriate template
        const templateId = receiptType + '-template';
        const tabContentTemplate = $('#' + templateId).html();
        
        // Create a unique ID for the new tab
        const newTabId = receiptType + '-' + tabCounters[receiptType];
        
        // Create the tab button
        const tabButton = `
            <li class="nav-item" role="presentation">
                <button class="nav-link receipt-tab" id="tab-${newTabId}" data-bs-toggle="tab" data-bs-target="#${newTabId}" type="button" role="tab">
                    ${receiptType === 'selling' ? 'فرۆشتن' : receiptType === 'buying' ? 'کڕین' : 'ڕێکخستنەوە'} #${tabCounters[receiptType]}
                    <span class="close-tab"><i class="fas fa-times"></i></span>
                </button>
            </li>
        `;
        
        // Insert the new tab button before the add button
        $(this).parent().before(tabButton);
        
        // Create the tab content
        const tabContent = `<div class="tab-pane fade" id="${newTabId}" role="tabpanel" data-receipt-type="${receiptType}">${tabContentTemplate}</div>`;
        
        // Add the tab content to the content area
        $('#receiptTabsContent').append(tabContent);
        
        // Initialize product dropdowns in the new tab
        $(`#${newTabId}`).find('td:nth-child(2)').each(function() {
            $(this).html('<select class="form-control product-select" style="width: 100%"></select>');
        });
        initProductDropdowns();
        
        // Show the new tab
        $(`#tab-${newTabId}`).tab('show');
    });

    // Close tab
    $(document).on('click', '.close-tab', function(e) {
        e.stopPropagation(); // Prevent the tab from being activated
        
        const tabNavItem = $(this).closest('.nav-item');
        const tabId = tabNavItem.find('.nav-link').attr('data-bs-target').substring(1);
        
        Swal.fire({
            title: 'دڵنیای؟',
            text: 'ئایا دەتەوێت ئەم پسوڵەیە دابخەیت؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                // Check if closing active tab
                const isActive = tabNavItem.find('.nav-link').hasClass('active');
                
                // Remove tab and its content
                tabNavItem.remove();
                $('#' + tabId).remove();
                
                // If the active tab was closed, show the first tab
                if (isActive && $('.receipt-tab').length > 0) {
                    $('.receipt-tab:first').tab('show');
                }
            }
        });
    });

    // Save receipt
    $(document).on('click', '.save-btn', function() {
        const tabPane = $(this).closest('.tab-pane');
        const receiptType = tabPane.data('receipt-type');
        
        // Validate the form
        let isValid = true;
        
        // Check required fields specific to each receipt type
        if (receiptType === 'selling') {
            if (!tabPane.find('.customer-select').val()) {
                Swal.fire('هەڵە', 'تکایە کڕیار هەڵبژێرە', 'error');
                isValid = false;
            }
        } else if (receiptType === 'buying') {
            if (!tabPane.find('.vendor-select').val()) {
                Swal.fire('هەڵە', 'تکایە فرۆشیار هەڵبژێرە', 'error');
                isValid = false;
            }
        } else if (receiptType === 'wasting') {
            if (!tabPane.find('.responsible-select').val()) {
                Swal.fire('هەڵە', 'تکایە بەرپرسیار هەڵبژێرە', 'error');
                isValid = false;
            }
        }
        
        // Check if there are items in the receipt
        if (isValid) {
            let hasItems = false;
            tabPane.find('.items-list tr').each(function() {
                const product = $(this).find('.product-select').val();
                const quantity = $(this).find('.quantity').val() || $(this).find('.adjusted-quantity').val();
                
                if (product && quantity && parseFloat(quantity) > 0) {
                    hasItems = true;
                    return false; // Break the loop
                }
            });
            
            if (!hasItems) {
                Swal.fire('هەڵە', 'تکایە لانی کەم یەک کاڵا زیاد بکە', 'error');
                isValid = false;
            }
        }
        
        // If form is valid, proceed with saving
        if (isValid) {
            // Here you would typically collect all data and send to server
            // For now, just show success message
            Swal.fire({
                title: 'سەرکەوتوو بوو',
                text: 'پسوڵە بە سەرکەوتوویی پاشەکەوت کرا',
                icon: 'success',
                confirmButtonText: 'باشە'
            });
        }
    });

    // Print receipt
    $(document).on('click', '.print-btn', function() {
        // Implement print functionality here
        alert('چاپکردنی پسوڵە لێرە پێکهاتووە');
    });

    // Mobile view adjustments
    function adjustForMobileView() {
        if (window.innerWidth < 768) {
            // Adjust tab navigation on mobile
            $('.nav-tabs').addClass('flex-nowrap');
            $('.nav-tabs').css('overflow-x', 'auto');
            
            // Make the tab buttons smaller on mobile
            $('.nav-tabs .nav-link').addClass('py-1 px-2').css('font-size', '0.85rem');
            
            // Show only one column of the total section on mobile
            $('.total-section .row > div').removeClass('col-md-3').addClass('col-6');
        } else {
            // Reset for desktop
            $('.nav-tabs').removeClass('flex-nowrap');
            $('.nav-tabs').css('overflow-x', 'visible');
            $('.nav-tabs .nav-link').removeClass('py-1 px-2').css('font-size', '');
            $('.total-section .row > div').removeClass('col-6').addClass('col-md-3');
        }
    }
    
    // Run mobile adjustments on load and resize
    adjustForMobileView();
    $(window).resize(adjustForMobileView);
});