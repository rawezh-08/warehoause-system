/**
 * Receipt filters functionality
 */
$(document).ready(function() {
    // Initialize Select2 for customer filter
    $('#customerFilter').select2({
        theme: 'bootstrap-5',
        width: '100%',
        ajax: {
            url: '../../api/search_customers.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term || '' };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        },
        placeholder: 'کڕیار هەڵبژێرە...',
        minimumInputLength: 0,
        allowClear: true,
        language: {
            inputTooShort: function() {
                return 'تکایە لانیکەم یەک پیت بنووسە...';
            },
            searching: function() {
                return 'گەڕان...';
            },
            noResults: function() {
                return 'هیچ ئەنجامێک نەدۆزرایەوە';
            }
        }
    });

    // Apply filters button click handler
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    // Reset filters button click handler
    $('#resetFilters').on('click', function() {
        resetFilters();
    });

    // Apply filters function
    function applyFilters() {
        const customerId = $('#customerFilter').val();
        const paymentType = $('#paymentTypeFilter').val();
        
        // Get the currently active tab
        const activeTabId = $('.nav-tabs .active').attr('id');
        let tableId;
        
        if (activeTabId === 'sales-tab') {
            tableId = 'salesHistoryTable';
        } else if (activeTabId === 'delivery-tab') {
            tableId = 'deliveryTable';
        } else if (activeTabId === 'drafts-tab') {
            tableId = 'draftsTable';
        } else if (activeTabId === 'returns-tab') {
            tableId = 'returnsTable';
        }
        
        // Filter the table rows
        $(`#${tableId} tbody tr`).each(function() {
            let showRow = true;
            const rowData = $(this).text().toLowerCase();
            
            // Apply customer filter if selected
            if (customerId) {
                const customerName = $('#customerFilter option:selected').text().toLowerCase();
                if (!rowData.includes(customerName)) {
                    showRow = false;
                }
            }
            
            // Apply payment type filter if selected
            if (paymentType && showRow) {
                if (paymentType === 'cash' && !rowData.includes('نەقد')) {
                    showRow = false;
                } else if (paymentType === 'debt' && !rowData.includes('قەرز')) {
                    showRow = false;
                }
            }
            
            $(this).toggle(showRow);
        });
        
        // Update pagination
        const tablePrefix = getTablePrefix(activeTabId);
        if (tablePrefix) {
            $(`#${tablePrefix}RecordsPerPage`).trigger('change');
        }
    }
    
    // Reset filters function
    function resetFilters() {
        $('#customerFilter').val(null).trigger('change');
        $('#paymentTypeFilter').val('').trigger('change');
        
        // Get the currently active tab
        const activeTabId = $('.nav-tabs .active').attr('id');
        let tableId;
        
        if (activeTabId === 'sales-tab') {
            tableId = 'salesHistoryTable';
        } else if (activeTabId === 'delivery-tab') {
            tableId = 'deliveryTable';
        } else if (activeTabId === 'drafts-tab') {
            tableId = 'draftsTable';
        } else if (activeTabId === 'returns-tab') {
            tableId = 'returnsTable';
        }
        
        // Show all rows
        $(`#${tableId} tbody tr`).show();
        
        // Update pagination
        const tablePrefix = getTablePrefix(activeTabId);
        if (tablePrefix) {
            $(`#${tablePrefix}RecordsPerPage`).trigger('change');
        }
    }
    
    // Helper function to get table prefix from tab ID
    function getTablePrefix(tabId) {
        if (tabId === 'sales-tab') return 'sales';
        if (tabId === 'delivery-tab') return 'delivery';
        if (tabId === 'drafts-tab') return 'drafts';
        if (tabId === 'returns-tab') return 'returns';
        return null;
    }
    
    // Filter when tab is changed
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        applyFilters();
    });
}); 