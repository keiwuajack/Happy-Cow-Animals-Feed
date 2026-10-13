// Credit Sales Management
let creditCustomers = [];
let payments = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCreditCustomers();
    loadPayments();
    loadStats();
    
    document.getElementById('recordPaymentBtn').addEventListener('click', openPaymentModal);
    document.getElementById('paymentForm').addEventListener('submit', recordPayment);
    document.getElementById('customerSearch').addEventListener('input', filterCustomers);
    document.getElementById('paymentCustomer').addEventListener('change', showCustomerBalance);
    
    document.querySelector('#paymentModal .close').addEventListener('click', closePaymentModal);
});

function loadCreditCustomers() {
    fetch('api/get_credit_customers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                creditCustomers = data.customers;
                displayCreditCustomers(data.customers);
                populateCustomerSelect(data.customers);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayCreditCustomers(customers) {
    const tbody = document.querySelector('#creditCustomersTable tbody');
    
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No credit customers found</td></tr>';
        return;
    }
    
    tbody.innerHTML = customers.map(customer => {
        const available = parseFloat(customer.credit_limit || 0) - parseFloat(customer.credit_balance || 0);
        const limitColor = available < 0 ? '#e74c3c' : '#2ecc71';
        
        return `
            <tr>
                <td><strong>${customer.name}</strong></td>
                <td>${customer.phone}</td>
                <td>KSh ${parseFloat(customer.credit_limit || 0).toFixed(2)}</td>
                <td><strong style="color: ${customer.credit_balance > 0 ? '#e74c3c' : '#2ecc71'}">
                    KSh ${parseFloat(customer.credit_balance || 0).toFixed(2)}
                </strong></td>
                <td style="color: ${limitColor}"><strong>KSh ${available.toFixed(2)}</strong></td>
                <td>${customer.last_payment || 'Never'}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="quickPayment(${customer.id})">
                        <i class="fas fa-money-bill"></i>
                    </button>
                    <button class="btn btn-sm" onclick="viewCustomerHistory(${customer.id})">
                        <i class="fas fa-history"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function populateCustomerSelect(customers) {
    const select = document.getElementById('paymentCustomer');
    select.innerHTML = '<option value="">Select Customer</option>';
    
    customers.forEach(customer => {
        if (parseFloat(customer.credit_balance) > 0) {
            select.innerHTML += `<option value="${customer.id}">${customer.name} - Balance: KSh ${parseFloat(customer.credit_balance).toFixed(2)}</option>`;
        }
    });
}

function loadPayments() {
    fetch('api/get_credit_payments.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPayments(data.payments);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayPayments(payments) {
    const tbody = document.querySelector('#paymentsTable tbody');
    
    if (payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No payments recorded</td></tr>';
        return;
    }
    
    tbody.innerHTML = payments.slice(0, 20).map(payment => `
        <tr>
            <td>${new Date(payment.payment_date).toLocaleString()}</td>
            <td>${payment.customer_name}</td>
            <td><strong>KSh ${parseFloat(payment.amount).toFixed(2)}</strong></td>
            <td><span class="badge">${payment.payment_method.toUpperCase()}</span></td>
            <td>${payment.reference_number || '-'}</td>
            <td>${payment.recorded_by_name}</td>
        </tr>
    `).join('');
}

function loadStats() {
    fetch('api/get_credit_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalCreditCustomers').textContent = data.stats.total_customers || 0;
                document.getElementById('totalOutstanding').textContent = 'KSh ' + parseFloat(data.stats.total_outstanding || 0).toFixed(2);
                document.getElementById('totalCreditLimit').textContent = 'KSh ' + parseFloat(data.stats.total_limit || 0).toFixed(2);
                document.getElementById('paymentsToday').textContent = 'KSh ' + parseFloat(data.stats.payments_today || 0).toFixed(2);
            }
        });
}

function filterCustomers() {
    const search = document.getElementById('customerSearch').value.toLowerCase();
    const filtered = creditCustomers.filter(c => 
        c.name.toLowerCase().includes(search) || 
        c.phone.includes(search)
    );
    displayCreditCustomers(filtered);
}

function openPaymentModal() {
    document.getElementById('paymentForm').reset();
    document.getElementById('customerBalance').style.display = 'none';
    document.getElementById('paymentModal').classList.add('show');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('show');
}

function showCustomerBalance() {
    const customerId = document.getElementById('paymentCustomer').value;
    if (!customerId) {
        document.getElementById('customerBalance').style.display = 'none';
        return;
    }
    
    const customer = creditCustomers.find(c => c.id == customerId);
    if (customer) {
        document.getElementById('balanceAmount').textContent = 'KSh ' + parseFloat(customer.credit_balance).toFixed(2);
        document.getElementById('limitAmount').textContent = 'KSh ' + parseFloat(customer.credit_limit).toFixed(2);
        document.getElementById('customerBalance').style.display = 'block';
        
        // Set max payment amount
        document.getElementById('paymentAmount').max = customer.credit_balance;
    }
}

function quickPayment(customerId) {
    openPaymentModal();
    document.getElementById('paymentCustomer').value = customerId;
    showCustomerBalance();
}

function recordPayment(e) {
    e.preventDefault();
    
    const data = {
        customer_id: document.getElementById('paymentCustomer').value,
        amount: parseFloat(document.getElementById('paymentAmount').value),
        payment_method: document.getElementById('paymentMethodSelect').value,
        reference_number: document.getElementById('paymentReference').value,
        notes: document.getElementById('paymentNotes').value
    };
    
    if (!data.customer_id) {
        showAlert('Please select a customer', 'error');
        return;
    }
    
    if (data.amount <= 0) {
        showAlert('Please enter a valid amount', 'error');
        return;
    }
    
    fetch('api/record_credit_payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Payment recorded successfully!', 'success');
            closePaymentModal();
            loadCreditCustomers();
            loadPayments();
            loadStats();
        } else {
            showAlert(data.message || 'Error recording payment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error recording payment', 'error');
    });
}

function viewCustomerHistory(customerId) {
    window.location.href = `customer-credit-history.php?id=${customerId}`;
}
