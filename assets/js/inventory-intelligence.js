document.addEventListener('DOMContentLoaded', loadInventoryIntelligence);

function loadInventoryIntelligence() {
    fetch('api/inventory_intelligence.php').then(response => response.json()).then(data => {
        if (!data.success) throw new Error(data.message || 'Unable to load inventory intelligence');
        renderSummary(data.summary);
        renderLowStock(data.low_stock);
        renderExpiry(data.expiring, data.expiry_tracking);
        renderMargins(data.margin_opportunities);
    }).catch(error => showAlert(error.message, 'error'));
}

function renderSummary(summary) {
    document.getElementById('inventorySummary').innerHTML = [
        summaryCard('fas fa-boxes-stacked', 'Stock at cost', money(summary.stock_value), 'Capital currently tied in stock'),
        summaryCard('fas fa-chart-line', 'Potential revenue', money(summary.potential_revenue), 'Revenue if all stock sells'),
        summaryCard('fas fa-sack-dollar', 'Potential margin', money(summary.potential_margin), 'Gross margin in current stock'),
        summaryCard('fas fa-triangle-exclamation', 'Reorder items', Number(summary.low_stock_count || 0), 'Products at or below reorder level'),
        summaryCard('fas fa-calendar-xmark', 'Expiring within 30 days', Number(summary.expiring_count || 0), 'Requires action soon'),
        summaryCard('fas fa-clock-rotate-left', 'Expired stock value', money(summary.expired_stock_value), 'Cost value requiring review')
    ].join('');
}
function summaryCard(icon, title, value, context) { return `<div class="stat-card"><div class="stat-icon"><i class="${icon}"></i></div><div class="stat-details"><h3>${title}</h3><span class="stat-context">${context}</span><p class="stat-value">${value}</p></div></div>`; }
function renderLowStock(items) { document.getElementById('lowStockRows').innerHTML = items.length ? items.map(item => `<tr><td><strong>${safe(item.name)}</strong><br><small>${safe(item.sku || 'No SKU')}</small></td><td>${item.quantity} ${safe(item.unit)}</td><td>${item.reorder_level}</td><td>${Math.max(item.reorder_level * 2 - item.quantity, item.reorder_level)}</td><td>${money(item.quantity * item.cost_price)}</td></tr>`).join('') : emptyRow(5, 'No reorder recommendations.'); }
function renderExpiry(items, tracking) { if (!tracking) return document.getElementById('expiryRows').innerHTML = emptyRow(5, 'Expiry tracking is not configured.'); document.getElementById('expiryRows').innerHTML = items.length ? items.map(item => `<tr><td><strong>${safe(item.name)}</strong></td><td>${item.quantity} ${safe(item.unit)}</td><td>${item.expiry_date}</td><td class="${item.days_left < 0 ? 'text-danger' : item.days_left <= 30 ? 'text-warning' : ''}">${item.days_left < 0 ? 'Expired' : item.days_left + ' days'}</td><td>${money(item.quantity * item.cost_price)}</td></tr>`).join('') : emptyRow(5, 'No products expiring within 90 days.'); }
function renderMargins(items) { document.getElementById('marginRows').innerHTML = items.length ? items.map(item => `<tr><td><strong>${safe(item.name)}</strong></td><td>${item.quantity} ${safe(item.unit)}</td><td>${money(item.cost_price)}</td><td>${money(item.selling_price)}</td><td>${money(item.margin_amount)}</td><td>${Number(item.margin_percent).toFixed(1)}%</td></tr>`).join('') : emptyRow(6, 'No margin data available.'); }
function emptyRow(columns, message) { return `<tr><td colspan="${columns}" style="text-align:center">${message}</td></tr>`; }
function money(value) { return 'KSh ' + Number(value || 0).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function safe(value) { return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char])); }
