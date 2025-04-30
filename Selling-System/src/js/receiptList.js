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
        let totalItems = 0;
        let filteredItems = [];
        
        // Get all table rows and store them in tableData
        $(tableId + ' tbody tr').each(function() {
            filteredItems.push($(this));
        });
        
        totalItems = filteredItems.length;
        
        // Function to display the current page
        function showPage(page) {
            // Clear the table
            $(tableId + ' tbody').empty();
            
            // Calculate start and end indices
            const start = (page - 1) * itemsPerPage;
            const end = Math.min(start + itemsPerPage, totalItems);
            
            // Display rows for the current page
            for (let i = start; i < end; i++) {
                $(tableId + ' tbody').append(filteredItems[i]);
            }
            
            // Update pagination info
            $(fromId).text(start + 1);
            $(toId).text(end);
            $(totalItemsId).text(totalItems);
            
            // Update pagination controls
            updatePaginationButtons();
        }
        
        // Function to update pagination controls
        function updatePaginationButtons() {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            // Clear pagination
            $(paginationId).empty();
            
            // Generate pagination buttons
            for (let i = 1; i <= totalPages; i++) {
                $(paginationId).append(`
                    <button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} page-number" 
                            data-page="${i}">${i}</button>
                `);
            }
            
            // Enable/disable prev/next buttons
            $(prevPageId).prop('disabled', currentPage === 1);
            $(nextPageId).prop('disabled', currentPage === totalPages);
        }
        
        // Event listeners for pagination
        $(paginationId).on('click', '.page-number', function() {
            currentPage = parseInt($(this).data('page'));
            showPage(currentPage);
        });
        
        $(prevPageId).click(function() {
            if (currentPage > 1) {
                currentPage--;
                showPage(currentPage);
            }
        });
        
        $(nextPageId).click(function() {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                showPage(currentPage);
            }
        });
        
        // Search functionality
        $(searchInputId).on('keyup', function() {
            const searchText = $(this).val().toLowerCase();
            
            filteredItems = $(tableId + ' tbody tr').filter(function() {
                return $(this).text().toLowerCase().includes(searchText);
            }).toArray();
            
            totalItems = filteredItems.length;
            currentPage = 1;
            
            showPage(currentPage);
        });
        
        // Initialize the first page
        showPage(1);
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
}); 