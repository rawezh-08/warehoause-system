$(document).ready(function() {
    // Constants
    const RECEIPT_TYPES = {
        SELLING: 'selling',
        BUYING: 'buying',
        WASTING: 'wasting'
    };

    const PAYMENT_TYPES = {
        CASH: 'cash',
        CREDIT: 'credit'
    };

    const PRICE_TYPES = {
        SINGLE: 'single',
        WHOLESALE: 'wholesale'
    };

    // Tab counter for each type
    let tabCounters = {
        'selling': 1,
        'buying': 0,
        'wasting': 0
    };

    // Current active receipt type
    let activeReceiptType = RECEIPT_TYPES.SELLING;

    // Initialize the page
    function initializePage() {
        // Set up event listeners for receipt type buttons
        $('.receipt-type-btn').on('click', function() {
            const type = $(this).data('type');
            activeReceiptType = type;
            updateReceiptType(type);
        });

        // Initialize the first tab
        updateReceiptType(RECEIPT_TYPES.SELLING);
        
        // Initialize Select2 for the first tab
        initializeSelectsForTab('selling-1');
        
        // Set today's date as default
        setDefaultDates();
        
        // Initialize event listeners for the first tab
        initTabEventListeners('selling-1', RECEIPT_TYPES.SELLING);

        // Add new tab button handler
        $('#addNewTab').on('click', function() {
            addNewTab();
        });
    }

    // Initialize selects specifically for a given tab
    function initializeSelectsForTab(tabId) {
        console.log(`Initializing selects for tab: ${tabId}`);
        
        // Customer select
        $(`#${tabId} .customer-select`).each(function() {
            // If already initialized, destroy first
            if ($(this).hasClass('select2-hidden-accessible')) {
                try {
                    $(this).select2('destroy');
                } catch (e) {
                    console.error('Error destroying customer select:', e);
                }
            }
            
            console.log(`Initializing customer select in ${tabId}`);
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    ajax: {
                    url: '../../api/search_customers.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                        },
                        cache: true
                    },
                placeholder: 'کڕیار هەڵبژێرە...',
                minimumInputLength: 1,
                language: {
                    inputTooShort: function() {
                        return 'تکایە لانیکەم یەک پیت بنووسە...';
                    },
                    searching: function() {
                        return 'گەڕان...';
                    },
                    noResults: function() {
                        return 'هیچ ئەنجامێک نەدۆزرایەوە';
                    }
                }
            }).on('select2:open', function() {
        setTimeout(function() {
                    $('.select2-search__field:visible').focus();
                }, 100);
            });
        });
        
        // Supplier select
        $(`#${tabId} .supplier-select`).each(function() {
            // If already initialized, destroy first
            if ($(this).hasClass('select2-hidden-accessible')) {
                try {
                    $(this).select2('destroy');
                } catch (e) {
                    console.error('Error destroying supplier select:', e);
                }
            }
            
            console.log(`Initializing supplier select in ${tabId}`);
            $(this).select2({
            theme: 'bootstrap-5',
            width: '100%',
            ajax: {
                    url: '../../api/search_suppliers.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                },
                cache: true
            },
                placeholder: 'فرۆشیار هەڵبژێرە...',
                minimumInputLength: 1,
                language: {
                    inputTooShort: function() {
                        return 'تکایە لانیکەم یەک پیت بنووسە...';
                    },
                    searching: function() {
                        return 'گەڕان...';
                    },
                    noResults: function() {
                        return 'هیچ ئەنجامێک نەدۆزرایەوە';
                    }
                }
        }).on('select2:open', function() {
            setTimeout(function() {
                    $('.select2-search__field:visible').focus();
                }, 100);
            });
        });
        
        // Product select
        $(`#${tabId} .product-select`).each(function() {
            // If already initialized, destroy first
            if ($(this).hasClass('select2-hidden-accessible')) {
                try {
                    $(this).select2('destroy');
                } catch (e) {
                    console.error('Error destroying product select:', e);
                }
            }
            
            console.log(`Initializing product select in ${tabId}`);
            $(this).select2({
            theme: 'bootstrap-5',
            width: '100%',
            ajax: {
                    url: '../../api/search_products.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                },
                cache: true
            },
                placeholder: 'کاڵا هەڵبژێرە...',
                minimumInputLength: 1,
                language: {
                    inputTooShort: function() {
                        return 'تکایە لانیکەم یەک پیت بنووسە...';
                    },
                    searching: function() {
                        return 'گەڕان...';
                    },
                    noResults: function() {
                        return 'هیچ ئەنجامێک نەدۆزرایەوە';
                    }
                }
        }).on('select2:open', function() {
            setTimeout(function() {
                    $('.select2-search__field:visible').focus();
                }, 100);
            }).on('select2:select', function(e) {
                const data = e.params.data;
                const row = $(this).closest('tr');
                
                // Set product image
                if (data.image) {
                    row.find('.product-image-cell').html(`<img src="${data.image}" alt="${data.text}" class="img-thumbnail">`);
                } else {
                    row.find('.product-image-cell').html('<i class="fas fa-image text-muted"></i>');
                }
                
                // Set product price based on price type
                const tabPane = row.closest('.tab-pane');
                const priceType = tabPane.find('.price-type').val();
                const price = priceType === PRICE_TYPES.WHOLESALE ? data.wholesale_price : data.retail_price;
                row.find('.unit-price').val(price);
                
                // Update totals
                updateRowTotal(row);
            });
        });
    }

    // Function to create a unique ID
    function generateUniqueId(prefix) {
        return prefix + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Set default dates
    function setDefaultDates() {
        const today = new Date().toISOString().split('T')[0];
        $('.sale-date, .purchase-date, .adjustment-date').val(today);
    }

    // Update receipt type
    function updateReceiptType(type) {
        // Remove active class from all receipt type buttons
        $('.receipt-type-btn').removeClass('active');
        
        // Add active class to clicked button
        $(`.receipt-type-btn[data-type="${type}"]`).addClass('active');
        
        // Check if there's an existing tab of this type
        let hasTabOfType = false;
        $('.receipt-tab').each(function() {
            const tabId = $(this).attr('data-bs-target').replace('#', '');
            if (tabId.startsWith(type)) {
                hasTabOfType = true;
                $(this).tab('show');
                return false;
            }
        });
        
        // If no tab exists for this type, create one
        if (!hasTabOfType) {
            addNewTab();
        }
    }

    // Get receipt type label
    function getReceiptTypeLabel(type) {
        switch(type) {
            case RECEIPT_TYPES.SELLING: return 'فرۆشتن';
            case RECEIPT_TYPES.BUYING: return 'کڕین';
            case RECEIPT_TYPES.WASTING: return 'ڕێکخستنەوە';
            default: return type;
        }
    }

    // Close tab handler
    $(document).on('click', '.close-tab', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const tabBtn = $(this).closest('.nav-link');
        const tabId = tabBtn.attr('data-bs-target').replace('#', '');
        const tabType = tabId.split('-')[0];
        
        // Remove tab content and button
        $(`#${tabId}`).remove();
        tabBtn.closest('li').remove();
        
        // If this was the last tab of its type, create a new one
        let hasOtherTabOfType = false;
        $('.receipt-tab').each(function() {
            const otherTabId = $(this).attr('data-bs-target').replace('#', '');
            if (otherTabId.startsWith(tabType)) {
                hasOtherTabOfType = true;
                return false;
            }
        });
        
        if (!hasOtherTabOfType) {
            addNewTab();
        }
    });

    // Fetch next invoice number
    function fetchNextInvoiceNumber(type) {
            $.ajax({
                url: '../../api/get_next_invoice.php',
                type: 'GET',
            data: { type: type },
                success: function(response) {
                    if (response.success) {
                    $(`.tab-pane[data-receipt-type="${type}"]:visible .receipt-number`).val(response.invoice_number);
                    }
                }
            });
    }

    // Initialize tab event listeners
    function initTabEventListeners(tabId, tabType) {
        // Price type change handler (selling only)
        if (tabType === RECEIPT_TYPES.SELLING) {
            $(`#${tabId} .price-type`).on('change', function() {
                const priceType = $(this).val();
                const itemsList = $(`#${tabId} .items-list`);
                
                itemsList.find('tr').each(function() {
                    const row = $(this);
                    const productSelect = row.find('.product-select');
                    const productData = productSelect.select2('data')[0];
                    
                    if (productData && productData.id) {
                        if (priceType === PRICE_TYPES.WHOLESALE && productData.wholesale_price) {
                            row.find('.unit-price').val(productData.wholesale_price);
                        } else {
                            row.find('.unit-price').val(productData.retail_price);
                        }
                        
                        calculateRowTotal(row);
                    }
                });
            });
        }
        
        // Product row quantity and price change handlers
        $(`#${tabId} .quantity, #${tabId} .unit-price`).on('input', function() {
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
            
            if (itemsList.find('tr').length > 1) {
                row.remove();
                
                // Renumber rows
                itemsList.find('tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
                
                calculateGrandTotal(tabId);
            }
        });
        
        // Payment type change handler
        $(`#${tabId} .payment-type`).on('change', function() {
            const paymentType = $(this).val();
            const tabPane = $(this).closest('.tab-pane');
            const creditFields = tabPane.find('.credit-payment-fields');
            const grandTotal = parseFloat(tabPane.find('.grand-total').val()) || 0;
            
            if (paymentType === PAYMENT_TYPES.CREDIT) {
                creditFields.show();
                tabPane.find('.paid-amount').val(0);
                tabPane.find('.remaining-amount').val(grandTotal);
            } else {
                creditFields.hide();
                tabPane.find('.paid-amount').val(grandTotal);
                tabPane.find('.remaining-amount').val(0);
            }
            
            updateRemainingAmount(tabPane);
        });
        
        // Paid amount change handler
        $(`#${tabId} .paid-amount`).on('input', function() {
            updateRemainingAmount($(this).closest('.tab-pane'));
        });
        
        // Save button handler
        $(`#${tabId} .save-btn`).on('click', function(e) {
            e.preventDefault();
            saveReceipt(tabId, tabType);
        });
    }

    // Add a new row to the items table
    function addNewRow(itemsList) {
        const lastRow = itemsList.find('tr:last');
        const newRow = lastRow.clone();
        const rowNumber = itemsList.find('tr').length + 1;
        
        // Update row number
        newRow.find('td:first').text(rowNumber);
        
        // Clear all inputs
        newRow.find('input').val('');
        
        // Reset the product select
        const productCell = newRow.find('td:nth-child(2)');
        
        // Generate unique ID for the new select
        const uniqueId = generateUniqueId('product');
        
        // Create a new select element with unique ID
        productCell.html(`<select class="form-control product-select" id="${uniqueId}" style="width: 100%"></select>`);
        
        // Clear product image
        newRow.find('.product-image-cell').empty();
        
        // Append the new row
        newRow.appendTo(itemsList);
        
        // Initialize the product select in the new row
        const tabId = itemsList.closest('.tab-pane').attr('id');
        console.log(`Adding new row in tab ${tabId} with product select ID ${uniqueId}`);
        
        // Initialize the product select that was just added
        const productSelect = $(`#${uniqueId}`);
        productSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            ajax: {
                url: '../../api/search_products.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            placeholder: 'کاڵا هەڵبژێرە...',
            minimumInputLength: 1,
            language: {
                inputTooShort: function() {
                    return 'تکایە لانیکەم یەک پیت بنووسە...';
                },
                searching: function() {
                    return 'گەڕان...';
                },
                noResults: function() {
                    return 'هیچ ئەنجامێک نەدۆزرایەوە';
                }
            }
        }).on('select2:open', function() {
            setTimeout(function() {
                $('.select2-search__field:visible').focus();
            }, 100);
        }).on('select2:select', function(e) {
            const data = e.params.data;
            const row = $(this).closest('tr');
            
            // Set product image
            if (data.image) {
                row.find('.product-image-cell').html(`<img src="${data.image}" alt="${data.text}" class="img-thumbnail">`);
            } else {
                row.find('.product-image-cell').html('<i class="fas fa-image text-muted"></i>');
            }
            
            // Set product price based on price type
            const tabPane = row.closest('.tab-pane');
            const priceType = tabPane.find('.price-type').val();
            const price = priceType === PRICE_TYPES.WHOLESALE ? data.wholesale_price : data.retail_price;
            row.find('.unit-price').val(price);
            
            // Update totals
            updateRowTotal(row);
        });
        
        return newRow;
    }

    // Calculate row total
    function calculateRowTotal(row) {
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const total = quantity * unitPrice;
        
        row.find('.total').val(total.toFixed(2));
        
        // Update grand total
        const tabId = row.closest('.tab-pane').attr('id');
        calculateGrandTotal(tabId);
    }

    // Calculate grand total
    function calculateGrandTotal(tabId) {
        const tabPane = $(`#${tabId}`);
        let subtotal = 0;
        
        // Sum all row totals
        tabPane.find('.total').each(function() {
            subtotal += parseFloat($(this).val()) || 0;
        });
        
        const discount = parseFloat(tabPane.find('.discount').val()) || 0;
        const shippingCost = parseFloat(tabPane.find('.shipping-cost').val()) || 0;
        const otherCosts = parseFloat(tabPane.find('.other-costs').val()) || 0;
        
        // Calculate grand total
        const grandTotal = subtotal - discount + shippingCost + otherCosts;
        
        // Update totals
        tabPane.find('.subtotal').val(subtotal.toFixed(2));
        tabPane.find('.grand-total').val(grandTotal.toFixed(2));
        
        // Update remaining amount if credit payment
        if (tabPane.find('.payment-type').val() === PAYMENT_TYPES.CREDIT) {
            const paidAmount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
            const remainingAmount = grandTotal - paidAmount;
            tabPane.find('.remaining-amount').val(remainingAmount.toFixed(2));
        }
    }

    // Update remaining amount
    function updateRemainingAmount(tabPane) {
        const grandTotal = parseFloat(tabPane.find('.grand-total').val()) || 0;
        const paidAmount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
        const remainingAmount = grandTotal - paidAmount;
        
        tabPane.find('.remaining-amount').val(remainingAmount.toFixed(2));
    }

    // Save receipt
    function saveReceipt(tabId, tabType) {
        const tabPane = $(`#${tabId}`);
        const formData = collectReceiptData(tabPane, tabType);
        
        if (!formData) return;
        
        // Show loading state
        const saveBtn = tabPane.find('.save-btn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> جاری پاشەکەوتکردن...');
        
        // Send data to server
        $.ajax({
            url: '../../api/save_receipt.php',
            type: 'POST',
            data: { 
                type: tabType,
                data: JSON.stringify(formData)
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'سەرکەوتوو بوو!',
                        text: 'پسوڵە بە سەرکەوتووی پاشەکەوت کرا.',
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        resetForm(tabPane, tabType);
                    });
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'هەڵەیەک ڕوویدا لەکاتی پاشەکەوتکردن.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵەیەکی پەیوەندی ڕوویدا.',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            },
            complete: function() {
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> پاشەکەوتکردن');
            }
        });
    }

    // Collect receipt data
    function collectReceiptData(tabPane, tabType) {
        const products = [];
        let valid = true;
        
        tabPane.find('.items-list tr').each(function() {
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
            
            const productData = {
                product_id: productId,
                unit_price: unitPrice,
                quantity: quantity
            };
            
            if (tabType === RECEIPT_TYPES.SELLING) {
                productData.unit_type = row.find('.unit-type').val();
            }
            
            products.push(productData);
        });
        
        if (!valid) return null;
        
        const formData = {
            receipt_type: tabType,
            invoice_number: tabPane.find('.receipt-number').val(),
            payment_type: tabPane.find('.payment-type').val(),
            date: tabPane.find(`.${tabType}-date`).val(),
            discount: parseFloat(tabPane.find('.discount').val()) || 0,
            notes: tabPane.find('.notes').val(),
            products: products
        };
        
        if (tabType === RECEIPT_TYPES.SELLING) {
            const customerId = tabPane.find('.customer-select').val();
        if (!customerId) {
            Swal.fire('خەتا', 'تکایە کڕیار هەڵبژێرە', 'error');
            return null;
        }
        
            formData.customer_id = customerId;
            formData.price_type = tabPane.find('.price-type').val();
            formData.shipping_cost = parseFloat(tabPane.find('.shipping-cost').val()) || 0;
            formData.other_costs = parseFloat(tabPane.find('.other-costs').val()) || 0;
        } else if (tabType === RECEIPT_TYPES.BUYING) {
            const supplierId = tabPane.find('.supplier-select').val();
            if (!supplierId) {
                Swal.fire('خەتا', 'تکایە فرۆشیار هەڵبژێرە', 'error');
                return null;
            }
            
            formData.supplier_id = supplierId;
        }
        
        if (formData.payment_type === PAYMENT_TYPES.CREDIT) {
            formData.paid_amount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
        }
        
        return formData;
    }

    // Reset form
    function resetForm(tabPane, tabType) {
        // Reset all inputs except receipt number
        tabPane.find('input:not(.receipt-number)').val('');
        tabPane.find('textarea').val('');
        tabPane.find('select:not(.payment-type, .price-type, .unit-type)').val(null).trigger('change');
        
        // Reset to defaults
        tabPane.find('.payment-type').val(PAYMENT_TYPES.CASH).trigger('change');
        tabPane.find('.price-type').val(PRICE_TYPES.SINGLE);
        tabPane.find('.shipping-cost, .other-costs, .discount').val('0');
        
        // Reset items
        const tbody = tabPane.find('.items-list');
        const firstRow = tbody.find('tr:first');
        
        // Clear first row
        firstRow.find('input').val('');
        firstRow.find('select.product-select').val(null).trigger('change');
        firstRow.find('.product-image-cell').empty();
        
        // Remove other rows
        tbody.find('tr:not(:first)').remove();
        
        // Set today's date
        setDefaultDates();
        
        // Update totals
        calculateGrandTotal(tabPane.attr('id'));
        
        // Get new receipt number
        fetchNextInvoiceNumber(tabType);
    }

    // Add new tab
    function addNewTab() {
        const newTabType = activeReceiptType;
        tabCounters[newTabType]++;
        
        console.log(`Adding new tab: ${newTabType}-${tabCounters[newTabType]}`);
        
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
        $(tabBtn).insertBefore($('#addNewTab').parent());
        
        // Get tab content template
        let tabContent = '';
        if (newTabType === RECEIPT_TYPES.SELLING) {
            tabContent = $('#selling-template').html();
        } else if (newTabType === RECEIPT_TYPES.BUYING) {
            tabContent = $('#buying-template').html();
        } else if (newTabType === RECEIPT_TYPES.WASTING) {
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
        
        // Ensure all selects in the new tab have unique IDs
        $(`#${tabId} select`).each(function() {
            const baseClass = $(this).attr('class').split(' ')[0];
            const uniqueId = generateUniqueId(baseClass);
            $(this).attr('id', uniqueId);
        });
        
        // Set default date in the new tab
        $(`#${tabId} .sale-date, #${tabId} .purchase-date, #${tabId} .adjustment-date`).val(new Date().toISOString().split('T')[0]);
        
        console.log(`New tab created: ${tabId}`);
        
        // Activate the new tab
        $(`#tab-${tabId}`).tab('show');
        
        // Initialize Select2 and event listeners for the new tab only
        setTimeout(function() {
            console.log(`Setting up selects for new tab: ${tabId}`);
            initializeSelectsForTab(tabId);
            initTabEventListeners(tabId, newTabType);
            
            // Fetch next invoice number for sales
            if (newTabType === RECEIPT_TYPES.SELLING) {
                fetchNextInvoiceNumber(newTabType);
            }
        }, 300);
    }

    // Add tab shown event handler to initialize selects properly when tab is shown
    $(document).on('shown.bs.tab', '.receipt-tab', function(e) {
        const tabId = $(e.target).attr('data-bs-target').replace('#', '');
        console.log(`Tab shown: ${tabId}`);
        
        // Make sure select2 instances in this tab are properly initialized
        initializeSelectsForTab(tabId);
    });

    // Initialize the page
    initializePage();
});