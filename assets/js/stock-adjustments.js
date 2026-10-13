// Stock Adjustments Management
let adjustments = [];
let products = [];
let selectedProduct = null;

document.addEventListener('DOMContentLoaded', function() {
    loadAdjustments();
    loadProducts();
    
    // Event listeners
    document.getElementById('addAdjustmentBtn').addEventListener('click', openAddModal);
    document.getElementById('searchAdjustment').addEventListener('input', filterAdjustments);
    document.getElementById('filterType').addEventListener('change', filterAdjustments);
    document.getElementById('filterDate').addEventListener('change', filterAdjustments);
    document.getElementById('adjustmentForm').addEventListener('submit', saveAdjustment);
    
    // Product selection change
    document.getElementById('productId').addEventListener('change', function() {
        const productId = this.value;
        if (productId) {
            const product = products.find(p => p.id == productId);
            if (product) {
                selectedProduct = product;
                document.getElementById('currentStock').value = product.quantity + ' ' + product.unit;
                updateNewStock();
            }
        } else {
            document.getElementById('currentStock').value = '';
            document.getElementById('newStock').value = '';
        }
    });
    
    // Quantity and type change
    document.getElementById('quantity').addEventListener('input', updateNewStock);
    document.getElementById('adjustmentType').addEventListener('change', updateNewStock);
    
    // Modal controls
    const modal = document.getElementById('adjustmentModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = document.getElementById('cancelAdjustment');
    
    closeBtn.onclick = () => modal.classList.remove('show');
    cancelBtn.onclick = () => modal.classList.remove('show');
    
    window.onclick = (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    };
});

function updateNewStock() {
    const type = document.getElementById('adjustmentType').value;
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    
    if (selectedProduct && quantity > 0) {
        let newQty = selectedProduct.quantity;
        
        if (type === 'add') {
            newQty += quantity;
        } else {
            newQty -= quantity;
        }
        
        document.getElementById('newStock').value = newQty + ' ' + selectedProduct.unit;
        
        if (newQty < 0) {
            document.getElementById('newStock').style.color = 'red';
        } else {
            document.getElementById('newStock').style.color = 'green';
        }
    }
}

function loadAdjustments() {
    fetch('api/get_stock_adjustments.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                adjustments = data.adjustments;
                displayAdjustments(adjustments);
            }
        })
        .catch(error => console.error('Error loading adjustments:', error));
}

function loadProducts() {
    fetch('api/get_products.php?all=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                products = data.products;
                const select = document.getElementById('productId');
                select.innerHTML = '<option value="">Select Product</option>';
                
                products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.name} (${product.quantity} ${product.unit})`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading products:', error));
}

function displayAdjustments(adjustmentsToDisplay) {
    const tbody = document.querySelector('#adjustmentsTable tbody');
    tbody.innerHTML = '';
    
    if (adjustmentsToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No adjustments found</td></tr>';
        return;
    }
    
    adjustmentsToDisplay.forEach(adj => {
        const row = document.createElement('tr');
        const typeClass = adj.adjustment_type === 'add' ? 'success' : 'danger';
        const typeIcon = adj.adjustment_type === 'add' ? 'plus' : 'minus';
        
        row.innerHTML = `
            <td>${formatDateTime(adj.created_at)}</td>
            <td><strong>${adj.product_name}</strong></td>
            <td><span class="badge badge-${typeClass}"><i class="fas fa-${typeIcon}"></i> ${adj.adjustment_type}</span></td>
            <td>${adj.quantity} ${adj.unit}</td>
            <td>${adj.previous_stock}</td>
            <td>${adj.new_stock}</td>
            <td>${adj.reason}</td>
            <td>${adj.adjusted_by_name}</td>
        `;
        tbody.appendChild(row);
    });
}

function filterAdjustments() {
    const searchTerm = document.getElementById('searchAdjustment').value.toLowerCase();
    const type = document.getElementById('filterType').value;
    const date = document.getElementById('filterDate').value;
    
    let filtered = adjustments;
    
    if (searchTerm) {
        filtered = filtered.filter(a => 
            a.product_name.toLowerCase().includes(searchTerm) || 
            a.reason.toLowerCase().includes(searchTerm)
        );
    }
    
    if (type) {
        filtered = filtered.filter(a => a.adjustment_type === type);
    }
    
    if (date) {
        filtered = filtered.filter(a => a.created_at.startsWith(date));
    }
    
    displayAdjustments(filtered);
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'New Stock Adjustment';
    document.getElementById('adjustmentForm').reset();
    document.getElementById('currentStock').value = '';
    document.getElementById('newStock').value = '';
    selectedProduct = null;
    document.getElementById('adjustmentModal').classList.add('show');
}

function saveAdjustment(e) {
    e.preventDefault();
    
    const adjustmentData = {
        product_id: document.getElementById('productId').value,
        adjustment_type: document.getElementById('adjustmentType').value,
        quantity: document.getElementById('quantity').value,
        reason: document.getElementById('reason').value
    };
    
    if (!adjustmentData.product_id) {
        showAlert('Please select a product', 'error');
        return;
    }
    
    fetch('api/save_stock_adjustment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(adjustmentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('adjustmentModal').classList.remove('show');
            loadAdjustments();
            loadProducts(); // Reload to update stock quantities
        } else {
            showAlert(data.message || 'Error saving adjustment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving adjustment', 'error');
    });
}

function formatDateTime(dateTimeStr) {
    const date = new Date(dateTimeStr);
    return date.toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
