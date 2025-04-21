/**
 * Load pending receipts data and display in the table
 * @param {Object} customFilters Optional custom filters for pending data
 */
function loadPendingData(customFilters = {}) {
    // Show loading indicator
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Collect filter values from form fields
    const startDate = $('#pendingStartDate').val();
    const endDate = $('#pendingEndDate').val();
    const customerName = $('#pendingCustomerName').val();
    const searchTerm = $('#pendingTableSearch').val();
    
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
        url: '../../api/receipts/filter_pending.php',
        type: 'POST',
        data: filters,
        success: function(response) {
            Swal.close();
            
            try {
                const data = JSON.parse(response);
                if (data.status === 'success' || data.success) {
                    // Get actual data array
                    const pendingData = data.data || data.pending || [];
                    
                    // Update table with data
                    updatePendingTable(pendingData);
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
                    updatePendingTable(response.data);
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
 * Update the pending receipts table with filtered data
 * @param {Array} pendingData Array of pending receipt data
 */
function updatePendingTable(pendingData) {
    const tbody = $('#pendingHistoryTable tbody');
    tbody.empty();
    
    if (pendingData.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center">هیچ پسوڵەیەکی هەڵواسراو نەدۆزرایەوە</td></tr>');
        return;
    }
    
    pendingData.forEach((pending, index) => {
        const row = `
            <tr data-id="${pending.id}">
                <td>${index + 1}</td>
                <td>${pending.invoice_number || 'N/A'}</td>
                <td>${pending.customer_name || 'N/A'}</td>
                <td>${formatDate(pending.date)}</td>
                <td>${numberFormat(pending.total_amount || 0)}</td>
                <td>${pending.notes || ''}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${pending.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${pending.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-circle complete-btn" data-id="${pending.id}">
                            <i class="fas fa-check-double"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${pending.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Update pagination info
    updatePendingPaginationInfo(pendingData.length);
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
function updatePendingPaginationInfo(totalRecords) {
    $('#pendingTotalRecords').text(totalRecords);
    $('#pendingEndRecord').text(Math.min(totalRecords, 10));
}

// Add search functionality on document ready
$(document).ready(function() {
    // Table search functionality
    $('#pendingTableSearch').on('keyup', function() {
        loadPendingData();
    });
}); 