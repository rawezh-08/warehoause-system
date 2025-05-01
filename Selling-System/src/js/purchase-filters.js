/**
 * Purchase filters functionality
 */
$(document).ready(function() {
    // Initialize Select2 for supplier filter
    $('#supplierFilter').select2({
        theme: 'bootstrap-5',
        width: '100%',
        ajax: {
            url: '../../api/search_suppliers.php',
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
        placeholder: 'دابینکەر هەڵبژێرە...',
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
    }).on('change', function() {
        // Automatically apply filters when selection changes
        applyFilters();
    });

    // Apply filters automatically when payment type changes
    $('#paymentTypeFilter').on('change', function() {
        applyFilters();
    });

    // Reset filters button click handler
    $('#resetFilters').on('click', function() {
        resetFilters();
    });

    // Apply filters function
    function applyFilters() {
        const supplierId = $('#supplierFilter').val();
        const paymentType = $('#paymentTypeFilter').val();
        
        // Filter the table rows
        $('#purchasesTable tbody tr').each(function() {
            let showRow = true;
            const rowData = $(this).text().toLowerCase();
            
            // Apply supplier filter if selected
            if (supplierId) {
                const supplierName = $('#supplierFilter option:selected').text().toLowerCase();
                if (!rowData.includes(supplierName)) {
                    showRow = false;
                }
            }
            
            // Apply payment type filter if selected
            if (paymentType && showRow) {
                if (paymentType === 'cash' && !rowData.includes('نەقد')) {
                    showRow = false;
                } else if (paymentType === 'credit' && !rowData.includes('قەرز')) {
                    showRow = false;
                }
            }
            
            $(this).toggle(showRow);
        });
        
        // Update pagination
        $('#purchasesRecordsPerPage').trigger('change');
    }
    
    // Reset filters function
    function resetFilters() {
        $('#supplierFilter').val(null).trigger('change');
        $('#paymentTypeFilter').val('').trigger('change');
        
        // Show all rows
        $('#purchasesTable tbody tr').show();
        
        // Update pagination
        $('#purchasesRecordsPerPage').trigger('change');
    }
}); 