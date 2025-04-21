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