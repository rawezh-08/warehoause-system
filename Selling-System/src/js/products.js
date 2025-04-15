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
    let paginationHtml = '';
    
    // First page
    if (pagination.current_page > 3) {
        paginationHtml += `<button class="btn btn-sm btn-outline-primary rounded-circle me-2" onclick="changePage(1)">1</button>`;
        if (pagination.current_page > 4) {
            paginationHtml += `<span class="btn btn-sm rounded-circle me-2 disabled">...</span>`;
        }
    }
    
    // Pages around current page
    for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
        paginationHtml += `<button class="btn btn-sm ${pagination.current_page == i ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2" onclick="changePage(${i})">${i}</button>`;
    }
    
    // Last page
    if (pagination.current_page < pagination.total_pages - 2) {
        if (pagination.current_page < pagination.total_pages - 3) {
            paginationHtml += `<span class="btn btn-sm rounded-circle me-2 disabled">...</span>`;
        }
        paginationHtml += `<button class="btn btn-sm btn-outline-primary rounded-circle me-2" onclick="changePage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }
    
    $('#paginationNumbers').html(paginationHtml);
    
    // Update prev/next buttons
    $('#prevPageBtn').prop('disabled', pagination.current_page <= 1);
    $('#nextPageBtn').prop('disabled', pagination.current_page >= pagination.total_pages);
}

// Helper functions
function basename(path) {
    return path.split('/').pop();
}

function numberFormat(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function min(a, b) {
    return a < b ? a : b;
}

function max(a, b) {
    return a > b ? a : b;
}

// Function to initialize event handlers
function initializeEventHandlers() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize edit product buttons
    $('.edit-product').on('click', function() {
        const data = $(this).data();
        
        // Fill the form with product data
        $('#edit_product_id').val(data.id);
        $('#edit_name').val(data.name);
        $('#edit_code').val(data.code);
        $('#edit_barcode').val(data.barcode);
        $('#edit_category').val(data.category);
        $('#edit_unit').val(data.unit);
        $('#edit_pieces_per_box').val(data.piecesPerBox);
        $('#edit_boxes_per_set').val(data.boxesPerSet);
        $('#edit_purchase_price').val(data.purchase);
        $('#edit_selling_price_single').val(data.sellingSingle);
        $('#edit_selling_price_wholesale').val(data.sellingWholesale);
        $('#edit_min_quantity').val(data.minQty);
        $('#edit_notes').val(data.notes);
        
        // Show/hide unit-specific inputs based on selected unit
        toggleUnitInputs($('#edit_unit').val());
        
        // Show the modal
        const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
        editModal.show();
    });

    // Handle unit change in edit modal
    $('#edit_unit').on('change', function() {
        toggleUnitInputs($(this).val());
    });

    // Function to toggle unit-specific inputs
    function toggleUnitInputs(unitId) {
        const piecesPerBoxInput = $('#edit_pieces_per_box').closest('.col-md-4');
        const boxesPerSetInput = $('#edit_boxes_per_set').closest('.col-md-4');

        // Hide both inputs by default
        piecesPerBoxInput.hide();
        boxesPerSetInput.hide();

        // Show relevant inputs based on unit type
        switch(unitId) {
            case '1': // دانە
                break;
            case '2': // دانە و کارتۆن
                piecesPerBoxInput.show();
                break;
            case '3': // دانە و کارتۆن و سێت
                piecesPerBoxInput.show();
                boxesPerSetInput.show();
                break;
        }
    }

    // Initialize view notes buttons
    $('.view-notes').on('click', function() {
        const data = $(this).data();
        Swal.fire({
            title: 'تێبینیەکانی ' + data.name,
            text: data.notes,
            icon: 'info',
            confirmButtonText: 'باشە'
        });
    });

    // Initialize delete product buttons
    $('.delete-product').on('click', function() {
        const productId = $(this).data('id');
        const productRow = $(this).closest('tr');
        const productName = productRow.find('td:nth-child(5)').text();
        
        Swal.fire({
            title: 'دڵنیای لە سڕینەوەی ئەم کاڵایە؟',
            html: `<div>کاڵای <strong>${productName}</strong> دەسڕدرێتەوە</div>
                  <div class="text-danger mt-2">
                    <small>ئاگاداری: ئەگەر ئەم کاڵایە لە پسووڵەی کڕین یان فرۆشتن بەکارهاتبێت، ناتوانرێت بسڕدرێتەوە.</small>
                  </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'پاشگەزبوونەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'چاوەڕوان بە...',
                    text: 'سڕینەوەی کاڵا',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send delete request
                fetch('../../process/deleteProduct.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table with animation
                        productRow.fadeOut(400, function() {
                            // After fadeOut completes, remove the row
                            $(this).remove();
                            
                            // Update row numbers for all remaining rows
                            $('#productsTableBody tr').each(function(index) {
                                $(this).find('td:first').text(index + 1);
                            });
                            
                            // Update pagination info - decrease total count by 1
                            const totalRecords = parseInt($('#totalRecords').text()) - 1;
                            $('#totalRecords').text(totalRecords);
                            
                            // Recalculate end record
                            const startRecord = parseInt($('#startRecord').text());
                            const endRecord = Math.min(parseInt($('#endRecord').text()), totalRecords);
                            $('#endRecord').text(endRecord);
                            
                            // Show success message
                            Swal.fire({
                                title: 'سڕایەوە!',
                                text: 'کاڵاکە بە سەرکەوتوویی سڕایەوە.',
                                icon: 'success',
                                confirmButtonText: 'باشە'
                            });
                            
                            // If table is now empty and we're not on first page, go to previous page
                            if ($('#productsTableBody tr').length === 0 && new URLSearchParams(window.location.search).get('page') > 1) {
                                const currentPage = parseInt(new URLSearchParams(window.location.search).get('page'));
                                changePage(currentPage - 1);
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'نەتوانرا کاڵاکە بسڕدرێتەوە',
                            html: `<div class="alert alert-danger">${data.message || 'کێشەیەک ڕوویدا لە سڕینەوەی کاڵاکە.'}</div>`,
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'هەڵە!',
                        text: error.message || 'کێشەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                });
            }
        });
    });
}

// Update the table row generation to include the image cell class
function updateTableContent(data) {
    let tableHtml = '';
    data.products.forEach((product, index) => {
        const rowNumber = (data.pagination.current_page - 1) * data.pagination.records_per_page + index + 1;
        tableHtml += `
            <tr>
                <td>${rowNumber}</td>
                <td class="image-cell">
                    ${product.image ? 
                        `<img src="../../api/product_image.php?filename=${encodeURIComponent(product.image.split('/').pop())}" 
                              alt="${product.name}" 
                              class="product-image"
                              data-bs-toggle="tooltip"
                              data-bs-placement="top"
                              title="${product.name}"
                              onclick="showLargeImage(this.src, '${product.name}')"
                              aria-label="${product.name}">` :
                        `<div class="no-image-placeholder">
                            <i class="fas fa-image"></i>
                        </div>`
                    }
                </td>
                <td>${product.code}</td>
                <td>${product.barcode || '-'}</td>
                <td>${product.name}</td>
                <td>${product.category_name}</td>
                <td>${product.unit_name}</td>
                <td>${product.pieces_per_box || '-'}</td>
                <td>${product.boxes_per_set || '-'}</td>
                <td>${numberFormat(product.purchase_price)} د.ع</td>
                <td>${numberFormat(product.selling_price_single)} د.ع</td>
                <td>${numberFormat(product.selling_price_wholesale)} د.ع</td>
                <td>${product.min_quantity}</td>
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
    });
    $('#productsTableBody').html(tableHtml);
}

$(document).ready(function() {
    // Initialize Select2 dropdowns
    $('#category, #unit').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'هەڵبژاردن بکە',
        allowClear: true,
        language: {
            noResults: function() {
                return "هیچ ئەنجامێک نەدۆزرایەوە";
            }
        }
    });

    // Initialize event handlers
    initializeEventHandlers();

    // Handle save changes button
    $('#saveProductChanges').on('click', function() {
        const formData = new FormData(document.getElementById('editProductForm'));
        const productId = formData.get('id');
        const productRow = $(`button.edit-product[data-id="${productId}"]`).closest('tr');
        
        // Remove commas from number fields before sending
        const numberFields = ['purchase_price', 'selling_price_single', 'selling_price_wholesale', 'min_quantity'];
        numberFields.forEach(field => {
            const value = formData.get(field);
            if (value) {
                formData.set(field, value.replace(/,/g, ''));
            }
        });

        // Show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاوەڕێ بکە...');

        fetch('../../process/updateProduct.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Collect updated values
                const updatedData = {
                    name: formData.get('name'),
                    code: formData.get('code'),
                    barcode: formData.get('barcode') || '-',
                    category_id: formData.get('category_id'),
                    category_name: $('#edit_category option:selected').text(),
                    unit_id: formData.get('unit_id'),
                    unit_name: $('#edit_unit option:selected').text(),
                    pieces_per_box: formData.get('pieces_per_box') || '-',
                    boxes_per_set: formData.get('boxes_per_set') || '-',
                    purchase_price: formData.get('purchase_price'),
                    selling_price_single: formData.get('selling_price_single'),
                    selling_price_wholesale: formData.get('selling_price_wholesale') || '-',
                    min_quantity: formData.get('min_quantity'),
                    notes: formData.get('notes') || ''
                };
                
                // Update the table row
                productRow.find('td:nth-child(3)').text(updatedData.code);
                productRow.find('td:nth-child(4)').text(updatedData.barcode);
                productRow.find('td:nth-child(5)').text(updatedData.name);
                productRow.find('td:nth-child(6)').text(updatedData.category_name);
                productRow.find('td:nth-child(7)').text(updatedData.unit_name);
                productRow.find('td:nth-child(8)').text(updatedData.pieces_per_box);
                productRow.find('td:nth-child(9)').text(updatedData.boxes_per_set);
                productRow.find('td:nth-child(10)').text(numberFormat(updatedData.purchase_price) + ' د.ع');
                productRow.find('td:nth-child(11)').text(numberFormat(updatedData.selling_price_single) + ' د.ع');
                productRow.find('td:nth-child(12)').text(numberFormat(updatedData.selling_price_wholesale) + ' د.ع');
                productRow.find('td:nth-child(13)').text(updatedData.min_quantity);
                
                // Update data attributes of action buttons
                const editButton = productRow.find('button.edit-product');
                editButton.attr('data-name', updatedData.name);
                editButton.attr('data-code', updatedData.code);
                editButton.attr('data-barcode', updatedData.barcode);
                editButton.attr('data-category', updatedData.category_id);
                editButton.attr('data-unit', updatedData.unit_id);
                editButton.attr('data-pieces-per-box', updatedData.pieces_per_box);
                editButton.attr('data-boxes-per-set', updatedData.boxes_per_set);
                editButton.attr('data-purchase', updatedData.purchase_price);
                editButton.attr('data-selling-single', updatedData.selling_price_single);
                editButton.attr('data-selling-wholesale', updatedData.selling_price_wholesale);
                editButton.attr('data-min-qty', updatedData.min_quantity);
                editButton.attr('data-notes', updatedData.notes);
                editButton.attr('aria-label', 'گۆڕینی ' + updatedData.name);
                
                // Update notes button
                const notesButton = productRow.find('button.view-notes');
                notesButton.attr('data-name', updatedData.name);
                notesButton.attr('data-notes', updatedData.notes || 'هیچ تێبینیەک نییە');
                notesButton.attr('aria-label', 'بینینی تێبینیەکانی ' + updatedData.name);
                
                // Update delete button
                const deleteButton = productRow.find('button.delete-product');
                deleteButton.attr('aria-label', 'سڕینەوەی ' + updatedData.name);
                
                // Highlight the updated row temporarily
                productRow.addClass('table-success');
                setTimeout(() => {
                    productRow.removeClass('table-success');
                }, 2000);
                
                // Hide the modal
                bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                
                // Show success message
                Swal.fire({
                    title: 'سەرکەوتوو بوو!',
                    text: 'زانیاریەکانی کاڵاکە بە سەرکەوتوویی گۆڕدرا.',
                    icon: 'success',
                    confirmButtonText: 'باشە'
                });
            } else {
                throw new Error(data.message || 'کێشەیەک ڕوویدا لە گۆڕینی زانیاریەکان.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'هەڵە!',
                text: error.message || 'کێشەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە.',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        })
        .finally(() => {
            // Reset button state
            $('#saveProductChanges').prop('disabled', false).text('پاشەکەوتکردن');
        });
    });

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        changePage(1);
    });

    // Add debounce function for live search
    let searchTimeout;
    function performSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            changePage(1);
        }, 300); // 300ms delay
    }

    // Handle live search
    $('#search').on('input', function() {
        performSearch();
    });

    // Handle search button click
    $('.search-btn').on('click', function() {
        changePage(1);
    });

    // Handle search input when pressing Enter
    $('#search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            changePage(1);
        }
    });

    // Handle category and unit dropdown changes
    $('#category, #unit').on('change', function() {
        changePage(1);
    });

    // Handle records per page change
    $('#recordsPerPage').on('change', function() {
        changePage(1);
    });

    // Handle reset filter button
    $('#resetFilter').on('click', function() {
        // Clear all filter inputs
        $('#search').val('');
        $('#category').val('').trigger('change');
        $('#unit').val('').trigger('change');
        $('#recordsPerPage').val('10');
        
        // Reset the page
        changePage(1);
    });

    // Initialize number formatting for edit form
    $(document).on('input', '#edit_purchase_price, #edit_selling_price_single, #edit_selling_price_wholesale', function() {
        let value = $(this).val().replace(/[^\d]/g, '');
        if (value) {
            value = parseInt(value).toLocaleString('en-US');
            $(this).val(value);
        }
    });
});