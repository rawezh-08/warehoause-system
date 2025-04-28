/**
 * Load returns data and display in the table
 * @param {Object} customFilters Optional custom filters for returns data
 */
function loadReturnsData(customFilters = {}) {
    // Show loading indicator
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Collect filter values from form fields
    const startDate = $('#returnStartDate').val();
    const endDate = $('#returnEndDate').val();
    const customerName = $('#returnCustomerName').val();
    const searchTerm = $('#returnTableSearch').val();
    
    // Combine form filters with any custom filters
    const filters = {
        ...customFilters,
        start_date: startDate,
        end_date: endDate,
        customer_name: customerName,
        search: searchTerm
    };
    
    // Remove empty filters
    Object.keys(filters).forEach(key => {
        if (!filters[key]) {
            delete filters[key];
        }
    });
    
    // Make AJAX request
    $.ajax({
        url: '../../api/receipts/filter_returns.php',
        type: 'POST',
        data: filters,
        success: function(response) {
            Swal.close();
            
            try {
                const data = JSON.parse(response);
                if (data.status === 'success' || data.success) {
                    // Get actual data array
                    const returnsData = data.data || data.returns || [];
                    
                    // Update table with data
                    updateReturnsTable(returnsData);
                } else {
                    console.error('API Error:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: data.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
                    });
                }
            } catch (e) {
                console.error('Parse Error:', e, response);
                // If response is already an object, use it directly
                if (typeof response === 'object' && response.success) {
                    updateReturnsTable(response.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە پڕۆسەکردنی داتا'
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەر'
            });
        }
    });
}

/**
 * Update the returns table with filtered data
 * @param {Array} returnsData Array of returns data
 */
function updateReturnsTable(returnsData) {
    const tbody = $('#returnHistoryTable tbody');
    tbody.empty();
    
    if (returnsData.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center">هیچ گەڕاندنەوەیەک نەدۆزرایەوە</td></tr>');
        return;
    }
    
    returnsData.forEach((returnItem, index) => {
        const row = `
            <tr data-id="${returnItem.id}">
                <td>${index + 1}</td>
                <td>${returnItem.invoice_number || 'N/A'}</td>
                <td>${returnItem.original_invoice_number || 'N/A'}</td>
                <td>${returnItem.customer_name || 'N/A'}</td>
                <td>${formatDate(returnItem.date)}</td>
                <td>${numberFormat(returnItem.total_amount || 0)}</td>
                <td>${returnItem.notes || ''}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${returnItem.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${returnItem.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Update pagination info
    updateReturnPaginationInfo(returnsData.length);
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).replace(/(\d+)\/(\d+)\/(\d+)/, '$3/$1/$2'); // Convert MM/DD/YYYY to YYYY/MM/DD
}

// Helper function to format numbers
function numberFormat(number) {
    return Number(number).toLocaleString() + ' د.ع';
}

// Update pagination information
function updateReturnPaginationInfo(totalRecords) {
    $('#returnTotalRecords').text(totalRecords);
    $('#returnEndRecord').text(Math.min(totalRecords, 10));
}

// Add search functionality on document ready
$(document).ready(function() {
    // Table search functionality
    $('#returnTableSearch').on('keyup', function() {
        loadReturnsData();
    });
}); 