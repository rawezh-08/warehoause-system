// Dashboard.js - JavaScript for dashboard functionality
// For ASHKAN Warehouse Management System

let currentChartType = 'line';
const chartTypes = ['line', 'bar', 'area'];

// Queue for deferred initialization
let initQueue = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard.js loaded, Chart availability:", typeof Chart !== 'undefined');
    
    // Check if Chart is defined
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Charts will not be initialized.');
        // Add to queue for later initialization
        initQueue.push(initCharts);
        initQueue.push(initChartTypeSwitch);
    } else {
        // Clear any existing chart instances
        window.salesChart = null;
        window.inventoryChart = null;
        
        // Initialize components
        setTimeout(function() {
            initCharts();
            initChartTypeSwitch();
        }, 100); // Small delay to ensure DOM is ready
    }
    
    // Initialize notification toggle directly
    initNotificationToggle();
});

// Function to be called if Chart.js is loaded dynamically
function runDeferredInit() {
    console.log("Running deferred initialization, queue length:", initQueue.length);
    while (initQueue.length > 0) {
        const fn = initQueue.shift();
        try {
            fn();
        } catch (e) {
            console.error("Error in deferred initialization:", e);
        }
    }
}

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
    try {
        console.log("Initializing charts...");
        // Initialize charts if elements exist
        if (document.getElementById('salesChart')) {
            initSalesChart();
        }
        
        if (document.getElementById('inventoryChart')) {
            initInventoryChart();
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

// Initialize Sales Chart
function initSalesChart() {
    try {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded. Sales chart will not be initialized.');
            return;
        }
        
        console.log("Initializing sales chart...");
        const salesChartElement = document.getElementById('salesChart');
        if (!salesChartElement) {
            console.error("Sales chart element not found");
            return;
        }
        
        const ctx = salesChartElement.getContext('2d');
        
        // Get data from PHP variables with validation
        const months = Array.isArray(window.chartMonths) ? window.chartMonths : [];
        const salesData = Array.isArray(window.salesData) ? window.salesData : [];
        const purchasesData = Array.isArray(window.purchasesData) ? window.purchasesData : [];
        
        console.log("Chart data:", {months, salesData, purchasesData});
        
        // Ensure we have at least some data
        if (months.length === 0) {
            // Create default data for demonstration
            for (let i = 5; i >= 0; i--) {
                const date = new Date();
                date.setMonth(date.getMonth() - i);
                months.push(date.toLocaleDateString('ku', { month: 'short', year: 'numeric' }));
            }
        }
        
        // Fill with zeros if the data arrays are empty
        const filledSalesData = salesData.length > 0 ? salesData : Array(months.length).fill(0);
        const filledPurchasesData = purchasesData.length > 0 ? purchasesData : Array(months.length).fill(0);
        
        // Calculate max value for better y-axis scaling
        const maxValue = Math.max(...filledSalesData, ...filledPurchasesData);
        const yAxisMax = maxValue > 0 ? Math.ceil(maxValue * 1.1 / 1000000) * 1000000 : 1000000;
        
        // Destroy existing chart if it exists
        if (window.salesChart && typeof window.salesChart.destroy === 'function') {
            try {
                window.salesChart.destroy();
                console.log("Existing sales chart destroyed");
            } catch (e) {
                console.error("Error destroying existing sales chart:", e);
            }
        }
        
        // Configure dataset based on chart type
        const datasets = [
            {
                label: 'فرۆشتن',
                data: filledSalesData,
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
                data: filledPurchasesData,
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
        
        console.log("Creating new sales chart...");
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
        console.log("Sales chart created successfully");
    } catch (error) {
        console.error('Error creating sales chart:', error);
        // Add a fallback message to the chart container
        const salesChartElement = document.getElementById('salesChart');
        if (salesChartElement) {
            const container = salesChartElement.parentNode;
            if (container) {
                container.innerHTML = '<div class="alert alert-warning text-center p-5">هەڵەیەک ڕوویدا لە دەرخستنی چارت. تکایە دواتر هەوڵبدەرەوە.</div>';
            }
        }
    }
}

// Initialize Inventory Chart
function initInventoryChart() {
    try {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded. Inventory chart will not be initialized.');
            return;
        }
        
        console.log("Initializing inventory chart...");
        const inventoryChartElement = document.getElementById('inventoryChart');
        if (!inventoryChartElement) {
            console.error("Inventory chart element not found");
            return;
        }
        
        const ctx = inventoryChartElement.getContext('2d');
        
        // Get data from PHP variables with validation
        const salesPercentage = isNaN(window.salesPercentage) ? 0 : window.salesPercentage;
        const purchasesPercentage = isNaN(window.purchasesPercentage) ? 0 : window.purchasesPercentage;
        
        // Ensure the percentages add up to 100%
        let validSalesPercentage = salesPercentage;
        let validPurchasesPercentage = purchasesPercentage;
        
        // If both values are 0, use a default split
        if (validSalesPercentage === 0 && validPurchasesPercentage === 0) {
            validSalesPercentage = 50;
            validPurchasesPercentage = 50;
        }
        
        // Destroy existing chart if it exists
        if (window.inventoryChart && typeof window.inventoryChart.destroy === 'function') {
            try {
                window.inventoryChart.destroy();
                console.log("Existing inventory chart destroyed");
            } catch (e) {
                console.error("Error destroying existing inventory chart:", e);
            }
        }
        
        console.log("Creating new inventory chart...");
        window.inventoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['فرۆشتن', 'کڕین'],
                datasets: [{
                    data: [validSalesPercentage, validPurchasesPercentage],
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
        console.log("Inventory chart created successfully");
    } catch (error) {
        console.error('Error creating inventory chart:', error);
        // Add a fallback message to the chart container
        const inventoryChartElement = document.getElementById('inventoryChart');
        if (inventoryChartElement) {
            const container = inventoryChartElement.parentNode;
            if (container) {
                container.innerHTML = '<div class="alert alert-warning text-center py-5">هەڵەیەک ڕوویدا لە دەرخستنی چارت. تکایە دواتر هەوڵبدەرەوە.</div>';
            }
        }
    }
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