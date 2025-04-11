$(document).ready(function() {
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    $('.start-date, .end-date, .purchase-date, .delivery-date, .adjustment-date, .sale-date').val(today);

    // Tab counter for each type
    let tabCounters = {
        'selling': 1,
        'buying': 0,
        'wasting': 0
    };

    // Current active receipt type
    let activeReceiptType = 'selling';
    
    // Initialize all select2 dropdowns for the first time - ONLY THIS SHOULD BE CALLED
    initializeFirstTimeDropdowns();
    
    // Initialize all select2 dropdowns
    function initializeFirstTimeDropdowns() {
        console.log('Initializing all dropdowns in the first tab...');
        
        // To prevent re-initialization, add a data attribute to the tab
        const firstTab = $('#selling-1');
        if (firstTab.data('initialized')) {
            console.log('First tab already initialized, skipping...');
            return;
        }
        
        // For selling tab, fetch initial invoice number
        if (firstTab.data('receipt-type') === 'selling') {
            $.ajax({
                url: 'api/get_next_invoice.php',
                type: 'GET',
                data: { type: 'selling' },
                success: function(response) {
                    if (response.success) {
                        firstTab.find('.receipt-number').val(response.invoice_number);
                    }
                }
            });
        }
        
        // Initialize product dropdowns
        console.log('Initializing product dropdowns in first tab...');
        $('.product-select').each(function() {
            initializeProductSelect($(this));
        });
        
        // Initialize customer dropdowns
        console.log('Initializing customer dropdowns in first tab...');
        $('.customer-select').each(function() {
            initializeCustomerSelect($(this));
        });
        
        // Initialize supplier dropdowns
        console.log('Initializing supplier dropdowns in first tab...');
        $('.supplier-select').each(function() {
            initializeSupplierSelect($(this));
        });
        
        // Mark the tab as initialized
        firstTab.data('initialized', true);
        console.log('First tab initialization complete.');
    }

    // Legacy initialize function (kept for reference but not called anywhere)
    function initDropdowns() {
        // This function should NOT be called to avoid duplicate initializations
        console.warn('initDropdowns() called - this should not happen');
        
        // Initialize product dropdowns
        initProductDropdowns();
        
        // Initialize customer dropdowns
        initCustomerDropdowns();
        
        // Initialize supplier dropdowns
        initSupplierDropdowns();
    }

    // Function to fetch products from database
    function loadProducts() {
        return $.ajax({
            url: 'api/products.php',
            type: 'GET',
            dataType: 'json'
        });
    }

    // Initialize product dropdowns
 

    // Initialize customer dropdowns
    function initCustomerDropdowns() {
        $('.customer-select').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    theme: 'bootstrap-5',
                    placeholder: 'کڕیار هەڵبژێرە',
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: 'api/customers.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            
                            return {
                                results: data.customers,
                                pagination: {
                                    more: (params.page * 10) < data.total_count
                                }
                            };
                        },
                        cache: true
                    },
                    templateResult: formatCustomerResult,
                    templateSelection: formatCustomerSelection
                });
            }
        });
    }



    // Format the product in the dropdown
    function formatProductResult(product) {
        if (!product.id) return product.text;
        
        // Simple display showing only product name and barcode
        return $(
            `<div class="product-result">
                <div class="product-name">${product.text}</div>
                <div class="product-meta small text-muted">بارکۆد: ${product.barcode}</div>
            </div>`
        );
    }

    // Format customer display in dropdown
    function formatCustomerResult(customer) {
        if (!customer.id) return customer.text;
        
        return $(
            `<div class="customer-result">
                <div class="customer-name">${customer.text}</div>
                <div class="small text-muted">${customer.phone1 || ''}</div>
                ${customer.debit_on_business > 0 ? `<div class="text-danger">قەرز: ${customer.debit_on_business}</div>` : ''}
            </div>`
        );
    }

    // Format supplier display in dropdown
    function formatSupplierResult(supplier) {
        if (!supplier.id) return supplier.text;
        
        return $(
            `<div class="supplier-result">
                <div class="supplier-name">${supplier.text}</div>
                <div class="small text-muted">${supplier.phone1 || ''}</div>
                ${supplier.debt_on_myself > 0 ? `<div class="text-success">قەرزم: ${supplier.debt_on_myself}</div>` : ''}
            </div>`
        );
    }

    // Format customer selection
    function formatCustomerSelection(customer) {
        if (!customer.id) return customer.text;
        return customer.text;
    }

    // Format supplier selection
    function formatSupplierSelection(supplier) {
        if (!supplier.id) return supplier.text;
        return supplier.text;
    }

    // Calculate row total
    function calculateRowTotal(row) {
        const unitPrice = parseFloat(row.find('.unit-price, .price').val()) || 0;
        const quantity = parseFloat(row.find('.quantity, .adjusted-quantity').val()) || 0;
        const total = unitPrice * quantity;
        
        row.find('.total').val(total);
        
        // Recalculate tab totals
        const tabId = row.closest('.tab-pane').attr('id');
        calculateGrandTotal(tabId);
    }

    // Calculate grand total for a specific tab
    function calculateGrandTotal(tabId) {
        const tabPane = $('#' + tabId);
        let subtotal = 0;
        
        tabPane.find('.total').each(function() {
            subtotal += parseFloat($(this).val()) || 0;
        });
        
        const discount = parseFloat(tabPane.find('.discount').val()) || 0;
        const receiptType = tabPane.data('receipt-type');
        
        // Different calculation based on receipt type
        let grandTotal = subtotal - discount;
        
        // If selling receipt, add shipping cost
        if (receiptType === 'selling') {
            const shippingCost = parseFloat(tabPane.find('.shipping-cost').val()) || 0;
            const otherCosts = parseFloat(tabPane.find('.other-costs').val()) || 0;
            tabPane.find('.shipping-cost-total').val(shippingCost);
            grandTotal = grandTotal + shippingCost + otherCosts;
        }
        
        tabPane.find('.subtotal').val(subtotal);
        tabPane.find('.grand-total').val(grandTotal);
        
        // Update the remaining amount if payment type is credit
        if (tabPane.find('.payment-type').val() === 'credit') {
            updateRemainingAmount(tabPane);
        }
    }

    // Update remaining amount based on paid amount
    function updateRemainingAmount(tabPane) {
        const grandTotal = parseFloat(tabPane.find('.grand-total').val()) || 0;
        const paidAmount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
        const remainingAmount = Math.max(0, grandTotal - paidAmount);
        
        tabPane.find('.remaining-amount').val(remainingAmount);
        
        // Ensure paid amount doesn't exceed grand total
        if (paidAmount > grandTotal) {
            tabPane.find('.paid-amount').val(grandTotal);
            tabPane.find('.remaining-amount').val(0);
        }
    }

    // Format the selected item
    function formatProductSelection(product) {
        if (!product.id) return product.text;
        
        // Show only name for the selected product
        return $(
            `<div class="d-flex align-items-center justify-content-between w-100">
                <span title="${product.text}">${product.text}</span>
            </div>`
        );
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
        
        // Reset the product select - create a new one instead of cloning
        const productCell = newRow.find('td:nth-child(2)');
        productCell.html('<select class="form-control product-select" style="width: 100%"></select>');
        
        // Clear the image cell
        newRow.find('.product-image-cell').empty();
        
        newRow.appendTo(itemsList);
        
        // Initialize the select2 in the new row only
        initializeProductSelect(newRow.find('.product-select'));
    });

    // Product selection change - auto-fill price and other details
    $(document).on('change', '.product-select', function() {
        const productData = $(this).select2('data')[0];
        const row = $(this).closest('tr');
        const tabPane = row.closest('.tab-pane');
        const receiptType = tabPane.data('receipt-type');
        
        if (productData && productData.id) {
            console.log("Product selected:", productData);
            console.log("Receipt type:", receiptType);
            console.log("Unit properties:", {
                unit_id: productData.unit_id,
                is_piece: productData.is_piece,
                is_box: productData.is_box,
                is_set: productData.is_set
            });
            
            // Update unit type dropdown based on product's unit
            updateUnitTypeOptions(row, productData);
            
            // The price will be updated by the unit type change handler
            // No need to set price here as it will be handled by the unit type change event
            
            // Display product image
            fetchProductImage(productData.id, row);
            
            // Update quantity to 1 by default
            row.find('.quantity').val(1);
            
            // Calculate row total
            calculateRowTotal(row);
        }
    });

    // Update unit type options based on product unit
    function updateUnitTypeOptions(row, productData) {
        console.log("Updating unit options for product:", productData);
        const unitTypeSelect = row.find('.unit-type');
        
        // Clear existing options
        unitTypeSelect.empty();
        
        // Add available unit options based on product's unit settings
        if (productData.is_piece) {
            unitTypeSelect.append('<option value="piece">دانە</option>');
        }
        
        if (productData.is_box) {
            unitTypeSelect.append('<option value="box">کارتۆن</option>');
        }
        
        if (productData.is_set) {
            unitTypeSelect.append('<option value="set">سێت</option>');
        }
        
        // Set default selection to piece if available, otherwise first option
        if (productData.is_piece) {
            unitTypeSelect.val('piece').trigger('change');
        } else {
            unitTypeSelect.val(unitTypeSelect.find('option:first').val()).trigger('change');
        }
    }

    // Add unit type change handler
    $(document).on('change', '.unit-type', function() {
        const row = $(this).closest('tr');
        const productSelect = row.find('.product-select');
            const productData = productSelect.select2('data')[0];
            const tabPane = row.closest('.tab-pane');
            const priceType = tabPane.find('.price-type').val();
            
        if (productData) {
            let basePrice;
            // Get base price based on price type (wholesale/single)
            if (priceType === 'wholesale' && productData.selling_price_wholesale) {
                basePrice = parseFloat(productData.selling_price_wholesale);
            } else {
                basePrice = parseFloat(productData.selling_price_single);
            }
            
            const unitType = $(this).val();
            let finalPrice = basePrice;
            
            // Calculate price based on unit type
            if (unitType === 'box' && productData.pieces_per_box) {
                finalPrice = basePrice * productData.pieces_per_box;
            } else if (unitType === 'set' && productData.pieces_per_box && productData.boxes_per_set) {
                finalPrice = basePrice * productData.pieces_per_box * productData.boxes_per_set;
            }
            
            // Update the unit price
            row.find('.unit-price').val(finalPrice);
            
            // Recalculate row total
            calculateRowTotal(row);
        }
    });

    // Update prices when price type changes (wholesale/single)
    $(document).on('change', '.price-type', function() {
        const tabPane = $(this).closest('.tab-pane');
        const priceType = $(this).val();
        
        // Update all product rows with the new price type
        tabPane.find('.items-list tr').each(function() {
            const row = $(this);
            const productSelect = row.find('.product-select');
            
            if (productSelect.val()) {
                const productData = productSelect.select2('data')[0];
                const unitType = row.find('.unit-type').val();
                
                let price = productData.selling_price_single;
                
                // Adjust price based on price type
                if (priceType === 'wholesale' && productData.selling_price_wholesale) {
                    price = productData.selling_price_wholesale;
                }
                
                // Adjust price based on unit type
                if (unitType === 'box' && productData.pieces_per_box) {
                    price = price * productData.pieces_per_box;
                } else if (unitType === 'set' && productData.pieces_per_box && productData.boxes_per_set) {
                    price = price * productData.pieces_per_box * productData.boxes_per_set;
                }
                
                row.find('.unit-price').val(parseFloat(price));
                calculateRowTotal(row);
            }
        });
    });

    // Calculate totals on input
    $(document).on('input', '.unit-price, .quantity, .adjusted-quantity, .discount, .shipping-cost, .other-costs', function() {
        const row = $(this).closest('tr');
        if (row.length) {
            calculateRowTotal(row);
        } else {
            // If not in a row (like discount field), just recalculate the grand total
            const tabId = $(this).closest('.tab-pane').attr('id');
            calculateGrandTotal(tabId);
        }
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

    // Receipt type tabs click handler
    $('.receipt-type-btn').on('click', function() {
        // Get the new type
        const newType = $(this).data('type');
        
        // If it's already active, do nothing
        if ($(this).hasClass('active')) return;
        
        // Remove active class from all tabs
        $('.receipt-type-btn').removeClass('active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Set the active receipt type
        activeReceiptType = newType;
        
        // When changing receipt type, we also need to:
        // 1. Update tab class/appearance 
        // 2. Close all open tabs of other types
        // 3. Open a new tab of the selected type if none exists
        
        let hasTabOfNewType = false;
        
        // Check if any open tabs match the new type
        $('.nav-link.receipt-tab').each(function() {
            const tabId = $(this).attr('id').replace('tab-', '');
            const tabType = $('#' + tabId).data('receipt-type');
            
            if (tabType === newType) {
                hasTabOfNewType = true;
                // Show this tab
                $(this).tab('show');
            } 
        });
        
        // If no tabs of new type exist, create one
        if (!hasTabOfNewType) {
            // Click the add tab button to create a new tab
            $('#addNewTab').click();
        }
    });

    // Add new tab
    $('#addNewTab').click(function() {
        const newTabType = activeReceiptType;
        tabCounters[newTabType]++;
        
        // Create new tab
        const tabId = `${newTabType}-${tabCounters[newTabType]}`;
        const tabLabel = `${getReceiptTypeLabel(newTabType)} #${tabCounters[newTabType]}`;
        
        // Create tab button
        const tabBtn = `
            <li class="nav-item" role="presentation">
                <button class="nav-link receipt-tab" id="tab-${tabId}" data-bs-toggle="tab" data-bs-target="#${tabId}" type="button" role="tab">
                    ${tabLabel}
                    <span class="close-tab"><i class="fas fa-times"></i></span>
                </button>
            </li>
        `;
        
        // Insert tab button before + button
        $(tabBtn).insertBefore($(this).parent());
        
        // Added for more accurate row cloning
        let tabContent = '';
        
        if (newTabType === 'selling') {
            tabContent = $('#selling-template').html();
            
            // Fetch next invoice number for sales
            $.ajax({
                url: 'api/get_next_invoice.php',
                type: 'GET',
                data: { type: 'selling' },
                success: function(response) {
                    if (response.success) {
                        $(`#${tabId} .receipt-number`).val(response.invoice_number);
                    }
                }
            });
        } else if (newTabType === 'buying') {
            tabContent = $('#buying-template').html();
        } else if (newTabType === 'wasting') {
            tabContent = $('#wasting-template').html();
        }
        
        // Create the tab content
        const tabContentHtml = `
            <div class="tab-pane fade" id="${tabId}" role="tabpanel" data-receipt-type="${newTabType}">
                ${tabContent}
            </div>
        `;
        
        // Add tab content
        $('#receiptTabsContent').append(tabContentHtml);
        
        // Set default date in the new tab
        $(`#${tabId} .sale-date, #${tabId} .purchase-date, #${tabId} .adjustment-date`).val(today);
        
        // Activate the new tab
        $(`#tab-${tabId}`).tab('show');
        
        // Force reinitialize dropdowns in the new tab
        setTimeout(function() {
            console.log(`Initializing dropdowns for new tab: ${tabId}, type: ${newTabType}`);
            
            // Initialize product dropdowns
            $(`#${tabId} .product-select`).each(function() {
                console.log(`Initializing product select in ${tabId}`);
                initializeProductSelect($(this));
            });
            
            // Initialize customer or supplier dropdowns based on tab type
            if (newTabType === 'selling') {
                $(`#${tabId} .customer-select`).each(function() {
                    console.log(`Initializing customer select in ${tabId}`);
                    initializeCustomerSelect($(this));
                });
            } else if (newTabType === 'buying') {
                $(`#${tabId} .supplier-select`).each(function() {
                    console.log(`Initializing supplier select in ${tabId}`);
                    initializeSupplierSelect($(this));
                });
            }
            
            // Add event listeners for this specific tab
            initTabEventListeners(tabId, newTabType);
        }, 500);  // Increased timeout to ensure DOM is ready
    });

    // Helper functions to initialize individual selects
    function initializeProductSelect(element) {
        // Check if element exists
        if (!element.length) {
            console.error('Product select element not found');
            return;
        }
        
        // If already initialized, destroy it first
        if (element.hasClass('select2-hidden-accessible')) {
            try {
                element.select2('destroy');
            } catch (error) {
                console.log('Error destroying product select2:', error);
            }
        }
        
        // Initialize the select2 dropdown
        element.select2({
            theme: 'bootstrap-5',
            placeholder: 'کاڵا هەڵبژێرە',
            allowClear: true,
            width: '100%',
            language: {
                searching: function() {
                    return "گەڕان...";
                },
                noResults: function() {
                    return "هیچ ئەنجامێک نەدۆزرایەوە";
                }
            },
            ajax: {
                url: 'api/products.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    
                    return {
                        results: data.products.map(function(product) {
                            return {
                                id: product.id,
                                text: product.name,
                                code: product.code,
                                barcode: product.barcode,
                                purchase_price: product.purchase_price,
                                selling_price_single: product.selling_price_single,
                                selling_price_wholesale: product.selling_price_wholesale,
                                current_quantity: product.current_quantity,
                                pieces_per_box: product.pieces_per_box,
                                boxes_per_set: product.boxes_per_set,
                                unit_id: product.unit_id,
                                is_piece: product.is_piece,
                                is_box: product.is_box,
                                is_set: product.is_set
                            };
                        }),
                        pagination: {
                            more: (params.page * 10) < data.total_count
                        }
                    };
                },
                cache: true
            },
            templateResult: formatProductResult,
            templateSelection: formatProductSelection
        }).on('select2:open', function() {
            // Ensure proper focus when dropdown opens
            setTimeout(function() {
                $('.select2-search__field').focus();
            }, 0);
        });
        
        // Add event handler for when a product is selected
        element.on('select2:select', function(e) {
            const productData = e.params.data;
            const row = $(this).closest('tr');
            
            // Set unit price based on receipt type
            const tabPane = row.closest('.tab-pane');
            const receiptType = tabPane.data('receipt-type');
            
            // Display product image
            fetchProductImage(productData.id, row);
            
            if (receiptType === 'selling') {
                const priceType = tabPane.find('.price-type').val();
                if (priceType === 'wholesale' && productData.selling_price_wholesale) {
                    row.find('.unit-price').val(productData.selling_price_wholesale);
                } else {
                    row.find('.unit-price').val(productData.selling_price_single);
                }
            } else if (receiptType === 'buying') {
                row.find('.unit-price').val(productData.purchase_price);
            }
            
            // Update unit options
            updateUnitTypeOptions(row, productData);
            
            // Update quantity
            row.find('.quantity').val(1);
            
            // Calculate row total
            calculateRowTotal(row);
        });
    }

    function initializeCustomerSelect(element) {
        // Check if element exists
        if (!element.length) {
            console.error('Customer select element not found');
            return;
        }
        
        // If already initialized, destroy it first
        if (element.hasClass('select2-hidden-accessible')) {
            try {
                element.select2('destroy');
            } catch (error) {
                console.log('Error destroying customer select2:', error);
            }
        }
        
        // Initialize the select2 dropdown
        element.select2({
            theme: 'bootstrap-5',
            placeholder: 'کڕیار هەڵبژێرە',
            allowClear: true,
            width: '100%',
            language: {
                searching: function() {
                    return "گەڕان...";
                },
                noResults: function() {
                    return "هیچ ئەنجامێک نەدۆزرایەوە";
                }
            },
            ajax: {
                url: 'api/customers.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    
                    return {
                        results: data.customers,
                        pagination: {
                            more: (params.page * 10) < data.total_count
                        }
                    };
                },
                cache: true
            },
            templateResult: formatCustomerResult,
            templateSelection: formatCustomerSelection
        }).on('select2:open', function() {
            // Ensure proper focus when dropdown opens
            setTimeout(function() {
                $('.select2-search__field').focus();
            }, 0);
        });
    }

    function initializeSupplierSelect(element) {
        // Check if element exists
        if (!element.length) {
            console.error('Supplier select element not found');
            return;
        }
        
        // If already initialized, destroy it first
        if (element.hasClass('select2-hidden-accessible')) {
            try {
                element.select2('destroy');
            } catch (error) {
                console.log('Error destroying supplier select2:', error);
            }
        }
        
        // Initialize the select2 dropdown
        element.select2({
            theme: 'bootstrap-5',
            placeholder: 'فرۆشیار هەڵبژێرە',
            allowClear: true,
            width: '100%',
            language: {
                searching: function() {
                    return "گەڕان...";
                },
                noResults: function() {
                    return "هیچ ئەنجامێک نەدۆزرایەوە";
                }
            },
            ajax: {
                url: 'api/suppliers.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    
                    return {
                        results: data.suppliers,
                        pagination: {
                            more: (params.page * 10) < data.total_count
                        }
                    };
                },
                cache: true
            },
            templateResult: formatSupplierResult,
            templateSelection: formatSupplierSelection
        }).on('select2:open', function() {
            // Ensure proper focus when dropdown opens
            setTimeout(function() {
                $('.select2-search__field').focus();
            }, 0);
        });
    }

    // Get receipt type label
    function getReceiptTypeLabel(type) {
        switch(type) {
            case 'selling': return 'فرۆشتن';
            case 'buying': return 'کڕین';
            case 'wasting': return 'ڕێکخستنەوە';
            default: return type;
        }
    }

    // Close tab
    $(document).on('click', '.close-tab', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Get the tab button and content
        const tabBtn = $(this).closest('.nav-link');
        const tabId = tabBtn.attr('data-bs-target') || tabBtn.attr('href');
        
        // Check if this is the active tab
        const isActive = tabBtn.hasClass('active');
        
        // Remove tab content and button
        $(tabId).remove();
        tabBtn.closest('li').remove();
        
        // If this was the active tab, activate another tab
        if (isActive && $('.receipt-tab').length > 0) {
            $('.receipt-tab:first').tab('show');
        }
    });

    // Save receipt function
    function saveReceipt(tabId, tabType) {
        const tab = $(`#${tabId}`);
        let formData;
        
        // Collect data based on receipt type
        if (tabType === 'selling') {
            formData = collectSellingReceiptData(tab);
        } else if (tabType === 'buying') {
            formData = collectBuyingReceiptData(tab);
        } else if (tabType === 'wasting') {
            formData = collectWastingReceiptData(tab);
        }
        
        if (!formData) return; // Data collection failed or validation failed
            
            // Submit the form data
            $.ajax({
                url: 'api/save_receipt.php',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                    // Show success message
                        Swal.fire({
                            title: 'سەرکەوتوو',
                            text: 'پسوڵە بە سەرکەوتوویی پاشەکەوت کرا',
                            icon: 'success',
                            confirmButtonText: 'باشە',
                            customClass: {
                                popup: 'swal2-rtl',
                                title: 'swal2-title',
                                htmlContainer: 'swal2-html-container',
                                confirmButton: 'swal2-confirm'
                            }
                        }).then((result) => {
                            // Reset form after success
                        resetForm(tab, tabType);
                        });
                    } else {
                        Swal.fire({
                            title: 'هەڵە',
                            text: response.message || 'هەڵەیەک ڕوویدا',
                            icon: 'error',
                            confirmButtonText: 'باشە',
                            customClass: {
                                popup: 'swal2-rtl',
                                title: 'swal2-title',
                                htmlContainer: 'swal2-html-container',
                                confirmButton: 'swal2-confirm'
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    Swal.fire({
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە کاتی پاشەکەوتکردن',
                        icon: 'error',
                        confirmButtonText: 'باشە',
                        customClass: {
                            popup: 'swal2-rtl',
                            title: 'swal2-title',
                            htmlContainer: 'swal2-html-container',
                            confirmButton: 'swal2-confirm'
                        }
                    });
                }
            });
    }

    // Reset form after successful save
    function resetForm(tab, receiptType) {
        // Save current selection data
        let savedData = {};
        if (receiptType === 'selling') {
            savedData.customer_id = tab.find('.customer-select').val();
            savedData.payment_type = tab.find('.payment-type').val();
            savedData.price_type = tab.find('.price-type').val();
        } else if (receiptType === 'buying') {
            savedData.supplier_id = tab.find('.supplier-select').val();
            savedData.payment_type = tab.find('.payment-type').val();
        }
        
        // Reset items
        tab.find('.items-list tr:not(:first)').remove();
        tab.find('.items-list tr:first').find('input').val('');
        tab.find('.items-list tr:first').find('.product-select').val(null).trigger('change');
        
        // Reset totals
        tab.find('.subtotal').val('0.00');
        tab.find('.discount').val('0');
        tab.find('.shipping-cost').val('0');
        tab.find('.other-costs').val('0');
        tab.find('.grand-total').val('0.00');
        tab.find('.paid-amount').val('0');
        tab.find('.remaining-amount').val('0');
        
        // Reset invoice number
        tab.find('.receipt-number').val('');
        
        // Set date to today
        const today = new Date().toISOString().split('T')[0];
        tab.find('.sale-date, .purchase-date, .adjustment-date').val(today);
        
        // Restore saved selections
        if (receiptType === 'selling') {
            if (savedData.customer_id) tab.find('.customer-select').val(savedData.customer_id).trigger('change');
            if (savedData.payment_type) tab.find('.payment-type').val(savedData.payment_type).trigger('change');
            if (savedData.price_type) tab.find('.price-type').val(savedData.price_type);
        } else if (receiptType === 'buying') {
            if (savedData.supplier_id) tab.find('.supplier-select').val(savedData.supplier_id).trigger('change');
            if (savedData.payment_type) tab.find('.payment-type').val(savedData.payment_type).trigger('change');
        }
        
        // If it's a selling receipt, fetch new invoice number
        if (receiptType === 'selling') {
            $.ajax({
                url: 'api/get_next_invoice.php',
                type: 'GET',
                data: { type: 'selling' },
                success: function(response) {
                    if (response.success) {
                        tab.find('.receipt-number').val(response.invoice_number);
                    }
                }
            });
        }
    }

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

    // Add event handler for product info button
    $(document).on('click', '.product-info-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const productId = $(this).data('product-id');
        const productSelect = $(this).closest('tr').find('.product-select');
        const productData = productSelect.select2('data')[0];
        
        if (productData) {
            // Calculate total pieces in box and set
            const piecesPerBox = productData.pieces_per_box || 0;
            const boxesPerSet = productData.boxes_per_set || 0;
            const totalPiecesPerSet = piecesPerBox * boxesPerSet;
            
            // Create a formatted modal with product details using cards
            Swal.fire({
                title: productData.text,
                html: `
                    <div class="product-details">
                        <div class="container-fluid px-0">
                            <div class="row g-3">
                                <!-- Basic Info Card -->
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>زانیاری بنەڕەتی</h5>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-bold">کۆد:</span>
                                                    <span>${productData.code}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-bold">بارکۆد:</span>
                                                    <span>${productData.barcode || 'نییە'}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-bold">بڕی بەردەست:</span>
                                                    <span class="badge bg-${productData.current_quantity > 10 ? 'success' : 'warning'} fs-6">${productData.current_quantity} دانە</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Price Info Card -->
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0"><i class="fas fa-tags me-2"></i>نرخەکان</h5>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-bold">نرخی کڕین:</span>
                                                    <span class="text-primary">${productData.purchase_price} د.ع</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-bold">نرخی فرۆشتن (دانە):</span>
                                                    <span class="text-success">${productData.selling_price_single} د.ع</span>
                                                </li>
                                                ${productData.selling_price_wholesale ? 
                                                `<li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-bold">نرخی فرۆشتن (کۆمەڵ):</span>
                                                    <span class="text-success">${productData.selling_price_wholesale} د.ع</span>
                                                </li>` : ''}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                ${(piecesPerBox || boxesPerSet) ? `
                                <!-- Unit Conversion Card -->
                                <div class="col-12">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>یەکەکان</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                ${piecesPerBox ? `
                                                <div class="col-md-4 mb-2">
                                                    <div class="border rounded p-3 text-center h-100">
                                                        <div class="fs-1 text-primary">${piecesPerBox}</div>
                                                        <div>دانە = ١ کارتۆن</div>
                                                        <div class="small text-muted">${Math.floor(productData.current_quantity / piecesPerBox)} کارتۆن بەردەستە</div>
                                                    </div>
                                                </div>` : ''}
                                                
                                                ${boxesPerSet ? `
                                                <div class="col-md-4 mb-2">
                                                    <div class="border rounded p-3 text-center h-100">
                                                        <div class="fs-1 text-primary">${boxesPerSet}</div>
                                                        <div>کارتۆن = ١ سێت</div>
                                                        <div class="small text-muted">${Math.floor(productData.current_quantity / piecesPerBox / boxesPerSet)} سێت بەردەستە</div>
                                                    </div>
                                                </div>` : ''}
                                                
                                                ${totalPiecesPerSet ? `
                                                <div class="col-md-4 mb-2">
                                                    <div class="border rounded p-3 text-center h-100">
                                                        <div class="fs-1 text-primary">${totalPiecesPerSet}</div>
                                                        <div>دانە = ١ سێت</div>
                                                        <div class="small text-muted">${Math.floor(productData.current_quantity / totalPiecesPerSet)} سێت بەردەستە</div>
                                                    </div>
                                                </div>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>` : ''}
                            </div>
                        </div>
                    </div>
                `,
                confirmButtonText: 'داخستن',
                width: '800px',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                },
                customClass: {
                    container: 'product-info-modal'
                }
            });
        }
    });

    // Refresh button
    $(document).on('click', '.refresh-btn', function(e) {
        e.preventDefault();
        const tabPane = $(this).closest('.tab-pane');
        const receiptType = tabPane.data('receipt-type');
        
        console.log('Refreshing dropdowns for tab type:', receiptType);
        
        // Reinitialize product dropdowns
        tabPane.find('.product-select').each(function() {
            initializeProductSelect($(this));
        });
        
        // Reinitialize other dropdowns based on receipt type
        if (receiptType === 'selling') {
            tabPane.find('.customer-select').each(function() {
                initializeCustomerSelect($(this));
            });
        } else if (receiptType === 'buying') {
            tabPane.find('.supplier-select').each(function() {
                initializeSupplierSelect($(this));
            });
        }
        
        Swal.fire({
            title: 'نوێکرایەوە',
            text: 'سێلێکتەکان بە سەرکەوتووی نوێ کرانەوە',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            customClass: {
                popup: 'swal2-rtl',
                title: 'swal2-title',
                htmlContainer: 'swal2-html-container'
            }
        });
    });

    // Initialize tab-specific event listeners
    function initTabEventListeners(tabId, tabType) {
        console.log(`Setting up event listeners for tab ${tabId}, type: ${tabType}`);
        
        // Price type change handler (selling only)
        if (tabType === 'selling') {
            $(`#${tabId} .price-type`).on('change', function() {
                const priceType = $(this).val();
                const itemsList = $(`#${tabId} .items-list`);
                
                // Update all product rows with the new price type
                itemsList.find('tr').each(function() {
                    const row = $(this);
                    const productSelect = row.find('.product-select');
                    const productData = productSelect.select2('data')[0];
                    
                    if (productData && productData.id) {
                        if (priceType === 'wholesale' && productData.selling_price_wholesale) {
                            row.find('.unit-price').val(productData.selling_price_wholesale);
                        } else {
                            row.find('.unit-price').val(productData.selling_price_single);
                        }
                        
                        // Recalculate totals
                        calculateRowTotal(row);
                    }
                });
            });
        }
        
        // Set up event handlers for this tab's elements
        
        // Product row quantity and price change handlers
        $(`#${tabId} .quantity, #${tabId} .unit-price, #${tabId} .adjusted-quantity, #${tabId} .price`).on('input', function() {
            const row = $(this).closest('tr');
            calculateRowTotal(row);
        });
        
        // Add row button handler
        $(`#${tabId} .add-row-btn`).on('click', function() {
            const itemsList = $(`#${tabId} .items-list`);
            addNewRow(itemsList);
        });
        
        // Remove row button handler
        $(`#${tabId}`).on('click', '.remove-row', function() {
            const row = $(this).closest('tr');
            const itemsList = row.closest('.items-list');
            
            // Don't remove if it's the only row
            if (itemsList.find('tr').length > 1) {
                row.remove();
                
                // Renumber rows
                itemsList.find('tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
                
                // Recalculate totals
                calculateGrandTotal(tabId);
            }
        });
        
        // Shipping cost and discount change handlers for selling receipts
        if (tabType === 'selling') {
            $(`#${tabId} .shipping-cost, #${tabId} .other-costs, #${tabId} .discount`).on('input', function() {
                calculateGrandTotal(tabId);
            });
        } else {
            // Just discount for other receipt types
            $(`#${tabId} .discount`).on('input', function() {
                calculateGrandTotal(tabId);
            });
        }
        
        // Save button handler
        $(`#${tabId} .save-btn`).on('click', function(e) {
            e.preventDefault();
            saveReceipt(tabId, tabType);
        });
        
        // Add payment type change event
        $(`#${tabId} .payment-type`).on('change', function() {
            const paymentType = $(this).val();
            const tabPane = $(this).closest('.tab-pane');
            const creditFields = tabPane.find('.credit-payment-fields');
            const grandTotal = parseFloat(tabPane.find('.grand-total').val()) || 0;
            
            if (paymentType === 'credit') {
                creditFields.show();
                // Reset paid amount to 0 when switching to credit
                tabPane.find('.paid-amount').val(0);
                // Set remaining amount to grand total
                tabPane.find('.remaining-amount').val(grandTotal);
            } else {
                creditFields.hide();
                // Set paid amount to grand total for cash payments
                tabPane.find('.paid-amount').val(grandTotal);
                tabPane.find('.remaining-amount').val(0);
            }
            
            updateRemainingAmount(tabPane);
        });
        
        // Add paid amount change event
        $(`#${tabId} .paid-amount`).on('input', function() {
            updateRemainingAmount($(this).closest('.tab-pane'));
        });
        
        console.log(`Event listeners set up for tab ${tabId}`);
    }
    
    // Add a new row to a table
    function addNewRow(itemsList) {
        const lastRow = itemsList.find('tr:last');
        const newRow = lastRow.clone();
        const rowNumber = itemsList.find('tr').length + 1;
        
        // Update row number
        newRow.find('td:first').text(rowNumber);
        
        // Clear all inputs
        newRow.find('input').val('');
        
        // Reset the product select - create a new one instead of cloning to avoid duplicate IDs
        const productCell = newRow.find('td:nth-child(2)');
        const tabPane = itemsList.closest('.tab-pane');
        const tabType = tabPane.data('receipt-type');
        
        // Create new select element
        productCell.html('<select class="form-control product-select" style="width: 100%"></select>');
        
        // Append the new row
        newRow.appendTo(itemsList);
        
        // Initialize the product select in the new row
        initializeProductSelect(newRow.find('.product-select'));
        
        return newRow;
    }

    // Function to fetch product image
    function fetchProductImage(productId, row) {
        $.ajax({
            url: 'api/product_details.php',
            type: 'GET',
            data: { id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.product) {
                    const imageUrl = response.product.image || 'assets/img/pro-1.png';
                    const productName = response.product.name || 'کاڵا';
                    const imgHtml = `
                        <div class="product-image-container" data-bs-toggle="tooltip" data-bs-placement="top" title="${productName}">
                            <img src="${imageUrl}" alt="${productName}" class="product-image">
                        </div>`;
                    
                    // Add image to the designated cell
                    row.find('.product-image-cell').html(imgHtml);

                    // Initialize tooltips
                    const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltips.forEach(function (tooltipTriggerEl) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    });

                    // Add click event for zooming
                    row.find('.product-image-container').on('click', function() {
                        const img = $(this).find('img');
                        const modal = document.createElement('div');
                        modal.className = 'modal fade image-modal';
                        modal.innerHTML = `
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="${img.attr('src')}" alt="${img.attr('alt')}">
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        const modalInstance = new bootstrap.Modal(modal);
                        modalInstance.show();
                        
                        $(modal).on('hidden.bs.modal', function () {
                            modal.remove();
                        });
                    });
                }
            },
            error: function() {
                // If error, show default image
                const imgHtml = `
                    <div class="product-image-container">
                        <img src="../../assets/img/pro-1.png" alt="کاڵا" class="product-image">
                    </div>`;
                row.find('.product-image-cell').html(imgHtml);
            }
        });
    }

    // Gather data for selling receipt
    function collectSellingReceiptData(tab) {
        const products = [];
        let valid = true;
        
        tab.find('.items-list tr').each(function() {
            const row = $(this);
            const productSelect = row.find('.product-select');
            const productId = productSelect.val();
            
            if (!productId) {
                Swal.fire('خەتا', 'تکایە کاڵا هەڵبژێرە', 'error');
                valid = false;
                return false;
            }
            
            const unitType = row.find('.unit-type').val();
            const unitPrice = parseFloat(row.find('.unit-price').val());
            const quantity = parseFloat(row.find('.quantity').val());
            
            if (isNaN(unitPrice) || unitPrice <= 0) {
                Swal.fire('خەتا', 'تکایە نرخی دروست بنووسە', 'error');
                valid = false;
                return false;
            }
            
            if (isNaN(quantity) || quantity <= 0) {
                Swal.fire('خەتا', 'تکایە بڕی دروست بنووسە', 'error');
                valid = false;
                return false;
            }
            
            products.push({
                product_id: productId,
                unit_type: unitType,
                unit_price: unitPrice,
                quantity: quantity
            });
        });
        
        if (!valid) return null;
        
        const customerId = tab.find('.customer-select').val();
        if (!customerId) {
            Swal.fire('خەتا', 'تکایە کڕیار هەڵبژێرە', 'error');
            return null;
        }
        
        const paymentType = tab.find('.payment-type').val();
        const priceType = tab.find('.price-type').val();
        const receiptNumber = tab.find('.receipt-number').val();
        const saleDate = tab.find('.sale-date').val();
        const discount = parseFloat(tab.find('.discount').val()) || 0;
        const shippingCost = parseFloat(tab.find('.shipping-cost').val()) || 0;
        const otherCosts = parseFloat(tab.find('.other-costs').val()) || 0;
        const notes = tab.find('.notes').val();
        const paidAmount = paymentType === 'credit' ? parseFloat(tab.find('.paid-amount').val()) || 0 : 0;
        
        return {
            receipt_type: 'selling',
            invoice_number: receiptNumber,
            customer_id: customerId,
            payment_type: paymentType,
            price_type: priceType,
            date: saleDate,
            discount: discount,
            shipping_cost: shippingCost,
            other_costs: otherCosts,
            notes: notes,
            paid_amount: paidAmount,
            products: products
        };
    }

    // Gather data for buying receipt
    function collectBuyingReceiptData(tab) {
        const products = [];
        let valid = true;
        
        tab.find('.items-list tr').each(function() {
            const row = $(this);
            const productSelect = row.find('.product-select');
            const productId = productSelect.val();
            
            if (!productId) {
                Swal.fire('خەتا', 'تکایە کاڵا هەڵبژێرە', 'error');
                valid = false;
                return false;
            }
            
            const unitPrice = parseFloat(row.find('.unit-price').val());
            const quantity = parseFloat(row.find('.quantity').val());
            
            if (isNaN(unitPrice) || unitPrice <= 0) {
                Swal.fire('خەتا', 'تکایە نرخی دروست بنووسە', 'error');
                valid = false;
                return false;
            }
            
            if (isNaN(quantity) || quantity <= 0) {
                Swal.fire('خەتا', 'تکایە بڕی دروست بنووسە', 'error');
                valid = false;
                return false;
            }
            
            products.push({
                product_id: productId,
                unit_price: unitPrice,
                quantity: quantity
            });
        });
        
        if (!valid) return null;
        
        const supplierId = tab.find('.supplier-select').val();
        if (!supplierId) {
            Swal.fire('خەتا', 'تکایە فرۆشیار هەڵبژێرە', 'error');
            return null;
        }
        
        const paymentType = tab.find('.payment-type').val();
        const receiptNumber = tab.find('.receipt-number').val();
        const purchaseDate = tab.find('.purchase-date').val();
        const discount = parseFloat(tab.find('.discount').val()) || 0;
        const notes = tab.find('.notes').val();
        const paidAmount = paymentType === 'credit' ? parseFloat(tab.find('.paid-amount').val()) || 0 : 0;
        
        return {
            receipt_type: 'buying',
            invoice_number: receiptNumber,
            supplier_id: supplierId,
            payment_type: paymentType,
            date: purchaseDate,
            discount: discount,
            notes: notes,
            paid_amount: paidAmount,
            products: products
        };
    }

    // Save button handler
    $(document).on('click', '.save-btn', function(e) {
        e.preventDefault();
        const tabPane = $(this).closest('.tab-pane');
        const tabId = tabPane.attr('id');
        const tabType = tabPane.data('receipt-type');
        saveReceipt(tabId, tabType);
    });
});