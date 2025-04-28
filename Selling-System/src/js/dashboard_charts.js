// Chart initialization and data handling
let salesChart = null;
let inventoryChart = null;

// Function to initialize charts
function initializeCharts() {
    // Initialize Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: window.chartMonths,
                datasets: [
                    {
                        label: 'فرۆشتن',
                        data: window.salesData,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'کڕین',
                        data: window.purchasesData,
                        borderColor: '#a8c7fa',
                        backgroundColor: 'rgba(168, 199, 250, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true
                    },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' د.ع';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' د.ع';
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize Inventory Chart
    const inventoryCtx = document.getElementById('inventoryChart');
    if (inventoryCtx) {
        inventoryChart = new Chart(inventoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['فرۆشتن', 'کڕین'],
                datasets: [{
                    data: [window.salesPercentage, window.purchasesPercentage],
                    backgroundColor: ['#0d6efd', '#a8c7fa'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }
}

// Function to handle chart type change
function changeChartType() {
    if (salesChart) {
        const newType = salesChart.config.type === 'line' ? 'bar' : 'line';
        salesChart.config.type = newType;
        salesChart.update();
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts when DOM is loaded
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }

    // Add event listener for chart type change button
    const changeChartTypeBtn = document.getElementById('changeChartType');
    if (changeChartTypeBtn) {
        changeChartTypeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            changeChartType();
        });
    }
});

// Export functions for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeCharts,
        changeChartType
    };
} 