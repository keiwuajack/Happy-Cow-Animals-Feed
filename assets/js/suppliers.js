// Suppliers Management JavaScript
let suppliers = [];

document.addEventListener('DOMContentLoaded', function() {
    loadSuppliers();
    
    // Event listeners
    document.getElementById('addSupplierBtn').addEventListener('click', openAddModal);
    document.getElementById('searchSupplier').addEventListener('input', filterSuppliers);
    document.getElementById('supplierForm').addEventListener('submit', saveSupplier);
    
    // Modal controls
    const modal = document.getElementById('supplierModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelSupplier');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function loadSuppliers() {
    fetch('api/get_suppliers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                suppliers = data.suppliers;
                displaySuppliers(suppliers);
            }
        })
        .catch(error => console.error('Error loading suppliers:', error));
}

function displaySuppliers(suppliersToDisplay) {
    const tbody = document.querySelector('#suppliersTable tbody');
    tbody.innerHTML = '';
    
    if (suppliersToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No suppliers found</td></tr>';
        return;
    }
    
    suppliersToDisplay.forEach(supplier => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${supplier.name}</strong></td>
            <td>${supplier.contact_person || 'N/A'}</td>
            <td>${supplier.phone || 'N/A'}</td>
            <td>${supplier.email || 'N/A'}</td>
            <td>${supplier.address || 'N/A'}</td>
            <td><span class="badge badge-${supplier.status}">${supplier.status}</span></td>
            <td>
                <button class="btn-icon" onclick="editSupplier(${supplier.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon text-danger" onclick="deleteSupplier(${supplier.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function filterSuppliers() {
    const searchTerm = document.getElementById('searchSupplier').value.toLowerCase();
    
    let filtered = suppliers;
    
    if (searchTerm) {
        filtered = filtered.filter(s => 
            s.name.toLowerCase().includes(searchTerm) || 
            (s.contact_person && s.contact_person.toLowerCase().includes(searchTerm)) ||
            (s.phone && s.phone.includes(searchTerm))
        );
    }
    
    displaySuppliers(filtered);
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    document.getElementById('supplierModal').classList.add('show');
}

function editSupplier(id) {
    const supplier = suppliers.find(s => s.id == id);
    if (!supplier) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('supplierId').value = supplier.id;
    document.getElementById('supplierName').value = supplier.name;
    document.getElementById('contactPerson').value = supplier.contact_person || '';
    document.getElementById('supplierPhone').value = supplier.phone || '';
    document.getElementById('supplierEmail').value = supplier.email || '';
    document.getElementById('supplierAddress').value = supplier.address || '';
    document.getElementById('supplierStatus').value = supplier.status;
    
    document.getElementById('supplierModal').classList.add('show');
}

function saveSupplier(e) {
    e.preventDefault();
    
    const supplierData = {
        id: document.getElementById('supplierId').value,
        name: document.getElementById('supplierName').value,
        contact_person: document.getElementById('contactPerson').value,
        phone: document.getElementById('supplierPhone').value,
        email: document.getElementById('supplierEmail').value,
        address: document.getElementById('supplierAddress').value,
        status: document.getElementById('supplierStatus').value
    };
    
    fetch('api/save_supplier.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(supplierData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('supplierModal').classList.remove('show');
            loadSuppliers();
        } else {
            showAlert(data.message || 'Error saving supplier', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving supplier', 'error');
    });
}

async function deleteSupplier(id) {
    if (!(await appConfirm('Are you sure you want to delete this supplier?', {danger: true}))) {
        return;
    }
    
    fetch('api/delete_supplier.php', {
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
            loadSuppliers();
        } else {
            showAlert(data.message || 'Error deleting supplier', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting supplier', 'error');
    });
}
