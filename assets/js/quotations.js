// Quotations Management JavaScript

let quoteItems = [];
let products = [];
let customers = [];
let editingQuoteId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadQuotations();
    loadCustomers();
    loadProducts();
    loadStats();
    
    document.getElementById('newQuoteBtn').addEventListener('click', openQuoteModal);
    document.getElementById('quoteForm').addEventListener('submit', saveQuotation);
    document.getElementById('statusFilter').addEventListener('change', loadQuotations);
    document.getElementById('quoteDiscount').addEventListener('input', updateQuoteSummary);
    document.getElementById('productSearch').addEventListener('input', filterProductsForQuote);
    
    // Modal close
    document.querySelector('#quoteModal .close').addEventListener('click', closeQuoteModal);
});

function loadQuotations() {
    const status = document.getElementById('statusFilter').value;
    const url = status ? `api/get_quotations.php?status=${status}` : 'api/get_quotations.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayQuotations(data.quotations);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displayQuotations(quotations) {
    const tbody = document.querySelector('#quotationsTable tbody');
    
    if (quotations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No quotations found</td></tr>';
        return;
    }
    
    tbody.innerHTML = quotations.map(quote => `
        <tr>
            <td><strong>${quote.quote_number}</strong></td>
            <td>${quote.customer_name || 'Walk-in Customer'}</td>
            <td>${new Date(quote.created_at).toLocaleDateString()}</td>
            <td>${quote.valid_until ? new Date(quote.valid_until).toLocaleDateString() : 'N/A'}</td>
            <td><strong>KSh ${parseFloat(quote.total).toFixed(2)}</strong></td>
            <td><span class="badge badge-${getStatusColor(quote.status)}">${quote.status.toUpperCase()}</span></td>
            <td>
                <button class="btn btn-sm" onclick="viewQuote(${quote.id})" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                ${quote.status === 'pending' ? `
                    <button class="btn btn-sm btn-primary" onclick="convertToSale(${quote.id})" title="Convert to Sale">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                    <button class="btn btn-sm" onclick="editQuote(${quote.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
                <button class="btn btn-sm" onclick="downloadQuotePDF(${quote.id})" title="Download PDF">
                    <i class="fas fa-file-pdf"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'accepted': 'success',
        'rejected': 'danger',
        'converted': 'info'
    };
    return colors[status] || 'secondary';
}

function loadCustomers() {
    fetch('api/get_customers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customers = data.customers;
                const select = document.getElementById('quoteCustomer');
                select.innerHTML = '<option value="">Select Customer</option>';
                data.customers.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = `${customer.name} - ${customer.phone}`;
                    select.appendChild(option);
                });
            }
        });
}

function loadProducts() {
    fetch('api/get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                products = data.products;
            }
        });
}

function loadStats() {
    fetch('api/get_quotation_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalQuotes').textContent = data.stats.total || 0;
                document.getElementById('pendingQuotes').textContent = data.stats.pending || 0;
                document.getElementById('convertedQuotes').textContent = data.stats.converted || 0;
                document.getElementById('quoteValue').textContent = 'KSh ' + parseFloat(data.stats.total_value || 0).toFixed(2);
            }
        });
}

function openQuoteModal() {
    editingQuoteId = null;
    quoteItems = [];
    document.getElementById('quoteForm').reset();
    document.getElementById('modalTitle').textContent = 'New Quotation';
    
    // Set valid until to 30 days from now
    const validUntil = new Date();
    validUntil.setDate(validUntil.getDate() + 30);
    document.getElementById('validUntil').valueAsDate = validUntil;
    
    updateQuoteItemsDisplay();
    updateQuoteSummary();
    document.getElementById('quoteModal').classList.add('show');
}

function closeQuoteModal() {
    document.getElementById('quoteModal').classList.remove('show');
}

function filterProductsForQuote() {
    const search = document.getElementById('productSearch').value.toLowerCase();
    if (search.length < 2) return;
    
    const filtered = products.filter(p => 
        p.name.toLowerCase().includes(search) || 
        (p.sku && p.sku.toLowerCase().includes(search))
    );
    
    // Show dropdown with filtered products
    showProductDropdown(filtered);
}

function showProductDropdown(products) {
    let dropdown = document.getElementById('productDropdown');
    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.id = 'productDropdown';
        dropdown.style.cssText = 'position: absolute; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; width: 100%; z-index: 1000; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);';
        document.getElementById('productSearch').parentNode.appendChild(dropdown);
    }
    
    dropdown.innerHTML = products.map(p => `
        <div style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;" 
             onmouseover="this.style.background='#f5f5f5'" 
             onmouseout="this.style.background='white'"
             onclick="addProductToQuote(${p.id})">
            <strong>${p.name}</strong><br>
            <small>Stock: ${p.quantity} | Price: KSh ${parseFloat(p.selling_price).toFixed(2)}</small>
        </div>
    `).join('');
}

function addProductToQuote(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;
    
    // Check if already added
    const existing = quoteItems.find(item => item.product_id == productId);
    if (existing) {
        existing.quantity++;
        existing.total = existing.quantity * existing.unit_price;
    } else {
        quoteItems.push({
            product_id: product.id,
            product_name: product.name,
            quantity: 1,
            unit_price: parseFloat(product.selling_price),
            total: parseFloat(product.selling_price)
        });
    }
    
    updateQuoteItemsDisplay();
    updateQuoteSummary();
    document.getElementById('productSearch').value = '';
    document.getElementById('productDropdown').innerHTML = '';
}

function updateQuoteItemsDisplay() {
    const tbody = document.getElementById('quoteItems');
    
    if (quoteItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No items added</td></tr>';
        return;
    }
    
    tbody.innerHTML = quoteItems.map((item, index) => `
        <tr>
            <td>${item.product_name}</td>
            <td>
                <input type="number" value="${item.quantity}" min="1" 
                       onchange="updateQuoteItemQuantity(${index}, this.value)"
                       style="width: 80px; padding: 5px;">
            </td>
            <td>
                <input type="number" value="${item.unit_price}" step="0.01" min="0"
                       onchange="updateQuoteItemPrice(${index}, this.value)"
                       style="width: 100px; padding: 5px;">
            </td>
            <td><strong>KSh ${item.total.toFixed(2)}</strong></td>
            <td>
                <button type="button" class="btn btn-sm" onclick="removeQuoteItem(${index})" style="background: #e74c3c; color: white;">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function updateQuoteItemQuantity(index, quantity) {
    quoteItems[index].quantity = parseInt(quantity) || 1;
    quoteItems[index].total = quoteItems[index].quantity * quoteItems[index].unit_price;
    updateQuoteItemsDisplay();
    updateQuoteSummary();
}

function updateQuoteItemPrice(index, price) {
    quoteItems[index].unit_price = parseFloat(price) || 0;
    quoteItems[index].total = quoteItems[index].quantity * quoteItems[index].unit_price;
    updateQuoteItemsDisplay();
    updateQuoteSummary();
}

function removeQuoteItem(index) {
    quoteItems.splice(index, 1);
    updateQuoteItemsDisplay();
    updateQuoteSummary();
}

function updateQuoteSummary() {
    const subtotal = quoteItems.reduce((sum, item) => sum + item.total, 0);
    const discount = parseFloat(document.getElementById('quoteDiscount').value) || 0;
    const total = subtotal - discount;
    
    document.getElementById('quoteSubtotal').value = 'KSh ' + subtotal.toFixed(2);
    document.getElementById('quoteTotal').value = 'KSh ' + total.toFixed(2);
}

function saveQuotation(e) {
    e.preventDefault();
    
    if (quoteItems.length === 0) {
        showAlert('Please add at least one item', 'error');
        return;
    }
    
    const data = {
        customer_id: document.getElementById('quoteCustomer').value || null,
        valid_until: document.getElementById('validUntil').value,
        notes: document.getElementById('quoteNotes').value,
        discount: parseFloat(document.getElementById('quoteDiscount').value) || 0,
        items: quoteItems
    };
    
    fetch('api/create_quotation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Quotation created successfully!', 'success');
            closeQuoteModal();
            loadQuotations();
            loadStats();
        } else {
            showAlert(data.message || 'Error creating quotation', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error creating quotation', 'error');
    });
}

async function convertToSale(quoteId) {
    if (!(await appConfirm('Convert this quotation to a sale?'))) return;
    
    fetch('api/convert_quotation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({quotation_id: quoteId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Quotation converted to sale successfully!', 'success');
            loadQuotations();
            loadStats();
            
            appConfirm('View the sale receipt?', { okText: 'View receipt', icon: 'receipt' })
                .then(yes => { if (yes) window.open(`receipt.php?id=${data.sale_id}`, '_blank'); });
        } else {
            showAlert(data.message || 'Error converting quotation', 'error');
        }
    });
}

function downloadQuotePDF(quoteId) {
    window.open(`api/quotation_pdf.php?id=${quoteId}`, '_blank');
}

function viewQuote(quoteId) {
    window.open(`api/quotation_pdf.php?id=${quoteId}`, '_blank');
}
