document.addEventListener('DOMContentLoaded', () => {
    loadShift();
    loadHistory();
});

function loadHistory() {
    fetch('api/get_shift_history.php')
        .then(response => response.json())
        .then(data => {
            const body = document.getElementById('shiftHistory');
            const count = document.getElementById('shiftCount');
            const shifts = Array.isArray(data.shifts) ? data.shifts : [];

            if (!data.success) throw new Error(data.message || 'Shift history could not be loaded');

            count.textContent = `${shifts.length} recent ${shifts.length === 1 ? 'shift' : 'shifts'}`;
            if (!shifts.length) {
                body.innerHTML = '<tr><td colspan="13" class="shift-empty-cell"><i class="fas fa-clock" aria-hidden="true"></i><strong>No shifts recorded yet</strong><span>Your completed and active shifts will appear here.</span></td></tr>';
                return;
            }

            body.innerHTML = shifts.map(shift => {
                const closed = shift.status === 'closed';
                const cashier = shift.cashier || 'Cashier';
                const notes = shift.notes || '-';
                const cashPaid = Number(shift.cash_sales || 0);
                const mpesaPaid = Number(shift.mpesa_sales || 0);
                const otherPaid = Number(shift.card_sales || 0);
                const totalReceived = Math.round((cashPaid + mpesaPaid + otherPaid) * 100) / 100;
                return `<tr>
                    <td>${escapeShiftHtml(cashier)}</td>
                    <td>${escapeShiftHtml(formatShiftDate(shift.opened_at))}</td>
                    <td>${money(shift.opening_cash)}</td>
                    <td>${closed ? escapeShiftHtml(formatShiftDate(shift.closed_at)) : '-'}</td>
                    <td>${closed ? money(shift.closing_cash) : '-'}</td>
                    <td>${closed ? money(shift.expected_cash) : '-'}</td>
                    <td>${closed ? varianceBadge(shift.variance) : '-'}</td>
                    <td class="money-cell">${money(cashPaid)}</td>
                    <td class="money-cell">${money(mpesaPaid)}</td>
                    <td class="money-cell">${money(otherPaid)}</td>
                    <td class="money-cell total-received-cell">${money(totalReceived)}</td>
                    <td><span class="shift-badge ${closed ? 'closed' : 'open'}">${closed ? 'Closed' : 'Open'}</span></td>
                    <td>${escapeShiftHtml(notes)}</td>
                </tr>`;
            }).join('');
        })
        .catch(error => {
            document.getElementById('shiftCount').textContent = 'History unavailable';
            document.getElementById('shiftHistory').innerHTML = '<tr><td colspan="13" class="shift-empty-cell"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><strong>Could not load shift history</strong><span>Refresh the page and try again.</span></td></tr>';
            console.error('Error loading shift history:', error);
        });
}

function varianceBadge(value) {
    const variance = Number(value || 0);
    if (variance === 0) {
        return '<span class="shift-badge ok"><i class="fas fa-check" aria-hidden="true"></i> Balanced</span>';
    }

    const isShort = variance < 0;
    const label = isShort ? `Short ${money(Math.abs(variance))}` : `Over ${money(variance)}`;
    return `<span class="shift-badge ${isShort ? 'short' : 'over'}">${label}</span>`;
}

function loadShift() {
    fetch('api/shift_status.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Shift status could not be loaded');
            renderShift(data.shift, data.previous_closing);
        })
        .catch(error => {
            document.getElementById('shiftPanel').innerHTML = `<div class="shift-loading shift-load-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span>${escapeShiftHtml(error.message || 'Could not load shift status')}</span></div>`;
            showAlert(error.message || 'Could not load shift status', 'error');
        });
}

function renderShift(shift, previousClosing) {
    const panel = document.getElementById('shiftPanel');

    if (!shift) {
        panel.innerHTML = `
            <div class="shift-state-card idle">
                <span class="shift-state-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                <div class="shift-state-copy">
                    <div class="shift-state-title-row">
                        <h2>No active shift</h2>
                        <span class="shift-live-badge drawer-closed-badge"><span class="shift-closed-dot" aria-hidden="true"></span> Drawer closed</span>
                    </div>
                    <p>Open a shift and count the drawer before starting sales.</p>
                </div>
            </div>
            ${previousClosing !== null && previousClosing !== undefined ? `
                <div class="shift-hint"><i class="fas fa-circle-info" aria-hidden="true"></i><span>Previous shift closing cash: <strong>${money(previousClosing)}</strong></span></div>
            ` : ''}
            <form id="openShiftForm" class="shift-form">
                <div class="shift-form-heading"><h3>Start a shift</h3><p>Count the physical cash in the drawer before you begin sales.</p></div>
                <div class="shift-field">
                    <label for="openingCash">Counted opening cash</label>
                    <div class="shift-money-input"><span class="shift-money-prefix">KSh</span><input id="openingCash" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00" required></div>
                    <p class="shift-field-hint">Enter the cash physically in the drawer.</p>
                </div>
                <div class="shift-field">
                    <label for="openingNote">Opening note</label>
                    <textarea id="openingNote" rows="2" placeholder="Explain any difference from the previous closing count"></textarea>
                    <p class="shift-field-hint">Required if today's opening count differs from the last closing count.</p>
                </div>
                <div class="shift-field shift-submit-field">
                    <button class="shift-btn" type="submit"><i class="fas fa-unlock-keyhole" aria-hidden="true"></i> Open shift</button>
                </div>
            </form>`;

        document.getElementById('openShiftForm').addEventListener('submit', event => {
            event.preventDefault();
            ajax('api/open_shift.php', {
                opening_cash: document.getElementById('openingCash').value,
                note: document.getElementById('openingNote').value
            }).then(handleShiftResponse).catch(error => showAlert(error.message, 'error'));
        });
        return;
    }

    panel.innerHTML = `
        <div class="shift-state-card active">
            <span class="shift-state-icon"><i class="fas fa-cash-register" aria-hidden="true"></i></span>
            <div class="shift-state-copy">
                <div class="shift-state-title-row">
                    <h2>Shift in progress</h2>
                    <span class="shift-live-badge"><span class="shift-live-dot"></span> Active</span>
                </div>
                <p>Record the drawer count when you finish this shift.</p>
            </div>
        </div>
        <div class="shift-stats">
            <div class="shift-stat">
                <span class="shift-stat-icon"><i class="fas fa-clock" aria-hidden="true"></i></span>
                <div class="shift-stat-copy"><span>Shift opened</span><strong>${escapeShiftHtml(formatShiftDate(shift.opened_at))}</strong></div>
            </div>
            <div class="shift-stat">
                <span class="shift-stat-icon"><i class="fas fa-money-bill-wave" aria-hidden="true"></i></span>
                <div class="shift-stat-copy"><span>Opening cash</span><strong>${money(shift.opening_cash)}</strong></div>
            </div>
        </div>
        <div class="shift-takings-heading">
            <div><h3>Current shift totals</h3><p>Payments are grouped by method; total received adds them together.</p></div>
            <span><i class="fas fa-arrows-rotate" aria-hidden="true"></i> Updates when this page refreshes</span>
        </div>
        <div class="shift-stats shift-takings-stats" aria-label="Payments received this shift">
            <div class="shift-stat">
                <span class="shift-stat-icon"><i class="fas fa-money-bill-wave" aria-hidden="true"></i></span>
                <div class="shift-stat-copy"><span>Cash paid</span><strong>${money(shift.cash_sales)}</strong></div>
            </div>
            <div class="shift-stat">
                <span class="shift-stat-icon"><i class="fas fa-mobile-screen-button" aria-hidden="true"></i></span>
                <div class="shift-stat-copy"><span>M-Pesa paid</span><strong>${money(shift.mpesa_sales)}</strong></div>
            </div>
            <div class="shift-stat">
                <span class="shift-stat-icon"><i class="fas fa-credit-card" aria-hidden="true"></i></span>
                <div class="shift-stat-copy"><span>Other paid</span><strong>${money(shift.card_sales)}</strong></div>
            </div>
            <div class="shift-stat total-received-stat">
                <span class="shift-stat-icon"><i class="fas fa-calculator" aria-hidden="true"></i></span>
                <div class="shift-stat-copy"><span>Total received</span><strong>${money(shift.total_sales)}</strong></div>
            </div>
            <div class="shift-stat expected-cash-stat">
                <span class="shift-stat-icon"><i class="fas fa-cash-register" aria-hidden="true"></i></span>
                <div class="shift-stat-copy"><span>Expected drawer cash</span><strong>${money(shift.expected_cash)}</strong></div>
            </div>
        </div>
        <form id="closeShiftForm" class="shift-form">
            <div class="shift-form-heading"><h3>Close out this shift</h3><p>Count the cash remaining in the drawer and record any difference.</p></div>
            <div class="shift-field">
                <label for="closingCash">Counted closing cash</label>
                <div class="shift-money-input"><span class="shift-money-prefix">KSh</span><input id="closingCash" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00" required></div>
                <p class="shift-field-hint">Enter the cash physically remaining. Expected cash includes opening cash plus cash payments.</p>
            </div>
            <div class="shift-field">
                <label for="shiftNotes">Closing note</label>
                <textarea id="shiftNotes" rows="2" placeholder="Explain any difference from the expected cash count"></textarea>
                <p class="shift-field-hint">Required if your closing count differs from expected cash.</p>
            </div>
            <div class="shift-field shift-submit-field">
                <button class="shift-btn danger" type="submit"><i class="fas fa-lock" aria-hidden="true"></i> Close shift</button>
            </div>
        </form>`;

    document.getElementById('closeShiftForm').addEventListener('submit', event => {
        event.preventDefault();
        ajax('api/close_shift.php', {
            closing_cash: document.getElementById('closingCash').value,
            notes: document.getElementById('shiftNotes').value
        }).then(handleShiftResponse).catch(error => showAlert(error.message, 'error'));
    });
}

function handleShiftResponse(data) {
    if (!data.success) throw new Error(data.message || 'The shift could not be updated');
    showAlert(data.message || 'Shift updated');
    loadShift();
    loadHistory();
}

function money(value) {
    const amount = Number(value || 0);
    return `KSh ${amount.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatShiftDate(value) {
    if (!value) return '-';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('en-KE', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function escapeShiftHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));
}
