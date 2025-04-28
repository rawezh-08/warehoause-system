// Initialize datepickers
$(document).ready(function() {
    // Initialize all date inputs with datepicker
    $('.date-input').each(function() {
        $(this).datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: '2000:2030'
        });
    });

    // Initialize all datetime inputs with datetimepicker
    $('.datetime-input').each(function() {
        $(this).datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss',
            changeMonth: true,
            changeYear: true,
            yearRange: '2000:2030'
        });
    });
}); 