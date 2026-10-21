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
    const root = document.querySelector('.dashboard-modern');
    if (!root) return;

    const permissions = {
        cashier: root.dataset.isCashier === 'true',
        shifts: root.dataset.canViewShifts === 'true',
        inventory: root.dataset.canViewInventory === 'true',
        analytics: root.dataset.canViewAnalytics === 'true',
        sales: root.dataset.canViewSales === 'true'
    };

    loadDashboardStats(permissions);
    if (permissions.shifts) loadDashboardShift(permissions);
    if (permissions.sales) loadRecentTransactions();
    if (permissions.analytics && typeof Chart !== 'undefined') loadCharts();
});

function setDashboardText(id, value) {
    const element = document.getElementById(id);
    if (element) element.textContent = value;
}

function dashboardEscape(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(character) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character];
    });
}

function dashboardCurrency(value) {
    return typeof formatCurrency === 'function' ? formatCurrency(Number(value) || 0) : 'KSh ' + (Number(value) || 0).toFixed(2);
}

function loadDashboardStats(permissions) {
    fetch('api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;

            if (!permissions.cashier && data.todaySales !== undefined) {
                setDashboardText('todaySales', dashboardCurrency(data.todaySales));
            }
            if (data.totalProducts !== undefined) setDashboardText('totalProducts', Number(data.totalProducts).toLocaleString());
            if (data.totalCustomers !== undefined) setDashboardText('totalCustomers', Number(data.totalCustomers).toLocaleString());
            if (data.lowStockItems !== undefined) setDashboardText('lowStockItems', Number(data.lowStockItems).toLocaleString());
            if (data.creditOutstanding !== undefined) setDashboardText('creditOutstanding', dashboardCurrency(data.creditOutstanding));
            if (data.expiringProducts !== undefined) setDashboardText('expiringProducts', Number(data.expiringProducts).toLocaleString());

            if (!permissions.inventory) return;
            const attentionItems = [];
            if (Number(data.lowStockItems) > 0) {
                attentionItems.push(`${data.lowStockItems} product${Number(data.lowStockItems) === 1 ? '' : 's'} need${Number(data.lowStockItems) === 1 ? 's' : ''} reordering`);
            }
            if (Number(data.expiringProducts) > 0) {
                attentionItems.push(`${data.expiringProducts} product${Number(data.expiringProducts) === 1 ? '' : 's'} expiring within 30 days`);
            }
            const attention = document.getElementById('dashboardAttention');
            if (attention && attentionItems.length > 0) {
                setDashboardText('dashboardAttentionText', attentionItems.join(' · '));
                attention.hidden = false;
            }

            const attentionList = document.getElementById('attentionList');
            const attentionSection = document.getElementById('dashboardNeedsAttention');
            if (!attentionList || !attentionSection) return;

            const reviewUrl = document.querySelector('.dashboard-modern')?.dataset.inventoryReviewUrl || 'inventory-intelligence.php';
            const attentionProducts = [];
            (data.lowStockProducts || []).forEach(product => {
                attentionProducts.push(`<div class="attention-item"><i class="fas fa-boxes-stacked" aria-hidden="true"></i><div><strong>${dashboardEscape(product.name)}</strong><span>Only ${dashboardEscape(product.quantity)} ${dashboardEscape(product.unit)} left (reorder at ${dashboardEscape(product.reorder_level)})</span></div><a href="${dashboardEscape(reviewUrl)}">View</a></div>`);
            });
            (data.expiringProductList || []).forEach(product => {
                attentionProducts.push(`<div class="attention-item attention-item-expiry"><i class="fas fa-calendar-xmark" aria-hidden="true"></i><div><strong>${dashboardEscape(product.name)}</strong><span>${dashboardEscape(product.quantity)} ${dashboardEscape(product.unit)} expiring on ${dashboardEscape(product.expiry_date)}</span></div><a href="${dashboardEscape(reviewUrl)}">View</a></div>`);
            });
            if (attentionProducts.length > 0) {
                attentionList.innerHTML = attentionProducts.join('');
                attentionSection.hidden = false;
            }
        })
        .catch(error => console.error('Error loading dashboard stats:', error));
}

function loadDashboardShift(permissions) {
    const panel = document.getElementById('dashboardShiftOverview');
    const metrics = document.getElementById('dashboardShiftMetrics');
    if (!panel || !metrics) return;

    fetch('api/shift_status.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Could not load shift status');

            const shift = data.shift;
            const title = document.getElementById('dashboardShiftTitle');
            const message = document.getElementById('dashboardShiftMessage');
            const linkText = document.getElementById('dashboardShiftLinkText');
            panel.classList.toggle('is-active', Boolean(shift));
            panel.classList.toggle('is-empty', !shift);

            if (!shift) {
                if (title) title.textContent = 'No active shift';
                if (message) message.textContent = 'Open a shift and count your starting cash before the first sale.';
                if (linkText) linkText.textContent = 'Open a shift';
                metrics.innerHTML = [
                    ['Cash received', 0], ['M-Pesa received', 0], ['Other payments', 0], ['Total received', 0], ['Expected cash', null]
                ].map(([label, value]) => `<div class="dashboard-shift-metric"><span>${label}</span><strong>${value === null ? '—' : dashboardCurrency(value)}</strong></div>`).join('');
                if (permissions.cashier) setDashboardText('todaySales', dashboardCurrency(0));
                return;
            }

            if (title) title.textContent = 'Shift in progress';
            if (message) message.textContent = 'Opened ' + dashboardFormatDate(shift.opened_at) + '. Your totals update as payments are recorded.';
            if (linkText) linkText.textContent = 'Review or close shift';
            const shiftMetrics = [
                ['Cash received', shift.cash_sales],
                ['M-Pesa received', shift.mpesa_sales],
                ['Other payments', shift.card_sales],
                ['Total received', shift.total_sales],
                ['Expected cash', shift.expected_cash]
            ];
            metrics.innerHTML = shiftMetrics.map(([label, value]) => `<div class="dashboard-shift-metric"><span>${label}</span><strong>${dashboardCurrency(value)}</strong></div>`).join('');
            if (permissions.cashier) setDashboardText('todaySales', dashboardCurrency(shift.total_sales));
        })
        .catch(error => {
            panel.classList.add('is-error');
            const title = document.getElementById('dashboardShiftTitle');
            const message = document.getElementById('dashboardShiftMessage');
            if (title) title.textContent = 'Shift status unavailable';
            if (message) message.textContent = 'Open the cashier shift page to check your drawer.';
            console.error('Error loading cashier shift:', error);
        });
}

function dashboardFormatDate(value) {
    if (!value) return 'the recorded time';
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? 'the recorded time' : date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
}

function loadRecentTransactions() {
    fetch('api/recent_transactions.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#recentTransactions tbody');
                if (!tbody) return;
                tbody.innerHTML = '';
                
                if (data.transactions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No transactions yet</td></tr>';
                    return;
                }
                
                data.transactions.forEach(transaction => {
                    const row = document.createElement('tr');
                    const invoice = document.createElement('td');
                    invoice.textContent = transaction.invoice_number || '—';
                    const customer = document.createElement('td');
                    customer.textContent = transaction.customer_name || 'Walk-in';
                    const date = document.createElement('td');
                    date.textContent = dashboardFormatDate(transaction.created_at);
                    const amount = document.createElement('td');
                    amount.textContent = dashboardCurrency(transaction.total);
                    const statusCell = document.createElement('td');
                    const badge = document.createElement('span');
                    const status = String(transaction.payment_status || 'unknown').toLowerCase();
                    badge.className = 'badge badge-' + status.replace(/[^a-z0-9_-]/g, '');
                    badge.textContent = status;
                    statusCell.appendChild(badge);
                    [invoice, customer, date, amount, statusCell].forEach(cell => row.appendChild(cell));
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
