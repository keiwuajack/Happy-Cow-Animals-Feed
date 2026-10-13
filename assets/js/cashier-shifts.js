document.addEventListener('DOMContentLoaded', () => { loadShift(); loadHistory(); });

function loadHistory() {
    fetch('api/get_shift_history.php').then(r => r.json()).then(d => {
        const b = document.getElementById('shiftHistory');
        if (!d.success || !d.shifts.length) { b.innerHTML = '<tr><td colspan="10">No shifts yet</td></tr>'; return; }
        b.innerHTML = d.shifts.map(s => {
            const closed = s.status === 'closed';
            return `<tr><td>${s.cashier}</td><td>${s.opened_at}</td><td>${money(s.opening_cash)}</td><td>${closed ? s.closed_at : '-'}</td><td>${closed ? money(s.closing_cash) : '-'}</td><td>${closed ? money(s.expected_cash) : '-'}</td><td>${closed ? varianceBadge(s.variance) : '-'}</td><td>${money(s.total_sales)}</td><td><span class="badge ${closed ? 'closed' : 'open'}">${closed ? 'Closed' : 'Open'}</span></td><td>${s.notes || ''}</td></tr>`;
        }).join('');
    });
}

function varianceBadge(value) {
    const v = Number(value || 0);
    if (v === 0) return '<span class="badge ok"><i class="fas fa-check"></i> Balanced</span>';
    return `<span class="badge ${v < 0 ? 'short' : 'over'}">${v < 0 ? 'Short ' : 'Over +'}${money(Math.abs(v))}</span>`;
}

function loadShift() {
    fetch('api/shift_status.php').then(r => r.json()).then(data => {
        if (!data.success) throw new Error(data.message);
        renderShift(data.shift, data.previous_closing);
    }).catch(e => showAlert(e.message, 'error'));
}

function renderShift(shift, prevClosing) {
    const panel = document.getElementById('shiftPanel');
    if (!shift) {
        panel.innerHTML = `<div class="shift-hero idle"><div class="shift-icon" style="margin:0"><i class="fas fa-lock"></i></div><div><h2>No active shift</h2><p>Selling is locked until you open a shift and count the drawer.</p></div></div>
        ${prevClosing != null ? `<div class="shift-hint"><i class="fas fa-circle-info"></i><span>Previous shift closed with <strong>${money(prevClosing)}</strong></span></div>` : ''}
        <form id="openShiftForm" class="shift-form"><div class="shift-field big"><label>Counted opening cash</label><input id="openingCash" type="number" min="0" step="0.01" placeholder="0.00" required></div><div class="shift-field"><label>Note (required if different from previous)</label><input id="openingNote" type="text" placeholder="Explain any difference"></div><div class="shift-field"><button class="shift-btn"><i class="fas fa-play"></i> Open shift</button></div></form>`;
        document.getElementById('openShiftForm').onsubmit = e => {
            e.preventDefault();
            ajax('api/open_shift.php', {opening_cash: document.getElementById('openingCash').value, note: document.getElementById('openingNote').value}).then(handleShiftResponse).catch(err => showAlert(err.message, 'error'));
        };
        return;
    }
    panel.innerHTML = `<div class="shift-hero"><span class="shift-pulse"></span><div><h2>Shift in progress</h2><p>You can sell now. Count the drawer when you are done.</p></div></div>
    <div class="shift-stats"><div class="shift-stat"><span>Opened</span><strong>${shift.opened_at}</strong></div><div class="shift-stat"><span>Opening cash</span><strong>${money(shift.opening_cash)}</strong></div></div>
    <form id="closeShiftForm" class="shift-form"><div class="shift-field big"><label>Counted closing cash</label><input id="closingCash" type="number" min="0" step="0.01" placeholder="0.00" required></div><div class="shift-field"><label>Notes (required if the count differs)</label><input id="shiftNotes" type="text" placeholder="Optional"></div><div class="shift-field"><button class="shift-btn danger"><i class="fas fa-stop"></i> Close shift</button></div></form>`;
    document.getElementById('closeShiftForm').onsubmit = e => {
        e.preventDefault();
        ajax('api/close_shift.php', {closing_cash: document.getElementById('closingCash').value, notes: document.getElementById('shiftNotes').value}).then(handleShiftResponse).catch(err => showAlert(err.message, 'error'));
    };
}

function handleShiftResponse(data) { if (!data.success) throw new Error(data.message); showAlert(data.message); loadShift(); loadHistory(); }
function money(value) { return 'KSh ' + Number(value || 0).toFixed(2); }
