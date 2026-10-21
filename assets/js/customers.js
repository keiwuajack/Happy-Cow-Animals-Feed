// Customers Management JavaScript
let customers = [];
let editingCustomerId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCustomers();
    
    // Event listeners
    document.getElementById('addCustomerBtn').addEventListener('click', openAddModal);
    document.getElementById('searchCustomer').addEventListener('input', filterCustomers);
    document.getElementById('filterCustomerType').addEventListener('change', filterCustomers);
    document.getElementById('filterCustomerStatus').addEventListener('change', filterCustomers);
    document.getElementById('customerForm').addEventListener('submit', saveCustomer);
    
    // Modal controls
    const modal = document.getElementById('customerModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelCustomer');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function loadCustomers() {
    fetch('api/get_customers.php?all=1')
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Could not load customers');
            customers = data.customers;
            filterCustomers();
        })
        .catch(error => {
            console.error('Error loading customers:', error);
            document.querySelector('#customersTable tbody').innerHTML = '<tr><td colspan="8" style="text-align:center">Could not load customers. Refresh the page or contact an administrator.</td></tr>';
            showAlert(error.message || 'Error loading customers', 'error');
        });
}

function displayCustomers(customersToDisplay) {
    const tbody = document.querySelector('#customersTable tbody');
    tbody.innerHTML = '';
    
    // Check if card view container exists, if not create it
    let cardView = document.getElementById('customerCardView');
    if (!cardView) {
        cardView = document.createElement('div');
        cardView.id = 'customerCardView';
        cardView.className = 'customer-card-view';
        cardView.style.display = 'none';
        document.querySelector('.table-responsive').parentNode.appendChild(cardView);
    }
    cardView.innerHTML = '';
    
    if (customersToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No customers found</td></tr>';
        cardView.innerHTML = '<p style="text-align: center; padding: 20px; color: #7f8c8d;">No customers found</p>';
        return;
    }
    
    customersToDisplay.forEach(customer => {
        // Table row for desktop
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${customer.name}</strong></td>
            <td>${customer.phone || 'N/A'}</td>
            <td>${customer.email || 'N/A'}</td>
            <td><span class="badge badge-info">${customer.customer_type}</span></td>
            <td>${customer.farm_size || 'N/A'}</td>
            <td>${formatCurrency(customer.credit_limit)}</td>
            <td><span class="badge badge-${customer.status}">${customer.status}</span></td>
            <td>
                <button class="btn-icon" onclick="editCustomer(${customer.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                ${window.canArchiveRecords && customer.status === 'active' ? `<button class="btn-icon text-danger" onclick="deleteCustomer(${customer.id})" title="Archive customer"><i class="fas fa-box-archive"></i></button>` : ''}
            </td>
        `;
        tbody.appendChild(row);
        
        // Card for mobile
        const card = document.createElement('div');
        card.className = 'customer-card';
        card.innerHTML = `
            <div class="customer-card-header">
                <div class="customer-card-name">${customer.name}</div>
                <div class="customer-card-type">${customer.customer_type}</div>
            </div>
            <div class="customer-card-body">
                <div class="customer-card-item">
                    <div class="customer-card-label"><i class="fas fa-phone"></i> Phone</div>
                    <div class="customer-card-value">${customer.phone || 'N/A'}</div>
                </div>
                <div class="customer-card-item">
                    <div class="customer-card-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="customer-card-value">${customer.email || 'N/A'}</div>
                </div>
                <div class="customer-card-item">
                    <div class="customer-card-label"><i class="fas fa-seedling"></i> Farm Size</div>
                    <div class="customer-card-value">${customer.farm_size || 'N/A'}</div>
                </div>
                <div class="customer-card-item">
                    <div class="customer-card-label"><i class="fas fa-credit-card"></i> Credit Limit</div>
                    <div class="customer-card-value">${formatCurrency(customer.credit_limit)}</div>
                </div>
            </div>
            <div class="customer-card-actions">
                <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                ${window.canArchiveRecords && customer.status === 'active' ? `<button class="btn btn-sm" style="background: #e74c3c; color: white;" onclick="deleteCustomer(${customer.id})"><i class="fas fa-box-archive"></i> Archive</button>` : ''}
            </div>
        `;
        cardView.appendChild(card);
    });
    
    // Show appropriate view based on screen size
    updateViewMode();
}

function updateViewMode() {
    const cardView = document.getElementById('customerCardView');
    const table = document.getElementById('customersTable');
    
    if (window.innerWidth <= 768) {
        if (table) table.style.display = 'none';
        if (cardView) cardView.style.display = 'block';
    } else {
        if (table) table.style.display = 'table';
        if (cardView) cardView.style.display = 'none';
    }
}

// Update view on window resize
window.addEventListener('resize', updateViewMode);

function filterCustomers() {
    const searchTerm = document.getElementById('searchCustomer').value.toLowerCase();
    const customerType = document.getElementById('filterCustomerType').value;
    const status = document.getElementById('filterCustomerStatus').value;
    
    let filtered = customers;
    
    if (searchTerm) {
        filtered = filtered.filter(c => 
            c.name.toLowerCase().includes(searchTerm) || 
            (c.phone && c.phone.includes(searchTerm)) ||
            (c.email && c.email.toLowerCase().includes(searchTerm))
        );
    }
    
    if (customerType) {
        filtered = filtered.filter(c => c.customer_type === customerType);
    }

    if (status) {
        filtered = filtered.filter(c => c.status === status);
    }
    
    displayCustomers(filtered);
}

function openAddModal() {
    editingCustomerId = null;
    document.getElementById('modalTitle').textContent = 'Add Customer';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('customerModal').classList.add('show');
}

function editCustomer(id) {
    const customer = customers.find(c => c.id == id);
    if (!customer) return;
    
    editingCustomerId = id;
    document.getElementById('modalTitle').textContent = 'Edit Customer';
    document.getElementById('customerId').value = customer.id;
    document.getElementById('customerName').value = customer.name;
    document.getElementById('customerPhone').value = customer.phone || '';
    document.getElementById('customerEmail').value = customer.email || '';
    document.getElementById('customerType').value = customer.customer_type;
    document.getElementById('customerAddress').value = customer.address || '';
    document.getElementById('farmSize').value = customer.farm_size || '';
    document.getElementById('creditLimit').value = customer.credit_limit;
    document.getElementById('customerStatus').value = customer.status;
    
    document.getElementById('customerModal').classList.add('show');
}

function saveCustomer(e) {
    e.preventDefault();
    
    const customerData = {
        id: document.getElementById('customerId').value,
        name: document.getElementById('customerName').value,
        phone: document.getElementById('customerPhone').value,
        email: document.getElementById('customerEmail').value,
        customer_type: document.getElementById('customerType').value,
        address: document.getElementById('customerAddress').value,
        farm_size: document.getElementById('farmSize').value,
        credit_limit: document.getElementById('creditLimit').value,
        status: document.getElementById('customerStatus').value
    };
    
    fetch('api/save_customer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(customerData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('customerModal').classList.remove('show');
            loadCustomers();
        } else {
            showAlert(data.message || 'Error saving customer', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving customer', 'error');
    });
}

async function deleteCustomer(id) {
    if (!(await appConfirm('Archive this customer? Their past sales and account history will be kept.', {danger: true}))) {
        return;
    }
    
    fetch('api/delete_customer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadCustomers();
        } else {
            showAlert(data.message || 'Error deleting customer', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting customer', 'error');
    });
}
