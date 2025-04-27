// Function to handle period filter changes
function handlePeriodFilter(period) {
    // Add active class to clicked button and remove from others
    document.querySelectorAll('.filter-buttons .btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
    });
    document.querySelector(`[data-period="${period}"]`).classList.remove('btn-outline-primary');
    document.querySelector(`[data-period="${period}"]`).classList.add('btn-primary');

    // Make AJAX request to get filtered data
    fetch(`../../process/dashboard_ajax.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update KPI values
            updateDashboardData(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Function to update dashboard data
function updateDashboardData(data) {
    // Update all KPI values
    if (data.cashSales !== undefined) {
        document.getElementById('cashSales').textContent = formatCurrency(data.cashSales);
    }
    if (data.creditSales !== undefined) {
        document.getElementById('creditSales').textContent = formatCurrency(data.creditSales);
    }
    if (data.totalPurchases !== undefined) {
        document.getElementById('totalPurchases').textContent = formatCurrency(data.totalPurchases);
    }
    if (data.totalExpenses !== undefined) {
        document.getElementById('totalExpenses').textContent = formatCurrency(data.totalExpenses);
    }
    // Add more KPI updates as needed
}

// Helper function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-IQ', {
        style: 'currency',
        currency: 'IQD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Initialize event listeners when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to filter buttons
    document.querySelectorAll('.filter-buttons .btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.getAttribute('data-period');
            handlePeriodFilter(period);
        });
    });
}); 