// Returns & Refunds Management

let selectedSale = null;
let returnItems = [];
let searchTimeout = null;
let searchRequestId = 0;

document.addEventListener('DOMContentLoaded', function() {
    loadReturns();
    loadStats();
    
    document.getElementById('newReturnBtn').addEventListener('click', openReturnModal);
    document.getElementById('returnForm').addEventListener('submit', processReturn);
    document.getElementById('statusFilter').addEventListener('change', loadReturns);
    document.getElementById('invoiceSearch').addEventListener('input', searchInvoice);
    
    document.querySelector('#returnModal .close').addEventListener('click', closeReturnModal);
    document.getElementById('returnModal').addEventListener('click', function(event) {
        if (event.target === this) closeReturnModal();
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.getElementById('returnModal').classList.contains('show')) {
            closeReturnModal();
        }
    });
});

function loadReturns() {
    const status = document.getElementById('statusFilter').value;
    const url = status ? `api/get_returns.php?status=${status}` : 'api/get_returns.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReturns(data.returns);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayReturns(returns) {
    const tbody = document.querySelector('#returnsTable tbody');
    
    if (returns.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="returns-table-empty">No returns found</td></tr>';
        return;
    }
    
    tbody.innerHTML = returns.map(ret => {
        const returnId = Number(ret.id) || 0;
        const status = String(ret.status || '').toLowerCase();
        const safeStatus = escapeHtml(status.toUpperCase());
        const actions = status === 'pending' && canManageReturns ? `
            <button class="btn btn-sm btn-primary" onclick="approveReturn(${returnId})" title="Approve" aria-label="Approve return">
                <i class="fas fa-check"></i>
            </button>
            <button class="btn btn-sm returns-reject-button" onclick="rejectReturn(${returnId})" title="Reject" aria-label="Reject return">
                <i class="fas fa-times"></i>
            </button>
        ` : '';

        return `
        <tr>
            <td><strong>${escapeHtml(ret.return_number || '')}</strong></td>
            <td>${escapeHtml(ret.invoice_number || '')}</td>
            <td>${escapeHtml(ret.customer_name || 'Walk-in')}</td>
            <td>${formatDate(ret.created_at)}</td>
            <td><strong>KSh ${formatMoney(ret.total_refund)}</strong></td>
            <td><span class="badge returns-method-badge">${escapeHtml(String(ret.refund_method || '').toUpperCase())}</span></td>
            <td><span class="badge badge-${getStatusColor(status)}">${safeStatus}</span></td>
            <td class="returns-actions-cell">
                <button class="btn btn-sm returns-view-button" onclick="viewReturn(${returnId})" title="View" aria-label="View return">
                    <i class="fas fa-eye"></i>
                </button>
                ${actions}
            </td>
        </tr>
    `;
    }).join('');
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[character]));
}

function formatMoney(value) {
    const amount = Number.parseFloat(value);
    return (Number.isFinite(amount) ? amount : 0).toFixed(2);
}

function formatDate(value) {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString();
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger'
    };
    return colors[status] || 'secondary';
}

function loadStats() {
    fetch('api/get_return_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalReturns').textContent = data.stats.total || 0;
                document.getElementById('pendingReturns').textContent = data.stats.pending || 0;
                document.getElementById('approvedReturns').textContent = data.stats.approved || 0;
                document.getElementById('refundValue').textContent = 'KSh ' + parseFloat(data.stats.total_refund || 0).toFixed(2);
            }
        });
}

function openReturnModal() {
    selectedSale = null;
    returnItems = [];
    searchRequestId++;
    clearTimeout(searchTimeout);
    document.getElementById('returnForm').reset();
    document.getElementById('saleDetails').hidden = true;
    document.getElementById('itemsSection').hidden = true;
    document.getElementById('returnDetailsSection').hidden = true;
    document.getElementById('submitReturnBtn').hidden = true;
    document.getElementById('submitReturnBtn').disabled = true;
    document.getElementById('returnItems').innerHTML = '';
    document.getElementById('returnItemCount').textContent = '';
    document.getElementById('totalRefund').textContent = 'KSh 0.00';
    setInvoiceSearchStatus('Enter at least 3 characters to search.');
    const modal = document.getElementById('returnModal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(() => {
        document.getElementById('invoiceSearch').focus();
    });
}

function closeReturnModal() {
    searchRequestId++;
    clearTimeout(searchTimeout);
    const modal = document.getElementById('returnModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function searchInvoice(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 3) {
        searchRequestId++;
        selectedSale = null;
        returnItems = [];
        document.getElementById('saleDetails').hidden = true;
        document.getElementById('itemsSection').hidden = true;
        document.getElementById('returnDetailsSection').hidden = true;
        document.getElementById('submitReturnBtn').hidden = true;
        document.getElementById('submitReturnBtn').disabled = true;
        document.getElementById('returnItems').innerHTML = '';
        document.getElementById('returnItemCount').textContent = '';
        document.getElementById('totalRefund').textContent = 'KSh 0.00';
        setInvoiceSearchStatus('Enter at least 3 characters to search.');
        return;
    }

    selectedSale = null;
    returnItems = [];
    document.getElementById('saleDetails').hidden = true;
    document.getElementById('itemsSection').hidden = true;
    document.getElementById('returnDetailsSection').hidden = true;
    document.getElementById('submitReturnBtn').hidden = true;
    document.getElementById('submitReturnBtn').disabled = true;
    setInvoiceSearchStatus('Searching for invoice…', 'loading');
    const requestId = ++searchRequestId;
    
    searchTimeout = setTimeout(() => {
        fetch(`api/search_sale.php?invoice=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('Invoice search failed');
                return response.json();
            })
            .then(data => {
                if (requestId !== searchRequestId) return;
                if (data.success && data.sale) {
                    displaySaleDetails(data.sale, data.items);
                } else {
                    setInvoiceSearchStatus('No sale found for that invoice number. Check the number and try again.', 'error');
                }
            })
            .catch(error => {
                if (requestId !== searchRequestId) return;
                console.error('Invoice search error:', error);
                setInvoiceSearchStatus('Could not search invoices. Check the local server and try again.', 'error');
            });
    }, 350);
}

function setInvoiceSearchStatus(message, state = '') {
    const status = document.getElementById('invoiceSearchStatus');
    status.textContent = message;
    status.classList.toggle('is-error', state === 'error');
    status.classList.toggle('is-loading', state === 'loading');
}

function displaySaleDetails(sale, items) {
    selectedSale = sale;
    items = Array.isArray(items) ? items : [];
    
    document.getElementById('saleInvoice').textContent = sale.invoice_number || '—';
    document.getElementById('saleDate').textContent = formatDate(sale.created_at);
    document.getElementById('saleCustomer').textContent = sale.customer_name || 'Walk-in Customer';
    document.getElementById('saleTotal').textContent = 'KSh ' + formatMoney(sale.grand_total);
    
    document.getElementById('saleDetails').hidden = false;
    setInvoiceSearchStatus(items.length ? 'Invoice found. Select the items being returned.' : 'Invoice found, but it has no returnable items.', items.length ? '' : 'error');
    
    // Display items for return
    const tbody = document.getElementById('returnItems');
    tbody.innerHTML = items.length ? items.map((item, index) => `
        <tr>
            <td>
                <input type="checkbox" class="return-item-check" data-index="${index}" aria-label="Select ${escapeHtml(item.product_name || 'item')} to return"
                       onchange="toggleReturnItem(${index}, this.checked)">
            </td>
            <td>${escapeHtml(item.product_name || 'Product')}</td>
            <td>${Number(item.quantity) || 0}</td>
            <td>
                <input type="number" id="returnQty_${index}" class="form-control returns-quantity-input"
                       value="${Number(item.quantity) || 1}" min="1" max="${Number(item.quantity) || 1}"
                       disabled aria-label="Return quantity for ${escapeHtml(item.product_name || 'item')}"
                       onchange="updateReturnQuantity(${index}, this.value)">
            </td>
            <td>KSh ${formatMoney(item.unit_price)}</td>
            <td><strong class="returns-row-refund" id="refund_${index}">KSh 0.00</strong></td>
        </tr>
    `).join('') : '<tr><td colspan="6" class="returns-table-empty">There are no items on this sale.</td></tr>';
    
    // Store items data
    returnItems = items.map(item => {
        const quantity = Number(item.quantity) || 0;
        const unitPrice = Number(item.unit_price) || 0;
        const parsedLineTotal = Number(item.total);
        const lineTotal = Number.isFinite(parsedLineTotal) ? parsedLineTotal : (quantity * unitPrice);

        return {
            ...item,
            quantity,
            unit_price: unitPrice,
            refund_unit_price: quantity > 0 ? lineTotal / quantity : unitPrice,
            total: lineTotal,
            return_quantity: 0,
            refund_amount: 0,
            selected: false
        };
    });
    
    document.getElementById('returnItemCount').textContent = `${items.length} ${items.length === 1 ? 'item' : 'items'}`;
    document.getElementById('itemsSection').hidden = false;
    document.getElementById('returnDetailsSection').hidden = false;
    document.getElementById('submitReturnBtn').hidden = false;
    document.getElementById('submitReturnBtn').disabled = true;
    updateTotalRefund();
}

function toggleReturnItem(index, checked) {
    returnItems[index].selected = checked;
    document.getElementById(`returnQty_${index}`).disabled = !checked;
    
    if (checked) {
        returnItems[index].return_quantity = returnItems[index].quantity;
        returnItems[index].refund_amount = returnItems[index].total;
    } else {
        returnItems[index].return_quantity = 0;
        returnItems[index].refund_amount = 0;
    }

    document.getElementById(`returnQty_${index}`).value = returnItems[index].quantity;
    document.getElementById(`refund_${index}`).textContent = 'KSh ' + formatMoney(returnItems[index].refund_amount);
    
    updateTotalRefund();
}

function updateReturnQuantity(index, qty) {
    const quantity = Math.max(1, parseInt(qty, 10) || 1);
    const maxQty = returnItems[index].quantity;
    const safeQuantity = Math.min(quantity, maxQty);
    
    document.getElementById(`returnQty_${index}`).value = safeQuantity;
    returnItems[index].return_quantity = safeQuantity;
    
    returnItems[index].refund_amount = returnItems[index].return_quantity === maxQty
        ? returnItems[index].total
        : returnItems[index].return_quantity * returnItems[index].refund_unit_price;
    document.getElementById(`refund_${index}`).textContent = 'KSh ' + formatMoney(returnItems[index].refund_amount);
    
    updateTotalRefund();
}

function updateTotalRefund() {
    const total = returnItems
        .filter(item => item.selected)
        .reduce((sum, item) => sum + item.refund_amount, 0);
    
    document.getElementById('totalRefund').textContent = 'KSh ' + formatMoney(total);
    document.getElementById('submitReturnBtn').disabled = !selectedSale || total <= 0;
}

function processReturn(e) {
    e.preventDefault();
    
    const selectedItems = returnItems.filter(item => item.selected && item.return_quantity > 0);
    
    if (!selectedSale || selectedItems.length === 0) {
        showAlert('Please select at least one item to return', 'error');
        return;
    }
    
    const data = {
        sale_id: selectedSale.id,
        customer_id: selectedSale.customer_id,
        items: selectedItems.map(item => ({
            product_id: item.product_id,
            quantity: item.return_quantity,
            unit_price: item.unit_price,
            subtotal: item.refund_amount
        })),
        reason: document.getElementById('returnReason').value,
        refund_method: document.getElementById('refundMethod').value,
        restore_stock: document.getElementById('restoreStock').checked
    };
    
    const submitButton = document.getElementById('submitReturnBtn');
    const originalButtonContent = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';

    fetch('api/create_return.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Return processed successfully!', 'success');
            closeReturnModal();
            loadReturns();
            loadStats();
        } else {
            showAlert(data.message || 'Error processing return', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error processing return', 'error');
    })
    .finally(() => {
        submitButton.innerHTML = originalButtonContent;
        submitButton.disabled = !selectedSale || !returnItems.some(item => item.selected && item.return_quantity > 0);
    });
}

async function approveReturn(returnId) {
    if (!(await appConfirm('Approve this return?'))) return;
    
    fetch('api/approve_return.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({return_id: returnId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Return approved successfully', 'success');
            loadReturns();
            loadStats();
        } else {
            showAlert(data.message || 'Error approving return', 'error');
        }
    });
}

async function rejectReturn(returnId) {
    const reason = await appPrompt('Enter reason for rejection:', { danger: true, okText: 'Reject' });
    if (!reason) return;
    
    fetch('api/reject_return.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({return_id: returnId, reason: reason})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Return rejected', 'success');
            loadReturns();
            loadStats();
        } else {
            showAlert(data.message || 'Error rejecting return', 'error');
        }
    });
}

function viewReturn(returnId) {
    // Could open a modal or redirect to details page
    window.location.href = `return-details.php?id=${returnId}`;
}
