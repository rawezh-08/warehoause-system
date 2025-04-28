// Sidebar functionality
$(document).ready(function() {
    // Toggle sidebar
    $('#sidebar-toggle').on('click', function() {
        $('.sidebar').toggleClass('collapsed');
        $('.main-content').toggleClass('expanded');
    });

    // Handle sidebar menu items
    $('.sidebar-menu-item').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('target');
        
        // Remove active class from all items
        $('.sidebar-menu-item').removeClass('active');
        
        // Add active class to clicked item
        $(this).addClass('active');
        
        // Load content if target is specified
        if (target) {
            loadContent(target);
        }
    });

    // Function to load content
    function loadContent(target) {
        $.ajax({
            url: target,
            method: 'GET',
            success: function(response) {
                $('#main-content').html(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading content:', error);
            }
        });
    }

    // Handle submenu toggles
    $('.submenu-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.menu-item').toggleClass('expanded');
        $(this).next('.submenu').slideToggle();
    });
}); 