/**
 * Products page JavaScript
 */

// Add custom styles for image modal and consistent image sizing
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .swal-wide {
            width: auto !important;
            max-width: 95% !important;
        }
        .product-image {
            transition: transform 0.2s;
            width: 50px !important;
            height: 50px !important;
            object-fit: contain !important;
            display: block !important;
            margin: 0 auto !important;
            padding: 2px !important;
            background-color: #f8f9fa !important;
            border-radius: 4px !important;
        }
        .product-image:hover {
            transform: scale(1.05);
        }
        .no-image-placeholder {
            width: 50px !important;
            height: 50px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: #f8f9fa !important;
            border-radius: 4px !important;
            margin: 0 auto !important;
        }
        .no-image-placeholder i {
            font-size: 1.5rem !important;
            color: #6c757d !important;
        }
        .image-cell {
            width: 60px !important;
            min-width: 60px !important;
            max-width: 60px !important;
            padding: 5px !important;
            text-align: center !important;
        }
    `;
    document.head.appendChild(style);
})();

// Global AJAX error handler
$(document).ajaxError(function(event, jqXHR, settings, errorThrown) {
    console.error('AJAX Error:', errorThrown || jqXHR.statusText);
    
    // Don't show the alert if it's just a page reload or navigation
    if (jqXHR.status !== 0 && errorThrown !== 'abort') {
        Swal.fire({
            title: 'هەڵە!',
            text: 'کێشەیەک لە پەیوەندیکردن بە سیستەمەوە ڕوویدا. تکایە دواتر هەوڵ بدەوە.',
            icon: 'error',
            confirmButtonText: 'باشە'
        });
    }
});

// Handle any unhandled promise rejections globally
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled Promise Rejection:', event.reason);
    
    Swal.fire({
        title: 'هەڵە!',
        text: 'هەندێک رووبەڕوو ناکرێت مەبەستەدینا',
        icon: 'error',
        confirmButtonText: 'باشە'
    });
});

// Handle global errors
window.onerror = function(message, source, lineno, colno, error) {
    console.error('Global error:', message, source, lineno, colno, error);
    
    // Avoid showing too many alerts for the same error
    if (!window.lastErrorTime || (Date.now() - window.lastErrorTime > 5000)) {
        window.lastErrorTime = Date.now();
        
        Swal.fire({
            title: 'هەڵە!',
            text: 'هەندێک رووبەڕوو ناکرێت مەبەستەدینا',
            icon: 'error',
            confirmButtonText: 'باشە'
        });
    }
    
    return true; // Prevent default error handler
};

// Improved component loading with error handling
function loadComponent(containerId, componentPath) {
    const container = document.getElementById(containerId);
    if (!container) return Promise.reject(new Error('Container not found: ' + containerId));
    
    return fetch(componentPath)
        .then(response => {
            if (!response.ok) {
                throw new Error(`پەیوەندی سەرکەوتوو نەبوو: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
            return html;
        })
        .catch(error => {
            console.error(`Error loading ${componentPath}:`, error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <strong>هەڵە:</strong> پێکهاتەکە نەتوانرا بارکرێت.
                    <button class="btn btn-sm btn-outline-danger ms-2" onclick="loadComponent('${containerId}', '${componentPath}')">
                        <i class="fas fa-sync-alt"></i> هەوڵدانەوە
                    </button>
                </div>
            `;
            throw error;
        });
}

// Function to handle product deletion with better error management
function deleteProduct(productId) {
    return fetch('../../process/deleteProduct.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + productId
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('کاڵای داواکراو نەدۆزرایەوە');
            } else if (response.status === 403) {
                throw new Error('مۆڵەتی سڕینەوەت نییە');
            } else {
                throw new Error('وەڵامی سێرڤەر سەرکەوتوو نەبوو: ' + response.status);
            }
        }
        
        return response.json().catch(() => {
            throw new Error('کێشەیەک هەیە لە وەرگرتنی داتاکان لە سێرڤەرەوە');
        });
    });
}

// Function to show a large version of an image in a modal
function showLargeImage(src, productName) {
    Swal.fire({
        title: productName,
        imageUrl: src,
        imageWidth: 400,
        imageHeight: 400,
        imageAlt: productName,
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            image: 'img-fluid',
            popup: 'swal-wide'
        }
    });
}

// Export functions
window.loadComponent = loadComponent;
window.deleteProduct = deleteProduct;
window.showLargeImage = showLargeImage;

// Define changePage function in global scope
function changePage(page) {
    // Show loading state
    $('.table-responsive').addClass('loading');
    
    // Get current filter values
    const search = $('#search').val();
    const category = $('#category').val();
    const unit = $('#unit').val();
    const recordsPerPage = $('#recordsPerPage').val();
    
    // Build query parameters
    const params = new URLSearchParams();
    params.set('page', page);
    if (search) params.set('search', search);
    if (category) params.set('category', category);
    if (unit) params.set('unit', unit);
    if (recordsPerPage) params.set('per_page', recordsPerPage);
    
    // Update URL without reloading
    window.history.pushState({}, '', '?' + params.toString());
    
    // Make AJAX request
    $.ajax({
        url: '../../process/products_logic.php',
        type: 'GET',
        data: params.toString(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update table content
                updateTableContent(data);
                
                // Update pagination info
                $('#startRecord').text(Math.min((data.pagination.current_page - 1) * data.pagination.records_per_page + 1, data.pagination.total_records));
                $('#endRecord').text(Math.min(data.pagination.current_page * data.pagination.records_per_page, data.pagination.total_records));
                $('#totalRecords').text(data.pagination.total_records);
                
                // Update pagination buttons
                updatePaginationButtons(data.pagination);
                
                // Reinitialize event handlers and tooltips
                initializeEventHandlers();
                
                // Reinitialize tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();
            } else {
                Swal.fire({
                    title: 'هەڵە!',
                    text: response.message || 'کێشەیەک ڕوویدا لە وەرگرتنی داتاکان',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'هەڵە!',
                text: 'کێشەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        },
        complete: function() {
            // Remove loading state
            $('.table-responsive').removeClass('loading');
        }
    });
}

// Function to update pagination buttons
function updatePaginationButtons(pagination) {
    const totalPages = pagination.total_pages;
    const currentPage = pagination.current_page;
    const paginationNumbers = $('#paginationNumbers');
    
    paginationNumbers.empty();
    
    // Prev button
    $('#prevPageBtn').prop('disabled', currentPage <= 1);
    
    // Always show first page
    if (currentPage > 3) {
        paginationNumbers.append(`<button class="btn btn-sm btn-outline-primary rounded-circle me-2" onclick="changePage(1)">1</button>`);
        if (currentPage > 4) {
            paginationNumbers.append('<span class="btn btn-sm rounded-circle me-2 disabled">...</span>');
        }
    }
    
    // Show pages around current page
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        paginationNumbers.append(`<button class="btn btn-sm ${currentPage == i ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" onclick="changePage(${i})">${i}</button>`);
    }
    
    // Show last page
    if (currentPage < totalPages - 2) {
        if (currentPage < totalPages - 3) {
            paginationNumbers.append('<span class="btn btn-sm rounded-circle me-2 disabled">...</span>');
        }
        paginationNumbers.append(`<button class="btn btn-sm btn-outline-primary rounded-circle me-2" onclick="changePage(${totalPages})">${totalPages}</button>`);
    }
    
    // Next button
    $('#nextPageBtn').prop('disabled', currentPage >= totalPages);
}

// Function to get basename of a path
function basename(path) {
    return path.split('/').reverse()[0];
}

// Function to format numbers
function numberFormat(number, decimals = 0) {
    number = parseFloat(String(number).replace(/,/g, '')); // Remove existing commas
    if (isNaN(number)) {
        return '0';
    }
    // Use toLocaleString for better number formatting, handle decimals
    return number.toLocaleString('en-US', { 
        minimumFractionDigits: decimals, 
        maximumFractionDigits: decimals 
    });
}

// Utility functions
function min(a, b) { return a < b ? a : b; }
function max(a, b) { return a > b ? a : b; }

// Function to format numbers with commas for thousands, allowing decimals
function formatDecimalNumberInput(input) {
    let value = input.value;
    // Remove any non-numeric characters except dots
    value = value.replace(/[^\d.]/g, '');
    
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Format the integer part with commas
    let [integerPart, decimalPart] = value.split('.');
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    // Reconstruct the value
    input.value = decimalPart !== undefined ? `${integerPart}.${decimalPart}` : integerPart;
}

// Function to clean number inputs before submitting the form
function cleanNumberForSubmission(value) {
    return String(value).replace(/,/g, '');
}


// Initialize event handlers on load and after AJAX updates
function initializeEventHandlers() {
    // Delete product handler
    $('.delete-product').off('click').on('click', function() {
        const productId = $(this).data('id');
        const productName = $(this).closest('tr').find('td:nth-child(5)').text();
        
        Swal.fire({
            title: 'دڵنیای؟',
            text: `ئایا دڵنیای لە سڕینەوەی کاڵای (${productName})؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بیسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'سڕینەوەی کاڵاکە...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                deleteProduct(productId)
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'سڕایەوە!',
                                text: data.message || 'کاڵاکە بە سەرکەوتوویی سڕایەوە.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload the page or update table
                                changePage(1); // Reload first page
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: data.message || 'کێشەیەک ڕوویدا لە کاتی سڕینەوە.',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'هەڵە!',
                            text: error.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی کاڵا.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    });
            }
        });
    });
    
    // Edit product handler
    $('.edit-product').off('click').on('click', function() {
        const productData = $(this).data();
        
        // Populate modal fields - format numbers correctly
        $('#edit_product_id').val(productData.id);
        $('#edit_name').val(productData.name);
        $('#edit_code').val(productData.code);
        $('#edit_barcode').val(productData.barcode);
        $('#edit_category').val(productData.category);
        $('#edit_unit').val(productData.unit);
        $('#edit_pieces_per_box').val(productData.piecesPerBox);
        $('#edit_boxes_per_set').val(productData.boxesPerSet);
        $('#edit_purchase_price').val(productData.purchase); // Keep as number for step
        $('#edit_selling_price_single').val(productData.sellingSingle); // Keep as number for step
        $('#edit_selling_price_wholesale').val(productData.sellingWholesale); // Keep as number for step
        $('#edit_min_quantity').val(productData.minQty);
        $('#edit_notes').val(productData.notes);

        // Format the number fields displayed in the modal
        formatDecimalNumberInput(document.getElementById('edit_purchase_price'));
        formatDecimalNumberInput(document.getElementById('edit_selling_price_single'));
        formatDecimalNumberInput(document.getElementById('edit_selling_price_wholesale'));
        
        // Handle image display
        const currentImage = $('#current_product_image');
        const imageCell = $(this).closest('tr').find('td:nth-child(2) img');
        if (imageCell.length > 0) {
            currentImage.attr('src', imageCell.attr('src')).show();
        } else {
            currentImage.hide();
        }
        
        // Show/hide unit inputs based on unit type
        toggleUnitInputs(productData.unit);
        
        // Show modal
        $('#editProductModal').modal('show');
    });
    
    // Show notes handler
    $('.view-notes').off('click').on('click', function() {
        const productName = $(this).data('name');
        const notes = $(this).data('notes');
        
        Swal.fire({
            title: `تێبینی بۆ (${productName})`,
            text: notes || 'هیچ تێبینیەک نییە',
            icon: 'info',
            confirmButtonText: 'داخستن'
        });
    });
    
    // Add tooltip initialization
    $('[data-bs-toggle="tooltip"]').tooltip();
}

// Function to show/hide unit specific inputs in the edit modal
function toggleUnitInputs(unitId) {
    const piecesPerBoxGroup = $('#edit_pieces_per_box').closest('.mb-3');
    const boxesPerSetGroup = $('#edit_boxes_per_set').closest('.mb-3');
    
    piecesPerBoxGroup.hide();
    boxesPerSetGroup.hide();
    
    // Use == for type coercion as unitId might be string
    if (unitId == '2') { // دانە و کارتۆن
        piecesPerBoxGroup.show();
    } else if (unitId == '3') { // دانە و کارتۆن و سێت
        piecesPerBoxGroup.show();
        boxesPerSetGroup.show();
    }
}

// Update table content after AJAX call
function updateTableContent(data) {
    const tableBody = $('#productsTableBody');
    tableBody.empty();
    
    if (data.products.length === 0) {
        const colCount = $('#productsTableBody').closest('table').find('thead th').length;
        tableBody.append(`<tr><td colspan="${colCount}" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>`);
        return;
    }
    
    data.products.forEach((product, index) => {
        const rowNumber = (data.pagination.current_page - 1) * data.pagination.records_per_page + index + 1;
        
        // Handle image path
        let imageUrl = '';
        let imageHtml = `
            <div class="no-image-placeholder" data-bs-toggle="tooltip" data-bs-placement="top" title="وێنە نییە">
                <i class="fas fa-image"></i>
            </div>
        `;
        if (product.image) {
            const filename = basename(product.image);
            imageUrl = `../../api/product_image.php?filename=${encodeURIComponent(filename)}`;
            imageHtml = `
                <img src="${imageUrl}" 
                     alt="${product.name}" 
                     class="product-image"
                     style="cursor: pointer;"
                     onclick="showLargeImage(this.src, '${product.name}')"
                     data-bs-toggle="tooltip"
                     data-bs-placement="top"
                     title="${product.name}">
            `;
        }
        
        // Determine stock status
        let stockStatusHtml = '';
        const currentQty = parseInt(product.current_quantity) || 0;
        if (currentQty < 10) {
            stockStatusHtml = `<span class="badge bg-danger rounded-pill">مەترسیدارە (${currentQty})</span>`;
        } else if (currentQty >= 10 && currentQty <= 50) {
            stockStatusHtml = `<span class="badge bg-warning rounded-pill">بڕێکی کەم بەردەستە (${currentQty})</span>`;
        } else {
            stockStatusHtml = `<span class="badge bg-success rounded-pill">کۆنتڕۆڵ (${currentQty})</span>`;
        }
        
        // Build table row
        const rowHtml = `
            <tr>
                <td>${rowNumber}</td>
                <td class="image-cell">${imageHtml}</td>
                <td>${product.code}</td>
                <td>${product.barcode || '-'}</td>
                <td>${product.name}</td>
                <td>${product.category_name}</td>
                <td>${product.unit_name}</td>
                <td>${product.pieces_per_box || '-'}</td>
                <td>${product.boxes_per_set || '-'}</td>
                <td>${numberFormat(product.purchase_price, 2)} د.ع</td>
                <td>${numberFormat(product.selling_price_single, 2)} د.ع</td>
                <td>${numberFormat(product.selling_price_wholesale, 2)} د.ع</td>
                <td>${product.min_quantity}</td>
                <td>${stockStatusHtml}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary rounded-circle edit-product" 
                                data-id="${product.id}"
                                data-name="${product.name}"
                                data-code="${product.code}"
                                data-barcode="${product.barcode || ''}"
                                data-category="${product.category_id}"
                                data-unit="${product.unit_id}"
                                data-pieces-per-box="${product.pieces_per_box || ''}"
                                data-boxes-per-set="${product.boxes_per_set || ''}"
                                data-purchase="${product.purchase_price}"
                                data-selling-single="${product.selling_price_single}"
                                data-selling-wholesale="${product.selling_price_wholesale}"
                                data-min-qty="${product.min_quantity}"
                                data-notes="${product.notes || ''}"
                                aria-label="گۆڕینی ${product.name}"
                                title="گۆڕین">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-notes" 
                                data-name="${product.name}"
                                data-notes="${product.notes || 'هیچ تێبینیەک نییە'}"
                                aria-label="بینینی تێبینیەکانی ${product.name}"
                                title="بینینی تێبینیەکان">
                            <i class="fas fa-sticky-note"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-product" 
                                data-id="${product.id}"
                                aria-label="سڕینەوەی ${product.name}"
                                title="سڕینەوە">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tableBody.append(rowHtml);
    });
}

// Main document ready function
$(document).ready(function() {
    // Initialize Select2 for category and unit dropdowns
    $('#category, #unit').select2({
        theme: 'bootstrap-5',
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder') || 'هەڵبژێرە...',
        allowClear: true,
        dir: 'rtl', // Ensure RTL support
        language: "ku" // Set language if available
    });
    
    // Debounce search input
    let searchTimeout;
    const searchInput = $('#search');
    const suggestionsContainer = $('.search-suggestions');
    
    // Fetch latest products for suggestions
    function fetchLatestProducts() {
        $.ajax({
            url: '../../process/get_latest_products.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data && data.length > 0) {
                    const suggestionsList = suggestionsContainer.find('.suggestions-list');
                    suggestionsList.empty();
                    data.slice(0, 5).forEach(product => {
                        suggestionsList.append(`
                            <div class="suggestion-item" data-name="${product.name}">
                                ${product.name} (${product.code})
                            </div>
                        `);
                    });
                }
            },
            error: function() {
                console.error('Failed to fetch latest products for suggestions.');
            }
        });
    }
    
    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch();
        }, 300); // Wait 300ms after user stops typing
    });
    
    searchInput.on('focus', function() {
        fetchLatestProducts();
        suggestionsContainer.show();
    });
    
    // Hide suggestions when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.search-wrapper').length) {
            suggestionsContainer.hide();
        }
    });
    
    // Handle suggestion click
    $(document).on('click', '.suggestion-item', function() {
        searchInput.val($(this).data('name'));
        suggestionsContainer.hide();
        performSearch();
    });
    
    // Initial form submission handler for filters
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Records per page change handler
    $('#recordsPerPage').on('change', function() {
        changePage(1); // Go back to page 1 when changing records per page
    });
    
    // Reset filter handler
    $('#resetFilter').on('click', function() {
        $('#filterForm')[0].reset();
        $('#category').val('').trigger('change');
        $('#unit').val('').trigger('change');
        performSearch();
    });
    
    // Pagination button handlers
    $('#prevPageBtn').on('click', function() {
        const currentPage = parseInt($('#paginationNumbers button.btn-primary').text());
        if (currentPage > 1) {
            changePage(currentPage - 1);
        }
    });
    
    $('#nextPageBtn').on('click', function() {
        const currentPage = parseInt($('#paginationNumbers button.btn-primary').text());
        // Need to get total pages dynamically if possible, otherwise use a placeholder
        // Assuming total_pages is available globally or fetched somehow
        // const totalPages = getTotalPages(); // Replace with actual logic
        // if (currentPage < totalPages) { changePage(currentPage + 1); }
        
        // Temporary fix: Check if next button is disabled (more reliable)
        if (!$(this).prop('disabled')) {
             changePage(currentPage + 1);
        }
    });
    
    // Apply decimal number formatting to price inputs in the edit modal
    const priceInputs = ['edit_purchase_price', 'edit_selling_price_single', 'edit_selling_price_wholesale'];
    priceInputs.forEach(inputId => {
        const inputElement = document.getElementById(inputId);
        if (inputElement) {
            // Keep type="number" for step functionality but format on input
            // Change type to text temporarily to allow formatting
            // inputElement.setAttribute('type', 'text'); 
            inputElement.addEventListener('input', function() {
                formatDecimalNumberInput(this);
            });
            // Optionally format on blur as well
            inputElement.addEventListener('blur', function() {
                formatDecimalNumberInput(this);
            });
        }
    });

    // Save changes from edit modal
    $('#saveProductChanges').on('click', function() {
        // Show loading state on the button
        const saveButton = $(this);
        saveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> پاشەکەوتکردن...');
        
        // Clean number inputs (remove commas) before submitting
        const purchasePriceInput = $('#edit_purchase_price');
        const sellingSingleInput = $('#edit_selling_price_single');
        const sellingWholesaleInput = $('#edit_selling_price_wholesale');

        const cleanedPurchasePrice = cleanNumberForSubmission(purchasePriceInput.val());
        const cleanedSellingSingle = cleanNumberForSubmission(sellingSingleInput.val());
        const cleanedSellingWholesale = cleanNumberForSubmission(sellingWholesaleInput.val());

        // Create FormData and append cleaned values
        const formData = new FormData($('#editProductForm')[0]);
        formData.set('purchase_price', cleanedPurchasePrice);
        formData.set('selling_price_single', cleanedSellingSingle);
        formData.set('selling_price_wholesale', cleanedSellingWholesale);
        
        // AJAX request to update product
        $.ajax({
            url: '../../process/editProduct.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editProductModal').modal('hide');
                    Swal.fire({
                        title: 'سەرکەوتوو!',
                        text: response.message || 'زانیاری کاڵا بە سەرکەوتوویی گۆڕدرا.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        const currentPage = parseInt($('#paginationNumbers button.btn-primary').text()) || 1;
                        changePage(currentPage); // Reload current page
                    });
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: response.message || 'کێشەیەک ڕوویدا لە کاتی گۆڕینی زانیاری.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'کێشەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە.',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            },
            complete: function() {
                // Restore button state
                saveButton.prop('disabled', false).html('پاشەکەوتکردن');
            }
        });
    });
    
    // Handle unit selection change in the edit modal
    $('#edit_unit').on('change', function() {
        toggleUnitInputs($(this).val());
    });
    
    // Initialize event handlers on initial page load
    initializeEventHandlers();
    
    // Function to perform search and update table
    function performSearch() {
        // Reset to page 1 for new search/filter
        changePage(1);
    }
});