document.addEventListener('DOMContentLoaded', loadInventoryIntelligence);

function loadInventoryIntelligence() {
    fetch('api/inventory_intelligence.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Unable to load inventory overview');
            renderSummary(data.summary || {}, Boolean(data.expiry_tracking));
            renderLowStock(data.low_stock || []);
            renderExpiry(data.expiring || [], Boolean(data.expiry_tracking));
            renderMargins(data.margin_opportunities || []);
        })
        .catch(error => {
            console.error('Inventory overview could not be loaded:', error);
            renderLoadError();
        });
}

function renderSummary(summary, expiryTracking) {
    const metrics = [
        metricCard('alert', 'fas fa-triangle-exclamation', 'Reorder now', formatCount(summary.low_stock_count), 'Products at or below their reorder point'),
        metricCard('alert', 'fas fa-calendar-days', 'Expiring within 30 days', expiryTracking ? formatCount(summary.expiring_count) : 'Not tracked', expiryTracking ? 'Products with an expiry date coming soon' : 'Expiry dates are not set up for products'),
        metricCard('alert', 'fas fa-calendar-xmark', 'Expired stock at cost', expiryTracking ? money(summary.expired_stock_value) : 'Not tracked', expiryTracking ? 'Review these products and their expiry dates' : 'Add expiry dates to see this figure'),
        metricCard('value', 'fas fa-boxes-stacked', 'Stock value at cost', money(summary.stock_value), 'Current stock valued at its cost price'),
        metricCard('value', 'fas fa-cash-register', 'Sales value if sold', money(summary.potential_revenue), 'Estimate at current selling prices, before discounts and tax'),
        metricCard('value', 'fas fa-chart-line', 'Estimated gross profit', money(summary.potential_margin), 'Selling price minus cost, before overheads and tax')
    ];

    document.getElementById('inventorySummary').innerHTML = `
        <div class="inventory-metric-group">
            <h3><i class="fas fa-bell" aria-hidden="true"></i> Items to review</h3>
            <div class="inventory-metric-grid inventory-metric-grid--attention">${metrics.slice(0, 3).join('')}</div>
        </div>
        <div class="inventory-metric-group">
            <h3><i class="fas fa-coins" aria-hidden="true"></i> Stock value overview</h3>
            <div class="inventory-metric-grid">${metrics.slice(3).join('')}</div>
        </div>`;
}

function metricCard(type, icon, title, value, context) {
    return `<article class="inventory-metric inventory-metric--${type}">
        <span class="inventory-metric-icon"><i class="${icon}" aria-hidden="true"></i></span>
        <div class="inventory-metric-copy">
            <h4>${title}</h4>
            <p class="inventory-metric-value">${value}</p>
            <span class="inventory-metric-context">${context}</span>
        </div>
    </article>`;
}

function renderLowStock(items) {
    const rows = Array.isArray(items) ? items : [];
    setListCount('reorderListCount', rows.length);
    document.getElementById('lowStockRows').innerHTML = rows.length
        ? rows.map(item => {
            const quantity = Number(item.quantity) || 0;
            const reorderLevel = Number(item.reorder_level) || 0;
            const topUp = Math.max(reorderLevel * 2 - quantity, reorderLevel);
            return `<tr>
                <td><strong>${safe(item.name)}</strong><small class="inventory-sku">${safe(item.sku || 'No SKU')}</small></td>
                <td><span class="inventory-quantity inventory-quantity--low">${quantityLabel(quantity, item.unit)}</span></td>
                <td>${quantityLabel(reorderLevel, item.unit)}</td>
                <td><span class="inventory-quantity inventory-quantity--suggested">${quantityLabel(topUp, item.unit)}</span></td>
                <td class="inventory-money">${money(quantity * (Number(item.cost_price) || 0))}</td>
            </tr>`;
        }).join('')
        : emptyRow(5, 'fas fa-circle-check', 'No products need reordering', 'All products are currently above their reorder point.');
}

function renderExpiry(items, tracking) {
    const target = document.getElementById('expiryRows');
    if (!tracking) {
        setListCount('expiryListCount', null, 'Not tracked');
        target.innerHTML = emptyRow(5, 'fas fa-calendar-minus', 'Expiry dates are not set up', 'Add expiry dates to product records to use this watch list.');
        return;
    }

    const rows = Array.isArray(items) ? items : [];
    setListCount('expiryListCount', rows.length);
    target.innerHTML = rows.length
        ? rows.map(item => {
            const daysLeft = Number(item.days_left);
            const status = expiryStatus(daysLeft);
            const quantity = Number(item.quantity) || 0;
            return `<tr>
                <td><strong>${safe(item.name)}</strong><small class="inventory-sku">${safe(item.sku || 'No SKU')}</small></td>
                <td>${quantityLabel(quantity, item.unit)}</td>
                <td>${formatDate(item.expiry_date)}</td>
                <td><span class="inventory-status inventory-status--${status.type}">${status.label}</span></td>
                <td class="inventory-money">${money(quantity * (Number(item.cost_price) || 0))}</td>
            </tr>`;
        }).join('')
        : emptyRow(5, 'fas fa-circle-check', 'No expiry dates to review', 'There is no expired stock or stock expiring within the next 90 days.');
}

function renderMargins(items) {
    const rows = Array.isArray(items) ? items : [];
    setListCount('marginListCount', rows.length);
    document.getElementById('marginRows').innerHTML = rows.length
        ? rows.map(item => `<tr>
            <td><strong>${safe(item.name)}</strong><small class="inventory-sku">${safe(item.sku || 'No SKU')}</small></td>
            <td>${quantityLabel(item.quantity, item.unit)}</td>
            <td class="inventory-money">${money(item.cost_price)}</td>
            <td class="inventory-money">${money(item.selling_price)}</td>
            <td class="inventory-money"><strong>${money(item.margin_amount)}</strong></td>
            <td><span class="inventory-margin-rate">${formatPercent(item.margin_percent)}</span></td>
        </tr>`).join('')
        : emptyRow(6, 'fas fa-chart-line', 'No margin data available', 'Add a cost price and selling price to stocked products to compare margins.');
}

function expiryStatus(daysLeft) {
    if (daysLeft < 0) return {type: 'expired', label: `Expired ${formatCount(Math.abs(daysLeft))} ${Math.abs(daysLeft) === 1 ? 'day' : 'days'} ago`};
    if (daysLeft === 0) return {type: 'urgent', label: 'Expires today'};
    if (daysLeft <= 30) return {type: 'urgent', label: `In ${formatCount(daysLeft)} days`};
    return {type: 'upcoming', label: `In ${formatCount(daysLeft)} days`};
}

function emptyRow(columns, icon, title, message) {
    return `<tr class="inventory-empty-row"><td colspan="${columns}">
        <div class="inventory-empty-state"><i class="${icon}" aria-hidden="true"></i><strong>${title}</strong><span>${message}</span></div>
    </td></tr>`;
}

function renderLoadError() {
    document.getElementById('inventorySummary').innerHTML = '<div class="inventory-load-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><strong>Inventory overview could not load.</strong><span>Refresh the page to try again.</span></div>';
    setListCount('reorderListCount', null, 'Unavailable');
    setListCount('expiryListCount', null, 'Unavailable');
    setListCount('marginListCount', null, 'Unavailable');
    document.getElementById('lowStockRows').innerHTML = emptyRow(5, 'fas fa-cloud-arrow-down', 'Could not load reorder list', 'Please refresh the page.');
    document.getElementById('expiryRows').innerHTML = emptyRow(5, 'fas fa-cloud-arrow-down', 'Could not load expiry list', 'Please refresh the page.');
    document.getElementById('marginRows').innerHTML = emptyRow(6, 'fas fa-cloud-arrow-down', 'Could not load margin list', 'Please refresh the page.');
}

function setListCount(elementId, count, label) {
    const element = document.getElementById(elementId);
    if (!element) return;
    element.textContent = label || `${formatCount(count)} ${Number(count) === 1 ? 'product' : 'products'}`;
}

function quantityLabel(value, unit) {
    return `${formatQuantity(value)} ${safe(unit || 'units')}`;
}

function formatQuantity(value) {
    const number = Number(value || 0);
    return (Number.isFinite(number) ? number : 0).toLocaleString('en-KE', {maximumFractionDigits: 2});
}

function formatCount(value) {
    const number = Number(value || 0);
    return (Number.isFinite(number) ? number : 0).toLocaleString('en-KE', {maximumFractionDigits: 0});
}

function formatPercent(value) {
    const number = Number(value || 0);
    return `${(Number.isFinite(number) ? number : 0).toFixed(1)}%`;
}

function formatDate(value) {
    const parts = String(value || '').split('-').map(Number);
    if (parts.length !== 3 || parts.some(part => !Number.isFinite(part))) return safe(value || '—');
    return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2])).toLocaleDateString('en-KE', {
        day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC'
    });
}

function money(value) {
    const number = Number(value || 0);
    return 'KSh ' + (Number.isFinite(number) ? number : 0).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function safe(value) {
    return String(value ?? '').replace(/[&<>\'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
}
