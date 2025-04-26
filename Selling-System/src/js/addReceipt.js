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

        // Fetch initial invoice number
        fetchNextInvoiceNumber(RECEIPT_TYPES.SELLING);

        // Add new tab button handler
        $('#addNewTab').on('click', function() {
            addNewTab();
        });
        
        // Fix for tab navigation in Bootstrap
        setupTabNavigation();
    }

    // Setup proper tab navigation
    function setupTabNavigation() {
        // Disable Bootstrap's built-in tab handling for our custom tabs
        // and override with our own implementation
        $('.nav-tabs').on('click', '.receipt-tab', function(e) {
            // Prevent default bootstrap behavior
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Nav tab clicked: Custom handler');
            
            const tabTarget = $(this).attr('data-bs-target');
            const tabId = tabTarget.replace('#', '');
            
            // Deactivate all tabs and panes first
            $('.receipt-tab').removeClass('active');
            $('.tab-pane').removeClass('show active');
            
            // Activate this tab and its corresponding pane
            $(this).addClass('active');
            $(tabTarget).addClass('show active');
            
            // Initialize selects for this tab
            setTimeout(() => {
                console.log('Reinitializing selects after nav tab click for:', tabId);
                initializeSelectsForTab(tabId);
            }, 50);
            
            console.log('Custom tab navigation - Active tabs:', $('.receipt-tab.active').length);
            console.log('Custom tab navigation - Active panes:', $('.tab-pane.show.active').length);
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
                        return { q: params.term || '' };
                    },
                    processResults: function(data) {
                        return { results: data };
                        },
                        cache: true
                    },
                placeholder: 'کڕیار هەڵبژێرە...',
                minimumInputLength: 0,
                allowClear: true,
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
                        return { q: params.term || '' };
                    },
                    processResults: function(data) {
                        return { results: data };
                },
                cache: true
            },
                placeholder: 'فرۆشیار هەڵبژێرە...',
                minimumInputLength: 0,
                allowClear: true,
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
                },
                templateResult: formatSupplierOption,
                templateSelection: formatSupplierSelection
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
                        return { 
                            q: params.term || '',
                            show_initial: !params.term ? '1' : '0'  // Show initial results if no search term
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: false  // Disable caching to always get fresh results
                },
                placeholder: 'کاڵا هەڵبژێرە...',
                minimumInputLength: 0,
                allowClear: true,
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
                },
                templateResult: formatProductOption,
                templateSelection: formatProductSelection
            }).on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field:visible').focus();
                    // Trigger search to show initial results
                    $('.select2-search__field:visible').trigger('input');
                }, 100);
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
        console.log('updateReceiptType called with type:', type);
        console.log('Current active tabs:', $('.receipt-tab.active').length);
        console.log('Current active panes:', $('.tab-pane.show.active').length);
        
        // Remove active class from all receipt type buttons
        $('.receipt-type-btn').removeClass('active');
        
        // Add active class to clicked button
        $(`.receipt-type-btn[data-type="${type}"]`).addClass('active');
        
        // Update active receipt type
        activeReceiptType = type;
        
        // Check if there's an existing tab of this type
        let existingTab = null;
        $('.receipt-tab').each(function() {
            const tabId = $(this).attr('data-bs-target').replace('#', '');
            console.log('Checking tab:', tabId);
            if (tabId.startsWith(type)) {
                existingTab = $(this);
                console.log('Found existing tab:', tabId);
                return false;
            }
        });
        
        // If no tab exists for this type, create one
        if (!existingTab) {
            console.log('No existing tab found, creating new tab');
            addNewTab();
        } else {
            console.log('Showing existing tab');
            // First deactivate all tabs and panes
            $('.receipt-tab').removeClass('active');
            $('.tab-pane').removeClass('show active');
            
            // Show existing tab
            existingTab.addClass('active');
            const tabId = existingTab.attr('data-bs-target');
            console.log('Activating tab and pane:', tabId);
            $(tabId).addClass('show active');
            
            // Update receipt number for the active tab
            fetchNextInvoiceNumber(type);
        }
        
        console.log('After update - Active tabs:', $('.receipt-tab.active').length);
        console.log('After update - Active panes:', $('.tab-pane.show.active').length);
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
        console.log(`Initializing event listeners for tab: ${tabId}, type: ${tabType}`);
        
        // Payment type change handler
        $(`#${tabId} .payment-type`).on('change', function() {
            const paymentType = $(this).val();
            const creditFields = $(`#${tabId} .credit-payment-fields`);
            
            if (paymentType === PAYMENT_TYPES.CREDIT) {
                creditFields.show();
            } else {
                creditFields.hide();
            }
        });
        
        // Price type change handler (selling only)
        if (tabType === RECEIPT_TYPES.SELLING) {
            $(`#${tabId} .price-type`).on('change', function() {
                const priceType = $(this).val();
                const tabPane = $(this).closest('.tab-pane');
                
                // Update prices for all existing products
                tabPane.find('.items-list tr').each(function() {
                    const row = $(this);
                    const productSelect = row.find('.product-select');
                    
                    if (productSelect.val()) {
                        const productData = productSelect.select2('data')[0];
                        if (productData) {
                            // Get base price based on price type
                            const basePrice = priceType === PRICE_TYPES.WHOLESALE ? 
                                parseFloat(productData.wholesale_price || 0) : 
                                parseFloat(productData.retail_price || 0);
                            
                            // Adjust price based on unit type
                            const unitType = row.find('.unit-type').val();
                            let finalPrice = basePrice;
                            
                            if (unitType === 'box' && productData.pieces_per_box) {
                                finalPrice = Math.round(basePrice * parseInt(productData.pieces_per_box || 0));
                            } else if (unitType === 'set' && productData.pieces_per_box && productData.boxes_per_set) {
                                finalPrice = Math.round(basePrice * parseInt(productData.pieces_per_box || 0) * parseInt(productData.boxes_per_set || 0));
                            }
                            
                            // Update the unit price
                            row.find('.unit-price').val(finalPrice);
                            
                            // Recalculate row total
                            calculateRowTotal(row);
                        }
                    }
                });
                
                // Recalculate grand total
                calculateGrandTotal(tabId);
            });
        }
        
        // Add row button handler
        $(`#${tabId} .add-row-btn`).on('click', function() {
            const itemsList = $(`#${tabId} .items-list`);
            addNewRow(itemsList);
        });
        
        // Remove row button handler (using event delegation for dynamically added rows)
        $(`#${tabId}`).on('click', '.remove-row', function() {
            const row = $(this).closest('tr');
            const itemsList = row.closest('tbody');
            
            // Don't remove if it's the only row
            if (itemsList.find('tr').length <= 1) {
                // Just clear the row instead
                row.find('input').val('');
                row.find('.product-select').val(null).trigger('change');
                row.find('.product-image-cell').empty();
            } else {
                // Remove the row with animation
                row.fadeOut(300, function() {
                    row.remove();
                    
                    // Update row numbers
                    itemsList.find('tr').each(function(index) {
                        $(this).find('td:first').text(index + 1);
                    });
                    
                    // Update totals
                    calculateGrandTotal(tabId);
                });
            }
        });
        
        // Product select change handler (for both selling and buying)
        $(`#${tabId}`).on('select2:select', '.product-select', function(e) {
            const data = e.params.data;
            const row = $(this).closest('tr');
            
            // Set product image
            if (data.image) {
                row.find('.product-image-cell').html(`
                    <div class="product-image-container">
                        <img src="${data.image}" alt="${data.text}" class="product-table-image">
                    </div>
                `);
            } else {
                row.find('.product-image-cell').html(`
                    <div class="product-image-container">
                        <div class="no-image-placeholder">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                `);
            }
            
            // Update unit type dropdown based on product data
            const unitTypeSelect = row.find('.unit-type');
            unitTypeSelect.empty(); // Clear existing options
            
            // Always add piece/unit option
            unitTypeSelect.append(`<option value="piece">دانە</option>`);
            
            // Add box option if pieces_per_box exists and greater than 0
            if (data.pieces_per_box && parseInt(data.pieces_per_box) > 0) {
                unitTypeSelect.append(`<option value="box">کارتۆن</option>`);
                
                // Add set option if boxes_per_set exists and greater than 0
                if (data.boxes_per_set && parseInt(data.boxes_per_set) > 0) {
                    unitTypeSelect.append(`<option value="set">سێت</option>`);
                }
            }
            
            // Set default unit type to piece
            unitTypeSelect.val('piece');
            
            // Set product price based on price type and unit type
            const tabPane = row.closest('.tab-pane');
            const tabType = tabPane.attr('data-receipt-type');
            const priceType = tabPane.find('.price-type').val();
            
            // Get base price based on price type (single unit price)
            let basePrice;
            if (tabType === RECEIPT_TYPES.SELLING) {
                basePrice = priceType === PRICE_TYPES.WHOLESALE ? 
                    parseFloat(data.wholesale_price || 0) : 
                    parseFloat(data.retail_price || 0);
            } else if (tabType === RECEIPT_TYPES.BUYING) {
                basePrice = parseFloat(data.purchase_price || 0);
            } else if (tabType === RECEIPT_TYPES.WASTING) {
                basePrice = parseFloat(data.purchase_price || 0);
            }
            
            // Adjust price based on unit type
            const unitType = unitTypeSelect.val();
            
            console.log(`Product selected: ${data.text}`);
            console.log(`Base price (${priceType}): ${basePrice}`);
            console.log(`Pieces per box: ${data.pieces_per_box}`);
            console.log(`Boxes per set: ${data.boxes_per_set}`);
            console.log(`Current unit type: ${unitType}`);
            
            if (unitType === 'box' && data.pieces_per_box) {
                basePrice = Math.round(basePrice * parseInt(data.pieces_per_box || 0));
                console.log(`Box price calculated: ${basePrice}`);
            } else if (unitType === 'set' && data.pieces_per_box && data.boxes_per_set) {
                basePrice = Math.round(basePrice * parseInt(data.pieces_per_box || 0) * parseInt(data.boxes_per_set || 0));
                console.log(`Set price calculated: ${basePrice}`);
            }
            
            // Update the appropriate price field based on tab type
            if (tabType === RECEIPT_TYPES.WASTING) {
                row.find('.price').val(basePrice);
            } else {
                row.find('.unit-price').val(basePrice);
            }
            
            // Update totals
            calculateRowTotal(row);
        });
        
        // Unit type change handler (for both selling and buying)
        $(`#${tabId}`).on('change', '.unit-type', function() {
            const row = $(this).closest('tr');
            const unitType = $(this).val();
            const productSelect = row.find('.product-select');
            
            if (!productSelect.val()) return;
            
            // Get product data
            const productData = productSelect.select2('data')[0];
            if (!productData) return;
            
            // Update unit price based on unit type
            let newPrice = 0;
            const tabPane = row.closest('.tab-pane');
            const tabType = tabPane.attr('data-receipt-type');
            const priceType = tabPane.find('.price-type').val();
            
            // Get base price based on tab type and price type
            let basePrice;
            if (tabType === RECEIPT_TYPES.SELLING) {
                basePrice = priceType === PRICE_TYPES.WHOLESALE ? 
                    parseFloat(productData.wholesale_price || 0) : 
                    parseFloat(productData.retail_price || 0);
            } else if (tabType === RECEIPT_TYPES.BUYING) {
                basePrice = parseFloat(productData.purchase_price || 0);
            } else if (tabType === RECEIPT_TYPES.WASTING) {
                basePrice = parseFloat(productData.purchase_price || 0);
            }
            
            // Adjust price based on unit type
            if (unitType === 'piece') {
                newPrice = basePrice;
            } else if (unitType === 'box' && productData.pieces_per_box) {
                newPrice = Math.round(basePrice * parseInt(productData.pieces_per_box));
            } else if (unitType === 'set' && productData.pieces_per_box && productData.boxes_per_set) {
                newPrice = Math.round(basePrice * parseInt(productData.pieces_per_box) * parseInt(productData.boxes_per_set));
            }
            
            // Update the appropriate price field based on tab type
            if (tabType === RECEIPT_TYPES.WASTING) {
                row.find('.price').val(newPrice);
            } else {
                row.find('.unit-price').val(newPrice);
            }
            
            // Recalculate row total
            calculateRowTotal(row);
        });
        
        // Unit price, quantity change handlers
        $(`#${tabId}`).on('input', '.unit-price, .quantity, .price, .adjusted-quantity', function() {
            const row = $(this).closest('tr');
            calculateRowTotal(row);
        });
        
        // Refresh button handler
        $(`#${tabId} .refresh-btn`).on('click', function() {
            resetForm($(`#${tabId}`), tabType);
        });
        
        // Print button handler
        $(`#${tabId} .print-btn`).on('click', function() {
            // Check if we have a saved receipt ID
            const receiptId = $(`#${tabId}`).data('saved-receipt-id');
            
            if (receiptId) {
                // Open print window for the saved receipt
                window.open(`../../views/admin/receipt/print_receipt.php?sale_id=${receiptId}`, '_blank');
            } else {
                Swal.fire({
                    title: 'پسوڵە پاشەکەوت نەکراوە',
                    text: 'تکایە سەرەتا پسوڵەکە پاشەکەوت بکە',
                    icon: 'warning',
                    confirmButtonText: 'باشە'
                });
            }
        });
        
        // Shipping cost, discount, other cost change handlers
        $(`#${tabId}`).on('input', '.shipping-cost, .discount, .other-cost', function() {
            calculateGrandTotal(tabId);
        });
        
        // Paid amount change handler
        $(`#${tabId}`).on('input', '.paid-amount', function() {
            updateRemainingAmount($(`#${tabId}`));
        });
        
        // Save button handler
        $(`#${tabId} .save-btn`).on('click', function() {
            saveReceipt(tabId, tabType, false);
        });

        // Add special buttons for selling tab
        if (tabType === RECEIPT_TYPES.SELLING) {
            // Add costs and profits button
            const costsBtn = $('<button>')
                .addClass('btn btn-info costs-btn')
                .html('<i class="fas fa-calculator me-2"></i>نرخ و قازانج')
                .on('click', function() {
                    showProductCostsAndProfits(tabId);
                });
            
            // Add the costs button next to the save button
            $(`#${tabId} .save-btn`).before(costsBtn);

            // Add draft button handler
            $(`#${tabId} .draft-btn`).on('click', function() {
                saveReceipt(tabId, tabType, true);
            });
        }
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
        
        // Remove any existing info button
        newRow.find('.product-info-btn').remove();
        
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
                    return { q: params.term || '' };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            placeholder: 'کاڵا هەڵبژێرە...',
            minimumInputLength: 0,
            allowClear: true,
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
            },
            templateResult: formatProductOption,
            templateSelection: formatProductSelection
        }).on('select2:open', function() {
            setTimeout(function() {
                $('.select2-search__field:visible').focus();
            }, 100);
        }).on('select2:select', function(e) {
            const data = e.params.data;
            const row = $(this).closest('tr');
            
            // Set product image
            if (data.image) {
                row.find('.product-image-cell').html(`
                    <div class="product-image-container">
                        <img src="${data.image}" alt="${data.text}" class="product-table-image">
                    </div>
                `);
            } else {
                row.find('.product-image-cell').html(`
                    <div class="product-image-container">
                        <div class="no-image-placeholder">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                `);
            }
            
            // Update unit type dropdown based on product data
            const unitTypeSelect = row.find('.unit-type');
            unitTypeSelect.empty(); // Clear existing options
            
            // Always add piece/unit option
            unitTypeSelect.append(`<option value="piece">دانە</option>`);
            
            // Add box option if pieces_per_box exists and greater than 0
            if (data.pieces_per_box && parseInt(data.pieces_per_box) > 0) {
                unitTypeSelect.append(`<option value="box">کارتۆن</option>`);
                
                // Add set option if boxes_per_set exists and greater than 0
                if (data.boxes_per_set && parseInt(data.boxes_per_set) > 0) {
                    unitTypeSelect.append(`<option value="set">سێت</option>`);
                }
            }
            
            // Set default unit type to piece
            unitTypeSelect.val('piece');
            
            // Set product price based on price type and unit type
            const tabPane = row.closest('.tab-pane');
            const tabType = tabPane.attr('data-receipt-type');
            const priceType = tabPane.find('.price-type').val();
            
            // Get base price based on price type (single unit price)
            let basePrice;
            if (tabType === RECEIPT_TYPES.SELLING) {
                basePrice = priceType === PRICE_TYPES.WHOLESALE ? 
                    parseFloat(data.wholesale_price || 0) : 
                    parseFloat(data.retail_price || 0);
            } else if (tabType === RECEIPT_TYPES.BUYING) {
                basePrice = parseFloat(data.purchase_price || 0);
            } else if (tabType === RECEIPT_TYPES.WASTING) {
                basePrice = parseFloat(data.purchase_price || 0);
            }
            
            // Adjust price based on unit type
            const unitType = unitTypeSelect.val();
            
            console.log(`Product selected: ${data.text}`);
            console.log(`Base price (${priceType}): ${basePrice}`);
            console.log(`Pieces per box: ${data.pieces_per_box}`);
            console.log(`Boxes per set: ${data.boxes_per_set}`);
            console.log(`Current unit type: ${unitType}`);
            
            if (unitType === 'box' && data.pieces_per_box) {
                basePrice = Math.round(basePrice * parseInt(data.pieces_per_box || 0));
                console.log(`Box price calculated: ${basePrice}`);
            } else if (unitType === 'set' && data.pieces_per_box && data.boxes_per_set) {
                basePrice = Math.round(basePrice * parseInt(data.pieces_per_box || 0) * parseInt(data.boxes_per_set || 0));
                console.log(`Set price calculated: ${basePrice}`);
            }
            
            // Update the appropriate price field based on tab type
            if (tabType === RECEIPT_TYPES.WASTING) {
                row.find('.price').val(basePrice);
            } else {
                row.find('.unit-price').val(basePrice);
            }
            
            // Update totals
            calculateRowTotal(row);
        });
        
        return newRow;
    }

    // Calculate row total
    function calculateRowTotal(row) {
        const tabPane = row.closest('.tab-pane');
        const tabType = tabPane.attr('data-receipt-type');
        
        let quantity, price, total;
        
        if (tabType === RECEIPT_TYPES.WASTING) {
            quantity = parseFloat(row.find('.adjusted-quantity').val()) || 0;
            price = parseFloat(row.find('.price').val()) || 0;
        } else {
            quantity = parseFloat(row.find('.quantity').val()) || 0;
            price = parseFloat(row.find('.unit-price').val()) || 0;
        }
        
        total = Math.round(quantity * price);
        row.find('.total').val(total);
        
        // Update grand total
        const tabId = tabPane.attr('id');
        calculateGrandTotal(tabId);
    }

    // Calculate grand total
    function calculateGrandTotal(tabId) {
        const tabPane = $(`#${tabId}`);
        
        // Calculate subtotal of all products
        let subtotal = 0;
        tabPane.find('.items-list tr').each(function() {
            const rowTotal = parseFloat($(this).find('.total').val()) || 0;
            subtotal += rowTotal;
        });
        
        // Get additional costs
        const discount = parseFloat(tabPane.find('.discount').val()) || 0;
        const shippingCost = parseFloat(tabPane.find('.shipping-cost').val()) || 0;
        const otherCost = parseFloat(tabPane.find('.other-cost').val()) || 0;
        
        // Calculate grand total
        const grandTotal = subtotal - discount + shippingCost + otherCost;
        
        // Update form fields without decimal places
        tabPane.find('.subtotal').val(Math.round(subtotal));
        tabPane.find('.shipping-cost-total').val(Math.round(shippingCost));
        tabPane.find('.grand-total').val(Math.round(grandTotal));
        
        // If this tab has payment type as credit, update the remaining amount
        if (tabPane.find('.payment-type').val() === PAYMENT_TYPES.CREDIT) {
            updateRemainingAmount(tabPane);
        }
        
        return grandTotal;
    }

    // Update remaining amount
    function updateRemainingAmount(tabPane) {
        const grandTotal = parseFloat(tabPane.find('.grand-total').val()) || 0;
        const paidAmount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
        const remainingAmount = grandTotal - paidAmount;
        tabPane.find('.remaining-amount').val(Math.round(remainingAmount));
    }

    // Function to save receipt (either as draft or normal)
    function saveReceipt(tabId, tabType, isDraft = false) {
        console.log(`Saving ${tabType} receipt ${tabId}. Is draft: ${isDraft}`);
        const tabPane = $(`#${tabId}`);
        
        // Only allow drafts for selling receipts
        if (isDraft && tabType !== RECEIPT_TYPES.SELLING) {
            console.error('Drafts are only allowed for selling receipts');
            return;
        }
        
        // Collect data from the form
        let receiptData = collectReceiptData(tabPane, tabType);
        
        if (!receiptData) {
            console.error('Failed to collect form data');
            return;
        }
        
        // Set the draft flag if needed (only for selling receipts)
        if (isDraft && tabType === RECEIPT_TYPES.SELLING) {
            receiptData.is_draft = true;
            console.log('Setting receipt as DRAFT');
        }
        
        // Convert to JSON
        const jsonData = JSON.stringify(receiptData);
        console.log('Receipt data:', jsonData);
        
        // Show loading indicator
        Swal.fire({
            title: isDraft ? 'پاشەکەوتکردنی ڕەشنووس...' : 'پاشەکەوتکردن...',
            text: isDraft ? 'ڕەشنووسی پسووڵە پاشەکەوت دەکرێت' : 'پسووڵە پاشەکەوت دەکرێت',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Send data to server
        let endpoint = '../../api/save_receipt.php';
        if (tabType === RECEIPT_TYPES.WASTING) {
            endpoint = '../../api/save_wasting.php';
        }
        
        $.ajax({
            url: endpoint,
            type: 'POST',
            data: jsonData,
            contentType: 'application/json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    // Store the receipt ID in the tab
                    tabPane.data('saved-receipt-id', response.receipt_id);
                    
                    // Check if customer has advance payment and this is a credit sale
                    if (tabType === RECEIPT_TYPES.SELLING && 
                        receiptData.payment_type === PAYMENT_TYPES.CREDIT && 
                        !isDraft) {
                        
                        // Calculate remaining amount after paid amount
                        const grandTotal = parseFloat(tabPane.find('.grand-total').val()) || 0;
                        const paidAmount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
                        const remainingAmount = grandTotal - paidAmount;
                        
                        if (remainingAmount > 0) {
                            // Check if customer has advance payment
                            checkCustomerAdvancePayment(receiptData.customer_id, function(result) {
                                if (result.success && result.hasAdvance) {
                                    // Ask user if they want to use advance payment
                                    Swal.fire({
                                        title: 'پارەی پێشەکی',
                                        text: `کڕیار ${result.advanceAmount.toLocaleString()} دینار پارەی پێشەکی هەیە. ئایا دەتەوێت بەکاری بهێنیت بۆ ئەم پسووڵەیە؟`,
                                        icon: 'question',
                                        showCancelButton: true,
                                        confirmButtonText: 'بەڵێ',
                                        cancelButtonText: 'نەخێر',
                                        reverseButtons: true
                                    }).then((confirmResult) => {
                                        if (confirmResult.isConfirmed) {
                                            // Use advance payment
                                            useAdvancePayment({
                                                customerId: receiptData.customer_id,
                                                saleId: response.receipt_id,
                                                remainingAmount: remainingAmount,
                                                invoiceNumber: receiptData.invoice_number,
                                                paymentType: receiptData.payment_type
                                            }, function(advanceResult) {
                                                if (advanceResult.success) {
                                                    // Show success message with advance payment info
                                                    showPrintDialog(response.receipt_id, tabPane, tabType, 
                                                                   `پاشەکەوت کرا. ${advanceResult.advanceUsed.toLocaleString()} دینار پارەی پێشەکی بەکارهێنرا.`);
                                                } else {
                                                    // Show regular success message
                                                    showPrintDialog(response.receipt_id, tabPane, tabType, response.message);
                                                }
                                            });
                                        } else {
                                            // User chose not to use advance payment
                                            showPrintDialog(response.receipt_id, tabPane, tabType, response.message);
                                        }
                                    });
                                } else {
                                    // No advance payment available, show regular success message
                                    showPrintDialog(response.receipt_id, tabPane, tabType, response.message);
                                }
                            });
                        } else {
                            // No remaining amount, no need for advance payment
                            showPrintDialog(response.receipt_id, tabPane, tabType, response.message);
                        }
                    } else {
                        // Not a credit sale, show regular success message
                        showPrintDialog(response.receipt_id, tabPane, tabType, response.message);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: response.message,
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving receipt:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی پاشەکەوتکردن. تکایە دووبارە هەوڵ بدەوە.',
                    confirmButtonText: 'باشە'
                });
            },
            complete: function() {
                // Reset button state
                $(`#${tabId} .save-btn`).prop('disabled', false).html('<i class="fas fa-save me-2"></i> پاشەکەوتکردن');
            }
        });
    }
    
    // Helper function to show the print dialog
    function showPrintDialog(receiptId, tabPane, tabType, message) {
        // Only show print dialog for selling receipts
        if (tabType === RECEIPT_TYPES.SELLING) {
            Swal.fire({
                icon: 'success',
                title: 'پاشەکەوت کرا',
                text: message,
                showCancelButton: true,
                confirmButtonText: 'بەڵێ، چاپی بکە',
                cancelButtonText: 'نەخێر',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-success ms-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Open print window
                    window.open(`../../views/admin/receipt/print_receipt.php?sale_id=${receiptId}`, '_blank');
                }
                
                // Reset form after handling print dialog
                resetForm(tabPane, tabType);
                // Fetch new invoice number
                fetchNextInvoiceNumber(tabType);
            });
        } else {
            // For buying receipts, just show success message and reset form
            Swal.fire({
                icon: 'success',
                title: 'پاشەکەوت کرا',
                text: message,
                confirmButtonText: 'باشە'
            }).then(() => {
                // Reset form
                resetForm(tabPane, tabType);
                // Fetch new invoice number
                fetchNextInvoiceNumber(tabType);
            });
        }
    }

    // Collect receipt data
    function collectReceiptData(tabPane, tabType) {
        const products = [];
        let valid = true;
        let insufficientProducts = [];
        
        // Collect product data
        tabPane.find('.items-list tr').each(function() {
            const row = $(this);
            const productId = row.find('.product-select').val();
            
            if (!productId) return;

            if (tabType === RECEIPT_TYPES.WASTING) {
                const adjustedQuantity = parseInt(row.find('.adjusted-quantity').val()) || 0;
                const unitPrice = parseFloat(row.find('.price').val()) || 0;
                const unitType = row.find('.unit-type').val() || 'piece';

                if (adjustedQuantity <= 0) {
                    Swal.fire({
                        title: 'هەڵە',
                        text: 'تکایە بڕی بەفیڕۆچوو بنووسە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    valid = false;
                    return false;
                }

                products.push({
                    product_id: productId,
                    adjusted_quantity: adjustedQuantity,
                    unit_price: unitPrice,
                    unit_type: unitType
                });
            } else {
                const quantity = parseInt(row.find('.quantity').val()) || 0;
                const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
                const unitType = row.find('.unit-type').val() || 'piece';
                
                if (!productId) {
                    Swal.fire('خەتا', 'تکایە کاڵا هەڵبژێرە', 'error');
                    valid = false;
                    return false;
                }
                
                if (unitPrice <= 0) {
                    Swal.fire('خەتا', 'تکایە نرخی دروست بنووسە', 'error');
                    valid = false;
                    return false;
                }
                
                if (quantity <= 0) {
                    Swal.fire('خەتا', 'تکایە بڕی دروست بنووسە', 'error');
                    valid = false;
                    return false;
                }

                // Check available quantity for selling receipts
                if (tabType === RECEIPT_TYPES.SELLING) {
                    const productData = row.find('.product-select').select2('data')[0];
                    const currentQuantity = parseInt(productData.current_quantity) || 0;
                    let requiredQuantity = quantity;

                    // Convert quantity based on unit type
                    if (unitType === 'box' && productData.pieces_per_box) {
                        requiredQuantity *= parseInt(productData.pieces_per_box);
                    } else if (unitType === 'set' && productData.pieces_per_box && productData.boxes_per_set) {
                        requiredQuantity *= parseInt(productData.pieces_per_box) * parseInt(productData.boxes_per_set);
                    }

                    if (requiredQuantity > currentQuantity) {
                        insufficientProducts.push({
                            name: productData.text,
                            requested: requiredQuantity,
                            available: currentQuantity
                        });
                        valid = false;
                    }
                }
                
                let productData = {
                    product_id: productId,
                    quantity: quantity,
                    unit_price: unitPrice,
                    unit_type: unitType
                };
                
                products.push(productData);
            }
        });
        
        if (!valid) {
            if (insufficientProducts.length > 0) {
                let errorMessage = 'بڕی کاڵاکان بەردەست نییە:<br><br>';
                insufficientProducts.forEach(product => {
                    errorMessage += `${product.name}:<br>`;
                    errorMessage += `داواکراو: ${product.requested} دانە<br>`;
                    errorMessage += `بەردەست: ${product.available} دانە<br><br>`;
                });
                
                Swal.fire({
                    title: 'بڕی کاڵا بەردەست نییە',
                    html: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
            return null;
        }

        if (products.length === 0) {
            Swal.fire('خەتا', 'تکایە لانیکەم یەک کاڵا زیاد بکە', 'error');
            return null;
        }

        // Get the date based on receipt type
        let date;
        if (tabType === RECEIPT_TYPES.WASTING) {
            date = tabPane.find('.adjustment-date').val();
        } else if (tabType === RECEIPT_TYPES.SELLING) {
            date = tabPane.find('.sale-date').val();
        } else {
            date = tabPane.find('.purchase-date').val();
        }

        // Validate date
        if (!date) {
            Swal.fire('خەتا', 'تکایە بەروار دیاری بکە', 'error');
            return null;
        }

        // Build the form data object
        const formData = {
            date: date,
            notes: tabPane.find('.notes').val() || '',
            products: products
        };

        // Add receipt type specific data
        if (tabType === RECEIPT_TYPES.SELLING || tabType === RECEIPT_TYPES.BUYING) {
            formData.receipt_type = tabType;
            formData.invoice_number = tabPane.find('.receipt-number').val();
            formData.payment_type = tabPane.find('.payment-type').val();
            formData.discount = parseFloat(tabPane.find('.discount').val()) || 0;
            formData.shipping_cost = parseFloat(tabPane.find('.shipping-cost').val()) || 0;
            formData.other_cost = parseFloat(tabPane.find('.other-cost').val()) || 0;
            formData.paid_amount = tabPane.find('.payment-type').val() === PAYMENT_TYPES.CREDIT ? 
                (parseFloat(tabPane.find('.paid-amount').val()) || 0) : 
                parseFloat(tabPane.find('.grand-total').val()) || 0;

            if (tabType === RECEIPT_TYPES.SELLING) {
                formData.customer_id = tabPane.find('.customer-select').val();
                formData.price_type = tabPane.find('.price-type').val();
                if (!formData.customer_id) {
                    Swal.fire('خەتا', 'تکایە کڕیار هەڵبژێرە', 'error');
                    return null;
                }
            } else {
                formData.supplier_id = tabPane.find('.supplier-select').val();
                if (!formData.supplier_id) {
                    Swal.fire('خەتا', 'تکایە فرۆشیار هەڵبژێرە', 'error');
                    return null;
                }
            }
        }

        console.log('Form data:', formData);
        return formData;
    }

    // Reset form
    function resetForm(tabPane, tabType) {
        // Clear the saved receipt ID
        tabPane.removeData('saved-receipt-id');
        
        // Reset all inputs except receipt number
        tabPane.find('input:not(.receipt-number)').val('');
        tabPane.find('textarea').val('');
        tabPane.find('select:not(.payment-type, .price-type, .unit-type)').val(null).trigger('change');
        
        // Reset to defaults
        tabPane.find('.payment-type').val(PAYMENT_TYPES.CASH).trigger('change');
        tabPane.find('.price-type').val(PRICE_TYPES.SINGLE);
        tabPane.find('.shipping-cost, .other-cost, .discount').val('0');
        
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
        
        console.log('addNewTab called');
        console.log('New tab type:', newTabType);
        console.log('Tab counter:', tabCounters[newTabType]);
        
        // Create new tab
        const tabId = `${newTabType}-${tabCounters[newTabType]}`;
        const tabLabel = `${getReceiptTypeLabel(newTabType)} #${tabCounters[newTabType]}`;
        
        console.log('Creating new tab with ID:', tabId);
        
        // Create tab button
        const tabBtn = `
            <li class="nav-item" role="presentation">
                <button class="nav-link receipt-tab active" id="tab-${tabId}" data-bs-toggle="tab" data-bs-target="#${tabId}" type="button" role="tab">
                    ${tabLabel}
                    <span class="close-tab"><i class="fas fa-times"></i></span>
                </button>
            </li>
        `;
        
        console.log('Before adding new tab - Active tabs:', $('.receipt-tab.active').length);
        console.log('Before adding new tab - Active panes:', $('.tab-pane.show.active').length);
        
        // Remove active class from all existing tabs and panes
        $('.receipt-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
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
        
        console.log('Template content length:', tabContent ? tabContent.length : 0);
        
        // Create the tab content
        const tabContentHtml = `
            <div class="tab-pane fade show active" id="${tabId}" role="tabpanel" data-receipt-type="${newTabType}">
                ${tabContent}
            </div>
        `;
        
        // Add tab content
        $('#receiptTabsContent').append(tabContentHtml);
        
        console.log('After adding new tab - Active tabs:', $('.receipt-tab.active').length);
        console.log('After adding new tab - Active panes:', $('.tab-pane.show.active').length);
        
        // Initialize Select2 for the new tab
        $(`#${tabId} .customer-select, #${tabId} .supplier-select, #${tabId} .product-select`).select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        
        // Set today's date
        $(`#${tabId} .sale-date, #${tabId} .purchase-date, #${tabId} .adjustment-date`).val(new Date().toISOString().split('T')[0]);
        
        // Initialize event listeners for the new tab
        initTabEventListeners(tabId, newTabType);
        
        // Get new receipt number
        fetchNextInvoiceNumber(newTabType);
        
        // Ensure all selects in the new tab have unique IDs
        $(`#${tabId} select`).each(function() {
            const baseClass = $(this).attr('class').split(' ')[0];
            const uniqueId = generateUniqueId(baseClass);
            $(this).attr('id', uniqueId);
        });
        
        // Initialize the new tab's content
        setTimeout(() => {
            console.log('Initializing new tab content');
            initializeSelectsForTab(tabId);
        }, 100);
    }

    // Add tab shown event handler
    $(document).on('shown.bs.tab', '.receipt-tab', function(e) {
        const tabId = $(e.target).attr('data-bs-target').replace('#', '');
        console.log('Tab shown event fired');
        console.log('Tab ID:', tabId);
        console.log('Active tabs:', $('.receipt-tab.active').length);
        console.log('Active panes:', $('.tab-pane.show.active').length);
        
        // Fix for missing active pane - ensure the pane is active
        const targetPane = $($(e.target).attr('data-bs-target'));
        if (!targetPane.hasClass('show active')) {
            console.log('Forcing pane to be active:', tabId);
            $('.tab-pane').removeClass('show active');
            targetPane.addClass('show active');
        }
        
        // Make sure only one tab is active
        $('.receipt-tab').not(this).removeClass('active');
        
        // Make sure select2 instances in this tab are properly initialized
        initializeSelectsForTab(tabId);
        
        // Log after fixing
        console.log('After fixing - Active panes:', $('.tab-pane.show.active').length);
    });

    // Add tab hide event handler
    $(document).on('hide.bs.tab', '.receipt-tab', function(e) {
        const tabId = $(e.target).attr('data-bs-target').replace('#', '');
        console.log('Tab hide event fired');
        console.log('Tab being hidden:', tabId);
    });

    // Initialize the page
    initializePage();

    // Add these new functions after the document.ready function but before the closing bracket

    function formatProductOption(product) {
        if (!product.id) {
            return product.text;
        }
        
        const stockStatus = parseInt(product.current_quantity) > 10 ? 'in-stock' : (parseInt(product.current_quantity) > 0 ? 'low-stock' : 'out-of-stock');
        const stockLabel = stockStatus === 'in-stock' ? 'بەردەستە' : (stockStatus === 'low-stock' ? 'کەمە' : 'نەماوە');
        
        const imageUrl = product.image || null;
        
        return $(
            `<div class="product-option-container">
                <div class="product-option-image-container">
                    <div class="product-option-image">
                        ${imageUrl ? 
                            `<img src="${imageUrl}" alt="${product.text}" class="product-thumbnail"/>` : 
                            `<div class="no-image"><i class="fas fa-box"></i></div>`
                        }
                    </div>
                    <div class="product-quantity-badge">
                        <i class="fas fa-cubes"></i> ${product.current_quantity} دانە
                    </div>
                </div>
                <div class="product-option-details">
                    <div class="product-name-row">
                        <span class="product-name">${product.text}</span>
                        <span class="product-stock ${stockStatus}">
                            <i class="fas fa-circle"></i> ${stockLabel}
                        </span>
                    </div>
                    <div class="product-prices">
                        <span class="retail-price"><i class="fas fa-tag"></i> تاک: ${product.retail_price}</span>
                        <span class="wholesale-price"><i class="fas fa-tags"></i> کۆ: ${product.wholesale_price}</span>
                    </div>
                </div>
            </div>`
        );
    }

    function formatProductSelection(product) {
        if (!product.id) {
            return product.text;
        }
        
        const stockStatus = parseInt(product.current_quantity) > 10 ? 'in-stock' : (parseInt(product.current_quantity) > 0 ? 'low-stock' : 'out-of-stock');
        
        return $(`
            <div class="product-selection">
                <span class="product-selection-name">
                    ${product.code ? `<span class="code">[${product.code}]</span> ` : ''}${product.text}
                </span>
                <span class="product-selection-stock ${stockStatus}">
                    <i class="fas fa-cubes"></i> ${product.current_quantity} دانە
                </span>
            </div>
        `);
    }

    // Add the new supplier formatting functions
    function formatSupplierOption(supplier) {
        if (!supplier.id) {
            return supplier.text;
        }

        // Convert debt values to numbers and handle null/undefined
        const debtOnMyself = parseFloat(supplier.debt_on_myself) || 0;
        const debtOnSupplier = parseFloat(supplier.debt_on_supplier) || 0;

        return $(
            `<div class="supplier-option-container">
                <div class="supplier-option-details">
                    <div class="supplier-name-row">
                        <span class="supplier-name">${supplier.text}</span>
                        <span class="supplier-code">${supplier.code || ''}</span>
                    </div>
                    <div class="supplier-debt-info">
                        <span class="debt-to-supplier ${debtOnMyself > 0 ? 'positive-debt' : ''}">
                            <i class="fas fa-arrow-up"></i> قەرزارم: ${debtOnMyself.toLocaleString()} دینار
                        </span>
                        <span class="debt-from-supplier ${debtOnSupplier > 0 ? 'negative-debt' : ''}">
                            <i class="fas fa-arrow-down"></i> قەرزارە: ${debtOnSupplier.toLocaleString()} دینار
                        </span>
                    </div>
                </div>
            </div>`
        );
    }

    function formatSupplierSelection(supplier) {
        if (!supplier.id) {
            return supplier.text;
        }

        // Convert debt values to numbers and handle null/undefined
        const debtOnMyself = parseFloat(supplier.debt_on_myself) || 0;
        const debtOnSupplier = parseFloat(supplier.debt_on_supplier) || 0;

        return $(
            `<div class="supplier-selection">
                <span class="supplier-selection-name">${supplier.text}</span>
                <span class="supplier-selection-debt">
                    ${debtOnMyself > 0 || debtOnSupplier > 0 ? 
                        `<span class="mini-debt ${debtOnMyself > 0 ? 'positive-debt' : 'negative-debt'}">
                            ${debtOnMyself > 0 ? 
                                `<i class="fas fa-arrow-up"></i> ${debtOnMyself.toLocaleString()}` : 
                                `<i class="fas fa-arrow-down"></i> ${debtOnSupplier.toLocaleString()}`
                            }
                        </span>` : 
                        `<span class="mini-debt no-debt">
                            <i class="fas fa-check-circle"></i>
                        </span>`
                    }
                </span>
            </div>`
        );
    }

    // Add CSS styles for supplier select
    if (!document.getElementById('supplier-select-styles')) {
        $('head').append(`
            <style id="supplier-select-styles">
                /* Supplier Option Styles */
                .supplier-option-container {
                    padding: 10px;
                    border-bottom: 1px solid #eee;
                    transition: background-color 0.2s;
                }
                
                .supplier-option-container:hover {
                    background-color: #f8f9fa;
                }
                
                .supplier-name-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 5px;
                }
                
                .supplier-name {
                    font-weight: 600;
                    font-size: 14px;
                    color: #2c3e50;
                }
                
                .supplier-code {
                    font-size: 12px;
                    color: #6c757d;
                    font-weight: normal;
                }
                
                .supplier-debt-info {
                    display: flex;
                    gap: 15px;
                    font-size: 13px;
                }
                
                .debt-to-supplier,
                .debt-from-supplier,
                .no-debt {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    padding: 2px 8px;
                    border-radius: 12px;
                    background-color: #f8f9fa;
                }
                
                .positive-debt {
                    color: #dc3545;
                    background-color: #ffebee;
                }
                
                .negative-debt {
                    color: #198754;
                    background-color: #e8f5e9;
                }
                
                .no-debt {
                    color: #6c757d;
                    background-color: #f8f9fa;
                }
                
                /* Supplier Selection Styles */
                .supplier-selection {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    width: 100%;
                }
                
                .supplier-selection-name {
                    font-weight: 500;
                }
                
                .mini-debt {
                    font-size: 12px;
                    padding: 2px 6px;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    gap: 3px;
                }
            </style>
        `);
    }

    // Add CSS styles to the head
    $('head').append(`
        <style>
            /* Product Option Styles */
            .product-option-container {
                display: flex;
                padding: 10px;
                align-items: flex-start;
                gap: 15px;
                border-bottom: 1px solid #eee;
                transition: background-color 0.2s;
            }
            
            .product-option-container:hover {
                background-color: #f8f9fa;
            }
            
            .product-option-image-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 5px;
            }
            
            .product-option-image {
                flex: 0 0 60px;
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #fff;
                border-radius: 6px;
                border: 1px solid #dee2e6;
                overflow: hidden;
            }
            
            .product-quantity-badge {
                font-size: 12px;
                color: #2c3e50;
                background-color: #e9ecef;
                padding: 2px 8px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 5px;
                white-space: nowrap;
            }
            
            .product-thumbnail {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
            
            .no-image {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #adb5bd;
                font-size: 24px;
                background-color: #f8f9fa;
            }
            
            .product-option-details {
                flex: 1;
                min-width: 0;
            }
            
            .product-name-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 5px;
            }
            
            .product-name {
                font-weight: 600;
                font-size: 14px;
                color: #2c3e50;
                margin-left: 10px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .product-meta {
                display: flex;
                gap: 15px;
                font-size: 12px;
                color: #6c757d;
                margin-bottom: 5px;
            }
            
            .product-meta span {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .product-prices {
                display: flex;
                gap: 15px;
                font-size: 13px;
                color: #2c3e50;
            }
            
            .product-prices span {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .product-stock {
                font-size: 12px;
                padding: 3px 8px;
                border-radius: 15px;
                display: flex;
                align-items: center;
                gap: 5px;
                font-weight: 500;
            }
            
            .in-stock {
                background-color: #e8f5e9;
                color: #2e7d32;
            }
            
            .low-stock {
                background-color: #fff3e0;
                color: #f57c00;
            }
            
            .out-of-stock {
                background-color: #ffebee;
                color: #c62828;
            }
            
            /* Product Selection Styles */
            .product-selection {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                padding: 2px 0;
            }
            
            .product-selection-name {
                font-weight: 500;
            }
            
            .product-selection-name .code {
                color: #6c757d;
                font-weight: normal;
            }
            
            .product-selection-stock {
                font-size: 12px;
                padding: 2px 8px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            /* Select2 Custom Styles */
            .select2-container--bootstrap-5 .select2-results__option {
                padding: 0;
            }
            
            .select2-container--bootstrap-5 .select2-results__option--selected {
                background-color: #e9ecef !important;
            }
            
            .select2-container--bootstrap-5 .select2-results__option--highlighted {
                background-color: #f8f9fa !important;
                color: inherit !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection {
                border-color: #dee2e6;
            }
            
            .select2-container--bootstrap-5.select2-container--focus .select2-selection {
                border-color: #86b7fe;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }
            
            /* Product Image Cell Styles */
            .product-image-cell {
                width: 120px;
                padding: 5px !important;
            }
            
            .product-image-container {
                width: 100px;
                height: 100px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #fff;
                border-radius: 8px;
                border: 1px solid #dee2e6;
                overflow: hidden;
                margin: 0 auto;
            }
            
            .product-table-image {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                border-radius: 6px;
            }
            
            .no-image-placeholder {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #f8f9fa;
                color: #adb5bd;
                font-size: 24px;
            }
        </style>
    `);

    // Calculate and show product costs and profits
    function showProductCostsAndProfits(tabId) {
        const tabPane = $(`#${tabId}`);
        let totalPurchasePrice = 0;
        let totalSellingPrice = 0;
        let hasProducts = false;

        // Calculate totals for each product
        tabPane.find('.items-list tr').each(function() {
            const row = $(this);
            const productId = row.find('.product-select').val();
            const quantity = parseFloat(row.find('.quantity').val()) || 0;
            
            if (productId && quantity > 0) {
                hasProducts = true;
                const productData = row.find('.product-select').select2('data')[0];
                const purchasePrice = parseFloat(productData.purchase_price) || 0;
                const sellingPrice = parseFloat(row.find('.unit-price').val()) || 0;
                
                totalPurchasePrice += purchasePrice * quantity;
                totalSellingPrice += sellingPrice * quantity;
            }
        });

        if (!hasProducts) {
            Swal.fire({
                title: 'ئاگاداری!',
                text: 'هیچ کاڵایەک هەڵنەبژێردراوە',
                icon: 'warning',
                confirmButtonText: 'باشە'
            });
            return;
        }

        const profit = totalSellingPrice - totalPurchasePrice;

        // Format numbers with commas for thousands
        const formatNumber = (num) => {
            return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, "،");
        };

        Swal.fire({
            title: 'کۆی نرخ و قازانج',
            html: `
                <div class="costs-summary">
                    <div class="cost-item">
                        <span class="cost-label">کۆی نرخی کڕین:</span>
                        <span class="cost-value">${formatNumber(totalPurchasePrice)} د.ع</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">کۆی نرخی فرۆشتن:</span>
                        <span class="cost-value">${formatNumber(totalSellingPrice)} د.ع</span>
                    </div>
                    <div class="cost-item profit">
                        <span class="cost-label">قازانج:</span>
                        <span class="cost-value">${formatNumber(profit)} د.ع</span>
                    </div>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'باشە',
            customClass: {
                popup: 'costs-popup',
                htmlContainer: 'costs-container'
            }
        });

        // Add custom styles
        $('<style>')
            .text(`
                .costs-popup {
                    max-width: 400px;
                }
                .costs-container {
                    padding: 1rem;
                }
                .costs-summary {
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                    margin-top: 1rem;
                }
                .cost-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 0.75rem;
                    background: #f8f9fa;
                    border-radius: 8px;
                    font-size: 1.1rem;
                }
                .cost-item.profit {
                    background: #e3fcef;
                    color: #0d6efd;
                    font-weight: bold;
                }
                .cost-label {
                    color: #6c757d;
                }
                .cost-value {
                    font-weight: 600;
                    color: #212529;
                }
            `)
            .appendTo('head');
    }

    // Add CSS styles to the head
    $('head').append(`
        <style>
            /* Action Column Styles */
            .action-column {
                min-width: 160px !important;
                text-align: center;
            }
            
            .action-buttons {
                display: flex;
                gap: 5px;
                justify-content: center;
            }
            
            .action-buttons .btn {
                width: 34px;
                height: 34px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.2s ease;
                position: relative;
                border: none;
                background: #f8f9fa;
                color: #6c757d;
            }
            
            .action-buttons .btn:hover {
                transform: translateY(-2px);
            }
            
            .action-buttons .btn i {
                font-size: 15px;
                line-height: 1;
            }

            /* Remove/Delete Button Specific Styles */
            .remove-row,
            .action-buttons .btn-danger {
                background: #fee2e2 !important;
                color: #ef4444 !important;
                transition: all 0.2s ease !important;
            }

            .remove-row:hover,
            .action-buttons .btn-danger:hover {
                background: #ef4444 !important;
                color: #ffffff !important;
                box-shadow: 0 4px 8px rgba(239, 68, 68, 0.2) !important;
            }

            .remove-row i,
            .action-buttons .btn-danger i {
                font-size: 15px;
                transition: transform 0.2s ease;
            }

            .remove-row:hover i,
            .action-buttons .btn-danger:hover i {
                transform: rotate(90deg);
            }
            
            /* Info Button */
            .action-buttons .btn-info {
                background: #e3f2fd;
                color: #0d6efd;
            }
            
            .action-buttons .btn-info:hover {
                background: #0d6efd;
                color: #fff;
                box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
            }
            
            /* Success Button (Profit) */
            .action-buttons .btn-success {
                background: #e8f5e9;
                color: #198754;
            }
            
            .action-buttons .btn-success:hover {
                background: #198754;
                color: #fff;
                box-shadow: 0 4px 8px rgba(25, 135, 84, 0.2);
            }

            /* Tooltip Styles */
            .action-buttons .btn::before {
                content: attr(title);
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                padding: 5px 10px;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                font-size: 12px;
                border-radius: 4px;
                white-space: nowrap;
                visibility: hidden;
                opacity: 0;
                transition: all 0.2s ease;
                margin-bottom: 5px;
                z-index: 1000;
            }

            .action-buttons .btn:hover::before {
                visibility: visible;
                opacity: 1;
            }

            /* Responsive Styles */
            @media screen and (max-width: 768px) {
                .action-column {
                    min-width: 130px !important;
                }
                
                .action-buttons .btn,
                .remove-row {
                    width: 30px;
                    height: 30px;
                }
                
                .action-buttons .btn i,
                .remove-row i {
                    font-size: 14px;
                }
            }

            @media screen and (max-width: 480px) {
                .action-column {
                    min-width: 110px !important;
                }
                
                .action-buttons .btn,
                .remove-row {
                    width: 28px;
                    height: 28px;
                }
                
                .action-buttons {
                    gap: 3px;
                }
            }
        </style>
    `);

    $('head').append(`
        <style>
            /* Main Action Buttons Container */
            .mt-4.text-start {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                align-items: center;
                flex-wrap: wrap;
                padding: 10px;
            }
            
            /* Main Action Buttons */
            .draft-btn,
            .costs-btn,
            .save-btn {
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
                border: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin: 0 5px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                flex: 1;
                justify-content: center;
                min-width: 120px;
                white-space: nowrap;
            }

            /* Responsive Styles */
            @media screen and (max-width: 768px) {
                .mt-4.text-start {
                    justify-content: center;
                    gap: 8px;
                    padding: 10px 5px;
                }

                .draft-btn,
                .costs-btn,
                .save-btn {
                    padding: 8px 15px;
                    font-size: 14px;
                    margin: 0;
                    min-width: 100px;
                }

                .action-buttons {
                    justify-content: center;
                }

                .action-buttons .btn {
                    width: 32px;
                    height: 32px;
                }

                .action-buttons .btn i {
                    font-size: 14px;
                }

                /* Make table responsive */
                .table-responsive {
                    margin: 0 -10px;
                    width: calc(100% + 20px);
                    overflow-x: auto;
                }

                .table {
                    min-width: 800px;
                }

                /* Adjust product image size on mobile */
                .product-image-container {
                    width: 80px;
                    height: 80px;
                }

                /* Fix input groups on mobile */
                .input-group {
                    flex-wrap: nowrap;
                }

                .input-group > .form-control {
                    width: 1%;
                }

                /* Total section responsive */
                .total-section .row {
                    margin: 0 -5px;
                }

                .total-section .col-md-3 {
                    padding: 0 5px;
                    margin-bottom: 10px;
                }

                /* Fix Select2 dropdown on mobile */
                .select2-container {
                    width: 100% !important;
                }

                /* Adjust form groups spacing */
                .form-group {
                    margin-bottom: 15px;
                }

                /* Make labels and inputs full width on mobile */
                .form-label {
                    width: 100%;
                    margin-bottom: 5px;
                }

                .form-control,
                .form-select {
                    width: 100%;
                }
            }

            /* Small mobile devices */
            @media screen and (max-width: 480px) {
                .mt-4.text-start {
                    flex-direction: column;
                    width: 100%;
                }

                .draft-btn,
                .costs-btn,
                .save-btn {
                    width: 100%;
                    margin: 5px 0;
                }

                .action-buttons {
                    gap: 5px;
                }

                .action-buttons .btn {
                    width: 28px;
                    height: 28px;
                }

                .action-buttons .btn i {
                    font-size: 12px;
                }
            }
        </style>
    `);

    // Override the Bootstrap tab functionality with our own implementation
    $(document).on('click', '.receipt-tab', function(e) {
        e.preventDefault();
        
        const tabTarget = $(this).attr('data-bs-target');
        const tabId = tabTarget.replace('#', '');
        
        console.log('Tab clicked manually:', tabId);
        
        // Deactivate all tabs and panes
        $('.receipt-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Activate this tab and its pane
        $(this).addClass('active');
        $(tabTarget).addClass('show active');
        
        // Initialize selects
        initializeSelectsForTab(tabId);
        
        console.log('After manual activation - Active tabs:', $('.receipt-tab.active').length);
        console.log('After manual activation - Active panes:', $('.tab-pane.show.active').length);
        
        // Let other handlers know the tab was shown
        $(this).trigger('shown.bs.tab', {
            relatedTarget: $('.receipt-tab.active').not(this)[0]
        });
    });

    // Function to check product stock
    function checkProductStock(input) {
        const row = $(input).closest('tr');
        const productSelect = row.find('.product-select');
        const quantity = parseInt($(input).val()) || 0;
        const productId = productSelect.val();
        const unitType = row.find('.unit-type').val();

        if (!productId) {
            Swal.fire({
                icon: 'warning',
                title: 'ئاگادارکردنەوە',
                text: 'تکایە سەرەتا کاڵا هەڵبژێرە'
            });
            $(input).val('');
            return;
        }

        // Get the selected product's data
        const selectedProduct = productSelect.select2('data')[0];
        const availableQuantity = parseInt(selectedProduct.available_quantity) || 0;

        if (quantity > availableQuantity) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: `بڕی بەردەست ${availableQuantity} ${unitType === 'piece' ? 'دانە' : (unitType === 'box' ? 'کارتۆن' : 'سێت')} یە`
            });
            $(input).val(availableQuantity);
            calculateRowTotal(row);
        }
    }
});