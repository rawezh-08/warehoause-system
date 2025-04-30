$(document).ready(function() {
    // Tab switching functionality
    $('#receiptTabs button').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    // Initialize all tables when their tabs are shown
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetId = $(e.target).attr('id');
        
        // Refresh the table when its tab is shown
        if (targetId === 'sales-tab') {
            refreshSalesTable();
        } else if (targetId === 'purchases-tab') {
            // Future implementation for purchases
        } else if (targetId === 'returns-tab') {
            // Future implementation for returns
        }
    });

    // Sales table management
    function refreshSalesTable() {
        // Sales table pagination and search functionality
        initializeTable('#salesTable', '#salesSearchInput', '#salesPagination', 
                        '#salesPrevPage', '#salesNextPage', '#salesFrom', '#salesTo', '#salesTotalItems');
    }

    // Generic table initialization function
    function initializeTable(tableId, searchInputId, paginationId, prevPageId, nextPageId, fromId, toId, totalItemsId) {
        const itemsPerPage = 10;
        let currentPage = 1;
        let tableData = [];
        
        // Get all table rows and store them in tableData
        $(tableId + ' tbody tr').each(function() {
            tableData.push($(this));
        });
        
        // Function to display the current page
        function displayPage(page) {
            // Clear the table
            $(tableId + ' tbody').empty();
            
            // Calculate start and end indices
            const start = (page - 1) * itemsPerPage;
            const end = Math.min(start + itemsPerPage, tableData.length);
            
            // Display rows for the current page
            for (let i = start; i < end; i++) {
                $(tableId + ' tbody').append(tableData[i]);
            }
            
            // Update pagination info
            $(fromId).text(tableData.length > 0 ? start + 1 : 0);
            $(toId).text(end);
            $(totalItemsId).text(tableData.length);
            
            // Update pagination controls
            updatePagination(page);
        }
        
        // Function to update pagination controls
        function updatePagination(page) {
            const totalPages = Math.ceil(tableData.length / itemsPerPage);
            
            // Clear pagination
            $(paginationId).empty();
            
            // Generate pagination buttons
            for (let i = 1; i <= totalPages; i++) {
                $(paginationId).append(`
                    <button class="btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-secondary'}" 
                            data-page="${i}">${i}</button>
                `);
            }
            
            // Enable/disable prev/next buttons
            $(prevPageId).prop('disabled', page === 1);
            $(nextPageId).prop('disabled', page >= totalPages);
            
            // Update current page
            currentPage = page;
        }
        
        // Event listeners for pagination
        $(paginationId).on('click', 'button', function() {
            const page = parseInt($(this).data('page'));
            displayPage(page);
        });
        
        $(prevPageId).click(function() {
            if (currentPage > 1) {
                displayPage(currentPage - 1);
            }
        });
        
        $(nextPageId).click(function() {
            const totalPages = Math.ceil(tableData.length / itemsPerPage);
            if (currentPage < totalPages) {
                displayPage(currentPage + 1);
            }
        });
        
        // Search functionality
        $(searchInputId).on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            // Collect all original rows if needed
            const originalRows = [];
            $(tableId + ' tbody tr').each(function() {
                originalRows.push($(this));
            });
            
            // If search term is empty, restore original data
            if (searchTerm.trim() === '') {
                tableData = [...originalRows];
            } else {
                // Filter data based on search term
                tableData = [];
                originalRows.forEach(row => {
                    const rowText = row.text().toLowerCase();
                    if (rowText.includes(searchTerm)) {
                        tableData.push(row.clone());
                    }
                });
            }
            
            // Display first page of filtered results
            displayPage(1);
        });
        
        // Initialize the first page
        displayPage(1);
    }

    // Initialize the sales table on page load
    refreshSalesTable();
    
    // View Receipt Details 
    $(document).on('click', '.view-receipt-btn', function() {
        const receiptId = $(this).data('receipt-id');
        const receiptType = $(this).data('receipt-type');
        
        // Redirect to the appropriate details page
        if (receiptType === 'sale') {
            window.location.href = `saleDetails.php?id=${receiptId}`;
        } else if (receiptType === 'purchase') {
            window.location.href = `purchaseDetails.php?id=${receiptId}`;
        } else if (receiptType === 'return') {
            window.location.href = `returnDetails.php?id=${receiptId}`;
        }
    });
    
    // Print Receipt
    $(document).on('click', '.print-receipt-btn', function() {
        const receiptId = $(this).data('receipt-id');
        const receiptType = $(this).data('receipt-type');
        
        // Open the print page in a new tab
        window.open(`printReceipt.php?id=${receiptId}&type=${receiptType}`, '_blank');
    });

    // Edit button click handler
    $('.edit-btn').on('click', function() {
        const saleId = $(this).data('id');
        window.location.href = `../../Views/receipt/edit_receipt.php?sale_id=${saleId}`;
    });

    // Show invoice items button click handler
    $('.show-invoice-items').on('click', function() {
        const invoiceNumber = $(this).data('invoice');
        Swal.fire({
            title: 'کاڵاکانی پسووڵە',
            html: 'سەیرکردن...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                $.ajax({
                    url: '../../Controllers/receipt/get_invoice_items.php',
                    type: 'POST',
                    data: { invoice_number: invoiceNumber },
                    success: function(response) {
                        if (response.success) {
                            let itemsHtml = '<div class="table-responsive"><table class="table table-bordered">';
                            itemsHtml += '<thead><tr><th>ناوی کاڵا</th><th>بڕ</th><th>یەکە</th><th>نرخی تاک</th><th>کۆی گشتی</th></tr></thead>';
                            itemsHtml += '<tbody>';
                            response.items.forEach(item => {
                                itemsHtml += `<tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>${item.unit_type === 'piece' ? 'دانە' : item.unit_type === 'box' ? 'کارتۆن' : 'سێت'}</td>
                                    <td>${item.unit_price} د.ع</td>
                                    <td>${item.total_price} د.ع</td>
                                </tr>`;
                            });
                            itemsHtml += '</tbody></table></div>';
                            Swal.fire({
                                title: 'کاڵاکانی پسووڵە',
                                html: itemsHtml,
                                width: '800px'
                            });
                        } else {
                            Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان', 'error');
                    }
                });
            }
        });
    });

    // Return products button click handler
    $('.return-products-btn').on('click', function() {
        const saleId = $(this).data('id');
        const invoiceNumber = $(this).data('invoice');
        window.location.href = `../../Views/receipt/return_products.php?sale_id=${saleId}&invoice_number=${invoiceNumber}`;
    });

    // Delete sale button click handler
    $('.delete-sale').on('click', function() {
        const saleId = $(this).data('id');
        const invoiceNumber = $(this).data('invoice');
        
        Swal.fire({
            title: 'دڵنیای لە سڕینەوە؟',
            text: `ئایا دڵنیای لە سڕینەوەی پسووڵەی ${invoiceNumber}؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بیسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../Controllers/receipt/delete_sale.php',
                    type: 'POST',
                    data: { sale_id: saleId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('سڕایەوە!', 'پسووڵەکە بە سەرکەوتوویی سڕایەوە.', 'success')
                            .then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('هەڵە', response.message || 'هەڵەیەک ڕوویدا لە کاتی سڕینەوە', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا لە کاتی سڕینەوە', 'error');
                    }
                });
            }
        });
    });
}); 