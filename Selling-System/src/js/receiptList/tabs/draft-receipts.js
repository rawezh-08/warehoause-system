// Draft Receipts Management
$(document).ready(function() {
    // Handle draft receipt finalization
    $(document).on('click', '.finalize-btn', function() {
        const receiptId = $(this).data('id');
        Swal.fire({
            title: 'دڵنیای لە تەواوکردنی پسووڵە؟',
            text: 'پاش تەواوکردن ناتوانیت گۆڕانکاری بکەیت',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، تەواو بکە',
            cancelButtonText: 'نەخێر، هەڵوەشانەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../api/receipts/finalize_draft.php',
                    method: 'POST',
                    data: { receipt_id: receiptId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو!',
                                text: 'پسووڵە بە سەرکەوتوویی تەواو کرا',
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا',
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });

    // Handle draft receipt deletion
    $(document).on('click', '.delete-btn', function() {
        const receiptId = $(this).data('id');
        Swal.fire({
            title: 'دڵنیای لە سڕینەوەی پسووڵە؟',
            text: 'پاش سڕینەوە ناتوانیت گەڕایەوە',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'نەخێر، هەڵوەشانەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../api/receipts/delete_draft.php',
                    method: 'POST',
                    data: { receipt_id: receiptId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو!',
                                text: 'پسووڵە بە سەرکەوتوویی سڕایەوە',
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا',
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });

    // Handle draft receipt view
    $(document).on('click', '#draftHistoryTable .view-btn', function(e) {
        e.preventDefault(); // Prevent default behavior
        const receiptId = $(this).data('id');
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch draft receipt details
        $.ajax({
            url: '../../api/receipts/get_draft_details.php',
            type: 'POST',
            data: { draft_id: receiptId },
            success: function(response) {
                Swal.close();
                
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        // Display draft details in a modal
                        showDraftDetails(data.draft);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: data.message || 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
                        });
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی کردن بە سێرڤەرەوە'
                });
            }
        });
    });

    // Handle draft receipt edit
    $(document).on('click', '.edit-btn', function(e) {
        e.preventDefault(); // Prevent default behavior
        const receiptId = $(this).data('id');
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch draft receipt details for editing
        $.ajax({
            url: '../../api/receipts/get_draft_details.php',
            type: 'POST',
            data: { draft_id: receiptId },
            success: function(response) {
                Swal.close();
                
                try {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        // Show edit form
                        showEditDraftForm(data.draft);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: data.message || 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
                        });
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی کردن بە سێرڤەرەوە'
                });
            }
        });
    });

    // Handle date filter changes
    $('#draftStartDate, #draftEndDate').on('change', function() {
        const startDate = $('#draftStartDate').val();
        const endDate = $('#draftEndDate').val();
        
        if (startDate && endDate) {
            $.ajax({
                url: '../../api/receipts/filter_drafts.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        updateDraftTable(response.data);
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: response.message || 'هەڵەیەک ڕوویدا',
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                        icon: 'error'
                    });
                }
            });
        }
    });

    // Handle reset filter
    $('#draftResetFilter').on('click', function() {
        $('#draftStartDate').val('');
        $('#draftEndDate').val('');
        location.reload();
    });

    // Function to update draft table with new data
    function updateDraftTable(data) {
        const tbody = $('#draftHistoryTable tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`<tr><td colspan="7" class="text-center">هیچ داتایەک نەدۆزرایەوە</td></tr>`);
            return;
        }

        data.forEach((draft, index) => {
            const row = `<tr>
                <td>${index + 1}</td>
                <td>${draft.date}</td>
                <td class="products-list-cell" data-products="${draft.products_list}">
                    ${draft.products_list}
                    <div class="products-popup"></div>
                </td>
                <td>${formatCurrency(draft.total_amount)}</td>
                <td>${draft.notes || ''}</td>
                <td>${draft.status}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${draft.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle edit-btn" data-id="${draft.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-circle finalize-btn" data-id="${draft.id}">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${draft.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
            tbody.append(row);
        });

        // Reinitialize product hover functionality
        initProductsListHover();
    }

    // Function to display draft details in a modal
    function showDraftDetails(draft) {
        // Create HTML for items
        let itemsHtml = '';
        if (draft.items && draft.items.length > 0) {
            draft.items.forEach((item, index) => {
                const unitTypeText = item.unit_type === 'piece' ? 'دانە' : 
                                    (item.unit_type === 'box' ? 'کارتۆن' : 'سێت');
                
                itemsHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.product_name}</td>
                        <td>${unitTypeText}</td>
                        <td>${item.quantity}</td>
                        <td>${Number(item.unit_price).toLocaleString()} د.ع</td>
                        <td>${Number(item.total_price).toLocaleString()} د.ع</td>
                    </tr>
                `;
            });
        } else {
            itemsHtml = '<tr><td colspan="6" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>';
        }

        // Calculate total
        const totalAmount = draft.total_amount || 0;
        
        // Create and show modal
        Swal.fire({
            title: `ڕەشنووسی پسووڵە: ${draft.invoice_number || ''}`,
            html: `
                <div class="draft-details">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>کڕیار:</strong> ${draft.customer_name || 'نەناسراو'}</p>
                            <p><strong>بەروار:</strong> ${draft.date || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>جۆری پارەدان:</strong> ${draft.payment_type === 'cash' ? 'نەقد' : 'قەرز'}</p>
                            <p><strong>کۆی گشتی:</strong> ${Number(totalAmount).toLocaleString()} د.ع</p>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>کاڵا</th>
                                    <th>یەکە</th>
                                    <th>بڕ</th>
                                    <th>نرخی یەکە</th>
                                    <th>کۆی گشتی</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <p><strong>تێبینی:</strong> ${draft.notes || 'هیچ تێبینیەک نیە'}</p>
                        </div>
                    </div>
                </div>
            `,
            width: 800,
            showCloseButton: true,
            showConfirmButton: false
        });
    }

    // Function to show edit form for draft receipt
    function showEditDraftForm(draft) {
        Swal.fire({
            title: 'دەستکاری ڕەشنووس',
            html: `<div class="text-center">بۆ دەستکاری ڕەشنووس، تکایە سەردانی بەشی پسووڵەی فرۆشتن بکە و ڕەشنووسێکی نوێ دروست بکە</div>`,
            icon: 'info',
            confirmButtonText: 'باشە'
        });
        
        // NOTE: Here you would typically implement a full edit form if needed
    }

    // Table search functionality
    $('#draftTableSearch').on('keyup', function() {
        loadDraftData();
    });
});

/**
 * Load draft receipts data and display in the table
 * @param {Object} customFilters Optional custom filters for draft data
 */
function loadDraftData(customFilters = {}) {
    // Show loading indicator
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Collect filter values from form fields
    const startDate = $('#draftStartDate').val();
    const endDate = $('#draftEndDate').val();
    const customerName = $('#draftCustomerName').val();
    const searchTerm = $('#draftTableSearch').val();
    
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
        url: '../../api/receipts/filter_draft.php',
        type: 'POST',
        data: filters,
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.status === 'success' || response.success) {
                // Get actual data array
                const draftData = response.data || response.draft || [];
                
                // Update table with data
                updateDraftTable(draftData);
            } else {
                console.error('API Error:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری'
                });
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
 * Update the draft table with filtered data
 * @param {Array} draftData - Array of draft data
 */
function updateDraftTable(draftData) {
    const tbody = $('#draftHistoryTable tbody');
    tbody.empty();
    
    if (draftData.length === 0) {
        tbody.html('<tr class="no-records"><td colspan="7" class="text-center">هیچ ڕەشنووسێک نەدۆزرایەوە</td></tr>');
        return;
    }
    
    draftData.forEach((draft, index) => {
        const row = `
            <tr data-id="${draft.id}">
                <td>${index + 1}</td>
                <td>${draft.invoice_number || 'N/A'} <span class="draft-badge">ڕەشنووس</span></td>
                <td>${draft.customer_name || 'N/A'}</td>
                <td>${formatDate(draft.date)}</td>
                <td>${draft.payment_type === 'cash' ? 'نەقد' : 'قەرز'}</td>
                <td>${numberFormat(draft.total_amount)}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${draft.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${draft.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-circle finalize-btn" data-id="${draft.id}">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${draft.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Initialize pagination
    initTablePagination(
        'draftHistoryTable',
        'draftRecordsPerPage',
        'draftPrevPageBtn',
        'draftNextPageBtn',
        'draftPaginationNumbers',
        'draftStartRecord',
        'draftEndRecord',
        'draftTotalRecords'
    );
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
function updateDraftPaginationInfo(totalRecords) {
    $('#draftTotalRecords').text(totalRecords);
    $('#draftEndRecord').text(Math.min(totalRecords, 10));
} 