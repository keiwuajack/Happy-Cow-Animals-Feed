// Products Management JavaScript
let products = [];
let editingProductId = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Products page loaded');
    
    loadProducts();
    loadCategories();
    
    // Event listeners
    const addBtn = document.getElementById('addProductBtn');
    if (addBtn) {
        addBtn.addEventListener('click', openAddModal);
        console.log('Add button listener attached');
    } else {
        console.error('Add Product button not found!');
    }
    
    document.getElementById('searchProduct').addEventListener('input', filterProducts);
    document.getElementById('filterCategory').addEventListener('change', filterProducts);
    document.getElementById('filterStatus').addEventListener('change', filterProducts);
    
    const form = document.getElementById('productForm');
    if (form) {
        form.addEventListener('submit', saveProduct);
        console.log('Form submit listener attached');
    } else {
        console.error('Product form not found!');
    }
    
    // Modal controls
    const modal = document.getElementById('productModal');
    if (modal) {
        const closeBtn = modal.querySelector('.close');
        const cancelBtn = document.getElementById('cancelProduct');
        
        if (closeBtn) closeBtn.onclick = () => modal.classList.remove('show');
        if (cancelBtn) cancelBtn.onclick = () => modal.classList.remove('show');
        
        window.onclick = (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        };
        console.log('Modal controls attached');
    } else {
        console.error('Product modal not found!');
    }
});

function loadProducts() {
    fetch('api/get_products.php?all=1')
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Could not load products');
            products = data.products;
            filterProducts();
        })
        .catch(error => {
            console.error('Error loading products:', error);
            document.querySelector('#productsTable tbody').innerHTML = '<tr><td colspan="9" style="text-align:center">Could not load products. Refresh the page or contact an administrator.</td></tr>';
            showAlert(error.message || 'Error loading products', 'error');
        });
}

function loadCategories() {
    fetch('api/get_categories.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const selects = [
                    document.getElementById('filterCategory'),
                    document.getElementById('productCategory')
                ];
                
                data.categories.forEach(cat => {
                    selects.forEach(select => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.name;
                        if (select.id !== 'filterCategory') {
                            select.appendChild(option);
                        } else {
                            select.appendChild(option);
                        }
                    });
                });
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

function displayProducts(productsToDisplay) {
    const tbody = document.querySelector('#productsTable tbody');
    tbody.innerHTML = '';
    
    if (productsToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">No products found</td></tr>';
        return;
    }
    
    productsToDisplay.forEach(product => {
        const isLowStock = product.quantity <= product.reorder_level;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.sku || 'N/A'}</td>
            <td>${product.image ? `<img src="${encodeURI(product.image)}" alt="" style="width:28px;height:28px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:8px">` : ''}<strong>${product.name}</strong></td>
            <td>${product.category_name || 'N/A'}</td>
            <td>${formatCurrency(product.cost_price)}</td>
            <td>${formatCurrency(product.selling_price)}</td>
            <td class="${isLowStock ? 'text-danger' : ''}">${product.quantity}</td>
            <td>${product.unit}</td>
            <td><span class="badge badge-${product.status}">${product.status}</span></td>
            <td>
                <button class="btn-icon" onclick="editProduct(${product.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                ${window.canArchiveRecords && product.status === 'active' ? `<button class="btn-icon text-danger" onclick="deleteProduct(${product.id})" title="Archive product"><i class="fas fa-box-archive"></i></button>` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

function filterProducts() {
    const searchTerm = document.getElementById('searchProduct').value.toLowerCase();
    const categoryId = document.getElementById('filterCategory').value;
    const status = document.getElementById('filterStatus').value;
    
    let filtered = products;
    
    if (searchTerm) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchTerm) || 
            (p.sku && p.sku.toLowerCase().includes(searchTerm)) ||
            (p.barcode && p.barcode.toLowerCase().includes(searchTerm))
        );
    }
    
    if (categoryId) {
        filtered = filtered.filter(p => p.category_id == categoryId);
    }
    
    if (status) {
        filtered = filtered.filter(p => p.status === status);
    }
    
    displayProducts(filtered);
}

function setImagePreview(src) {
    const img = document.getElementById('productImagePreview');
    if (!img) return;
    img.src = src || '';
    img.style.display = src ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('productImage');
    if (input) {
        input.addEventListener('change', () => {
            const f = input.files[0];
            setImagePreview(f ? URL.createObjectURL(f) : '');
        });
    }
});

function openAddModal() {
    console.log('Opening add modal...');
    editingProductId = null;
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    setImagePreview('');
    const modal = document.getElementById('productModal');
    modal.classList.add('show');
    console.log('Modal opened, classes:', modal.className);
}

function editProduct(id) {
    const product = products.find(p => p.id == id);
    if (!product) return;
    
    editingProductId = id;
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('productSku').value = product.sku || '';
    document.getElementById('productBarcode').value = product.barcode || '';
    document.getElementById('productCategory').value = product.category_id || '';
    document.getElementById('productUnit').value = product.unit;
    document.getElementById('costPrice').value = product.cost_price;
    document.getElementById('sellingPrice').value = product.selling_price;
    document.getElementById('productStock').value = product.quantity;
    document.getElementById('reorderLevel').value = product.reorder_level;
    document.getElementById('productDescription').value = product.description || '';
    document.getElementById('productExpiry').value = product.expiry_date || '';
    document.getElementById('productStatus').value = product.status;
    document.getElementById('productImage').value = '';
    setImagePreview(product.image || '');
    
    document.getElementById('productModal').classList.add('show');
}

function saveProduct(e) {
    e.preventDefault();
    
    const productData = {
        id: document.getElementById('productId').value,
        name: document.getElementById('productName').value,
        sku: document.getElementById('productSku').value || '',
        barcode: document.getElementById('productBarcode').value.trim(),
        category_id: document.getElementById('productCategory').value,
        unit: document.getElementById('productUnit').value,
        cost_price: document.getElementById('costPrice').value,
        selling_price: document.getElementById('sellingPrice').value,
        quantity: document.getElementById('productStock').value,
        reorder_level: document.getElementById('reorderLevel').value,
        description: document.getElementById('productDescription').value,
        expiry_date: document.getElementById('productExpiry').value || null,
        status: document.getElementById('productStatus').value
    };
    
    console.log('Saving product:', productData);
    
    // Validate
    if (!productData.category_id) {
        showAlert('Please select a category', 'error');
        return;
    }
    
    fetch('api/save_product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(productData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(async data => {
        console.log('Server response:', data);
        if (data.success) {
            const imageFile = document.getElementById('productImage').files[0];
            let message = data.message;
            let type = 'success';
            if (imageFile) {
                try {
                    const form = new FormData();
                    form.append('product_id', data.id);
                    form.append('image', imageFile);
                    const up = await (await fetch('api/upload_product_image.php', { method: 'POST', body: form })).json();
                    if (!up.success) {
                        message += ' (photo not saved: ' + up.message + ')';
                        type = 'error';
                    }
                } catch (err) {
                    message += ' (photo upload failed)';
                    type = 'error';
                }
            }
            showAlert(message, type);
            document.getElementById('productModal').classList.remove('show');
            loadProducts();
        } else {
            showAlert(data.message || 'Error saving product', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving product', 'error');
    });
}

async function deleteProduct(id) {
    if (!(await appConfirm('Archive this product? It will be hidden from active inventory, while past sales are kept.', {danger: true}))) {
        return;
    }
    
    fetch('api/delete_product.php', {
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
            loadProducts();
        } else {
            showAlert(data.message || 'Error deleting product', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting product', 'error');
    });
}
