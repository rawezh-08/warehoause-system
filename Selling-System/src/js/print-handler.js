/**
 * Print Handler for Customer Profile
 */
$(document).ready(function() {
    // Handle print button click for receipts
    $(document).on('click', '.print-btn', function() {
        const saleId = $(this).data('id');
        const isDelivery = $(this).data('is-delivery');
        
        if (isDelivery == 1) {
            // If it's a delivery receipt, open the delivery receipt page
            window.open(`../../Views/receipt/delivery_receipt.php?sale_id=${saleId}`, '_blank');
        } else {
            // If it's a regular receipt, open the regular receipt page
            window.open(`../../Views/receipt/print_receipt.php?sale_id=${saleId}`, '_blank');
        }
    });
}); 