$(document).ready(function() {
    // Debug logging for AJAX requests
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.url.includes('api/search_products.php')) {
            console.log('AJAX Success: ' + settings.url, xhr.responseJSON);
        }
        if (settings.url.includes('api/search_customers.php')) {
            console.log('AJAX Success: ' + settings.url, xhr.responseJSON);
        }
        if (settings.url.includes('api/search_suppliers.php')) {
            console.log('AJAX Success: ' + settings.url, xhr.responseJSON);
        }
    });

    $(document).ajaxError(function(event, xhr, settings) {
        console.log('AJAX Error: ' + settings.url, 'error', xhr.responseText);
        try {
            console.log('Response JSON:', JSON.parse(xhr.responseText));
        } catch (e) {
            console.log('Could not parse response as JSON');
        }
    });
}); 