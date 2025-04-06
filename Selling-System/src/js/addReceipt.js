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
    
    // Initialize all select2 dropdowns for the first time
    initializeFirstTimeDropdowns();
    
    // Initialize all select2 dropdowns
    function initializeFirstTimeDropdowns() {
        console.log('Initializing all dropdowns...');
        
        // Initialize product dropdowns
        $('.product-select').each(function() {
            initializeProductSelect($(this));
        });
        
        // Initialize customer dropdowns
        $('.customer-select').each(function() {
            initializeCustomerSelect($(this));
        });
        
        // Initialize supplier dropdowns
        $('.supplier-select').each(function() {
            initializeSupplierSelect($(this));
        });
    }

    // Legacy initialize function (kept for compatibility)
    function initDropdowns() {
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
        
        // Create a more detailed result with stock information
        return $(
            `<div class="product-result d-flex justify-content-between align-items-center">
                <div class="product-info">
                    <div class="product-name">${product.text}</div>
                    <div class="product-meta small text-muted">کۆد: ${product.code} | بارکۆد: ${product.barcode}</div>
                </div>
                <div class="product-price text-end">
                    <div>دانە: ${product.selling_price_single}</div>
                    ${product.selling_price_wholesale ? `<div>کۆمەڵ: ${product.selling_price_wholesale}</div>` : ''}
                    <div class="text-success">بەردەست: ${product.current_quantity}</div>
                </div>
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
    }

    // Format the selected item
    function formatProductSelection(product) {
        if (!product.id) return product.text;
        
        // Show only name for the selected product
        return $(
            `<div class="d-flex align-items-center justify-content-between w-100">
                <span title="${product.text} (${product.code})">${product.text}</span>
                <button type="button" class="btn btn-sm btn-outline-info product-info-btn ms-2" data-product-id="${product.id}">
                    <i class="fas fa-info-circle"></i>
                </button>
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
            console.log("Product selected:", productData); // Debug log
            console.log("Receipt type:", receiptType); // Debug log
            console.log("Unit properties:", {
                unit_id: productData.unit_id,
                is_piece: productData.is_piece,
                is_box: productData.is_box,
                is_set: productData.is_set
            });
            
            // Update unit type dropdown based on product's unit
            updateUnitTypeOptions(row, productData);
            
            // Set appropriate values based on receipt type
            if (receiptType === 'selling') {
                // For selling, use wholesale or single price based on selection
                const priceType = tabPane.find('.price-type').val();
                const price = priceType === 'wholesale' && productData.selling_price_wholesale ? 
                    productData.selling_price_wholesale : productData.selling_price_single;
                    
                row.find('.unit-price').val(parseFloat(price));
            } else if (receiptType === 'buying') {
                // For buying, use purchase price
                console.log("Setting buying price to:", productData.purchase_price); // Debug log
                row.find('.unit-price').val(parseFloat(productData.purchase_price));
            } else if (receiptType === 'wasting') {
                // For wasting, use purchase price and set current quantity
                row.find('.price').val(parseFloat(productData.purchase_price));
                row.find('.current-quantity').val(productData.current_quantity);
            }
            
            // Update the row total
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

    // Handle unit type change - update pricing
    $(document).on('change', '.unit-type', function() {
        const row = $(this).closest('tr');
        const productSelect = row.find('.product-select');
        
        if (productSelect.val()) {
            const productData = productSelect.select2('data')[0];
            const unitType = $(this).val();
            const tabPane = row.closest('.tab-pane');
            const priceType = tabPane.find('.price-type').val();
            
            let price = productData.selling_price_single;
            
            // Adjust price based on unit type and price type
            if (priceType === 'wholesale' && productData.selling_price_wholesale) {
                price = productData.selling_price_wholesale;
            }
            
            if (unitType === 'box' && productData.pieces_per_box) {
                price = price * productData.pieces_per_box;
            } else if (unitType === 'set' && productData.pieces_per_box && productData.boxes_per_set) {
                price = price * productData.pieces_per_box * productData.boxes_per_set;
            }
            
            row.find('.unit-price').val(parseFloat(price));
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

    // Receipt type buttons
    $('.receipt-type-btn').click(function() {
        $('.receipt-type-btn').removeClass('active');
        $(this).addClass('active');
        activeReceiptType = $(this).data('type');
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
        
        // Create tab content
        const tabTemplate = $(`#${newTabType}-template`).html();
        const tabContent = `
            <div class="tab-pane fade" id="${tabId}" role="tabpanel" data-receipt-type="${newTabType}">
                ${tabTemplate}
            </div>
        `;
        
        // Add tab content
        $('#receiptTabsContent').append(tabContent);
        
        // Set default date in the new tab
        $(`#${tabId} .sale-date, #${tabId} .purchase-date, #${tabId} .adjustment-date`).val(today);
        
        // Activate the new tab
        $(`#tab-${tabId}`).tab('show');
        
        // Force reinitialize dropdowns in the new tab
        setTimeout(function() {
            // Don't try to destroy existing select2 instances - just initialize new ones
            
            // Initialize product dropdowns
            $(`#${tabId} .product-select`).each(function() {
                initializeProductSelect($(this));
            });
            
            // Initialize customer or supplier dropdowns based on tab type
            if (newTabType === 'selling') {
                $(`#${tabId} .customer-select`).each(function() {
                    initializeCustomerSelect($(this));
                });
            } else if (newTabType === 'buying') {
                $(`#${tabId} .supplier-select`).each(function() {
                    initializeSupplierSelect($(this));
                });
            }
        }, 100);
    });

    // Helper functions to initialize individual selects
    function initializeProductSelect(element) {
        try {
            if (element.hasClass('select2-hidden-accessible')) {
                element.select2('destroy');
            }
        } catch (error) {
            console.log('Error destroying product select2:', error);
        }
        
        // Check if the element is already initialized
        if (!element.data('select2')) {
        element.select2({
            theme: 'bootstrap-5',
            placeholder: 'کاڵا هەڵبژێرە',
            allowClear: true,
            width: '100%',
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
        });
            
            // Add event handler for when a product is selected
            element.on('select2:select', function(e) {
                const productData = e.params.data;
                const row = $(this).closest('tr');
                
                // Update unit options
                updateUnitTypeOptions(row, productData);
            });
        }
    }

    function initializeCustomerSelect(element) {
        try {
            if (element.hasClass('select2-hidden-accessible')) {
                element.select2('destroy');
            }
        } catch (error) {
            console.log('Error destroying customer select2:', error);
        }
        
        // Check if the element is already initialized
        if (!element.data('select2')) {
        element.select2({
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
    }

    function initializeSupplierSelect(element) {
        try {
            if (element.hasClass('select2-hidden-accessible')) {
                element.select2('destroy');
            }
        } catch (error) {
            console.log('Error destroying supplier select2:', error);
        }
        
        // Check if the element is already initialized
        if (!element.data('select2')) {
        element.select2({
            theme: 'bootstrap-5',
            placeholder: 'فرۆشیار هەڵبژێرە',
            allowClear: true,
            width: '100%',
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
        });
        }
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

    // Save receipt
    $(document).on('click', '.save-btn', function() {
        const tabPane = $(this).closest('.tab-pane');
        const receiptType = tabPane.data('receipt-type');
        const invoiceNumber = tabPane.find('.receipt-number').val();
        
        // Validate the form
        let isValid = true;
        
        // Check if invoice number exists
        if (invoiceNumber) {
            $.ajax({
                url: 'api/check_invoice.php',
                type: 'POST',
                data: {
                    invoice_number: invoiceNumber,
                    receipt_type: receiptType
                },
                async: false,
                success: function(response) {
                    if (response.exists) {
                        Swal.fire('هەڵە', 'ژمارەی پسووڵە پێشتر بەکارهاتووە', 'error');
                        isValid = false;
                    }
                }
            });
        }
        
        // Check required fields specific to each receipt type
        if (isValid) {
        if (receiptType === 'selling' && !tabPane.find('.customer-select').val()) {
            Swal.fire('هەڵە', 'تکایە کڕیار هەڵبژێرە', 'error');
            isValid = false;
        } else if (receiptType === 'buying' && !tabPane.find('.supplier-select').val()) {
            Swal.fire('هەڵە', 'تکایە فرۆشیار هەڵبژێرە', 'error');
            isValid = false;
        } else if (receiptType === 'wasting' && !tabPane.find('.responsible-select').val()) {
            Swal.fire('هەڵە', 'تکایە بەرپرسیار هەڵبژێرە', 'error');
                isValid = false;
            }
        }
        
        // If form is valid, submit it
        if (isValid) {
            // Collect form data
            const formData = {
                receipt_type: receiptType,
                items: []
            };
            
            // Common fields
            formData.invoice_number = tabPane.find('.receipt-number').val();
            formData.notes = tabPane.find('.notes').val();
            formData.discount = tabPane.find('.discount').val() || 0;
            
            // Receipt type specific fields
            if (receiptType === 'selling') {
                formData.customer_id = tabPane.find('.customer-select').val();
                formData.payment_type = tabPane.find('.payment-type').val();
                formData.price_type = tabPane.find('.price-type').val();
                formData.shipping_cost = tabPane.find('.shipping-cost').val() || 0;
                formData.other_costs = tabPane.find('.other-costs').val() || 0;
                formData.date = tabPane.find('.sale-date').val();
                
                // Collect selling items
                tabPane.find('.items-list tr').each(function() {
                    const product_id = $(this).find('.product-select').val();
                    const quantity = $(this).find('.quantity').val();
                    
                    if (product_id && quantity && parseFloat(quantity) > 0) {
                        const unit_type = $(this).find('.unit-type').val() || 'piece'; // Default to 'piece' if not found
                        formData.items.push({
                            product_id: product_id,
                            quantity: quantity,
                            unit_type: unit_type,
                            unit_price: $(this).find('.unit-price').val(),
                            total_price: $(this).find('.total').val()
                        });
                    }
                });
            } else if (receiptType === 'buying') {
                formData.supplier_id = tabPane.find('.supplier-select').val();
                formData.payment_type = tabPane.find('.payment-type').val();
                formData.date = tabPane.find('.purchase-date').val();
                
                // Collect buying items
                tabPane.find('.items-list tr').each(function() {
                    const product_id = $(this).find('.product-select').val();
                    const quantity = $(this).find('.quantity').val();
                    
                    if (product_id && quantity && parseFloat(quantity) > 0) {
                        formData.items.push({
                            product_id: product_id,
                            quantity: quantity,
                            unit_price: $(this).find('.unit-price').val(),
                            total_price: $(this).find('.total').val()
                        });
                    }
                });
            } else if (receiptType === 'wasting') {
                formData.responsible_id = tabPane.find('.responsible-select').val();
                formData.adjustment_reason = tabPane.find('.adjustment-reason').val();
                formData.date = tabPane.find('.adjustment-date').val();
                
                // Collect adjustment items
                tabPane.find('.items-list tr').each(function() {
                    const product_id = $(this).find('.product-select').val();
                    const adjusted_quantity = $(this).find('.adjusted-quantity').val();
                    
                    if (product_id && adjusted_quantity && parseFloat(adjusted_quantity) > 0) {
                        formData.items.push({
                            product_id: product_id,
                            expected_quantity: $(this).find('.current-quantity').val(),
                            actual_quantity: adjusted_quantity,
                            price: $(this).find('.price').val(),
                            total_price: $(this).find('.total').val()
                        });
                    }
                });
            }
            
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
                            confirmButtonText: 'باشە'
                        }).then((result) => {
                            // Don't redirect, just reset the current tab or create a new one
                            // Clear form fields, but keep the customer/supplier selection
                            const currentTab = $('.tab-pane.active');
                            const receiptType = currentTab.data('receipt-type');
                            
                            // Save current selection data
                            let savedData = {};
                            if (receiptType === 'selling') {
                                savedData.customer_id = currentTab.find('.customer-select').val();
                                savedData.payment_type = currentTab.find('.payment-type').val();
                                savedData.price_type = currentTab.find('.price-type').val();
                            } else if (receiptType === 'buying') {
                                savedData.supplier_id = currentTab.find('.supplier-select').val();
                                savedData.payment_type = currentTab.find('.payment-type').val();
                            }
                            
                            // Reset items
                            currentTab.find('.items-list tr:not(:first)').remove();
                            currentTab.find('.items-list tr:first').find('input').val('');
                            currentTab.find('.items-list tr:first').find('.product-select').val(null).trigger('change');
                            
                            // Reset totals
                            currentTab.find('.subtotal').val('0.00');
                            currentTab.find('.discount').val('0');
                            currentTab.find('.shipping-cost').val('0');
                            currentTab.find('.other-costs').val('0');
                            currentTab.find('.grand-total').val('0.00');
                            
                            // Reset invoice number
                            currentTab.find('.receipt-number').val('');
                            
                            // Set date to today
                            const today = new Date().toISOString().split('T')[0];
                            currentTab.find('.sale-date, .purchase-date, .adjustment-date').val(today);
                            
                            // Restore saved selections
                            if (receiptType === 'selling') {
                                if (savedData.customer_id) currentTab.find('.customer-select').val(savedData.customer_id).trigger('change');
                                if (savedData.payment_type) currentTab.find('.payment-type').val(savedData.payment_type);
                                if (savedData.price_type) currentTab.find('.price-type').val(savedData.price_type);
                            } else if (receiptType === 'buying') {
                                if (savedData.supplier_id) currentTab.find('.supplier-select').val(savedData.supplier_id).trigger('change');
                                if (savedData.payment_type) currentTab.find('.payment-type').val(savedData.payment_type);
                            }
                        });
                    } else {
                        Swal.fire('هەڵە', response.message || 'هەڵەیەک ڕوویدا', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا لە کاتی پاشەکەوتکردن', 'error');
                }
            });
        }
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
            showConfirmButton: false
        });
    });
});