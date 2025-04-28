// Navbar functionality
$(document).ready(function() {
    // Toggle mobile menu
    $('#mobile-menu-toggle').on('click', function() {
        $('.navbar-menu').toggleClass('is-active');
    });

    // Handle dropdown menus
    $('.has-dropdown').on('click', function(e) {
        e.preventDefault();
        $(this).toggleClass('is-active');
        $(this).find('.dropdown-menu').toggleClass('is-active');
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.has-dropdown').length) {
            $('.has-dropdown').removeClass('is-active');
            $('.dropdown-menu').removeClass('is-active');
        }
    });

    // Handle search functionality
    $('#search-form').on('submit', function(e) {
        e.preventDefault();
        const searchTerm = $('#search-input').val().trim();
        if (searchTerm) {
            window.location.href = '/search?q=' + encodeURIComponent(searchTerm);
        }
    });
}); 