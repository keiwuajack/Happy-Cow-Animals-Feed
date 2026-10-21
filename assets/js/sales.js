// Sales History JavaScript
let sales = [];
let currentSaleId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Set default date range (last 30 days)
    const today = new Date();
    const lastMonth = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
    
    document.getElementById('dateTo').value = formatSalesDate(today);
    document.getElementById('dateFrom').value = formatSalesDate(lastMonth);
    
    // Event listeners
    document.getElementById('searchSale').addEventListener('input', filterSales);
    document.getElementById('filterPaymentStatus').addEventListener('change', filterSales);
    document.getElementById('filterSales').addEventListener('click', loadSales);
    loadSales();
    
    // Modal controls
    const modal = document.getElementById('saleDetailsModal');
    const closeBtn = modal.querySelector('.close');
    const closeDetailsBtn = document.getElementById('closeSaleDetails');
    const printBtn = document.getElementById('printSaleReceipt');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    closeDetailsBtn.onclick = () => modal.classList.remove('show');
    printBtn.onclick = () => {
        if (currentSaleId) {
            window.open(`print_receipt.php?id=${currentSaleId}`, '_blank');
        }
    };
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function loadSales() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    let url = 'api/get_sales.php';
    if (dateFrom && dateTo) {
        url += `?from=${dateFrom}&to=${dateTo}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                sales = data.sales;
                filterSales();
            }
        })
        .catch(error => console.error('Error loading sales:', error));
}

function displaySales(salesToDisplay) {
    const tbody = document.querySelector('#salesTable tbody');
    tbody.innerHTML = '';
    
    if (salesToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="sales-empty-cell"><i class="fas fa-receipt" aria-hidden="true"></i><strong>No sales found</strong><span>Try changing the date range or search filters.</span></td></tr>';
        return;
    }
    
    salesToDisplay.forEach(sale => {
        const row = document.createElement('tr');
        const paymentStatus = ['paid', 'partial', 'unpaid'].includes(String(sale.payment_status).toLowerCase())
            ? String(sale.payment_status).toLowerCase()
            : 'unpaid';
        const paymentMethod = String(sale.payment_method || 'unknown').toUpperCase();
        const saleDate = new Date(sale.created_at).toLocaleString();
        row.innerHTML = `
            <td><strong>${escapeSalesHtml(sale.invoice_number)}</strong></td>
            <td>${escapeSalesHtml(saleDate)}</td>
            <td>${escapeSalesHtml(sale.customer_name || 'Walk-in')}</td>
            <td>${Number(sale.item_count) || 0}</td>
            <td>${formatCurrency(sale.subtotal)}</td>
            <td>${formatCurrency(sale.discount)}</td>
            <td>${formatCurrency(sale.tax)}</td>
            <td><strong>${formatCurrency(sale.total)}</strong></td>
            <td>${escapeSalesHtml(paymentMethod)}</td>
            <td><span class="badge badge-${paymentStatus}">${escapeSalesHtml(paymentStatus)}</span></td>
            <td>
                <button class="btn-icon" onclick="viewSaleDetails(${Number(sale.id) || 0})" title="View details" aria-label="View sale details">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
                <button class="btn-icon" onclick="printReceipt(${Number(sale.id) || 0})" title="Print receipt" aria-label="Print receipt">
                    <i class="fas fa-print" aria-hidden="true"></i>
                </button>
                <button class="btn-icon" onclick="downloadReceipt(${Number(sale.id) || 0})" title="Download receipt" aria-label="Download receipt">
                    <i class="fas fa-download" aria-hidden="true"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function downloadReceipt(saleId) {
    window.location.href = `api/download_receipt.php?id=${saleId}`;
}

function filterSales() {
    const searchTerm = document.getElementById('searchSale').value.trim().toLowerCase();
    const paymentStatus = document.getElementById('filterPaymentStatus').value;
    
    let filtered = sales;
    
    if (searchTerm) {
        filtered = filtered.filter(s =>
            String(s.invoice_number || '').toLowerCase().includes(searchTerm) ||
            String(s.customer_name || '').toLowerCase().includes(searchTerm)
        );
    }
    
    if (paymentStatus) {
        filtered = filtered.filter(s => String(s.payment_status || '').toLowerCase() === paymentStatus);
    }
    
    displaySales(filtered);
}

function viewSaleDetails(saleId) {
    currentSaleId = saleId;
    
    fetch(`api/get_sale_details.php?id=${saleId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.sale) throw new Error(data.message || 'Sale details could not be loaded');
            const sale = data.sale;
            const items = Array.isArray(data.items) ? data.items : [];
            const status = ['paid', 'partial', 'unpaid'].includes(String(sale.payment_status).toLowerCase())
                ? String(sale.payment_status).toLowerCase()
                : 'unpaid';
            const paid = Number(sale.amount_paid) || 0;
            const total = Number(sale.total) || 0;
            const balance = Math.max(total - paid, 0);
            const itemQuantity = items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
            const itemsHtml = items.map(item => `
                <tr>
                    <td><strong>${escapeSalesHtml(item.product_name || 'Product')}</strong></td>
                    <td>${Number(item.quantity) || 0}</td>
                    <td>${formatCurrency(item.unit_price)}</td>
                    <td><strong>${formatCurrency(item.subtotal)}</strong></td>
                </tr>
            `).join('');
            const detailsHtml = `
                <div class="sale-detail-overview">
                    <div class="sale-detail-invoice">
                        <span class="sale-detail-label">Invoice number</span>
                        <h3>${escapeSalesHtml(sale.invoice_number)}</h3>
                        <p><i class="fas fa-calendar-day" aria-hidden="true"></i> ${escapeSalesHtml(new Date(sale.created_at).toLocaleString())}</p>
                    </div>
                    <div class="sale-detail-total">
                        <span class="sale-detail-label">Sale total</span>
                        <strong>${formatCurrency(total)}</strong>
                        <span class="sale-detail-status ${status}">${escapeSalesHtml(status)}</span>
                    </div>
                </div>
                <div class="sale-detail-meta">
                    <div class="sale-detail-meta-card"><span class="sale-detail-meta-icon"><i class="fas fa-user" aria-hidden="true"></i></span><div class="sale-detail-meta-copy"><span>Customer</span><strong>${escapeSalesHtml(sale.customer_name || 'Walk-in customer')}</strong></div></div>
                    <div class="sale-detail-meta-card"><span class="sale-detail-meta-icon"><i class="fas fa-cash-register" aria-hidden="true"></i></span><div class="sale-detail-meta-copy"><span>Cashier</span><strong>${escapeSalesHtml(sale.user_name || 'Not recorded')}</strong></div></div>
                    <div class="sale-detail-meta-card"><span class="sale-detail-meta-icon"><i class="fas fa-credit-card" aria-hidden="true"></i></span><div class="sale-detail-meta-copy"><span>Payment method</span><strong>${escapeSalesHtml(String(sale.payment_method || 'Unknown').toUpperCase())}</strong></div></div>
                </div>
                <section class="sale-detail-items">
                    <div class="sale-detail-section-heading"><h3>Items sold</h3><span class="sale-detail-item-count">${items.length} line${items.length === 1 ? '' : 's'} / ${itemQuantity} item${itemQuantity === 1 ? '' : 's'}</span></div>
                    <div class="table-responsive"><table class="sale-detail-items-table"><thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Subtotal</th></tr></thead><tbody>${itemsHtml || '<tr><td colspan="4">No items recorded</td></tr>'}</tbody></table></div>
                </section>
                <div class="sale-detail-bottom">
                    <section class="sale-detail-summary">
                        <h3 class="sale-detail-card-heading"><i class="fas fa-calculator" aria-hidden="true"></i> Amount summary</h3>
                        <div class="sale-detail-row"><span>Subtotal</span><strong>${formatCurrency(sale.subtotal)}</strong></div>
                        ${Number(sale.discount) > 0 ? `<div class="sale-detail-row"><span>Discount</span><strong>- ${formatCurrency(sale.discount)}</strong></div>` : ''}
                        ${Number(sale.tax) > 0 ? `<div class="sale-detail-row"><span>Tax</span><strong>${formatCurrency(sale.tax)}</strong></div>` : ''}
                        <div class="sale-detail-row total"><span>Total</span><strong>${formatCurrency(total)}</strong></div>
                    </section>
                    <section class="sale-detail-payment">
                        <h3 class="sale-detail-card-heading"><i class="fas fa-wallet" aria-hidden="true"></i> Payment</h3>
                        <div class="sale-detail-row"><span>Amount received</span><strong>${formatCurrency(paid)}</strong></div>
                        ${balance > 0 ? `<div class="sale-detail-row"><span>Balance due</span><strong>${formatCurrency(balance)}</strong></div>` : `<div class="sale-detail-row"><span>Change</span><strong>${formatCurrency(Math.max(paid - total, 0))}</strong></div>`}
                        ${sale.notes ? `<div class="sale-detail-note"><strong>Note:</strong> ${escapeSalesHtml(sale.notes)}</div>` : ''}
                    </section>
                </div>
            `;
            document.getElementById('saleDetailsBody').innerHTML = detailsHtml;
            document.getElementById('saleDetailsModal').classList.add('show');
        })
        .catch(error => showAlert(error.message || 'Error loading sale details', 'error'));
}

function printReceipt(saleId) {
    window.open(`print_receipt.php?id=${saleId}`, '_blank');
}

function escapeSalesHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));
}

function formatSalesDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
