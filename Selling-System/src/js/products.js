/**
 * Products page JavaScript
 */

// Global AJAX error handler
$(document).ajaxError(function(event, jqXHR, settings, errorThrown) {
    console.error('AJAX Error:', errorThrown || jqXHR.statusText);
    
    // Don't show the alert if it's just a page reload or navigation
    if (jqXHR.status !== 0 && errorThrown !== 'abort') {
        Swal.fire({
            title: 'هەڵە!',
            text: 'کێشەیەک لە پەیوەندیکردن بە سیستەمەوە ڕوویدا. تکایە دواتر هەوڵ بدەوە.',
            icon: 'error',
            confirmButtonText: 'باشە'
        });
    }
});

// Handle any unhandled promise rejections globally
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled Promise Rejection:', event.reason);
    
    Swal.fire({
        title: 'هەڵە!',
        text: 'هەندێک رووبەڕوو ناکرێت مەبەستەدینا',
        icon: 'error',
        confirmButtonText: 'باشە'
    });
});

// Handle global errors
window.onerror = function(message, source, lineno, colno, error) {
    console.error('Global error:', message, source, lineno, colno, error);
    
    // Avoid showing too many alerts for the same error
    if (!window.lastErrorTime || (Date.now() - window.lastErrorTime > 5000)) {
        window.lastErrorTime = Date.now();
        
        Swal.fire({
            title: 'هەڵە!',
            text: 'هەندێک رووبەڕوو ناکرێت مەبەستەدینا',
            icon: 'error',
            confirmButtonText: 'باشە'
        });
    }
    
    return true; // Prevent default error handler
};

// Improved component loading with error handling
function loadComponent(containerId, componentPath) {
    const container = document.getElementById(containerId);
    if (!container) return Promise.reject(new Error('Container not found: ' + containerId));
    
    return fetch(componentPath)
        .then(response => {
            if (!response.ok) {
                throw new Error(`پەیوەندی سەرکەوتوو نەبوو: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
            return html;
        })
        .catch(error => {
            console.error(`Error loading ${componentPath}:`, error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <strong>هەڵە:</strong> پێکهاتەکە نەتوانرا بارکرێت.
                    <button class="btn btn-sm btn-outline-danger ms-2" onclick="loadComponent('${containerId}', '${componentPath}')">
                        <i class="fas fa-sync-alt"></i> هەوڵدانەوە
                    </button>
                </div>
            `;
            throw error;
        });
}

// Function to handle product deletion with better error management
function deleteProduct(productId) {
    return fetch('../../process/deleteProduct.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + productId
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('کاڵای داواکراو نەدۆزرایەوە');
            } else if (response.status === 403) {
                throw new Error('مۆڵەتی سڕینەوەت نییە');
            } else {
                throw new Error('وەڵامی سێرڤەر سەرکەوتوو نەبوو: ' + response.status);
            }
        }
        
        return response.json().catch(() => {
            throw new Error('کێشەیەک هەیە لە وەرگرتنی داتاکان لە سێرڤەرەوە');
        });
    });
}

// Export functions
window.loadComponent = loadComponent;
window.deleteProduct = deleteProduct;