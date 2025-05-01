/**
 * Enhanced search functionality for receipt tables
 */
$(document).ready(function() {
    // Initialize search for all tabs with correct table IDs
    setupTableSearch('sales', 'salesHistoryTable');
    setupTableSearch('delivery', 'deliveryTable');
    setupTableSearch('drafts', 'draftsTable');
    setupTableSearch('returns', 'returnsTable');
    
    function setupTableSearch(tabId, tableId) {
        const searchInput = $(`#${tabId}SearchInput`);
        
        searchInput.on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            const tableRows = $(`#${tableId} tbody tr`);
            
            // Search in the table rows
            tableRows.each(function() {
                const rowText = $(this).text().toLowerCase();
                const match = rowText.indexOf(searchTerm) > -1;
                $(this).toggle(match);
            });
            
            // Trigger pagination update to reflect filtered rows
            $(`#${tabId}RecordsPerPage`).trigger('change');
        });
    }
}); 