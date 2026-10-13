// Returns & Refunds Management

let selectedSale = null;
let returnItems = [];

document.addEventListener('DOMContentLoaded', function() {
    loadReturns();
    loadStats();
    
    document.getElementById('newReturnBtn').addEventListener('click', openReturnModal);
    document.getElementById('returnForm').addEventListener('submit', processReturn);
    document.getElementById('statusFilter').addEventListener('change', loadReturns);
    document.getElementById('invoiceSearch').addEventListener('input', searchInvoice);
    
    document.querySelector('#returnModal .close').addEventListener('click', closeReturnModal);
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
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No returns found</td></tr>';
        return;
    }
    
    tbody.innerHTML = returns.map(ret => `
        <tr>
            <td><strong>${ret.return_number}</strong></td>
            <td>${ret.invoice_number}</td>
            <td>${ret.customer_name || 'Walk-in'}</td>
            <td>${new Date(ret.created_at).toLocaleDateString()}</td>
            <td><strong>KSh ${parseFloat(ret.total_refund).toFixed(2)}</strong></td>
            <td><span class="badge">${ret.refund_method.toUpperCase()}</span></td>
            <td><span class="badge badge-${getStatusColor(ret.status)}">${ret.status.toUpperCase()}</span></td>
            <td>
                <button class="btn btn-sm" onclick="viewReturn(${ret.id})" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                ${ret.status === 'pending' && ($_SESSION.role === 'admin' || $_SESSION.role === 'manager') ? `
                    <button class="btn btn-sm btn-primary" onclick="approveReturn(${ret.id})" title="Approve">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-sm" onclick="rejectReturn(${ret.id})" title="Reject" style="background: #e74c3c; color: white;">
                        <i class="fas fa-times"></i>
                    </button>
                ` : ''}
            </td>
        </tr>
    `).join('');
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
    document.getElementById('returnForm').reset();
    document.getElementById('saleDetails').style.display = 'none';
    document.getElementById('itemsSection').style.display = 'none';
    document.getElementById('returnDetailsSection').style.display = 'none';
    document.getElementById('submitReturnBtn').style.display = 'none';
    document.getElementById('returnModal').classList.add('show');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('show');
}

let searchTimeout;
function searchInvoice(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 3) {
        document.getElementById('saleDetails').style.display = 'none';
        document.getElementById('itemsSection').style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`api/search_sale.php?invoice=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.sale) {
                    displaySaleDetails(data.sale, data.items);
                } else {
                    showAlert('Sale not found', 'error');
                    document.getElementById('saleDetails').style.display = 'none';
                    document.getElementById('itemsSection').style.display = 'none';
                }
            });
    }, 500);
}

function displaySaleDetails(sale, items) {
    selectedSale = sale;
    
    document.getElementById('saleInvoice').textContent = sale.invoice_number;
    document.getElementById('saleDate').textContent = new Date(sale.created_at).toLocaleDateString();
    document.getElementById('saleCustomer').textContent = sale.customer_name || 'Walk-in Customer';
    document.getElementById('saleTotal').textContent = 'KSh ' + parseFloat(sale.grand_total).toFixed(2);
    
    document.getElementById('saleDetails').style.display = 'block';
    
    // Display items for return
    const tbody = document.getElementById('returnItems');
    tbody.innerHTML = items.map((item, index) => `
        <tr>
            <td>
                <input type="checkbox" class="return-item-check" data-index="${index}" 
                       onchange="toggleReturnItem(${index}, this.checked)">
            </td>
            <td>${item.product_name}</td>
            <td>${item.quantity}</td>
            <td>
                <input type="number" id="returnQty_${index}" class="form-control" 
                       value="${item.quantity}" min="1" max="${item.quantity}" 
                       style="width: 80px;" disabled
                       onchange="updateReturnQuantity(${index}, this.value)">
            </td>
            <td>KSh ${parseFloat(item.unit_price).toFixed(2)}</td>
            <td><strong id="refund_${index}">KSh ${parseFloat(item.total).toFixed(2)}</strong></td>
        </tr>
    `).join('');
    
    // Store items data
    returnItems = items.map((item, index) => ({
        ...item,
        return_quantity: 0,
        refund_amount: 0,
        selected: false
    }));
    
    document.getElementById('itemsSection').style.display = 'block';
    document.getElementById('returnDetailsSection').style.display = 'block';
    document.getElementById('submitReturnBtn').style.display = 'block';
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
    
    updateTotalRefund();
}

function updateReturnQuantity(index, qty) {
    const quantity = parseInt(qty) || 0;
    const maxQty = returnItems[index].quantity;
    
    if (quantity > maxQty) {
        document.getElementById(`returnQty_${index}`).value = maxQty;
        returnItems[index].return_quantity = maxQty;
    } else {
        returnItems[index].return_quantity = quantity;
    }
    
    returnItems[index].refund_amount = returnItems[index].return_quantity * returnItems[index].unit_price;
    document.getElementById(`refund_${index}`).textContent = 'KSh ' + returnItems[index].refund_amount.toFixed(2);
    
    updateTotalRefund();
}

function updateTotalRefund() {
    const total = returnItems
        .filter(item => item.selected)
        .reduce((sum, item) => sum + item.refund_amount, 0);
    
    document.getElementById('totalRefund').value = 'KSh ' + total.toFixed(2);
}

function processReturn(e) {
    e.preventDefault();
    
    const selectedItems = returnItems.filter(item => item.selected && item.return_quantity > 0);
    
    if (selectedItems.length === 0) {
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
