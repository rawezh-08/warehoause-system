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
        // Table pagination and filtering
        const salesTable = $('#salesHistoryTable');
        const salesTableBody = salesTable.find('tbody');
        const salesRows = salesTableBody.find('tr');
        const salesItemsPerPage = 10;
        let salesCurrentPage = 1;
        const salesTotalItems = salesRows.length;
        const salesTotalPages = Math.ceil(salesTotalItems / salesItemsPerPage);

        // Initial pagination setup
        updateSalesPagination();
        showSalesPage(1);

        // Update pagination info and buttons
        function updateSalesPagination() {
            // Update pagination text
            const from = ((salesCurrentPage - 1) * salesItemsPerPage) + 1;
            const to = Math.min(salesCurrentPage * salesItemsPerPage, salesTotalItems);
            $('#salesFrom').text(from);
            $('#salesTo').text(to);
            $('#salesTotalItems').text(salesTotalItems);

            // Clear pagination
            const pagination = $('#salesPagination');
            pagination.empty();

            // Add page numbers
            const maxPagesToShow = 5;
            let startPage = Math.max(1, salesCurrentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(salesTotalPages, startPage + maxPagesToShow - 1);

            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageButton = $('<button class="btn btn-sm ' + (i === salesCurrentPage ? 'btn-primary' : 'btn-outline-secondary') + '">' + i + '</button>');
                pageButton.on('click', function () {
                    salesCurrentPage = i;
                    showSalesPage(i);
                    updateSalesPagination();
                });
                pagination.append(pageButton);
            }

            // Update prev/next buttons
            $('#salesPrevPage').prop('disabled', salesCurrentPage === 1);
            $('#salesNextPage').prop('disabled', salesCurrentPage === salesTotalPages || salesTotalPages === 0);
        }

        // Show specific page
        function showSalesPage(page) {
            salesRows.hide();
            salesRows.slice((page - 1) * salesItemsPerPage, page * salesItemsPerPage).show();
        }

        // Previous page button
        $('#salesPrevPage').on('click', function () {
            if (salesCurrentPage > 1) {
                salesCurrentPage--;
                showSalesPage(salesCurrentPage);
                updateSalesPagination();
            }
        });

        // Next page button
        $('#salesNextPage').on('click', function () {
            if (salesCurrentPage < salesTotalPages) {
                salesCurrentPage++;
                showSalesPage(salesCurrentPage);
                updateSalesPagination();
            }
        });

        // Search functionality
        $('#salesSearchInput').on('keyup', function () {
            const searchTerm = $(this).val().toLowerCase();
            let matchCount = 0;

            salesRows.each(function () {
                const rowText = $(this).text().toLowerCase();
                const showRow = rowText.indexOf(searchTerm) > -1;
                $(this).toggle(showRow);
                if (showRow) {
                    matchCount++;
                }
            });

            // Update pagination after search
            $('#salesTotalItems').text(matchCount);
            const newTotalPages = Math.ceil(matchCount / salesItemsPerPage);
            
            // Reset to first page on search
            salesCurrentPage = 1;
            showSalesPage(1);
            updateSalesPagination();
        });

        // Show invoice items when clicking the info button
        $('.show-invoice-items').on('click', function () {
            const invoiceNumber = $(this).data('invoice');
            
            $.ajax({
                url: '../../includes/get_invoice_items.php',
                type: 'POST',
                data: { invoice_number: invoiceNumber },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Create table with items
                        let itemsHtml = `
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>ناوی کاڵا</th>
                                            <th>بڕ</th>
                                            <th>یەکە</th>
                                            <th>نرخی تاک</th>
                                            <th>کۆی گشتی</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                        
                        if (response.items.length === 0) {
                            itemsHtml += `<tr><td colspan="6" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>`;
                        } else {
                            response.items.forEach((item, index) => {
                                itemsHtml += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity}</td>
                                        <td>${getUnitName(item.unit_type)}</td>
                                        <td>${formatCurrency(item.unit_price)}</td>
                                        <td>${formatCurrency(item.total_price)}</td>
                                    </tr>`;
                            });
                        }
                        
                        itemsHtml += `</tbody></table></div>`;
                        
                        // Show modal with items
                        Swal.fire({
                            title: `ناوەرۆکی پسووڵەی <strong dir="ltr">#${invoiceNumber}</strong>`,
                            html: itemsHtml,
                            width: '80%',
                            confirmButtonText: 'داخستن'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە ڕوویدا!',
                            text: response.message || 'نەتوانرا زانیاریەکان بهێنرێت، تکایە دووبارە هەوڵبدەوە.'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە ڕوویدا!',
                        text: 'کێشەیەک لە پەیوەندی کردن بە سێرڤەرەوە ڕوویدا، تکایە دواتر هەوڵبدەوە.'
                    });
                }
            });
        });

        // Helper function for formatting currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US').format(amount) + ' د.ع';
        }

        // Helper function to display unit type in Kurdish
        function getUnitName(unitType) {
            switch (unitType) {
                case 'piece':
                    return 'دانە';
                case 'box':
                    return 'کارتۆن';
                case 'set':
                    return 'سێت';
                default:
                    return unitType || '-';
            }
        }
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
    
    // View Sale Items
    $(document).on('click', '.view-sale-items', function(e) {
        e.preventDefault();
        console.log('View sale items button clicked');
        const saleId = $(this).data('sale-id');
        console.log('Sale ID:', saleId);
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕوان بە...',
            text: 'دەستکەوتنی زانیاری کاڵاکان',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Fetch sale items
        $.ajax({
            url: '../ajax/get_sale_items.php',
            type: 'POST',
            data: { sale_id: saleId },
            dataType: 'json',
            success: function(response) {
                console.log('AJAX response:', response);
                Swal.close();
                
                if (response.status === 'success') {
                    // Create table for items
                    let itemsHtml = `
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>ناوی کاڵا</th>
                                        <th>کۆدی کاڵا</th>
                                        <th>بڕ</th>
                                        <th>یەکە</th>
                                        <th>نرخی تاک</th>
                                        <th>کۆی گشتی</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    if (response.items.length === 0) {
                        itemsHtml += `<tr><td colspan="7" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>`;
                    } else {
                        response.items.forEach((item, index) => {
                            itemsHtml += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${item.product_name || '-'}</td>
                                    <td>${item.product_code || '-'}</td>
                                    <td>${item.quantity || '-'}</td>
                                    <td>${getUnitName(item.unit_type)}</td>
                                    <td>${formatCurrency(item.unit_price)}</td>
                                    <td>${formatCurrency(item.total_price)}</td>
                                </tr>`;
                        });
                    }
                    
                    itemsHtml += `</tbody></table></div>`;
                    
                    // Show modal with items
                    Swal.fire({
                        title: `کاڵاکانی پسووڵە - ${response.sale.invoice_number || ''}`,
                        html: itemsHtml,
                        width: '80%',
                        confirmButtonText: 'داخستن'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە ڕوویدا!',
                        text: response.message || 'نەتوانرا زانیاریەکان بهێنرێت، تکایە دووبارە هەوڵبدەوە.'
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە ڕوویدا!',
                    text: 'کێشەیەک لە پەیوەندی کردن بە سێرڤەرەوە ڕوویدا، تکایە دواتر هەوڵبدەوە.'
                });
            }
        });
    });
    
    // Print Receipt
    $(document).on('click', '.print-receipt-btn', function() {
        const receiptId = $(this).data('receipt-id');
        const receiptType = $(this).data('receipt-type');
        
        // Open the print page in a new tab
        window.open(`printReceipt.php?id=${receiptId}&type=${receiptType}`, '_blank');
    });
    
    // Helper function for formatting currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US').format(amount) + ' د.ع';
    }

    // Helper function to display unit type in Kurdish
    function getUnitName(unitType) {
        switch (unitType) {
            case 'piece':
                return 'دانە';
            case 'box':
                return 'کارتۆن';
            case 'set':
                return 'سێت';
            default:
                return unitType || '-';
        }
    }
}); 