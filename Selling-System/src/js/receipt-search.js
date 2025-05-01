/**
 * Enhanced search functionality for receipt tables
 */
$(document).ready(function() {
    // Initialize search for all tabs
    setupTableSearch('sales');
    setupTableSearch('delivery');
    setupTableSearch('drafts');
    setupTableSearch('returns');
    
    function setupTableSearch(tableId) {
        const searchInput = $(`#${tableId}SearchInput`);
        
        searchInput.on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            const tableRows = $(`#${tableId}Table tbody tr`);
            
            // Search in the table rows
            tableRows.each(function() {
                const rowText = $(this).text().toLowerCase();
                const match = rowText.indexOf(searchTerm) > -1;
                $(this).toggle(match);
            });
            
            // Trigger pagination update to reflect filtered rows
            $(`#${tableId}RecordsPerPage`).trigger('change');
        });
    }
}); 