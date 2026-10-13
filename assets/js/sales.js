// Sales History JavaScript
let sales = [];
let currentSaleId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadSales();
    
    // Set default date range (last 30 days)
    const today = new Date();
    const lastMonth = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
    
    document.getElementById('dateTo').valueAsDate = today;
    document.getElementById('dateFrom').valueAsDate = lastMonth;
    
    // Event listeners
    document.getElementById('searchSale').addEventListener('input', filterSales);
    document.getElementById('filterPaymentStatus').addEventListener('change', filterSales);
    document.getElementById('filterSales').addEventListener('click', filterSales);
    
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
                displaySales(sales);
            }
        })
        .catch(error => console.error('Error loading sales:', error));
}

function displaySales(salesToDisplay) {
    const tbody = document.querySelector('#salesTable tbody');
    tbody.innerHTML = '';
    
    if (salesToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align: center;">No sales found</td></tr>';
        return;
    }
    
    salesToDisplay.forEach(sale => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${sale.invoice_number}</strong></td>
            <td>${new Date(sale.created_at).toLocaleString()}</td>
            <td>${sale.customer_name || 'Walk-in'}</td>
            <td>${sale.item_count}</td>
            <td>${formatCurrency(sale.subtotal)}</td>
            <td>${formatCurrency(sale.discount)}</td>
            <td>${formatCurrency(sale.tax)}</td>
            <td><strong>${formatCurrency(sale.total)}</strong></td>
            <td>${sale.payment_method.toUpperCase()}</td>
            <td><span class="badge badge-${sale.payment_status}">${sale.payment_status}</span></td>
            <td>
                <button class="btn-icon" onclick="viewSaleDetails(${sale.id})" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-icon" onclick="printReceipt(${sale.id})" title="Print Receipt">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn-icon" onclick="downloadReceipt(${sale.id})" title="Download Receipt">
                    <i class="fas fa-download"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function printReceipt(saleId) {
    window.open(`receipt.php?id=${saleId}`, '_blank');
}

function downloadReceipt(saleId) {
    window.location.href = `api/download_receipt.php?id=${saleId}`;
}

function filterSales() {
    const searchTerm = document.getElementById('searchSale').value.toLowerCase();
    const paymentStatus = document.getElementById('filterPaymentStatus').value;
    
    let filtered = sales;
    
    if (searchTerm) {
        filtered = filtered.filter(s => 
            s.invoice_number.toLowerCase().includes(searchTerm) || 
            (s.customer_name && s.customer_name.toLowerCase().includes(searchTerm))
        );
    }
    
    if (paymentStatus) {
        filtered = filtered.filter(s => s.payment_status === paymentStatus);
    }
    
    displaySales(filtered);
}

function viewSaleDetails(saleId) {
    currentSaleId = saleId;
    
    fetch(`api/get_sale_details.php?id=${saleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sale = data.sale;
                const items = data.items;
                
                let itemsHtml = '';
                items.forEach(item => {
                    itemsHtml += `
                        <tr>
                            <td>${item.product_name}</td>
                            <td>${item.quantity}</td>
                            <td>${formatCurrency(item.unit_price)}</td>
                            <td><strong>${formatCurrency(item.subtotal)}</strong></td>
                        </tr>
                    `;
                });
                
                const detailsHtml = `
                    <div class="sale-details-header">
                        <h4>Invoice: ${sale.invoice_number}</h4>
                        <p>Date: ${new Date(sale.created_at).toLocaleString()}</p>
                        <p>Customer: ${sale.customer_name || 'Walk-in Customer'}</p>
                        <p>Cashier: ${sale.user_name}</p>
                    </div>
                    
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                    
                    <div class="sale-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>${formatCurrency(sale.subtotal)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Discount:</span>
                            <span>${formatCurrency(sale.discount)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (16%):</span>
                            <span>${formatCurrency(sale.tax)}</span>
                        </div>
                        <div class="summary-row total">
                            <span><strong>Total:</strong></span>
                            <span><strong>${formatCurrency(sale.total)}</strong></span>
                        </div>
                        <div class="summary-row">
                            <span>Amount Paid:</span>
                            <span>${formatCurrency(sale.amount_paid)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Payment Method:</span>
                            <span>${sale.payment_method.toUpperCase()}</span>
                        </div>
                        ${sale.notes ? `<div class="summary-row"><span>Notes:</span><span>${sale.notes}</span></div>` : ''}
                    </div>
                `;
                
                document.getElementById('saleDetailsBody').innerHTML = detailsHtml;
                document.getElementById('saleDetailsModal').classList.add('show');
            }
        })
        .catch(error => console.error('Error loading sale details:', error));
}

function printReceipt(saleId) {
    window.open(`print_receipt.php?id=${saleId}`, '_blank');
}
