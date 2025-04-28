// Custom number formatting function for Iraqi Dinar
function numberFormat(number) {
    return number_format(number, 0, '.', ',') + ' د.ع';
}

// Helper function for number formatting
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
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

// Wasting Receipts Management
$(document).ready(function() {
    // Handle wasting receipt view
    $(document).on('click', '#withdrawalHistoryTable .view-btn', function() {
        const wastingId = $(this).data('id');
        viewWastingDetails(wastingId);
    });

    /**
     * View receipt details - a function to handle viewing of wasting/withdrawal receipts
     * @param {number} id - ID of the receipt to view
     */
    function viewReceipt(id) {
        // Simply call the appropriate function for wasting receipts
        viewWastingDetails(id);
    }

    // Function to view wasting details
    function viewWastingDetails(wastingId) {
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch wasting details
        $.ajax({
            url: '../../api/receipts/get_wasting_details.php',
            type: 'POST',
            data: { wasting_id: wastingId },
            success: function(response) {
                Swal.close();
                
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.status === 'success') {
                        // Display wasting details
                        showWastingDetailsModal(data.wasting);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: data.message || 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
                        });
                    }
                } catch (e) {
                    console.error('JSON parsing error:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr, status, error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی کردن بە سێرڤەرەوە'
                });
            }
        });
    }

    // Function to show wasting details modal
    function showWastingDetailsModal(wasting) {
        let itemsHtml = '';
        
        if (wasting.items && wasting.items.length > 0) {
            wasting.items.forEach((item, index) => {
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
        
        const modalHtml = `
            <div class="container-fluid">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>بەروار:</strong> ${formatDate(wasting.date)}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>تێبینی:</strong> ${wasting.notes || 'هیچ تێبینییەک نیە'}</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h5>کاڵاکان</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>ناوی کاڵا</th>
                                        <th>یەکە</th>
                                        <th>بڕ</th>
                                        <th>نرخی یەکە</th>
                                        <th>کۆی گشتی</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHtml}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-left">کۆی گشتی:</th>
                                        <th>${Number(wasting.total_amount).toLocaleString()} د.ع</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        Swal.fire({
            title: 'وردەکاری بەفیڕۆچوو',
            html: modalHtml,
            width: 800,
            confirmButtonText: 'داخستن',
            customClass: {
                popup: 'swal-rtl',
                confirmButton: 'btn btn-primary'
            }
        });
    }

    // Handle date filter changes
    $('#withdrawalStartDate, #withdrawalEndDate').on('change', function() {
        const startDate = $('#withdrawalStartDate').val();
        const endDate = $('#withdrawalEndDate').val();
        const searchTerm = $('#withdrawalTableSearch').val();
        const recordsPerPage = $('#withdrawalRecordsPerPage').val();
        
        if (startDate && endDate) {
            $.ajax({
                url: '../../api/receipts/filter_wastings.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    search: searchTerm,
                    records_per_page: recordsPerPage,
                    page: 1
                },
                success: function(response) {
                    if (response.success) {
                        updateWastingTable(response.data);
                        if (response.pagination) {
                            updateWastingPagination(response.pagination);
                        }
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: response.message || 'هەڵەیەک ڕوویدا',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Filter error:', xhr.responseText);
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
    $('#withdrawalResetFilter').on('click', function() {
        $('#withdrawalStartDate').val('');
        $('#withdrawalEndDate').val('');
        location.reload();
    });

    /**
     * Update the wasting table with filtered data
     * @param {Array} wastingData - Array of wasting data
     */
    function updateWastingTable(wastingData) {
        const tbody = $('#withdrawalHistoryTable tbody');
        tbody.empty();
        
        if (wastingData.length === 0) {
            tbody.html('<tr class="no-records"><td colspan="6" class="text-center">هیچ بەفیڕۆچوویەک نەدۆزرایەوە</td></tr>');
            return;
        }
        
        wastingData.forEach((wasting, index) => {
            const row = `
                <tr data-id="${wasting.id}">
                    <td>${index + 1}</td>
                    <td>${formatDate(wasting.date)}</td>
                    <td class="products-list-cell" data-products="${wasting.products_list || ''}">
                        ${wasting.products_list || ''}
                        <div class="products-popup"></div>
                    </td>
                    <td>${numberFormat(wasting.total_amount)}</td>
                    <td>${wasting.notes || ''}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${wasting.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${wasting.id}">
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
            'withdrawalHistoryTable',
            'withdrawalRecordsPerPage',
            'withdrawalPrevPageBtn',
            'withdrawalNextPageBtn',
            'withdrawalPaginationNumbers',
            'withdrawalStartRecord',
            'withdrawalEndRecord',
            'withdrawalTotalRecords'
        );
    }

    // Function to load wasting data
    function loadWastingData() {
        const startDate = $('#withdrawalStartDate').val();
        const endDate = $('#withdrawalEndDate').val();
        const searchTerm = $('#withdrawalTableSearch').val();
        const recordsPerPage = $('#withdrawalRecordsPerPage').val();

        $.ajax({
            url: '../../api/receipts/get_wasting_data.php',
            method: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate,
                search: searchTerm,
                records_per_page: recordsPerPage
            },
            success: function(response) {
                if (response.success) {
                    updateWastingTable(response.data);
                    updateWastingPagination(response.pagination);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'کێشەیەک هەیە لە پەیوەندی بە سێرڤەرەوە'
                });
            }
        });
    }

    // Function to update wasting pagination
    function updateWastingPagination(pagination) {
        const { current_page, total_pages, total_records, start_record, end_record } = pagination;
        
        $('#withdrawalStartRecord').text(start_record);
        $('#withdrawalEndRecord').text(end_record);
        $('#withdrawalTotalRecords').text(total_records);
        
        const paginationNumbers = $('#withdrawalPaginationNumbers');
        paginationNumbers.empty();
        
        // Previous button
        $('#withdrawalPrevPageBtn').prop('disabled', current_page === 1);
        
        // Page numbers
        for (let i = 1; i <= total_pages; i++) {
            const button = $(`
                <button class="btn btn-sm ${i === current_page ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2">
                    ${i}
                </button>
            `);
            
            button.on('click', function() {
                loadWastingData(i);
            });
            
            paginationNumbers.append(button);
        }
        
        // Next button
        $('#withdrawalNextPageBtn').prop('disabled', current_page === total_pages);
    }

    // Function to delete a wasting record
    function deleteWastingRecord(wastingId) {
        Swal.fire({
            title: 'دڵنیای',
            text: 'دڵنیای لە سڕینەوەی ئەم بەفیڕۆچووە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'چاوەڕێ بکە...',
                    text: 'سڕینەوەی بەفیڕۆچوو',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: '../../api/receipts/delete_wasting.php',
                    method: 'POST',
                    data: { wasting_id: wastingId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: response.message || 'بەفیڕۆچووەکە بە سەرکەوتوویی سڕایەوە'
                            }).then(() => {
                                loadWastingData();
                            });
                        } else {
                            console.error('Delete error:', response);
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی بەفیڕۆچوو'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.error('AJAX error:', xhr.responseText);
                        
                        let errorMessage = 'کێشەیەک هەیە لە پەیوەندی بە سێرڤەرەوە';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch(e) {
                            console.error('Error parsing response:', e);
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    }

    // Initialize wasting receipts functionality
    $(document).ready(function() {
        // Load initial data
        loadWastingData();
        
        // Handle filter changes
        $('.auto-filter').on('change', function() {
            loadWastingData();
        });
        
        // Handle search input
        let searchTimeout;
        $('#withdrawalTableSearch').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadWastingData();
            }, 500);
        });
        
        // Handle records per page change
        $('#withdrawalRecordsPerPage').on('change', function() {
            loadWastingData();
        });
        
        // Handle reset filter
        $('#withdrawalResetFilter').on('click', function() {
            $('#withdrawalStartDate').val('');
            $('#withdrawalEndDate').val('');
            $('#withdrawalTableSearch').val('');
            loadWastingData();
        });
        
        // Handle refresh button
        $('.refresh-btn').on('click', function() {
            loadWastingData();
        });
        
        // Handle view button
        $(document).on('click', '.view-btn', function() {
            const receiptId = $(this).data('id');
            viewReceipt(receiptId, 'wasting');
        });
    });
}); 