// Loyalty Points Management
let customers = [];
let settings = {
    points_per_amount: 100,
    points_value: 100,
    money_value: 10,
    points_expiry_months: 12,
    min_redemption: 100
};

document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
    loadCustomers();
    loadStats();
    
    // Event listeners
    document.getElementById('settingsBtn').addEventListener('click', openSettingsModal);
    document.getElementById('searchCustomer').addEventListener('input', filterCustomers);
    document.getElementById('settingsForm').addEventListener('submit', saveSettings);
    
    // Settings Modal
    const settingsModal = document.getElementById('settingsModal');
    const settingsClose = settingsModal.querySelector('.close');
    const settingsCancel = document.getElementById('cancelSettings');
    
    settingsClose.onclick = () => settingsModal.classList.remove('show');
    settingsCancel.onclick = () => settingsModal.classList.remove('show');
    
    // History Modal
    const historyModal = document.getElementById('historyModal');
    const historyClose = historyModal.querySelector('.close');
    
    historyClose.onclick = () => historyModal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === settingsModal) {
            settingsModal.classList.remove('show');
        }
        if (e.target === historyModal) {
            historyModal.classList.remove('show');
        }
    };
});

function loadSettings() {
    fetch('api/get_loyalty_settings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.settings) {
                settings = data.settings;
                updateSettingsDisplay();
            }
        })
        .catch(error => console.error('Error loading settings:', error));
}

function updateSettingsDisplay() {
    document.getElementById('displayPointsPerPurchase').textContent = 
        `1 point per KSh ${settings.points_per_amount}`;
    document.getElementById('displayRedemptionRate').textContent = 
        `${settings.points_value} points = KSh ${settings.money_value}`;
    document.getElementById('displayExpiry').textContent = 
        `${settings.points_expiry_months} months`;
    document.getElementById('displayMinRedemption').textContent = 
        `${settings.min_redemption} points`;
}

function loadCustomers() {
    fetch('api/get_loyalty_customers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customers = data.customers;
                displayCustomers(customers);
            }
        })
        .catch(error => console.error('Error loading customers:', error));
}

function loadStats() {
    fetch('api/get_loyalty_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('activeCustomers').textContent = data.active_customers || 0;
                document.getElementById('totalPoints').textContent = (data.total_points || 0).toLocaleString();
                document.getElementById('redeemedPoints').textContent = (data.redeemed_points || 0).toLocaleString();
                document.getElementById('pendingPoints').textContent = (data.pending_points || 0).toLocaleString();
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

function displayCustomers(customersToDisplay) {
    const tbody = document.querySelector('#customersTable tbody');
    tbody.innerHTML = '';
    
    if (customersToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No customers with loyalty points found</td></tr>';
        return;
    }
    
    customersToDisplay.forEach(customer => {
        const points = parseFloat(customer.loyalty_points) || 0;
        const lifetimePoints = parseFloat(customer.lifetime_points) || 0;
        const redeemedPoints = lifetimePoints - points;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${customer.name}</strong></td>
            <td>${customer.phone}</td>
            <td><span class="badge badge-primary">${points.toFixed(0)} pts</span></td>
            <td><span class="badge badge-info">${lifetimePoints.toFixed(0)} pts</span></td>
            <td><span class="badge badge-warning">${redeemedPoints.toFixed(0)} pts</span></td>
            <td>${formatDate(customer.created_at)}</td>
            <td>
                <button class="btn-icon" onclick="viewHistory(${customer.id}, '${customer.name}')" title="View History">
                    <i class="fas fa-history"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function filterCustomers() {
    const searchTerm = document.getElementById('searchCustomer').value.toLowerCase();
    
    if (!searchTerm) {
        displayCustomers(customers);
        return;
    }
    
    const filtered = customers.filter(c => 
        c.name.toLowerCase().includes(searchTerm) ||
        c.phone.includes(searchTerm)
    );
    
    displayCustomers(filtered);
}

function openSettingsModal() {
    document.getElementById('pointsPerAmount').value = settings.points_per_amount;
    document.getElementById('pointsValue').value = settings.points_value;
    document.getElementById('moneyValue').value = settings.money_value;
    document.getElementById('pointsExpiry').value = settings.points_expiry_months;
    document.getElementById('minRedemption').value = settings.min_redemption;
    
    document.getElementById('settingsModal').classList.add('show');
}

function saveSettings(e) {
    e.preventDefault();
    
    const settingsData = {
        points_per_amount: parseInt(document.getElementById('pointsPerAmount').value),
        points_value: parseInt(document.getElementById('pointsValue').value),
        money_value: parseInt(document.getElementById('moneyValue').value),
        points_expiry_months: parseInt(document.getElementById('pointsExpiry').value),
        min_redemption: parseInt(document.getElementById('minRedemption').value)
    };
    
    fetch('api/save_loyalty_settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(settingsData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('settingsModal').classList.remove('show');
            loadSettings();
        } else {
            showAlert(data.message || 'Error saving settings', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving settings', 'error');
    });
}

function viewHistory(customerId, customerName) {
    document.getElementById('historyCustomerName').textContent = customerName;
    document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="5" style="text-align: center;">Loading...</td></tr>';
    
    fetch(`api/get_points_history.php?customer_id=${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayHistory(data.history);
            } else {
                document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="5" style="text-align: center;">Error loading history</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="5" style="text-align: center;">Error loading history</td></tr>';
        });
    
    document.getElementById('historyModal').classList.add('show');
}

function displayHistory(history) {
    const tbody = document.getElementById('historyTableBody');
    tbody.innerHTML = '';
    
    if (history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No history found</td></tr>';
        return;
    }
    
    history.forEach(entry => {
        const row = document.createElement('tr');
        const pointsClass = entry.type === 'earned' ? 'text-success' : 'text-danger';
        const pointsSign = entry.type === 'earned' ? '+' : '-';
        
        row.innerHTML = `
            <td>${formatDateTime(entry.created_at)}</td>
            <td><span class="badge ${entry.type === 'earned' ? 'badge-success' : 'badge-danger'}">${entry.type}</span></td>
            <td class="${pointsClass}"><strong>${pointsSign}${parseFloat(entry.points).toFixed(0)}</strong></td>
            <td>${entry.description}</td>
            <td>${parseFloat(entry.balance_after).toFixed(0)} pts</td>
        `;
        tbody.appendChild(row);
    });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
