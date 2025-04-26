// Refresh session every 7 hours to prevent timeout
setInterval(function() {
    fetch('/api/refresh_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                window.location.href = '/index.php';
            }
        })
        .catch(() => {
            window.location.href = '/index.php';
        });
}, 25200000); // 7 hours in milliseconds

// Add event listeners for user activity
['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
    document.addEventListener(event, function() {
        fetch('/api/refresh_session.php');
    }, { passive: true });
}); 