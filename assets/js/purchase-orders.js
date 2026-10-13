let poProducts = [];
let poOrders = [];

document.addEventListener('DOMContentLoaded', () => {
    loadPurchaseOrders();
    Promise.all([fetch('api/get_suppliers.php').then(r => r.json()), fetch('api/get_products.php').then(r => r.json())]).then(([supplierData, productData]) => {
        const supplierSelect = document.getElementById('poSupplier');
        supplierSelect.innerHTML = '<option value="">Select supplier</option>';
        (supplierData.suppliers || []).filter(s => s.status === 'active').forEach(s => supplierSelect.add(new Option(s.name, s.id)));
        poProducts = productData.products || [];
    });
    document.getElementById('newPoBtn').onclick = openPo;
    document.getElementById('closePo').onclick = closePo;
    document.getElementById('cancelPo').onclick = closePo;
    document.getElementById('addPoItem').onclick = addPoItem;
    document.getElementById('poForm').onsubmit = savePurchaseOrder;
    document.getElementById('poSearch').oninput = renderPurchaseOrders;
    document.getElementById('poStatus').onchange = loadPurchaseOrders;
});

function loadPurchaseOrders() {
    const status = document.getElementById('poStatus').value;
    fetch(`api/get_purchase_orders.php${status ? '?status=' + encodeURIComponent(status) : ''}`).then(r => r.json()).then(data => {
        if (!data.success) throw new Error(data.message);
        poOrders = data.orders || [];
        renderPurchaseOrders();
    }).catch(error => showAlert(error.message, 'error'));
}

function renderPurchaseOrders() {
    const query = document.getElementById('poSearch').value.toLowerCase();
    const rows = poOrders.filter(po => !query || `${po.po_number} ${po.supplier_name}`.toLowerCase().includes(query));
    const tbody = document.querySelector('#purchaseOrdersTable tbody');
    tbody.innerHTML = rows.length ? rows.map(po => `<tr><td><strong>${escapeHtml(po.po_number)}</strong></td><td>${escapeHtml(po.supplier_name)}</td><td>KSh ${Number(po.total_amount).toFixed(2)}</td><td><span class="badge badge-${po.status}">${po.status}</span></td><td>${po.expected_date || 'Not set'}</td><td>${po.created_at}</td><td>${['draft','sent','partial'].includes(po.status) ? `<button class="btn btn-sm btn-primary" onclick="receivePo(${po.id})"><i class="fas fa-box-open"></i> Receive</button>` : '-'}</td></tr>`).join('') : '<tr><td colspan="7" style="text-align:center">No purchase orders found</td></tr>';
}

function openPo() { document.getElementById('poForm').reset(); document.getElementById('poItems').innerHTML = ''; addPoItem(); document.getElementById('poModal').classList.add('show'); }
function closePo() { document.getElementById('poModal').classList.remove('show'); }
function addPoItem() {
    const row = document.createElement('tr');
    row.innerHTML = `<td><select class="po-product" required><option value="">Select product</option>${poProducts.map(p => `<option value="${p.id}" data-cost="${p.cost_price}">${escapeHtml(p.name)} (${escapeHtml(p.sku || 'no SKU')})</option>`).join('')}</select></td><td><input class="po-qty" type="number" min="1" value="1" required></td><td><input class="po-cost" type="number" min="0" step="0.01" value="0" required></td><td><button type="button" class="btn-icon text-danger" title="Remove" onclick="this.closest('tr').remove(); updatePoTotal()"><i class="fas fa-trash"></i></button></td>`;
    const product = row.querySelector('.po-product');
    product.onchange = () => { row.querySelector('.po-cost').value = product.selectedOptions[0]?.dataset.cost || 0; updatePoTotal(); };
    row.querySelector('.po-qty').oninput = updatePoTotal; row.querySelector('.po-cost').oninput = updatePoTotal;
    document.getElementById('poItems').appendChild(row);
}
function updatePoTotal() { let total = 0; document.querySelectorAll('#poItems tr').forEach(row => total += Number(row.querySelector('.po-qty').value) * Number(row.querySelector('.po-cost').value)); document.getElementById('poTotal').textContent = total.toFixed(2); }
function savePurchaseOrder(event) {
    event.preventDefault();
    const items = [...document.querySelectorAll('#poItems tr')].map(row => ({ product_id: row.querySelector('.po-product').value, quantity: row.querySelector('.po-qty').value, unit_cost: row.querySelector('.po-cost').value }));
    if (!items.length || items.some(item => !item.product_id)) return showAlert('Add at least one product', 'error');
    ajax('api/create_purchase_order.php', { supplier_id: document.getElementById('poSupplier').value, expected_date: document.getElementById('poExpected').value || null, notes: document.getElementById('poNotes').value, items }).then(data => { if (!data.success) throw new Error(data.message); showAlert(data.message); closePo(); loadPurchaseOrders(); }).catch(error => showAlert(error.message, 'error'));
}
async function receivePo(id) {
    if (!(await appConfirm('Receive all outstanding quantities for this purchase order and add them to stock?'))) return;
    ajax('api/receive_purchase_order.php', { id }).then(data => { if (!data.success) throw new Error(data.message); showAlert(data.message); loadPurchaseOrders(); }).catch(error => showAlert(error.message, 'error'));
}
function escapeHtml(value) { return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char])); }
