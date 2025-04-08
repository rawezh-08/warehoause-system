// Dashboard.js - JavaScript for dashboard functionality
// For ASHKAN Warehouse Management System

let currentChartType = 'line';
const chartTypes = ['line', 'bar', 'area'];

document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart is defined
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Charts will not be initialized.');
        // Try to load Chart.js dynamically
        const scriptElement = document.createElement('script');
        scriptElement.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        scriptElement.onload = function() {
            console.log('Chart.js loaded dynamically');
            initCharts();
            initChartTypeSwitch();
        };
        document.head.appendChild(scriptElement);
    } else {
        initCharts();
        initChartTypeSwitch();
    }
    
    // Initialize notification toggle directly here as a backup
    initNotificationToggle();
});

// Initialize chart type switching
function initChartTypeSwitch() {
    const chartTypeButton = document.getElementById('changeChartType');
    if (chartTypeButton) {
        chartTypeButton.addEventListener('click', function() {
            // Cycle through chart types
            const currentIndex = chartTypes.indexOf(currentChartType);
            const nextIndex = (currentIndex + 1) % chartTypes.length;
            currentChartType = chartTypes[nextIndex];
            
            // Reinitialize the sales chart with new type
            initSalesChart();
        });
    }
}

// Initialize all charts
function initCharts() {
    // Initialize charts if elements exist
    if (document.getElementById('salesChart')) {
        initSalesChart();
    }
    
    if (document.getElementById('inventoryChart')) {
        initInventoryChart();
    }
}

// Initialize Sales Chart
function initSalesChart() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Sales chart will not be initialized.');
        return;
    }
    
    const salesChartElement = document.getElementById('salesChart');
    if (!salesChartElement) return;
    
    const ctx = salesChartElement.getContext('2d');
    
    // Get data from PHP variables
    const months = window.chartMonths || [];
    const salesData = window.salesData || [];
    const purchasesData = window.purchasesData || [];
    
    // Calculate max value for better y-axis scaling
    const maxValue = Math.max(...salesData, ...purchasesData);
    const yAxisMax = Math.ceil(maxValue * 1.1 / 1000000) * 1000000;
    
    // Destroy existing chart if it exists
    if (window.salesChart) {
        window.salesChart.destroy();
    }
    
    // Configure dataset based on chart type
    const datasets = [
        {
            label: 'فرۆشتن',
            data: salesData,
            backgroundColor: currentChartType === 'line' ? 'rgba(13, 110, 253, 0.1)' : '#0d6efd',
            borderColor: '#0d6efd',
            borderWidth: 2,
            fill: currentChartType === 'area' ? true : false,
            tension: 0.4,
            pointBackgroundColor: '#0d6efd',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: currentChartType === 'line' ? 5 : 0,
            pointHoverRadius: currentChartType === 'line' ? 7 : 0
        },
        {
            label: 'کڕین',
            data: purchasesData,
            backgroundColor: currentChartType === 'line' ? 'rgba(168, 199, 250, 0.1)' : '#a8c7fa',
            borderColor: '#a8c7fa',
            borderWidth: 2,
            fill: currentChartType === 'area' ? true : false,
            tension: 0.4,
            pointBackgroundColor: '#a8c7fa',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: currentChartType === 'line' ? 5 : 0,
            pointHoverRadius: currentChartType === 'line' ? 7 : 0
        }
    ];
    
    window.salesChart = new Chart(ctx, {
        type: currentChartType === 'area' ? 'line' : currentChartType,
        data: {
            labels: months,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: yAxisMax,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + ' ملیۆن د.ع';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(1) + ' هەزار د.ع';
                            }
                            return value + ' د.ع';
                        },
                        padding: 10
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        padding: 10
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#000',
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyColor: '#666',
                    bodyFont: {
                        size: 13
                    },
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            var value = context.parsed.y || 0;
                            if (value >= 1000000) {
                                return label + ': ' + (value / 1000000).toFixed(1) + ' ملیۆن د.ع';
                            } else if (value >= 1000) {
                                return label + ': ' + (value / 1000).toFixed(1) + ' هەزار د.ع';
                            }
                            return label + ': ' + value + ' د.ع';
                        }
                    }
                }
            }
        }
    });
}

// Initialize Inventory Chart
function initInventoryChart() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Inventory chart will not be initialized.');
        return;
    }
    
    const inventoryChartElement = document.getElementById('inventoryChart');
    if (!inventoryChartElement) return;
    
    const ctx = inventoryChartElement.getContext('2d');
    
    // Get data from PHP variables
    const salesPercentage = window.salesPercentage || 0;
    const purchasesPercentage = window.purchasesPercentage || 0;
    
    // Destroy existing chart if it exists
    if (window.inventoryChart) {
        window.inventoryChart.destroy();
    }
    
    window.inventoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['فرۆشتن', 'کڕین'],
            datasets: [{
                data: [salesPercentage, purchasesPercentage],
                backgroundColor: ['#0d6efd', '#a8c7fa'],
                borderWidth: 0,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
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

// Initialize notification toggle functionality
function initNotificationToggle() {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationPanel = document.querySelector('.notification-panel');
    const closeButton = document.querySelector('.btn-close-panel');
    
    if (notificationToggle && notificationPanel) {
        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            notificationPanel.classList.toggle('show');
        });
    }
    
    if (closeButton && notificationPanel) {
        closeButton.addEventListener('click', function() {
            notificationPanel.classList.remove('show');
        });
    }
    
    // Close notification panel when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationPanel && notificationPanel.classList.contains('show') &&
            !notificationPanel.contains(e.target) &&
            e.target !== notificationToggle &&
            !notificationToggle.contains(e.target)) {
            notificationPanel.classList.remove('show');
        }
    });
}