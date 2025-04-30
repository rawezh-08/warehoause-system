$(document).ready(function() {
    // Wait for sidebar and navbar to be loaded before initializing tabs
    setTimeout(function() {
        initializeTabsAndTables();
    }, 500);
    
    function initializeTabsAndTables() {
        // Tab switching functionality
        $('#receiptTabs button').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    
        // Initialize all tables when their tabs are shown
        $('a[data-bs-toggle="tab"], button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
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
            initializeTable('#salesTable', '#salesSearchInput', '#salesPagination', 
                            '#salesPrevPage', '#salesNextPage', '#salesFrom', '#salesTo', '#salesTotalItems');
        }
    
        // Initialize the sales table on page load
        refreshSalesTable();
    }

    // Generic table initialization function
    function initializeTable(tableId, searchInputId, paginationId, prevPageId, nextPageId, fromId, toId, totalItemsId) {
        const itemsPerPage = 10;
        let currentPage = 1;
        let tableData = [];
        let originalData = [];
        
        // Get all table rows and store them in tableData
        $(tableId + ' tbody tr').each(function() {
            const clonedRow = $(this).clone();
            tableData.push(clonedRow);
            originalData.push(clonedRow);
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
                $(tableId + ' tbody').append(tableData[i].clone());
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
            if (totalPages <= 5) {
                // Show all page numbers if we have 5 or fewer pages
                for (let i = 1; i <= totalPages; i++) {
                    addPageButton(i, page);
                }
            } else {
                // Show a limited range of page numbers for more than 5 pages
                if (page <= 3) {
                    // We're near the start, show first 5 pages
                    for (let i = 1; i <= 5; i++) {
                        addPageButton(i, page);
                    }
                    $(paginationId).append('<span class="px-2">...</span>');
                    addPageButton(totalPages, page);
                } else if (page >= totalPages - 2) {
                    // We're near the end, show last 5 pages
                    addPageButton(1, page);
                    $(paginationId).append('<span class="px-2">...</span>');
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        addPageButton(i, page);
                    }
                } else {
                    // We're in the middle, show current page and neighbors
                    addPageButton(1, page);
                    $(paginationId).append('<span class="px-2">...</span>');
                    for (let i = page - 1; i <= page + 1; i++) {
                        addPageButton(i, page);
                    }
                    $(paginationId).append('<span class="px-2">...</span>');
                    addPageButton(totalPages, page);
                }
            }
            
            // Enable/disable prev/next buttons
            $(prevPageId).prop('disabled', page === 1);
            $(nextPageId).prop('disabled', page >= totalPages);
            
            // Update current page
            currentPage = page;
        }
        
        // Helper function to add page button
        function addPageButton(pageNumber, currentPage) {
            $(paginationId).append(`
                <button class="btn btn-sm ${pageNumber === currentPage ? 'btn-primary' : 'btn-outline-secondary'}" 
                        data-page="${pageNumber}">${pageNumber}</button>
            `);
        }
        
        // Event listeners for pagination
        $(document).on('click', `${paginationId} button`, function() {
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
            
            // If search term is empty, restore original data
            if (searchTerm.trim() === '') {
                tableData = originalData.map(row => row.clone());
            } else {
                // Filter data based on search term
                tableData = [];
                originalData.forEach(row => {
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
    
    // View Receipt Details 
    $(document).on('click', '.view-receipt-btn', function(e) {
        e.preventDefault();
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
    $(document).on('click', '.print-receipt-btn', function(e) {
        // Don't prevent default here since we want the link to open in a new tab
        const receiptId = $(this).data('receipt-id');
        const receiptType = $(this).data('receipt-type');
    });
}); 