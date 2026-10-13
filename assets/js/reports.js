// Enhanced Reports with Multiple Report Types
let currentTab = 'sales';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Reports JS loaded');
    
    // Set default dates (this month)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    const dateFrom = document.getElementById('reportDateFrom');
    const dateTo = document.getElementById('reportDateTo');
    const generateBtn = document.getElementById('generateReport');
    const todayBtn = document.getElementById('todayBtn');
    const weekBtn = document.getElementById('weekBtn');
    const monthBtn = document.getElementById('monthBtn');
    const exportBtn = document.getElementById('exportPdfBtn');
    
    console.log('Elements found:', {dateFrom, dateTo, generateBtn, todayBtn, weekBtn, monthBtn, exportBtn});
    
    if (dateFrom) dateFrom.valueAsDate = firstDay;
    if (dateTo) dateTo.valueAsDate = today;
    
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Tab clicked:', this.dataset.tab);
            switchTab(this.dataset.tab);
        });
    });
    
    // Quick date filters
    if (todayBtn) {
        todayBtn.addEventListener('click', function(e) {
            console.log('Today button clicked');
            e.preventDefault();
            setDateRange('today');
        });
    }
    
    if (weekBtn) {
        weekBtn.addEventListener('click', function(e) {
            console.log('Week button clicked');
            e.preventDefault();
            setDateRange('week');
        });
    }
    
    if (monthBtn) {
        monthBtn.addEventListener('click', function(e) {
            console.log('Month button clicked');
            e.preventDefault();
            setDateRange('month');
        });
    }
    
    // Generate report
    if (generateBtn) {
        generateBtn.addEventListener('click', function(e) {
            console.log('Generate button clicked');
            e.preventDefault();
            generateReport();
        });
    }
    
    // Export PDF
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            console.log('Export PDF clicked');
            e.preventDefault();
            exportPDF();
        });
    }
    
    // Generate initial report
    console.log('Generating initial report');
    generateReport();
});

function switchTab(tab) {
    currentTab = tab;
    
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.report-tab').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tab}-tab`).classList.add('active');
    
    // Generate report for this tab
    generateReport();
}

function setDateRange(period) {
    const today = new Date();
    let startDate = new Date();
    
    switch(period) {
        case 'today':
            startDate = today;
            break;
        case 'week':
            startDate.setDate(today.getDate() - 7);
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            break;
    }
    
    document.getElementById('reportDateFrom').valueAsDate = startDate;
    document.getElementById('reportDateTo').valueAsDate = today;
    
    generateReport();
}

function generateReport() {
    const startDate = document.getElementById('reportDateFrom').value;
    const endDate = document.getElementById('reportDateTo').value;
    
    if (!startDate || !endDate) {
        showAlert('Please select date range', 'error');
        return;
    }
    
    switch(currentTab) {
        case 'sales':
            loadSalesReport(startDate, endDate);
            break;
        case 'best-sellers':
            loadBestSellersReport(startDate, endDate);
            break;
        case 'slow-moving':
            loadSlowMovingReport(startDate, endDate);
            break;
        case 'profit-loss':
            loadProfitLossReport(startDate, endDate);
            break;
        case 'customers':
            loadCustomersReport(startDate, endDate);
            break;
    }
}

function loadSalesReport(startDate, endDate) {
    fetch(`api/get_sales_report.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySalesSummary(data.summary);
                displaySalesTable(data.sales);
                displayDaily(data.daily || []);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displaySalesSummary(summary) {
    const stat = (color, icon, label, value) => `
        <div class="rpt-stat" style="--c:${color}">
            <div class="rpt-stat-icon"><i class="fas fa-${icon}"></i></div>
            <div><p>${label}</p><h2>${value}</h2></div>
        </div>`;
    document.getElementById('salesSummary').innerHTML =
        stat('#27ae60', 'money-bill-wave', 'Total revenue', formatCurrency(summary.total_revenue)) +
        stat('#3498db', 'shopping-cart', 'Total sales', summary.total_sales) +
        stat('#f39c12', 'chart-line', 'Average sale', formatCurrency(summary.average_sale)) +
        stat('#9b59b6', 'boxes-stacked', 'Items sold', summary.total_items) +
        stat('#16a085', 'sack-dollar', 'Total profit', formatCurrency(summary.total_profit || 0));
}

function emptyRow(cols, icon, text) {
    return `<tr><td colspan="${cols}" class="rpt-empty"><i class="fas fa-${icon}"></i>${text}</td></tr>`;
}

function displaySalesTable(sales) {
    const tbody = document.querySelector('#salesTable tbody');
    if (!sales.length) {
        tbody.innerHTML = emptyRow(10, 'receipt', 'No sales found for this period');
        return;
    }
    tbody.innerHTML = sales.map(sale => {
        const tone = sale.payment_status === 'paid' ? 'success' : sale.payment_status === 'partial' ? 'warning' : 'danger';
        return `<tr>
            <td><strong>${sale.invoice_number}</strong></td>
            <td>${formatDate(sale.created_at)}</td>
            <td>${sale.customer_name || 'Walk-in'}</td>
            <td>${sale.item_count}</td>
            <td class="num">${formatCurrency(sale.subtotal)}</td>
            <td class="num">${formatCurrency(sale.discount)}</td>
            <td class="num"><strong>${formatCurrency(sale.total)}</strong></td>
            <td class="num"><strong class="${sale.profit < 0 ? 'rpt-neg' : 'rpt-pos'}">${formatCurrency(sale.profit)}</strong></td>
            <td><span class="rpt-pill">${sale.payment_method}</span></td>
            <td><span class="rpt-pill ${tone}">${sale.payment_status}</span></td>
        </tr>`;
    }).join('');
}

function displayDaily(days) {
    const tbody = document.querySelector('#dailyTable tbody');
    if (!days.length) {
        tbody.innerHTML = emptyRow(5, 'calendar-day', 'No sales found for this period');
        return;
    }
    tbody.innerHTML = days.map(d => `<tr>
        <td><strong>${formatDate(d.date)}</strong></td>
        <td class="num">${d.sales}</td>
        <td class="num">${formatCurrency(d.revenue)}</td>
        <td class="num">${formatCurrency(d.cost)}</td>
        <td class="num"><strong class="${d.profit < 0 ? 'rpt-neg' : 'rpt-pos'}">${formatCurrency(d.profit)}</strong></td>
    </tr>`).join('');
}

function loadBestSellersReport(startDate, endDate) {
    fetch(`api/get_best_sellers.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBestSellers(data.products);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayBestSellers(products) {
    const tbody = document.querySelector('#bestSellersTable tbody');
    if (!products.length) {
        tbody.innerHTML = emptyRow(6, 'trophy', 'No products sold in this period');
        return;
    }
    tbody.innerHTML = products.map((product, index) => `<tr>
        <td><span class="rpt-rank ${index < 3 ? 'r' + (index + 1) : ''}">${index + 1}</span></td>
        <td><strong>${product.product_name}</strong></td>
        <td><span class="rpt-pill">${product.category_name}</span></td>
        <td class="num"><strong>${product.total_quantity}</strong> units</td>
        <td class="num"><strong>${formatCurrency(product.total_revenue)}</strong></td>
        <td class="num">${formatCurrency(product.avg_price)}</td>
    </tr>`).join('');
}

function loadSlowMovingReport(startDate, endDate) {
    fetch(`api/get_slow_moving.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySlowMoving(data.products);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displaySlowMoving(products) {
    const tbody = document.querySelector('#slowMovingTable tbody');
    if (!products.length) {
        tbody.innerHTML = emptyRow(6, 'circle-check', 'All products are selling well!');
        return;
    }
    tbody.innerHTML = products.map(product => {
        const days = product.last_sale_date ?
            Math.floor((new Date() - new Date(product.last_sale_date)) / 86400000) : null;
        const tone = days === null ? 'danger' : days > 60 ? 'warning' : 'muted';
        return `<tr>
            <td><strong>${product.name}</strong></td>
            <td><span class="rpt-pill">${product.category_name}</span></td>
            <td class="num"><span class="rpt-pill ${product.quantity < 10 ? 'danger' : 'warning'}">${product.quantity}</span></td>
            <td class="num">${product.total_sold || 0} units</td>
            <td>${product.last_sale_date ? formatDate(product.last_sale_date) : 'Never'}</td>
            <td><span class="rpt-pill ${tone}">${days === null ? 'Never sold' : days + ' days'}</span></td>
        </tr>`;
    }).join('');
}

function loadProfitLossReport(startDate, endDate) {
    fetch(`api/get_profit_loss.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProfitLoss(data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayProfitLoss(data) {
    const revenue = parseFloat(data.revenue) || 0;
    const expenses = parseFloat(data.expenses) || 0;
    const grossProfit = parseFloat(data.gross_profit) || 0;
    const profit = grossProfit - expenses;
    const margin = revenue > 0 ? ((profit / revenue) * 100).toFixed(1) : '0.0';
    const stat = (color, icon, label, value) => `
        <div class="rpt-stat" style="--c:${color}">
            <div class="rpt-stat-icon"><i class="fas fa-${icon}"></i></div>
            <div><p>${label}</p><h2>${value}</h2></div>
        </div>`;

    document.getElementById('profitLossSummary').innerHTML = `
        <div class="rpt-pl">
            <div class="rpt-pl-banner ${profit < 0 ? 'loss' : ''}">
                <p>Net ${profit >= 0 ? 'profit' : 'loss'}</p>
                <h2>${formatCurrency(Math.abs(profit))}</h2>
                <span>Profit margin ${margin}%</span>
                <div class="rpt-period">${formatDate(document.getElementById('reportDateFrom').value)} - ${formatDate(document.getElementById('reportDateTo').value)}</div>
            </div>
            ${stat('#27ae60', 'arrow-trend-up', 'Total revenue', formatCurrency(revenue))}
            ${stat('#3498db', 'boxes-stacked', 'Gross profit (sales - cost of goods)', formatCurrency(grossProfit))}
            ${stat('#e74c3c', 'arrow-trend-down', 'Total expenses', formatCurrency(expenses))}
        </div>
        <div class="rpt-card">
            <div class="rpt-card-head"><h3><i class="fas fa-list-check"></i> Breakdown</h3></div>
            <div class="rpt-breakdown">
                <div><p>Total sales</p><strong>${data.total_sales}</strong></div>
                <div><p>Average sale</p><strong>${formatCurrency(data.average_sale)}</strong></div>
                <div><p>Expense entries</p><strong>${data.expense_count}</strong></div>
                <div><p>Largest expense</p><strong>${formatCurrency(data.largest_expense)}</strong></div>
            </div>
        </div>`;
}

function loadCustomersReport(startDate, endDate) {
    fetch(`api/get_top_customers.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTopCustomers(data.customers);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayTopCustomers(customers) {
    const tbody = document.querySelector('#customersTable tbody');
    if (!customers.length) {
        tbody.innerHTML = emptyRow(6, 'users', 'No customer data for this period');
        return;
    }
    tbody.innerHTML = customers.map((customer, index) => `<tr>
        <td><span class="rpt-rank ${index < 3 ? 'r' + (index + 1) : ''}">${index + 1}</span></td>
        <td><strong>${customer.customer_name}</strong></td>
        <td>${customer.phone || '-'}</td>
        <td class="num">${customer.purchase_count}</td>
        <td class="num"><strong>${formatCurrency(customer.total_spent)}</strong></td>
        <td><span class="rpt-pill success">${customer.loyalty_points} pts</span></td>
    </tr>`).join('');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function exportPDF() {
    const element = document.querySelector('.report-tab.active');
    const dateFrom = document.getElementById('reportDateFrom').value;
    const dateTo = document.getElementById('reportDateTo').value;
    const reportType = currentTab.replace('-', ' ').toUpperCase();
    
    if (!element.innerHTML.trim() || element.innerHTML.includes('Generate report to view data')) {
        showAlert('Please generate a report first', 'error');
        return;
    }
    
    const pdfElement = document.createElement('div');
    pdfElement.style.padding = '20px';
    pdfElement.style.backgroundColor = 'white';
    
    pdfElement.innerHTML = `
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px;">
            <h1 style="margin: 0 0 5px 0; color: #333;">Happy Cow Animal Feeds</h1>
            <p style="margin: 0; color: #666;">Report Export</p>
        </div>
        
        <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
            <p style="margin: 5px 0;"><strong>Report Type:</strong> ${reportType}</p>
            <p style="margin: 5px 0;"><strong>Period:</strong> ${formatDate(dateFrom)} to ${formatDate(dateTo)}</p>
            <p style="margin: 5px 0;"><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
        </div>
        
        ${element.innerHTML}
    `;
    
    const opt = {
        margin: 10,
        filename: `${currentTab}_report_${new Date().toISOString().slice(0, 10)}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
    };
    
    html2pdf().set(opt).from(pdfElement).save().then(() => {
        showAlert('PDF exported successfully!', 'success');
    }).catch(error => {
        console.error('PDF export error:', error);
        showAlert('Error exporting PDF', 'error');
    });
}
