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
                                <i class="fas fa-image"></i>
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
                
                // Trigger change event to update the price based on the unit type
                unitTypeSelect.trigger('change');
                
                // Set product price based on price type and unit type
                const tabPane = row.closest('.tab-pane');
                const priceType = tabPane.find('.price-type').val();
                
                // Get base price based on price type (single unit price)
                let basePrice = priceType === PRICE_TYPES.WHOLESALE ? 
                    parseFloat(data.wholesale_price || 0) : 
                    parseFloat(data.retail_price || 0);
                
                // Adjust price based on unit type
                const unitType = row.find('.unit-type').val();
                
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
                
                row.find('.unit-price').val(basePrice);
                
                // Add info button to action column if not already added
                if (row.find('.product-info-btn').length === 0) {
                    const actionCell = row.find('td:last');
                    const infoBtn = $(`<button type="button" class="btn btn-info btn-sm product-info-btn ms-1" title="زانیاری بەرهەم">
                        <i class="fas fa-info-circle"></i>
                    </button>`);
                    
                    // Store product data for the info button
                    infoBtn.data('product', data);
                    
                    // Insert the button before remove button
                    actionCell.find('.remove-row').before(infoBtn);
                    
                    // Add click event to the info button
                    infoBtn.on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const productData = $(this).data('product');
                        const piecesInStock = parseInt(productData.current_quantity);
                        const piecesPerBox = parseInt(productData.pieces_per_box) || 0;
                        const boxesPerSet = parseInt(productData.boxes_per_set) || 0;
                        
                        let boxesInStock = 0;
                        let setsInStock = 0;
                        
                        if (piecesPerBox > 0) {
                            boxesInStock = Math.floor(piecesInStock / piecesPerBox);
                            
                            if (boxesPerSet > 0) {
                                setsInStock = Math.floor(boxesInStock / boxesPerSet);
                            }
                        }
                        
                        // Create HTML content for the modal
                        let htmlContent = `
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">${productData.text}</h5>
                                    <small>${productData.code} | ${productData.barcode}</small>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="card inventory-card">
                                                <div class="card-body">
                                                    <h3>${piecesInStock}</h3>
                                                    <p>دانە</p>
                                                </div>
                                            </div>
                                        </div>`;
                
                        if (piecesPerBox > 0) {
                            htmlContent += `
                                        <div class="col-md-4">
                                            <div class="card inventory-card">
                                                <div class="card-body">
                                                    <h3>${boxesInStock}</h3>
                                                    <p>کارتۆن</p>
                                                    <small>(${piecesPerBox} دانە)</small>
                                                </div>
                                            </div>
                                        </div>`;
                        }
                        
                        if (boxesPerSet > 0) {
                            htmlContent += `
                                        <div class="col-md-4">
                                            <div class="card inventory-card">
                                                <div class="card-body">
                                                    <h3>${setsInStock}</h3>
                                                    <p>سێت</p>
                                                    <small>(${boxesPerSet} کارتۆن)</small>
                                                </div>
                                            </div>
                                        </div>`;
                        }
                        
                        htmlContent += `
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <p><strong>نرخی تاک:</strong> ${productData.retail_price}</p>
                                            <p><strong>نرخی کۆمەڵ:</strong> ${productData.wholesale_price}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>پۆل:</strong> ${productData.category || '-'}</p>
                                            <p><strong>یەکە:</strong> ${productData.unit || '-'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Show SweetAlert2 modal
                        Swal.fire({
                            title: 'زانیاری لەسەر بڕی بەردەست',
                            html: htmlContent,
                            width: 600,
                            showCloseButton: true,
                            showConfirmButton: false,
                            customClass: {
                                container: 'inventory-alert'
                            }
                        });
                    });
                } else {
                    // Update product data for existing info button
                    row.find('.product-info-btn').data('product', data);
                }
                
                // Update totals
                calculateRowTotal(row);
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
        const tabPane = $(`#${tabId}`);
        
        // Initialize selects
        initializeSelectsForTab(tabId);
        
        // Save button click
        tabPane.find('.save-btn').click(function() {
            saveReceipt(tabId, tabType);
        });
        
        // Payment type change
        tabPane.find('.payment-type').change(function() {
            const paymentType = $(this).val();
            const creditFields = tabPane.find('.credit-payment-fields');
            
            if (paymentType === PAYMENT_TYPES.CREDIT) {
                creditFields.slideDown();
                updateRemainingAmount(tabPane);
            } else {
                creditFields.slideUp();
            }
        });
        
        // Add new row button click
        tabPane.find('.add-row-btn').click(function() {
            const itemsList = tabPane.find('.items-list');
            addNewRow(itemsList);
        });
        
        // Paid amount change for credit payments
        tabPane.find('.paid-amount').on('input', function() {
            updateRemainingAmount(tabPane);
        });
        
        // Discount, shipping cost and other cost changes
        tabPane.find('.discount, .shipping-cost, .other-cost').on('input', function() {
            calculateGrandTotal(tabId);
        });
        
        // Refresh button click
        tabPane.find('.refresh-btn').click(function() {
            resetForm(tabPane, tabType);
        });
        
        // Print button click
        tabPane.find('.print-btn').click(function() {
            Swal.fire('نیشاندان', 'تایبەتمەندی چاپکردن لە داهاتوودا زیاد دەکرێت', 'info');
        });
        
        // Handle row removal
        tabPane.on('click', '.remove-row', function() {
            const row = $(this).closest('tr');
            const table = row.closest('table');
            
            // Don't remove if it's the only row
            if (table.find('tbody tr').length > 1) {
                row.remove();
                // Recalculate totals
                calculateGrandTotal(tabId);
            } else {
                Swal.fire('ئاگاداری', 'ناتوانی هەموو ڕیزەکان بسڕیتەوە', 'warning');
            }
        });
        
        // Handle dynamic row fields
        tabPane.on('input', '.unit-price, .quantity', function() {
            const row = $(this).closest('tr');
            calculateRowTotal(row);
            calculateGrandTotal(tabId);
        });
        
        // Handle unit type change for selling tab
        if (tabType === RECEIPT_TYPES.SELLING) {
            tabPane.on('change', '.unit-type', function() {
                const row = $(this).closest('tr');
                const productId = row.find('.product-select').val();
                
                if (productId) {
                    const unitType = $(this).val();
                    const priceType = tabPane.find('.price-type').val();
                    
                    // Fetch unit price based on product, unit type and price type
                    $.ajax({
                        url: '../../api/get_product_price.php',
                        type: 'GET',
                        data: {
                            product_id: productId,
                            unit_type: unitType,
                            price_type: priceType
                        },
                        success: function(response) {
                            if (response.success) {
                                row.find('.unit-price').val(response.price);
                                calculateRowTotal(row);
                                calculateGrandTotal(tabId);
                            }
                        }
                    });
                }
            });
            
            // Price type change
            tabPane.find('.price-type').change(function() {
                const priceType = $(this).val();
                
                // Update all rows
                tabPane.find('.items-list tr').each(function() {
                    const row = $(this);
                    const productId = row.find('.product-select').val();
                    
                    if (productId) {
                        const unitType = row.find('.unit-type').val();
                        
                        // Fetch updated unit price
                        $.ajax({
                            url: '../../api/get_product_price.php',
                            type: 'GET',
                            data: {
                                product_id: productId,
                                unit_type: unitType,
                                price_type: priceType
                            },
                            success: function(response) {
                                if (response.success) {
                                    row.find('.unit-price').val(response.price);
                                    calculateRowTotal(row);
                                    calculateGrandTotal(tabId);
                                }
                            }
                        });
                    }
                });
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
                            <i class="fas fa-image"></i>
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
            
            // Trigger change event to update the price based on the unit type
            unitTypeSelect.trigger('change');
            
            // Set product price based on price type and unit type
            const tabPane = row.closest('.tab-pane');
            const priceType = tabPane.find('.price-type').val();
            
            // Get base price based on price type (single unit price)
            let basePrice = priceType === PRICE_TYPES.WHOLESALE ? 
                parseFloat(data.wholesale_price || 0) : 
                parseFloat(data.retail_price || 0);
            
            // Adjust price based on unit type
            const unitType = row.find('.unit-type').val();
            
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
            
            row.find('.unit-price').val(basePrice);
            
            // Add info button to action column if not already added
            if (row.find('.product-info-btn').length === 0) {
                const actionCell = row.find('td:last');
                const infoBtn = $(`<button type="button" class="btn btn-info btn-sm product-info-btn ms-1" title="زانیاری بەرهەم">
                    <i class="fas fa-info-circle"></i>
                </button>`);
                
                // Store product data for the info button
                infoBtn.data('product', data);
                
                // Insert the button before remove button
                actionCell.find('.remove-row').before(infoBtn);
                
                // Add click event to the info button
                infoBtn.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productData = $(this).data('product');
                    const piecesInStock = parseInt(productData.current_quantity);
                    const piecesPerBox = parseInt(productData.pieces_per_box) || 0;
                    const boxesPerSet = parseInt(productData.boxes_per_set) || 0;
                    
                    let boxesInStock = 0;
                    let setsInStock = 0;
                    
                    if (piecesPerBox > 0) {
                        boxesInStock = Math.floor(piecesInStock / piecesPerBox);
                        
                        if (boxesPerSet > 0) {
                            setsInStock = Math.floor(boxesInStock / boxesPerSet);
                        }
                    }
                    
                    // Create HTML content for the modal
                    let htmlContent = `
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">${productData.text}</h5>
                                <small>${productData.code} | ${productData.barcode}</small>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="card inventory-card">
                                            <div class="card-body">
                                                <h3>${piecesInStock}</h3>
                                                <p>دانە</p>
                                            </div>
                                        </div>
                                    </div>`;
                
                    if (piecesPerBox > 0) {
                        htmlContent += `
                                    <div class="col-md-4">
                                        <div class="card inventory-card">
                                            <div class="card-body">
                                                <h3>${boxesInStock}</h3>
                                                <p>کارتۆن</p>
                                                <small>(${piecesPerBox} دانە)</small>
                                            </div>
                                        </div>
                                    </div>`;
                    }
                    
                    if (boxesPerSet > 0) {
                        htmlContent += `
                                    <div class="col-md-4">
                                        <div class="card inventory-card">
                                            <div class="card-body">
                                                <h3>${setsInStock}</h3>
                                                <p>سێت</p>
                                                <small>(${boxesPerSet} کارتۆن)</small>
                                            </div>
                                        </div>
                                    </div>`;
                    }
                    
                    htmlContent += `
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <p><strong>نرخی تاک:</strong> ${productData.retail_price}</p>
                                        <p><strong>نرخی کۆمەڵ:</strong> ${productData.wholesale_price}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>پۆل:</strong> ${productData.category || '-'}</p>
                                        <p><strong>یەکە:</strong> ${productData.unit || '-'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Show SweetAlert2 modal
                    Swal.fire({
                        title: 'زانیاری لەسەر بڕی بەردەست',
                        html: htmlContent,
                        width: 600,
                        showCloseButton: true,
                        showConfirmButton: false,
                        customClass: {
                            container: 'inventory-alert'
                        }
                    });
                });
            } else {
                // Update product data for existing info button
                row.find('.product-info-btn').data('product', data);
            }
            
            // Update totals
            calculateRowTotal(row);
        });
        
        return newRow;
    }

    // Calculate row total
    function calculateRowTotal(row) {
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const total = Math.round(quantity * unitPrice);
        
        row.find('.total').val(total);
        
        // Update grand total
        const tabId = row.closest('.tab-pane').attr('id');
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
        
        // Update form fields
        tabPane.find('.subtotal').val(subtotal.toFixed(2));
        tabPane.find('.shipping-cost-total').val(shippingCost.toFixed(2));
        tabPane.find('.grand-total').val(grandTotal.toFixed(2));
        
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
        tabPane.find('.remaining-amount').val(remainingAmount.toFixed(2));
    }

    // Save receipt
    function saveReceipt(tabId, tabType) {
        console.log(`Saving receipt for tab ${tabId}, type: ${tabType}`);
        
        const tabPane = $(`#${tabId}`);
        const formData = collectReceiptData(tabPane, tabType);
        
        if (!formData) {
            console.error('Failed to collect form data');
            return;
        }
        
        if (!formData.invoice_number) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'ژمارەی پسوڵە پێویستە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        if (!formData.payment_type) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'جۆری پارەدان پێویستە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        if (tabType === RECEIPT_TYPES.SELLING && !formData.customer_id) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'کڕیار پێویستە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        } else if (tabType === RECEIPT_TYPES.BUYING && !formData.supplier_id) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'فرۆشیار پێویستە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        if (!formData.products || formData.products.length === 0) {
            Swal.fire({
                title: 'هەڵە!',
                text: 'کاڵاکان پێویستن',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            return;
        }
        
        // Show loading state
        const saveBtn = tabPane.find('.save-btn');
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> جاری پاشەکەوتکردن...');
        
        // Log the data being sent
        console.log('Sending data to server:', formData);
        
        // Send data to server
        $.ajax({
            url: '../../api/save_receipt.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
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
                        text: response.message || 'هەڵەیەکی ڕوویدا لەکاتی پاشەکەوتکردن.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                
                let errorMessage = 'هەڵەیەکی پەیوەندی ڕوویدا.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // If JSON parsing fails, use the default error message
                    console.error('Failed to parse error response:', e);
                }
                
                Swal.fire({
                    title: 'هەڵە!',
                    text: errorMessage,
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
        
        // Calculate the grand total directly from the form
        const grandTotal = parseFloat(tabPane.find('.grand-total').val()) || 0;
        const discount = parseFloat(tabPane.find('.discount').val()) || 0;
        const shippingCost = parseFloat(tabPane.find('.shipping-cost').val()) || 0;
        const otherCost = parseFloat(tabPane.find('.other-cost').val()) || 0;
        
        const formData = {
            receipt_type: tabType,
            invoice_number: tabPane.find('.receipt-number').val(),
            payment_type: tabPane.find('.payment-type').val(),
            date: tabPane.find(`.${tabType === RECEIPT_TYPES.SELLING ? 'sale' : 'purchase'}-date`).val(),
            discount: discount,
            notes: tabPane.find('.notes').val(),
            products: products,
            shipping_cost: shippingCost,
            other_cost: otherCost,
            paid_amount: 0 // Will be set based on payment type
        };
        
        // Set paid amount based on payment type for both buying and selling
        if (formData.payment_type === PAYMENT_TYPES.CREDIT) {
            // For credit payments, use the paid amount entered by user
            formData.paid_amount = parseFloat(tabPane.find('.paid-amount').val()) || 0;
        } else {
            // For cash payments, paid amount equals grand total (all is paid)
            formData.paid_amount = grandTotal;
        }
        
        if (tabType === RECEIPT_TYPES.SELLING) {
            const customerId = tabPane.find('.customer-select').val();
            if (!customerId) {
                Swal.fire('خەتا', 'تکایە کڕیار هەڵبژێرە', 'error');
                return null;
            }
            
            formData.customer_id = customerId;
            formData.price_type = tabPane.find('.price-type').val();
        } else if (tabType === RECEIPT_TYPES.BUYING) {
            const supplierId = tabPane.find('.supplier-select').val();
            if (!supplierId) {
                Swal.fire('خەتا', 'تکایە فرۆشیار هەڵبژێرە', 'error');
                return null;
            }
            
            formData.supplier_id = supplierId;
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

    // Add these new functions after the document.ready function but before the closing bracket

    function formatProductOption(product) {
        if (!product.id) {
            return product.text;
        }
        
        const stockStatus = parseInt(product.current_quantity) > 10 ? 'in-stock' : (parseInt(product.current_quantity) > 0 ? 'low-stock' : 'out-of-stock');
        const stockLabel = stockStatus === 'in-stock' ? 'بەردەستە' : (stockStatus === 'low-stock' ? 'کەمە' : 'نەماوە');
        
        // Use the image path directly from the product data
        const imageUrl = product.image || null;
        
        let $option = $(
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
                    <div class="product-meta">
                        ${product.code ? `<span class="product-code"><i class="fas fa-hashtag"></i> ${product.code}</span>` : ''}
                        ${product.barcode ? `<span class="product-barcode"><i class="fas fa-barcode"></i> ${product.barcode}</span>` : ''}
                    </div>
                    <div class="product-prices">
                        <span class="retail-price"><i class="fas fa-tag"></i> تاک: ${product.retail_price}</span>
                        <span class="wholesale-price"><i class="fas fa-tags"></i> کۆ: ${product.wholesale_price}</span>
                    </div>
                </div>
            </div>`
        );
        
        return $option;
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
});