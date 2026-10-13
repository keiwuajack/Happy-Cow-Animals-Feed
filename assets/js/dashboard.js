// Dashboard JavaScript with Charts

// Chart instances
let salesTrendChart, topProductsChart, categoryChart, revenueExpensesChart;

const dashboardChartText = '#263238';
const dashboardGridColor = 'rgba(38, 50, 56, 0.14)';

function dashboardChartOptions(options) {
    options.plugins = options.plugins || {};
    options.plugins.legend = options.plugins.legend || {};
    options.plugins.legend.labels = options.plugins.legend.labels || {};
    options.plugins.legend.labels.color = dashboardChartText;
    options.plugins.legend.labels.font = { weight: '600', size: 12 };
    options.scales = options.scales || {};

    Object.keys(options.scales).forEach(function(axis) {
        options.scales[axis].ticks = options.scales[axis].ticks || {};
        options.scales[axis].ticks.color = dashboardChartText;
        options.scales[axis].grid = options.scales[axis].grid || {};
        options.scales[axis].grid.color = dashboardGridColor;
    });

    return options;
}

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadRecentTransactions();
    loadCharts();
});

function loadDashboardStats() {
    fetch('api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('todaySales').textContent = formatCurrency(data.todaySales);
                document.getElementById('totalProducts').textContent = data.totalProducts;
                document.getElementById('totalCustomers').textContent = data.totalCustomers;
                document.getElementById('lowStockItems').textContent = data.lowStockItems;
                document.getElementById('creditOutstanding').textContent = formatCurrency(data.creditOutstanding || 0);
                document.getElementById('expiringProducts').textContent = data.expiringProducts || 0;

                const attentionItems = [];
                if (Number(data.lowStockItems) > 0) {
                    attentionItems.push(`${data.lowStockItems} product${data.lowStockItems == 1 ? '' : 's'} need${data.lowStockItems == 1 ? 's' : ''} reordering`);
                }
                if (Number(data.expiringProducts) > 0) {
                    attentionItems.push(`${data.expiringProducts} product${data.expiringProducts == 1 ? '' : 's'} expiring within 30 days`);
                }
                const attention = document.getElementById('dashboardAttention');
                if (attentionItems.length > 0) {
                    document.getElementById('dashboardAttentionText').textContent = attentionItems.join(' | ');
                    attention.hidden = false;
                }

                const attentionProducts = [];
                (data.lowStockProducts || []).forEach(product => {
                    attentionProducts.push(`<div class="attention-item"><i class="fas fa-boxes-stacked"></i><div><strong>${product.name}</strong><span>Only ${product.quantity} ${product.unit} left (reorder at ${product.reorder_level})</span></div><a href="products.php">View</a></div>`);
                });
                (data.expiringProductList || []).forEach(product => {
                    attentionProducts.push(`<div class="attention-item attention-item-expiry"><i class="fas fa-calendar-xmark"></i><div><strong>${product.name}</strong><span>${product.quantity} ${product.unit} expiring on ${product.expiry_date}</span></div><a href="products.php">View</a></div>`);
                });
                if (attentionProducts.length > 0) {
                    document.getElementById('attentionList').innerHTML = attentionProducts.join('');
                    document.getElementById('dashboardNeedsAttention').hidden = false;
                }
            }
        })
        .catch(error => console.error('Error loading dashboard stats:', error));
}

function loadRecentTransactions() {
    fetch('api/recent_transactions.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#recentTransactions tbody');
                tbody.innerHTML = '';
                
                if (data.transactions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No transactions yet</td></tr>';
                    return;
                }
                
                data.transactions.forEach(transaction => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${transaction.invoice_number}</td>
                        <td>${transaction.customer_name || 'Walk-in'}</td>
                        <td>${new Date(transaction.created_at).toLocaleDateString()}</td>
                        <td>${formatCurrency(transaction.total)}</td>
                        <td><span class="badge badge-${transaction.payment_status}">${transaction.payment_status}</span></td>
                    `;
                    tbody.appendChild(row);
                });
            }
        })
        .catch(error => console.error('Error loading recent transactions:', error));
}

function loadCharts() {
    loadSalesTrendChart();
    loadTopProductsChart();
    loadCategoryChart();
    loadRevenueExpensesChart();
}

function loadSalesTrendChart() {
    fetch('api/get_sales_trend.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('salesTrendChart').getContext('2d');
                
                // Destroy existing chart if it exists
                if (salesTrendChart) {
                    salesTrendChart.destroy();
                }
                
                salesTrendChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Daily Sales (KSh)',
                            data: data.values,
                            borderColor: '#2ecc71',
                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#2ecc71',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: dashboardChartOptions({
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Sales: KSh ' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'KSh ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    })
                });
            }
        })
        .catch(error => console.error('Error loading sales trend:', error));
}

function loadTopProductsChart() {
    fetch('api/get_top_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('topProductsChart').getContext('2d');
                
                if (topProductsChart) {
                    topProductsChart.destroy();
                }
                
                topProductsChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                '#2ecc71',
                                '#3498db',
                                '#f39c12',
                                '#e74c3c',
                                '#9b59b6'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: dashboardChartOptions({
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + ' units';
                                    }
                                }
                            }
                        }
                    })
                });
            }
        })
        .catch(error => console.error('Error loading top products:', error));
}

function loadCategoryChart() {
    fetch('api/get_category_sales.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('categoryChart').getContext('2d');
                
                if (categoryChart) {
                    categoryChart.destroy();
                }
                
                categoryChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Sales (KSh)',
                            data: data.values,
                            backgroundColor: '#3498db',
                            borderColor: '#2980b9',
                            borderWidth: 1,
                            borderRadius: 5
                        }]
                    },
                    options: dashboardChartOptions({
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Sales: KSh ' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'KSh ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    })
                });
            }
        })
        .catch(error => console.error('Error loading category sales:', error));
}

function loadRevenueExpensesChart() {
    fetch('api/get_revenue_expenses.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('revenueExpensesChart').getContext('2d');
                
                if (revenueExpensesChart) {
                    revenueExpensesChart.destroy();
                }
                
                revenueExpensesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: data.revenue,
                                backgroundColor: '#2ecc71',
                                borderColor: '#27ae60',
                                borderWidth: 1,
                                borderRadius: 5
                            },
                            {
                                label: 'Expenses',
                                data: data.expenses,
                                backgroundColor: '#e74c3c',
                                borderColor: '#c0392b',
                                borderWidth: 1,
                                borderRadius: 5
                            }
                        ]
                    },
                    options: dashboardChartOptions({
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': KSh ' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'KSh ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    })
                });
            }
        })
        .catch(error => console.error('Error loading revenue/expenses:', error));
}
