// AJAX Configuration
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    error: function(xhr, status, error) {
        console.error('AJAX Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی داواکاری',
            confirmButtonText: 'باشە'
        });
    }
});

// Global AJAX Loading Indicator
$(document).ajaxStart(function() {
    // Show loading indicator
    Swal.fire({
        title: 'تکایە چاوەڕوان بکە...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
});

$(document).ajaxStop(function() {
    // Hide loading indicator
    Swal.close();
});

// Global AJAX Success Handler
$(document).ajaxSuccess(function(event, xhr, settings) {
    // Check if the response contains a success message
    if (xhr.responseJSON && xhr.responseJSON.success) {
        Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو',
            text: xhr.responseJSON.message || 'ئەمەنجام درا',
            confirmButtonText: 'باشە'
        });
    }
});

// Global AJAX Error Handler
$(document).ajaxError(function(event, xhr, settings, error) {
    // Check if the response contains an error message
    if (xhr.responseJSON && xhr.responseJSON.error) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: xhr.responseJSON.message || 'هەڵەیەک ڕوویدا',
            confirmButtonText: 'باشە'
        });
    }
}); 