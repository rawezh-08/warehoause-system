/**
 * Common utility functions for receipt management
 */

/**
 * Format date for display
 * @param {Date|string} date - Date object or date string
 * @returns {string} Formatted date string (YYYY/MM/DD)
 */
function formatDate(date) {
    if (!date) return '';
    
    if (typeof date === 'string') {
        date = new Date(date);
    }
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}/${month}/${day}`;
}

/**
 * Format date for input fields
 * @param {string} dateString - Date string
 * @returns {string} Formatted date string (YYYY-MM-DD)
 */
function formatDateForInput(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
}

/**
 * Format number as currency
 * @param {number} number - Number to format
 * @returns {string} Formatted currency string
 */
function formatCurrency(number) {
    return number_format(number, 0, '.', ',') + ' د.ع';
}

/**
 * PHP-like number_format function for JavaScript
 * @param {number} number - The number to format
 * @param {number} decimals - Number of decimal places
 * @param {string} dec_point - Decimal separator
 * @param {string} thousands_sep - Thousands separator
 * @returns {string} Formatted number string
 */
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    const n = !isFinite(+number) ? 0 : +number;
    const prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    const sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep;
    const dec = (typeof dec_point === 'undefined') ? '.' : dec_point;
    
    let s = '';
    
    const toFixedFix = function (n, prec) {
        const k = Math.pow(10, prec);
        return '' + Math.round(n * k) / k;
    };
    
    // Fix for IE parseFloat(0.55).toFixed(0) = 0
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    
    return s.join(dec);
}

/**
 * Show error message using SweetAlert
 * @param {string} message - Error message to display
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'هەڵە!',
        text: message || 'هەڵەیەک ڕوویدا',
        confirmButtonText: 'باشە'
    });
}

/**
 * Show success message using SweetAlert
 * @param {string} message - Success message to display
 */
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'سەرکەوتوو بوو!',
        text: message,
        confirmButtonText: 'باشە'
    });
}

/**
 * Show loading indicator
 * @param {string} message - Loading message to display
 */
function showLoading(message = 'تکایە چاوەڕێ بکە...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    Swal.close();
}

/**
 * Confirm action with SweetAlert
 * @param {string} title - Confirmation title
 * @param {string} text - Confirmation text
 * @param {string} confirmButtonText - Text for confirm button
 * @param {string} cancelButtonText - Text for cancel button
 * @param {Function} onConfirm - Callback function on confirmation
 */
function confirmAction(title, text, confirmButtonText, cancelButtonText, onConfirm) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText || 'بەڵێ',
        cancelButtonText: cancelButtonText || 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed && typeof onConfirm === 'function') {
            onConfirm();
        }
    });
}

/**
 * Initialize DataTable with common options
 * @param {string} tableId - ID of the table element
 * @param {Object} options - Additional DataTable options
 * @returns {Object} DataTable instance
 */
function initDataTable(tableId, options = {}) {
    const defaultOptions = {
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ku.json'
        },
        dom: 'Bfrtip',
        buttons: [
            'copy', 'excel', 'pdf', 'print'
        ]
    };
    
    return $(`#${tableId}`).DataTable({
        ...defaultOptions,
        ...options
    });
}

/**
 * Initialize Date Range Picker for filters
 * @param {string} startDateId - ID of start date input
 * @param {string} endDateId - ID of end date input
 */
function initDateRangePicker(startDateId, endDateId) {
    // Set default dates (current month)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $(`#${startDateId}`).val(formatDateForInput(firstDay));
    $(`#${endDateId}`).val(formatDateForInput(today));
}

/**
 * Apply event handlers for product list hover in tables
 */
function initProductsListHover() {
    // Show products popup on hover
    $(document).on('mouseenter', '.products-list-cell', function() {
        const products = $(this).data('products');
        if (products) {
            const productsList = products.split(', ');
            let popupHtml = '<ul class="product-list">';
            
            productsList.forEach(product => {
                popupHtml += `<li class="product-item">${product}</li>`;
            });
            
            popupHtml += '</ul>';
            
            $(this).find('.products-popup').html(popupHtml).show();
        }
    });
    
    // Hide products popup when mouse leaves
    $(document).on('mouseleave', '.products-list-cell', function() {
        $(this).find('.products-popup').hide();
    });
}

/**
 * Scroll to top of page
 */
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

/**
 * Initialize pagination for a table
 * @param {string} tableId - ID of the table
 * @param {string} recordsPerPageId - ID of the records per page select
 * @param {string} prevBtnId - ID of the previous page button
 * @param {string} nextBtnId - ID of the next page button
 * @param {string} paginationNumbersId - ID of the pagination numbers container
 * @param {string} startRecordId - ID of the start record span
 * @param {string} endRecordId - ID of the end record span
 * @param {string} totalRecordsId - ID of the total records span
 */
function initTablePagination(tableId, recordsPerPageId, prevBtnId, nextBtnId, paginationNumbersId, startRecordId, endRecordId, totalRecordsId) {
    let currentPage = 1;
    let recordsPerPage = parseInt($(`#${recordsPerPageId}`).val()) || 10;
    const table = $(`#${tableId}`);
    const rows = table.find('tbody tr:not(.no-records)');
    const totalRecords = rows.length;
    
    // Update records per page when select changes
    $(`#${recordsPerPageId}`).on('change', function() {
        recordsPerPage = parseInt($(this).val());
        currentPage = 1;
        updatePagination();
    });
    
    // Previous page button click
    $(`#${prevBtnId}`).on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            updatePagination();
        }
    });
    
    // Next page button click
    $(`#${nextBtnId}`).on('click', function() {
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updatePagination();
        }
    });
    
    // Update pagination
    function updatePagination() {
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        
        // Hide all rows
        rows.hide();
        
        // Show rows for current page
        rows.slice(startIndex, endIndex).show();
        
        // Update pagination info
        $(`#${startRecordId}`).text(totalRecords > 0 ? startIndex + 1 : 0);
        $(`#${endRecordId}`).text(Math.min(endIndex, totalRecords));
        $(`#${totalRecordsId}`).text(totalRecords);
        
        // Update pagination buttons
        $(`#${prevBtnId}`).prop('disabled', currentPage === 1);
        $(`#${nextBtnId}`).prop('disabled', currentPage === totalPages || totalPages === 0);
        
        // Update pagination numbers
        updatePaginationNumbers();
    }
    
    // Update pagination numbers
    function updatePaginationNumbers() {
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        let paginationHtml = '';
        
        // Always show first page
        paginationHtml += `
            <button class="btn btn-sm ${1 === currentPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2 ${1 === currentPage ? 'active' : ''}" 
                data-page="1">
                1
            </button>
        `;
        
        // Calculate range of pages to show
        let startPage = Math.max(2, currentPage - 2);
        let endPage = Math.min(totalPages - 1, currentPage + 2);
        
        // Add dots after first page if needed
        if (startPage > 2) {
            paginationHtml += '<span class="mx-2">...</span>';
        }
        
        // Add pages in the middle
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2 ${i === currentPage ? 'active' : ''}" 
                    data-page="${i}">
                    ${i}
                </button>
            `;
        }
        
        // Add dots before last page if needed
        if (endPage < totalPages - 1) {
            paginationHtml += '<span class="mx-2">...</span>';
        }
        
        // Always show last page if there is more than one page
        if (totalPages > 1) {
            paginationHtml += `
                <button class="btn btn-sm ${totalPages === currentPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2 ${totalPages === currentPage ? 'active' : ''}" 
                    data-page="${totalPages}">
                    ${totalPages}
                </button>
            `;
        }
        
        $(`#${paginationNumbersId}`).html(paginationHtml);
        
        // Add click handlers for pagination numbers
        $(`#${paginationNumbersId} button`).on('click', function() {
            currentPage = parseInt($(this).data('page'));
            updatePagination();
        });
    }
    
    // Initialize pagination
    updatePagination();
}

/**
 * Show return form for products
 * @param {number} receiptId - ID of the receipt
 * @param {string} receiptType - Type of receipt ('sale' or 'purchase')
 * @param {Array} items - Array of items to potentially return
 */
function showReturnForm(receiptId, receiptType, items) {
    console.log("showReturnForm called with:", {
        receiptId: receiptId,
        receiptType: receiptType,
        items: items
    });

    // Validate receiptId
    if (!receiptId) {
        console.error("Invalid receipt ID:", receiptId);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'ناسنامەی پسووڵە نادروستە'
        });
        return;
    }

    // Validate receiptType
    if (!receiptType || (receiptType !== 'selling' && receiptType !== 'buying')) {
        console.error("Invalid receipt type:", receiptType);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'جۆری پسووڵە نادروستە'
        });
        return;
    }

    // Check if items is valid array
    if (!items || !Array.isArray(items)) {
        console.error("Invalid items (not an array):", items);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'هیچ کاڵایەک نەدۆزرایەوە بۆ گەڕاندنەوە'
        });
        return;
    }

    // Check if array is empty
    if (items.length === 0) {
        console.error("No items found (empty array)");
        Swal.fire({
            icon: 'info',
            title: 'ئاگاداری',
            text: 'هیچ کاڵایەک نەدۆزرایەوە بۆ گەڕاندنەوە'
        });
        return;
    }

    let formHtml = `
        <form id="returnForm">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ناوی کاڵا</th>
                            <th>یەکە</th>
                            <th>بڕی کڕدراو</th>
                            <th>بڕی گەڕاوە</th>
                            <th>بڕی گەڕاندنەوە</th>
                            <th>هۆکار</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    let itemsChecked = 0;
    let itemsIncluded = 0;
    let itemsSkipped = 0;
    let hasReturnableItems = false;

    items.forEach(item => {
        itemsChecked++;
        
        // Validate item data
        if (!item || typeof item !== 'object') {
            console.warn("Invalid item data (not an object):", item);
            itemsSkipped++;
            return;
        }

        // Check required properties
        if (!item.product_name || !item.product_id) {
            console.warn("Missing required item properties:", item);
            itemsSkipped++;
            return;
        }

        // For debugging
        console.log(`Processing item: ${item.product_name} (ID: ${item.product_id})`);
        console.log(`  - Original quantity: ${item.quantity}`);
        console.log(`  - Returned quantity: ${item.returned_quantity || 0}`);
        
        // Ensure numeric values
        const quantity = parseFloat(item.quantity) || 0;
        const returnedQuantity = parseFloat(item.returned_quantity) || 0;
        const remainingQty = Math.max(0, quantity - returnedQuantity);
        
        console.log(`  - Calculated remaining quantity: ${remainingQty}`);
        
        if (remainingQty > 0) {
            hasReturnableItems = true;
            itemsIncluded++;
            
            const unitTypeText = item.unit_type === 'piece' ? 'دانە' : 
                               (item.unit_type === 'box' ? 'کارتۆن' : 'سێت');
            
            formHtml += `
                <tr data-item-id="${item.id}" data-product-id="${item.product_id}">
                    <td>${item.product_name}</td>
                    <td>${unitTypeText}</td>
                    <td>${quantity}</td>
                    <td>${returnedQuantity}</td>
                    <td>
                        <input type="number" class="form-control return-qty" 
                            min="0" max="${remainingQty}" value="0" step="1"
                            data-unit-type="${item.unit_type || 'piece'}"
                            data-unit-price="${item.unit_price || 0}"
                            data-max-return="${remainingQty}"
                            onchange="validateReturnQuantity(this)">
                    </td>
                    <td>
                        <select class="form-control return-reason">
                            <option value="damaged">خراپ بوو</option>
                            <option value="wrong_product">کاڵای هەڵە</option>
                            <option value="customer_request">داواکاری کڕیار</option>
                            <option value="other">هۆکاری تر</option>
                        </select>
                    </td>
                </tr>
            `;
        } else {
            console.log(`  - Item skipped: No remaining quantity to return`);
            itemsSkipped++;
        }
    });

    formHtml += `
                    </tbody>
                </table>
            </div>
            <div class="mb-3">
                <label for="returnNotes" class="form-label">تێبینی</label>
                <textarea id="returnNotes" class="form-control" rows="3"></textarea>
            </div>
        </form>
    `;

    console.log(`Return form summary: ${itemsChecked} items checked, ${itemsIncluded} included, ${itemsSkipped} skipped`);

    if (!hasReturnableItems) {
        Swal.fire({
            icon: 'info',
            title: 'ئاگاداری',
            text: 'هەموو کاڵاکان گەڕێنراونەتەوە'
        });
        return;
    }

    // Add validation function to window scope
    window.validateReturnQuantity = function(input) {
        const value = parseFloat(input.value) || 0;
        const maxReturn = parseFloat(input.dataset.maxReturn) || 0;
        
        if (value > maxReturn) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: `بڕی گەڕاندنەوە نابێت لە ${maxReturn} زیاتر بێت`
            });
            input.value = maxReturn;
        }
    };

    Swal.fire({
        title: 'گەڕاندنەوەی کاڵا',
        html: formHtml,
        showCancelButton: true,
        confirmButtonText: 'پاشەکەوتکردن',
        cancelButtonText: 'داخستن',
        showLoaderOnConfirm: true,
        width: '900px',
        customClass: {
            container: 'return-form-dialog',
            popup: 'return-form-popup',
            content: 'return-form-content'
        },
        preConfirm: () => {
            // Collect return data
            const returnData = {
                receipt_id: receiptId,
                receipt_type: receiptType,
                notes: $('#returnNotes').val(),
                items: []
            };

            // Collect items to return
            $('.return-qty').each(function() {
                const qty = parseFloat($(this).val());
                if (qty > 0) {
                    const row = $(this).closest('tr');
                    const maxReturn = parseFloat($(this).data('max-return'));
                    
                    if (qty > maxReturn) {
                        Swal.showValidationMessage(`بڕی گەڕاندنەوە نابێت لە ${maxReturn} زیاتر بێت`);
                        return false;
                    }
                    
                    returnData.items.push({
                        product_id: row.data('product-id'),
                        quantity: qty,
                        unit_type: $(this).data('unit-type'),
                        unit_price: $(this).data('unit-price'),
                        reason: row.find('.return-reason').val()
                    });
                }
            });

            if (returnData.items.length === 0) {
                Swal.showValidationMessage('تکایە بڕی گەڕاندنەوە دیاری بکە');
                return false;
            }

            console.log("Return data:", returnData);
            return returnData;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            processReturn(result.value);
        }
    });
}

/**
 * Process the return of products
 * @param {Object} returnData - Data about the return
 */
function processReturn(returnData) {
    console.log("Processing return data:", returnData);
    
    // Show loading
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send return data to server
    $.ajax({
        url: '../../api/receipts/process_return.php',
        type: 'POST',
        data: returnData,
        dataType: 'json',
        success: function(response) {
            Swal.close();
            console.log("Return processing response:", response);
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو!',
                    text: 'گەڕاندنەوەی کاڵاکان بە سەرکەوتوویی ئەنجام درا',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    // Reload the page or update the table
                    location.reload();
                });
            } else {
                console.error("Return processing error:", response);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    html: `<div dir="ltr" style="text-align: left;">
                        <p>هەڵەیەک ڕوویدا لە گەڕاندنەوەی کاڵاکان:</p>
                        <pre style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow: auto;">${response.message || 'Unknown error'}</pre>
                    </div>`,
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error("AJAX error in processReturn:", {xhr, status, error});
            
            let errorDetails = '';
            try {
                if (xhr.responseText) {
                    errorDetails = xhr.responseText;
                }
            } catch (e) {
                errorDetails = error || 'Unknown error';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'هەڵە لە پەیوەندی کردن',
                html: `<div dir="ltr" style="text-align: left;">
                    <p>هەڵەیەک ڕوویدا لە کاتی پەیوەندی کردن بە سێرڤەر:</p>
                    <p><strong>Status:</strong> ${status}</p>
                    <p><strong>Error:</strong> ${error}</p>
                    <pre style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow: auto;">${errorDetails}</pre>
                </div>`,
                confirmButtonText: 'باشە'
            });
        }
    });
} 