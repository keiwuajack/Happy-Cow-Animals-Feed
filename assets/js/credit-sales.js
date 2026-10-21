// Credit Sales Management
let creditCustomers = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCreditCustomers();
    loadPayments();
    loadStats();

    document.getElementById('recordPaymentBtn').addEventListener('click', openPaymentModal);
    document.getElementById('paymentForm').addEventListener('submit', recordPayment);
    document.getElementById('customerSearch').addEventListener('input', filterCustomers);
    document.getElementById('paymentCustomer').addEventListener('change', showCustomerBalance);
    document.getElementById('paymentAmount').addEventListener('input', updatePaymentSummary);

    document.querySelector('#paymentModal .close').addEventListener('click', closePaymentModal);
    document.getElementById('paymentModal').addEventListener('click', function(event) {
        if (event.target === this) closePaymentModal();
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.getElementById('paymentModal').classList.contains('show')) {
            closePaymentModal();
        }
    });
});

function loadCreditCustomers() {
    fetch('api/get_credit_customers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                creditCustomers = data.customers || [];
                displayCreditCustomers(creditCustomers);
                populateCustomerSelect(creditCustomers);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayCreditCustomers(customers) {
    const tbody = document.querySelector('#creditCustomersTable tbody');

    if (!customers.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="credit-table-empty">No credit customers found</td></tr>';
        return;
    }

    tbody.innerHTML = customers.map(customer => {
        const balance = Number.parseFloat(customer.credit_balance) || 0;
        const available = (Number.parseFloat(customer.credit_limit) || 0) - balance;
        const customerId = Number(customer.id) || 0;
        const balanceClass = balance > 0 ? 'credit-balance-positive' : 'credit-balance-clear';
        const availableClass = available < 0 ? 'credit-available-negative' : 'credit-available-positive';
        const name = customer.name || 'Customer';

        return `
            <tr>
                <td><strong class="credit-customer-name">${escapeHtml(name)}</strong></td>
                <td>${escapeHtml(customer.phone || '—')}</td>
                <td>KSh ${formatMoney(customer.credit_limit)}</td>
                <td><strong class="${balanceClass}">KSh ${formatMoney(balance)}</strong></td>
                <td><strong class="${availableClass}">KSh ${formatMoney(available)}</strong></td>
                <td>${customer.last_payment ? formatDate(customer.last_payment) : 'Never'}</td>
                <td class="credit-actions-cell">
                    <button class="btn btn-sm btn-primary credit-action-payment" onclick="quickPayment(${customerId})" title="Record payment" aria-label="Record payment for ${escapeHtml(name)}">
                        <i class="fas fa-money-bill"></i>
                    </button>
                    <button class="btn btn-sm credit-action-history" onclick="viewCustomerHistory(${customerId})" title="Payment history" aria-label="View payment history for ${escapeHtml(name)}">
                        <i class="fas fa-history"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function populateCustomerSelect(customers) {
    const select = document.getElementById('paymentCustomer');
    select.innerHTML = '<option value="">Select a customer with an outstanding balance</option>';

    customers.forEach(customer => {
        const balance = Number.parseFloat(customer.credit_balance) || 0;
        if (balance > 0) {
            const option = new Option(`${customer.name} — Balance: KSh ${formatMoney(balance)}`, customer.id);
            select.add(option);
        }
    });
}

function loadPayments() {
    fetch('api/get_credit_payments.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) displayPayments(data.payments || []);
        })
        .catch(error => console.error('Error:', error));
}

function displayPayments(payments) {
    const tbody = document.querySelector('#paymentsTable tbody');

    if (!payments.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="credit-table-empty">No payments recorded</td></tr>';
        return;
    }

    tbody.innerHTML = payments.slice(0, 20).map(payment => `
        <tr>
            <td>${formatDate(payment.payment_date)}</td>
            <td>${escapeHtml(payment.customer_name || 'Customer')}</td>
            <td><strong>KSh ${formatMoney(payment.amount)}</strong></td>
            <td><span class="badge credit-method-badge">${escapeHtml(String(payment.payment_method || '').toUpperCase())}</span></td>
            <td>${escapeHtml(payment.reference_number || '—')}</td>
            <td>${escapeHtml(payment.recorded_by_name || '—')}</td>
        </tr>
    `).join('');
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
    return Number.isNaN(date.getTime()) ? '—' : date.toLocaleString();
}

function loadStats() {
    fetch('api/get_credit_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalCreditCustomers').textContent = data.stats.total_customers || 0;
                document.getElementById('totalOutstanding').textContent = 'KSh ' + formatMoney(data.stats.total_outstanding);
                document.getElementById('totalCreditLimit').textContent = 'KSh ' + formatMoney(data.stats.total_limit);
                document.getElementById('paymentsToday').textContent = 'KSh ' + formatMoney(data.stats.payments_today);
            }
        })
        .catch(error => console.error('Error:', error));
}

function filterCustomers() {
    const search = document.getElementById('customerSearch').value.trim().toLowerCase();
    const filtered = creditCustomers.filter(customer =>
        String(customer.name || '').toLowerCase().includes(search) ||
        String(customer.phone || '').toLowerCase().includes(search)
    );
    displayCreditCustomers(filtered);
}

function openPaymentModal() {
    document.getElementById('paymentForm').reset();
    document.getElementById('customerBalance').hidden = true;
    document.getElementById('paymentAmount').max = '';
    document.getElementById('remainingBalance').textContent = '—';
    document.querySelector('.credit-remaining-balance').classList.remove('is-overpay');
    const modal = document.getElementById('paymentModal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(() => document.getElementById('paymentCustomer').focus());
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function showCustomerBalance() {
    const customerId = document.getElementById('paymentCustomer').value;
    const amountInput = document.getElementById('paymentAmount');

    if (!customerId) {
        document.getElementById('customerBalance').hidden = true;
        amountInput.max = '';
        document.getElementById('remainingBalance').textContent = '—';
        return;
    }

    const customer = creditCustomers.find(item => Number(item.id) === Number(customerId));
    if (!customer) return;

    const balance = Number.parseFloat(customer.credit_balance) || 0;
    document.getElementById('balanceAmount').textContent = 'KSh ' + formatMoney(balance);
    document.getElementById('limitAmount').textContent = 'KSh ' + formatMoney(customer.credit_limit);
    document.getElementById('customerBalance').hidden = false;
    amountInput.max = balance.toFixed(2);
    updatePaymentSummary();
}

function updatePaymentSummary() {
    const customerId = document.getElementById('paymentCustomer').value;
    const customer = creditCustomers.find(item => Number(item.id) === Number(customerId));
    if (!customer) return;

    const balance = Number.parseFloat(customer.credit_balance) || 0;
    const amount = Number.parseFloat(document.getElementById('paymentAmount').value) || 0;
    const remaining = balance - amount;
    document.getElementById('remainingBalance').textContent = 'KSh ' + formatMoney(remaining);
    document.querySelector('.credit-remaining-balance').classList.toggle('is-overpay', remaining < 0);
}

function quickPayment(customerId) {
    openPaymentModal();
    document.getElementById('paymentCustomer').value = customerId;
    showCustomerBalance();
}

function recordPayment(event) {
    event.preventDefault();

    const data = {
        customer_id: document.getElementById('paymentCustomer').value,
        amount: Number.parseFloat(document.getElementById('paymentAmount').value),
        payment_method: document.getElementById('paymentMethodSelect').value,
        reference_number: document.getElementById('paymentReference').value,
        notes: document.getElementById('paymentNotes').value
    };

    if (!data.customer_id) {
        showAlert('Please select a customer', 'error');
        return;
    }

    if (!Number.isFinite(data.amount) || data.amount <= 0) {
        showAlert('Please enter a valid amount', 'error');
        return;
    }

    const customer = creditCustomers.find(item => Number(item.id) === Number(data.customer_id));
    const outstanding = Number.parseFloat(customer?.credit_balance) || 0;
    if (data.amount > outstanding) {
        showAlert('Payment amount cannot exceed the outstanding balance', 'error');
        return;
    }

    const submitButton = document.getElementById('submitPaymentBtn');
    const originalButtonContent = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recording…';

    fetch('api/record_credit_payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('Payment recorded successfully!', 'success');
            closePaymentModal();
            loadCreditCustomers();
            loadPayments();
            loadStats();
        } else {
            showAlert(result.message || 'Error recording payment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error recording payment', 'error');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonContent;
    });
}

function viewCustomerHistory(customerId) {
    window.location.href = `customer-credit-history.php?id=${Number(customerId)}`;
}
